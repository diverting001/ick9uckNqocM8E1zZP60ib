<?php

/**
 * 中粮售后推送信息
 * @package     neigou_store
 * @author      guke
 * @since       Version
 * @filesource
 */
class wmqy_aftersale
{
    private $_curl;

    public function __construct()
    {
        // curl
        $this->_curl = new \Neigou\Curl();
        $this->_curl->time_out = 10;
    }

    public function init($after_sale_bn)
    {
        /* @var wmqy_mdl_aftersale $model */
        $model = app::get('wmqy')->model('aftersale');
        //获取售后单
        $after_info = $this->getReturns($after_sale_bn);
        if ($after_info['status'] != 6) {
            echo '跳过：售后状态未完成:' . $after_sale_bn, PHP_EOL;
            return true;
        }
        if (!$this->check_channel($after_info['order_id'], $order_info)) {
            echo '跳过：channel非中粮:' . $after_sale_bn, PHP_EOL;
            return true;
        }
        if ($order_info['split'] != 1 || substr($after_info['product_data'][0]['product_bn'], 0, 5) === 'WMQY-') {
            echo '跳过：order_split!=1或商品前缀是WMQY-:' . $after_sale_bn, PHP_EOL;
            return true;
        }
        $model->add(array(
            'after_sale_bn' => $after_sale_bn,
            'created_at' => time(),
            'status' => 'ready',
            'status_msg' => '初始化',
        ));
        echo '新增：' . $after_sale_bn, PHP_EOL;
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

    public function push($wmqy_aftersale_info, &$err_msg = '')
    {
        $after_sale_bn = $wmqy_aftersale_info['after_sale_bn'];
        //获取售后单
        $after_info = $this->getReturns($after_sale_bn);
        $server_order_lib = kernel::single("b2c_service_order");
        $order_info = $server_order_lib->getOrderInfo($after_info['order_id']);
        // 获取售后记录
        $returnLogs = $this->getReturnLog(array('after_sale_bn' => $after_sale_bn, 'status' => 2));
        // 虚入
        $auditInfo['is_simulate_storage'] = $after_info['service_is_simulate_storage'];
        // 审核时间
        $auditInfo['audit_time'] = $returnLogs[0] ? $returnLogs[0]['create_time'] : '';
        // 审核人
        $auditInfo['audit_user'] = $returnLogs[0] ? $returnLogs[0]['operator_name'] : '';
        // 获取售后统计信息
        $statisticsData = $this->getStatistics(array('source_bn' => $after_sale_bn, 'source_type' => 1));
        // 责任归属
        $auditInfo['responsible'] = $statisticsData['responsible_msg'] ? $statisticsData['responsible_msg'] : '';
        // 审核备注
        $auditInfo['audit_desc'] = $after_info['service_desc'];
        // 获取我买订单数据
        /* @var wmqy_order $lib_order */
        $lib_order = kernel::single('wmqy_order');
        $wmOrderInfo = $lib_order->getOrderInfo($after_info['order_id']);

//        print_r($wmOrderInfo);die;

        if (!empty($after_info['ship_province'])) {
            $region_info = $lib_order->getRegions($after_info);
        } else {
            $region_info = $lib_order->getRegions($order_info);
        }
        // 获取退款金额
        $refundAmount = $this->_getRefundAmount($after_info, $order_info, $wmOrderInfo);
        $company_info = $lib_order->getCompanyInfo($order_info['company_id']);

        $returnTypes = array(
            1 => 1, // 退货
            2 => 2, // 换货
            3 => 2, // 维修
            4 => 3, // 拒收
        );
        $after_info['ship_addr'] = trim($after_info['ship_addr']);
        $pars = array(
            'refundno' => $after_sale_bn, // 售后单号(限制同销售单号)
            'orderno' => $after_info['order_id'], // 订单号
            'refundtype' => $returnTypes[$after_info['after_type']] ? $returnTypes[$after_info['after_type']] : 1, // 1退货  2换货 3拒收
            'createtime' => $after_info['create_time'] ? date('Y-m-d H:i:s', $after_info['create_time']) : date('Y-m-d H:i:s'), // 售后单创建时间
            'audittime' => $auditInfo['audit_time'] ? date('Y-m-d H:i:s', $auditInfo['audit_time']) : date('Y-m-d H:i:s'), // 售后单审核时间
            'auditor' => $auditInfo['audit_user'] ? $auditInfo['audit_user'] : 'unknown', // 审核人
            'issimulatestorage' => $auditInfo['is_simulate_storage'] ? $auditInfo['is_simulate_storage'] : 0, // 是否虚入 1是 0否
            'refundreason' => $auditInfo['audit_reason'] ? $auditInfo['audit_reason'] : '无', // 售后原因
            'notes' => $auditInfo['audit_desc'] ? $after_info['audit_desc'] : '无', // 售后描述
            'reasonowner' => $auditInfo['responsible'] ? $auditInfo['responsible'] : '无', // 责任归属
            'fullrefundflag' => $refundAmount['is_full'] ? 1 : 0, // 是否整单退货 0部分  1整单
            'userid' => '', // 用户ID
            'enterpriseid' => $order_info['company_id'], // 企业编码
            'enterprisename' => $company_info['display_name'],
            'fullname' => mb_substr($after_info['ship_name'] ? $after_info['ship_name'] : $order_info['ship_name'], 0, 20, 'UTF8'), // 收货人姓名
            'telephone' => $after_info['ship_mobile'] ? $after_info['ship_mobile'] : $order_info['ship_mobile'], // 收货人电话
            'mobile' => $after_info['ship_mobile'] ? $after_info['ship_mobile'] : $order_info['ship_mobile'], // 收货人电话
            'province' => $region_info['wm_province'], // 配送省份
            'city' => $region_info['wm_city'], // 配送市
            'county' => $region_info['wm_county'], // 配送区
            'fulladdress' => !empty($after_info['ship_addr']) ? $after_info['ship_addr'] : $wmOrderInfo['fulladdress'], // 详细地址
            'provinceid' => $region_info['wm_province_id'], // 省级编码
            'cityid' => $region_info['wm_city_id'], // 市级编码
            'countyid' => $region_info['wm_county_id'], // 区级编码
            'isfresh' => 0, // 是否生鲜订单 1是 0否
            'dirsend' => 1, // 自营订单标识 0自营  其它 否
            'supplierid' => 0, // 供应商编码
            'suppliername' => '内购', // 供应商名称
            'refundamount' => intval(bcmul($refundAmount['amount'], 100, 0)), // 售后单实际支付/退款金额  单位 分
            'refundfreightprice' => intval(bcmul($refundAmount['freight'], 100, 0)), // 售后单运费金额
            'refunditemamount' => intval(bcmul($refundAmount['goods_amount'], 100, 0)), //售后单商品总金额
        );
        /* @var wmqy_mdl_companymapping $company_mapping_model */
        $company_mapping_model = app::get('wmqy')->model('companymapping');
        $company_mapping_info = $company_mapping_model->getByWhere('ng_company_id=' . $order_info['company_id']);
        if (!empty($company_mapping_info)) {
            $pars['enterpriseid'] = $company_mapping_info['wmqy_company_id'];
        }

        $wmOrderItems = array();
        foreach ($wmOrderInfo['itemlist'] as $item) {
            $bn = $item['outskuno'];
//            $noInfo = explode('-', $bn);
//            // 礼包商品
//            if ($noInfo[1] != $item['suborderno']) {
//                $bn = $item['suborderno'];
//            }

            $wmOrderItems[$bn] = $item;
        }

        $applyItems = array();

        /* @var wmqy_mdl_goods $goods_model */
        $goods_model = app::get('wmqy')->model('goods');
        $wmqy_sku_list = $goods_model->getByBns(array($wmOrderInfo['itemlist'][0]['outskuno']));
        $mall_id = $wmqy_sku_list[$wmOrderInfo['itemlist'][0]['outskuno']]['mall_id'];
        foreach ($refundAmount['items'] as $item) {
            if ($item['package_bn']) {
                $bn = end(explode('-', $item['package_bn'])) . 'G' . end(explode('-', $item['product_bn']));
            } else {
                $bn = $item['product_bn'];
            }
            if (!isset($wmOrderItems[$bn])) {
                continue;
            }

            $amount = intval(bcmul($item['amount'], 100, 0));

            $discount = intval(bcmul($item['discount'], 100, 0));

            $avgOrderDiscount = intval(bcmul($item['avg_order_discount'], 100, 0));

            $price = $wmOrderItems[$bn]['price'];

            $count = $item['count'];

            $goodsAmount = $price * $count - $discount;

            $diff = $amount - $goodsAmount;

            if ($diff < 0) {
                $diff = abs($diff);
                $discount += $diff;
            } elseif ($diff > 0) {
                $priceBuff = ceil($diff / $count);

                $price += $priceBuff;

                if ($priceBuff * $count > $diff) {
                    $discount += ($priceBuff * $count - $diff);
                }
            }
            // 商品
            $applyItems[] = array(
                'suborderno' => $wmOrderItems[$bn]['suborderno'], // 子订单编码
                'goodsid' => $wmOrderItems[$bn]['goodsid'], // 商品编码
                'outskuno' => $bn, // 三方平台商品编码
                'goodsname' => $wmOrderItems[$bn]['goodsname'],
                'num' => $item['count'], // 数量
                'price' => $price, // 单价
                'goodsdiscount' => $discount, // 单品折扣  单位 分
                'goodstotalprice' => $amount, // 商品总金额 单位分  单价*数量-单品折扣
                'avgorderdiscount' => $avgOrderDiscount, // 商品平分订单优惠 单位 分
                'isfresh' => $wmOrderItems[$bn]['isfresh'], // 是否生鲜品  1是 0否
                'isselfoperates' => $wmOrderItems[$bn]['isselfoperates'], // 自营商品标识  0自营  其它 否
                'supplierid' => $wmOrderItems[$bn]['supplierid'], // 供应商编码   非自营 不为空
                'suppliername' => '', // 供应商名称
                'weight' => $wmOrderItems[$bn]['weight'], // 重量
                'brandname' => $wmOrderItems[$bn]['brandname'], // 品牌
                'brandid' => $wmOrderItems[$bn]['brandid'], // 品牌ID
                'origin' => $wmOrderItems[$bn]['brandid'], // 产地
                'unit' => $wmOrderItems[$bn]['unit'], // 单位
                'specification' => $wmOrderItems[$bn]['specification'], // 规格
                'barcode' => $wmOrderItems[$bn]['barcode'], // 条形码
            );
        }

        // 订单商品集合
        $pars['itemlist'] = $applyItems;

        $payTime = '';
        $payDetailList = $lib_order->createPayPars($order_info, $payTime);
        if (empty($payDetailList)) {
            $data_wmqy_after_sale['pre_status'] = $wmqy_aftersale_info['status'];
            $data_wmqy_after_sale['fail_count'] = $wmqy_aftersale_info['fail_count'] + 1;
            if ($data_wmqy_after_sale['fail_count'] > 3) {
                $data_wmqy_after_sale['status'] = 'fail';
            }
            /* @var wmqy_mdl_aftersale $after_sale_model */
            $after_sale_model = app::get('wmqy')->model('aftersale');
            $after_sale_model->save($data_wmqy_after_sale, "after_sale_bn={$after_sale_bn}");
            $err_msg = '获取支付信息失败';
            return false;
        }
        // 积分
        if (isset($payDetailList['POINT'])) {
            $payDetailList['POINT']['amount'] = intval(bcmul($refundAmount['point'], 100, 0));
        }

        // 现金
        if (isset($payDetailList['CASH'])) {
            $payDetailList['CASH']['amount'] = intval(bcmul($refundAmount['money'], 100, 0));
        }
        // 支付方式
        $applyPayInfo = array(
            'refundno' => $after_sale_bn, // 售后单号
            'payment' => bcmul($refundAmount['amount'], 100, 0), // 实际支付/退款 金额 单位 分
            'paytime' => $payTime ? $payTime : date('Y-m-d H:i:s', $order_info['create_time']), // 付款时间
            'payno' => $after_info['order_id'], // 支付流水号否
            'paydetaillist' => array_values($payDetailList),
        );

        // 支付类型
        $pars['payinfo'] = $applyPayInfo;
        $res = $this->_curl->Post(OPENAPI_DOMAIN . '/ChannelInterop/V1/WMQY/Web/PushRefund?mall_id=' . $mall_id, $pars);
        $data_wmqy_after_sale = array(
            'order_id' => $after_info['order_id'],
            'refundamount' => $pars['refundamount'],
            'refundfreightprice' => $pars['refundfreightprice'],
            'refunditemamount' => $pars['refunditemamount'],
            'pars' => json_encode($pars),
            'result' => json_encode($res),
            'updated_at' => time(),
        );
        $res = json_decode($res, true);

        /* @var wmqy_mdl_log $log_model */
        $log_model = app::get('wmqy')->model('log');
        $log_model->add(array(
            'type' => 'aftersale',
            'data_bn' => $after_sale_bn,
            'pars' => addslashes(json_encode($res['Data']['pars'])),
            'resp' => $res['Data']['resp'],
            'created_at' => time(),
        ));
        $resp = json_decode($res['Data']['resp'], true);
        $data_wmqy_after_sale['pre_status'] = $wmqy_aftersale_info['status'];
        $return = true;
        if ($resp['iserro'] === 0 && $resp['errcode'] === '0000') {
            $data_wmqy_after_sale['status'] = 'succ';
        } else {
            $data_wmqy_after_sale['fail_count'] = $wmqy_aftersale_info['fail_count'] + 1;
            if ($data_wmqy_after_sale['fail_count'] > 3) {
                $data_wmqy_after_sale['status'] = 'fail';
            }
            $err_msg = '接口错误';
            $return = false;
        }
        /* @var wmqy_mdl_aftersale $after_sale_model */
        $after_sale_model = app::get('wmqy')->model('aftersale');
        $after_sale_model->save($data_wmqy_after_sale, "after_sale_bn={$after_sale_bn}");
        return $return;
    }

    public function getReturnLog($where)
    {
        if (empty($where)) return array();
        $ret = \Neigou\ApiClient::doServiceCall('aftersale', 'AfterSale/GetLog', 'v1', null, $where, array('debug' => false));
        if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'];
        } else {
            return array();
        }
    }


