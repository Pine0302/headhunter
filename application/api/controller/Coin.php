<?php

namespace app\api\controller;


use app\common\controller\Api;
use app\common\library\wx\WXBizDataCrypt;
use app\common\service\UserService;
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
class Coin extends Api
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

    //列表
    public function coinConfigList(){
        $data = $this->request->post();
        $is_agent = $data['is_agent'] ?? 1;
        $coinConfigQuery = Db::table('re_coin_config');
        $line_arr = $coinConfigQuery
            ->where('status',1)
            ->where('is_agent','=',1)
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

    //金币增加
    public function coinInc(){
        $data = $this->request->post();
        $now = time();
        $now_date = date("Y-m-d H:i:s");
        $year_later = $now + 365*24*60*60;
        $sess_key = $data['sess_key'] ?? '';
        $user_type = $data['user_type'] ?? '';
        $user_info = $this->getTUserInfo($sess_key);
        $user_info['user_type'] = $user_type;
        $method = $data['method'] ?? 0;
        $userServiceOBj = new UserService();

        $coin_log_config_arr = config('webset.coin_log');
        $coin_log_config = $coin_log_config_arr[$method];
        switch ($method){
            case 8:
                //增加分享记录
                $arr_share = [
                    'user_id' =>$user_info['id'],
                    'user_type' =>$user_info['user_type'],
                    'create_at'=>$now,
                    'update_at'=>$now,
                    'admin_id'=>1,
                ];
                $re_share_log_id= Db::table('re_share_log')->insertGetId($arr_share);
                Db::table('re_share_log')->removeOption();
                //增加用户金币数
                $userServiceOBj->changeUserCoin($user_info['id'],$user_info['user_type'],$coin_log_config['coin'],1);
                //增加金币变动记录
                $coinLogQuery = Db::table('re_coin_log');
                //给用户添加coin 使用记录
                $arr_coin_log = [
                    'user_id'=>$user_info['id'],
                    'user_type'=>$user_info['user_type'],
                    'num'=>$coin_log_config['coin'],
                    'method'=>$method,
                    'way'=>$coin_log_config['way'],
                    're_share_id'=>$re_share_log_id,
                    'create_at'=>$now_date,
                    'update_at'=>$now_date,
                    'expire_at'=>$year_later,
                ];
                $coinLogQuery->insert($arr_coin_log);
                $coinLogQuery->removeOption();
                break;
            case 9:
                //判断用户今日是否兑换
                $coin_num = $data['coin_num'] ?? 0;
                $latest_exchange = Db::table('re_coin_log')->where('user_id','=',$user_info['id'])->order('id desc')->find();
                $create_date = date("Y-m-d",strtotime($latest_exchange['create_at']));
                $today_date = date("Y-m-d");
                if($create_date==$today_date){
                    $this->error('今日已兑换步数,明天再来吧!');
                }else{
                    //增加用户金币数
                    $userServiceOBj->changeUserCoin($user_info['id'],$user_info['user_type'],$coin_num,1);
                    //增加金币变动记录
                    $coinLogQuery = Db::table('re_coin_log');
                    //给用户添加coin 使用记录
                    $arr_coin_log = [
                        'user_id'=>$user_info['id'],
                        'user_type'=>$user_info['user_type'],
                        'num'=>$coin_num,
                        'method'=>$method,
                        'way'=>$coin_log_config['way'],
                        'create_at'=>$now_date,
                        'update_at'=>$now_date,
                        'expire_at'=>$year_later,
                    ];
                    $coinLogQuery->insert($arr_coin_log);
                    $coinLogQuery->removeOption();

                }
                break;

        }





        $this->success();

    }




}
