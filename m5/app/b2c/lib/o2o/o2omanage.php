<?php
class b2c_o2o_o2omanage
{
    /*
    * O2O商品发码处理
    *
    * @param   $sdf  array   订单信息
    * @return  boolean
    */
    public function sendCoupon($sdf)
    {
        $return = true;
        // 更新 O2O 订单支付状态
        app::get('b2c')->model('o2o_handorder')->updateOrderPayStatus($sdf['order_id'], 1);
        // 获取订单信息
        //@TODO maojz pop订单兼容ec订单
        if(!isset($sdf['items'])) {
            $orders = app::get('b2c')->model('orders')->getList('order_id', array('order_id' => $sdf['order_id']));
            if( empty($orders)) return false;
            $order_items = app::get('b2c')->model('order_items')->getList('bn,nums', array('order_id' => $sdf['order_id']));
        } else {
            $order_items = $sdf['items'];
        }
        //TODO 自定义短信渠道
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($sdf['order_id']);
        $message_channel = '';
        if ($order_info) {
            $message_channel = kernel::single("b2c_global_scope")->getCompanyMessageChannel($order_info['company_id']);
        }
        \Neigou\Logger::General(
            "o2o-send-coupon-message_channel",
            array("data" => json_encode(array("message_channel" => $message_channel))
            ));
        // 商品发码
        foreach ($order_items as $key => $val) {
            // 获取O2O商品信息
            $product = app::get('b2c')->model('o2o_products')->get_Coupon_auth($val['bn']);
            if (empty($product)) continue;
            $product = current($product);
            /** 根据type值进行发码处理 0:系统发码 1:第三方对接自动 发码 2:人工处理 */
            switch ($product['type']) {
                case 0:
                    if (!app::get('b2c')->model('o2o_products')->sms_order_id($val['bn'], $val['nums'], $sdf,$message_channel))  {
                        return false;
                    }
                    $serviceOrderModel = app::get('b2c')->model('service_orders');
                    $orderInfo = $serviceOrderModel->getRow('order_id',array('service_order_id'=>$sdf['order_id']));
                    // 更新 O2O 订单支付状态
                    app::get('b2c')->model('orders')->updateOrderStatus($orderInfo['order_id'], 'finish');
                    // 通知订单
                    $this->notifyOrder($orderInfo['order_id']);
                    break;

                case 1:
                    if (!app::get('b2c')->model("o2o_thirdproducts")->sms_order_id($val['bn'], $val['nums'],
                        $sdf)) {
                        $return = false;
                    }
                    break;
            }
            // 更新库存更新时间
            app::get('b2c')->model('o2o_products')->updateStockTime($val['bn']);
        }
        if ($return) {
            // 获取订单的类型
            $handOrder = app::get('b2c')->model('o2o_handorder')->getByOrderId($sdf['order_id']);
            if (isset($handOrder[0]['type']) && in_array($handOrder[0]['type'], array(0, 1))) {
                // 更新O2O订单状态为已预订
                $return = (bool) app::get('b2c')->model('o2o_handorder')->updateOrderStatus($sdf['order_id'], 1);
            }
        }
        return $return;
    }

    public function getCoupon($order_id,$product_bn){
        $o2o = app::get('b2c')->model("o2o_products");
        $coupon = $o2o->get_Coupon_auth($product_bn);
        \Neigou\Logger::General("o2o_thirdproducts.resondSms", array(
            'coupon'=>$coupon,
            'par'=>array($order_id,$mobile,$bn,$members,$message_channel)
        ));
        if($coupon){
            foreach($coupon as $key=>$val){
                if($val['type']=='1'){
                    return $o2o->third_order_Id_bn_coupon($order_id,$product_bn);
                }
                if($val['type']=='0'){
                    return $o2o->order_Id_bn_coupon($order_id,$product_bn);
                }
            }
        }
    }

