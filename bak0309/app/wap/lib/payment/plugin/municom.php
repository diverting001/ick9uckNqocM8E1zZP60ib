<?php

/**
 * 联通沃支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/05/09
 * Time: 16:07
 */
final class wap_payment_plugin_municom extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '联通沃支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '联通沃支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'municom';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'municom';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '联通沃支付';
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
//        echo 2;die;
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_municom_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_municom', 'callback');
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
//        $this->signtype = 'MD5';
//        $this->app_id = trim($this->getConf('appId', 'weixin_payment_plugin_wxpay')); // appid 公众号 ID
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '联通沃支付配置信息';
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
            'mer_id'=>array(
                'title'=>app::get('ectools')->_('商户号'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
//            'sign_key'=>array(
//                'title'=>app::get('ectools')->_('sign_key'),
//                'type'=>'string',
//                'validate_type' => 'required',
//            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('wap收银台支付接口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'query_url'=>array(
                'title'=>app::get('ectools')->_('单笔查询'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('退款URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pfx_path'=>array(
                'title'=>app::get('ectools')->_('pfx密钥文件路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pfx_pass'=>array(
                'title'=>app::get('ectools')->_('pfx密钥密码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'cer_path'=>array(
                'title'=>app::get('ectools')->_('cer证书文件路径'),
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
        return app::get('ectools')->_('联通沃支付配置');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
//        $this->add_field('version','2.2.4');//可空 接入收银台规范具体版本号 2.2.0之前的版本，该值为空； 2.2.0 之后（含2.2.0），根据具体接入规范版本号填写该值
        $this->add_field('merno',$this->getConf('mer_id',__CLASS__));//沃支付分配给商户的商户号
        $this->add_field('goodsname','内购网订单');
        $this->add_field('storeorderid',$payment['payment_id']);//对于同一个商户，订单日期与订单号共同确定唯一一笔订单。
        $this->add_field('orderbalance',strval( bcmul($payment['cur_money'], 100) ) );//单位:分移动收银台展示使用
        $this->add_field('paybalance',strval( bcmul($payment['cur_money'], 100) ));//单位:分实际支付的金额，移动收银台以此为准。
//        $this->add_field('merUserId','');// 是否是SSO登录手机号 在商家处付款的用户id
        $this->add_field('wostoretime',date('YmdHis'));//商户发起请求的时间        (YYYYmmddhh24miss格式)
        $this->add_field('respmode','1');//应答机制： 1．页面重定向应答+点对点 (多次应答)  2．页面重定向应答+点对点 (一次应答)  3．仅页面重定向应答
        $this->add_field('callbackurl',$this->callback_url);//页面重定向回调url，返回交易结果到此地址。
        $this->add_field('servercallurl',$this->notify_url);//点对点(后台通知)地址 （仅当应答机制为1、2时该字段必填）
        $this->add_field('storeindex',kernel::base_url(1) . '/m/member-new_orders-nopayed.html');//在收银台页面下均存在返回商户链接，该字段为指定的返回商户地址，展现给付款用户，建议返回到商户下单页面或商户首页
        $this->add_field('loginname',$payment['member_id']);//TODO 确认 在商家处付款的用户名
        $this->add_field('mp','1');//将请求的参数保持不变返回 (仅限浏览器回调 1,后台点对点回调默认返回2)
        $this->add_field('storename','内购网');//商户的名称
        $this->add_field('signtype','RSA_SHA256');//签名方式
        $this->add_field('trademode','0001');//直接交易
        $link_str = $this->_create_link_string($this->fields,true,false);
        $sign = $this->sign($link_str);
        $this->add_field('signmsg',$sign);//签名
        $str = $this->_create_link_string_post($this->fields,true,false);
        \Neigou\Logger::General('pay.municom.field',array('field'=>$this->fields));
        echo $this->get_html($str);exit;
    }

    //生成签名 联通沃支付证书处理 开头信息
    private function sign($data) {
        $cer_key = file_get_contents($this->getConf('pfx_path',__CLASS__)); //获取密钥内容
        openssl_pkcs12_read($cer_key, $certs, $this->getConf('pfx_pass',__CLASS__));
        //修改证书开头和结尾信息
        $arr = explode("\n",trim($certs['pkey']));
        array_shift($arr);
        array_pop($arr);
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .implode("\n",$arr)."\n-----END RSA PRIVATE KEY-----";
        //修改证书开头和结尾信息 END
        $private_id = openssl_pkey_get_private( $privateKey , $this->getConf('pfx_pass',__CLASS__));
        $signature = '';
        openssl_sign($data, $signature, $private_id, 'SHA256' );
        return base64_encode($signature);
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        \Neigou\Logger::General('ecstore.callback.mnicom.req', array('remark' => 'recv_param', 'post_data'=>$recv));
        //替换请求串中的$为 &
        $tmp_str = str_replace('$','&',$recv['param']);
        parse_str($tmp_str,$arr);
        //获取sign信息
        $sign = $arr['signmsg'];
        //去掉不参与签名的信息
        unset($arr['hmac']);
        unset($arr['signmsg']);
        //创建签名串
        $data = $this->_create_link_string($arr,true,false);
        $pub_key = file_get_contents($this->getConf('cer_path',__CLASS__));
        $key = openssl_get_publickey($pub_key);
        $sign = str_replace(' ','+',$sign);
        $res = (bool)openssl_verify($data,base64_decode($sign),$key,'SHA256');
        if($res){
            //签名验证通过
            $ret['payment_id'] = $arr['orderid'];
            $ret['account'] = $arr['payfloodid'];
            $ret['bank'] = app::get('ectools')->_('联通沃支付');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $arr['paybalance']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $arr['paybalance']/100;
            $ret['trade_no'] = $arr['payfloodid'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = strtotime($arr['resptime']);
            $ret['pay_app_id'] = "municom";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            \Neigou\Logger::General('ecstore.callback.mnicom', array('remark' => 'trade_succ', 'data' => $ret));
        } else {
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mnicom',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    public function gen_form(){
        return '';
    }

    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function get_html($value) {
        $encodeType =  'utf-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">

eot;
            $html .= "    <input type=\"hidden\" name=\"param\" id=\"param\" value=\"{$value}\" />\n";
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }

    /**
     * 【新】将数组转换成String
     * @return string
     */
    public function _create_link_string_post($para, $sort, $encode){
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
            if(!empty($value)){
                $linkString .= $key  .'='. $value.'$' ;
            }

        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        $linkString = $this->getConf('mer_id',__CLASS__).$linkString;
        \Neigou\Logger::General('pay.municom.post_param',array('link_str'=>$linkString));
        //添加key
//        $linkString .= 'key='.$this->getConf('sign_key',__CLASS__);
        return $linkString;
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
            if(!empty($value)){
                $linkString .= $key  .'='. $value.'|' ;
            }

        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        \Neigou\Logger::General('pay.municom.sign_param',array('link_str'=>$linkString));
        //添加key
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