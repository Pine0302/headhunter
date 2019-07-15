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
class Letter extends Api
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
    public function latelyLetterMember()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        $user = $this->getGUserInfo($sess_key,2);
        $my_info = $user['member_info'];
        $member_id = $my_info['id'];

        $members = Db::table('bbs_letter')
            ->where('member_id','=',$member_id)
            ->whereOr('to_member_id','=',$member_id)
            ->select();
        $result = array();
        $DaterObj = new Dater();
        foreach ($members as $key => $item) {
            // 对方是接收人
            if ($item['to_member_id'] != $member_id && !(isset($result[$item['to_member_id']]))) {
                $value['add_time1'] = $item['add_time'];
             //   $value['add_time'] = format_date($item['add_time']);
                $value['add_time'] = $DaterObj->socialDateDisplay($item['add_time']);
                // 未读消息数量
              //  $count = $this->getLetterCount(array('member_id' => $item['to_member_id'], 'to_member_id' => $member_id, 'is_read' => 0));
                $count = Db::table('bbs_letter')
                    ->where('member_id','=',$item['to_member_id'])
                    ->where('to_member_id','=',$member_id)
                    ->where('is_read','=',0)
                    ->count();
                $value['unread_count'] = $count;
             //   $value['member'] = D('Member')->getMemberInfo(array('id' => $item['to_member_id']), $this->member_field);
                $value['member'] = Db::table('bbs_member')->where('id','=',$item['to_member_id'])->find();
                $result[$item['to_member_id']] = $value;
            }
            // 对方是发送人
            if ($item['member_id'] != $member_id && !(isset($result[$item['member_id']]))) {
                $value['add_time1'] = $item['add_time'];
                $value['add_time'] = $DaterObj->socialDateDisplay($item['add_time']);
                // 未读消息数量
            //    $count = $this->getLetterCount(array('member_id' => $member_id, 'to_member_id' => $item['member_id'], 'is_read' => 0));
                $count = Db::table('bbs_letter')
                    ->where('to_member_id','=',$item['to_member_id'])
                    ->where('member_id','=',$member_id)
                    ->where('is_read','=',0)
                    ->count();
                $value['unread_count'] = $count;
          //      $value['member'] = D('Member')->getMemberInfo(array('id' => $item['member_id']), $this->member_field);
                $value['member'] = Db::table('bbs_member')->where('id','=',$item['member_id'])->find();
                $result[$item['member_id']] = $value;
            }
        }
        $result = array_values($result);
        array_multisort(array_column($result, 'add_time1'), SORT_DESC, $result);
        $data = [
            'data'=>$result,
            'has_more'=>false,
            'page_total'=>0,
        ];
     //   $data = !empty($result) ?? [];
        $this->success('success', $data);
    }


    // 和他人的聊天记录
    public function letterList()
    {
        $data = $this->request->post();
        $sess_key = $data['sess_key'];
    //    $type = $data['type'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 100;
        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];
        $my_id = $user['member_info']['id'];
        $to_member = intval($data['to_member']);
        if (!$to_member) {
            $this->error("参数错误！");
        }


        $list = Db::table('bbs_letter')
            ->alias('l')
            ->join('bbs_member m','m.id = l.member_id')
            ->join('bbs_member tm','tm.id = l.to_member_id')
            ->field('l.*,m.id as member_id,m.avatar_url as member_avatar_url,m.nick_name as member_nick_name,tm.id as to_member_id,tm.avatar_url as to_member_avatar_url,tm.nick_name as to_member_nick_name')
            ->where(function ($query) use($my_id,$to_member){ $query->where('l.member_id','=',$my_id)->where('l.to_member_id','=',$to_member);})
            ->whereOr(function ($query) use($my_id,$to_member){ $query->where('l.to_member_id','=',$my_id)->where('l.member_id','=',$to_member);})
            ->order("l.id asc")
            ->page($page,$page_size)
            ->select();

        $count = Db::table('bbs_letter')
            ->where(function ($query) use($my_id,$to_member){ $query->where('member_id','=',$my_id)->where('to_member_id','=',$to_member);})
            ->whereOr(function ($query) use($my_id,$to_member){ $query->where('to_member_id','=',$my_id)->where('member_id','=',$to_member);})
            ->count();
        $last_time = 0;
        $timer = 10 * 60;
        foreach($list as $kl=>$vl){

            if ($vl['add_time'] - $last_time > $timer) {
                $last_time = $vl['add_time'];
                $list[$kl]['add_time'] = date('Y年m月d日', $vl['add_time']) . '——' . (date('A', $vl['add_time']) == 'AM' ? '上午' : '下午') . date('H:i', $vl['add_time']);
            } else {
                $list[$kl]['add_time'] = null;
            }
            $list[$kl]['member'] = [
                'id'=>$vl['member_id'],
                'avatar_url'=>$vl['member_avatar_url'],
                'nick_name'=>$vl['member_nick_name'],
            ];
            $list[$kl]['to_member'] = [
                'id'=>$vl['to_member_id'],
                'avatar_url'=>$vl['to_member_avatar_url'],
                'nick_name'=>$vl['to_member_nick_name'],
            ];
            $list[$kl]['is_self'] = $vl['member_id'] == $my_id;

            if ($vl['is_img']) {
                // $item['content'] = 'https://'.$_SERVER['HTTP_HOST'].'/' . $item['content'];
                $list[$kl]['content'] = strpos($vl['content'],"resources")===0 ?($_SERVER['HTTP_HOST'].'/'. $vl['content']):($_SERVER['HTTP_HOST'].'/postimg/'.$vl['content']);
            }
        }

        //把该用户和对应用户聊天记录设置为已读
        Db::table('bbs_letter')
            ->where('member_id','=',$to_member)
            ->where('to_member_id','=',$my_id)
            ->update(['is_read'=>1]);

        $page_total = ceil($count / $page_size);
        $has_more = ($page < $page_total) ? 1 : 0;
        $response = [
            'data' => $list,
            'has_more' => $has_more,
            'page_total' => $page_total,
        ];
        $this->success("success", $response);


     /*   $list = D('Letter')->getLetterList($this->member['id'], $to_member, $this->member['bbs_id'], $limit);
        $count = D('Letter')->getLetterCountForMember($this->member['id'], $to_member, $this->member['bbs_id']);
        $page_count = ($count % $this->pageSize == 0) ? ($count / $this->pageSize) : intval($count / $this->pageSize) + 1;

        // 更新此用户消息状态为已读
        D('Letter')->updateLetter(array('member_id' => $to_member, 'to_member_id' => $this->member['id']), array('is_read' => 1));
        $this->echoSuccess($list, $page_count);*/
    }

    // 发送私信
    public function sendLetterToMember()
    {

        $data = $this->request->post();
        $sess_key = $data['sess_key'];
        //    $type = $data['type'];
        $page = $data['page'] ?? 1;
        $page_size = $data['page_size'] ?? 10;
        $user = $this->getGUserInfo($sess_key, 2);
        $user_info = $user['user_info'];
        $my_info = $user['member_info'];

        $member_id = $user['member_info']['id'];
        $to_member_id  = intval($data['to_member_id']);
        $content = trim($data['content']);

        $arr = [
            'member_id' => $member_id,
            'to_member_id' => $to_member_id,
            'content' => $content,
            'is_read' => 0,
            'is_img' => intval($data['is_img']),
            'createtime'=>date('Y-m-d H:i:s',time()),
            'add_time'=>time(),
            'update_time'=>time(),
        ];

        $rs = Db::table('bbs_letter')->insert($arr);

        if ($rs) {
            $this->success("发送成功");
        } else {
            $this->error('发送失败!');
        }
    }







}
