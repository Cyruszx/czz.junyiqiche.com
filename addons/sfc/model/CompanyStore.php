<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/24
 * Time: 10:12
 */

namespace addons\sfc\model;


use think\Model;

class CompanyStore extends Model
{
    // 表名
    protected $name = 'company_store';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    protected $updateTime = 'updatetime';

    // 定义全局的查询范围
    protected function base($query)
    {
        $query->where('statuss','normal');
    }

    public function distribution(){
        return $this->hasMany('Distribution','store_id','id')->field('id,store_id,level_store_id,earnings');
    }

    public function sondistribution()
    {
        return $this->hasMany('Distribution','level_store_id','id')->field('id,store_id,level_store_id,earnings');
    }


}