    /**
     * 获取订单退款金额
     *
     * @param   $orderRefund            array   售后单数据
     * @param   $orderInfo              array   订单信息
     * @param   $wmOrderInfo            array   我买订单信息
     * @return  mixed
     */
    private function _getRefundAmount($orderRefund, $orderInfo, $wmOrderInfo)
    {
        if (empty($orderRefund) or empty($orderInfo)) {
            return false;
        }

        // 退款金额
        $totalAmount = 0;

        // 是否为整单退
        $isFull = 1;

        // 总运费
        $totalFreight = 0;

        // 退款商品金额
        $totalGoodsAmount = 0;

        // 退现金
        $totalMoney = 0;

        // 退积分
        $totalPoint = 0;

        // 优惠
        $totalDiscount = 0;

        // 商品订单优惠金额
        $totalGoodsOrderDiscount = 0;

        // 商品信息
        $items = array();

        // 售后商品
        $returnItems_tmp = $orderRefund['product_data'];
        $returnItems = array();
        foreach ($returnItems_tmp as $returnItem) {
            $returnItems[$returnItem['product_bn']] = $returnItem;
        }

        $orderItems = $orderInfo['items'];

//        print_r($orderItems);die;

        foreach ($orderItems as $item) {
            if (!isset($returnItems[$item['bn']]) or intval($returnItems[$item['bn']]['nums']) !== intval($item['nums'])) {
                $isFull = 0;
            }
            $key = $this->_createUniqueProductBn($item['bn'], $item['item_type'], $item['p_bn']);
            $orderItems[$key] = $item;
        }

        $orderRefundItemList = array();
        foreach ($returnItems as $item) {
            $key = $this->_createUniqueProductBn($item['product_bn'], $item['item_type'], $item['p_bn']);
            $orderRefundItemList[$key] = $item;
        }

        if (!empty($orderRefund['gift_items'])) {
            // 检查赠品
            $orderGiftItems = $orderModel->getOrderGiftItems($orderRefund['order_id']);
            foreach ($orderGiftItems as $item) {
                $key = $item['p_bn'] . $item['item_bn'];
                if (!isset($orderRefund['gift_items'][$key]) or intval($orderRefund['gift_items'][$key]['count']) !== intval($item['count'])) {
                    $isFull = 0;
                }
                $key = $this->_createUniqueProductBn($item['item_bn'], $item['item_type'], $item['p_bn']);
                $orderItems[$key] = $item;
            }
            foreach ($orderRefund['gift_items'] as $item) {
                $key = $this->_createUniqueProductBn($item['item_bn'], $item['item_type'], $item['p_bn']);
                $orderRefundItemList[$key] = $item;
            }
        }
        $returnItems = $orderRefundItemList;

        // 非整单售后获取已售后的商品
        if (!$isFull) {
            $finishedItems = array();
            $param = array(
                'page' => 1,
                'limit' => 100,
                '_str' => "order_id = '{$orderInfo['external_order_id']}' AND after_type = 1 AND status = 6",
            );

            $ret = \Neigou\ApiClient::doServiceCall('aftersale', 'AfterSale/GetSearchList', 'v1', null, $param, array('debug' => false));
            if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
                if (!empty($ret['service_data']['data']['data'])) {
                    foreach ($ret['service_data']['data']['data'] as $returnInfo) {
                        foreach ($returnInfo['product_data'] as $productInfo) {
                            $key = $this->_createUniqueProductBn($productInfo['product_bn'], $productInfo['item_type'], $productInfo['p_bn']);
                            if (!isset($finishedItems[$key])) {
                                $finishedItems[$key] = 0;
                            }
                            $finishedItems[$key] += $productInfo['nums'];
                        }
                    }
                }
            }
        }

