<?php
/** 收银台跳转到第三方类
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:19 PM
 */

final class wap_payment_plugin_mqiyewx extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = 'wap 企业微信支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = 'wap 企业微信支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mqiyewx'; // 重要
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mqiyewx'; // 重要
    /**
     * @var string 统一显示的名称
     */
    public $display_name = 'wap 企业微信支付';
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
    public $auto_submit = 0;

    /**
     * @var array 展示的环境[h5 weixin wxwork]
     */
    public $display_env = array('wxwork');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);

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
        return 'wap 企业微信收银台配置信息';
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
            'order_expire_time' => array(
                'title' => app::get('ectools')->_('订单有效时间'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pay_type' => array(
                'title' => app::get('ectools')->_('支付类型(是否在线支付)'),
                'type' => 'radio',
                'options' => array('false' => app::get('wap')->_('否'), 'true' => app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'is_general'=>array(
                'title'=>app::get('ectools')->_('通用支付(是否为缺省通用支付)'),
                'type'=>'radio',
                'options'=>array('0'=>app::get('ectools')->_('否'),'1'=>app::get('ectools')->_('是')),
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
        return app::get('ectools')->_('wap 企业微信支付');
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

            // 设置支付需要的数据
            $orderDetail = $this->getOrderDetail($payment['order_id']);
            $memberLib = kernel::single("b2c_member");
            $extend = array(
                'guid' => $_SESSION['account']['guid'],
            );
            $currTime = time();
            $ttl = $this->_config['order_expire_time'] - ($currTime - $orderDetail['create_time']);
            $token = $memberLib->setToken($payment['member_id'],$companyId,$extend,$ttl);
            if (empty($token)){
                throw new Exception('创建token失败');
            }
            /** 设置参数  -end */

            // 请求url地址
            $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'index', 'arg0' => $payment['order_id'],'arg1' => '1','arg2' => $token));
        } catch (Exception $e) {
            \Neigou\Logger::General('ecstore.mqiyewx_pay_error', array('errorMsg' => $e->getMessage(), 'order_id' => $payment['order_id'], 'env' => 'wap端', 'payment' => $payment));
            //跳转支付失败  reason 商品更新失败
            $url = app::get('site')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'site_paycenter2', 'act' => 'result_failed', 'arg0' => $payment['order_id']));
        }
        header('Location: ' . $url);
        exit();
    }

    /** 获取订单详情
     *
     * @param string $orderId
     * @return mixed
     * @author liuming
     */
    public function getOrderDetail($orderId = '')
    {
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($orderId);
        return $order_info;
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


    protected function get_html()
    {
    }

}