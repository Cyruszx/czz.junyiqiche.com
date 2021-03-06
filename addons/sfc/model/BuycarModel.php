<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/29
 * Time: 11:13
 */

namespace addons\sfc\model;


use think\Model;

class BuycarModel extends Model
{
    protected $name = 'buycar_model';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    public function setKilometresAttr($value)
    {
        return floatval(findNum($value))*10000;
    }
    public function setPsychologyPriceAttr($value)
    {
        return floatval(findNum($value))*10000;
    }
}