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
/**
 * 工作相关接口
 */
class Project extends Api
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


    //发布项目
    public function publish(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id = isset($data['id']) ? $data['id'] : '';
        $city_code = isset($data['city_code']) ? $data['city_code'] : '';
        $district_code = isset($data['district_code']) ? $data['district_code'] : '';
        $name = isset($data['name']) ? $data['name'] : '';

        $job_experience = isset($data['job_experience']) ? $data['job_experience'] : '';
        $education = isset($data['education']) ? $data['education'] : '';
        $mini_salary = isset($data['mini_salary']) ? $data['mini_salary'] : '';
        $max_salary = isset($data['max_salary']) ? $data['max_salary'] : '';
        $instruction = isset($data['instruction']) ? $data['instruction'] : '';
        $requirement = isset($data['requirement']) ? $data['requirement'] : '';
        $nature = isset($data['nature']) ? $data['nature'] : 0;

        $project_label1 = isset($data['project_label1']) ? $data['project_label1'] : '';
        $project_label2 = isset($data['project_label2']) ? $data['project_label2'] : '';
        $project_label3 = isset($data['project_label3']) ? $data['project_label3'] : '';
        $project_label4 = isset($data['project_label4']) ? $data['project_label4'] : '';

        $is_bonus = isset($data['is_bonus']) ? $data['is_bonus'] : 1;
        $reward = isset($data['reward']) ? $data['reward'] : 0;

        $salary_range = isset($data['salary_range']) ? $data['salary_range'] : 0;
        $need_type = isset($data['need_type']) ? $data['need_type'] : 3;

        $arr_job = [];
        $arr_job['need_type'] = $need_type;
        if(!empty($sess_key)){
            $hr_info = $this->getTUserInfo($sess_key); //hr 信息

            if(empty($hr_info['re_company_id'])){
                $this->error('未认证公司,后台审核',null,3);
            }
            $companyQuery = Db::table('re_company');
            $company_info = $companyQuery->where('id','=',$hr_info['re_company_id'])->find();
            $companyQuery->removeOption();
            /*if(!$company_info['id']){
                $this->error('未认证公司',null,3);
            }*/
            $arr_job['re_company_id'] = $company_info['id'];
            $arr_job['user_id'] = $hr_info['id'];
            $arr_job['admin_id'] = $hr_info['ad_id'];
            $arr_job['is_bonus'] = $is_bonus;
            $arr_job['reward'] = $reward;
            if(!empty($company_info['coordinate'])) $arr_job['coordinate'] = $company_info['coordinate'];
          //  if(!empty($company_info['district_code'])) $arr_job['district_code'] = $company_info['district_code'];
            if(!empty($company_info['re_line_id'])) $arr_job['re_line_id'] = $company_info['re_line_id'];

            $commonFuncObj = new CommonFunc();
            if((!empty($job_label1)||(!empty($job_label2))||(!empty($job_label3))||(!empty($job_label4)))){
                $label_arr = [$job_label1,$job_label2,$job_label3,$job_label4];
                $job_label = $commonFuncObj->labelImplode($label_arr,"/");
                $arr_job['project_label'] = $job_label;
            }
            if(!empty($city_code)) {
                $arr_job['city_code'] = $city_code;
                $arr_job['district_code'] = $district_code;
                $areasQuery = Db::table('areas');
                $area_info = $areasQuery->where('areano','=',$city_code)->find();
                $areasQuery->removeOption();
                $arr_job['prov_code'] = $area_info['parentno'];
            }else{
                $arr_job['city_code'] = $company_info['city_code'];
                $arr_job['prov_code'] = $company_info['prov_code'];
            }
            if(!empty($name)) $arr_job['name'] = $name;
            if(!empty($job_experience)) $arr_job['job_experience'] = $job_experience;
            if(!empty($education)) $arr_job['education'] = $education;
            if(!empty($nature)) $arr_job['nature'] = $nature;
            if(!empty($mini_salary)) $arr_job['mini_salary'] = $mini_salary;
            if(!empty($max_salary)) $arr_job['max_salary'] = $max_salary;
            if(!empty($instruction)) $arr_job['instruction'] = $instruction;
            if(!empty($requirement)) $arr_job['requirement'] = $requirement;
            $projectQuery = Db::table('re_project');
            if($salary_range)    {
                $daterObj = new Dater();
                $salary_range = $daterObj->getSalaryPath($salary_range);
                if(!empty($salary_range['min_salary']))  $arr_job['mini_salary'] = $salary_range['min_salary'];
                if(!empty($salary_range['max_salary']))  $arr_job['max_salary'] = $salary_range['max_salary'];
            }
            if(!empty($id)){ //编辑项目
                $arr_job['update_at'] = date("Y-m-d H:i;:s");
                $arr_job['operate_status'] = 1;
                $result = $projectQuery->where('id','=',$id)->update($arr_job);
            }else{           //新增项目

                //查看金币是否足够发布
                $hr_coin = intval($hr_info['hr_coin']);
                if($hr_coin<$reward){
                    $this->error('您的金币不足以支付佣金,请先充值!',null,3);
                }

                //扣除hr佣金和新增佣金记录
                $userQuery = Db::table('user');
                $result_user_query = $userQuery->where('id','=',$hr_info['id'])->setDec('hr_coin',$reward);
                $userQuery->removeOption();



                $arr_job['update_at'] = date("Y-m-d H:i;:s");
                $arr_job['create_at'] = date("Y-m-d H:i;:s");
                $result = $projectQuery->insertGetId($arr_job);
                //coin_log 表 减少hr 金币记录
                $coinLogQuery = Db::table('re_coin_log');
                $coin_dec_log = [
                    'user_id'=>$hr_info['id'],
                    'user_type'=>2,
                    'num'=>$reward,
                    'way'=>2,
                    'method'=>4,
                    're_apply_id'=>0,
                    're_project_id'=>$result,
                    'create_at'=>date("Y-m-d H:i:s"),
                    'update_at'=>date("Y-m-d H:i:s"),
                ];
                $coinLogQuery->insert($coin_dec_log);
            }
            $projectQuery->removeOption();
            $this->success('success');
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //岗位列表
    public function  projectList(){
        $data = $this->request->post();
        //   error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_company_id = isset($data['re_company_id']) ? $data['re_company_id'] : '';
        $is_bonus = isset($data['is_bonus']) ? $data['is_bonus'] : 1 ;
        $re_line_id = isset($data['re_line_id']) ? $data['re_line_id'] : '';
        $job_experience = isset($data['job_experience']) ? $data['job_experience'] : '';
        $mini_salary = isset($data['mini_salary']) ? $data['mini_salary'] : '';
        $max_salary = isset($data['max_salary']) ? $data['max_salary'] : '';
        $education = isset($data['education']) ? $data['education'] : '';
        $city_code = isset($data['city_code']) ? $data['city_code'] : '';
        $district_code = isset($data['district_code']) ? $data['district_code'] : '';
        $nature = isset($data['nature']) ? $data['nature'] : '';
        $create_time_num = isset($data['create_time']) ? $data['create_time'] : 1;
        $keyword = isset($data['keyword']) ? $data['keyword'] : '';
        $sort = isset($data['sort']) ? $data['sort'] : 5;
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        $hr_key = isset($data['hr_key']) ? $data['hr_key'] : '';
        $status = isset($data['status']) ? $data['status'] : '';

        //if((!empty($is_rec))&&(!empty($areano))&&(!enpty($job_type))&&(!empty($sort_way))){
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $jobQuery = Db::table('re_project');
                $jobQuery->alias('j');
               // $jobQuery->where('j.is_bonus','=',$is_bonus);
                $jobQuery->where('j.status','=',1);
                if($re_company_id)      $jobQuery->where('j.re_company_id','=',$re_company_id);
                if($status)     {
                    if($status!=6){
                        $jobQuery->where('j.status','=',$status);
                    }else{
                        $jobQuery->join('re_apply a ','a.re_project_id = j.id');
                        $jobQuery->where('a.offer ','=',5);
                    }
                }
                if($re_line_id)         $jobQuery->where('j.re_line_id','=',$re_line_id);
                if($job_experience)     $jobQuery->where('j.job_experience','=',$job_experience);
                if($mini_salary)  {
                    $mini_salary--;
                    $jobQuery->where('j.max_salary','>',$mini_salary);
                }
                if($max_salary)  {
                    $max_salary++;
                    $jobQuery->where('j.mini_salary','<',$max_salary);
                }
                if(($education!=1)&&($education!=0))         $jobQuery->where('j.education','=',$education);
                if($city_code)      $jobQuery->where('j.city_code','=',$city_code);
                if($district_code)      $jobQuery->where('j.district_code','=',$district_code);
                if($nature)      $jobQuery->where('j.nature','=',$nature);
                if($create_time_num)     {
                    $time_range = $this->getTimePath($create_time_num);
                    $time_range = array_values($time_range);
                    if(count($time_range)==2) $jobQuery->where('j.create_at','between',$time_range);
                }
                if($keyword)      $jobQuery->where('j.name','like',"%".$keyword."%");

                if($hr_key) {
                    $hr_info = $this->getGUserInfo($hr_key);
                    $jobQuery->where('j.user_id','=',$hr_info['id']);
                }

                $count = $jobQuery->count();
                $jobQuery->removeOption('field');
                $jobQuery->removeOption('order');
                $jobQuery->join('re_company c','j.re_company_id = c.id','left');
                $jobQuery->join('re_apply_mission ac','ac.re_project_id = j.id','left');
                $jobQuery->distinct(true);
                $jobQuery->group('j.id');
                $jobQuery->field('j.id,ac.id as acid,ac.hour_status,j.name,j.sign_num,j.project_label,j.status,j.mini_salary,j.max_salary,j.job_experience,j.nature,j.instruction,j.requirement,c.city_name,c.name as company_name,c.label as company_label,c.icon as company_icon,j.is_bonus,j.reward');

                $order_str = '';
                switch($sort){
                    case 1:    //最新发布
                        $order_str = " j.create_at desc,j.id desc ";
                        break;
                    case 2:     //热门工作
                        $order_str = " j.is_hot asc,j.id desc ";
                        break;
                    case 3:     //薪资最高
                        $order_str = " j.max_salary desc,j.mini_salary desc,j.id desc ";
                        break;
                    case 4:     //离我最近
                        $jobQuery->field("(st_distance (point (j.lng, j.lat),point(".$user_info['lng'].",".$user_info['lat'].") ) / 0.0111) AS distance");
                        $order_str = " distance asc,j.id desc ";
                        break;
                    case 5:     //智能排序
                        $order_str = "  j.update_at desc,j.id desc ";
                        break;
                }

                $jobQuery->order($order_str);

                $work_list = [];
                if($count>0){
                    $work_arr = $jobQuery
                        ->page($page,$page_size)
                        ->select();
                    $sql = $jobQuery->getLastSql();
                 //   print_r($sql);exit;
                    $jobQuery->removeOption();
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    foreach($work_arr as $kw=>$vw){
                        $hour_status = 0;
                        if(!empty($vw['hour_status'])){
                            $hour_status = $vw['hour_status'];
                        }
                        $work_info = [];
                        $work_info = [
                            'id'=>$vw['id'],
                            'name'=>$vw['name'],
                            'mini_salary'=>$vw['mini_salary'],
                            'max_salary'=>$vw['max_salary'],
                            'company_name'=>$vw['company_name'],
                            'is_bonus'=>$vw['is_bonus'],
                            'reward'=>$vw['reward'],
                            'sign_num'=>$vw['sign_num'],
                            'instruction'=>$vw['instruction'],
                            'requirement'=>$vw['requirement'],
                            'status'=>$vw['status'],
                            'hour_status'=>$hour_status,
                        ];
                        $work_info['company_icon'] = !empty($vw['company_icon']) ? $vw['company_icon'] : ("https://".config('webset.server_name').config('webset.default_company_icon'));
                        $work_info['job_label1'] = $work_info['job_label2'] = $work_info['job_label3'] = $work_info['job_label4'] = '';

                       // $work_info['company_label1'] = $work_info['company_label2'] = $work_info['company_label3'] = $work_info['company_label4'] = '';
                        /*if(!empty($vw['job_label'])){
                            $job_label_arr = explode("/",$vw['job_label']);
                            for ($i=0;$i<count($job_label_arr);$i++){
                                $work_info['job_label'.($i+1)] = $job_label_arr[$i];
                            }
                        }*/
                        $work_info['job_label1'] = $vw['city_name'];
                        $work_info['job_label2'] = !empty($vw['job_experience']) ? (config('webset.job_experience')[$vw['job_experience']]) : '应届毕业生';
                        $work_info['job_label3'] = !empty($vw['nature']) ? (config('webset.nature')[$vw['nature']]) : '不限';



                        /*$company_list[$kc]['label1'] = $company_list[$kc]['label2'] = $company_list[$kc]['label3'] = '';
                        $company_list[$kc]['label1'] = !empty($vc['financing']) ? (config('webset.financing')[$vc['financing']]) : '不需要融资';
                        $company_list[$kc]['label2'] = !empty($vc['scale']) ? (config('webset.scale')[$vc['scale']]) : '少于15人';
                        // $company_list[$kc]['label2'] = $vc['scale'];
                        $company_list[$kc]['label3'] = isset($vc['lname']) ? $vc['lname'] : '互联网';*/

                        /*if(!empty($vw['company_label'])){
                            $company_label_arr = explode("/",$vw['company_label']);
                            for ($i=0;$i<count($company_label_arr);$i++){
                                $work_info['company_label'.($i+1)] = $company_label_arr[$i];
                            }
                        }*/
                        $work_list[] = $work_info;
                    }
                }else{
                    $page_info = null;
                }
                $response_data = [
                    'data'=>[
                        'project_list'=>$work_list,
                        'page_info'=>$page_info,
                    ],
                ];
                $this->success('success', $response_data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }










    //工作详情
    public function projectDetail(){
        $data = $this->request->post();
        $id = isset($data['id']) ? $data['id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
   /*     $ukey = isset($data['ukey']) ? $data['ukey'] : '';
        $flag = isset($data['flag']) ? $data['flag'] : 1;*/

       /* if(!empty($ukey)){
            $this->addUser2Team($sess_key,$ukey);
        }*/
        //addUser2Team
        if((!empty($sess_key))&&(!empty($id))){
            try{
                $jobQuery = Db::table('re_project');
                $work_detail = $jobQuery
                        ->alias('j')
                        ->join('re_company c','j.re_company_id = c.id')
                        ->field('j.*,c.id as re_company_id')
                        ->where('j.id',$id)
                        ->find();
                $jobQuery->removeOption();



                $area = '';
                $city_info = Db::table('areas')->where('areano','=',$work_detail['city_code'])->find();
                $district_info = Db::table('areas')->where('areano','=',$work_detail['district_code'])->find();
                $nature = config('webset.nature')[$work_detail['nature']];
                $job_experience = '无';
                if (isset($work_detail['job_experience'])){
                    $job_experience = config('webset.job_experience')[$work_detail['job_experience']];
                }
                if (isset($work_detail['job_experience'])){
                    $need_type = config('webset.need_type')[$work_detail['need_type']];
                }



            //    $job_label_arr = explode("/",$work_detail['project_label']);
                $work_info = [
                    'id'=>$work_detail['id'],
                    're_company_id'=>$work_detail['re_company_id'],
                    'name'=>$work_detail['name'],
                    'mini_salary'=>$work_detail['mini_salary'],
                    'max_salary'=>$work_detail['max_salary'],
                    'instruction'=>$work_detail['instruction'],
                    'requirement'=>$work_detail['requirement'],
                    'need_type'=>$need_type,
                    'hr_name' =>'',
                    'hr_icon' =>'',
                    'hr_day' =>'',
                    'is_bonus' =>$work_detail['is_bonus'],
                    'reward' =>$work_detail['reward'],
                    'city_name'=>$city_info['areaname'],
                    'district_name'=>$district_info['areaname'],
                    'nature'=>$nature,
                    'job_experience'=>$job_experience,
                    'operate_status'=>$work_detail['status'],
                ];
                //$work_info['project_label1'] = $work_info['project_label2'] = $work_info['project_label3'] = $work_info['project_label4'] = '';
              //  $work_info['label1'] = $work_info['label2'] = $work_info['label3'] = $work_info['label4'] = '';
                /*if(!empty($work_detail['project_label'])){
                    $job_label_arr = explode("/",$work_detail['project_label']);
                    for ($i=0;$i<count($job_label_arr);$i++){
                        $work_info['project_label'.($i+1)] = $job_label_arr[$i];
                    }
                }*/
                /*todo job_label
                 * if(!empty($vw['company_label'])){
                    $company_label_arr = explode("/",$vw['company_label']);
                    for ($i=0;$i<count($company_label_arr);$i++){
                        $work_info['job_label'.($i+1)] = $company_label_arr[$i];
                    }
                }*/
             /*   $area_arr = explode("-",$work_detail['area']);
                $pic_swap_arr =  explode(",",$work_detail['pic_swap']);
                $pic_swap_new = [];
                foreach($pic_swap_arr as $vp){
                    $pic_swap_new[] = "https://".$_SERVER['HTTP_HOST'].$vp;
                }*/
                $is_post = 0 ;

                //判断是否申请
                $user_info = $this->getGUserInfo($sess_key);
                $uid = $user_info['id'];
                $applyQuery = Db::table('re_apply');
                $check_apply = $applyQuery
                    ->where('user_id',$uid)
                    ->where('re_project_id',$work_detail['id'])
                    ->find();
                $applyQuery->removeOption();
                empty($check_apply) ? ($is_post = 2) : ($is_post = 1);
                $work_info['is_post'] = $is_post;
                //获取hr 状态
                $userQuery = Db::table('user');
                $hr_info = $userQuery->where('id','=',$work_detail['user_id'])->find();
                if(!empty($hr_info)){
                    $work_info['hr_name'] = $hr_info['username'];
                    $work_info['hr_icon'] = $hr_info['avatar'];
                    $daterObj = new Dater();
                    $work_info['hr_day'] = $daterObj->socialDateDisplay($hr_info['logintime']);
                }

                //待录取  待结算  已完成
                $apply_count = Db::table('re_apply')
                    ->where('re_project_id','=',$id)
                    ->where('offer','=',3)
                    ->count();
                $calc_count =Db::table('re_apply_mission')
                    ->where('re_project_id','=',$id)
                    ->where('hour_status','=',7)
                    ->count();
                $finish_count = Db::table('re_apply_mission')
                    ->where('re_project_id','=',$id)
                    ->where('hour_status','=',8)
                    ->count();

                $work_info['apply_count'] = $apply_count;
                $work_info['calc_count'] = $calc_count;
                $work_info['finish_count'] = $finish_count;


                $response_data = [
                    'data'=>[
                        'job_info'=>$work_info,
                    ],
                ];
                $this->success('success', $response_data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }



    //最近发布时间函数
    public function getTimePath($num){
        $time_range = [];
        $day_time = 24*60*60;
        $now = time();
        $day_begin_time = strtotime(date("Y-m-d 00:00:00"));
        switch($num){
            case 1; //不限
                break;
            case 2; //今天发布
                $time_range['start'] = date("Y-m-d 00:00:00",$now);
                $time_range['end'] = date("Y-m-d H:i:s",$now);
                break;
            case 3; //三天内
                $start = $now - $day_time*3;
                $time_range['start'] = date("Y-m-d 00:00:00",$start);
                $time_range['end'] = date("Y-m-d H:i:s",$now);
                break;
            case 4; //一周内
                $start = $now - $day_time*7;
                $time_range['start'] = date("Y-m-d 00:00:00",$start);
                $time_range['end'] = date("Y-m-d H:i:s",$now);
                break;
            case 5; //两周内
                $start = $now - $day_time*14;
                $time_range['start'] = date("Y-m-d 00:00:00",$start);
                $time_range['end'] = date("Y-m-d H:i:s",$now);
                break;
        }
        return $time_range;
    }



    //我的任务
    public function myProjectList(){
        $data = $this->request->post();
        //   error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $status = isset($data['status']) ? $data['status'] : 0;
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        //if((!empty($is_rec))&&(!empty($areano))&&(!enpty($job_type))&&(!empty($sort_way))){
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $applyMissionQuery = Db::table('re_apply_mission');
                $applyMissionQuery->alias('m');
                $applyMissionQuery->where('m.user_id','=',$user_info['id']);
                $applyMissionQuery->where('m.status','=',$status);
                $count = $applyMissionQuery->count();
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];
                $applyMissionQuery->removeOption('field');
                $applyMissionQuery->join('re_project p','p.id = m.re_project_id','left');
                $applyMissionQuery->join('re_company c','c.id = m.re_company_id','left');
                $applyMissionQuery->join('areas s','s.areano = p.city_code','left');
                $applyMissionQuery->field('m.hour_status,p.name,p.project_label as label,p.city_code,s.areaname as city_name,p.job_experience,p.nature,p.mini_salary,p.max_salary,p.id as pid,m.create_at,m.hr_id,m.agent_id,m.id,c.name as company_name,m.status');
                $applyMissionQuery->page($page,$page_size);
                $project_list = [];
                $mission_arr = $applyMissionQuery->select();
                if(count($mission_arr)>0){
                    foreach($mission_arr as $km=>$vm){
                        $nature = config('webset.nature')[$vm['nature']];
                        $job_experience = config('webset.job_experience')[$vm['job_experience']];
                        $project_list[$km] = [
                            'id'=>$vm['id'],
                            'project_id'=>$vm['pid'],
                            'name'=>$vm['name'],
                            'mini_salary'=>$vm['mini_salary']+1,
                            'max_salary'=>$vm['max_salary']-1,
                            'status'=>$vm['status'],
                            'company_name'=>$vm['company_name'],
                            'create_at'=>$vm['create_at'],
                            'city_name'=>$vm['city_name'],
                            'mini_salary'=>$vm['mini_salary'],
                            'max_salary'=>$vm['max_salary'],
                            'nature'=>$nature,
                            'job_experience'=>$job_experience,
                            'hour_status'=>$vm['hour_status'],
                        ];
                        $project_list[$km]['label1'] = $project_list[$km]['label2'] = $project_list[$km]['label3'] = $project_list[$km]['label4'] = '';
                        if(!empty($vm['label'])){
                            $project_label_arr = explode("/",$vm['label']);
                            for ($i=0;$i<count($project_label_arr);$i++){
                                $project_list[$km]['project_label'.($i+1)] = $project_label_arr[$i];
                            }
                        }
                        $rec_user_type = empty($vm['agent_id']) ? 2 : 3;
                        $project_list[$km]['user_type'] = $rec_user_type;

                        $rec_user_id =  empty($vm['agent_id']) ? $vm['hr_id'] : $vm['agent_id'];
                        $user_info = Db::table('user')->where('id','=',$rec_user_id)->field('username,logintime')->find();
                        $daterObj = new Dater();
                        $project_list[$km]['lastlogin'] = $daterObj->socialDateDisplay($user_info['logintime']);
                        if($rec_user_type==2){
                            $project_list[$km]['ha_name'] = "来自".$vm['company_name']."的hr".$user_info['username'];
                        }else{
                            $project_list[$km]['ha_name'] = "来自经纪人".$user_info['username'];
                        }
                    }
                }

                $response_data = [
                    'data'=>[
                        'project_list'=>$project_list,
                        'page_info'=>$page_info,
                    ],
                ];
                $this->success('success', $response_data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }

    }

    //接受(开始)任务
    public function beginProject(){
        $data = $this->request->post();
        //   error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id = isset($data['id']) ? $data['id'] : 0;
        $user_info = $this->getGUserInfo($sess_key);
        $result = Db::table('re_apply_mission')
            ->where('user_id','=',$user_info['id'])
            ->where('id','=',$id)
            ->update(['start_time'=>date("Y-m-d H:i:s"),'status'=>1]);
        Db::table('re_apply_mission')->removeOption();
        if($result){
            $this->success('success');
        }else{
            $this->success('系统繁忙请稍候再试');
        }

    }




    //工时核对
    public function hourSubmit(){
        $data = $this->request->post();
        //   error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        $re_apply_mission_id = isset($data['re_hour_id']) ? $data['re_hour_id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $start_time = isset($data['start_time']) ? $data['start_time'] : '';
        $end_time = isset($data['end_time']) ? $data['end_time'] : '';
        $images = isset($data['images']) ? $data['images'] : '';
        $content = isset($data['content']) ? $data['content'] : '';
        $rate = isset($data['rate']) ? $data['rate'] : 1;

        if(!empty($sess_key)&&(!empty($re_apply_mission_id))){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $arr = ['update_at'=>date("Y-m-d H:i:s")];
                if(!empty($start_time)) $arr['start_time'] = date("Y-m-d H:i:s",strtotime($start_time));
                if(!empty($end_time)) $arr['end_time'] = date("Y-m-d H:i:s",strtotime($start_time));
                if(!empty($images)) $arr['imgs'] = serialize($images);
                if(!empty($content)) $arr['content'] = $content;
                switch ($rate){
                    case 1:
                        $arr['hour_status'] = 1;
                        break;
                    case 2:
                        $arr['hour_status'] = 4;
                        break;
                    case 3:
                        $arr['hour_status'] = 7;
                        break;
                }
                Db::table('re_apply_mission')->where('id','=',$re_apply_mission_id)->update($arr);
                Db::table('re_apply_mission')->removeOption();
                $check_hour = Db::table('re_hour')
                    ->where('re_apply_mission_id','=',$re_apply_mission_id)
                    ->where('rate','=',$rate)
                    ->find();
                Db::table('re_hour')->removeOption();
                $arr['status'] = 1;
                $arr['rate'] = $rate;
                $mission_info = Db::table('re_apply_mission')->where('id','=',$re_apply_mission_id)->find();

                Db::table('re_apply_mission')->removeOption();
                $arr['re_apply_id'] = $mission_info['re_apply_id'];
                $arr['re_apply_mission_id'] = $re_apply_mission_id;
                unset($arr['hour_status']);
                $arr['user_id'] = $user_info['id'];
                if(empty($check_hour)){
                    $arr['create_at'] = date("Y-m-d H:i:s");
                    $result = Db::table('re_hour')->insertGetId($arr);
                }else{
                    $result = Db::table('re_hour')->where('id','=',$check_hour['id'])->update($arr);
                }
                if($result){
                    $this->success('success');
                }else{
                    $this->success('系统繁忙请稍候再试');
                }
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //工时核对信息
    public function hourSubmitInfo(){
        $data = $this->request->post();
        //   error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        $re_apply_mission_id = isset($data['re_apply_mission_id']) ? $data['re_apply_mission_id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        if(!empty($sess_key)&&(!empty($re_apply_mission_id))){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $apply_mission = Db::table('re_apply_mission')
                    ->where('id','=',$re_apply_mission_id)
                    ->where('user_id','=',$user_info['id'])
                    ->find();
                Db::table('re_apply_mission')->removeOption();
                if($apply_mission['hour_status']==0){  //没有核对过工时
                    $info = [
                        'start_time'=>0,
                        'end_time'=>0,
                        // 'end_time'=>$result['end_time'],
                        'hour'=>0,
                        'status'=>$apply_mission['status'],
                        'hour_status'=>$apply_mission['hour_status'],
                        'content'=>$apply_mission['content'],
                        'images'=>[],
                    ];
                }else{
                    $hour_info = Db::table('re_hour')->where('re_apply_mission_id','=',$re_apply_mission_id)->select();
                    $start_time = strtotime($apply_mission['start_time']);
                    $end_time = strtotime($apply_mission['end_time']);
                    $hour = ($end_time-$start_time)/3600;
                    $images = unserialize($apply_mission['imgs']);
                    $info = [
                        'start_time'=>date("Y-m-d",$start_time),
                        'end_time'=>date("Y-m-d",$end_time),
                        'hour_status'=>$apply_mission['hour_status'],
                        'hour'=>$hour,
                        'status'=>$apply_mission['status'],
                        'content'=>$apply_mission['content'],
                        'images'=>$images,
                    ];
                }
                $response_data = [
                    'data'=>[
                        'info'=>$info,
                    ],
                ];
                if($info){
                    $this->success('success',$response_data);
                }else{
                    $this->success('系统繁忙请稍候再试');
                }
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }

    }

    //我的任务详情
    public function myProDetail(){
        $data = $this->request->post();
        //   error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id = isset($data['id']) ? $data['id'] : 0;
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        //if((!empty($is_rec))&&(!empty($areano))&&(!enpty($job_type))&&(!empty($sort_way))){
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $applyMissionQuery = Db::table('re_apply_mission');
                $applyMissionQuery->alias('m');
                $applyMissionQuery->where('m.id','=',$id);
                $applyMissionQuery->join('re_project p','p.id = m.re_project_id','left');
                $applyMissionQuery->join('re_company c','c.id = m.re_company_id','left');
                $applyMissionQuery->field('m.start_time,m.end_time,m.content,p.name,p.project_label as label,p.mini_salary,p.max_salary,p.id as pid,m.create_at,m.imgs,m.hr_id,m.agent_id,m.id,c.name as company_name,m.status');
                $mission_info = $applyMissionQuery->find();
                $project_info = [
                    'company_name'=>$mission_info['company_name'],
                    'name'=>$mission_info['name'],
                    'start_time'=>date("Y-m-d",strtotime($mission_info['start_time'])),
                    'end_time'=>date("Y-m-d",strtotime($mission_info['end_time'])),
                    'content'=>$mission_info['content'],
                    'status'=>$mission_info['status'],
                ];
             /*   $project_info['label1'] = $project_info['label2'] = $project_info['label3'] = $project_info['label4'] = '';
                if(!empty($mission_info['label'])){
                    $project_label_arr = explode("/",$mission_info['label']);
                    for ($i=0;$i<count($project_label_arr);$i++){
                        $project_info['project_label'.($i+1)] = $project_label_arr[$i];
                    }
                }*/
                if(!empty($project_info['imgs'])){
                    $img_arr = unserialize($project_info['imgs']);
                    if(count($img_arr)>0){
                        $project_info['img_arr'] = $img_arr;
                    }
                }
                $response_data = [
                    'data'=>[
                        'project_info'=>$project_info,
                    ],
                ];
                $this->success('success', $response_data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }

    }




















































    //工作列表
    public function  miniWorkList(){
        $data = $this->request->post();
        //   error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        $is_rec = isset($data['is_rec']) ? $data['is_rec'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $areano = isset($data['areano']) ? $data['areano'] : 0;
        if($areano=="999999"){
            $areano =0;
        }
        $job_type = isset($data['job_type']) ? $data['job_type'] : '';
        $sort_way = isset($data['sort_way']) ? $data['sort_way'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        //if((!empty($is_rec))&&(!empty($areano))&&(!enpty($job_type))&&(!empty($sort_way))){
        if(!empty($sess_key)){
            try{
                $map = [];
                ($areano) ? ($map['j.city_code']=$areano) : '';
                ($job_type) ? ($map['j.re_jobtype_id']=$job_type) : '';
                /*   $map['city_code'] = ($areano) ??  "";
                   $map['re_jobtype_id'] = $job_type ??  '';*/
                switch ($sort_way)
                {
                    case 1:
                        $order = 'j.update_at desc ';
                        break;
                    case 2:
                        $order = 'j.max_salary desc ';
                        break;
                    case 3:
                        $order = 'j.reward desc ';
                        break;
                    default:
                        $order = 'j.id desc ';
                        break;
                }
                $work_list = Db::table('re_job')
                    ->alias('j')
                    ->join('re_company c ','j.re_company_id = c.id')
                    ->where('c.status','=',1)
                    ->where('j.re_jobtype_id','=',1)
                    ->where('j.status','=',1)
                    ->where('j.reward_up','>',0)
                    ->where($map)
                    ->field('j.*,c.name as company_name')
                    ->order($order)
                    ->page($page,$page_size)
                    ->select();

                $data = [];
                if(!empty($work_list)){

                    $count = Db::table('re_job')
                        ->alias('j')
                        ->join('re_company c ','j.re_company_id = c.id')
                        ->where('c.status','=',1)
                        ->where('j.re_jobtype_id','=',1)
                        ->where('j.status','=',1)
                        ->where('j.reward_up','>',0)
                        ->where($map)
                        ->count();

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



//
    //首页工作列表
    public function  recWorkList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $search_name = $data['search_name'] ?? "";
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $prov_code = $data['prov_code'] ?? "";
        $city_code = $data['city_code'] ?? "";
        $district_code = $data['district_code'] ?? "";

        if(!empty($sess_key)){
            $this->validSessKey($sess_key);
            $arr = [  'openid', 'session_key' ];
            $sess_info = $this->redis->hmget($sess_key,$arr);
            $openid_re = $sess_info['openid'];
            $user_info = Db::table('user')
                ->where('openid_re',$openid_re)
                ->field('id,username,mobile,gender,birthday,available_balance,district_code,city_code')
                ->find();
            $dis_flag = "district";
            if(empty($district_code)&&empty($city_code)&&empty($prov_code)){
                $district_code = $user_info['district_code'];
                $city_code = $user_info['city_code'];
            }else{
                if(!empty($district_code)){

                }elseif(!empty($city_code)){
                    $dis_flag = "city";
                }elseif (!empty($prov_code)){
                    $dis_flag = "prov";
                }
            }

            try{
                $comp_ids = '';
                $job_ids = '';
                if (!empty($search_name)){
                    //找到对应公司id
                    $comp_list = Db::table('re_company')
                        ->where('name','like','%'.$search_name.'%')
                        ->where('status','=',1)
                        ->select();
                    if(!empty($comp_list)){
                        foreach($comp_list as $kc=>$vc){
                            $comp_ids.=$vc['id'].",";
                        }
                        $comp_ids = substr($comp_ids,0,strlen($comp_ids)-1);
                    }
                    //找到对应职位id
                    $job_list = Db::table('re_job')
                        ->alias('j')
                        ->join('re_company c ','j.re_company_id = c.id')
                        ->field('j.id')
                        ->where('c.status','=',1)
                        ->where('j.status','=',1)
                        ->where('j.reward_up','>',0)
                        ->where('j.name','like','%'.$search_name.'%')
                        // ->where('j.is_hot','=',1)
                        ->order('j.update_at desc')
                        ->select();

                    if(!empty($job_list)){
                        foreach($job_list as $kc=>$vc){
                            $job_ids.=$vc['id'].",";
                        }
                        $job_ids = substr($job_ids,0,strlen($job_ids)-1);
                    }
                    $count = Db::table('re_job')
                        ->alias('j')
                        ->join('re_company c ','j.re_company_id = c.id')
                        ->where('j.re_company_id','in',$comp_ids)
                        ->where('j.reward_up','>',0)
                        ->whereOr('j.id','in',$job_ids)
                        ->count();
                    if(!empty($count)){
                        $page_info = [
                            'cur_page'=>$page,
                            'page_size'=>$page_size,
                            'total_items'=>$count,
                            'total_pages'=>ceil($count/$page_size)
                        ];

                        $job_list = Db::table('re_job')
                            ->alias('j')
                            ->join('re_company c ','j.re_company_id = c.id')
                            ->field('j.*,c.name as company_name')
                            ->where('j.re_company_id','in',$comp_ids)
                            ->whereOr('j.id','in',$job_ids)
                            ->where('j.reward_up','>',0)
                            ->page($page,$page_size)
                            ->select();
                    }
                }else{

                    $count = Db::table('re_job')
                        ->alias('j')
                        ->join('re_company c ','j.re_company_id = c.id')
                        ->where('c.status','=',1)
                        ->where('j.status','=',1)
                        ->where('j.is_hot','=',1)
                        ->where('j.reward_up','>',0)
                        ->field('j.*,c.name as company_name')
                        ->count();


                    if(!empty($count)){
                        $page_info = [
                            'cur_page'=>$page,
                            'page_size'=>$page_size,
                            'total_items'=>$count,
                            'total_pages'=>ceil($count/$page_size)
                        ];
                        if(  $dis_flag == "district"){
                            $field = "j.*,c.name as company_name, case j.district_code when '".$district_code."' then 1 else 2 end as district_rank";
                            $order = "district_rank asc";
                        }elseif( $dis_flag == "city"){
                            $field = "j.*,c.name as company_name, case j.district_code when '".$district_code."' then 1 else 2 end as district_rank, case j.city_code when '".$city_code."' then 1 else 2 end as city_rank ";
                            $order = "city_rank asc ,district_rank asc";
                        }elseif($dis_flag == "prov"){
                            $field = "j.*,c.name as company_name, case j.district_code when '".$district_code."' then 1 else 2 end as district_rank, case j.prov_code when '".$prov_code."' then 1 else 2 end as prov_rank ";
                            $order = "prov_rank asc ,district_rank asc";
                        }

                        $job_list = Db::table('re_job')
                            ->alias('j')
                            ->join('re_company c ','j.re_company_id = c.id')
                            ->field($field)
                            ->field('j.*,c.name as company_name')
                            ->where('c.status','=',1)
                            ->where('j.status','=',1)
                            ->where('j.is_hot','=',1)
                            ->where('j.reward_up','>',0)
                            //->order('district_rank asc')
                            ->order($order)
                            ->order('j.update_at desc')
                            ->page($page,$page_size)
                            ->select();
                        //   echo Db::table('re_job')->getlastsql();
                    }
                }
                if(!empty($job_list)){
                    foreach($job_list as $kw=>$vw){
                        $keyword_arr = explode("/",$vw['keyword']);
                        $area_arr = explode("-",$vw['area']);
                        $pic_env_arr = explode(",",$vw['pic_env']);
                        $pic_env = $pic_env_arr['0'];

                        $data1[] = [
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
                }else{
                    $data1 = null;
                    $page_info=null;
                }
                $data = [
                    'data'=>$data1,
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

    public function wordCut($str,$len){
        $len_str = mb_strlen($str);
        if($len_str>$len){
            return mb_substr($str,0,$len)."...";
        }else{
            return $str;
        }

    }


    //申请工作
    public function applyWork(){
        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        $rec_user_id = $data['rec_user_id'] ?? '';
        $id = $data['id'] ?? '';
        if((!empty($sess_key))&&(!empty($id))){
            try {
                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id,nickname')
                    ->find();

                $uid = $user_info['id'];
                $user_team =  Db::table('user_team')
                    ->where('low_user_id',$user_info['id'])
                    ->field('up_user_id')
                    ->find();
                $rec_user_id = empty($user_team['up_user_id']) ? $rec_user_id : $user_team['up_user_id'];
                //判断用户是否完善了resume
                $resume_info = Db::table('re_resume')
                    ->where('user_id',$uid)
                    ->find();
                if((!empty($resume_info['name']))&&(!empty($resume_info['age']))&&(!empty($resume_info['mobile']))){
                    //通过员工档案表查询用户所在的公司和管理员id
                    $job_info = Db::table('re_job')
                        ->where('id',$id)
                        ->field('re_company_id,admin_id,name')
                        ->find();

                    $add_info = [
                        'user_id' => $uid,
                        're_job_id' => $id,
                        're_company_id' => $job_info['re_company_id'],
                        'admin_id' => $job_info['admin_id'],
                        'offer' => 0,
                        're_resume_id'=>$resume_info['id'],
                        'rec_user_id'=>$rec_user_id,
                        'create_at' => date("Y-m-d H:i:s",time()),
                        'update_at' => date("Y-m-d H:i:s",time()),
                    ];
                    //查看是否有预约申请记录
                    $check_job = Db::table('re_apply')->where('re_job_id','=',$id)->where('user_id','=',$uid)->find();
                    if(empty($check_job)){
                        $result = Db::table('re_apply')->insert($add_info);
                        if(!empty($result)){
                            //查找驻场人员
                            $noticeHandleObj = new NoticeHandle();
                           // $bind_list = $this->getBindUsersByAdminCompanyId($job_info['admin_id'],$job_info['re_company_id']);
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
                            }
                            $this->success('success',null);
                        }else{
                            $this->error('网络繁忙,请稍后再试');
                        }
                    }else{
                        $this->success('success',null);
                    }


                }else{
                    $this->error('请先完善个人信息再投递简历!',null,3);
                }

            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }


    //申请的工作记录
    public function  applyWorkList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        if(!empty($sess_key)){
            try{

                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id,username,mobile,gender,birthday,available_balance')
                    ->find();

                $count = Db::table('re_apply')
                    ->alias('a')
                    ->join('re_company c','a.re_company_id = c.id')
                    ->join('re_job j','a.re_job_id = j.id')
                    ->where('a.user_id',$user_info['id'])
                    ->count();

                if(!empty($count)){

                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];



                    $apply_list = Db::table('re_apply')
                        ->alias('a')
                        ->join('re_company c','a.re_company_id = c.id')
                        ->join('re_job j','a.re_job_id = j.id')
                        ->where('a.user_id',$user_info['id'])
                        ->field('a.*,j.name,j.reward,j.mini_salary,j.max_salary,j.area,j.keyword,j.update_at,j.pic_env,a.offer')
                        ->order('a.id desc')
                        ->page($page,$page_size)
                        ->select();

                    if(!empty($apply_list)){
                        foreach($apply_list as $kw=>$vw){
                            $pic_env_arr = explode(",",$vw['pic_env']);
                            $pic_env = $pic_env_arr['0'];
                            $keyword_arr = explode("/",$vw['keyword']);
                            $area_arr = explode("-",$vw['area']);
                            switch($vw['offer']){
                                case 0:
                                    $offer = "已申请";
                                    break;
                                case 1:
                                    $offer = "已录用";
                                    break;
                                case 2:
                                    $offer = "已拒绝";
                                    break;
                                case 3:
                                    $offer = "已查看";
                                    break;
                                case 4:
                                    $offer = "通知面试";
                                    break;
                                case 5:
                                    $offer = "已离职";
                                    break;
                            }

                            $data_res[] = [
                                'id'=>$vw['id'],
                                're_job_id'=>$vw['re_job_id'],
                                'pic_env'=>"https://".$_SERVER['HTTP_HOST'].$pic_env,
                                'name'=>$vw['name'],
                                'reward'=>$vw['reward'],
                                'offer'=>$offer,
                                'mini_salary'=>intval($vw['mini_salary']),
                                'max_salary'=>intval($vw['max_salary']),
                                'update_at'=>$this->handleTime($vw['update_at']),
                                'keyword1'=>$keyword_arr[0] ?? '',
                                'keyword2'=>$keyword_arr[1] ?? '',
                                'city_name'=>$area_arr[1] ?? '',
                            ];
                        }

                    }else{
                        $data_res = null;
                        $page_info = null;
                    }



                    $data = [
                        'data'=>$data_res,
                        'page_info'=>$page_info,
                    ];
                }else{
                    $data = [
                        'data'=>null,
                        'page_info'=>null,
                    ];
                }
                $this->success('success', $data);

            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //申请的工作记录
    public function  collectWorkList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        if(!empty($sess_key)){
            try{
                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id,username,mobile,gender,birthday,available_balance')
                    ->find();

                $count = Db::table('re_collect')
                    ->alias('a')
                    ->join('re_company c','a.re_company_id = c.id')
                    ->join('re_job j','a.re_job_id = j.id')
                    ->where('a.user_id',$user_info['id'])
                    ->count();
                if(!empty($count)){

                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];



                    $apply_list = Db::table('re_collect')
                        ->alias('a')
                        ->join('re_company c','a.re_company_id = c.id')
                        ->join('re_job j','a.re_job_id = j.id')
                        ->where('a.user_id',$user_info['id'])
                        ->field('a.*,j.name,j.reward,j.mini_salary,j.max_salary,j.area,j.keyword,j.update_at,j.pic_env')
                        ->order('a.id desc')
                        ->page($page,$page_size)
                        ->select();
                    if(!empty($apply_list)){
                        foreach($apply_list as $kw=>$vw){
                            $pic_env_arr = explode(",",$vw['pic_env']);
                            $pic_env = $pic_env_arr['0'];
                            $keyword_arr = explode("/",$vw['keyword']);
                            $area_arr = explode("-",$vw['area']);
                            $data_res[] = [
                                'id'=>$vw['id'],
                                're_job_id'=>$vw['re_job_id'],
                                'pic_env'=>"https://".$_SERVER['HTTP_HOST'].$pic_env,
                                'name'=>$vw['name'],
                                'reward'=>$vw['reward'],
                                'mini_salary'=>intval($vw['mini_salary']),
                                'max_salary'=>intval($vw['max_salary']),
                                'update_at'=>$this->handleTime($vw['update_at']),
                                'keyword1'=>$keyword_arr[0] ?? '',
                                'keyword2'=>$keyword_arr[1] ?? '',
                                'city_name'=>$area_arr[1] ?? '',
                            ];
                        }
                    }else{
                        $data_res = null;
                        $page_info = null;
                    }
                    $data = [
                        'data'=>$data_res,
                        'page_info'=>$page_info,
                    ];
                }else{
                    $data = [
                        'data'=>null,
                        'page_info'=>null,
                    ];
                }
                $this->success('success', $data);

            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }



    //收藏工作
    public function collectWork(){
        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        $id = $data['id'] ?? '';
        if((!empty($sess_key))&&(!empty($id))){
            try {
                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id')
                    ->find();
                $uid = $user_info['id'];
                //查看工作信息
                $job_info = Db::table('re_job')
                    ->where('id',$id)
                    ->field('re_company_id,admin_id')
                    ->find();

                $add_info = [
                    'user_id' => $uid,
                    're_job_id' => $id,
                    're_company_id' => $job_info['re_company_id'],
                    'create_at' => date("Y-m-d H:i:s",time()),
                    'update_at' => date("Y-m-d H:i:s",time()),

                ];
                $result = Db::table('re_collect')->insert($add_info);
                if(!empty($result)){
                    $this->success('success',null);
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


    //取消收藏工作
    public function unCollectWork(){
        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        $id = $data['id'] ?? '';
        if((!empty($sess_key))&&(!empty($id))){
            try {
                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id')
                    ->find();
                $uid = $user_info['id'];
                //查看工作信息
                $job_info = Db::table('re_job')
                    ->where('id',$id)
                    ->field('re_company_id,admin_id')
                    ->find();

                $del_info = [
                    'user_id' => $uid,
                    're_job_id' => $id,
                ];

                $result = Db::table('re_collect')->where($del_info)->delete();

                if(!empty($result)){
                    $this->success('success',null);
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



}
