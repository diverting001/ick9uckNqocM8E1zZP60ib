<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/**
 * alipay notify 异步验证接口
 * @auther shopex ecstore dev dev@shopex.cn
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_malipay_server extends ectools_payment_app {

    /**
     * @支付宝固定参数
     */
    public $sec_id = 'MD5';    //签名方式 不需修改
    public $_input_charset = 'utf-8';    //字符编码格式
    public $_input_charset_GBK = "GBK";
    public $v = '2.0';    //版本号
    public $gateway_paychannel="https://mapi.alipay.com/cooperate/gateway.do?";
    public $gateway="http://wappaygw.alipay.com/service/rest.htm?";

	/**
	 * 支付后返回后处理的事件的动作
	 * @params array - 所有返回的参数，包括POST和GET
	 * @return null
	 */
    public function callback(&$recv){
        $ret['callback_source'] = 'server';
        //键名与pay_setting中设置的一致
        //$mer_id = trim( $this->getConf('mer_id', substr(__CLASS__, 0, strrpos(__CLASS__, '_'))) );
        //$mer_key = trim( $this->getConf('mer_key', substr(__CLASS__, 0, strrpos(__CLASS__, '_'))) );
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_malipay');
        $mer_key = $this->getConf('mer_key', 'wap_payment_plugin_malipay');
        if($this->is_return_vaild($recv,$mer_key,$this->sec_id)){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('wap')->_('手机支付宝');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee'];
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time();
            $ret['pay_app_id'] = "malipay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';

            $status = $recv['trade_status'];        //返回token
            if($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS'){
                echo "success";
                $ret['status'] = 'succ';
            }else{
                echo "fail";
                $ret['status'] = 'failed';
            }
        }else{
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }
        return $ret;
    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    // todo v2.2
    public function is_return_vaild($form,$key,$sec_id){
        $get = $this->para_filter($form);
        $sort_get = $this->arg_sort($get);
        $my_sign = $this->build_mysign($sort_get,$key,$sec_id);
        if($form['sign'] == $my_sign){
            return true;
        }
        #记录返回失败的情况
        logger::error(app::get('wap')->_('支付单号：') . $form['out_trade_no'] . app::get('wap')->_('签名验证不通过，请确认！')."\n");
        logger::error(app::get('wap')->_('本地产生的加密串：') . $my_sign);
        logger::error(app::get('wap')->_('手机支付宝传递打过来的签名串：') . $form['sign']);
        $str_xml .= "<alipayform>";
        foreach ($form as $key=>$value)
        {
            $str_xml .= "<$key>" . $value . "</$key>";
        }
        $str_xml .= "</alipayform>";

        return false;
    }

    /*
    public function is_return_vaild($form,$key,$secu_id)
    {
        $_key      = $key;
        $sign_type = $secu_id;
        //此处为固定顺序，支付宝Notify返回消息通知比较特殊，这里不需要升序排列
        $notifyarray = array(
            "service"     => $form['service'],
            "v"           => $form['v'],
            "sec_id"      => $form['sec_id'],
            "notify_data" => $form['notify_data']
        );
        $mysign = $this->build_mysign($notifyarray,$_key,$sign_type);

        if($form['sign'] == $mysign){
            return true;
        }
        #记录返回失败的情况
        logger::error(app::get('wap')->_('支付单号：') . $form['out_trade_no'] . app::get('wap')->_('签名验证不通过，请确认！')."\n");
        logger::error(app::get('wap')->_('本地产生的加密串：') . $mysign);
        logger::error(app::get('wap')->_('手机支付宝传递打过来的签名串：') . $form['sign']);
        $str_xml .= "<alipayform>";
        foreach ($form as $key=>$value)
        {
            $str_xml .= "<$key>" . $value . "</$key>";
        }
        $str_xml .= "</alipayform>";

        return false;
    }
    */


    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_global_return_vaild($form,$key)
    {
        ksort($form);
        foreach($form as $k=>$v){
            if($k!='sign'&&$k!='sign_type'&& strpos($k, 'passby_')!==0){
                $signstr .= "&$k=$v";
            }
        }

        foreach($form as $k=>$v){
            if(strpos($k, 'passby_')===0 && $k!="passby_sign"){
                $signstr_passby .= "&$k=$v";
            }
        }


        $signstr = ltrim($signstr,"&");
        $signstr = $signstr.$key;

        $signstr_passby = ltrim($signstr_passby,"&");
        $signstr_passby = $signstr_passby.$key;

        $alipay_sign_valid = $form['sign'] == md5($signstr);
        $inner_sign_valid = $form['passby_sign'] == md5($signstr_passby);

        $msg = "[alipay_params]".var_export($form, true);

        if ($alipay_sign_valid && $inner_sign_valid)
        {
            logger::logtestkv('ecstore.ectools.payment.success',
                array(
                    "pay_method"=>"alipayglobal",
                    "trade_no" => $form['out_trade_no'],
                    "from"=>"notify_url",
                    "platform"=>"wap",
                    "remark"=>$msg
                ));

            return true;
        }

        #记录返回失败的情况

        logger::logtestkv('ecstore.ectools.payment.fail',
            array(
                "pay_method"=>"alipayglobal",
                "trade_no" => $form['out_trade_no'],
                "from"=>"notify_url",
                "platform"=>"wap",
                "remark"=>$msg
            )
            ,LOG_SYS_ERR);

        return false;
    }

    /**
     * 支付后返回后处理的事件的动作,用于国际支付
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function globalcallback(&$recv)
    {
        $ret['callback_source'] = 'server';
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', substr(__CLASS__, 0, strrpos(__CLASS__, '_'))."global");
        $mer_id = $mer_id == '' ? '2088002003028751' : $mer_id;
        $mer_key = $this->getConf('mer_key', substr(__CLASS__, 0, strrpos(__CLASS__, '_'))."global");
        $mer_key = $mer_key=='' ? 'afsvq2mqwc7j0i69uzvukqexrzd0jq6h' : $mer_key;

        if($this->is_global_return_vaild($recv,$mer_key)){
            $alipay['notify_type'] = $recv['notify_type'];
            $alipay['notify_id'] = $recv['notify_id'];
            $alipay['notify_time'] = $recv['notify_time'];
            $alipay['_input_charset'] = $recv['_input_chars'];


            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('支付宝');
            $ret['pay_account'] = app::get('ectools')->_('付款帐号');
            $ret['currency'] = "CNY";
            $ret['money'] = $recv['passby_rmb_fee'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['passby_rmb_fee'];
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time();
            $ret['pay_app_id'] = "alipayglobal";
            $ret['pay_type'] = 'online';
            $ret['memo'] = $recv['body'];
            $ret['settle_payment'] = $recv['total_fee'];
            $ret['settle_currency'] = $recv['currency'];

            switch($recv['trade_status']){
                case 'WAIT_BUYER_PAY':
                    echo "success";
                    $ret['status'] = 'ready';
                    break;
                case 'TRADE_PENDING':
                    echo "success";
                    $ret['status'] = 'progress';
                    break;
                case 'TRADE_FINISHED':
                    echo "success";
                    $ret['status'] = 'succ';
                    break;
                case 'TRADE_SUCCESS':
                    echo "success";
                    $ret['status'] = 'succ';
                    break;
                case 'TRADE_CLOSED':
                    echo "success";
                    $ret['status'] = 'failed';
                    break;

            }

        }else{
            echo 'fail';
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }

        return $ret;
    }


//↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓公共函数部分↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

    /**生成签名结果
     * $array要签名的数组
     * return 签名结果字符串
     */
    public function build_mysign($sort_array,$key,$sign_type = "MD5") {
        $prestr = $this->create_linkstring($sort_array);         //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $prestr.$key;                            //把拼接后的字符串再与安全校验码直接连接起来
        $mysgin = $this->sign($prestr,$sign_type);                //把最终的字符串签名，获得签名结果
        return $mysgin;
    }


    /**把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * $array 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function create_linkstring($array) {
        $arg  = "";
        while (list ($key, $val) = each ($array)) {
            $arg.=$key."=".$val."&";
        }
        $arg = substr($arg,0,count($arg)-2);             //去掉最后一个&字符
        return $arg;
    }


    /**对数组排序
     * $array 排序前的数组
     * return 排序后的数组
     */
    public function arg_sort($array) {
        ksort($array);
        reset($array);
        return $array;
    }


    /**签名字符串
     * $prestr 需要签名的字符串
     * $sign_type 签名类型，也就是sec_id
     * return 签名结果
     */
    public function sign($prestr,$sign_type) {
        $sign='';
        if($sign_type == 'MD5') {
            $sign = md5($prestr);
        }elseif($sign_type =='DSA') {
            //DSA 签名方法待后续开发
            die("DSA 签名方法待后续开发，请先使用MD5签名方式");
        }else {
            die("支付宝暂不支持".$sign_type."类型的签名方式");
        }
        return $sign;
    }

    /**
     * 通过节点路径返回字符串的某个节点值
     * $res_data——XML 格式字符串
     * 返回节点参数
     */
    function getDataForXML($res_data,$node)
    {
        $xml = simplexml_load_string($res_data);
        $result = $xml->xpath($node);

        while(list( , $node) = each($result))
        {
            return $node;
        }
    }

    /**除去数组中的空值和签名参数
     * $parameter 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function para_filter($parameter) {
        $para = array();
        while (list ($key, $val) = each ($parameter)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else    $para[$key] = $parameter[$key];
        }
        return $para;
    }

//↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑公共函数部分↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

}
