<?php

class b2c_service_orderconfirmfinished
{


    /**
     * @param $order_id
     * 支付成功，发o2o码 @TODO maojz 写消息到通知队列
     */
    public function o2o_order_pay_finish($order)
    {
        \Neigou\Logger::General(
            "b2c_service_order_pay_finish_message",
            array('action' => 'order_pay_finish', 'type'=>'o2o_order_pay_finish','order' => $order)
        );
        include_once ROOT_DIR . '/NG_PHP_LIB/AutoCIUtils.php';
        $host    = str_replace('http://', '', ECSTORE_DOMAIN);
        $current = \Neigou\AutoCIUtils::getCurrentVersion($host);
        $res     = $this->do_o2o_order_pay_finish($order);
        \Neigou\Logger::General("b2c_service_orderpayfinished", array(
            'action'  => 'order_pay_finish',
            'type'    =>'o2o_order_pay_finish',
            'current' => $current,
            'order'   => $order,
            'iparam1' => $order['order_id'],
            'sparam1' => $res
        ));
        return $res;
    }

    /**
     * 支付成功，发o2o码
     * @param $order
     * @return bool
     */
    public function do_o2o_order_pay_finish($order)
    {
        if (!$order || !$order['order_id']) {
            return false;
        }
        $model  = app::get('b2c')->model('o2o_products');
        $result = $model->order_id_count_count($order['order_id']);
        //是否已绑定电子码
        if ($result) {
            \Neigou\Logger::General("b2c_service_orderpayfinished.rpc.o2o.send.err",
                array('action' => 'do_order_pay_finish', 'order' => $order, 'iparam1' => $order['order_id']));
            return false;
        }
        //是否已存在订单
        $result = $model->get_third_order($order['order_id']);
        if ($result) {
            \Neigou\Logger::General("b2c_service_orderpayfinished.rpc.third.o2o.send.err",
                array('action' => 'do_order_pay_finish', 'order' => $order, 'iparam1' => $order['order_id']));
            return false;
        }

        $o2o_manage = kernel::single("b2c_o2o_o2omanage");
        $ret        = $o2o_manage->sendCoupon($order);
        \Neigou\Logger::General("b2c_service_orderpayfinished.rpc.do", array(
            'action'  => 'order_pay_finish',
            'order'   => $order,
            'iparam1' => $order['order_id'],
            'result'  => $ret
        ));
        return true;
    }

    /*
     * 订单支付完成优惠券和免邮券的使用
     */
    public function order_pay_finish_voucher_freeshipping_coupon($order_id)
    {
        \Neigou\Logger::Debug("action.voucher", array("action" => "order_pay_finish", "order" => $order_id));
        $service          = kernel::service('voucher.service');
        $voucher_list     = $service->queryOrderVoucher($order_id);
        $ret              = true;
        $voucher_num_list = array();
        if (is_array($voucher_list) && !empty($voucher_list)) {
            foreach ($voucher_list as $voucher_item) {
                $voucher_num_list[] = $voucher_item['number'];
            }
        }
        if ($ret) {
            $service->exchange_status($voucher_num_list, 'finish', "订单支付");
            \Neigou\Logger::Debug("action.voucher",
                array("action" => "payed", "number" => json_encode($voucher_num_list), "order_id" => $order_id));
        }

        $service->finish_order_for_freeshipping_coupon($order_id);
        return $ret;
    }

    /*
     * 支付成功，是否内购积点
     */
    public function order_pay_finish_pointV2($order_info)
    {
        if (bccomp($order_info['point_amount'] ,'0' ,2) <= 0 || empty($order_info['point_channel'])) {
            return true; // 不用积分确认
        }

        $pointAmount = $order_info['point_amount'];

        $scenePointDb     = app::get('b2c')->model('order_scene_point_info');
        $itemUsePointList = $scenePointDb->getList('*', array('order_id' => $order_info['order_id']));
        if (!$itemUsePointList) {
            \Neigou\Logger::General("b2c_service_orderpayfinished", array(
                'action' => 'order_pay_finish_point',
                'order'  => $order_info['order_id'],
                'ret'    => 'order_scene_point_info => false'
            ));
        }

        $totalPoint      = 0;
        $itemPointAmount = 0;
        $accountList     = array();

        foreach ($itemUsePointList as $item) {
            if (isset($accountList[$item['scene_id']])) {
                $accountList[$item['scene_id']]['point'] += $item['point'];
            } else {
                $accountList[$item['scene_id']] = array(
                    'account' => $item['scene_id'],
                    'point'   => $item['point']
                );
            }
            $totalPoint      += $item['point'];
            $itemPointAmount += $item['money'];
        }

        $point_server = kernel::single('b2c_service_scenepoint');

        $totalMoney = 0;
        foreach ($accountList as &$accountInfo) {
            $accountInfo['money'] = $point_server->point2money(
                $accountInfo['point'],
                $order_info['point_channel'],
                $order_info['member_id'],
                $order_info['company_id'],
                $accountInfo['account']
            );

            $totalMoney += $accountInfo['money'];
        }

        if ($pointAmount - $itemPointAmount > 0.01) {
            \Neigou\Logger::General(
                "b2c_service_orderpayfinished",
                array(
                    'action' => 'order_pay_finish_point',
                    'order'  => $order_info['order_id'],
                    'ret'    => 'pointAmount != itemPointAmount'
                )
            );
            return false;
        }

        $cancel_lock_data = array(
            'company_id'   => $order_info['company_id'],
            'member_id'    => $order_info['member_id'],
            'use_type'     => 'order',
            'use_obj'      => $order_info['order_id'],
            'channel'      => $order_info['point_channel'],
            'point'        => $totalPoint,
            'money'        => $totalMoney,
            'account_list' => array_values($accountList),
            'memo'         => isset($order_info['memo']) ? $order_info['memo'] : '确认订单,订单号:' . $order_info['order_id'],
        );

        $ret = $point_server->confirmLockPoint($cancel_lock_data);
        \Neigou\Logger::General(
            "b2c_service_orderpayfinished",
            array(
                'action' => 'order_pay_finish_point',
                'order'  => $order_info['order_id'],
                'ret'    => $ret ,
                'version'=> 'v2'
            )
        );
        return $ret ;
    }

