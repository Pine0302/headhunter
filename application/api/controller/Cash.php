<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\wx\WXBizDataCrypt;
use fast\Http;
use think\cache\driver\Redis;
use think\Db;
use think\Session;
use think\Cache;

/**
 * 工作相关接口
 */
class Cash extends Api
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


    public function cashLog(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        $user_type = $data['user_type'] ?? '';
        $way = $data['way'] ?? '';
        $page = $data['page ='] ?? 1;
        $page_size = $data['page_size'] ?? 10;

        $sess_key = $data['sess_key'];
        $user_info = $this->getTUserInfo($sess_key);
        $total_cash = $user_info['available_balance'];
        /*switch ($user_type){
            case 1;
                $total_coin = intval($user_info['coin']);
                break;
            case 2;
                $total_coin = intval($user_info['hr_coin']);
                break;
            case 3;
                $total_coin = intval($user_info['agent_coin']);
                break;
        }*/

        $coinLogQuery = Db::table('cash_log');
        if(!empty($way)) $coinLogQuery->where('way','=',$way);
        $coinLogQuery
            ->where('user_id','=',$user_info['id']);
        //    ->where('user_type','=',$user_type);
        $count = $coinLogQuery->count();
        $coinLogQuery->removeOption('field');
        $log_list = [];
        if($count>0){
            $page_info = [
                'cur_page'=>$page,
                'page_size'=>$page_size,
                'total_items'=>$count,
                'total_pages'=>ceil($count/$page_size)
            ];
            $log_arr = $coinLogQuery
                ->page($page,$page_size)
                ->select();
            $coinLogQuery->removeOption();
            $cash_config = config("webset.cash_log");
            foreach($log_arr as $kd=>$vd){
                $log_list[] = [
                    'id'=>$vd['id'],
                    'title'=>$cash_config[$vd['type']]['discription'],
                    'num'=>$vd['cash'],
                    'way'=>$vd['way'],
                    'create_at'=>$vd['update_at'],
                ];
            }
        }else{
            $page_info = null;
        }
        $response_data = [
            'data'=>[
                'cash_log'=> $log_list,
                'total_cash'=> $total_cash,
                'page_info'=> $page_info,
            ],
        ];
        $this->success('success', $response_data);
    }



    //资金明细
    public function  detail(){
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
                    ->field('id,username,mobile,gender,birthday,available_balance,avatar,nickname')
                    ->find();
                $count = Db::table('cash_log')
                    ->where('user_id',$user_info['id'])
                    ->where('status',1)
                    ->where('type','in',[1,2,3,10,11,13])
                    ->count();
                if(!empty($count)){
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];

                    $cash_list = Db::table('cash_log')
                        ->alias('c')
                        ->join('re_company y','c.apply_company_id = y.id','left')
                        ->where('c.user_id',$user_info['id'])
                        ->where('c.status',1)
                        ->where('c.type','in',[1,2,3,10,11,13,20])
                        ->field('c.*,y.name as company_name')
                        ->order('c.id desc')
                        ->page($page,$page_size)
                        ->select();

                    if(!empty($cash_list)){
                        $method_config = config("method");
                        foreach($cash_list as $kw=>$vw){
                            if(in_array($vw['type'],[11])){
                                $cash = "-".$vw['cash'];
                            }else{
                                $cash = $vw['cash'];
                            }
                            if(in_array($vw['type'],[2,13])){  //推荐的
                                $apply_user_info = Db::table('user')->where('id','=',$vw['apply_user_id'])->find();
                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>$vw['tip'],
                                    'method'=>$method_config[$vw['type']],
                                    'cash'=>$cash,
                                    'way'=>$vw['way'],
                                    'update_at'=>date("Y-m-d",strtotime($vw['update_at'])),
                                    'ratio'=>"已到账",
                                    'company_name'=>$vw['company_name'],
                                    'avatar'=>$apply_user_info['avatar'],
                                    'nickname'=>$apply_user_info['nickname'],
                                ];
                            }else{
                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>$method_config[$vw['type']],
                                    'cash'=>$cash,
                                    'way'=>$vw['way'],
                                    'update_at'=>date("Y-m-d",strtotime($vw['update_at'])),
                                    'ratio'=>"已到账",
                                    'company_name'=>$vw['company_name'],
                                    'avatar'=>$user_info['avatar'],
                                    'nickname'=>$user_info['nickname'],
                                ];
                            }
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



    //新进款项
    public function newIn(){
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
                    ->field('id,username,nickname,mobile,gender,birthday,available_balance,avatar')
                    ->find();
                $count = Db::table('cash_log')
                    ->where('user_id',$user_info['id'])
                    ->where('status',1)
                    ->where('type','in',[1,2,3,10,11,13])
                    ->count();
                if(!empty($count)){
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];

                    $cash_list = Db::table('cash_log')
                        ->alias('c')
                        ->join('re_company y','c.apply_company_id = y.id','left')
                        ->where('c.user_id',$user_info['id'])
                        ->where('c.status',1)
                        ->where('c.type','in',[1,2,3,10,11,13])
                        ->field('c.*,y.name as company_name')
                        ->order('c.id desc')
                        ->page($page,$page_size)
                        ->select();

                    if(!empty($cash_list)){
                        $method_config = config("method");
                        foreach($cash_list as $kw=>$vw){
                            if(in_array($vw['type'],[11])){
                                $cash = "-".$vw['cash'];
                            }else{
                                $cash = $vw['cash'];
                            }

                            if(in_array($vw['type'],[2,13])){  //推荐的
                                $apply_user_info = Db::table('user')->where('id','=',$vw['apply_user_id'])->find();
                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>$method_config[$vw['type']],
                                    'cash'=>$cash,
                                    'way'=>$vw['way'],
                                    'update_at'=>date("Y-m-d",strtotime($vw['update_at'])),
                                    'ratio'=>"已到账",
                                    'company_name'=>$vw['company_name'],
                                    'avatar'=>$apply_user_info['avatar'],
                                    'nickname'=>$apply_user_info['nickname'],
                                ];
                            }else{
                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>$method_config[$vw['type']],
                                    'cash'=>$cash,
                                    'way'=>$vw['way'],
                                    'update_at'=>date("Y-m-d",strtotime($vw['update_at'])),
                                    'ratio'=>"已到账",
                                    'company_name'=>$vw['company_name'],
                                    'avatar'=>$user_info['avatar'],
                                    'nickname'=>$user_info['nickname'],
                                ];
                            }
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

    //新进款项
    public function willIn_bak(){
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

                $count = Db::table('re_recommenddetail')
                    ->where('low_user_id='.$user_info['id'].' AND status=2')
                    ->whereOr('up_user_id='.$user_info['id'].' AND status=2')
                    ->count();
                if(!empty($count)){
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];

                    $cash_list = Db::table('re_recommenddetail')
                        ->where('low_user_id='.$user_info['id'].' AND status=2')
                        ->whereOr('up_user_id='.$user_info['id'].' AND status=2')
                        ->order('id desc')
                        ->page($page,$page_size)
                        ->select();


                    if(!empty($cash_list)){
                        foreach($cash_list as $kw=>$vw){
                            $update_at = empty($vw['update_at']) ? $vw['create_at'] : $vw['update_at'];
                            if($user_info['id'] == $vw['low_user_id']){    //入职奖励

                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>"入职奖励",
                                    'cash'=>$vw['lower_cash'],
                                    'way'=>1,
                                   // 'update_at'=>$vw['update_at'],
                                    'update_at'=>$update_at,
                                ];
                            }else{                                          //推荐入职奖励
                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>"会员推荐奖励",
                                    'cash'=>$vw['up_cash'],
                                    'way'=>1,
                                    //'update_at'=>$vw['update_at'],
                                    'update_at'=>$update_at,
                                ];
                            }
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

    //个人钱包概览
    public function purseInfo()
    {
        $now = time();
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        if (!empty($sess_key)) {
            try {
                $arr = ['openid', 'session_key'];
                $sess_info = $this->redis->hmget($sess_key, $arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re', $openid_re)
                    ->field('id,username,mobile,gender,birthday,available_balance,rec_cash')
                    ->find();

                //查出今日佣金和本月佣金
                $today_begin = date("Y-m-d 00:00:00",$now);
                $month_begin = date("Y-m-01 00:00:00",$now);
                $cash_log_today = Db::table('cash_log')
                    ->where('type','in',[1,2,10,13])
                    ->where('status','=',1)
                    ->where('update_at','>',$today_begin)
                    ->select();
                $cash_log_month = Db::table('cash_log')
                    ->where('type','in',[1,2,10,13])
                    ->where('status','=',1)
                    ->where('update_at','>',$month_begin)
                    ->select();
                $month_commision = 0;
                $today_commision = 0;
                if(count($cash_log_today)>0){
                    foreach($cash_log_today as $kc=>$vc){
                       $today_commision = $today_commision + floatval($vc['cash']);
                    }
                    $today_commision = number_format($today_commision,2);
                }
                if(count($cash_log_month)>0){
                    foreach($cash_log_month as $kc=>$vc){
                        $month_commision = $month_commision + floatval($vc['cash']);
                    }
                    $month_commision = number_format($month_commision,2);
                }
                $response = [
                    'id'=>$user_info['id'],
                    'available_balance'=>$user_info['available_balance'],
                    'total_commision'=>$user_info['rec_cash'],
                    'today_commision'=>$today_commision,
                    'month_commision'=>$month_commision,
                ];
                $data = [
                    'data' => $response,
                ];

                $this->success('success', $data);
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        } else {
            $this->error('缺少参数', null, 2);
        }
    }

    //新进款项
    public function willIn(){
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
                    ->field('id,username,nickname,mobile,gender,birthday,available_balance,avatar')
                    ->find();

                $count = Db::table('rec')
                    ->where('low_user_id='.$user_info['id'].' AND status=2')
                    ->whereOr('up_user_id='.$user_info['id'].' AND status=2')
                    ->count();

                if(!empty($count)){
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];

                    $cash_list = Db::table('rec')
                        ->alias('r')
                        ->join('re_company c','r.re_company_id = c.id','left')
                        ->field('r.*,c.name as company_name')
                        ->where('r.low_user_id='.$user_info['id'].' AND r.status=2')
                        ->whereOr('r.up_user_id='.$user_info['id'].' AND r.status=2')
                        ->order('r.id desc')
                        ->page($page,$page_size)
                        ->select();
                    if(!empty($cash_list)){
                        $method_config = config("method");
                        foreach($cash_list as $kw=>$vw){
                            $update_at = empty($vw['update_at']) ? $vw['create_at'] : $vw['update_at'];
                            $total_days = round((strtotime($vw['timeline'])-strtotime($vw['create_at']))/(60*60*24));
                            $past_days = round((time()-strtotime($vw['create_at']))/(60*60*24));
                            $ratio = $past_days."/".$total_days;
                            if($vw['type']==1){
                                if($user_info['id'] == $vw['low_user_id']){    //入职奖励
                                    $method = "入职奖";
                                }else{
                                    //推荐入职奖励
                                    $method = "推荐奖";
                                }
                            }else{
                                $method = "推荐奖";

                            }
                            if($vw['up_user_id']==$user_info['id']){
                                $low_user_info = Db::table('user')->where('id','=',$vw['low_user_id'])->find();
                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>$method,
                                    'cash'=>$vw['lower_cash'],
                                    'way'=>1,
                                    'company_name'=>$vw['company_name'],
                                    // 'update_at'=>$vw['update_at'],
                                    'update_at'=>$update_at,
                                    'ratio'=>$ratio,
                                    'avatar'=>$low_user_info['avatar'],
                                    'nickname'=>$low_user_info['nickname'],
                                ];

                            }else{

                                $data_res[] = [
                                    'id'=>$vw['id'],
                                    'method'=>$method,
                                    'cash'=>$vw['lower_cash'],
                                    'way'=>1,
                                    'company_name'=>$vw['company_name'],
                                    // 'update_at'=>$vw['update_at'],
                                    'update_at'=>$update_at,
                                    'ratio'=>$ratio,
                                    'avatar'=>$user_info['avatar'],
                                    'nickname'=>$user_info['nickname'],
                                ];
                            }


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

}
