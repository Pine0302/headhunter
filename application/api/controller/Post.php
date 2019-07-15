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
use app\common\service\UserService;
use app\common\service\BbsService;




/**
 * 示例接口
 */
class Post extends Api
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


    // 发帖
    public function savePost()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $type = $data['type'] ?? 1;
        $user_type = $data['user_type'] ?? 1;
        $title = $data['title'] ?? "";
        $lat = $data['lat'] ?? '';
        $lng = $data['lng'] ?? '';
        $address = $data['address'];
        $content = htmlspecialchars(trim($data['content']));
        $imgs = $data['imgs'];
        if ((!$content && !$imgs)) {
            //   $this->error("内容必填!");
        }


        $user = $this->getGUserInfo($sess_key);
        $member = $user;
        /*   if ($member['is_banned'] == 1) {
               $this->echoError("您已被禁言，暂时不能发帖！");
           }*/
        //处理图片

        if ($imgs != '') {
            //$imgArr = json_decode($imgs,true);
            $imgArr = $imgs;
        } else {

            $imgArr = array();
            /* $imgArr=[
             'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1556775438176&di=cfff5e14e7f4cd921bc493f1927fa896&imgtype=0&src=http%3A%2F%2Fk.zol-img.com.cn%2Fsjbbs%2F7692%2Fa7691515_s.jpg',
             'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1556775438174&di=930e973c979c2ebb2fb927efd07a3c38&imgtype=0&src=http%3A%2F%2Fpic53.nipic.com%2Ffile%2F20141115%2F9448607_175255450000_2.jpg',
             'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1556775489712&di=3afea8747d3f8bf80d3fc6f026dff12d&imgtype=0&src=http%3A%2F%2Fpic26.nipic.com%2F20121219%2F2457331_085744965000_2.jpg',
             ];*/
        }

        if(!empty($address)){
            $address_str =  $address[0].$address[1].$address[2];
        }
        $post = array(
            'type' => $type,
            //  'bbs_member_id' => $member['id'],
            'user_id'=>$user['id'],
            'content' => $content,
            'imgs' => ($imgArr && is_array($imgArr)) ? serialize($imgArr) : '',
            'address' =>$address_str,
            'latitude' => $lat,
            'longitude' => $lng,
            'user_type' => $user_type,
            'title' => $title,
            'status' => 1,
            'create_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
        );
        $postQuery = Db::table('bbs_post');
        $result = $postQuery->insert($post);
        if($result){
            $this->success('success');
        }else{
            $this->error('网络繁忙,请稍候再试');
        }


        /*  if ($insert_result) {
              //添加积分
              $point_set = config('point.tip');
              $arr = [
                  'bbs_member_id' => $member['id'],
                  'point' => $point_set['point'],
                  'point_item' => $point_set['point_item'],
                  'remark' => $point_set['remark'],
                  //   'is_valid'=> 1,
                  'add_time' => time(),
                  'create_time' => date("Y-m-d H:i:s", time()),

              ];
              Db::table('bbs_member_point')->insert($arr);

              //todo member列表添加积分
              Db::table('bbs_member')->where('id', '=', $member['id'])->setInc('point', 1);

              $this->success('success', 1);
          } else {
              $this->success('success', 1);
          }*/
    }


    //type=1 热门 2 ;最新 3:关注
    public function getPostList()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $user_type = $data['user_type'];
        $type = $data['type'] ?? 1;
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10;
        $user_info = $this->getGUserInfo($sess_key);
        $postQuery = Db::table('bbs_post');
        $postQuery->alias('p');
        $postQuery->where('p.status','=',1);
        switch($type){
            case 1:                              //推荐贴
                $recommend = 1;
                $postQuery->where('p.recommend','=',1);
                break;
            case 2:                              //关注贴
                //获取我关注的人
                $userServiceObj = new UserService();
                $user_collect_list = $userServiceObj->getCollectUser($user_info['id']);
                $postQuery->where('p.user_id','in',$user_collect_list);
                break;
            case 3:                             //筛选贴
                $postQuery->where('p.user_type','=',$user_type);
                break;
        }

        $count = $postQuery->count();
        $postQuery->removeOption('field');
        $post_list = [];
        if($count>0){
            $daterObj = new Dater();
            $post_arr = $postQuery
                ->join('user u','p.user_id = u.id','left')
                ->join('bbs_collect c','p.user_id = c.user_id and p.id = c.bbs_post_id','left')
                ->field('p.*,u.username,u.avatar,c.id as cid')
                ->page($page,$page_size)
                ->select();

            foreach($post_arr as $kp=>$vp){
                $author_info = [
                    'id'=>$vp['user_id'],
                    'user_type'=>$vp['user_type'],
                    'username'=>$vp['username'],
                    'avatar'=>$vp['avatar'],
                ];

                $publish_time =$daterObj -> socialDateDisplay(strtotime($vp['create_time']));
                $imgs = [];
                if(!empty($vp['imgs'])){
                  //  var_dump($vp['imgs']);
                    $imgs = $this->mb_unserialize($vp['imgs']);
                    $imgs = unserialize($vp['imgs']);
                }
                $ic_collect = (empty($va['cid'])) ? 2 : 1;
                $post_list[] = [
                    'id' => $vp['id'],
                    'user_info'=>$author_info,
                    'type' => $vp['type'],
                    'title' => $vp['title'],
                    'content' => $vp['content'],
                    'address' => $vp['address'],
                    'message_count' => $vp['message_count'],
                    'collect_count' => $vp['thumb_up_count'],
                    'repost_count' => $vp['repost_count'],
                    'ic_collect' => $ic_collect,
                    'imgs' => $imgs,
                    'publish_time' => $publish_time,

                ];

            }
        }
        $total_page = ceil($count / $page_size);
        $page_info = [
            'cur_page' => $page,
            'page_size' => $page_size,
            'total_items' => $count,
            'total_pages' => $total_page
        ];
        $response_data = [
            'data'=>[
                'post_list'=>$post_list,
                'page_info'=>$page_info,
            ],
        ];
        $this->success('success', $response_data);
    }

    //获取某个人帖子列表
    public function getMemberPostList()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $user_id = $data['user_id'] ?? 0;
        //  $type = $data['type'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10;

        $postQuery = Db::table('bbs_post');
        $postQuery->alias('p');
        $postQuery->where('p.status','=',1);

        $user_info = $this->getGUserInfo($sess_key);
        if(empty($user_id)){
            $postQuery->where('p.user_id','=',$user_info['id']);
        }else{
            $postQuery->where('p.user_id','=',$user_id);
        }
        $count = $postQuery->count();
        $postQuery->removeOption('field');
        $post_list = [];
        if($count>0){
            $daterObj = new Dater();
            $post_arr = $postQuery
                ->join('user u','p.user_id = u.id','left')
                ->join('bbs_collect c','p.user_id = c.user_id and p.id = c.bbs_post_id','left')
                ->field('p.*,u.username,u.avatar,c.id as cid')
                ->page($page,$page_size)
                ->select();

            foreach($post_arr as $kp=>$vp){
                $author_info = [
                    'id'=>$vp['user_id'],
                    'user_type'=>$vp['user_type'],
                    'username'=>$vp['username'],
                    'avatar'=>$vp['avatar'],
                ];

                $publish_time =$daterObj -> socialDateDisplay(strtotime($vp['create_time']));
                $imgs = [];
                if(!empty($vp['imgs'])){
                  //  $imgs = $this->mb_unserialize($vp['imgs']);
                    $imgs = unserialize($vp['imgs']);
                }
                $ic_collect = (empty($va['cid'])) ? 2 : 1;
                $post_list[] = [
                    'id' => $vp['id'],
                    'user_info'=>$author_info,
                    'type' => $vp['type'],
                    'title' => $vp['title'],
                    'content' => $vp['content'],
                    'address' => $vp['address'],
                    'message_count' => $vp['message_count'],
                    'collect_count' => $vp['thumb_up_count'],
                    'repost_count' => $vp['repost_count'],
                    'ic_collect' => $ic_collect,
                    'imgs' => $imgs,
                    'publish_time' => $publish_time,

                ];

            }
        }
        $total_page = ceil($count / $page_size);
        $page_info = [
            'cur_page' => $page,
            'page_size' => $page_size,
            'total_items' => $count,
            'total_pages' => $total_page
        ];
        $response_data = [
            'data'=>[
                'post_list'=>$post_list,
                'page_info'=>$page_info,
            ],
        ];
        $this->success('success', $response_data);
    }


    /**
     * 帖子详情
     */
    public function getPostInfo()
    {
        $data = $this->request->post();
        $id = intval($data['id']);
        $sess_key = $data['sess_key'];


        $user_info = $this->getGUserInfo($sess_key);

        $postQuery = Db::table('bbs_post');

        //给帖子增加一个浏览量
        $postQuery->where("id",'=',$id)->setInc('view_count',1);
        $postQuery->removeOption('where');

        $post_detail = $postQuery
            ->alias('p')
            ->join('user u','p.user_id = u.id','left')
            ->where('p.id','=',$id)
            ->field('p.*,u.username,u.avatar')
            ->find();
        //判断石关注了该用户
        $user_collect = 2;
        $userServiceObj = new UserService();
        $check_collect = $userServiceObj->checkCollectUser($user_info['id'],$post_detail['user_id']);
        $user_collect = empty($check_collect) ? 2:1;
        $author_info = [
            'id'=>$post_detail['user_id'],
            'user_type'=>$post_detail['user_type'],
            'username'=>$post_detail['username'],
            'avatar'=>$post_detail['avatar'],
            'is_collect'=>$user_collect,
        ];

        $daterObj = new Dater();
        $publish_time =$daterObj -> socialDateDisplay(strtotime($post_detail['create_time']));
        $imgs= [];
        if(!empty($post_detail['imgs'])){
          //  $imgs = $this->mb_unserialize($post_detail['imgs']);
            $imgs = unserialize($post_detail['imgs']);
        }

        $collect_info = Db::table('bbs_post_records')
            ->where('type','=',1)
            ->where('bbs_post_id','=',$id)
            ->where('user_id','=',$user_info['id'])
            ->find();
        $is_collect = (empty($collect_info)) ? 2 : 1;

        $bbsServiceObj = new BbsService();
        $comment_list =[];
        $comment_arr = $bbsServiceObj->getCommentList($post_detail['id']);
        if(!empty($comment_arr)){
            $i = 0;
            foreach($comment_arr as $kc=>$vc){

                if($vc['parent_id']==0){
                    $comment_list[$i] = [
                        'user_info'=>[
                            'id'=>$vc['user_id'],
                            'avatar'=>$vc['avatar'],
                            'username'=>$vc['username'],
                        ],
                        'content'=>$vc['content'],
                        'id'=>$vc['id'],
                        'parent_id'=>$vc['parent_id'],
                        'create_time'=>$vc['createtime'],
                    ];
                    $i++;
                }else{
                    $sub_comment_list[] = [
                        'user_info'=>[
                            'id'=>$vc['user_id'],
                            'avatar'=>$vc['avatar'],
                            'username'=>$vc['username'],
                        ],
                        'content'=>$vc['content'],
                        'id'=>$vc['id'],
                        'parent_id'=>$vc['parent_id'],
                        'create_time'=>$vc['createtime'],
                    ];
                }

            }
            if(!empty($sub_comment_list)){
                foreach($comment_list as $kcc=>$vcc){
                    foreach($sub_comment_list as $ks=>$vs){
                        if($vs['parent_id']==$vcc['id']){
                            $comment_list[$kcc]['sub_comment'][] = $vs;
                            unset($sub_comment_list[$ks]);
                        }
                    }
                }
            }
        }
        $post_info = [
            'id'=>$post_detail['id'],
            'type'=>$post_detail['type'],
            'user_info'=>$author_info,
            'title'=>$post_detail['title'],
            'content'=>$post_detail['content'],
            'address'=>$post_detail['address'],
            'publish_time'=>$publish_time,
            'imgs'=>$imgs,
            'message_count'=>$post_detail['message_count'],
            'repost_count'=>$post_detail['repost_count'],
            'collect_count'=>$post_detail['thumb_up_count'],
            'is_collect'=>$is_collect,
            'comment_list'=>$comment_list,
        ];
        $response_data = [
            'data'=>[
                'post_info'=>$post_info,
            ],
        ];
        $this->success('success', $response_data);
    }


    // 帖子点赞
    public function postThumbUp()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $post_id = intval($data['post_id']);

        $postQuery = Db::table('bbs_post');
        $post_info = $postQuery->where('id','=',$post_id)->find();
        $postQuery->removeOption();

        $member_for = $post_info['user_id'];  // 发帖人

        $user_info = $this->getGUserInfo($sess_key);

        if (!$post_id) {
            $this->error('找不到帖子!');
        }

        $check_thumb_up = Db::table('bbs_post_records')
            ->where('user_id', '=', $user_info['id'])
            ->where('bbs_post_id', '=', $post_id)
            ->where('type', '=', 1)
            ->find();
        if (!empty($check_thumb_up)) {
            $this->error('已经点赞了,不能重复点赞!');
        }
        //添加点赞记录
        $arr_isnert = [
            'bbs_post_id' => $post_id,
            'user_id' => $user_info['id'],
            'type' => 1,
            'member_for' => $member_for,
            'isvalid' => 1,
            'is_read' => 0,
            'createtime' => date("Y-m-d H:i:s", time()),
            'add_time' => time(),
            'update_time' => time(),
        ];

        $rs = Db::table('bbs_post_records')->insert($arr_isnert);
        Db::table('bbs_post_records')->removeOption();
        if ($rs) {
            //帖子点赞数+1
            Db::table('bbs_post')->where('id', $post_id)->setInc('thumb_up_count', 1);
            Db::table('bbs_post')->removeOption();
            $this->success('success');
        } else {
            $this->error('网络繁忙,请稍后再试!');
        }
    }

    // 取消点赞
    public function postUnThumbUp()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $post_id = intval($data['post_id']);

        $user_info = $this->getGUserInfo($sess_key);
        /*$user_info = $user['user_info'];
        $member = $user['member_info'];*/
        if (!$post_id) {
            $this->error('找不到帖子!');
        }
        $rs = Db::table('bbs_post_records')
            ->where('bbs_post_id', "=", $post_id)
            ->where('user_id', "=", $user_info['id'])
            ->delete();
        Db::table('bbs_post_records')->removeOption();
        if ($rs) {
            //帖子点赞数-1
            Db::table('bbs_post')->where('id', $post_id)->setDec('thumb_up_count', 1);
            Db::table('bbs_post')->removeOption();
            $this->success("success");
        } else {
            $this->error('网络繁忙,请稍后再试!');
        }
    }


    // 回复帖子
    public function replyPost()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $post_id = intval($data['post_id']);
        $parent_id = intval($data['parent_id']);
        if (!$post_id) {
            $this->error('找不到帖子!');
        }

        $content = trim($data['content']);


        $postQuery = Db::table('bbs_post');
        $post_info = $postQuery->where('id','=',$post_id)->find();
        $postQuery->removeOption();

        $member_for = $post_info['user_id'];  // 发帖人
        $user_info = $this->getGUserInfo($sess_key);

        if (!$content) {
            $this->error('请输入内容!');
        }
        /*    $imgs = $data['imgs'];
            //处理图片
            if ($imgs != '') {
                $imgArr = explode(',', $imgs);
            } else {
                $imgArr = array();
            }*/
        $reply = array(
            'user_id' => $user_info['id'],
            'parent_id' => intval($data['parent_id']),
            'member_for' => intval($member_for),
            'bbs_post_id' => $post_id,
            'content' => $content,
            //  'imgs' => serialize($imgArr),
            'is_read' => 0,
            'status' => 2,
            'createtime'=>date('Y-m-d H:i:s',time()),
            'add_time' => time(),
            'update_time' => time(),
        );

        //  $rs = D('Reply')->addReply($reply);
        $rs = Db::table('bbs_reply')->insert($reply);
        Db::table('bbs_reply')->removeOption();
        if ($rs) {
            Db::table('bbs_post')->where('id', '=', $post_id)->setInc('message_count', 1);
            Db::table('bbs_post')->removeOption();
            //todo给回复帖子的人增加1分积分
            //todo member列表添加积分

            /*  $point_set = config('point.reply');
              $arr = [
                  'bbs_member_id' => $member['id'],
                  'point' => $point_set['point'],
                  'point_item' => $point_set['point_item'],
                  'remark' => $point_set['remark'],
                  'add_time' => time(),
                  'create_time' => date("Y-m-d H:i:s", time()),

              ];
              Db::table('bbs_member_point')->insert($arr);
              Db::table('bbs_member')->where('id', '=', $member['id'])->setInc('point', 1);*/
            $this->success("操作成功!");;
        } else {
            $this->error("操作失败!");
        }
    }




    // 转发帖子
    public function rePost()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $post_id = intval($data['post_id']);
        $user_type = intval($data['user_type']);
        if (!$post_id) {
            $this->error('找不到帖子!');
        }
        $postQuery = Db::table('bbs_post');
        $post_info = $postQuery->where('id','=',$post_id)->find();
        $postQuery->removeOption();

        $user_info = $this->getGUserInfo($sess_key);
        unset($post_info['id']);
        $post_info['user_id'] = $user_info['id'];
        $post_info['user_type'] = $user_type;
        $post_info['thumb_up_count'] = 0;
        $post_info['message_count'] = 0;
        $post_info['view_count'] = 0;
        $post_info['repost_count'] = 0;
        $post_info['recommend'] = 2;
        $post_info['create_time'] = date("Y-m-d H:i:s");
        $post_info['update_time'] = date("Y-m-d H:i:s");
        $post_info['latitude'] = $user_info['lat'] ?? '';
        $post_info['longitude'] = $user_info['lng'] ?? '';

        $rs = Db::table('bbs_post')->insert($post_info);
        Db::table('bbs_post')->removeOption();

        Db::table('bbs_post')->where('id','=',$post_id)->setInc('repost_count',1);
        if ($rs) {
            //帖子点赞数+1
            $this->success('success');
        } else {
            $this->error('系统繁忙,请稍后再试!');
        }
    }












    //顶部导航栏列表
    public function topPostList()
    {
        $data = Db::table('bbs_rec')
            ->where('status', '=', 1)
            ->order('hot desc')
            ->select();
        $response['data'] = $data;
        $this->success('success', $response);

    }


    //顶部导航栏列表
    public function bottomMenuList()
    {
        $arr = [
            [
                'title' => '首页',
                'default_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/home_default.png",
                'choose_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/home_choose.png",
                'footer_url' => 1,
            ],
            [
                'title' => '圈子',
                'default_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/circle_default.png",
                'choose_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/circle_choose.png",
                'footer_url' => 7,
            ],
            [
                'title' => '私信',
                'default_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/msg_default.png",
                'choose_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/msg_choose.png",
                'footer_url' => 3,
            ],
            [
                'title' => '我的',
                'default_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/my_default.png",
                'choose_img' => 'https://' . $_SERVER['HTTP_HOST'] . "/webinfo/footimg/my_choose.png",
                'footer_url' => 4,
            ],
        ];
        $response['data'] = $arr;
        $this->success('success', $response);
    }




    // 根据帖子id, 获取其评论列表
    public function getPostReplyList()
    {
        $data = $this->request->post();
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 6;
        $post_id = intval($data['post_id']);
        if (!$post_id) {
            $this->error('参数异常!');
        }
        $list = Db::table('bbs_reply')
            ->alias('r')
            ->join('bbs_member m', 'm.id = r.bbs_member_id')
            ->where('r.bbs_post_id', '=', $post_id)
            ->where('r.status', '=', 2)
            ->field('r.*,m.nick_name,m.avatar_url,m.id as member_id')
            ->page($page, $page_size)
            ->order('id desc')
            ->select();

        foreach ($list as $kl => $vl) {
            $list[$kl]['content'] = $this->replaceMessageExpression($vl['content']);
            $list[$kl]['member'] = [
                'id' => $vl['member_id'],
                'nick_name' => $vl['nick_name'],
                'avatar_url' => $vl['avatar_url'],
            ];
            $img_arr = unserialize($vl['imgs']);
            $new_img_arr = [];
            if (!empty($img_arr)) {
                foreach ($img_arr as $ki => $vi) {
                    $new_img_arr[] = "https://" . $_SERVER['HTTP_HOST'] . "/postimg/" . $vi;
                }
            }
            $list[$kl]['imgs'] = $new_img_arr;
            if (!empty($vl['parent_id'])) {
                $reply_info = Db::table('bbs_reply')->where('id', '=', $vl['parent_id'])->find();
                $reply_member = Db::table('bbs_member')->where('id', '=', $reply_info['bbs_member_id'])->find();
            } else {
                $reply_member = [];
            }
            $list[$kl]['reply_member']['0'] = $reply_member;
        }


        $count = Db::table('bbs_reply')
            ->where('bbs_post_id', '=', $post_id)
            ->where('status', '=', 2)
            ->count();
        $page_total = ceil($count / $page_size);
        $has_more = ($page < $page_total) ? 1 : 0;
        $response = [
            'data' => $list,
            'has_more' => $has_more,
            'page_total' => $page_total,
        ];
        $this->success("success", $response);
    }


    /**
     * 替换表情
     * @param $content
     * @return string
     */
    function replaceMessageExpression($content)
    {
        preg_match_all('(\\[[^\\]]*\\])', $content, $result);

        if ($result) {
            foreach ($result[0] as $value) {
                $number = substr($value, 1, strlen($value) - 2);
                $url = "https://" . $_SERVER['HTTP_HOST'] . '/uploads/web/emojis/' . $number . '.gif';
                $content = str_replace($value, '|' . $url . '|', $content);
            }
            $strArray = explode('|', $content);
            $newContent = [];
            foreach ($strArray as $value) {
                if ($value != '') {

                    if (strpos($value, 'https://') !== false) {
                        $newContent[] = array('isTest' => 0, 'value' => $value);
                    } else {
                        $newContent[] = array('isTest' => 1, 'value' => $value);
                    }

                }
            }
            return $newContent;
        } else {
            return array('isTest' => 1, 'value' => $content);
        }
    }


    // 帖子点赞列表
    public function getThumbUpList()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 6;


        $post_id = $data['post_id'] ?? '';

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $member = $user['member_info'];

        $post_info = Db::table('bbs_post')
            ->where('id', "=", $post_id)
            ->find();
        if ($post_info['bbs_member_id'] == $member['id']) {
            //设置为已读
            Db::table('bbs_post_records')->where('bbs_post_id', '=', $post_id)->update(['is_read' => 1]);
        }
        $list = Db::table('bbs_post_records')
            ->alias('c')
            ->join('bbs_member m', 'm.id = c.bbs_member_id')
            //  ->join('bbs_post p','p.id = c.bbs_post_id')
            ->field('c.*,m.id as member_id,m.avatar_url,m.nick_name')
            ->where('c.type', '=', 1)
            ->where('c.bbs_post_id', '=', $post_id)
            ->page($page, $page_size)
            ->order('c.id desc')
            ->select();
        //var_dump($list);exit;
        $DaterObj = new Dater();
        if (!empty($list)) {
            foreach ($list as $kl => $vl) {
                $add_time = $DaterObj->socialDateDisplay($vl['add_time']);
                $list[$kl]['add_time'] = $add_time;
                $list[$kl]['member'] = [
                    'id' => $vl['member_id'],
                    'avatar_url' => $vl['avatar_url'],
                    'nick_name' => $vl['nick_name'],
                ];
            }
        }
        $count = Db::table('bbs_post_records')
            ->where('type', '=', 1)
            ->where('bbs_post_id', '=', $post_id)
            ->count();


        //$page_count = ($count % $this->pageSize == 0) ? ($count / $this->pageSize) : intval($count / $this->pageSize) + 1;
        $page_total = ceil($count / $page_size);
        $has_more = ($page_total > $page) ? true : false;
        $res = [
            'data' => $list,
            'has_more' => $has_more,
            'page_total' => $page_total,
        ];
        $this->success("success", $res);
    }

    // 帖子点赞列表
    public function getMyThumbUpList()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 6;

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $member = $user['member_info'];
        $my_info = $user['member_info'];

      /*  $post_info = Db::table('bbs_post')
            ->where('id', "=", $post_id)
            ->find();*/
     //   if ($post_info['bbs_member_id'] == $member['id']) {
            //设置为已读
            Db::table('bbs_post_records')->where('member_for', '=', $member['id'])->update(['is_read' => 1]);
      //  }
        $list = Db::table('bbs_post_records')
            ->alias('c')
            ->join('bbs_member m', 'm.id = c.bbs_member_id')
            //  ->join('bbs_post p','p.id = c.bbs_post_id')
            ->field('c.*,m.id as member_id,m.avatar_url,m.nick_name')
            ->where('c.type', '=', 1)
            ->where('c.member_for', '=', $member['id'])
            ->page($page, $page_size)
            ->order('c.id desc')
            ->select();
        $DaterObj = new Dater();
        if (!empty($list)) {
            foreach ($list as $kl => $vl) {


                $add_time = $DaterObj->socialDateDisplay($vl['add_time']);
                $list[$kl]['add_time'] = $add_time;
                $list[$kl]['member'] = [
                    'id' => $vl['member_id'],
                    'avatar_url' => $vl['avatar_url'],
                    'nick_name' => $vl['nick_name'],
                ];
                $list[$kl]['post_id'] = $vl['bbs_post_id'];

                //获取post 信息
                $post_info = Db::table('bbs_post')
                    ->where('id','=',$vl['bbs_post_id'])
                    ->find();
                if(!empty($post_info)){
                    $add_time_text = date("m-d", strtotime($post_info['update_time']));
                    if (empty($vd['imgs'])) {
                        $data1 = [
                            'member' => $my_info,
                            'imgs' => array(),
                            'id' => $post_info['id'],
                            'content' => $post_info['content'],
                            'title' => $post_info['title'],
                            'thumb_up_count' => $post_info['thumb_up_count'],
                            'message_count' => $post_info['message_count'],
                            'view_count' => $post_info['view_count'],
                            'add_time_txt' => $add_time_text,
                            'member_id' => $post_info['bbs_member_id'],
                        ];
                    } else {
                        $imgs = unserialize($this->mb_unserialize($vd["imgs"]));
                        foreach ($imgs as $ki => $vi) {
                            $imgs[$ki] = "https://" . $_SERVER['HTTP_HOST'] . "/postimg/" . $vi;
                        }
                        $data1 = array(
                            'member' => $my_info,
                            'imgs' => $imgs,
                            'id' => $post_info['id'],
                            'title' => $post_info['title'],
                            'content' => $post_info['content'],
                            'thumb_up_count' => $post_info['thumb_up_count'],
                            'message_count' => $post_info['message_count'],
                            'view_count' => $post_info['view_count'],
                            'add_time_txt' => $add_time_text,
                            'member_id' => $post_info['bbs_member_id'],
                        );
                    }
                }else{
                    $data1 = [];
                }
                $list[$kl]['post_info'] = $data1;
            }

        }
        $count = Db::table('bbs_post_records')
            ->where('type', '=', 1)
            ->where('member_for', '=', $member['id'])
            ->count();


        //$page_count = ($count % $this->pageSize == 0) ? ($count / $this->pageSize) : intval($count / $this->pageSize) + 1;
        $page_total = ceil($count / $page_size);
        $has_more = ($page_total > $page) ? true : false;
        $res = [
            'data' => $list,
            'has_more' => $has_more,
            'page_total' => $page_total,
        ];
        $this->success("success", $res);
    }




    public function mb_unserialize($str)
    {
        return preg_replace_callback('#s:(\d+):"(.*?)";#s', function ($match) {
            return 's:' . strlen($match[2]) . ':"' . $match[2] . '";';
        }, $str);
    }



    //收藏
    public function collect()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $post_id = intval($data['post_id']);
        $member_for = $data['member_for'];  // 发帖人

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $member = $user['member_info'];
        if (!$post_id) {
            $this->error('找不到帖子!');
        }
        $collect_check = Db::table('bbs_post_records')
            ->where('bbs_member_id', '=', $member['id'])
            ->where('bbs_post_id', '=', $post_id)
            ->where('type', '=', 3)
            ->find();
        if (!empty($collect_check)) {
            $this->success("收藏成功");
        } else {
            $arr_collect = [
                'type' => 3,
                'bbs_member_id' => $member['id'],
                'member_for' => $member_for,
                'bbs_post_id' => $post_id,
                'isvalid' => 1,
                'is_read' => 0,
                'createtime' => date('Y-m-d H:i:s', time()),
                'add_time' => time(),
                'update_time' => time(),
            ];
            $rs = Db::table('bbs_post_records')
                ->insert($arr_collect);
            if ($rs) {
                $this->success("收藏成功");
            } else {
                $this->error('收藏失败!');
            }
        }
    }


    //取消收藏
    public function uncollect()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $post_id = intval($data['post_id']);
        $member_for = $data['member_for'];  // 发帖人

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $member = $user['member_info'];
        $rs = Db::table('bbs_post_records')
            ->where('bbs_member_id', '=', $member['id'])
            ->where('bbs_post_id', '=', $post_id)
            ->where('type', '=', 3)
            ->delete();
        if ($rs) {
            $this->success("取消收藏成功");
        } else {
            $this->error('取消收藏失败！');
        }
    }





    //某个用户是否给某个帖子点赞
    //type 1 点赞 2:查看 3:收藏
    //  返回 true 是  false 否
    public function isMemberType($member_id, $post_id, $type)
    {
        $checkMemberNiceInfo = Db::table('bbs_post_records')
            ->where('bbs_member_id', '=', $member_id)
            ->where('bbs_post_id', '=', $post_id)
            ->where('type', '=', $type)
            ->find();
        if (!empty($checkMemberNiceInfo)) {
            return true;
        } else {
            return false;
        }
    }


    public function getMyAttentionMember($member_id)
    {
        //获取我关注的用户
        $attention_list = Db::table('bbs_attention')
            ->where('member_id', '=', $member_id)
            ->field('member_for')
            ->select();
        return $attention_list;
    }


    // 我收藏的帖子
    public function collectionPostList()
    {
        //获取我的信息
        $data = $this->request->post();

        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10;
        $user = $this->getGUserInfo($sess_key, 2);

        $user_info = $user['user_info'];
        $my_info = $user['member_info'];
        //获取我收藏的帖子id
        $collect_info = Db::table('bbs_post_records')
                        ->where('type','=',3)
                        ->where('bbs_member_id','=',$my_info['id'])
                        ->field("bbs_post_id")
                        ->select();

        $bbs_post_ids = [];
        if (!empty($collect_info)) {
            foreach ($collect_info as $ka => $va) {
                $bbs_post_ids[] = $va['bbs_post_id'];
            }
            $bbs_post_ids = array_values($bbs_post_ids);

            $count = Db::table('bbs_post')
                ->where('id', 'in', $bbs_post_ids)
                ->count();
            if ($count > 0) {
                $data = Db::table('bbs_post')
                    ->alias('p')
                    ->join('bbs_member m', 'p.bbs_member_id = m.id')
                    ->field('p.*,m.nick_name,m.avatar_url,m.id as member_id')
                    ->page($page, $page_size)
                    ->where('p.id', 'in', $bbs_post_ids)
                    ->order('p.id desc')
                    ->select();
                $data1 = [];
                foreach ($data as $kd => $vd) {
                    $member_info = [
                        'nick_name' => $vd['nick_name'],
                        'avatar_url' => $vd['avatar_url'],
                        'id' => $vd['member_id'],
                    ];
                    $add_time_text = date("m-d", strtotime($vd['update_time']));
                    if (empty($vd['imgs'])) {
                        $data1[] = [
                            'member' => $member_info,
                            'nick_name'=>$vd['nick_name'],
                            'avatar_url'=>$vd['avatar_url'],
                            'imgs' => array(),
                            'id' => $vd['id'],
                            'title' => $vd['title'],
                            'content' => $vd['content'],
                            'thumb_up_count' => $vd['thumb_up_count'],
                            'message_count' => $vd['message_count'],
                            'view_count' => $vd['view_count'],
                            'add_time_txt' => $add_time_text,
                            'collection_time_txt' => $add_time_text,
                            'post_id'=>$vd['id'],
                        ];
                    } else {
                        $imgs = unserialize($this->mb_unserialize($vd["imgs"]));
                        foreach ($imgs as $ki => $vi) {
                            $imgs[$ki] = "https://" . $_SERVER['HTTP_HOST'] . "/postimg/" . $vi;
                        }
                        $data1[] = array(
                            'member' => $member_info,
                            'imgs' => $imgs,
                            'nick_name'=>$vd['nick_name'],
                            'avatar_url'=>$vd['avatar_url'],
                            'id' => $vd['id'],
                            'title' => $vd['title'],
                            'content' => $vd['content'],
                            'thumb_up_count' => $vd['thumb_up_count'],
                            'message_count' => $vd['message_count'],
                            'view_count' => $vd['view_count'],
                            'add_time_txt' => $add_time_text,
                            'collection_time_txt' => $add_time_text,
                            'post_id'=>$vd['id'],
                        );
                    }
                }
                $total_page = ceil($count / $page_size);
                if ($total_page > $page) {
                    $has_more = 1;
                } else {
                    $has_more = 0;
                }
                $page_info = [
                    'cur_page' => $page,
                    'page_size' => $page_size,
                    'total_items' => $count,
                    'total_pages' => ceil($count / $page_size)
                ];
            } else {
                $data1 = null;
                $page_info = null;
                $has_more = 0;
            }
        } else {
            $data1 = [];
            $page_info = null;
            $has_more = 0;
        }
        $res = [
            'data' => $data1,
            'page_info' => $page_info,
            'has_more' => $has_more,
        ];
        $this->success('success', $res);
    }




    /**
     * 回复消息列表
     */
    public function myReplyList()
    {

        //获取我的信息
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10;
        $user = $this->getGUserInfo($sess_key, 2);

        $user_info = $user['user_info'];
        $my_info = $user['member_info'];

        $count = Db::table('bbs_reply')
            ->alias('r')
            ->where('r.status', '=', 2)
            ->where('r.member_for', '=', $my_info['id'])
            ->count();
        //把所有的评论is_read 设置为1
        Db::table('bbs_reply')
            ->alias('r')
            ->where('r.status', '=', 2)
            ->where('r.member_for', '=', $my_info['id'])
            ->update(['is_read'=>1]);
        if($count){
            $list = Db::table('bbs_reply')
                ->alias('r')
                ->join('bbs_member m', 'm.id = r.bbs_member_id')
                ->where('r.status', '=', 2)
                ->where('r.member_for', '=', $my_info['id'])
                ->field('r.*,m.nick_name,m.avatar_url,m.id as member_id ')
                ->page($page, $page_size)
                ->order('r.id desc')
                ->select();
            $DaterObj = new Dater();
            foreach ($list as $kl => $vl) {
                $list[$kl]['content'] = $this->replaceMessageExpression($vl['content']);
                $list[$kl]['add_time'] = $DaterObj->socialDateDisplay($vl['add_time']);

                $list[$kl]['post_id'] = $vl['bbs_post_id'];
                $list[$kl]['member'] = [
                    'id' => $vl['member_id'],
                    'nick_name' => $vl['nick_name'],
                    'avatar_url' => $vl['avatar_url'],
                ];
                $img_arr = unserialize($vl['imgs']);
                $new_img_arr = [];
                if (!empty($img_arr)) {
                    foreach ($img_arr as $ki => $vi) {
                        $new_img_arr[] = "https://" . $_SERVER['HTTP_HOST'] . "/postimg/" . $vi;
                    }
                }
                $list[$kl]['imgs'] = $new_img_arr;
                if (!empty($vl['parent_id'])) {
                    $reply_info = Db::table('bbs_reply')->where('id', '=', $vl['parent_id'])->find();
                    $reply_member = Db::table('bbs_member')->where('id', '=', $reply_info['bbs_member_id'])->find();
                } else {
                    $reply_member = [];
                }
                $list[$kl]['reply_member']['0'] = $reply_member;

                //获取post 信息
                $post_info = Db::table('bbs_post')
                    ->where('id','=',$vl['bbs_post_id'])
                    ->find();
                if(!empty($post_info)){
                    $add_time_text = date("m-d", strtotime($post_info['update_time']));
                    if (empty($vd['imgs'])) {
                        $data1 = [
                            'member' => $my_info,
                            'imgs' => array(),
                            'id' => $post_info['id'],
                            'content' => $post_info['content'],
                            'title' => $post_info['title'],
                            'thumb_up_count' => $post_info['thumb_up_count'],
                            'message_count' => $post_info['message_count'],
                            'view_count' => $post_info['view_count'],
                            'add_time_txt' => $add_time_text,
                            'member_id' => $post_info['bbs_member_id'],
                        ];
                    } else {
                        $imgs = unserialize($this->mb_unserialize($vd["imgs"]));
                        foreach ($imgs as $ki => $vi) {
                            $imgs[$ki] = "https://" . $_SERVER['HTTP_HOST'] . "/postimg/" . $vi;
                        }
                        $data1 = array(
                            'member' => $my_info,
                            'imgs' => $imgs,
                            'id' => $post_info['id'],
                            'title' => $post_info['title'],
                            'content' => $post_info['content'],
                            'thumb_up_count' => $post_info['thumb_up_count'],
                            'message_count' => $post_info['message_count'],
                            'view_count' => $post_info['view_count'],
                            'add_time_txt' => $add_time_text,
                            'member_id' => $post_info['bbs_member_id'],
                        );
                    }
                }else{
                    $data1 = [];
                }
                $list[$kl]['post_info'] = $data1;
            }

            $total_page = ceil($count / $page_size);
            $page_info = [
                'cur_page' => $page,
                'page_size' => $page_size,
                'total_items' => $count,
                'total_pages' => $total_page
            ];
            if ($total_page > $page) {
                $has_more = 1;
            } else {
                $has_more = 0;
            }
        }else{
            $list = [];
            $page_info = null;
            $has_more = 0;
        }
        $res = [
            'data' => $list,
            'page_info' => $page_info,
            'has_more' => $has_more,
        ];
        $this->success('success', $res);


    }


    //type=1 热门 2 ;最新 3:关注
    public function getForumPostList()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $type = $data['type'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10;
        $forum_id = $data['forum_id'];
        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];

        //获取帖子列表
        if ($type == 2) {
            $count = Db::table('bbs_post')
                ->where('status','=',1)
                ->where('bbs_forum_id','=',$forum_id)
                ->count();
            if ($count > 0) {
                $data = Db::table('bbs_post')
                    ->alias('p')
                    ->join('bbs_member m', 'p.bbs_member_id = m.id')
                    ->field('p.*,m.nick_name,m.avatar_url,m.id as member_id')
                    ->where('p.status','=',1)
                    ->where('p.bbs_forum_id','=',$forum_id)
                    ->page($page, $page_size)
                    ->order('p.id desc')
                    ->select();
                $data1 = [];
                foreach ($data as $kd => $vd) {
                    $member_info = [
                        'nick_name' => $vd['nick_name'],
                        'avatar_url' => $vd['avatar_url'],
                        'id' => $vd['member_id'],
                    ];

                    $member_nice = $this->isMemberType($my_info['id'], $vd['id'], 1);
                    /* $member_nice_info = Db::table('bbs_post')
                         ->where()*/
                    $add_time_text = date("m-d", strtotime($vd['update_time']));
                    if (empty($vd['imgs'])) {
                        $data1[] = [
                            'member' => $member_info,
                            'imgs' => array(),
                            'id' => $vd['id'],
                            'content' => $vd['content'],
                            'title' => $vd['title'],
                            'thumb_up_count' => $vd['thumb_up_count'],
                            'message_count' => $vd['message_count'],
                            'reply_count' => $vd['message_count'],
                            'view_count' => $vd['view_count'],
                            'member_nice' => $member_nice,
                            'add_time_txt' => $add_time_text,
                            'member_id' => $vd['member_id'],
                        ];
                    } else {
                        $imgs = unserialize($this->mb_unserialize($vd["imgs"]));
                        foreach ($imgs as $ki => $vi) {
                            $imgs[$ki] = "https://" . $_SERVER['HTTP_HOST'] . "/postimg/" . $vi;
                        }
                        $data1[] = array(
                            'member' => $member_info,
                            'imgs' => $imgs,
                            'id' => $vd['id'],
                            'title' => $vd['title'],
                            'content' => $vd['content'],
                            'thumb_up_count' => $vd['thumb_up_count'],
                            'message_count' => $vd['message_count'],
                            'reply_count' => $vd['message_count'],
                            'member_nice' => $member_nice,
                            'view_count' => $vd['view_count'],
                            'add_time_txt' => $add_time_text,
                            'member_id' => $vd['member_id'],
                        );
                    }

                }
                $total_page = ceil($count / $page_size);
                $page_info = [
                    'cur_page' => $page,
                    'page_size' => $page_size,
                    'total_items' => $count,
                    'total_pages' => $total_page
                ];
                if ($total_page > $page) {
                    $has_more = 1;
                } else {
                    $has_more = 0;
                }
            } else {
                $data1 = null;
                $page_info = null;
                $has_more = 0;
            }
        }

        $res = [
            'data' => $data1,
            'page_info' => $page_info,
            'has_more' => $has_more,
        ];


        $this->success('success', $res);
    }







        //删除帖子
    public function delPost()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
     /*   $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];*/
        $id = intval($data['id']);
        $rs = Db::table('bbs_post')->where('id','=',$id)->delete();
        if($rs){
            $this->success("success");
        }else{
            $this->error("error");
        }
