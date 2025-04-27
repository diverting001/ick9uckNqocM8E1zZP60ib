<?php

/**
 * 微信小程序 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mwxmini extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '微信小程序 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '微信小程序 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mwxmini';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mwxmini';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '微信小程序 支付';
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
    public $display_env = array('weixin_program');

    /**
     * 是否自动提交表单 1==>是
     * @var int
     */
    public $auto_submit = 0;

    /**
     * 自动提交表单 channel
     *
     * @var array
     */
    public $auto_submit_channel = array('aiguanhuai');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwxmini_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwxmini', 'callback');
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
        return '微信小程序 支付配置信息';
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
            'title'=>array(
                'title'=>app::get('ectools')->_('支付页面显示标题'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_id'=>array(
                'title'=>app::get('ectools')->_('小程序app_id'),
                'type'=>'string',
                'validate_type' => '',
            ),
            'app_secret'=>array(
                'title'=>app::get('ectools')->_('小程序app_secret'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'mch_id'=>array(
                'title'=>app::get('ectools')->_('微信商户ID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pay_sign'=>array(
                'title'=>app::get('ectools')->_('微信商户支付sign'),
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
        return app::get('ectools')->_('渠道 我买网微信小程序内 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array |提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        //直接跳转到小程序端
        //保存当前的payment_id
        //小程序提交code 发起支付
        $sign_key = md5($payment['payment_id']);
        //保存当前的支付信息
        $_redis = kernel::single('base_sharedkvstore');
        $payment['company_id'] = kernel::single("b2c_member_company")->get_cur_company();
        $_redis->store('store:mwxmini:payment_info',$sign_key,$payment,300);
        $this->add_field('sign_key',$sign_key);
        echo $this->get_html();
    }

    public function GetOpenId($channel,$external_bn,$app_id){
        $request = kernel::single('b2c_member_thirdMember');
        $res = $request->GetThirdMemberInfo($channel,$external_bn);
        if($res){
            if(isset($res[$app_id])){
                return $res[$app_id];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getPayData($sign_key,$app_id=''){
        $_redis = kernel::single('base_sharedkvstore');
        $payment = array();
        $_redis->fetch('store:mwxmini:payment_info',$sign_key,$payment);

        //获取用户的open_id
        $_third_member = app::get('b2c') -> model('third_members');
        $cas_model = kernel::single('b2c_cas_member');
        $member_id_list = $cas_model->getMemberIdList($payment['member_id']);
        //获取公司和渠道
        $company_id = $payment['company_id'];
        $channel = app::get('b2c')->model('club_company')->getCompanyRealChannel($company_id);
        $third_member_info = $_third_member -> getRowByFiled(array('channel' => $channel,'internal_id|in' => $member_id_list));
        $open_id = $this->GetOpenId($channel,$third_member_info[0]['external_bn'],$app_id);

        $req['bank_type'] = 'WX';
        $req['body'] = strval( str_replace(' ', '', (isset($payment['body']) && $payment['body']) ? $payment['body'] : app::get('weixin')->_('内购网订单') ) );
        $req['mch_id'] = $this->getConf('mch_id',__CLASS__);
        $req['out_trade_no'] = strval( $payment['payment_id'] );
        $req['total_fee'] = number_format(strval( bcmul($payment['cur_money'], 100) ),0,'','');
        $req['notify_url'] = $this->notify_url;
        $req['spbill_create_ip'] = $payment['ip'];
        $req['input_charset'] = "UTF-8";
        $req['trade_type'] = "JSAPI";
        $req['openid'] = $open_id;
        $prepay_id = $this->get_prepay_id($app_id,$this->getConf('pay_sign',__CLASS__),$req);
        $req['prepay_id'] = $prepay_id;

        $time = time();
        $native['appId'] = $app_id;
        $native['timeStamp'] = strval($time);
        $native['nonceStr'] = weixin_commonUtil::create_noncestr();
        $native['package'] = 'prepay_id='.$prepay_id;
        $native['signType'] = 'MD5';
        ksort($native);
        $bizString = $this->formatQueryParaMap($native,false);
        $native['paySign'] = strtoupper(md5($bizString.'&key='.$this->getConf('pay_sign',__CLASS__)));
        $success_url = app::get('wap')->router()->gen_url(array('app'=> 'b2c','ctl'=>'wap_paycenter2','act'=>'result_wait','full'=>1,'arg0'=>$payment['order_id'],'arg1'=>'true'));
        $failure_url = app::get('wap')->router()->gen_url(array('app'=> 'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','full'=>1,'arg0'=>$payment['order_id'],'arg1'=>'result_placeholder'));
        $native['success_url'] = $success_url;
        $native['failure_url'] = $failure_url;
        $native['prepay_id'] = $prepay_id;
        return array('payment'=>$native);
    }

    function formatQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v){
            if (null != $v && "null" != $v && "sign" != $k) {
                if($urlencode){
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }

    private function get_prepay_id($appId,$paySignKey,$req){
        $prepay_id = weixin_commonUtil::getPrepayId($appId,$paySignKey,$req);
        if ($prepay_id =='' ) {
            $prepay_id = weixin_commonUtil::getPrepayId($appId,$paySignKey,$req);
        }
        return $prepay_id;
    }

    public function jsReturn(){
        $js = '
<script>

            function callWxPay() {
                wx.miniProgram.navigateTo({
                    url:\'/pages/wxpay/wxpay?'.$this->fields['url_param'].'\',
                    success: function(){
                        console.log(\'success\')
                    },
                    fail: function(){
                        console.log(\'跳转回小程序的页面fail\');
                    },
                });
            }    
            callWxPay();
</script>';
        $return['js'] = $js;
        $return['init'] = 'callpay';
        return json_encode($return);
    }


    protected function get_html(){
        $str = $this->fields['str'];
        $title = $this->getConf('title',__CLASS__);
        $strHtml = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="maximum-scale=1.0,minimum-scale=1.0,user-scalable=0,width=device-width,initial-scale=1.0"/>
    <title>微信支付</title>
    <script type="text/javascript" src="//res.wx.qq.com/open/js/jweixin-1.3.2.js"></script>
</head>
<body>
微信支付中，请稍后……
<script>
    function callWxZPay() {
        wx.miniProgram.navigateTo({
            url:\'/pages/neigou_login/pay?sign_key='.$this->fields['sign_key'].'\',
            success: function(){
                console.log(\'success\')
            },
            fail: function(){
                console.log(\'跳转回小程序的页面fail\');
            },
        });
    }
    callWxZPay();
</script>
</body>
</html>';
        return $strHtml;
    }

    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $linkStr = $this->_create_link_string($data,true,true);
        $linkStr .= 'key='.$this->getConf('md5_key',__CLASS__);
        \Neigou\Logger::General('ecstore.mwxmini.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
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




    /**
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
       exit('deny');
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
}