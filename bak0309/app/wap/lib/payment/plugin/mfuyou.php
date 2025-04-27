<?php

/**
 * 福游网支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/11/6
 * Time: 15:39
 */
final class wap_payment_plugin_mfuyou extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '福优支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '福优支付接口';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mfuyou';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mfuyou';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '福优支付';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.1';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mfuyou_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mfuyou', 'callback');
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
        return '福游网支付配置信息';
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
            'client_secret'=>array(
                'title'=>app::get('ectools')->_('client_secret同登录使用的secret'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('支付请求API'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
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
        return app::get('ectools')->_('福优支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        /*new 12.7 增加多渠道判断*/
        $company_id = kernel::single("b2c_member_company")->get_cur_company();
        $_third_company = app::get('b2c') -> model('third_company');
        $channel_info = $_third_company -> getChannelByCompanyId($company_id);
        $this->add_field('channel',$channel_info['external_bn']);

        //获取福优 用户UUID
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));
        $this->add_field('memberCode',$member_info['external_bn']);//用户账号
//        $this->add_field('memberCode','13427969179');//TODO TEST 数据 用户账号
        $this->add_field('orderNo',$payment['payment_id']);//商户订单号，8-32位数字字母，不能含“-”或“_”，此处默认取demo演示页面传递的参数，可以自行定制规则
        $this->add_field('transferScore',number_format($payment['cur_money'],2,".",""));//交易金额，单位元
        $this->add_field('notify_url',$this->notify_url);//手续费
        $this->add_field('return_url',$this->callback_url);//手续费
        $this->add_field('time',time());//Common 服务器时间戳 误差120s之内
        $fields = $this->fields;
        $str = $this->_create_link_string((array)$fields,true,false);
        $str = $str.$this->getConf('client_secret', __CLASS__);
        $this->add_field('sign',md5($str));
        \Neigou\Logger::General('pay.mfuyou', array('action' => 'req_param', 'data' => $this->fields,'link_str'=>$str));
        echo $this->get_html();
        exit();
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $mer_id = $mer_id == '' ? '01510084' : $mer_id;
        if($this->is_return_vaild($recv)){
            if($recv['trade_status']==1){
                $ret['payment_id'] = $recv['orderNo'];
                $ret['account'] = $mer_id;
                $ret['bank'] = app::get('ectools')->_('福优支付');
                $ret['pay_account'] = $recv['customNo'];
                $ret['currency'] = 'CNY';
                $ret['money'] = $recv['settleAmt'];
                $ret['paycost'] = '0.000';
                $ret['cur_money'] = $recv['settleAmt'];
                $ret['trade_no'] = $recv['orderNoFlx'];//queryId 交易流水号 traceNo 系统跟踪号
                $ret['t_payed'] = $recv['notify_time'];
                $ret['pay_app_id'] = "mfuyou";
                $ret['pay_type'] = 'online';
                $ret['status'] = 'succ';
            } else {
                \Neigou\Logger::General('pay.mfuyou', array('action' => 'trade_status_err', 'data' => $recv));
                $ret['status'] = 'invalid';
            }

        }else{
            \Neigou\Logger::General('pay.mfuyou', array('action' => 'sign_err', 'data' => $recv));
            $ret['status'] = 'invalid';
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
     * @param $params
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params) {
        $signature_str = $params ['sign'];
        unset ( $params ['sign'] );
        $str = $this->_create_link_string($params,true,false);
        $str = $str.$this->getConf('client_secret', __CLASS__);
        $sign = md5($str);
        if ($sign==$signature_str) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 将数组转换成String
     * @param $para array 参数
     * @param $sort bool 是否排序
     * @param $encode string 是否urlencode
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