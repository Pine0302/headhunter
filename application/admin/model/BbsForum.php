<?php

namespace app\admin\model;

use think\Model;

class BbsForum extends Model
{
    // 表名
    protected $table = 'bbs_forum';
    
    // 自动写入时间戳字段
   // protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
   // protected $createTime = 'createtime';
 //   protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'type_text',
    ];
    

    
    public function getTypeList()
    {
        return ['2' => __('Type 2')];
    }     


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

/*
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
    }*/


    public function bbsForumtype()
    {
        return $this->hasOne('bbsForumtype','id','bbs_forumtype_id','','left');
    }



}
