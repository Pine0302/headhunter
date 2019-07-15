<?php

namespace app\admin\model;

use think\Model;

class CashLog extends Model
{
    // 表名
    protected $table = 'cash_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function reCompany()
    {
        return $this->belongsTo('reCompany', 're_company_id','id','','left')->setEagerlyType(0);
    }

    public function reApplyCompany()
    {
        return $this->belongsTo('reCompany', 'apply_company_id','id','','left')->setEagerlyType(0);
    }

    public function applyUser()
    {
        return $this->belongsTo('user', 'apply_user_id','id','','left')->setEagerlyType(0);
    }

    public function user()
    {
        return $this->belongsTo('user', 'user_id','id','','left')->setEagerlyType(0);
    }

    public function training()
    {
        return $this->belongsTo('re_training', 're_training_id','id','','left')->setEagerlyType(0);
    }









}
