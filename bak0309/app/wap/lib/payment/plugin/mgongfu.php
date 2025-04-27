<?php
/** 收银台跳转到第三方类
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2020/8/25
 * Time: 3:19 PM
 */

final class wap_payment_plugin_mgongfu extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = 'wap 工福支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = 'wap 工福支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mgongfu'; // 重要
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mgongfu'; // 重要
    /**
     * @var string 统一显示的名称
     */
    public $display_name = 'wap 工福支付';
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
    public $display_env = array('h5','weixin','wxwork','weixin_program');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);
        // 异步通知
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mgongfu_server', 'callback');

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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mgongfu', 'callback');
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
        return 'wap 工福收银台配置信息';
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
            // 签名加密
            'appid' => array(
                'title' => app::get('ectools')->_('工福appid'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'appsecret' => array(
                'title' => app::get('ectools')->_('工福appsecret'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            'channel' => array(
                'title' => app::get('ectools')->_('openapi的channel'),
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
        return app::get('ectools')->_('wap 工福支付');
    }

    /** 提交支付信息的接口
     *
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        try {
            /** 设置请求参数 -begin */
            // 获取当前用户公司id
            $companyId = kernel::single("b2c_member_company")->get_cur_company();
            if (empty($companyId)) {
                throw new Exception('公司不存在');
            }

            //获取当前用户external_bn
            $_third_company_member = app::get('b2c')->model('third_members');
            $findUserInfo = $_third_company_member->getRow('external_bn', array('channel' => $this->_config['channel'], 'internal_id' => $payment['member_id']));

            if (empty($findUserInfo) || empty($findUserInfo['external_bn'])) {
                throw new Exception('用户信息不存在');
            }
            $externalBn = $findUserInfo['external_bn'];


            // 设置支付需要的数据
            $createOrderData = $this->getCreateOrderData($payment, $externalBn);
            $createOrderRes = $this->request('create_order',$createOrderData);
            if ($this->checkResult($createOrderRes) !== true){
                throw new Exception('创建订单失败:工福返回'.$createOrderRes['msg']);
            }
            /** 设置参数  -end */

            Neigou\Logger::General('ecstore.mgongfu_pay',array('sub_name' => 'request','fields' => $this->fields,'method' => __METHOD__));
            // 请求url地址
            $sign = $this->setSign(array(),$createOrderRes['data']);

            //echo '<pre>';print_r(array($sign,$createOrderRes['sign']));die(__FILE__.':'.__LINE__);
            $createOrderData = json_decode($createOrderRes['data'],true);
            $url = $this->get_html($createOrderData);
        } catch (Exception $e) {
            \Neigou\Logger::General('ecstore.mgongfu_pay_error', array('errorMsg' => $e->getMessage(), 'order_id' => $payment['order_id'], 'env' => 'wap端', 'payment' => $payment));
            //跳转支付失败  reason 商品更新失败
            $url = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_paycenter2', 'act' => 'result_failed', 'arg0' => $payment['order_id']));
        }
        header('Location: ' . $url);
        exit();
    }

    /** 获取支付方式参数
     *
     * @param array $data 支付数据
     * @param string $userBn 用户bn
     * @throws Exception
     * @author liuming
     */
    private function getCreateOrderData($data = array(), $userBn = '')
    {
        if (empty($data)) {
            throw new Exception('支付请求参数错误');
        }
        $totalFee = number_format($data['cur_money'], 2, ".", "");
        $requestData = array(
            'thirdPayId' => $data['payment_id'],
            'userId' => $userBn,
            'payTotal' => $totalFee, // 支付总金额
            'payCompleteUrl' => CHOUTI_NEIGOU_INDEX.'/m/paycenter2-result_wait-'.$data['order_id'].'-true.html'
        );
        $requestData['orderList'][] = array(
            'orderId' => $data['order_id'],
            'total' => $totalFee,
        );
        return $requestData;
    }



    /** 设置签名
     *
     * @return string
     * @author liuming
     */
    public function setSign($commonData = array(),$dataJson = '')
    {
        if ($commonData && $dataJson){
            if ($commonData['timestamp']){
                $signOrgStr = 'appid='.$this->_config['appid'].'&data='.$dataJson.'&timestamp='.$commonData['timestamp'].$this->_config['appsecret'];
            }else{
                $signOrgStr = 'appid='.$this->_config['appid'].'&data='.$dataJson.$this->_config['appsecret'];
            }
        }else if ($dataJson){
            $signOrgStr = 'appid='.$this->_config['appid'].'&data='.$dataJson.$this->_config['appsecret'];
        }else{
            $signOrgStr = 'appid='.$this->_config['appid'].$this->_config['appsecret'];
        }
        $sign = md5($signOrgStr);
        //echo '<pre>';print_r(array('签名字符串' => $signOrgStr,'签名结果' => $sign));
        return $sign;
    }


    private function getCommonParams(){
        $data = array();
        $time = time();
        $data['timestamp'] = $time;
        $data['appid'] = $this->_config['appid'];
        return $data;
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
//        $encodeType = 'utf-8';
//        $html = <<<eot
//<html>
//<head>
//    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
//</head>
//<body onload="javascript:document.pay_form.submit();">
//    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">
//
//eot;
//        foreach ($this->fields as $key => $value) {
//            if ($key != 'salt') {
//                $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
//            }
//        }
//        $html .= <<<eot
//   <!-- <input type="submit" type="hidden">-->
//    </form>
//</body>
//</html>
//eot;
        return $html;
    }


    protected function get_html($data = array())
    {
        $payPath = $this->getPath('pay');
        if (empty($payPath)){
            throw new Exception('支付路径不存在');
        }

        $url = $this->_config['submit_url'].$payPath;
        $params = array(
            'payId' => $data['payId'],
        );
        $paramsJson = json_encode($params);
        $commonParams = array();
        $commonParams['data'] = $paramsJson;
        $requestParams = array(
            'payid' => $data['payId'],
            'sign' => $this->setSign($commonParams,$paramsJson),
        );
        return $url.'?'.http_build_query($requestParams);
    }

    /** 发送请求
     *
     * @param string $pathName
     * @param array $params
     * @return mixed
     * @throws Exception
     * @author liuming
     */
    public function request($pathName = '',$params = array()){
        $path = $this->getPath($pathName);
        if (empty($path)){
            throw new Exception($pathName.'请求路径不存在');
        }
        $requestData = $this->getCommonParams();
        $dataJson = json_encode($params);
        $sign = $this->setSign($requestData,$dataJson);
        $requestData['data'] = $dataJson;
        $requestData['sign'] = $sign;
        $reqeustDataJson = json_encode($requestData);
        $url = $this->_config['submit_url'].$path;
        $curl = new \Neigou\Curl();
        $opt_config = array(
            CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
            CURLOPT_PROXY => NEIGOU_HTTP_PROXY,
        );
        $curl->SetOpt($opt_config);
        $curl->SetHeader('Content-Type', 'application/json');
        $resultTmp = $curl->Post($url, $reqeustDataJson);
        $result = json_decode($resultTmp,true);
        if ($this->checkResult($result) !== true){
            $logData = array('sub_name' => $pathName,'request_url'=>$url,'params'=>$reqeustDataJson,'res' => $resultTmp,'method' => __METHOD__);
            \Neigou\Logger::General('ecstore.mgongfu_pay',$logData);
        }
        return $result;
    }

    /** 检查返回结果
     *
     * @param array $res
     * @return bool
     * @author liuming
     */
    public function checkResult($res = array()){
        if (empty($res) || $res['code'] != 1){
            return false;
        }
        return true;
    }

    private function getPath($name = ''){
        if (empty($name)){
            return '';
        }

        $pathList = array(
            'create_order' => 'api/gfypay.create',//创建订单
            'pay' => 'page/gfypay.aspx',//支付
            'refund' => 'api/gfypay.refund',//退款
            'notify' => 'api/gfypay.receive_notify_orderchange',//订单通知
        );
        return isset($pathList[$name]) ? $pathList[$name] : '';
    }

}