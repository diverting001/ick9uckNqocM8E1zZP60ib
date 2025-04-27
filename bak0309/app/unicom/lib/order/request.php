<?php
/**
 * opapi 内部调用
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
class unicom_order_request extends unicom_order_abstract
{

    private $preKey = 'UNICOM';
    private $accessTokenKey = 'accessToken';

    /**
     * 确认订单接口
     * @param  [type] $orderNo [description]
     * @param  string $errMsg  [description]
     * @return [type]          [description]
     */
    public function confirmOrder($orderNo)
    {
        $msg        = '';
        $error_code = '';
        $ret_data   = '';

        $b2c_service_order_unicom = kernel::single("unicom_service_order_order"); 
        $result = $b2c_service_order_unicom->doConfirmOrder($orderNo,$msg,$error_code,$ret_data);
        \Neigou\Logger::General('unicom_order_request_confirmOrder', array('request' => $requestData, 'result' => $result));
        if ($result) {
            return $this->makeMsg(10000,'success', $ret_data);
        }
        return $this->makeMsg($error_code, $msg);
    }

    /**
     * 订单取消接口
     * @param  [type] $requestData [order_id, reason]
     * @param  string $errMsg      [description]
     * @return [type]              [description]
     */
    public function cancelOrder($requestData, & $errMsg = '', &$error_code = '')
    {
        $b2c_service_order_unicom = kernel::single("unicom_service_order_order"); 
        $result = $b2c_service_order_unicom->doCancelOrder($requestData['order_id'],$requestData['reason'],$errMsg,$error_code);
        \Neigou\Logger::General('unicom_order_request_cancelOrder', array('request' => $requestData, 'result' => $result));

        return $result;
    }

}

