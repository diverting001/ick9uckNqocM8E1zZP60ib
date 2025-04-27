<?php

/**
 * 招行一网通支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/12/28
 * Time: 15:25
 */
final class wap_payment_plugin_mywt extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '一网通支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '一网通支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mywt';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mywt';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '一网通支付';
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
    public $supportCurrency = array('CNY' => '01');

    private $_config = array();

    /**
     * 自动提交表单 channel
     *
     * @var array
     */
    public $auto_submit_channel = array('ywt');

    /**
     * 是否自动提交表单 1==>是
     * @var int
     */
    public $auto_submit = 1;

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mywt_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mywt', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
        $this->submit_url = $this->getConf('submit_url', __CLASS__);
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';

        $config = app::get('ectools')->getConf(__CLASS__);
        $config = unserialize($config);
        $this->_config = $config['setting'];
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro()
    {
        return '一网通支付配置信息';
    }

    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting()
    {
        return array(
            'pay_name' => array(
                'title' => app::get('ectools')->_('支付方式名称'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'merchantNo' => array(
                'title' => app::get('ectools')->_('商户号，6位数字'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'branchNo' => array(
                'title' => app::get('ectools')->_('商户分行号，4位数字'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            //密钥管理：OnkOpGdrS3ru6txp   商户API密钥是按照指定规则对您的参数进行签名,服务器收到您的请求时会进行签名验证,既可以认定您的身份也可以防止他人恶意篡改请求数据.
            'sMerchantKey' => array(
                'title' => app::get('ectools')->_('商户API密钥'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'domain' => array(
                'title' => app::get('ectools')->_('接口网址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'api_public_key' => array(
                'title' => app::get('ectools')->_('查询招行公钥API'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'order_by' => array(
                'title' => app::get('ectools')->_('排序'),
                'type' => 'string',
                'label' => app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'pay_type' => array(
                'title' => app::get('wap')->_('支付类型(是否在线支付)'),
                'type' => 'radio',
                'options' => array('false' => app::get('wap')->_('否'), 'true' => app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'status' => array(
                'title' => app::get('ectools')->_('是否开启此支付方式'),
                'type' => 'radio',
                'options' => array('false' => app::get('ectools')->_('否'), 'true' => app::get('ectools')->_('是')),
                'name' => 'status',
            ),
        );
    }

    /**
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro()
    {
        return app::get('ectools')->_('一网通支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);

        if ($order_info['point_amount'] == 0) {
            /* @var b2c_service_xinfutongorder $lib_xinfutong */
            $lib_xinfutong = kernel::single('b2c_service_xinfutongorder');
            if ($lib_xinfutong->lockPoint($order_info) !== true) {
                echo '创建支付单失败：锁定积分失败';
                die;
            }
        }

        $pars = array(
            'version' => '1.0',
            'charset' => 'UTF-8',
            'signType' => 'SHA-256',
            'reqData' => array(
                'dateTime' => date('YmdHis', time()),
                'branchNo' => $this->_config['branchNo'],
                'merchantNo' => $this->_config['merchantNo'],
                'date' => date('Ymd', $payment['create_time']),
                'orderNo' => $payment['payment_id'],
                'amount' => number_format($payment['cur_money'], 2, '.', ''),
                'expireTimeSpan' => 40 - intval((time() - $order_info['create_time']) / 60),
                'payNoticeUrl' => $this->notify_url,
                'payNoticePara' => 'neigou',
                'returnUrl' => $this->callback_url . '?order_id=' . $payment['order_id'] . '&payment_id=' . $payment['payment_id'],
            )
        );
        $pars['sign'] = $this->sign($pars['reqData']);
        $html = $this->get_html();
        $html = str_replace('_replace_', json_encode($pars), $html);
        echo $html;
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_wait', 'full' => 1, 'arg0' => $recv['order_id'], 'arg1' => 'true'));
        header('Location: ' . $url);
        //获取订单编号
//        $order_info = app::get('ectools')->model('order_bills')->getRow('rel_id', array('bill_id' => $recv['merchant_order_no']));
//        if (kernel::single('base_mobiledetect')->isMobile()) {
//            $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_wait', 'full' => 1, 'arg0' => $order_info['rel_id'], 'arg1' => 'true'));
//        } else {
//            $url = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_paycenter2', 'act' => 'result_pay', 'full' => 1, 'arg0' => $recv['merchant_order_no']));
//        }
//        header('Location: ' . $url);
//        die;
        //跳转到订单详情页面

//        $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'new_orderdetail', 'arg0' => $order_info['rel_id']));
//        \Neigou\Logger::General('pay.mfafuli', array('action' => 'pay_fail', 'result' => $order_info));
//        header('Location: ' . $url);
//        die;
    }

    /**
     * 发送请求
     *
     * @param   $path       string      地址
     * @param   $params     array       参数
     * @param   $method     string       请求方式
     * @return  array
     */
    private function request($path, $params = array(), $method = 'Post')
    {
        $return = array();
        $url = $this->_config['domain'] . $path;
        $params['sign'] = $this->sign($params['jsonRequestData']['reqData']);
        $curl = new \Neigou\Curl();
        $curl->SetHeader('Content-Type', 'application/json');
        $result = $curl->$method($url, json_encode($params));
        \Neigou\Logger::General('pay.mywt.api', array('path' => $url, 'pars' => $params, 'res' => $result));
//        $result = json_decode($result, true);
//        if ($result['return_code'] == '00') {
//            return $result['data'];
//        } else {
//            \Neigou\Logger::General('pay.mywt.api.err', array('path' => $url, 'pars' => $params, 'res' => $result));
//        }
        return $result;
    }

    /**
     * <生成签名方法>
     * <功能详细描述>
     * @param $pars
     * @return string
     */
    public function sign($pars)
    {
        //按照文档顺序顺序排列好
        ksort($pars);
        $sign_str = urldecode(http_build_query($pars));
        $sign_str .= '&' . $this->_config['sMerchantKey'];
        //SHA-256签名
        $baSrc = mb_convert_encoding($sign_str, 'UTF-8');
        $sign = hash('sha256', $baSrc);
        \Neigou\Logger::General('paysign.mywt', array('str' => $sign_str, 'sign' => $sign,));
        return $sign;
    }

    public function get_html()
    {
        $html = <<<eot
<!DOCTYPE html>
<html class="um landscape min-width-240px min-width-320px min-width-480px min-width-768px min-width-1024px">
<head>
    <title>一网通支付</title>
    <meta charset="utf-8">
    <meta name="viewport" content="target-densitydpi=device-dpi, width=device-width, initial-scale=1, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
	<script src="/app/wap/statics/js/cmblapi.js"></script>
    <script src="/app/wap/statics/js/cmbnetpayapi.js"></script>
</head>
<body class="um-vp bc_c9a" ontouchstart>
支付跳转中……
</body>
<script>
    var RequestData = '_replace_';
    var param = {
        showType:'popup',
        jsonRequestData:RequestData,
        popupParam:{
            payType:"default",
            ReturnMethod:"MerchantView"
        }
    } 
    cmbnetpay(JSON.stringify(param));
</script>
</html>
eot;
        return $html;
    }

    public function is_fields_valiad()
    {
        return true;
    }

    public function gen_form()
    {
        return '';
    }
}