        // 获取下单商品价格
        /* @var wmqy_mdl_orderitem $order_item_model */
        $order_item_model = app::get('wmqy')->model('orderitem');
        $wmOrderItems = $order_item_model->getByOrderID($orderInfo['order_id']);

        if (empty($orderItems)) {
            return false;
        }
        $refundItems = array();
//        print_r($returnItems);
//        print_r($orderItems);
//        print_r($wmOrderItems);die;
        foreach ($wmOrderItems as $item) {
            $key = $this->_createUniqueProductBn($item['product_bn'], $item['item_type'], $item['p_bn']);
            $finishedCount = isset($finishedItems[$key]) ? intval($finishedItems[$key]) : 0;
            if (!empty($item['package_bn'])) {
                $key = $this->_createUniqueProductBn($item['package_bn'], $item['item_type'], $item['p_bn']);
                if (isset($returnItems[$key])) {
                    $item['refund_count'] = intval($item['count'] * $returnItems[$key]['nums'] / $orderItems[$key]['nums']);
                    $item['finished_count'] = intval($item['count'] * $finishedCount / $orderItems[$key]['nums']);
                    $refundItems[] = $item;
                }
            } else {
                if (isset($returnItems[$key])) {
                    $item['refund_count'] = $returnItems[$key]['nums'];
                    $item['finished_count'] = $finishedCount;
                    $refundItems[] = $item;
                }
            }
        }

