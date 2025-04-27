<?php

/**
 * 联通对外接口
 */
class unicom_openapi_order {

    protected $data = array();  //订单数据
    private $company_id = 0;
    private $member_id = 0;

    public function __construct() {
        $this->data = json_decode(base64_decode(trim($_POST['data'])), true);
        $_check = kernel::single('b2c_safe_apitoken');
        if ($_check->check_token($_POST, OPENAPI_TOKEN_SIGN) === false) {
            $this->ResponseJson(40200, '签名错误');
        }
        //设置company_id、member_id
        $this->company_id = UNICOM_COMPANY_ID;
        $this->member_id = UNICOM_MEMBER_ID;
    }

    public function QueryLogisticsInfo() {
        if (empty($this->data['sendOrderNo'])) {
            $this->ResponseJson(20000, '请选择订单');
        }
        $sendOrderNo = $this->data['sendOrderNo'];

        $unicomInfo = app::get('unicom')->model('order')->getDeliveryOrderByPson($sendOrderNo);
        if (empty($unicomInfo)) {
            $this->ResponseJson(60101, '发货单不存在');
        }
        $orderInfo = kernel::single("unicom_service_order_order")->getOrderInfo($unicomInfo['send_order_no']);
        if (empty($orderInfo)) {
            $this->ResponseJson(60024, '订单未找到');
        }
        $dlycorp_code = $orderInfo['logi_code'];
        $logi_no = $orderInfo['logi_no'];
        
        if(empty($dlycorp_code) || empty($logi_no)){
            $this->ResponseJson(60402,'物流公司或物流单号不存在');
        }
        
        $dataInfo = array();
        $r = logisticstrack_puller::new_pull_logi($dlycorp_code, $logi_no, $dataInfo);
        if (FALSE === $r) {
            $this->ResponseJson(60401, $dataInfo['msg']);
        }
        $data = array('sendOrderNo' => $sendOrderNo, 'orderTrack' => array(), 'sendOrderState' => 0);

        if (!empty($dataInfo)) {
            foreach ($dataInfo['data'] as $v) {
                $data['orderTrack'][] = array('msgTime' => $v['time'], 'content' => $v['context']);
            }
            $state = $dataInfo['state'];
            if (in_array($state, array(0))) {
                $data['sendOrderState'] = 43;
            }elseif (in_array($state, array(1))) {
                $data['sendOrderState'] = 44;
            }elseif (in_array($state, array(3))) {
                $data['sendOrderState'] = 45;
            }else {
                $data['sendOrderState'] = 44;
            }
        } else {
            $data['sendOrderState'] = 43;
        }  
        return $this->ResponseJson(10000, '订单获取成功', $data);
    }

    public function ResponseJson($code, $msg, $data = array()) {
        $Result = $code == 10000 ? 'true' : 'false';
        $response_data = array(
            "Result" => $Result,
            "ErrorId" => (string) $code,
            "ErrorMsg" => $msg,
            "Data" => empty($data) ? null : $data,
        );
        echo json_encode($response_data);
        exit;
    }

