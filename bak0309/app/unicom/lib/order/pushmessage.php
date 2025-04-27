<?php

use OSS\OssClient;
use OSS\Core\OssException;
/**
 * 联通订单推送信息 
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
class unicom_order_pushmessage extends unicom_order_abstract {
    /**
     * 更新发货单(此方法联通未实现)
     */
    private function updateDeliveryOrder($orderNo, $update_delivery_order) {
        $sendType = 2;
        $state = 1;
        foreach ($update_delivery_order as $item) {
            $sendOrderInfo = array();
            $goods_list = $item['send_order_items'];
            $send_order_no = $item['send_order_no'];
            $p_sendOrderNo = $item['p_sendOrderNo'];
            $sendOrderInfo[] = $this->packagingData($send_order_no, $goods_list, $sendType, $state,$p_sendOrderNo);

            $process_result = $this->pushOrderSendDeliveryInfo($orderNo, $sendOrderInfo);
            if ($process_result['ErrorId'] != 10000) {
                return $process_result;
            }

            $params = array();
            $params['status'] = 'NORMAL';
            $params['push_status'] = 2;
            $id = $item['id'];
            $ret = app::get('unicom')->model('order')->updateDeliverOrder($id, $params);
            Neigou\Logger::General('unicom.deliveryOrder.updateDeliverOrder', array('id' => $id, 'params' => $params, 'ret' => $ret));
        }

        return $this->makeMsg(10000, '更新发货单成功');
    }

    /**
     * 作废发货单
     */
    private function cancelDeliveryOrder($orderNo, $cancel_delivery_order) {
        $sendType = 3;
        $state = 2;
        foreach ($cancel_delivery_order as $item) {
            
            $goods_list = $item['send_order_items'];
            $send_order_no = $item['send_order_no'];
            $p_sendOrderNo = $item['p_sendOrderNo'];
            
            //兼容旧逻辑
            if(strlen($p_sendOrderNo) > 0){
                $sendOrderInfo = array();
                $sendOrderInfo[] = $this->packagingData($send_order_no, $goods_list, $sendType, $state,$p_sendOrderNo);
                $process_result = $this->pushOrderSendDeliveryInfo($orderNo, $sendOrderInfo);
                if ($process_result['ErrorId'] != 10000) {
                    return $process_result;
                }
                $params = array();
                $params['status'] = 'CANCEL';
                $params['push_status'] = 2;
                $id = $item['id'];
                $ret = app::get('unicom')->model('order')->updateDeliverOrder($id, $params);
                Neigou\Logger::General('unicom.deliveryOrder.cancelDeliveryOrder', array('id' => $id, 'params' => $params, 'ret' => $ret));
            }
            
            //以SKU发货单
            else{
                $push_succ_count = 0;
                //以单个sku推送发货单信息
                foreach($goods_list as $sku_info){
                    $temp_goods_list = array();
                    $temp_goods_list[] = $sku_info;
                    $send_order_no_with_sku = $send_order_no.'_'.$sku_info['bn'];
                    $delivery_order_info_with_sku = app::get('unicom')->model('order')->getDeliveryOrderWithSku($send_order_no_with_sku);
                    if(!empty($delivery_order_info_with_sku)){
                        $p_sendOrderNo = $delivery_order_info_with_sku['p_sendOrderNo'];
                        if($delivery_order_info_with_sku['push_status'] == 'NORMAL' && $delivery_order_info_with_sku['push_status'] == 2){
                            $sendOrderInfo = array();
                            $sendOrderInfo[] = $this->packagingData($send_order_no_with_sku, $temp_goods_list, $sendType, $state,$p_sendOrderNo);
                            $process_result = $this->pushOrderSendDeliveryInfo($orderNo, $sendOrderInfo);
                            if ($process_result['ErrorId'] != 10000) {
                                continue;
                            }
                            $params = array();
                            $params['status'] = 'CANCEL';
                            $params['push_status'] = 2;
                            $id = $delivery_order_info_with_sku['id'];
                            $ret = app::get('unicom')->model('order')->updateDeliverOrderBySku($id, $params);
                            Neigou\Logger::General('unicom.deliveryOrder.updateDeliverOrderBySku', array('id' => $id, 'params' => $params, 'ret' => $ret));
                            if($ret){
                                $push_succ_count += 1;
                            }
                        }  
                    }else{
                        continue;
                    }
                }
                
                //更新联通发货单号到本地
                if ($push_succ_count > 0) {
                    $params = array();
                    $params['status'] = 'CANCEL';
                    $params['push_status'] = 2;
                    $id = $item['id'];
                    $ret = app::get('unicom')->model('order')->updateDeliverOrder($id, $params);
                    Neigou\Logger::General('unicom.deliveryOrder.cancelDeliveryOrder', array('id' => $id, 'params' => $params, 'ret' => $ret));
                }
                 
            }

        }

        return $this->makeMsg(10000, '作废发货单成功');
    }

    /**
     * 新建发货单物流信息
     */
    private function createDeliveryOrder($orderNo, $new_create_delivery_order) {
        //统一在写入时序列化
        foreach($new_create_delivery_order as $k=>$row){
            $new_create_delivery_order[$k]['send_order_items'] = serialize($row['send_order_items']);
        }
        $ret = app::get('unicom')->model('order')->insertDeliveryOrder($new_create_delivery_order);
        if (FALSE === $ret) {
            Neigou\Logger::General('unicom.deliveryOrder.createDeliveryOrder', array('new_create_delivery_order' => $new_create_delivery_order, 'ret' => $ret));
            return $this->makeMsg(60221, '保存发货单推送信息失败');
        }

        $sendType = 1;
        $state = 1;
        foreach ($new_create_delivery_order as $item) {
            $send_order_no = $item['send_order_no'];
           
            $delivery_order_info = app::get('unicom')->model('order')->getDeliveryOrder($send_order_no);

            $goods_list = unserialize($delivery_order_info['send_order_items']);
            
            $push_succ_count = 0;
            //以单个sku推送发货单信息
            foreach($goods_list as $sku_info){
                $temp_goods_list = array();
                $temp_goods_list[] = $sku_info;
                $sendOrderInfo = array();
                $send_order_no_with_sku = $send_order_no.'_'.$sku_info['bn'];
                $sendOrderInfo[] = $this->packagingData($send_order_no_with_sku, $temp_goods_list, $sendType, $state);

                $process_result = $this->pushOrderSendDeliveryInfo($orderNo, $sendOrderInfo);
                if ($process_result['ErrorId'] != 10000) {
                    continue;
                }

                $p_send_order_no = '';
                //单条返回结构
                $p_send_order_no = $process_result['Data']['p_sendOrderNo'];
                /*
                foreach ($process_result['Data'] as $item_row) {
                    $p_send_order_no = $item_row['sendOrderNo'];
                    break;
                }*/
                
                $params = array();
                $params['platform_order_id'] = $delivery_order_info['platform_order_id'];
                $params['send_order_no'] = $delivery_order_info['send_order_no'];
                $params['send_order_items'] = serialize($temp_goods_list);
                $params['send_order_no_with_sku'] = $send_order_no_with_sku;
                $params['p_send_order_no'] = $p_send_order_no;
                $params['push_status'] = 2;
                $ret = app::get('unicom')->model('order')->insertDeliveryOrderBySku(array($params));
                Neigou\Logger::General('unicom.deliveryOrder.insertDeliveryOrderBySku', array('params' => $params, 'ret' => $ret));
                if($ret){
                    $push_succ_count += 1;
                }
            }
            
            //更新联通发货单号到本地
            if ($push_succ_count > 0) {
                $id = $delivery_order_info['id'];
                $params = array();
                //$params['p_send_order_no'] = $p_send_order_no;
                $params['push_status'] = 2;
                $ret = app::get('unicom')->model('order')->updateDeliverOrder($id, $params);
                Neigou\Logger::General('unicom.deliveryOrder.updateDeliverOrder', array('id' => $id, 'params' => $params, 'ret' => $ret));
            }
        }

        return $this->makeMsg(10000, '保存发货单推送信息');
    }
    
    /**
     * 重试推送发货单号
     */
    public function retryPushFailDeliveryOrders()
    {
        //1.首次推送失败
        $sendType = 1;
        $state = 1;
        $filter = array();
        $results = app::get('unicom')->model('order')->getPushFailDeliveryOrders($filter);
        echo '待处理数据:'.count($results).PHP_EOL;
        foreach($results as $item){
            $send_order_no = $item['send_order_no'];
            $delivery_order_info = app::get('unicom')->model('order')->getDeliveryOrder($send_order_no);

            $goods_list = unserialize($delivery_order_info['send_order_items']);
            $push_succ_count = 0;
            foreach($goods_list as $sku_info){
                $temp_goods_list = array();
                $temp_goods_list[] = $sku_info;
                $sendOrderInfo = array();
                $send_order_no_with_sku = $send_order_no.'_'.$sku_info['bn'];
                $sendOrderInfo[] = $this->packagingData($send_order_no_with_sku, $temp_goods_list, $sendType, $state);

                $root_pid = $item['platform_order_id'];
                $orderInfo = app::get('unicom')->model('order')->getInfoByOrderId($root_pid);
                if (empty($orderInfo)) {
                    continue;
                }
                $orderNo = $orderInfo['orderNo'];
                $process_result = $this->pushOrderSendDeliveryInfo($orderNo, $sendOrderInfo);
                //处理成功保存数据
                if ($process_result['ErrorId'] == 10000) {
                    $p_send_order_no = '';
                    //单条目返回结构
                    $p_send_order_no = $process_result['Data']['p_sendOrderNo'];

                    //推送多条返回结构
                    /*
                    foreach ($process_result['Data'] as $item_row) {
                        $p_send_order_no = $item_row['sendOrderNo'];
                        break;
                    }*/
                    //
                    $params = array();
                    $params['platform_order_id'] = $delivery_order_info['platform_order_id'];
                    $params['send_order_no'] = $delivery_order_info['send_order_no'];
                    $params['send_order_no_with_sku'] = $send_order_no_with_sku;
                    $params['send_order_items'] = serialize($temp_goods_list);
                    $params['p_send_order_no'] = $p_send_order_no;
                    $params['push_status'] = 2;
                    $ret = app::get('unicom')->model('order')->insertDeliveryOrderBySku(array($params));
                    Neigou\Logger::General('unicom.deliveryOrder.insertDeliveryOrderBySku', array('params' => $params, 'ret' => $ret));
                    if($ret){
                        $push_succ_count += 1;
                    }
                }
                
                Neigou\Logger::General('unicom.deliveryOrder.retryPushFailDeliveryOrders', array('send_order_no_with_sku'=>$send_order_no_with_sku,'process_result' => $process_result));
                echo '$send_order_no_with_sku:'.$send_order_no_with_sku.PHP_EOL;
                var_dump($process_result);
            }
            
            //更新联通发货单号到本地
            if ($push_succ_count > 0) {
                $id = $delivery_order_info['id'];
                $params = array();
                //$params['p_send_order_no'] = $p_send_order_no;
                $params['push_status'] = 2;
                $ret = app::get('unicom')->model('order')->updateDeliverOrder($id, $params);
                Neigou\Logger::General('unicom.deliveryOrder.retryPushFailDeliveryOrders', array('id' => $id, 'params' => $params, 'ret' => $ret));
            }
        }
        echo '已处理完毕'.PHP_EOL;
    }

    /**
     * 处理发货推送逻辑
     * @param  [type] $orderId [内部ID]
     * @return [type]          [description]
     */
    public function deliveryOrder($orderId) {
        //通过子订单获取主订单
        $platformOrderInfo = kernel::single("unicom_service_order_order")->getOrderInfo($orderId);
        if (empty($platformOrderInfo)) {
            return $this->makeMsg(60201, '订单数据未找到');
        }
        //获取主订单号
        $root_pid = $platformOrderInfo['root_pid'];
        // 获取联通订单数据
        $orderInfo = app::get('unicom')->model('order')->getInfoByOrderId($root_pid);
        if (empty($orderInfo)) {
            return $this->makeMsg(10000, '非联通订单直接丢弃');
        }
        $platform_order_id = $orderInfo['platform_order_id'];
        if($root_pid != $orderId){
            $platformOrderInfo = kernel::single("unicom_service_order_order")->getOrderInfo($platform_order_id);
        }
        //读取发货的子订单并取消拆单的订单
        $delivery_orders = app::get('unicom')->model('order')->getDeliveryOrders($platform_order_id);

        $new_create_delivery_order = array();
        $cancel_delivery_order = array();
        //$update_delivery_order = array();

        //如果无子单
        if (empty($platformOrderInfo['split_orders'])) {
            $ship_status = $platformOrderInfo['ship_status'];
            if (in_array($platformOrderInfo['status'],array(1,3)) && in_array($ship_status, array(2, 3))) { //'发货状态 1：未发货 2：已发货 3：已收货 4：已退货',
                if($platformOrderInfo['split'] == 2){
                    return $this->makeMsg(60228, '只有一个订单且被分割，停止推送');
                }
                $send_order_no = $platformOrderInfo['order_id'];
                $is_exists = FALSE;
                foreach ($delivery_orders as $delivery_order) {
                    if ($send_order_no == $delivery_order['send_order_no']) {
                        $is_exists = TRUE;
                        //当前发货单只要不作废,无需更新数据
                        /*
                        if ($delivery_order['push_status'] == 2 && $delivery_order['status'] == 'NORMAL' && !empty($delivery_order['p_send_order_no'])) {
                            $update_delivery_order[] = array(
                                'id' => $delivery_order['id'],
                                'p_send_order_no' => $delivery_order['p_send_order_no'],
                                'send_order_items' => unserialize($delivery_order['send_order_items']),
                                'send_order_no' => $delivery_order['send_order_no'],
                            );
                        }*/
                    } else {
                        //作废旧发货单
                        if ($delivery_order['push_status'] == 2 && $delivery_order['status'] == 'NORMAL') {
                            $cancel_delivery_order[] = array(
                                'id' => $delivery_order['id'],
                                'p_send_order_no' => $delivery_order['p_send_order_no'],
                                'send_order_items' => unserialize($delivery_order['send_order_items']),
                                'send_order_no' => $delivery_order['send_order_no'],
                            );
                        }
                    }
                }

                //作废发货单
                if (!empty($cancel_delivery_order)) {
                    $process_result = $this->cancelDeliveryOrder($orderInfo['orderNo'], $cancel_delivery_order);
                }
                
                if ($is_exists) {
                    //当前发货单只要不作废,无需更新数据
                    /*
                    if(!empty($update_delivery_order)){
                        $this->updateDeliveryOrder($orderInfo['orderNo'], $update_delivery_order);
                    }*/
                    return $this->makeMsg(10000, '此发货单已推送');
                }
                //写入推送信息
                $new_create_delivery_order[] = array(
                    'platform_order_id' => $platform_order_id,
                    'send_order_no' => $send_order_no,
                    'status' => 'NORMAL',
                    'send_order_items' => $platformOrderInfo['items'],
                );
                //新建发货单
                if (!empty($new_create_delivery_order)) {
                    $process_result = $this->createDeliveryOrder($orderInfo['orderNo'], $new_create_delivery_order);
                }
                return $process_result;
            }
        } else {
            $curent_delivery_success_son_orders = array();
            foreach ($platformOrderInfo['split_orders'] as $son_order) {
                if($son_order['split'] == 2){
                    continue;
                }
                if (in_array($son_order['status'],array(1,3)) && in_array($son_order['ship_status'], array(2, 3))) {
                    $key = $son_order['order_id'];
                    $curent_delivery_success_son_orders[$key] = $son_order;
                }
            }

            $current_success_delivery_order_ids = array_keys($curent_delivery_success_son_orders);
            $delivery_order_send_order_nos = array();
            foreach ($delivery_orders as $delivery_order) {
                if (!in_array($delivery_order['send_order_no'], $current_success_delivery_order_ids)) {
                    //作废旧发货单
                    if ($delivery_order['push_status'] == 2 && $delivery_order['status'] == 'NORMAL') {
                        $cancel_delivery_order[] = array(
                            'id' => $delivery_order['id'],
                            'p_send_order_no' => $delivery_order['p_send_order_no'],
                            'send_order_items' => unserialize($delivery_order['send_order_items']),
                            'send_order_no' => $delivery_order['send_order_no'],
                        );
                    }
                } else {
                    //
                }

                $delivery_order_send_order_nos[] = $delivery_order['send_order_no'];
            }


            foreach ($curent_delivery_success_son_orders as $key => $item) {
                if (!in_array($key, $delivery_order_send_order_nos)) {
                    $new_create_delivery_order[] = array(
                        'platform_order_id' => $platform_order_id,
                        'send_order_no' => $item['order_id'],
                        'status' => 'NORMAL',
                        'send_order_items' =>$item['items'],
                    );
                } else {
                    //
                }
            }

            //作废发货单
            if (!empty($cancel_delivery_order)) {
                $process_result = $this->cancelDeliveryOrder($orderInfo['orderNo'], $cancel_delivery_order);
            }

            //新建发货单
            if (!empty($new_create_delivery_order)) {
                $process_result = $this->createDeliveryOrder($orderInfo['orderNo'], $new_create_delivery_order);
            }
            return isset($process_result) ? $process_result : $this->makeMsg(60224,'无待处理数据');
        }

        return $this->makeMsg(60223, '订单发货状态异常,跳过处理');
    }

    /**
     * 售后推送接口
     * @param  [type] $after_sale_bn [description]
     * @return [type]                [description]
     */
    public function pushReturnOrder($after_sale_bn) {

        $afterInfo = app::get('unicom')->model('order')->getReturnInfoByid($after_sale_bn);
        if (empty($afterInfo)) {
            return $this->makeMsg('60012', '未找到退货信息');
        }

        $untreadOrderNo = $afterInfo['untreadOrderNo'];
        $param = array(
            'untreadOrderNo' => $untreadOrderNo,
            'untreadResult' => 0,
            'reason' => '',
            'dealUser' => '客服',
            'dealTime' => date('Y-m-d H:i:s'),
        );

        //查找分拆的多个售后单状态
        $messageList = app::get('unicom')->model('order')->searchReturnOrder($untreadOrderNo);
        if (empty($messageList)) {
            return $this->makeMsg('60405', '未找到退货信息');
        }


        $untreadResult_finish_count = 0;
        $untreadResult_reject_count = 0;
        
        $max_update_time = 0;
        foreach ($messageList as $info) {
            $after_sale_bn = $info['return_id'];
            $after_sale_info = kernel::single("unicom_order_customer")->getReturns($after_sale_bn);
            if (empty($after_sale_info)) {
                return $this->makeMsg('60012', '未找到退货信息');
            }

            /*
             * 退货状态
             *  1	申请退货
                2	通过申请
                3	拒绝申请
                4	商品寄回仓库
                5	退款商品入库
                6	售后完成
                7	拒绝商品入库
                8	已取消
                9	重新提交退款
                10	退款审核中
                11	换货商品寄回客户
                12	重新提交入库
                13	拒绝退款
                14	换货商品入库
             */
            if ($after_sale_info['status'] == 6
                    /*|| 
                    $after_sale_info['status'] == 2 || 
                    $after_sale_info['status'] == 4 || 
                    $after_sale_info['status'] == 5 || 
                    $after_sale_info['status'] == 9 || 
                    $after_sale_info['status'] == 10 ||
                    $after_sale_info['status'] == 11 || 
                    $after_sale_info['status'] == 12 || 
                    $after_sale_info['status'] == 14 
                     * 
                     */
                    ) {
                $untreadResult_finish_count += 1;
                $reason = strlen($after_sale_info['service_reason']) > 0 ? $after_sale_info['service_reason'] : '';
            } elseif (in_array($after_sale_info['status'], array(3, 7, 8, 13))) {
                $untreadResult_reject_count += 1;
                // 未受理成功
                $reason = strlen($after_sale_info['service_reason']) > 0 ? $after_sale_info['service_reason'] : '拒绝申请';
            }
            else {
                $reason = '售后状态异常，请联系客服';
            }
            
            if($after_sale_info['update_time'] > $max_update_time){
                $max_update_time = $after_sale_info['update_time'];
            }
        }

        $message_list_count = count($messageList);
        Neigou\Logger::General('unicom.pushReturnOrder', array(
            'after_sale_bn' => $after_sale_bn,
            'untreadOrderNo' => $untreadOrderNo,
            'messageList' => $messageList,
            'message_list_count' => $message_list_count,
            'untreadResult_finish_count' => $untreadResult_finish_count,
            'untreadResult_reject_count' => $untreadResult_reject_count));

        //部分拒绝即整个售后单驳回
        $reason = strlen($reason) > 0 ? $reason : '';
        $param['dealTime'] = date('Y-m-d H:i:s',$max_update_time);
        if ($untreadResult_reject_count > 0) {
            $param['untreadResult'] = 2;
            $param['reason'] = $reason;
        } elseif ($untreadResult_finish_count == $message_list_count) {
            $param['untreadResult'] = 1;
            $param['reason'] = $reason;
        } else {
            return $this->makeMsg(10000, 'success', '售后单处理中或部分处理中');
        }
        return $this->pushOrderSendAfterSales($param);
    }

    /******************  联通接口对接  *********************** */

    /**
     * 推送订单发货信息
     * @param  [type] $orderNo     [description]
     * @param  [type] $packingList [description]
     * @return [type]              [description]
     */
    public function pushOrderSendDeliveryInfo($orderNo, $sendOrderInfo) {
        $errMsg = '';
        $request = kernel::single('unicom_request');
        $req_data = array('orderNo' => $orderNo, 'sendOrderInfo' => json_encode($sendOrderInfo));
        $result = $request->request(array(
            'method' => 'sendDeliveryInfo',
            'data' => $req_data,
        ), $errMsg);
        
        Neigou\Logger::General('unicom.pushOrderSendDeliveryInfo', array('orderNo' => $orderNo, 'sendOrderInfo' => $sendOrderInfo, 'result' => $result));
        // 记录发送数据
        if (!empty($result['success']) || ($result['resultCode'] == 1004 && !empty($result['result']['p_sendOrderNo']))) {
            app::get('unicom')->model('order')->insertPushLog($orderNo, 1, 1, serialize($req_data), serialize($result));
            return $this->makeMsg(10000, 'success', $result['result']);
        } else {
            app::get('unicom')->model('order')->insertPushLog($orderNo, 1, 2, serialize($req_data), serialize($result));
            return $this->makeMsg('60002', (!empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误'));
        }
    }

    public function pushOrderSendAfterSales($info) {
        $errMsg = '';
        $request = kernel::single('unicom_request');
        $result = $request->request(array(
            'method' => 'sendUntreadResult'
            , 'data' => $info
                ), $errMsg);

        $untreadOrderNo = $info['untreadOrderNo'];
        $messageList = app::get('unicom')->model('order')->searchReturnOrder($untreadOrderNo);
        if (empty($messageList)) {
            return $this->makeMsg('60405', '未找到退货信息');
        }
        $orderNo = $messageList[0]['orderNo'];
        if (empty($result['success']) && $result['resultCode'] != 5002) {
            app::get('unicom')->model('order')->insertPushLog($orderNo, 2, 2, serialize($info), serialize($result));
            return $this->makeMsg('60012', (!empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误'));
        }

        app::get('unicom')->model('order')->updateReturnInfo($info['untreadOrderNo'], array('status' => $info['untreadResult'], 'message' => $info['reason']));
        app::get('unicom')->model('order')->insertPushLog($orderNo, 2, 1, serialize($info), serialize($result));
        return $this->makeMsg(10000, 'success', $result['result']);
    }

    /**
     * 生成配置包裹信息
     */
    public function packagingData($sendOrderNo, $goodsList, $sendType = 1, $state = 1,$p_sendOrderNo = '') {
        $sendOrderInfo = array(
            'sendOrderNo' => $sendOrderNo,
            'sendType' => $sendType, //处理类型 1-新增 2-更新 3-作废
            'state' => $state, //发货单状态  1-正常 2-作废 
            'sendState' => 1, //发货状态 1-已发货
            'logisticsType' => 2,
            'logisticsUrl' => $this->makeRequesDeliveryUrl(),
            'packingList' => array(),
        );
        
        if(in_array($sendType,array(2,3))){
            $sendOrderInfo['p_sendOrderNo'] = $p_sendOrderNo;
        }

        if (is_array($goodsList)) {
            foreach ($goodsList as $k => $v) {
                $sendOrderInfo['packingList'][$k]['packingListNo'] = (string) ($k + 1);
                $sendOrderInfo['packingList'][$k]['details'][] = array('sku' => $v['bn'], 'num' => $v['nums']);
            }
        }
        $sendOrderInfo['packingList'] = json_encode($sendOrderInfo['packingList']);
        return $sendOrderInfo;
    }

    /**
     * 生成URL （查询物流使用U采文档第九章 POST 获取物流信息）
     * @param  [type] $logi_code [description]
     * @param  [type] $logi_no   [description]
     * @return [type]            [description]
     */
    public function makeRequesDeliveryUrl() {
        return '';
        //return OPENAPI_DOMAIN . '/ChannelInterop/V1/Unicom/Web/Dispatch';
        //return urlencode(OPENAPI_DOMAIN.'/ChannelInterop/V1/Unicom/Web/queryLogisticsInfo?'.http_build_query(array('logi_code' => $logi_code, 'logi_no' => $logi_no, 'sendOrderNo' => $sendOrderNo)));
    }

    /** 联通履约信息推送
     *
     * @param $orderId
     * @return bool
     * @throws Exception
     * @author liuming
     */
    public function pushDeliveredInfoByPlatformOrderId($orderId){


        /**  根据订单服务获取订单信息, 查看当前订单是不是主订单,如果是主订单, 获取下面子订单 */
        $platformOrderInfo = kernel::single("unicom_service_order_order")->getOrderInfo($orderId);
        if (empty($platformOrderInfo)){
            return false;
        }

        // 设置主订单号
        $rootOrderId = $platformOrderInfo['root_pid'];
        $this->doPushDeliveredInfo($rootOrderId,$platformOrderInfo);

//        /** 设置子订单信息 */
//        if ($platformOrderInfo['split'] == 2){//是否拆单 1:未拆分 2：已拆分
//            $splitOrderInfoList =  $platformOrderInfo['split_orders'];
//        }else{
//            $splitOrderInfoList[$rootOrderId] = $platformOrderInfo;
//        }
//
//        foreach ($splitOrderInfoList as $splitOrderV){
//            $this->doPushDeliveredInfo($rootOrderId,$splitOrderV);
//        }

    }


    /** 根据平台订单id给联通推送订单完成信息
     *
     * @param $rootOrderId 该订单的主订单id, 如果该订单是主订单, 那么这个id就是自身
     * @param $platformOrderInfo 订单信息
     * @return bool
     * @throws Exception
     * @author liuming
     */
    public function doPushDeliveredInfo($rootOrderId,$platformOrderInfo){
        $orderId = $platformOrderInfo['order_id'];
        $orderModel = app::get('unicom')->model('order');
        /**  查询该订单是否属于联通 */
        $orderDetailRes = $orderModel->getInfoRawByPlatformOrderId($rootOrderId);
        if (empty($orderDetailRes)){
            return false;
        }

        /** 查询履约过的列表 */
        $deliveryList = $orderModel->getDeliveryOrderPushListBySendOrderId($orderId);
        if (!$deliveryList){
            return false;
        }

        foreach ($deliveryList as $deliveryInfo){
            try{

                /** 如果该订单推送过, 那么将跳过 */
                $deliveryExpressInfoRes = $orderModel->getDeliveryExpressInfoByPSendOrderNo($deliveryInfo['p_send_order_no']);
                if ($deliveryExpressInfoRes){
                    throw new Exception('该平台信息已经推送过',99990);
                }

                /** 获取快递信息 */
                // 1.获取快递单号
                $expressNo = $platformOrderInfo['logi_no'];
                // 2.通过快递单号获取快递信息
                $expressModel = app::get('unicom')->model('express');
                $expressInfo = $expressModel->getExpressInfoOne($expressNo);
                if (empty($expressInfo)){
                    throw new Exception('查询快递信息失败',80003);
                }

                //匹配出快递员姓名, 快递员手机
                $expresData = unserialize($expressInfo['data']);
                if (!$expresData['data']){
                    throw new Exception('快递信息不存在');

                }
                $courierInfo = $this->getCourierInfo2($expresData['data'],$expresData['com']);
                if (empty($courierInfo['name']) || strlen($courierInfo['name']) > 15){
                    $courierInfo['name'] = '快递员';
                    //$courierInfo = $this->getCourierInfo($expresData['data'],$expresData['com']);
                }

                if (empty($courierInfo['name'])){
                    throw new Exception('快递员姓名不能为空',80004);
                }

                if (empty($courierInfo['mobile'])){
                    //throw new Exception('快递员手机号不存在',80008);
                }

                //将快递信息生成excel
                $fileName = $orderId.'.xls';
                $tmpDir = ROOT_DIR . '/tmp/oss/';
                $this->oprationTmpDir($tmpDir);
                $tmpPath = $tmpDir.$fileName;

                $this->createExcelFile($expresData['data'],$tmpPath,$platformOrderInfo);
                //上传到阿里云oss
                $month = date('Ymd',time());
                $ossRes = $this->upOss('unicom/courierinfo/'.$month.'/'.$fileName,$tmpPath);
                if (empty($ossRes)){
                    throw new Exception('阿里云上传失败',80005);
                }

                @unlink($tmpPath);
                /**  请求到openapi */
                $signMobile = !empty($platformOrderInfo['ship_mobile']) ? $platformOrderInfo['ship_mobile'] : $platformOrderInfo['ship_tel'];
                $requestData = array(
                    'deliveredId' => $deliveryInfo['id'],
                    'orderNo' => $orderDetailRes['orderNo'], //联通订单号
                    'p_sendOrderNo' => $deliveryInfo['p_send_order_no'], //平台发货单号
                    'deliveredName' => $courierInfo['name'], //托投入姓名
                    'deliveredMobile' => empty($courierInfo['mobile']) ? $signMobile : $courierInfo['mobile'], //托投入姓名
                    'deliveredTime' => date('Y-m-d H:i:s',$platformOrderInfo['finish_time']), //托投时间,订单完成时间
                    'remark' => '', //备注
                    'signer' => $platformOrderInfo['ship_name'], //签收人
                    'signMobile' =>  $signMobile, //签收人手机
                    'attachment' => $ossRes['oss-request-url'], //附件
                );

                $errMsg = '';
                $request = kernel::single('unicom_request');
                $result = $request->request(array('method' => 'submitDeliveredInfo', 'data' => $requestData), $errMsg);
                if ($result['success'] != "true"){
                    $type = 2; //推送失败
                }else{
                    $type = 1; //推送成功
                }
                /** 保存请求信息 */
                $orderModel->insertPushLog($orderDetailRes['orderNo'],1,$type,serialize($requestData),serialize($result));
                if ($type != 1){
                    throw new Exception('订单:'.$orderId.'快递信息推送失败,原因: '.$result['resultMessage'],80007);
                }

                $insertExpressRes = $orderModel->insertDeliveryExpressInfo($requestData);
                if (empty($insertExpressRes)){
                    throw new Exception('履约快递信息插入失败!',80006);
                }

            }catch(Exception $e){
                if (!in_array($e->getCode(),array(99990))){ //特殊错误不记录日志
                    \Neigou\Logger::General('unicom_push_express_error',array('order_id'=>$orderId,'orderNo' => $orderDetailRes['orderNo'],'message' => $e->getMessage(),'errorCode' => $e->getCode()));
                }
            }
        }

    }


    /** 获快递员信息
     *
     * @param array $contextArr
     * @return array
     * @author liuming
     */
    public function getCourierInfo($contextArr = array()){
        $courierName = $mobile = '';
        $lastname_arr = array('赵','钱','孙','李','周','吴','郑','王','冯','陈','褚','卫','蒋','沈','韩','杨','朱','秦','尤','许','何','吕','施','张','孔','曹','严','华','金','魏','陶','姜',
            '戚','谢','邹','喻','柏','水','窦','章','云','苏','潘','葛','奚','范','彭','郎','鲁','韦','昌','马','苗','凤','花','方','任','袁','柳','鲍','史','唐','费','薛','雷','贺','倪',
            '汤','滕','殷','罗','毕','郝','安','常','傅','卞','齐','元','顾','孟','平','黄','穆','萧','尹','姚','邵','湛','汪','祁','毛','狄','米','伏','成','戴','谈','宋','茅','庞','熊',
            '纪','舒','屈','项','祝','董','梁','杜','阮','蓝','闵','季','贾','路','娄','江','童','颜','郭','梅','盛','林','钟','徐','邱','骆','高','夏','蔡','田','樊','胡','凌','霍','虞',
            '万','支','柯','管','卢','莫','柯','房','裘','缪','解','应','宗','丁','宣','邓','单','杭','洪','包','诸','左','石','崔','吉','龚','程','嵇','邢','裴','陆','荣','翁','荀','于',
            '惠','甄','曲','封','储','仲','伊','宁','仇','甘','武','符','刘','景','詹','龙','叶','幸','司','黎','溥','印','怀','蒲','邰','从','索','赖','卓','屠','池','乔','胥','闻','莘',
            '党','翟','谭','贡','劳','逄','姬','申','扶','堵','冉','宰','雍','桑','寿','通','燕','浦','尚','农','温','别','庄','晏','柴','瞿','阎','连','习','容','向','古','易','廖','庾',
            '终','步','都','耿','满','弘','匡','国','文','寇','广','禄','阙','东','欧','利','师','巩','聂','关','荆','司马','上官','欧阳','夏侯','诸葛','闻人','东方','赫连','皇甫','尉迟',
            '公羊','澹台','公冶','宗政','濮阳','淳于','单于','太叔','申屠','公孙','仲孙','轩辕','令狐','徐离','宇文','长孙','慕容','司徒','司空','a','b','c','d','e','f','g','h','i','g','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','A','B','C','D','E','F','G','H','I','G','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',"0","1","2","3","4","5","6","7","8","9");
        foreach ($contextArr as $v) {
            if ((strpos($v['context'], '派') !== false) || (strpos($v['context'], '配') !== false) || (strpos($v['context'], '投') !== false)) {
                mb_internal_encoding("UTF-8");
                foreach ($lastname_arr as $cv) {
                    $tmpStr = strstr($v['context'],$cv);
                    if ($tmpStr) {
                        $courierName = mb_substr($tmpStr, 0, 3);
                        // 去掉特殊符合
                        $courierName=urlencode($courierName);//将关键字编码
                        $courierName=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99)+/",'',$courierName);
                        $courierName=urldecode($courierName);//将过滤后的关键字解码

                        // 设置手机号
                        preg_match('/1[345789][0-9]{8,10}/', $v['context'], $match);
                        if(isset($match[0]) && !empty($match[0])){
                            $mobile = $match[0];
                            $recommendName = $courierName;
                        }

                        if ($mobile && $courierName){
                            break 2;
                        }else if ($recommendName){
                            $courierName = $recommendName;
                        }
                    }
                }
            }
        }

        return array(
            'name' => $courierName,
            'mobile' => $mobile
        );
    }

    /** 生成excel
     *
     * @param string $courierInfo
     * @param string $fileName
     * @return bool
     * @author liuming
     */
    private function createExcelFile($courierInfo = '',$fileName = '',$orderInfo = array()){

        if (empty($courierInfo) || empty($fileName)) return false;

        //$rootDir = dirname(__FILE__)."/../../../omeio/lib/static";
        $rootDir = ROOT_DIR."/app/omeio/lib/static";
        define('PHPEXCEL_ROOT',$rootDir);
        require_once PHPEXCEL_ROOT.'/PHPExcel.php';
        require_once PHPEXCEL_ROOT.'/PHPExcel/IOFactory.php';

        $phpExcelObj = new PHPExcel();
        foreach ($courierInfo as $k => $v){
            if ($k == 0){
                $phpExcelObj->getActiveSheet()->setCellValueExplicit('A1','时间');
                $phpExcelObj->getActiveSheet()->setCellValueExplicit('B1','快递信息');
                $phpExcelObj->getActiveSheet()->setCellValueExplicit('C1','快递公司');
                $phpExcelObj->getActiveSheet()->setCellValueExplicit('D1','快递单号');
            }else{
                if ($k == 1){
                    $phpExcelObj->getActiveSheet()->setCellValueExplicit('C'.($k+1),$orderInfo['logi_name']);
                    $phpExcelObj->getActiveSheet()->setCellValueExplicit('D'.($k+1),$orderInfo['logi_no']);
                }
                $phpExcelObj->getActiveSheet()->setCellValueExplicit('A'.($k+1),$v['time']);
                $phpExcelObj->getActiveSheet()->setCellValueExplicit('B'.($k+1),$v['context']);
            }
        }
        $objWriter = PHPExcel_IOFactory::createWriter($phpExcelObj, 'Excel5');
        $objWriter->save($fileName);
    }

    /** oss 上传文件
     *
     * @param string $remoteFilePath
     * @param string $localFilePath
     * @return bool
     * @author liuming
     */
    public function upOss($remoteFilePath = '',$localFilePath = ''){
        if (empty($remoteFilePath) || empty($localFilePath)){
            echo '路径不能为空';
            return false;
        }

        require_once ROOT_DIR . '/plugin/aliyun-oss-php-sdk-2.3.0.phar';
        $ossClient = new OSS\OssClient(OSS_ASSESS_KEY_ID, OSS_ACCESS_KEY_SECRET, OSS_ENDPOINT);
        $ossRes = array();
        try {
            $ossRes = $ossClient->uploadFile(OSS_BUCKET, $remoteFilePath, $localFilePath);
        } catch (OSS\Core\OssException $e) {
              \Neigou\Logger::Debug('unicom_push_message_error',array('remote_file' => $remoteFilePath,'local_file' => $localFilePath,'error_msg' => $e->getMessage(),'report_name' => 'ali_oss_error'));
        }
        return $ossRes;
    }


    private function oprationTmpDir($tmpDir = ''){
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0777, true);
        }
    }

    public function getCourierInfo2($contextArr = array(),$com = ''){
        $courierName = '';

        foreach ($contextArr as $v){
            if ((strpos($v['context'],'派') !== false) || (strpos($v['context'],'配') !== false)){
                // EMS,一号店
                if ($com == 'ems' || $com == 'yihaodian'){
                    $tmpStr = strstr($v['context'],'：');
                    $tmpArr = explode('：',current(explode(';',$tmpStr)));
                    $courierName = end($tmpArr);
                }

                //顺丰
                if ($com == 'shunfeng'){
                    $tmpStr = strstr($v['context'],'派件人:');
                    $tmpArr = explode(':',current(explode(',',$tmpStr)));
                    $courierName = end($tmpArr);
                }

                //韵达
                if ($com == 'yunda'){
                    $tmpStr = strstr($v['context'],'：');
                    $tmpArr = explode('：',current(explode('；',$tmpStr)));
                    $courierName = end($tmpArr);
                }

                //宅急送
                if ($com == 'zhaijisong'){
                    preg_match_all("/\[(.*?)\]/i",$v['context'], $result);
                    if (!empty($result[1])){
                        $courierName = $result[1][1];
                    }
                }

                //圆通
                if ($com == 'yuantong'){
                    $tmpStr = strstr($v['context'],':');
                    $tmpArr = explode(':',current(explode(' ',next(explode(' ',$tmpStr)))));
                    $courierName = end($tmpArr);
                }

                //申通
                if ($com == 'shentong'){
                    $tmpStr = strstr($v['context'],' ');
                    $tmpArr = explode(' ',$tmpStr);
                    $courierName = $tmpArr[2];
                }

                //汇通快递
                if ($com == 'huitongkuaidi'){
                    //【上海嘉定区十五部001】，【谢发银/18717921086】正在派件
                    preg_match_all("/【(.*?)】/i",$v['context'], $result);
                    $courierName = current(explode('/',end(end($result))));
                }

                //中通快递
                if ($com == 'zhongtong'){
                    // [大连市]大连西山水库分部的(13364116052) 荆庆国(15140456321) 正在派件 网点派件扫描-Physical delivery scheduled.
                    $tmpStr = strstr($v['context'],' ');
                    $tmpArr = explode(' ',$tmpStr);
                    $courierName = $tmpArr[1];
                }

                //优顺物流,快捷速递
                if ($com == 'youshuwuliu' || $com == 'kuaijiesudi'){
                    preg_match_all("/【(.*?)】/i",$v['context'], $result);
                    if (!empty($result[1])){
                        $courierName = end($result[1]);
                    }
                }

                //天天快递
                if ($com == 'tiantian'){
                    $tmpStr = strstr($v['context'],'的');
                    mb_internal_encoding("UTF-8");
                    $courierName = mb_substr($tmpStr,1,3);
                }

                //京东
                if ($com == 'jd'){
                    $tmpArr = explode('，',$v['context']);
                    $courierName = $tmpArr[3];
                }

                // 如果没有匹配过进行匹配
                if (!isset($newCourierName) || empty($newCourierName)){
                    if ($courierName){
                        $newCourierName = $this->getCourierNmae($courierName);
                    }
                }

                // 没有手机号进行匹配
                if (!isset($mobile) || empty($mobile)){
                    // 设置手机号
                    preg_match('/1[345789][0-9]{8,10}/', $v['context'], $match);
                    if(isset($match[0]) && !empty($match[0])){
                        $mobile = $match[0];
                    }else{
                        //匹配固定电话
                        $isTel="/^([0-9]{3,4}-)?[0-9]{7,8}$/";
                        preg_match($isTel, $v['context'], $match);
                        if (isset($match[0]) && !empty($match[0])){
                            $mobile = $match[0];
                        }
                    }
                }

                // 有手机号并且有姓名break
                if (!empty($newCourierName) && !empty($mobile)){
                    break;
                }

            }

        }
        return array(
            'name' => $newCourierName,
            'mobile' => $mobile
        );
    }


    public function getCourierNmae($name)
    {
        if(empty($name)) return '';
        $lastname_arr = array('赵', '钱', '孙', '李', '周', '吴', '郑', '王', '冯', '陈', '褚', '卫', '蒋', '沈', '韩', '杨', '朱', '秦', '尤', '许', '何', '吕', '施', '张', '孔', '曹', '严', '华', '金', '魏', '陶', '姜',
            '戚', '谢', '邹', '喻', '柏', '水', '窦', '章', '云', '苏', '潘', '葛', '奚', '范', '彭', '郎', '鲁', '韦', '昌', '马', '苗', '凤', '花', '方', '任', '袁', '柳', '鲍', '史', '唐', '费', '薛', '雷', '贺', '倪',
            '汤', '滕', '殷', '罗', '毕', '郝', '安', '常', '傅', '卞', '齐', '元', '顾', '孟', '平', '黄', '穆', '萧', '尹', '姚', '邵', '湛', '汪', '祁', '毛', '狄', '米', '伏', '成', '戴', '谈', '宋', '茅', '庞', '熊',
            '纪', '舒', '屈', '项', '祝', '董', '梁', '杜', '阮', '蓝', '闵', '季', '贾', '路', '娄', '江', '童', '颜', '郭', '梅', '盛', '林', '钟', '徐', '邱', '骆', '高', '夏', '蔡', '田', '樊', '胡', '凌', '霍', '虞',
            '万', '支', '柯', '管', '卢', '莫', '柯', '房', '裘', '缪', '解', '应', '宗', '丁', '宣', '邓', '单', '杭', '洪', '包', '诸', '左', '石', '崔', '吉', '龚', '程', '嵇', '邢', '裴', '陆', '荣', '翁', '荀', '于',
            '惠', '甄', '曲', '封', '储', '仲', '伊', '宁', '仇', '甘', '武', '符', '刘', '景', '詹', '龙', '叶', '幸', '司', '黎', '溥', '印', '怀', '蒲', '邰', '从', '索', '赖', '卓', '屠', '池', '乔', '胥', '闻', '莘',
            '党', '翟', '谭', '贡', '劳', '逄', '姬', '申', '扶', '堵', '冉', '宰', '雍', '桑', '寿', '通', '燕', '浦', '尚', '农', '温', '别', '庄', '晏', '柴', '瞿', '阎', '连', '习', '容', '向', '古', '易', '廖', '庾',
            '终', '步', '都', '耿', '满', '弘', '匡', '国', '文', '寇', '广', '禄', '阙', '东', '欧', '利', '师', '巩', '聂', '关', '荆', '司马', '上官', '欧阳', '夏侯', '诸葛', '闻人', '东方', '赫连', '皇甫', '尉迟',
            '公羊', '澹台', '公冶', '宗政', '濮阳', '淳于', '单于', '太叔', '申屠', '公孙', '仲孙', '轩辕', '令狐', '徐离', '宇文', '长孙', '慕容', '司徒', '司空', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'g', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'G', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");

        foreach ($lastname_arr as $cv) {
            $tmpStr = strstr($name, $cv);
            if ($tmpStr) {
                $courierName = mb_substr($tmpStr, 0, 3);
                // 去掉特殊符合
                $courierName = urlencode($courierName);//将关键字编码
                $courierName = preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99)+/", '', $courierName);
                $courierName = urldecode($courierName);//将过滤后的关键字解码
            }
        }
        return $name;
    }

}
