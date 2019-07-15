<?php

namespace app\admin\model;

use think\Model;

class UserTraining extends Model
{
    // 表名
    protected $table = 'user_training';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function user()
    {
        return $this->belongsTo('User', 'user_id','id','','left')->setEagerlyType(0);;
    }

    public function reTraining()
    {
        return $this->belongsTo('re_training', 're_training_id','id','','left')->setEagerlyType(0);;
    }









}
