<?php

namespace fast;
use fast\Http;
use think\cache\driver\Redis;

/**
 * 微信公众方法类
 */
class Wx
{
    public $app_id;
    public $app_secret;
    /* public $app_code;
     public $type; */

    public function __construct($arr=array())
    {
        $this->app_id = $arr['app_id'];
        $this->app_secret = $arr['app_secret'];

      /*  $this->app_id = 'wxdff1a01c3575172c';
        $this->app_secret = '344c915e0f0c24bdd64b7e066996fe14';*/
        /* $this->app_id = 'wxdff1a01c3575172c';
         $this->app_secret = '344c915e0f0c24bdd64b7e066996fe14';*/
         /*$this->app_id = 'wx75055d972d69a26f';
         $this->app_secret = '595d9de5f3759265acc318fffaef1235';*/
    }

    public function getAccessToken(){
        $get_token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->app_id}&secret={$this->app_secret}";
        $http = new Http();
        $result_json = $http->get($get_token_url);
        $result = json_decode($result_json,true);
        if (!empty($result['access_token'])){
            return $result['access_token'];
        }else{
            return null;
        }
    }
    //发送模版消息
    public function sendTemplateMessage($openid,$template_id,$page,$form_id,$data,$emphasis_keyword){
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$token;
        $input_arr = [
            'touser'=>$openid,
            'template_id'=>$template_id,
            "page"=> $page,
            "form_id"=> $form_id,
            'data'=>$data,
            //"emphasis_keyword"=>$emphasis_keyword,
            "emphasis_keyword"=>"keyword1.DATA",
        ];
/*     echo "<pre>";
        var_dump($url);
        var_dump($input_arr);*/
        //exit;
        $http = new Http();
        $result_json = $http->sendRequest($url,json_encode($input_arr), 'POST');
     /*   var_dump($result_json);*/
      //  error_log(var_export($result_json,1),3,$_SERVER['DOCUMENT_ROOT']."/tt.txt");
     /*   var_dump($result_json);exit;*/
    }

    //发送模版消息
    public function sendTemplateMessageTest(){
        $token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=".$token;
        $input_arr = [
            //    'touser'=>'oj4J35EDIRdaJ5DQ1Eme1MyTYSGU',
            'touser'=>'oSfj05SWJc4S6UMI9eh3KD9lTfsc',
            'template_id'=>'ldyIRlmlXZ8sQHuh8mU6JwLfysMCIlcEqD4xqJHvjJI',
            "page"=> "index",
            "form_id"=> "0171eeef084a617fd24420a9ffc3744a",
            'DATA'=>[
                'keyword1'=>['value'=>'公司1'],
                'keyword2'=>['value'=>'销售经理'],
                'keyword3'=>['value'=>'2018-10-08'],
                'keyword4'=>['value'=>'西溪湿地'],
                'keyword5'=>['value'=>'携带身份证,学位证,毕业证'],
                'keyword6'=>['value'=>'沈阳'],
            ],
            "emphasis_keyword"=>"keyword1.DATA",
        ];
        echo "<pre>";
        var_dump($url);
        var_dump(json_encode($input_arr));
        $http = new Http();
        $result_json = $http->sendRequest($url,json_encode($input_arr), 'POST');
        var_dump($result_json);exit;
    }






    public function curl_get1($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $data;
    }
    //获得二维码
    public function get_qrcode($access_token) {
        //header('content-type:image/gif');
        //  header('content-type:application/json');
        //header('content-type:image/png');//格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        $data = array();
        $data['scene'] = 'uid=1' ;
        // $data['path'] = "pages/index/index";
        $data['page'] = 'pages/index/index';
        $data['width'] = "430";
        $data['auto_color'] = true;
        $data['line_color'] = '{"r":"777","g":"777","b":"777"}';
        $data['is_hyaline'] = false;

        //$data = json_encode($data);
        //    var_dump($data);exit;
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
        // $url = "https://api.weixin.qq.com/wxa/createwxaqrcode?access_token={$access_token}";
        $da = $this->get_http_array($url,$data);
        var_dump($da);exit;
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }


    public function get_http_array1($url,$post_data) {

        $ch = curl_init();
        $header = array(
            //'content-type:html/text',
            //'content-type:application/json',
            // 'Content-Type:image/png',
        );
        curl_setopt($ch,CURLOPT_HTTPHEADER,$header);  //设置头信息
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, \GuzzleHttp\json_encode($post_data));
        $output = curl_exec($ch);
        curl_close($ch);
        $out = json_decode($output);
        return $out;
    }

    public function getWxaCodeUnlimit($access_token,$path,$width='430',$auto_color=true,$line_color='{"r":"0","g":"0","b":"0"}',$is_hyaline=false){
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";
        //  $url = "https://api.weixin.qq.com/wxa/createwxaqrcode?access_token=".$access_token;
        $data = [
            'page'=>$path,
            'width'=>$width,
            'auto_color'=>$auto_color,
            'line_color'=>'',
            'is_hyaline'=>$is_hyaline,
            'scene'=>'test=1',
        ];

        $http = new Http();


        $postUrl = $url;
        $curlPost = json_encode($data);
        $data1 = $this->get_http_array($postUrl,$curlPost);
        var_dump($data1);exit;

    }


















    function api_notice_increment($url, $data){
        $ch = curl_init();
        //  $header = "Accept-Charset: utf-8";
        $header = array(
            'Accept-Charset: utf-8',
        );
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        //     var_dump($tmpInfo);
        //    exit;
        if (curl_errno($ch)) {
            return false;
        }else{
            // var_dump($tmpInfo);
            return $tmpInfo;
        }
    }


    public function getTest(){
        $path="pages/index?query=1";
        $width=430;
        //   $post_data='{"path":"'.$path.'","width":'.$width.'}';

        $scene = 'uid=1';
        $autu_color = true;
        $line_color =  '{"r":"777","g":"777","b":"777"}';
        $is_hyaline  = false;
        $page="pages/index/index";
        $width=430;
        // $post_data=' {"page":"'.$page.'","width":'.$width.'","scene":'.$scene.'","autu_color":'.$autu_color.'","line_color":'.$line_color.'","is_hyaline":false }';
        $post_data = '{"page":"pages/index/index","width":430,"scene":"uid=1","autu_color":1,"line_color":false,"is_hyaline":false }';

        /*$data['scene'] = 'uid=1' ;
        $data['page'] = 'pages/index/index';
        $data['width'] = "430";
        $data['auto_color'] = true;
        $data['line_color'] = '{"r":"777","g":"777","b":"777"}';
        $data['is_hyaline'] = false;

        $post_data = json_encode($data);*/


        //  var_dump($post_data);exit;


        $access_token = $this->getAccessToken();
        $url="https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$access_token}";

        $result=$this->api_notice_increment($url,$post_data);
        $this->saveImg($result);
        var_dump($result);
        exit;
    }


    public function saveImg($result){
        file_put_contents("/data/wwwroot/mini3.pinecc.cn/public/sharepic/test1.png",$result);


    }















    public function curl_get($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        return $data;
    }
    //获得二维码
    public function get_qrcode_unlimit($uid,$page) {
        // header('content-type:image/gif');
        //  header('content-type:image/png');//格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        //    header('content-type:application/octet-stream');
        //    header('content-type:application/json');
        $data = array();
        $data['scene'] = "uid=" . $uid;
        $data['page'] = $page;
        $data['width'] = 520;
        //    error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/tt.txt");
        $data = json_encode($data);
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $da = $this->get_http_array($url,$data);
        $filename = "person_".$uid.".png";
        file_put_contents("/data/wwwroot/".$_SERVER['HTTP_HOST']."/public/sharepic/".$filename,$da);
        return $filename;
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }

    //获得二维码
    public function get_work_qrcode_unlimit($uid,$page,$work_id) {
        // header('content-type:image/gif');
        //  header('content-type:image/png');//格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        //    header('content-type:application/octet-stream');
        //    header('content-type:application/json');
        $ukey = base64_encode($uid);
        $data = array();
        //   $data['scene'] = "uid=" . $uid."&work_id=".$work_id;
        $data['scene'] = "ukey=" .$ukey."&work_id=".$work_id;
        $data['page'] = $page;
        $data['width'] = 270;
      //  error_log(var_export($data,1),3,"/data/wwwroot/recruit.czucw.com/tt.txt");
        $data = json_encode($data);

        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $da = $this->get_http_array($url,$data);
        $filename = "work_".$work_id."_person_".$uid.".png";
        file_put_contents("/data/wwwroot/".$_SERVER['HTTP_HOST']."/public/sharepic_work/".$filename,$da);
        return $filename;
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }


    //获得二维码
    public function get_comp2user_qrcode_unlimit($re_company_id,$uid,$page) {
        // header('content-type:image/gif');
        //  header('content-type:image/png');//格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        //    header('content-type:application/octet-stream');
        //    header('content-type:application/json');

        $ukey = base64_encode($uid);
        $data = array();
        //   $data['scene'] = "uid=" . $uid."&work_id=".$work_id;
        $data['scene'] = "comp_id=".$re_company_id."=ukey=" .$ukey;
        $data['page'] = $page;
        $data['width'] = 270;
        //  error_log(var_export($data,1),3,"/data/wwwroot/recruit.czucw.com/tt.txt");
        $data = json_encode($data);

        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $da = $this->get_http_array($url,$data);
        $filename = "comp_".$re_company_id."_user_".$uid.".png";
        file_put_contents("/data/wwwroot/".$_SERVER['HTTP_HOST']."/public/sharepic/".$filename,$da);
        return $filename;
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }

    //获得二维码
    public function get_work_qrcode_unlimit_train($uid,$page,$train_id) {
        // header('content-type:image/gif');
        //  header('content-type:image/png');//格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        //    header('content-type:application/octet-stream');
        //    header('content-type:application/json');
        $ukey = base64_encode($uid);
        $data = array();
        //   $data['scene'] = "uid=" . $uid."&work_id=".$work_id;
        $data['scene'] = "ukey=" .$ukey."&work_id=".$train_id;
        $data['page'] = $page;
        $data['width'] = 270;
        //  error_log(var_export($data,1),3,"/data/wwwroot/recruit.czucw.com/tt.txt");
        $data = json_encode($data);

        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $da = $this->get_http_array($url,$data);
        $filename = "train_".$train_id."_person_".$uid.".png";
        file_put_contents("/data/wwwroot/".$_SERVER['HTTP_HOST']."/public/sharepic_train/".$filename,$da);
        return $filename;
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }


    public function get_http_array($url,$post_data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);   //没有这个会自动输出，不用print_r();也会在后面多个1
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        $output = curl_exec($ch);
        curl_close($ch);
        //   $out = json_decode($output);
        return $output;
    }


    //获得二维码
    public function get_comp_qrcode_unlimit($comp_id,$page) {
        // header('content-type:image/gif');
        //  header('content-type:image/png');//格式自选，不同格式貌似加载速度略有不同，想加载更快可选择jpg
        //header('content-type:image/jpg');
        //    header('content-type:application/octet-stream');
        //    header('content-type:application/json');
        $data = array();
        $data['scene'] = "comp_id=" . $comp_id;
        $data['page'] = $page;
        $data['width'] = 520;
        //    error_log(var_export($data,1),3,"/data/wwwroot/mini3.pinecc.cn/tt.txt");
        $data = json_encode($data);
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=".$access_token;
        $da = $this->get_http_array($url,$data);

        $filename = "comp_".$comp_id.".png";
        file_put_contents("/data/wwwroot/".$_SERVER['HTTP_HOST']."/public/sharepic/".$filename,$da);
        return $filename;
        //这里强调显示二维码可以直接写该访问路径，同时也可以使用curl保存到本地，详细用法可以加群或者加我扣扣
    }









}
