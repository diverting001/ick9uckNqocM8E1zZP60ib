<?php

/**
 * 知心荟 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2020/04/09
 * Time: 16:18
 */
final class wap_payment_plugin_mwanxin extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '知心荟 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '知心荟 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mwanxin';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mwanxin';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '知心荟 支付';
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
     * 是否自动提交表单 1==>是
     * @var int
     */
    public $auto_submit = 1;

    /**
     * 自动提交表单 channel
     *
     * @var array
     */
    public $auto_submit_channel = array('wanxin');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwanxin_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwanxin', 'callback');
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
        return '知心荟 支付配置信息';
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
                'title'=>app::get('ectools')->_('商户号 知心荟提供'),
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
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('退款提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_update_url' => array(
                'title' => app::get('ectools')->_('订单变更同步URL'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'order_after_sale_update_url' => array(
                'title' => app::get('ectools')->_('售后变更通知接口'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'app_id'=>array(
                'title'=>app::get('ectools')->_('APP ID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_secret'=>array(
                'title'=>app::get('ectools')->_('APP SECRET'),
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
        return app::get('ectools')->_('知心荟 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //判断是否是礼包订单
        $order_type = 1;

        //third company info
        $company_id = kernel::single("b2c_member_company")->get_cur_company();
        $_third_company = app::get('b2c') -> model('third_company');
        $channel_info = $_third_company -> getChannelsByCompanyId($company_id);
        $external_company_bn = $channel_info[0]['external_bn'];
        //如果外部用户BN 以 zxh-labor-xxxxx 开头 则 订单类型为2
        if(strpos($external_company_bn,'zxh-labor-') === 0){
            $order_type = 2;
        }
        $order_items = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $this->add_field('is_mvp',0);
        //如果订单是MVP订单 则 类型为3 需要先调用JS方法
        if($order_items['system_code'] == 'mvp') {
            $order_type = 3;
            $this->add_field('is_mvp',1);
            $this->add_field('special_id',$payment['package_key']);
        }
        $this->add_field('order_sn',$payment['order_id']);
        $this->add_field('pay_sn',$payment['payment_id']);
        $this->add_field('order_source',$order_type);
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
        \Neigou\Logger::General('ecstore.mwanxin.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
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
        $field = $this->fields;
        $success_url = app::get('wap')->router()->gen_url(array('app'=> 'b2c','ctl'=>'wap_paycenter2','act'=>'result_wait','full'=>1,'arg0'=>$field['order_sn'],'arg1'=>'true'));
        $html = <<<eot
<!DOCTYPE html>
<html
	class="um landscape min-width-240px min-width-320px min-width-480px min-width-768px min-width-1024px">
<head>
<title>支付跳转中</title>
<meta charset="utf-8">
<meta name="viewport"
	content="target-densitydpi=device-dpi, width=device-width, initial-scale=1, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
</head>
<style>
</style>
<body class="um-vp bc_c9a" ontouchstart>
支付跳转中……
</body>
<script>
        function callPayWindow(order_sn,pay_sn ,order_source) {
            try {
                if(/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
                    //ios收银台调用
                    window.webkit.messageHandlers.callPayWindow.postMessage({order_sn:order_sn,pay_sn:pay_sn ,order_source:order_source});
                }else if(/(Android)/i.test(navigator.userAgent)){
                    //android收银台调用
                    jsObject.callPayWindow(order_sn,pay_sn ,order_source)
                }else if(null == localStorage.getItem("device_type")){
                    throw '设备类型为空'
                }else{
                    throw 'unknown device type'
                }
    
            }catch (err) {
                document.getElementById('content').innerText='调用报错:'+err
            }
        }
        
        function orderBind(order_sn) {
            try {
                if(/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
                    //ios收银台调用
                    window.webkit.messageHandlers.orderBind.postMessage({order_sn:order_sn});
                }else if(/(Android)/i.test(navigator.userAgent)){
                    //android收银台调用
                    jsObject.orderBind(order_sn)
                }else if(null == localStorage.getItem("device_type")){
                    throw '设备类型为空'
                }else{
                    throw 'unknown device type'
                }
    
            }catch (err) {
                alert('调用报错:'+err)
            }
        }
        
        window.onload = function(){
            var is_mvp = "{$field['is_mvp']}";
            if(is_mvp>0){
                //bind order
                orderBind("{$field['order_sn']}");
                callPayWindow("{$field['order_sn']}","{$field["pay_sn"]}","{$field["order_source"]}","{$field["special_id"]}");
            } else {
                callPayWindow("{$field['order_sn']}","{$field["pay_sn"]}","{$field["order_source"]}");
            }
            
            window.location.href='{$success_url}';
        };
</script>
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
            $ret['bank'] = app::get('ectools')->_('知心荟支付');
            $ret['pay_account'] = $recv['account'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = $recv['notify_time']? $recv['notify_time'] : time();
            $ret['pay_app_id'] = "mwanxin";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mwanxin',array('remark'=>'sign_err','data'=>$recv));
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