<?php
/**
 * 联通物流 crontab
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
class unicom_cron_pullmessage
{
    private $orderTyep = array(1,2,3,4,5);

    /**
     * 拉取联通消息
     *
     * @return string
     */
    public function pullUnicomMessage($type = 1,$is_del = 0)
    {
        $errMsg = '';
        $request = kernel::single('unicom_request');
        // 获取物流信息
        $result = $request->request(array(
            'method' => 'getOrderPushMsg'
            ,'data'  => array('type'  => $type, 'is_del' => $is_del)//
        ), $errMsg);
        //var_dump($result, $errMsg);
        if ($result === false) {
            return $errMsg;
        }
        if ($result['success'] != 'true') {
            return !empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误';
        }
        $data = $result['result'];
        $errMsgList = array();
        if (in_array($type, $this->orderTyep) && !empty($data)) {
            switch ((int)$type) {
                case 1: // 订单相关
                    $errMsgList = kernel::single("unicom_order_handle")->handleOrderMessage($data);
                    break;
                case 2: // 发货单 相关
                    $errMsgList = kernel::single("unicom_order_handle")->handleSendOrderMessage($data);
                    break;
                case 3: // 退货相关
                    $errMsgList = kernel::single("unicom_order_handle")->handleReturnOrderMessage($data);
                    break;
                case 4: // 结算相关
                    $errMsgList = kernel::single("unicom_order_bill")->handleBillOrderMessage($data);
                    break;
                case 5: // 地址相关
                    # @TODO 处理地址相关逻辑
                    $errMsgList = kernel::single("unicom_cron_region")->pullRegion();
                    break;
            }
            
        }
        
        return $errMsgList;
    }

    /**
     * 删除推送消息
     * @param  [type] $msgIdArr [description]
     * @return [type]         [description]
     */
    /*public function delOrderPushMsg ($msgIdArr)
    {
        if (empty($msgIdArr)) {
            return '';
        }
        $msgIds = implode(',', $msgIdArr);
        if (empty($msgIds)) {
            return '';
        }
        $request = kernel::single('unicom_request');
        // 获取物流信息
        $result = $request->request(array(
            'method' => 'delOrderPushMsg'
            ,'data'  => array('msgIds'  => $msgIds)//
        ), $errMsg);
        if ($result === false) {
            return $errMsg;
        }
        if ($result['success'] != 'true') {
            return !empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误';
        }
        return $result['result'];
    }*/

}
