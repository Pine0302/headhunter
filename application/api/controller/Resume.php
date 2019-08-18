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
class Resume extends Api
{

    //如果$noNeedLogin为空表示所有接口都需要登录才能请求
    //如果$noNeedRight为空表示所有接口都需要验证权限才能请求
    //如果接口已经设置无需登录,那也就无需鉴权了
    //
    // 无需登录的接口,*表示全部
    //  protected $noNeedLogin = ['test1","login'];
    protected $noNeedLogin = ['*'];
    // 无需鉴权的接口,*表示全部
   //  /protected $noNeedRight = ['test2'];
    protected $noNeedRight = ['*'];

    //求职意向
    public function intension(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_job_id = isset($data['re_job_id']) ? $data['re_job_id'] : '';
        $nature = isset($data['nature']) ? $data['nature'] : '';
        $mini_salary = isset($data['mini_salary']) ? $data['mini_salary'] : '';
        $max_salary = isset($data['max_salary']) ? $data['max_salary'] : '';
        $salary_range = isset($data['salary_range']) ? $data['salary_range'] : '';
        $will_city_code = isset($data['city_id']) ? $data['city_id'] : '';
        $will = isset($data['will']) ? $data['will'] : '';
        $intime = isset($data['intime']) ? $data['intime'] : '';
        $job_name = isset($data['job_name']) ? $data['job_name'] : '';


        if(!empty($sess_key)){
            //查看用户是否有简历,有,更新 ,没有, 添加
            $commonFuncObj = new CommonFunc();
            $arr_resume['type'] = 1; //普通简历
            if(!empty($re_job_id)) $arr_resume['re_job_id'] = $re_job_id;
            if(!empty($nature)) $arr_resume['nature'] = $nature;
            if(!empty($mini_salary)) {
                $arr_resume['mini_salary'] = $mini_salary;
                if($mini_salary*12>300000){
                    $arr_resume['type']=2;
                }
            }
            if(!empty($max_salary)) $arr_resume['max_salary'] = $max_salary;
            if(!empty($will_city_code)) $arr_resume['will_city_code'] = $will_city_code;
            if(!empty($will)) $arr_resume['will'] = $will;
            if(!empty($intime)) $arr_resume['intime'] = $intime;
            if(!empty($job_name)) $arr_resume['job_name'] = $job_name;
            $user_info = $this->getGUserInfo($sess_key);
            $resumeQuery = Db::table('re_resume');
            $check_resume = $resumeQuery->where('user_id','=',$user_info['id'])->field('id')->find();
            $resumeQuery->removeOption('where');
            $resumeQuery->removeOption('field');
            if($salary_range)    {
                $daterObj = new Dater();
                $salary_range = $daterObj->getSalaryPath($salary_range);

                if(!empty($salary_range['min_salary']))  $arr_resume['mini_salary'] = $salary_range['min_salary']+1;
                if($salary_range['min_salary']*12>300000){
                    $arr_resume['type']=2;
                }
                if(!empty($salary_range['max_salary'])) $arr_resume['max_salary'] = $salary_range['max_salary']-1;
                    //$resumeQuery->where('max_salary','<',$salary_range['max_salary']);
            }
            if(!empty($check_resume)){
                $arr_resume['update_at'] = date("Y-m-d H:i:s");
                $resumeQuery->where('id','=',$check_resume['id'])->update($arr_resume);
            }else{
                $arr_resume['update_at'] = date("Y-m-d H:i:s");
                $arr_resume['create_at'] = date("Y-m-d H:i:s");
                $arr_resume['user_id'] = $user_info['id'];
                $resumeQuery->insert($arr_resume);
            }
            $this->success('success');
        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //完善简历基本信息
    public function resumeFillBasic(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $user_name = isset($data['user_name']) ? $data['user_name'] : '';
        $gender = isset($data['gender']) ? $data['gender'] : '';
        $birthday = isset($data['birthday']) ? $data['birthday'] : '';
        $work_begin_time = isset($data['work_begin_time']) ? $data['work_begin_time'] : '';
        $identity = isset($data['identity']) ? $data['identity'] : '';
        $mobile = isset($data['mobile']) ? $data['mobile'] : '';
        $email = isset($data['email']) ? $data['email'] : '';
        $re_job_id = isset($data['re_job_id']) ? $data['re_job_id'] : '';
        $job_name = isset($data['job_name']) ? $data['job_name'] : '';
        $city_code = isset($data['city_code']) ? $data['city_code'] : '';
        $district_code = isset($data['district_code']) ? $data['district_code'] : '';
        $prov_code = isset($data['prov_code']) ? $data['prov_code'] : '';
        $city_name = isset($data['city_name']) ? $data['city_name'] : '';
        $district_name = isset($data['district_name']) ? $data['district_name'] : '';
        $prov_name = isset($data['prov_name']) ? $data['prov_name'] : '';


        $label1 = isset($data['label1']) ? $data['label1'] : '';
        $label2 = isset($data['label2']) ? $data['label2'] : '';
        $label3 = isset($data['label3']) ? $data['label3'] : '';
        $label4 = isset($data['label4']) ? $data['label4'] : '';

        if(!empty($sess_key)){
            //查看用户是否有简历,有,更新 ,没有, 添加
            $commonFuncObj = new CommonFunc();
            if((!empty($label1)||(!empty($label2))||(!empty($label3))||(!empty($label4)))){
                $label_arr = [$label1,$label2,$label3,$label4];
                $label = $commonFuncObj->labelImplode($label_arr,"/");
                $arr_resume['label'] = $label;
            }
            if(!empty($user_name)) $arr_resume['name'] = $user_name;
            if(!empty($gender)) $arr_resume['sex'] = $gender;
            if(!empty($birthday)) $arr_resume['birthday'] = $birthday;
            if(!empty($work_begin_time)) $arr_resume['work_begin_time'] = $work_begin_time;
            if(!empty($identity)) $arr_resume['identity'] = $identity;
            if(!empty($mobile)) $arr_resume['mobile'] = $mobile;
            if(!empty($email)) $arr_resume['email'] = $email;
            if(!empty($re_job_id)) $arr_resume['re_job_id'] = $re_job_id;
            if(!empty($job_name)) {
                $arr_resume['job_name'] = $job_name;
                $arr_resume['title'] = $job_name;
            }
            if(!empty($prov_code)) $arr_resume['prov_code'] = $prov_code;
            if(!empty($prov_name)) $arr_resume['prov_name'] = $prov_name;
            if(!empty($city_code)) $arr_resume['city_code'] = $city_code;
            if(!empty($city_name)) $arr_resume['city_name'] = $city_name;
            if(!empty($district_code)) $arr_resume['district_code'] = $district_code;
            if(!empty($district_name)) $arr_resume['district_name'] = $district_name;
            $arr_resume['update_at'] = date("Y-m-d H:i:s");
            $user_info = $this->getGUserInfo($sess_key);

            $resumeQuery = Db::table('re_resume');
            $check_resume = $resumeQuery->where('user_id','=',$user_info['id'])->field('id')->find();
            $resumeQuery -> removeOption('field');
            if(!empty($check_resume)){
                $resumeQuery->where('id','=',$check_resume['id'])->update($arr_resume);
            }else{
                $arr_resume['create_at'] = date("Y-m-d H:i:s");
                $arr_resume['user_id'] = $user_info['id'];
                $resumeQuery->insert($arr_resume);
            }
            $this->success('success');
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //完善简历自我描述
    public function resumeFillSelf(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $self_introduction = isset($data['self_introduction']) ? $data['self_introduction'] : '';
        if(!empty($sess_key)){
            $arr_resume['update_at'] = date("Y-m-d H:i:s");
            $arr_resume['self_introduction'] = $self_introduction;
            $user_info = $this->getGUserInfo($sess_key);
            $resumeQuery = Db::table('re_resume');
            $check_resume = $resumeQuery->where('user_id','=',$user_info['id'])->field('id')->find();
            $resumeQuery -> removeOption();
            $resumeQuery = Db::table('re_resume');
            if(!empty($check_resume)){
                $resumeQuery->where('id','=',$check_resume['id'])->update($arr_resume);
            }else{
                $arr_resume['create_at'] = date("Y-m-d H:i:s");
                $arr_resume['user_id'] = $user_info['id'];
                $resumeQuery->insert($arr_resume);
            }
            $this->success('success');
        }else{
            $this->error('缺少参数',null,2);
        }
    }
    //完善简历教育经历
    public function resumeFillEducation(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_resume_education_id = isset($data['re_resume_education_id']) ? $data['re_resume_education_id'] : 0;
        $type = isset($data['type']) ? $data['type'] : 1;
        $name = isset($data['name']) ? $data['name'] : '';
        $major = isset($data['major']) ? $data['major'] : 0;
        $level = isset($data['level']) ? $data['level'] : 1;
        $start_time = isset($data['start_time']) ? $data['start_time'] : '';
        $end_time = isset($data['end_time']) ? $data['end_time'] : '';
        $icon = isset($data['icon']) ? $data['icon'] : '';
        if(!empty($sess_key)){
            $user_info = $this->getGUserInfo($sess_key);
            $resumeQuery = Db::table('re_resume');
            $resumeEducationQuery = Db::table('re_resume_education');
            $resume_basic_info = [];
            $resume_basic_info = $resumeQuery->where('user_id','=',$user_info['id'])->field('id')->find();
            $resumeQuery->removeOption('where');
            $arr_resume_education = [];
            if(!empty($name)) $arr_resume_education['name'] = $name;
            //if(!empty($re_resume_education_id)) $arr_resume_education['re_resume_education_id'] = $re_resume_education_id;
            if(!empty($major)) $arr_resume_education['major'] = $major;
            if(!empty($level)) $arr_resume_education['level'] = $level;
            if(!empty($start_time)) $arr_resume_education['start_time'] = $start_time;
            if(!empty($end_time)) $arr_resume_education['end_time'] = $end_time;
            if(!empty($icon)) $arr_resume_education['icon'] = $icon;
            $arr_resume_education['user_id'] = $user_info['id'];
            $arr_resume_education['re_resume_id'] = $resume_basic_info['id'];
            switch ($type){
                case 1:     //新增教育经历
                    $arr_resume_education['icon'] = (!empty($icon)) ? $icon : ("https://".config('webset.server_name').config('webset.default_company_icon'));
                    $result = $resumeEducationQuery->insert($arr_resume_education);
                    break;
                case 2:     //删除教育经历
                    $result = $resumeEducationQuery->delete(['id'=>$re_resume_education_id]);
                    break;
                case 3:     //修改教育经历
                    $result = $resumeEducationQuery->where('id','=',$re_resume_education_id)->update($arr_resume_education);
                    break;
            }
            $this->success('success');
        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //完善简历工作经历
    public function resumeFillWork(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_resume_work_id = isset($data['re_resume_work_id']) ? $data['re_resume_work_id'] : 0;
        $type = isset($data['type']) ? $data['type'] : 1;
        $name = isset($data['name']) ? $data['name'] : '';
        $major = isset($data['major']) ? $data['major'] : '';
        $start_time = isset($data['start_time']) ? $data['start_time'] : '';
        $end_time = isset($data['end_time']) ? $data['end_time'] : '';
        $icon = isset($data['icon']) ? $data['icon'] : '';
        $content = isset($data['content']) ? $data['content'] : '';
        $re_company_id = isset($data['re_company_id']) ? $data['re_company_id'] : '';
        if(!empty($sess_key)){
            $user_info = $this->getGUserInfo($sess_key);
            $resumeQuery = Db::table('re_resume');
            $resumeWorkQuery = Db::table('re_resume_work');
            $resume_basic_info = [];
            $resume_basic_info = $resumeQuery->where('user_id','=',$user_info['id'])->field('id')->find();
            $resumeQuery->removeOption('where');
            $arr_resume_work = [];
            if(!empty($name)) $arr_resume_work['name'] = $name;
            //if(!empty($re_resume_work_id)) $arr_resume_work['re_resume_work_id'] = $re_resume_work_id;
            if(!empty($major)) $arr_resume_work['major'] = $major;
            if(!empty($start_time)) $arr_resume_work['start_time'] = $start_time;
            if(!empty($end_time)) $arr_resume_work['end_time'] = $end_time;
            if(!empty($icon)) $arr_resume_work['icon'] = $icon;
            if(!empty($content)) $arr_resume_work['content'] = $content;
            if(!empty($re_company_id)) $arr_resume_work['re_company_id'] = $re_company_id;
            $arr_resume_work['user_id'] = $user_info['id'];
            $arr_resume_work['re_resume_id'] = $resume_basic_info['id'];
            switch ($type){
                case 1:     //新增教育经历
                    $arr_resume_work['icon'] = (!empty($icon)) ? $icon : ("https://".config('webset.server_name').config('webset.default_company_icon'));
                    $result = $resumeWorkQuery->insert($arr_resume_work);
                    break;
                case 2:     //删除教育经历
                    $result = $resumeWorkQuery->delete(['id'=>$re_resume_work_id]);
                    break;
                case 3:     //修改教育经历
                    $result = $resumeWorkQuery->where('id','=',$re_resume_work_id)->update($arr_resume_work);
                    break;
            }
            $this->success('success');
        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //完善简历项目经历
    public function resumeFillProject(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_resume_project_id = isset($data['re_resume_work_id']) ? $data['re_resume_work_id'] : 0;
        $type = isset($data['type']) ? $data['type'] : 1;
        $name = isset($data['name']) ? $data['name'] : '';
        $major = isset($data['major']) ? $data['major'] : '';
        $start_time = isset($data['start_time']) ? $data['start_time'] : '';
        $end_time = isset($data['end_time']) ? $data['end_time'] : '';
        $content = isset($data['content']) ? $data['content'] : '';
        if(!empty($sess_key)){
            $user_info = $this->getGUserInfo($sess_key);
            $resumeQuery = Db::table('re_resume');
            $resumeProjectQuery = Db::table('re_resume_project');
            $resume_basic_info = [];
            $resume_basic_info = $resumeQuery->where('user_id','=',$user_info['id'])->field('id')->find();
            $resumeQuery->removeOption('where');
            $arr_resume_project = [];
            if(!empty($name)) $arr_resume_project['name'] = $name;
            //if(!empty($re_resume_project_id)) $arr_resume_project['re_resume_project_id'] = $re_resume_project_id;
            if(!empty($major)) $arr_resume_project['major'] = $major;
            if(!empty($start_time)) $arr_resume_project['start_time'] = $start_time;
            if(!empty($end_time)) $arr_resume_project['end_time'] = $end_time;
            if(!empty($content)) $arr_resume_project['content'] = $content;
            $arr_resume_project['user_id'] = $user_info['id'];
            $arr_resume_project['re_resume_id'] = $resume_basic_info['id'];
            switch ($type){
                case 1:     //新增项目经历
                    $result = $resumeProjectQuery->insert($arr_resume_project);
                    break;
                case 2:     //删除项目经历
                    $result = $resumeProjectQuery->delete(['id'=>$re_resume_project_id]);
                    break;
                case 3:     //修改项目经历
                    $result = $resumeProjectQuery->where('id','=',$re_resume_project_id)->update($arr_resume_project);
                    break;
            }
            $this->success('success');
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //岗位列表
    public function  resumeList(){
        $data = $this->request->post();

        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $job_experience = isset($data['job_experience']) ? $data['job_experience'] : '';
        $education = isset($data['education']) ? $data['education'] : '';
        $city_code = isset($data['city_code']) ? $data['city_code'] : '';
      //  $city_code = '';
     //   $re_line_id = isset($data['re_line_id']) ? $data['re_line_id'] : '';
        $nature = isset($data['nature']) ? $data['nature'] : '';
        $salary_range = isset($data['salary_range']) ? $data['salary_range'] : '';
        $create_time_num = isset($data['create_time']) ? $data['create_time'] : 1;
        $keyword = isset($data['keyword']) ? $data['keyword'] : '';
        $district_code = isset($data['district_code']) ? $data['district_code'] : '';
        $prov_code = isset($data['prov_code']) ? $data['prov_code'] : '';

        //$sort = isset($data['sort']) ? $data['sort'] : 0;
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        //if((!empty($is_rec))&&(!empty($areano))&&(!enpty($resume_type))&&(!empty($sort_way))){
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $resumeQuery = Db::table('re_resume');
                $resumeQuery->alias('j');

               // if($re_line_id)         $resumeQuery->where('j.re_line_id','=',$re_line_id);
                if($job_experience)    {
                    $daterObj = new Dater();
                    $time_range = $daterObj->getWorkTimePath($job_experience);
                   // print_r($time_range);exit;
                    if(!empty($time_range['start']))  $resumeQuery->where('j.work_begin_time','>',$time_range['start']);
                    if(!empty($time_range['end']))  $resumeQuery->where('j.work_begin_time','<',$time_range['end']);
                }
                if($salary_range)    {
                    $daterObj = new Dater();
                    $salary_range = $daterObj->getSalaryPath($salary_range);
                    if(!empty($salary_range['min_salary']))  $resumeQuery->where('j.mini_salary','>',$salary_range['min_salary']);
                    if(!empty($salary_range['max_salary']))  $resumeQuery->where('j.max_salary','<',$salary_range['max_salary']);
                }


                if($education)          $resumeQuery->where('j.education','=',$education);
                if($city_code)      $resumeQuery->where('j.city_code','=',$city_code);
                if($district_code)      $resumeQuery->where('j.district_code','=',$district_code);
                if($prov_code)      $resumeQuery->where('j.prov_code','=',$prov_code);
                if($prov_code)      $resumeQuery->where('j.prov_code','=',$prov_code);
                if($prov_code)      $resumeQuery->where('j.prov_code','=',$prov_code);

                if($nature)      $resumeQuery->where('j.nature','=',$nature);
                // if($nature)      $resumeQuery->where('j.nature','=',$nature);
                if($create_time_num)     {
                    $daterObj = new Dater();
                    $time_range = $daterObj->getTimePath($create_time_num);
                    $time_range = array_values($time_range);
                    if(count($time_range)==2) $resumeQuery->where('j.create_at','between',$time_range);
                }
                if($keyword)      $resumeQuery->where('j.name|j.title|j.job_name','like',"%".$keyword."%");
                $count = $resumeQuery->count();
                $resumeQuery->removeOption('field');
                $resumeQuery->join('user c','j.user_id = c.id','left');
                $resumeQuery->field('j.id,j.name,j.label,j.mini_salary,j.max_salary,j.job_name,j.title,j.work_begin_time,j.education,j.self_introduction as introduction,c.avatar');
                $order_str = '';
                /*switch($sort){
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
                        $resumeQuery->field("(st_distance (point (j.lng, j.lat),point(".$user_info['lng'].",".$user_info['lat'].") ) / 0.0111) AS distance");
                        $order_str = " distance asc,j.id desc ";
                        break;
                    case 5:     //智能排序
                        $order_str = " j.update_at desc";
                        break;
                }*/
                $resumeQuery->order($order_str);
                $resume_list = [];

                if($count>0){
                    $resume_arr =$resumeQuery
                        ->page($page,$page_size)
                        ->select();


                    $resumeQuery->removeOption();
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    $daterObj = new Dater();
                    foreach($resume_arr as $kw=>$vw){

                        $resume_info = [];
                        $resume_info = [
                            'id'=>$vw['id'],
                            'name'=>$vw['name'],
                            'mini_salary'=>$vw['mini_salary'],
                            'max_salary'=>$vw['max_salary'],
                            'title'=>$vw['title'],
                            'job_name'=>$vw['job_name'],
                            'work_yeas'=>0,
                            'education_name'=>'无',
                            'introduction'=>$vw['introduction'],
                            'avatar'=>$vw['avatar'],
                        ];
                      //  var_dump($resume_info);
                        $resume_info['mini_salary'] = round($vw['mini_salary']);
                        $resume_info['max_salary'] = round($vw['max_salary']);
                        $resume_info['label1'] = $resume_info['label2'] = $resume_info['label3'] = $resume_info['label4'] = '';
                        if(!empty($vw['label'])){
                            $resume_label_arr = explode("/",$vw['label']);
                            for ($i=0;$i<count($resume_label_arr);$i++){
                                $resume_info['label'.($i+1)] = $resume_label_arr[$i];
                            }
                        }
    /*                    $work_yeas = round((time()-$vw['work_begin_time'])/(60*60*24*365));
                        $work_yeas = round((time()-$vw['work_begin_time'])/(60*60*24*365));*/
                        $resume_info['work_yeas'] = $daterObj->getWorkYears(strtotime($vw['work_begin_time']));
                        if($vw['education']){
                            $resume_info['education_name'] = config('webset.education_name')[$vw['education']];
                        }else{
                            $resume_info['education_name'] = '未填';
                        }

                        $resume_list[] = $resume_info;
                    }
                }else{
                    $page_info = null;
                }

                $response_data = [
                    'data'=>[
                        'resume_list'=>$resume_list,
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


    //简历详情
    public function resumeDetail(){
        $data = $this->request->post();
        $id = isset($data['id']) ? $data['id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $user_type = isset($data['user_type']) ? $data['user_type'] : 1;
        $re_apply_id = isset($data['re_apply_id']) ? $data['re_apply_id'] : 0;
        if(!empty($sess_key)){
            try{
                $user_info = $this->getTUserInfo($sess_key);

                switch($user_type){
                    case 1:
                        $coin = $user_info['coin'];
                        break;
                    case 2:
                        $coin = $user_info['hr_coin'];
                        break;
                    case 3:
                        $coin = $user_info['agent_coin'];
                        break;
                }
                $resumeQuery = Db::table('re_resume');
                $resumeEducationQuery = Db::table('re_resume_education');
                $resumeWorkQuery = Db::table('re_resume_work');
                $resumeProjectQuery = Db::table('re_resume_project');
                if(!empty($id)){
                    $resumeQuery->where('r.id','=',$id);
                }else{
                    $resumeQuery->where('r.user_id','=',$user_info['id']);
                }
                $resume_detail = $resumeQuery
                    ->alias('r')
                    ->join('user u','u.id = r.user_id')
                    ->field('r.*,u.avatar,u.birthday')
                    ->find();
                $resumeQuery->removeOption();
                $collectQuery = Db::table('re_collect');
                $check_collect = $collectQuery
                    ->where('user_id','=',$user_info['id'])
                    ->where('type','=',3)
                    ->where('re_resume_id','=',$resume_detail['id'])
                    ->find();
                $collectQuery->removeOption();
                $is_collect = (empty($check_collect)) ? 2 :1 ;
                $education = $work = $project = [];
                $education_list = $resumeEducationQuery
                    ->where('re_resume_id','=',$resume_detail['id'])
                    ->order('level asc,id asc')
                    ->select();
                if(count($education_list) > 0){
                    foreach($education_list as $key=>$val){
                        $education[] = [
                            'id'=>$val['id'],
                            'name'=>$val['name'],
                            'level'=>$val['level'],
                            'major'=>$val['major'],
                            'start_time'=>$val['start_time'],
                            'end_time'=>$val['end_time'],
                        ];
                    }
                }
                $work_list = $resumeWorkQuery
                    ->where('re_resume_id','=',$resume_detail['id'])
                    ->order('id asc')
                    ->select();
                if(count($work_list) > 0){
                    foreach($work_list as $key=>$val){
                        $work[] = [
                            'name'=>$val['name'],
                            'id'=>$val['id'],
                            'content'=>$val['content'],
                            'major'=>$val['major'],
                            'start_time'=>$val['start_time'],
                            'end_time'=>$val['end_time'],
                        ];
                    }
                }
                $project_list = $resumeProjectQuery
                    ->where('re_resume_id','=',$resume_detail['id'])
                    ->order('id asc')
                    ->select();
                if(count($project_list) > 0){
                    foreach($project_list as $key=>$val){
                        $project[] = [
                            'id'=>$val['id'],
                            'name'=>$val['name'],
                            'content'=>$val['content'],
                            'major'=>$val['major'],
                            'start_time'=>$val['start_time'],
                            'end_time'=>$val['end_time'],
                        ];
                    }
                }

                //判断简历是否已下载
                $resumeDownloadQuery = Db::table('re_resume_download');
                $check_download = $resumeDownloadQuery
                    ->where('user_id','=',$user_info['id'])
                    ->where('re_resume_id','=',$resume_detail['id'])
                    ->find();
                $download_status = empty($check_download) ? 2 : 1;

                $resume_type_info = $this->getResumeType($resume_detail);  //获取简历类型 1. 普通简历 2.金边简历

                $city_info = Db::table('areas')->where('areano','=',$resume_detail['city_code'])->find();
                $apply_status = 0;
                if(!empty($re_apply_id)){
               /*     $apply_info = Db::table('re_apply')->where('id','=',$re_apply_id)->update(['offer'=>2,'update_at'=>date("Y-m-d H:i:s")]);
                    Db::table('re_apply')->removeOption();*/
                    $apply_info = Db::table('re_apply')->where('id','=',$re_apply_id)->find();
                    Db::table('re_apply')->removeOption();
                    $apply_status = $apply_info['offer'];
                }
                $resume_info = [
                    're_resume_id'=>$resume_detail['id'],
                    'user_id'=>$resume_detail['user_id'],
                    'coin'=>$coin,
                    'resume_type'=>$resume_type_info['type'],
                    'download_coin'=>$resume_type_info['download_coin'],
                    'username'=>$resume_detail['name'],
                    'is_collect'=>$is_collect,
                    'birthday'=>$resume_detail['birthday'],
                    'gender'=>$resume_detail['sex'],
                    'avatar'=>$resume_detail['avatar'],
                    'work_begin_time'=>$resume_detail['work_begin_time'],
                    'identity'=>$resume_detail['identity'],
                    'mobile'=>$resume_detail['mobile'],
                    'email'=>$resume_detail['email'],
                    're_job_id'=>$resume_detail['re_job_id'],
                    'job_name'=>$resume_detail['job_name'],
                    'city_code'=>$resume_detail['city_code'],
                    'city_name'=>$city_info['areaname'],
                    'self_introduction'=>$resume_detail['self_introduction'],
                    'download_status'=>$download_status,
                    'apply_status'=>$apply_status,
                    'district_code'=>$resume_detail['district_code'],
                    'district_name'=>$resume_detail['district_name'],
                    'prov_code'=>$resume_detail['prov_code'],
                    'prov_name'=>$resume_detail['prov_name'],


                ];
                $resume_info['label1'] = $resume_info['label2'] = $resume_info['label3'] = $resume_info['label4'] = '';
                if(!empty($resume_detail['label'])){
                    $job_label_arr = explode("/",$resume_detail['label']);
                    for ($i=0;$i<count($job_label_arr);$i++){
                        $resume_info['label'.($i+1)] = $job_label_arr[$i];
                    }
                }
                $response_data = [
                    'data'=>[
                        'resume_info'=>[
                            'user_info'=>$resume_info,
                            'education'=>$education,
                            'work'=>$work,
                            'project'=>$project,
                        ],
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



    public function getResumeType($resume_detail){

        $resume_config = config('webset.resume');
        $arr = [];
        if($resume_detail['max_salary']>$resume_config['salary_line']){   //金边简历
            $arr = [
                'type'=>2,
                'show_coin'=>$resume_config['show_golden'],
                'download_coin'=>$resume_config['download_golden'],
            ];
        }else{
            $arr = [
                'type'=>1,
                'show_coin'=>$resume_config['show_normal'],
                'download_coin'=>$resume_config['download_normal'],
            ];
        }
        return $arr;
    }




    //hr-我下载的简历列表
    public function resumeDownloadList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $offer = isset($data['offer']) ? $data['offer'] : '';

        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        $apply_list = [];
        if(!empty($sess_key)){
            try{
                $user_info = $this->getTUserInfo($sess_key);
                $resumeDownloadQuery = Db::table('re_resume_download');
                $resumeDownloadQuery
                    ->alias('d')
                    //->join('re_company c','j.re_company_id = c.id','left')
                    ->join('re_resume r','d.re_resume_id = r.id','left')
                    ->join('user c','r.user_id = c.id','left');
                $resumeDownloadQuery->where('d.user_id','=',$user_info['id']);

                /*if(!empty($offer)){
                    if($offer==3){
                        $applyQuery->where('a.offer','in',[3,5]);
                    }else{
                        $applyQuery->where('a.offer','=',$offer);
                    }
                }*/
                $count = $resumeDownloadQuery->count();
                $resumeDownloadQuery->removeOption('field');

                $apply_arr = $resumeDownloadQuery
                    ->field('d.*,c.avatar as avatar,c.logintime,r.title,r.label as label,r.id as resume_id,r.name as username,r.work_begin_time,r.education,r.mini_salary,r.max_salary')
                    ->page($page,$page_size)->select();
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];
                //  $applyQuery->getLastSql();exit;
                $resumeDownloadQuery->removeOption();
                foreach($apply_arr as $ka=>$va){
                    //j.name,j.job_label,j.mini_salary,j.max_salary,
                    //->join('re_job j','a.re_job_id = j.id','left')
                   /* if($va['type']==1){  //投递岗位
                        $jobQuery= Db::table('re_job');
                        $wlist = $jobQuery->where('id','=',$va['re_job_id'])->field('name,job_label as label,mini_salary,max_salary')->find();
                    }else{                //投递项目
                        $projectQuery= Db::table('re_project');
                        $wlist = $projectQuery->where('id','=',$va['re_project_id'])->field('name,project_label as label,mini_salary,max_salary')->find();
                    }*/
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
                    $work_years = $daterObj->getWorkYears(strtotime($va['work_begin_time']));;
                    //  $company_icon = empty($va['company_icon']) ? "https://".config('webset.server_name').config('webset.default_company_icon') : $va['company_icon'];
                    $apply_list[] = [
                        'id'=>$va['id'],
                        're_resume_id'=>$va['resume_id'],
                        'username'=>$va['username'],
                        'avatar'=>$va['avatar'],
                        'mini_salary'=>$va['mini_salary'],
                        'max_salary'=>$va['max_salary'],
                        'create_time'=>$va['create_at'],
                        'eigneer_day'=>$eigneer_day,
                        'education'=>$education,
                        'work_years'=>$work_years,
                        'title'=>$va['title'],
                    ];
                }
                $response_data = [
                    'data'=>[
                        'resume_list'=>$apply_list,
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
























    //简历修改
    public function  changeResume(){

        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id = $data['id'];
        $status = isset($data['status']) ? $data['status'] : '-1';
        $tips = isset($data['tips']) ? $data['tips'] : '';

        //if((!empty($is_rec))&&(!empty($areano))&&(!enpty($job_type))&&(!empty($sort_way))){
        if((!empty($sess_key))&&(!empty($id))){
            try{
                $user_info = $this->getGUserInfo($sess_key,1);
                $apply_info = Db::table('re_apply')->where('id','=',$id)->find();
                $user_id = $user_info['user_info']['id'];
                $apply_user_info = Db::table('user')->where('id','=',$apply_info['user_id'])->find();
                $apply_user_id = Db::table('user')->where('id','=',$apply_info['user_id'])->find();
                if($status=='-1'){  //修改了tips
                    $result_change_tips = Db::table('re_apply')->where('id','=',$id)->update(['tips'=>$tips]);
                    if($result_change_tips){
                        $this->success('success', null);
                    }else{
                        $this->error('系统繁忙，请稍候再试', null);
                    }
                }else{
                    //如果status为1 先冻结金额，然后生成推荐关系表
                    if($status!=1){
                        $result_change_tips = Db::table('re_apply')->where('id','=',$id)->update(['offer'=>$status]);

                        if($result_change_tips){

                            if($status==5){  //给上级发送离职消息
                                $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();

                                $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
                                //找该用户的上级
                                $up_user_info = $this->getUpUserInfo($apply_info['user_id']);

                                $noticeHandleObj = new NoticeHandle();
                                if(!empty($up_user_info['up_user_id'])){
                                    $type = 3;
                                    $content ="您的团队成员".$apply_user_info['nickname']."已从".$company_info['name']."(公司)".$job_info['name']."岗位离职";
                                    $is_read = 2;
                                    $noticeHandleObj->createNotice($type,$up_user_info['up_user_id'],$content,$is_read);
                                }
                                //发送离职模版
                                $data = [
                                    'keyword1'=>['value'=>$apply_user_info['nickname']],
                                    'keyword2'=>['value'=>"已离职"],
                                    'keyword3'=>['value'=>$job_info['name']],
                                    'keyword4'=>['value'=>$company_info['name']],
                                ];
                                $noticeHandleObj->sendModelMsg($apply_user_info,$data,'','EntryNotice','pages/work/workDetails');
                            }

                            if($status==2){  //未录用

                                $noticeHandleObj = new NoticeHandle();
                                $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();
                                $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
                                //发送未录用模版

                                $data = [
                                    'keyword1'=>['value'=>$apply_user_info['nickname']],
                                    'keyword2'=>['value'=>"未录用"],
                                    'keyword3'=>['value'=>$job_info['name']],
                                    'keyword4'=>['value'=>$company_info['name']],
                                ];

                                $noticeHandleObj->sendModelMsg($apply_user_info,$data,'','EntryNotice','pages/work/workDetails');
                            }


                            $this->success('success', null);
                        }else{
                            $this->error('系统繁忙，请稍候再试', null);
                        }
                    }else{
                        //todo 先判断是否有该推荐记录
                        //简历通过筛选 冻结金额
                        $frozen_money = $this-> giveMoney($apply_info['id'],$apply_info['re_job_id'],$apply_info['user_id'],$apply_info['rec_user_id']);
                        if(($frozen_money!=1)&&($frozen_money!=3)){
                            $this->error('您账户余额不足,不够支付入职者的奖励和推荐佣金');exit;
                        }else{
                            if($frozen_money!=3){
                                $this->createRecommendDetailRecord($apply_info['id']);
                            }
                            $this->updateUserCompRelation($apply_info['id'],1);
                            $result = Db::table('re_apply')->where('id','=',$id)->update(['offer'=>$status]);
                            //给入职者和推荐者发送消息
                            $job_info = Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->find();
                            $company_info = Db::table('re_company')->where('id','=',$job_info['re_company_id'])->find();
                            $noticeHandleObj = new NoticeHandle();
                            $type = 4;
                            $content ="恭喜您，您已入职".$company_info['name']."(公司)".$job_info['name']."岗位";
                            $is_read = 2;
                            $noticeHandleObj->createNotice($type,$apply_user_id,$content,$is_read);

                            //找该用户的上级
                            $up_user_info = $this->getUpUserInfo($apply_user_id);
                            if(!empty($up_user_info['up_user_id'])){
                                $type = 3;
                                $content ="您的团队成员".$apply_user_info['user_info']['nickname']."入职".$company_info['name']."(公司)".$job_info['name']."岗位";
                                $is_read = 2;
                                $noticeHandleObj->createNotice($type,$up_user_info['up_user_id'],$content,$is_read);
                            }
                            if($result){
                                $this->success('success');
                            }else{
                                $this->error('系统繁忙,请稍后再试');
                            }
                        }
                    }
                }
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //简历通过筛选 冻结金额
    public function giveMoney($apply_id=53,$re_job_id=15,$user_id=10,$rec_user_id=0){
        $flag = 0 ; //是否有推荐上级 0 无 1有
        //先确定该职位是否是推荐入职的
        //查看用户是否有上级
        $team_info = Db::table('user_team')->where('low_user_id','=',$user_id)->find();
        if (!empty($team_info)){
            $flag = 1;
        }else{
            if(!empty($rec_user_id!=0)){
                $flag = 1;
            }
        }

        $job_info = Db::table('re_job')->where('id','=',$re_job_id)->find();
        $admin_id = $job_info['admin_id'];

        $reward_ratio = Db::table('re_ratio')->where('uid','=',$admin_id)->find();
        if(empty($reward_ratio)){
            $reward_ratio = Db::table('re_ratio')->where('uid','=',1)->find();
        }
        $reward_type = $reward_ratio['reward_type'];
        $reward = $job_info['reward'];

        if($flag==1){       //本人入职奖励+ 上级佣金+ 平台佣金
            $up_reward = $job_info['reward_up'];
            if($reward_type==2){  //按比例分配
                //  $up_reward = $reward * $reward_ratio['rec_user_per']/100;
                $p_reward = $up_reward * $reward_ratio['p_per']/100;
            }else{
                //    $up_reward = $reward_ratio['rec_user_cash'];
                //   $p_reward = $reward * $reward_ratio['p_cash'];
                $p_reward = $reward_ratio['p_cash'];
            }
            $total_reward = $reward + $up_reward + $p_reward;
        }else{        //本人入职奖励 + 平台佣金
            $up_reward = 0;
            if($reward_type==2){  //按比例分配
                $p_reward = $up_reward * $reward_ratio['p_per']/100;
            }else{
                // $p_reward = $reward * $reward_ratio['p_cash'];
                $p_reward = $reward_ratio['p_cash'];
            }
            $total_reward = $reward + $up_reward + $p_reward;
        }

        //给该职位发布者冻结金额
        $publish_company = Db::table('re_company')->where('admin_id','=',$admin_id)->find();

        if($publish_company['account'] > $total_reward){
            //冻结金额
            if($total_reward==0){
                return 3;
            }
            $arr['account'] = $publish_company['account'] - $total_reward;
            $arr['frozen'] = $publish_company['frozen'] + $total_reward;
            $result = Db::table('re_company')->where('id','=',$publish_company['id'])->update($arr);
            return $result;
        }else{
            return 2;
        }

    }

    //添加记录
    public function  createRecommendDetailRecord($apply_id){

        $apply_info = Db::table('re_apply')->where('id',$apply_id)->find();
       // $admin_session = Session::get('admin'); //todo 没有的话,重新登录

        $up_user_info = Db::table('user_team')->where('low_user_id',$apply_info['user_id'])->find();
        if(empty($up_user_info)&&(empty($apply_info['rec_user_id']))){   //没有上级没有推荐
            $flag_up_user = 0;
        }else{
            $flag_up_user = 1;
        }


        $job_info = Db::table('re_job')->where('id',$apply_info['re_job_id'])->field('reward_up,reward_type,reward,id,admin_id,reward_days')->find();

        $create_at_time = time();
        $create_at = date('Y-m-d H:i:s',$create_at_time);
        $reward_days = empty($job_info['reward_days']) ? 0 : $job_info['reward_days'] ;
        $timeline = date("Y-m-d H:i:s",$create_at_time + 60*60*24*$reward_days);
        $deadline = date("Y-m-d H:i:s",$create_at_time + 60*60*24*($reward_days+7));




        $reward_setting = Db::table('re_ratio')->where('uid','=',$job_info['admin_id'])->find();
        if(empty($reward_setting)){
            $reward_setting = Db::table('re_ratio')->where('uid','=',1)->find();
        }
        // var_dump($job_info);exit;
        //查看该公司是否有代理商,如果有agent_cash 不为0 否则为0
        $company_info = Db::table('re_company')
            ->where('id',$apply_info['re_company_id'])
            ->find();

        if ($reward_setting['reward_type']==1){
            $way = 1;
            if($flag_up_user==1){
                //$up_cash = $reward_setting['rec_user_cash'];
                $up_cash = $job_info['reward_up'];
            }else{
                $up_cash = 0;
            }
            $p_cash = $reward_setting['p_cash'];
            if(!empty($company_info['re_company_id'])){
                //    $agent_cash = $reward_setting['agent_cash'];
                $agent_cash = 0;
            }else{
                $agent_cash = 0;
            }
        }else{
            $way = 2;
            if($flag_up_user==1){
                //  $up_cash = $job_info['reward'] * $reward_setting['rec_user_per']/100;
                $up_cash = $job_info['reward_up'];
            }else{
                $up_cash = 0;
            }

            $p_cash = $job_info['reward_up'] * $reward_setting['p_per']/100;
            if(!empty($company_info['re_company_id'])){
                //  $agent_cash = $p_cash * $reward_setting['agent_per']/100;
                $agent_cash = 0;
            }else{
                $agent_cash = 0;
            }
        }
        $total_cash = floatval($up_cash) + floatval($p_cash) + floatval($agent_cash) + floatval($job_info['reward']);

        if(!empty($company_info['re_company_id'])){
            $up_company_id = $company_info['re_company_id'];
        }else{
            $up_company_id = 0;
        }


        $up_user_id = empty($up_user_info) ? $apply_info['rec_user_id'] : $up_user_info['up_user_id'];

        $check_rec_detail = Db::table('re_recommenddetail')->where('reply_id',$apply_id)->find();
        if(empty($check_rec_detail)){
            $param = array();
            $param = array(
                're_company_id'=>$apply_info['re_company_id'],
                'reply_id'=>$apply_id,
                'low_user_id'=>$apply_info['user_id'],
                'up_user_id'=>$up_user_id,
                'rec_user_id'=>$apply_info['rec_user_id'],
                'lower_cash'=>$job_info['reward'],
                'status'=>2,
                //'admin_id'=>$admin_session['id'],
                'admin_id'=>$job_info['admin_id'],
                're_job_id'=>$job_info['id'],
                'way'=>$way,
                'up_cash'=>$up_cash,
                'p_cash'=>$p_cash,
                'agent_cash'=>$agent_cash,
                'total_cash'=>$total_cash,
                'up_company_id'=>$up_company_id,
                'create_at'=>$create_at,
                'timeline'=>$timeline,
                'deadline'=>$deadline,
            );
            Db::name('re_recommenddetail')
                ->data($param)
                ->insert();
        }
    }

    //更新用户职员与公司关系记录
    public function updateUserCompRelation($re_apply_id,$status){
        //$admin_id = "对应公司管理员id";$user_id,$re_company_id,$re_job_id,

        $check_usercomp = Db::table('re_usercomp')->where('re_apply_id','=',$re_apply_id)->find();
        if(!empty($check_usercomp)){
            Db::table('re_usercomp')->where('re_apply_id','=',$re_apply_id)->update(['status'=>$status,'update_at'=>date('Y-m-d H:i:s')]);
        }else{
            if($status==1){
                $apply_info = Db::table('re_apply')->where('id','=',$re_apply_id)->find();
                $company_info = Db::table('re_company')->where('id','=',$apply_info['re_company_id'])->find();
                $arr_insert = [
                    'user_id'=>$apply_info['user_id'],
                    're_company_id'=>$apply_info['re_company_id'],
                    're_job_id'=>$apply_info['re_job_id'],
                    'admin_id'=>$company_info['admin_id'],
                    're_apply_id'=>$re_apply_id,
                    'status'=>$status,
                    'create_at'=>date("Y-m-d H:i:s"),
                    'update_at'=>date("Y-m-d H:i:s"),
                ];
                Db::table('re_usercomp')->insert($arr_insert);
            }

        }
    }
}
