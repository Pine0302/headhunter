<?php

namespace app\admin\model;

use think\Model;

class BbsComplain extends Model
{
    // 表名
    protected $table = 'bbs_complain';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

    ];

    public function bbsMember()
    {
        return $this->belongsTo('bbsMember', 'bbs_member_id','id','','left')->setEagerlyType(0);
    }

    public function bbsPost()
    {
        return $this->belongsTo('bbsPost', 'bbs_post_id','id','','left')->setEagerlyType(0);
    }
    







}
