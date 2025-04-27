<?php

class unicom_service_order_order
{
    //系统状态
    const ORDER_PAY_CONFIRM_SYS_STATUS_INIT = 0;//初始
    const ORDER_PAY_CONFIRM_SYS_STATUS_AUTO_CANCEL = 1;//自动取消
    
    //订单支付确认状态
    const ORDER_PAY_CONFIRM_STATUS_INIT = 0; //初始
    const ORDER_PAY_CONFIRM_STATUS_PROCESSING = 1;//处理中
    const ORDER_PAY_CONFIRM_STATUS_PAID = 2;//已支付
    const ORDER_PAY_CONFIRM_STATUS_CANCEL = 3;//已取消
    const ORDER_PAY_CONFIRM_STATUS_WAIT_CONFIRM = 4;//待确认
    const ORDER_PAY_CONFIRM_STATUS_RECEIPT = 5;//已收货

    private $request_data;
    private $cart; //以bn为key保存货品初始数据
    private $objMath;
    private $product_list;//检查通过货品数据
 
    private $split;  //拆分
    private $split_orders;
    
    function __construct()
    {
        $this->objMath = kernel::single('ectools_math');
    }
    
    
    /**
     * 获取订单映射信息
     * @param string $third_order_bn 
     */
    public function getPayOrderInfoByUcOrder($third_order_bn)
    {
        $third_order_bn = strpos($third_order_bn,'(FLSC)') === FALSE ? '(FLSC)'.$third_order_bn : $third_order_bn;
        return $this->getPayConfirmInfo($third_order_bn);
    }

    /**
     * 取消订单
     * @param string $order_id
     * @param string $reason
     * @param string $msg
     * @param string $error_code
     * @return boolean
     */
    public function doCancelOrder($order_id,$reason,&$msg,&$error_code)
    {
        $pay_confirm_info = $this->getPayConfirmInfoByOrderId($order_id);
        
        if(empty($pay_confirm_info)){
            $error_code = 20010;
            $msg = '记录不存在';
            return FALSE;
        }
        
        if($pay_confirm_info['status'] != self::ORDER_PAY_CONFIRM_STATUS_WAIT_CONFIRM){
            $error_code = 20012;
            $msg = '当前状态不能取消';
            return FALSE;
        }
        
        $r = $this->cancelOrder($pay_confirm_info['order_id']);
        if(FALSE === $r){
            $error_code = 20011;
            $msg = '取消订单服务订单失败';
            return FALSE;
        }
        $confirm_data = array(
            'id'=>$pay_confirm_info['id'],
            'extent_data'=>array('remark'=>$reason)
        );
        $r1 = $this->setPayConfirmInfo($confirm_data,self::ORDER_PAY_CONFIRM_STATUS_CANCEL);
        $this->updateGoodsByOrderId($order_id);
        
        if($r1 === FALSE){
            $error_code = 20013;
            $msg = '更新记录状态失败';
            return FALSE;
        }
        return TRUE;
    }
    
    
    /**
     * 确认订单
     * 
     * @param string $third_order_bn
     * @param string $msg
     * @param string $error_code
     * @param array  $ret_data  rel
     * @return boolean
     */
    public function doConfirmOrder($third_order_bn,&$msg,&$error_code,&$ret_data)
    {
        $third_order_bn = strpos($third_order_bn,'(FLSC)') === FALSE ? '(FLSC)'.$third_order_bn : $third_order_bn;
        $pay_confirm_info = $this->getPayConfirmInfo($third_order_bn);
        //获取订单信息
        $order_id = isset($pay_confirm_info['order_id']) ? $pay_confirm_info['order_id'] : '';
        $order_data = $this->getOrderInfo($order_id, $code = '0');

        if(!empty($pay_confirm_info) && !empty($order_data)){
            //开始处理
            $confirm_data = array(
                'id'=>$pay_confirm_info['id'],
            );
        
            if($order_data['pay_status'] == 1){ //'支付状态 1：未支付 2：已支付 3：全额退款',
                $member_id = $pay_confirm_info['member_id'];
                $final_amount = $order_data['final_amount'];
                $result = array(
                    'order_id' =>$order_id,
                    'member_id'=>$member_id,
                    'payment'=>'unicom',
                    'currency'=>'CNY',
                    'cur_money'=>$final_amount,
                );

                //获取支付交易号
                $payment_id = kernel::single("ectools_pay")->get_payment_id();
                $order_confirm_flag = $this->orderConfirmLogic($result,$payment_id);
                if(false === $order_confirm_flag){
                    $error_code = 10042;
                    $msg = '订单确认处理失败';
                }else{
                    $status = self::ORDER_PAY_CONFIRM_STATUS_PAID;
                    $r1 = $this->setPayConfirmInfo($confirm_data,$status);
                    if(!$r1){
                        $error_code = 10043;
                        $msg = '更新订单数据异常';
                    }else{
                        $error_code = 10000;
                        $msg = '订单确认处理成功';
                        $ret_data = array(
                            'order_id'=>$order_id,
                        );
                        return true;
                    }
                }
            }else{
                // 已支付
                if ($order_data['pay_status'] == 2)
                {
                    $error_code = 10000;
                    $msg = '订单确认处理成功';
                    return true;
                }
                $error_code = 10041;
                $msg = '订单服务数据状态不能被确认';
            }
        }else{
            $error_code = 10040;
            $msg = '获取订单服务数据异常';
        }
        return false;
    }

    public function updateGoodsByOrderId($order_id)
    {
	return;    
    $order_data = $this->getOrderInfo($order_id, $code = '0');
        if(!empty($order_data)){
            if(count($order_data['items']) > 0){
                foreach($order_data['items'] as $row){
                    $bn = $row['bn'];//货品bn
                    $product_data = $this->getProductDataByBn($bn);
                    if(!empty($product_data)){
                        $goods_id = $product_data['goods_id'];
                        $goods_data = $this->getGoodsDataByGoodsId($goods_id);
                        $mdl_goods = app::get('b2c')->model('goods');
                        $data = array(
                            'goods_id'=>$goods_id,
                            'bn'=>$goods_data['bn'],
                            'last_modify'=>time(),
                        );
                        $mdl_goods->save($data);
                    }
                }
               
            }
            
        }
    }

    public function getPayConfirmInfoByOrderId($order_id)
    {
        return app::get('unicom')->model('preorder')->getInfoByOrderId($order_id);
    }

    public function getPayConfirmInfo($third_order_bn)
    {
        return app::get('unicom')->model('preorder')->getInfo($third_order_bn);
    }
    
