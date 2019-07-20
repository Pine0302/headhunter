<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\Db;
use think\Session;
use fast\Wx;
use app\api\library\NoticeHandle;
use app\common\library\Dater;
/**
 * 首页接口
 */
class Notice extends Api
{

    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];

    /**
     * 首页
     * 
     */
    public function index()
    {
        $this->success('请求成功');
    }



    //消息通知列表
    public function noticeInfo(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $notice_config = config('notice');

        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                $noticeQuery = Db::table('re_notice');
                $noticeQuery->where('to_user_id',$user_info['id']);
                $count = $noticeQuery->count();
                $noticeQuery->removeOption('field');
                $page_info = [
                    'cur_page'=>$page,
                    'page_size'=>$page_size,
                    'total_items'=>$count,
                    'total_pages'=>ceil($count/$page_size)
                ];
                $notice_list = $noticeQuery
                    ->alias('n')
                    ->join('user u','u.id=n.from_user_id','left')
                    ->order('id desc')
                    ->field('u.username,u.sess_key ,n.*')
                    ->page($page,$page_size)
                    ->select();

                $noticeQuery->removeOption();
                if(!empty($notice_list)){
                    foreach($notice_list as $kw=>$vw){
                        $title = '来自'.config('webset.id_type')[$vw['from_user_type']];

                        $daterObj = new Dater();
                        $time = $daterObj->socialDateDisplay(strtotime($vw['create_at']));

                        $type = ($vw['from_user_id']==0) ? 1 : 2 ;
                        $user_type_ch = ($vw['from_user_type']==0) ? 1 : 2 ;
                        $data_res[] = [
                            'id'=>$vw['id'],
                            're_job_id'=>$vw['re_job_id'],
                            're_project_id'=>$vw['re_project_id'],
                            'title'=>$title,
                            'content'=>$vw['content'],
                            'brief_content'=>$vw['brief_content'],
                            'is_read'=>$vw['is_read'],
                            'type'=>$vw['type'],
                            'time'=>$time,
                            'from_sess_key'=>$vw['sess_key']
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
                $this->success('success', $data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }



    //消息通知列表
    public function noticeCount(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $notice_config = config('notice');

        if(!empty($sess_key)){
            try{
                $user_info = $this->getTUserInfo($sess_key);
                $noticeQuery = Db::table('re_notice');
                $noticeQuery->where('to_user_id',$user_info['id']);
                $noticeQuery->where('is_read',2);
                $count = $noticeQuery->count();
                $noticeQuery->removeOption();

                $data = [
                    'data'=>[
                        'new_message'=>$count,
                    ],
                ];
                $this->success('success', $data);
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }

        }else{
            $this->error('缺少参数',null,2);
        }
    }

























    //消息通知列表
    public function noticeList(){
        $data = $this->request->request();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $page = isset($data['page']) ? $data['page'] : 1;
        $page_size = isset($data['page_size']) ? $data['page_size'] : 10;
        $notice_config = config('notice');

        if(!empty($sess_key)){
            try{
                $arr = [  'openid', 'session_key' ];
                $sess_info = $this->redis->hmget($sess_key,$arr);
                $openid_re = $sess_info['openid'];
                $user_info = Db::table('user')
                    ->where('openid_re',$openid_re)
                    ->field('id,username,mobile,gender,birthday,available_balance,avatar')
                    ->find();
                $count = Db::table('re_notice')
                    ->where('user_id',$user_info['id'])
                    ->count();

                if(!empty($count)){
                    $page_info = [
                        'cur_page'=>$page,
                        'page_size'=>$page_size,
                        'total_items'=>$count,
                        'total_pages'=>ceil($count/$page_size)
                    ];

                    $notice_list = Db::table('re_notice')
                        ->where('user_id',$user_info['id'])
                        ->order('id desc')
                        ->page($page,$page_size)
                        ->select();

                    if(!empty($notice_list)){
                        foreach($notice_list as $kw=>$vw){
                            $data_res[] = [
                                'id'=>$vw['id'],
                                'title'=>$notice_config[$vw['type']],
                                'content'=>$vw['content'],
                                'is_read'=>$vw['is_read'],
                                'type'=>$vw['type'],
                                'create_at'=>date("Y年m月d日",strtotime($vw['create_at'])),
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

    //消息通知列表
    public function noticeRead(){
        $data = $this->request->post();
        $sess_key = isset($data['sess_key']) ? $data['sess_key'] : '';
        $id = isset($data['id']) ? $data['id'] : '';

        if(!empty($sess_key)){
            try{
                $user_info = $this->getGUserInfo($sess_key);
                    $notice_change = Db::table('re_notice')
                        ->where('to_user_id',$user_info['id'])
                        ->where('id',$id)
                        ->update(['is_read'=>1]);
                $this->success('success');
            }catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }
    }

    //模版消息测试
    public function sendTest(){
        $user_info = [
            'id'=>78,
            'openid_re'=>'oxync4naNdmlcqOrb2_nMCL-b69M',
            /*'id'=>71,
            'openid_re'=>'oxync4lr6IK1BMBw7kFnYq4PogyE',*/
           /* 'id'=>67,
            'openid_re'=>'oxync4nTADqBAQNT6pi2ZNx2or-4',*/
        ];
        $data = [
            'keyword1'=>['value'=>'公司3'],
            'keyword2'=>['value'=>'销售经理'],
            'keyword3'=>['value'=>'2018-10-08'],
            'keyword4'=>['value'=>'西溪湿地'],
            'keyword5'=>['value'=>'携带身份证,学位证,毕业证'],
            'keyword6'=>['value'=>'沈阳'],
        ];
     //   $emphasis_keyword='keyword1.DATA';
        $emphasis_keyword='';
        $template_type="EntryNotice";
        $page = "pages/index/index";
        $this->sendModelMsg($user_info,$data,$emphasis_keyword,$template_type,$page);
    }


    //模版消息接口
    public function sendModelMsg($user_info,$data,$emphasis_keyword,$template_type,$page=''){
        $arr = [
            'app_id'=>config("wxpay.APPID"),
            'app_secret'=>config("wxpay.APPSECRET"),
        ];
        $wx = new Wx($arr);
        $openid = $user_info['openid_re'];
        $form_id = $this->redis->spop('recruitFormIdCollection_'.$user_info['id']);
      //  var_dump($form_id);
        if(!empty($form_id)){
            $openid =$user_info['openid_re'];
            $template_id =config("wxTemplate.".$template_type);
            $page = empty($page) ? "pages/auth/login" : $page;
            $emphasis_keyword = $emphasis_keyword;
            $wx->sendTemplateMessage($openid,$template_id,$page,$form_id,$data,$emphasis_keyword);
        }else{

        }

    }




}
