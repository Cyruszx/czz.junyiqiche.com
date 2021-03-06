<?php

namespace app\admin\model;

use think\Model;

class ModelsInfo extends Model
{
    // 表名
    protected $name = 'models_info';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'car_licensetime_text',
        'factorytime_text'
    ];

    public function getCarLicensetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['car_licensetime']) ? $data['car_licensetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getFactorytimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['factorytime']) ? $data['factorytime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setCarLicensetimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setFactorytimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    //关联user
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    //关联店铺
    public function store()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }

    //关联品牌
    public function brand()
    {
        return $this->belongsTo('Brand', 'brand_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