    //设置支付确认信息
    public function setPayConfirmInfo($confirm_data,$status)
    {
        //更新
        if(isset($confirm_data['id'])){
            $data = array(
                'status'=>$status,
                'extent_data'=>isset($confirm_data['extent_data']) ? serialize($confirm_data['extent_data']) : '',
            );
            if(isset($confirm_data['order_id'])){
                $data['order_id'] = $confirm_data['order_id'];
            }
            if(isset($confirm_data['sys_status'])){
                $data['sys_status'] = $confirm_data['sys_status'];
            }
            
            $obj_preorder = app::get('unicom')->model('preorder'); 
            return $obj_preorder->updateInfo($confirm_data['id'],$data);
        }else{
            $data = array(
                'order_id'=>$confirm_data['order_id'],
                'third_order_bn'=>$confirm_data['third_order_bn'],
                'company_id'=>$confirm_data['company_id'],
                'member_id'=>$confirm_data['member_id'],
                'status'=>$status,//(0->未支付，1->处理中，2->已支付),
                'extent_data'=>isset($confirm_data['extent_data']) ? serialize($confirm_data['extent_data']) : '',
                'create_at'=>time(),
            );
            $obj_preorder = app::get('unicom')->model('preorder'); 
            return $obj_preorder->addInfo($data);
        }
    }

    //更新货品价格
    public function updateProductByBn($bn,$cost)
    {
        $product_data = $this->getProductDataByBn($bn);
        if(!empty($product_data)){
            $product_id = $product_data['product_id'];
            $mdl_products = app::get('b2c')->model('products');
            $last_modify = time();
            $sql = "update sdb_b2c_products set `last_modify` = {$last_modify},`cost`={$cost} where `product_id`=$product_id";
            $mdl_products->db->exec($sql);
        }
    }
    
    public function setProductPrice($bns)
    {
        //重新生成定价缓存 set_rang_priceing.php
//        $set_price_obj = kernel::single("b2c_products_price_setprice");
//        return $set_price_obj->CreateCache($bns);//array($bn)
        $service_lib =  kernel::single3('b2c_products_price_service');
        return  $service_lib->createPricing($bns) ;
    }
    
    public function getProductDataByBn($bn)
    {
        $mdl_products = app::get('b2c')->model('products');
        $filter = array(
            'bn'=>$bn,
        );

        $product_results = $mdl_products->getList($cols='*',$filter,$start=0,$limit=1);
        return $product_results[0];
    }
    
    public function getGoodsDataByGoodsId($goods_id)
    {
        $mdl_goods = app::get('b2c')->model('goods');
        $filter = array(
            'goods_id'=>$goods_id,
        );
        $goods_results = $mdl_goods->getList($cols='*',$filter,$start=0,$limit=1);
        return $goods_results[0];
    }
    
    //确认支付逻辑
    private function orderConfirmLogic($result,$payment_id)
    {
        // 模拟支付流程
        //$objPay = kernel::single("ectools_pay");
        $sdf = array(
            'payment_id' => $payment_id,
            'order_id' => $result['order_id'],
            'rel_id' => $result['order_id'],
            'op_id' => $result['member_id'],
            'pay_app_id' => $result['payment'],
            'currency' => $result['currency'],
            'payinfo' => array(
                'cost_payment' => 0,
            ),
            'trade_no'=>isset($result['trade_no']) ? $result['trade_no'] : '',
            'pay_object' => 'pop_order',
            'member_id' => $result['member_id'],
            'op_name' => $result['member_id'],
            'status' => 'ready',
            'cur_money' => $result['cur_money'],
            'money' => $result['cur_money'],
        );
        $pop_payment = kernel::single("b2c_pop_payment");
        $ret = $pop_payment->pop_order_zero_pay_gallywix($sdf, $msg);
        if(false === $ret){
            \Neigou\Logger::General('unicom.service.orderConfirmLogic', array('result'=>$result,'msg'=>$msg));   
        }
        return $ret;
    }

