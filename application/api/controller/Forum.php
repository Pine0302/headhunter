<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\wx\WXBizDataCrypt;
use fast\Http;
use think\cache\driver\Redis;
use think\Db;
use think\Session;
use think\Cache;
use app\common\library\UploadFile;
use app\common\library\Dater;


/**
 * 示例接口
 */
class Forum extends Api
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


    //顶部导航栏列表
    public function forumTypeList()
    {
        $list = Db::table('bbs_forumtype')
            ->where('enable', '=', 1)
            ->order('hot desc')
            ->select();
        $response['data'] = $list;
        $this->success('success', $response);

    }

    //论坛列表
    public function forumList(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $type_id = $data['type_id'];

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];

        $forum_list = Db::table('bbs_forum')
            ->where('bbs_forumtype_id','=',$type_id)
            ->where('isvalid','=',1)
            ->select();

        if(!empty($forum_list)){
            //获取用户关注的圈子
            $atten_list = Db::table('bbs_forumcollect')
                ->where('bbs_member_id','=',$my_info['id'])
                ->select();
            if (!empty($atten_list)){
                foreach($forum_list as $kf=>$vf){
                    $forum_list[$kf]['is_atten'] = 0;
                    $forum_list[$kf]['img'] = "https://mini3.pinecc.cn".$vf['img'];
                    foreach ($atten_list as $ka=>$va){
                        if($va['bbs_forum_id']==$vf['id']){
                            $forum_list[$kf]['is_atten'] = 1;
                        }
                    }
                }
            }else{
                foreach($forum_list as $kf=>$vf){
                    $forum_list[$kf]['is_atten'] = 0;
                    $forum_list[$kf]['img'] = "https://mini3.pinecc.cn".$vf['img'];
                }
            }
        }else {
            $forum_list = [];
        }
        $data = [
            'data' => $forum_list,
        ];
        $this->success("success",$data);
    }

    public function collectForum(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $type = $data['type'];
        $forum_id = $data['forum_id'];

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];
        if($type==1){  // 关注forum
            $check_collect_forum = Db::table('bbs_forumcollect')
                        ->where('bbs_member_id','=',$my_info['id'])
                        ->where('bbs_forum_id','=',$forum_id)
                        ->find();
            if(empty($check_collect_forum)){
                $arr = [
                    'bbs_member_id' => $my_info['id'],
                    'bbs_forum_id' => $forum_id,
                    'create_at' => date("Y-m-d H:i:s",time()),
                ];
               $result = Db::table('bbs_forumcollect')->insert($arr);
            }else{
                $result = 1;
            }
        }elseif($type==2){
            $result = Db::table('bbs_forumcollect')
                ->where('bbs_member_id','=',$my_info['id'])
                ->where('bbs_forum_id','=',$forum_id)
                ->delete();
        }
        if(!empty($result)){
           $this->success('success');
        }else{
            $this->error('error');
        }
    }


    public function getForumInfo(){
        $data = $this->request->post();
        $sess_key = $data['sess_key'];

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];

        $forum_id = $data['forum_id'];

        $forum_info = Db::table('bbs_forum')
            ->where("id","=",$forum_id)
            ->find();
        $tip_count = Db::table('bbs_post')
            ->where("bbs_forum_id","=",$forum_id)
            ->count();
        $forum_info['tip_count'] = $tip_count;
        $forum_info['img'] = "https://".$_SERVER['HTTP_HOST'].$forum_info['img'];

        //判断用户是否关注该forum
        $is_atten_info =  Db::table('bbs_forumcollect')
            ->where('bbs_forum_id',"=",$forum_id)
            ->where('bbs_member_id',"=",$my_info['id'])
            ->find();
        $forum_info['is_atten'] = empty($is_atten_info) ? 0 : 1;
        $data = [
            'data'=>$forum_info,
        ];
        $this->success('success',$data);
    }






}
