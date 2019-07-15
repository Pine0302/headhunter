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
class Sign extends Api
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


    // 本月签到记录,及连续签到天数
    public function signLog()
    {
        $data = $this->request->post();
        $now = time();
        $two_yewa_later = $now + 34*60*60*365*2;
        $nowdate = date("Y-m-d H:i:s");
        $sess_key = $data['sess_key'];
        $user_type = $data['user_type'] ?? 0;
        $user_info = $this->getTUserInfo($sess_key);

        $date1 = date('Y-m-d');

        $today_sign = 0;
        $count = Db::table('re_sign_log')
            ->where('user_id','=',$user_info['id'])
            ->where('sign_time','=',$date1)
            ->count();
        Db::table('re_sign_log')->removeOption();
        // 一天只能签到一次
        if ($count > 0) {
            $today_sign = 1;
        }


        $year = isset($data['year']) ? $data['year'] : date('Y');
        $month = isset($data['month']) ? $data['month'] : date('m');

        $result = array('sign_count' => 0, 'list' => array());

        // 昨天有没有签到
        if ($this->yesterdaySign($user_info)) {
            if(( $user_info['sign_count']>0 )&&($user_info['sign_count']%7==0)){
                $sign_count = 7;
            }else{
                $sign_count = $user_info['sign_count']%7;
            }
        }else{
            $sign_count = $today_sign;
        }
        $month_start = strtotime(date($year . '-' . $month . '-01'));
        $month_end = strtotime('+1 month' . date($year . '-' . $month . '-01'));

        $log_list = Db::table('re_sign_log')->field('sign_time')->where(array( 'user_id' => $user_info['id'], 'add_time' => array('between', $month_start . ',' . $month_end)))->select();
        $log_arr = [];
        if(!empty($log_list)){
            foreach($log_list as $kl=>$vl){
                $log_arr[] = $vl['sign_time'];
            }
          //  $log_arr = array_values($log_list);
        }
     //   var_dump($log_arr);exit;
      //  $result['list'] = $log_arr;
      /*  $sign_count = $user_info['sign_count'];
        if($sign_count>7){
            $sign_count = $sign_count%7;
        }*/
        $response_data = [
            'data'=>[
                'sign_list'=>$log_arr,
                'sign_count'=>$sign_count,
                'today_sign'=>$today_sign,
            ],
        ];
        $this->success('success',$response_data);
    }

    // 签到
    public function sign()
    {
        $data = $this->request->post();
        $now = time();
        $two_yewa_later = $now + 34*60*60*365*2;
        $nowdate = date("Y-m-d H:i:s");
        $sess_key = $data['sess_key'];
        $user_type = $data['user_type'] ?? 0;
        $user_info = $this->getGUserInfo($sess_key);
        $user_info['user_type'] = $user_type;
        $date1 = date('Y-m-d');
        //print_r($date1);
        $count = Db::table('re_sign_log')
            ->where('user_id','=',$user_info['id'])
            ->where('sign_time','=',$date1)
            ->count();
        //print_r($count);
        Db::table('re_sign_log')->removeOption();
        // 一天只能签到一次
        if ($count > 0) {
            $this->error("今天已签到,不能重复签到！");
        }

        //查看昨天是否签到
        $sign_config = config('webset.sign');
        //print_r($sign_config);
        if ($this->yesterdaySign($user_info)) {

            Db::table('user')->where('id','=',$user_info['id'])->setInc('sign_count');
            Db::table('user')->removeOption();
            $sign_count_info = Db::table('user')->where('id','=',$user_info['id'])->field('id,sign_count')->find();
            $sign_count = $sign_count_info['sign_count'];
            Db::table('user')->removeOption();
            $key = $sign_count%$sign_config['day'];
            if(($sign_count%$sign_config['day'])==0){
                $inc_field = config('webset.coin_type')[$user_type]['name'];
                Db::table('user')->where('id','=',$user_info['id'])->setInc($inc_field,$sign_config['coin']);
                Db::table('user')->removeOption();
                $arr_sign_log = [
                    'user_id' => $user_info['id'],
                    'user_type' => $user_type,
                    'coin' => $sign_config['coin'],
                    'isvalid' => 1,
                    'add_time' => time(),
                    'sign_time' => date('Y-m-d'),
                    'update_time' => time(),
                ];
                $re_sign_log_id = Db::table('re_sign_log')->insertGetId($arr_sign_log);
                //print_r(333);

                Db::table('re_sign_log')->removeOption();
                $coin_log_inc = [
                    'user_id'=>$user_info['id'],
                    'admin_id'=>1,
                    'user_type'=>$user_type,
                    'num'=>$sign_config['coin'],
                    'left_coin'=>$sign_config['coin'],
                    'way'=>1,
                    'method'=>7,
                    're_sign_log_id'=>$re_sign_log_id,
                    'status'=>1,
                    'create_at'=>$nowdate,
                    'expire_at'=>$two_yewa_later,
                    'update_at'=>$nowdate,
                ];
                //print_r($coin_log_inc);
                //print_r(222);
             //   exit;
                $coinLogQuery = Db::table('re_coin_log');
                $coin_log_id = $coinLogQuery->insertGetId($coin_log_inc);
            //    print_r($coin_log_id);
                $coinLogQuery->removeOption();
            }else{
                Db::table('user')->where('id','=',$user_info['id'])->update(['sign_count'=>1]);
                Db::table('user')->removeOption();
                $arr_sign_log = [
                    'user_id' => $user_info['id'],
                    'user_type' => $user_type,
                    'coin' =>0,
                    'isvalid' => 1,
                    'add_time' => time(),
                    'sign_time' => date('Y-m-d'),
                    'update_time' => time(),
                ];
                $re_sign_log_id = Db::table('re_sign_log')->insertGetId($arr_sign_log);
              //  var_dump($re_sign_log_id);
                Db::table('re_sign_log')->removeOption();
            }
        }else{

            Db::table('user')->where('id','=',$user_info['id'])->update(['sign_count'=>1]);
            Db::table('user')->removeOption();
            $arr_sign_log = [
                'user_id' => $user_info['id'],
                'user_type' => $user_type,
                'coin' =>0,
                'isvalid' => 1,
                'add_time' => time(),
                'sign_time' => date('Y-m-d'),
                'update_time' => time(),
            ];
            $re_sign_log_id = Db::table('re_sign_log')->insertGetId($arr_sign_log);
          //  var_dump($re_sign_log_id);
            Db::table('re_sign_log')->removeOption();
        }
        $this->success();
    }

    /**
     * 判断用户昨天有没有签到
     * @return bool
     */
    private function yesterdaySign($user_info)
    {
        $today = strtotime(date('Y-m-d'));
        $yesterday = strtotime('-1 day' . date('Y-m-d'));
        $count = Db::table('re_sign_log')->where(array('user_id' => $user_info['id'], 'add_time' => array('between', $yesterday . ',' . $today)))->count();
        return $count > 0;
    }









    //列表
    public function coinConfigList(){
        $data = $this->request->post();
        $coinConfigQuery = Db::table('re_coin_config');
        $line_arr = $coinConfigQuery
            ->where('status',1)
            ->order('sort desc')
            ->select();
        $coinConfigQuery->removeOption();
        $line_list = [];
        foreach($line_arr as $kd=>$vd){
            $line_list[] = [
                'id'=>$vd['id'],
                'title'=>$vd['title'],
                'subtitle'=>$vd['subtitle'],
                'price'=>$vd['price'],
                'coin_num'=>$vd['coin_num'],
            ];
        }
        $response_data = [
            'data'=>['config_list'=> $line_list],
        ];
        $this->success('success', $response_data);
    }

    public function coinLog(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        $user_type = $data['user_type'] ?? '';
        $way = $data['way'] ?? '';
        $page = $data['page ='] ?? 1;
        $page_size = $data['page_size'] ?? 10;

        $sess_key = $data['sess_key'];
        $user_info = $this->getTUserInfo($sess_key);
        switch ($user_type){
            case 1;
                $total_coin = intval($user_info['coin']);
                break;
            case 2;
                $total_coin = intval($user_info['hr_coin']);
                break;
            case 3;
                $total_coin = intval($user_info['agent_coin']);
                break;
        }

        $coinLogQuery = Db::table('re_coin_log');
        if(!empty($way)) $coinLogQuery->where('way','=',$way);
        $coinLogQuery
            ->where('user_id','=',$user_info['id'])
            ->where('user_type','=',$user_type);
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
            $coin_config = config("webset.coin_log");
            foreach($log_arr as $kd=>$vd){
                $log_list[] = [
                    'id'=>$vd['id'],
                    'title'=>$coin_config[$vd['method']]['discription'],
                    'num'=>$vd['num'],
                    'way'=>$vd['way'],
                    'create_at'=>$vd['create_at'],
                ];
            }
        }else{
            $page_info = null;
        }
        $response_data = [
            'data'=>[
                'coin_log'=> $log_list,
                'total_coin'=> $total_coin,
                'page_info'=> $page_info,
            ],
        ];
        $this->success('success', $response_data);
    }




}
