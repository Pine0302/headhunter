<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Sms as Smslib;
use app\common\model\User;
use think\addons\Controller;
use think\Session;
use think\Db;
use think\cache\driver\Redis;


/**
 * 手机短信接口
 */
class Sms extends Api
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }





    public function sendProfileSms(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        $mobile = $data['mobile'];
        $sign = '致远宣大科技';
        $user_info = $this->getTUserInfo($sess_key);
        $openid_re = $user_info['openid'];

        $type = $data['type'] ?? 1;
        switch ($type){
            case 1:                         //注册
                $template = "SMS_164375082";
                $profile_key = $openid_re."_profile";
                break;
            case 2:                          //更换手机号
                $template = "SMS_164375082";
                $profile_key = $openid_re."_mobile";
                break;
            case 3:                          //修改登录密码
                $template = "SMS_164375082";
                $profile_key = $openid_re."_pass";
                break;
        }
        if((!empty($sess_key))&&(!empty($mobile))){
            try {

                //发短信
                //生成随机6位数
                $code = rand(100000,999999);
                $param = array(
                    'code'=>$code,
                );
             //   error_log(var_export($param,1),3,"/data/wwwroot/headhunter.pinecc.cn/tt.txt");

                $result_set = $this->redis->set($profile_key,$code);
                //Session::set($profile_key,$code);
           //     $code = Session::get($profile_key);
               // $code = $this->redis->get($profile_key);
            //    error_log(var_export($code,1),3,"/data/wwwroot/headhunter.pinecc.cn/runtime/test.txt");
                error_log(var_export($profile_key,1),3,"/data/wwwroot/headhunter.pinecc.cn/tt.txt");
                error_log(var_export($code,1),3,"/data/wwwroot/headhunter.pinecc.cn/tt.txt");
                error_log(var_export($result_set,1),3,"/data/wwwroot/headhunter.pinecc.cn/tt.txt");

                $alisms = new \addons\alisms\library\Alisms();
                $ret = $alisms->mobile($mobile)
                    ->template($template)
                    ->sign($sign)
                    ->param($param)
                    ->send();
                if ($ret)
                {
                    $this->success("发送成功");
                }
                else
                {
                   // $this->error('网络繁忙,请稍后再试');
                    $this->error("发送失败！失败原因：" . $alisms->getError());
                }
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }


















    }

























    /**
     * 发送验证码
     *
     * @param string    $mobile     手机号
     * @param string    $event      事件名称
     */
    public function send()
    {
        $mobile = $this->request->request("mobile");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';

        $last = Smslib::get($mobile, $event);
        if ($last && time() - $last['createtime'] < 60)
        {
            $this->error(__('发送频繁'));
        }
        if ($event)
        {
            $userinfo = User::getByMobile($mobile);
            if ($event == 'register' && $userinfo)
            {
                //已被注册
                $this->error(__('已被注册'));
            }
            else if (in_array($event, ['changemobile']) && $userinfo)
            {
                //被占用
                $this->error(__('已被占用'));
            }
            else if (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo)
            {
                //未注册
                $this->error(__('未注册'));
            }
        }
        $ret = Smslib::send($mobile, NULL, $event);
        if ($ret)
        {
            $this->success(__('发送成功'));
        }
        else
        {
            $this->error(__('发送失败'));
        }
    }

    /**
     * 检测验证码
     *
     * @param string    $mobile     手机号
     * @param string    $event      事件名称
     * @param string    $captcha    验证码
     */
    public function check()
    {
        $mobile = $this->request->request("mobile");
        $event = $this->request->request("event");
        $event = $event ? $event : 'register';
        $captcha = $this->request->request("captcha");

        if ($event)
        {
            $userinfo = User::getByMobile($mobile);
            if ($event == 'register' && $userinfo)
            {
                //已被注册
                $this->error(__('已被注册'));
            }
            else if (in_array($event, ['changemobile']) && $userinfo)
            {
                //被占用
                $this->error(__('已被占用'));
            }
            else if (in_array($event, ['changepwd', 'resetpwd']) && !$userinfo)
            {
                //未注册
                $this->error(__('未注册'));
            }
        }
        $ret = Smslib::check($mobile, $captcha, $event);
        if ($ret)
        {
            $this->success(__('成功'));
        }
        else
        {
            $this->error(__('验证码不正确'));
        }
    }


}
