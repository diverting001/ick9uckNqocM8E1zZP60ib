<?php

/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/25
 * Time: 3:19 PM
 */
final class wap_payment_plugin_municomo2opay extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = 'wap 联通支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = 'wap 联通支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'municomo2opay';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'municomo2opay';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = 'wap 联通支付';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.2';
    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'iswap';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array( 'CNY' => '01' );

    private $_config = array();

    /**
     * @var array 展示的环境[h5 weixin wxwork]
     */
    //public $display_env = array('h5');
    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct( $app )
    {
        parent::__construct( $app );
        $this->notify_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_municomo2opay_server', 'callback' );
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
        $this->callback_url = kernel::openapi_url( 'openapi.ectools_payment/parse/wap/wap_payment_plugin_municomo2opay', 'callback' );
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

        $config = app::get( 'ectools' )->getConf( __CLASS__ );

        $config = unserialize( $config );
        $this->_config = $config['setting'];
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro()
    {
        return 'wap 联通配置信息';
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
            'method' => array(
                'title' => app::get( 'ectools' )->_( '请求方法' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'providerCode' => array(
                'title' => app::get( 'ectools' )->_( '供应商编号' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'contactNumber' => array(
                'title' => app::get( 'ectools' )->_( '协议编号' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'providerName' => array(
                'title' => app::get( 'ectools' )->_( '供应商名称' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'payType' => array(
                'title' => app::get( 'ectools' )->_( '商城支付 1' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'systemId' => array(
                'title' => app::get( 'ectools' )->_( '来源系统client_id' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'systemName' => array(
                'title' => app::get( 'ectools' )->_( '来源系统名称' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'invoiceState' => array(
                'title' => app::get( 'ectools' )->_( '开票方式 2 为集中开票' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'invoiceType' => array(
                'title' => app::get( 'ectools' )->_( '1 普通发票' ),
                'type' => 'string',
                'validate_type' => 'required',
            ),
            'selectedInvoiceTitle' => array(
                'title' => app::get( 'ectools' )->_( '发票类型' ),
                'type' => 'string',
                'label' => app::get( 'ectools' )->_( '1 个人， 2 单位' ),
            ),
            'paymentType' => array(
                'title' => app::get( 'wap' )->_( '付款类型' ),
                'type' => 'string',
                'label' => app::get( 'ectools' )->_( '0、月结后付' ),
            ),
            'submitState' => array(
                'title' => app::get( 'ectools' )->_( '直接发货' ),
                'type' => 'string',
                'label' => app::get( 'ectools' )->_( '预占库存， 0 是预占库存（需要调用确认订单接口）， 1 是不预占库存（供应商可以直接发货）' ),
            ),
            'order_status' => array(
                'title' => app::get( 'ectools' )->_( '订单状态 ' ),
                'type' => 'string',
                'label' => app::get( 'ectools' )->_( '0-待审核 1-待支付 此处是: 1' ),
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
            'cur_url' => array(
                'title' => app::get( 'ectools' )->_( '现金补差价支付接口地址' ),
                'type' => 'string',
                'validate_type' => 'required',
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
        return app::get( 'ectools' )->_( 'wap 联通支付' );
    }

    /** 提交支付信息的接口
     *
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay( $payment )
    {

        $paymentId = $payment['payment_id'];
        $platformOrderId = $payment['order_id'];

        \Neigou\Logger::General( 'municomo2o', array( 'action' => 'init', 'payment' => $payment ,'notify_url'=>$this->notify_url,'callback_url'=>$this->callback_url) );

        try
        {
            //获取用户信息
            $oo2oUserModel = app::get( 'unicom' )->model( 'o2ouser' );
            $userInfo = $oo2oUserModel->getUserInfoRawByMemberId( $payment['member_id'] );
            $extendInfo = json_decode( $userInfo['wap_extendInfo'], true );
            if ( empty( $userInfo ) || empty( $extendInfo ) )
            {
                throw new Exception( '没有用户或用户扩展信息' );
            }

            $orderModel = app::get( 'unicom' )->model( 'o2oPaymentOrder' );
            $orderPayRaw = $orderModel->getOrderRaw( array( 'platformOrderId' => $platformOrderId ) );
            //判断订单是否同步过, 如果同步过直接跳转,否则的话请求联通同步
            if ( !$orderPayRaw['unicomOrderId'] )
            {
                //todo 设置联通数据
                //订单信息
                $order_info = kernel::single( "b2c_service_order" )->getOrderInfo( $payment['order_id'] );
                if ( empty( $order_info ) )
                {
                    throw new Exception( $payment['order_id'] . '订单获取失败' );
                }
                if ( $order_info['extend_info_code'] != 'zhoubianyou' )
                {
                    throw new Exception( $payment['order_id'] . '订单不属于周边游.' . $order_info['extend_info_code'] );
                }
                $requestOrderData = $this->setUnicomRequestOrderInfo( $order_info );
                //配置信息
                $requestConfigData = $this->setUnicomRequestConfigData();

                //基础信息
                $requestBaseData = $this->setUnicomRequestBaseData( $userInfo, $extendInfo );
                $requestData = array_merge( $requestOrderData, $requestConfigData, $requestBaseData );

                if ( !$orderPayRaw )
                {
                    //保存订单数据
                    $orderService = kernel::single( 'unicom_order_o2oOrder' );
                    $insertData = array_merge( $requestData, array( 'paymentId' => $paymentId, 'member_id' => $payment['member_id'] ) );
                    $res = $orderService->createOrder( $insertData );
                    if ( $res['Result'] != "true" )
                    {
                        throw new Exception( '创建订单失败：'.$res['ErrorMsg'] );
                    }
                }

                //todo 请求联通
                $request = kernel::single( 'unicom_request' );

                //用户在供应商系统保存订单后， 将订单信息返回给联通侧， 成功后打开弹性激励商城订单页面 Callbackurl（订单号作为参数 orderno=联通订单号） 。
                $result = $request->requestOpenapi( $requestData, '' );

                //如果同步成功, 增加数据
                if ( $result['Result'] != "true" )
                {
                    throw new Exception( '同步联通失败：'.$result['ErrorMsg'] );
                }
                //$res = $orderModel->add(array('payment_id' => $paymentId,'platform_order_id' => $platformOrderId,'unicom_order_id' => $result['Data']['orderNo'],'create_id' => $userInfo['create_id']));

                $unicomOrderId = $result['Data']['orderNo'];
                $res = $orderModel->update( array( 'platformOrderId' => $payment['order_id'] ), array( 'unicomOrderId' => $unicomOrderId ) );
                if ( !$res )
                {
                    throw new Exception( '平台订单更新异常' );
                }
            }
            else if ( $orderPayRaw['pointCost'] > 0 )
            {
                //todo 现金支付
                $pay_url = $this->get_pay_url( $platformOrderId, $orderPayRaw['unicomOrderId'], number_format( $orderPayRaw['orderPrice'] - $orderPayRaw['pointCost'], 2 ) );

                \Neigou\Logger::General( 'municomo2o', array( 'action' => 'pointcost', 'payment' => $payment, 'env' => 'wap手机端', 'url' => $pay_url ) );

                header( 'Location: ' . $pay_url );
                die();
            }
            else
            {
                $unicomOrderId = $orderPayRaw['unicomOrderId'];
            }

            //todo 联通支付链接拼接
            $url = $extendInfo['user_info']['callbackurl'];
            if(empty($url) || empty($userInfo['createId']) || empty($extendInfo['callToken']))
            {
                throw new Exception( '支付链接错误' );
            }

            $url = str_replace( ':userId', $userInfo['createId'], $url );
            $url = str_replace( ':type', 'order', $url );
            $url = str_replace( ':callToken', $extendInfo['callToken'], $url );
            $url = str_replace( ':orderNo', $unicomOrderId, $url );

            \Neigou\Logger::General( 'municomo2o', array( 'action' => 'pay_url', 'payment' => $payment, 'env' => 'wap手机端', 'url' => $url ) );

        } catch ( Exception $e )
        {
            //跳转支付失败  reason 商品更新失败
            $url = app::get( 'wap' )->router()->gen_url( array( 'app' => 'b2c', 'ctl' => 'wap_paycenter2', 'act' => 'result_failed', 'arg0' => $payment['order_id'] ) );

            \Neigou\Logger::General( 'municomo2o', array( 'action' => 'error', 'errorMsg' => $e->getMessage(), 'order_id' => $payment['order_id'], 'env' => 'wap手机端' ) );
        }

        \Neigou\Logger::General( 'municomo2o', array( 'action' => 'pay_location', 'payment' => $payment, 'env' => 'wap手机端', 'url' => $url ) );

        header( 'Location: ' . $url );
        die();
    }

    /** 设置请求联通的订单数据
     *
     * @param array $order_info
     * @return mixed
     * @author liuming
     */
    private function setUnicomRequestOrderInfo( $order_info = array() )
    {
        $orderId = $order_info['order_id'];
        $requestData['comOrderNo'] = $orderId;
        $requestData['createTime'] = date( 'YmdHis', $order_info['create_time'] );
        $requestData['orderPrice'] = $order_info['final_amount'];   //订单金额
        $requestData['orderNakedPrice'] = $order_info['final_amount'] - $order_info['cost_tax']; //订单裸价: 订单总价 - 订单税额
        $requestData['orderTaxPrice'] = $order_info['cost_tax']; //订单税额
        $requestData['name'] = $order_info['ship_name']; //收货人
        //$requestData['fullAddress'] = ; //全量收货人地址

        $goodsDetail = array();
        foreach ( $order_info['items'] as $k => $productV )
        {
            $goodsDetail[$k] = array(
                //'comOrderDetailNo' => $order_info['order_id'],
                'comOrderDetailNo' => $k,
                'sku' => $productV['bn'],//商品唯一标识
                'goodsName' => $productV['name'],//商品名称
                'num' => $productV['nums'],//采购数量
                'marketPrice' => $productV['mktprice'],//市场价
                'price' => $productV['price'],//单价（含税）
                'nakedPrice' => $productV['price'],//裸价，如果是普票则为含税单价
                'taxPrice' => 0,//税，如果是普票为 0
                'taxRate' => 0,//税率（如果是普票为 0）
                'marketSums' => $productV['mktprice'] * $productV['nums'],//市场价小计
                'sellAmount' => $productV['amount'],//金额小计（含税）
                'nakedSums' => $productV['price'] * $productV['nums'],//不含税金额小计
                'taxSums' => 0,//税额小计
                'goodsSummary' => $productV['name'],//商品摘要
            );
        }
        //$requestData['orderDetail'] = json_encode($goodsDetail,JSON_UNESCAPED_UNICODE);  //订单明细
        $requestData['orderDetail'] = $this->jsonEncodeTool( $goodsDetail );  //订单明细
        return $requestData;
    }

    /** 设置请求联通的配置信息
     *
     * @return mixed
     * @author liuming
     */
    private function setUnicomRequestConfigData()
    {
        $requestConfigData['method'] = $this->_config['method']; //请求方法
        $requestConfigData['providerCode'] = $this->_config['providerCode']; //测试环境使用, 供应商编号
        $requestConfigData['providerName'] = $this->_config['providerName'];   //测试环境使用,供应商名称
        $requestConfigData['payType'] = $this->_config['payType'];   //商城支付
        $requestConfigData['systemId'] = $this->_config['systemId'];   //来源系统client_id
        $requestConfigData['systemName'] = $this->_config['systemName'];   //来源系统名称
        $requestConfigData['invoiceState'] = $this->_config['invoiceState'];   //开票方式 2 为集中开票
        $requestConfigData['invoiceType'] = $this->_config['invoiceType'];   //1 普通发票
        $requestConfigData['selectedInvoiceTitle'] = $this->_config['selectedInvoiceTitle'];   //1 个人， 2 单位
        $requestConfigData['paymentType'] = (isset( $this->_config['paymentType'] ) && !empty( $this->_config['paymentType'] )) ? $this->_config['paymentType'] : 0;   //付款类型 0、月结后付
        $requestConfigData['submitState'] = $this->_config['submitState'];   //直接发货
        $requestConfigData['status'] = $this->_config['order_status'];   //订单状态 0-待审核 1-待支付 此处是: 1
        $requestConfigData['contactNumber'] = empty( $this->_config['contactNumber'] ) ? 'neigwpx_contract' : $this->_config['contactNumber'];   //协议编号
        return $requestConfigData;
    }

    /** 设置请求联通的基本信息
     *
     * @return mixed
     * @author liuming
     */
    private function setUnicomRequestBaseData( $userInfo = array(), $extendInfo = array() )
    {
        //设置请求信息
        $requestData['encouragePlan'] = $extendInfo['encouragePlan'];   //激励计划
        //$requestData['contactNumber'] = UNICOM_CONTACT_NUMBER;   //协议编号
        //用户信息
        $requestData['comCode'] = $userInfo['comCode'];   //用户公司编码
        $requestData['company'] = $userInfo['comName'];   //公司名称
        $requestData['company_value'] = $userInfo['company_value'];   //公司段值
        $requestData['company_name'] = $userInfo['company_name'];   //公司段值
        $requestData['createId'] = $userInfo['createId'];   //用户标识
        $requestData['createName'] = $userInfo['createName'];   //用户姓名
        $requestData['ou'] = $userInfo['ou'];   //用户所属部门编号
        $requestData['orgfullname'] = $userInfo['ouName'];   //用户所属部门名称全称
        return $requestData;
    }

    public function jsonEncodeTool( $array )
    {
        if ( version_compare( PHP_VERSION, '5.4.0', '<' ) )
        {
            $str = json_encode( $array );
            $str = preg_replace_callback( "#\\\u([0-9a-f]{4})#i", function ( $matchs )
            {
                return iconv( 'UCS-2BE', 'UTF-8', pack( 'H4', $matchs[1] ) );
            }, $str );
        }
        else
        {
            $str = json_encode( $array, JSON_UNESCAPED_UNICODE );
        }
        return $str;
    }


    /**
     * 仅用于现金补差以后的同步回调
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback( &$recv )
    {
        \Neigou\Logger::General( 'municomo2o', array( 'action' => 'sync_callback_init', 'data' => $recv ) );

        try
        {
            $bill_id = $recv['bill_id'];
            if ( empty( $bill_id ) )
            {
                throw new Exception( 'bill_id empty' );
            }

            $orderModel = app::get( 'unicom' )->model( 'o2oPaymentOrder' );
            $orderPayRaw = $orderModel->getOrderRaw( array( 'bill_id' => $bill_id ) );
            if ( empty( $orderPayRaw ) )
            {
                throw new Exception( 'order info empty' );
            }

            $waitUrl = ECSTORE_DOMAIN . "/m/paycenter2-result_wait-" . $orderPayRaw['platformOrderId'] . ".html";

            header( 'Location: ' . $waitUrl );
        } catch ( Exception $exception )
        {
            \Neigou\Logger::General( 'municomo2o', array( 'action' => 'sync_callback_error', 'errorMsg' => $exception->getMessage(), 'data' => $recv, 'env' => 'wap手机端' ) );

            echo $exception->getMessage();
        }

        die();
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

    /*
     * @todo 获取支付地址
     */
    public function get_pay_url( $platformOrderId, $unicomOrderId, $payAmount )
    {

        $order_info = kernel::single( "b2c_service_order" )->getOrderInfo( $platformOrderId );
        if ( empty( $order_info ) )
        {
            throw new Exception( $platformOrderId . '订单获取失败' );
        }

        $bill_id = '';
        $res = \Neigou\ApiClient::doServiceCall( 'bill', 'BillId/Create', 'v1', null, array( 'a' => 1 ), array('debug'=>false) );
        if ( $res['service_data']['error_code'] === 'SUCCESS' && !empty( $res['service_data']['data'] ) )
        {
            $bill_id = $res['service_data']['data']['bill_id'];
        }

        \Neigou\Logger::General( 'municomo2o', array( 'action' => 'bill_id', 'bill_id' => $bill_id, 'env' => 'wap手机端' ) );

        if ( empty( $bill_id ) )
        {
            throw new Exception( '生成支付单号失败' );
        }

        // 组织请求数据
        $req_data = array(
            'bill_id' => $bill_id,
            'pay_app_version' => '1.0',
            'bill_type' => 'pay',
            'version' => 'V1',
            'status' => 'ready',
            'expire_time' => $order_info['create_time'] + 2400,
            'cur_money' => $payAmount,
            'memo' => 'unicom:' . $platformOrderId,
            'extend_data' => array(
                'ip' => $this->get_client_ip(),
                'subject' => $platformOrderId
            )
        );

        $res = \Neigou\ApiClient::doServiceCall( 'bill', 'Bill/Create', 'v1', null, $req_data, array(
            'timeout' => 20,
            'debug' => false,
        ) );

        \Neigou\Logger::General( 'municomo2o', array( 'action' => 'bill_create', 'data' => $res['service_data']['data'], 'req_data' => $req_data, 'env' => 'wap手机端' ) );

        if ( $res['service_data']['error_code'] != 'SUCCESS' || empty( $res['service_data']['data'] ) )
        {
            throw new Exception( '生成支付失败' );
        }

        //记录bill_id
        $orderModel = app::get( 'unicom' )->model( 'o2oPaymentOrder' );
        $res = $orderModel->update( array( 'platformOrderId' => $platformOrderId, 'unicomOrderId' => $unicomOrderId ), array( 'bill_id' => $bill_id ) );
        if ( !$res )
        {
            throw new Exception( '支付单号更新失败' );
        }

        $url = ECSTORE_DOMAIN . '/pay/dopay.html?bill_id=' . $bill_id . '&code=unicom';

        \Neigou\Logger::General( 'municomo2o', array( 'action' => 'bill_pay', 'data' => $url, 'env' => 'wap手机端' ) );

        return $url;
    }

    public function notifyBill($billPost = array())
    {
        $ret = \Neigou\ApiClient::doServiceCall( 'bill', 'Bill/setPayed', 'v1', null, $billPost, array('debug'=>false) );
        \Neigou\Logger::General( 'municomo2o', array( 'action' => 'notify_bill', 'res' => $ret ,'req'=>$billPost) );
    }

    /**
     * 获取客户端IP
     * @return string
     */
    public function get_client_ip()
    {
        if ( getenv( 'HTTP_CLIENT_IP' ) && strcasecmp( getenv( 'HTTP_CLIENT_IP' ), 'unknown' ) )
        {
            $ip = getenv( 'HTTP_CLIENT_IP' );
        }
        elseif ( getenv( 'HTTP_X_FORWARDED_FOR' ) && strcasecmp( getenv( 'HTTP_X_FORWARDED_FOR' ), 'unknown' ) )
        {
            $ip = getenv( 'HTTP_X_FORWARDED_FOR' );
        }
        elseif ( getenv( 'REMOTE_ADDR' ) && strcasecmp( getenv( 'REMOTE_ADDR' ), 'unknown' ) )
        {
            $ip = getenv( 'REMOTE_ADDR' );
        }
        elseif ( isset( $_SERVER['REMOTE_ADDR'] ) && $_SERVER['REMOTE_ADDR'] && strcasecmp( $_SERVER['REMOTE_ADDR'], 'unknown' ) )
        {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return preg_match( '/[\d\.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
    }

    /*
     * @todo 支付验证
     */
    public function pay_sign( $pay_data )
    {
        $sign_str = $pay_data['orderNo'] . $pay_data['payAmount'] . $this->_config['systemId'] . $pay_data['code'] . $pay_data['timestamp'] . $pay_data['state'];
        return strtoupper( md5( $sign_str ) );
    }

}