    public function getServerAddress($address) {
        $address  = str_replace(array(' ',"(" ,')' ,"\t") ,'' , trim($address)) ;
        $sendData = array(
            'addr' => $address  ,
        ) ;
        $res = \Neigou\ApiClient::doServiceCall('tools', 'Region/GetRegionByAddr', 'v3', null, $sendData);
        if ($res['service_status'] == 'OK' &&   'SUCCESS' == $res['service_data']['error_code']) {
            $data  = $res['service_data']['data'];
            if(!empty($data)) {
                $data['origin_address'] = $address ;
            }
            $memo = '地址合法';
        } else {
            $data = array() ;
            $memo = $res['service_data']['error_msg'] ;
            $memo = is_array($memo) ? implode("," ,$memo) : $memo ;
        }
        $status = $data ? true : false  ;
        return array("msg" => $memo ,'data' => $data ,'status' => $status) ;
    }

    
    //格式化post_data
    public function formatPostData($origin_post_data,&$pre_order_data)
    {
        //进行地址转换
        $mdl_regions    = app::get('ectools')->model('regions');
        $region = $origin_post_data['delivery']['region'];
        $ret_data = kernel::single("unicom_region")->getRegionMapping($region['province'], $region['city'], $region['county'],$region['town']);
        
        \Neigou\Logger::General("unicom.region.getRegionMapping", array('delivery'=> $origin_post_data['delivery'] ,"ret_data"=>$ret_data));
        if(!empty($ret_data)){
            $origin_post_data['delivery']['province_id'] = isset($ret_data['provinceRegionId']) ? $ret_data['provinceRegionId'] : '';
            $origin_post_data['delivery']['city_id'] = isset($ret_data['cityRegionId']) ? $ret_data['cityRegionId'] : '';
            $origin_post_data['delivery']['county_id'] = isset($ret_data['countryRegionId']) ? $ret_data['countryRegionId'] : '';
            $origin_post_data['delivery']['town_id'] = isset($ret_data['townRegionId']) ? $ret_data['townRegionId'] : '';

            $province_info  = $mdl_regions->dump(array('region_id'=>$origin_post_data['delivery']['province_id']),'package,region_id,local_name');
            $city_info  = $mdl_regions->dump(array('region_id'=>$origin_post_data['delivery']['city_id'],'p_region_id'=>$province_info['region_id']),'region_id,local_name');
            $county_info  = $mdl_regions->dump(array('region_id'=>$origin_post_data['delivery']['county_id'],'p_region_id'=>$city_info['region_id']),'region_id,local_name');
            $town_info  = $mdl_regions->dump(array('region_id'=>$origin_post_data['delivery']['town_id'],'p_region_id'=>$county_info['region_id']),'region_id,local_name,package');

            $city_name = $city_info['local_name'] ;
            $province_name= $province_info['local_name'] ;
            $county_name = $county_info['local_name'] ;
            $town_name = isset($town_info['local_name']) ? $town_info['local_name'] :'' ;
            if(empty($town_info)){
                $ship_area  = $province_info['package'].':'.$province_info['local_name'].'/'.$city_info['local_name'].'/'.$county_info['local_name'].':'.$county_info['region_id'];
            }else{
                $ship_area  = $province_info['package'].':'.$province_info['local_name'].'/'.$city_info['local_name'].'/'.$county_info['local_name'].'/'.$town_info['local_name'].':'.$town_info['region_id'];
            }
        } else {
            $ship_addr  = $origin_post_data['delivery']['ship_addr'] ;
            $addr_info = $this->getServerAddress($ship_addr) ;
            \Neigou\Logger::General("unicom.region.getRegionMapping.serverAddress", array("ship_addr"=>$ship_addr, "ret_data"=>$addr_info ,'delivery'=> $origin_post_data['delivery']));
            $addr_data = $addr_info['data'] ;
            $ship_area = '' ;
            if($addr_data) {
                $ship_area = sprintf("mainland:%s/%s/%s/%s:%d" ,
                    $addr_data['province_name'] ,
                    $addr_data['city_name'] ,
                    $addr_data['county_name'] ,
                    $addr_data['town_name'] ,
                    $addr_data['town_id']
                ) ;
                $city_name = $addr_data['city_name'] ;
                $province_name=  $addr_data['province_name']  ;
                $county_name = $addr_data['county_name'] ;
                $town_name = $addr_data['town_name'] ;
            }
        }

        $post_data = array(
            'member_id'=>$origin_post_data['member_id'],
            'company_id'=>$origin_post_data['company_id'],
            'mem_cpaddr_id'=>'',
            'voucher_list'=>'',
            'freeshipping_coupon_id'=>'',
            'check_point'=>false,
            'use_point'=>0,
            'delivery'=>array(
                'ship_name'=>$origin_post_data['delivery']['ship_name'],
                'ship_mobile'=>$origin_post_data['delivery']['ship_mobile'],
                'ship_zip'=>$origin_post_data['delivery']['ship_zip'],
                'ship_addr'=>$origin_post_data['delivery']['ship_addr'],
                
                //第三方传递
                'ship_province'=> $province_name,
                'ship_city'=> $city_name,
                'ship_county'=> $county_name,
                'ship_town'=> $town_name,
                'ship_area'=> $ship_area,
            ),
            'payment'=>array(
                'anonymous'=>'no',
                'currency'=>'CNY',
            ),
            'memo'=>'',
            'global'=>'false',
            'extend_info_code'=>'standard',
            'order_category'=>'unicom',
            'idcardname'=>'',
            'idcardno'=>'',
            'extend_data'=>'',
            'temp_order_id'=>'',
        );
        $pre_order_data = array(
            'temp_order_id'  =>  '', //订单号
            'ship_name'  => $post_data['delivery']['ship_name'],   //收货人姓名
            'ship_addr'  => $post_data['delivery']['ship_addr'],   //收货人详情地址
            'ship_zip'  => $post_data['delivery']['ship_zip'], //收货人邮编
            'ship_tel'  => '', //收货人电话
            'ship_mobile'  => $post_data['delivery']['ship_mobile'],   //收货人手机号
            'ship_province'  => $post_data['delivery']['ship_province'],   //收货人所在省
            'ship_city'  => $post_data['delivery']['ship_city'],   //收货人所在市
            'ship_county'  => $post_data['delivery']['ship_county'],   //收货人所在县
            'ship_town'  => $post_data['delivery']['ship_town'],   //收货人所在镇
            'idcardname'  => '', //收货人证件类型 (身份证)
            'idcardno'  => '', //收货人证件号 (身份证号)
        );
        return $post_data;
    }
    
    
    //填充货品属性
    public function formatProductList($origin_product_list,&$pre_order_items,&$msg = 'OK',&$error_code = 0,&$ret_data = array())
    {
        //检验商品授权
        $productBn = array();
        foreach($origin_product_list as $row){
            $productBn[] = $row['bn'];
        }
        
        $valid_product_bn = kernel::single("unicom_goods")->checkGoodsScope($productBn);
        if(count($productBn) != count($valid_product_bn) || count($valid_product_bn) == 0){
            $error_code = 10072;
            $msg = '商品数据未授权';
            return FALSE;
        }else{
            $check_scope_flag = TRUE;
            foreach($valid_product_bn as $product_bn=>$bool_val){
                if($bool_val == FALSE){
                    $ret_data[] = $product_bn;
                    $check_scope_flag = FALSE;
                }
            }
            if(FALSE === $check_scope_flag){
                $error_code = 10072;
                $msg = '商品数据未授权';
                return FALSE;
            }
        }

        // 获取商品信息
        $goodsList = app::get('unicom')->model('goods')->getGoodsInfo($productBn);

        foreach ($origin_product_list as $v) {
            if ( ! isset($goodsList[$v['bn']]) OR $goodsList[$v['bn']]['status'] != 1) {
                $ret_data[] = $v['bn'];
            }
        }
        if ( ! empty($ret_data)) {
            $error_code = 10073;
            $msg = '商品已下架';
            return false;
        }

        // 检查商品金额
        $productPriceList = app::get('unicom')->model('goods')->getGoodsPrice($productBn);
        if ( ! empty($productPriceList))
        {
            foreach ($productPriceList as $bn => $priceInfo)
            {
                if ($priceInfo['price'] >= 4000)
                {
                    $ret_data[] = $bn;
                }
            }
        }

        if ( ! empty($ret_data)) {
            $error_code = 10073;
            $msg = '商品已下架';
            return false;
        }
        $product_list = array();
        $thirdgoods_lib = kernel::single('b2c_openapi_basic_gallywixgoods');
        foreach($origin_product_list as $row){
            $tmp = array();
            $bn = $row['bn'];          
            
            $product_data = $this->getProductDataByBn($bn);
            if(empty($product_data)){
                $error_code = 10071;
                $msg = '商品数据未找到';
                $ret_data = array('bn'=>$bn);
                return FALSE;
            }
            $goods_data = $this->getGoodsDataByGoodsId($product_data['goods_id']);
            $tmp = array(
                'bn'=>$bn,
                //'subtotal'=>$subtotal,
                'goods_id'=>$product_data['goods_id'],
                'product_id'=>$product_data['product_id'],
                'quantity'=>$row['nums'], //商品数量
                'checked'=>true,
                'type'=>$goods_data['type_id'],
                //'img'=>$product_data['img'],
                'product_name'=>$product_data['name'],
                
                'weight'=>$product_data['weight'],
                'cost'=>$product_data['cost'],
                'spec_info'=>$product_data['spec_info'],
                
                'marketable'=>$product_data['marketable'],
                //'cat_id'=>$product_data['cat_id'],
                //'cat_path'=>$product_data['cat_path'],
                
                //'mall_list_id'=>$product_data['mall_list_id'],
                //'brand_id'=>$product_data['brand_id'],
                'ziti'=>$goods_data['ziti'],
                
                'jifen_pay'=>$goods_data['jifen_pay'],
                //'new_ziti_addr_list'=>$product_data['new_ziti_addr_list'],
                //'ziti_addr_list'=>$product_data['ziti_addr_list'],
                
                'goods_bn'=>$goods_data['bn'],
                'shop_id'=>$product_data['pop_shop_id'],
                'goods_bonded_type'=>$goods_data['goods_bonded_type'],
                
                //'operate'=>$product_data['operate'],
                 'name'=>$product_data['name'],
            );
            
            
            $price_info = $thirdgoods_lib->getPrice($bn);
            
            $price = $price_info[$bn]['price'];
            $point_price = $price_info[$bn]['point_price'];
            $mktprice = $price_info[$bn]['mktprice'];
            
            $tmp['price'] = array(
                'price'=>$price,//
                'point_price'=>$point_price,
                'mktprice'=>$mktprice,//$product_data['mktprice']
                //'operate_price'=>$goods_data['operate_price'],
            );

            $product_list[$bn] = $tmp;
            
            //筛选出可以预下单的货品,配置参照订单服务：Salyt->SplitProduct
            $product_bn_info_arr = explode('-',$bn);
            if(isset($product_bn_info_arr[0]) && in_array($product_bn_info_arr[0],array('JD','YHD','YGSX','KL','MRYX','ZP'))){
                //预下单组装数据
                $pre_order_items[] = array(
                    'price'=>$price,
                    'nums'=>$tmp['quantity'],
                    'product_bn'=>$bn,
                    'name'=>$tmp['product_name'],
                );
            }
        }
        
        $ret_data = $product_list;
        return TRUE;
    }
    