    public function resondSms($order_id,$mobile,$bn,$members,$message_channel = ""){
        $o2o = app::get('b2c')->model("o2o_products");
        $o2o_third = app::get('b2c')->model("o2o_thirdproducts");
        $coupon = $o2o->get_Coupon_auth($bn);
        if($coupon){
            foreach($coupon as $key=>$val){
                if($val['type']=='1'){
                    return $o2o_third->retrySend($order_id,$members['member_id'],$bn,$val['name'],$mobile,$message_channel);  //重发
                }
                if($val['type']=='0'){
                    return $o2o->sms_($order_id,$mobile,$bn,$message_channel);
                }
            }
        }
    }

    // --------------------------------------------------------------------

    /**
     * 订单发货
     * @params  $orderId    string  订单ID
     * @param   $msg        string  描述
     * @return  boolean
     */
    private function _orderDoDelivery($orderId, & $msg = '')
    {

        // 订单数据
        $sdf = array(
            'order_id'      =>  $orderId,
            'op_id'         =>  1,
            'opname'        => 'admin',
            'account_type'  => 'shopadmin',
        );

        // 检查发货状态
        $checkOrder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));
        if ( ! $checkOrder->check_order_delivery($sdf['order_id'], $sdf, $msg))
        {
            return false;
        }

        // 处理支付单据.
        $objDelivery = b2c_order_delivery::getInstance(app::get('b2c'), app::get('b2c')->model('delivery'));

        $controller = app::get('b2c')->controller('site_order');
        if ( ! $objDelivery->generate($sdf, $controller, $msg))
        {
            return false;
        }

        return true;
    }
    // --------------------------------------------------------------------

    /**
     * 订单完成
     * @params  $orderId    string  订单ID
     * @param   $msg        string  描述
     * @return  boolean
     */
    private function _orderDoFinish($orderId, & $msg = '')
    {
        $checkOrder = kernel::service('b2c_order_apps', array('content_path'=>'b2c_order_checkorder'));

        // 检查订单状态
        if ( ! $checkOrder->check_order_finish($orderId, '', $msg))
        {
            return false;
        }

        // 订单数据
        $sdf = array(
            'order_id'      =>  $orderId,
            'op_id'         =>  1,
            'opname'        => 'admin',
            'account_type'  => 'shopadmin',
        );

        $controller = app::get('b2c')->controller('site_order');

        // 订单完成
        $orderFinish = kernel::single("b2c_order_finish");
        if ( ! $orderFinish->generate($sdf, $controller, $msg))
        {
            return false;
        }
        return true;
    }


    /** 获取salyut远程卡券信息
     *
     * @param string $orderId   内购订单id
     * @param string $bn    内购商品bn
     * @param string $couponNo  卡券号
     * @return bool
     * @author liuming
     */
    public function getRemoteCouponInfo($orderId = '',$bn = '',$couponNo = '',$memberId = ''){
        if (empty($orderId) || empty($bn) || empty($couponNo)){
            return false;
        }
        return app::get('b2c')->model("o2o_thirdproducts")->getCouponInfo($orderId, $bn,$couponNo,$memberId);
    }


    /** 激活卡券
     *
     * @param string $orderId
     * @param string $couponNo
     * @param string $bn
     * @param string $mobile
     * @return bool
     * @author liuming
     */
    public function remoteActivateCoupon($orderId = '',$couponNo = '',$bn = '',$mobile = '',$memberId = 0){
        if (empty($orderId) || empty($bn) || empty($couponNo) || empty($mobile)){
            return false;
        }
        return app::get('b2c')->model("o2o_thirdproducts")->activateCoupon($orderId,$couponNo,$bn,$mobile,$memberId);
    }


    /** 批量获取券码信息ByOrderId
     *
     * @param array $orderIdList
     * @param int $memberId
     * @param array $offSupplier   线下激活的供应商
     * @return array
     * @author liuming
     */
    public function batchGetCouponInfoByOrderId($orderIdList = array(),$memberId = 0, & $offSupplier = array()){
        $allowSupplier = array( 'XMLY','ZH');
        $returnData = array();

        foreach($orderIdList as $k => $v){
            if (in_array($k,$allowSupplier)){
               //实际发起请求
                $couponSupplierRes = app::get('b2c')->model("o2o_thirdproducts")->batchGetCouponInfo($v,$memberId,$k);
                if ($couponSupplierRes['result'] == "true"){
                    $returnData[$k] = $couponSupplierRes['data'];
                }
            }
        }
        $offSupplier = $allowSupplier;
        return $returnData;
    }


    /** 批量获取券码信息ByOrderId
     *
     * @param array $orderIdList
     * @return array
     */
    public function batchGetShopCouponInfoByOrderId($orderIdList = array())
    {
        // 获取SHOP 券码
        $orderCouponList = app::get('b2c')->model("o2o_thirdproducts")->batchGetShopCouponInfo($orderIdList);
        return $orderCouponList ? $orderCouponList : array();
    }

    public function getRestCodeNum($order_id, $couponOrderRes = array()){
        $couponOrderRes = $couponOrderRes ?: $this->batchGetShopCouponInfoByOrderId(array($order_id));
        $couponList = $couponOrderRes[$order_id];
        $count = 0;
        foreach ($couponList['ticket_list'] as $tickInfo) {
            // (1:未使用 2:锁定 3:已核销 4:作废)
            if ($tickInfo['status'] == 2 || $tickInfo['status'] == 1) {
                $count++;
            }
        }
        return $count;
    }

    public function getShopCouponStatus($order_ids, $couponOrderRes = array())
    {
        $couponOrderRes = $couponOrderRes ?: $this->batchGetShopCouponInfoByOrderId($order_ids);
        $result = array();
        $time = time();
        $statusRelation = array(
            1 => 2, // 未使用=>待核销
            2 => 2, // 锁定=>待核销
            3 => 3, // 已核销
            4 => 4, // 已作废
        );
        foreach ($couponOrderRes as $item) {
            foreach ($item['ticket_list'] as $tickInfo) {
                $showStatus = $statusRelation[$tickInfo['status']];
                $isExpire = $tickInfo['expire_time'] < $time;
                $showStatus = $showStatus == 2 && $isExpire ? 5 : $showStatus; // 未核销过期
                $result[$item['order_id']][$tickInfo['ticket_code']] = array(
                    'show_status' => $showStatus,
                    'is_expire' => $isExpire,
                    'status' => $tickInfo['status'],
                    'verify_url' => $tickInfo['verify_url'],
                    'expire_time' => $tickInfo['expire_time'],
                );
            }
        }
        return $result;
    }

    public function notifyOrder($order_id)
    {
        $req_data = array(
            'wms_order_bn' => $order_id,
            'wms_code' => 'EC',
        );
        $extend_config = array(
            'timeout' => 3,
        );
        $ret = \Neigou\ApiClient::doServiceCall('order', 'Order/Message/OrderUpdate', 'v1', null, $req_data, $extend_config);
        if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'])
        {
            return true;
        }
        else
        {
            $error_code = $ret['service_data']['error_code'];
            $msg = '发送订单变化通知到订单服务失败';
            \Neigou\Logger::General("cps_update_order_status", array('func' => 'sendNotifyToOrderService', 'error_code' => $error_code, 'msg' => $msg));

            return false;
        }
    }

    /**
     * Notes:根据券码获取券码详情
     * Date: 2024/12/16 下午2:29
     * @param $productBn
     * @param $couponNo
     * @return array|false|mixed
     */
    public function getCouponInfoByCouponNo($productBn = '', $couponNo = '')
    {
        if (empty($productBn) || empty($couponNo)) {
            return false;
}
        return app::get('b2c')->model("o2o_thirdproducts")->getCouponInfoByCouponNo($productBn, $couponNo);
    }

    /**
     * Notes
     * Date: 2024/12/16 下午2:29
     */
    public function checkActivateCoupon($orderId = '', $couponNo = '', $productBn = '', $mobile = '', $account = '')
    {
        if (empty($orderId) || empty($couponNo) || empty($productBn)) {
            return false;
        }
        if (empty($mobile) && empty($account)) {
            return false;
        }
        return app::get('b2c')->model("o2o_thirdproducts")->checkActivateCoupon($orderId, $couponNo, $productBn, $mobile, $account);

    }
}
