<?php

namespace app\admin\model;

use think\Model;

class ReRatio extends Model
{
    // 表名
    protected $table = 're_ratio';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function admin()
    {
        return $this->belongsTo('admin','uid','id','','left')->setEagerlyType(0);
    }

    







}
