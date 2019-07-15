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
 * 示例接口
 */
class Blogin extends Api
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





    public function login()
    {
        $data = $this->request->post();
        $code = $data['code'];
        $mini_config_url = config('mini.url');
        $appid = config('Bbs.APPID');
        $app_secret = config('Bbs.APPSECRET');
        $login_url = $mini_config_url['wx_login']."?appid={$appid}&secret={$app_secret}&js_code={$code}&grant_type=authorization_code";

        $result_json = Http::get($login_url);
        var_dump($result_json);exit;
        $result = json_decode($result_json,true);
        $sess_key = $this->rd3_session(16);
     //   error_log(var_export($result_json,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");
        if(!empty($result['openid'])&&(!empty($result['session_key']))){
            $arr = [
                'openid'=>$result['openid'],
                'session_key'=>$result['session_key'],
                'sess_key'=>$sess_key,
            ];
    /*        error_log(var_export($arr,1),3,"/data/wwwroot/mini3.pinecc.cn/runtime/test.txt");exit;*/
            $this->redis->hmset($sess_key,$arr);
            $data = ['sess_key'=>$sess_key];
            $bizobj = ['data'=>$data];
            $this->success('成功', $bizobj);
        }else{
            $this->error('没有获取到数据');
        }

    }

    public function getUserInfo(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $sessionKey = $this->redis->hget($sess_key,'session_key');
        $appid = config('Bbs.APPID');
        $encryptedData = $data['encrypteData'];
        $iv = $data['iv'];

        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $user_info_json = $pc->decryptData($encryptedData, $iv, $data );
        $user_info_arr = json_decode($user_info_json,true);

        $unionId = $user_info_arr['unionId'];
        $openId = $user_info_arr['openId'];

        if(!empty($unionId)){
            $this->redis->hset($sess_key,'unionid',$unionId);
            $arr_user = [
                'openid_com'=>$user_info_arr['openId'],
                'unionid'=>$user_info_arr['unionId'],
                'nickname'=>$user_info_arr['nickName'],
                'gender'=>$user_info_arr['gender'],
                'avatar'=>$user_info_arr['avatarUrl'],
                'loginip'=>$this->request->ip(),
                'logintime'=>time(),
                'createtime'=>time(),
            ];
            //存数据库
            $checkUser = Db::table('user')->where('unionid',$unionId)->find();
            if(empty($checkUser)){
                Db::table('user')->insert($arr_user);
                $checkUser = Db::table('user')->where('unionid',$unionId)->find();
            }else{
                Db::table('user')->where('unionid',$unionId)->update($arr_user);
            }
         //   $this->wlog($checkUser);
            $arr_member = [
                'user_id' => $checkUser['id'],
                'open_id' => $openId,
                'nick_name' => $user_info_arr['nickName'],
                'avatar_url' => $user_info_arr['avatarUrl'],
                'gender' => $user_info_arr['gender'],
                'province' => $user_info_arr['province'],
                'city' => $user_info_arr['city'],
            ];
        //    $this->wlog($arr_member);
            $checkMember = Db::table('bbs_member')->where('open_id',$openId)->find();
            //  error_log(var_export($checkUser,1),3,$_SERVER['DOCUMENT_ROOT'].'/tt.txt');
            if(empty($checkMember)){
                Db::table('bbs_member')->insert($arr_member);
            }else{
                if(!empty($checkMember['avatar_url'])){
                    unset($arr_member['avatar_url']);
                }
                if(!empty($checkMember['nick_name'])){
                    unset($arr_member['nick_name']);
                }
                Db::table('bbs_member')->where('open_id',$openId)->update($arr_member);
            }
            $memberInfo = Db::table('bbs_member')->field('id,nick_name,avatar_url')->where('open_id',$openId)->find();
                $res_data = [
                'ukey'=>base64_encode($checkUser['id']),
                'nickname'=>$user_info_arr['nickName'],
                'gender'=>$user_info_arr['gender'],
                'avatar'=>$user_info_arr['avatarUrl'],
                'loginip'=>$this->request->ip(),
                'member_info'=>$memberInfo,
            ];
            $data = [
                'data'=>$res_data
            ];
            $this->success('success',$data);
        }else{
            $this->error('error');
        }

    }


//个人资料
    public function userInfo(){
        //  $data = $this->request->post();
        $data = $this->request->request();
        $sess_key = $data['sess_key'] ?? '';
        //   $data1 = file_get_contents("php://input");
        if(!empty($sess_key)){
            try {
                $user = $this->getGUserInfo($sess_key,2);
                if(!empty($user['member_info']) && (!empty($user['user_info']))){
                    $user_info = $user['user_info'];
                    $member_info = $user['member_info'];
                }else{
                    $user_info = [];
                    $member_info = [];
                }
                $arr = [
                    'user_info' => $user_info ,
                    'member_info' => $member_info ,
                ];
                $data = [
                    'data'=>$arr,
                ];
                $this->success('success', $data);
            } catch (Exception $e) {
                $this->error('网络繁忙,请稍后再试');
            }
        }else{
            $this->error('缺少必要的参数',null,2);
        }
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
        $arr = [
            ''
        ];
        var_dump();
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



}
