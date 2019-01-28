<?php

namespace app\admin\model;

use think\Model;

class StoreUser extends Model
{
    // 表名
    protected $name = 'store_user';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    
    // 追加属性
    protected $append = [
        'sex_text'
    ];
    

    
    public function getSexList()
    {
        return ['0' => __('Sex 0'),'1' => __('Sex 1')];
    }     


    public function getSexTextAttr($value, $data)
    {        
        $value = $value ? $value : (isset($data['sex']) ? $data['sex'] : '');
        $list = $this->getSexList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function store()
    {
        return $this->belongsTo('CompanyStore', 'store_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