    /**
     * 请求数据初始化
     * @param $arr_args_post
     * @param string $terminal
     * @param $platform
     */
    public function initRequestData($arr_args_post, $terminal = 'web', $platform)
    {
        //用户id
        $this->request_data['member_id'] = $arr_args_post['member_id'];
        //公司id
        $this->request_data['company_id'] = $arr_args_post['company_id'];
        //用户公司地址id
        $this->request_data['mem_cpaddr_id'] = $arr_args_post['mem_cpaddr_id'];
        
        //收货地址信息
        $delivery = $arr_args_post['delivery'];
        $this->request_data['ship_name'] = $delivery['ship_name'];
        $this->request_data['ship_mobile'] = $delivery['ship_mobile'];
        $this->request_data['ship_zip'] = $delivery['ship_zip'];

        $this->request_data['shipping_id'] =  $delivery['shipping_id'];
        $temp_area_addr = explode(':', $delivery['ship_area']);
        $temp_area = explode('/',$temp_area_addr[1]);

        //收货人所在省
        $this->request_data['ship_province'] = $temp_area[0];
        //收货人所在市
        $this->request_data['ship_city'] = $temp_area[1];
        //收货人所在县
        $this->request_data['ship_county'] = !empty($temp_area[2]) ? $temp_area[2] : "";
        //收货人所在镇
        $this->request_data['ship_town'] = !empty($temp_area[3]) ? $temp_area[3] : '';

        $this->request_data['area_id'] = !empty($temp_area_addr[2]) ? $temp_area_addr[2] : "";

        //积分是老购物车单独处理地址
        if($platform == 'jifen'){
            $this->request_data['ship_addr']= $delivery['ship_addr'];
        }else {
            $separator = ' ';//需要用空格分割拼接，四级地址+详细地址
            $this->request_data['ship_addr'] = $this->request_data['ship_province'] .$separator.
                $this->request_data['ship_city'].$separator.
                $this->request_data['ship_county'].$separator.
                $this->request_data['ship_town'].$separator.
                $delivery['ship_addr'];
        }

        //平台来源PC|手机
        $this->request_data['terminal'] = $terminal;

        //匿名下单
        $this->request_data['anonymous'] = isset($arr_args_post['payment']['anonymous']) ? 'yes' : 'no';
        //订单附言
        $this->request_data['memo'] = !empty($arr_args_post['memo']) ? $arr_args_post['memo'] : '';
        //交易币种
        $this->request_data['currency'] = isset($arr_args_post['payment']['currency']) ? $arr_args_post['payment']['currency'] : '';

        //支付方式限制
        $this->request_data['payment_restriction'] = $arr_args_post['global'] == 'true' ? 'global' : '';
        //内购业务关系编码 type（1:普通订单，2:虚拟，3:周边游）
        $this->request_data['extend_info_code'] = $arr_args_post['extend_info_code'];
        //内购业务项目编码 order_refer (订单来源),[neigou,jifen]
        $this->request_data['order_category'] = $arr_args_post['order_category'];
        //订单分类 platform(内购订单，积分订单)
        $this->request_data['system_code'] = $platform;
        //拆单结果id
        $this->request_data['split_id'] = '';
        //扩展数据 dcard_zhengmain_pic,idcard_fanmian_pic,idcardname,idcardno
        $this->request_data['extend_data'] = $arr_args_post['extend_data'];
        $this->request_data['idcardname'] = $arr_args_post['idcardname'];
        $this->request_data['idcardno'] = $arr_args_post['idcardno'];
        $this->request_data['channel'] = 'EC';
        //临时订单号
        $this->request_data['temp_order_id'] = (isset($arr_args_post['temp_order_id']) && !empty($arr_args_post['temp_order_id'])) ? $arr_args_post['temp_order_id'] : $this->getOrderId();
        //收货方式,默认为：1 表示快递
        $this->request_data['receive_mode'] = isset($arr_args_post['receive_mode']) ? $arr_args_post['receive_mode'] : '1';
        //如果自提就直接免邮
        if($this->request_data['receive_mode'] == 2){
            $this->request_data['is_free_shipping'] = true;
        }
        //预下单订单
        $this->request_data['preorder_order'] = isset($arr_args_post['preorder_order']) ? $arr_args_post['preorder_order'] : '';
        \Neigou\Logger::General("unicom.2b.create", array("platform"=>"web", "initRequestData_request_data"=>$this->request_data));
    }
    
