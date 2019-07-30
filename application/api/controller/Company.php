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
use app\common\controller\CommonFunc;
use app\api\library\NoticeHandle;
/**
 * 工作相关接口
 */
class Company extends Api
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

    /**
     * 无需登录的接口
     *
     */
    public function test1()
    {
        $this->success('返回成功', ['action' => 'test1']);
    }

    /**
     * 需要登录的接口
     *
     */
    public function test2()
    {
        $this->success('返回成功', ['action' => 'test2']);
    }

    /**
     * 需要登录且需要验证有相应组的权限
     *
     */
    public function test3()
    {
        $this->success('返回成功', ['action' => 'test3']);
    }

    //公司列表
    public function  companyList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $areano = isset($data['city_id']) ? $data['city_id'] : 0;
        $re_line_id = isset($data['re_line_id']) ? $data['re_line_id'] : 0;
        $financing = isset($data['financing']) ? $data['financing'] : 0;
        $scale = isset($data['scale']) ? $data['scale'] : 0;
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;

        if(!empty($sess_key)){
            try{

                $companyQuery = Db::table('re_company');
                $companyQuery->alias('c');
                if($areano) $companyQuery->where('c.city_code','=',$areano);
                if($re_line_id) $companyQuery->where('c.re_line_id','=',$re_line_id);
                if($financing) $companyQuery->where('c.financing','=',$financing);
                if($scale) $companyQuery->where('c.scale','=',$scale);

                //获取数量
                $count = $companyQuery->count();
                $companyQuery->removeOption('field');

                $order = 'c.id desc';
                $companyQuery->order($order);
                //获取列表
                $company_list = $companyQuery
                    ->join('re_line l','c.re_line_id=l.id','left')
                    ->field('c.id,c.name,c.icon,l.name as lname,c.scale,c.financing')
                    ->page($page,$page_size)
                    ->select();
                $companyQuery->removeOption();
                //列表数据整合
               $scale_config = config('webset.scale');

                foreach($company_list as $kc=>$vc){

                    $company_list[$kc]['label1'] = $company_list[$kc]['label2'] = $company_list[$kc]['label3'] = '';
                    $company_list[$kc]['label1'] = !empty($vc['financing']) ? (config('webset.financing')[$vc['financing']]) : '不需要融资';
                    $company_list[$kc]['label2'] = !empty($vc['scale']) ? (config('webset.scale')[$vc['scale']]) : '少于15人';
                   // $company_list[$kc]['label2'] = $vc['scale'];
                    $company_list[$kc]['label3'] = isset($vc['lname']) ? $vc['lname'] : '互联网';
                    unset($company_list[$kc]['scale']);
                    unset($company_list[$kc]['lname']);
                    unset($company_list[$kc]['financing']);
                    /*if(!empty($vc['label'])){
                        $label_arr = explode('/',$vc['label']);
                        $x=1;
                        while($x<=count($label_arr)) {
                            $key = "label".$x;
                            $company_list[$kc][$key] = $label_arr[$x-1];
                            $x++;
                        }
                    }*/
                   // unset($company_list[$kc]['label']);
                    if(empty($vc['icon'])){
                        $company_list[$kc]['icon'] = "https://".config('webset.server_name').config('webset.default_company_icon');
                    }
                    //获取job_name
                    $jobQuery = Db::table('re_job');
                    $job_num = $jobQuery
                        ->where('re_company_id','=',$vc['id'])
                        ->where('status','=',1)
                        ->count();
                    $jobQuery->removeOption();
                    //获取 project_num
                    $projectQuery = Db::table('re_project');
                    $project_num = $projectQuery
                        ->where('re_company_id','=',$vc['id'])
                        ->where('status','=',1)
                        ->count();
                    $projectQuery->removeOption();
                    $company_list[$kc]['job_num'] = $job_num;
                    $company_list[$kc]['project_num'] = $project_num;
                }
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];
                $response_data = [
                    'data'=>[
                        'company_list'=>$company_list,
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


    //申请公司认证
    public function applyCompany(){
        $data = $this->request->post();
        $re_company_id = isset($data['re_company_id']) ? $data['re_company_id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';

        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $company_apply_info = [];
                $companyApplyQuery = Db::table('re_company_apply');
                $company_apply_info = $companyApplyQuery
                    ->where('re_company_id','=',$re_company_id)
                    ->where('user_id','=',$user_info['id'])
                    ->find();
                $companyApplyQuery->removeOption();
                $arr_insert_company_apply =[
                    'user_id'=> $user_info['id'],
                    're_company_id'=> $re_company_id,
                    'create_at'=> date("Y-m-d H:i:s"),
                    'update_at'=> date("Y-m-d H:i:s"),
                    'status'=>0,
                ];
                $companyApplyQuery = Db::table('re_company_apply');
                if(!empty($company_apply_info)){
                    $companyApplyQuery->where('re_company_id','=',$re_company_id)
                        ->where('user_id','=',$user_info['id'])
                        ->update($arr_insert_company_apply);
                }else{
                    $companyApplyQuery
                        ->insert($arr_insert_company_apply);
                }

                $this->success('success');
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //公司信息
    public function companyInfo(){
        $data = $this->request->post();
        $id = isset($data['id']) ? $data['id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
     /*   $ukey = isset($data['ukey']) ? $data['ukey'] : '';
        if(!empty($ukey)){
            $this->addUser2Team($sess_key,$ukey);
        }*/

        if(!empty($sess_key)){
            try{
                $company_info = [];
                $companyQuery = Db::table('re_company');
                if(empty($id)){
                    $user_info = $this->getTUserInfo($sess_key);

                    $id = $user_info['re_company_id'];
                    if(!empty($id)){
                        $company_info = $companyQuery
                            ->field('id,name,image,icon,coordinate,areas as area,address,instruction,status,financing,scale,re_line_id,city_name')
                            // ->where('user_id','=',$user_info['id'])
                            ->where('id','=',$id)
                            ->find();
                    }
                }else{
                    $company_info = $companyQuery
                        ->field('id,name,image,icon,coordinate,areas as area,address,instruction,status,financing,scale,re_line_id,city_name')
                        ->where('id','=',$id)
                        ->find();
                }
        
                $companyQuery->removeOption();
                if(empty($company_info)){
                    $response_data = [
                        'data'=>[
                            'company_info'=>[],
                        ],
                    ];
                    $this->success('success', $response_data);
                }
                $company_info['label1'] = $company_info['label2'] = $company_info['label3'] = $company_info['label4'] = '';
                $line_info = Db::table('re_line')->where('id','=',$company_info['re_line_id'])->find();
                $company_info['label1'] = !empty($company_info['city_name']) ? $company_info['city_name'] :'杭州';
                $company_info['label2'] = !empty($company_info['financing']) ? (config('webset.financing')[$company_info['financing']]) : '不需要融资';
                $company_info['label3'] = !empty($company_info['scale']) ? (config('webset.scale')[$company_info['scale']]) : '少于15人';
                // $company_list[$kc]['label2'] = $vc['scale'];
                $company_info['label4'] = isset($line_info['name']) ? $line_info['name'] : '互联网';

                /*if(!empty($company_info['label'])){
                    $label_arr = explode('/',$company_info['label']);
                    $x=1;
                    while($x<=count($label_arr)) {
                        $key = "label".$x;
                        $company_info[$key] = $label_arr[$x-1];
                        $x++;
                    }
                }*/
                $company_info['lat'] = $company_info['lng'] = '';
                if(!empty($company_info['coordinate'])){
                    $coordinate_arr = explode(",",$company_info['coordinate']);
                    $company_info['lat'] = $coordinate_arr[0];
                    $company_info['lng'] = $coordinate_arr[1];
                }
           //     unset($company_info['label']);
                if(empty($company_info['icon'])){
                    $company_info['icon'] = "https://".config('webset.server_name').config('webset.default_company_icon');
                }

                //todo 查看用户是否关注
                $user_info = $this->getGUserInfo($sess_key);
                $collectQuery = Db::table('re_collect');
                $collect_info = $collectQuery
                    ->where('user_id','=',$user_info['id'])
                    ->where('type','=',1)
                    ->where('re_company_id','=',$company_info['id'])
                    ->find();
                if(!empty($collect_info)){
                    $company_info['attention'] = 1;
                }else{
                    $company_info['attention'] = 0;
                }
              //  $collect_info =
               /* $pic_swap_new = [];
                if(!empty($company_info['pic_swap'])){
                        $pic_swap_arr =  explode(",",$company_info['pic_swap']);
                    foreach($pic_swap_arr as $vp){
                        $pic_swap_new[] = "https://".$_SERVER['HTTP_HOST'].$vp;
                    }
                }*/
                $response_data = [
                    'data'=>[
                        'company_info'=>$company_info,
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

    //企业认证
    public function companyFill(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';

        $city_name = isset($data['city_name']) ? $data['city_name'] : '';
        $city_code = isset($data['city_code']) ? $data['city_code'] : '';

        $district_name = isset($data['district_name']) ? $data['district_name'] : '';
        $district_code = isset($data['district_code']) ? $data['district_code'] : '';


        $name = isset($data['name']) ? $data['name'] : '';
        $financing = isset($data['financing']) ? $data['financing'] : '';
        $scale = isset($data['scale']) ? $data['scale'] : '';
        $re_line_id = isset($data['re_line_id']) ? $data['re_line_id'] : '';
        $lng = isset($data['lng']) ? $data['lng'] : '';
        $lat = isset($data['lat']) ? $data['lat'] : '';
        $address = isset($data['address']) ? $data['address'] : '';
        $image = isset($data['image']) ? $data['image'] : '';
        $instruction = isset($data['instruction']) ? $data['instruction'] : '';
        $licence_image = isset($data['licence_image']) ? $data['licence_image'] : '';
        if(!empty($sess_key)){
            try{
                $hr_info = $this->getTUserInfo($sess_key);

               /* if(!empty($lat)&&(!empty($lng))){
                    $CommonFuncObj = new CommonFunc();
                    $company_position = $CommonFuncObj->getUserPostition($lat,$lng);
                    $areas = $company_position['prov_name']."-".$company_position['city_name']."-".$company_position['district_name'];
                    $coordinate = $lat.",".$lng;
                }else{
                    $coordinate = $hr_info['lat'].",".$hr_info['lng'];
                }*/
             /*   $city_info = Db::table('areas')->where('areano','=',$city_code)->find();
                Db::table('areas')->removeOption();
                $prov_info = Db::table('areas')->where('areano','=',$city_info['parentno'])->find();
                $areas = $prov_info['areaname']."-".$city_name."-".$district_name;*/
                $CommonFuncObj = new CommonFunc();
                $company_position = $CommonFuncObj->getUserPostition($lat,$lng);
                $areas = $company_position['prov_name']."-".$company_position['city_name']."-".$company_position['district_name'];
                $coordinate = $lat.",".$lng;
                $company_info = [
                    'name'=>$name,
                    'financing'=>$financing,
                    'scale'=>$scale,
                    're_line_id'=>$re_line_id,
                    'address'=>$address,
                    'areas'=>$areas,
                    'image'=>$image,
                    'instruction'=>$instruction,
                    'licence_image'=>$licence_image,
                    'city_name'=>$company_position['city_name'],
                    'prov_name'=>$company_position['prov_name'],
                    'district_name'=>$company_position['district_name'],
                    'city_code'=>$company_position['city_code'],
                    'prov_code'=>$company_position['prov_code'],
                    'district_code'=>$company_position['district_code'],
                    'admin_id'=>$hr_info['ad_id'],
                 /*   'city_name'=>$city_name,
                    'prov_name'=>$prov_info['areaname'],
                    'district_name'=>$district_name,
                    'city_code'=>$city_code,
                    'prov_code'=>$prov_info['areano'],
                    'district_code'=>$district_code,*/
                    'user_id'=>$hr_info['id'],
                    'mobile'=>$hr_info['mobile'],
                    'coordinate'=>$coordinate,
                    'update_at'=>date("Y-m-d H:i:s"),
                ];
                $companyQuery = Db::table('re_company');
                $check_company = $companyQuery
                    ->where('user_id','=',$hr_info['id'])
                    ->find();

                if(!empty($check_company)){
                    $result = $companyQuery->where('id','=', $check_company['id'])
                        ->update($company_info);
                    $companyQuery->removeOption();
                }else{
                    $company_info['status'] = 2;
                    $company_info['create_at'] = date("Y-m-d H:i:s");
                    $companyQuery->removeOption('where');
                    $result = $companyQuery->insert($company_info);
                    $companyQuery->removeOption('');
                }
                $this->success('success');
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //关注企业
    public function collectCompany(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_company_id = isset($data['re_company_id']) ? $data['re_company_id'] : '';
        $now_date = date("Y-m-d H:i:s");
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);

                $checkCollect = Db::table('re_collect')
                    ->where('user_id','=',$user_info['id'])
                    ->where('re_company_id','=',$re_company_id)
                    ->where('type','=',1)
                    ->find();
                Db::table('re_collect')->removeOption();
                if(empty($checkCollect)){
                    $arr = [
                        'user_id'=>$user_info['id'],
                        'type'=>1,
                        're_company_id'=>$re_company_id,
                        'update_at'=>$now_date,
                        'create_at'=>$now_date,
                        'admin_id'=>1,
                    ];
                    $result = Db::table('re_collect')->insert($arr);
                    if($result){
                        $this->success('success');
                    }else{
                        $this->error('网络繁忙,请稍候再试!');
                    }
                }
                $this->success('success');
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //取消关注企业
    public function unCollectCompany(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $re_company_id = isset($data['re_company_id']) ? $data['re_company_id'] : '';
        $now_date = date("Y-m-d H:i:s");
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);

                $checkCollect = Db::table('re_collect')
                    ->where('user_id','=',$user_info['id'])
                    ->where('re_company_id','=',$re_company_id)
                    ->where('type','=',1)
                    ->find();
                Db::table('re_collect')->removeOption();
                if(!empty($checkCollect)){

                    $result = Db::table('re_collect')
                        ->where('user_id','=',$user_info['id'])
                        ->where('re_company_id','=',$re_company_id)
                        ->where('type','=',1)
                        ->delete();
                    if($result){
                        $this->success('success');
                    }else{
                        $this->error('网络繁忙,请稍候再试!');
                    }
                }
                $this->success('success');
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //我关注的企业
    public function collectCompanyList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;


        if(!empty($sess_key)){
            try{
                $company_list = [];
                $page_info = [];

                $user_info = $this->getGUserInfo($sess_key);

                $collectQuery = Db::table('re_collect');
                $collectQuery->alias('t');
                $collectQuery->where('t.user_id','=',$user_info['id']);
                $collectQuery->where('t.type','=',1);
                $count = $collectQuery->count();
                $collectQuery->removeOption('field');

                if($count>0){
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    $company_list = $collectQuery
                        ->join('re_company c','c.id = t.re_company_id','left')
                        ->join('re_line l','c.re_line_id= l.id','left')
                        ->field('c.id,c.city_name,c.name,c.icon,c.scale,c.financing,t.id as tid,l.name as lname')
                        ->page($page,$page_size)
                        ->select();
                    foreach($company_list as $kc=>$vc){

                        $company_list[$kc]['label1'] = $company_list[$kc]['label2'] = $company_list[$kc]['label3'] = $company_list[$kc]['label4'] = '';
                        $company_list[$kc]['label1'] =  !empty($vc['city_name']) ? $vc['city_name'] :'杭州';
                        $company_list[$kc]['label2'] = !empty($vc['financing']) ? (config('webset.financing')[$vc['financing']]) : '不需要融资';
                        $company_list[$kc]['label3'] = !empty($vc['scale']) ? (config('webset.scale')[$vc['scale']]) : '少于15人';
                        // $company_list[$kc]['label2'] = $vc['scale'];
                        $company_list[$kc]['label4'] = isset($vc['lname']) ? $vc['lname'] : '互联网';
                        unset($company_list[$kc]['scale']);
                        unset($company_list[$kc]['lname']);
                        unset($company_list[$kc]['financing']);
                        unset($company_list[$kc]['tid']);
                        /*if(!empty($vc['label'])){
                            $label_arr = explode('/',$vc['label']);
                            $x=1;
                            while($x<=count($label_arr)) {
                                $key = "label".$x;
                                $company_list[$kc][$key] = $label_arr[$x-1];
                                $x++;
                            }
                        }*/
                        // unset($company_list[$kc]['label']);
                        if(empty($vc['icon'])){
                            $company_list[$kc]['icon'] = "https://".config('webset.server_name').config('webset.default_company_icon');
                        }
                        //获取job_name
                        $jobQuery = Db::table('re_job');
                        $job_num = $jobQuery
                            ->where('re_company_id','=',$vc['id'])
                            ->where('status','=',1)
                            ->count();
                        $jobQuery->removeOption();
                        //获取 project_num
                        $projectQuery = Db::table('re_project');
                        $project_num = $projectQuery
                            ->where('re_company_id','=',$vc['id'])
                            ->where('status','=',1)
                            ->count();
                        $projectQuery->removeOption();
                        $company_list[$kc]['job_num'] = $job_num;
                        $company_list[$kc]['project_num'] = $project_num;
                    }
                }
                $response_data = [
                    'data'=>[
                        'company_list'=>$company_list,
                        'page_info'=>$page_info,
                    ],
                ];

                $this->success('success',$response_data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }




    }



}
