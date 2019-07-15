<?php

namespace app\admin\model;

use think\Model;

class BbsPost extends Model
{
    // 表名
    protected $table = 'bbs_post';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    
    // 追加属性
    protected $append = [
    //    'recommend_text'
    ];
    

    
    public function getRecommendList()
    {
        return ['1' => __('Recommend 1')];
    }     


    public function getRecommendTextAttr($value, $data)
    {        
        $value = $value ? $value : $data['recommend'];
        $list = $this->getRecommendList();
        return isset($list[$value]) ? $list[$value] : '';
    }


  /*  public function bbsForum()
    {
        return $this->hasOne('bbsForum','id','bbs_forum_id','','left');
    }*/


    public function bbsForum()
    {
        return $this->belongsTo('bbsForum', 'bbs_forum_id','id','','left')->setEagerlyType(0);
    }


    public function bbsMember()
    {
        return $this->belongsTo('bbsMember', 'bbs_member_id','id','','left')->setEagerlyType(0);
    }

  /*  public function bbsMember()
    {
        return $this->hasOne('bbsMember','id','bbs_member_id','','left');
    }*/



}
