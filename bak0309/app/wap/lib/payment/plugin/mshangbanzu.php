<?php

/**
 * 上班族支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/11/13
 * Time: 15:39
 */
final class wap_payment_plugin_mshangbanzu extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '上班族支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '上班族支付接口';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mshangbanzu';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mshangbanzu';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '上班族支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mshangbanzu_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mshangbanzu', 'callback');
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
        return '上班族支付配置信息';
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
            'appid'=>array(
                'title'=>app::get('ectools')->_('上班族提供的App id'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'secret' =>array(
                'title'=>app::get('ectools')->_('上班族提供的secret'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'base_url' =>array(
                'title'=>app::get('ectools')->_('接口地址'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_url'=>array(
                'title'=>app::get('ectools')->_('统一下单地址'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('支付地址'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
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
        return app::get('ectools')->_('上班族支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $this->add_field('appid',$this->getConf('appid',__CLASS__));
        $this->add_field('unique_order',$payment['payment_id']);
        $this->add_field('price',number_format($payment['cur_money'],2,".","")*100);
        $this->add_field('body','内购-产品');
        $this->add_field('callback_url',$this->notify_url);
        $this->add_field('jump_url',$this->callback_url);
        $param = $this->fields;
        $param['secret'] = $this->getConf('secret',__CLASS__);
        $str = $this->_create_link_string($param,true,true);
        $this->add_field('token',md5($str));
        $curl = new \Neigou\Curl();
        $gen_url = $this->getConf('base_url',__CLASS__).$this->getConf('order_url',__CLASS__);
        $result = $curl->Post($gen_url, $this->fields);
        $resultData = json_decode($result, true);
        if($resultData['success']==true){
            $dense_str = $resultData['data']['dense_str'];
            //执行支付请求
            $url = $this->getConf('base_url',__CLASS__).$this->submit_url.$dense_str;
            header('Location:'.$url);
        } else {
            \Neigou\Logger::General('pay.mshangbanzu',array('remark'=>'get dense_str fail','rzt'=>$resultData));
        }
        exit;
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        //由于没有实际可验证的参数 所以直接跳转到订单详情
        //通过payment_id查询order_id 并跳转到
//        $billList = app::get('ectools')->model('order_bills')->getList('*',array('bill_id'=>$recv['order_id']));
//        $order_id = $billList[0]['rel_id'];
//        http://test.wuchuanbin.dev.neigou.com/m/paycenter2-result_pay-15108107376778.html
        header('Location:/m/paycenter2-result_pay-'.$recv['orderId'].'.html');
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

    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function get_html() {
        $encodeType =  'utf-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">

eot;
        foreach ( $this->fields as $key => $value ) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
eot;
        return $html;
    }

    /**
     * 将数组转换成String
     * @param $para array 参数
     * @param $sort bool 是否排序
     * @param $encode string 是否urlencode
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
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
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


}