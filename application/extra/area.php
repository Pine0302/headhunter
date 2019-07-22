<?php
return [
    'prov_list'=>[
        [
            'prov_code'=>8,
            'prov_name'=>'直辖市',
        ],
        [
            'prov_code'=>330000,
            'prov_name'=>'浙江省',
        ],
    ],
    'direct_city_code'=>8,
    'direct_city_list'=>[
        [
            'city_code'=>110000,
            'city_name'=>'北京市',
        ],
        [
            'city_code'=>121000,
            'city_name'=>'天津市',
        ],
        [
            'city_code'=>311000,
            'city_name'=>'上海市',
        ],
        [
            'city_code'=>501000,
            'city_name'=>'重庆市',
        ],

    ],
    'hot_city_list'=>[
        [
            'city_code'=>110000,
            'city_name'=>'北京市',
        ],
        [
            'city_code'=>121000,
            'city_name'=>'天津市',
        ],
        [
            'city_code'=>311000,
            'city_name'=>'上海市',
        ],
        [
            'city_code'=>330100,
            'city_name'=>'杭州市',
        ],
    ],
    'user_default_position'=> [
        'lat'=> 30.28 ,
        'lng'=> 120.15,
    ]

];

/*
Array
(
    [0] => Array
        (
            [areano] => 110000
            [areaname] => 北京
            [parentno] => 0
            [areacode] => 010
            [arealevel] => 1
            [typename] => 市
            [sort] => 15
            [isuse] => 1
        )

    [1] => Array
        (
            [areano] => 120000
            [areaname] => 天津
            [parentno] => 0
            [areacode] => 022
            [arealevel] => 1
            [typename] => 市
            [sort] => 3
            [isuse] => 1
        )

    [2] => Array
        (
            [areano] => 130000
            [areaname] => 河北省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 2
            [isuse] => 1
        )

    [3] => Array
        (
            [areano] => 140000
            [areaname] => 山西省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 23
            [isuse] => 0
        )

    [4] => Array
        (
            [areano] => 150000
            [areaname] => 内蒙古自治区
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [5] => Array
        (
            [areano] => 210000
            [areaname] => 辽宁省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 1
            [isuse] => 1
        )

    [6] => Array
        (
            [areano] => 220000
            [areaname] => 吉林省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [7] => Array
        (
            [areano] => 230000
            [areaname] => 黑龙江省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [8] => Array
        (
            [areano] => 310000
            [areaname] => 上海
            [parentno] => 0
            [areacode] => 021
            [arealevel] => 1
            [typename] => 市
            [sort] => 6
            [isuse] => 0
        )

    [9] => Array
        (
            [areano] => 320000
            [areaname] => 江苏省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 5
            [isuse] => 1
        )

    [10] => Array
        (
            [areano] => 330000
            [areaname] => 浙江省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 7
            [isuse] => 1
        )

    [11] => Array
        (
            [areano] => 340000
            [areaname] => 安徽省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 15
            [isuse] => 1
        )

    [12] => Array
        (
            [areano] => 350000
            [areaname] => 福建省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 8
            [isuse] => 0
        )

    [13] => Array
        (
            [areano] => 360000
            [areaname] => 江西省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 16
            [isuse] => 1
        )

    [14] => Array
        (
            [areano] => 370000
            [areaname] => 山东省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 4
            [isuse] => 1
        )

    [15] => Array
        (
            [areano] => 410000
            [areaname] => 河南省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 22
            [isuse] => 0
        )

    [16] => Array
        (
            [areano] => 420000
            [areaname] => 湖北省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 17
            [isuse] => 0
        )

    [17] => Array
        (
            [areano] => 430000
            [areaname] => 湖南省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 18
            [isuse] => 0
        )

    [18] => Array
        (
            [areano] => 440000
            [areaname] => 广东省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 10
            [isuse] => 0
        )

    [19] => Array
        (
            [areano] => 450000
            [areaname] => 广西壮族自治区
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 12
            [isuse] => 1
        )

    [20] => Array
        (
            [areano] => 460000
            [areaname] => 海南省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 11
            [isuse] => 0
        )

    [21] => Array
        (
            [areano] => 500000
            [areaname] => 重庆
            [parentno] => 0
            [areacode] => 0811
            [arealevel] => 1
            [typename] => 市
            [sort] => 19
            [isuse] => 0
        )

    [22] => Array
        (
            [areano] => 510000
            [areaname] => 四川省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 20
            [isuse] => 0
        )

    [23] => Array
        (
            [areano] => 520000
            [areaname] => 贵州省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [24] => Array
        (
            [areano] => 530000
            [areaname] => 云南省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 21
            [isuse] => 0
        )

    [25] => Array
        (
            [areano] => 540000
            [areaname] => 西藏自治区
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [26] => Array
        (
            [areano] => 610000
            [areaname] => 陕西省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [27] => Array
        (
            [areano] => 620000
            [areaname] => 甘肃省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [28] => Array
        (
            [areano] => 630000
            [areaname] => 青海省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [29] => Array
        (
            [areano] => 640000
            [areaname] => 宁夏回族自治区
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [30] => Array
        (
            [areano] => 650000
            [areaname] => 新疆维吾尔自治区
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 99
            [isuse] => 0
        )

    [31] => Array
        (
            [areano] => 710000
            [areaname] => 台湾省
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 9
            [isuse] => 0
        )

    [32] => Array
        (
            [areano] => 810000
            [areaname] => 香港特别行政区
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 13
            [isuse] => 0
        )

    [33] => Array
        (
            [areano] => 820000
            [areaname] => 澳门特别行政区
            [parentno] => 0
            [areacode] =>
            [arealevel] => 1
            [typename] => 省
            [sort] => 14
            [isuse] => 0
        )

)

 * */