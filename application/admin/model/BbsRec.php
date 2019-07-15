<?php

namespace app\admin\model;

use think\Model;

class BbsRec extends Model
{
    // 表名
    protected $table = 'bbs_rec';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'add_time_text',
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1')];
    }     


    public function getAddTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['add_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setAddTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


/*    public function bbsPost()
    {
        return $this->hasOne('bbsPost','id','bbs_post_id','','left');
    }*/

    public function bbsPost()
    {
        return $this->belongsTo('bbsPost','bbs_post_id','id','','left');
    }




}
