<?php

/**
 * 兜礼支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/11/21
 * Time: 15:39
 */
final class wap_payment_plugin_mdouli2 extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '兜礼支付2';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '兜礼支付2';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mdouli2';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mdouli2';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '兜礼支付2';
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

    /**
     * 自动提交表单 channel
     *
     * @var array
     */
    public $auto_submit_channel = array('douli');

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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdouli2_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdouli2', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
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
        return '兜礼支付2配置信息';
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
            'businessId' => array(
                'title' => app::get('ectools')->_('商户编号'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'storesId' => array(
                'title' => app::get('ectools')->_('商家门店或设备编号(如A001)'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'domain' => array(
                'title' => app::get('ectools')->_('域名'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'company_id' => array(
                'title' => app::get('ectools')->_('公司ID 对账使用'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'ssl_cert' => array(
                'title' => app::get('ectools')->_('ssl证书路径'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'ssl_key' => array(
                'title' => app::get('ectools')->_('ssl私钥路径'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'ssl_pass' => array(
                'title' => app::get('ectools')->_('ssl私钥密码'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'daily_prefix' => array(
                'title' => app::get('ectools')->_('日对账单前缀'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'month_prefix' => array(
                'title' => app::get('ectools')->_('月对账单前缀'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'file_path' => array(
                'title' => app::get('ectools')->_('账单存储路径'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'ftp_server' => array(
                'title' => app::get('ectools')->_('FTP 服务器地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'ftp_port' => array(
                'title' => app::get('ectools')->_('FTP 服务器端口'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'ftp_user' => array(
                'title' => app::get('ectools')->_('FTP 用户名'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'ftp_pwd' => array(
                'title' => app::get('ectools')->_('FTP 登录密码'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'sync_shipped_url' => array(
                'title' => app::get('ectools')->_('物流信息同步接口'),
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
        return app::get('ectools')->_('福优支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        \Neigou\Logger::General('pay.douli2', array('action' => 'payment_douli', 'result' => $payment));
        //third member info
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id" => $member_id, 'source' => 1));

        $orderDetail = array();
        $name_str = '';
        $order_price = 0;
        foreach ($order_info['items'] as $item) {
            $item_price = $item['mktprice'] * $item['nums'];
            $orderDetail[] = array(
                'code' => $item['bn'],
                'goods' => $item['name'],
                'number' => $item['nums'],
                'amount' => $item['amount'],
                'category' => 'ng001',
                'costPrice' => $item['cost'],
                'price' => $item_price,
                'tax' => $item['cost_tax'],
            );
            $name_str .= $item['name'] . ';';
            $order_price += $item_price;
        }

        if ($order_info['cost_freight'] > 0) {
            $orderDetail[] = array(
                'goods' => '运费',
                'number' => 1,
                'category' => 'ng001',
                'amount' => $order_info['cost_freight'],
                'price' => $order_info['cost_freight'],
                'costPrice' => $order_info['cost_freight'],
                'tax' => 0,
            );
            $order_price += $order_info['cost_freight'];
        }

        $param = array(
            'businessId' => $this->_config['businessId'],
            'storesId' => $this->_config['storesId'],
            'cardNumber' => empty($member_info['external_bn']) ? '' : $member_info['external_bn'],
//            'cardNumber' => '18501603537',
            'merchantOrderNo' => $payment['payment_id'],
            'price' => number_format($order_price, 2, '.', ''),
            'amount' => number_format($payment['cur_money'], 2, '.', ''),
            'tradeType' => 'DOOOLY_JS',
            'body' => mb_substr($name_str, 0, 25, 'UTF-8'),
            'isSource' => '2',
            'notifyUrl' => $this->notify_url,
            'orderDate' => date('Y-m-d H:i:s', $order_info['create_time']),
            'orderDetail' => json_encode($orderDetail),
            'clientIp' => $this->get_client_ip(),
            'nonceStr' => date('Y-m-d H:i:s', time()),
            'expireTime' => date('Y-m-d H:i:s', $order_info['create_time'] + 2700),
            'redirectUrl' => $this->callback_url . '?merchantOrderNo=' . $payment['payment_id'],
        );
        $ret = $this->request('payment/mchpay/unifiedorder', $param);
        $param_getTradeInfo = array(
            'businessId' => $this->_config['businessId'],
            'merchantOrderNo' => $payment['payment_id'],
            'payId' => $ret['payId'],
        );

        //保存三方订单号
        $set['payment_id'] = $payment['payment_id'];
        $set['pay_account'] = $ret['payId'];
        app::get('ectools')->model('payments')->save($set);

        $ret = $this->request('payment/pay/getTradeInfo', $param_getTradeInfo);
        header('Location: ' . $ret['url']);
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        //获取订单编号
        $order_info = app::get('ectools')->model('order_bills')->getRow('rel_id', array('bill_id' => $recv['merchantOrderNo']));
        if (kernel::single('base_mobiledetect')->isMobile()) {
            $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_wait', 'full' => 1, 'arg0' => $order_info['rel_id'], 'arg1' => 'true'));
        } else {
            $url = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_paycenter2', 'act' => 'result_pay', 'full' => 1, 'arg0' => $recv['merchantOrderNo']));
        }
        header('Location: ' . $url);
        die;
    }

    function request($path, $param = array(), $method = 'Post')
    {
        $return = array();

        $pars = array(
            'client_id' => $this->_config['client_id'],
            'access_token' => $this->token(),
            'timestamp' => time(),
            'param' => json_encode($param),
        );

        $pars['sign'] = $this->sign($pars);
        $url = $this->_config['domain'] . $path;
        $curl = new \Neigou\Curl();
        $curl->SetHeader('Content-Type', 'application/json');
        $result = $curl->$method($url, json_encode($pars));
        \Neigou\Logger::General('pay.mdouli2.api', array('path' => $url, 'pars' => $pars, 'res' => $result));
        $result = json_decode($result, true);
        if ($result['code'] == 1000) {
            return $result['data'];
        } else {
            \Neigou\Logger::General('pay.mdouli2.api.err', array('path' => $url, 'pars' => $pars, 'res' => $result));
        }

        return $return;
    }

    function sign($pars)
    {
        ksort($pars);
        $sign_str = '';
        foreach ($pars as $kk => $vv) {
            $sign_str .= $kk . '=' . $vv . '&';
        }
        $sign_str = $sign_str . 'client_secret=' . $this->_config['client_secret'];
        $sign = md5($sign_str);
        return $sign;
    }

    function token()
    {
//        $token = '';
//        $_redis = kernel::single('base_sharedkvstore');
//        $_redis->fetch('', 'douli_token', $token);
//        if ($token) {
//            return $token;
//        }
        $curl = new \Neigou\Curl();
        $curl->SetHeader('Content-Type', 'application/json');
        $pars = array(
            'client_id' => $this->_config['client_id'],
            'timestamp' => time(),
        );
        $pars['sign'] = $this->sign($pars);
        $result = $curl->Post($url = $this->_config['domain'] . 'payment/auth/authorize', json_encode($pars));
        $result = json_decode($result, true);
        if ($result['code'] == 1000) {
//            $_redis->store('', 'douli_token', $result['data']['access_token'], $result['data']['expires_in'] - 100);
            return $result['data']['access_token'];
        }
        return '';
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

    /**
     * 获取客户端IP
     * @return string
     */
    function get_client_ip()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';
    }
}