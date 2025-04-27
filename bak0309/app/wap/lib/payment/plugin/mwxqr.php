<?php

/**
 * 微信WAP适配PC端扫码支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mwxqr extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '微信WAP适配PC端扫码支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '微信WAP适配PC端扫码支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mwxqr';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mwxqr';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '微信WAP适配PC端扫码支付';
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
     * @var array 展示的环境[h5 weixin wxwork]
     */
    public $display_env = array('desktop_wxwork');

    /**
     * 是否自动提交表单 1==>是
     * @var int
     */
    public $auto_submit = 0;

    /**
     * 自动提交表单 channel
     *
     * @var array
     */
//    public $auto_submit_channel = array('aiguanhuai');

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
        return '微信WAP适配PC端扫码支付 支付配置信息';
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
            'is_general'=>array(
                'title'=>app::get('ectools')->_('通用支付(是否为缺省通用支付)'),
                'type'=>'radio',
                'options'=>array('0'=>app::get('ectools')->_('否'),'1'=>app::get('ectools')->_('是')),
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
        return app::get('ectools')->_('渠道 我买网微信小程序内 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array |提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        kernel::single('weixin_payment_plugin_pcwxpay')->dopay($payment);
    }
    protected function get_html(){

    }
    /**
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
       exit('deny');
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