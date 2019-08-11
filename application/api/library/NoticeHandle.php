<?php

namespace app\api\library;

use think\Db;
use think\exception\Handle;
use fast\Wx;
use think\cache\driver\Redis;

/**
 * 消息通知
 */
class NoticeHandle
{

    public function __construct()
    {
        $this->redis = new Redis();
    }

    //生成notice通知
    public function createNotice($type,$from_user_id,$from_user_type,$to_user_id,$content,$is_read,$brief_content=''){
        $arr_insert = [
            'type'=>$type,
            'to_user_id'=>$to_user_id,
            'from_user_id'=>$from_user_id,
            'from_user_type'=>$from_user_type,
            'content'=>$content,
            'brief_content'=>$brief_content,
            'is_read'=>$is_read,
            'create_at'=>date("Y-m-d H:i:s",time()),
            'update_at'=>date("Y-m-d H:i:s",time()),
        ];
        $result = Db::table('re_notice')->insertGetId($arr_insert);
        Db::table('re_notice')->removeOption();
        return $result;
    }

    //生成模版消息接口
    public function sendModelMsg($user_info,$data,$emphasis_keyword,$template_type,$page=''){
        $arr = [
            'app_id'=>config("wxpay.APPID"),
            'app_secret'=>config("wxpay.APPSECRET"),
        ];
        $wx = new Wx($arr);
        $openid = $user_info['openid_re'];
        $form_id = $this->redis->spop('recruitFormIdCollection_'.$user_info['id']);

     // $form_id = 'c9872d85269ba51214f7a853d2af0add';
      //  error_log(var_export($form_id),3,$_SERVER['DOCUMENT_ROOT'].'/tt.txt');
        if(!empty($form_id)){
            $openid =$user_info['openid_re'];
            $template_id =config("wxTemplate.".$template_type);
            $page = empty($page) ? "pages/index/index" : $page;
            $emphasis_keyword = $emphasis_keyword;
            $wx->sendTemplateMessage($openid,$template_id,$page,$form_id,$data,$emphasis_keyword);
        }else{

        }

    }
}
