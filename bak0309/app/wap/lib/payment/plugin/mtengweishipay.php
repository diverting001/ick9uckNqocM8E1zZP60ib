<?php
/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:19 PM
 */

final class wap_payment_plugin_mtengweishipay extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = 'wap 腾威视支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = 'wap 腾威视支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mtengweishipay'; // 重要
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mtengweishipay'; // 重要
    /**
     * @var string 统一显示的名称
     */
    public $display_name = 'wap 腾威视支付';
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
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app)
    {
        parent::__construct($app);
        // 异步通知
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mtengweishipay_server', 'callback');

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
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mtengweishipay', 'callback');
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
        return 'wap 腾威视配置信息';
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
            'url' => array(
                'title' => app::get('ectools')->_('支付请求url'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'otherUrl' => array(
                'title' => app::get('ectools')->_('腾威视其他请求url'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'appkey' => array(
                'title' => app::get('ectools')->_('appKey'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'appSecret' => array(
                'title' => app::get('ectools')->_('appSecret'),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pay_type'=>array(
                'title'=>app::get('ectools')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'status'=>array(
                'title'=>app::get('ectools')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
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
        try{
            /** 获取公司和用户信息 -begin */
            // 获取当前用户公司id
            $companyId = kernel::single("b2c_member_company")->get_cur_company();
            if (empty($companyId)){
                throw new Exception('公司不存在');
            }

            //获取当前用户external_bn
            $_third_company_member = app::get('b2c') -> model('third_members');
            $findUserInfo = $_third_company_member -> getRow('external_bn',array('channel' => TENGWEISHI_CHANNEL,'internal_id' => $payment['member_id']));

            if (empty($findUserInfo) || empty($findUserInfo['external_bn'])){
                throw new Exception('用户信息不存在');
            }
            $externalBn = $findUserInfo['external_bn'];
            /** 获取公司和用户信息 -end */

            $time = time();
            /** 设置参数  -begin */
            $params = array(
                'appkey' => $this->_config['appkey'],
                'callback_url' => $this->notify_url, // 支付回调地址
                'id' => $externalBn, // 三方用户id
                'orderno' => $payment['order_id'],
                'timestamp' => $time,
                'totalpay' => number_format($payment['money'], 2, ".", ""), //订单总金额
                'remark' => !empty($payment['memo']) ? $payment['memo'] : '未填写备注信息', // 订单备注
                'pay_id' => $payment['payment_id'],
                //'sign' => $sign,
            );
            $params['sign'] = $this->setSign($params);
            $params = http_build_query($params);
            /** 设置参数  -end */

            // 请求url地址
            $url = $this->_config['url'].'checkOut/checkOut?'.$params;
        }catch (Exception $e){
            \Neigou\Logger::General('tengweishi_pay_error',array('errorMsg' => $e->getMessage(),'order_id' => $payment['order_id'],'env' => 'wap端','payment' => $payment));
            //跳转支付失败  reason 商品更新失败
            $url = app::get('site')->router()->gen_url(array('app'=>'b2c','ctl'=>'site_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
        }
        header('Location: ' .$url);exit();
    }


    /** 设置签名
     *
     * @param array $data
     * @return string
     * @author liuming
     */
    public function setSign($data = array()){
        ksort($data);
        $info = '';
        foreach ($data as $k => $v) {
            $info .= $k;
            $info .= '=';
            $info .= $v;
        }
        $info = $this->_config['appSecret'] . '' . $info . '' . $this->_config['appSecret'];
        return strtoupper(md5($info));

    }

    public function jsonEncodeTool($array)
    {
        if(version_compare(PHP_VERSION,'5.4.0','<')){
            $str = json_encode($array);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i",function($matchs){
                return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
            },$str);
        }else{
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
        //跳转到订单详情页面
        //获取订单编号
//        $order_info = app::get('ectools')->model('order_bills')->getRow('rel_id', array('bill_id' => $recv['merchant_order_no']));
//
//
//        $url = app::get('wap')->router()->gen_url(array('app' => 'b2c', 'ctl' => 'wap_member', 'act' => 'new_orderdetail', 'arg0' => $order_info['rel_id']));
//        \Neigou\Logger::General('pay.unicomO2O', array('action' => 'pay_unicomo2opay', 'result' => $order_info));
//        header('Location: ' . $url);
        die;
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

    public function gen_form()
    {
        // api模拟表单提交
        return '';
    }

}