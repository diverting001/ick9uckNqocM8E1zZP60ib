<?php
final class wap_payment_plugin_mbeifupay extends ectools_payment_app implements ectools_interface_payment_app {
    public $name = '第三方支付'; 

    public $app_name = '第三方支付接口';

    public $app_key = 'mbeifupay';

    public $app_rpc_key = 'mbeifupay';

    public $display_name = '第三方支付';

    public $curname = 'CNY';

    public $ver = '1.0';

    public $platform = 'iswap';

    public $supportCurrency = array("CNY"=>"01");

    private $id = '';
    private $key = '';

    public function __construct($app){
        parent::__construct($app);
        $this->return_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mbeifupay_server', 'callback');
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
        $this->page_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mbeifupay', 'callback');
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

        $this->submit_url = '';
        $this->submit_method = 'GET';
        $this->submit_charset = 'utf-8';

        $this -> gateways = json_decode(BEIFUPAY_API_URL,true);

        $mer_id = $this->getConf('mer_id', __CLASS__);
        $mer_key = $this->getConf('mer_key', __CLASS__);
        $this -> id = $mer_id;
        $this -> key = $mer_key;
    }

    /**
     * 显示支付接口后台的信息
     * @params null
     * @return string - 显示的信息，html格式
     */
    public function admin_intro(){
        return '第三方在线支付 移动版';
    }
    
    /**
     * 设置后台的显示项目（表单项目）
     * @params null
     * @return array - 配置的表单项
     */
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
                    'status'=>array(
                        'title'=>app::get('ectools')->_('是否开启此支付方式'),
                        'type'=>'radio',
                        'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
                        'name' => 'status',
                    ),
                );        
    }
    
    /**
     * 前台在线支付列表相应项目的说明
     * @params null
     * @return string - html格式的
     */
    public function intro(){
        return "北付宝在线支付";
    }
    
    /**
     * 支付表单的提交方式
     * @params array - 提交的表单数据
     * @return html - 自动提交的表单
     */
    public function dopay($payments){
        $_third_member = app::get('b2c') -> model('third_members');
        $_order = app::get('b2c') -> model('order_items');
        $_order_model = app::get('b2c') -> model('orders');
        $order_info = $_order_model -> getRow('*',array('order_id' => $payments['order_id']));

        //@TODO maojz 兼容pop订单
        if( empty($order_info)){
            $order_info = kernel::single("b2c_service_order")->getOrderInfo($payments['order_id']);

        }
        $cas_model = kernel::single('b2c_cas_member');
        $member_id_list = $cas_model->getMemberIdList($payments['member_id']);
        $third_member_info = $_third_member -> getRowByFiled(array('channel|in' => 'louxiaoyi_beiqi','internal_id|in' => $member_id_list));
        if(count($third_member_info)<=0 || !$third_member_info[0]['external_bn']){
            header('Location: /m/member-orders-nopayed.html');
            exit;
        }

        if(!array_key_exists($order_info['company_id'],$this -> gateways)){
            header('Location: /m/member-orders-nopayed.html');
            exit;
        }

        $this -> submit_url = $this -> gateways[$order_info['company_id']];

        $external_bn = $third_member_info[0]['external_bn'];

        $_time = time();
        $price = number_format($payments['cur_money'],2,".","");
        // $subject = $payments['account'].$payments['payment_id'];
        // $subject = str_replace("'",'`',trim($subject));
        // $subject = str_replace('"','`',$subject);

        // $list = $_order -> getList('nums,name',array('order_id' => $payments['order_id']),0,-1,array('item_id','asc'));
        $subject = '内购网合作平台订单';
        // foreach ($list as $k => $v) {
        //     $subject .= $v['name'] . ' x ' . $v['nums'] . ' ';
        // }
        
        $params = array();
        $params['appID'] = $this -> id;
        $params['timestamp'] = $_time;
        $params['transactionId'] = md5($_time);
        $params['asyncMode'] = 'true';
        $params['signMethod'] = 'MD5';
        $params['inputcharset'] = $this->submit_charset;
        $params['callbackUrl'] = $this->return_url;

        $params['jobcode'] = $external_bn;
        $params['payserial'] = $payments['payment_id'];
        $params['ordercode'] = $payments['order_id'];
        $params['orderdetails'] = $subject;
        $params['totalmoney'] = $price * 100;
        $params['requesturl'] = $this->page_url;
        $sign = $this -> mkSign($params);
        $params['sign'] = $sign;

        $this -> fields = $params;        
        echo $this -> get_html();
        exit;
    }

    public function mkSign($params = array()){
        ksort($params);
        // $params['appPass'] = $this -> key;
        $str = $this -> bulidReq($params);
        $str .= $this -> key;
        $sign = md5($str);
        return $sign;
    }

    public function bulidReq($params){
        $temp = array();
        foreach ($params as $k => $v) {
            $temp[] = "{$k}={$v}";
        }
        $str = implode('&',$temp);  
        return $str;
    }

    public function ckSign($params = array()){
        if(!$params['sign']) return false;
        $sign = $params['sign'];
        unset($params['sign']);
        $sign_str = $this -> mkSign($params);
        if($sign === $sign_str)
            return true;
        return false;
    }
    
    /**
     * 验证提交表单数据的正确性
     * @params null
     * @return boolean 
     */
    public function is_fields_valiad(){
        return true;
    }
    
    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv){
        $ret = array();
        $ret['callback_source'] = 'client';

        if($recv['trade_status'] != 0 && $recv['trade_status'] != 1){
            $ret['status'] = 'failed';
            return $ret;
        }
        if(!$recv['ordercode']){
            $ret['status'] = 'failed';
            return $ret;
        }
        if(!isset($recv['totalmoney'])){
            $ret['status'] = 'failed';
            return $ret;
        }
        if(!$recv['payserial']){
            $ret['status'] = 'failed';
            return $ret;
        }

        if(!$this -> ckSign($recv)){
            $ret['status'] = 'invalid'; 
            return $ret;
        }else{
            $ret['payment_id'] = $recv['payserial'];
            $ret['account'] = $this -> id;
            $ret['bank'] = app::get('wap')->_('北付宝');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['totalmoney'] / 100; 
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['totalmoney'] / 100; 
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = (strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time());
            $ret['pay_app_id'] = "mbeifupay";
            $ret['pay_type'] = 'online';
            $ret['memo'] = ''; 
            $ret['status'] = 'succ';
            return $ret;
        }
    }
    
    /**
     * 生成支付表单 - 自动提交(点击链接提交的那种方式，通常用于支付方式列表)
     * @params null
     * @return null
     */
    public function gen_form(){
        echo '';
    }

}