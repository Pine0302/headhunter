<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\controller\CommonFunc;
use app\common\library\wx\WXBizDataCrypt;
use fast\Http;
use think\cache\driver\Redis;
use think\Db;
use think\Session;
use think\Cache;

/**
 * 示例接口
 */
class Login extends Api
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


    //授权登录
    public function login()
    {

        $data = $this->request->post();
        $code = $data['code'];
        $mini_config_url = config('mini.url');
        $appid = config('Wxpay.APPID');
        $app_secret = config('Wxpay.APPSECRET');
        $login_url = $mini_config_url['wx_login']."?appid={$appid}&secret={$app_secret}&js_code={$code}&grant_type=authorization_code";
        $result_json = Http::get($login_url);
        $result = json_decode($result_json,true);

        $user_info = [];
        if(!empty($result['openid'])){
            $session_key = $result['session_key'];
            $need_auth = 1;
            $user_cache = $this->redis->get($result['openid']);

            if(!empty($user_cache)){
                $need_auth = 2 ;  //2 不需要授权  1：需要授权
                $user_arr = json_decode($user_cache,true);
                //$session_key = $user_arr['session_key'];
            }else{
                $user_arr =  Db::table('user')
                    ->where('openid','=',$result['openid'])
                    ->field('id,nickname,username,avatar,gender,mobile,city_name,city_code,is_engineer,is_hr,is_agent,sess_key,lng,lat,password')
                    ->find();
                $user_arr['session_key'] = $session_key;

            }


            //缓存用户数据
            if(!empty($user_arr['nickname'])&&(!empty($user_arr['city_code']))&&(!empty($user_arr['mobile']))){
                $week_time = 24*60*60*7;
                $result_set_redis = $this->redis->set($result['openid'],json_encode($user_arr),$week_time);
                $this->redis->set($user_arr['sess_key'],json_encode($user_arr),$week_time);
            }

            if(!empty($user_arr['sess_key'])){
                $sess_key = $user_arr['sess_key'];
                $has_password = empty($user_arr['password']) ? 2 : 1 ;
                $user_info = $user_arr;
                unset($user_info['is_engineer']);
                unset($user_info['is_hr']);
                unset($user_info['is_agent']);
                unset($user_info['sess_key']);
                $user_info['identity_auth'] = ['is_engineer'=>$user_arr['is_engineer'],'is_hr'=>$user_arr['is_hr'],'is_agent'=>$user_arr['is_agent']];
                Db::table('user')->where('openid','=',$result['openid'])->update(['session_key'=>$session_key]);   //更新session_key
            }else{  //第一次登陆
                //$sess_key = $this->rd3_session(16);
                $sess_key = $this->create16str();
                Db::table('user')->insert(['openid'=>$result['openid'],'sess_key'=>$sess_key,'session_key'=>$session_key]);
                $need_auth = $need_auth;
                $has_password = 2;
                $user_info = null;
            }

           // $this->redis->expire($sess_key,config('Wxpay.LOGIN_EXPIRE_TIME'));
            $data = [
                'sess_key'=>$sess_key,
                'user_info'=>$user_info,
                'need_auth'=>$need_auth,
                'has_password'=>$has_password,
            ];
            $bizobj = ['data'=>$data];
            $this->success('成功', $bizobj);
        }else{
            $this->error('授权失败');
        }
    }



    public function filterEmoji($str)
    {
      $str = preg_replace_callback( '/./u',
          function (array $match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
          },
          $str);
       return $str;
    }






    public function getUserInfo(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'];

        $user_cache = $this->redis->get($sess_key);
        $user_cache_arr = (!empty($user_cache)) ? json_decode($user_cache,true) : [];
        if(!empty($user_cache_arr['nickname'])&&(!empty($user_cache_arr['city_code']))&&(!empty($user_cache_arr['mobile']))){
            $user_info = $user_cache_arr;
        }
        unset($user_info['is_engineer']);
        unset($user_info['is_hr']);
        unset($user_info['is_agent']);
        $user_info['identity_auth'] = ['is_engineer'=>$user_cache_arr['is_engineer'],'is_hr'=>$user_cache_arr['is_hr'],'is_agent'=>$user_cache_arr['is_agent']];
        $data = [
            'sess_key'=>$sess_key,
            'user_info'=>$user_info,
        ];
        $bizobj = ['data'=>$data];
        $this->success('成功', $bizobj);
    }



    //添加上级
    public function updateTeam($up_user_id,$low_user_id){
            //先看看下级用户是否有上级,如果有则不管了,没有则添加上级
            $check_low_user = Db::table("user_team")->where('low_user_id',$low_user_id)->find();

            if(empty($check_low_user)){
                $arr =[
                    'up_user_id' => $up_user_id,
                    'low_user_id' => $low_user_id,
                    'create_at' => date("Y-m-d H:i:s",time()),
                    'update_at' => date("Y-m-d H:i:s",time()),
                ];
                Db::table('user_team')->insert($arr);
            }
    }


    public function  test(){
        $result = $this->create16str();
        var_dump($result);exit;
        echo phpinfo();
     //   $redis = Cache::getHandler();
     //   echo"</pre>";
     //   print_r($redis);
      //  $redis = new Redis();
      //  for ($i=0;$i<10;$i++){
          //  $redis->lpush('tets_list',$i);
     //   }
      //  print_r($redis->lrange('tets_list',0,-1));
        /*$redis->set('myname','scs890302');
        $myname = $redis->get('myname');*/
        //var_dump($myname);


    }

   public function rd3_session($len) {
	$fp = @fopen('/dev/urandom','rb');
	$result = '';
	if ($fp !== FALSE) {
		$result .= @fread($fp, $len);
		@fclose($fp);
	} else {
        trigger_error('Can not open /dev/urandom.');
    }
    // convert from binary to string
    $result = base64_encode($result);
    // remove none url chars
    $result = strtr($result, '+/', '-_');
    return substr($result, 0, $len);
}

    public function create16str(){
        $str = uniqid();
        $str_supply = rand(100,999);
        return $str.$str_supply;
    }


    //获取用户位置
    public function getUserPosition(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $config_area = config('area');
        $lat = isset($data['lat']) ? $data['lat'] : $config_area['user_default_position']['lat'] ;
        $lng = isset($data['lng']) ? $data['lng'] : $config_area['user_default_position']['lng'] ;
        $commonFuncObj = new CommonFunc();
        $arr_user_area = $commonFuncObj->getUserPostition($lat,$lng);

        Db::table('user')->where('sess_key','=',$sess_key)->update($arr_user_area);

        $user_arr =  Db::table('user')
            ->where('sess_key','=',$sess_key)
            ->field('id,openid,nickname,username,avatar,gender,mobile,city_name,city_code,is_engineer,is_hr,is_agent,sess_key')
            ->find();
        //缓存用户数据
        if(!empty($user_arr['nickname'])&&(!empty($user_arr['city_code']))&&(!empty($user_arr['mobile']))){
            $week_time = 24*60*60*7;
            $openid = $user_arr['openid'];
            unset($user_arr['openid']);
            $result_set_redis = $this->redis->set($openid,json_encode($user_arr),$week_time);
            $this->redis->set($user_arr['sess_key'],json_encode($user_arr),$week_time);
        }

        $user_info = $user_arr;
        unset($user_info['is_engineer']);
        unset($user_info['is_hr']);
        unset($user_info['is_agent']);
        unset($user_info['sess_key']);
        $user_info['identity_auth'] = ['is_engineer'=>$user_arr['is_engineer'],'is_hr'=>$user_arr['is_hr'],'is_agent'=>$user_arr['is_agent']];
        $data = [
            'sess_key'=>$sess_key,
            'user_info'=>$user_info,
        ];
        $bizobj = ['data'=>$data];
        $this->success('成功', $bizobj);
    }


    /*
     *注册
     */
    public function register()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $password = $data['password'] ?? '';
        $type = $data['type'] ?? 1;
        $code = $data['code'] ?? 0;

        if((!empty($sess_key))&&(!empty($code))&&(!empty($password))&&(!empty($mobile))){
            try{
                //验证短信验证码
                $user_info = $this->getTUserInfo($sess_key);
                $cache_code = $this->redis->get($user_info['openid']."_profile");
                if($cache_code==$code){   //添加用户密码和手机号
                    $userQuery = Db::table('user');
                    $userQuery->where('id','=',$user_info['id'])->update([
                        'mobile'=>$mobile,
                        'password'=>$password,
                        'logintime'=>time(),
                    ]);
                    $userQuery->removeOption();
                }else{
                    $this->error('验证码有误,请稍后再试');
                }
                $this->success('success');
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }

    }


    /*
     *注册
     */
    public function passwordLogin()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'] ?? '';
        $mobile = $data['mobile'] ?? '';
        $password = $data['password'] ?? '';

        if((!empty($sess_key))&&(!empty($mobile))&&(!empty($password))){
            try{
                //验证短信验证码
                $user_info = $this->getTUserInfo($sess_key);
                if($user_info['password']==$password){   //添加用户密码和手机号
                    $this->success('success');
                    $userQuery = Db::table('user');
                    $userQuery->where('id','=',$user_info['id'])->update([
                        'logintime'=>time(),
                    ]);
                    $userQuery->removeOption();


                    $user_arr =  Db::table('user')
                        ->where('openid','=',$user_info['openid'])
                        ->field('id,nickname,username,avatar,gender,mobile,city_name,city_code,is_engineer,is_hr,is_agent,sess_key,lng,lat,password')
                        ->find();
                    /*$sess_key = $user_arr['sess_key'];
                    $has_password = empty($user_arr['password']) ? 2 : 1 ;*/
                    $week_time = 24*60*60*7;
                    $this->redis->set($user_arr['sess_key'],json_encode($user_arr),$week_time);
                    $this->redis->set($user_info['openid'],json_encode($user_arr),$week_time);
                }else{
                    $this->error('密码有误,请稍后再试');
                }
            }catch(Exception $e){
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少参数',null,2);
        }

    }



}