    //获取供应商运费
    public function getSupplierFreight($req_data,&$msg = '', &$error_code = '')
    {
        $ret = \Neigou\ApiClient::doServiceCall('delivery', 'Delivery/ToB/Freight', 'v1', null,$req_data);
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'];
        }else{
            $error_code = $ret['service_data']['error_code'];
            $msg = '获取供应商运费异常';
        }
        return false;
    }
    
    //预下单获取真实商品价格
    public function preOrder($order_data,$order_items,&$pre_order,&$msg, &$error_code)
    {
        $address = $order_data['ship_province'].' '
                   .$order_data['ship_city'].' '
                   .$order_data['ship_county'].' '
                   .$order_data['ship_town'].' '
                   .$order_data['ship_addr'];
        $req_data  = array(
            'temp_order_id'  =>  $order_data['temp_order_id'], //订单号
            'ship_name'  => $order_data['ship_name'],   //收货人姓名
            'ship_addr'  => $address,   //收货人详情地址
            'ship_zip'  => $order_data['ship_zip'], //收货人邮编
            'ship_tel'  => $order_data['ship_tel'], //收货人电话
            'ship_mobile'  => $order_data['ship_mobile'],   //收货人手机号
            'ship_province'  => $order_data['ship_province'],   //收货人所在省
            'ship_city'  => $order_data['ship_city'],   //收货人所在市
            'ship_county'  => $order_data['ship_county'],   //收货人所在县
            'ship_town'  => $order_data['ship_town'],   //收货人所在镇
            'idcardname'  => $order_data['idcardname'], //收货人证件类型 (身份证)
            'idcardno'  => $order_data['idcardno'], //收货人证件号 (身份证号)
            'items'=>$order_items
        );

        $ret = \Neigou\ApiClient::doServiceCall('preorder', 'PreOrder/Create', 'v1', null,$req_data);
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            $pre_order = $ret['service_data']['data'];
            return true;
        }

        $error_code = isset($ret['service_data']['error_detail_code']) ? $ret['service_data']['error_detail_code'] : 10301;
        $msg = isset($ret['service_data']['error_msg'][0]) ? $ret['service_data']['error_msg'][0] : '预下单服务异常';
        return false;
   
    }
    
    //获取商品基础信息
    public function getBaseProductInfo($bns)
    {
        $products_lib = kernel::single('b2c_openapi_gallywixgoods');
        $stages = array(
            'company'=>$this->request_data['company_id'],
        );
        
        $skus = array();
        $result = $products_lib->queryProductsByBns4Unicom($bns,$stages);
        if($result['result']  == 'true'){
            $skus = $result['list'];
        }
        return $skus;
    }

    /**
     *
     * @param $msg
     * @return bool
     */
    public function checkData(&$msg, &$error_code, &$error_data = array())
    {
        //商品上下架检查
        {
            $is_marketable = true;
            foreach ($this->cart['products'] as $bn=>$item){
                if('true' != $item['marketable']){
                    $error_data = array(
                        'bn'=>array(
                            'bn'=>$bn,
                            'marketable'=>$item['marketable']
                        ),
                    );
                    $error_code = '3001';
                    $msg = '货品已下架';
                    $is_marketable = false;
                    break;
                }
                
            }
            
            if(false === $is_marketable){
                goto LABEL_RET_FAIL;
            }else{
                goto LABEL_RET_SUCC;
            }
        }
        
        LABEL_RET_FAIL:
            return false;
        LABEL_RET_SUCC:
            $this->product_list = $this->cart['products']; //检查通过的商品数据
            return true;
    }
    
    
     /**
     * 创建订单
     * @return bool
     */
    public function createOrder(&$msg = '', &$error_code = '', &$error_data = array())
    {
        $order_info = $this->request_data;

        //@TODO 调用接口创建订单
        $ret = \Neigou\ApiClient::doServiceCall('order', 'Order/Create', 'v1', null, $order_info);
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            $order_id = $ret['service_data']['data']['order_id'];
            //写入成功，记录$order_id信息
            $this->request_data['order_id'] = $order_id;
            //订单创建成功，记录状态，根据此状态快速释放锁定资源
            return $this->request_data;
        } elseif($ret['service_data']['error_detail_code'] == '401') { //库存不足
            $bn_list = $ret['service_data']['data'];
            \Neigou\Logger::General("unicom.2b.create",array("action"=>"createOrder", "bn_list"=>json_encode($bn_list), "order_info"=>$order_info));
            if(is_array($bn_list) && count($bn_list)>0){
                $msg = '库存不足';
                $error_data = array_values($bn_list);
                $error_code = 401;
            }
        } elseif($ret['service_data']['error_detail_code'] == '402') { //下单风控拦截
            $msg = '订单生成失败，当日未支付或取消订单数量过多。如需帮助，请拨打客服电话：4006666365';
            $error_code = 402;
        } else {
            $msg = '订单生成失败';
            $error_code = 499;
        }
        return false;
    }
    
    //完善货品结算对象和履约信息
    private function splitOrderListInit(){
        if(empty($this->product_list)) return false;
        //获取商品对于的履约平台
        $split_orders = kernel::single("b2c_pop_wms")->get_goods_wms(array('product_bns'=>array_keys($this->product_list)));
        if(!empty($split_orders)){
            \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'splitOrderListInit', "split_orders" => $split_orders));
            foreach ($split_orders['pop_wms'] as $wms_id=>$wms_list ){
                $wms_code = $wms_list['pack']['code'];
                foreach ($wms_list['orders'] as $owner_id=>$order_items){
                    //货品数据绑定
                    foreach($order_items as $product_bn=>$item){
                       $this->product_list[$product_bn]['pop_shop_id'] = $item['pop_owner_name'];
                       $this->product_list[$product_bn]['pop_owner_id'] = $owner_id;
                       $this->product_list[$product_bn]['pop_wms_code'] = $wms_code;
                       $this->product_list[$product_bn]['pop_shop_id'] = $item['pop_shop_id'];
                    }
                }
            }
        }
    }
    
    private function initSplitItems()
    {
        //快递费用
        $info['cost_freight'] = 0;
        //商品总金额
        $info['cost_item'] = 0;
        //订单总金额(快递费用 +商品总金额-优惠总金额)
        $info['final_amount'] = 0;
        //优惠总金额
        $info['pmt_amount'] = 0;
        //订单在线支付总金额(订单总金额-积分支付金额)
        $info['cur_money'] = 0;
        //积分支付金额
        $info['point_amount'] = 0;
        //商品重量
        $info['weight'] = 0;

        //履约平台编码
        $info['wms_code'] = '';
        //运营主体id
        $info['pop_owner_id'] = '';
        //商品税金
        $info['cost_tax'] = 0;
        //商品明细
        $info['items'] = array();
        return $info;
    }
  
    private function getSplitOrder()
    {
        //快递费用
        $info['cost_freight'] = 0;
        //商品总金额
        $info['cost_item'] = 0;
        //订单总金额(快递费用 +商品总金额-优惠总金额)
        $info['final_amount'] = 0;
        //优惠总金额
        $info['pmt_amount'] = 0;
        //积分支付金额
        $info['point_amount'] = 0;
        //履约平台编码
        $info['wms_code'] = '';
        //运营主体id
        $info['pop_owner_id'] = '';
        //商品税金
        $info['cost_tax'] = 0;
        //商品明细
        $info['items'] = array();
        return $info;
    }
    
   /**
     * 拆单数据组装（第二层数据）
     */
    private function initSplitOrder(&$msg,&$error_code)
    {
        //$split_orders
        if(count($this->product_list) >0){
            foreach($this->product_list as &$item){
                if(!isset($this->split_orders[$item['pop_owner_id']])){
                    $this->split_orders[$item['pop_owner_id']] = $this->initSplitItems();
                }
                $this->split_orders[$item['pop_owner_id']]['cost_freight'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['cost_freight'],$item['cost_freight']));
                $this->split_orders[$item['pop_owner_id']]['pmt_amount'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['pmt_amount'],$item['pmt_amount']));
                $this->split_orders[$item['pop_owner_id']]['point_amount'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['point_amount'],$item['point_amount']));
                //$tmp_cost_item = $this->objMath->number_multiple(array($item['price'],$item['nums']));
                $tmp_cost_item = $item['amount'];
                $this->split_orders[$item['pop_owner_id']]['cost_item'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['cost_item'], $tmp_cost_item));
                //订单总金额(快递费用 +商品总金额-优惠总金额)

                $tmp = $this->objMath->number_minus(array($this->objMath->number_plus(array($item['cost_freight'], $tmp_cost_item,$item['cost_tax'])), $item['pmt_amount']));
                $this->split_orders[$item['pop_owner_id']]['final_amount'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['final_amount'],$tmp));
                //订单在线支付总金额(订单总金额-积分支付金额)
                $tmp2 = $this->objMath->number_minus(array($tmp, $item['point_amount']));
                $this->split_orders[$item['pop_owner_id']]['cur_money'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['cur_money'],$tmp2));
                //商品总重量汇总
                $tmp_weight = $this->objMath->number_multiple(array($item['weight'],$item['nums']));
                $this->split_orders[$item['pop_owner_id']]['weight'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['weight'],$tmp_weight));

                $this->split_orders[$item['pop_owner_id']]['wms_code'] = $item['pop_wms_code'];
                $this->split_orders[$item['pop_owner_id']]['pop_owner_id'] = $item['pop_owner_id'];
                $this->split_orders[$item['pop_owner_id']]['cost_tax'] = $this->objMath->number_plus(array($this->split_orders[$item['pop_owner_id']]['cost_tax'],$item['cost_tax']));
                $this->split_orders[$item['pop_owner_id']]['items'][] = $item;
            }
        }else{
            $msg = '商品列表为空';
            $error_code = '2500';
            return false;
        }

        /*
        foreach($this->split_orders as $k=>$v){
            $v['cur_money'] = $this->objMath->number_minus(array($v['final_amount'] , $v['point_amount']));
            if($v['cur_money'] < 0 ){
                $v['cur_money'] = 0;
            }
        }*/
        foreach($this->split_orders as $k=>&$v){
            $v['cur_money'] = $this->objMath->number_minus(array($v['final_amount'] , $v['point_amount']));
            if($v['cur_money'] < 0 ){
                $v['cur_money'] = 0;
            }

            /*
            //按照订单计算运费
            $supplier_map = array();
            $total_weight = 0;
            $supplier_bn = '';
            foreach($v['items'] as $product){
                $total_weight += $product['weight'];
                if(0 == strlen($supplier_bn)){
                    $product_bn_prefix_arr = explode('-',$product['product_bn']);
                    $supplier_bn = $product_bn_prefix_arr[0];
                }
            }

            $sub_total = $v['cost_item'];
            $split_product_list = array();
            $split_product_list[$supplier_bn] = array(
                'weight' => $total_weight,
                'shipping_area' => array(
                    'area_id'=>$this->request_data['area_id'],
                ),
                'subtotal' => $sub_total,
            );

            $freigth_info = array();
            $r = $this->getOrderFreight($split_product_list,$freigth_info,$msg,$error_code);
            if(!$r){
                return false;
            }

            $freigth_total = $freigth_info[$supplier_bn]['freight'];
            if($freigth_total > 0 && $sub_total > 0){
                //分摊运费到商品
                foreach($v['items'] as $product){
                    $bn = $product['product_bn'];
                    if(!isset($supplier_map[$supplier_bn])){
                        $supplier_map[$supplier_bn] = array(
                            'bn'=>$bn,
                            'max'=>$product['amount'],
                            'use_freight_sum'=>0,
                        );
                    }else{
                        if($supplier_map[$supplier_bn]['max'] < $product['amount']){
                            $supplier_map[$supplier_bn] = array(
                                'bn'=>$bn,
                                'max'=>$product['amount'],
                                'use_freight_sum'=>$supplier_map[$supplier_bn]['use_freight_sum'],
                            );
                        }
                    }
                    $product['cost_freight'] = round($product['amount'] / $sub_total * $freigth_total,3);
                    $supplier_map[$supplier_bn]['use_freight_sum'] += $product['cost_freight'];
                }

                //如果未分配完
                foreach($supplier_map as $supplier_bn_key=>$row_item){
                    if($item['use_freight_sum'] <> $freigth_info[$supplier_bn_key]['freight']){
                        $bn = $row_item['bn'];
                        foreach($v['items'] as $product){
                            if($product['product_bn'] == $bn){
                                $product['cost_freight'] += ($freigth_info[$supplier_bn_key]['freight'] - $row_item['use_freight_sum']);
                            }
                        }
                    }
                }
                
                //运费绑定到单子,并增加现金需支付金额、总的订单金额
                $v['cost_freight'] = $freigth_total;
                $v['cur_money'] = $this->objMath->number_plus(array($v['cur_money'],$freigth_total));
                $v['final_amount'] = $this->objMath->number_plus(array($v['final_amount'],$freigth_total));
            }*/
        }
        return true;
    }

    /**
     * 拆单数据组装（第一层数据）
     */
    private function initSplit()
    {
        if(count($this->split_orders) >0){
            $this->split = $this->getSplitOrder();
            $final_amount_sum = 0;
            $pmt_amount_sum = 0;
            $point_amount_sum = 0;
            $cur_money_sum = 0;
            $cost_tax_sum = 0;
            
            $cost_freight_sum = 0;
            $total_amount = 0;
            
            foreach($this->split_orders as $item){
                $this->split['cost_freight'] = $this->objMath->number_plus(array($this->split['cost_freight'],$item['cost_freight']));
                $this->split['cost_item'] = $this->objMath->number_plus(array($this->split['cost_item'],$item['cost_item']));

                $this->split['wms_code'] = $item['wms_code'];
                $this->split['pop_owner_id'] = $item['pop_owner_id'];
                $this->split['weight'] = $this->objMath->number_plus(array($this->split['weight'], $item['weight']));
                $this->split['items'] = $this->product_list;
                $this->split['split_orders'][] = $item;
                
                //累加所有订单
                $final_amount_sum += $item['final_amount'];
                $pmt_amount_sum += $item['pmt_amount'];
                $point_amount_sum += $item['point_amount'];
                $cur_money_sum += $item['cur_money'];
                $cost_tax_sum += $item['cost_tax'];
                
                $cost_freight_sum += $item['cost_freight'];
                foreach($item['items'] as $row){
                    $total_amount += $row['amount'];
                }
            }
            $this->split['final_amount'] = $final_amount_sum;
            $this->split['pmt_amount'] = $pmt_amount_sum;
            $this->split['point_amount'] = $point_amount_sum;
            $this->split['cur_money'] = $cur_money_sum;
            $this->split['cost_tax'] = $cost_tax_sum;
            
            //成功时同步返回所需数据
            $this->request_data['final_amount'] = $final_amount_sum;
            $this->request_data['cost_freight'] = $cost_freight_sum;
            $this->request_data['total_amount'] = $total_amount;
            $this->request_data['cur_money'] = $cur_money_sum;
        }
    }
    
    //准备订单数据
    private function initOrder(&$msg,&$error_code)
    {
        $this->splitOrderListInit();
        $r = $this->initSplitOrder($msg,$error_code);
        if(!$r) return false;
        $this->initSplit();
        return true;
    }
    
    /**
     * 保持splist信息
     */
    public function saveSplit()
    {
        $split_info = $this->split;
        $product_list = $this->product_list;
        //@TODO 调用接口
        $ret = \Neigou\ApiClient::doServiceCall('order_split', 'OrderSplit/Create', 'v1', null, array('split_info'=>$split_info,'order_info'=>$product_list));
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            $split_id = $ret['service_data']['data']['split_id'];
            //写入成功，记录$split_id信息
            $this->request_data['split_id'] = $split_id;
            return true;
        }
        return false;
    }
    
    //验证收货地址有效性
    public function checkDeliveryAddress($ship_area,&$msg,&$error_code)//$post['delivery']['ship_area']
    {
        $obj_region = app::get('ectools')->model('regions');
        if (!$obj_region->is_correct_leaf_region_2b($ship_area)) { 
            $msg = '所选收货地址信息不完整，请完善信息';
            $error_code = '1005';
            \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'checkDeliveryAddress', 'ship_area'=>$ship_area,"error_code" => $error_code, "message" => $msg));
            return false;
        }
        return true;
    }
    
    /**
     * 商品明细信息
     * @param $product
     * @return array
     */
    private function initProduct($product,$skus)
    {
        $info = array();
        //货品bn
        $info['product_bn'] = $product['bn'];
        //货品名称
        $info['name'] = $product['name'];
        //货品数量
        $info['nums'] = $product['quantity'];
        //货品单价
        $info['price'] = $skus["{$info['product_bn']}"]['price'];//$product['price']['price']
        //市场价
        $info['mktprice'] = $skus["{$info['product_bn']}"]['mktprice'];//$product['price']['mktprice']
        //成本价
        $info['cost'] = $skus["{$info['product_bn']}"]['cost'];//$product['price']['cost'];
        //货品总价格（货品单价*货品数量）
        $info['amount'] = $this->objMath->number_multiple(array($info['price'],$info['nums']));//以市场价9折计算
        //积分价格
        $info['point_amount'] = 0;
        //货品总重量
        $info['weight'] = $skus["{$info['product_bn']}"]['weight'];//$product['weight'];
        //默认优惠金额
        $info['pmt_amount'] = 0;
        //goods_id 限时限购时需要goods_id
        $info['goods_id'] = $product['goods_id'];
        //商品类型（'product','pkg','gift','adjunct'）
        $info['item_type'] = 'product';
        //店铺id (运营服务使用)
        $info['shop_id'] = $product['shop_id'];
        // (运营服务使用)
        $info['bn'] = $product['bn'];
        //product_id(运营服务使用)
        $info['id'] = $product['product_id'];
        //品牌id(运营服务使用)
        $info['brand_id'] = $product['brand_id'];
        //运费
        $info['cost_freight'] = 0;
        //货品税金
        $info['cost_tax'] = 0;
        //上下架
        $info['marketable'] = $skus["{$info['product_bn']}"]['marketable'];
        //商品参与免邮运营活动
        if(isset($product['is_free_shipping']) && $product['is_free_shipping'] == true){
            $info['is_free_shipping'] = true;
        }
        
        return $info;
    }
    
    //初始化货品数据
    private function initCart($cart, &$msg, &$error_code)
    {
        $product_list = $cart['product_list'];
        
        $bns = array_keys($product_list);
        $skus = $this->getBaseProductInfo($bns);
        if(count($skus) != count($bns)){
            $error_code = 2001;
            $msg = '获取货品基本信息失败';
            \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'getBaseProductInfo','skus'=>$skus,"error_code" => $error_code, "message" => $msg));
            return  false;
        }
       
        //格式化内部需要的货品属性
        foreach($product_list as $product){
           $this->cart['products'][$product['bn']] = $this->initProduct($product,$skus);
        }
        
        return true;
    }
    
    private function getOrderFreight($split_product_list,&$freigth_info,&$msg,&$error_code)
    {
        $freigth_info  = $this->getSupplierFreight($split_product_list,$msg,$error_code);
        if(false == $freigth_info || empty($freigth_info)){
            $error_code = 2002;
            $msg = '获取货品运费失败';
            \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'getOrderFreight', 'freigth_info'=>$freigth_info,"error_code" => $error_code, "message" => $msg));
            return  false;
        }
        return true;
    }

    //分摊运费
    private function initProductFreight(&$msg,&$error_code)
    {
         //按照供应商分割出平台，如JD/SHOP
        $split_product_list = array();
        foreach ($this->cart['products'] as $bn => $product){
            $product_bn = explode('-',$bn);
            $supplier_bn = $product_bn[0];
            if(!isset($split_product_list[$supplier_bn])){
                $split_product_list[$supplier_bn] = array(
                    'weight' => $product['weight'] + 0,
                    'shipping_area' => array(
                        'area_id'=>$this->request_data['area_id'],
                    ),
                    'subtotal' => $product['amount'],
                );
            }else{
                $split_product_list[$supplier_bn] = array(
                    'weight' => $product['weight'] + $split_product_list[$supplier_bn]['weight'],
                    'shipping_area' => array(
                        'area_id'=>$this->request_data['area_id'],
                    ),
                    'subtotal' => ($product['amount'] + $split_product_list[$supplier_bn]['subtotal']),
                );
            }
        }
        
        $freigth_info  = $this->getSupplierFreight($split_product_list,$msg,$error_code);
        if(false == $freigth_info || empty($freigth_info)){
            $error_code = 2002;
            $msg = '获取货品运费失败';
            \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'initProductFreight', 'freigth_info'=>$freigth_info,"error_code" => $error_code, "message" => $msg));
            return  false;
        }
        
        //开始分摊运费
        $supplier_map = array();
        foreach ($this->cart['products'] as $bn => &$product){
            $product_bn = explode('-',$bn);
            $supplier_bn = $product_bn[0];
            
            $freigth_total = $freigth_info[$supplier_bn]['freight'];
            $sub_total = $split_product_list[$supplier_bn]['subtotal'];
            if(!isset($supplier_map[$supplier_bn])){
                $supplier_map[$supplier_bn] = array(
                    'bn'=>$bn,
                    'max'=>$product['amount'],
                    'use_freight_sum'=>0,
                );
            }else{
                if($supplier_map[$supplier_bn]['max'] < $product['amount']){
                    $supplier_map[$supplier_bn] = array(
                        'bn'=>$bn,
                        'max'=>$product['amount'],
                        'use_freight_sum'=>$supplier_map[$supplier_bn]['use_freight_sum'],
                    );
                }
            }
            
            if($freigth_total > 0 && $sub_total > 0){
                $product['cost_freight'] = round($product['amount'] / $sub_total * $freigth_total,3);
                $supplier_map[$supplier_bn]['use_freight_sum'] += $product['cost_freight'];
            }
        }
        
        //如果未分配完
        foreach($supplier_map as $supplier_bn=>$item){
            if($item['use_freight_sum'] <> $freigth_info[$supplier_bn]['freight']){
                $bn = $item['bn'];
                $this->cart['products']["{$bn}"]['cost_freight'] += ($freigth_info[$supplier_bn]['freight'] - $item['use_freight_sum']);
            }
        }
        return true;
    }

    /**
     * @param $post
     * @param $cart
     * @param string $terminal [pc,wap]
     * @param string $platform ['neigou']
     * @param $msg
     * @param $error_code
     * @return bool
     */
    public function generate($post, $cart, $terminal = 'pc', $platform = 'neigou', &$msg, &$error_code, &$error_data = array())
    {
        try {
            //收货地址检查
            $r = $this->checkDeliveryAddress($post['delivery']['ship_area'],$msg,$error_code);
            if(!$r) return false;
            
            //组织数据1.订单基本信息
            $this->initRequestData($post, $terminal, $platform);
            //组织数据2.商品信息
            if(false === $this->initCart($cart,$msg,$error_code)){
                \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'initCart','post'=>$post,'cart'=>$cart,"error_code" => $error_code, "message" => $msg));
                return false;
            }

            //检查数据
            if (false === $this->checkData($msg, $error_code, $error_data)) {
                \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'checkData', "error_code" => $error_code, "message" => $msg));
                return false;
            }

            //初始化订单数据
            //$this->initOrder();
            $init_order_result = $this->initOrder($msg,$error_code);
            if(!$init_order_result) return false;
 
            //保存拆单信息
            if (false === $this->saveSplit()) {
                $msg = '订单创建失败';
                $error_code = '1040';
                \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'saveSplit', "error_code" => $error_code, "message" => $msg));
                return false;
            }

            //创建订单
            if (false === $result = $this->createOrder($msg, $error_code, $error_data)) {
                switch ($error_code) {
                    case '401':
                        $error_code = '1050';
                        break;
                    case '402':
                        $error_code = '1060';
                        break;
                    default:
                        $error_code = '1099';
                }
                \Neigou\Logger::General("unicom.2b.create", array("platform" => "web", 'function' => 'createOrder', "error_code" => $error_code, "message" => $msg));
                return false;
            }
            
            return $this->request_data;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 取消订单
     * @param $order_id
     * @return bool
     */
    public function cancelOrder($order_id)
    {
        if(empty($order_id)){
            return false;
        }
        $ret = \Neigou\ApiClient::doServiceCall('order', 'Order/Cancel', 'v1', null, array('order_id'=>$order_id));
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code']) {
            return true;
        }
        return false;
    }
    
     /**
     * 获取订单号
     * @return bool
     */
    public function getOrderId()
    {
        $ret = \Neigou\ApiClient::doServiceCall('order', '/OrderId/Create', 'v1', null, array('null'=>'null'));
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            $order_id = $ret['service_data']['data']['order_id'];
            return $order_id;
        }
        return false;
    }

    /**
     * 获取订单信息
     *
     * @param $order_id
     * @param $code （code明确了服务调用是否成功、订单是否存在：1403:参数错误，1200:订单存在,1204:订单不存在，1499:服务不可用）
     * @return bool
     */
    public function getOrderInfo($order_id, &$code = '0')
    {
        if(empty($order_id)){
            $code = '1403';
            return false;
        }
        $ret = \Neigou\ApiClient::doServiceCall('order', 'Order/Get', 'v1', null, array('order_id'=>$order_id));
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            $order_info = $ret['service_data']['data'];
            $code = '1200';
            return $order_info;
        } elseif ('OK' == $ret['service_status'] && $ret['service_data']['error_detail_code'] == '401'){
            $code = '1204';
        }else{
            $code = '1499';
        }
        return false;
    }
    
    
    private function getUnConfirmOrders()
    {
        $preorder_model_obj = app::get('unicom')->model('preorder');
        $filter = array(
            'status'=>self::ORDER_PAY_CONFIRM_STATUS_WAIT_CONFIRM,
        );
        $limit = 1000;
        $results = $preorder_model_obj->getList('*', $filter,$limit);
        return is_array($results) ? $results : array();
    }
    
    private function doAutoCancel($order,$seconds =7200)
    {
        $order_id = $order['order_id'];
        $order_data = $this->getOrderInfo($order_id, $code = '0');
        if(empty($order_data))return false;
        
        if(time() < $order_data['create_time'] + $seconds){
            return false;
        }

        if($order_data['pay_status'] == 1){//'支付状态 1：未支付 2：已支付 3：全额退款',
            $r = $this->cancelOrder($order_id);
            if($r){
                $preorder_model_obj = app::get('unicom')->model('preorder');
                $data = array(
                    'sys_status'=>self::ORDER_PAY_CONFIRM_SYS_STATUS_AUTO_CANCEL,
                    'status'=>self::ORDER_PAY_CONFIRM_STATUS_CANCEL,
                    'last_modified'=>time(),
                );
                $r1 = $preorder_model_obj->updateInfo($order['id'],$data);
                if($r1) return true;
            }
            \Neigou\Logger::Debug("unicom.gallywix.order", array("function"=>"doAutoCancel","order"=>$order,"msg"=>"自动取消订单失败"));
        }
        return false;
    }
    
    //超时自动取消订单
    public function autoCancel()
    {
        $results = $this->getUnConfirmOrders();
        foreach($results as $order){
            $seconds = 3600 * 24 * 7;
            $this->doAutoCancel($order,$seconds);
        }
    }
    
    
}
