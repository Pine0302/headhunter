<?php

namespace app\common\library;

use app\common\library\Auth;
use think\Config;
use think\Request;
use think\Response;
use think\Cache;
use think\Db;
use fast\Http;
use think\cache\driver\Redis;

/**
 * API控制器基类
 */
class CommonFunc
{
    public function getPlatformRatio($admin_id){
        $ratio = Db::table('re_ratio')->where('uid','=',$admin_id)->find();
        if(empty($ratio)){
            $ratio = Db::table('re_ratio')->where('uid','=',1)->find();
        }
        return $ratio;
    }








}
