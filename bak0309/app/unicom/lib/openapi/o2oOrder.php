<?php
/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/4/1
 * Time: 1:24 PM
 */


class unicom_openapi_o2oOrder
{
    public function __construct()
    {
        $this->data = $_POST; //测试用
    }

    public function cancelOrderByUnicomOrderId(){
        try{
            if (!$this->data['unicomOrderId']){
                throw new Exception('订单状态取消失败，联通订单id不能为空');
            }

            $orderModel = app::get('unicom')->model('o2oPaymentOrder');
            $unicomOrderInfo = $orderModel->getOrderRaw(array('unicomOrderId' => $this->data['unicomOrderId']));
            if (empty($unicomOrderInfo)){
                throw new Exception('订单状态取消失败，未获取到数据');
            }

            //获取用户信息
            $order_info = kernel::single("b2c_service_order")->getOrderInfo($unicomOrderInfo['platformOrderId']);
            if (!$order_info){
                throw new Exception('订单状态取消失败，订单信息获取失败');
            }

            if ($this->data['delUserId'] != $unicomOrderInfo['createId']){
                throw new Exception('订单状态取消失败，下单人与原订单不符');
            }

            //未支付
            if($order_info['pay_status'] != 1){
                throw new Exception('订单状态已锁定取消失败，请联系客服');
            }
            //取消/已完成
            if($order_info['status'] == 2 || $order_info['status'] == 3){
                throw new Exception('订单状态错误，请联系客服');
            }
            $cancelRes = kernel::single("b2c_service_order")->cancelOrder($unicomOrderInfo['platformOrderId']);
            if (!$cancelRes){
                throw new Exception('订单取消失败，请联系客服');
            }

            $insertData = array(
                'platformOrderId' => $unicomOrderInfo['platformOrderId'],
                'unicomOrderId' => $unicomOrderInfo['unicomOrderId'],
                'delUserId' => $this->data['delUserId'],
                'delUserName' => $this->data['delUserName'],
                'type' => 2, //联通方取消
                'create_time' => time(),
            );

            $orderCancelRecordModel = app::get('unicom')->model('o2oOrderCancelRecord');
            $orderCancelRecordModel->add($insertData);
            return $this->_apiReturn(0,'取消成功');
        }catch (Exception $e){
            return $this->_apiReturn(500,$e->getMessage());
        }
    }

    /** 获取订单详情
     *
     * @param int $unicomOrderId
     * @return mixed
     * @author liuming
     */
    public function getOrderDetailByUnicomOrderId($unicomOrderId = 0){
        $orderModel = app::get('unicom')->model('o2oPaymentOrder');
        $res = $orderModel->getOrderRaw(array('unicomOrderId' => $this->data['unicomOrderId']));
        if ($res){
            return $this->_apiReturn(0,'查询成功',$res);
        }else{
            return $this->_apiReturn(500,'未获取到数据',array());
        }
    }

    /** 获取订单支付状态
     *
     * @author liuming
     */
    public function getOrderPayStatus(){
        if (!$this->data['unicomOrderId']){
            return $this->_apiReturn(400,'联通订单id不能为空',array());
        }

        $orderModel = app::get('unicom')->model('o2oPaymentOrder');
        $res = $orderModel->getOrderRaw(array('unicomOrderId' => $this->data['unicomOrderId']));
        if (!$res) {
            return $this->_apiReturn(500, '未获取到数据', array());
        }

        $order_info = kernel::single("b2c_service_order")->getOrderInfo($res['platformOrderId']);
        if (!$order_info){
            return $this->_apiReturn(500, '未获取到数据', array());
        }

        if ($order_info['status'] == 1 && $order_info['pay_status'] == 1){
            return $this->_apiReturn(0,'订单可以支付');
        }

        if ($order_info['status'] == 2){
            return $this->_apiReturn(400,'该订单已取消');
        }

        if ($order_info['status'] == 3){
            return $this->_apiReturn(400,'该订单已完成');
        }

        if ($order_info['pay_status'] == 2){
            return $this->_apiReturn(400,'该订单已支付');
        }

        return $this->_apiReturn(400,'订单异常');
    }

    /*
     * 接口返回
     *
     * @param   $result     boolean     返回状态
     * @param   $errId      int         错误ID
     * @param   $errMsg     string      错误描述
     * @param   $data       mixed       返回内容
     * @return  string
     */
    private static function _apiReturn($errId = 0, $errMsg = '', $data = null)
    {
        echo json_encode(array('Result' => $errId == 0 ? 'true' : 'false', 'ErrorId' => $errId, 'ErrorMsg' => $errMsg, 'Data' => $data));
        exit;
    }
}