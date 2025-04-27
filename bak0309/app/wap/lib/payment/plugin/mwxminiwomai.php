<?php

/**
 * 我买网对接 微信小程序 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mwxminiwomai extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '渠道 微信小程序 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '渠道 微信小程序 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mwxminiwomai';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mwxminiwomai';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '渠道 微信小程序 支付';
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
    public $display_env = array('weixin_program');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwxminiwomai_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwxminiwomai', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
        $this->submit_url = $this->getConf('submit_url', __CLASS__);
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '渠道 微信小程序 支付配置信息';
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
            'title'=>array(
                'title'=>app::get('ectools')->_('支付页面显示标题'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_prefix'=>array(
                'title'=>app::get('ectools')->_('支付单前缀'),
                'type'=>'string',
                'validate_type' => '',
            ),
            'md5_key'=>array(
                'title'=>app::get('ectools')->_('签名字符串'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('退款提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'query_refund_url'=>array(
                'title'=>app::get('ectools')->_('退款进度查询URL'),
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
        $this->add_field('outTradeNo', $this->getConf('order_prefix',__CLASS__).$payment['payment_id']);//交易订单编号
        $this->add_field('timestamp', time());//时间戳 精确到秒
        $this->add_field('sign',$this->genSign($this->fields));
        $this->add_field('orderId', $payment['order_id']);//订单编号
        $this->add_field('url_param', http_build_query($this->fields));
        echo $this->jsReturn();exit;
    }

    public function jsReturn(){
        $js = '
<script>

            function callWxPay() {
                wx.miniProgram.navigateTo({
                    url:\'/pages/wxpay/wxpay?'.$this->fields['url_param'].'\',
                    success: function(){
                        console.log(\'success\')
                    },
                    fail: function(){
                        console.log(\'跳转回小程序的页面fail\');
                    },
                });
            }    
            callWxPay();
</script>';
        $return['js'] = $js;
        $return['init'] = 'callpay';
        return json_encode($return);
    }


    protected function get_html(){
        $title = $this->getConf('title',__CLASS__);
        $strHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>微信支付</title>
    <script type="text/javascript" src="//res.wx.qq.com/open/js/jweixin-1.3.2.js"></script>
</head>
<body>
微信支付中，请稍后……
<script>
    function callWxZPay() {
        wx.miniProgram.navigateTo({
            url:\'/pages/wxpay/wxpay?'.$this->fields['url_param'].'\',
            success: function(){
                alert(\'succ\');
                console.log(\'success\')
            },
            fail: function(){
                console.log(\'跳转回小程序的页面fail\');
            },
        });
    }
    callWxZPay();
</script>
</body>
</html>';
        return $strHtml;
    }

    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $linkStr = $this->_create_link_string($data,true,true);
        $linkStr .= 'key='.$this->getConf('md5_key',__CLASS__);
        \Neigou\Logger::General('ecstore.mwxminiwomai.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
        return strtoupper(md5($linkStr));
    }

    /**
     * 组合字符串
     * @param $para
     * @param $sort
     * @param $encode
     * @return string
     */
    public function _create_link_string($para, $sort, $encode){
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if($sort){
            $para = $this->argSort ( $para );
        }
        while ( list ( $key, $value ) = each( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            $linkString .= $key . "=" . $value."&";
        }
        return $linkString;
    }

    /**
     * 数组排序
     * @param $para
     * @return mixed
     */
    function argSort($para) {
        ksort ( $para );
        reset ( $para );
        return $para;
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