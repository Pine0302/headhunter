<?php

namespace app\admin\model;

use think\Model;

class ReRectraindetail extends Model
{
    // 表名
    protected $table = 're_rectraindetail';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $relationSearch = true;
    
    // 追加属性
    protected $append = [

    ];

    public function recUser()
    {
        return $this->belongsTo('user', 'up_user_id','id','','left')->setEagerlyType(0);
    }
    public function user()
    {
        return $this->belongsTo('user', 'low_user_id','id','','left')->setEagerlyType(0);
    }
    public function reCompany()
    {
        return $this->belongsTo('re_company', 're_company_id','id','','left')->setEagerlyType(0);
    }
    public function reTraining()
    {
        return $this->belongsTo('reTraining', 're_training_id','id','','left')->setEagerlyType(0);
    }
    














}
