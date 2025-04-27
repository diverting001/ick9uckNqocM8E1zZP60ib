<?php
/**
 * 联通订单处理类 
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
class unicom_order_handle extends unicom_order_abstract
{

    /***************************  对外联通接口  **********************/
    /**
     * 获取联通订单信息
     * @param  [type] $orderNo [description]
     * @return [type]          [description]
     */
    public function getUnicomOrderInfo ($orderNo)
    {
        if (empty($orderNo)) {
            return $this->makeMsg('60001', '订单编号不能为空');
        }
        $errMsg = '';
        $request = kernel::single('unicom_request');
        $result = $request->request(array(
            'method' => 'queryOrderInfo'
            ,'data'  => array('orderNo'  => $orderNo)
        ), $errMsg);

        if ($result === false) {
            return $this->makeMsg('60002', $errMsg);
        }

        if ($result['success'] != 'true') {
            return  $this->makeMsg('60002',(!empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误'));
        }

        return $this->makeMsg(10000,'success', $result['result']);
    }

    /**
     * 获取退货信息
     * @param  [type] $untreadOrderNo [description]
     * @return [type]                 [description]
     */
    public function getUnicomReturnOrderInfo ($untreadOrderNo)
    {
        $errMsg = '';
        $request = kernel::single('unicom_request');
        $result = $request->request(array(
            'method' => 'queryUntreadOrderInfo'
            ,'data'  => array('untreadOrderNo'  => $untreadOrderNo)
        ), $errMsg);

        if ($result === false)
        {
            return $this->makeMsg('60002', $errMsg);
        }

        if (empty($result['success']) && $result['resultCode'] != 5002) {
            return  $this->makeMsg('60002',(!empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误'));
        }

        return $this->makeMsg(10000,'success', $result['result']);
    }


    /***************************  业务内部逻辑  **********************/
    
    /**
     * 处理推送订单消息
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function handleOrderMessage ($data)
    {
        if (!is_array($data)) {
            return '';
        }
        $return = array();

        foreach ($data as $key => $value) {
            // 记录信息
            app::get('unicom')->model('order')->insertUnicomMessage(1, $value['msgId'], $value['msgInfo']);
            $messInfo = $value['msgInfo'];
            switch ((int)$messInfo['stype']) {
                case 0: // 新增订单(即确认订单)
                    $tmp = $this->saveOrder($messInfo['orderNo']);
                    break;
                case 1: // 变更订单
                    //$tmp = $this->changeOrder($messInfo['orderNo']);
                    $tmp = $this->makeMsg(10000,'变更订单消息不用处理');
                    break;
                case 2: // 取消订单
                    //$tmp = $this->cancelOrderByMessage($messInfo['orderNo']);
                    $tmp = $this->makeMsg(10000,'取消订单消息不用处理');
                    break;
                default:
                    break;
            }
            // 超过6个月强制删除
            if ($value['msgTime'] && time() - strtotime($value['msgTime']) > 180*86400)
            {
                $tmp['ErrorId'] = 10000;
            }
            // @TODO 需要删除推送消息
            $this->delUnicomPushMessage($tmp, $value['msgId']);
            $return[] = $tmp;
        }

        return $return;
    }

    /**
     * 处理发货订单消息
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function handleSendOrderMessage ($data)
    {
        if (!is_array($data)) {
            return '';
        }
        $return = array();
        foreach ($data as $key => $value) {
            // 记录信息
            app::get('unicom')->model('order')->insertUnicomMessage(2,  $value['msgId'], $value['msgInfo']);
            $messInfo = $value['msgInfo'];
            switch ((int)$messInfo['stype']) {
                case 1: // 订单拒收重复
                    $tmp = $this->makeMsg(10000,'此类消息可直接删除');
                    break;
                case 2: // 订单拒收
                    $tmp = $this->makeMsg(10000,'此类消息可直接删除');
                    break;
                default:
                    break;
            }
            // 超过6个月强制删除
            if ($value['msgTime'] && time() - strtotime($value['msgTime']) > 180*86400)
            {
                $tmp['ErrorId'] = 10000;
            }
            $this->delUnicomPushMessage($tmp, $value['msgId']);
        }

        return $return;
    }

    /**
     * 处理退货订单消息
     * @param  [type] $data [description]
     * @return [type]       [description]
     */
    public function handleReturnOrderMessage ($data)
    {
        if (!is_array($data)) {
            return '';
        }
        $return = array();

        foreach ($data as $key => $value) {
            // 记录信息
            app::get('unicom')->model('order')->insertUnicomMessage(3,  $value['msgId'], $value['msgInfo']);
            $messInfo = $value['msgInfo'];
            switch ((int)$messInfo['stype']) {
                case 1: // 发起退货
                    $tmp = $this->returnOrder($messInfo['untreadOrderNo']);
                    break;
                case 2: // 取消退货
                    $tmp = $this->cancelReturnOrder($messInfo['untreadOrderNo']);
                    break;
                default:
                    break;
            }
            // 超过6个月强制删除
            if ($value['msgTime'] && time() - strtotime($value['msgTime']) > 180*86400)
            {
                $tmp['ErrorId'] = 10000;
            }
            $this->delUnicomPushMessage($tmp, $value['msgId']);
            $return[] = $tmp;
        }

        return $return;
    }

    /**
     * [saveOrder description]
     * @return [type] [description]
     */
    public function saveOrder ($orderNo)
    {
        if (empty($orderNo)) {
            return $this->makeMsg('60001', '订单编号不能为空');
        }
        
        $data = $this->getUnicomOrderInfo($orderNo);
        if ($data['ErrorId'] != 10000) {
            return $data;
        }
        
        $orderInfo = $data['Data'];
        // 获取确认订单信息
        $payOrderInfo = kernel::single('unicom_service_order_order')->getPayOrderInfoByUcOrder($orderNo);
        //不存在预订单,则创建预订单
        if (empty($payOrderInfo)) {
            \Neigou\Logger::General('unicom.checkPreOrder',array('orderInfo'=>$orderInfo,'orderNo'=>$orderNo,'msg'=>'not found preorder info'));
            return $this->makeMsg(60302,'预订单信息未找到');
        }
        
        if($orderInfo['orderState'] != 1){
            \Neigou\Logger::General('unicom.saveOrder',array('orderInfo'=>$orderInfo,'orderNo'=>$orderNo,'msg'=>'the unicom order orderState exception'));
            return $this->makeMsg(60301,'联通订单装态异常');
        }
       
        // 处理正常订单
        $result = kernel::single('unicom_order_request')->confirmOrder($orderInfo['orderNo']);
        if ($result['ErrorId'] != 10000) {
            return $result;
        }
        $platform_order_id = $result['Data']['order_id'];// @TODO 需要确定返回值*/

        //本地保存订单,以表示确认的联通订单
        $st = $this->insertOrderData($orderInfo, $platform_order_id);
        return $st;
    }

    /**
     * 修改订单
     * @param  [type] $orderNo [description]
     * @return [type]          [description]
     */
    public function changeOrder ($orderNo)
    {
        if (empty($orderNo)) {
            return $this->makeMsg('60001', '订单编号不能为空');
        }
        $data = $this->getUnicomOrderInfo($orderNo);
        if ($data['ErrorId'] != 10000) {
            return $data;
        }
        
        $orderDetail = $data['Data']['orderDetail'];
        app::get('unicom')->model('order')->updateInfo($orderNo, $orderDetail);
    }
    
    /**
     * 取消订单入口
     * @param type $orderNo
     */
    public function cancelOrderByMessage($orderNo)
    {
        $data = $this->getUnicomOrderInfo($orderNo);
        if ($data['ErrorId'] != 10000) {
            return $data;
        }
        
        $orderInfo = $data['Data'];
        return $this->cancelOrder($orderInfo);
    }

    /**
     * 取消订单
     * @param  [type] $orderInfo [description]
     * @return [type]          [description]
     */
    public function cancelOrder($orderInfo)
    {
        // 获取订单信息
        $insertStatus = false;
        $order_id = app::get('unicom')->model('order')->getOrderId($orderInfo['orderNo']);
        if (empty($order_id)) {
            // 查找用户
            $insertStatus = true;
            $p_orderInfo = $this->getPlatformOrderInfo($orderInfo['orderNo']);
            if (empty($p_orderInfo)) {
                return $this->makeMsg('60006', '订单取消失败');
            }
            $order_id = $p_orderInfo['order_id'];
        }
        
        //@TODO 调用系统取消
        // 获取物流信息
        $result = $this->doCancelOrder($order_id);
        if ($result['ErrorId'] != 10000) {
            return $result;
        }
        
        //FOR 本地无数据创建数据
        if ($insertStatus) { //插入数据
            $result = $this->insertOrderData($orderInfo, $order_id);
            if ($result['ErrorId'] != 10000) {
                $reutrn = 0;
            } else{
                $reutrn = 1;
            }
        } else {
            $info = array(
                'orderState' => 0,
            );
            $orderNo = $orderInfo['orderNo'];
            $reutrn = app::get('unicom')->model('order')->updateInfo($orderNo, $info);
        }
        
        if (!$reutrn) {
            return $this->makeMsg('60007', '订单修改失败');
        }
        return $this->makeMsg(10000, 'success');
    }

    public function hangUpOrder ($orderInfo)
    {
        $info = array(
            'orderState'   => $orderInfo['orderState'],
            'hangupReason' => $orderInfo['hangupReason'],
        );
        app::get('unicom')->model('order')->updateInfo($orderInfo['orderNo'], $info);
        return $this->makeMsg(10000, 'success');
    }
    
    
    //根据货品bn,从拆分子订单中找到所在的子订单号
    private function getSonOrderId($split_orders,$bn,&$son_order_data = null){
        $order_id = null;
        //检查是否拆单,若拆单则匹配售后子订单号
        if(!empty($split_orders)){
            foreach ($split_orders as $son_order){
                if(count($son_order['items']) > 0){
                    foreach($son_order['items'] as $row){
                        //找到售后货品bn
                        if($row['bn'] == $bn){
                            $order_id = $son_order['order_id'];
                            $son_order_data = $son_order;
                            break 2;
                        }
                    }
                }
            }
        }
        return $order_id;
    }

    
    private function do_temp_apply_return_order($untreadOrderNo,$returnInfo)
    {
        $msg = '';
        $platformOrderId = app::get('unicom')->model('order')->getOrderId($returnInfo['orderNo']);
        if (empty($platformOrderId)) {
            return $this->makeMsg('60403', '未找到平台订单ID');
        }
        $platformOrderInfo = kernel::single('unicom_service_order_order')->getOrderInfo($platformOrderId, $code = '0');
        
        $returnDetail = unserialize($returnInfo['untreadDetails']);       
        $request = kernel::single('unicom_order_customer');
        foreach ($returnDetail as $key => $goodsInfo) {
            $returnData = array(
                'order_id'        => $platformOrderId,
                'member_id'       => UNICOM_MEMBER_ID,// 下单用户id
                'product_id'      => 0,
                'product_bn'      => '',
                'product_num'     => $goodsInfo['num'],
                'customer_reason' => $returnInfo['untreadReason'],
                'after_type'      => 1,
                'status'          => 1,
                'operator_type'   => 2,//1-用户,2-pop,3-mis
                'operator_name'   => '联通分销售后',
                'ship_name'       => $returnInfo['createName'],
                'ship_mobile'     => (!empty($returnInfo['createNameMobile']) ? $returnInfo['createNameMobile'] : $platformOrderInfo['ship_mobile']),
                //换货时,传递收货地址信息
                'ship_province'   => '',
                'ship_city'       => '',
                'ship_county'     => '',
                'ship_town'       => '',
                'ship_addr'       => '',
                'pic'             => '',//以英文逗号分隔图片id
            );
            // 获取订单
            $pGoodsInfo = $this->getGoodsInfoBySku($returnInfo['orderNo'], $goodsInfo['sku']);
            //尝试从货品信息转货sku 信息
            if(empty($pGoodsInfo)){
                $pGoodsInfo = $this->getGoodsInfo($goodsInfo['sku']);
            }
            $returnData['product_id'] = $pGoodsInfo['product_id'];
            $returnData['product_bn'] = $pGoodsInfo['product_bn'];
            $returnData['ship_province'] = $platformOrderInfo['ship_province'];
            $returnData['ship_city'] = $platformOrderInfo['ship_city'];
            $returnData['ship_county'] = $platformOrderInfo['ship_county'];
            $returnData['ship_town'] = $platformOrderInfo['ship_town'];
            $returnData['ship_addr'] = $platformOrderInfo['ship_addr'];
            
            //用子单号提交售后申请
            $son_order_data = array();
            $son_order_id = $this->getSonOrderId($platformOrderInfo['split_orders'],$returnData['product_bn'],$son_order_data);
            if(!empty($son_order_id)){
                $returnData['order_id'] = $son_order_id;
            }
            
            $after_sale_bn = $request->returnsApply($returnData, $msg);
            Neigou\Logger::General('unicom.returnOrder',array(
                'son_order_id'=>$son_order_id,'returnData'=>$returnData,'after_sale_bn'=>$after_sale_bn,'msg'=>$msg));
            if ($after_sale_bn) {
                // 记录退货信息
                $insertInfo = array(
                    'return_id'         => $after_sale_bn,
                    'platform_order_id' => $platformOrderId,
                    'orderNo'           => $returnInfo['orderNo'],
                    'untreadOrderNo'    => $untreadOrderNo,
                    'sku'               => $goodsInfo['sku'],
                    'platform_sku'      => $returnData['product_bn'],
                    'nums'              => $goodsInfo['num'],
                    'createName'        => $returnInfo['createName'],
                    'createNameMobile'  => $returnInfo['createNameMobile'],
                    'createTime'        => $returnInfo['createTime'],
                    'untreadReason'     => $returnInfo['untreadReason'],
                    'untreadDetails'    => serialize($returnInfo['untreadDetails']),
                );
                app::get('unicom')->model('order')->insertReturnOrder( $insertInfo);
            }else {
                return $this->makeMsg(60031, $msg);
            }
            
        }
        // 判断推送成功
        return $this->makeMsg(10000, 'success', $returnData);
    }
    //临时处理
    public function temp_apply_return_order()
    {
        $process_untreadOrderNos = array();
        $return_list = app::get('unicom')->model('order')->getUnicomReturnList(1);
        $count = 0;
        echo '开始处理temp_apply_return_order:'.PHP_EOL;
        foreach($return_list as $row){
            $untreadOrderNo = $row['untreadOrderNo'];
            if(!in_array($untreadOrderNo,$process_untreadOrderNos)){
                echo '开始处理第'.(++$count).'条'.PHP_EOL;
                echo '$untreadOrderNo:'.$untreadOrderNo.PHP_EOL;
                $process_untreadOrderNos[] = $untreadOrderNo;
                
                $ret = $this->do_temp_apply_return_order($untreadOrderNo,$row);
                
                var_dump($ret);
            }
        }
    }
    
    //临时处理
    public function temp_push_return_order()
    {
        $category = 2;
        $type = 2;
        $push_log_list = app::get('unicom')->model('order')->getPushLog($category,$type);
        $count = 0;
        echo '开始处理temp_push_return_order:'.PHP_EOL;
        
        foreach($push_log_list as $row){
            $info = unserialize($row['request']);
            $ret = kernel::single('unicom_order_pushmessage')->pushOrderSendAfterSales($info);
            var_dump($ret);
        }
    }

    /**
     * 发起退货申请
     * @param  [type] $untreadOrderNo [退货订单号]
     * @return [type]          [description]
     */
    public function returnOrder ($untreadOrderNo)
    {
        if (empty($untreadOrderNo)) {
            return $this->makeMsg('60401', '退货订单编号不能为空');
        }
        
        // 获取退货信息
        $data = $this->getUnicomReturnOrderInfo($untreadOrderNo);
        if ($data['ErrorId'] != 10000) {
            Neigou\Logger::General('unicom.returnOrder.getUnicomReturnOrderInfo',array(
                'untreadOrderNo'=>$untreadOrderNo,'data'=>$data));
            return $data;
        }
        $returnInfo = $data['Data'];
        $returnInfo['untreadOrderNo'] = $untreadOrderNo;
        
        // 记录退货信息
        app::get('unicom')->model('order')->insertReturnOrderInfo($returnInfo);
        
        //已经存在本地映射关系
        $results = app::get('unicom')->model('order')->searchReturnOrder($untreadOrderNo);
        if(!empty($results)){
            return $this->makeMsg(10000, 'success');
        }
        
        // 处理退货逻辑
        //$returnInfo['orderNo'] = strpos($returnInfo['orderNo'],'(UC)') === FALSE ? '(UC)'.$returnInfo['orderNo'] : $returnInfo['orderNo']; 
        $returnInfo['orderNo'] = strpos($returnInfo['orderNo'],'(FLSC)') === FALSE ? '(FLSC)'.$returnInfo['orderNo'] : $returnInfo['orderNo']; 
        $platformOrderId = app::get('unicom')->model('order')->getOrderId($returnInfo['orderNo']);
        if (empty($platformOrderId)) {
            return $this->makeMsg('60403', '未找到平台订单ID');
        }
        $platformOrderInfo = kernel::single('unicom_service_order_order')->getOrderInfo($platformOrderId, $code = '0');
        
        $returnDetail = $returnInfo['untreadDetails'];       
        $request = kernel::single('unicom_order_customer');
        foreach ($returnDetail as $key => $goodsInfo) {
            $returnData = array(
                'order_id'        => $platformOrderId,
                'member_id'       => UNICOM_MEMBER_ID,// 下单用户id
                'product_id'      => 0,
                'product_bn'      => '',
                'product_num'     => $goodsInfo['num'],
                'customer_reason' => $returnInfo['untreadReason'],
                'after_type'      => 1,
                'status'          => 1,
                'operator_type'   => 2,//1-用户,2-pop,3-mis
                'operator_name'   => '联通分销售后',
                'ship_name'       => $returnInfo['createName'],
                'ship_mobile'     => (!empty($returnInfo['createNameMobile']) ? $returnInfo['createNameMobile'] : $platformOrderInfo['ship_mobile']),
                //换货时,传递收货地址信息
                'ship_province'   => '',
                'ship_city'       => '',
                'ship_county'     => '',
                'ship_town'       => '',
                'ship_addr'       => '',
                'pic'             => '',//以英文逗号分隔图片id
            );
            // 获取订单
            $pGoodsInfo = $this->getGoodsInfoBySku($returnInfo['orderNo'], $goodsInfo['sku']);
            //尝试从货品信息转货sku 信息
            if(empty($pGoodsInfo)){
                $pGoodsInfo = $this->getGoodsInfo($goodsInfo['sku']);
            }
            $returnData['product_id'] = $pGoodsInfo['product_id'];
            $returnData['product_bn'] = $pGoodsInfo['product_bn'];
            $returnData['ship_province'] = $platformOrderInfo['ship_province'];
            $returnData['ship_city'] = $platformOrderInfo['ship_city'];
            $returnData['ship_county'] = $platformOrderInfo['ship_county'];
            $returnData['ship_town'] = $platformOrderInfo['ship_town'];
            $returnData['ship_addr'] = $platformOrderInfo['ship_addr'];
            
            //用子单号提交售后申请
            $son_order_data = array();
            $son_order_id = $this->getSonOrderId($platformOrderInfo['split_orders'],$returnData['product_bn'],$son_order_data);
            if(!empty($son_order_id)){
                $returnData['order_id'] = $son_order_id;
            }
            
            $after_sale_bn = $request->returnsApply($returnData, $msg);
            Neigou\Logger::General('unicom.returnOrder',array(
                'son_order_id'=>$son_order_id,'returnData'=>$returnData,'after_sale_bn'=>$after_sale_bn,'msg'=>$msg));
            if ($after_sale_bn) {
                // 记录退货信息
                $insertInfo = array(
                    'return_id'         => $after_sale_bn,
                    'platform_order_id' => $platformOrderId,
                    'orderNo'           => $returnInfo['orderNo'],
                    'untreadOrderNo'    => $untreadOrderNo,
                    'sku'               => $goodsInfo['sku'],
                    'platform_sku'      => $returnData['product_bn'],
                    'nums'              => $goodsInfo['num'],
                    'createName'        => $returnInfo['createName'],
                    'createNameMobile'  => $returnInfo['createNameMobile'],
                    'createTime'        => $returnInfo['createTime'],
                    'untreadReason'     => $returnInfo['untreadReason'],
                    'untreadDetails'    => serialize($returnInfo['untreadDetails']),
                );
                app::get('unicom')->model('order')->insertReturnOrder( $insertInfo);
            }else {
                $req_data = array(
                    'wms_msg'=>'订单未完成申请售后',
                    'type'=>12,
                    'data_id' => $returnData['order_id'],
                    'business' => 'order',
                );
                $extend_config = array(
                    'timeout'=>3,
                );
                $ret = \Neigou\ApiClient::doServiceCall('order', 'Order/UpdateWmsOrderMsg', 'v1', null,$req_data,$extend_config);
                return $this->makeMsg(60031, $msg);
            }
            
        }
        // 判断推送成功
        return $this->makeMsg(10000, 'success', $returnData);
    }

    /**
     * 取消退货
     * @param  [type] $untreadOrderNo [description]
     * @return [type]          [description]
     */
    public function cancelReturnOrder ($untreadOrderNo)
    {
        if (empty($untreadOrderNo)) {
            return $this->makeMsg('60401', '退货订单编号不能为空');
        }
        // 获取退货信息
        $data = $this->getUnicomReturnOrderInfo($untreadOrderNo);
        if ($data['ErrorId'] != 10000) {
            return $data;
        }
        $returnInfo = $data['Data'];
        // 记录退货信息
        $returnInfo['untreadOrderNo'] = $untreadOrderNo;
        if ($returnInfo['untreadOrderState'] != 2) {
            return $this->makeMsg(10000, 'success');
        }
        // 取消退货逻辑
        $messageList = app::get('unicom')->model('order')->searchReturnOrder($untreadOrderNo);
        if (empty($messageList)) {
            return $this->makeMsg('60405', '未找到退货信息');
        }
        $request = kernel::single('unicom_order_customer');
        foreach ($messageList as $info) {
            $after_sale_bn = $info['return_id'];
            $operator_name = '联通售后';
            $desc = '消息推送取消售后申请';
            $request->cancelReturns($after_sale_bn,$operator_name,$desc);
        }
        return $this->makeMsg(10000, 'success');
    }

    public function getOrderInfoByOrderId($order_id) 
    {
        if (empty($order_id)) {
            return $this->makeMsg('60001', '订单编号不能为空');
        }
        $reutrn = app::get('unicom')->model('order')->getInfoByOrderId($order_id);
        if(empty($reutrn)) {
            return $this->makeMsg(60404, '未找到订单信息');
        }
        return $this->makeMsg(10000, 'success', $reutrn);
    }

    private function verifyOrder ($orderInfo)
    {
        return $this->makeMsg(10000,'success', $orderInfo);
    }

    /**
     * 查询内部商品数据
     * @param  [type] $sku [description]
     * @return [type]      [description]
     */
    private function switchGoodsInfo($p_sku)
    {
        $return = array();
        //@TODO 获取平台商品售卖价格
        $info = kernel::single("unicom_goods")->getGoodsInfoByPSku($p_sku);
        $return['platform_price']      = $info['bizPrice'];
        $return['platform_nakedPrice'] = $info['nakedPrice'];
        return $return;
    }
    /**
     * 获取内部地址编码
     * @param  [type] $province [description]
     * @param  [type] $city     [description]
     * @param  [type] $county   [description]
     * @param  [type] $town     [description]
     * @return [type]           [description]
     */
    private function switchAddress($province, $city, $county, $town)
    {
        //@TODO 获取平台对应地址
        $result = kernel::single("unicom_region")->getRegionMapping($province, $city, $county, $town);
        $return['platform_province'] = $result['provinceRegionId'];
        $return['platform_city']     = $result['cityRegionId'];
        $return['platform_county']   = $result['countryRegionId'];
        $return['platform_town']     = $result['townRegionId'];
        return $return;
    }


    private function insertOrderData($orderInfo, $platform_order_id)
    {

        $data = $this->verifyOrder($orderInfo);
        if ($data['ErrorId'] != 10000) {
            return $data;
        }
        $orderInfo['platform_order_id'] = $platform_order_id;
        // 
        $info = app::get('unicom')->model('order')->getInfo($orderInfo['orderNo']);
        if (!empty($info)) {
            return $this->makeMsg(10000,'success', $info['platform_order_id']);
        }
        // 收货地址转换
        $platformAddress = $this->switchAddress($orderInfo['province'], $orderInfo['city'], $orderInfo['county'], $orderInfo['town']);
        $orderInfo = array_merge($orderInfo, $platformAddress);
        // 查询内部商品价格
        $orderDetailList = $orderInfo['orderDetails'];
        unset($orderInfo['orderDetails']);
        // 主订单入库
        $order_id = app::get('unicom')->model('order')->addInfo($orderInfo);

        if (empty($order_id)) {
            return $this->makeMsg('60004','订单保存失败');
        }
        // 商品入库
        foreach ($orderDetailList as $orderDetail) {
            $orderDetail['unicom_order_id'] = $order_id;
            $orderDetail['orderNo']         = $orderInfo['orderNo'];
            $orderDetail['platform_order_id'] = $platform_order_id;
            $platformGoodsInfo              = $this->switchGoodsInfo($orderDetail['p_sku']);
            $orderDetail                    = array_merge($orderDetail, $platformGoodsInfo);
            app::get('unicom')->model('order')->addDetail($orderDetail);
        }
        return $this->makeMsg(10000,'success', $order_id);
    }

    private function getPlatformOrderInfo($orderNo)
    {
        $unicom_service_order_order = kernel::single("unicom_service_order_order"); 
        $orderInfo = $unicom_service_order_order->getPayOrderInfoByUcOrder($orderNo);
        return $orderInfo;
    }

    private function doCancelOrder($order_id)
    {
        $errMsg     = '';
        $error_code = '';
        $request    = kernel::single('unicom_order_request');
        // 获取物流信息
        $result     = $request->cancelOrder(array('order_id' => $order_id, 'reason' => '联通系统推送取消订单'), $errMsg, $error_code);
        if ($result) {
            return $this->makeMsg(10000,'success', $order_id);
        }
        return $this->makeMsg($error_code, $errMsg, $order_id);

    }

    public function getGoodsInfo($p_sku)
    {
        $info = kernel::single("unicom_goods")->getGoodsInfoByPSku($p_sku);
        return $info;
    }

    public function getGoodsInfoBySku($orderNo, $sku)
    {
        $info = app::get('unicom')->model('order')->getGoodsInfoBySku($orderNo, $sku);
        if(!empty($info)) {
            return $this->getGoodsInfo($info['p_sku']);
        }
        return array();
    }
    

    /**
     * 通过p_sku 获取商品信息
     * @param  [type] $p_sku [description]
     * @return [type]        [description]
     */
    public function getGoodsInfoByPSku($p_sku)
    {
        $goodsInfo = kernel::single("unicom_goods")->getGoodsInfoByPSku($p_sku);

        return $goodsInfo;
    }

}