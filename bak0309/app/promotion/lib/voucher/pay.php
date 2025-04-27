<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class promotion_voucher_pay
{
    /*
     * 支付成功，是否代金劵
     */
    public function order_pay_finish($order){
        \Neigou\Logger::Debug("action.voucher",array("action"=>"order_pay_finish", "order"=>$order));
        $service = kernel::service('voucher.service');
        $voucher_list = $service->queryOrderVoucher($order['order_id']);
        $ret = true;
        $voucher_num_list = array();
        if (is_array($voucher_list) && !empty($voucher_list)) {
            foreach ($voucher_list as $voucher_item) {
                $voucher_num_list[]=$voucher_item['number'];
            }
        }
        if ($ret) {
            $service->exchange_status($voucher_num_list, 'finish', "订单支付");
            \Neigou\Logger::Debug("action.voucher",array("action"=>"payed", "number"=>json_encode($voucher_num_list), "order_id"=>$order['order_id']));
        }

        $service->finish_order_for_freeshipping_coupon($order['order_id']);
        return $ret;
    }

}