    /*
     * 支付成功，是否内购积点
     */
    public function order_pay_finish_point($order_info)
    {
        if (bccomp($order_info['point_amount'] ,'0' ,2) <= 0 || empty($order_info['point_channel'])) {
            return true; // 不用进行积分确认
        }
        $point_server = kernel::single('b2c_service_scenepoint');
        $channelInfo  = $point_server->pointInfo($order_info['point_channel'], $order_info['member_id'],
            $order_info['company_id']);
        if ($channelInfo['point_version'] == 2) {
            $ret =  $this->order_pay_finish_pointV2($order_info);
            return $ret ;
        }

        $point_server     = kernel::single('b2c_service_point');
        $cancel_lock_data = array(
            'company_id' => $order_info['company_id'],
            'member_id'  => $order_info['member_id'],
            'use_type'   => 'order',
            'use_obj'    => $order_info['order_id'],
            'channel'    => $order_info['point_channel'],
        );
        $ret              = $point_server->confirmLockPoint($cancel_lock_data);
        \Neigou\Logger::General("b2c_service_orderpayfinished",
            array('action' => 'order_pay_finish_point', 'order' => $order_info['order_id'], 'ret' => $ret ,'version'=> 'v1'));
        return $ret ;
    }


    /**
     * 兜礼订单通知
     * @param $order
     */
    public function notify_douli_order($order)
    {
        kernel::single("b2c_douli_notify")->sync_order($order);
    }

    public function order_pay_finish_dutyfree_coupon($order_id)
    {
        $dutyfree_server = kernel::single('b2c_service_dutyfree');
        $ret             = $dutyfree_server->ConfirmLockDutyFree($order_id);
        \Neigou\Logger::General("b2c_service_orderpayfinished",
            array('action' => 'order_pay_finish_dutyfree_coupon', 'order' => $order_id, 'ret' => $ret));
    }


    /**
     * 支付成功后续处理流程汇总
     * @param $order
     */
    public function order_confirm_finish($order)
    {
        $this->o2o_order_pay_finish($order);//之前是注释的，我现在要打开
//        $flag = $this->do_o2o_order_pay_finish($order);
        if ($order['system_code'] == 'mvp') { // mvp在订单确认完成券，商城在订单支付完成券
            $this->order_pay_finish_voucher_freeshipping_coupon($order['order_id']);

            //$this->notify_douli_order($order);
            $this->order_pay_finish_dutyfree_coupon($order['order_id']);

        }

        // 发票确认
        $this->_order_pay_finish_invoice_confirm($order['order_id']);
    }

    /**
     * 订单支付发票确认
     * @param   $orderId    string      订单ID
     * @return  void
     */
    private function _order_pay_finish_invoice_confirm($orderId)
    {
        $ret       = \Neigou\ApiClient::doServiceCall('order', 'Order/Get', 'v1', null, array('order_id' => $orderId));
        $orderData = array();
        if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            $orderData = $ret['service_data']['data'];
        }

        if (empty($orderData['extend_data']['invoice_service_info'])) {
            return;
        }

        $orderIds = array();

        if (!empty($orderData['split_orders'])) {
            foreach ($orderData['split_orders'] as $v) {
                $orderIds[] = $v['order_id'];
            }
        } else {
            $orderIds[] = $orderData['order_id'];
        }

        $orderIds = implode(',', $orderIds);
        // 发票支付确认
        \Neigou\ApiClient::doServiceCall('order', 'Order/Invoice/Confirm', 'v1', null, array('order_id' => $orderIds));
    }

}

?>
