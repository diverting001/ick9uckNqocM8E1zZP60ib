<?php
/** 收银台跳转到第三方类
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:19 PM
 */

final class wap_payment_plugin_mdongliyuebo extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = 'wap 动力跃博支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = 'wap 动力跃博支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mdongliyuebo'; // 重要
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mdongliyuebo'; // 重要
    /**
     * @var string 统一显示的名称
     */
    public $display_name = 'wap 动力跃博支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdongliyuebo_server', 'callback');

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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdongliyuebo', 'callback');
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
        return 'wap 动力跃博收银台配置信息';
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
            // 动力跃博专有
            'password' => array(
                'title' => app::get('ectools')->_('秘钥'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            // 动力跃博专有
            'offset' => array(
                'title' => app::get('ectools')->_('偏移量'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'submit_url' => array(
                'title' => app::get('ectools')->_('支付请求url'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            // 签名加密
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
        return app::get('ectools')->_('wap 腾威视支付');
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
            $totalFee = number_format($payment['cur_money'], 2, ".", "");
            $params = array(
                'OrderSN' => $payment['order_id'],
                'Body' => '',
                'TotalFee' => $totalFee,
                'UserOpenId' => $externalBn,
                'payment_id' => $payment['payment_id'],
                "timestamp" => time(),
            );
            /** 设置参数  -end */
            $requestStr = $this->setRequestStr($params);
            $encryStr = $this->encrypt($requestStr);
            Neigou\Logger::General('ecstore.mdongliyuebo_pay',array('sub_name' => 'request','fields' => $this->fields,'method' => __METHOD__));
            // 请求url地址
            $url = $this->_config['submit_url'].'?Attach=ddshop&ddshop='.$encryStr;
        } catch (Exception $e) {
            \Neigou\Logger::General('ecstore.mdongliyuebo_pay_error', array('errorMsg' => $e->getMessage(), 'order_id' => $payment['order_id'], 'env' => 'wap端', 'payment' => $payment));
            //跳转支付失败  reason 商品更新失败
            $url = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_paycenter2', 'act' => 'result_failed', 'arg0' => $payment['order_id']));
        }
        header('Location: ' . $url);
        exit();
    }

    /** 设置请求字符串
     *
     * @param array $data
     * @return string
     * @author liuming
     */
    private function setRequestStr($data = array()){
        $str = $data['OrderSN'].':'.$data['body'].':'.$data['TotalFee'].':'.$data['UserOpenId'].':'.$data['timestamp'].':'.$data['payment_id'];
        return $str;
    }



    /** 设置签名
     *
     * @return string
     * @author liuming
     */
    public function encrypt($encryptStr = '')
    {
        //$encryptStr = 'ZX11000';

        $localIV = $this->_config['offset'];
        $encryptKey = $this->_config['password'];
        $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, $localIV);
        mcrypt_generic_init($module, $encryptKey, $localIV);
        $block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
        $pad = $block - (strlen($encryptStr) % $block);
        $encryptStr .= str_repeat(chr($pad), $pad);
        $encrypted = mcrypt_generic($module, $encryptStr);
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        $encryptStr = base64_encode($encrypted);
        return $encryptStr;
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
        exit();
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

}