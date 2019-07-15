<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\wx\WXBizDataCrypt;
use app\common\library\Dater;
use fast\Http;
use think\cache\driver\Redis;
use think\Db;
use think\Session;
use think\Cache;




/**
 * 示例接口
 */
class Member extends Api
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

    public function getMemberByMemberID()
    {
        $data = $this->request->post();
       // $sess_key = $data['sess_key'];
       // $post_id = intval($data['post_id']);
        $member_id = $data['member_id'];  // 要回复的人 /发帖人或者是评论人
        if ($member_id > 0) {
            $member_info = Db::table('bbs_member')->where('id','=',$member_id)->find();
            $arr = ['nick_name'=>$member_info['nick_name']];
            $this->success('success',$arr);
        } else {
            $this->error('参数错误！');
        }
    }

    // 是否关注了某个用户
    public function isFollowMember()
    {
        $data = $this->request->request();
        $sess_key = $data['sess_key'];
        $user = $this->getGUserInfo($sess_key,2);
        $member = $user['member_info'];

        $member_id = intval($data['member_id']);
        if (!$member_id) {
            $this->error("参数异常!");
        }
        $count = Db::table('bbs_attention')
            ->where('member_id','=',$member['id'])
            ->where('member_for','=',$member_id)
            ->count();
        $is_follow = $count > 0;
        $data = [
            'is_follow' => $is_follow,
        ];
        $this->success('success',array('data' => $data));
    }

    //关注
    public function follow()
    {
        $data = $this->request->request();
        $sess_key = $data['sess_key'];
        $user = $this->getGUserInfo($sess_key,2);
        $member = $user['member_info'];
        $member_id = intval($data['member_id']);
        $count = Db::table('bbs_attention')
            ->where('member_id','=',$member['id'])
            ->where('member_for','=',$member_id)
            ->count();
        if ($count > 0) {
            $this->error("已经关注该用户，不能重复关注！");
        }
        $arr_attention = [
            'member_id' => $member['id'],
            'member_for' => $member_id,
            'isvalid' => 1,
            'createtime' => date('Y-m-d H:i:s'),
            'update_time' => time(),
            'add_time' => time()
        ];
        //关注
        $rs = Db::table('bbs_attention')->insert($arr_attention);
        if ($rs) {
            $this->success("关注成功！");
        } else {
            $this->error('关注失败！');
        }
    }

    //取消关注
    public function unfollow()
    {
        $data = $this->request->request();
        $sess_key = $data['sess_key'];
        $user = $this->getGUserInfo($sess_key,2);
        $member = $user['member_info'];

        $member_id = intval($data['member_id']);
        $update = [
            'member_id' => $member['id'],
            'member_for' => $member_id,
        ];

        $rs = Db::table('bbs_attention')
            ->where('member_id','=',$member['id'])
            ->where('member_for','=',$member_id)
            ->delete();

        if ($rs) {
            $this->success('取消关注成功');
        } else {
            $this->error('取消关注失败！');
        }
    }


    // 用户信息
    public function memberInfo()
    {
        $data = $this->request->request();
        $sess_key = $data['sess_key'];
        $user = $this->getGUserInfo($sess_key,2);


        $member = $user['member_info'];
        $user_info = $user['user_info'];
        //手机号为空,获取用户手机号
        if(empty($member['mobile'])){
            $member['mobile'] = $user_info['mobile'];
        }
        //$member = D('Member')->getMemberInfo(array('id' => $this->member['id']));
        // 我关注人的数量
        $member['attention_count'] = Db::table('bbs_attention')->where('member_id','=',$member['id'])->count();
       // $member['attention_count'] = D('Attention')->getAttentionCount(array('bbs_id' => $this->member['bbs_id'], 'member_id' => $this->member['id'], 'member_for' => array('GT', 0)));

        //收藏的数量
        $member['collect_count'] = Db::table('bbs_post_records')
            ->where('bbs_member_id','=',$member['id'])
            ->where('type','=',3)
            ->count();
       // $member['collect_count'] = D('PostRecords')->getPost_RecordsCount(array('bbs_id' => $this->member['bbs_id'], 'member_id' => $this->member['id'], 'type' => 3));

        //粉丝的数量, 别人关注我的数量
       // $member['fans_count'] = D('Attention')->getAttentionCount(array('bbs_id' => $this->member['bbs_id'], 'member_for' => $this->member['id']));
        $member['fans_count'] = Db::table('bbs_attention')->where('member_for','=',$member['id'])->count();
        $data = [
            'data'=>$member,
        ];
        $this->success('success',$data);
    }



    // 我关注的用户列表
    public function attentionMember()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10 ;
        $user = $this->getGUserInfo($sess_key,2);
        $my_info = $user['member_info'];


        $count = Db::table('bbs_attention')
            ->where('member_id','=',$my_info['id'])
            ->count();
        if($count>0){
            $data = Db::table('bbs_attention')
                ->alias('p')
                ->join('bbs_member m','p.member_for = m.id')
                ->field('p.*,m.nick_name,m.avatar_url,m.id as member_id')
                ->page($page,$page_size)
                ->where('p.member_id','=',$my_info['id'])
                ->order('p.id desc')
                ->select();
            $data1 = [];
            foreach ($data as $kd=>$vd){
                $member_info[0] = [
                    'nick_name' => $vd['nick_name'],
                    'avatar_url' => $vd['avatar_url'],
                    'id'=>$vd['member_id'],
                ];
                $add_time_text = date("m-d",strtotime($vd['update_time']));
                $data1[] = [
                    'member'=>$member_info,
                    'id' => $vd['id'],
                    'is_follow' => true,
                    'member_id' =>$my_info['id'],
                    'member_for' =>$vd['member_for'],
                ];
            }
            $total_page = ceil($count/$page_size);
            if($total_page>$page){
                $has_more = 1;
            }else{
                $has_more = 0;
            }
            $page_info = [
                'cur_page'=>$page,
                'page_size'=>$page_size,
                'total_items'=>$count,
                'total_pages'=>ceil($count/$page_size)
            ];
        }else{
            $data1 = [];
            $page_info= null;
            $has_more = 0;
        }


        $res = [
            'data' => $data1,
            'page_info' => $page_info,
            'has_more' => $has_more,
        ];

        $this->success("success", $res);
    }



    // 我的粉丝, 别人关注我
    public function fansList()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10 ;
        $user = $this->getGUserInfo($sess_key,2);
        $my_info = $user['member_info'];
        $count = Db::table('bbs_attention')
            ->where('member_for','=',$my_info['id'])
            ->count();
        if($count>0){
            $data = Db::table('bbs_attention')
                ->alias('p')
                ->join('bbs_member m','p.member_id = m.id')
                ->field('p.*,m.nick_name,m.avatar_url,m.id as member_id')
                ->page($page,$page_size)
                ->where('p.member_for','=',$my_info['id'])
                ->order('p.id desc')
                ->select();
            $data1 = [];
            foreach ($data as $kd=>$vd){
                $member_info[0] = [
                    'nick_name' => $vd['nick_name'],
                    'avatar_url' => $vd['avatar_url'],
                    'id'=>$vd['member_id'],
                ];
                $add_time_text = date("m-d",strtotime($vd['update_time']));
                $is_follow_info = Db::table('bbs_attention')
                    ->where('member_id','=',$my_info['id'])
                    ->where('member_for','=',$vd['member_id'])
                    ->find();
                if(!empty($is_follow_info)){
                    $is_follow = true;
                }else{
                    $is_follow = false;
                }
                $data1[] = [
                    'member'=>$member_info,
                    'id' => $vd['id'],
                    'is_follow' => $is_follow,
                    'member_id' =>$vd['member_id'],
                    'member_for' =>$my_info['id'],
                ];
            }
            $total_page = ceil($count/$page_size);
            if($total_page>$page){
                $has_more = 1;
            }else{
                $has_more = 0;
            }
            $page_info = [
                'cur_page'=>$page,
                'page_size'=>$page_size,
                'total_items'=>$count,
                'total_pages'=>ceil($count/$page_size)
            ];
        }else{
            $data1 = [];
            $page_info= null;
            $has_more = 0;
        }
        $res = [
            'data' => $data1,
            'page_info' => $page_info,
            'has_more' => $has_more,
        ];

        $this->success("success", $res);
    }


    //修改个人信息
    public function edit_member()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10 ;
        $user = $this->getGUserInfo($sess_key,2);
        $my_info = $user['member_info'];
        $avatar_url = $data['avatar_url'] ?? '';
        $nick_name = $data['nick_name'] ?? '';
        $data = array();
        if ($avatar_url) {
            $data['avatar_url'] = $avatar_url;
        }
        if ($nick_name) {
            $data['nick_name'] = $nick_name;
        }
        $rs = Db::table('bbs_member')
            ->where('id','=',$my_info['id'])
            ->update($data);

        if ($rs) {
            $this->success("修改成功！");
        } else {
            $this->error('修改失败！');
        }
    }


    public function changeMobile()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10 ;
        $user = $this->getGUserInfo($sess_key,2);
        $my_info = $user['member_info'];

        $mobile = $data['mobile'];
        if (!$mobile) {
            $this->error('参数异常!');
        }
        Db::table('bbs_member')->where('id','=',$my_info['id'])->update(array('mobile' => $mobile));
     //   D('Member')->updateMember(array('id' => $this->member['id']), array('mobile' => $mobile));
        $this->success('success');
    }

}
