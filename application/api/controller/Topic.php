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
class Topic extends Api
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



    //话题列表
    public function topicList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $type = isset($data['type']) ? $data['type'] : 0;
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $topic_list = [];
        if(!empty($sess_key)){
            try{
                $user_info = $this->getTUserInfo($sess_key);
                $topicQuery = Db::table('re_topic');
                $topicQuery
                    ->alias('a')
                    ->join('re_topic_vote v','a.id = v.re_topic_id','left');
                //  if($user_info['is_engineer']==1){
                if(!empty($type)){
                    if($type==1){  //活动中的话题
                        $topicQuery->where('a.result','=',0);
                    }else{          //往期话题
                        $topicQuery->where('a.result','>',0);
                    }
                }
                $count = $topicQuery->count();
                $topicQuery->removeOption('field');
                $topic_arr = $topicQuery
                    ->field('distinct a.id,a.*,v.vote,v.result as vote_result')
                    ->page($page,$page_size)->select();
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];
                foreach($topic_arr as $ka=>$va){
                    $blue_percent = ($va['total_num']==0) ? 0 : ($va['blue_num']/$va['total_num']);
                    $red_percent = ($va['total_num']==0) ? 0 : ($va['red_num']/$va['total_num']);

                    $topicVoteQuery = Db::table('re_topic_vote');
                    $result_vote = $topicVoteQuery->where('user_id','=',$user_info['id'])
                        ->where('re_topic_id','=',$va['id'])
                        ->find();
                    $vote = 0;    //未参与  1.投票蓝方  2.投票红方
                    $coin_gain = 0; //获得的金币
                    $status = 0; //投票结果是否出来
                    $coinLogQuery = Db::table('re_coin_log');
                    if(!empty($result_vote)){      //参与了
                        $vote = $result_vote['vote'];
                        if($va['result']!=0){    //投票结果已出来
                            $status = 1;
                            $coin_log_arr = $coinLogQuery
                                ->where('user_id','=',$user_info['id'])
                                ->where('re_topic_id','=',$va['id'])
                                ->select();
                            //金币计算
                            $coinLogQuery->removeOption();
                            if(count($coin_log_arr)>0){
                                foreach($coin_log_arr as $kc=>$vc){
                                    if($vc['way']==1){
                                        $coin_gain += $vc['num'];
                                    }else{
                                        $coin_gain -= $vc['num'];
                                    }
                                }
                            }
                        }
                    }
                    //时间计算
                    if($va['result']==0){
                        $time_left = $va['end_time'] - time();
                    }else{
                        $time_left = 0;
                    }
                    $topic_list[] = [
                        'id'=>$va['id'],
                        'title'=>$va['title'],
                        'coin'=>$va['coin'],
                        'rule'=>$va['rule'],
                        'blue_num'=>$va['blue_num'],
                        'red_num'=>$va['red_num'],
                        'total_num'=>$va['total_num'],
                        'blue_percent'=>$blue_percent,
                        'red_percent'=>$red_percent,
                        'time_left'=>$time_left,
                        'vote'=>$vote,
                        'coin_gain'=>$coin_gain,
                        'status'=>$status,
                    ];
                }
                $response_data = [
                    'data'=>[
                        'topic_list'=>$topic_list,
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



    //投票 活动
    public function chooseTopic(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        $re_topic_id = $data['re_topic_id'] ?? '';
        $vote_side = $data['vote_side'] ?? '';
        $user_type = $data['user_type'] ?? 1;
        $now_date = date("Y-m-d H:i:s");
        if((!empty($sess_key))&&(!empty($re_topic_id))&&(!empty($vote_side))&&(!empty($user_type))){
            try {
                $user_info = $this->getTUserInfo($sess_key);
                $uid = $user_info['id'];
                /*$JobServiceObj = new JobService();
                $check_resume_fill = $JobServiceObj->checkResumeFill($uid);*/
                $topicQuery = Db::table('re_topic');

                $topic_info = $topicQuery->where('id','=',$re_topic_id)->find();
                $topicQuery->removeOption();
                if($topic_info['result']==0){
                    //查看用户是否投过票
                    $topicVoteQuery = Db::table('re_topic_vote');
                    $check_user_submit = $topicVoteQuery
                        ->where('re_topic_id','=',$re_topic_id)
                        ->where('user_id','=',$user_info['id'])
                        ->find();
                    $topicVoteQuery->removeOption();
                    if(empty($check_user_submit)){
                        //查看用户对应的身份coin 够不够
                        $coin= $user_info[config('webset.coin_type')[$user_type]['name']];
                        if(!($coin<$topic_info['coin'])){
                            //扣除用户coin
                            $userQuery= Db::table('user');
                            $userQuery->where('id','=',$user_info['id'])->setDec(config('webset.coin_type')[$user_type]['name'],$topic_info['coin']);
                            $userQuery->removeOption();
                            //添加re_topic_vote 记录
                            $topicVoteQuery = Db::table('re_topic_vote');
                            $arr_topic_vote_add = [
                                're_topic_id'=>$re_topic_id,
                                'user_type'=>$user_type,
                                'user_id'=>$user_info['id'],
                                'vote'=>$vote_side,
                                'result'=>0,
                                'coin'=>$topic_info['coin'],
                                'create_at'=>$now_date,
                                'update_at'=>$now_date,
                            ];
                            $re_topic_vote_id = $topicVoteQuery->insertGetId($arr_topic_vote_add);
                            //修改re_topic 记录
                            $topicQuery = Db::table('re_topic');
                            $arr_topic_update = [
                                'total_num'=>$topic_info['total_num']+1,
                                'total_coin'=>$topic_info['total_coin']+$topic_info['coin'],
                            ];
                            if($vote_side==1){
                                $arr_topic_update['blue_num'] = $topic_info['blue_num']+1;
                            }else{
                                $arr_topic_update['red_num'] = $topic_info['red_num']+1;
                            }
                            $topicQuery->where('id','=',$re_topic_id)->update($arr_topic_update);
                            $topicQuery->removeOption();
                            // 添加re_coin_log记录
                            $coinLogQuery = Db::table('re_coin_log');
                            $arr_coin_log_add = [
                                're_topic_id'=>$re_topic_id,
                                'user_type'=>$user_type,
                                'user_id'=>$user_info['id'],
                                'method'=>2,
                                'way'=>2,
                                'num'=>$topic_info['coin'],
                                'create_at'=>$now_date,
                                'update_at'=>$now_date,
                            ];
                            $result = $coinLogQuery->insertGetId($arr_coin_log_add);
                            $coinLogQuery->removeOption();
                            if($result){
                                $this->success('success');
                            }else{
                                $this->error('系统繁忙,请稍候再试');
                            }

                        }else{
                            $this->error('您的'.config('webset.coin_type')[$user_type]['title'].'不足,请先充值');
                        }
                    }else{
                        $this->error('您已经投过票了');
                    }
                }else{
                    $this->error('该话题已结束');
                }
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }





}
