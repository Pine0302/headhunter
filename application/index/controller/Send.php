<?php

namespace app\index\controller;

use app\common\controller\Frontend;
use app\common\library\Token;
use think\Db;

/*
 *消息和模版消息推送类
 *
 *
 */
class Send extends Frontend
{

    protected $noNeedLogin = '*';
    protected $noNeedRight = '*';
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();
    }


    /*
     * 推荐奖励满足timeline 发放通知
     * 1.上级--推荐 2.本人-入职 3.公司提醒
     */
    public function noticeRecruitRec()
    {
       $date = date("Y-m-d H:i:s");
       $recommend_list = Db::table('re_recommenddetail')
           ->where('status','=',2)
           ->where('deadline','neq','')
           ->select();
       foreach($recommend_list as $kr=>$vr){
           if(($vr['timeline']>$date)&&($vr['status']==2)){
               $this->sendRecruitNotice($vr['id']);exit;
           }
       }
    }


    /**
     * 推送消息
     */
    public function sendRecruitNotice($ids = NULL)
    {
        $platform_id = 1; //平台id
      //  $row = $this->recommendDetailModel->get($ids);
        $row = Db::table("re_recommenddetail")->where('id','=',$ids)->find();

        $re_recommenddetail_id = $row['id'];
        $re_recommenddetail_info = Db::table('re_recommenddetail')->where('id',$re_recommenddetail_id)->find();
        $re_job_id  =  $re_recommenddetail_info['re_job_id'];
        $job_info = Db::table('re_job')->where('id','=',$re_job_id)->find();
        $job_admin_id = $job_info['admin_id'];

        $re_company_id = $row['re_company_id'];
        $total = $row['total_cash'];

        $comp_info = Db::table('re_company')->where('admin_id','=',$job_admin_id)->find();
        //发奖励之前先从发布该岗位的公司账户里扣除对应金额
        $result_down_money =  $this->downMoney($job_admin_id,$total);



        if ($result_down_money!=2) {


            $params_commend_detail_update = [
                'status' => 1,
                'update_at' => date('Y-m-d H:i:s')
            ];
            $params_company_account_update = $result_down_money;

            //todo  给用户增加金额,给用户增加记录,给推荐人添加金额,给推荐人添加记录,给平台(公司)添加金额/记录,给代理商(公司)添加金额/记录
            //     $re_recommenddetail_info = Db::table('re_recommenddetail')->where('id',$re_recommenddetail_id)->find();
            //公司资金变动记录
            $params_company_cash_log_update = [
                're_company_id' => $comp_info['id'],
                'apply_company_id' => $job_info['re_company_id'],
                'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                'admin_id' => $re_recommenddetail_info['admin_id'],
                'way' => 2,
                'tip' => "入职员工奖励金支付",
                'rec_id' => $re_recommenddetail_info['id'],
                'type' => 6,
                'status' => 1,
                'cash' => $total,
                'update_at' => date("Y-m-d H:i:s",time())
            ];

            //1.给入职用户发奖
            $low_user_info = Db::table('user')->field('id,total_balance,available_balance,rec_cash')->where('id',$re_recommenddetail_info['low_user_id'])->find();
            $arr_update_low_user = [
                'total_balance' => $low_user_info['total_balance'] + $re_recommenddetail_info['lower_cash'],
                'available_balance' => $low_user_info['available_balance'] + $re_recommenddetail_info['lower_cash'],
                'rec_cash' => $low_user_info['rec_cash'] + $re_recommenddetail_info['lower_cash'],
            ];
            //a.给入职用户添加奖金记录
            $arr_update_low_user_cash_log = [
                'user_id' => $re_recommenddetail_info['low_user_id'],
                'apply_company_id' => $job_info['re_company_id'],
                'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                'admin_id' => $re_recommenddetail_info['admin_id'],
                'way' => 1,
                'tip' => "入职奖励",
                'rec_id' => $re_recommenddetail_info['id'],
                'type' => 1,
                'status' => 1,
                'cash' => $re_recommenddetail_info['lower_cash'],
                'update_at' => date("Y-m-d H:i:s",time())
            ];

            //2.给上级人发奖()
            if(!empty($re_recommenddetail_info['up_user_id'])){
                $up_user_info = Db::table('user')->field('id,total_balance,available_balance,rec_cash')->where('id',$re_recommenddetail_info['up_user_id'])->find();
                $arr_update_up_user = [
                    'total_balance' => $up_user_info['total_balance'] + $re_recommenddetail_info['up_cash'],
                    'available_balance' => $up_user_info['available_balance'] + $re_recommenddetail_info['up_cash'],
                    'rec_cash' => $up_user_info['rec_cash'] + $re_recommenddetail_info['up_cash'],
                ];
                //a.给上级用户添加奖金记录
                $arr_update_up_user_cash_log = [
                    'user_id' => $re_recommenddetail_info['up_user_id'],
                    'apply_company_id' => $job_info['re_company_id'],
                    'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                    'admin_id' => $re_recommenddetail_info['admin_id'],
                    'way' => 1,
                    'tip' => "会员推荐奖励",
                    'rec_id' => $re_recommenddetail_info['id'],
                    'type' => 2,
                    'status' => 1,
                    'cash' => $re_recommenddetail_info['up_cash'],
                    'update_at' => date("Y-m-d H:i:s",time())
                ];

            }

            //3.给平台(公司)添加金额
            $p_company_info = Db::table('re_company')->field('id,account')->where('id',$platform_id)->find();
            $arr_update_up_p = [
                'account' => $p_company_info['account'] + $re_recommenddetail_info['p_cash'],
            ];
            $arr_update_p_company_cash_log = [
                're_company_id' => $p_company_info['id'],
                'apply_company_id' => $job_info['re_company_id'],
                'apply_user_id' => $re_recommenddetail_info['low_user_id'],
                'admin_id' => $re_recommenddetail_info['admin_id'],
                'way' => 1,
                'tip' => "入职平台分红",
                'rec_id' => $re_recommenddetail_info['id'],
                'type' => 4,
                'status' => 1,
                'cash' => $re_recommenddetail_info['p_cash'],
                'update_at' => date("Y-m-d H:i:s",time())
            ];

            // 启动事务
            Db::startTrans();
            try {
                Db::table('re_company')->where('id', $re_company_id)->update($params_company_account_update);
                Db::table('re_recommenddetail')->where('id', $re_recommenddetail_id)->update($params_commend_detail_update);
                Db::table('cash_log')->insert($params_company_cash_log_update);  //"支付公司";
                //"入职用户";
                Db::table('user')->where('id', $low_user_info['id'])->update($arr_update_low_user);
                Db::table('cash_log')->insert($arr_update_low_user_cash_log);
                //"上级用户";
                if(!empty($re_recommenddetail_info['up_user_id'])){
                    Db::table('user')->where('id', $up_user_info['id'])->update($arr_update_up_user);
                    Db::table('cash_log')->insert($arr_update_up_user_cash_log);
                }

                //平台
                Db::table('re_company')->where('id', $p_company_info['id'])->update($arr_update_up_p);
                Db::table('cash_log')->insert($arr_update_p_company_cash_log);

                /*  if($re_recommenddetail_info['agent_cash']!=0) {
                      //代理商
                      Db::table('re_company')->where('id', $a_company_info['id'])->update($arr_update_up_a);
                      Db::table('cash_log')->insert($arr_update_a_company_cash_log);
                  }*/
                // 提交事务
                Db::commit();
            } catch (\Exception $e) {
                // 回滚事务
                Db::rollback();
                $this->error("网络繁忙,请稍后再试");exit;
            }
            $this->success();
        } else {
            $this->error("余额不足,请先充值");
        }
    }


    public function downMoney($admin_id,$total){
        $company_info = Db::table('re_company')->where('admin_id','=',$admin_id)->find();
        $ori_frozen = $company_info['frozen'];
        $ori_account = $company_info['account'];
        $ori_total = $ori_frozen + $ori_account;
        if ($ori_frozen > $total){
            $arr['frozen'] = $ori_frozen - $total;
            $result =  Db::table('re_company')->where('admin_id','=',$admin_id)->update($arr);
            return $arr;
        }elseif($ori_total>$total){
            $arr['frozen'] = 0;
            $arr['account'] = $ori_total - $total;
            $result =  Db::table('re_company')->where('admin_id','=',$admin_id)->update($arr);
            return $arr;
        }else{
            return 2;
        }

    }




    public function news()
    {

        $newslist = [];
        return jsonp(['newslist' => $newslist, 'new' => count($newslist), 'url' => 'https://www.fastadmin.net?ref=news']);
    }



}
