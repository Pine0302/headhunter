<?php

namespace app\common\controller;

use app\admin\library\Auth;
use think\Config;
use think\Controller;
use think\Hook;
use think\Lang;
use think\Session;
use think\Db;
use fast\Http;

/**
 * 后台控制器基类
 */
class CommonFunc extends Controller
{

    public function userInfo($sess_key){
        $user_arr = [];
        $userQuery = Db::table('user');
        $user_arr = $userQuery
            ->where('sess_key','=',$sess_key)
            ->field('id,openid,nickname,username,avatar,gender,mobile,city_name,city_code,is_engineer,is_hr,is_agent,sess_key,lat,lng,coin,hr_coin,agent_coin')
            ->find();
        $userQuery->removeOption();
        return $user_arr;
    }
    //获取用户/企业所在城市
    public function getUserPostition($lat,$lng){
        $lbs_qq_key = config('Lbs.QQ_KEY');
        $location = $lat.",".$lng;
        $url = "http://apis.map.qq.com/ws/geocoder/v1/?location={$location}&key={$lbs_qq_key}&get_poi=1";

        $http = new Http();
        $result_user_position = $http->get($url);
        $result_user_position_arr = json_decode($result_user_position,true);
         // $this->wlog($result_user_position_arr['result']['ad_info']);

        $location_areano = substr($result_user_position_arr['result']['ad_info']['city_code'],3);
        $location_area_name = $result_user_position_arr['result']['ad_info']['city'];
        $location_district_name = $result_user_position_arr['result']['ad_info']['district'];

        $area_info = Db::table('areas')->where('areano','=',$location_areano)->find();

        //更新用户信息到数据库
        $arr = [
            'prov_name' => $result_user_position_arr['result']['ad_info']['province'],
            'city_name' => $result_user_position_arr['result']['ad_info']['city'],
            'district_name' => $result_user_position_arr['result']['ad_info']['district'],
            'district_code' => $result_user_position_arr['result']['ad_info']['adcode'],
            //'city_code' => $location_areano,
            'city_code' => $area_info['areano'],
            'prov_code' => $area_info['parentno'],
        ];
        return $arr;
    }


    public function wordCut($str,$len){
        $len_str = mb_strlen($str);
        if($len_str>$len){
            return mb_substr($str,0,$len)."...";
        }else{
            return $str;
        }
    }

    //标签数组转字符串
    public function labelImplode($arr,$glue=","){
        $num = count($arr);
        $str = '';
        for ($i=0;$i<$num;$i++){
            $str.= (!empty($arr[$i])) ? ($arr[$i].$glue) : '';
        }
        if(strlen($str)>0){
            $str = rtrim($str,$glue);
        }
        return $str;
    }

