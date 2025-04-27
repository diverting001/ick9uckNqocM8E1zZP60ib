<?php

/**
 * 平安银行支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/01/17
 * Time: 15:22
 */
final class wap_payment_plugin_mpingan extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '平安银行支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '平安银行支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mpingan';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mpingan';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '平安银行支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mpingan_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mpingan', 'callback');
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
        return '平安银行支付配置信息';
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
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'create_order_url'=>array(
                'title'=>app::get('ectools')->_('创建订单URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('退款URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'salt'=>array(
                'title'=>app::get('ectools')->_('签名salt'),
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
        return app::get('ectools')->_('平安银行支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //商户系统向平台下订单
        $this->add_field('funCode','ORDER001');//功能码
        $this->add_field('reqDate',date('Ymd',$payment['create_time']));//请求日期
        $this->add_field('reqTime',date('His',$payment['create_time']));//请求时间
        $this->add_field('userId',$this->getMemberBn());//会员号 TODO memberinfo
        $this->add_field('merId',$this->getConf('mer_id',__CLASS__));//商户号
        $this->add_field('orderId',$payment['payment_id']);//订单号 订单号码支持数字、英文字母
        $this->add_field('orderDate',date('Ymd',$payment['create_time']));//商户订单日期 商户生成订单的日期，格式YYYYMMDD
        $this->add_field('orderDesc','内购网订单');//订单描述
        $this->add_field('amount',number_format($payment['cur_money'],2,".","")*100);//订单总金额 以分为单位
        $spend = time()-$payment['create_time'];
        $expire = 2380-$spend;
        if($expire<=0){
            $expire = 60;
        }
        $this->add_field('effectSec',$expire);//订单有效时长 必须为整数，单位：秒 TODO 确认最小单位是多少

        $ret = $this->request($this->getConf('create_order_url',__CLASS__),$this->fields,1);

        //pay faild url
        $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
        if($ret['retCode']=='0000' && $ret){
            //TODO 判定返回的签名是否正确
            //核对订单金额是否一致
            $amount = $ret['amount']/100;
            $amount = number_format($amount,2,".","")*100;
            if($amount!=number_format($payment['cur_money'],2,".","")*100){
                \Neigou\Logger::General('dopay.mpingan.err', array('action' => '支付金额不一致', 'result' => $ret));
                header('Location: '.$url);
                exit;
            }
            //创建请求字符串
            $req_param['funCode'] = 'PAY00001';
            $req_param['reqDate'] = date('Ymd',$payment['create_time']);
            $req_param['reqTime'] = date('His',$payment['create_time']);
            $req_param['paySeq'] = $ret['paySeq'];
            $req_param['retUrl'] = $url;
            $req_param['sign'] = $this->genSign($req_param);
            $req_str = $this->_create_link_string($req_param,true,false);
            \Neigou\Logger::General('dopay.mpingan.req',array('action'=>'请求支付URL','req_url'=>$this->submit_url.'?'.$req_str));
            header('Location:'.$this->submit_url.'?'.$req_str);
            exit();
        } else {
            if($ret['amount']!=number_format($payment['cur_money'],2,".","")*100){
                //redirect to pay err page
                \Neigou\Logger::General('dopay.mpingan.err', array('action' => '请求下单失败', 'result' => $ret));
                header('Location: '.$url);
                exit;
            }
        }
    }

    /**
     * 获取第三方用户的ID
     * @return mixed
     */
    public function getMemberBn(){
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));
        return $member_info['external_bn'];
//        return "c8f5f96740b73d8b6d01e24b871015d2";
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', __CLASS__);
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['orderId'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('平安支付');//TODO 这里是支付方式名称 确认
            $ret['pay_account'] = $mer_id;
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['amount']/100;//TODO 确认是否是这个字段 清算金额
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['amount']/100;
            $ret['trade_no'] = $recv['paySeq'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mpingan";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
        }else{
            $ret['status'] = 'invalid';
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

    /**
     * 平安银行生成签名方法
     * <功能详细描述>
     * @param $data
     * @return string
     */
    public function genSign($data) {
        $client_secret     = $this->getConf('salt',__CLASS__);  //签名密码（由内购网分配）
        ksort($data);   //进行排序
        $sign_arr = array();
        foreach ($data as $key => $value) {
            $sign_arr[] = $key . '=' . $value;
        }
        $sign_str = implode('&' , $sign_arr);   //client_id=102103f90901c29a798586cc64&external_user_id=test&name=测试用户1&surl=http://test.neigou.com/&time=1509372694
        $sign_str   = $sign_str.$client_secret;
        $sign = md5($sign_str); //462699cf49a5876592cd7703cc62ce13
        //按照文档顺序顺序排列好
        \Neigou\Logger::General('paysign.mpingan',array('linkS'=>$sign_str));
        return $sign;
    }




    public function request($api = '', $post_data = array(), $sign = 1) {
        $url = $api;
        if ($sign) {
            $post_data['sign'] = $this->genSign($post_data);
        }
        $curl = new \Neigou\Curl();
        //正式环境也不需要打开代理请求
//        $proxyServer = NEIGOU_HTTP_PROXY;
        $opt_config = array(
            CURLOPT_POST => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
//            CURLOPT_PROXY => $proxyServer,
        );
        $curl->SetOpt($opt_config);
        $curl->SetHeader('Content-Type', 'application/json');

        $result = $curl->Post($url, $post_data);

        $resultData = json_decode(json_decode($result, true),true);
        \Neigou\Logger::General('pay.mpingan', array('action' => 'req', 'opt_config' => $opt_config,'req_url'=>$url,'post_data'=>$post_data,'response_data'=>$result));
        return $resultData;
    }

    public function gen_form(){
        return '';
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
     * 【新】将数组转换成String
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
            $linkString .= $key  .'='. $value.'&' ;
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
        return $linkString;
    }




    /**
     * 【新】生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function get_html() {
        $encodeType =  'utf-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$this->submit_url}" method="post">

eot;
        foreach ( $this->fields as $key => $value ) {
            $html .= "    <input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
   <!-- <input type="submit" type="hidden">-->
    </form>
</body>
</html>
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
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $calc_sign = $this->genSign($param);
        if($sign==$calc_sign){
            return true;
        } else {
            \Neigou\Logger::General('check_sign.mpingan.err',array('sign'=>$calc_sign,'sign_req'=>$sign));
            return false;
        }
    }


}