/*        $rs = D('Post')->delPost(array('id' => $id));
        if ($rs) {

            $where['customer_id'] = $this->member['customer_id'];
            $where['add_time'] = array('EGT', strtotime(date('Y-m-d')));
            $where['isvalid'] = 0;
            $where['member_id'] = $this->member['id'];
            $count = M('post')->where($where)->count();

            D('PointSet')->updateMemberPoint($this->member['customer_id'], $this->member['bbs_id'], $this->member['id'], 'del_post', $count);
            $this->echoSuccess();
        } else {
            $this->echoError('删除失败!');
        }*/
    }

    //举报帖子
    public function complain()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $id = intval($data['post_id']);
        $member_for = $data['member_for'];
        $post_id = $data['post_id'];

        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];
        $arr = [
            'bbs_member_id' => $my_info['id'],
            'bbs_post_id' => $post_id,
            'member_for' => $member_for,
            'create_at' => date("Y-m-d H:i:s",time()),
        ];
        //查询用户是否有举报记录
        $check_complain = Db::table('bbs_complain')
                        ->where('bbs_member_id',"=",$my_info['id'])
                        ->where('bbs_post_id',"=",$post_id)
                        ->find();
        if(empty($check_complain)){
            Db::table('bbs_complain')->insert($arr);
        }
        $this->success("success");
    }







    public function testun(){
        $un = 'a:1:{i:0;s:6:"1.jpeg";}';
        var_dump(unserialize($un));
        var_dump($un);
        exit;
    }




}
