<?php

namespace app\admin\controller\user;

use app\common\controller\Backend;
use think\Db;
use app\pay\controller\Payhandle;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Withdraw extends Backend
{
    
    /**
     * UserWithdraw模型对象
     * @var \app\admin\model\UserWithdraw
     */
    protected $model = null;

    protected $dataLimit = 'auth'; //默认基类中为false，表示不启用，可额外使用auth和personal两个值
    protected $dataLimitField = 'admin_id'; //数据关联字段,当前控制器对应的模型表中必须存在该字段

    protected $relationSearch = true;

    protected $noNeedRight = ['detail','pass','deny','transfer','index'];

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('UserWithdraw');

    }
    
    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */


    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax())
        {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField'))
            {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->with('user,reResume')
                ->order($sort, $order)
                ->count();

            $list = $this->model
                ->where($where)
                ->with('user,reResume')
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            foreach ($list as $kl=>$vl){
                $list[$kl]['status_num'] = $vl['status'];
                $list[$kl]['reResume'] = $list[$kl]['re_resume'];
                switch( $list[$kl]['status_num'])
                {
                    case 0:
                        $list[$kl]['status'] = '已申请';break;
                    case 1:
                        $list[$kl]['status'] = '已通过';break;
                    case 2:
                        $list[$kl]['status'] = '已拒绝';break;
                }
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }




    /**
     * 确认通过提现
     */
    public function pass($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds))
        {
            if (!in_array($row[$this->dataLimitField], $adminIds))
            {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost())
        {
            $params = [ 'status'=>1 ];
            if ($params)
            {
                try
                {
                    //是否采用模型验证
                    if ($this->modelValidate)
                    {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    $user_id = $row->user_id;
                    $cash = $row->cash;
                    $order_id = $row->order_id;
                    //get userinfo
                    $user_info = Db::table('user')->where("id",$user_id)->field('openid_re,loginip')->find();

                    $result_transfer = $this->transfer($order_id,$user_info['openid_re'],$cash,$user_info['loginip']);

                 //   $result_transfer = $this->afterPayP2UTest();
                    if ($result_transfer==1){
                        $this->success();
                    }elseif($result_transfer==3){
                        $this->error("您的微信商户号余额不足,请先充值!");
                    }else{
                        $this->error("网络繁忙,请稍后再试!");
                    }
                }
                catch (\think\exception\PDOException $e)
                {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
    }

    //提现流程
    public function transfer($order_id,$openid,$cash,$ip){
        //支付信息
        $wx_pay_config = config('Wxpay');
        $webdata = array(
            'mch_appid' => $wx_pay_config['APPID'],//商户账号appid
            'mchid'     => $wx_pay_config['MCHID'],//商户号
            'nonce_str' => md5(time()),//随机字符串
            'partner_trade_no'=> $order_id, //商户订单号，需要唯一
            'openid' => $openid,//转账用户的openid
            'check_name'=> 'NO_CHECK', //OPTION_CHECK不强制校验真实姓名, FORCE_CHECK：强制 NO_CHECK：
            'amount' => $cash*100, //付款金额单位为分
            'desc'   => '会员提现',//企业付款描述信息
            'spbill_create_ip' => $ip,//获取IP

        );

        foreach ($webdata as $k => $v) {
            $tarr[] =$k.'='.$v;
        }
        sort($tarr);
        $sign = implode($tarr, '&');
        $sign .= '&key='.$wx_pay_config['KEY'];
        $webdata['sign']=strtoupper(md5($sign));

        $wget = $this->ArrToXml($webdata);//数组转XML

        $pay_url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';//api地址

        $res = $this->postData($pay_url,$wget);//发送数据
        if(!$res){
            //return array('status'=>2, 'msg'=>"Can't connect the server" );
            return 2;
        }
        $content = simplexml_load_string($res, 'SimpleXMLElement', LIBXML_NOCDATA);


        if(strval($content->return_code) == 'FAIL'){
            //return array('status'=>2, 'msg'=>strval($content->return_msg));
            var_dump(array('status'=>2, 'msg'=>strval($content->return_msg)));
            return 2;
        }
        if(strval($content->result_code) == 'FAIL'){
            if (strval($content->err_code) == "NOTENOUGH"){
                return 3;
            }else{
                return 2;
            }
          //  var_dump(array('status'=>2, 'msg'=>strval($content->err_code),':'.strval($content->err_code_des)));

        }
        $rdata = array(
            'mch_appid'        => strval($content->mch_appid),
            'mchid'            => strval($content->mchid),
            'device_info'      => strval($content->device_info),
            'nonce_str'        => strval($content->nonce_str),
            'result_code'      => strval($content->result_code),
            'partner_trade_no' => strval($content->partner_trade_no),
            'payment_no'       => strval($content->payment_no),
            'payment_time'     => strval($content->payment_time),
            'openid'           => $openid,
            'cash'             => $cash,
        );
     //   error_log(json_encode($rdata),3,'/data/wwwroot/mini3.pinecc.cn/runtime/test.txt');
       // error_log(var_export($rdata,1),3,'/data/wwwroot/mini3.pinecc.cn/runtime/test.txt');
        $result = $this->afterPayP2U($rdata);
        return $result; exit;
    }


    //商家付款给个人
    public function afterPayP2U($arr){

        $order_id = $arr['partner_trade_no'];

        $user_info = Db::table('user')
            ->field('id,total_balance,frozen_balance,available_balance,rec_cash')
            ->where('openid_re',$arr['openid'])
            ->find();
        $update_userinfo['frozen_balance'] = $user_info['frozen_balance'] - $arr['cash'];
        $update_userinfo['rec_cash'] = $user_info['rec_cash'] + $arr['cash'];

        $update_user_withdraw = [
            'status' => 1,
            'update_at' => date("Y-m-d H:i:s"),
        ];

        $flag = 1;
        //更新提现记录
        //用户冻结金额减少对应数目,已提现金额增加相应数目
        Db::startTrans();
        try {
            //更新商家账单详情表
            Db::name('user')
                ->where('openid_re', $arr['openid'])
                ->data($update_userinfo)
                ->update();
            //更新商信息
            Db::name('user_withdraw')
                ->where('order_id', $order_id)
                ->data($update_user_withdraw)
                ->update();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $flag = 0;
            Db::rollback();
        }
        if($flag == 1){
            //$result = array('status'=>1, 'msg'=>'通过提现成功');
            $result = 1;
        }else{
            //$result = array('status'=>2, 'msg'=>'提现有误,请联系管理员处理');
            $result = 2;
        }
      //  error_log(var_export($result,1),3,'/data/wwwroot/mini3.pinecc.cn/runtime/test.txt');
        return $result;
    }



        ///商家付款给个人
    public function afterPayP2UTest(){
        $json = '{"mch_appid":"wxdff1a01c3575172c","mchid":"1377192102","device_info":"","nonce_str":"ca3325e5d323f40234c9046f7148a7d0","result_code":"SUCCESS","partner_trade_no":"P2U5B03E41FB0C5D","payment_no":"1000018301201806048399534247","payment_time":"2018-06-04 14:07:57","openid":"oj4J35EDIRdaJ5DQ1Eme1MyTYSGU","cash":"1.00"}';
        $arr = json_decode($json,true);
        $order_id = $arr['partner_trade_no'];

        $user_info = Db::table('user')
            ->field('id,total_balance,frozen_balance,available_balance,rec_cash')
            ->where('openid_re',$arr['openid'])
            ->find();
        $update_userinfo['frozen_balance'] = $user_info['frozen_balance'] - $arr['cash'];
        $update_userinfo['rec_cash'] = $user_info['rec_cash'] + $arr['cash'];

        $update_user_withdraw = [
            'status' => 1,
            'update_at' => date("Y-m-d H:i:s"),
        ];

        $flag = 1;
        //更新提现记录
        //用户冻结金额减少对应数目,已提现金额增加相应数目
        Db::startTrans();
        try {
            //更新商家账单详情表
            Db::name('user')
                ->where('openid_re', $arr['openid'])
                ->data($update_userinfo)
                ->update();
            //更新商信息
            Db::name('user_withdraw')
                ->where('order_id', $order_id)
                ->data($update_user_withdraw)
                ->update();
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            // 回滚事务
            $flag = 0;
            Db::rollback();
        }
        if($flag == 1){
            //$result = array('status'=>1, 'msg'=>'通过提现成功');
            $result = 1;
        }else{
            //$result = array('status'=>2, 'msg'=>'提现有误,请联系管理员处理');
            $result = 2;
        }
    //    error_log(var_export($result,1),3,'/data/wwwroot/mini3.pinecc.cn/runtime/test.txt');
        return $result;
    }















    //数组转XML
    public function ArrToXml($arr)
    {

    //    var_dump($arr);exit;
        if(!is_array($arr) || count($arr) == 0) return '';
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }



    //发送数据
    function postData($url,$postfields){
        $ch = curl_init();
        $params[CURLOPT_URL] = $url;    //请求url地址
        $params[CURLOPT_HEADER] = false; //是否返回响应头信息
        $params[CURLOPT_RETURNTRANSFER] = true; //是否将结果返回
        $params[CURLOPT_FOLLOWLOCATION] = true; //是否重定向
        $params[CURLOPT_POST] = true;
        $params[CURLOPT_POSTFIELDS] = $postfields;
        $params[CURLOPT_SSL_VERIFYPEER] = false;
        $params[CURLOPT_SSL_VERIFYHOST] = false;
        //以下是证书相关代码
        $params[CURLOPT_SSLCERTTYPE] = 'PEM';
     //   $params[CURLOPT_SSLCERT] = getcwd().'/plugins/payment/weixin/cert/apiclient_cert.pem';//绝对路径
     //   $params[CURLOPT_SSLCERT] = getcwd().'/cert_zr/apiclient_cert.pem';//绝对路径
        $params[CURLOPT_SSLCERT] = getcwd().'/cert/apiclient_cert.pem';//绝对路径
        $params[CURLOPT_SSLKEYTYPE] = 'PEM';
     //   $params[CURLOPT_SSLKEY] = getcwd().'/plugins/payment/weixin/cert/apiclient_key.pem';//绝对路径
     //   $params[CURLOPT_SSLKEY] = getcwd().'/cert_zr/apiclient_key.pem';//绝对路径
        $params[CURLOPT_SSLKEY] = getcwd().'/cert/apiclient_key.pem';//绝对路径
        curl_setopt_array($ch, $params); //传入curl参数
        $content = curl_exec($ch); //执行
        curl_close($ch); //关闭连接
        return $content;
    }


}
