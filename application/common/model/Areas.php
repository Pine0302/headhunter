<?php

namespace app\common\model;

use think\Cache;
use think\Model;

/**
 * 地区数据模型
 */
class Areas extends Model
{

    /**
     * 根据经纬度获取当前地区信息
     *
     * @param string $lng   经度
     * @param string $lat   纬度
     * @return array 城市信息
     */
    public static function getAreaFromLngLat($lng, $lat, $level = 3)
    {
        $namearr = [1 => 'geo:province', 2 => 'geo:city', 3 => 'geo:district'];
        $rangearr = [1 => 15000, 2 => 1000, 3 => 200];
        $geoname = isset($namearr[$level]) ? $namearr[$level] : $namearr[3];
        $georange = isset($rangearr[$level]) ? $rangearr[$level] : $rangearr[3];
        $neararea = [];
        // 读取范围内的ID
        $redis = Cache::store('redis')->handler();
        $georadiuslist = [];
        if (method_exists($redis, 'georadius'))
        {
            $georadiuslist = $redis->georadius($geoname, $lng, $lat, $georange, 'km', ['WITHDIST', 'COUNT' => 5, 'ASC']);
        }

        if ($georadiuslist)
        {
            list($id, $distance) = $georadiuslist[0];
        }
        $id = isset($id) && $id ? $id : 3;
        return self::get($id);
    }

    /**
     * 根据经纬度获取省份
     *
     * @param string $lng   经度
     * @param string $lat   纬度
     * @return array
     */
    public static function getProvinceFromLngLat($lng, $lat)
    {
        $provincedata = [];
        $citydata = self::getCityFromLngLat($lng, $lat);
        if ($citydata)
        {
            $provincedata = self::get($citydata['pid']);
        }
        return $provincedata;
    }

    /**
     * 根据经纬度获取城市
     *
     * @param string $lng   经度
     * @param string $lat   纬度
     * @return array
     */
    public static function getCityFromLngLat($lng, $lat)
    {
        $citydata = [];
        $districtdata = self::getDistrictFromLngLat($lng, $lat);
        if ($districtdata)
        {
            $citydata = self::get($districtdata['pid']);
        }
        return $citydata;
    }

    /**
     * 根据经纬度获取地区
     *
     * @param string $lng   经度
     * @param string $lat   纬度
     * @return array
     */
    public static function getDistrictFromLngLat($lng, $lat)
    {
        $districtdata = self::getAreaFromLngLat($lng, $lat, 3);
        return $districtdata;
    }

    /**
     * 根据省份名称获取地区code
     *
     * @param string $areas_arr   省份名称 城市名称 数组
     * @return array
     */
    public function getAreaCodeByName($areas_arr){
        $prov_info =Areas::where('areaname','like',$areas_arr['0']."%")->find();
        Areas::removeOption();
        $city_info =Areas::where('areaname','like',$areas_arr['1']."%")->find();
        Areas::removeOption();
        $district_info =Areas::where('areaname','like',$areas_arr['2']."%")->find();
        Areas::removeOption();
        $response = array(
            'prov_info'=>array(
                'areano'=>$prov_info['areano'],
            ),
            'city_info'=>array(
                'areano'=>$city_info['areano'],
            ),
            'district_info'=>array(
                'areano'=>$district_info['areano'],
            ),

        );
        return $response;
    }

    public function areaNameFormat($areas){
        $areas_arr = explode('-',$areas);
        if(!$areas_arr['0']){  //直辖市
            $areas_arr['0'] = $areas_arr['1'];
        }
        ((mb_substr($areas_arr['0'],-1)=="省")||(mb_substr($areas_arr['0'],-1)=="市"))? ($areas_arr['0'] = mb_substr($areas_arr['0'],0, mb_strlen($areas_arr['0'])-1)) : 1 ;
        ((mb_substr($areas_arr['1'],-1)=="省")||(mb_substr($areas_arr['1'],-1)=="市")) ? ($areas_arr['1'] = mb_substr($areas_arr['1'],0, mb_strlen($areas_arr['1'])-1)) : 1 ;

        return  $this->getAreaCodeByName($areas_arr);
    }

}
