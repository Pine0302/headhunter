<?php
namespace app\common\controller;
use app\common\library\Auth;
use think\Config;
use think\Controller;
use think\Hook;
use think\Lang;
use think\Db;



class Crond extends Controller
{

    protected $auth = null;

    public function __construct()
    {

    }

    public function test(){
        var_dump(123);
    }

}