    /*
     * @创建订单
     */
    public function CreateOrder() {
        if (empty($this->data)) {
            $this->ResponseJson(41000, '订单数据不能为空');
        }
        $origin_post_data = $this->data['post_data'];
        $origin_post_data['company_id'] = $this->company_id;
        $origin_post_data['member_id'] = $this->member_id;

        $origin_product_list = $this->data['goods_list'];
        $third_order_bn = $origin_post_data['third_order_bn'];
        $orderPrice = $origin_post_data['orderPrice'];
        $error_code = 10000;
        $msg = 'OK';

        $key_lock = 'unicom_create_order_' . $third_order_bn;
        $unicom_service_order_unicom_utils = kernel::single("unicom_service_order_unicom_utils");
        $locked = $unicom_service_order_unicom_utils->getLogicLock($key_lock);
        if (!$locked) {
            $error_code = 10011;
            $msg = '加锁失败';
            goto LABEL_RES;
        }

        $unicom_service_order_order = kernel::single("unicom_service_order_order");
        //检查重复下单
        $pay_confirm_info = $unicom_service_order_order->getPayConfirmInfo($third_order_bn);
        $isExisted = !empty($pay_confirm_info) ? true : false;

        if ($isExisted) {
            $can_return_status = array(
                unicom_service_order_order::ORDER_PAY_CONFIRM_STATUS_WAIT_CONFIRM,
            );
            $can_rewrite_status = array(
                unicom_service_order_order::ORDER_PAY_CONFIRM_STATUS_INIT,
            );
            if (in_array($pay_confirm_info['status'], $can_return_status)) {
                $order_id = $pay_confirm_info['order_id'];

                $ret_data = array(
                    'order_id' => $order_id,
                );
                goto LABEL_RES;
            } elseif (in_array($pay_confirm_info['status'], $can_rewrite_status)) {
                //继续处理
            } else {
                $error_code = 10015;
                $msg = '第三方订单号已存在';
                goto LABEL_RES;
            }
        }

        //获取预下单号
        $temp_order_id = $unicom_service_order_order->getOrderId();
        if ($temp_order_id === false) {
            $error_code = 10012;
            $msg = '预下单号生成';
            $ret_data = array(
                'third_order_bn' => $third_order_bn,
            );
            goto LABEL_RES;
        }
        if ($isExisted) {//可覆盖的状态，使用新的服务单号
            $confirm_data = array(
                'id' => $pay_confirm_info['id'],
                'order_id' => $temp_order_id,
            );
            $confirm_data_status = unicom_service_order_order::ORDER_PAY_CONFIRM_STATUS_INIT;
            $unicom_service_order_order->setPayConfirmInfo($confirm_data, $confirm_data_status);
            $pay_confirm_info = $unicom_service_order_order->getPayConfirmInfo($third_order_bn);
            if ($pay_confirm_info['order_id'] != $temp_order_id) {
                $error_code = 10062;
                $msg = '更新服务单号失败';
                goto LABEL_RES;
            }
        }
        //订单不存在
        else {
            $confirm_data = array(
                'third_order_bn' => $third_order_bn,
                'order_id' => $temp_order_id,
                'company_id' => $origin_post_data['company_id'],
                'member_id' => $origin_post_data['member_id'],
            );
        }

        $confirm_data_status = unicom_service_order_order::ORDER_PAY_CONFIRM_STATUS_PROCESSING; //处理中标志
        $r0 = $unicom_service_order_order->setPayConfirmInfo($confirm_data, $confirm_data_status);
        if (!$r0) {
            \Neigou\Logger::General('unicom.create', array('confirm_data' => $confirm_data, 'confirm_data_status' => $confirm_data_status, 'msg' => 'setPayConfirmInfo fail'));
            $error_code = 10013;
            $msg = '内部服务异常';
            goto LABEL_RES;
        }

        $pre_order_data = array();
        $pre_order_items = array();
        $post_data = $unicom_service_order_order->formatPostData($origin_post_data, $pre_order_data);
        $post_data['temp_order_id'] = $temp_order_id;
        $pre_order_data['temp_order_id'] = $temp_order_id;


        $format_ret = $unicom_service_order_order->formatProductList($origin_product_list, $pre_order_items, $msg, $error_code, $format_ret_data);
        if ($format_ret === TRUE) {
            $product_list = $format_ret_data;
        } else {
            $ret_data = $format_ret_data;
            goto LABEL_RES;
        }

        /*
         *
          //联通授权商品无预下单
          //去预下单处理
          if(!empty($pre_order_items)){
          $r = $unicom_service_order_order->preOrder($pre_order_data,$pre_order_items,$pre_order,$msg,$error_code);
          if(!$r){
          $error_code = 10099;
          $msg = !empty($msg) ? $msg : '预下单处理失败';
          $pay_confirm_info = $unicom_service_order_order->getPayConfirmInfo($third_order_bn);
          $confirm_data = array(
          'id'=>$pay_confirm_info['id'],
          );
          $confirm_data_status = unicom_service_order_order::ORDER_PAY_CONFIRM_STATUS_INIT;
          $unicom_service_order_order->setPayConfirmInfo($confirm_data,$confirm_data_status);
          goto LABEL_RES;
          }else{
          if(is_array($pre_order)){
          $temp_product_bns = array();
          foreach($pre_order['product_list'] as $key=> $item){
          $product_bn = $key;
          $old_price = $product_list[$product_bn]['price']['price'];
          $new_price = $item['cost_price'];
          if(0 != bccomp($old_price,$new_price,3)){
          $temp_product_bns[] = $product_bn;
          //更新货品价格
          $unicom_service_order_order->updateProductByBn($product_bn,$new_price);
          //返回新的结算价格
          $product_list[$product_bn]['price']['price'] = $new_price;
          }
          }
          if(count($temp_product_bns) > 0){
          $unicom_service_order_order->setProductPrice($temp_product_bns);
          }
          //已成功预下单
          $post_data['preorder_order'] = $temp_order_id;
          }
          }
          } */

        $terminal = $this->data['terminal'];
        $platform = 'unicom';
        $cart['product_list'] = $product_list;

        $result = $unicom_service_order_order->generate($post_data, $cart, $terminal, $platform, $msg, $error_code, $ret_data);
        if (false === $result) {
            \Neigou\Logger::General("unicom.create", array("platform" => "web", "this->data" => $this->data, 'msg' => $msg, 'error_code' => $error_code, 'ret_data' => $ret_data));
            if (empty($msg)) {
                $msg = '下单处理异常';
            }

            $pay_confirm_info = $unicom_service_order_order->getPayConfirmInfo($third_order_bn);
            $confirm_data = array(
                'id' => $pay_confirm_info['id'],
            );
            $confirm_data_status = unicom_service_order_order::ORDER_PAY_CONFIRM_STATUS_INIT;
            $unicom_service_order_order->setPayConfirmInfo($confirm_data, $confirm_data_status);

            goto LABEL_RES;
        }

        if ($result) {
            $pay_confirm_info = $unicom_service_order_order->getPayConfirmInfo($third_order_bn);
            $confirm_data = array(
                'id' => $pay_confirm_info['id'],
            );
            $confirm_data_status = unicom_service_order_order::ORDER_PAY_CONFIRM_STATUS_WAIT_CONFIRM;
            $r1 = $unicom_service_order_order->setPayConfirmInfo($confirm_data, $confirm_data_status);
            if ($r1 === FALSE) {
                $error_code = 10018;
                $msg = '更新预订单状态失败';
                \Neigou\Logger::General("unicom.create", array("platform" => "web", "this->data" => $this->data, 'msg' => $msg, 'error_code' => $error_code));
                goto LABEL_RES;
            }

            //如果预订单金额 > 第三方下单声明订单金额，则自动取消订单
            if (1 == bccomp($result['final_amount'],$orderPrice,3)) {
                $error_code = 10020;
                // $msg = '预订单金额大于下单订单总金额';
                $msg = '商品价格异常，请稍后重试！';
                \Neigou\Logger::General("unicom.create", array("platform" => "web", "this->data" => $this->data, 'msg' => $msg, 'error_code' => $error_code, 'result' => $result));
                $reason = $msg . '(预订单自动取消)';
                $unicom_service_order_order->doCancelOrder($result['order_id'], $reason, $msg, $error_code);

                $goodsLogic = kernel::single("unicom_goods");

                foreach ($origin_product_list as $productInfo)
                {
                    $goodsLogic->pushGoods($productInfo['bn'], 'price');
                }
                goto LABEL_RES;
            }

            $ret_data = array(
                'order_id' => $result['order_id'],
            );
            $unicom_service_order_order->updateGoodsByOrderId($temp_order_id);
        }

        LABEL_RES:
        $unicom_service_order_unicom_utils->releaseLogicLock($key_lock);
        $this->ResponseJson($error_code, $msg, $ret_data);
    }

