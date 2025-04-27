<?php

/**
 * 农行网上支付-佶宓
 * Class wap_payment_plugin_mabcjm
 */
final class wap_payment_plugin_mabcjm extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '农行网上支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '农行网上支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mabcjm';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mabcjm';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '农行网上支付';
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
    public $supportCurrency = array( "CNY" => "01" );

    public $ErrorCodeInfo = array(
        '0'=>'交易成功',
        '1000'=>'无法读取商户端配置文件',
        '1001'=>'商户端配置文件中参数设置错误',
        '1002'=>'无法读取商户证书文档',
        '1003'=>'无法读取商户私钥',
        '1004'=>'无法写入交易日志文档',
        '1005'=>'证书过期',
        '1006'=>'证书格式错误',
        '1100'=>'商户提交的交易资料不完整',
        '1101'=>'商户提交的交易资料不合法',
        '1102'=>'签名交易报文时发生错误',
        '1103'=>'无法连线签名服务器',
        '1104'=>'签名服务器返回签名错误',
        '1201'=>'无法连线网上支付平台',
        '1202'=>'提交交易时发生网络错误',
        '1203'=>'无法接收到网上支付平台的响应',
        '1204'=>'接收网上支付平台响应报文时发生网络错误',
        '1205'=>'无法辨识网上支付平台的响应报文',
        '1206'=>'网上支付平台服务暂时停止',
        '1301'=>'网上支付平台的响应报文不完整',
        '1302'=>'网上支付平台的响应报文签名验证失败',
        '1303'=>'无法辨识网上支付平台的交易结果',
        '1999'=>'系统发生无法预期的错误',
        '2000'=>'无法读取网上支付平台系统配置文件',
        '2001'=>'网上支付平台系统配置文件中参数设置错误',
        '2002'=>'无法读取网上支付平台证书',
        '2003'=>'无法读取网上支付平台私钥',
        '2004'=>'数据库错误',
        '2006'=>'证书格式错误',
        '2100'=>'商户提交的交易资料不完整',
        '2101'=>'商户提交的交易资料不合法',
        '2102'=>'签名响应报文时发生错误',
        '2201'=>'无法连线银行后台系统',
        '2202'=>'接收商户交易请求时发生网络错误',
        '2205'=>'无法辨识商户提交的交易请求报文',
        '2301'=>'商户提交的交易请求报文不完整',
        '2302'=>'商户提交的交易请求报文签名验证失败',
        '2303'=>'商户提交的商户号与签名所用的证书不匹配',
        '2304'=>'商户状态不允许交易',
        '2305'=>'商户不存在',
        '2306'=>'订单状态不允许进行此种交易',
        '2307'=>'无此订单',
        '2308'=>'商户无可用的支付方式',
        '2309'=>'无法取得商户证书',
        '2310'=>'网上支付平台未开放此种类的交易',
        '2311'=>'商户未开放指定的商品种类',
        '2400'=>'后台系统响应交易失败',
        '2500'=>'所有交易已测试通过，请通知银行开放可以进行正式交易',
        '2501'=>'测试交易种类错误，请按照网上支付平台所指示的顺序进行测试',
        '2600'=>'未到可以下载对账单的时间，请在可以下载对账单的时间再下载',
        '2999'=>'系统发生无法预期的错误'
    );


    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct( $app )
    {
        parent::__construct( $app );
        $this->notify_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_mabcjm_server', 'callback' );
        if ( preg_match( "/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches ) )
        {
            $this->notify_url = str_replace( 'http://', '', $this->notify_url );
            $this->notify_url = preg_replace( "|/+|", "/", $this->notify_url );
            $this->notify_url = "http://" . $this->notify_url;
        }
        else
        {
            $this->notify_url = str_replace( 'https://', '', $this->notify_url );
            $this->notify_url = preg_replace( "|/+|", "/", $this->notify_url );
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_mabcjm', 'callback' );
        if ( preg_match( "/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches ) )
        {
            $this->callback_url = str_replace( 'http://', '', $this->callback_url );
            $this->callback_url = preg_replace( "|/+|", "/", $this->callback_url );
            $this->callback_url = "http://" . $this->callback_url;
        }
        else
        {
            $this->callback_url = str_replace( 'https://', '', $this->callback_url );
            $this->callback_url = preg_replace( "|/+|", "/", $this->callback_url );
            $this->callback_url = "https://" . $this->callback_url;
        }
        $this->submit_url = $this->getConf( 'submit_url', __CLASS__ );
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }


    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro()
    {
        return '农行网上支付配置信息(上海佶宓)';
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
                'title' => app::get( 'ectools' )->_( '支付方式名称' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'mer_id' => array(
                'title' => app::get( 'ectools' )->_( '商户号' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'app_id' => array(
                'title' => app::get( 'ectools' )->_( 'APPID' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'app_secret' => array(
                'title' => app::get( 'ectools' )->_( 'SECRET' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pfx_file' => array(
                'title' => app::get( 'ectools' )->_( 'pfx证书路径' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'pfx_file_pass' => array(
                'title' => app::get( 'ectools' )->_( 'pfx证书密码' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'trustPay_file' => array(
                'title' => app::get( 'ectools' )->_( '网上支付证书路径' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'submit_url' => array(
                'title' => app::get( 'ectools' )->_( '订单提交URL' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'query_url' => array(
                'title' => app::get( 'ectools' )->_( '交易查询URL' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'refund_url' => array(
                'title' => app::get( 'ectools' )->_( '退款提交URL' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'refund_query_url' => array(
                'title' => app::get( 'ectools' )->_( '退款查询URL' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'order_by' => array(
                'title' => app::get( 'ectools' )->_( '排序' ),
                'type' => 'string',
                'label' => app::get( 'ectools' )->_( '整数值越小,显示越靠前,默认值为1' ),
            ),
            'pay_type' => array(
                'title' => app::get( 'wap' )->_( '支付类型(是否在线支付)' ),
                'type' => 'radio',
                'options' => array( 'false' => app::get( 'wap' )->_( '否' ), 'true' => app::get( 'wap' )->_( '是' ) ),
                'name' => 'pay_type',
            ),
            'status' => array(
                'title' => app::get( 'ectools' )->_( '是否开启此支付方式' ),
                'type' => 'radio',
                'options' => array( 'false' => app::get( 'ectools' )->_( '否' ), 'true' => app::get( 'ectools' )->_( '是' ) ),
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
        return app::get( 'ectools' )->_( '农行网上支付' );
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay( $payment )
    {
        //设定订单属性
        $order['PayTypeID'] = 'ImmediatePay';//交易类型 直接支付
        $order['OrderNo'] = $payment['payment_id'];//订单编号

        $spend = time() - $payment['create_time'];
        $expire = 2380 - $spend;
        if ( $expire > 0 )
        {
            $data_str = date( 'YmdHis', time() + $expire );
        }
        else
        {
            $data_str = date( 'YmdHis', time() + 300 );
        }
        $order['orderTimeoutDate'] = $data_str;//支付有效期 精确到s * 选择性输入
        $order['OrderAmount'] = number_format( $payment['cur_money'], 2, ".", "" );//订单编号
        $order['CurrencyCode'] = '156';//交易币种 156 人民币
        $order['InstallmentMark'] = '0';//分期标志 1分期 0 不分期
        $order['OrderDate'] = date( 'Y/m/d' );//订单日期 YYYY/MM/DD
        $order['OrderTime'] = date( 'H:m:s' );//交易时间 HH:MM:SS
        $order['CommodityType'] = '0202';//商品种类
        $pay_obj = new stdClass();

        $pay_obj->order = $order;

        //订单明细加入订单中
        $order_info = kernel::single( "b2c_service_order" )->getOrderInfo( $payment['order_id'] );
        $products = array();
        foreach ( $order_info['items'] as $k => $v )
        {
            $products[$k]['ProductName'] = $v['name'];
        }
        $pay_obj->orderitems = $products;//商品名称
        //支付请求对象的属性
        $request['TrxType'] = 'PayReq';
        $request['PaymentType'] = 'A';//支付类型  1：农行卡支付 2：国际卡支付 3：农行贷记卡支付 5：基于第三方的跨行支付 6：银联跨行支付 7：对公户 A:支付方式合并 TODO 用户是否可以自己修改
        $request['PaymentLinkType'] = '2';//交易渠道 	1：internet网络接入  2：手机网络接入  3：数字电视网络接入  4：智能客户端 TODO 用户是否可以自己修改
        $request['NotifyType'] = '1';//交易渠道 	0 URL通知 1 服务器通知
        $request['ResultNotifyURL'] = $this->notify_url;//通知URL地址
        $request['IsBreakAccount'] = '0';//交易是否分账 1是 0 否
        $pay_obj->request = $request;//商品名称
        $req_msg = $this->getRequestMessage( $pay_obj );
        $tSignature = $this->genSignature( $req_msg );

        $post_data['MSG'] = $tSignature;
        $post_data['errorPage'] = $this->callback_url;

        $res = $this->request( $this->submit_url, $tSignature );

        \Neigou\Logger::General( 'mabcjm', array( 'action' => 'order', 'remark' => '提交订单', 'sign' => $tSignature, 'payment' => $payment ,'result'=>base64_encode($res)) );

        $returnCode = $this->GetValue( 'ReturnCode', $res );

        if ( $this->is_return_vaild( $res )  &&  $returnCode == '0000')
        {
            //获取订单详细信息
            $url = $this->GetValue( 'PaymentURL', $res );
            $tmpA = explode( 'TOKEN=', $url );
            $token = $tmpA[1];
            $this->callback_url = $this->callback_url . '?sn=' . $payment['payment_id'];

            $jsApi = <<<EOS
<script>
function ready(callback){
    if(window.AlipayJSBridge){
        callback&&callback();
    }else{
        document.addEventListener('AlipayJSBridgeReady',callback,false);
    }
}
ready(function(){
    window.AlipayJSBridge && AlipayJSBridge.call('startApp',{
        appId:'30603024',
        param:{
          type:"3",
          tokenId:"%s",
          paySystem:"",
          payType:"1111",
          webviewURL:"%s",
          remark:"其他参数",
          startMultApp:"YES",
          showProgress:"NO",
          backBehavior:"back"
        },
  },function(result){
       
  });
});
</script>
EOS;

//            $jsApi = sprintf($jsApi,$this->getConf( 'app_id', __CLASS__ ),$token,$this->callback_url);
            $jsApi = sprintf($jsApi,$token,$this->callback_url);

            \Neigou\Logger::General( 'mabcjm', array( 'action' => 'pay', 'remark' => 'pay_jsapi', 'token' => $token, 'callback_url' => $this->callback_url,'jsapi'=>base64_encode($jsApi)) );

            echo $jsApi;
            die;
        }

        $failUrl = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_failed', 'arg1' => '提交失败 '.$this->ErrorCodeInfo[$returnCode] ) );

        header( 'Location:' . $failUrl );
        die;
    }


    /// 发送交易报文至网上支付平台
    private function request( $tURL, $aMessage )
    {
        //组成<MSG>段
        $tMessage = strval( $aMessage );
        $opts = array(
            'http' => array(
                'method' => 'POST',
                'user_agent' => 'TrustPayClient V3.0.0',
                'protocol_version' => 1.0,
                'header' => array( 'Content-Type: text/html', 'Accept: */*' ),
                'content' => $tMessage
            ),
            'ssl' => array(
                'verify_peer' => false
            )
        );
        $context = stream_context_create( $opts );
        $tResponseMessage = file_get_contents( $tURL, false, $context );

        \Neigou\Logger::General( 'mabcjm', array( 'action' => 'request', 'remark' => '支付提交', 'option' => $opts, 'result' => $tResponseMessage ) );

        return $tResponseMessage;

    }

    /**
     * 获取请求消息
     * @param $obj | req消息
     * @return string
     */
    public function getRequestMessage( $obj )
    {
        $js = '"Order":' . (json_encode( ($obj->order) ));
        $js = substr( $js, 0, -1 );
        $js = $js . ',"OrderItems":[';
        $count = count( $obj->orderitems, COUNT_NORMAL );
        for ( $i = 0 ; $i < $count ; $i++ )
        {
            $js = $js . json_encode( $obj->orderitems[$i] );
            if ( $i < $count - 1 )
            {
                $js = $js . ',';
            }
        }
        $js = $js . ']}}';
        $tMessage = json_encode( $obj->request );
        $tMessage = substr( $tMessage, 0, -1 );
        $tMessage = $tMessage . ',' . $js;
        return $tMessage;
    }

    /**
     * 组成完整交易报文
     * @param aMessage |交易报文
     * @return 完整交易报文
     * @throws TrxException：报文内容不合法
     */
    private function composeRequestMessage( $aMessage )
    {
        $tMessage = "{\"Version\":\"V3.0.0\",\"Format\":\"JSON\",\"Merchant\":" . "{\"ECMerchantType\":\"" . "EBUS" . "\",\"MerchantID\":\"" . $this->getConf( 'mer_id', __CLASS__ ) . "\"}," . "\"TrxRequest\":" . $aMessage . "}";
        return $tMessage;
    }


    private function der2pem( $der_data )
    {
        $pem = chunk_split( base64_encode( $der_data ), 64, "\n" );
        $pem = "-----BEGIN CERTIFICATE-----\n" . $pem . "-----END CERTIFICATE-----\n";
        return $pem;
    }

    public function signMessage( $aMessage )
    {
        //1、读取商户证书
        $tMerchantCertFile = $this->getConf( 'pfx_file', __CLASS__ );
        $tMerchantCertPassword = $this->getConf( 'pfx_file_pass', __CLASS__ );
        //2、读取证书
        if ( openssl_pkcs12_read( file_get_contents( $tMerchantCertFile ), $tCertificate, $tMerchantCertPassword ) )
        {
            //3、验证证书是否在有效期内
            $cer = openssl_x509_parse( $tCertificate['cert'] );
            $t = time();
            if ( $t < $cer['validFrom_time_t'] || $t > $cer['validTo_time_t'] )
            {
                \Neigou\Logger::General( 'mabcjm', array('action'=>'pfx valid time',  'remark' => 'pfx证书过期' ) );
            }
            //4、取得密钥
            $pkey = openssl_pkey_get_private( $tCertificate['pkey'] );
            if ( !$pkey )
            {
                \Neigou\Logger::General( 'mabcjm', array('action'=>'openssl_pkey_get_private fail',  'remark' => '无法生成私钥证书对象' ) );
            }
        }
        else
        {
            \Neigou\Logger::General( 'mabcjm', array('action'=>'openssl_pkcs12_read fail', 'remark' => '证书读取失败' ) );
        }
        $signature = '';
        $data = strval( $aMessage );
        if ( !openssl_sign( $data, $signature, $pkey, OPENSSL_ALGO_SHA1 ) )
        {
            return null;
        }

        $signature = base64_encode( $signature );
        $tMessage = "{\"Message\":$data" . "," . '"Signature-Algorithm":' . '"SHA1withRSA","Signature":"' . $signature . '"}';
//        $tMessage = str_replace('"', "&quot;", $tMessage);//TODO 网站提交需
        return $tMessage;
    }

    //4、对交易报文进行签名
    public function genSignature( $tRequestMessage )
    {
        $tRequestMessage = $this->composeRequestMessage( $tRequestMessage );
        $sign_str = $this->signMessage( $tRequestMessage );
        \Neigou\Logger::General( 'mabcjm', array( 'action' => 'sign', 'remark' => '交易报文', 'req_str' => $tRequestMessage, 'sign_str' => $sign_str ) );
        return $sign_str;
    }

    public function GetValue( $aTag, $json )
    {
        $index = 0;
        $length = 0;
        $index = strpos( $json, $aTag, 0 );
        if ( $index === false )
            return "";
        do
        {
            if ( $json[$index - 1] === "\"" && $json[$index + strlen( $aTag )] === "\"" )
            {
                break;
            }
            else
            {
                $index = strpos( $json, $aTag, $index + 1 );
                if ( $index === false )
                    return "";
            }
        } while ( true );
        $index = $index + strlen( $aTag ) + 2;
        $c = $json[$index];
        if ( $c === '{' )
        {
            $output = $this->GetObjectValue( $index, $json );
        }
        if ( $c === '"' )
        {
            $output = $this->GetStringValue( $index, $json );
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
    private function GetObjectValue( $index, $json )
    {
        $count = 0;
        $_output = "";
        do
        {
            $c = $json[$index];
            if ( $c === '{' )
            {
                $count++;
            }
            if ( $c === '}' )
                $count--;

            if ( $count !== 0 )
            {
                $_output = $_output . $c;
            }
            else
            {
                $_output = $_output . $c;
                return $_output;
            }
            $index++;
        } while ( true );
    }
    /// <summary> 回传文件中的信息
    /// </summary>
    /// <param name="aTag">域名
    /// </param>
    /// <returns> 指定域的值
    ///
    /// </returns>
    private function GetStringValue( $index, $json )
    {
        $index++;
        $_output = "";
        do
        {
            $c = $json[$index++];
            if ( $c !== '"' )
            {
                $_output = $_output . $c;
            }
            else
            {
                return $_output;
            }

        } while ( true );
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback( &$recv )
    {
        //获取订单号
        $payment_id = $recv['sn'];

        //查询是否已经付款
        $field['TrxType'] = 'Query';
        $field['PayTypeID'] = 'ImmediatePay';
        $field['OrderNo'] = $payment_id;

        //详细查询
        $field['QueryDetail'] = '1';

        $req_msg = json_encode( $field );

        $tSignature = $this->genSignature( $req_msg );

        //查询订单状态
        $url = $this->getConf( 'query_url', __CLASS__ );
        $res = $this->request( $url, $tSignature );

        //响应码
        $ReturnCode = $this->GetValue( 'ReturnCode', $res );

        //获取订单详细信息
        $order = $this->GetValue( 'Order', $res );
        $str = base64_decode( $order );
        $str = iconv( 'GB2312', 'UTF-8', $str );
        $rzt = json_decode( $str, true );

        \Neigou\Logger::General( 'mabcjm', array( 'action' => 'callback', 'remark' => '请求参数', 'field' => json_encode( $field ), 'tSignature' => $tSignature, 'response_data' => base64_encode($res), 'rzt' => $rzt, 'recv' => $recv ) );

        //支付状态：https://pay.test.abchina.com/easyebus/#/api/query/single-query
        if ( $this->is_return_vaild( $res ) && $ReturnCode == '0000' && $rzt && $rzt['Status'] == '04' )
        {
            $ret['payment_id'] = $payment_id;
            $ret['account'] = $this->getConf( 'mer_id', __CLASS__ );
            $ret['bank'] = app::get( 'ectools' )->_( '农行支付' );
            $ret['pay_account'] = $rzt['AcctNo'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $rzt['OrderAmount'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $rzt['OrderAmount'];
            $ret['trade_no'] = $rzt['iRspRef'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mabcjm";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';

            \Neigou\Logger::General( 'mabcjm', array( 'action' => 'callback_success', 'remark' => 'trade_succ', 'data' => $ret ) );

        }elseif ($this->is_return_vaild( $res ) && $ReturnCode == '0000' && $rzt && in_array( $rzt['Status'], array( '01', '02')))
        {
            $ret['status'] = 'ready';
            \Neigou\Logger::General( 'mabcjm', array( 'action' => 'callback_ready', 'remark' => 'sign_err', 'data' => $recv ) );
        }
        else
        {
            $ret['status'] = 'invalid';
            \Neigou\Logger::General( 'mabcjm', array( 'action' => 'callback_fail', 'remark' => 'sign_err', 'data' => $recv ) );
        }

        return $ret;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad()
    {
        return true;
    }

    public function gen_form()
    {
        return '';
    }

    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function get_html( $tSignature )
    {

    }

    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function  is_return_vaild( $aMessage )
    {
        $tTrxResponse = $this->GetValue( 'Message', $aMessage );
        $tSignBase64 = $this->GetValue( 'Signature', $aMessage );
        $tSign = base64_decode( $tSignBase64 );
        $iTrustpayCertificate = openssl_x509_read( $this->der2pem( file_get_contents( $this->getConf( 'trustPay_file', 'wap_payment_plugin_mabcjm' ) ) ) );
        $key = openssl_pkey_get_public( $iTrustpayCertificate );
        $data = strval( $tTrxResponse );
        if ( openssl_verify( $data, $tSign, $key, OPENSSL_ALGO_SHA1 ) == 1 )
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}