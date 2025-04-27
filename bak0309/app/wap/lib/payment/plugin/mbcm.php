<?php

/**
 * 交行支付
 */

final class wap_payment_plugin_mbcm extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '交行数字人民币';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '交行数字人民币';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mbcm';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mbcm';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '交行数字人民币';
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

    /**
     * @var string 通用支付
     */
    public $is_general = 1;

    private static $CONTENT_TYPE = "application/x-www-form-urlencoded;charset=UTF-8";
    public static $CHARSET_UTF8 = "UTF-8";

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct( $app )
    {
        parent::__construct( $app );
        $this->notify_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_mbcm_server', 'callback' );
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
        $this->callback_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_mbcm', 'callback' );
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
        return '交行数字人民币标准支付配置信息';
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
            'mch_id' => array(
                'title' => app::get( 'ectools' )->_( '商户号' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'appid' => array(
                'title' => app::get( 'ectools' )->_( 'APPID' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'public_rsa_key' => array(
                'title' => app::get( 'ectools' )->_( 'RSA公钥' ),
                'type' => 'textarea',
                'validate_type' => 'required',
            ),
            'private_rsa_key' => array(
                'title' => app::get( 'ectools' )->_( 'RSA私钥' ),
                'type' => 'textarea',
                'validate_type' => 'required',
            ),
            'sp_ip' => array(
                'title' => app::get( 'ectools' )->_( 'IP' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'submit_url' => array(
                'title' => app::get( 'ectools' )->_( '预下单地址' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'refund_url' => array(
                'title' => app::get( 'ectools' )->_( '退款地址' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'refund_status_url' => array(
                'title' => app::get( 'ectools' )->_( '退款查询' ),
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
            'is_general' => array(
                'title' => app::get( 'ectools' )->_( '通用支付(是否为缺省通用支付)' ),
                'type' => 'radio',
                'options' => array( '0' => app::get( 'ectools' )->_( '否' ), '1' => app::get( 'ectools' )->_( '是' ) ),
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
        return app::get( 'ectools' )->_( '交行数字人民币' );
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay( $payment )
    {
        $time = time();
        $time_start = date( 'YmdHis', $time );

        $trade_no = $payment['payment_id'];

        $expire_sec = 40 * 60;
        $expire_time = $time + $expire_sec;
        $expire_date = date( 'YmdHis', $expire_time );
        $price = number_format( $payment['cur_money'], 2, ".", "" ) * 100;
//        $waitUrl = self::waitUrl( $payment['order_id'] );
        
        $waitUrl = "https://q.womai.com/m/paycenter2-result_wait-".$payment['order_id'].".html";
        $notify_url = 'https://q.womai.com/openapi/ectools_payment/parse/wap/wap_payment_plugin_mbcm_server/callback/';

        $this->add_field( 'mch_id', $this->getConf( 'mch_id', __CLASS__ ) );
        $this->add_field( 'front_notify_url', $waitUrl );
        $this->add_field( 'device_info', 'WEB' );
        $this->add_field( 'time_expire', $expire_date );
        $this->add_field( 'total_amount', (string)$price );
        $this->add_field( 'time_start', $time_start );
        $this->add_field( 'sp_ip', $this->getConf( 'sp_ip', __CLASS__ ) );
        $this->add_field( 'trans_type', 'HH01' );
        $this->add_field( 'out_trade_no', $trade_no );
        $this->add_field( 'notify_url', $this->getConf( 'appid', __CLASS__ ) . '@' . $notify_url );

        $bizContent = array(
            'req_head' => array(
                'term_trans_time' => $time_start,
                'trace_no' => $trade_no
            ),
            'req_body' => $this->fields
        );

        $params = array(
            'app_id' => $this->getConf( 'appid', __CLASS__ ),
            'msg_id' => md5( uniqid() . rand( 10000, 99999 ) ),
            'fmt_type' => 'json',
            'charset' => 'UTF-8',
            'timestamp' => date( 'Y-m-d H:i:s', $time ),
            'biz_content' => $bizContent,
            'submit_url' => $this->submit_url,
            'private_rsa_key' => $this->getConf( 'private_rsa_key', __CLASS__ )
        );

        //签名
        $error = '';
        $post = self::toSign( $params, $error );

        $responseStr = self::request( $this->submit_url, $post, null, 0, 10000, 10000, $resinfo );

//        $responseStr = '{"rsp_biz_content":{"biz_state":"S","rsp_code":null,"rsp_msg":null,"rsp_body":{"redirect_url":"http://mbanktest.95559.com.cn:9090/DCMO/H5Cashier/Cashier/cashier_main.html?mrchntNo=301140815209534&deviceInfo=WEB&timeStart=20210424211722&timeExpire=20210424212722&outTradeNo=202104242048284542&orderNo=0505202104242117230027650005&totalAmount=000000000001&frontNotifyUrl=app201901170913@http://store.liangtao.dev.neigou.com/openapi/ectools_payment/parse/wap/wap_payment_plugin_mbcm/callback/&sign=50ac13de39bff0eb125cc5290974f74f&mrchntName=我买网线上统一支付测试商户","order_id":"0505202104242117230027650005","out_trade_no":"202104242048284542"},"rsp_head":{"term_trans_time":"20210424211723","response_code":"CIPP0004PY0000","transcode":"CIPP180103","remark":null,"trace_no":"202104242048284542","response_msg":"交易成功"}},"sign":"i+74JRyHZodqucFAgomb/qQ8qtcewN6wDkDagknHvLUBHeAp61GBB2Lvh4Oy/20IKzbwErEYis6WCS2hfuAdcbKhGAPv+n5JhntaGV9eRTmEklktfRWLWAcF0Rk4d/RI8oOiAZBRArgpO0qtDOLX4YRFzjsD9usOq+pYU+aHhZciJo+b6PXPXW8Y3ShWmTilBeJMR6N1tZURRv8H4gpw0I13mFjUiBLdFw3RTWuUx4BMosDMn4SqYgHIeJ5lUeNSnEMY7TnT42+mjmDmuYQR/Cpscs4vpH7bI89JWcc419k+LCA+JUzdEoy9s6VvAc1/Ddd3xUnl8kKy22H7W5mhzA=="}';

        $log = array(
            'payment' => $payment,
            'params' => $params,
            'doPost' => $post,
            'response' => $responseStr,
            'resinfo' => $resinfo,
            'error' => $error
        );

        \Neigou\Logger::General( 'mbcm', array( 'remark' => 'mbcm', 'data' => json_encode( $log ) ) );

        $failUrl = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_failed', 'arg0' => $payment['order_id'] ) );

        //响应
        $respStr = json_decode( $responseStr, true );
        if ( empty( $respStr ) || empty( $respStr['rsp_biz_content'] ) || empty( $respStr['sign'] ) )
        {
            \Neigou\Logger::General( 'mbcm', array( 'remark' => 'mbcm_response', 'data' => json_encode( $log ) ) );
            header( 'Location: ' . $failUrl );
            echo 'mbcm_response';
            die;
        }

        $rsp_biz_content = $respStr['rsp_biz_content'];
        $rspBody = $rsp_biz_content['rsp_body'];

        //响应验签
        $verifyData = array(
            'response_str' => $responseStr,
            'public_rsa_key' => $this->getConf( 'public_rsa_key', __CLASS__ )
        );

        $verifyResult = self::toVerify( $verifyData );
        if ( $verifyResult['verify'] !== 'true' )
        {
            \Neigou\Logger::General( 'mbcm', array( 'remark' => 'mbcm_verify', 'data' => json_encode( $log ) ) );
            header( 'Location: ' . $failUrl );
            echo 'mbcm_verify';
            die;
        }

        if ( $rsp_biz_content && $rsp_biz_content['biz_state'] == 'S' && $rspBody && $rspBody['redirect_url'] )
        {
            $redirect = $rspBody['redirect_url'];
            header( "Location: $redirect" );
            echo 'redirect first fail';
            die;
        }

        if ( $rsp_biz_content && $rsp_biz_content['biz_state'] == 'S' && empty( $rspBody ) )
        {
            \Neigou\Logger::General( 'mbcm', array( 'remark' => 'mbcm_error', 'data' => json_encode( $log ) ) );
            header( 'Location: ' . $failUrl );
            echo 'mbcm_error';
            die;
        }

        echo 'redirect null';
        exit;
    }

    private static function waitUrl( $order_id = 0 )
    {
        $waitUrl = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_wait', 'arg0' => $order_id ) );
        $waitUrl = kernel::base_url( 1 ) . $waitUrl;

        if ( preg_match( "/^(http):\/\/?([^\/]+)/i", $waitUrl, $matches ) )
        {
            $waitUrl = str_replace( 'http://', '', $waitUrl );
            $waitUrl = preg_replace( "|/+|", "/", $waitUrl );
            $waitUrl = "http://" . $waitUrl;
        }
        else
        {
            $waitUrl = str_replace( 'https://', '', $waitUrl );
            $waitUrl = preg_replace( "|/+|", "/", $waitUrl );
            $waitUrl = "https://" . $waitUrl;
        }

        return $waitUrl;
    }

    /**
     * 签名
     * @param array $params
     * @param string $error
     * @return bool
     */
    private static function toSign( $params = array(), &$error = '' )
    {
        if ( empty( $params['app_id'] ) || empty( $params['biz_content'] ) || empty( $params['submit_url'] ) || empty( $params['private_rsa_key'] ) )
        {
            return false;
        }

        //系统间加密
        $post['body'] = self::authcode( base64_encode( json_encode( $params ) ), 'ENCODE' );

        $curl = new \Neigou\Curl();
        $result = $curl->Post( PAY_DOMAIN . '/tools/wmSign', $post );

        \Neigou\Logger::General( 'mbcm', array( 'action' => 'pay.sign', 'data' => json_encode( $params ), 'remark' => json_encode( $result ), 'sender' => json_encode( $post ) ) );

        $result = json_decode( $result, true );
        if ( empty( $result ) || $result['result'] == 'false' || empty( $result['data'] ) || empty( $result['data']['body'] ) )
        {
            $error = $result['message'];
            return false;
        }

        $body = $result['data']['body'];
        $decode = self::authcode( $body, 'DECODE' );
        $content = json_decode( base64_decode( $decode ), true );

        return $content;
    }

    /**
     * 验签
     * @param array $params
     * @param string $error
     * @return bool
     */
    private static function toVerify( $params = array(), &$error = '' )
    {
        if ( empty( $params['response_str'] ) || empty( $params['public_rsa_key'] ) )
        {
            return false;
        }

        //系统间加密
        $post['body'] = self::authcode( base64_encode( json_encode( $params ) ), 'ENCODE' );

        $curl = new \Neigou\Curl();
        $result = $curl->Post( PAY_DOMAIN . '/tools/wmSignVerify', $post );

        \Neigou\Logger::General( 'mbcm', array( 'action' => 'pay.verify', 'data' => json_encode( $params ), 'remark' => json_encode( $result ), 'sender' => json_encode( $post ) ) );

        $result = json_decode( $result, true );
        if ( empty( $result ) || $result['result'] == 'false' || empty( $result['data'] ) )
        {
            $error = $result['message'];
            return false;
        }

        return $result['data'];
    }

    private static function request( $url, $params, $proxyIp, $proxyPort, $connectTimeOut, $readTimeout, &$resinfo )
    {
        $headers = array();
        $headers [] = 'Expect:';
        $headers ['user-agent'] = 'bankcomm-sdk-java';
        $headers ['content-type'] = 'application/x-www-form-urlencoded;charset=UTF-8';

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
        curl_setopt( $ch, CURLOPT_POST, true );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $params ) );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

        curl_setopt( $ch, CURLOPT_NOSIGNAL, 1 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT_MS, $connectTimeOut );
        curl_setopt( $ch, CURLOPT_TIMEOUT_MS, $readTimeout );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
        curl_setopt( $ch, CURLOPT_PROXY, $proxyIp );
        curl_setopt( $ch, CURLOPT_PROXYPORT, $proxyPort );

        $response = curl_exec( $ch );
        $resinfo = curl_getinfo( $ch );
        curl_close( $ch );

        if ( $resinfo ["http_code"] != 200 )
        {
            return false;
        }
        return $response;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback( &$recv )
    {
        $r = file_get_contents( 'php://input', 'r' );
        \Neigou\Logger::General( 'mbcm', array( 'action' => 'callback', 'data' => $recv, 'r' => $r ) );
        exit;
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
     * 检验返回数据合法性
     * @param $params
     * @access private
     * @return boolean
     */
    public function is_return_vaild( $responseStr )
    {
        $verifyData = array(
            'response_str' => $responseStr,
            'public_rsa_key' => $this->getConf( 'public_rsa_key', __CLASS__ )
        );

        $verifyResult = self::toVerify( $verifyData );

        if ( $verifyResult['verify'] !== 'true' )
        {
            return false;
        }

        return true;
    }


    /**
     * Discuz 加解密函数
     * @param $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @return bool|string
     */
    private static function authcode( $string = '', $operation = 'DECODE', $key = 'f0u6@X&$ssGZyJOiQ$IbfaMCtTbCkrzz', $expiry = 0 )
    {
        $ckey_length = 4;
        $key = md5( $key );
        $keya = md5( substr( $key, 0, 16 ) );
        $keyb = md5( substr( $key, 16, 16 ) );
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr( $string, 0, $ckey_length ) : substr( md5( microtime() ), -$ckey_length )) : '';

        $cryptkey = $keya . md5( $keya . $keyc );
        $key_length = strlen( $cryptkey );

        $string = $operation == 'DECODE' ? base64_decode( substr( $string, $ckey_length ) ) : sprintf( '%010d', $expiry ? $expiry + time() : 0 ) . substr( md5( $string . $keyb ), 0, 16 ) . $string;
        $string_length = strlen( $string );

        $result = '';
        $box = range( 0, 255 );

        $rndkey = array();
        for ( $i = 0 ; $i <= 255 ; $i++ )
        {
            $rndkey[$i] = ord( $cryptkey[$i % $key_length] );
        }

        for ( $j = $i = 0 ; $i < 256 ; $i++ )
        {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ( $a = $j = $i = 0 ; $i < $string_length ; $i++ )
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr( ord( $string[$i] ) ^ ($box[($box[$a] + $box[$j]) % 256]) );
        }

        if ( $operation == 'DECODE' )
        {
            if ( (substr( $result, 0, 10 ) == 0 || substr( $result, 0, 10 ) - time() > 0) && substr( $result, 10, 16 ) == substr( md5( substr( $result, 26 ) . $keyb ), 0, 16 ) )
            {
                return substr( $result, 26 );
            }
            else
            {
                return '';
            }
        }
        else
        {
            return $keyc . str_replace( '=', '', base64_encode( $result ) );
        }
    }

}