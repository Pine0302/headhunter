<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/21 0021
 * Time: 上午 10:44
 */
namespace app\common\library;



 class Dater
{
     /**
      * 构造函数
      * @param $sessionKey string 用户在小程序登录后获取的会话密钥
      * @param $appid string 小程序的appid
      */
     public function __construct()
     {

     }


     //生成社交时间戳
     //1小时以内 显示xx分钟前
     //24个小时以内,显示xx小时前
     //其余 日期+时间 06-28
    public function socialDateDisplay($str){
        $now = time();
        $time_diff = $now - $str;
        if($time_diff<60*60){
            $min = ceil($time_diff/60);
            $result = $min."分钟前";
        }elseif(($time_diff>60*60)&&($time_diff<24*60*60)){
            $hour = ceil($time_diff/(60*60));
            $result = $hour."小时前";
        }elseif(($time_diff>24*60*60)&&($time_diff<30*24*60*60)){
            $day = ceil($time_diff/(60*60*24));
            $result = $day.'天前';
        }else{
            $result = date('m-d',$str);
        }
        return $result;
    }


     //最近发布时间函数
     public function getTimePath($num){
         $time_range = [];
         $day_time = 24*60*60;
         $now = time();
         $day_begin_time = strtotime(date("Y-m-d 00:00:00"));
         switch($num){
             case 1; //不限
                 break;
             case 2; //今天发布
                 $time_range['start'] = date("Y-m-d 00:00:00",$now);
                 $time_range['end'] = date("Y-m-d H:i:s",$now);
                 break;
             case 3; //三天内
                 $start = $now - $day_time*3;
                 $time_range['start'] = date("Y-m-d 00:00:00",$start);
                 $time_range['end'] = date("Y-m-d H:i:s",$now);
                 break;
             case 4; //一周内
                 $start = $now - $day_time*7;
                 $time_range['start'] = date("Y-m-d 00:00:00",$start);
                 $time_range['end'] = date("Y-m-d H:i:s",$now);
                 break;
             case 5; //两周内
                 $start = $now - $day_time*14;
                 $time_range['start'] = date("Y-m-d 00:00:00",$start);
                 $time_range['end'] = date("Y-m-d H:i:s",$now);
                 break;
         }
         return $time_range;
     }

     //获取工作经验对应开始工作时间
     public function getWorkTimePath($num){
         $time_range = [];
         $year_time = 24*60*60*365;
         $now = time();
         $day_begin_time = strtotime(date("Y-m-d 00:00:00"));
         switch($num){
             case 1; //应届
                 break;
             case 2; //三年以内
                 $time_range['start'] = date("Y-m-d 00:00:00",($now-3*$year_time));
                 break;
             case 3; //三到五年
                 $time_range['start'] = date("Y-m-d 00:00:00",($now-5*$year_time));
                 $time_range['end'] = date("Y-m-d 00:00:00",($now-3*$year_time));
                 break;
             case 4; //五到十年
                 $time_range['start'] = date("Y-m-d 00:00:00",($now-10*$year_time));
                 $time_range['end'] = date("Y-m-d 00:00:00",($now-5*$year_time));
                 break;
             case 5; //十年以上
                 $time_range['end'] = date("Y-m-d 00:00:00",($now-10*$year_time));
                 break;
         }
         return $time_range;
     }


     //
     public function getSalaryPath($num){
         $salary_range = [];
         switch($num){
             case 1; //不限
                 break;
             case 2; //小于2K
                 $salary_range['min_salary'] = 0;
                 $salary_range['max_salary'] = 2001;
                 break;
             case 3; //2 - 5 k
                 $salary_range['min_salary'] = 1999;
                 $salary_range['max_salary'] = 5001;
                 break;
             case 4; //5-10k
                 $salary_range['min_salary'] = 4999;
                 $salary_range['max_salary'] = 10001;
                 break;
             case 5; //10-15k
                 $salary_range['min_salary'] = 9999;
                 $salary_range['max_salary'] = 15001;
                 break;
             case 6; //15-25k
                 $salary_range['min_salary'] = 14999;
                 $salary_range['max_salary'] = 25001;
                 break;
             case 7; //25-50k
                 $salary_range['min_salary'] = 24999;
                 $salary_range['max_salary'] = 50001;
                 break;
             case 8; //50K以上
                 $salary_range['min_salary'] = 49999;
                 break;
         }
         return $salary_range;
     }


    public function getSalaryRange($min_salary,$max_salary){
         if(empty($min_salary) && (empty($max_salary))){
             return 1; exit;
         }
         if(empty($min_salary)){
             return 2;
         }

        switch($min_salary){
            case 1999;
                $salary_range = 3;
                break;
            case 4999;
                $salary_range = 4;
                break;
            case 9999;
                $salary_range = 5;
                break;
            case 14999;
                $salary_range = 6;
                break;
            case 424999;
                $salary_range = 7;
                break;
            case 49999;
                $salary_range = 8;
                break;
        }
        return $salary_range;
    }


     //获取工作年限
     public function getWorkYears($begin_time){

         $now = time();
         if($begin_time==0){
             $work_yeas = '未知';
         }else{
             $work_yeas = ceil(($now-$begin_time)/(365*24*60*60));
         }

         return $work_yeas;
     }



}
