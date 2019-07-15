<?php

namespace app\admin\model;

use think\Model;

class ReTraining extends Model
{
    // 表名
    protected $table = 're_training';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function reTraintype()
    {
        return $this->belongsTo('reTraintype', 're_traintype_id','id','','left')->setEagerlyType(0);
    }
    

    







}
