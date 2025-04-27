<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class thriftcenter_point_cancel
{
    /*
     * 订单取消，退内购积点
     */
    public function order_pay_cancel($order){
        \Neigou\Logger::General("thriftcenter_point_cancel",array('action'=>'order_pay_cancel','order'=>$order));
        $point_= kernel::service("point.service");
        if(!$order['member_id']){
            $order['member_id']=0;//系统自动取消
        }
        $point_->memberOrderStatusChanged($order['order_id'],'cancel',$order['member_id'],'取消订单'.$order['order_id']);
    }

}
