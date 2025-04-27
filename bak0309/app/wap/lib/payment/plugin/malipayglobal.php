<?php

/**
 * alipay global 境外收单
 * @auther nishaoliang
 * @package ectools.lib.payment.plugin
 */
final class wap_payment_plugin_malipayglobal extends ectools_payment_app implements ectools_interface_payment_app {

    /**
     * @var string 支付方式名称
     */
    public $name = '手机支付宝国际支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '手机支付宝国际支付接口';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'malipayglobal';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'malipayglobal';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '手机支付宝(国际)';
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
    public $platform = 'isglobalwap';
    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"01");
    /**
     * @var string 通用支付
     */
    public $is_general = 1;

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        logger::info("imoagn-malipayglobal_create");
        parent::__construct($app);

        //$this->callback_url = $this->app->base_url(true)."/apps/".basename(dirname(__FILE__))."/".basename(__FILE__);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_malipay_server', 'globalcallback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches))
        {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        }
        else
        {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_malipayglobal', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches))
        {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        }
        else
        {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }

        $alipay_env = $this->getConf('alipay_env', __CLASS__);#$config['real_method'];

        $submit_url = 'https://openapi.alipaydev.com/gateway.do?_input_charset=utf-8';

        switch ($alipay_env){
            case '0':
                $submit_url = 'https://openapi.alipaydev.com/gateway.do?_input_charset=utf-8';
                break;
            case '1':
                $submit_url = 'https://mapi.alipay.com/gateway.do?_input_charset=utf-8';
                break;
        }

        //$this->submit_url = 'https://www.alipay.com/cooperate/gateway.do?_input_charset=utf-8';
        //ajx  按照相应要求请求接口网关改为一下地址
        $this->submit_url = $submit_url;
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        $regIp = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['HTTP_HOST'];
        return '<img src="' . $this->app->res_url . '/payments/images/ALIPAY.gif"><br /><b style="font-family:verdana;font-size:13px;padding:3px;color:#000"><br>手机支付宝国际版</b><div style="padding:10px 0 0 388px"><a  href="javascript:void(0)" onclick="document.ALIPAYFORM.submit();"><img src="' . $this->app->res_url . '/payments/images/alipaysq.png"></a></div><div>如果您已经和支付宝签约了其他套餐，同样可以点击上面申请按钮重新签约，即可享受新的套餐。<br>如果不需要更换套餐，请将签约合作者身份ID等信息在下面填写即可，<a href="http://www.shopex.cn/help/ShopEx48/help_shopex48-1235733634-11323.html" target="_blank">点击这里查看使用帮助</a><form name="ALIPAYFORM" method="GET" action="http://top.shopex.cn/recordpayagent.php" target="_blank"><input type="hidden" name="postmethod" value="GET"><input type="hidden" name="payagentname" value="支付宝"><input type="hidden" name="payagentkey" value="ALIPAY"><input type="hidden" name="market_type" value="from_agent_contract"><input type="hidden" name="customer_external_id" value="C433530444855584111X"><input type="hidden" name="pro_codes" value="6AECD60F4D75A7FB"><input type="hidden" name="regIp" value="'.$regIp.'"><input type="hidden" name="domain" value="'.$this->app->base_url(true).'"></form></div>';
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
                'title'=>app::get('ectools')->_('合作者身份(parterID)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'mer_key'=>array(
                'title'=>app::get('ectools')->_('交易安全校验码(key)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'support_cur'=>array(
                'title'=>app::get('ectools')->_('支持币种'),
                'type'=>'text hidden cur',
                'options'=>$this->arrayCurrencyOptions,
            ),
            'alipay_env'=>array(
                'title'=>app::get('ectools')->_('支付宝环境'),
                'type'=>'select',
                'options'=>array('0'=>app::get('ectools')->_('支付宝测试环境(需配HOST)'),
                                 '1'=>app::get('ectools')->_('支付宝正式环境')),
                'tip'=>'',
            ),
            'real_method'=>array(
                'title'=>app::get('ectools')->_('选择接口类型'),
                'type'=>'select',
                'options'=>array('0'=>app::get('ectools')->_('使用标准境外收单')),
                'tip'=>'',
            ),
            'pay_fee'=>array(
                'title'=>app::get('ectools')->_('交易费率'),
                'type'=>'pecentage',
                'validate_type' => 'number',
            ),
            'pay_timeout'=>array(
                'title'=>app::get('ectools')->_('超时时间'),
                'type'=>'select',
                'options'=>array('5m'=>app::get('ectools')->_('5m')),
                'options'=>array('10m'=>app::get('ectools')->_('10m')),
                'options'=>array('15m'=>app::get('ectools')->_('15m')),
                'options'=>array('30m'=>app::get('ectools')->_('30m')),
                'options'=>array('1h'=>app::get('ectools')->_('1h')),
                'options'=>array('2h'=>app::get('ectools')->_('2h')),
            ),
            'pay_brief'=>array(
                'title'=>app::get('ectools')->_('支付方式简介'),
                'type'=>'textarea',
            ),
            'pay_desc'=>array(
                'title'=>app::get('ectools')->_('描述'),
                'type'=>'html',
                'includeBase' => true,
            ),
            'pay_type'=>array(
                'title'=>app::get('ectools')->_('支付类型(是否在线支付)'),
                'type'=>'hidden',
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
        return app::get('ectools')->_('支付宝（中国）网络技术有限公司是国内领先的独立第三方支付平台，由阿里巴巴集团创办。支付宝致力于为中国电子商务提供“简单、安全、快速”的在线支付解决方案。').'
<a target="_blank" href="https://www.alipay.com/static/utoj/utojindex.htm">'.app::get('ectools')->_('如何使用支付宝支付？').'</a>';
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment)
    {
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $mer_id = $mer_id;
        $mer_key = $this->getConf('mer_key', __CLASS__);
        $mer_key = $mer_key;

        $subject = $payment['account'].$payment['payment_id'];
        $subject = str_replace("'",'`',trim($subject));
        $subject = str_replace('"','`',$subject);


        $real_method = $this->getConf('real_method', __CLASS__);#$config['real_method'];

        switch ($real_method){
            case '0':
                $this->add_field('service', 'create_forex_trade_wap');#form['service'] = 'create_forex_trade';
                break;
        }

        $pay_timeout = $this->getConf('pay_timeout', __CLASS__);
        if (empty($pay_timeout)){
            $pay_timeout = '1h';
        }


        $rmb_fee = number_format($payment['cur_money'],2,".","");

        //nishaoliang: pass params across return/notify url requests, alipayglobal do not tell us
        //how mush RMB has been payed,pass here rather than read DB to avoid consider about consistency
        $RMB_passby = "passby_rmb_fee=".$rmb_fee;

        $passby_sign = md5($RMB_passby.$mer_key);

        $passby .= $RMB_passby."&passby_sign=".$passby_sign;
        $passby = base64_encode($passby);

        $this->add_field('partner',$mer_id);
        $this->add_field('return_url',$this->callback_url.$passby);
        $this->add_field('notify_url',$this->notify_url.$passby);
        #$this->add_field('notify_url',"http://xxx.xx.com/sdfsf");
        if (isset($payment['subject']) && $payment['subject'])
            $this->add_field('subject',$payment['subject']);
        else
            $this->add_field('subject',$subject);
        if (isset($payment['body']) && $payment['body'])
            $this->add_field('body', urlencode($payment['body']));
        else
            $this->add_field('body', urlencode(app::get('ectools')->_('网店订单')));
        $this->add_field('out_trade_no',$payment['payment_id']);
        $this->add_field('currency', 'USD');
        $this->add_field('merchant_url', kernel::base_url(1).kernel::url_prefix().app::get('wap')->router()->gen_url(array('app'=>'b2c', 'ctl'=>'wap_store', 'act'=>'index')));        $this->add_field('rmb_fee', $rmb_fee);

        $spend = time()-$payment['create_time'];
        $expire = 2380-$spend;
        if($expire>0){
            $this->add_field('timeout_rule',$this->get_timeout_rule($expire));
        }

        $this->add_field('_input_charset','utf-8');
        $this->add_field('product_code','NEW_WAP_OVERSEAS_SELLER');
//		$this->add_field('timeout_rule', $pay_timeout);
        $this->add_field('sign',$this->_get_mac($mer_key));
        $this->add_field('sign_type','MD5');


        unset($this->fields['_input_charset']);

        logger::info("imoagn:alipay");

        if($this->is_fields_valiad())
        {
            // Generate html and send payment.
            echo $this->get_html();exit;
        }
        else
        {
            return false;
        }
    }

    /**
     * 根据支付宝国际支付设置超时参数
     * 参考取值范围：5m 10m 15m 30m 1h2h 3h 5h 10h 12h。 (忽略大小写) 默认为12h
     * @param int $time
     * @return string
     */
    public function get_timeout_rule($time=0){
        if($time>0){
            $rest = floor($time/60);
            if($rest>30){
                return '30m';
            } elseif($rest>15){
                return '15m';
            } elseif($rest>10){
                return '10m';
            } else {
                return '5m';
            }
        } else {
            return '5m';
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

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {

        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $mer_id = $mer_id == '' ? '2088002003028751' : $mer_id;
        $mer_key = $this->getConf('mer_key', __CLASS__);
        $mer_key = $mer_key=='' ? 'afsvq2mqwc7j0i69uzvukqexrzd0jq6h' : $mer_key;

        if($this->is_return_vaild($recv,$mer_key)){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('支付宝');
            $ret['pay_account'] = app::get('ectools')->_('付款帐号');
            $ret['currency'] = "CNY";
            $ret['money'] = $recv['passby_rmb_fee'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['passby_rmb_fee'];
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time();
            $ret['pay_app_id'] = "alipayglobal";
            $ret['pay_type'] = 'online';
            $ret['memo'] = $recv['body'];
            $ret['settle_payment'] = $recv['total_fee'];
            $ret['settle_currency'] = $recv['currency'];

            switch($recv['trade_status']){
                case 'WAIT_BUYER_PAY':
                    $ret['status'] = 'ready';
                    break;
                case 'TRADE_PENDING':
                    $ret['status'] = 'progress';
                    break;
                case 'TRADE_FINISHED':
                    $ret['status'] = 'succ';
                    break;
                case 'TRADE_SUCCESS':
                    $ret['status'] = 'succ';
                    break;
                case 'TRADE_CLOSED':
                    $ret['status'] = 'failed';
                    break;

            }

        }else{
            $message = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }

        return $ret;
    }


    function createLinkstringUrlencode($para) {

        $arg  = "";

        foreach ($this->fields as $key=>$val){
            $arg.=$key."=".urlencode($val)."&";
        }
        //去掉最后一个&字符
        $arg = substr($arg,0,count($arg)-2);

        //如果存在转义字符，那么去掉转义
        if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

        return $arg;
    }
    
    protected function get_html()
    {

        $real_submit_url = $this->submit_url . '&' . $this->createLinkstringUrlencode($this->fields);

        // 简单的form的自动提交的代码。
        header("Content-Type: text/html;charset=".$this->submit_charset);
        $strHtml ="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
		<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\" dir=\"ltr\">
		<head>
		</head><body><div>Redirecting...</div>";
        $strHtml .= '<form action="' . $real_submit_url . '" method="' . $this->submit_method . '" name="pay_form" id="pay_form">';

        $strHtml .= '<input type="submit" name="btn_purchase" value="'.app::get('ectools')->_('购买').'" style="display:none;" />';
        $strHtml .= '</form><script type="text/javascript">
						window.onload=function(){
							document.getElementById("pay_form").submit();
						}
					</script>';
        $strHtml .= '</body></html>';
        return $strHtml;
    }

    /**
     * 生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function gen_form()
    {
        $tmp_form='<a href="javascript:void(0)" onclick="document.applyForm.submit();">'.app::get('ectools')->_('立即申请支付宝').'</a>';
        $tmp_form.="<form name='applyForm' method='".$this->submit_method."' action='" . $this->submit_url . "' target='_blank'>";
        // 生成提交的hidden属性
        foreach($this->fields as $key => $val)
        {
            $tmp_form.="<input type='hidden' name='".$key."' value='".$val."'>";
        }

        $tmp_form.="</form>";

        return $tmp_form;
    }


    /**
     * 生成签名
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return string
     */
    public function _get_mac($key){
        ksort($this->fields);
        reset($this->fields);
        $mac= "";
        foreach($this->fields as $k=>$v){
            $mac .= "&{$k}={$v}";
        }
        $mac = substr($mac,1);
        $mac = md5($mac.$key);  //验证信息
        return $mac;
    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($form,$key)
    {
        ksort($form);
        foreach($form as $k=>$v){
            if($k!='sign'&&$k!='sign_type'&& strpos($k, 'passby_')!==0){
                $signstr .= "&$k=$v";
            }
        }

        foreach($form as $k=>$v){
            if(strpos($k, 'passby_')===0 && $k!="passby_sign"){
                $signstr_passby .= "&$k=$v";
            }
        }


        $signstr = ltrim($signstr,"&");
        $signstr = $signstr.$key;

        $signstr_passby = ltrim($signstr_passby,"&");
        $signstr_passby = $signstr_passby.$key;

        $alipay_sign_valid = $form['sign'] == md5($signstr);
        $inner_sign_valid = $form['passby_sign'] == md5($signstr_passby);

        $msg = "[alipay_params]".var_export($form, true);

        if ($alipay_sign_valid && $inner_sign_valid)
        {
            logger::logtestkv('ecstore.ectools.payment.success',
                array(
                    "pay_method"=>"alipayglobal",
                    "trade_no" => $form['out_trade_no'],
                    "from"=>"return_url",
                    "platform"=>"wap",
                    "remark"=>$msg
                ));

            return true;
        }

        #记录返回失败的情况

        logger::logtestkv('ecstore.ectools.payment.fail',
            array(
                "pay_method"=>"alipayglobal",
                "trade_no" => $form['out_trade_no'],
                "from"=>"return_url",
                "platform"=>"wap",
                "remark"=>$msg
            )
            ,LOG_SYS_ERR);

        return false;
    }

}
