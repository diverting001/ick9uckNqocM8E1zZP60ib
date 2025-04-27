<?php

/**
 * 发福利支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/12/28
 * Time: 15:25
 */
final class wap_payment_plugin_mfafuli extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '发福利支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '发福利支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mfafuli';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mfafuli';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '发福利支付';
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
    public $auto_submit_channel = array('fafuli');

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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mfafuli_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mfafuli', 'callback');
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
        return '发福利支付配置信息';
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
            'app_name' => array(
                'title' => app::get('ectools')->_('AppName'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'app_key' => array(
                'title' => app::get('ectools')->_('AppKey'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'app_security' => array(
                'title' => app::get('ectools')->_('AppSecurity'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'merchant_account_id' => array(
                'title' => app::get('ectools')->_('商户授权账户ID'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'merchant_account_id_mt' => array(
                'title' => app::get('ectools')->_('商户授权账户ID_美团外卖'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'merchant_account_id_mt_108' => array(
                'title' => app::get('ectools')->_('商户授权账户ID_美团外卖_专票108'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'merchant_account_id_dp_canyin' => array(
                'title' => app::get('ectools')->_('商户授权账户ID_点评餐饮'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'merchant_account_id_mtcar' => array(
                'title' => app::get('ectools')->_('商户授权账户ID_美团打车'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'org_no' => array(
                'title' => app::get('ectools')->_('机构号'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'domain' => array(
                'title' => app::get('ectools')->_('接口地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'grant_type' => array(
                'title' => app::get('ectools')->_('grant_type'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'username' => array(
                'title' => app::get('ectools')->_('username'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'password' => array(
                'title' => app::get('ectools')->_('password'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'scope' => array(
                'title' => app::get('ectools')->_('scope'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'client_id' => array(
                'title' => app::get('ectools')->_('client_id'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'client_secret' => array(
                'title' => app::get('ectools')->_('client_secret'),
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
        return app::get('ectools')->_('发福利支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
//        if (!kernel::single('base_component_request')->is_browser_tag('weixin')) {
//            echo '移动端只支持微信';
//            die;
//        }
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $merchant_account_id = $this->_config['merchant_account_id'];
        if ($order_info['extend_info_code'] == 'mt_waimai') {
            $merchant_account_id = $this->_config['merchant_account_id_mt'];
        } elseif ($order_info['extend_info_code'] == 'mt_car') {
            $merchant_account_id = $this->_config['merchant_account_id_mtcar'];
        } elseif ($order_info['extend_info_code'] == 'dp_canyin') {
            $merchant_account_id = $this->_config['merchant_account_id_dp_canyin'];
        }
        $fafuliorder = kernel::single('b2c_service_fafuliorder');
        $supplier_bn = $fafuliorder->get_supplier_bn($order_info['items'][0]);
        if ($supplier_bn == 'MTWM108') {
            $merchant_account_id = $this->_config['merchant_account_id_mt_108'];
        }
        if ($order_info['create_time'] + 900 <= time()) {
            header('Content-Type:text/html; charset=utf-8');
            echo app::get('b2c')->_('支付超时！');
            exit;
        }
        // 预下单，生成三方订单号
        $pre_data = $this->pre_order(array(
            'orgNo' => $this->_config['org_no'],
            'merchantAccountId' => $merchant_account_id,
        ));

        if (empty($pre_data)) {
            header('Content-Type:text/html; charset=utf-8');
            echo '预下单失败';
            die;
        }

        //保存三方订单号
        $set['payment_id'] = $payment['payment_id'];
        $set['pay_account'] = $pre_data['orderNo'];
        app::get('ectools')->model('payments')->save($set);

        // 订单商品
        $orderTitle = '';
        foreach ($order_info['items'] as $k => $v) {
            $orderTitle .= $v['name'] . ';';
        }
        $orderTitle = trim($orderTitle, ';');
        $orderTitle = str_replace(array("'", '-', '&'), '', $orderTitle);
        $orderTitle = mb_substr($orderTitle, 0, 35, 'UTF-8');
        // 第三方用户
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow('*', array('internal_id' => $member_id, 'source' => 1));

        $is_weixin = kernel::single('base_component_request')->is_browser_tag('weixin');

        $is_dingding    = kernel::single('base_component_request')->is_dingding_porgram();

        // 正式下单，支付
        $pay_data = $this->pay_order(array(
            'orderScene' => $is_weixin ? '10' : ($is_dingding ? '40' : '50'), // 下单场景，PC:00;WECHAT:10;50
            'orderTitle' => $orderTitle, // 订单标题
            'orderNo' => $pre_data['orderNo'], // 预下单的订单号
            'orderSku' => json_encode(array(
                'productId' => '1'
            )), // 订单商品信息
            'pointClassCode' => 51,
            'orderPrice' => number_format($payment['cur_money'], 2, '.', ''), // 订单金额
            'orgNo' => $this->_config['org_no'], // 机构号
            'merchantAccountId' => $merchant_account_id, //商户授权账户ID
            'merchantOrderNo' => $payment['payment_id'], //商户订单号
            'userToken' => $member_info['external_bn'],
        ));
        if (empty($pay_data)) {
            header('Content-Type:text/html; charset=utf-8');
            echo '正式下单失败';
            die;
        }

        header('Location: ' . $pay_data);
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        //获取订单编号
        $order_info = app::get('ectools')->model('order_bills')->getRow('rel_id', array('bill_id' => $recv['merchant_order_no']));
        if (kernel::single('base_mobiledetect')->isMobile()) {
            $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_wait', 'full' => 1, 'arg0' => $order_info['rel_id'], 'arg1' => 'true'));
        } else {
            $url = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_paycenter2', 'act' => 'result_pay', 'full' => 1, 'arg0' => $recv['merchant_order_no']));
        }
        header('Location: ' . $url);
        die;
        //跳转到订单详情页面

//        $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'new_orderdetail', 'arg0' => $order_info['rel_id']));
//        \Neigou\Logger::General('pay.mfafuli', array('action' => 'pay_fail', 'result' => $order_info));
//        header('Location: ' . $url);
//        die;
    }

    /**
     * 3.13 退款订单状态变更通知(设计规约)
     * @return null
     */
    public function refund_status_change_notice(&$recv)
    {
        header('content-type:application/json;charset=utf-8');
        echo json_encode(array(
            'return_code' => '00',
            'return_msg' => ''
        ));
        \Neigou\Logger::General('mfafuli.refund_status_change_notice', array('get' => $_GET, 'post' => $_POST, 'recv' => $recv, 'raw' => file_get_contents('php://input')));
        die;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad()
    {
        return true;
    }

    public function gen_form()
    {
        return '';
    }

    public function gen_app_page($order_id, $data)
    {
        //判断是否是APP
        if (isset($_SERVER['HTTP_USER_AGENT']) && (stripos(strtolower($_SERVER['HTTP_USER_AGENT']), 'appcan') == false)) {
            header('Location:' . $this->getConf('wx_url', __CLASS__) . '?orderSubNO=' . $order_id . '&zj=' . $data['wx_price']);
            \Neigou\Logger::General('pay.mfafuli.wx', array('remark' => '跳转支付', 'subNO' => $order_id));
            exit();
        }
        $html = <<<eot
<!DOCTYPE html>
<html
	class="um landscape min-width-240px min-width-320px min-width-480px min-width-768px min-width-1024px">
<head>
<title>发福利支付</title>
<meta charset="utf-8">
<meta name="viewport"
	content="target-densitydpi=device-dpi, width=device-width, initial-scale=1, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
</head>
<style>
</style>
<body class="um-vp bc_c9a" ontouchstart>
支付跳转中……



</body>
<script src="/app/wap/statics/fafuli/jquery-1.7.2.min.js"></script>
<script src="/app/wap/statics/fafuli/main.js"></script>
<script src="/app/wap/statics/fafuli/appcan.js"></script>
<script src="/app/wap/statics/fafuli/appcan.control.js"></script>
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
    public function get_html()
    {
        $encodeType = 'utf-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">

eot;
        foreach ($this->fields as $key => $value) {
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
     * 预下单：获取订单号
     * @return string
     */
    private function pre_order($pars)
    {
        return $this->request('pointOrder/order/pre_order', $pars);
    }

    /**
     * 正式下单
     * @return string
     */
    private function pay_order($pars)
    {
        return $this->request('pointOrder/order/pay_order', $pars);
    }

    /**
     * 获取token
     *
     * @return  string
     */
    private function token($refresh = 0)
    {
        $token = '';
        $_redis = kernel::single('base_sharedkvstore');
        $_redis->fetch('', 'fafuli_token', $token);
        if ($token && $refresh === 0) {
            return $token;
        }
        $curl = new \Neigou\Curl();
        $result = $curl->Post($this->_config['domain'] . 'oauth/token', array(
            'grant_type' => $this->_config['grant_type'],
            'username' => $this->_config['username'],
            'password' => $this->_config['password'],
            'scope' => $this->_config['scope'],
            'client_id' => $this->_config['client_id'],
            'client_secret' => $this->_config['client_secret'],
        ));
        $result = json_decode($result, true);
        $expires_in = 3600 * 24 * 3;
        $_redis->store('', 'fafuli_token', $result['access_token'], $expires_in);
        return $result['access_token'];
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
        $url = $this->_config['domain'] . $path . '?access_token=' . $this->token();
        $params['sign'] = $this->sign($params);
        $curl = new \Neigou\Curl();
        $curl->SetHeader('Content-Type', 'application/json');
        $result = $curl->$method($url, json_encode($params));
        \Neigou\Logger::General('pay.fafuli.api', array('path' => $url, 'pars' => $params, 'res' => $result));
        $result = json_decode($result, true);
        if ($path == 'oauth/token') {
            return $result;
        }
        if ($result['return_code'] == '00') {
            return $result['data'];
        } else if ($result['error'] == 'invalid_token') {
            $url = $this->_config['domain'] . $path . '?access_token=' . $this->token(1);
            $params['sign'] = $this->sign($params);
            $result = $curl->$method($url, json_encode($params));
            $result = json_decode($result, true);
            if ($result['return_code'] == '00') {
                return $result['data'];
            }
        } else {
            \Neigou\Logger::General('pay.fafuli.api.err', array('path' => $url, 'pars' => $params, 'res' => $result));
        }
        return $return;
    }

    /**
     * <生成签名方法>
     * <功能详细描述>
     * @param $pars
     * @return string
     */
    private function sign($pars)
    {
        //按照文档顺序顺序排列好
        ksort($pars);
        $sign_str = urldecode(http_build_query($pars));
        $sign = sha1($sign_str . $this->_config['app_key'], false);
        \Neigou\Logger::General('paysign.mfafuli', array('linkS' => $sign_str));
        return $sign;
    }
}