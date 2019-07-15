<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use app\common\controller\Backend;
use think\Config;
use think\Hook;
use think\Validate;

/**
 * 后台首页
 * @internal
 */
class Common extends Backend
{

    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['index', 'logout','getWebConfig'];
    protected $layout = '';

    public function _initialize()
    {

        parent::_initialize();
    }

    /**
     * 查看工作经验配置
     */
    public function getWebConfig()
    {
        $name = $_GET['name'];
        $key_value = $_POST['keyValue'] ?? 0;
        $webset = config('webset');

        if(!empty($key_value)){
            $arr = $this->switchConfig2List($webset[$name]);
            $list = [];
            $list[] = $arr[$key_value];
            $total = 1;
        }else{
            $arr = $this->switchConfig2List1($webset[$name]);
            $list = $arr;
            $total = count($list);
        }
        $result = array("list" => $list,"total" => $total);
        ob_clean();
        return json($result);
    }



    public function switchConfig2List($arr){
        $response_data = [];
        foreach($arr as $kp=>$vp){
            $response_data[$kp] = [
                'id'=>$kp,
                'name'=>$vp,
            ];
        }
        return $response_data;
    }

    public function switchConfig2List1($arr){
        $response_data = [];
        foreach($arr as $kp=>$vp){
            $response_data[] = [
                'id'=>$kp,
                'name'=>$vp,
            ];
        }
        return $response_data;
    }


}
