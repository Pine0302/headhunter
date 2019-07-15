<?php

namespace app\admin\model;

use think\Model;

class ReRecord extends Model
{
    // 表名
    protected $table = 're_record';
    
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

    public function reJob(){
        return $this->belongsTo('reJob','re_job_id','id','','left')->setEagerlyType(0);
    }

    







}