        $wmOrderItems = array();
        foreach ($wmOrderInfo['itemlist'] as $item) {
            $bn = $item['outskuno'];
            $noInfo = explode('-', $bn);
            // 礼包商品
            if ($noInfo[1] != $item['suborderno']) {
                $bn = $item['suborderno'];
            }

            $wmOrderItems[$bn] = $item;
        }

//        print_r($refundItems);die;
        foreach ($refundItems as $item) {
            // 商品总购买数字
            $count = $item['count'];

            // 商品退款数量
            $refundCount = $item['refund_count'];

            // 每个分摊的现金（含运费）
            $perMoney = bcdiv($item['payed_amount'], $count, 2);

            // 每个分摊的积分（含运费）
            $perPoint = bcdiv($item['point_amount_dis'], $count, 2);

            // 每个分摊的运费
            $perFreight = bcdiv($item['cost_freight'], $count, 2);

            // 每个分摊的优惠
            $perDiscount = bcdiv($item['pmt_amount'], $count, 2);

            // 每个商品分摊总金额（不含优惠）
            $perGoodsAmount = bcdiv(bcsub(bcmul($item['price'], $count, 2), $item['pmt_amount'], 2), $count, 2);

            // 每个商品分摊现金运费
            $perMoneyFreight = bcdiv($item['payed_freight'], $count, 2);

            // 每个商品分摊积分运费
            $perPointFreight = bcdiv($item['point_freight'], $count, 2);

            // 商品分摊的订单优惠金额
            $avgOrderDiscount = $wmOrderItems[$item['product_bn']]['avgorderdiscount'] ? $wmOrderItems[$item['product_bn']]['avgorderdiscount'] : 0;

            $avgOrderDiscount = bcdiv($avgOrderDiscount, 100, 2);

            // 每个分摊的订单优惠金额
            $perAvgOrderDiscount = bcdiv($avgOrderDiscount, $count, 2);

            // 最后退款
            if ($isFull or $count - $item['finished_count'] == $refundCount) {
                // 已完成售后数量(仅退货)
                $finishedCount = intval($item['finished_count']);

                // 整单退
                if ($isFull) {
                    // 现金
                    $money = $item['payed_amount'];

                    // 积分
                    $point = $item['point_amount_dis'];

                    // 运费
                    $freight = $item['cost_freight'];

                    // 优惠
                    $discount = $item['pmt_amount'];

                    // 商品总金额（不含优惠）
                    $goodsAmount = bcsub(bcmul($item['price'], $count, 2), $item['pmt_amount'], 2);

                    // 商品订单优惠金额
                    $goodsAvgOrderDiscount = $avgOrderDiscount;
                } else {
                    // 现金（不含运费）
                    $money = bcsub(bcsub($item['payed_amount'], $item['payed_freight'], 2), bcmul(bcsub($perMoney, $perMoneyFreight, 2), $finishedCount, 2), 2);

                    // 积分（不含运费）
                    $point = bcsub(bcsub($item['point_amount_dis'], $item['point_freight'], 2), bcmul(bcsub($perPoint, $perPointFreight, 2), $finishedCount, 2), 2);

                    // 运费
                    $freight = 0;

                    // 优惠
                    $discount = bcsub($item['pmt_amount'], bcmul($perDiscount, $finishedCount, 2), 2);

                    // 商品总金额（不含优惠）
                    $goodsAmount = bcsub(bcsub(bcmul($item['price'], $count, 2), $item['pmt_amount'], 2), bcmul($perGoodsAmount, $finishedCount, 2), 2);

                    // 商品订单优惠金额
                    $goodsAvgOrderDiscount = bcsub($avgOrderDiscount, bcmul($perAvgOrderDiscount, $finishedCount, 2), 2);
                }
            } else {
                // 现金（不含运费）
                $money = bcmul(bcsub($perMoney, $perMoneyFreight, 2), $refundCount, 2);

                // 积分（不含运费）
                $point = bcmul(bcsub($perPoint, $perPointFreight, 2), $refundCount, 2);

                // 运费
                $freight = 0;

                // 优惠
                $discount = bcmul($perDiscount, $refundCount, 2);

                // 商品总金额（不含优惠）
                $goodsAmount = bcmul($perGoodsAmount, $refundCount, 2);

                // 商品订单优惠金额
                $goodsAvgOrderDiscount = bcmul($perAvgOrderDiscount, $refundCount, 2);
            }

            $totalAmount = bcadd(bcadd($totalAmount, $money, 2), $point, 2);

            $totalFreight = bcadd($totalFreight, $freight, 2);

            $totalGoodsAmount = bcadd($totalGoodsAmount, $goodsAmount, 2);

            $totalGoodsAmount = bcsub($totalGoodsAmount, $goodsAvgOrderDiscount, 2);

            $totalDiscount = bcadd($totalDiscount, $discount, 2);

            $totalMoney = bcadd($totalMoney, $money, 2);

            $totalPoint = bcadd($totalPoint, $point, 2);

            $totalGoodsOrderDiscount = bcadd($totalGoodsOrderDiscount, $goodsAvgOrderDiscount, 2);

            $items[] = array(
                'product_bn' => $item['product_bn'],//商品编码
                'count' => $refundCount, // 数量
                'amount' => $goodsAmount,// 商品总金额（不含优惠）
                'freight' => $freight, // 邮费
                'discount' => $discount,
                'package_bn' => $item['package_bn'],
                'avg_order_discount' => $goodsAvgOrderDiscount,
            );
        }

