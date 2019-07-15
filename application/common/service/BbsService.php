<?php
/**
 * Created by PhpStorm.
 * User: jiqing
 * Date: 18-12-21
 * Time: 下午8:49
 */

namespace app\common\service;
// 服务层，介于C层与M层之间

/**  根据上面的分析，Service夹在C层和M层中间，从逻辑上大致划分为3大类：
### model侧的Service：也就是封装每个model与业务相关的通用数据接口，比如：查询订单。（我认为：访问远程服务获取数据也应该归属于这一类Service）
### 中间的Service：封装通用的业务逻辑，比如：计算订单折扣（会用到1中的Service）。
### controller侧的Service：基于1、2中的Service进一步封装对外接口的用户业务逻辑。
 **/

use app\common\model\UserModel;
use think\Db;
use fast\Http;
use fast\Wx;

class BbsService extends CommonService
{

    //获取文章的评论列表
    public function getCommentList($post_id){
        $replyQuery = Db::table('bbs_reply');
        $collect_arr = $replyQuery
            ->alias('r')
            ->join('user u','r.user_id = u.id','left')
            ->where('bbs_post_id','=',$post_id)
            ->field('r.*,u.username,u.avatar')
            ->select();
        $replyQuery->removeOption('where');

        return $collect_arr;
    }

    //检测用户是否关注另一个用户
    public function checkCollectUser($from_user_id,$to_user_id){
        $userCollectQuery = Db::table('re_user_collect');
        $collect_info = $userCollectQuery
            ->where('from_user_id',$from_user_id)
            ->where('to_user_id',$to_user_id)
            ->field('id')
            ->find();
        $userCollectQuery->removeOption();
        if(!empty($collect_info)){
            return true;
        }else{
            return false;
        }
    }



    //检测简历是否完善
    public function checkResumeFill($user_id){
        $resumeQuery = Db::table('re_resume');
        $resume_info = $resumeQuery
            ->where('user_id',$user_id)
            ->find();
        $resumeQuery->removeOption('where');
        if(!empty($resume_info['name'])&&(!empty($resume_info['mobile']))&&(!empty($resume_info['title']))){
            return true;
        }else{
            return false;
        }
    }



    //生成小程序二维码
    public function createShareQr($filename,$page,$arr)
    {
       /* $data = $arr;
        $local_file_path = $_SERVER['DOCUMENT_ROOT'] . "/sharepic/person_" . $user_info['id'] . ".png";
        if (file_exists($local_file_path)) {
            $arr_res['pic_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/sharepic/person_" . $user_info['id'] . ".png";
        } else {
            //获取access_token
            $wx_info = config('Wxpay');
            $arr = ['app_id' => $wx_info['APPID'], 'app_secret' => $wx_info['APPSECRET']];
            $wx = new Wx($arr);
            //生成小程序二维码
            $page = "pages/personal/login";
            $page = "";
            //   error_log(var_export($user_info['id'],1),3,$_SERVER['DOCUMENT_ROOT']."/test.txt");
            $return_file_path = $wx->get_qrcode_unlimit($user_info['id'], $page);

            $arr_res['pic_url'] = "http://" . $_SERVER['HTTP_HOST'] . "/sharepic/" . $return_file_path;
        }

        //     $this->wlog($arr_res['pic_url'],"tt.txt");
        if (!empty($arr_res['pic_url'])) {
            $data = [
                'data' => $arr_res,
            ];
            //     error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/tt.txt");
            $this->success('success', $data);

        }*/
    }


    //获取用户对应身份的coin
    public function getUserCoin($sess_key,$user_type){
        $userQuery = Db::table('user');
        $user_info = $userQuery->where('sess_key','=',$sess_key)->find();
        switch ($user_type){
            case 1:
                $coin = $user_info['coin'];
                break;
            case 2:
                $coin = $user_info['hr_coin'];
                break;
            case 3:
                $coin = $user_info['agent_coin'];
                break;
        }
        return $coin;
    }


}