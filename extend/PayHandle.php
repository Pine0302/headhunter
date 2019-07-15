<?php

/**
 * 个人中心页面展示
 * @author pine 514837643@qq.com
 * @version 6.4.20
 */

class PayHandle extends Controller {
    /**
     * 构造方法
     */
    public $alipay_config;

    public function __construct() {
        parent::__construct();
        $this->alipay_config= $this->config->item('alipay_config');
        $this->load->library("c_sms");
        $this->load->model('User_model', 'member');
        $this->load->model('House_model', 'house');
        $this->load->model('Horder_model', 'horder');
        $this->load->model('Base_model', 'base');
        $this->load->model('Pay_model', 'pay');
        $this->load->library('MyCurl','mycurl');
        $this->load->library('Myfunc','myfunc');
        $this->uid = $_SESSION['uid'];
        if(empty($this->uid)){
            header('Location: http://'.$_SERVER['HTTP_HOST'].'/www/user/login');
        }
    }


    //支付前的验证处理
    public function beforePay(){
        $data = $_REQUEST;
        $param['trade_no'] = base64_decode($data['oid']);
        $param['total_amount'] = base64_decode($data['total']);
        $param['pay_mode'] = $data['mode'];

        //验证订单
        if($param['pay_mode']==1){
            $sql = "select * from horder where order_num = '".$param['trade_no']."' and total = ".$param['total_amount'];
            $result_query = $this->db->query($sql);
            $result_arr = $result_query->result_array();
            if(!empty($result_arr['0'])){
                $param['trade_no'] = $result_arr['0']['order_num'];
                $param['subject'] = '预定房间订单';
                $param['total_amount'] = $result_arr['0']['total'];
                $param['body'] = "普通房源订单";
                header('Location: http://'.$_SERVER['HTTP_HOST'].'/www/pay/pay?WIDout_trade_no='.$param['trade_no']."&WIDsubject=".$param['subject']."&WIDtotal_amount=". $param['total_amount']."&WIDbody=".$param['body']);
            }else{
                //todo  验证订单失败的处理
            }
        }

    }

    //支付后的处理
    public function afterPay(){
        $data = $_GET;
        $from = base64_decode($data['from']);
        $mode = base64_decode($data['mode']);
        if($from=="alipay"){
            switch ($mode)
            {
                case 1:
                    $this->aterNormalRoomReservation($data);    //普通房间预定
                    break;

                default:
                    break;
            }
        }

    }


    //普通房间预定的处理
    public function aterNormalRoomReservation($data){
        $time = time();
        $out_trade_no = base64_decode($data['out_trade_no']);
        //验证钱数对不对
        $order_info = $this->horder->checkHorder(array('order_num'=>$out_trade_no));
        if($order_info['status']!="20"){
            $this->myfunc->alert('订单有误,请联系客服!','http://'.$_SERVER['HTTP_HOST']);
        }else{
            $this->myfunc->alert('订单已提交,点击跳转到我的个人中心!','http://'.$_SERVER['HTTP_HOST']."/www/user/user");
        }
    }


    //从我的订单点击支付过来
    public function order2Pay(){
        $data = $_REQUEST;
        $param['trade_no'] = base64_decode($data['trade_no']);
        $param['pay_mode'] = $data['mode'];
        //验证订单
        if($param['pay_mode']==1){
            $sql = "select * from horder where order_num = '".$param['trade_no']."'";
            $result_query = $this->db->query($sql);
            $result_arr = $result_query->result_array();
            if(!empty($result_arr['0'])){
                $param['trade_no'] = $result_arr['0']['order_num'];
                $param['subject'] = '预定房间订单';
                $param['total_amount'] = $result_arr['0']['total'];
                $param['body'] = "普通房源订单";
                header('Location: http://'.$_SERVER['HTTP_HOST'].'/www/pay/pay?WIDout_trade_no='.$param['trade_no']."&WIDsubject=".$param['subject']."&WIDtotal_amount=". $param['total_amount']."&WIDbody=".$param['body']);
            }else{
                //todo  验证订单失败的处理
            }
        }
    }




}
