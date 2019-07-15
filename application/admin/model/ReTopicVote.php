<?php

namespace app\admin\model;

use think\Model;

class ReTopicVote extends Model
{
    // 表名
    protected $table = 're_topic_vote';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];
    public function reTopic()
    {
        return $this->belongsTo('reTopic','re_topic_id','id','','left')->setEagerlyType(0);
    }

    public function user()
    {
        return $this->belongsTo('user','user_id','id','','left')->setEagerlyType(0);
    }










}
