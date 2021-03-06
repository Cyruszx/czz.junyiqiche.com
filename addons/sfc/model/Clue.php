<?php
/**
 * Created by PhpStorm.
 * User: EDZ
 * Date: 2019/1/29
 * Time: 17:45
 */

namespace addons\sfc\model;


use think\Model;

class Clue extends Model
{
    protected $name = 'clue';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'createtime';

    protected $updateTime = 'updatetime';

    public function setKilometresAttr($value)
    {
        return floatval(findNum($value))*10000;
    }

    public function setGuidePriceAttr($value)
    {
        return floatval(findNum($value))*10000;
    }

    public function setFactoryTimeAttr($value)
    {
        return strtotime($value);
    }

    public function setBrandtimeAttr($value)
    {
        return strtotime($value);
    }
}