        //  换货则不退运费 退款类型：0-未填写，1-退货，2-换货，3-维修, 4-拒收
        if ($orderRefund['refund_type'] == 2 && $totalFreight > 0) {
            if ($totalPoint >= $totalFreight) {
                $totalPoint = bcsub($totalPoint, $totalFreight, 2);
            } else {
                $totalPoint = 0;
                $diff = bcsub($totalFreight, $totalPoint, 2);
                $totalMoney = $totalMoney > $diff ? bcsub($totalMoney, $diff, 2) : 0;
            }
            $totalFreight = 0;
            $totalAmount = bcadd($totalPoint, $totalMoney, 2);
        }

        // 商品金额大于总金额
        if ($totalGoodsAmount > $totalAmount) {
            $diff = bcsub($totalGoodsAmount, $totalAmount, 2);
            foreach ($items as $key => $item) {
                if ($item['amount'] > $diff) {
                    $items[$key]['amount'] = bcsub($item['amount'], $diff, 2);
                    break;
                } else {
                    $items[$key]['amount'] = 0;
                    $diff = bcsub($diff, $item['amount'], 2);
                }
            }

            $totalGoodsAmount = $totalAmount;
        }

        // 运费为0为商品价格与总价存在差异
        if ($totalFreight == 0 && $totalAmount > $totalGoodsAmount) {
            $diff = bcsub($totalAmount, $totalGoodsAmount, 2);

            $items[count($items) - 1]['amount'] = bcadd($items[count($items) - 1]['amount'], $diff, 2);

            $totalGoodsAmount = $totalAmount;
        }

