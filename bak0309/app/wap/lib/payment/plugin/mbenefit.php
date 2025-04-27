<?php

/**
 * 贝那支付
 */

final class wap_payment_plugin_mbenefit extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '贝那支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '贝那支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mbenefit';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mbenefit';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '贝那支付';
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
    private $benefit_key;

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct( $app )
    {
        parent::__construct( $app );
        $this->notify_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_mbenefit_server', 'callback' );
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
        $this->callback_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_mbenefit', 'callback' );
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
        $this->benefit_key = $this->getConf( 'benefit_key', __CLASS__ );
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
        return '贝那标准支付配置信息';
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
            'discount_config' => array(
                'title' => app::get( 'ectools' )->_( '折扣价系数按照店铺和SKU设置' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'app_id' => array(
                'title' => app::get( 'ectools' )->_( 'APPID' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'benefit_key' => array(
                'title' => app::get( 'ectools' )->_( '商户key' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'submit_url' => array(
                'title' => app::get( 'ectools' )->_( '预下单地址' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'do_pay_url' => array(
                'title' => app::get( 'ectools' )->_( '收银台跳转地址' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'query_url' => array(
                'title' => app::get( 'ectools' )->_( '支付结果查询通知' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'refund_url' => array(
                'title' => app::get( 'ectools' )->_( '退款接口' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'query_refund_url' => array(
                'title' => app::get( 'ectools' )->_( '退款查询接口' ),
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
        return app::get( 'ectools' )->_( '贝那支付' );
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay( $payment )
    {
        $trade_no = $payment['payment_id'];
        $memberId = $payment['member_id'];

        //用户信息
        $members_mdl = app::get( 'b2c' )->model( 'third_members' );
        $member_info = $members_mdl->getRow( "*", array( "internal_id" => $memberId, 'source' => 1 ) );

        $price = number_format( $payment['cur_money'], 2, ".", "" ) * 100;
        $failUrl = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_failed', 'arg0' => $payment['order_id'] ) );

        //登录信息
        if ( empty( $member_info ) || empty( $member_info['external_bn'] ) )
        {
            \Neigou\Logger::General( 'mbenefit', array( 'remark' => 'check_error', 'data' => json_encode( array( 'remark' => 'member info error', 'data' => $member_info ) ) ) );
            header( 'Location: ' . $failUrl );
            echo 'response error';
            exit;
        }

        //主单信息
        $orderInfo = kernel::single( "b2c_service_order" )->getOrderInfo( $payment['order_id'] );
        $goodsName = '';
        foreach ( $orderInfo['items'] as $item )
        {
            if(empty($goodsName))
            {
                $goodsName = $item['name'];
                break;
            }
        }

        $subName = mb_substr( $goodsName, 0, 100 );
        $goodsName = $subName ? $subName : (string)$payment['order_id'];

        //协议价格(分)
        $error = "";
        $contract_price = $this->getDisCountPrice( $orderInfo, $error );
        if ( $contract_price === false )
        {
            \Neigou\Logger::General( 'mbenefit', array( 'remark' => 'check_error', 'data' => json_encode( array( 'remark' => 'contract_price error', 'data' => $contract_price, 'remark' => $error ) ) ) );
            header( 'Location: ' . $failUrl );
            echo 'response error';
            exit;
        }

        //企业ID
        $this->add_field( 'app_id', (string)$this->getConf( 'app_id', __CLASS__ ) );
        //随机字符串
        $this->add_field( 'nonce_str', (string)strtoupper( md5( uniqid() . mt_rand( 1000, 9999 ) ) ) );
        //商品描述
        $this->add_field( 'body', (string)$goodsName );
        //外部订单号
        $this->add_field( 'out_trade_no', (string)$payment['order_id'] );
        //外部支付单号
        $this->add_field( 'out_payment_no', (string)$trade_no );
        //ToDo 贝那平台用户ID
        $this->add_field( 'user_id', (string)$member_info['external_bn'] );
        //订单金额
        $this->add_field( 'total_fee', (int)$price );
        //todo 协议价格
        $this->add_field( 'settlement_price', (int)$contract_price );
        //异步通知地址
        $this->add_field( 'notify_url', (string)$this->notify_url );
        //支付完成后跳转地址
        $this->add_field( 'front_url', (string)$this->callback_url );
        //用户终止支付返回地址
        $this->add_field( 'back_url', (string)$this->failUrl( $payment['order_id'] ) );
        //签名
        $this->add_field( 'sign', (string)$this->createSign( $this->fields ) );

        $response = self::request( $this->submit_url, $this->fields );

        if ( $response === false || empty( $response['app_id'] ) || empty( $response['nonce_str'] ) || empty( $response['sign'] ) || empty( $response['transaction_id'] ) )
        {
            \Neigou\Logger::General( 'mbenefit', array( 'remark' => 'response_error', 'data' => json_encode( array( 'submit_url' => $this->submit_url, 'post' => $this->fields ) ) ) );
            header( 'Location: ' . $failUrl );
            echo 'response error';
            exit;
        }

        //验签
        $checkSign = $this->checkSign( $response );
        if ( $checkSign === false )
        {
            \Neigou\Logger::General( 'mbenefit', array( 'remark' => 'sign_error', 'data' => json_encode( array( 'remark' => $checkSign ) ) ) );
            header( 'Location: ' . $failUrl );
            echo 'response error';
            exit;
        }

        //签名跳转收银台
        $get = array(
            'app_id' => (string)$response['app_id'],
            'nonce_str' => (string)$response['nonce_str'],
            'transaction_id' => (string)$response['transaction_id'],
        );
        $get['sign'] = $this->createSign( $get );

        $pay_url = $this->getConf( 'do_pay_url', __CLASS__ ) . '?' . http_build_query( $get );

        header( 'Location: ' . $pay_url );

        echo 'redirect fail';
        exit;
    }

    //获取订单的协议价格
    private function getDisCountPrice( $orderInfo = array(), &$error = '' )
    {
        $discountConfig = $this->getConf( 'discount_config', __CLASS__ );
        $discountConfig = json_decode( base64_decode( $discountConfig ), true );
        if ( empty( $discountConfig ) )
        {
            $error = "协议参数未配置";
            return false;
        }

        if ( empty( $discountConfig['default'] ) )
        {
            $error = "默认协议折扣未匹配";
            return false;
        }

        if ( empty( $discountConfig['o2o_sku'] ) )
        {
            $error = "O2O协议参数未配置";
            return false;
        }

        //OTO按照SKU判断折扣
        if ( $orderInfo['system_code'] == 'neigouoto' )
        {
            $bns = array();

            foreach ($orderInfo['items'] as $item)
            {
                $bns[] = $item['bn'];
            }

            $bns = array_unique( $bns );
            
            $discounts = array();
            foreach ( $bns as $bn )
            {
                if ( in_array( $bn, array_keys( $discountConfig['o2o_sku'] ) ) )
                {
                    $discounts[] = $discountConfig['o2o_sku'][$bn];
                }
            }

            if ( empty( $discounts ) )
            {
                $error = "O2O协议折扣未匹配";
                return false;
            }

            $maxDiscount = max( $discounts );

            $totalContractPrice = bcadd( $orderInfo['cost_item'] * $maxDiscount, $orderInfo['cost_freight'], 3 ) * 100;
            $totalContractPrice = ceil( $totalContractPrice );
        }
        else
        {
            //普通商品按照店铺判断折扣
            if ( $orderInfo['split_orders'] )
            {
                $orders = $orderInfo['split_orders'];
            }
            else
            {
                $orders = array( $orderInfo );
            }

            $totalContractPrice = 0;
            foreach ( $orders as $order )
            {
                //计算店铺协议折扣
                $shopDiscount = $discountConfig['default'];
                if ( $discountConfig['pop_owner_id'][$order['pop_owner_id']] )
                {
                    $shopDiscount = $discountConfig['pop_owner_id'][$order['pop_owner_id']];
                }

//                echo $order['cost_item'], '_', $shopDiscount, '_', $order['cost_freight'], PHP_EOL;

                //计算店铺协议价格
                $shopContractPrice = bcadd( $order['cost_item'] * $shopDiscount, $order['cost_freight'], 3 ) * 100;
                $shopContractPrice = ceil( $shopContractPrice );

                $totalContractPrice += $shopContractPrice;
            }
        }

        return $totalContractPrice;
    }

    private function createSign( $data = array() )
    {
        if ( empty( $data ) )
        {
            return false;
        }

        //去空值
        $data = array_filter( $data );

        //排序
        ksort( $data );
        
        $buildString = "";
        foreach ( $data as $key => $value )
        {
            $buildString .= $key . "=" . $value . '&';
        }

        $tempSign = $buildString . 'key=' . $this->benefit_key;

        return strtoupper( md5( $tempSign ) );
    }

    private function checkSign( $data = array() )
    {
        if ( empty( $data ) )
        {
            return false;
        }

        $sign = $data['sign'];

        unset( $data['sign'] );

        $data = array_filter( $data );

        ksort( $data );

        $buildString = "";
        foreach ( $data as $key => $value )
        {
            $buildString .= $key . "=" . $value . '&';
        }

        $tempSign = $buildString . 'key=' . $this->benefit_key;

        return (strtoupper( md5( $tempSign ) ) === $sign) ? true : false;
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

    private static function failUrl( $order_id = 0 )
    {
        $failUrl = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_failed', 'arg0' => $order_id ) );
        $failUrl = kernel::base_url( 1 ) . $failUrl;

        if ( preg_match( "/^(http):\/\/?([^\/]+)/i", $failUrl, $matches ) )
        {
            $failUrl = str_replace( 'http://', '', $failUrl );
            $failUrl = preg_replace( "|/+|", "/", $failUrl );
            $failUrl = "http://" . $failUrl;
        }
        else
        {
            $failUrl = str_replace( 'https://', '', $failUrl );
            $failUrl = preg_replace( "|/+|", "/", $failUrl );
            $failUrl = "https://" . $failUrl;
        }

        return $failUrl;
    }

    private static function request( $url, $params )
    {
        unset( $params['key'] );

        $curl = new \Neigou\Curl();
        $curl->SetHeader( "Content-Type", "application/json" );
        $result = $curl->Post( $url, json_encode( $params ) );

        \Neigou\Logger::General( 'mbenefit', array( 'action' => 'request', 'remark' => $url, 'data' => $params, 'result' => $result ) );

        $result = json_decode( $result, true );
        if ( empty( $result ) || $result['code'] !== 'SUCCESS' )
        {
            return false;
        }

        return $result;
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback( &$recv )
    {
        $error = '';
        $valid = $this->is_return_vaild( $recv, $error );

        \Neigou\Logger::General( 'mbenefit', array( 'action' => 'callback.init', 'data' => $recv, 'remark' => $valid, 'reason' => $error ) );

        if ( $valid['status'] === true )
        {
            $receive = $valid['data'];

            $ret['payment_id'] = $receive['out_payment_no'];
            $ret['account'] = $receive['app_id'];
            $ret['bank'] = app::get( 'ectools' )->_( '贝那支付' );
            $ret['pay_account'] = $receive['app_id'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $receive['total_fee'] / 100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $receive['total_fee'] / 100;
            $ret['trade_no'] = $receive['transaction_id']; //贝那交易流水号为上报订单号 对接人称此号 在贝那系统唯一
            $ret['t_payed'] = strtotime( $receive['time_end'] );
            $ret['pay_app_id'] = "mbenefit";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';

            \Neigou\Logger::General( 'mbenefit', array( 'action' => 'callback.success', 'data' => $receive ) );
        }
        else
        {
            \Neigou\Logger::General( 'mbenefit', array( 'action' => 'callback.sign_err', 'data' => $valid ) );
            $ret['status'] = 'invalid';
        }

        $ret['callback_source'] = 'client';

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
     * 检验返回数据合法性
     * @param $recv
     * @param string $error
     * @return mixed
     */
    public function is_return_vaild( $recv, &$error = '' )
    {
        //验签
        $check = $this->checkSign( $recv );

        if ( $check === false )
        {
            $return['status'] = false;
            $error = 'sign error';
            return $return;
        }

        if ( $recv['app_id'] != $this->getConf( 'app_id', __CLASS__ ) )
        {
            $return['status'] = false;
            $error = 'app_id error';
            return $return;
        }

        if ( $recv['trade_state'] !== 'SUCCESS' )
        {
            $return['status'] = false;
            $error = 'trade_state error';
            return $return;
        }

        $return['data'] = $recv;
        $return['status'] = true;
        return $return;
    }

}