<?php

/**
 * 麦当劳 麦麦商城支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mmcdonld extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '麦当劳 麦麦商城支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '麦当劳支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mmcdonld';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mmcdonld';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '麦当劳支付';
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

    public $display_env = array('joywok');
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mmcdonld_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mmcdonld', 'callback');
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
        return '麦当劳支付 使用内购支付宝参数';
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
            'app_id'=>array(
                'title'=>app::get('ectools')->_('app_id'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'alipay_app_id'=>array(
                'title'=>app::get('ectools')->_('alipay_app_id'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'alipay_account'=>array(
                'title'=>app::get('ectools')->_('alipay_account'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'corp_id'=>array(
                'title'=>app::get('ectools')->_('corp_id'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'js_url'=>array(
                'title'=>app::get('ectools')->_('jw_js URL'),
                'type'=>'string',
                'validate_type' => 'required',
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
        return app::get('ectools')->_('麦当劳支付 使用内购支付宝 malipay2 参数');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $timestamp = time();
        $data['app_id'] = $this->getConf('alipay_app_id',__CLASS__);
        $data['method'] = 'alipay.trade.app.pay';
        $data['format'] = 'JSON';
        $data['charset'] = 'utf-8';
        $data['sign_type'] = 'RSA2';
        $data['timestamp'] = date('Y-m-d H:i:s',$timestamp);
        $data['version'] = '1.0';
        $data['notify_url'] = $this->notify_url;

        $expire_time = 39;
        $spend = time()-$payment['create_time'];
        $expire = ceil($expire_time-$spend/60);

        $timeout_express = "{$expire}m";

        $biz_content['body'] = '内购网订单-'.$payment['payment_id'];
        $biz_content['subject'] = '内购网订单-'.$payment['payment_id'];
        $biz_content['out_trade_no'] = $payment['payment_id'];
        $biz_content['total_amount'] = number_format($payment['cur_money'],2,".","");
        $biz_content['product_code'] = 'QUICK_MSECURITY_PAY';
        $biz_content['timeout_express'] = $timeout_express;

        $data['biz_content'] = json_encode($biz_content,256);
        $sign_str = $this->getSignStr($data);

        $data['sign'] = $this->sign($sign_str,'RSA2');
        foreach ($data as $key=>$val){
            $data[$key] = rawurlencode($val);
        }
        $str = $this->getSignStr($data);
        echo $this->jw_pay($str,$payment['order_id']);
        die;
    }

    public function jw_pay($data,$order_id){
        $js_url = $this->getConf('js_url',__CLASS__);
        $app_id = $this->getConf('app_id',__CLASS__);
        $corp_id = $this->getConf('corp_id',__CLASS__);
        $success_url = app::get('wap')->router()->gen_url(array('app'=> 'b2c','ctl'=>'wap_paycenter2','act'=>'result_wait','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));
        $str = <<<EOF
        <html>
<head>
<title>支付中……</title>
<meta charset="UTF-8">
<script src="{$js_url}"></script>
<!--<script src="/vconsole.min.js"></script>-->
<script>
// var vConsole = new VConsole();
    jw.ready = function(){
        // console.log('$data');
	    setTimeout(function(){
                jw.aPay('{$data}',{
            success:function(resp){
                // console.log(resp);
	            window.location.href='{$success_url}';
            },
            fail:function(resp){
                 console.log(resp);
	            history.back();
	        }
        });
	        },2000)
            }
    jw.config({
        debug:true,
        appid:'{$app_id}',
        corpid:'{$corp_id}',
        timestamp:Date.parse(new Date())/1000,
    });
</script>
</head>

支付中……


</html>
EOF;
        return $str;

    }

    public function sign($data,$signType='RSA'){
        $priKey=$this->getConf('rsa_private_key','wap_payment_plugin_malipay2');
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if(!$res){
            return false;
        }
        if ("RSA2" == $signType) {
            $res = openssl_sign($data, $sign, $res,'sha256');
            if(!$res){
                \Neigou\Logger::General('ecstore.pay.mmcdonld.sign', array('action' => 'sign_err', 'data' => json_encode($data)));
                return false;
            }
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    public function getSignStr($params) {
        ksort($params);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        return false;
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