        $totalFreight = bcsub($totalAmount, $totalGoodsAmount, 2);

        $return = array(
            'is_full' => $isFull, // 整单退
            'amount' => $totalAmount, // 退款金额
            'goods_amount' => $totalGoodsAmount, // 退款商品金额
            'freight' => $totalFreight, // 退款运费金额
            'money' => $totalMoney, // 退现金
            'point' => $totalPoint, // 退积分
            'discount' => $totalDiscount, // 优惠金额
            'items' => array_values($items),
        );
        return $return;
    }

    // 唯一的售后商品编码
    public function _createUniqueProductBn($productBn, $itemType = 'product', $pBn = '', $split = '_')
    {
        return implode($split, array_filter(array($itemType, $productBn, $pBn)));
    }

    /*
     * 获取售后统计信息
     */
    public function getStatistics($param = array())
    {
        if (empty($param['source_bn']) || empty($param['source_type'])) {
            return array();
        }
        $ret = \Neigou\ApiClient::doServiceCall('aftersale', 'AfterSale/StatisticsGet', 'v1', null, $param, array('debug' => false));
        if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && $ret['service_data']['data']) {
            return $ret['service_data']['data'];
        } else {
            return 0;
        }
    }

    //获取售后申请数据
    public function getReturns($after_sale_bn)
    {
        $param['filter_data'] = array(
            'after_sale_bn' => array(
                'type' => 'eq',
                'value' => $after_sale_bn,
            ),
        );
        $ret = \Neigou\ApiClient::doServiceCall('aftersale', 'CustomerCare/AfterSale/Find', 'v2', null, $param, array('debug' => false));
        if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'];
        }
        return null;
    }
}

