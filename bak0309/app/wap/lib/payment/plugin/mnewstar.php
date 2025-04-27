<?php

/**
 * 民生纽斯达支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/12/28
 * Time: 15:25
 */
final class wap_payment_plugin_mnewstar extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '民生纽斯达支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '民生纽斯达支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mnewstar';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mnewstar';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '民生纽斯达支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mnewstar_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mnewstar', 'callback');
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
        return '民生纽斯达支付配置信息';
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
            'app_key'=>array(
                'title'=>app::get('ectools')->_('app_key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'des_key'=>array(
                'title'=>app::get('ectools')->_('des_key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sign_key'=>array(
                'title'=>app::get('ectools')->_('sign_key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('退款URL'),
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
        return app::get('ectools')->_('民生纽斯达支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //构建支付参数
        $this->add_field('order_id',$this->des_encode($payment['payment_id']));//交易流水号(需DES加密)
        $this->add_field('rel_id',$payment['order_id']);//交易流水号(需DES加密)
        $this->add_field('trans_amt',$this->des_encode(number_format($payment['cur_money'],2,".","")*100));//交易金额，单位：分(需DES加密)
        $this->add_field('trans_date',date('Ymd'));//交易日期 YYYYMMDD
        $this->add_field('trans_time',date('His'));//交易时间 HHMMSS
        $this->add_field('mer_id',$this->getConf('mer_id', __CLASS__));//商户号(纽斯达为每个支付网关接入方分配15位商户号)
        $this->add_field('pay_code','0003');//支付活动(默认为0003)

        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));
        $this->add_field('openid',$this->des_encode($member_info['external_bn']));//用户微信号(需DES加密)  提醒对方换成微信的公众号OpenId
        $this->add_field('card_id',$this->des_encode('99999'));//固定值：99999
        $this->add_field('module','ALLINPAY');//固定值：ALLINPAY
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $this->add_field('cust_name',trim($order_info['ship_name']));//固定值：姓名
        $this->add_field('ph_no',trim($order_info['ship_mobile']));//固定值：手机号
        $this->add_field('order_addr',trim($order_info['ship_addr']));//固定值：收货地址

        //Common 参数
        $this->add_field('app_key',$this->getConf('app_key',__CLASS__));//OpenAPI分配给应用的AppKey(纽斯达提供)
        $this->add_field('method','pay.welfarebm.card.pay.add');//接口方法名称，如pay.card.cardinfo.get
        $this->add_field('timestamp',date('YmdHis'));//时间戳，格式为yyyyMMddHHmmss 例如：20110125010101
        $this->add_field('create_time',$payment['create_time']);//时间戳，订单创建时间
        $this->add_field('v','1.0');//版本号，目前默认值：1.0

        $this->add_field('sign_v','1');//签名版本号，目前默认值：1
        $this->add_field('format','JSON');//可选，响应数据格式。XML或JSON
        $this->add_field('sign',$this->genSign($this->fields));//请求数据签名结果，使用MD5、HMAC或DSA加密
        $query_param = http_build_query($this->fields);
        $url = $this->submit_url.'?'.$query_param;
        $log['order_id'] = $payment['payment_id'];
        $log['trans_amt'] = number_format($payment['cur_money'],2,".","")*100;
        $log['open_id'] = $member_info['external_bn'];
        \Neigou\Logger::General('pay.mnewstar',array('remark'=>'支付参数','request_data'=>$this->fields,'des_field'=>$log));
        header('Location:'.$url);
        exit;
    }



    /**
     * 参数DES加密
     * @param $str
     * @return string
     */
    public function des_encode($str){
        $block = mcrypt_get_block_size('des', 'ecb');
        $pad = $block - (strlen($str) % $block);
        $str .= str_repeat(chr($pad), $pad);
        return base64_encode(mcrypt_encrypt(MCRYPT_DES, $this->getConf('des_key',__CLASS__), $str, MCRYPT_MODE_ECB));
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        \Neigou\Logger::Debug('notify.mnewstar.req', array('remark' => 'recv_param', 'post_data'=>$recv));
        header('Content-Type:text/html; charset=utf-8');
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $recv['account'];
            $ret['bank'] = app::get('ectools')->_('纽斯达支付');
            $ret['pay_account'] = $recv['account'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['trade_no'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = strtotime($recv['notify_time']);
            $ret['pay_app_id'] = "mnewstar";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            \Neigou\Logger::General('ecstore.callback.mnewstar', array('remark' => 'trade_succ', 'data' => $ret));
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mnewstar',array('remark'=>'sign_err','data'=>$recv));
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

    /**
     * MD5签名
     * @param $postData
     * @return string
     */
    public function genSign($postData) {
        $sign_str = $this->getConf('sign_key',__CLASS__).$this->_create_link_string($postData,true,true).$this->getConf('sign_key',__CLASS__);
        \Neigou\Logger::General('paysign.mnewstar',array('linkS'=>$sign_str));
        return strtoupper(md5($sign_str));
    }






    public function gen_form(){
        return '';
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
        $param['salt'] = 'mnewstarxneigou';
        $str = $this->_create_link_string_callback($param,true,true);
        if($sign==md5($str)){
            return true;
        } else {
            \Neigou\Logger::General('callback.mnewstar.sign.err',array('linkS'=>$str,'sign'=>md5($str),'sign_req'=>$sign));
            return false;
        }
    }

    /**
     * 【新】将数组转换成String
     * @return string
     */
    public function _create_link_string_callback($para, $sort, $encode){
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
            $linkString .= $key  .'='. $value.'&' ;
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
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
            $linkString .= $key  . $value ;
        }

        // 去掉最后一个&字符
//        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
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