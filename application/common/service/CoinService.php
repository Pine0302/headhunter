<?php
/**
 * Created by PhpStorm.
 * User: jiqing
 * Date: 18-12-21
 * Time: 下午8:49
 */

namespace app\common\service;
// 服务层，介于C层与M层之间

/**  根据上面的分析，Service夹在C层和M层中间，从逻辑上大致划分为3大类：
### model侧的Service：也就是封装每个model与业务相关的通用数据接口，比如：查询订单。（我认为：访问远程服务获取数据也应该归属于这一类Service）
### 中间的Service：封装通用的业务逻辑，比如：计算订单折扣（会用到1中的Service）。
### controller侧的Service：基于1、2中的Service进一步封装对外接口的用户业务逻辑。
 **/

use app\common\model\UserModel;
use think\Db;
use fast\Http;
use fast\Wx;

class CoinService extends CommonService
{



    public function checkUserCoin($user_id,$user_type,$coin_num,$type){
        $coin_type_config = config('webset.coin_type');
        $letter = $coin_type_config[$user_type]['name'];
        $action = ($type==1) ? 'setInc':"setDec";
        $result = Db::table('user')
            ->where('id','=',$user_id)
            ->$action($letter,$coin_num);
        Db::table('user')->removeOption();
    }




    //创造检测coin的类
    public function checkCoinLimit($method,$args){
        return $this->$method($args);
    }


    public function checkCoin(){

    }







}