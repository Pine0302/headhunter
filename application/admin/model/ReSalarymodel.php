<?php

namespace app\admin\model;

use think\Model;

class ReSalarymodel extends Model
{
    // 表名
    protected $table = 're_salarymodel';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function reCompany(){
        return $this->belongsTo('re_company','re_company_id','id','','left')->setEagerlyType(0);
    }
    






}
