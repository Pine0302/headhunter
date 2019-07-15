<?php

namespace app\admin\model;

use think\Model;

class ReCompany extends Model
{
    // 表名
    protected $table = 're_company';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function admin()
    {
        return $this->belongsTo('admin', 'admin_id','id','','left')->setEagerlyType(0);
        // return $this->belongsTo('userTeam', 'id','low_user_id','','left')->setEagerlyType(0);
    }

    public function user()
    {
        return $this->belongsTo('user', 'user_id','id','','left')->setEagerlyType(0);
        // return $this->belongsTo('userTeam', 'id','low_user_id','','left')->setEagerlyType(0);
    }

    public function reLine()
    {
        return $this->belongsTo('re_line', 're_line_id','id','','left')->setEagerlyType(0);
        // return $this->belongsTo('userTeam', 'id','low_user_id','','left')->setEagerlyType(0);
    }







}
