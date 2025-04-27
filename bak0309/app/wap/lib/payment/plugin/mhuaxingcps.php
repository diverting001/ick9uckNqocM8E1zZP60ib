<?php

/**
 * 华兴cps 模拟支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2020/04/09
 * Time: 16:18
 */
final class wap_payment_plugin_mhuaxingcps extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '华兴cps 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '华兴cps 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mhuaxingcps';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mhuaxingcps';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '华兴cps 支付';
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
     * 是否自动提交表单 1==>是
     * @var int
     */
    public $auto_submit = 1;

    /**
     * 自动提交表单 channel
     *
     * @var array
     */
    public $auto_submit_channel = array('huaxing','huaxing_fx');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mhuaxingcps_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mhuaxingcps', 'callback');
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
        return '华兴CPS 支付配置信息';
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
                'title'=>app::get('ectools')->_('商户号 华兴OTO提供的商户号'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'client_id'=>array(
                'title'=>app::get('ectools')->_('商户号 client_id'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'salt'=>array(
                'title'=>app::get('ectools')->_('华兴OTO服务salt'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('CPS下单URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('CPS订单申请退款URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_query_url'=>array(
                'title'=>app::get('ectools')->_('CPS订单状态查询URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sync_url'=>array(
                'title'=>app::get('ectools')->_('CPS订单状态通知URL'),
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
        return app::get('ectools')->_('华兴 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //组合cps创建订单提交到华兴

        $this->add_field('mer_id',$this->getConf('mer_id',__CLASS__));
        $this->add_field('client_id', $this->getConf('client_id',__CLASS__));

        $members_mdl = app::get('b2c')->model('third_members');
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$payment['member_id'],'source' => 1));
        $this->add_field('user_id', $member_info['external_bn']);//当前用户的 member_id 下划线 company_id
        $this->add_field('order_id', $payment['payment_id']);//交易流水号作为订单号
        $this->add_field('total_fee', $payment['cur_money']);//内购订单号
        $this->add_field('callback_url', $this->callback_url);//callback
        $this->add_field('notify_url', $this->notify_url);//notify
        $this->add_field('create_time', time());
        $this->add_field('expire_time', time()+3600);

        //获取CPS订单信息 此处修改为获取service 订单信息 获取extend_data中记录的信息
        $service_order = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $order_detail = '';
        foreach ($service_order['items'] as $item) {
            $order_detail .= $item['name'];
        }

        $cps_order_info = $service_order['extend_data'];

        $this->add_field('order_detail', $order_detail);//CPS订单说明
        $this->add_field('order_type', $cps_order_info['order_type']);//CPS订单说明
        $this->add_field('detail_link', $cps_order_info['detail_link']);//TODO CPS订单详情 需要包含 SSO地址
        $this->add_field('redirect', 1);//CPS订单详情 需要包含 SSO地址
        $this->add_field('sign', $this->genSign($this->fields));
        echo $this->gen_form();

        die;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        if($this->is_return_vaild($recv)){
            //获取订单详细信息
            $ret['payment_id'] = $recv['order_id'];
            $ret['account'] = $recv['mer_id'];
            $ret['bank'] = app::get('ectools')->_('华兴CPS支付');
            $ret['pay_account'] = '华兴cps';
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee'];
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mhuaxingcps";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mhuaxingcps',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
    }

    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $data['salt'] = $this->getConf('salt',__CLASS__);
        $linkStr = $this->_create_link_string($data,true,true);
        \Neigou\Logger::General('ecstore.mhuaxingcps.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
        return md5($linkStr);
    }

    /**
     * 将数组转换成String
     * @param $para array 参数
     * @param $sort bool 是否排序
     * @param $encode string 是否urlencode
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
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
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

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    public function gen_form(){
        $sHtml = "<form id='form_submit' name='form_submit' action='".$this->submit_url."' method='POST'>";
        foreach ($this->fields as $key=>$val){
            $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
        }
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";
        $sHtml = $sHtml."<script>document.forms['form_submit'].submit();</script>";
        return $sHtml;
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

    /**
     * 订单消息同步推送
     * @param $data
     * @return bool
     */
    public function SyncOrder($data){
        $req['mer_id'] = $this->getConf('mer_id',__CLASS__);
        $req['client_id'] = $this->getConf('client_id',__CLASS__);
        $req['user_id'] = $data['user_id'];
        $req['order_id'] = $data['order_id'];
        $req['price'] = $data['price'];
        $req['status'] = $data['status'];
        $req['status_display'] = $data['status_display'];
        $req['timestamp'] = time();
        $req['order_type'] = $data['order_type'];
        $req['sign'] = $this->genSign($req);
        $refundRes = $this->request($this->getConf('sync_url',__CLASS__),$req);
        if (isset($refundRes['ErrorId']) && $refundRes['ErrorId'] == 0 && $refundRes['ErrorMsg']=='success') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $api
     * @param array $post_data
     * @return mixed
     */
    public function request($api = '', $post_data = array())
    {
        $curl        = new \Neigou\Curl();
        $proxyServer = NEIGOU_HTTP_PROXY;
        $opt_config  = array(
            CURLOPT_FAILONERROR    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PROXYTYPE      => CURLPROXY_HTTP,
            CURLOPT_PROXY          => $proxyServer,
        );
        $curl->SetOpt($opt_config);
        $result = $curl->Post($api, $post_data);
        echo $result;
        \Neigou\Logger::General("ecstore.sync.mhuaxingcps",
            array('action' => 'do_req', 'request' => $post_data, 'response' => $result, 'api' => $api));
        return json_decode($result, true);
    }
}