<?php

namespace app\admin\model;

use think\Model;

class ReCompaccountdetail extends Model
{
    // 表名
    protected $table = 're_compaccountdetail';
    
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









}
