<?php

/**
 * 我买网对接 支付宝支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2019/08/27
 * Time: 15:38
 */
final class wap_payment_plugin_mqalipaywomai extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '中粮 专用 支付宝支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '中粮 专用 支付宝支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mqalipaywomai';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mqalipaywomai';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '中粮 专用 支付宝支付';
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
    public $display_env = array('h5','wxwork');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = 'https://q.womai.com/openapi/ectools_payment/parse/b2c/ectools_payment_plugin_qalipaywomai_server/callback/';
        $this->callback_url = 'https://q.womai.com/openapi/ectools_payment/parse/b2c/ectools_payment_plugin_qalipaywomai/callback/';
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
        return '中粮 专用 支付宝支付 支付配置信息';
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
            'mer_id'=>array(
                'title'=>app::get('ectools')->_('商户代码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_id'=>array(
                'title'=>app::get('ectools')->_('app_id'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pay_code'=>array(
                'title'=>app::get('ectools')->_('pay_code'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'md52_key'=>array(
                'title'=>app::get('ectools')->_('md5 openid Key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),

            'md5_key'=>array(
                'title'=>app::get('ectools')->_('md5 Key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'payment_code'=>array(
                'title'=>app::get('ectools')->_('goto_cashier 跳转收银台 其他为具体支付方式app_id'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sha1_key'=>array(
                'title'=>app::get('ectools')->_('sha1 Key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'code'=>array(
                'title'=>app::get('ectools')->_('活动代码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'query_url'=>array(
                'title'=>app::get('ectools')->_('query_url'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'return_url'=>array(
                'title'=>app::get('ectools')->_('return_url'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'return_query_url'=>array(
                'title'=>app::get('ectools')->_('return_url'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_prefix'=>array(
                'title'=>app::get('ectools')->_('order_prefix'),
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
        return app::get('ectools')->_('渠道 我买网微信内 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array |提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $timestamp = date('Y-m-d H:i:s');


        $this->add_field('merchantId',$this->getConf('mer_id',__CLASS__));//支付平台分配给商户的唯一标识
        $this->add_field('charset','UTF-8');//请求使用的编码格式
        $this->add_field('signType','MD5');//商户生成签名字符串所使用的签名算法类型，目前仅支持MD5

        $this->add_field('timestamp',$timestamp);//发送请求的时间，格式"yyyy-MM-dd HH:mm:ss"
        $payment_code = $this->getConf('payment_code',__CLASS__);
        //如果是goto_cashier 直接跳转到收银台
        if($payment_code != 'goto_cashier'){
            $this->add_field('paymodeCode',$this->getConf('payment_code',__CLASS__));//支付方式编码，通过此来区分是何种支付方式；非必填项，如果不填那么会调起支付平台支付列表页（支付列表页逻辑同5.1接口），填写的话，会调起对应的支付界面
        }
        $this->add_field('outTradeNo','QYDD'.$payment['payment_id']);//商户网站唯一订单号
        $this->add_field('subject','QYDD'.$payment['payment_id']);//商品的标题/交易标题/订单标题/订单关键字等
        $this->add_field('totalAmount',number_format($payment['cur_money'],2,".",""));//订单总金额，单位为元，精确到小数点后两位，取值范围[0.01,100000000]
        $this->add_field('returnUrl',$this->callback_url);//支付结果前台通知接口地址
        $this->add_field('notifyUrl',$this->notify_url);//支付结果异步通知接口地址
        $this->add_field('sign',$this->genSign($this->fields));//商户请求参数的签名串

        $html = $this->get_html();
        \Neigou\Logger::General('pay.mqalipaywomai.req',array('remark'=>'form','html'=>$html,'data'=>$this->fields));
        exit($html);
    }

    protected function get_html(){
        $json = json_encode($this->fields);
        header("Content-Type: text/html;charset=".$this->submit_charset);
        $strHtml ="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
		<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\" dir=\"ltr\">
		<head>
		</head><body><div>Redirecting...</div>";
        $strHtml .= '<form action="' . $this->submit_url . '" method="' . $this->submit_method . '" name="pay_form" id="pay_form">';
        $strHtml .= '<input type="hidden" name="data" value=\'' . $json . '\' />';
        $strHtml .= '<input type="submit" name="btn_purchase" value="提交" />';
        $strHtml .= '</form><script type="text/javascript">
						window.onload=function(){
							document.getElementById("pay_form").submit();
						}
					</script>';
        $strHtml .= '</body></html>';
        return $strHtml;
    }


    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $linkStr = $this->_create_link_string($data,true,false);
        $linkStr .= 'key='.$this->getConf('md5_key',__CLASS__);
        \Neigou\Logger::General('ecstore.mqalipaywomai.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
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


    public function request($api = '', $post_data = array(),$is_post=true) {
        $curl = new \Neigou\Curl();
        $proxyServer = NEIGOU_HTTP_PROXY;
        $opt_config = array(
            CURLOPT_FAILONERROR => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
            CURLOPT_PROXY => $proxyServer,
        );
        $curl->SetOpt($opt_config);
        $req['data'] = json_encode($post_data);
        if($is_post){
            $result = $curl->Post($api, $req);
        } else {
            $result = $curl->Get($api,$req);
        }
        \Neigou\Logger::General('ecstore.mqalipaywomai.req', array('action' => 'do req','req_url'=>$api,'post_data'=>$post_data,'response_data'=>$result));
        $resultData = json_decode($result, true);
        return $resultData;
    }

    /**
     * 此接口不作为更改支付状态的依据，请以4.4支付结果异步通知或4.5支付查询结果为准
     * 商户收到同步通知，对参数进行验签，验证成功后，如果outTradeNo、tradeNo、bankTradeNo有值，即认为交易成功，商户可展示支付成功页面。
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        $recv = json_decode($recv['data'],true);
        if($this->is_return_vaild($recv)){
            //判断必要参数
            if($recv['outTradeNo'] && $recv['tradeNo'] && $recv['bankTradeNo']){
                //进行查询
                $info = $this->queryOrder($recv['outTradeNo']);
                if($info){
                    $payment_id = str_replace('QYDD','',$recv['outTradeNo']);
                    $ret['payment_id'] = $payment_id;
                    $ret['account'] = $this->getConf('app_id',__CLASS__);
                    $ret['bank'] = app::get('ectools')->_('中粮企业支付');
                    $ret['pay_account'] = $recv['paymodeCode'];
                    $ret['currency'] = 'CNY';
                    $ret['money'] = $recv['totalAmount'];
                    $ret['paycost'] = '0.000';
                    $ret['cur_money'] = $recv['totalAmount'];
                    $ret['trade_no'] = $recv['tradeNo'];
                    $ret['t_payed'] = time();
                    $ret['pay_app_id'] = "mqalipaywomai";
                    $ret['pay_type'] = 'online';
                    $ret['status'] = 'succ';
                    \Neigou\Logger::General('ecstore.callback.mqalipaywomai', array('remark' => 'trade_succ', 'data' => $ret));

                } else {
                    $ret['status'] = 'invalid';
                    \Neigou\Logger::General('ecstore.callback.mqalipaywomai',array('remark'=>'not_pay','data'=>$recv));
                }

            } else {
                $ret['status'] = 'invalid';
                \Neigou\Logger::General('ecstore.callback.mqalipaywomai',array('remark'=>'trade_err','data'=>$recv));
            }
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mqalipaywomai',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
    }

    public function queryOrder($out_trade_no){
        $timestamp = date('Y-m-d H:i:s');
        $req['merchantId'] = $this->getConf('mer_id',__CLASS__);
        $req['charset'] = 'UTF-8';
        $req['signType'] = 'MD5';
        $req['timestamp'] = $timestamp;
        $req['outTradeNo'] = $out_trade_no;
        $req['sign'] = $this->genSign($req);
        $response = $this->request($this->getConf('query_url',__CLASS__),$req,false);
        $response['data'] = json_decode($response['data'],true);
        if(isset($response['response']['tradeNo']) && $response['code'] = 'SUCCESS' && $response['response']['tradeStatus']=='TRADE_SUCCESS'){
            return $response['response'];
        } else {
            return false;
        }
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
     * 检验返回数据合法性
     * @param $param
     * @access private
     * @return boolean
     */
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $req_sign = $this->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
}