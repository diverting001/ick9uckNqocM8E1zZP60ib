<?php
/** 收银台跳转到第三方类
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:19 PM
 */

final class wap_payment_plugin_mdianke extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = 'wap 电科商城支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = 'wap 电科商城支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mdianke'; // 重要
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mdianke'; // 重要
    /**
     * @var string 统一显示的名称
     */
    public $display_name = 'wap 电科商城支付';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.2';
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
    public $auto_submit_channel = array();

    /**
     * 是否自动提交表单 1==>是
     * @var int
     */
    public $auto_submit = 1;

    /**
     * @var array 展示的环境[h5 weixin wxwork]
     */
    public $display_env = array('h5', 'weixin', 'wxwork', 'weixin_program');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);
        // 异步通知
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdianke_server', 'callback');

        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://', '', $this->notify_url);
            $this->notify_url = preg_replace("|/+|", "/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        // 同步通知
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdianke', 'callback');
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
        $this->auto_submit_channel = array($this->_config['channel']);

    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro()
    {
        return 'wap 电科收银台配置信息';
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
            'submit_url' => array(
                'title' => app::get('ectools')->_('支付请求url'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            'key' => array(
                'title' => app::get('ectools')->_('key'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            'secret' => array(
                'title' => app::get('ectools')->_('secret'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            // 签名加密
            'salt' => array(
                'title' => app::get('ectools')->_('签名salt'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            // channel
            'channel' => array(
                'title' => app::get('ectools')->_('openapi的channel'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pay_redirect_url' => array(
                'title' => app::get('ectools')->_('支付跳转地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pay_first_path' => array(
                'title' => app::get('ectools')->_('支付第一级路径'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pay_type' => array(
                'title' => app::get('ectools')->_('支付类型(是否在线支付)'),
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
        return app::get('ectools')->_('wap 电科支付');
    }

    /** 提交支付信息的接口
     *
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        $orderId = $payment['order_id'];
        try {
            // 步骤1: 同步第三方订单
            $totalFee = number_format($payment['cur_money'], 2, ".", ""); // 订单需要支付金额
            //$totalAmount = number_format($payment['total_amount'], 2, ".", ""); // 订单总金额
            $res = $this->createOrder($orderId,$totalFee,$payment['payment_id']);
            if (empty($res) || $this->checkResult($res) !== true){
                throw new Exception('订单:'.$orderId.'同步电科失败. 电科返回:'.$res['code'].'-'.$res['msg']);
            }
            $url = $this->callJsPay($res['result']['data']['ordernumber']);
            // 设置将结果
            //$this->printfRes('true',$res['result']['data']);
        } catch (Exception $e) {
            \Neigou\Logger::General('ecstore.mdianke_pay_error', array('errorMsg' => $e->getMessage(), 'order_id' => $payment['order_id'], 'env' => 'wap端', 'payment' => $payment));
            // 设置结果
            //$this->printfRes('false',array(),$e->getMessage(),4000);
            $url = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_paycenter2', 'act' => 'result_failed', 'arg0' => $payment['order_id']));
        }
        header('Location: ' . $url);
        exit();
    }


    /** 设置签名
     *
     * @return string
     * @author liuming
     */
    public function setSign($data = array())
    {
        if (isset($data['sign'])) {
            unset($data['sign']);
        }
        $data['salt'] = $this->_config['salt'];
        ksort($data);
        $info = '';
        foreach ($data as $k => $v) {
            $v = urlencode($v);
            $info .= $k;
            $info .= '=';
            $info .= $v;
            $info .= '&';
        }
        $info = trim($info, '&');
        return md5($info);
    }

    /** 设置公共请求参数
     *
     * @param array $params
     * @return array
     * @author liuming
     */
    private function addCommonParams($params = array())
    {
        // 设置签名参数
        $params['key'] = $this->_config['key'];
        $params['timestamp'] = $this->getMillisecond();
        $params['nonce'] = rand(10000,99999);
        return $params;
    }

    /** 电科签名
     *
     * @param array $data
     * @param string $path
     * @return string
     * @author liuming
     */
    private function setDkSign($data = array(),$path = '')
    {
        if (isset($data['signData'])) {
            unset($data['signData']);
        }
        //$data['salt'] = $this->_config['salt'];
        ksort($data);
        $info = '';
        foreach ($data as $k => $v) {
            if (is_array($v)){
                $v = json_encode($v);
            }
            $info .= $k;
            $info .= '=';
            $info .= $v;
            $info .= '&';
        }
        $info = trim($info, '&');
        $info = $path.'?'.'secret='.$this->_config['secret'].'&'.$info;
        $sign = md5($info);
        //echo '<pre>';print_r(array('原始数据' =>$data,'拼接字符串' => $info,'md5' => $sign));die(__FILE__.':'.__LINE__);
        return $sign;

    }


    public function jsonEncodeTool($array)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $str = json_encode($array);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function ($matchs) {
                return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
            }, $str);
        } else {
            $str = json_encode($array, JSON_UNESCAPED_UNICODE);
        }
        return $str;
//        $str = str_replace('\/\/','',$str);
//        return $str;
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        return '';
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad()
    {
        // 校验
        return true;
    }

//    public function gen_form(){
//        return '';
//    }

    /** 设置html表单提交
     *
     * @return string
     * @author liuming
     */
    public function gen_form()
    {
        $html = '';
        return $html;
    }

    private function callJsPay($orderId = ''){
        $params = array(
            'orderNumber' => $orderId
        );
        $paramsStr = http_build_query($params);
        return $this->_config['pay_redirect_url'].'?'.$paramsStr;
    }



    /** 请求电科
     *
     * @param string $name
     * @param array $requestData
     * @return mixed
     * @throws Exception
     * @author liuming
     */
    public function request($name = '',$requestData = array()){
        $basePath = $this->getPath($name);
        if (empty($basePath)){
            throw new Exception('请求路径名称: '.$name.'不存在');
        }
        $path = $this->_config['pay_first_path'].$basePath;
        $requestData = $this->addCommonParams($requestData);
        // 设置签名
        $dkSign = $this->setDkSign($requestData,$basePath);
        $requestData['signData'] = $dkSign;
        $url = $this->_config['submit_url'];
        $requestUrl = $url.$path;
        $curl = new \Neigou\Curl();
        $proxyServer = NEIGOU_HTTP_PROXY;
        $opt_config = array(
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
            CURLOPT_PROXY => $proxyServer,
        );
        $curl->SetOpt($opt_config);
        $orgResult = $curl->Post($requestUrl, $requestData);
        $result = json_decode($orgResult,true);
        //echo '<pre>';print_r(array('参数' => json_encode($requestData),'返回结果' => $result,'路径' => $requestUrl));die(__FILE__.':'.__LINE__);
        $logData = array('sub_name' => $name,'param' => $requestData, 'result' => $result,'orgResult' => $orgResult,'url' => $requestUrl);
        if (!$this->checkResult($result)){ // 请求失败
            \Neigou\Logger::General("ecstore.mdianke_refund_request", $logData);
        }else{
            \Neigou\Logger::Debug("ecstore.mdianke_refund_request", $logData);
        }
        return $result;
    }


    /** 检查返回结果
     *
     * @param array $result 返回结果
     * @return bool
     * @author liuming
     */
    public function checkResult($result = array()){
        if (empty($result) || $result['code'] !== '0'){
            return false;
        }
        return true;
    }
    /** 获取请求路径
     *
     * @param $name string 路径名称
     * @return string
     * @author liuming
     */
    private function getPath($name = '')
    {
        $list = array(
            'createOrder' => '/diandi/addOrder', // 文档: 创建订单接口
            'orderStateNotify' =>'/diandi/orderStateNotify', // 订单状态更新通知接口
            'pointPayNotify' => '/diandi/payNotify', // 积分支付完成通知接口
            'refund' => '/diandi/refund', // 订单退款接口
        );

        return isset($list[$name]) ? $list[$name] : '';
    }

    /** 获取订单详情
     *
     * @param string $orderId
     * @return mixed
     * @author liuming
     */
    public function getOrderDetail($orderId = ''){
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($orderId);
        return $order_info;
    }

    /** 同步创建订单接口
     *
     * @param string $orderId 订单id
     * @param float $cashPrice 支付现金金额
     * @param string $paymentId 支付id
     * @return mixed
     * @throws Exception
     * @author liuming
     */
    public function createOrder($orderId = '',$cashPrice = 0.00,$paymentId = ''){
        // ----  设置请求参数 begin ----//
        $orderDetail = $this->getOrderDetail($orderId);
        $uerExternalBn = $this->findUserExternalBn($orderDetail['member_id']);
        $requestData = array(
            'orderId' => $orderId,
            'paycode' => $paymentId,
            'userId' => $uerExternalBn,
            'price' => $cashPrice,// 本次订单支付金额
            'orderPrice' => $orderDetail['final_amount'],// 订单总金额
        );
        // ----  设置请求参数 end ----//
        // 发起请求
        $res = $this->request('createOrder',$requestData);
        return $res;

    }

    /** 查询商品详情
     *
     * @param string $productBn
     * @param string $fields 查询字段
     * @return array 商品详情
     * @author liuming
     */
    private function findProductDetail($productBn = '',$fields = 'spec_info'){
        $sql = 'SELECT '.$fields.' FROM `sdb_b2c_products` WHERE bn = "'.$productBn.'"';
        $result = kernel::database()->selectrow($sql);
        return $result;
    }

    /** 查询用户external bn
     *
     * @param int $memberId
     * @return mixed
     * @throws Exception
     * @author liuming
     */
    private function findUserExternalBn($memberId = 0){
        //获取当前用户external_bn
        $_third_company_member = app::get('b2c')->model('third_members');
        $findUserInfo = $_third_company_member->getRow('external_bn', array('channel' => $this->_config['channel'], 'internal_id' => $memberId));

        if (empty($findUserInfo) || empty($findUserInfo['external_bn'])) {
            throw new Exception('用户信息不存在');
        }
        return $findUserInfo['external_bn'];
    }

    /** 获取毫秒时间戳
     *
     * @return float
     * @author liuming
     */
    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    /** 打印返回结果
     *
     * @param string $res
     * @param array $data
     * @param string $msg
     * @param int $errId
     * @author liuming
     */
    private function printfRes($res = 'false',$data = array(),$msg = '',$errId = 0)
    {
        $data = array(
            'Result' => $res,
            'ErrorId' => $errId,
            'ErrMsg' => $msg,
            'Data' => $data
        );
        die(json_encode($data));
    }


}