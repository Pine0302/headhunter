<?php
/**
 * Created by PhpStorm.
 * User: jiqing
 * Date: 18-12-21
 * Time: 下午8:49
 */

namespace app\common\service;
// 服务层，介于C层与M层之间

/**  根据上面的分析，Service夹在C层和M层中间，从逻辑上大致划分为3大类：
### model侧的Service：也就是封装每个model与业务相关的通用数据接口，比如：查询订单。（我认为：访问远程服务获取数据也应该归属于这一类Service）
### 中间的Service：封装通用的业务逻辑，比如：计算订单折扣（会用到1中的Service）。
### controller侧的Service：基于1、2中的Service进一步封装对外接口的用户业务逻辑。
 **/

class CommonService
{
    protected $out_data;
    // 构造函数
    public function __construct()
    {
        $this->out_data = ['errno'=>0,'errdesc'=>''];
    }

    public function set_err($errno,$errdesc) {
        $this->out_data['errno'] = $errno;
        $this->out_data['errdesc'] = $errdesc;
    }

    public function set_data($data) {
        $this->out_data['data'] = $data;
    }



}