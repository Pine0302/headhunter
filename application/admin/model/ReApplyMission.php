<?php

namespace app\admin\model;

use think\Model;

class ReApplyMission extends Model
{
    // 表名
    protected $table = 're_apply_mission';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'hour_status_text'
    ];
    

    
    public function getHourStatusList()
    {
        return ['1' => __('Hour_status 1')];
    }     


    public function getHourStatusTextAttr($value, $data)
    {        
        /*$value = $value ? $value : $data['hour_status'];
        $list = $this->getHourStatusList();
        return isset($list[$value]) ? $list[$value] : '';*/
    }

    public function user()
    {
        return $this->hasOne('user','id','user_id','','left');
    }

    public function hr()
    {
        return $this->hasOne('user','id','hr_id','','left');
    }

    public function project()
    {
        return $this->hasOne('re_project','id','re_project_id','','left');
    }


}
