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
class Train extends Api
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


    //培训列表
    public function  trainList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $user_info = $this->getGUserInfo($sess_key,1);
        $user_id = $user_info['user_info']['id'];
        //if((!empty($is_rec))&&(!empty($areano))&&(!enpty($job_type))&&(!empty($sort_way))){
        if(!empty($sess_key)){
            try{
                $map = [
                    'status'=>2,
                    're_traintype_id'=>1,
                ];
                $order  = "sign_time asc";
                    $count = Db::table('re_training')
                        ->where($map)
                        ->order($order)
                        ->count();
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];

                $train_list = Db::table('re_training')
                    ->where($map)
                    ->order($order)
                    ->page($page,$page_size)
                    ->select();

                    foreach($train_list as $kw=>$vw){
                        //查看该用户是否已报名
                        $checkSign = Db::table('user_training')
                            ->where('user_id','=',$user_id)
                            ->where("re_training_id",'=',$vw['id'])
                            ->find();
                        $has_apply = empty($checkSign) ? 0 : 1;
                        $area_arr = explode("-",$vw['area']);
                        $data1[] = [
                            'id'=>$vw['id'],
                            'pic_env'=>"http://".$_SERVER['HTTP_HOST'].$vw['pic_env'],
                            'name'=>$vw['name'],
                            'train_time'=>$vw['train_time'],
                            'address'=>$vw['address'],
                            'update_at'=>$vw['update_at'],
                            'fee'=>$vw['fee'],
                            'has_apply'=>$has_apply,
                        ];
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


    //培训详情
    public function trainDetail(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id = isset($data['id']) ? $data['id'] : '';

        if((!empty($sess_key))&&(!empty($id))){
            try{

                $train_info = Db::table('re_training')
                     ->where('id',$id)
                     ->find();

                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id')
                    ->find();
                $uid = $user_info['id'];

                $has_apply = 0;
                //查看用户是否已报名
                $check_apply = Db::table('user_training')
                    ->where('user_id',$uid)
                    ->where('re_training_id',$id)
                    ->find();
                empty($check_apply) ? ($has_apply = 0) : ($has_apply = 1);

                //统计报名人数
                $count = Db::table('user_training')
                    ->where('re_training_id',$id)
                    ->where('status',1)
                    ->count();

                $train_time_int = strtotime($train_info['train_time']);
                $train_time_end_int = strtotime($train_info['train_end_time']);
                $train_time_suspend = date("Y.m/d H:i",$train_time_int)."-".date("Y.m/d H:i",$train_time_end_int);
                $sign_time = date("Y.m/d",strtotime($train_info['sign_time']));

                $pic_swap_arr =  explode(",",$train_info['pic_swap']);
                $pic_swap_new = [];
                foreach($pic_swap_arr as $vp){
                    $pic_swap_new[] = "https://".$_SERVER['HTTP_HOST'].$vp;
                }

                $pattern  = '/src="/';
                $replacement  = 'src="https://'.$_SERVER['HTTP_HOST'];
                $subject = $train_info['content'];
                $content = preg_replace($pattern,$replacement,$subject);
                $response_data = [
                    'id' => $train_info['id'],
                    'train_time' => $train_time_suspend,
                    'name' => $train_info['name'],
                    'address' => $train_info['address'],
                    'content' => $content,
                    'has_apply' => $has_apply,
                    'update_at' => $train_info['update_at'],
                    'num' => $count,
                    'sign_time'=>$sign_time,
                    'max_person'=>$train_info['max_person'],
                    'fee'=>$train_info['fee'],
                    'reward_up'=>$train_info['reward_up'],
                    'pic_swap'=>$pic_swap_new,
                ];


                $data = [
                    'data'=>$response_data,
                ];
                $this->success('success', $data);

            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //培训详情
    public function activityDetail(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id = isset($data['id']) ? $data['id'] : '';

        if((!empty($sess_key))&&(!empty($id))){
            try{

                $train_info = Db::table('re_training')
                    ->where('id',$id)
                    ->find();

                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id')
                    ->find();
                $uid = $user_info['id'];

                $has_apply = 0;
                //查看用户是否已报名
                $check_apply = Db::table('user_training')
                    ->where('user_id',$uid)
                    ->where('re_training_id',$id)
                    ->find();
                empty($check_apply) ? ($has_apply = 0) : ($has_apply = 1);

                //统计报名人数
                $count = Db::table('user_training')
                    ->where('re_training_id',$id)
                    ->where('status',1)
                    ->count();

                $train_time_int = strtotime($train_info['train_time']);
                $train_time_end_int = strtotime($train_info['train_end_time']);
                $train_time_suspend = date("Y.m/d H:i",$train_time_int)."-".date("Y.m/d H:i",$train_time_end_int);
                $sign_time = date("Y.m/d",strtotime($train_info['sign_time']));

                $pic_swap_arr =  explode(",",$train_info['pic_swap']);
                $pic_swap_new = [];
                foreach($pic_swap_arr as $vp){
                    $pic_swap_new[] = "https://".$_SERVER['HTTP_HOST'].$vp;
                }

                $pattern  = '/src="/';
                $replacement  = 'src="https://'.$_SERVER['HTTP_HOST'];
                $subject = $train_info['content'];
                $content = preg_replace($pattern,$replacement,$subject);
                $response_data = [
                    'id' => $train_info['id'],
                    'train_time' => $train_time_suspend,
                    'name' => $train_info['name'],
                    'address' => $train_info['address'],
                    'content' => $content,
                    'has_apply' => $has_apply,
                    'update_at' => $train_info['update_at'],
                    'num' => $count,
                    'sign_time'=>$sign_time,
                    'max_person'=>$train_info['max_person'],
                    'fee'=>$train_info['fee'],
                    'reward_up'=>$train_info['reward_up'],
                    'pic_swap'=>$pic_swap_new,
                ];


                $data = [
                    'data'=>$response_data,
                ];
                $this->success('success', $data);

            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //添加培训需求
    public function addTrainWill(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $content = isset($data['content']) ? $data['content'] : '';
        if((!empty($sess_key))&&(!empty($content))){
            try{
                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id,username,mobile,gender,birthday,available_balance')
                    ->find();
                $add_will_data = [
                    'user_id' => $user_info['id'],
                    'username' => $user_info['username'],
                    'mobile' => $user_info['mobile'],
                    'content' => $content,
                    'create_at' => date("Y-m-d H:i:s",time()),
                ];
                $result = Db::table("user_trainingwill")
                    ->insert($add_will_data);
                if(!empty($result)){
                    $this->success('success');
                }else{
                    $this->error('网络繁忙,请稍后再试');
                }
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }

    public function  checkTraining($user_info,$train_info){
        $flag = 0;
        //检测该活动是否可以报名
        $available_num = $train_info['max_person']-$train_info['person_count'];
        if($available_num==1){
            $flag = 3; //报名结束就满人
        }elseif($available_num<1){
            $flag = 1;
        }else{      //检测该用户是否已报名
            $user_training_info = Db::table('user_training')
                ->where('user_id','=',$user_info['id'])
                ->where('re_training_id','=',$train_info['id'])
                ->find();
            if(!empty($user_training_info)){
                $flag = 2;
            }
        }
        return $flag;
    }

    // 免费的报名培训/活动
    public function applyTrain(){
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
                $train_info = Db::table('re_training')->where('id','=',$id)->find();

                $checkTrain = $this->checkTraining($user_info,$train_info);

                if(($checkTrain==0)||($checkTrain==3)){
                    $add_info = [
                        'user_id' => $uid,
                        're_training_id' => $id,
                        'create_at' => date("Y-m-d H:i:s",time()),
                        'update_at' => date("Y-m-d H:i:s",time()),
                        'status' => 1,
                    ];
                    $result = Db::table('user_training')->insert($add_info);
                    if(!empty($result)){
                        $new_person_count= $train_info['person_count']+1;
                        //更新活动状态情况报名已满
                        if($checkTrain==3){
                            Db::table('re_training')->where('id','=',$train_info['id'])->update(['status'=>1,'person_count'=>$new_person_count]);
                        }else{
                            Db::table('re_training')->where('id','=',$train_info['id'])->update(['person_count'=>$new_person_count]);
                        }
                        $this->success('success',null);
                    }else{
                        $this->error('网络繁忙,请稍后再试');
                    }
                }elseif($checkTrain==1){
                    $this->error('该活动报名已满');
                }elseif($checkTrain==2){
                    $this->error('您已报名该活动,请勿重复报名');
                }
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
    }


    //培训分类列表
    public function activityTypeList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        if(!empty($sess_key)){
            try{
                $order = 'hot desc ';
                $train_type_list = Db::table('re_traintype')
                    ->where('status','=',1)
                    ->order($order)
                    ->select();

                foreach($train_type_list as $kw=>$vw){
                    $response[] = [
                        'id'=>$vw['id'],
                        'name'=>$vw['name'],
                    ];
                }
                $data = [
                    'data'=>$response,
                ];
                $this->success('success', $data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }



    //培训列表
    public function  activityList(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $type_id = isset($data['type_id']) ? $data['type_id'] : '-1';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key,1);
                $user_id = $user_info['user_info']['id'];
                if($type_id!='-1'){
                    $map = [
                        'status'=>2,
                        're_traintype_id'=>$type_id
                    ];
                }else{
                    $map = [
                        'status'=>2,
                    ];
                }

                $order  = "sign_time asc";
                $count = Db::table('re_training')
                    ->where($map)
                    ->order($order)
                    ->count();
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];

                $train_list = Db::table('re_training')
                    ->where($map)
                    ->order($order)
                    ->page($page,$page_size)
                    ->select();
                $esponse=[];
                foreach($train_list as $kw=>$vw){
                    $area_arr = explode("-",$vw['area']);
                    //查看该用户是否已报名
                    $checkSign = Db::table('user_training')
                        ->where('user_id','=',$user_id)
                        ->where("re_training_id",'=',$vw['id'])
                        ->find();
                    $has_apply = empty($checkSign) ? 0 : 1;

                    $esponse[] = [
                        'id'=>$vw['id'],
                        'pic_env'=>"http://".$_SERVER['HTTP_HOST'].$vw['pic_env'],
                        'name'=>$vw['name'],
                        'train_time'=>$vw['train_time'],
                        'address'=>$vw['address'],
                        'update_at'=>$vw['update_at'],
                        'fee'=>$vw['fee'],
                        'has_apply'=>$has_apply,
                    ];
                }

                $data = [
                    'data'=>$esponse,
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



    //我的报名
    public function applyTrainList(){
       // var_dump(class_exists("app\\api\\controller\\Train"));
       // var_dump(class_exists("app\\api\\controller\\Resume"));exit;
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key,1);
                $user_id = $user_info['user_info']['id'];
                $order  = "u.id desc";
                $field = "t.*,u.status as ustatus";
                $count = Db::table('user_training')
                    ->alias('u')
                    ->join('re_training t','u.re_training_id = t.id')
                    ->where('u.user_id','=',$user_id)
                    ->count();
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];

                $apply_list = Db::table('user_training')
                    ->alias('u')
                    ->join('re_training t','u.re_training_id = t.id')
                    ->where('u.user_id','=',$user_id)
                    ->field($field)
                    ->page($page,$page_size)
                    ->order($order)
                    ->select();
                $esponse=[];
                foreach($apply_list as $kw=>$vw){
                    $area_arr = explode("-",$vw['area']);
                    //查看该用户是否已报名
                    $esponse[] = [
                        'id'=>$vw['id'],
                        'pic_env'=>"http://".$_SERVER['HTTP_HOST'].$vw['pic_env'],
                        'name'=>$vw['name'],
                        'train_time'=>$vw['train_time'],
                        'address'=>$vw['address'],
                        'update_at'=>$vw['update_at'],
                       // 'fee'=>$vw['fee'],
                    ];
                }
                $data = [
                    'data'=>$esponse,
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
