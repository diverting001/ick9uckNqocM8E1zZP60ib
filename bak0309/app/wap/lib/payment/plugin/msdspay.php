<?php

use Neigou\Curl;

final class wap_payment_plugin_msdspay extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '索迪斯支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '索迪斯支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'msdspay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'msdspay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '索迪斯支付';
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
    public $is_general = 1;

    private $prefix = 'wap_payment_plugin_msdspay_prefix';

    private $payTokenUrl = 'ExpenseTokenRequest';

    private $payUrl = 'ExpenseByToken';

    private $balanceUrl = 'TBalanceQuery';

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_msdspay_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }

        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_msdspay', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://', '', $this->callback_url);
            $this->callback_url = preg_replace("|/+|", "/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro()
    {
        return app::get('wap')->_('索迪斯支付');
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
            'token_url' => array(
                'title' => app::get('wap')->_('Token地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'api_url' => array(
                'title' => app::get('wap')->_('Api地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'client_id' => array(
                'title' => app::get('wap')->_('合作者身份(client_id)'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'client_secret' => array(
                'title' => app::get('wap')->_('合作者秘钥(client_secret)'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'mid' => array(
                'title' => app::get('wap')->_('商户MID(mid)'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'tid' => array(
                'title' => app::get('wap')->_('商户TID(tid)'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'order_by' => array(
                'title' => app::get('wap')->_('排序'),
                'type' => 'string',
                'label' => app::get('wap')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'pay_fee' => array(
                'title' => app::get('wap')->_('交易费率'),
                'type' => 'pecentage',
                'validate_type' => 'number',
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
        return app::get('wap')->_('索迪斯在线支付解决方案。');
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
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        try {
            // $this->testRefund();die;

            $this->initDopay($payment);

            $balance = $this->getBalance();

            $this->log('wap_msdspay_dopay_trace', array(
                'payment' => $payment,
                'payment_id' => $this->getPaymentId(),
                'amount' => $this->getAmount(),
                'balance' => $balance
            ));

            // 余额不足跳转到错误页
            if ($balance < $this->getAmount()) {
                $this->redirectApp('b2c', 'wap_paycenter2', 'result_failure_sds');
            }

            $outerOrderId = $this->pay();

            $params = array(
                'outer_order_id' => $outerOrderId,
                'amount' => $this->getAmount(),
                'payment_id' => $this->getPaymentId(),
                'notify_time' => time()
            );
            $params['sign'] = $this->getSign($params);

            $url = $this->callback_url . '?' . http_build_query($params);

            $this->redirect($url);
            die;
        } catch (Exception $e) {
            $this->log('wap_msdspay_dopay_error', array(
                'payment' => $payment,
                'payment_id' => $this->getPaymentId(),
                'amount' => $this->getAmount(),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ));
            header('Content-Type: text/html; charset=UTF-8');
            echo sprintf('<h1>%s:%s</h1>', $e->getMessage(), $e->getCode());
            die;
        }
    }

    private function getSign($params)
    {
        ksort($params);
        $pieces = array();
        foreach ($params as $key => $value) {
            $pieces[] = $key . '=' . $value;
        }
        return hash_hmac('md5', implode('&', $pieces), md5($this->getClientSecret()));
    }

    private $paymentId;
    private $orderId;
    private $amount;

    private function initDopay($payment)
    {
        $this->setMember($payment['member_id']);
        
        $this->paymentId = $payment['payment_id'];
        $this->orderId = $payment['order_id'];
        $this->amount = number_format($payment['cur_money'], 2, ".", "");
    }

    private function pay()
    {
        $url = $this->payUrl;
        $params = array(
            'transType' => 'ExpenseByToken',
            'clientTraceNo' => $this->getPaymentId(),
            'expensetoken' => $this->getPayToken(),
            'DiscountAmount' => 0,
            'actualAmount' => $this->getAmount()
        );
        $result = $this->request($url, $params);
        $outerOrderId = $result['hostTraceNo'];

        $this->record($params['clientTraceNo'], $params['expensetoken'], $outerOrderId, $params['actualAmount']);

        return $outerOrderId;
    }

    private function record($paymentId, $token, $outerOrderId, $amount)
    {
        $now = date('Y-m-d H:i:s');
        $orderId = $this->getOrderId();
        $this->getDb()->exec("UPDATE sodexo_orders SET state=2 WHERE payment_id='{$paymentId}' and state=1;");
        $this->getDb()->exec("INSERT INTO sodexo_orders (payment_id,order_id,outer_order_id,token,amount,state,created_at) VALUES(
            '{$paymentId}','{$orderId}','{$outerOrderId}','{$token}','{$amount}',1,'{$now}'
        )");
    }

    private function getPayToken()
    {
        $url = $this->payTokenUrl;
        $params = array(
            'transType' => 'ExpenseTokenRequest',
            'clientTraceNo' => $this->getPaymentId(),
            'openid' => $this->getOpenId()
        );
        $result = $this->request($url, $params);
        return $result['expensetoken'];
    }

    private function getBalance()
    {
        $url = $this->balanceUrl;
        $params = array(
            'transType' => 'BalanceQuery',
            'clientTraceNo' => $this->getPaymentId() . mt_rand(1000, 9999),
            'expensetoken' => $this->getPayToken()
        );
        $result = $this->request($url, $params);
        return $result['avaAmount'] ?: 0;
    }

    private function getOauthToken()
    {
        $token = $this->getCache('oauthToken');
        if ($token) {
            return $token;
        }

        list($token, $ttl) = $this->tokenRequest();
        $this->setCache('oauthToken', $token, $ttl);
        return $token;
    }

    private function tokenRequest()
    {
        $url = $this->getTokenUrl();
        $params = array(
            'client_id' => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'client_credentials'
        );
        $curl = new Curl();
        $curl->SetHeader('Content-Type', 'application/x-www-form-urlencoded');
        $string = $curl->Post($url, $params);
        $result = json_decode($string, true);
        $accessToken = isset($result['access_token']) ? $result['access_token'] : null;
        if ($accessToken === null) {
            $this->log($this->prefix . 'getOauthToken', compact('url', 'params', 'string'));
            throw new Exception('获取oauthToken失败');
        }
        $expire = $result['expires_in'];
        return array($accessToken, $expire);
    }

    private function request($url, $params)
    {
        $curl = new Curl();
        $curl->SetOpt(CURLOPT_TIMEOUT, 15);
        $curl->SetHeader('Content-Type', 'application/x-www-form-urlencoded');
        $curl->SetHeader('Authorization', 'Bearer ' . $this->getOauthToken());
        $url = $this->getApiUrl() . $url;
        $params = array_merge(array(
            'version' => '1.0',
            'submitTime' => $this->getSubmitTime(),
            'mid' => $this->getMID(),
            'tid' => $this->getTID()
        ), $params);
        $string = $curl->Post($url, $params);
        $result = json_decode($string, true);

        $returnCode = isset($result['returnCode']) ? $result['returnCode'] : '-1';
        if ($returnCode === '-1') {
            $returnCode = isset($result['returncode']) ? $result['returncode'] : '-2';
        }
        if ($returnCode !== '0000') {
            $this->log($this->prefix . 'requestError', compact('url', 'params', 'string'));
            throw new Exception('支付失败，错误代码：'. $returnCode);
        }
        return $result;
    }

    private function getSubmitTime()
    {
        return date('YmdHis');
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

    private $member = null;

    private function setMember($memberId)
    {
        /** @var dbeav_model */
        $memberModel = app::get('b2c')->model('members');
        $member = $memberModel->getRow('company_id', array(
            'member_id' => $memberId
        ));
        /** @var dbeav_model */
        $memberCompanyModel = app::get('b2c')->model('member_company');
        $this->member = $memberCompanyModel->getRow('member_key', array(
            'member_id' => $memberId,
            'company_id' => $member['company_id']
        ));
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

    private function getOpenId()
    {
        return $this->member['member_key'];
    }

    public function getTokenUrl()
    {
        return $this->getConf('token_url', __CLASS__);
    }

    public function getApiUrl()
    {
        return $this->getConf('api_url', __CLASS__);
    }

    private function getClientId()
    {
        return $this->getConf('client_id', __CLASS__);
    }

    private function getClientSecret()
    {
        return $this->getConf('client_secret', __CLASS__);
    }

    private function getMID()
    {
        return $this->getConf('mid', __CLASS__);
    }

    private function getTID()
    {
        return $this->getConf('tid', __CLASS__);
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
        $getSign = $recv['sign'];
        unset($recv['sign']);
        $newSign = $this->getSign($recv);
        $ret = array();
        if ($getSign === $newSign) {
            $ret['payment_id'] = $recv['payment_id'];
            $ret['account'] = $this->getClientId();
            $ret['bank'] = app::get('wap')->_('手机索迪斯');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['amount'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['amount'];
            $ret['trade_no'] = $recv['outer_order_id'];
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = 'msdspay';
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';

            $ret['status'] = 'succ';
            $this->log('msdspay_callback_success', compact('ret', 'recv'));
        } else {
            $ret['status'] = 'invalid';
            $this->log('msdspay_callback_error', compact('recv'));
        }

        return $ret;
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
        /** @var ectools_newrefund_plugin_msdspay */
        $refundObject = kernel::single('ectools_newrefund_plugin_msdspay');
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
    }
}
