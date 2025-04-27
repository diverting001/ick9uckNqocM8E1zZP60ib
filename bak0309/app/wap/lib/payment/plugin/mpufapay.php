<?php

use Neigou\Curl;

final class wap_payment_plugin_mpufapay extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '浦发支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '浦发支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mpufapay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mpufapay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '浦发支付';
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
    public $supportCurrency = array("CNY" => "01");
    /**
     * @var array 展示的环境[h5 weixin wxwork]
     */
    public $display_env = array('h5');

    /**
     * @var string 通用支付
     */
    public $is_general = 0;

    private $prefix = 'wap_payment_plugin_mpufapay_prefix';

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mpufapay_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }

        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mpufapay', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }

        $this->submit_url = 'spdbbank://wap.spdb.com.cn/pay';
        $this->submit_charset = 'GBK';
        $this->submit_method = 'GET';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro()
    {
        return app::get('wap')->_('浦发支付');
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
                'title' => app::get('wap')->_('支付方式名称'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'base_url' => array(
                'title' => app::get('wap')->_('乐健中间件基础地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pay_url' => array(
                'title' => app::get('wap')->_('支付接口地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'query_url' => array(
                'title' => app::get('wap')->_('订单查询接口地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'refund_url' => array(
                'title' => app::get('wap')->_('退款接口地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'secret_key' => array(
                'title' => app::get('wap')->_('中间件秘钥'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'order_by' => array(
                'title' => app::get('wap')->_('排序'),
                'type' => 'string',
                'label' => app::get('wap')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'pay_brief' => array(
                'title' => app::get('wap')->_('支付方式简介'),
                'type' => 'textarea',
            ),
            'pay_desc' => array(
                'title' => app::get('wap')->_('描述'),
                'type' => 'html',
                'includeBase' => true,
            ),
            'pay_type' => array(
                'title' => app::get('wap')->_('支付类型(是否在线支付)'),
                'type' => 'radio',
                'options' => array('false' => app::get('wap')->_('否'), 'true' => app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'is_general' => array(
                'title' => app::get('wap')->_('通用支付(是否为缺省通用支付)'),
                'type' => 'radio',
                'options' => array('0' => app::get('wap')->_('否'), '1' => app::get('wap')->_('是')),
            ),
            'status' => array(
                'title' => app::get('wap')->_('是否开启此支付方式'),
                'type' => 'radio',
                'options' => array('false' => app::get('wap')->_('否'), 'true' => app::get('wap')->_('是')),
                'name' => 'status',
            )
        );
    }

    /**
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro()
    {
        return app::get('wap')->_('浦发在线支付解决方案。');
    }

    private $db = null;

    /**
     * @return base_interface_db
     */
    private function getDb()
    {
        if ($this->db === null) {
            $this->db = kernel::database();
        }
        return $this->db;
    }

    /**
     * 
     *Array
     *(
     *    [order_id] => 202110261453223500
     *    [return_url] => /m/paycenter2-result_pay.html
     *    [package_key] => 
     *    [combination_pay] => false
     *    [memo] => 
     *    [pay_app_id] => mpufapay
     *    [cur_money] => 1.000
     *    [payment_id] => 1642661146545986
     *    [member_id] => 1238178
     *    [wxopenid] => 
     *    [pay_object] => pop_order
     *    [shopName] => 内购网 - neigou.com
     *    [is_certification] => 0
     *    [cur_amount] => 1.000
     *    [money] => 1.000
     *    [currency] => CNY
     *    [total_amount] => 1.000
     *    [payed] => 1.000
     *    [payinfo] => Array
     *        (
     *            [cost_payment] => 1.000
     *        )
     *
     *    [rel_id] => 202110261453223500
     *    [status] => ready
     *    [account] => 内购网 - neigou.com
     *    [bank] => 浦发支付
     *    [pay_account] => api_294699751a9532f25540bad38f56e188
     *    [create_time] => 1635231204
     *    [paycost] => 1.000
     *    [pay_type] => online
     *    [pay_name] => 浦发支付
     *    [pay_ver] => 1.0
     *    [op_id] => 1238178
     *    [ip] => 127.0.0.1
     *    [t_begin] => 1642661152
     *    [t_payed] => 1642661152
     *    [t_confirm] => 1642661152
     *    [trade_no] => 
     *    [orders] => Array
     *        (
     *            [0] => Array
     *                (
     *                    [rel_id] => 202110261453223500
     *                    [bill_type] => payments
     *                    [pay_object] => pop_order
     *                    [bill_id] => 1642661146545986
     *                    [money] => 1.000
     *                )
     *        )
     *)
     *
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        try {
            // $this->testRefund();die;

            $this->initDopay($payment);

            if (isset($payment['body']) && $payment['body']) {
                $body_text = $this->subtext($payment['body'], 30);
                $body = $body_text;
            } else {
                $body = app::get('ectools')->_('我的订单');
            }

            $callbackUrl = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_pay', 'full' => 1, 'arg0' => $payment['order_id'], 'arg1' => 'true'));

            $params = array(
                'transName' => "OPER",
                'termSsn' => $this->getPaymentId(),
                'tranAmt' => $this->getAmount(),
                'mercUrl' => $callbackUrl,  # 支付交易中，前端页面接收交易结果的url
                'notifyUrl' => $this->notify_url,
                'subMercFlag' => 0,
                'subMercGoodsName' => $body,
                'channel' => 2,
            );

            $this->log($this->prefix . 'trace', $params);

            $formData = $this->pay($params);

            $form = $this->getForm($formData);

            echo $form;
            die;
        } catch (Exception $e) {
            $this->log($this->prefix . 'dopayError', array(
                'payment' => $payment,
                'params' => $params,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'code' => $e->getCode()
            ));
            header('Content-Type: text/html; charset=UTF-8');
            echo sprintf('<h1>%s:%s</h1>', $e->getMessage(), $e->getCode());
            die;
        }
    }

    private $paymentId;
    private $orderId;
    private $amount;

    private function initDopay($payment)
    {
        $this->paymentId = $payment['payment_id'];
        $this->orderId = $payment['order_id'];
        $this->amount = number_format($payment['cur_money'], 2, ".", "");
    }

    private function getForm($formData)
    {
        $this->fields = array(
            'Version' => '10.23',
            'Plain' => $formData['plain'],
            'Signature' => $formData['signature'],
            'transName' => $formData['transName'],
        );
        return $this->get_html();
    }

    protected function get_html()
    {
        $sHtml = '<form action="' . $this->submit_url . '" method="' . $this->submit_method . '" name="pay_form" id="pay_form">';
        foreach ($this->fields as $key => $value) {
            $sHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        $sHtml .= "<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml .= "<script>document.forms['pay_form'].submit();</script>";
        return $sHtml;
    }

    private function pay($params)
    {
        $params['sign'] = $this->getSign($params);
        return $this->request($this->getPayUrl(), $params);
    }

    private function getSign($params)
    {
        $params['secretKey'] = $this->getSecretKey();
        if (isset($params['transName'])) {
            unset($params['transName']);
        }
        ksort($params);
        $pieces = array();
        foreach ($params as $key => $value) {
            $pieces[] = $key . '=' . $value;
        }
        return md5(implode('&', $pieces));
    }

    private function request($url, $params)
    {
        $curl = new Curl();
        $curl->SetOpt(CURLOPT_TIMEOUT, 15);
        $curl->SetHeader('Content-Type', 'application/json');
        $url = $this->getBaseUrl() . $url;
        $string = $curl->Post($url, json_encode($params));
        if ($curl->GetHttpCode() != 200) {
            $this->log($this->prefix . 'requestStatusError', compact('url', 'params', 'string'));
            throw new Exception('支付中间件服务异常，请求状态码非200', 550);
        }
        $result = json_decode($string, true);

        $state = isset($result['success']) ? $result['success'] : null;
        $code = isset($result['code']) ? $result['code'] : '';
        $message = isset($result['message']) ? $result['message'] : '';
        $data = isset($result['data']) ? $result['data'] : '';
        if (!$state || $code != '10000') {
            $this->log($this->prefix . 'requestResultError', compact('url', 'params', 'string'));
            throw new Exception(sprintf('支付中间件错误: %s', $message ?: '未知错误'), 551);
        }
        return $data;
    }

    private function setCache($key, $value, $ttl = 0)
    {
        $key = $this->prefix . $key;
        /** @var base_kvstore_redisV2 */
        $redis = kernel::single('base_kvstore_redisV2');
        $redis->store($key, $value, $ttl);
    }

    private function getCache($key)
    {
        $key = $this->prefix . $key;
        /** @var base_kvstore_redisV2 */
        $redis = kernel::single('base_kvstore_redisV2');
        $value = null;
        $redis->fetch($key, $value);
        return $value;
    }

    private function redirect($url)
    {
        /** @var base_component_response */
        $response = kernel::single('base_component_response');
        $response->set_redirect($url)->send_headers();
        die;
    }

    private function redirectApp($app, $ctl, $act)
    {
        /** @var base_component_response */
        $response = kernel::single('base_component_response');
        $url = app::get('wap')->router()->gen_url(compact('app', 'ctl', 'act'));
        $this->redirect($url);
        die;
    }

    private function getOrderId()
    {
        return $this->orderId;
    }

    private function getPaymentId()
    {
        return $this->paymentId;
    }

    private function getAmount()
    {
        return $this->amount;
    }

    public function getBaseUrl()
    {
        return $this->getConf('base_url', __CLASS__);
    }

    public function getPayUrl()
    {
        return $this->getConf('pay_url', __CLASS__);
    }

    private function getQueryUrl()
    {
        return $this->getConf('query_url', __CLASS__);
    }

    private function getRefundUrl()
    {
        return $this->getConf('refund_url', __CLASS__);
    }

    private function getSecretKey()
    {
        return $this->getConf('secret_key', __CLASS__);
    }

    private function log($name, $params)
    {
        \Neigou\Logger::General($name, $params);
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        
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

    /**
     * 生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function gen_form()
    {
        return '';
    }

    private function testRefund()
    {
        /** @var ectools_newrefund_plugin_mpufapay */
        $refundObject = kernel::single('ectools_newrefund_plugin_mpufapay');
        $info = array(
            'status' => 'none',
            'payment_id' => '1635928712835326',
            'cur_money' => '1',
            'refund_id' => '200127',
            'trade_no' => 'sdxtestuser154961755090805525193040847847930',
        );
        $ret = $refundObject->dorefund($info);
        var_dump($ret, $info);
        $ret = $refundObject->getRefundStatus($info);
        var_dump($ret, $info);
        die;
    }
}
