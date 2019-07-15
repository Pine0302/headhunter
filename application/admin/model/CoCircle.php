<?php

namespace app\admin\model;

use think\Model;

class CoCircle extends Model
{
    // 表名
    protected $table = 'co_circle';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];


    public function coType()
    {
        return $this->hasOne('coType','id','co_type_id','','left');
    }







}
