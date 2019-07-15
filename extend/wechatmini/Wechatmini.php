<?php
namespace weichatmini;
/*use app\common\library\QRcode;
use think\library\Vender\phpqrcode\phpqrcode;*/
//error_reporting(0);
//error_reporting(E_ALL);
//error_reporting(E_ALL & ~E_NOTICE );
// 定义时区
ini_set('date.timezone','Asia/Shanghai');


class Weichatmini
{

    public $appid = '';
    public $appsecret = '';

    public function __construct()
    {

        $config=array(
            'appid'=>config('wxpay.APPID'),
            'appsecret'=>config('wxpay.APPID'),
        );
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
            error_log('---sign---'.json_encode(var_export($sign,1)),3,'/data/wwwroot/mini3.pinecc.cn/public/log/test.txt');

            if ($sign===$data_sign && $data['return_code']=='SUCCESS' && $data['result_code']=='SUCCESS') {
                $result=$data;
            }else{
                $result=false;
            }
            error_log('---result---'.json_encode(var_export($result,1)),3,'/data/wwwroot/mini3.pinecc.cn/public/log/test.txt');
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
