<?php

namespace app\admin\model;

use think\Model;

class ReApply extends Model
{
    // 表名
    protected $table = 're_apply';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $relationSearch = true;
    
    // 追加属性
    protected $append = [

    ];

    public function reResumeOne()
    {
        return $this->hasOne('reResume','id','re_resume_id','','left');
    }

    public function reCompanyOne()
    {
        return $this->hasOne('reCompany','id','re_company_id','','left');
    }

    public function reJobOne()
    {
        return $this->hasOne('reJob','id','re_job_id','','left');
    }

    public function recUserOne()
    {
        return $this->hasOne('user','id','rec_user_id','','left');
    }

    public function reResume()
    {
        return $this->belongsTo('reResume', 're_resume_id','id','','left')->setEagerlyType(0);
    }

    public function reCompany()
    {
        return $this->belongsTo('reCompany', 're_company_id','id','','left')->setEagerlyType(0);
    }
    public function re_company()
    {
        return $this->belongsTo('re_company', 're_company_id','id','','left')->setEagerlyType(0);
    }

    public function reJob()
    {
        return $this->belongsTo('reJob', 're_job_id','id','','left')->setEagerlyType(0);
    }

    public function recUser()
    {
        return $this->belongsTo('user', 'agent_id','id','','left')->setEagerlyType(0);
        // return $this->belongsTo('userTeam', 'id','low_user_id','','left')->setEagerlyType(0);
    }







}
