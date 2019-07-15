<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/5/21 0021
 * Time: 上午 10:44
 */
namespace app\common\library;

class Order
{
    /**
     * 查看
     * prefix  b2p:公司支付到平台
     */
    public function createOrderCode($prefix="re")
    {
        return strtoupper($prefix."_".uniqid());
    }

    /**
     * 查看
     * prefix  p2u:平台支付到用户
     */
    public function createOrder2($prefix="P2U")
    {
        return strtoupper($prefix.uniqid());
    }

}
