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
class Apply extends Api
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


    //申请工作 todo 分享此工作的经纪人身份key  agent_sess_key
    public function apply(){
        $data = $this->request->post();

        $sess_key = $data['sess_key'] ?? '';
        $agent_sess_key = $data['agent_sess_key'] ?? '';
        $re_job_id = $data['re_job_id'] ?? '';
        $re_project_id = $data['re_project_id'] ?? '';
        $type = $data['type'] ?? 1;
        if(!empty($sess_key)){
            try {
                $user_info = $this->getTUserInfo($sess_key);
                $uid = $user_info['id'];
                /*$user_team =  Db::table('user_team')
                    ->where('low_user_id',$user_info['id'])
                    ->field('up_user_id')
                    ->find();*/
           //     $rec_user_id = empty($user_team['up_user_id']) ? $rec_user_id : $user_team['up_user_id'];
                //判断用户是否完善了resume
                $JobServiceObj = new JobService();
                $check_resume_fill = $JobServiceObj->checkResumeFill($uid);

                if (!$check_resume_fill){
                    $this->error('请先完善个人信息再投递简历!',null,3);
                    exit;
                }
                $resumeQuery = Db::table('re_resume');
                $resume_info = $resumeQuery
                    ->where('user_id',$uid)
                    ->find();
                $resumeQuery->removeOption('where');
                $add_info = [
                    'user_id'=>$uid,
                    'type'=>$type,
                    'offer' => 1,
                    're_resume_id'=>$resume_info['id'],
                    //'rec_user_id'=>$rec_user_id,
                    'create_at' => date("Y-m-d H:i:s",time()),
                    'update_at' => date("Y-m-d H:i:s",time()),
                    'admin_id' => $user_info['ad_id'],
                ];

                //通过员工档案表查询用户所在的公司和管理员id
                if($type == 1){
                    $jobQuery = Db::table('re_job');
                    $job_info = $jobQuery
                        ->where('id',$re_job_id)
                        ->field('id,re_company_id,user_id as hr_id,name,is_bonus,reward,status')
                        ->find();
                    $jobQuery->removeOption('where');
                    $add_info['re_job_id'] = $job_info['id'];
                    $add_info['re_company_id'] = $job_info['re_company_id'];
                    $add_info['hr_id'] = $job_info['hr_id'];
                    $add_info['is_bonus'] = $job_info['is_bonus'];
                    $add_info['bonus'] = $job_info['reward'];
                    if($job_info['status']!=1){
                        if($job_info['status']==2){
                            $this->error('该岗位已暂停');
                        }else{
                            $this->error('该岗位已关闭');
                        }
                        exit;
                    }



                }else{
                    $projectQuery = Db::table('re_project');
                    $project_info = $projectQuery
                        ->where('id',$re_project_id)
                        ->field('id,re_company_id,user_id as hr_id,name,is_bonus,reward,status')
                        ->find();
                    $projectQuery->removeOption('where');
                    if($project_info['status']!=1){
                        if($project_info['status']==2){
                            $this->error('该项目已暂停');
                        }else{
                            $this->error('该项目已关闭');
                        }
                        exit;
                    }
                    $add_info['re_project_id'] = $project_info['id'];
                    $add_info['re_company_id'] = $project_info['re_company_id'];
                    $add_info['hr_id'] = $project_info['hr_id'];
                    $add_info['is_bonus'] = $project_info['is_bonus'];
                    $add_info['bonus'] = $project_info['reward'];
                }
                $add_info['agent_id'] = 0;
                if(!empty($agent_sess_key)){
                    $agent_info = $this->getGUserInfo($agent_sess_key);
                    $add_info['agent_id'] = $agent_info['id'];
                }

                //查看是否有预约申请记录

                $applyQuery = Db::table('re_apply');
                $applyQuery->where('user_id','=',$uid);
                if($type==1){
                    $applyQuery->where('re_job_id','=',$re_job_id);
                }else{
                    $applyQuery->where('re_project_id','=',$re_project_id);
                }
                $check = $applyQuery->find();

                if(empty($check)){
                    $result = Db::table('re_apply')->insertGetId($add_info);
                    if($type==1){
                     //   Db::table('re_job')->where('id','=',$re_job_id)->setInc('sign_num');
                    }else{
                        Db::table('re_project')->where('id','=',$re_project_id)->setInc('sign_num');
                    }

                    // 给hr 发送一个申请信息
                    $resume_info = Db::table('re_resume')->where('user_id','=',$user_info['id'])->find();
                    $notice_config = config('webset.notice_type');
                    if($type == 1){
                        $arr_notice = [
                            'type'=>5,
                            'from_user_type'=>1,
                            'from_user_id'=>$user_info['id'],
                            'type'=>5,
                            'to_user_id'=> $add_info['hr_id'],
                            'brief_content'=>'您有一个投递申请',
                            'content'=>"您有一个投递申请",
                            're_apply_id'=>$result,
                            're_resume_id'=>$resume_info['id'],
                            're_job_id'=>$add_info['re_job_id'],
                            'create_at'=>date("Y-m-d H:i:s"),
                            'update_at'=>date("Y-m-d H:i:s"),
                            'is_read'=>2,
                        ];
                    }else{
                        $arr_notice = [
                            'type'=>5,
                            'from_user_type'=>1,
                            'from_user_id'=>$user_info['id'],
                            'type'=>5,
                            'to_user_id'=> $add_info['hr_id'],
                            'brief_content'=>'您有一个投递申请',
                            'content'=>"您有一个投递申请",
                            're_apply_id'=>$result,
                            're_resume_id'=>$resume_info['id'],
                            're_project_id'=>$add_info['re_project_id'],
                            'create_at'=>date("Y-m-d H:i:s"),
                            'update_at'=>date("Y-m-d H:i:s"),
                            'is_read'=>2,
                        ];
                    }


                    Db::table('re_notice')->insert($arr_notice);


                    if(!empty($result)){
                        //查找驻场人员
                        /*$noticeHandleObj = new NoticeHandle();
                        $bind_list = $this->getBindUsersByAdminId($job_info['admin_id']);
                        if(count($bind_list)>0){
                            foreach($bind_list as $kb=>$vb){
                                //添加驻场人员消息记录
                                $type = 1;
                                $content = $user_info['nickname']."投递您公司".$job_info['name']."岗位（驻场人员收到）";
                                $is_read = 2;
                                $noticeHandleObj->createNotice($type,$vb['user_id'],$content,$is_read);
                            }
                        }
                        //查看该用户是否有上级,如果有的话,添加上级的消息记录
                        $up_user_info = $this->getUpUserInfo($uid);
                        if(!empty($up_user_info)){
                            $up_user_id = $up_user_info['up_user_id'];
                            $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->field('name')->find();
                            $type = 2;
                            $content = "您的团队成员".$user_info['nickname']."报名".$company_info['name']."（公司）的".$job_info['name']."岗位";
                            $is_read = 2;
                            $noticeHandleObj->createNotice($type,$up_user_id,$content,$is_read);
                        }*/
                        $this->success('success',null);
                    }else{
                        $this->error('网络繁忙,请稍后再试');
                    }
                }else{
                    $this->success('success',null);
                }
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }


    //下载简历
    public function buyResume(){

        $data = $this->request->post();
        $re_resume_id = isset($data['re_resume_id']) ? $data['re_resume_id'] : '';
        $re_apply_id = isset($data['re_apply_id']) ? $data['re_apply_id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $user_type = isset($data['user_type']) ? $data['user_type'] : '';
        if(!empty($sess_key)&&(!empty($re_resume_id))){
            try{
                $user_info = $this->getTUserInfo($sess_key);
                $resumeQuery = Db::table('re_resume');
                $resumeEducationQuery = Db::table('re_resume_education');
                $resumeWorkQuery = Db::table('re_resume_work');
                $resumeProjectQuery = Db::table('re_resume_project');
                $resumeQuery->where('id','=',$re_resume_id);
                $resume_detail = $resumeQuery
                    ->field('*')
                    ->find();
                $resumeQuery->removeOption();

                    //确认用户今日下载数量不超额
                    $resume_download_config_arr = config('coin.download_resume');
                    $resume_download_config = ($resume_detail['type']==2) ? $resume_download_config_arr['golden'] : $resume_download_config_arr['normal'];
                    $resumeDownloadQuery = Db::table('re_resume_download');
                    $today_download_num = $resumeDownloadQuery
                        ->where('user_id','=',$user_info['id'])
                        ->where('resume_type','=',$resume_detail['type'])
                        ->where('create_date','=',date("Y-m-d"))
                        ->count();
                    $resumeDownloadQuery->removeOption('where');
                    if(!($today_download_num<$resume_download_config['day_limit'])){
                        $this->success('您今日下载简历数量已超额',null,2);
                    }
                    //确认用户猎币足够
                    $user_coin = ($user_type==2) ? $user_info['hr_coin'] : $user_info['agent_coin'];
                    $coin_num = $resume_download_config['num'];
                    if(!($user_coin<$coin_num)){   //可下载
                            // todo update 事务
                        //给用户减去$coin_num
                        $userQuery = Db::table('user');
                        $userQuery->where('id', '=',$user_info['id']);
                        $arr_resume_download = [
                            're_resume_id'=>$resume_detail['id'],
                            'resume_type'=>$resume_detail['type'],
                            'user_id'=>$user_info['id'],
                            'num'=>$coin_num,
                            'create_date'=>date("Y-m-d"),
                            'create_at'=>date("Y-m-d H:i:s"),
                        ];
                        if($user_type==2){
                            $userQuery->setDec('hr_coin',$coin_num);
                        }
                        if($user_type==3){
                            $userQuery->setDec('agent_coin',$coin_num);
                        }

                        //给用户添加下载记录
                        $result =  Db::table('re_resume_download')->insertGetId($arr_resume_download);

                        $coinLogQuery = Db::table('re_coin_log');
                        //给用户添加coin 使用记录  todo 筛选应该使用哪些coin
                        $coin_method = config('coin.coin_method');
                        $arr_coin_log = [
                            'user_id'=>$user_info['id'],
                            'user_type'=>$user_type,
                            'num'=>$coin_num,
                            'method'=>$coin_method['download_resume']['method_id'],
                            'way'=>$coin_method['download_resume']['way'],
                            're_resume_id'=>$resume_detail['id'],
                            'create_at'=>date("Y-m-d H:i:s"),
                        ];
                        $coinLogQuery->insert($arr_coin_log);

                        //下载过的简历状态修改
                        if(!empty($re_apply_id)){
                            Db::table('re_apply')->where('id','=',$re_apply_id)->update(['offer'=>2]);
                        }
                        $userQuery->removeOption();
                        $resumeDownloadQuery->removeOption();
                        $coinLogQuery->removeOption();
                        if($result){
                            $this->success('success');
                        }else{
                            $this->error('网络繁忙,请稍后再试');
                        }
                    }else{
                        $this->error('您的猎币数量不足');
                    }

            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //工程师--我投递的
    public function applyList(){
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

                $applyQuery = Db::table('re_apply');

                if(!empty($type)){
                    if($type==1){
                        $applyQuery->join('re_job j','a.re_job_id = j.id','left');
                    }
                    if($type==2){
                        $applyQuery->join('re_project j','a.re_project_id = j.id','left');
                    }
                    $applyQuery
                        ->alias('a')
                        ->join('re_company c','j.re_company_id = c.id','left')
                        ->join('re_resume r','a.re_resume_id = r.id','left');
                    $applyQuery->where('a.type','=',$type);
                    $applyQuery->where('a.user_id','=',$user_info['id']);
                    if(!empty($offer)){
                        if($offer==3){
                            $applyQuery->where('a.offer','in',[3,5]);
                        }else{
                            $applyQuery->where('a.offer','=',$offer);
                        }
                    }
                    $count = $applyQuery->count();
                    $applyQuery->removeOption('field');
                    if($type==1){
                        $applyQuery
                            ->join('areas s','s.areano = j.city_code','left')
                            ->join('re_interview v','v.re_apply_id = a.id','left')
                            ->field('a.*,s.areaname as city_name,v.id as re_interview_id,j.name,j.job_experience,j.job_label,j.mini_salary,j.max_salary,c.icon as company_icon,r.name as username,j.city_code,c.name as company_name,3 as nature');
                    }else{
                        $applyQuery
                            ->join('areas s','s.areano = j.city_code','left')
                            ->join('re_interview v','v.re_apply_id = a.id','left')
                            ->field('a.*,v.id as re_interview_id,s.areaname as city_name,j.name,j.job_experience,j.project_label,j.mini_salary,j.max_salary,c.icon as company_icon,r.name as username,j.city_code,c.name as company_name,j.nature');
                    }

                    $apply_arr = $applyQuery
                        ->page($page,$page_size)->select();
                    $applyQuery->removeOption();
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    foreach($apply_arr as $ka=>$va){
                        /*$work_info['label1'] = $work_info['label2'] = $work_info['label3'] = $work_info['label4'] = '';
                        if(!empty($va['job_label'])){
                            $job_label_arr = explode("/",$va['job_label']);
                            for ($i=0;$i<count($job_label_arr);$i++){
                                $work_info['label'.($i+1)] = $job_label_arr[$i];
                            }
                        }*/
                        $company_icon = empty($va['company_icon']) ? "https://".config('webset.server_name').config('webset.default_company_icon') : $va['company_icon'];

                        if(!empty($va['job_experience'])){
                            $job_experience = config('webset.job_experience')[$va['job_experience']];
                        }else{
                            $job_experience = "未知";
                        }

                        //$nature = config('webset.nature')[$va['nature']];
                        $nature = $va['nature'];
                        $apply_list[] = [
                            'id'=>$va['id'],
                            'type'=>$va['type'],
                            'name'=>$va['name'],
                            'username'=>$va['username'],
                            'mini_salary'=>$va['mini_salary'],
                            'max_salary'=>$va['max_salary'],
                            'create_time'=>$va['create_at'],
                            'company_icon'=>$company_icon,
                            'city_name'=>$va['city_name'],
                            'job_experience'=>$job_experience,
                            'company_name'=>$va['company_name'],
                            'nature'=>$nature,
                            're_job_id'=>$va['re_job_id'],
                            're_project_id'=>$va['re_project_id'],
                            're_interview_id'=>$va['re_interview_id'],
                            'status'=>$va['offer'],
                        ];
                    }
                }else{

                    $applyQuery = Db::table('re_apply');
                    $applyQuery
                        ->alias('a')
                        ->join('re_resume r','a.re_resume_id = r.id','left')
                       ->join('re_interview v','v.re_apply_id = a.id','left');
                    $applyQuery->where('a.user_id','=',$user_info['id']);
                    if(!empty($offer)){
                        if($offer==3){
                            $applyQuery->where('a.offer','in',[3,5]);
                        }else{
                            $applyQuery->where('a.offer','=',$offer);
                        }
                    }
                    $count = $applyQuery->count();
                    $applyQuery->removeOption('field');
                    $applyQuery->field('a.*,r.name as username,v.id as re_interview_id');
                    $apply_arr = $applyQuery
                        ->page($page,$page_size)->select();

                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    foreach($apply_arr as $ka=>$va){
                        if($va['type']==1){     //岗位
                            $jobQuery = Db::table('re_job');
                            $job_info = $jobQuery
                                ->alias('j')
                                ->join('re_company c','j.re_company_id = c.id','left')
                                ->join('areas s','j.city_code = s.areano','left')
                                ->where('j.id','=',$va['re_job_id'])
                                ->field('j.*,c.name as company_name,s.areaname as city_name')
                                ->find();
                            $company_icon = empty($job_info['company_icon']) ? "https://".config('webset.server_name').config('webset.default_company_icon') : $job_info['company_icon'];
                            if(!empty($job_info['job_experience'])){
                                $job_experience = config('webset.job_experience')[$job_info['job_experience']];
                            }else{
                                $job_experience = "未知";
                            }
                          //


                         //   $nature = config('webset.nature')[$va['nature']];
                            $nature = '不限';
                            $apply_list[] = [
                                'id'=>$va['id'],
                                'type'=>$va['type'],
                                'name'=>$job_info['name'],
                                'username'=>$va['username'],
                                'mini_salary'=>$job_info['mini_salary'],
                                'max_salary'=>$job_info['max_salary'],
                                'create_time'=>$va['create_at'],
                                'company_icon'=>$company_icon,
                                'city_name'=>$job_info['city_name'],
                                'job_experience'=>$job_experience,
                                'company_name'=>$job_info['company_name'],
                                'nature'=>$nature,
                                're_job_id'=>$va['re_job_id'],
                                're_project_id'=>$va['re_project_id'],
                                're_interview_id'=>$va['re_interview_id'],
                                'status'=>$va['offer'],
                            ];

                        }else{                  //项目
                            $jobQuery = Db::table('re_project');
                            $job_info = $jobQuery
                                ->alias('j')
                                ->join('re_company c','j.re_company_id = c.id','left')
                                ->join('areas s','j.city_code = s.areano','left')
                                ->where('j.id','=',$va['re_project_id'])
                                ->field('j.*,c.name as company_name,s.areaname as city_name')
                                ->find();
                            $company_icon = empty($job_info['company_icon']) ? "https://".config('webset.server_name').config('webset.default_company_icon') : $job_info['company_icon'];
                            $job_experience = config('webset.job_experience')[$job_info['job_experience']];
                            //$nature = config('webset.nature')[$job_info['nature']];
                            $nature = $job_info['nature'];
                            $apply_list[] = [
                                'id'=>$va['id'],
                                'type'=>$va['type'],
                                'name'=>$job_info['name'],
                                'username'=>$va['username'],
                                'mini_salary'=>$job_info['mini_salary'],
                                'max_salary'=>$job_info['max_salary'],
                                'create_time'=>$va['create_at'],
                                'company_icon'=>$company_icon,
                                'city_name'=>$job_info['city_name'],
                                'job_experience'=>$job_experience,
                                'company_name'=>$job_info['company_name'],
                                'nature'=>$nature,
                                're_job_id'=>$va['re_job_id'],
                                're_project_id'=>$va['re_project_id'],
                                're_interview_id'=>$va['re_interview_id'],
                                'status'=>$va['offer'],
                            ];
                        }
                    }











                }

                $response_data = [
                    'data'=>[
                        'apply_list'=>$apply_list,
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




    //hr-投递给我的申请表
    public function apply2MeList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $offer = isset($data['offer']) ? $data['offer'] : '';

        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $apply_list = [];
        if(!empty($sess_key)){
            try{
                $user_info = $this->getTUserInfo($sess_key);
                $applyQuery = Db::table('re_apply');
                $applyQuery
                    ->alias('a')

                    //->join('re_company c','j.re_company_id = c.id','left')
                    ->join('re_resume r','a.re_resume_id = r.id','left')
                    ->join('re_resume_download d','d.re_resume_id = r.id','left')
                    ->join('user c','a.user_id = c.id','left');


                $applyQuery->where('a.hr_id','=',$user_info['id']);
                $applyQuery->where('a.hr_status','=',1);

                if(!empty($offer)){
                    if($offer==3){
                        $applyQuery->where('a.offer','in',[3,5]);
                    }else{
                        $applyQuery->where('a.offer','=',$offer);
                    }
                }
                $count = $applyQuery->count();
                $applyQuery->removeOption('field');

                $apply_arr = $applyQuery
                    ->field('a.*,d.id as did,c.avatar as avatar,c.logintime,r.title,r.label as label,r.id as resume_id,r.name as username,r.work_begin_time,r.education')
                    ->page($page,$page_size)->select();
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];
              //  $applyQuery->getLastSql();exit;
                $applyQuery->removeOption();
                foreach($apply_arr as $ka=>$va){
                    //j.name,j.job_label,j.mini_salary,j.max_salary,
                    //->join('re_job j','a.re_job_id = j.id','left')
                    if($va['type']==1){  //投递岗位
                        $jobQuery= Db::table('re_job');
                        $wlist = $jobQuery->where('id','=',$va['re_job_id'])->field('name,job_label as label,mini_salary,max_salary,status')->find();
                    }else{                //投递项目
                        $projectQuery= Db::table('re_project');
                        $wlist = $projectQuery->where('id','=',$va['re_project_id'])->field('name,project_label as label,mini_salary,max_salary,status')->find();
                    }
                    $work_info['label1'] = $work_info['label2'] = $work_info['label3'] = $work_info['label4'] = '';
                    if(!empty($va['label'])){
                        $job_label_arr = explode("/",$va['label']);
                        for ($i=0;$i<count($job_label_arr);$i++){
                            $work_info['label'.($i+1)] = $job_label_arr[$i];
                        }
                    }
                    $daterObj = new Dater();
                    $eigneer_day = $daterObj->socialDateDisplay($va['logintime']);
                    $education = config('webset.education')[$va['education']];
                    $work_years = $daterObj->getWorkYears(strtotime($va['work_begin_time']));
                    $is_download = empty($va['did']) ? 2 : 1;
                  //  $company_icon = empty($va['company_icon']) ? "https://".config('webset.server_name').config('webset.default_company_icon') : $va['company_icon'];
                    $apply_list[] = [
                        'id'=>$va['id'],
                        're_resume_id'=>$va['resume_id'],
                        'type'=>$va['type'],
                        'name'=>$wlist['name'],
                        'username'=>$va['username'],
                        'avatar'=>$va['avatar'],
                        'mini_salary'=>$wlist['mini_salary'],
                        'max_salary'=>$wlist['max_salary'],
                        'create_time'=>$va['create_at'],
                        'label1'=>$work_info['label1'],
                        'label2'=>$work_info['label2'],
                        'label3'=>$work_info['label3'],
                        'label4'=>$work_info['label4'],
                        'eigneer_day'=>$eigneer_day,
                        'education'=>$education,
                        'is_download'=>$is_download,
                        'work_years'=>$work_years,
                        'title'=>$va['title'],
                        'offer'=>$va['offer'],
                        'status'=>$wlist['status'],
                    ];
                }
                $response_data = [
                    'data'=>[
                        'apply_list'=>$apply_list,
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


    //邀请面试
    public function interview(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        // $rec_user_id = $data['rec_user_id'] ?? '';
        $re_apply_id = $data['re_apply_id'] ?? '';
        $time = $data['time'] ?? '';
        $address = $data['address'] ?? '';
        $contract = $data['contract'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $now = date("Y-m-d H::i:s");
        if(!empty($sess_key)){
            try {
                $hr_info = $this->getGUserInfo($sess_key);
                $arr_interview = [
                    're_apply_id'=>$re_apply_id,
                    'time'=>$time,
                    'address'=>$address,
                    'contract'=>$contract,
                    'mobile'=>$mobile,
                    'update_at'=>$now,
                    'status'=>1,
                    'hr_id'=>$hr_info['id'],
                ];
                $interviewQuery = Db::table('re_interview');

                $check_interview = $interviewQuery->where('re_apply_id','=',$re_apply_id)->find();
                $interviewQuery->removeOption('where');
                if($check_interview){
                    $interviewQuery->where('re_apply_id','=',$re_apply_id)->update($arr_interview);

                }else{
                    $arr_interview['create_at'] = $now;
                    $interviewQuery->insert($arr_interview);
                }
                Db::table('re_apply')->where('id','=',$re_apply_id)->update(['offer'=>3]);
                Db::table('re_apply')->removeOption();
                $interviewQuery->removeOption();
                $apply_info =   Db::table('re_apply')->where('id','=',$re_apply_id)->find();
                $notice_config = config('webset.notice_type');
                $arr_notice = [
                    'type'=>4,
                    'from_user_type'=>2,
                    'from_user_id'=>$hr_info['id'],
                    'type'=>4,
                    'to_user_id'=>$apply_info['user_id'],
                    'brief_content'=>'您有一个面试邀请',
                    'content'=>"您有一个面试邀请",
                    're_job_id'=>$apply_info['re_job_id'],
                    're_apply_id'=>$apply_info['id'],
                    'create_at'=>date("Y-m-d H:i:s"),
                    'update_at'=>date("Y-m-d H:i:s"),
                    'is_read'=>2,
                ];
                Db::table('re_notice')->insert($arr_notice);
                $this->success('success',null);
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }


       //邀请面试
    public function interviewStatus(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        // $rec_user_id = $data['rec_user_id'] ?? '';
        $re_apply_id = $data['re_apply_id'] ?? '';
        $type = $data['type'] ?? '';
        $now = date("Y-m-d H::i:s");
        if(!empty($sess_key)&&(!empty($re_apply_id))){
            try {
                $hr_info = $this->getGUserInfo($sess_key);
                $interviewQuery = Db::table('re_interview');
                $applyQuery = Db::table('re_apply');
                switch($type){
                    case 3:
                        $applyQuery->where('id','=',$re_apply_id)->update(['offer'=>4,'hr_status'=>2,'update_at'=>$now]);
                        $interviewQuery->where('re_apply_id','=',$re_apply_id)->update(['status'=>4,'update_at'=>$now]);
                        $result = 1;
                        $applyQuery->removeOption();
                        $interviewQuery->removeOption();
                        break;
                    case 2:
                        $applyQuery->where('id','=',$re_apply_id)->update(['offer'=>4,'update_at'=>$now]);
                        $interviewQuery->where('re_apply_id','=',$re_apply_id)->update(['status'=>4,'update_at'=>$now]);
                        $this->sendrefuseApply($re_apply_id);
                        $result = 1;
                        $applyQuery->removeOption();
                        $interviewQuery->removeOption();
                        break;
                    case 1:
                        $commonFuncObj = new CommonFunc();
/*                        $applyQuery->where('id','=',$re_apply_id)->update(['offer'=>5,'update_at'=>$now]);
                        $interviewQuery->where('re_apply_id','=',$re_apply_id)->update(['status'=>3,'update_at'=>$now]);*/

                        $result = $commonFuncObj -> recruitSuccess($re_apply_id);
                        $this->sendPassApply($re_apply_id);
                        break;
                }
                if($result==1){
                    $this->success('success',null);
                }else{
                    $this->error('您的猎币不足支付赏金,请稍候再试',null);
                }
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }

    //面试详情
    public function interviewDetail(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        // $rec_user_id = $data['rec_user_id'] ?? '';
        $re_apply_id = $data['re_apply_id'] ?? '';

        if(!empty($sess_key)&&(!empty($re_apply_id))){
            try {
                $hr_info = $this->getGUserInfo($sess_key);
                $interviewQuery = Db::table('re_interview');
                $applyQuery = Db::table('re_apply');
                $interview_arr = $interviewQuery->where('re_apply_id','=',$re_apply_id)->find();
                $apply_arr = $applyQuery->where('id','=',$re_apply_id)->find();
                if(($interview_arr['status']==2)||($interview_arr['status']==3)){
                    $status = 2;
                }elseif($interview_arr['status']==4){
                    $status = 3;
                }else{
                    $status = 1;
                }

                $interview_info= [
                    'id'=>$interview_arr['id'],
                    'time'=>$interview_arr['time'],
                    'address'=>$interview_arr['address'],
                    'contact'=>$interview_arr['contract'],
                    'mobile'=>$interview_arr['mobile'],
                    'status'=>$status,
                    're_job_id'=>$apply_arr['re_job_id'],
                    're_company_id'=>$apply_arr['re_company_id'],
                    're_project_id'=>$apply_arr['re_project_id'],
                ];

                $applyQuery->removeOption();
                $interviewQuery->removeOption();
                $response_data = [
                    'data'=>[
                        'interview_info'=>$interview_info,
                    ]
                ];
                $this->success('success',$response_data);
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }


    //hr/agent-改变岗位/项目状态
    public function changeOperateStatus(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        // $rec_user_id = $data['rec_user_id'] ?? '';
        $id = $data['id'] ?? '';
        $operate_status = $data['operate_status'] ?? '';
        $type = $data['type'] ? $data['type'] : 1;

        if(!empty($sess_key)&&(!empty($operate_status))&&(!empty($operate_status))){
            try {
                $date_time = date("Y-m-d H:i:s");
                //todo 验证hr/agent 身份
                $hr_info = $this->getGUserInfo($sess_key);
                if($type==1){   //岗位
                    $jobQuery = Db::table('re_job');
                    $result = $jobQuery->where('id','=',$id)->update([
                        'status'=>$operate_status,
                        'update_at'=>$date_time,
                    ]);
                    $jobQuery->removeOption();
                }else{
                    $jobQuery = Db::table('re_project');
                    $result = $jobQuery->where('id','=',$id)->update([
                        'status'=>$operate_status,
                        'update_at'=>$date_time,
                    ]);
                    $jobQuery->removeOption();
                    //var_dump($result);
                }
                if($result){
                    $this->success('success');
                }else{
                    $this->error('网络繁忙,请稍后再试');
                }

            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }




































    public function handleTime($jobtime){
        $jobtime_str = strtotime($jobtime);
        $jobtime_date = date('Y-m-d',$jobtime_str);
        $now_date = date('Y-m-d',time());
        if ($jobtime_date == $now_date){
            return date("H:i",$jobtime_str);
        }else{
            return date('m-d',$jobtime_str);
        }

    }

    //获取五个相关岗位
    public function getRelatedWork($work_info,$flag){
       // var_dump($work_info);exit;
        //同公司,同类型,同区,同市,同省
        $re_company_id = $work_info['re_company_id'];
        $re_jobtype_id = $work_info['re_jobtype_id'];
        $district_code = $work_info['district_code'];
        $city_code = $work_info['city_code'];
        $prov_code = $work_info['prov_code'];
        $field = "j.*, c.name as company_name,
        case j.re_company_id when '".$re_company_id."' then 1 else  2 end as company_rank ,
        case j.re_jobtype_id when '".$re_jobtype_id."' then 1 else  2 end as jobtype_rank ,
        case j.district_code when '".$district_code."' then 1 else  2 end as district_rank ,
        case j.city_code when '".$city_code."' then 1 else  2 end as city_rank ,
        case j.prov_code when '".$prov_code."' then 1 else  2 end as prov_rank ";
        $order = "company_rank asc,jobtype_rank asc,district_rank asc,city_rank asc,prov_rank asc";
        if($flag==1){    //总平台必须推荐奖
            $work_list = Db::table('re_job')
                ->alias('j')
                ->join('re_company c ','j.re_company_id = c.id')
                ->where('c.status','=',1)
                ->where('j.status','=',1)
                ->where('j.status','=',1)
                ->where('j.reward_up','>',0)
                ->field($field)
                ->order($order)
                ->page(0,4)
                ->select();
        }else{
            $work_list = Db::table('re_job')
                ->alias('j')
                ->join('re_company c ','j.re_company_id = c.id')
                ->where('c.status','=',1)
                ->where('j.status','=',1)
                ->where('j.status','=',1)
                ->field($field)
                ->order($order)
                ->page(0,4)
                ->select();
        }

        $rec_work_list = [];
        if(count($work_list)>1){
            foreach($work_list as $kw=>$vw){
                if($vw['id']==$work_info['id']){
                    unset($work_list[$kw]);
                }else{
                    $keyword_arr = explode("/",$vw['keyword']);
                    $area_arr = explode("-",$vw['area']);
                    $pic_env_arr = explode(",",$vw['pic_env']);
                    $pic_env = $pic_env_arr['0'];
                    $rec_work_list[] = [
                        'id'=>$vw['id'],
                        'pic_env'=>"https://".$_SERVER['HTTP_HOST'].$pic_env,
                        'name'=>$this->wordCut($vw['name'],8),
                        'company_name'=>$vw['company_name'],
                        'reward'=>intval($vw['reward']),
                        'reward_up'=>intval($vw['reward_up']),
                        'mini_salary'=>intval($vw['mini_salary']),
                        'max_salary'=>intval($vw['max_salary']),
                        'update_at'=>$this->handleTime($vw['update_at']),
                        'keyword1'=>($keyword_arr[0]) ?? '',
                        'keyword2'=>($keyword_arr[1]) ?? '',
                        'city_name'=>isset($area_arr[1]) ? $area_arr[1] : '',
                    ];
                }
            }
        }else{
            $rec_work_list = [];
        }
        return $rec_work_list;
    }


    //公司信息
    public function companyInfo(){
        $data = $this->request->post();
        $id = isset($data['id']) ? $data['id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $ukey = isset($data['ukey']) ? $data['ukey'] : '';
        if(!empty($ukey)){
            $this->addUser2Team($sess_key,$ukey);
        }
        if((!empty($sess_key))&&(!empty($id))){
            try{
                $company_info =
                    Db::table('re_company')
                        ->field('id,pic_swap,name')
                        ->where('id','=',$id)
                        ->find();
                $pic_swap_new = [];
                if(!empty($company_info['pic_swap'])){
                    $pic_swap_arr =  explode(",",$company_info['pic_swap']);
                    foreach($pic_swap_arr as $vp){
                        $pic_swap_new[] = "https://".$_SERVER['HTTP_HOST'].$vp;
                    }
                }
                $response = [
                    'id' => $company_info['id'],
                    'name' => $company_info['name'],
                    'pic_swap' =>$pic_swap_new,
                ];
                $data = [
                    'data'=>$response,
                ];
                $this->success('success', $data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //公司的岗位列表
    public function  companyWorkList(){
        $data = $this->request->post();
        $re_company_id = isset($data['id']) ? $data['id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        if(!empty($sess_key)&&(!empty($re_company_id))){
            try{
                //获取所有re_company_id
                $company_list = Db::table('re_company')
                    ->where('status','=',1)
                    ->where(function ($query) use($re_company_id) {
                        $query->where('id','=',$re_company_id)
                            ->whereOr('re_company_id', '=', $re_company_id);
                    })
                    ->field('id,re_company_id')
                    ->select();
                $order = 'j.reward desc ';
                $company_num = count($company_list);
                if($company_num>1){
                    $arr_re_company_ids = '';
                    foreach($company_list as $kc=>$vc){
                        $arr_re_company_ids = $arr_re_company_ids.$vc['id'].",";
                    }
                    $arr_re_company_ids = substr($arr_re_company_ids,0,(strlen($arr_re_company_ids)-1));
                    $arr_re_company_ids = "[".$arr_re_company_ids."]";
                    $work_list = Db::table('re_job')
                        ->alias('j')
                        ->join('re_company c ','j.re_company_id = c.id')
                        ->where('j.status','=',1)
                        ->where('j.re_company_id', 'in', $arr_re_company_ids)
                        ->field('j.*,c.name as company_name')
                        ->order($order)
                        ->page($page,$page_size)
                        ->select();
                }else{
                    $work_list = Db::table('re_job')
                        ->alias('j')
                        ->join('re_company c ','j.re_company_id = c.id')
                        ->where('j.status','=',1)
                        ->where('j.re_company_id', '=', $re_company_id)
                        ->field('j.*,c.name as company_name')
                        ->order($order)
                        ->page($page,$page_size)
                        ->select();
                }
                $data = [];
                if(!empty($work_list)){
                    if($company_num>1){
                        $count = Db::table('re_job')
                            ->alias('j')
                            ->join('re_company c ','j.re_company_id = c.id')
                            ->where('j.status','=',1)
                            ->where('j.re_company_id', 'in', $arr_re_company_ids)
                            ->count();
                    }else{
                        $count = Db::table('re_job')
                            ->alias('j')
                            ->join('re_company c ','j.re_company_id = c.id')
                            ->where('j.status','=',1)
                            ->where('j.re_company_id', '=', $re_company_id)
                            ->count();
                    }
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    foreach($work_list as $kw=>$vw){
                        $keyword_arr = explode("/",$vw['keyword']);
                        $area_arr = explode("-",$vw['area']);
                        $pic_env_arr = explode(",",$vw['pic_env']);
                        $pic_env = $pic_env_arr['0'];
                        $data[] = [
                            'id'=>$vw['id'],
                            'pic_env'=>"https://".$_SERVER['HTTP_HOST'].$vw['pic_env'],
                            'name'=>$this->wordCut($vw['name'],8),
                            'reward'=>intval($vw['reward']),
                            'company_name'=>$vw['company_name'],
                            'reward_up'=>intval($vw['reward_up']),
                            'mini_salary'=>intval($vw['mini_salary']),
                            'max_salary'=>intval($vw['max_salary']),
                            'update_at'=>$this->handleTime($vw['update_at']),
                            'keyword1'=>($keyword_arr[0]) ?? '',
                            'keyword2'=>($keyword_arr[1]) ?? '' ,
                            'city_name'=>$area_arr[1] ?? '',
                        ];
                    }
                }else{
                    $page_info = null;
                    $data = null;
                }
                $data = [
                    'data'=>$data,
                    'page_info'=>$page_info,
                ];
                $this->success('success', $data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }

    // 工程师,获取面试邀请数量
    public function getUnReadInterview(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $interview_count = Db::table('re_notice')
                    ->where('to_user_id','=',$user_info['id'])
                    ->where('type','=',4)
                    ->where('is_read','=',2)
                    ->count();
                $response = [
                    'interview_count' => $interview_count,
                ];
                $data = [
                    'data'=>$response,
                ];
                $this->success('success', $data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    // 工程师,读取面试邀请
    public function readInterview(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_apply_id= isset($data['re_apply_id']) ? $data['re_apply_id'] : '';
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                 Db::table('re_notice')
                    ->where('to_user_id','=',$user_info['id'])
                    ->where('type','=',4)
                    ->where('re_apply_id','=',$re_apply_id)
                    ->update(['is_read'=>1]);
                $data = [];
                $this->success('success', $data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //检测是否已推荐
    public function checkRec(){
        $data = $this->request->post();
        $agent_key = isset($data['agent_key']) ? $data['agent_key'] : '';
        $engineer_id = isset($data['engineer_id']) ? $data['engineer_id'] : '';
        $type = isset($data['type']) ? $data['type'] : 1 ;
        $id = isset($data['id']) ? $data['id'] : '';
        if(!empty($agent_key)){
            try{
                $agent_info = $this->getGUserInfo($agent_key);
                if($type=1){   //岗位
                    $check = Db::table('re_notice')
                        ->where('to_user_id','=',$engineer_id)
                        ->where('from_user_id','=',$agent_info['id'])
                        ->where('type','=',1)
                        ->where('re_job_id','=',$id)
                        ->find();
                }else{
                    $check = Db::table('re_notice')
                        ->where('to_user_id','=',$engineer_id)
                        ->where('from_user_id','=',$agent_info['id'])
                        ->where('type','=',2)
                        ->where('re_project_id','=',$id)
                        ->find();
                }
                $data = [];
                if(!empty($check)){
                    $data['flag'] = 1 ;
                }else{
                    $data['flag'] = 0 ;
                }
                $this->success('success', $data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    public function sendrefuseApply($apply_id)
    {
        $applyQuery = Db::table('re_apply');
        $apply_info = $applyQuery->where('id','=',$apply_id)->find();
        $applyQuery->removeOption();
        $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();
        Db::table('re_job')->removeOption();
        $noticeObj = new NoticeHandle();
        //给求职者发未录用信息
        $noticeObj->createNotice(7,$apply_info['hr_id'],2,$apply_info['user_id'],"您申请的".$job_info['name']."岗位未通过录用。",2,'岗位申请未通过录用');
        //给推荐者发未录用信息
        if(!empty($apply_info['agent_id'])){
            $user_info = Db::table('user')->where('id','=',$apply_info['user_id'])->find();
            Db::table('user')->removeOption();
            $noticeObj->createNotice(8,$apply_info['hr_id'],2,$apply_info['agent_id'],"您推荐".$user_info['nickname']."的".$job_info['name']."岗位未通过录用。",2,'推荐者未通过录用');
        }

    }

    public function sendPassApply($apply_id)
    {
        $applyQuery = Db::table('re_apply');
        $apply_info = $applyQuery->where('id','=',$apply_id)->find();
        $applyQuery->removeOption();
        $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();
        Db::table('re_job')->removeOption();
        $noticeObj = new NoticeHandle();
        //给求职者发未录用信息
        $noticeObj->createNotice(9,$apply_info['hr_id'],2,$apply_info['user_id'],"您申请的".$job_info['name']."岗位通过录用。",2,'岗位申请通过录用');
        //给推荐者发未录用信息
        if(!empty($apply_info['agent_id'])){
            $user_info = Db::table('user')->where('id','=',$apply_info['user_id'])->find();
            Db::table('user')->removeOption();
            $noticeObj->createNotice(10,$apply_info['hr_id'],2,$apply_info['agent_id'],"您推荐".$user_info['nickname']."的".$job_info['name']."岗位通过录用。",2,'推荐者通过录用');
        }

    }




}
