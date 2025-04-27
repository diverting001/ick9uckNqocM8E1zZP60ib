<?php

/**
 * 渠道 miha 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2019/12/11
 * Time: 14:08
 */
final class wap_payment_plugin_mmiha extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '渠道 miha 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '渠道 miha 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mmiha';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mmiha';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '渠道 miha 支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mmiha_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mmiha', 'callback');
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
        return '渠道 miha 支付配置信息';
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
                'title'=>app::get('ectools')->_('商户号 miha提供'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'salt'=>array(
                'title'=>app::get('ectools')->_('签名salt'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_query_url'=>array(
                'title'=>app::get('ectools')->_('refund_query_url'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('refund_url'),
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
        return app::get('ectools')->_('渠道 miha 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //third user info
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));

        $this->add_field('out_trade_no',$payment['payment_id']);
        $this->add_field('order_id',$payment['order_id']);
        $this->add_field('account_no',$member_info['external_bn']);
        $this->add_field('total_fee',number_format($payment['cur_money'],2,".","")*100);
        $this->add_field('notify_url',$this->notify_url);
        $this->add_field('callback_url',$this->callback_url);
        $this->add_field('mer_id',$this->getConf('mer_id',__CLASS__));
        $this->add_field('sub_time',$payment['create_time']);
        $this->add_field('expir_time',$payment['create_time']+2380);
        $this->add_field('sign',$this->genSign($this->fields));
        echo $this->get_html();
        die;
    }

    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $data['salt'] = $this->getConf('salt',__CLASS__);
        $linkStr = $this->_create_link_string($data,true,true);
        \Neigou\Logger::General('ecstore.mmiha.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
        return md5($linkStr);
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
            if($key!='salt'){
                $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
            }
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
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        if($this->is_return_vaild($recv)){
            //获取订单详细信息
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $recv['account'];
            $ret['bank'] = app::get('ectools')->_('采贝支付');
            $ret['pay_account'] = $recv['account'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = $recv['notify_time']? $recv['notify_time'] : time();
            $ret['pay_app_id'] = "mmiha";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mmiha',array('remark'=>'sign_err','data'=>$recv));
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
     * 检验返回数据合法性
     * @param $param
     * @access private
     * @return boolean
     */
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $req_sign = $this->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
}