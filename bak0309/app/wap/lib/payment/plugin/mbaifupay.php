<?php
/**
* 百付宝手机端
* version v1.0
* author zfc
* date 2016/9/23
* data https://b.baifubao.com/sp_register/0/page_controller/0?page=access_process
*/

final class wap_payment_plugin_mbaifupay extends ectools_payment_app implements ectools_interface_payment_app {

    public $display_name = '百付宝';
    public $ver = '1.0';
    public $app_name = '百付宝支付接口';
    public $app_key = 'mbaifupay';
    public $app_rpc_key = 'mbaifupay';
    public $curname = 'CNY';
    public $platform = 'iswap';
    public $name = '百付宝支付';

    /**
     * @var string 通用支付
     */
    public $is_general = 1;

    // 业务属性
    private $sign_method = 'MD5'; 
    private $input_charset = 'GBK';
    private $gateway = 'https://www.baifubao.com/api/0/pay/0/wapdirect/0';

    public function __construct(){
        parent::__construct($app);
        $this->return_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mbaifupay_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->return_url, $matches))
        {
            $this->return_url = str_replace('http://','',$this->return_url);
            $this->return_url = preg_replace("|/+|","/", $this->return_url);
            $this->return_url = "http://" . $this->return_url;
        }
        else
        {
            $this->return_url = str_replace('https://','',$this->return_url);
            $this->return_url = preg_replace("|/+|","/", $this->return_url);
            $this->return_url = "https://" . $this->return_url;
        }
        $this->page_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mbaifupay', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->page_url, $matches))
        {
            $this->page_url = str_replace('http://','',$this->page_url);
            $this->page_url = preg_replace("|/+|","/", $this->page_url);
            $this->page_url = "http://" . $this->page_url;
        }
        else
        {
            $this->page_url = str_replace('https://','',$this->page_url);
            $this->page_url = preg_replace("|/+|","/", $this->page_url);
            $this->page_url = "https://" . $this->page_url;
        }

        // $this -> return_url = str_replace('localhost:8081','wx.zhaofuchun.dev.neigou.com',$this -> return_url);
        // $this -> page_url = str_replace('localhost:8081','wx.zhaofuchun.dev.neigou.com',$this -> page_url);

        $this -> submit_url = $this -> gateway;
        $this -> submit_method = 'GET';
        $this -> submit_charset = $this -> input_charset;
    }

    public function admin_intro(){
        return '百度支付平台 https://vip.baifubao.com/user/0/login/0';
    }

    public function setting(){
        return array(
                    'pay_name'=>array(
                        'title'=>app::get('ectools')->_('支付方式名称'),
                        'type'=>'string',
                        'validate_type' => 'required',
                    ),
                    'mer_id'=>array(
                        'title'=>app::get('ectools')->_('合作者身份(parterID)'),
                        'type'=>'string',
                        'validate_type' => 'required',
                    ),
                    'mer_key'=>array(
                        'title'=>app::get('ectools')->_('交易安全校验码(key)'),
                        'type'=>'string',
                        'validate_type' => 'required',
                    ),
                    'order_by' =>array(
                        'title'=>app::get('ectools')->_('排序'),
                        'type'=>'string',
                        'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
                    ),
                    'support_cur'=>array(
                        'title'=>app::get('ectools')->_('支持币种'),
                        'type'=>'text hidden cur',
                        'options'=>$this->arrayCurrencyOptions,
                    ),
                    'real_method'=>array(
                        'title'=>app::get('ectools')->_('选择接口类型'),
                        'type'=>'select',
                        'options'=>array('0'=>app::get('ectools')->_('使用标准双接口'),'2'=>app::get('ectools')->_('使用担保交易接口'),'1'=>app::get('ectools')->_('使用即时到帐交易接口')),
                        'tip'=>'消费者如用担保交易充值，需在支付宝后台进行确认操作，充值款才可到账',
                    ),
                    'pay_fee'=>array(
                        'title'=>app::get('ectools')->_('交易费率'),
                        'type'=>'pecentage',
                        'validate_type' => 'number',
                    ),
                    'pay_brief'=>array(
                        'title'=>app::get('ectools')->_('支付方式简介'),
                         'type'=>'textarea',
                    ),
                    'pay_desc'=>array(
                        'title'=>app::get('ectools')->_('描述'),
                        'type'=>'html',
                        'includeBase' => true,
                    ),
                    'pay_type'=>array(
                         'title'=>app::get('ectools')->_('支付类型(是否在线支付)'),
                         'type'=>'hidden',
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

    public function intro(){
return app::get('ectools')->_('支付宝（中国）网络技术有限公司是国内领先的独立第三方支付平台，由阿里巴巴集团创办。支付宝致力于为中国电子商务提供“简单、安全、快速”的在线支付解决方案。').'
<a target="_blank" href="https://www.alipay.com/static/utoj/utojindex.htm">'.app::get('ectools')->_('如何使用支付宝支付？').'</a>';
    }

    public function dopay($payments){
        $id = $this->getConf('mer_id', __CLASS__);
        $key = $this->getConf('mer_key', __CLASS__);
        $this -> id = $id;
        $this -> key = $key;

        $subject = $payments['account'].$payments['payment_id'];
        $subject = str_replace("'",'`',trim($subject));
        $subject = str_replace('"','`',$subject);

        if (isset($payments['subject']) && $payments['subject']){
            $subject_tmp = $payments['subject'];
        }else{
            $subject_tmp = $subject;
        }

        $price = number_format($payments['cur_money'],2,".","");

        $params_must = array();
        $params_must['service_code'] = 1;
        $params_must['sp_no'] = $id;
        $params_must['order_create_time'] = date('YmdHis',time());//$payments['rel_id'];
        $params_must['order_no'] = $payments['payment_id'];
        $params_must['goods_name'] = mb_convert_encoding($subject_tmp, "GBK", "UTF-8");
        $params_must['goods_url'] = kernel::base_url(1) . '/m/member-orders.html';
        $params_must['total_amount'] = $price * 100;
        $params_must['currency'] = 1; //CNY
        $params_must['return_url'] = $this -> return_url;
        $params_must['page_url'] = $this -> page_url;
        $params_must['pay_type'] = 1; //默认余额支付
        $params_must['input_charset'] = 1; // GBK $this -> input_charset;
        $params_must['version'] = 2;
        // $params_must['goods_category'] = 1;
        $params_must['sign_method'] = 1; //MD5
        $sign = $this -> get_sign($params_must);
        $params_must['sign'] = $sign;

        foreach ($params_must as $k => $v) {
            $this->add_field($k,$v);
        }

        $url = http_build_query($params_must);
        $url = $this -> gateway . '?' . $url;
        header('Location: ' . $url);
        exit;

        // echo mb_convert_encoding($this->get_html(),"UTF-8","GBK");exit;
    }

    public function get_sign($arr){
        $arr = $this -> sort_array($arr);
        $arr['key'] = $this -> key;
        $str = $this -> build_str($arr);
        return md5($str);
    }

    public function sort_array($arr){
        ksort($arr);
        return $arr;
    }

    public function build_str($arr){
        $arr_temp = array ();
        foreach ($arr as $key => $val) {
            $arr_temp [] = $key . '=' . $val;
        }
        $sign_str = implode('&', $arr_temp);
        return $sign_str;
    }

    public function array_filter_empty($arr){
        foreach ($arr as $k => $v) {
            if($v == ''){
                unset($arr[$k]);
            }
        }
        return $arr;
    }

    public function is_fields_valiad(){
        return true;
    }

    public  function callback(&$recv){
        $ret = array();
        if($this -> is_return_vaild($recv)){
            $ret['payment_id'] = $recv['order_no'];
            $ret['account'] = $this -> id;
            $ret['bank'] = app::get('wap')->_('手机百付宝');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_amount'] / 100; 
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_amount'] / 100; 
            $ret['trade_no'] = $recv['bfb_order_no'];
            $ret['t_payed'] = (strtotime($recv['pay_time']) ? strtotime($recv['pay_time']) : time());
            $ret['pay_app_id'] = "mbaifupay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = '';
            if($recv['pay_result'] == 1){
                $ret['status'] = 'succ';
            }else{
                $ret['status'] =  'failed'; 
            }
        }else{
            $ret['status'] = 'invalid';
        }
        $ret['callback_source'] = 'client';
        return $ret;
    }

    public function is_return_vaild($request){
        $id = $this->getConf('mer_id', __CLASS__);
        $key = $this->getConf('mer_key', __CLASS__);
        $this -> id = $id;
        $this -> key = $key;
        $sign_temp = $request['sign'];
        unset($request['sign']);
        $sign = $this -> get_sign($request);
        if($sign == $sign_temp){
            return true;
        }else{
            #记录返回失败的情况
            logger::error(app::get('wap')->_('支付单号：') . $request['bfb_order_no'] . app::get('wap')->_('签名验证不通过，请确认！')."\n");
            logger::error(app::get('wap')->_('本地产生的加密串：') . $sign);
            logger::error(app::get('wap')->_('手机百付宝传递打过来的签名串：') . $sign_temp);
            $str_xml .= "<alipayform>";
            foreach ($request as $key=>$value){
                $str_xml .= "<$key>" . $value . "</$key>";
            }
            $str_xml .= "</alipayform>";
            return false;
        }
    }

    public function gen_form(){
        return '';
    }

}
