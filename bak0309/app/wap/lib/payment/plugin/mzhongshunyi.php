<?php

/**
 * 渠道 中顺意 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mzhongshunyi extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '渠道 中顺意 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '渠道 中顺意 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mzhongshunyi';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mzhongshunyi';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '渠道 中顺意 支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mzhongshunyi_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mzhongshunyi', 'callback');
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
        return '渠道 中顺意 支付配置信息';
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
                'title'=>app::get('ectools')->_('商户AppID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_key'=>array(
                'title'=>app::get('ectools')->_('App Key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
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
        return app::get('ectools')->_('渠道 中顺意 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //third user info
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));

        /* Service Order Info*/
        $service_order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);

        /* Items */
        $item_info = array();
        foreach ($service_order_info['items'] as $k => $v) {
            $item_info[$k]['itemNo'] = $v['bn'];
            $item_info[$k]['itemName'] = $v['name'];
            $item_info[$k]['counts'] = $v['nums'];
            $item_info[$k]['price'] = number_format($v['price'],2,".","")*100;
            $item_info[$k]['amount'] = number_format($v['amount'],2,".","")*100;
        }

        /* OrderInfo */
        $order_info['outOrderNo'] = $service_order_info['order_id'];
        $order_info['orderAmount'] = number_format($service_order_info['final_amount'],2,".","")*100;
        $order_info['itemAmount'] = number_format($service_order_info['cost_item'],2,".","")*100;
//        $order_info['pmtAmount'] = $service_order_info['pmt_amount'];
        $order_info['freight'] = number_format($service_order_info['freight'],2,".","")*100;
        $order_info['taxAmount'] = number_format($service_order_info['tax_amount'],2,".","")*100;
//        $order_info['pointAmount'] = $service_order_info['point_amount'];
//        $order_info['weight'] = $service_order_info['weight'];
        $order_info['status'] = $service_order_info['status'];
        $order_info['payStatus'] = $service_order_info['pay_status'];
        $order_info['shipName'] = $service_order_info['ship_name'];
        $order_info['shipPhone'] = $service_order_info['ship_mobile'];
        $order_info['shipProvince'] = $service_order_info['ship_province'];
        $order_info['shipCty'] = $service_order_info['ship_city'];
        $order_info['shipCounty'] = $service_order_info['ship_county'];
        $order_info['shipTown'] = $service_order_info['ship_town'];
        $order_info['shipAddress'] = $service_order_info['ship_addr'];
        $order_info['items'] = json_encode($item_info);

        /* Common */
        $time = time();
        $this->add_field('uid', $member_info['external_bn']);//第三方用户BN
        $this->add_field('orderNotifyUrl', $this->notify_url);//异步回调URL
        $this->add_field('orderFrontNotifyUrl', $this->callback_url);//异步回调URL
        $this->add_field('orderInfo', json_encode($order_info));//订单JSON
        $this->add_field('outTradeNo', $payment['payment_id']);//订单JSON
        $this->add_field('payAmount', number_format($payment['cur_money'],2,".","")*100);//订单JSON
        $this->add_field('appId', $this->getConf('app_id',__CLASS__));//商户AppId
        $this->add_field('timestamp', $time);//商户渠道号
        $this->add_field('signature',$this->genSign($this->fields));
        $response = $this->request($this->getConf('submit_url',__CLASS__),$this->fields);
        if($response['status']==1 && $response['statusDes'] = 'SUCCESS'){
            header('Location:'.$response['payUrl']);
        } else {
            $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
            \Neigou\Logger::General('ecstore.mzhongshunyi.do_pay', array('action' => 'pay_fail', 'result' => $response));
            header('Location: '.$url);die;
        }
        die;
    }

    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $linkStr = $this->_create_link_string($data,true,true);
        $linkStr .= $this->getConf('app_key',__CLASS__);
        \Neigou\Logger::General('ecstore.mzhongshunyi.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
        return md5($linkStr);
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
            $linkString .= $key . "=" . $value;
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


    public function request($api = '', $post_data = array()) {
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
        $result = $curl->Post($api, $req);
        \Neigou\Logger::General('ecstore.mzhongshunyi.req', array('action' => 'do req','req_url'=>$api,'post_data'=>$post_data,'response_data'=>$result));
        $resultData = json_decode($result, true);
        return $resultData;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        if($this->is_return_vaild($recv)){
            //获取订单详细信息
            $ret['payment_id'] = $recv['outTradeNo'];
            $ret['account'] = $this->getConf('app_id',__CLASS__);
            $ret['bank'] = app::get('ectools')->_('中顺易支付');
            $ret['pay_account'] = $recv['appId'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['payAmount']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['payAmount']/100;
            $ret['trade_no'] = $recv['orderNo'];
            $ret['t_payed'] = $recv['timestamp']? $recv['timestamp'] : time();
            $ret['pay_app_id'] = "mzhongshunyi";
            $ret['pay_type'] = 'online';
            if($recv['payStatus']==2){
                $ret['status'] = 'succ';
                \Neigou\Logger::General('ecstore.callback.mzhongshunyi', array('remark' => 'trade_succ', 'data' => $ret));
            } else {
                $ret['status'] = 'invalid';
                \Neigou\Logger::General('ecstore.callback.mzhongshunyi',array('remark'=>'trade_err','data'=>$recv));
            }

        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mzhongshunyi',array('remark'=>'sign_err','data'=>$recv));
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
     * 检验返回数据合法性
     * @param $param
     * @access private
     * @return boolean
     */
    public function is_return_vaild($param) {
        $sign = $param['signature'];
        unset($param['signature']);
        $req_sign = $this->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
}