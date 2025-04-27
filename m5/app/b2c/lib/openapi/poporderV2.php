<?php
class b2c_openapi_poporderV2
{
    public function __construct()
    {
        $this->data   = json_decode(base64_decode(trim($_POST['data'])),true);
        //$this->data = json_decode(trim($_POST['data']) ,true) ;

        $_check = kernel::single('b2c_safe_apitoken');
        if($_check->check_token($_POST,OPENAPI_TOKEN_SIGN) === false) {
            $this->ResponseJson(20000,'签名错误');
        }
    }

    public function service_create_test_data()
    {
       $order_data_str =  '{
            "post_data":{
                "member_id":"971254",
                "company_id":"18643",
                "mem_cpaddr_id":"",
                "voucher_list":{
                    "ED48566CCCE9E32A":{
                        "match_product_bn":[
                            "SHOP-5B4DBD4126F87-0"
                        ]
                    }
                },
                "freeshipping_list":[],
                "dutyfree_list":[],
                "use_point":{
                    "201911051448313843":{
                        "point":"0.5"
                    }
                },
                "point_channel":"SCENENEIGOU",
                "delivery":{
                    "ship_name":"lhw ",
                    "ship_mobile":"18510335783",
                    "ship_zip":"1231",
                    "ship_addr":"远东大厦 23123123",
                    "shipping_id":5,
                    "ship_area":"mainland:上海/上海市/黄浦区/城区:3928"
                },
                "payment":{
                    "anonymous":"no",
                    "currency":"CNY"
                },
                "memo":[],
                "terminal":"pc",
                "global":"false",
                "extend_info_code":"standard",
                "order_category":"local",
                "idcardname":"",
                "idcardno":"",
                "extend_data":"{"isprintprice":"0","isNewZiti":0,"order_source":"","needVerifyLevel":0,"is_jdbt_mx":0,"is_mabcchinahtmlmx":0,"invoice_info":[],"invoice_service_info":"","o2o_express":"","delivery_time":"","gps_info":{"lat":"31.229113899","lng":"121.51741645"}}",
                "receive_mode":1,
                "temp_order_id":"",
                "third_point_pwd":"",
                "region_info":{
                    "province":"上海",
                    "city":"上海市",
                    "county":"黄浦区",
                    "town":"城区",
                    "gps_info":{
                        "lat":"31.229113899",
                        "lng":"121.51741645"
                    }
                },
                "delivery_time":1616829801,
                "order_use_rules":[  311  ]
            },
            "goods_list":[
                {
                    "goods_bn":"SHOP-5B4DBD4126F87",
                    "goods_id":"874466",
                    "bn":"SHOP-5B4DBD4126F87-0",
                    "name":"王松涛测试产品40元",
                    "price":{
                        "price":"45.00",
                        "point_price":"40.000",
                        "mktprice":"45.000",
                        "cost":"40.000"
                    },
                    "weight":null,
                    "quantity":"1",
                    "shop_id":"29",
                    "product_id":"3818783",
                    "use_rules":null,
                    "is_presale":null,
                    "taxfees":0,
                    "presents":null
                }
            ],
            "platform":"neigou",
            "terminal":"pc",
            "zero_dopay":true
        }' ;


