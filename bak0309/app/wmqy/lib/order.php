<?php

/**
 * 中粮订单推送信息
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
class wmqy_order
{
    private $_curl;

    public function __construct()
    {
        // curl
        $this->_curl = new \Neigou\Curl();
        $this->_curl->time_out = 29;
    }

    public function initOrder($order_id)
    {
        if (!$this->check_channel($order_id, $order_info)) {
            echo '跳过：channel非中粮:' . $order_id, PHP_EOL;
            return true;
        }
        if ($order_info['split'] != 1 || substr($order_info['items'][0]['bn'], 0, 5) === 'WMQY-') {
            echo '跳过：order_split!=1或商品前缀是WMQY-:' . $order_id, PHP_EOL;
            return true;
        }
        /* @var wmqy_mdl_order $order_model */
        $order_model = app::get('wmqy')->model('order');
        $order_model->add(array(
            'order_id' => $order_id,
            'created_at' => time(),
            'status' => 'ready',
            'status_msg' => '初始化',
        ));
        return true;
    }

    private function check_channel($order_id, &$order_info)
    {
        $order_info = kernel::single('b2c_service_order')->getOrderInfo($order_id);
        //更新用户所在场景信息
        $companyModel = app::get('b2c')->model_for_config('company', b2c_util::get_club_db_conf());
        $channel_info = $companyModel->GetChannelByCompanyId($order_info['company_id']);
        $channel = $channel_info['channel'];
        if (!in_array($channel, array('qwomai', 'qiyegou'))) {
            return false;
        }
        return true;
    }

    public function push($wmqy_order_info, &$err_msg = '')
    {
        $order_id = $wmqy_order_info['order_id'];
        if ($this->check_channel($order_id, $order_info) === false) {
//            return false;
        } elseif ($order_info['split'] != 1) {
            return false;
        }

//        print_r($order_info);
//        die;

        $point_discount = $this->getPointDis($order_info['company_id']);
        $bn_arr = array();
        /* @var wmqy_mdl_order $order_model */
        $order_model = app::get('wmqy')->model('order');
        $point_amount_total = 0;
        $payed_amount_total = 0;

        $items = array();
        /* @var wmqy_aftersale $lib_after */
        $lib_after = kernel::single('wmqy_aftersale');
        foreach ($order_info['items'] as &$item) {
            $key = $lib_after->_createUniqueProductBn($item['bn'], $item['item_type'], $item['p_bn']);
            $items[$key] = $item;
            foreach ($item['gift'] as $gift_item) {
                $bn_arr[] = $gift_item['item_bn'];
                $gift_key = $lib_after->_createUniqueProductBn($gift_item['bn'], $gift_item['item_type'], $item['bn']);
                $gift_item['pmt_amount'] = bcmul($gift_item['price'], $gift_item['nums'], 2);
                $items[$gift_key] = $gift_item;
            }
            if (isset($items[$key]['gift'])) {
                unset($items[$key]['gift']);
            }
        }
        $order_info['items'] = $items;
        foreach ($order_info['items'] as &$item) {
            $data = array(
                'product_bn' => $item['bn'],
                'count' => $item['nums'],
                'price' => $item['price'],
                'pmt_amount' => $item['pmt_amount'],
                'cost_freight' => $item['cost_freight'],
                'point_amount' => $item['point_amount'],
                'payed_amount' => $this->getPayedAmount($item),
                'point_discount' => $point_discount,
                'item_type' => $item['item_type'] ? $item['item_type'] : 'product',
                'p_bn' => $item['p_bn'] ? $item['p_bn'] : null,
            );


            $order_items[] = $this->createOrderItem($data, $item, $wmqy_order_item);
            $data['order_id'] = $order_info['order_id'];
            $data = array_merge($data, $wmqy_order_item);
            $order_model->addWmqyOrderItem($data);
            $bn_arr[] = $item['bn'];
            $point_amount_total = bcadd($point_amount_total, $data['point_amount'], 2);
            $payed_amount_total = bcadd($payed_amount_total, $data['payed_amount'], 2);
        }

        /* @var wmqy_mdl_goods $goods_model */
        $goods_model = app::get('wmqy')->model('goods');
        $wmqy_sku_list = $goods_model->getByBns($bn_arr);
        if (count($wmqy_sku_list) !== count($bn_arr)) {
            $err_msg = '商品未授权:' . json_encode($wmqy_sku_list) . '====' . json_encode($bn_arr);
            $data_wmqy_order['pre_status'] = $wmqy_order_info['status'];
            $data_wmqy_order['fail_count'] = $wmqy_order_info['fail_count'] + 1;
            if ($data_wmqy_order['fail_count'] > 3) {
                $data_wmqy_order['status'] = 'fail';
            }
            $order_model->save($data_wmqy_order, "order_id={$order_id}");
            \Neigou\Logger::General('wmqy.order.push.err', array('bn_arr' => $bn_arr, 'wmqy_sku_list' => $wmqy_sku_list));
            return false;
        }

        $mall_id = 0;
        $product_info = app::get('b2c')->model('products')->getProductByBn($bn_arr);
        foreach ($order_items as &$order_item) {
            $order_item['goodsid'] = $wmqy_sku_list[$order_item['outskuno']]['sku_id'];
            $order_item['suborderno'] = $wmqy_sku_list[$order_item['outskuno']]['sku_id'];
            $order_item['goodsname'] = mb_substr($order_item['goodsname'], 0, 32, 'utf-8') . '_' . $product_info[$order_item['outskuno']]['product_id'];
            $mall_id = $wmqy_sku_list[$order_item['outskuno']]['mall_id'];
        }
        // 地址信息
        $region_info = $this->getRegions($order_info);
        // 公司信息
        $company_info = $this->getCompanyInfo($order_info['company_id']);
        // 发票信息
        $invoice_info = $this->getOrderInvoiceDetail($order_info['order_id']);
        $extend_data = $order_info['extend_data'];
        // 支付金额
        $payment = bcmul($order_info['final_amount'], 100, 2);
        // 优惠金额
        $discount_fee = 0;
        if ($order_info['pmt_amount'] > 0) {
            $discount_fee = bcmul($order_info['pmt_amount'], 100, 2);
        }
        // 积分折扣金额
        $avgorderdiscount = 0;
        foreach ($order_items as $order_item_info) {
            $avgorderdiscount = bcadd($avgorderdiscount, $order_item_info['avgorderdiscount'], 2);
        }
        $discount_fee = bcadd($discount_fee, $avgorderdiscount, 2);

        $point_freight = 0;
        $payed_freight = 0;
        if ($point_amount_total > 0 && $point_discount > 0 && $point_discount < 1) {
            $amount_total = bcadd($point_amount_total, $payed_amount_total, 2);
            // 积分占比
            $dis_point_of_freight = bcdiv($point_amount_total, $amount_total, 2);
            // 积分分摊运费
            $point_freight = bcmul($order_info['cost_freight'], $dis_point_of_freight, 2);
            $payed_freight = bcsub($order_info['cost_freight'], $point_freight, 2);

            $dis_point_amount = $point_amount_total - bcdiv($avgorderdiscount, 100, 2);
        }

        $pars = array(
            'orderno' => $order_id, // 外部订单号
            'createtime' => date('Y-m-d H:i:s', time()), // 交易创建时间
            'payment' => (float)$payment, // 实际支付金额  单位 分 （包含运费）
            'freightprice' => (float)bcmul($order_info['cost_freight'], 100, 2), // 运费（包邮为0）
            'orderdiscount' => (float)$discount_fee, // 订单优惠金额（默认0）

            'isprintprice' => empty($extend_data['isprintprice']) ? 1 : 0, // 是否打印金额 0不打印 1打印  默认 1打印
            'invoicetype' => empty($invoice_info) ? 0 : 1, // 是否要发票  0=不开发票 1开发票
            'invoicetitle' => empty($invoice_info['company_name']) ? '' : $invoice_info['company_name'], // 发票抬头 个人/公司  开发票时不为空
            'invoicecode' => empty($invoice_info['tax_number']) ? '' : $invoice_info['tax_number'], // 发票纳税人识别号
            'userid' => $order_info['ship_mobile'], // 用户id
            'nickname' => mb_substr($order_info['ship_name'], 0, 15, 'UTF8'), // 用户昵称
            'enterpriseid' => $order_info['company_id'], // 企业id
            'enterprisename' => $company_info['display_name'], // 企业名称
            // 用户数据
            'fullname' => mb_substr($order_info['ship_name'], 0, 15, 'UTF8'), // 买家昵称
            'receiver_name' => mb_substr($order_info['ship_name'], 0, 15, 'UTF8'), // 收货人的姓名
            'telephone' => $order_info['ship_mobile'], // 收货人电话
            'mobile' => $order_info['ship_mobile'], // 收货人手机号
            // 地址
            'province' => $region_info['wm_province'], // 配送省份
            'city' => $region_info['wm_city'], // 配送市
            'county' => $region_info['wm_county'], // 配送区
            'fulladdress' => str_replace(array("\r\n", "\r", "\n"), '', $order_info['ship_addr']), // 详细地址
            'provinceid' => $region_info['wm_province_id'], // 省级编码
            'cityid' => $region_info['wm_city_id'], // 市级编码
            'countyid' => $region_info['wm_county_id'], // 区级编码

            'venderremark' => '',//商家备注
            'orderremark' => empty($order_info['memo']) ? '' : $order_info['memo'],//客户订单备注
            'isfresh' => 0,//是否生鲜订单 1是 0否
            'dirsend' => 1,//自营订单标识  0自营  其它 否
            'supplierid' => 0,//供应商编码 非自营不可为空
            'suppliername' => '内购',//供应商名称

            'itemlist' => $order_items,
        );
        /* @var wmqy_mdl_companymapping $company_mapping_model */
        $company_mapping_model = app::get('wmqy')->model('companymapping');
        $company_mapping_info = $company_mapping_model->getByWhere('ng_company_id=' . $order_info['company_id']);
        if (!empty($company_mapping_info)) {
            $pars['enterpriseid'] = $company_mapping_info['wmqy_company_id'];
        }
//        echo $mall_id;
//print_r($pars);die;
        $res = $this->_curl->Post(OPENAPI_DOMAIN . '/ChannelInterop/V1/WMQY/Web/PushOrder?mall_id=' . $mall_id, $pars);
        $data_wmqy_order = array(
//            'order_id' => $order_id,
            'point_amount' => bcmul($point_amount_total, 100, 2),
            'payed_amount' => bcmul($payed_amount_total, 100, 2),
            'payment' => $payment,
            'dis_point_amount' => bcmul($dis_point_amount, 100, 2),
            'payed_freight' => bcmul($payed_freight, 100, 2),
            'point_freight' => bcmul($point_freight, 100, 2),
            'pars' => json_encode($pars),
            'result' => json_encode($res),
            'updated_at' => time(),
        );
        if ($dis_point_amount == 0) {
            $data_wmqy_order['dis_point_amount'] = $data_wmqy_order['point_amount'];
        }
        $res = json_decode($res, true);
        /* @var wmqy_mdl_log $log_model */
        $log_model = app::get('wmqy')->model('log');
        $log_model->add(array(
            'type' => 'order',
            'data_bn' => $order_id,
            'pars' => addslashes(json_encode($res['Data']['pars'])),
            'resp' => $res['Data']['resp'],
            'created_at' => time(),
        ));
        $resp = json_decode($res['Data']['resp'], true);
//        print_r($resp);die;
        $data_wmqy_order['pre_status'] = $wmqy_order_info['status'];
        $return = true;
        if ($resp['iserro'] === 0 && $resp['errcode'] === '0000') {
            $data_wmqy_order['status'] = 'readypay';
            $data_wmqy_order['fail_count'] = 0;
            $err_msg = '';
        } else {
            $data_wmqy_order['fail_count'] = $wmqy_order_info['fail_count'] + 1;
            if ($data_wmqy_order['fail_count'] > 10) {
                $data_wmqy_order['status'] = 'fail';
            }
            $err_msg = '接口失败';
            $return = false;
        }
        $res = $order_model->save($data_wmqy_order, "order_id={$order_id}", $sql_log);
        if ($res['rs'] !== true) {
            $data_wmqy_order = array(
                'fail_count' => $wmqy_order_info['fail_count'] + 1,
                'result' => $res['sql']
            );
            if ($data_wmqy_order['fail_count'] > 10) {
                $data_wmqy_order['status'] = 'fail';
            }
            $order_model->save($data_wmqy_order, "order_id={$order_id}", $sql);
            \Neigou\Logger::General('wmqy.order.push.err', array('order_id' => $order_id, 'sql' => $sql_log, 'sql_res' => $res));
            $return = false;
        }
        return $return;
    }

    public function pay($order_id)
    {
        $order_info = kernel::single('b2c_service_order')->getOrderInfo($order_id);
        $pay_time = null;
        $paydetaillist = $this->createPayPars($order_info, $pay_time);
        $payment = bcmul($order_info['final_amount'], 100, 2);
        /* @var wmqy_mdl_order $order_model */
        $order_model = app::get('wmqy')->model('order');
        $wmqy_order_info = $order_model->getByWhere("order_id='{$order_info['order_id']}'");
        if (!empty($paydetaillist['POINT'])) {
            if ($paydetaillist['POINT']['amount'] == $wmqy_order_info['point_amount']) {
                $paydetaillist['POINT']['amount'] = $wmqy_order_info['dis_point_amount'];
                $payment = $wmqy_order_info['payment'];
            }
        }
        $pars = array(
            'orderno' => $order_info['order_id'],
            'payment' => (float)$payment,
            'paytime' => $pay_time,//'mwomai',
            'payno' => $order_info['order_id'],//'mwomai',
            'paydetaillist' => array_values($paydetaillist)
        );
        $res = $this->_curl->Post(OPENAPI_DOMAIN . '/ChannelInterop/V1/WMQY/Web/PayOrder', $pars);
        $res = json_decode($res, true);
        /* @var wmqy_mdl_log $log_model */
        $log_model = app::get('wmqy')->model('log');
        $log_model->add(array(
            'type' => 'pay',
            'data_bn' => $order_id,
            'pars' => addslashes(json_encode($res['Data']['pars'])),
            'resp' => $res['Data']['resp'],
            'created_at' => time(),
        ));
        $resp = json_decode($res['Data']['resp'], true);
        $data_wmqy_order['pre_status'] = $wmqy_order_info['status'];
        $return = true;
        if ($resp['iserro'] === 0 && $resp['errcode'] === '0000') {
            $data_wmqy_order['status'] = 'succ';
        } else {
            $data_wmqy_order['fail_count'] = $wmqy_order_info['fail_count'] + 1;
            if ($data_wmqy_order['fail_count'] > 10) {
                $data_wmqy_order['status'] = 'fail';
            }
            $return = false;
        }
        $order_model->save($data_wmqy_order, "order_id={$order_id}");
        return $return;
    }

    public function getOrderInfo($order_bn)
    {
        $res = $this->_curl->Post(OPENAPI_DOMAIN . '/ChannelInterop/V1/WMQY/Web/GetOrder', array('orderno' => $order_bn));
        $res = json_decode($res, true);
        $res = json_decode($res['Data']['resp'], true);
        return $res['result'][0];
    }

    public function createPayPars($order_info, &$pay_time = null)
    {
        $record_info = $this->getOrderRecord($order_info['root_pid'], $order_info['point_channel']);
        $account_info = $this->getCompanyAccount($record_info['company_id'], $record_info['scene_id']);
        $pay_info = $this->getPayInfo($order_info['root_pid']);

//        print_r($record_info);
//        print_r($account_info);
//        print_r($pay_info);
//        die;


        $paydetail_list = array();
        if (!empty($record_info)) {
            $key = 'WMQY_PAY_TYPE_POINT_' . $account_info['account'];
            $config_info = $this->getPayConfig($key);
            $config_info['config_val'] = preg_replace('/\p{C}+/u', "", $config_info['config_val']);
            $paydetail_point = array(
//                'amount' => $record_info['finish_money'] * 100,
                'amount' => $order_info['point_amount'] * 100,
                'paytype' => intval($config_info['config_val']),
                'detailpayno' => $record_info['business_frozen_code'],
                'paytypename' => $config_info['config_remark'],
            );
            $paydetail_list['POINT'] = $paydetail_point;
        }

        if (!empty($pay_info) && $pay_info['pay_money'] > 0) {
            $key = 'WMQY_PAY_TYPE_MONEY_' . $pay_info['pay_code'];
            $config_info = $this->getPayConfig($key);
            $config_info['config_val'] = preg_replace('/\p{C}+/u', "", $config_info['config_val']);
            $paydetail_point = array(
//                'amount' => $pay_info['pay_money'] * 100,
                'amount' => $order_info['payed'] * 100,
                'paytype' => $config_info['config_val'],
                'detailpayno' => $pay_info['trade_no'],
                'paytypename' => $config_info['config_remark'],
            );
            $paydetail_list['CASH'] = $paydetail_point;
        }
        $pay_time = date("Y-m-d H:i:s", $pay_info['pay_time']);
        return $paydetail_list;
    }

    public function getPayConfig($key)
    {
//        $url = C('SALYUT_DOMAIN') . '/OpenApi/apirun/';
//        $requestParam = array(
//            'class_obj' => 'WMQY',
//            'method' => 'getPayConfig',
//            'key' => $key,
//        );
//        $result = newCurlOpenApi($url, $requestParam);
//        $result = json_decode($result, true);
//        return $result['Data'];

        $requestParam = array(
            'class_obj' => 'WMQY',
            'method' => 'getPayConfig',
            'key' => $key,
        );
        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $result = $this->_curl->Post(SALYUT_DOMIN . '/OpenApi/apirun/', $requestParam);
        $result = json_decode($result, true);
        if ($result['Result'] !== 'true' || empty($result['Data'])) {
            return false;
        }
        return $result['Data'];
    }

    /*
     * 用户积分锁定账户查询
     * @param   $external_order_bn     array      订单id
     */
    function getOrderRecord($root_pid, $point_channel)
    {
        $pars = array(
            'system_code' => 'NEIGOU',
            'channel' => $point_channel,
            'order_id' => $root_pid,
        );
        $ret = \Neigou\ApiClient::doServiceCall('order', 'ScenePoint/OrderRecord/Get', 'v1', null, $pars);
        if ($ret['service_status'] === 'OK' && $ret['service_data']['error_code'] === 'SUCCESS' && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'][0];
        }
        return false;
    }

    /*
     * 获取账户详情
     */
    function getCompanyAccount($company_id, $scene_id)
    {
        $pars = array(
            'company_id' => $company_id,
            'scene_id' => $scene_id,
        );
        $ret = \Neigou\ApiClient::doServiceCall('order', 'ScenePoint/CompanyAccount/Get', 'v1', null, $pars);
        if ($ret['service_status'] === 'OK' && $ret['service_data']['error_code'] === 'SUCCESS' && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'];
        }
        return false;
    }

    /*
     * 支付信息
     * @param   $external_order_bn     array      订单id
     */
    function getPayInfo($external_order_bn)
    {
        $ret = \Neigou\ApiClient::doServiceCall('order', 'OrderPayment/GetList', 'v1', null, array(
            'order_id_list' => array($external_order_bn)
        ));
        if ($ret['service_status'] === 'OK' && $ret['service_data']['error_code'] === 'SUCCESS' && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'][0];
        }
        return false;
    }

    public function getCompanyInfo($company_id)
    {
        $pars = array(
            'company_id' => $company_id,
        );
        $ret = \Neigou\ApiClient::doServiceCall('account', 'Company/QueryById', 'v3', null, $pars);
        if ($ret['service_status'] === 'OK' && $ret['service_data']['error_code'] === 'SUCCESS' && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'];
        }
        return false;
    }

    private function getPayedAmount($item)
    {
        $payed_amount = 0;
        $payed_amount = bcadd($payed_amount, $item['amount'], 2);
        $payed_amount = bcadd($payed_amount, $item['cost_freight'], 2);
        $payed_amount = bcadd($payed_amount, $item['cost_tax'], 2);
        $payed_amount = bcsub($payed_amount, $item['pmt_amount'], 2);
        $payed_amount = bcsub($payed_amount, $item['point_amount'], 2);
        return $payed_amount;
    }

    private function getPointDis($company_id)
    {
        $requestParam = array(
            'class_obj' => 'WMQY',
            'method' => 'getPointDis',
            'company_id' => $company_id,
        );
        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $result = $this->_curl->Post(SALYUT_DOMIN . '/OpenApi/apirun/', $requestParam);
        $result = json_decode($result, true);
        if ($result['Result'] !== 'true' || empty($result['Data'])) {
            return false;
        }
        return $result['Data'];
    }

    public function getRegions($order_info)
    {
        $requestParam = array(
            'class_obj' => 'WMQY',
            'method' => 'getRegions',
            'ship_province' => $order_info['ship_province'],
            'ship_city' => $order_info['ship_city'],
            'ship_county' => $order_info['ship_county'],
        );
        $token = kernel::single('b2c_safe_apitoken')->generate_token($requestParam, OPENAPI_TOKEN_SIGN);
        $requestParam['token'] = $token;
        $result = $this->_curl->Post(SALYUT_DOMIN . '/OpenApi/apirun/', $requestParam);
        $result = json_decode($result, true);
        if ($result['Result'] !== 'true' || empty($result['Data'])) {
            return false;
        }
        if ($result['Data']['wm_province_id'] == $result['Data']['wm_city_id']) {
            $result['Data']['wm_city_id'] = $result['Data']['wm_county_id'];
            $result['Data']['wm_city'] = $result['Data']['wm_county'];
            $result['Data']['wm_county_id'] = '';
            $result['Data']['wm_county'] = '';
        }
        return $result['Data'];
    }

    private function createOrderItem($data, $item, &$wmqy_order_item)
    {
        $ori_price = $data['price'];
        $ori_pmt_amount = $data['pmt_amount'];
        $bn = $data['product_bn'];
        $count = $data['count'];
        $third_sku_id = end(explode('-', $bn));
        $pmt_amount = $data['pmt_amount'];
        $amount = bcmul($data['price'], $count, 2);

        // 计算积分分摊运费
        $point_freight = 0;
        if ($data['cost_freight'] > 0 && $data['point_amount'] > 0 && $data['point_discount'] > 0) {
            // 积分+现金
            $point_money_amount = bcadd($data['point_amount'], $data['payed_amount'], 2);
            // 积分占比
            $point_proportion = bcdiv($data['point_amount'], $point_money_amount, 10);
            // 积分分摊运费
            $point_freight = bcmul($data['cost_freight'], $point_proportion, 2);
            if ($point_freight > $data['point_amount']) {
                $point_freight = $data['point_amount'];
            }
        }

        $payed_freight = (float)bcsub($data['cost_freight'], $point_freight, 2);
        if ($payed_freight > $data['payed_amount']) {
            $point_freight = (float)bcadd($point_freight, bcsub($payed_freight, $data['payed_amount'], 2), 2);
            $payed_freight = $data['payed_amount'];
        }
        // 积分金额
        $point_amount = bcsub($data['point_amount'], $point_freight, 2);

        // 积分折扣
        $avgorderdiscount = 0;
        // 折扣后的积分
        $point_product_dis = $point_amount;
        if ($data['point_discount'] > 0 && $data['point_discount'] <= 1) {
            $dis = bcsub(1, $data['point_discount'], 2);
            $avgorderdiscount = bcmul($point_amount, $dis, 2);
            $point_product_dis = bcsub($point_amount, $avgorderdiscount, 2);
        }
        $goodstotalprice = bcsub($amount, $pmt_amount, 2);
        $avgorderdiscount = bcmul($avgorderdiscount, 100, 2);

        $wmqy_order_item = array(
            'point_freight' => $point_freight,// 积分运费
            'point_product_dis' => $point_product_dis, // 折扣后的积分支付商品金额
            'payed_freight' => $payed_freight,// 现金运费
            'point_amount_dis' => (float)bcadd($point_freight, $point_product_dis, 2), // 折扣后的积分支付商品金额+积分支付的运费
            'pmt_amount' => $pmt_amount,
            'price' => $data['price'],
            'ori_pmt_amount' => $ori_pmt_amount,
            'ori_price' => $ori_price,
        );

        $return_data = array(
            'suborderno' => '',//子订单编码
            'goodsid' => '',//商品编码
            'outskuno' => $bn,//三方平台商品编码
            'goodsname' => $item['name'],//商品名称
            'num' => $count,//商品数量
            'price' => (float)bcmul($data['price'], 100, 2),//商品单价  单位 分
            'goodsdiscount' => (float)bcmul($pmt_amount, 100, 2),//单品折扣  单位 分
            'goodstotalprice' => (float)bcmul($goodstotalprice, 100, 2),//商品总金额 单位分  单价*数量-单品折扣
            'avgorderdiscount' => (float)$avgorderdiscount,//商品平分订单优惠 单位 分
            'isfresh' => 0,//是否生鲜品  1是 0否
            'isselfoperates' => 1,//自营商品标识  0自营  其它 否
            'supplierid' => 0,//供应商编码   非自营 不为空
            'weight' => 1, //重量  单位g
            'brandname' => '品牌', //品牌
            'origin' => '北京', //产地
            'unit' => '袋', //单位
            'specification' => 'spec', //规格
            'brandid' => '50340', //品牌id
            'barcode' => $bn, //条形码
        );
        return $return_data;
    }

    /**
     * 获取订单发票详情
     *
     * @param   $orderId    string   订单ID
     * @return  mixed
     */
    public function getOrderInvoiceDetail($orderId)
    {
        $return = array();

        $orderInvoiceDetail = $this->getServiceInvoiceDetail($orderId);

        if (empty($orderInvoiceDetail)) {
            return $return;
        }

        foreach ($orderInvoiceDetail as $order) {
            $invoiceInfo = $order['invoice_data']['invoice_data']['invoice_info']['member_invoice_data'];

            if (!empty($invoiceInfo)) {
                $return[] = array(
                    'type' => $invoiceInfo['type'],
                    'title' => $invoiceInfo['title'],
                    'tax_number' => $invoiceInfo['tax_number'],
                    'company_name' => $invoiceInfo['company_name'],
                    'email' => $invoiceInfo['email'],
                );
            }
        }

        return $return ? current($return) : array();
    }

    /**
     * 获取发票详情
     *
     * @param   $orderId    string      订单ID
     * @return  mixed
     */
    public function getServiceInvoiceDetail($orderId)
    {
        $return = array();

        // 获取发票结果信息
        $ret = \Neigou\ApiClient::doServiceCall('order', '/Order/Invoice/GetRecord', 'v1', null, array('order_id' => $orderId), array());

        if ($ret['service_data']['error_code'] == 'SUCCESS' && !empty($ret['service_data']['data'])) {
            $return = $ret['service_data']['data'];
        }

        return $return;
    }
}
