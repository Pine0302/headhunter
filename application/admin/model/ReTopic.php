<?php

namespace app\admin\model;

use think\Model;

class ReTopic extends Model
{
    // 表名
    protected $table = 're_topic';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'end_time_text',
        'result_text'
    ];
    

    
    public function getResultList()
    {
        return ['1' => __('Result 1')];
    }     


    public function getEndTimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['end_time'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getResultTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['result'];
        $list = $this->getResultList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setEndTimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }


}