       return json_decode($order_data_str ,true) ;
    }
    /**
     * 新版购物车 下单
     */
    public function service_create()
    {neigou_store/trunk/m5/app/b2c/lib/openapi/poporderV2.php:107
        $post_data =  $this->data['post_data'];
        $goods_list = $this->data['goods_list'];
        $terminal =   $this->data['terminal'];
        $zero_dopay = $this->data['zero_dopay'];
        $platform   = $this->data['platform'] ;

        $delivery  = $post_data['delivery'] ;
        if(isset($post_data['region_info']['gps_info'])) {
            $delivery['gps_info'] = $post_data['region_info']['gps_info'];
        }
        if(empty($goods_list)) {
            $this->responseJson(400, '商品列表不能为空');
        }
        $new_goods_list = array() ;
        foreach ($goods_list as &$info) {
            $bn =  isset($info['product_bn']) ? $info['product_bn'] : $info['bn'];
            $info['product_bn'] = $bn ;
            $info['is_gift'] = '0' ;
            //  //商品类型（'product','pkg','gift','adjunct'）
            $info['item_type'] = 'product' ;
            $info['root_product_bn'] = array() ;
            $new_goods_list[$bn] = $info ;
            if(isset($info['presents']) && !empty($info['presents']))
                foreach ($info['presents'] as $pbn=>$item) {
                    $item["is_gift"] = '1' ;
                    $item['root_product_bn'][] = $bn ;
                    $pbn = $pbn . "_gift" ;
                    $item['name'] = $item['product_name'] . "(赠品)" ;
                    $item['item_type']  = 'gift' ;
                    unset($item['product_name']) ;
                    $new_goods_list[$pbn] = $item ;
                }
        }
        /** @var b2c_service_order_newcartv3 $service_order */
        $service_order = kernel::single('b2c_service_order_newcartv3');
        // 验证数据是否符合 需求
        $checkRes =   $service_order->checkData($post_data['member_id'] ,$post_data['company_id'] ,$goods_list) ;
        if($checkRes['code'] != '200' ) {
            $error_data  = !empty($checkRes['error_data']) ? $checkRes['error_data'] : array() ;
            $this->responseJson($checkRes['code'], $checkRes['msg'], $error_data);
        }
        // 根据 goods_bn 获取 商品的运费模版
        $goods_freight_list =  $service_order->getProductFreightBn($new_goods_list) ;
        if(empty($goods_freight_list)) {
            $this->responseJson(401, '运费模版获取失败');
        }
        foreach ($new_goods_list as $key=>$info) {
            $goods_bn = $info['goods_bn'] ;
            if(!isset($goods_freight_list[$goods_bn])) {
                $this->responseJson(402, 'BN='.$goods_bn.'运费模版获取失败');
            }
            $freight_info = $goods_freight_list[$goods_bn] ;
            $new_goods_list[$key]['freight_bn'] = $freight_info['freight_bn'] ;
            $new_goods_list[$key]['freight_source'] = $freight_info['source'] ;
        }
        $asset_list = array() ;

        if(!empty($post_data['use_point'])) {
            foreach ($post_data['use_point'] as $bn=>$pointInfo) {

                if(bccomp($pointInfo['point'] , '0' ,2) <=  0) {
                    continue ;
                }
                $asset_list[] = array(
                    'type' => 'point' ,
                    'bn'  => $bn ,
                    'amount' =>  $pointInfo['point'] ,
                    'extend_data' => array(
                        'point_channel' => isset($pointInfo['point_channel']) ? $pointInfo['point_channel'] : $post_data['point_channel'] ,
                        'third_point_pwd' => isset($pointInfo['third_point_pwd']) ? $pointInfo['third_point_pwd'] : $post_data['third_point_pwd'] ,
                    ) ,
                    'match_product_bn' => array() ,
                ) ;
            }
        }
        $dictAsset = array('voucher_list' =>'voucher' , 'freeshipping_list' => 'freeshipping' ,'dutyfree_list' => 'dutyfree') ;
        foreach ($dictAsset as $key=>$type) {
            if( empty($post_data[$key]) ) {
                continue ;
            }
            foreach ($post_data[$key] as $bn=>$voucherInfo) {
                $asset_list[] = array(
                    'type' => $type ,
                    'bn'  => $bn ,
                    'amount' => '0' ,
                    'match_product_bn' => $voucherInfo['match_product_bn']
                ) ;
            }
        }

        //指定现金折扣
        if(!empty($post_data['payable_cash_discount'])) {
            if(bccomp($post_data['payable_cash_discount']['amount'] , '0' ,2) >  0) {
                $asset_list[] = array(
                    'type' => 'cash' ,
                    'bn' => 0,
                    'rule_list'  => $post_data['payable_cash_discount']['rule_list'],
                    'amount' =>  $post_data['payable_cash_discount']['amount'],
                    'match_product_bn' => $post_data['payable_cash_discount']['match_product_bn'],
                ) ;
            }
        }

        //在此处开始检查备用库存，并将不满足库存条件的主sku替换成备用sku
        /** @var b2c_service_sparestock $sparestock_service_order */
        $sparestock_service_order = kernel::single('b2c_service_sparestock');
        $spare_ret = $sparestock_service_order->checkSpareStock($new_goods_list, $delivery['ship_area']);
        if ($spare_ret['code'] != 0) {
            $this->responseJson(403, '验证库存失败：' . $spare_ret['msg']);
        }
        list($new_goods_list, $with_replace_arr) = $spare_ret['data'];

        $order_auto_confirm = 1;
        $checkIsReview = kernel::single('b2c_order_review')->getCompanyOrderReview($post_data['company_id']);
        if ($checkIsReview){
            $order_auto_confirm = 0;
        }
        // 定制订单不自动确认
        if ($post_data['extend_info_code'] == 'customization') {
            $order_auto_confirm = 0;
        }
        $extend_data = $post_data['extend_data'] ?  json_decode($post_data['extend_data'],true) : array() ;
        $extend_data['auto_confirm'] = $order_auto_confirm;
        //将替换结果存入扩展数组，以便后续使用订单时，做相关逻辑使用
        if($with_replace_arr){
            $extend_data['spare_stock'] = $with_replace_arr;
        }
       $extend_data['idcardname'] =  $post_data['idcardname'] ;
       $extend_data['idcardno'] = $post_data['idcardno'] ;

       $order_post_data =  array(
            "member_id"=>  $post_data['member_id'],
            "company_id"=> $post_data['company_id'],
            'temp_order_id' => $post_data['temp_order_id'] ? $post_data['temp_order_id'] : '' ,
            "terminal" => $terminal  , // 来源
            "from"  => "store" ,
            'extend_data' => $extend_data ,
            "asset_list" =>  $asset_list ,
            "product_list" => $new_goods_list ,
            'point_channel' => $post_data['point_channel']  , // 积分渠道
            'order_use_rules' => $post_data['order_use_rules'] , // 运营活动规则
            'delivery_time' => $post_data['delivery_time'] , // 配送时间
             // 地址信息
            "delivery" => $delivery ,
            "payment"           => $post_data['payment'],
            "memo"              => $post_data['memo'],
            "platform"          => $platform,
            "global"            => $post_data['global'],
            "system_code"       => $post_data['system_code'],
            "extend_info_code"  => $post_data['extend_info_code'],
            'receive_mode'      => $post_data['receive_mode'] ,
            "order_category"    => $post_data['order_category'],
            "third_point_pwd"   => $post_data['third_point_pwd'] ?: '',
        );neigou_store/trunk/m5/app/b2c/lib/openapi/poporderV2.php:261
        //查询支付方式
        $channel = app::get('b2c')->model('club_company')->getCompanyRealChannel($post_data['company_id']);
        //拿取公司对应的现金支付方式
        $payment_assigned = kernel::single('b2c_payment_index')->getCompanyPayment($post_data['company_id'], $channel, $post_data['terminal']);
        $order_post_data['payment']['payment_list'] = $payment_assigned;
        $ret = \Neigou\ApiClient::doServiceCall('order',
            'OrderGather/create',  'v1',  null,  $order_post_data);

        \Neigou\Logger::Debug( 'order_OrderGather_create',  array(
                'action' => 'service_orderGather_create',
                'post_data'   => $order_post_data,
                'result' => $ret
            )
        );
        if ($ret['service_status'] != 'OK' OR 'SUCCESS' != $ret['service_data']['error_code']) {
            $error_code = $ret['service_data']['error_detail_code'];
            $msg        =  array_pop($ret['service_data']['error_msg']);
            $error_data  = isset($ret['service_data']['data']) ? $ret['service_data']['data'] : array() ;
            $this->responseJson($error_code, $msg ,$error_data);
        }
        $error_code = 0;
        $msg = 'ok';
        $result = $ret['service_data']['data'] ;

        // 更新订单数量  订单数量从service 查询
        $service_order->updateOrderNum($result['member_id']) ;

        // 增加订单审核
        kernel::single('b2c_order_review')->addOrderReview($result['company_id'], $result['order_id']);

        if($zero_dopay == false || empty($result)) {
            $this->responseJson($error_code, $msg, $result);
        }
        if(bccomp($result['cur_money'] , '0' ,2) != 0) {
            $this->responseJson($error_code, $msg, $result);
        }
        /** 订单金额为0 **/
        $sdf = array(
            'payment_id' => '',
            'order_id' => $result['order_id'],
            'rel_id' => $result['order_id'],
            'op_id' => $result['member_id'],
            'pay_app_id' => $result['payment'],
            'currency' => $result['currency'],
            'payinfo' => array(
                'cost_payment' => 0.00,
            ),
            'pay_object' => 'pop_order',
            'member_id' => $result['member_id'],
            'op_name' => $result['member_id'],
            'status' => 'ready',
            'cur_money' => $result['cur_money'],
            'money' => $result['cur_money'],
        );
        $pop_payment = kernel::single("b2c_pop_payment");
        $ret = $pop_payment->pop_order_zero_pay($sdf, $msg);
        if(false === $ret){
            $error_code = '1080';
            $msg .= '[自动支付失败]';
            $this->responseJson($error_code, $msg);
        }
        $this->responseJson($error_code, $msg, $result);
    }

    /*autocancelorder.php
     * @todo 返回信息
     */
    protected function responseJson($code,$msg,$data=array()){
        \Neigou\Logger::General("order.newcart.create.v2", array("error_code" => $code, "message" => $msg, "data" => $data, "req_data" => $this->data));
        $data   = array (
            'code'  =>$code,
            'msg'  =>$msg,
            'data'  =>$data,
        );
        echo json_encode($data);
        exit;
    }
}
