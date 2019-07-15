<?php

namespace app\admin\model;

use think\Model;

class ReResume extends Model
{
    // 表名
    protected $table = 're_resume';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [

      // 'identity_text',
      /*  'will_text',
        'intime_text',
        'nature_text',
        'type_text'*/
    ];
/*
    public function getIdentityList()
    {
        return ['1' => __('Identity 1')];
    }*/
    /*


        public function getWillList()
        {
            return ['1' => __('Will 1')];
        }

        public function getNatureList()
        {
            return ['1' => __('Nature 1')];
        }

        public function getTypeList()
        {
            return ['1' => __('Type 1')];
        }     */


/*    public function getIdentityTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['identity'];
        $list = $this->getIdentityList();
        return isset($list[$value]) ? $list[$value] : '';
    }*/

/*
    public function getWillTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['will'];
        $list = $this->getWillList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIntimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['intime'];
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getNatureTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['nature'];
        $list = $this->getNatureList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getTypeTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['type'];
        $list = $this->getTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setIntimeAttr($value)
    {
        return $value && !is_numeric($value) ? strtotime($value) : $value;
    }*/


}
