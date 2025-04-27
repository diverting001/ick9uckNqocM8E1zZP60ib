<?php

/**
 * 中燃支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/12/28
 * Time: 15:25
 */
final class wap_payment_plugin_mchinagas extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '中燃支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '中燃支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mchinagas';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mchinagas';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '中燃支付';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';
    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'iswap';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"01");

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mchinagas_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mchinagas', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
        $this->submit_url = $this->getConf('submit_url', __CLASS__);
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '中燃支付配置信息';
    }

    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('ectools')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'open_id'=>array(
                'title'=>app::get('ectools')->_('OpenId'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_key'=>array(
                'title'=>app::get('ectools')->_('AppKey'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'company_id'=>array(
                'title'=>app::get('ectools')->_('公司ID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'cancel_url'=>array(
                'title'=>app::get('ectools')->_('订单取消URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'add_product_url'=>array(
                'title'=>app::get('ectools')->_('添加商品接口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ship_url'=>array(
                'title'=>app::get('ectools')->_('物流通知接口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'finish_url'=>array(
                'title'=>app::get('ectools')->_('确认收货接口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'wx_url'=>array(
                'title'=>app::get('ectools')->_('微信支付URl'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'shop_id'=>array(
                'title'=>app::get('ectools')->_('shop_id 确认收货使用'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'pay_type'=>array(
                'title'=>app::get('wap')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'status'=>array(
                'title'=>app::get('ectools')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
                'name' => 'status',
            ),
        );
    }
    /**
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro(){
        return app::get('ectools')->_('中燃支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $this->add_field('appkey',$this->getConf('app_key', __CLASS__));//商家appkey
        $this->add_field('timestamp',time());//商品信息 TODO 文档上疑问 非必填
        //获取商品图片
        $image = app::get("image")->model("image_attach");

        //商品信息参数
        $productNoList = '';
        $productCountList = '';
        foreach ($order_info['items'] as $k => $v) {
            //先进行商品新增操作
            $product['productNo'] = $v['bn'];//商品编码
            $product['productName'] = $v['name'];//商品编码
            $product['salePrice'] = number_format($v['price'],2,".","")*100;//商品编码
            $product['marketPrice'] = number_format($v['mktprice'],2,".","")*100;//商品编码
            $product_tmp = app::get('b2c')->model('products')->getRow('goods_id',array('bn'=>$v['bn']));
            $image_data = $image->getList("attach_id,image_id",array("target_id"=>intval($product_tmp['goods_id']),'target_type'=>'goods'), 0, 4); // 只显示4个图片
            $pic_url = base_storager::image_path($image_data[0]['image_id']);
            $product['picdirs'] = $pic_url;//商品编码
            $product['appkey'] = $this->getConf('app_key', __CLASS__);//商品编码
            $product['timestamp'] = time();//商品编码
            $product['sign'] = $this->genProductSign($product);//商品编码
            $r = $this->request($this->getConf('add_product_url', __CLASS__),$product,0);
            if($r && $r['status']==1){
                $productNoList .= $v['bn'].',';
                $productCountList .= $v['nums'].',';
            } else {
                //跳转支付失败  reason 商品更新失败
                $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
                //redirect to pay err page
                \Neigou\Logger::General('pay.mchinagas', array('action' => 'product add fail', 'result' => $r));
                header('Location: '.$url);die;
            }
        }
        $this->add_field('productNOList',substr($productNoList,0,-1));//商品编号用，隔开
        $this->add_field('productCountList',substr($productCountList,0,-1));//商品数量用，隔开

        //订单信息参数
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));

        $this->add_field('userMobile',$member_info['external_bn']);//订单信息-订单编号 非必填
        $this->add_field('orderSubNOAPI',$payment['order_id']);//订单号
        $this->add_field('orderNOAPI',$payment['payment_id']);//订单信息-订单编号 非必填
        $this->add_field('totalFee',number_format($payment['cur_money'],2,".","")*100);//订单信息-订单总支付金额（单位：分，可选，实际支付金额取商品价格汇总）非必填
        $this->add_field('discountAmount',number_format($order_info['pmt_amount']+$order_info['point_amount'],2,".","")*100);//订单信息-优惠金额（单位：分）
        $this->add_field('freight',number_format($order_info['cost_freight'],2,".","")*100);//订单信息-运费（单位：分）
        $this->add_field('yunfei',number_format($order_info['cost_freight'],2,".","")*100);//订单信息-运费（单位：分）
        $this->add_field('commodityTax',number_format($order_info['cost_tax'],2,".","")*100);//订单信息-税费（单位：分）
        //查询地区码
        $city_info = app::get('ectools')->model('order_area')->getRow('code',array('name'=>$order_info['ship_county']));
        $this->add_field('areaId',$city_info['code']);//订单信息-收货人地址编码 //TODO 这个怎么对应
        $this->add_field('deliveryName',$order_info['ship_name']);//订单信息-收货人姓名
        $this->add_field('deliveryAddress',$order_info['ship_addr']);//订单信息-收货人地址
        $this->add_field('deliveryPhone',$order_info['ship_mobile']);//订单信息-收货人手机号
//        $this->add_field('remark',$order_info['memo']);//订单信息-订单表备注 非必填
        $url = $this->getConf('submit_url',__CLASS__);
        $ret = $this->request($url,$this->fields,1);
        if($ret['status']==1 && $ret){
            //请求成功并且服务器正常
            //page执行脚本提交
            $order_id = $ret['data']['orderSubNO'];
            //输出html进行支付
            $genA['price'] = number_format($payment['cur_money'],2,".","")*100-$order_info['cost_freight']*100;
            $genA['freight'] = $order_info['cost_freight']*100;
            $genA['ship_name'] = $order_info['ship_name'];
            $genA['ship_addr'] = $order_info['ship_addr'];
            $genA['ship_mobile'] = $order_info['ship_mobile'];
            $genA['wx_price'] = number_format($payment['cur_money'],2,".","")*100;
            echo $this->gen_app_page($order_id,$genA);
        }
        exit;
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        //跳转到订单详情页面
        //获取订单编号
        $order_info = app::get('ectools')->model('order_bills')->getRow('rel_id',array('bill_id'=>$recv['out_trade_no']));
        $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_member','act'=>'new_orderdetail','arg0'=>$order_info['rel_id']));
        \Neigou\Logger::General('pay.mchinagas', array('action' => 'pay_fail', 'result' => $order_info));
        header('Location: '.$url);die;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    /**
     * <生成签名方法>
     * <功能详细描述>
     * @param $postData
     * @return string
     * eg:md5(OpenID+appkey+timestamp+productNoList+productCountList+orderNO+totalFee+discountAmount+freight+areaId+deliveryName+deliveryAddress+deliveryPhone+remark)
     */
    public function genSign($postData) {
        //按照文档顺序顺序排列好
        $data['OpenID'] = $this->getConf('open_id', __CLASS__);
        $data['appkey'] = $this->getConf('app_key', __CLASS__);
        $data['timestamp'] = $postData['timestamp'];
        $data['productNoList'] = $postData['productNoList'];
        $data['orderNO'] = $postData['orderNO'];
        $data['totalFee'] = $postData['totalFee'];
        $data['discountAmount'] = $postData['discountAmount'];
        $data['freight'] = $postData['freight'];
        $data['areaId'] = $postData['areaId'];
        $data['deliveryName'] = $postData['deliveryName'];
        $data['deliveryAddress'] = $postData['deliveryAddress'];
        $data['deliveryPhone'] = $postData['deliveryPhone'];
        $data['remark'] = $postData['remark'];
        $sign_str = http_build_query($data);
        \Neigou\Logger::General('paysign.mchinagas',array('linkS'=>$sign_str));
        return md5($sign_str);
    }

    /**
     * <生成签名方法>
     * <功能详细描述>
     * @param $postData
     * @return string
     * eg:md5(OpenID+appkey+timestamp+productNoList+productCountList+orderNO+totalFee+discountAmount+freight+areaId+deliveryName+deliveryAddress+deliveryPhone+remark)
     */
    public function genProductSign($postData) {
        //按照文档顺序顺序排列好
        $data['OpenID'] = $this->getConf('open_id', __CLASS__);
        $data['appkey'] = $this->getConf('app_key', __CLASS__);
        $data['timestamp'] = $postData['timestamp'];
        $data['productNo'] = $postData['productNo'];
        $data['productName'] = $postData['productName'];
        $data['salePrice'] = $postData['salePrice'];
        $data['marketPrice'] = $postData['marketPrice'];
        $data['picdirs'] = $postData['picdirs'];
        $sign_str = http_build_query($data);
        \Neigou\Logger::General('product.mchinagas',array('linkS'=>$sign_str));
        return md5($sign_str);
    }


    public function request($api = '', $post_data = array(), $sign = 1) {
        $url = $api;
        if ($sign) {
            $post_data['sign'] = $this->genSign($post_data);
        }
        $curl = new \Neigou\Curl();
        //TODO 正式环境打开代理请求
//        $proxyServer = NEIGOU_HTTP_PROXY;
        $opt_config = array(
//            CURLOPT_POST => true,
            CURLOPT_FAILONERROR => true,
//            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
//            CURLOPT_PROXY => $proxyServer,
        );
        $curl->SetOpt($opt_config);
        $curl->SetHeader('Content-Type', 'application/json');
        $req = http_build_query($post_data);
        $url = $api.'?'.$req;
//        echo $url;die;
        $result = $curl->Post($url, json_encode($post_data));
        \Neigou\Logger::General('pay.mchinagas', array('action' => 'req', 'opt_config' => $opt_config,'req_url'=>$url,'post_data'=>$post_data,'response_data'=>$result));
        $resultData = json_decode($result, true);
        return $resultData;
    }

    public function gen_form(){
        return '';
    }

    public function gen_app_page($order_id,$data){
        //判断是否是APP
        if(isset($_SERVER['HTTP_USER_AGENT'])&&(stripos(strtolower($_SERVER['HTTP_USER_AGENT']), 'appcan')==false)){
            header('Location:'.$this->getConf('wx_url',__CLASS__).'?orderSubNO='.$order_id.'&zj='.$data['wx_price']);
            \Neigou\Logger::General('pay.mchinagas.wx',array('remark'=>'跳转支付','subNO'=>$order_id));
            exit();
        }
        $html = <<<eot
<!DOCTYPE html>
<html
	class="um landscape min-width-240px min-width-320px min-width-480px min-width-768px min-width-1024px">
<head>
<title>中燃支付</title>
<meta charset="utf-8">
<meta name="viewport"
	content="target-densitydpi=device-dpi, width=device-width, initial-scale=1, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
</head>
<style>
</style>
<body class="um-vp bc_c9a" ontouchstart>
支付跳转中……



</body>
<script src="/app/wap/statics/chinagas/jquery-1.7.2.min.js"></script>
<script src="/app/wap/statics/chinagas/main.js"></script>
<script src="/app/wap/statics/chinagas/appcan.js"></script>
<script src="/app/wap/statics/chinagas/appcan.control.js"></script>
<script>
//setTimeout(function(){
//
//	        },1000)

	    appcan.ready(function() {
            OrderPayment("{$order_id}","{$data["price"]}","{$data["freight"]}");
        })
        setTimeout(function(){
                window.close();
	        },5000)
	/*
	 * 订单支付 OrderPayment()
	 * @param {string} orderNo  订单编号
	 * @param {object} productFee 订单商品总价，实体必传
	 * @param {object} yunfei 订单运费总价，实体必传
	 */
	function OrderPayment(orderNo, productFee, yunfei) {
		var FeeAndFei = {};
		FeeAndFei.yunfei = yunfei;
		FeeAndFei.productFee = productFee;
        FeeAndFei.deliveryName = "{$data["ship_name"]}";
        FeeAndFei.deliveryAddress = "{$data["ship_addr"]}";
        FeeAndFei.deliveryPhone = "{$data["ship_mobile"]}";
        console.log("setLocVal('isVirtual',0);setLocVal('payOrderNO','"+orderNo+"');setLocVal('totalFee','"+JSON.stringify(FeeAndFei)+"');openNewWin('checkstand_ST','checkstand_ST.html',10)");
		uescript(
				"other",
				"setLocVal('isVirtual',0);setLocVal('payOrderNO','"+orderNo+"');setLocVal('totalFee','"+JSON.stringify(FeeAndFei)+"');openNewWin('checkstand_ST','checkstand_ST.html',10);;appcan.window.close(0);");
	 }
</script>
</html>
eot;
        return $html;

    }

    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function get_html() {
        $encodeType =  'utf-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">

eot;
        foreach ( $this->fields as $key => $value ) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $param['salt'] = 'mchinagasxneigou';
        $str = $this->_create_link_string($param,true,true);
        if($sign==md5($str)){
            return true;
        } else {
            \Neigou\Logger::General('callback.mchinagas.sign.err',array('linkS'=>$str,'sign'=>md5($str),'sign_req'=>$sign));
            return false;
        }
    }

    /**
     * 【新】将数组转换成String
     * @return string
     */
    public function _create_link_string($para, $sort, $encode){
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if($sort){
            $para = $this->argSort ( $para );
        }
        while ( list ( $key, $value ) = each( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }

    /**
     * 数组排序
     * @param $para
     * @return mixed
     */
    function argSort($para) {
        ksort ( $para );
        reset ( $para );
        return $para;
    }
}