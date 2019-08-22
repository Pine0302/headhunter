<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\wx\WXBizDataCrypt;
use fast\Http;
use think\cache\driver\Redis;
use think\Db;
use think\Session;
use think\Cache;
use app\api\controller\Common;
use app\api\library\NoticeHandle;
use app\common\controller\CommonFunc;
use app\common\library\Dater;
use app\common\service\JobService;
/**
 * 工作相关接口
 */
class Rec extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    //  protected $noNeedLogin = ['test1","login'];
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
//    /protected $noNeedRight = ['test2'];
    protected $noNeedRight = ['*'];



    //agent--我推荐的申请
    public function recList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $offer = isset($data['offer']) ? $data['offer'] : '';
        $type = isset($data['type']) ? $data['type'] : 0;
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $apply_list = [];
        if(!empty($sess_key)){
            try{
                $user_info = $this->getTUserInfo($sess_key);
                if($offer==0){

                    $noticeQuery = Db::table('re_notice');
                    $noticeQuery
                        ->alias('a')
                        ->where('a.type','between',[1,2])
                        ->where('a.from_user_id','=',$user_info['id']);
                    $count = $noticeQuery->count();
                    $noticeQuery->removeOption('field');
                    $apply_arr = $noticeQuery
                        ->join('re_resume r','a.to_user_id = r.user_id','left')
                        ->page($page,$page_size)
                        ->field('a.*,r.name as username,r.user_id as user_id,r.id as resume_id')
                        ->select();
                    foreach($apply_arr as $ka=>$va){
                        if($va['type']==1){
                            $info = Db::table('re_job')
                                ->alias('j')
                                ->where('j.id','=',$va['re_job_id'])
                                ->join('re_company c','j.re_company_id = c.id','left')
                                ->join('areas s','s.areano = j.city_code')
                                ->field('j.id as pj_id,j.name,j.reward,j.job_experience,s.areaname as city_name,j.is_bonus,j.job_label,j.mini_salary,j.max_salary,c.icon as company_icon,c.name as company_name')
                                ->find();
                        }else{
                            $info = Db::table('re_project')
                                ->alias('j')
                                ->where('j.id','=',$va['re_project_id'])
                                ->join('re_company c','j.re_company_id = c.id','left')
                                ->join('areas s','s.areano = j.city_code')
                                ->field('j.id as pj_id,j.name,j.reward,s.areaname as city_name,j.job_experience,j.nature,j.is_bonus,j.project_label,j.mini_salary,j.max_salary,c.icon as company_icon,c.name as company_name')
                                ->find();
                        }

                        $apply_arr[$ka]['info'] = $info;
                    }

                 //   var_dump($apply_arr);exit;
                    /*$apply_arr = $noticeQuery
                        ->join('re_job j','a.re_job_id = j.id','left')
                        ->join('re_company c','j.re_company_id = c.id','left')
                        ->join('re_resume r','a.to_user_id = r.user_id','left')
                        ->order('id desc')
                        ->field('a.*,j.name,j.is_bonus,j.job_label,j.mini_salary,j.max_salary,c.icon as company_icon,r.name as username')
                        ->page($page,$page_size)
                        ->select();*/
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    foreach($apply_arr as $ka=>$va){
                     /*   $work_info['label1'] = $work_info['label2'] = $work_info['label3'] = $work_info['label4'] = '';
                        if(!empty($va['job_label'])){
                            $job_label_arr = explode("/",$va['job_label']);
                            for ($i=0;$i<count($job_label_arr);$i++){
                                $work_info['label'.($i+1)] = $job_label_arr[$i];
                            }
                        }*/
                        $company_icon = empty($va['info']['company_icon']) ? "https://".config('webset.server_name').config('webset.default_company_icon') : $va['info']['company_icon'];
                        $nature = '';
                        if(isset($va['info']['nature'])){
                            $nature = $va['info']['nature'];
                        }
                        $apply_list[] = [
                            'id'=>$va['id'],
                            'type'=>$va['type'],
                            'resume_id'=>$va['resume_id'],
                            'user_id'=>$va['user_id'],
                            'name'=>$va['info']['name'],
                            'username'=>$va['username'],
                            'mini_salary'=>$va['info']['mini_salary']+1,
                            'max_salary'=>$va['info']['max_salary']-1,
                            'create_time'=>$va['create_at'],
                            'bonus'=>intval($va['info']['reward']),
                            'is_bonus'=>intval($va['info']['is_bonus']),
                            'city_name'=>$va['info']['city_name'],
                            'company_name'=>$va['info']['company_name'],
                            'job_experience'=>config('webset.job_experience')[$va['info']['job_experience']],
                            'nature'=>$nature,
                            'company_icon'=>$company_icon,
                            'pj_id'=>$va['info']['pj_id'],
                        ];
                    }
                }else{


                    $applyQuery = Db::table('re_apply');
                    $applyQuery
                        ->alias('a')
                    //    ->join('re_job j','a.re_job_id = j.id','left')
                        ->join('re_company c','a.re_company_id = c.id','left')
                        ->join('re_resume r','a.re_resume_id = r.id','left');
                    //  if($user_info['is_engineer']==1){
                    $applyQuery->where('a.agent_id','=',$user_info['id']);
                    // }else{
                    //  $applyQuery->where('a.hr_id','=',$user_info['id']);
                    //}
                    if(!empty($offer)) $applyQuery->where('a.offer','=',$offer);

                    if(!empty($type)) $applyQuery->where('a.type','=',$type);
                    $count = $applyQuery->count();
                    $applyQuery->removeOption('field');
                    $apply_arr = $applyQuery
                      //  ->field('a.*,j.name,j.job_label,j.mini_salary,j.max_salary,c.icon as company_icon,r.name as username,c.icon as company_icon,c.name as company_name')
                        ->field('a.*,c.icon as company_icon,r.name as username,c.icon as company_icon,c.name as company_name,r.id as resume_id,r.user_id')
                        ->page($page,$page_size)->select();
                    foreach($apply_arr as $ka=>$va){
                        if($va['type']==1){
                            $info = Db::table('re_job')
                                ->alias('j')
                                ->where('j.id','=',$va['re_job_id'])
                                ->join('areas s','s.areano = j.city_code')
                                ->field('j.name,j.reward,j.job_experience,s.areaname as city_name,j.is_bonus,j.job_label,j.mini_salary,j.max_salary')
                                ->find();
                        }else{
                            $info = Db::table('re_project')
                                ->alias('j')
                                ->where('j.id','=',$va['re_project_id'])
                                ->join('re_company c','j.re_company_id = c.id','left')
                                ->join('areas s','s.areano = j.city_code')
                                ->field('j.name,j.reward,s.areaname as city_name,j.job_experience,j.nature,j.is_bonus,j.project_label,j.mini_salary,j.max_salary')
                                ->find();
                        }

                        $apply_arr[$ka]['info'] = $info;
                    }

                    $applyQuery->removeOption();
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    foreach($apply_arr as $ka=>$va){
                        $company_icon = empty($va['company_icon']) ? "https://".config('webset.server_name').config('webset.default_company_icon') : $va['company_icon'];
                        $nature = '';
                        if(isset($va['info']['nature'])){
                            $nature = config('webset.nature')[$va['info']['nature']];
                        }
                        $apply_list[] = [
                            'id'=>$va['id'],
                            'type'=>$va['type'],
                            'resume_id'=>$va['resume_id'],
                            'user_id'=>$va['user_id'],
                            'name'=>$va['info']['name'],
                            'username'=>$va['username'],
                            'mini_salary'=>$va['info']['mini_salary']+1,
                            'max_salary'=>$va['info']['max_salary']-1,
                            'create_time'=>$va['create_at'],
                            'bonus'=>intval($va['info']['reward']),
                            'is_bonus'=>intval($va['info']['is_bonus']),
                            'company_icon'=>$company_icon,
                            'city_name'=>$va['info']['city_name'],
                            'company_name'=>$va['company_name'],
                            'job_experience'=>config('webset.job_experience')[$va['info']['job_experience']],
                            'nature'=>$nature,
                        ];
                    }


                }




                $response_data = [
                    'data'=>[
                        'rec_list'=>$apply_list,
                        'page_info'=>$page_info
                    ]
                ];
                $this->success('success',$response_data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }

}
