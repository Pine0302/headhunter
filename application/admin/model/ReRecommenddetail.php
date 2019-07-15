<?php

namespace app\admin\model;

use think\Model;

class ReRecommenddetail extends Model
{
    // 表名
    protected $table = 're_recommenddetail';
    
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
        return $this->belongsTo('user', 'low_user_id','id','','left')->setEagerlyType(0);
    }

    public function rec_user()
    {
        return $this->belongsTo('user', 'rec_user_id','id','','left')->setEagerlyType(0);
    }

    public function up_user()
    {
        return $this->belongsTo('user', 'up_user_id','id','','left')->setEagerlyType(0);
    }







}
