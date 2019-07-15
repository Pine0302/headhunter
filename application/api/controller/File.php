<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\controller\CommonFunc;
use app\common\library\wx\WXBizDataCrypt;
use fast\Http;
use think\cache\driver\Redis;
use think\Db;
use think\Request;
use think\Session;
use think\Cache;

/**
 * 文件相关接口
 */
class File extends Api
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

  //  protected $config_area = [];

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
   //     $this->config_area = config('area');

    }

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

    //图片base64encode 上传
    public function uploadImagesBase64(){
        $input = file_get_contents("php://input");
        $data_input = json_decode($input ,true);
        $data = $data_input['image'];
        $data_arr = explode(",",$data);
        $type_info = $data_arr[0];
        $data_info = $data_arr[1];
        $data_decode= base64_decode($data_info);//对截取后的字符使用base64_decode进行解码
        $type_arr = explode(":",$type_info);
        $type_arr2 = explode("/",$type_arr[1]);
        $type_arr4 = explode(";",$type_arr2[1]);
        $ext = $type_arr4[0];
        $filename = '/upload/'.uniqid().'.'.$ext;
        $filepath = $_SERVER['DOCUMENT_ROOT'].$filename;
        file_put_contents($filepath, $data_decode);
        ob_clean();
        $url = 'https://'.config('webset.server_name').$filename;
        $image_info = ['image_url'=>$url];
        $response_data = [
            'data'=>$image_info,
        ];
        $this->success('success',$response_data);
    }

    //todo 图片文件上传(音频,图片,文档)
    public function uploadFiles(){
      /*  $data = $_FILES['file'];
        $data_arr = explode(",",$data);
        $type_info = $data_arr[0];
        $data_info = $data_arr[1];
        $data_decode= base64_decode($data_info);//对截取后的字符使用base64_decode进行解码
        $type_arr = explode(":",$type_info);
        $type_arr2 = explode("/",$type_arr[1]);
        $type_arr4 = explode(";",$type_arr2[1]);
        $ext = $type_arr4[0];
        $filename = '/upload/'.uniqid().'.'.$ext;
        $filepath = $_SERVER['DOCUMENT_ROOT'].$filename;
        file_put_contents($filepath, $data_decode);
        ob_clean();
        $url = 'https://'.config('webset.server_name').$filename;
        $image_info = ['image_url'=>$url];
        $response_data = [
            'data'=>$image_info,
        ];
        $this->success('success',$response_data);*/
    }





}
