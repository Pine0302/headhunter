<?php

namespace app\admin\model;

use think\Model;

class BbsReply extends Model
{
    // 表名
    protected $table = 'bbs_reply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text',
        'is_read_text',
        'add_time_text',
        'update_time_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1')];
    }     

    public function getIsReadList()
    {
        return ['1' => __('Is_read 1')];
    }     


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsReadTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['is_read'];
        $list = $this->getIsReadList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getAddTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['add_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getUpdateTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['update_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setAddTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }

    protected function setUpdateTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


    public function bbsMember()
    {
        return $this->belongsTo('bbs_member','bbs_member_id','id','','left')->setEagerlyType(0);
    }

    public function bbsMemberFor()
    {
        return $this->belongsTo('bbs_member','member_for','id','','left')->setEagerlyType(0);
    }

    public function bbsPost()
    {
        return $this->belongsTo('bbs_post','bbs_post_id','id','','left')->setEagerlyType(0);
    }

}
