<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/**
 * alipay notify 异步验证接口
 * @auther shopex ecstore dev dev@shopex.cn
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mljalipay2_server extends ectools_payment_app {

    /**
     * @支付宝固定参数
     */
    public $sec_id = 'MD5';    //签名方式 不需修改
    public $_input_charset = 'utf-8';    //字符编码格式
    public $_input_charset_GBK = "GBK";
    public $v = '2.0';    //版本号
    public $gateway_paychannel="https://mapi.alipay.com/cooperate/gateway.do?";
    public $gateway="http://wappaygw.alipay.com/service/rest.htm?";

    public $supportCurrency = array("CNY"=>"01");
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mljalipay2';


    public $submit_url = 'https://openapi.alipay.com/gateway.do';
    public $submit_method = 'POST';
    public $submit_charset = 'UTF-8';
    /**
     * @var string 销售产品码，与支付宝签约的产品码名称。 注：目前仅支持FAST_INSTANT_TRADE_PAY
     */
    public $product_code = 'FAST_INSTANT_TRADE_PAY';
    public $sign_type = 'RSA2';

    public $rsa_public_key = '';
    public $rsa_private_key = '';
    public $zfb_public_key = '';
    private $seller_id = '';
    public $app_id = '';
	/**
	 * 支付后返回后处理的事件的动作
	 * @params array - 所有返回的参数，包括POST和GET
	 * @return null
	 */
    public function callback(&$recv){

        $seller_id_save = $this->getConf('seller_id', 'wap_payment_plugin_mljalipay2');
        if(!empty($seller_id_save)){
            $this->seller_id = $seller_id_save;
        }
        $rsa_public_key_save = $this->getConf('rsa_public_key', 'wap_payment_plugin_mljalipay2');
        if(!empty($rsa_public_key_save)){
            $this->rsa_public_key = $rsa_public_key_save;
        }
        $rsa_private_key_save = $this->getConf('rsa_private_key', 'wap_payment_plugin_mljalipay2');
        if(!empty($rsa_private_key_save)){
            $this->rsa_private_key = $rsa_private_key_save;
        }
        $zfb_public_key_save = $this->getConf('zfb_public_key', 'wap_payment_plugin_mljalipay2');
        if(!empty($zfb_public_key_save)){
            $this->zfb_public_key = $zfb_public_key_save;
        }
        $app_id_save = $this->getConf('app_id', 'wap_payment_plugin_mljalipay2');
        if(!empty($app_id_save)){
            $this->app_id = $app_id_save;
        }
        \Neigou\Logger::General('ecstore.notify.mljalipay2', array('action' => 'mrecv', 'data' => json_encode($recv),'data2'=>json_encode($GLOBALS["HTTP_RAW_POST_DATA"])));
        $ret['callback_source'] = 'server';
        //键名与pay_setting中设置的一致
        if($this->rsaCheck($recv,$this->getConf('sign_type','wap_payment_plugin_mljalipay2'))){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $this->seller_id;
            $ret['bank'] = app::get('wap')->_('手机支付宝');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_amount'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_amount'];
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time();
            $ret['pay_app_id'] = $this->app_key;
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';

            $status = $recv['trade_status'];        //返回token
            if($status == 'TRADE_FINISHED' || $status == 'TRADE_SUCCESS'){
                echo "success";
                $ret['status'] = 'succ';
                \Neigou\Logger::General('ecstore.notify.mljalipay2', array('action' => 'trade_succ', 'data' => json_encode($recv)));
            }else{
                \Neigou\Logger::General('ecstore.notify.mljalipay2', array('action' => 'trade_status_err', 'data' => json_encode($recv)));
                echo "fail";
                $ret['status'] = 'failed';
            }
        }else{
            \Neigou\Logger::General('ecstore.notify.mljalipay2', array('action' => 'sign_err', 'data' => json_encode($recv)));
            $ret['message'] = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }
        return $ret;
    }



    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {

        if (!empty($data)) {
            $fileType = $this->submit_charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    public function getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->submit_charset);

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
    /** rsaCheckV1 & rsaCheckV2
     *  验证签名
     **/
    public function rsaCheck($params,$signType='RSA') {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;
        $ret = $this->verify($this->getSignContent($params), $sign,$signType);
        if(!$ret){
            \Neigou\Logger::General('ecstore.notify.mljalipay2', array('action' => 'trade_start', 'data' => json_encode($params),'data2'=>$signType,'sparam1'=>json_encode($this->zfb_public_key)));
        }
        return $ret;
    }
    function verify($data, $sign, $signType = 'RSA') {

        $pubKey= $this->zfb_public_key;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";


        if(!$res){
            return false;
        }

        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, 'sha256');
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
        if(!$this->checkEmpty($this->zfb_public_key)) {
            //释放资源
            openssl_free_key($res);
        }
        return $result;
    }
}
