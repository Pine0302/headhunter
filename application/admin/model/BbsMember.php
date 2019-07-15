<?php

namespace app\admin\model;

use think\Model;

class BbsMember extends Model
{
    // 表名
    protected $table = 'bbs_member';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
        'gender_text',
        'level_text',
        'is_banned_text',
        'is_back_text',
        'bind_mobile_text',
        'is_manage_text',
        'add_time_text',
        'update_time_text'
    ];
    

    
    public function getGenderList()
    {
        return ['2' => __('Gender 2')];
    }     

    public function getLevelList()
    {
        return ['2' => __('Level 2')];
    }     

    public function getIsBannedList()
    {
        return ['2' => __('Is_banned 2')];
    }     

    public function getIsBackList()
    {
        return ['2' => __('Is_back 2')];
    }     

    public function getBindMobileList()
    {
        return ['2' => __('Bind_mobile 2')];
    }     

    public function getIsManageList()
    {
        return ['2' => __('Is_manage 2')];
    }     


    public function getGenderTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['gender'];
        $list = $this->getGenderList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getLevelTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['level'];
        $list = $this->getLevelList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsBannedTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['is_banned'];
        $list = $this->getIsBannedList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsBackTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['is_back'];
        $list = $this->getIsBackList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getBindMobileTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['bind_mobile'];
        $list = $this->getBindMobileList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsManageTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['is_manage'];
        $list = $this->getIsManageList();
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


}
