<?php

/**
 * 我买网对接 微信环境内 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mwxwomai extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '渠道 我买网微信内 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '渠道 我买网微信内 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mwxwomai';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mwxwomai';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '渠道 我买网微信内 支付';
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
    public $display_env = array('weixin');

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwxwomai_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mwxwomai', 'callback');
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
        return '渠道 我买网微信内 支付配置信息';
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
                'title'=>app::get('ectools')->_('支付标题title'),
                'type'=>'string',
            ),
            'mer_id'=>array(
                'title'=>app::get('ectools')->_('商户代码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pay_code'=>array(
                'title'=>app::get('ectools')->_('pay_code'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'md52_key'=>array(
                'title'=>app::get('ectools')->_('md5 openid Key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),

            'md5_key'=>array(
                'title'=>app::get('ectools')->_('md5 Key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sha1_key'=>array(
                'title'=>app::get('ectools')->_('sha1 Key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'code'=>array(
                'title'=>app::get('ectools')->_('活动代码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('订单提交URL'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'oauth_url'=>array(
                'title'=>app::get('ectools')->_('oauth_url'),
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
        return app::get('ectools')->_('渠道 我买网微信内 支付配置信息');
    }

    /**
     * 提交支付信息的接口
     * @param array |提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        $_redis = kernel::single('base_sharedkvstore');
        $temp_id = '';
        //1.查询是否是跳转回来的请求
        $_redis->fetch('payment_mwxwomai_payment_id_',$payment['order_id'],$temp_id);
        if($temp_id){
            $payment['payment_id'] = $temp_id;
            $payment_info = array();
            $_redis->fetch('payment_mwxwomai_info_',$payment['payment_id'],$payment_info);
            $payment['member_id'] = $payment_info['userId'];
        }

        $time = $this->getMillisecond();
        $this->add_field('payCode', $this->getConf('pay_code',__CLASS__));//支付方式标识
        $this->add_field('userId', $payment['member_id']);//用户唯一标识
        $this->add_field('orderSource', 'neigou');//渠道标识
        $this->add_field('nonceStr', $time);//订单JSON
        $this->add_field('outTradeNo', 'NGDD'.$payment['payment_id']);//交易订单编号
        $this->add_field('wxType', 'PUB');//微信支付的类型
        $this->add_field('timeStamp', $time);//交易时间 TODO确认时间戳格式

        //2.获取openid 如果不存在 跳转获取
        $open_id = '';
        $_redis -> fetch('payment_mwxwomai_open_id_',$payment['payment_id'],$open_id);
        if(empty($open_id)){
            //先保存当前支付单信息
            $_redis->store('payment_mwxwomai_info_',$payment['payment_id'],$this->fields,300);
            //开始调用获取openid接口
            $redirect_url = $this->getConf('oauth_url',__CLASS__).'?partner='.$this->getConf('mer_id',__CLASS__).'&payment_id='.$payment['payment_id'];
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=wx7b37229179b64bf3&redirect_uri='.urlencode($redirect_url).'%26flags%3D111111111&response_type=code&scope=snsapi_base&state=STATE#wechat_redirect';
            header('Location: '.$url);die;

        } else {
            $this->add_field('openId',$open_id);
            $this->add_field('sign',$this->genSign($this->fields));
            $response = $this->request($this->getConf('submit_url',__CLASS__),$this->fields);
            $response['data'] = json_decode($response['data'],true);
            if(isset($response['data']['appid']) && $response['msg'] = '成功'){
                $package["appId"] = $response['data']['appid'];
                $package["package"] = $response['data']['packageValue'];
                $package["timeStamp"] = $response['data']['timestamp'];
                $package["nonceStr"] = $response['data']['noncestr'];
                $package["signType"] = 'MD5';
                $package["mweburl"] = $response['data']['mweb_url'];
                $package["paySign"] = $response['data']['paySign'];
                echo $this->get_html(json_encode($package),$payment['order_id']);
            } else {
                $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
                \Neigou\Logger::General('ecstore.mwomai.do_pay', array('action' => 'pay_fail', 'result' => $response));
                header('Location: '.$url);die;
            }
            die;
        }
    }

    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }

    protected function get_html($create_biz_package,$order_id){
        // 微信提交支付,调用微信内置js
        // header("Content-Type: text/html;charset=".$this->submit_charset);

        $app = 'b2c';
        $title = $this->getConf('title',__CLASS__);

        $success_url = app::get('wap')->router()->gen_url(array('app'=> $app,'ctl'=>'wap_paycenter2','act'=>'result_wait','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));
        $failure_url = app::get('wap')->router()->gen_url(array('app'=> $app,'ctl'=>'wap_paycenter2','act'=>'result_failed','full'=>1,'arg0'=>$order_id,'arg1'=>'result_placeholder'));
        $strHtml = '<html>
        		   <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
        		   <title>' . $title . '订单支付</title>
                    <script language="javascript">
                    function callpay()
                    {
                        WeixinJSBridge.invoke(
					                "getBrandWCPayRequest",
					                '. $create_biz_package .',
					                function(res){
					                    if(res.err_msg == "get_brand_wcpay_request:ok"){
			                                window.location.href = "' . $success_url . '";
			                            }else{
			                                //alert("支付失败，请重新支付，或联系客服：400-6666-365");
                                            failure_url = "' . $failure_url . '";
                                            failure_url = failure_url.replace(/result_placeholder/, encodeURIComponent(res.err_msg));
			                                window.location.href = failure_url;
			                            }
					                }
					            );
                    }
                    // 当微信内置浏览器完成内部初始化后会触发WeixinJSBridgeReady事件。
                    if (typeof WeixinJSBridge == "undefined"){
					   if( document.addEventListener ){
					       document.addEventListener("WeixinJSBridgeReady", callpay, false);
					   }else if (document.attachEvent){
					       document.attachEvent("WeixinJSBridgeReady", callpay);
					       document.attachEvent("onWeixinJSBridgeReady", callpay);
					   }
					}else{
					   callpay();
					}

                    </script>
                    <body>
                    <button type="button" id="btn_pay" onclick="callpay()" style="display:none;">微信支付</button>
			         <script>
					document.getElementById("btn_pay").click();
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
        \Neigou\Logger::General('ecstore.mwomai.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
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
        \Neigou\Logger::General('ecstore.mwomai.req', array('action' => 'do req','req_url'=>$api,'post_data'=>$post_data,'response_data'=>$result));
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
        $sign = $param['sign'];
        unset($param['sign']);
        $req_sign = $this->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
}