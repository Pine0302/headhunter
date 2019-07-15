<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use think\Validate;
use think\Db;
use think\Cache;
use think\cache\driver\Redis;

/**
 * 会员接口
 */
class Salary extends Api
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    //预支工资时选择公司
    public function advanceCompany(){
        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        if(!empty($sess_key)){
            $arr = [  'openid', 'session_key' ];
            $sess_info = $this->redis->hmget($sess_key,$arr);
            $openid_re = $sess_info['openid'];
            $user_info = Db::table('user')
                ->where('openid_re',$openid_re)
                ->field('id')
                ->find();
            $uid = $user_info['id'];

            $apply_info = Db::table('re_apply')
                ->alias('a')
                ->join('re_company c ','c.id = a.re_company_id')
                ->where('user_id',$uid)
                ->where('offer',1)
                ->field('distinct a.re_company_id,c.name')
                ->select();
            if(!empty($apply_info)){
                foreach($apply_info as $ka=>$va){
                    $arr1[] = [
                        'company_id'=>$va['re_company_id'],
                        'name'=>$va['name'],
                    ];
                }
            }else{
                //$arr1 = Db::table('re_company')->where('status','=',1)->field('id as company_id,name')->select();
                $arr1=[];
            }
            $data = [
                'data'=>$arr1,
            ];
            $this->success('success', $data);
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }


    //预支工资申请
    public function advance(){
        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        $amount = $data['amount'] ?? '';
        $reason = $data['reason'] ?? '';
        $re_company_id = $data['company_id'] ?? '';
        //  if((!empty($sess_key))&&(!empty($amount))&&(!empty($reason))&&(!empty($re_company_id))){
        if((!empty($sess_key))&&(!empty($amount))&&(!empty($reason))){
            try {
                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id')
                    ->find();
                $uid = $user_info['id'];
                //通过员工档案表查询用户所在的公司和管理员id

                /*$record_info = Db::table('re_record')
                    ->where('user_id',$uid)
                    ->where('in_service',1)
                    ->field('re_company_id,admin_id')
                    ->find();*/

                /*$apply_info = Db::table('re_apply')
                    ->where('user_id',$uid)
                    ->where('offer',1)
                    ->field('re_company_id,admin_id')
                    ->find();*/

           //     if(!empty($apply_info)){
                $withdraw_per_info = Db::table('re_ratio')->where('uid','=',1)->find();
                $per = (100-$withdraw_per_info['withdraw_per'])/100;
                $re_company_info = Db::table('re_company')->where('id','=',$re_company_id)->find();
                    $add_info = [
                        'user_id' => $uid,
                        //  're_company_id' => $apply_info['re_company_id'],
                        'admin_id' => $re_company_info['admin_id'],
                        'reason' => $reason,
                        'amount' => $amount * $per,
                        're_company_id' => $re_company_id,
                        'withdraw_per' => $withdraw_per_info['withdraw_per'],
                        'user_id' => $uid,
                        'create_at'=>date("Y-m-d H:i:s",time()),
                        'update_at'=>date("Y-m-d H:i:s",time()),
                    ];
                    $result = Db::table('re_advance')->insert($add_info);
                    if(!empty($result)){
                        $this->success('success',null);
                    }else{
                        $this->error('网络繁忙,请稍后再试');
                    }
                /*}else{
                    $this->error('您还不是入职员工不能预支工资');
                }*/
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }



    public function companyList(){
        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        $company_list = Db::table('re_company')->where(['status'=>1])->select();
        $arr = [];
        if(!empty($company_list)){
            foreach($company_list as $kc=>$vc){
                $arr[] = [
                    'id' => $vc['id'],
                    'name' => $vc['name'],
                    'instruction' => $vc['instruction'],
                ];
            }
        }
        $data = [
            'data'=>$arr,
        ];
        $this->success('success', $data);

    }


    //薪资列表
    public function salaryList(){

        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 6;
        $id_num = isset($data['id_num']) ? $data['id_num'] : '';
        $company_name= isset($data['company_name']) ? $data['company_name'] : '';
        if((!empty($id_num))&&(!empty($sess_key))){
            try{
                //先判断改身份证是不是本人的

                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_list = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id,username,mobile,gender,birthday,available_balance')
                    ->find();
                $resume = Db::table('re_resume')
                    ->where('user_id',$user_list['id'])
                    ->find();
                if(empty($resume['id_num'])){
                    $this->error('您还未实名认证!',null,3);exit;
                }
                if($resume['id_num']!=$id_num){
                    $this->error('只有已加入链匠平台的公司的员工才能查询工资!',null,3);exit;
                }



                $arr = array();
                if(!empty($company_name)){
                    $count = Db::table('re_salary')
                        ->where(['id_num'=>$id_num])
                        ->where(['company_name'=>$company_name])
                        ->count();
                    if($count){
                        $data = Db::table('re_salary')
                            ->where(['id_num'=>$id_num])
                            ->where(['company_name'=>$company_name])
                            ->page($page,$page_size)
                            ->order('month desc')
                            ->select();
                    }else{
                        $data = null;
                    }
                }else{
                    $count = Db::table('re_salary')
                        ->where(['id_num'=>$id_num])
                        ->count();

                    if($count){
                        $data = Db::table('re_salary')
                            ->where(['id_num'=>$id_num])
                            ->page($page,$page_size)
                            ->order('month desc')
                            ->select();
                    }else{
                        $data = null;
                    }
                }

                if(!empty($data)){
                    foreach($data as $kd=>$vd){
                        $month_time = strtotime($vd['month']);
                        $month_date = date("Y-m",$month_time);
                        $arr[] = [
                            'id'=>$vd['id'],
                            'month_date'=>$month_date,
                            'company_name'=>$vd['company_name'],
                            'salary'=>$vd['salary'],
                        ];
                    }
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];
                    $data = [
                        'data'=>$arr,
                        'page_info'=>$page_info,
                    ];
                    $this->success('success', $data);
                }else{
                    $data = [
                        'data'=>null,
                        'page_info'=>null,
                    ];
                    $this->error('非本平台合作单，无法查询工资');
                }
            }catch(exception $e){
                $this->error('非本平台合作单，无法查询工资');
            }
        }else{
            $this->error('缺少参数',null,2);
        }

    }




    //薪资详情
    public function salaryDetail(){
        $data = $this->request->post();
        $id = isset($data['id']) ? $data['id'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';

        if((!empty($sess_key))&&(!empty($id))){
            try{
                $salary_detail =
                    Db::table('re_salary')
                        /* ->alias('s')
                         ->join('re_company c','j.re_company_id = c.id')
                         ->field('j.*,c.name as company_name,c.instruction as company_instruction,c.address as company_address')*/
                        ->where('id',$id)
                        ->order('id desc')
                        ->find();
                // var_dump($salary_detail);exit;

                $arr_res = [
                    'detail'=> unserialize($salary_detail['detail']),
                    'info'=>[
                        'total'=>$salary_detail['salary'],
                    ]
                ];
                $data = [
                    'data'=>$arr_res,
                ];
                $this->success('success', $data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }


    //薪资详情
    public function salaryDetailByMonth(){
        $data = $this->request->post();
        $month = isset($data['month']) ? $data['month'] : '';
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id_num = isset($data['id_num']) ? $data['id_num'] : '';

        if((!empty($sess_key))&&(!empty($month))&&(!empty($id_num))){
            try{
                $time = strtotime($month);
                $month_date = date("Y-m-d",$time);
                $salary_detail =
                    Db::table('re_salary')
                        /* ->alias('s')
                         ->join('re_company c','j.re_company_id = c.id')
                         ->field('j.*,c.name as company_name,c.instruction as company_instruction,c.address as company_address')*/
                        ->where('id_num',$id_num)
                        ->where('month',$month)
                        ->order('id desc')
                        ->find();

                $detail = unserialize($salary_detail['detail']);
                $detail_arr_new = array_values($detail);
                $arr_res = [
                    //  'detail'=> unserialize($salary_detail['detail']),
                    'detail'=>  $detail_arr_new,
                    'info'=>[
                        'total'=>$salary_detail['salary'],
                    ]
                ];

                $data = [
                    'data'=>$arr_res,
                ];

                $this->success('success', $data);
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }



    public function test123(){
        $data = '2018-4-1';

    }







}
