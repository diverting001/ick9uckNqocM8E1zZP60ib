<?php
/** 收银台跳转到第三方类
 *
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:19 PM
 */

final class wap_payment_plugin_mzhongxiang extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = 'wap 众享支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = 'wap 众享支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mzhongxiang'; // 重要
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mzhongxiang'; // 重要
    /**
     * @var string 统一显示的名称
     */
    public $display_name = 'wap 众享支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mzhongxiang_server', 'callback');

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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mzhongxiang', 'callback');
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
        return 'wap 众享收银台配置信息';
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
            'salt' => array(
                'title' => app::get('ectools')->_('签名salt'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            // 签名加密
            'channel' => array(
                'title' => app::get('ectools')->_('openapi的channel'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            // 订单退款地址
            'refund_url' => array(
                'title' => app::get('ectools')->_('订单退款地址'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            // 退款信息/状态查询
            'refund_detail_url' => array(
                'title' => app::get('ectools')->_('退款信息/状态查询'),
                'type' => 'string',
                'validate_type' => 'required',
            ),

            // 订单/售后更新同步通知地址
            'order_notify_url' => array(
                'title' => app::get('ectools')->_('订单售后更新同步通知地址'),
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
            $this->setFields($payment, $externalBn);
            /** 设置参数  -end */

            Neigou\Logger::General('ecstore.mzhongxiang_pay',array('sub_name' => 'request','fields' => $this->fields,'method' => __METHOD__));
            // 请求url地址
            $html = $this->get_html();
            die($html);
        } catch (Exception $e) {
            \Neigou\Logger::General('ecstore.mzhongxiang_pay_error', array('errorMsg' => $e->getMessage(), 'order_id' => $payment['order_id'], 'env' => 'wap端', 'payment' => $payment));
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
    private function setFields($data = array(), $userBn = '')
    {
        if (empty($data)) {
            throw new Exception('支付请求参数错误');
        }
        $time = time();
        $expireTime = $time + 2380;
        $totalFee = number_format($data['cur_money'], 2, ".", "") * 100;


        $this->add_field('out_trade_no', $data['payment_id']);
        $this->add_field('order_id', $data['order_id']);
        $this->add_field('account_no', $userBn);//external_user_bn
        $this->add_field('total_fee', $totalFee);
        $this->add_field('notify_url', $this->notify_url);
        $this->add_field('callback_url', $this->callback_url);
        $this->add_field('sub_time', $time); // 支付发起时间
        $this->add_field('expir_time', $expireTime); // 支付单据过期时间

        $sign = $this->setSign($this->fields);
        $this->add_field('sign', $sign); // 支付单据过期时间
    }


    /** 设置签名
     *
     * @return string
     * @author liuming
     */
    public function setSign($data = array())
    {
        if (isset($data['sign'])){
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
        \Neigou\Logger::Debug('ecstore.mzhongxiang_pay',array('sub_name' => 'sync_callback','recv' => $recv,'method' => __METHOD__));

        // 签名验证
        $sign = $this->setSign($recv);
        if ($sign == $recv['sign']){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $recv['account']; //支付账户 （第三方用户支付账户）
            $ret['bank'] = app::get('ectools')->_('众享支付');
            //$ret['pay_account'] = $recv['appId'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = $recv['notify_time']? $recv['notify_time'] : time();
            $ret['pay_app_id'] = "mzhongxiang";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            \Neigou\Logger::General('ecstore.mzhongxiang_pay', array('sub_name' => 'sync_callback','remark'=>'sign_err','data'=>$recv,'method' => __METHOD__));
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.mzhongxiang_pay_error',array('sub_name' => 'sync_callback','remark'=>'sign_err','data'=>$recv,'method' => __METHOD__));
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
        // 简单的form的自动提交的代码。
        header("Content-Type: text/html;charset=".$this->submit_charset);
        $strHtml ="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
		<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\" dir=\"ltr\">
		<head>
		</head><body><div>正在跳转支付...</div>";
        $strHtml .= '<form action="' . $this->submit_url . '" method="' . $this->submit_method . '" name="pay_form" id="pay_form">';

        // Generate all the hidden field.
        foreach ($this->fields as $key=>$value)
        {
            $strHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        $strHtml .= '<input type="submit" name="btn_purchase" value="'.app::get('ectools')->_('购买').'" style="display:none;" />';
        $strHtml .= '</form><script type="text/javascript">
						window.onload=function(){
							document.getElementById("pay_form").submit();
						}
					</script>';
        $strHtml .= '</body></html>';
        return $strHtml;
    }

}