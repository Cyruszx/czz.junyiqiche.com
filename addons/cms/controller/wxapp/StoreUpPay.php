<?php
/**
 * Created by PhpStorm.
 * User: glen9
 * Date: 2019/3/8
 * Time: 14:31
 */

namespace addons\cms\controller\wxapp;

use addons\cms\model\CompanyStore;
use addons\cms\model\EarningDetailed;
use addons\cms\model\FormIds;
use addons\cms\model\PayOrder;
use addons\cms\model\Distribution;
use addons\cms\model\StoreLevel;
use think\Cache;
use think\Config;
use Think\Db;
use app\common\library\Auth;
use addons\cms\model\Config as ConfigModel;
use think\Env;
use think\Loader;
use think\Exception;

Loader::import('WxPay.WxPay', EXTEND_PATH, '.Api.php');
Loader::import('WxPay.WxPay', EXTEND_PATH, '.Notify.php');


class StoreUpPay extends Base
{
    protected $noNeedLogin = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 店铺升级支付
     * @return \成功时返回，其他抛异常
     * @throws \WxPayException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */

    public function upShop()
    {
        $user_id = $this->request->post('user_id');
        $store_id = $this->request->post('store_id');
        $up_level_id = $this->request->post('up_level_id');
        $formId = $this->request->post('formId');
        $out_trade_no = $this->request->post('out_trade_no');
        $base_level_id = $this->request->post('base_level_id');
        $bas = Common::getLevelStoreName($base_level_id)->partner_rank;
        //写入formIds表
        Common::writeFormId($formId, $user_id);
        $openid = Common::getOpenid($user_id);
        $money = (floatval(self::getLevelStoreName($up_level_id)->money) - floatval(self::getLevelStoreName($base_level_id)->money)) * 100;

//        $money = 0.01 * 100;
        //     初始化值对象
        $input = new \WxPayUnifiedOrder();
        //     文档提及的参数规范：商家名称-销售商品类目
        $input->SetBody("友车圈认证店铺升级");
        //     订单号应该是由小程序端传给服务端的，在用户下单时即生成，demo中取值是一个生成的时间戳
        $input->SetOut_trade_no("$out_trade_no");
        //     费用应该是由小程序端传给服务端的，在用户下单时告知服务端应付金额，demo中取值是1，即1分钱
        $input->SetTotal_fee("$money");
        $input->SetNotify_url($_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . '/addons/cms/wxapp.store_up_pay/up_wxPay_noTify');
        $input->SetTrade_type("JSAPI");
        //     由小程序端传给服务端
        $input->SetOpenid($openid);
        //     向微信统一下单，并返回order，它是一个array数组
        $order = \WxPayApi::unifiedOrder($input);
        if ($order['result_code'] == 'SUCCESS') {
            $order['key'] = Env::get('wxpay.key');
            $order['appid'] = Config::get('oauth')['appid'];
            return $order;
//            $this->success('预支付successful', $order);
        }
        $this->error('签名失败', $order);

    }

    /**
     * 店铺升级支付后回调
     */
    public function up_wxPay_noTify()
    {
        $res = file_get_contents("php://input");
        $getData = xmlstr_to_array($res);
        if (($getData['total_fee']) && ($getData['result_code'] == 'SUCCESS')) {  //支付回调通知成功
            //将回调通知里的订单号前缀user_id +store_id 分隔
            $user_id = explode('_', $getData['out_trade_no'])[0]; //获取user_id
            $store_id = explode('_', $getData['out_trade_no'])[1]; //获取门店id
            $level_id = explode('_', $getData['out_trade_no'])[2]; //获取需要升级的id
            $getData['out_trade_no'] = explode('_', $getData['out_trade_no'])[3]; //获取订单号

            Db::startTrans();
            try {
                $res = PayOrder::create(
                    ['out_trade_no' => $getData['out_trade_no'],
                        'store_id' => $store_id,
                        'user_id' => $user_id,
                        'time_end' => $getData['time_end'],
                        'total_fee' => $getData['total_fee'] / 100,
                        'trade_type' => $getData['trade_type'],
                        'bank_type' => $getData['bank_type'],
                        'transaction_id' => $getData['transaction_id'],
                        'pay_type' => 'up',
                        'level_id' => $level_id
                    ]
                );
                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                echo exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>');

            }
            echo exit('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>');

        } else {
            echo exit('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>');

        }

    }


    /**
     * 店铺升级支付成功后接口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function after_successful_payment()
    {
        $user_id = $this->request->post('user_id');
        $store_id = $this->request->post('store_id');
        $up_level_id = $this->request->post('up_level_id');
        $formId = $this->request->post('formId');
        $out_trade_no = $this->request->post('out_trade_no');
        $base_level_id = $this->request->post('base_level_id');
        $store_name = $this->request->post('store_name');
        if (!$user_id || !$store_id || !$up_level_id || !$formId || !$out_trade_no || !$base_level_id) $this->error('缺少参数');
        //写入formIds表
        $openid = Common::getOpenid($user_id);
        Db::startTrans();
        try {
            $formId = Common::getFormId($user_id); //获取formId
            //修改店铺等级为 升级后的level
            CompanyStore::where(['user_id' => $user_id, 'id' => $store_id])->update(['level_id' => $up_level_id]);

            PayOrder::where(['store_id' => $store_id, 'user_id' => $user_id, 'out_trade_no' => explode('_', $out_trade_no)[2]])->update(['level_id' => $up_level_id]);
            if ($openid && $formId) {
                $o = Common::getLevelStoreName($base_level_id)->partner_rank;
                $keyword2 = Common::getLevelStoreName($up_level_id)->partner_rank;
                $newKey = $keyword2 . "（原{$o}）";
                $temp_msg = array(
                    'touser' => "{$openid}",
                    'template_id' => "-pD8LYQSrGITNoQU45yHS-aXtwfFzcpOXuOaWf_2Jso",
                    'page' => "/pages/mine/mine",
                    'form_id' => "{$formId}",
                    'data' => array(
                        'keyword1' => array(
                            'value' => "{$store_name}",
                            'color' => '#FF5722'
                        ),
                        'keyword2' => array(
                            'value' => "{$newKey}",
                        ),
                        'keyword3' => array(
                            'value' => "升级{$keyword2}成功",
                        ),
                        'keyword4' => array(
                            'value' => date('Y-m-d H:i:s', time()),
                        ),

                    ),
                );
                $res = Common::sendXcxTemplateMsg(json_encode($temp_msg));
                if ($res['errcode'] == 0) {
                    FormIds::where(['user_id' => $user_id, 'form_id' => $formId])->delete();
                }
            }
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage(), '');
        }
        $this->success('升级成功！');

    }

}