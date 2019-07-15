<?php

namespace app\admin\model;

use think\Model;

class CoTip extends Model
{
    // 表名
    protected $table = 'co_tip';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function user(){
        return $this->belongsTo('user','user_id','id','','left')->setEagerlyType(0);
    }
    public function coCircle(){
        return $this->belongsTo('coCircle','co_circle_id','id','','left')->setEagerlyType(0);
    }



    /*public function coType()
    {
        return $this->hasOne('coType','id','co_type_id','','left');
    }*/

    







}
