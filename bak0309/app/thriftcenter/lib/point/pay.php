<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class thriftcenter_point_pay
{
    /*
     * 支付成功，是否内购积点
     */
    public function order_pay_finish($order){
        \Neigou\Logger::General("thriftcenter_point_pay",array('action'=>'order_pay_finish','order'=>$order));
        $point_= kernel::service("point.service");
        $point_->memberOrderStatusChanged($order['order_id'],'finish',$order['member_id'],'订单'.$order['order_id'].'支付');
    }

}
