<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class b2c_mdl_o2o_thirdproducts extends base_db_external_model
{
    /*
     * @var 支持的供应商及名称
     */
    private $_supportSuppliers = array(
        'LDPW' => 'LianDong', // 联动票务
        'YKQ' => 'YiKuaiQu', // 一块去
        'OF' => 'OfPay', // 欧飞充值
        'XMLY' => 'XMLY', // 喜马拉雅
        'ZH' => 'ZH', // 知乎
        'SHOPNG' => 'SHOPNG',
        'SHOP' => 'SHOP',
        'NGJF' => 'NGJF',
        'YDSH' => 'YDSH', // 券仓云
    );

    /**
     * 公开构造方法
     *
     * @params app object
     * @return void
     */
    public function __construct($app)
    {
        // var_dump($app);exit;
        // parent::__construct($app, $this->db_conf);
    }

    public function sms_order_id($bn,$num,$sdf){
         return $this->send_third_coupon($bn,$num,$sdf);
    }

    private function send_third_coupon($bn, $num, $sdf)
    {
        \Neigou\Logger::General("o2o_third_send_coupon",array('action'=>'send_third_coupon','order'=>$sdf, 'bn' => $bn, 'num' => $num));
        //状态检查
        $obj_members = app::get('b2c')->model('members');
        $members = $obj_members->get_current_member();

        if(!$members || $members['member_id'] == 0){
            $member_id = $sdf['member_id'];
            $members = $obj_members -> getRow('*',array('member_id' => $member_id));
        }

        if(!$members['mobile']){
            //@TODO maojz pop订单兼容ec订单
            if(isset($sdf['items'])){
                $members['mobile'] = $sdf['ship_mobile]'];

            } else {
                $member_order_info = app::get('b2c')->model("orders")->getRow("*",array("order_id"=>$sdf['order_id']));
                $members['mobile'] = $member_order_info['ship_mobile'];
            }

        }

        /*
        // 检测是否是第三方的o2o商品
        $o2o = app::get('b2c')->model("o2o_products");
        if(empty($o2o->get_Coupon_auth($bn)))
        {
            return false;
        }*/

        // 获取供应商的BN
        $supplierBn = self::_getSupplierBn($bn);

        // 获取供应商驱动标识，不存在则取默认的供应商
        if ( ! isset($this->_supportSuppliers[$supplierBn]))
        {
            reset($this->_supportSuppliers);
            $supplierBn = key($this->_supportSuppliers);
        }

        // 获取O2O商品订单其他信息
        $orderExtend = app::get('b2c')->model('o2o_handorder')->getByOrderId($sdf['order_id']);

        $hidePhone  =  kernel::single("b2c_global_scope")->getScopeByWeight(
            'checkout_o2o_hide_phone',
            '',
            $sdf['company_id']
        );


        // 联动票务
        if ($supplierBn === 'LDPW')
        {
            $orderExtend = isset($orderExtend[0]['extends']) && $orderExtend[0]['extends'] ? unserialize($orderExtend[0]['extends']) : array();

            if (isset($orderExtend['phone']) && self::_checkMobile($orderExtend['phone']))
            {
                $mobile = $orderExtend['phone'];
            }
            else
            {
                $mobile = isset($members['mobile']) ? $members['mobile'] : '';
            }

            //联动必须要手机号，公司定制需求没有手机号固定传富春的手机号
            if ($hidePhone['key_value']) {
                $mobile = '13699278164';
            }

            $result = kernel::single('b2c_o2o_liandong_coupon')->getTicket($sdf['order_id'], $bn, $members['member_id'], $num, $mobile, $orderExtend);

            $result = $result ? json_decode($result, true) : array();

            if (isset($result['Result']) && $result['Result'] == 'true')
            {
                return true;
            }
            \Neigou\Logger::General("o2o_thirdproducts.err", array('action'=>'send_third_coupon', 'order'=> json_encode($sdf), 'result' => json_encode($result)));
        } // SHOP 推消费码
        else if ($supplierBn === 'SHOPNG' || $supplierBn == 'SHOP') {
            $result = $this->supplierSendTicket($bn, $num, $members['member_id'], $sdf, $orderExtend, 'sendTicket');
            if (!isset($result['result']) || $result['result'] != 'true') {
                return false;
            }
            // 供应商订单 ID
            $supplierOrderId = $supplierBn . '-' . (isset($result['data']['order_id']) ? $result['data']['order_id'] : '');
            // 供应商激活码
            $coupons = isset($result['data']['ticket_code']) ? $result['data']['ticket_code'] : array();
            // 用户手机号
            $mobile = isset($result['data']['phone']) ? $result['data']['phone'] : '';

            foreach ($coupons as $coupon) {
                // 保存消费码发送
                if (!app::get("b2c")->model('o2o_products')->add_third_coupon($supplierOrderId, $coupon, '', '', '', '', $members['member_id'], '', $num, $bn, $sdf['order_id'], $mobile, time(), '', '', '', 'addOrder', '')) {
                    return false;
                }
            }

            if (isset($result['result']) && $result['result'] == 'true') {
                return true;
            } else {
                return false;
            }
        }
        else
        {
            // 请求 SALYUT 请求供应商发送激活码
            $result = $this->supplierSendCoupon($bn, $this->_supportSuppliers[$supplierBn], $num, $members['member_id'], $sdf, $orderExtend);

            // 供应商订单 ID
            $supplierOrderId = $supplierBn. '-'. (isset($result['data']['supplier_order_id']) ? $result['data']['supplier_order_id'] : '');
            // 供应商激活码
            $coupons = isset($result['data']['supplier_coupon']) ? $result['data']['supplier_coupon'] : '';
            // 错误信息
            $log = isset($result['error_msg']) ? $result['error_msg'] : '';
            // 用户手机号
            $mobile = isset($members['mobile']) ? $members['mobile'] : '';
            // 异步
            $isAsync = isset($result['data']['is_async']) ? $result['data']['is_async'] : 0;

            if ($hidePhone['key_value']) {
                $mobile = '13699278164';
            }

            if ($result['result'] == 'true' && $result['data']['supplier_order_id'])
            {
                $syncStatus = $isAsync ? 0 : 1;
                $thirdResult = app::get('b2c')->model('o2o_thirdOrder')->addThirdOrder($sdf['order_id'], $supplierBn, $result['data']['supplier_order_id'], $bn, $syncStatus);

                if ( ! $thirdResult)
                {
                    Neigou\Logger::General("o2o_third_coupon_send", array('action'=>'send_third_coupon', 'order_id' => $sdf['order_id'], 'member_id' => $members['member_id'], 'bn' => $bn, 'mobile' => $mobile, 'result' => $result));
                }
                // 异步
                if ($isAsync)
                {
                    return $thirdResult ? true : false;
                }
            }

            if ( ! is_array($coupons))
            {
                $coupons = array($coupons);
            }
            foreach ($coupons as $coupon)
            {
                if (is_array($coupon))
                {
                    $coupon = implode('<BR/>', $coupon);
                }
                // 保存激活码发送
                if ( ! app::get("b2c")->model('o2o_products')->add_third_coupon($supplierOrderId, $coupon, '', '', '', '', $members['member_id'], '', $num, $bn, $sdf['order_id'], $mobile, time(), '',$log, '', 'addOrder', ''))
                {
                    return false;
                }
            }

            if (isset($result['result']) && $result['result'] == 'true')
            {
                return true;
            }
        }
        return false;
    }

    // 重新发送激活码
    public function retrySend($order_id, $member_id, $bn, $name, $mobile,$message_channel = "")
    {
        \Neigou\Logger::General("o2o_thirdproducts.retrySend", array('action'=>'retrySend', 'order_id' => $order_id, 'member_id' => $member_id, 'bn' => $bn, 'mobile' => $mobile));

        // 获取供应商的BN
        $supplierBn = self::_getSupplierBn($bn);

        // 获取供应商驱动标识，不存在则取默认的供应商
        $supplierName = isset(self::$this->_supportSuppliers[$supplierBn]) ? self::$this->_supportSuppliers[$supplierBn] : current($this->_supportSuppliers);

        // 获取供应商驱动标识，不存在则取默认的供应商
        if ( ! isset($this->_supportSuppliers[$supplierBn]))
        {
            reset($this->_supportSuppliers);
            $supplierBn = key($this->_supportSuppliers);
        }

        // 联动票务
        if ($supplierBn === 'LDPW')
        {
            return kernel::single('b2c_o2o_liandong_coupon')->retrySend($order_id, $member_id, $bn, $name, $mobile,$message_channel);
        } // SHOP 重发消费码
        elseif ($supplierBn === 'SHOPNG' || $supplierBn == 'SHOP') {
            $result = $this->supplierSendTicket($bn, $mobile, $member_id, $order_id, null, 'reSend', $message_channel);

            if (!isset($result['result']) || $result['result'] != 'true') {
                return false;
            }
            return true;
        }
        // 非第三方重发
        elseif (in_array($supplierBn, array('OF', 'YDSH')))
        {
            $info = app::get("b2c")->model('o2o_products')->getLdInfoByOrderId($order_id, $member_id, $bn);
            if (empty($info))
            {
                return json_encode(array("Result" => 'false', "ErrorMsg" => '该订单不存在', 'ErrorId' => '10000'));
            }
            foreach($info as $key=>$val){
                if(!$val['aux_code']){
                    $content="您购买的电子商品为特殊商品，系统无法自动生成电子券码。请拨打客服热线400-6666-365，了解详情。";
                    sms189_mobile::sendContent($mobile,$content,$message_channel);
                }else{
                    $content=$name.",消费码:[".str_replace('<BR/>', ' ', $val['aux_code'])."]~详情请登录“个人中心”查看。";
                    sms189_mobile::sendContent($mobile,$content,$message_channel);
                }
            }
        }elseif (in_array($supplierBn, array('XMLY','ZH'))){
            $info = app::get("b2c")->model('o2o_products')->getLdInfoByOrderId($order_id, $member_id, $bn);
            if (empty($info))
            {
                return json_encode(array("Result" => 'false', "ErrorMsg" => '该订单不存在', 'ErrorId' => '10000'));
            }
            foreach($info as $key=>$val){
                if(!$val['aux_code']){
                    $content="您购买的电子商品为特殊商品，系统无法自动生成电子券码。请拨打客服热线400-6666-365，了解详情。";
                    sms189_mobile::sendContent($mobile,$content,$message_channel);
                }else{
                    $content = '您已成功购买【'.$name.'】，券码：'.str_replace('<BR/>', ' ', $val['aux_code']).',请到在线教育商城订单页面激活使用。';
                    sms189_mobile::sendContent($mobile,$content,$message_channel);
                }
            }
        }
        else
        {
            // 获取第三方订单ID
            $thirdOrderInfo = app::get('b2c')->model('o2o_products')->get_third_order($order_id);
            $thirdOrderId = self::_getOriginThirdOrderId($thirdOrderInfo[0]['third_order_id'], $supplierBn);

            // 请求 SALYUT 重新发送短信
            $requestParam = array(
                'class_obj' => 'O2O',
                'method'    => 'order',
                'action'    => 'resend', // 操作
                'supplier'  => $supplierName, // 供应商
                'data'      => array(
                    // 订单信息
                    'order' => array(
                        'order_id'          => $order_id,
                        'third_order_id'    => $thirdOrderId,
                    ),
                    'phone' => $mobile,
                    // 预订信息
                    'order_extend'   => isset($orderExtend[0]['extends']) && ! empty($orderExtend[0]['extends']) ? unserialize($orderExtend[0]['extends']) : '',
                )
            );

            $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
            $requestParam['token'] = $token;
            $curl   = new \Neigou\Curl();
            $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);

            \Neigou\Logger::General("o2o_thirdproducts.retrySend", array('action'=>'retrySend', 'requestData'=> json_encode($requestParam), 'result' => $result));

            $result = $result ? json_decode($result, true) : array();

            if(isset($result['result']) && $result['result'] == 'true')
            {
                return json_encode(array("Result"=>'true',"ErrorMsg"=>'发送成功','ErrorId'=>''));
            }

            return json_encode(array("Result"=>'false', "ErrorMsg"=>'发送失败', 'ErrorId'=>'10000'));
        }

        return json_encode(array("Result"=>'false', "ErrorMsg"=>'发送失败', 'ErrorId'=>'10000'));
    }

    // --------------------------------------------------------------------

    /*
     * 获取SALYUT货品的上下架状态
     *
     * @param   $bn     mixed   货品编码
     * @return  mixed
     */
    public function getSalyutProductMarketable($bn = null)
    {
        if (empty($bn))
        {
            return false;
        }

        $bns = is_array($bn) ? implode(',', $bn) : $bn;

        // 请求SALYUT获取商品的上下架状态数据
        $requestParam = array(
            'class_obj' => 'SalyutGoods',
            'method'    => 'getProductMarketable',
            'bn'        => $bns,
        );

        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;

        $curl   = new \Neigou\Curl();
        $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);
        $result = $result ? json_decode($result, true) : array();

        if (empty($result) OR ! isset($result['Result']) OR $result['Result'] == 'false')
        {
            return false;
        }

        if ( ! is_array($bn))
        {
            return isset($result['Data'][$bn]) && $result['Data'][$bn] == 'true' ? true : false;
        }

        $return = array();
        foreach ($bn as $v)
        {
            $return[$v] = isset($result['Data'][$bn]) && $result['Data'][$bn] == 'true' ? 'true' : 'false';
        }

        return $return;
    }

    // --------------------------------------------------------------------

    /*
     * 请求 SALYUT 发送激活码
     *
     * @param   $bn             string      货品编码
     * @param   $supplierName   string      供应商名称
     * @param   $num            int         购买数量
     * @param   $memberId       int         用户 ID
     * @param   $orderExtend    string      订单预订信息
     * @return  mixed
     */
    public function supplierSendCoupon($bn, $supplierName, $num, $memberId, $sdf, $orderExtend)
    {
        \Neigou\Logger::General("o2o_third_send_coupon",array('action'=>'supplierSendCoupon','order'=>$sdf, 'bn' => $bn, 'num' => $num));

        $model = &app::get("b2c")->model('o2o_products');
        $third_product = $model->get_third_product_bn($bn);
        $supplierProductBn = $third_product[0]['third_product_bn'];

        // 请求 SALYUT 发送激活码
        $requestParam = array(
            'class_obj' => 'O2O',
            'method'    => 'order',
            'action'    => 'sendCoupon', // 操作
            'supplier'  => $supplierName, // 供应商
            'data'      => array(
                // 订单信息
                'order' => array(
                    'order_id'      => $sdf['order_id'],
                    'extend_data'   => $sdf['extend_data'],
                    'number'        => $num,
                    'product_bn'    => $bn,
                    'third_product_bn' => $supplierProductBn,
                    'createtime'    => $sdf['createtime'],
                ),
                // 用户信息
                'member'    => array(
                    'member_id' => $memberId,
                ),
                // 预订信息
                'order_extend'   => isset($orderExtend[0]['extends']) && ! empty($orderExtend[0]['extends']) ? unserialize($orderExtend[0]['extends']) : '',
            )
        );



        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $curl   = new \Neigou\Curl();
        $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);
        \Neigou\Logger::General("o2o_thirdproducts.supplierSendCoupon", array('action'=>'supplierSendCoupon', 'requestData'=> json_encode($requestParam), 'result' => $result));

        $result = $result ? json_decode($result, true) : array();

        return $result;
    }

    /*
     * 请求 SHOP 发送消费码
     *
     * @param   $bn             string      货品编码
     * @param   $num            int         购买数量
     * @param   $memberId       int         用户 ID
     * @param   $sdf            array       订单信息
     * @param   $orderExtend    string      订单预订信息
     * @param   $type           string      使用类型
     * @return  mixed
     */
    public function supplierSendTicket($bn, $num, $memberId, $sdf, $orderExtend, $type = 'sendTicket', $message_channel = '')
    {
        \Neigou\Logger::General("o2o_shopng." . $type, array('action' => 'before' . $type, 'order' => $sdf, 'bn'  => $bn, 'num' => $num));

        $model = &app::get("b2c")->model('o2o_products');

        $data = array();
        // 请求 SHOP 发送消费码
        if ($type == 'sendTicket') {
            // 订单信息
            $data = array(
                'class_obj'        => 'Shop/Channel/V1/Order/Order',
                'method'           => $type,
                'order'            => $sdf,
                'order_id'         => $sdf['order_id'],
                'number'           => $num,
                'product_bn'       => $bn,
                'supplier'         => 'SHOPNG',
                'member_id'        => $memberId,
                'order_extend'     => isset($orderExtend[0]['extends']) && ! empty($orderExtend[0]['extends']) ? unserialize($orderExtend[0]['extends']) : '',
            );
        } else {
            if (!empty($message_channel)) {
                $info = app::get("b2c")->model('o2o_products')->getLdInfoByOrderId($sdf, $memberId, $bn);
                if (empty($info)) {
                    \Neigou\Logger::General("o2o_shopng_result.getOrderBnLastSms".$type, array(
                        'action' => 'getLdInfoByOrderId', 'requestData' => json_encode(array($sdf, $memberId, $bn)),
                        'result' => $info,
                    ));

                    return true;
                }

                $ticket_code = array();
                foreach ($info as $val) {
                    if ($val['aux_code']) {
                        $ticket_code[] = $val['aux_code'];
                    }
                }
                if (!$ticket_code) {
                    \Neigou\Logger::General("o2o_shopng_result.getOrderBnLastSms".$type, array(
                        'action' => 'nocode', 'requestData' => json_encode(array($sdf, $memberId, $bn)),
                        'result' => $info,
                    ));

                    return true;
                }

                $data = array(
                    'class_obj' => 'Shop/Channel/V1/Order/Order',
                    'method' => 'getOrderBnLastSms',
                    'ticket_code' => $ticket_code,
                    'order_id' => $sdf,
                );
            } else {
                $data = array(
                    'class_obj' => 'Shop/Channel/V1/Order/Order',
                    'method' => $type,
                    'member_id' => $memberId,
                    'mobile' => $num,
                    'order_id' => $sdf,
                );
            }
        }

        $token = kernel::single('b2c_safe_apitoken')->generate_token($data, SHOP_APPSECRET);
        $data['token'] = $token;
        $curl   = new \Neigou\Curl();
        $result = $curl->Post(SHOP_DOMAIN. '/Shop/OpenApi/apirun/', $data);
        \Neigou\Logger::General("o2o_shopng_result." . $type, array('action' => 'supplier' . $type, 'requestData' => json_encode($data), 'result' => $result));

        $result = $result ? json_decode($result, true) : array();
        if (!empty($message_channel) && $type == 'reSend' && $result['result'] == 'true' && !empty($result['data']) && is_array($result['data'])) {
            $sendData = array(
                'channel' => $message_channel,
                'data' => array(
                    array(
                        'receiver' => array($num),
                        'template_id' => 66,
                        'template_param' => array('name' => '', 'code' => '', 'date' => ''),
                    ),
                ),
            );

            foreach ($result['data'] as $datum) {
                if ($datum['ticket_code']) {
                    $sendData['data'][0]['template_param'] = array(
                        'name' => $datum['product_name'], 'code' => $datum['ticket_code'],
                        'date' => $datum['expire_date'],
                    );

                    $this->sendMessage($sendData);
                }
            }
        }

        return $result;
    }

    /**
     * @param $sendData
     * @return bool
     */
    private function sendMessage($sendData)
    {
        $result = \Neigou\ApiClient::doServiceCall('message', 'Message/sendMessage', 'v1', null, $sendData,
            array('debug' => false));

        \Neigou\Logger::General('store.sms', array(
            'action' => 'o2o.reSendMessage',
            'data' => $sendData,
        ));

        if ($result['service_status'] == 'OK' &&
            $result['service_result']['error_code'] == 'SUCCESS' &&
            $result['service_data']['error_code'] == 'SUCCESS' &&
            $result['service_data']['data']
        ) {
            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------

    /*
     * 请求 SALYUT 检查预下单
     *
     * @param   $orderId        string      订单编号
     * @param   $bn             string      货品编码
     * @param   $quantity       int         购买数量
     * @param   $orderExtends   string      订单扩展
     * @return  mixed
     */
    public function orderPrepare($orderId, $bn, $quantity, $orderExtends)
    {
        // 获取第三方编码
        $third_product = app::get("b2c")->model('o2o_products')->get_third_product_bn($bn);
        $supplierProductBn = $third_product[0]['third_product_bn'];

        // 获取供应商驱动标识，不存在则取默认的供应商
        $supplierBn = self::_getSupplierBn($bn);
        if (in_array($supplierBn, array('SHOP', 'SHOPNG'))) {
            return array('result' => 'true');
        }

        if (!isset($this->_supportSuppliers[$supplierBn]))
        {
            reset($this->_supportSuppliers);
            $supplierBn = key($this->_supportSuppliers);
        }

        // 请求 SALYUT 检查预下单
        $requestParam = array(
            'class_obj' => 'O2O',
            'method'    => 'order',
            'action'    => 'prepare', // 操作
            'supplier'  => $this->_supportSuppliers[$supplierBn], // 供应商
            'data'      => array(
                // 订单信息
                'order' => array(
                    'order_id'      => $orderId,
                    'number'        => $quantity,
                    'product_bn'    => $bn,
                    'third_product_bn' => $supplierProductBn,
                ),
                // 预订信息
                'order_extend'   => $orderExtends,
            ),
        );

        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $curl   = new \Neigou\Curl();
        $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);

        \Neigou\Logger::General("o2o_thirdproducts.order.prepare", array('action'=>'orderPrepare', 'requestData'=> json_encode($requestParam), 'result' => $result));

        return $result ? json_decode($result, true) : array();
    }


    /** 获取券信息
     *
     * @param string $orderId
     * @param string $couponNo
     * @param string $bn
     * @return array|mixed
     * @author liuming
     */
    public function getCouponInfo($orderId = '',$bn = '',$couponNo = '',$memberId = 0){
        // 获取第三方编码
        $third_product = app::get("b2c")->model('o2o_products')->get_third_product_bn($bn);
        $supplierProductBn = $third_product[0]['third_product_bn'];

        // 获取供应商驱动标识，不存在则取默认的供应商
        $supplierBn = self::_getSupplierBn($bn);

        if ( ! isset($this->_supportSuppliers[$supplierBn]))
        {
            reset($this->_supportSuppliers);
            $supplierBn = key($this->_supportSuppliers);
        }

        // 请求 SALYUT 检查预下单
        $requestParam = array(
            'class_obj' => 'O2O',
            'method'    => 'order',
            'action'    => 'getCouponInfo', // 操作
            'supplier'  => $this->_supportSuppliers[$supplierBn], // 供应商
            'data'      => array(
                // 订单信息
                'order' => array(
                    'order_id'      => $orderId,
                    'coupon_no'        => $couponNo,
                    'product_bn'    => $bn,
                    'third_product_bn' => $supplierProductBn,
                ),
                'member' => array(
                    'member_id' => $memberId,
                ),
            ),
        );

        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $curl   = new \Neigou\Curl();

        $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);

        \Neigou\Logger::General("o2o_thirdproducts.order.prepare", array('action'=>'orderPrepare', 'requestData'=> json_encode($requestParam), 'result' => $result));

        return $result ? json_decode($result, true) : array();


    }

    /** 批量获卡券信息
     *
     * @param array $orderList
     * @param int $memberId
     * @param string $supplier
     * @return array|mixed
     * @author liuming
     */
    public function batchGetCouponInfo($orderList = array(),$memberId = 0,$supplier = ''){

        // 请求 SALYUT 检查预下单
        $requestParam = array(
            'class_obj' => 'O2O',
            'method'    => 'order',
            'action'    => 'batchGetCouponInfo', // 操作
            'supplier'  => $this->_supportSuppliers[$supplier], // 供应商
            'data'      => array(
                // 订单信息
                'order' => array(
                    'order_list'      => $orderList,
                ),
                'member' => array(
                    'member_id' => $memberId,
                ),
            ),
        );

        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $curl   = new \Neigou\Curl();

        $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);

        \Neigou\Logger::General("o2o_thirdproducts.order.prepare", array('action'=>'orderPrepare', 'requestData'=> json_encode($requestParam), 'result' => $result));

        return $result ? json_decode($result, true) : array();


    }


    /** 卡券激活
     *
     * @param string $orderId
     * @param string $couponNo
     * @param string $bn
     * @param string $mobile
     * @return array|mixed
     * @author liuming
     */
    public function activateCoupon($orderId = '',$couponNo = '',$bn = '',$mobile = '',$memberId){
        // 获取第三方编码
        $third_product = app::get("b2c")->model('o2o_products')->get_third_product_bn($bn);
        $supplierProductBn = $third_product[0]['third_product_bn'];

        // 获取供应商驱动标识，不存在则取默认的供应商
        $supplierBn = self::_getSupplierBn($bn);
        if ( ! isset($this->_supportSuppliers[$supplierBn]))
        {
            reset($this->_supportSuppliers);
            $supplierBn = key($this->_supportSuppliers);
        }

        // 请求 SALYUT 检查预下单
        $requestParam = array(
            'class_obj' => 'O2O',
            'method'    => 'order',
            'action'    => 'activateCoupon', // 操作
            'supplier'  => $this->_supportSuppliers[$supplierBn], // 供应商
            'data'      => array(
                // 订单信息
                'order' => array(
                    'order_id'      => $orderId,
                    'no'        => $couponNo,
                    'product_bn'    => $bn,
                    'third_product_bn' => $supplierProductBn,
                    'mobile' => $mobile,
                ),
                'member' => array(
                    'member_id' => $memberId,
                ),

            ),
        );

        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $curl   = new \Neigou\Curl();
        $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);

        \Neigou\Logger::General("o2o_thirdproducts.order.prepare", array('action'=>'orderPrepare', 'requestData'=> json_encode($requestParam), 'result' => $result));

        return $result ? json_decode($result, true) : array();
    }

    /**
     * 获取供应商订单券码信息
     *
     * @param $orderId          string 订单
     * @param $supplierBn       string 供应商编码
     * @param $supplierOrderId  string 供应商订单ID
     * @param $bn               string 商品编码
     * @return array
     */
    public function getOrderCouponInfo($orderId, $supplierBn, $supplierOrderId, $bn) {
        $return = array();
        if ( ! isset($this->_supportSuppliers[$supplierBn]))
        {
            return $return;
        }

        // 请求 SALYUT 检查预下单
        $requestParam = array(
            'class_obj' => 'O2O',
            'method'    => 'order',
            'action'    => 'getOrderCouponInfo', // 操作
            'supplier'  => $this->_supportSuppliers[$supplierBn], // 供应商
            'data'      => array(
                // 订单信息
                'order' => array(
                    'order_id'           => $orderId,
                    'supplier_order_id'  => $supplierOrderId,
                    'product_bn'         => $bn,
                ),
            ),
        );

        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $curl   = new \Neigou\Curl();

        $result = $curl->Post(SALYUT_DOMIN. '/OpenApi/apirun/', $requestParam);

        \Neigou\Logger::Debug("o2o_thirdproducts.order.get", array('action'=>'getOrderCouponInfo', 'requestData'=> json_encode($requestParam), 'result' => $result));

        return $result ? json_decode($result, true) : array();
    }

    /**
     * 批量获SHOP卡券信息
     *
     * @param array $orderList
     * @return array
     */
    public function batchGetShopCouponInfo($orderList = array())
    {
        $return = array();

        // SHOP 获取券码
        $data = array(
            'class_obj'        => 'Shop/Channel/V1/Order/Order',
            'method'           => 'getOrderTicketInfo',
            'order_list'       => $orderList,
        );

        $token = kernel::single('b2c_safe_apitoken')->generate_token($data, SHOP_APPSECRET);
        $data['token'] = $token;
        $curl   = new \Neigou\Curl();
        $result = $curl->Post(SHOP_DOMAIN. '/Shop/OpenApi/apirun/', $data);

        $result = json_decode($result, true);

        if (empty($result) OR empty($result['data']))
        {
            return $return;
        }

        return $result['data'];
    }


    // --------------------------------------------------------------------

    /*
     * 通过BN获取供应商的标识
     *
     */
    private static function _getSupplierBn($bn)
    {
        if (empty($bn) OR strpos($bn, '-') === false)
        {
            return '';
        }

        return strtoupper(substr($bn, 0, strpos($bn, '-')));
    }

    // --------------------------------------------------------------------

    /*
     * 检查手机号
     *
     * @param   $mobile     string  手机号
     * @return  boolean
     */
    private static function _checkMobile($mobile)
    {
        return preg_match('/^1[3456789][0-9]{9}$/', $mobile);
    }

    // --------------------------------------------------------------------

    /*
     * 获取原始的第三方订单ID
     *
     * @param   $thirdOrderId   string      第三方订单 ID
     * @param   $supplierBn     string      第三方供应商编码
     * @return  string
     */
    private static function _getOriginThirdOrderId($thirdOrderId, $supplierBn = '')
    {
        if ( ! empty($supplierBn))
        {
            return str_replace($supplierBn. '-', '', $thirdOrderId);
        }

        if (strpos($thirdOrderId, '-') !== false)
        {
            $thirdOrderId = substr($thirdOrderId, strpos($thirdOrderId, '-') + 1);
        }

        return $thirdOrderId;
    }

}
