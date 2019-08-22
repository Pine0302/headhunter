<?php

namespace app\admin\model;

use think\Model;

class ReCoinLog extends Model
{
    // 表名
    protected $table = 're_coin_log';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'way_text',
        'method_text',
        'status_text'
    ];
    

    
    public function getWayList()
    {
        return ['1' => __('Way 1')];
    }     

    public function getMethodList()
    {
        return ['2' => __('Method 2')];
    }     

    public function getStatusList()
    {
        return ['1' => __('Status 1')];
    }     


    public function getWayTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['way'];
        $list = $this->getWayList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getMethodTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['method'];
        $list = $this->getMethodList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function user()
    {
        return $this->belongsTo('user', 'user_id','id','','left')->setEagerlyType(0);
    }

    public function company()
    {
        return $this->belongsTo('re_company', 're_company_id','id','','left')->setEagerlyType(0);
    }



}
