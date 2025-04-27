<?php

/**
 * 外部收款专用 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mouterpay extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '外部收款专用 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '外部收款专用 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mouterpay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mouterpay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '外部收款专用 支付';
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
    public $supportCurrency = array("CNY"=>"01");

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '外部收款专用 支付配置信息';
    }

    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('ectools')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'pay_type'=>array(
                'title'=>app::get('wap')->_('支付类型(是否在线支付)'),
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
    public function intro(){
        return app::get('ectools')->_('外部收款专用 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {

        die;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        $ret['bank'] = app::get('ectools')->_('外部支付');//TODO 这里是支付方式名称 确认
        $ret['pay_account'] = '1';
        $ret['currency'] = 'CNY';
        $ret['paycost'] = '0.000';
        $ret['trade_no'] = 'outer：'.rand(1000,9999).time();//queryId 交易流水号 traceNo 系统跟踪号
        $ret['t_payed'] = time();
        $ret['pay_app_id'] = "mouterpay";
        $ret['pay_type'] = 'online';
        $ret['status'] = 'succ';
        return true;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    public function gen_form(){
        return '';
    }
}