    //招聘成功
    public function recruitSuccess($apply_id){
        //查看apply_info
        $applyQuery = Db::table('re_apply');
        $apply_info = $applyQuery
            ->where('id','=',$apply_id)
            ->find();

        $applyQuery->removeOption('where');
        $userQuery = Db::table('user');

        if(($apply_info['is_bonus']==1)&&($apply_info['bonus']>0)&&($apply_info['agent_id']>0)){     //需要支付赏金
            print_r(111);exit;
            $userQuery = Db::table('user');
            $hr_info = $userQuery
                ->where('id','=',$apply_info['hr_id'])
                ->field('id,openid,nickname,username,avatar,gender,mobile,city_name,city_code,is_engineer,is_hr,is_agent,sess_key,lat,lng,coin,hr_coin,agent_coin')
                ->find();
            $userQuery->removeOption('where');

            $userQuery = Db::table('user');
            $agent_info = $userQuery
                ->where('id','=',$apply_info['agent_id'])
                ->field('id,openid,nickname,username,avatar,gender,mobile,city_name,city_code,is_engineer,is_hr,is_agent,sess_key,lat,lng,coin,hr_coin,agent_coin')
                ->find();
            $userQuery->removeOption('where');

            $coinLogQuery = Db::table('re_coin_log');
            //确认hr 猎币足够支付佣金

                //user 表 给hr  减少 猎币佣金
                $agent_per_config = config('webset.agent_per');
               // var_dump($apply_info['agent_id']);exit;
                /*$userQuery = Db::table('user');
                $result_user_query = $userQuery->where('id','=',$apply_info['hr_id'])->setDec('hr_coin',$apply_info['bonus']);
                $userQuery->removeOption('where'); $userQuery->removeOption('field');*/
                //user 表 给agent 增加 猎币佣金
                $userQuery = Db::table('user');
                $userQuery->where('id','=',$apply_info['agent_id'])->setInc('agent_coin',$apply_info['bonus']* $agent_per_config);
                $userQuery->removeOption('where'); $userQuery->removeOption('field');

                $interviewQuery = Db::table('re_interview');
               // $applyQuery = Db::table('re_apply');
                //coin_log 表 减少佣金记录
               /* $coin_dec_log = [
                    'user_id'=>$hr_info['id'],
                    'user_type'=>2,
                    'num'=>$apply_info['bonus'],
                    'way'=>2,
                    'method'=>4,
                    're_apply_id'=>$apply_id,
                    'create_at'=>date("Y-m-d H:i:s"),
                    'update_at'=>date("Y-m-d H:i:s"),
                ];
                $coinLogQuery->insert($coin_dec_log);*/
                $now = time();
                $year = 24*60*60*365;
                $expire_time = $now + $year;
                $coin_add_log = [
                    'user_id'=>$agent_info['id'],
                    'user_type'=>3,
                    'num'=>$apply_info['bonus'] * $agent_per_config ,
                    'way'=>1,
                    'method'=>5,
                    're_apply_id'=>$apply_id,
                    'status'=>1,
                    'left_coin'=>$apply_info['bonus'] * $agent_per_config,
                    'create_at'=>date("Y-m-d H:i:s"),
                    'update_at'=>date("Y-m-d H:i:s"),
                    'expire_at'=>$expire_time,
                ];
                //coin_log 表 增加佣金记录
                $coinLogQuery->insert($coin_add_log);
                $applyQuery->where('id','=',$apply_id)->update(['offer'=>5,'update_at'=>date("Y-m-d H:i:s")]);
                $interviewQuery->where('re_apply_id','=',$apply_id)->update(['status'=>3,'update_at'=>date("Y-m-d H:i:s")]);
                $applyQuery->removeOption();
                $interviewQuery->removeOption();

                //面试项目通过增加一条工程师的任务记录
                if($apply_info['type']==2){
                    $arr_mission= [
                        'user_id'=>$apply_info['user_id'],
                        'agent_id'=>$apply_info['agent_id'],
                        'hr_id'=>$apply_info['hr_id'],
                        're_apply_id'=>$apply_info['id'],
                        're_project_id'=>$apply_info['re_project_id'],
                        're_company_id'=>$apply_info['re_company_id'],
                        'status'=>0,
                        'create_at'=>date("Y-m-d H:i:s"),
                        'update_at'=>date("Y-m-d H:i:s"),
                        'admin_id'=>1,
                    ];
                    $applyCommisionQuery = Db::table('re_apply_mission');
                    $applyCommisionQuery->insert($arr_mission);
                }

            //关闭该工作/项目的其他apply,下架该项目/工作
            $this->closeWp($apply_info);



                return 1;

        }else{

            $interviewQuery = Db::table('re_interview');
            $applyQuery->where('id','=',$apply_id)->update(['offer'=>5,'update_at'=>date("Y-m-d H:i:s")]);
            $interviewQuery->where('re_apply_id','=',$apply_id)->update(['status'=>3,'update_at'=>date("Y-m-d H:i:s")]);
            //面试项目通过增加一条工程师的任务记录
            if($apply_info['type']==2){
                $arr_mission= [
                    'user_id'=>$apply_info['user_id'],
                    'agent_id'=>$apply_info['agent_id'],
                    'hr_id'=>$apply_info['hr_id'],
                    're_apply_id'=>$apply_info['id'],
                    're_project_id'=>$apply_info['re_project_id'],
                    're_company_id'=>$apply_info['re_company_id'],
                    'status'=>0,
                    'create_at'=>date("Y-m-d H:i:s"),
                    'update_at'=>date("Y-m-d H:i:s"),
                    'admin_id'=>1,
                ];
                $applyCommisionQuery = Db::table('re_apply_mission');
                $applyCommisionQuery->insert($arr_mission);
            }
            //关闭该工作/项目的其他apply,下架该项目/工作
            $this->closeWp($apply_info);
            return 1;
        }

    }

    public function closeWp($apply_info){
        if($apply_info['type']==1){ //投递职位
            Db::table('re_job')->where('id','=',$apply_info['re_job_id'])->update(['status'=>3]);
            Db::table('re_apply')
                ->where('re_job_id','=',$apply_info['re_job_id'])
                ->where('id','neq',$apply_info['id'])
                ->update(['offer'=>4]);
        }else{
            Db::table('re_project')->where('id','=',$apply_info['re_project_id'])->update(['status'=>3]);
            Db::table('re_apply')
                ->where('re_project_id','=',$apply_info['re_project_id'])
                ->where('id','neq',$apply_info['id'])
                ->update(['offer'=>4]);
        }
    }






}
