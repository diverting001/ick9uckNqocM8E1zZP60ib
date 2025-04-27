<?php
/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/29
 * Time: 7:05 PM
 */

class unicom_order_o2oPushOrderMessage{

    const UNICOM_UPDATE_ORDER_STATUS_MMETHOD = 'updateEncourageOrderStatus';

    private $_config;
    public function __construct()
    {
        $config = app::get('ectools')->getConf('ectools_payment_plugin_unicomo2opay');
        $config = unserialize($config);
        $this->_config = $config['setting'];
    }

    /** 订单操作
     *
     * @param string $orderId
     * @param string $action
     * @author liuming
     */
    public function sync_order($orderId = '',$action = ''){
        try{
            if (empty($orderId) || empty($action)){
                throw new Exception('action不能为空');
            }

            if (!method_exists($this,$action)){
                throw new Exception('方法不存在');
            }
            call_user_func_array(array($this,$action),array($orderId));
        }catch (Exception $e){
            echo $e->getMessage();
        }
    }


    /** 订单支付
     *
     * @param $orderId
     * @return bool
     * @author liuming
     */
    private function delivery($orderId){
        try{
            //todo 获取用户信息
            $orderRes = $this->getOrderInfoByOrderId(array('platformOrderId' => $orderId));
            if (!$orderRes){
                return false;
            }

            $o2oOrderUpModel = app::get('unicom')->model('o2oOrderUpdateRecord');
            $upRawRes = $o2oOrderUpModel->getOrderRaw(array('platformOrderId' => $orderId,'updateType' => 1));
            if ($upRawRes){
                throw new Exception('该订单已履约完成!');
            }

            $requestData = array(
                'method' => 'updateEncourageOrderStatus',
                'comCode' => $orderRes['comCode'],
                'company' => $orderRes['company'],
                'orderNo' => $orderRes['unicomOrderId'],
                'providerCode' => $this->_config['providerCode'],
                'providerName' => $this->_config['providerName'],
                'status' => 2,
                'statusNote' => '订单已发货',
            );

            //todo 请求联通
            $request = kernel::single('unicom_request');
            $result = $request->requestOpenapi($requestData,'');

            if ($result['Result'] != "true"){
                throw new Exception($result['ErrorMsg']);
            }

            $insertData = array(
                'unicomOrderId' => $orderRes['unicomOrderId'],
                'platformOrderId' => $orderRes['platformOrderId'],
                'comCode' => $orderRes['comCode'],
                'company' => $orderRes['company'],
                'updateType' => 1,
                'status' => 2,
                'statusNote' => $requestData['statusNote'],
                'createTime' => time()
            );
            $o2oOrderUpModel->add($insertData);
        }catch (Exception $e){
            \Neigou\Logger::General('unicom_o2o_order_pay_error',array('request' => $requestData,'res' => $result,'errorMsg' => $e->getMessage()));
            return false;
        }

    }

    /** 取消订单
     *
     * author liuming
     */
    private function cancelOrder($orderId = 0){
        try{
            $orderRes = $this->getOrderInfoByOrderId(array('platformOrderId' => $orderId));
            if (!$orderRes){
                return false;
            }

            $orderCancelModel = app::get('unicom')->model('o2oOrderCancelRecord');
            $orderCancelRes = $orderCancelModel->getOrderRaw(array('platformOrderId' => $orderId));
            if ($orderCancelRes){
                throw new Exception($orderId.' :该订单已经被取消过');
            }

            $requestData = array(
                'method' => 'orderCancle',
                'orderNo' => $orderRes['unicomOrderId'],
                'comCode' => $orderRes['comCode'],
                'providerCode' => $this->_config['providerCode'],
                'providerName' => $this->_config['providerName'],
                'cancleReason' => '订单取消',
            );

            $request = kernel::single('unicom_request');
            $result = $request->requestOpenapi($requestData,'');
            if ($result['Result'] != "true"){
                throw new Exception($result['ErrorMsg']);
            }

            $insertOrderCancelData = array(
                'platformOrderId' => $orderRes['platformOrderId'],
                'unicomOrderId' => $orderRes['unicomOrderId'],
                'type' => 1,//内购方取消
                'create_time' => time()
            );
            $res = $orderCancelModel->add($insertOrderCancelData);
            if (!$res){
                throw new Exception('添加记录失败');
            }

        }catch (Exception $e){
            \Neigou\Logger::General('unicom_o2o_order_cancel_error',array('request' => $requestData,'res' => $result,'errorMsg' => $e->getMessage()));
            return false;
        }

    }

    //todo 根据内购orderid获取联通用户信息和订单信息
    private function getOrderInfoByOrderId($whereArr = array()){
        //todo 查询数据库
        $orderModel = app::get('unicom')->model('o2oPaymentOrder');
        $orderRes = $orderModel->getOrderRaw($whereArr);
        if (!$orderRes){
            return false;
        }
        return $orderRes;
    }



}