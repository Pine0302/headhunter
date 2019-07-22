<?php

namespace app\admin\model;

use think\Model;

class ReCompanyApply extends Model
{
    // 表名
    protected $table = 're_company_apply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1')];
    }     


    public function getStatusTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['status'];
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    public function hr()
    {
        return $this->hasOne('user','id','user_id','','left');
    }


    public function company()
    {
        return $this->hasOne('re_company','id','re_company_id','','left');
    }



}