    /*
     * @todo 订单取消
     */

    public function CancelOrder() {
        if (empty($this->data['third_order_bn'])) {
            $this->ResponseJson(10010, '请选择订单');
        }

        $third_order_bn = $this->data['third_order_bn'];

        $unicom_service_order_unicom_utils = kernel::single("unicom_service_order_unicom_utils");
        $key_lock = 'unicom_order_cancel_order_' . $third_order_bn;
        $locked = $unicom_service_order_unicom_utils->getLogicLock($key_lock);
        if (!$locked) {
            $error_code = 10011;
            $msg = '加锁失败';
            goto LABEL_RES;
        }

        $unicom_service_order_order = kernel::single("unicom_service_order_order");
        $pay_confirm_info = $unicom_service_order_order->getPayConfirmInfo($third_order_bn);
        if (empty($pay_confirm_info)) {
            $error_code = 10031;
            $msg = '订单信息未找到';
            goto LABEL_RES;
        }

        $order_id = $pay_confirm_info['order_id'];
        $reason = mb_substr($this->data['reason'], 0, 50, 'utf-8');
        if (empty($reason)) {
            $error_code = 10072;
            $msg = '请填写取消订单原因';
            goto LABEL_RES;
        }

        $r = $unicom_service_order_order->doCancelOrder($order_id, $reason, $msg, $error_code);
        if ($r === TRUE) {
            $error_code = 10000;
            $msg = '取消订单成功';
        } else {
            \Neigou\Logger::General('unicom.create.cancel_order', array('pay_confirm_info' => $pay_confirm_info, 'confirm_data' => $confirm_data, 'confirm_data_status' => $confirm_data_status, 'msg' => 'setPayConfirmInfo fail'));
        }

        LABEL_RES:
        $unicom_service_order_unicom_utils->releaseLogicLock($key_lock);
        $this->ResponseJson($error_code, $msg);
    }
}
