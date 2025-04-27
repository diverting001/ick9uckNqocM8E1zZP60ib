<?php

/**
 * 农行网上支付H5
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/12/28
 * Time: 15:25
 */
final class wap_payment_plugin_mabcchinahtml extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '农行H5支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '农行H5支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mabcchinahtml';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mabcchinahtml';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '农行H5支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mabcchinahtml_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mabcchinahtml', 'callback');
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
        return '农行H5网上支付配置信息';
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
                'title'=>app::get('ectools')->_('商户号'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pfx_file'=>array(
                'title'=>app::get('ectools')->_('pfx证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pfx_file_pass'=>array(
                'title'=>app::get('ectools')->_('pfx证书密码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'trustPay_file'=>array(
                'title'=>app::get('ectools')->_('网上支付证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'query_url'=>array(
                'title'=>app::get('ectools')->_('交易查询URL'),
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
        return app::get('ectools')->_('农行H5网上支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //设定订单属性
        $order['PayTypeID'] = 'ImmediatePay';//交易类型 直接支付
        $order['OrderNo'] = $payment['payment_id'];//订单编号

        $spend = time()-$payment['create_time'];
        $expire = 2380-$spend;
        if($expire>0){
            $data_str = date('YmdHis',time()+$expire);
        } else {
            $data_str = date('YmdHis',time()+300);
        }
        $order['orderTimeoutDate'] = $data_str;//支付有效期 精确到s * 选择性输入
        $order['OrderAmount'] = number_format($payment['cur_money'],2,".","");//订单编号
        $order['CurrencyCode'] = '156';//交易币种 156 人民币
        $order['InstallmentMark'] = '0';//分期标志 1分期 0 不分期
        $order['OrderDate'] = date('Y/m/d');//订单日期 YYYY/MM/DD
        $order['OrderTime'] = date('H:m:s');//交易时间 HH:MM:SS
        $order['CommodityType']= '0202';//商品种类
        $pay_obj = new stdClass();

        $pay_obj->order = $order;
//        print_r($pay_obj);die;
        // 充值类 0101:支付账户充值
        // 消费类 0201:虚拟类,0202:传统类,0203:实名类 TODO 这个怎么选择
        // 转账类 0301:本行转账,0302:他行转账
        // 缴费类 0401:水费,0402:电费,0403:煤气费,0404:有线电视费,0405:通讯费, 0406:物业费,0407:保险费,0408:行政费用,0409:税费,0410:学费,0499:其他
        // 理财类 0501:基金,0502:理财产品,0599:其他

        //订单明细加入订单中
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $products = array();
        foreach ($order_info['items'] as $k => $v) {
            $products[$k]['ProductName'] = $v['name'];
        }
        $pay_obj->orderitems = $products;//商品名称
        //支付请求对象的属性
        $request['TrxType'] = 'PayReq';
        $request['PaymentType'] = 'A';//支付类型  1：农行卡支付 2：国际卡支付 3：农行贷记卡支付 5：基于第三方的跨行支付 6：银联跨行支付 7：对公户 A:支付方式合并 TODO 用户是否可以自己修改
        $request['PaymentLinkType'] = '2';//交易渠道 	1：internet网络接入  2：手机网络接入  3：数字电视网络接入  4：智能客户端 TODO 用户是否可以自己修改
        $request['NotifyType'] = '1';//交易渠道 	0 URL通知 1 服务器通知 TODO 是否可以都通知？
        $request['ResultNotifyURL'] = $this->notify_url;//通知URL地址
        $request['IsBreakAccount'] = '0';//交易是否分账 1是 0 否
        $pay_obj->request = $request;//商品名称
        $req_msg = $this->getRequestMessage($pay_obj);
        $tSignature = $this->genSignature($req_msg);

        echo $this->get_html($tSignature);
        die;
    }


    /// 发送交易报文至网上支付平台
    private function request($tURL,$aMessage)
    {
        //组成<MSG>段
        $tMessage = strval($aMessage);
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'user_agent' => 'TrustPayClient V3.0.0',
                'protocol_version' => 1.0,
                'header' => array('Content-Type: text/html', 'Accept: */*'),
                'content' => $tMessage
            ),
            'ssl' => array(
                'verify_peer' => false
            )
        );
        $context = stream_context_create($opts);
        $tResponseMessage = file_get_contents($tURL, false, $context);
        return $tResponseMessage;

    }

    /**
     * 获取请求消息
     * @param $obj | req消息
     * @return string
     */
    public function getRequestMessage($obj) {
        $js = '"Order":' . (json_encode(($obj->order)));
        $js = substr($js, 0, -1);
        $js = $js . ',"OrderItems":[';
        $count = count($obj->orderitems, COUNT_NORMAL);
        for ($i = 0; $i < $count; $i++) {
            $js = $js . json_encode($obj->orderitems[$i]);
            if ($i < $count -1) {
                $js = $js . ',';
            }
        }
        $js = $js . ']}}';
        $tMessage = json_encode($obj->request);
        $tMessage = substr($tMessage, 0, -1);
        $tMessage = $tMessage . ',' . $js;
        return $tMessage;
    }

    /**
     * 组成完整交易报文
     * @param aMessage |交易报文
     * @throws TrxException：报文内容不合法
     * @return 完整交易报文
     */
    private function composeRequestMessage($aMessage) {
        $tMessage = "{\"Version\":\"V3.0.0\",\"Format\":\"JSON\",\"Merchant\":" . "{\"ECMerchantType\":\"" . "EBUS" . "\",\"MerchantID\":\"" . $this->getConf('mer_id',__CLASS__) . "\"}," . "\"TrxRequest\":" . $aMessage . "}";
        return $tMessage;
    }


    private function der2pem($der_data) {
        $pem = chunk_split(base64_encode($der_data), 64, "\n");
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
//        echo $pem;die;
        return $pem;
    }



    public function signMessage($aMessage) {
        //1、读取商户证书
        $tMerchantCertFile = $this->getConf('pfx_file',__CLASS__);
        $tMerchantCertPassword = $this->getConf('pfx_file_pass',__CLASS__);
        //2、读取证书
        if (openssl_pkcs12_read(file_get_contents($tMerchantCertFile), $tCertificate, $tMerchantCertPassword)) {
            //3、验证证书是否在有效期内
            $cer = openssl_x509_parse($tCertificate['cert']);
            $t = time();
            if ($t < $cer['validFrom_time_t'] || $t > $cer['validTo_time_t']) {
                \Neigou\Logger::General('sign.mabcchinahtml.pfx_file', array('remark' => 'pfx证书过期'));
            }
            //4、取得密钥
            $pkey = openssl_pkey_get_private($tCertificate['pkey']);
            if (!$pkey) {
                \Neigou\Logger::General('sign.mabcchinahtml.pfx_file', array('remark' => '无法生成私钥证书对象'));
            }
        } else {
            \Neigou\Logger::General('sign.mabcchinahtml.pfx_file', array('remark' => '证书读取失败'));
        }
        $signature = '';
        $data = strval($aMessage);
        if (!openssl_sign($data, $signature, $pkey, OPENSSL_ALGO_SHA1)) {
            return null;
        }

        $signature = base64_encode($signature);
        $tMessage = "{\"Message\":$data" . "," . '"Signature-Algorithm":' . '"SHA1withRSA","Signature":"' . $signature . '"}';
//        $tMessage = str_replace('"', "&quot;", $tMessage);//TODO 网站提交需
        return $tMessage;
    }

    public function genSignature($tRequestMessage) {
        $tRequestMessage = $this->composeRequestMessage($tRequestMessage);
        //4、对交易报文进行签名
        $sign_str = $this->signMessage($tRequestMessage);
        \Neigou\Logger::General('pay.mabcchinahtml',array('remark'=>'交易报文','req_str'=>$tRequestMessage,'sign_str'=>$sign_str));
        return $sign_str;
    }

    public function GetValue($aTag,$json)
    {
        $index = 0;
        $length = 0;
        $index = strpos($json, $aTag, 0);
        if ($index === false)
            return "";
        do
        {
            if($json[$index-1] === "\"" && $json[$index+strlen($aTag)] === "\"")
            {
                break;
            }
            else
            {
                $index = strpos($json, $aTag, $index+1);
                if ($index === false)
                    return "";
            }
        } while (true);
        $index = $index + strlen($aTag) + 2;
        $c = $json[$index];
        if ($c === '{')
        {
            $output = $this->GetObjectValue($index, $json);
        }
        if ($c === '"')
        {
            $output = $this->GetStringValue($index, $json);
        }
        return $output;
    }

    /// <summary> 回传文件中的信息
    /// </summary>
    /// <param name="aTag">域名
    /// </param>
    /// <returns> 指定域的object值
    ///
    /// </returns>
    private function GetObjectValue($index, $json)
    {
        $count = 0;
        $_output = "";
        do
        {
            $c = $json[$index];
            if ($c === '{')
            {
                $count++;
            }
            if ($c === '}')
                $count--;

            if ($count !== 0)
            {
                $_output =$_output.$c;
            }
            else
            {
                $_output = $_output.$c;
                return $_output;
            }
            $index++;
        } while (true);
    }
    /// <summary> 回传文件中的信息
    /// </summary>
    /// <param name="aTag">域名
    /// </param>
    /// <returns> 指定域的值
    ///
    /// </returns>
    private function GetStringValue($index, $json)
    {
        $index++;
        $_output = "";
        do
        {
            $c = $json[$index++];
            if ($c !== '"')
            {
                $_output = $_output.$c;
            }
            else
            {
                return $_output;
            }

        } while (true);
    }






    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        //获取订单号
        $payment_id = $recv['sn'];
        //查询是否已经付款
        $field['TrxType'] = 'Query';
        $field['PayTypeID'] = 'ImmediatePay';
        $field['OrderNo'] = $payment_id;
        $field['QueryDetail'] = '1';
        $req_msg = json_encode($field);
        $tSignature = $this->genSignature($req_msg);
        $url = $this->getConf('query_url',__CLASS__);
        $res = $this->request($url,$tSignature);
        \Neigou\Logger::General('ecstore.callback.mabcchinahtml',array('remark'=>'请求参数','field'=>json_encode($field),'tSignature'=>$tSignature,'response_data'=>$res));
        $pay_status = $this->GetValue('ReturnCode',$res);
        if($this->is_return_vaild($res) && $pay_status=='0000'){
            //获取订单详细信息
            $order = $this->GetValue('Order',$res);
            $str =  base64_decode($order);
            $str = iconv('GB2312','UTF-8',$str);
            $rzt = json_decode($str,true);
            $ret['payment_id'] = $rzt['OrderNo'];
            $ret['account'] = $this->getConf('mer_id',__CLASS__);
            $ret['bank'] = app::get('ectools')->_('农行支付');
            $ret['pay_account'] = $rzt['AcctNo'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $rzt['OrderAmount'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['OrderAmount'];
            $ret['trade_no'] = $recv['iRspRef'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mabcchinahtml";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            \Neigou\Logger::General('ecstore.callback.mabcchinahtml', array('remark' => 'trade_succ', 'data' => $ret));
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mabcchinahtml',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
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
    public function get_html($tSignature) {
        $encodeType =  'utf-8';
        $html = <<<eot
<HTML>
<HEAD><TITLE>ABChina Wait</TITLE></HEAD>
<BODY BGCOLOR='#FFFFFF' TEXT='#000000' LINK='#0000FF' VLINK='#0000FF' ALINK='#FF0000'>
<form id="pay_form" method="post" action="{$this->submit_url}">
<input type="hidden" name="MSG" value='$tSignature'>
<input type="hidden" name="errorPage" value="{$this->callback_url}">
</form>
</BODY></HTML>
<script type="text/javascript">
						window.onload=function(){
							document.getElementById("pay_form").submit();
						}
					</script>
eot;

        return $html;
    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($aMessage) {
        $tTrxResponse = $this->GetValue('Message',$aMessage);
        $tSignBase64 = $this->GetValue('Signature',$aMessage);
        $tSign = base64_decode($tSignBase64);
        $iTrustpayCertificate = openssl_x509_read($this->der2pem(file_get_contents($this->getConf('trustPay_file','wap_payment_plugin_mabcchina'))));
        $key = openssl_pkey_get_public($iTrustpayCertificate);
        $data = strval($tTrxResponse);
        if (openssl_verify($data, $tSign, $key, OPENSSL_ALGO_SHA1) == 1) {
            return true;
        } else {
            return false;
        }
    }
}