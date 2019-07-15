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


/**
 * 示例接口
 */
class Message extends Api
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


    // 我的动态,评论,点赞,私信,我的关注
    public function memberMessageCount()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $user = $this->getGUserInfo($sess_key,2);
        $my_info = $user['member_info'];

        $member_id = $my_info['id'];
        //我的评论
        $reply_count = Db::table('bbs_reply')
                        ->where('member_for','=',$member_id)
                        ->where('is_read','=',0)
                        ->where('status','=',2)
                        ->count();
     //   $reply_count = D('Reply')->getReplyCount(array('bbs_id' => $this->member['bbs_id'], 'member_for' => $member_id, 'is_read' => 0, 'post_id' => array('NEQ', 0)));

        $nice_count = Db::table('bbs_post_records')
            ->where('member_for','=',$member_id)
            ->where('is_read','=',0)
            ->where('type','=',1)
            ->where('isvalid','=',1)
            ->count();
        $letter_count = Db::table('bbs_letter')
            ->where('to_member_id','=',$member_id)
            ->where('is_read','=',0)
            ->where('isvalid','=',1)
            ->count();

      //  $nice_count = D('PostRecords')->getPost_RecordsCount(array('bbs_id' => $this->member['bbs_id'], 'type' => 1, 'member_for' => $member_id, 'is_read' => 0, 'isvalid' => 1));
      //  $letter_count = D('Letter')->getLetterCount(array('bbs_id' => $this->member['bbs_id'], 'to_member_id' => $member_id, 'is_read' => 0));

        // 我关注用户发的帖子
        /*$AttentionModel = D('Attention');
        $member_for = $AttentionModel->getAttentionList(array('bbs_id' => $this->member['bbs_id'], 'member_id' => $member_id), 'member_for');
        $member_for = arr2arr1($member_for);
        $follow_post_count = D("Post")->getPostCount(array('bbs_id' => $this->member['bbs_id'], 'member_id' => array('in', $member_for)));*/

        $follow_post_count = 0;   //本项目不用
        //$reply_count = $nice_count = $letter_count = $follow_post_count = 1;
        $total_count = $reply_count + $nice_count + $letter_count + $follow_post_count;


        $data = [
            'reply_count' => intval($reply_count),
            'nice_count' => intval($nice_count),
            'letter_count' => intval($letter_count),
            'follow_post_count' => intval($follow_post_count),
            'total_count' => $total_count
        ];
        $this->success('success', $data);
    }











    public function uploadPic(){
        $path = "postimg"  . '/' . date('Y/');
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $upload = new UploadFile(
            array(
                'maxSize' => 2 * 1024 * 1024,
                'autoSub' => true,
                'allowExts' => array('jpg', 'gif', 'png', 'jpeg'),
                'savePath' =>  "./" . $path,
            )
        );
        // 上传文件
        $info = $upload->upload();
        if (!$info) {// 上传错误提示错误信息
            $this->echoError($upload->getErrorMsg());
            // exit(json_encode(array('code' => 0, 'msg' => $upload->getErrorMsg())));s
        } else {// 上传成功
            $info = $upload->getUploadFileInfo();
            $imgPath = "https://".$_SERVER['HTTP_HOST']."/".$path . $info[0]['savename'];
            $imgName =  date('Y/') . $info[0]['savename'];
            exit(json_encode(array('code' => 1, 'msg' => '上传成功！', 'imgPath' => $imgPath, 'imgName' => $imgName)));
        }
    }




}
