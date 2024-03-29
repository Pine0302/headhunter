<?php
namespace weixinpay;
require_once EXTEND_PATH . 'weixinpay/lib/WxPay.Api.php';
require_once EXTEND_PATH . 'weixinpay/example/WxPay.JsApiPay.php';
/*use app\common\library\QRcode;
use think\library\Vender\phpqrcode\phpqrcode;*/
error_reporting(0);
//error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_NOTICE );
// 定义时区
ini_set('date.timezone','Asia/Shanghai');


class Weixinpay
{
    /**
     * 统一下单
     * @param  array $order 订单 必须包含支付所需要的参数 body(产品描述)、total_fee(订单金额)、out_trade_no(订单号)、product_id(产品id)、trade_type(类型：JSAPI，NATIVE，APP)
     */
    public function unifiedOrder($order)
    {
        // 生成随机加密盐
        $nonce_str = $this->makeCode(4);
        // 获取配置项
        $config=array(
            'appid'=>config('wxpay.APPID'),
            'mch_id'=>config('wxpay.MCHID'),
            'nonce_str'=>$nonce_str,
            'spbill_create_ip'=>'115.199.251.81',
            'notify_url'=>config('wxpay.NOTIFY_URL')
        );
      //  var_dump($config);exit;
        // 合并配置数据和订单数据
        $data=array_merge($order,$config);
        // 生成签名
        $sign=$this->makeSign($data);

        $data['sign']=$sign;
        $xml=$this->toXml($data);
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';//接收xml数据的文件
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容本地没有指定curl.cainfo路径的错误
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            // 显示报错信息；终止继续执行
            die(curl_error($ch));
        }
        curl_close($ch);
        $result=$this->toArray($response);
        // 显示错误信息
        if ($result['return_code']=='FAIL') {
            die($result['return_msg']);
        }
        $result['sign']=$sign;
        $result['nonce_str']=$nonce_str;
        return $result;
    }

    public function unifiedMiniOrder($order)
    {

        $wxPayApi = new \WxPayApi();
        //①、获取用户openid
        $tools = new \JsApiPay();
        //$openId = $tools->GetOpenid();
        $openId =$order['openId'];
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("recruit-".$openId);
        $input->SetAttach("recruit-attach");
        //$input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));
        $input->SetOut_trade_no($order['out_trade_no']);
        $input->SetTotal_fee($order['total_fee']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($order['product_id']);
       // $input->SetNotify_url("http://paysdk.weixin.qq.com/example/notify.php");
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = $wxPayApi::unifiedOrder($input);

        $jsApiParameters = $tools->GetJsApiParameters($order);

        return $jsApiParameters;exit;
        //   $this->success('success',$jsApiParameters);
        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();

    }

    /**
     * 退款
     * @param $transaction_id 微信订单号
     * @param $total_fee 订单总金额，单位为分
     * @param $refund_fee 退款总金额
     * @param $out_refund_no 商户退款单号
     * @param $out_trade_no 商户订单号
     * @return array|bool|mixed
     */
    public function refund($transaction_id, $total_fee, $refund_fee, $out_refund_no, $out_trade_no)
    {
       // var_dump(dirname(__FILE__).'/cert/apiclient_cert.pem');exit;
        // 生成随机加密盐
        $nonce_str = $this->makeCode(4);
        $data = [
            'appid' => config('wxpay.APPID'),    // 公众号id
            'mch_id' => config('wxpay.MCHID'),   // 商户号
            'nonce_str' => $nonce_str,           // 随机字符串
            'out_refund_no' => $out_refund_no,   // 商户退款单号(根据实际情况生成)
            'refund_fee' => $refund_fee,         // 退款总金额，单位为分，只能比订单总金额小于或等于(可分多次退款)
            'total_fee' => $total_fee,           // 订单总金额，单位为分
            'transaction_id' => $transaction_id, // 微信订单号
            'out_trade_no' => $out_trade_no      // 商户订单号，与微信订单号可二选一填写(优先使用微信订单号)
        ];
      //  error_log("refund---".json_encode($data),3,'/data/wwwroot/mini3.pinecc.cn/public/log/test.txt');
        $sign=$this->makeSign($data);
        $data['sign'] = $sign;
        $xml = $this->toXml($data);

        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';//接收xml数据的文件
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_TIMEOUT,30);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_URL,$url);
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        }else{
            curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
            curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        }
        curl_setopt($ch,CURLOPT_HEADER,0);

        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_SSLCERT,dirname(__FILE__).'/cert/apiclient_cert.pem');
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_SSLKEY,dirname(__FILE__).'/cert/apiclient_key.pem');
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_CAINFO,dirname(__FILE__).'/cert/rootca.pem');
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        $data = curl_exec($ch);

        if($data){
            curl_close($ch);
            $result = $this->toArray($data);
           // $data['sign']=$sign;
           // $data['nonce_str']=$nonce_str;
         //   error_log("refund---".json_encode($data),3,'/data/wwwroot/mini3.pinecc.cn/public/log/test.txt');
            return $result;
        }
        else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    function xmlToArray($xml){
        $ret = array();
        if($xml instanceOf SimpleXMLElement){
            $xmlDoc = $xml;
        }
        else{
            $xmlDoc = simplexml_load_string($xml, 'SimpleXMLIterator');
            if(!$xmlDoc){      // xml字符串格式有问题
                return null;
            }
        }

        for($xmlDoc->rewind(); $xmlDoc->valid(); $xmlDoc->next()){
            $key = $xmlDoc->key();       // 获取标签名
            $val = $xmlDoc->current();   // 获取当前标签
            if($xmlDoc->hasChildren()){     // 如果有子元素
                $ret[$key] = xmlToArray($val);  // 子元素变量递归处理返回
            }
            else{
                $ret[$key] = (string)$val;
            }
        }
        return $ret;
    }



    /**
     * 退款
     * @param $transaction_id 微信订单号
     * @param $total_fee 订单总金额，单位为分
     * @param $refund_fee 退款总金额
     * @param $out_refund_no 商户退款单号
     * @param $out_trade_no 商户订单号
     * @return array|bool|mixed
     */
    public function refund_bak($transaction_id, $total_fee, $refund_fee, $out_refund_no, $out_trade_no)
    {
        // var_dump(dirname(__FILE__).'/cert/apiclient_cert.pem');exit;
        // 生成随机加密盐
        $nonce_str = $this->makeCode(4);
        $data = [
            'appid' => config('wxpay.APPID'),    // 公众号id
            'mch_id' => config('wxpay.MCHID'),   // 商户号
            'nonce_str' => $nonce_str,           // 随机字符串
            'out_refund_no' => $out_refund_no,   // 商户退款单号(根据实际情况生成)
            'refund_fee' => $refund_fee,         // 退款总金额，单位为分，只能比订单总金额小于或等于(可分多次退款)
            'total_fee' => $total_fee,           // 订单总金额，单位为分
            'transaction_id' => $transaction_id, // 微信订单号
            'out_trade_no' => $out_trade_no      // 商户订单号，与微信订单号可二选一填写(优先使用微信订单号)
        ];
        // var_dump($data);exit;
        $sign=$this->makeSign($data);
        $data['sign'] = $sign;
        $xml = $this->toXml($data);
        var_dump($data);
        error_log(var_export($xml,1),3,'/data/wwwroot/mini3.pinecc.cn/public/log/test.txt');
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/refund';//接收xml数据的文件
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch=curl_init();
        curl_setopt($ch,CURLOPT_TIMEOUT,30);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_HEADER,1);

        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,1);//证书检查
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_SSLCERT,dirname(__FILE__).'/cert/apiclient_cert.pem');
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_SSLKEY,dirname(__FILE__).'/cert/apiclient_key.pem');
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'pem');
        curl_setopt($ch,CURLOPT_CAINFO,dirname(__FILE__).'/cert/rootca.pem');
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
        $data = curl_exec($ch);

        if($data){
            curl_close($ch);
            $data['sign']=$sign;
            $data['nonce_str']=$nonce_str;
            return $data;
        }
        else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    /**
     * 查询退款
     * @param $transaction_id 微信订单号
     * @param $out_trade_no 商户订单号
     * @return array
     */
    public function refundquery($transaction_id, $out_trade_no)
    {
        // 生成随机加密盐
        $nonce_str = $this->makeCode(4);
        // 获取配置项
        $data=array(
            'appid' => config('wxpay.APPID'),    // 公众账号ID
            'mch_id' => config('wxpay.MCHID'),   // 商户号
            'nonce_str' => $nonce_str,           // 随机字符串
            'transaction_id' => $transaction_id, // 微信订单号
            'out_trade_no' => $out_trade_no,     // 商户订单号，与微信订单号可二选一填写(优先使用微信订单号)
            'sign_type' => 'MD5'                 // 加密方式
        );

        // 加密
        $sign=$this->makeSign($data);
        $data['sign']=$sign;
        // 数组转xml
        $xml=$this->toXml($data);
        $url = 'https://api.mch.weixin.qq.com/pay/refundquery';//接收xml数据的文件
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容本地没有指定curl.cainfo路径的错误
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            // 显示报错信息；终止继续执行
            die(curl_error($ch));
        }
        curl_close($ch);
        $result=$this->toArray($response);
      //  var_dump($result);exit;
        // 显示错误信息
        if ($result['return_code']=='FAIL') {
            die($result['return_msg']);
        }
        $result['sign']=$sign;
        $result['nonce_str']=$nonce_str;
        return $result;
    }

    /**
     * 查询订单
     */
    public function orderquery($transaction_id, $out_trade_no)
    {
        // 生成随机加密盐
        $nonce_str = $this->makeCode(4);
        // 获取配置项
        $data=array(
            'appid' => config('wxpay.APPID'),    // 公众账号ID
            'mch_id' => config('wxpay.MCHID'),   // 商户号
            'nonce_str' => $nonce_str,           // 随机字符串
            'transaction_id' => $transaction_id, // 微信订单号
            'out_trade_no' => $out_trade_no      // 商户订单号,与微信订单号可二选一填写(优先使用微信订单号)
        );
        // 加密
        $sign=$this->makeSign($data);
        $data['sign']=$sign;
        // 数组转xml
        $xml=$this->toXml($data);
        $url = 'https://api.mch.weixin.qq.com/pay/orderquery';//接收xml数据的文件
        $header[] = "Content-type: text/xml";//定义content-type为xml,注意是数组
        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 兼容本地没有指定curl.cainfo路径的错误
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        if(curl_errno($ch)){
            // 显示报错信息；终止继续执行
            die(curl_error($ch));
        }
        curl_close($ch);
        $result=$this->toArray($response);
        // 显示错误信息
        if ($result['return_code']=='FAIL') {
            die($result['return_msg']);
        }
        $result['sign']=$sign;
        $result['nonce_str']=$nonce_str;
        return $result;
    }

    /**
     * 验证
     * @return array 返回数组格式的notify数据
     */
    public function notify()
    {
        // 获取xml
        $xml=file_get_contents('php://input', 'r');
        // 转成php数组
        $data=$this->toArray($xml);
        // 保存原sign
        $data_sign=$data['sign'];
        // sign不参与签名
        unset($data['sign']);
        $sign=$this->makeSign($data);
        // 判断签名是否正确  判断支付状态


        if ($sign===$data_sign && $data['return_code']=='SUCCESS' && $data['result_code']=='SUCCESS') {
            $result=$data;
        }else{
            $result=false;
        }

        // 返回状态给微信服务器
        if ($result) {
            $str='<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
        }else{
            $str='<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名失败]]></return_msg></xml>';
        }
        echo $str;
        return $result;
    }

    /**
     * 输出xml字符
     * @throws WxPayException
    **/
    public function toXml($data)
    {
        if(!is_array($data) || count($data) <= 0){
            throw new WxPayException("数组数据异常！");
        }
        $xml = "<xml>";
        foreach ($data as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    /**
     * 生成签名
     * @return 签名，本函数不覆盖sign成员变量，如要设置签名需要调用SetSign方法赋值
     */
    public function makeSign($data)
    {
        // 去空
        $data=array_filter($data);
        //签名步骤一：按字典序排序参数
        ksort($data);
        $string_a=http_build_query($data);
        $string_a=urldecode($string_a);
        //签名步骤二：在string后加入KEY
        $string_sign_temp=$string_a."&key=".config('wxpay.KEY');
        //签名步骤三：MD5加密
        $sign = md5($string_sign_temp);
        // 签名步骤四：所有字符转为大写
        $result=strtoupper($sign);
        return $result;
    }

    /**
     * 将xml转为array
     * @param  string $xml xml字符串
     * @return array       转换得到的数组
     */
    public function toArray($xml)
    {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        $result= json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $result;
    }

    /**
     * 生成随机串
     * @param $count 随机串个数
     */
    public function makeCode($count)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $nonce_str = '';
        for ( $i = 0; $i < $count; $i++ ) {
            $nonce_str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $nonce_str;
    }

    /**
     * 获取jssdk需要用到的数据
     * @return array jssdk需要用到的数据
     */
    public function getParameters()
    {
        // 如果没有get参数没有code；则重定向去获取openid；
        if (!isset($_GET['code'])) {
            // 获取订单号
            $out_trade_no=input('request.out_trade_no',1,'intval');
            // 返回的url
            $redirect_uri=url('pay/weixinpay/pay','','',true);
            $redirect_uri=urlencode($redirect_uri);
            $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.config('wxpay.APPID').'&redirect_uri='.$redirect_uri.'&response_type=code&scope=snsapi_base&state='.$out_trade_no.'#wechat_redirect';
            Header("Location: $url");
            exit();
        }else{
            // 如果有code参数；则表示获取到openid
            $code=input('get.code');
            // 取出订单号
            $out_trade_no=input('request.state',0,'intval');
            // 组合获取prepay_id的url
            $url='https://api.weixin.qq.com/sns/oauth2/access_token?appid='.config('wxpay.APPID').'&secret='.config('wxpay.APPSECRET').'&code='.$code.'&grant_type=authorization_code';
            // curl获取prepay_id
            $result=$this->curl_get_contents($url);
            $result=json_decode($result,true);
            $openid=$result['openid'];
            // 订单数据  请根据订单号out_trade_no 从数据库中查出实际的body、total_fee、out_trade_no、product_id
            $order=array(
                'body'=>'微信支付',// 商品描述（需要根据自己的业务修改）
                'total_fee'=>1,// 订单金额  以(分)为单位（需要根据自己的业务修改）
                'out_trade_no'=>$out_trade_no,// 订单号（需要根据自己的业务修改）
                'product_id'=>'1',// 商品id（需要根据自己的业务修改）
                'trade_type'=>'JSAPI',// JSAPI公众号支付
                'openid'=>$openid// 获取到的openid
            );
            // 统一下单 获取prepay_id
            $unified_order=$this->unifiedOrder($order);
            // 获取当前时间戳
            $time=time();
            // 组合jssdk需要用到的数据
            $data=array(
                'appId'=>config('wxpay.APPID'), //appid
                'timeStamp'=>strval($time), //时间戳
                'nonceStr'=>$unified_order['nonce_str'],// 随机字符串
                'package'=>'prepay_id='.$unified_order['prepay_id'],// 预支付交易会话标识
                'signType'=>'MD5'//加密方式
            );
            // 生成签名
            $data['paySign']=$this->makeSign($data);
            return $data;
        }
    }

    public function beforepay($order){
        $result=$this->unifiedOrder($order);
        $decodeurl=urldecode($result['code_url']);
   //     $jump_url = "https://mini3.pinecc.cn/"

        $url = urldecode($result["code_url"]);
        $url2 = urlencode($result["code_url"]);
        QRcode::png($url2);
    }

    /**
     * 生成支付二维码
     * @param  array $order 订单 必须包含支付所需要的参数 body(产品描述)、total_fee(订单金额)、out_trade_no(订单号)、product_id(产品id)、trade_type(类型：JSAPI，NATIVE，APP)
     */
    public function pay($order)
    {
        $result=$this->unifiedOrder($order);

        $decodeurl=urldecode($result['code_url']);
        $url = urldecode($result["code_url"]);
        $url2 = urlencode($result["code_url"]);
        $url2 = $result["code_url"];
        $imgurl = $this->qrcode($url2);
        return $imgurl;


    }

    /**
     * curl 请求http
     */
    public function curl_get_contents($url)
    {
        $ch=curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);                //设置访问的url地址
        // curl_setopt($ch,CURLOPT_HEADER,1);               //是否显示头部信息
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);               //设置超时
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   //用户访问代理 User-Agent
        curl_setopt($ch, CURLOPT_REFERER,$_SERVER['HTTP_HOST']);        //设置 referer
        curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);          //跟踪301
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);        //返回结果
        $r=curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    public function qrcode($url){
        //不带LOGO
        \think\Loader::import('phpqrcode.phpqrcode');
        //生成二维码图片
        $object = new \QRcode();
        $level=3;
        $size=4;

        $imgurl = '/erweima/'.time().'.png';
        $ad = $_SERVER['DOCUMENT_ROOT'].$imgurl;
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $result = $object->png($url,$ad,$level,$size);
        return $imgurl;
    }



}
