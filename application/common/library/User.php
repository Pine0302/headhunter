<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/21 0021
 * Time: 上午 10:44
 */
namespace app\common\library;
use think\Db;
class User
{

    /**
     * @param $user_id
     * @return array
     */
    public function getUserTypeCoin($user_id)
    {
        $user_info = Db::table('user')->where('id','=',$user_id)->find();
        $id_type = 4;
        $coin = 0;
        if($user_info['is_engineer']==1){
            $id_type = 1;
            $coin = $user_info['coin'];
        }elseif($user_info['is_hr']==1){
            $id_type = 2;
            $coin = $user_info['hr_coin'];
        }elseif($user_info['is_agent']==1){
            $id_type = 3;
            $coin = $user_info['agent_coin'];
        }
        $data = [
            'id_type'=>$id_type,
            'coin'=>$coin,
        ];
        return $data;
    }


}