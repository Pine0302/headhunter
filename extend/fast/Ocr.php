<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/25 0025
 * Time: 下午 2:43
 */

namespace fast;


class Ocr
{
    public $app_key;
    public $app_secret;
    public $app_code;
    public $type;    //1:使用app_code  2:使用app_key+app_secret

    public function __construct($arr)
    {
        $this->url = $arr['url'];
        $this->app_code = $arr['app_code'];

    }


    public function analyzeId($image_base64)
    {

        $method = "POST";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $this->app_code);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type" . ":" . "text/html; charset=UTF-8");
        $querys = "";
        $bodys = "{'imgbase64':'".$image_base64."'}";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);   //为0不反回响应头部,为1返回响应头部
        if (1 == strpos("$" . $this->url, "https://")) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $bodys);
        $result_json = curl_exec($curl);

        $result = json_decode($result_json,true);
        if(($result['code']==200)&&(!empty($result['data']['front_side']))){
            $memberinfo = $result['data']['front_side'];
            $data = [
                'name' => $memberinfo['name'],
                'sex' => $memberinfo['sex'],
                'nation' => $memberinfo['nation'],
                'date_of_birth' => $memberinfo['date_of_birth'],
                'address' => $memberinfo['address'],
                'card_no' => $memberinfo['card_no'],
            ];

            return $data;
        }else{
            return null;
        }

    }

    public function analyzeId1($image_base64)
    {
        $url = "https://dm-51.data.aliyun.com/rest/160601/ocr/ocr_idcard.json";
        $appcode = $this->app_code;
        //    $file = $_SERVER['DOCUMENT_ROOT']."/json/test.json";
        //如果输入带有inputs, 设置为True，否则设为False
        $is_old_format = false;
        //如果没有configure字段，config设为空
        $config = array(
            "side" => "face"
        );
        //$config = array()


        /*     if($fp = fopen($file, "rb", 0)) {
                 $binary = fread($fp, filesize($file)); // 文件读取
                 fclose($fp);
                 //   $base64 = base64_encode($binary); // 转码
                 $base64 =$binary; // 转码
             }*/
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $querys = "";
        if($is_old_format == TRUE){
            $request = array();
            $request["image"] = array(
                "dataType" => 50,
                "dataValue" => "$image_base64"
            );

            if(count($config) > 0){
                $request["configure"] = array(
                    "dataType" => 50,
                    "dataValue" => json_encode($config)
                );
            }
            $body = json_encode(array("inputs" => array($request)));

        }else{
            $request = array(
                "image" => "$image_base64"
            );
            if(count($config) > 0){
                $request["configure"] = json_encode($config);
            }
            $body = json_encode($request);

        }
        $method = "POST";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$url, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $result = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $rheader = substr($result, 0, $header_size);
        $rbody = substr($result, $header_size);

        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);

        if($httpCode == 200){
            if($is_old_format){
                $output = json_decode($rbody, true);
                $result_str = $output["outputs"][0]["outputValue"]["dataValue"];
            }else{
                $result_str = $rbody;
            }
            $memberinfo = json_decode($result_str,true);
            $birthtime = strtotime($memberinfo['birth']);
            $date_of_birth = date('Y-m-d',$birthtime);
            $data = [
                'name' => $memberinfo['name'],
                'sex' => $memberinfo['sex'],
                'nation' => $memberinfo['nationality'],
                'date_of_birth' => $date_of_birth,
                'address' => $memberinfo['address'],
                'card_no' => $memberinfo['num'],
            ];
            return $data;
        }else{
            return null;
        }
    }



    public function analyzeId2($image_base64)
    {
        $url = "https://dm-51.data.aliyun.com/rest/160601/ocr/ocr_idcard.json";
        $appcode = $this->app_code;
        $file = $_SERVER['DOCUMENT_ROOT']."/json/test.json";
        //如果输入带有inputs, 设置为True，否则设为False
        $is_old_format = false;
        //如果没有configure字段，config设为空
        $config = array(
            "side" => "face"
        );
        //$config = array()


        if($fp = fopen($file, "rb", 0)) {
            $binary = fread($fp, filesize($file)); // 文件读取
            fclose($fp);
            //   $base64 = base64_encode($binary); // 转码
            $base64 =$binary; // 转码
        }
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        //根据API的要求，定义相对应的Content-Type
        array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
        $querys = "";
        if($is_old_format == TRUE){
            $request = array();
            $request["image"] = array(
                "dataType" => 50,
                "dataValue" => "$base64"
            );

            if(count($config) > 0){
                $request["configure"] = array(
                    "dataType" => 50,
                    "dataValue" => json_encode($config)
                );
            }
            $body = json_encode(array("inputs" => array($request)));
        }else{
            $request = array(
                "image" => "$base64"
            );
            if(count($config) > 0){
                $request["configure"] = json_encode($config);
            }
            $body = json_encode($request);
        }
        $method = "POST";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        if (1 == strpos("$".$url, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $result = curl_exec($curl);
        var_dump($result);exit;
        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $rheader = substr($result, 0, $header_size);
        $rbody = substr($result, $header_size);

        $httpCode = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        if($httpCode == 200){
            if($is_old_format){
                $output = json_decode($rbody, true);
                $result_str = $output["outputs"][0]["outputValue"]["dataValue"];
            }else{
                $result_str = $rbody;
            }
            printf("result is :\n %s\n", $result_str);
        }else{
            printf("Http error code: %d\n", $httpCode);
            printf("Error msg in body: %s\n", $rbody);
            printf("header: %s\n", $rheader);
        }
    }







}