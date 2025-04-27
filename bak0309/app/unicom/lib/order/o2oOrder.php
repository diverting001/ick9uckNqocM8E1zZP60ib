<?php
/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/4/1
 * Time: 9:47 AM
 */

class unicom_order_o2oOrder
{

    /** 联通方创建订单
     *
     * @param array $data
     * @return array|bool
     * @author liuming
     */
    public function createOrder($data = array())
    {
        try {
            if (empty($data)) return false;
            $orderMustkey = array(
                'paymentId', 'comOrderNo', 'member_id', 'comCode', 'company', 'encouragePlan', 'contactNumber', 'createId', 'createName', 'name', 'ou', 'orgfullname', 'submitState', 'orderPrice', 'orderNakedPrice', 'orderTaxPrice', 'status'
            );

            $orderChangeKey = array(
                'comOrderNo' => 'platformOrderId','paymentId' => 'payment_id'
            );

            $orderItemsMustKey = array(
                'platformOrderId', 'sku', 'goodsName', 'num', 'marketPrice', 'price', 'nakedPrice', 'taxPrice', 'taxRate', 'marketSums', 'sellAmount', 'nakedSums', 'taxSums', 'goodsSummary'
            );


            $insertOrderData = array();
            foreach ($orderMustkey as $v) {
                if (isset($data[$v])) {
                    $insertOrderData[$v] = $data[$v];
                } else {
                    throw new Exception($v . '不能为空');
                }
            }


            foreach ($orderChangeKey as $k => $v){
                if (isset($insertOrderData[$k])){
                    $insertOrderData[$v] = $insertOrderData[$k];
                    unset($insertOrderData[$k]);
                }
            }


            $insertOrderItemsData = array();
            $orderDetail = json_decode($data['orderDetail'], true);


            foreach($orderDetail as $detailK => $detailV){
                $detailV['platformOrderId'] = $insertOrderData['platformOrderId'];
                foreach ($orderItemsMustKey as $v) {
                    if (isset($detailV[$v])) {
                        $insertOrderItemsData[$detailK][$v] = $detailV[$v];
                    } else {
                        throw new Exception('订单明细: '.$v . '不能为空');
                    }
                }
            }

            $db = kernel::database();
            $db->beginTransaction();

            $orderModel = app::get('unicom')->model('o2oPaymentOrder');
            $res = $orderModel->add($insertOrderData);
            if (!$res) throw new Exception('订单表插入异常');
            $orderItemsModel = app::get('unicom')->model('o2oOrderItems');
            foreach($insertOrderItemsData as $v){
                $res = $orderItemsModel->add($v);
                if (!$res) throw new Exception('订单明细表插入异常');
            }
            $db->commit();
            return $this->_return(0, '插入成功');
        } catch (Exception $e) {
            $db->rollback();
            return $this->_return(500, $e->getMessage());
        }
    }

    /** 平台订单表更新
     *
     * @param array $where
     * @param $data
     * @return array
     * @author liuming
     */
    private function upodateOrder($where =array(),$data){
        try{
            if (!$where){
                throw new Exception('查询条件不能为空!');
            }
            $orderModel = app::get('unicom')->model('o2oPaymentOrder');
            $res = $orderModel->update($where,$data);
            if (!$res){
                throw new Exception('平台订单更新失败');
            }
            return $this->_return(0,'平台订单更新成功');
        }catch (Exception $e){
            return $this->_return(500, $e->getMessage());
        }
    }


    /*
     *  返回信息
     */
    public function _return($code = 0, $mess = '', $data = array())
    {
        return array(
            'Result' => empty($code) ? true : false,
            'ErrorId' => $code,
            'ErrorMsg' => $mess,
            'Data' => $data
        );
    }
}