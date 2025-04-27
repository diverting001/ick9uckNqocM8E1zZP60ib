<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2012 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/**
 * alipay支付宝手机支付接口
 * @auther shopex ecstore dev dev@shopex.cn
 * @version 0.1
 * @package ectools.lib.payment.plugin
 */

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//  desc  :wap alipay update v2.2 2016/8/16
//  author:zhaofuchun@neigou.com
//  usage :https://support.open.alipay.com/doc2/detail.htm?spm=a219a.7386797.0.0.7pv3AS&treeId=60&articleId=104790&docType=1
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

final class wap_payment_plugin_malipay2 extends ectools_payment_app implements ectools_interface_payment_app {

    /**
     * @var string 支付方式名称
     */
    public $name = '手机支付宝2';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '手机支付宝2';
     /**
     * @var string 支付方式key
     */
    public $app_key = 'malipay2';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'malipay2';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '手机支付宝2';
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
    public $display_env = array('h5','ivwcustomer');

    /**
     * @var string 通用支付
     */
    public $is_general = 1;
    /**
     * @支付宝固定参数
     */
    public $Service_Paychannel = "mobile.merchant.paychannel";
    public $Service1 = "alipay.wap.trade.create.direct";    //接口1
    public $Service2 = "alipay.wap.auth.authAndExecute";    //接口2
    public $format = "xml";    //http传输格式
    public $sec_id = 'MD5';    //签名方式 不需修改
    public $_input_charset = 'utf-8';    //字符编码格式
    public $_input_charset_GBK = "GBK";
    public $v = '2.0';    //版本号




    public $submit_url = 'https://openapi.alipay.com/gateway.do';
    public $submit_method = 'POST';
    public $submit_charset = 'UTF-8';
    /**
     * @var string 销售产品码，商家和支付宝签约的产品码。该产品请填写固定值：QUICK_WAP_WAY
     */
    public $product_code = 'QUICK_WAP_PAY';
    public $sign_type = 'RSA2';

    public $app_id = '';
    public $rsa_public_key = '';
    public $rsa_private_key = '';
    public $zfb_public_key = '';
    private $seller_id = '';


    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_malipay2_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches))
        {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        }
        else
        {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_malipay2', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches))
        {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        }
        else
        {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
        $app_id_save = $this->getConf('app_id', __CLASS__);
        if(!empty($app_id_save)){
            $this->app_id = $app_id_save;
        }
        $this->sign_type = $this->getConf('sign_type', __CLASS__);


    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        $regIp = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:$_SERVER['HTTP_HOST'];
        return '<img src="' . $this->app->res_url . '/payments/images/ALIPAY.gif"><br /><b style="font-family:verdana;font-size:13px;padding:3px;color:#000"><br>ShopEx联合支付宝推出优惠套餐：无预付/年费，单笔费率低至0.7%-1.2%，无流量限制。</b><div style="padding:10px 0 0 388px"><a  href="javascript:void(0)" onclick="document.ALIPAYFORM.submit();"><img src="' . $this->app->res_url . '/payments/images/alipaysq.png"></a></div><div>如果您已经和支付宝签约了其他套餐，同样可以点击上面申请按钮重新签约，即可享受新的套餐。<br>如果不需要更换套餐，请将签约合作者身份ID等信息在下面填写即可，<a href="http://www.shopex.cn/help/ShopEx48/help_shopex48-1235733634-11323.html" target="_blank">点击这里查看使用帮助</a><form name="ALIPAYFORM" method="GET" action="http://top.shopex.cn/recordpayagent.php" target="_blank"><input type="hidden" name="postmethod" value="GET"><input type="hidden" name="payagentname" value="支付宝"><input type="hidden" name="payagentkey" value="ALIPAY"><input type="hidden" name="market_type" value="from_agent_contract"><input type="hidden" name="customer_external_id" value="C433530444855584111X"><input type="hidden" name="pro_codes" value="6AECD60F4D75A7FB"><input type="hidden" name="regIp" value="'.$regIp.'"><input type="hidden" name="domain" value="'.$this->app->base_url(true).'"></form></div>';
    }

     /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('wap')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'seller_id'=>array(
                'title'=>app::get('wap')->_('合作者身份(parterID)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_id'=>array(
                'title'=>app::get('wap')->_('应用appID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'rsa_public_key'=>array(
                'title'=>app::get('wap')->_('加密共钥(public_key)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'rsa_private_key'=>array(
                'title'=>app::get('wap')->_('加密私钥(private_key)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'zfb_public_key'=>array(
                'title'=>app::get('wap')->_('支付宝公钥(zfb_public_key)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sign_type'=>array(
                'title'=>app::get('wap')->_('签名方式 内购 cofco选择 RSA2 juyou RSA'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('wap')->_('排序'),
                'type'=>'string',
                'label'=>app::get('wap')->_('整数值越小,显示越靠前,默认值为1'),
            ),

            'pay_fee'=>array(
                'title'=>app::get('wap')->_('交易费率'),
                'type'=>'pecentage',
                'validate_type' => 'number',
            ),
            'pay_brief'=>array(
                'title'=>app::get('wap')->_('支付方式简介'),
                'type'=>'textarea',
            ),
            'pay_desc'=>array(
                'title'=>app::get('wap')->_('描述'),
                'type'=>'html',
                'includeBase' => true,
            ),
            'pay_type'=>array(
                'title'=>app::get('wap')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'is_general'=>array(
                'title'=>app::get('wap')->_('通用支付(是否为缺省通用支付)'),
                'type'=>'radio',
                'options'=>array('0'=>app::get('wap')->_('否'),'1'=>app::get('wap')->_('是')),
            ),
            'status'=>array(
                'title'=>app::get('wap')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
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
        return app::get('wap')->_('支付宝（中国）网络技术有限公司是国内领先的独立第三方支付平台，由阿里巴巴集团创办。支付宝致力于为中国电子商务提供“简单、安全、快速”的在线支付解决方案。').'
<a target="_blank" href="https://www.alipay.com/static/utoj/utojindex.htm">'.app::get('wap')->_('如何使用支付宝支付？').'</a>';
    }



    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    // todo v2.2
    public function dopay($payment){
        $this->fields = array();
        $rsa_public_key_save = $this->getConf('rsa_public_key', __CLASS__);
        if(!empty($rsa_public_key_save)){
            $this->rsa_public_key = $rsa_public_key_save;
        }
        $rsa_private_key_save = $this->getConf('rsa_private_key', __CLASS__);
        if(!empty($rsa_private_key_save)){
            $this->rsa_private_key = $rsa_private_key_save;
        }



        {
            $app_id = $this->app_id;
            $charset = $this->submit_charset;
            $method = 'alipay.trade.wap.pay';
            $return_url = $this->callback_url;//同步
            $sign_type = $this->sign_type;
            $timestamp = date('Y-m-d H:m:s',time());
            $version = $this->ver;
            $notify_url = $this->notify_url;

            $this->add_field('app_id', $app_id);
            $this->add_field('method', $method);
            $this->add_field('format', 'json');
            $this->add_field('return_url', $return_url);
            $this->add_field('charset', $charset);


            $this->add_field('sign_type', $sign_type);
            $this->add_field('timestamp', $timestamp);
            $this->add_field('version', $version);
            $this->add_field('notify_url', $notify_url);
            $this->add_field('alipay_sdk', 'alipay-sdk-php-20161101');

        }

        {
            $subject = '订单支付（'.$payment['payment_id'].")";
            $subject = str_replace("'",'`',trim($subject));
            $subject = str_replace('"','`',$subject);
            $out_trade_no = $payment['payment_id'];
            $product_code = $this->product_code;
            $total_amount = number_format($payment['cur_money'],2,".","");

            if (isset($payment['subject']) && $payment['subject']){
                $subject = $payment['subject'];
            }
            //$subject = '1';
            if (isset($payment['body']) && $payment['body']){
                $body_text = $this->subtext($payment['body'],30);
                $body = $body_text;
            }else{
                $body = app::get('ectools')->_('网店订单');
            }
            //$goods_detail = '';
            //$passback_params = '';
            //$extend_params = '';
            $goods_type = '1';

            //判断公司是否是24小时可以支付的
            $_third_company = app::get('b2c') -> model('third_company');
            $company_list_for24hours = $_third_company -> getCompanyByChannel(array('louxiaoyi_beiqi'));
            $company_list_for24hours = array_merge($company_list_for24hours, (array)explode(',',CANCEL_ORDER_24));

            $company_id = kernel::single("b2c_member_company")->get_cur_company();

            if(in_array($company_id,$company_list_for24hours)){
                $expire_time = 1440;
            } else {
                $expire_time = 39;
            }
            $spend = time()-$payment['create_time'];
            $expire = ceil($expire_time-$spend/60);

            $timeout_express = "{$expire}m";

            //$biz_content =
            $biz_content_arr = array();
            $biz_content_arr['subject'] = $subject;
            $biz_content_arr['out_trade_no'] = $out_trade_no;
            $biz_content_arr['product_code'] = $product_code;
            $biz_content_arr['total_amount'] = $total_amount;
            $biz_content_arr['body'] = $body;

            //添加身份验证参数
            if($payment['is_certification']){
                $card_id = $payment['card_id'];
                $card_name = $payment['card_name'];
                $check = array();
                $check['need_check_info'] = 'T';
                $check['name'] = $card_name;
                $check['cert_type'] = 'IDENTITY_CARD';
                $check['cert_no'] = $card_id;
                $biz_content_arr['ext_user_info'] = $check;
                \Neigou\Logger::General('ecstore.alipay2.global',array('remark'=>'支付宝身份校验参数','card_id'=>$payment['card_id'],'card_name'=>$payment['card_name'],'payment'=>$payment));
            }

            //$biz_content_arr['goods_detail'] = $goods_detail;
            //$biz_content_arr['passback_params'] = $passback_params;
            //$biz_content_arr['extend_params'] = $extend_params;
            //$biz_content_arr['goods_type'] = $goods_type;
            $biz_content_arr['timeout_express'] = $timeout_express;
            $biz_content = json_encode($biz_content_arr,256);
            $this->add_field('biz_content', $biz_content);
        }

        //$app_pay = 'Y';
        $member_id = $payment['member_id'];
        $_member = app::get('b2c') -> model('members');
        $member_info = $_member -> getRow('*',array('member_id' => $member_id));
        // 度生活 & IOS 2.1.8 不做支付宝唤起
        if(in_array($member_info['company_id'],explode(',',NO_MALIPAY_APP)) ||
            (
                isset($_COOKIE['phoneType'])
                && isset($_COOKIE['versionCode'])
                && strtolower($_COOKIE['phoneType']) == 'app_ios'
                && version_compare($_COOKIE['versionCode'],'2.1.8','<')
            )
        ){
            $app_pay = '';
        }
//        if($app_pay){
//            $this->add_field('app_pay', $app_pay);
//        }
        $a =  $this->getSignContent($this->fields);
        $sign = $this->sign($a,$this->sign_type);
        $this->add_field('sign', $sign);
        \Neigou\Logger::General("pay.malipay2", array("action"=>"dopaymalipay2",
            'sparam1' => 'wap',
            'sparam2' => json_encode($this->fields)
        ));
        echo $this->get_html();exit;

    }
    /**
     * 建立请求，以表单HTML形式构造（默认）
     * @param $para_temp 请求参数数组
     * @return 提交表单HTML文本
     */
    protected function get_html() {
        $fields_data = $this->fields;
        $sHtml = "<form id='alipaysubmit' name='alipaysubmit' action='".$this->submit_url."?charset=".trim($this->submit_charset)."' method='POST'>";
        while (list ($key, $val) = each ($fields_data)) {
            if (false === $this->checkEmpty($val)) {
                //$val = $this->characet($val, $this->postCharset);
                $val = str_replace("'","&apos;",$val);
                //$val = str_replace("\"","&quot;",$val);
                $sHtml.= "<input type='hidden' name='".$key."' value='".$val."'/>";
            }
        }

        //submit按钮控件请不要含有name属性
        $sHtml = $sHtml."<input type='submit' value='ok' style='display:none;''></form>";

        $sHtml = $sHtml."<script>document.forms['alipaysubmit'].submit();</script>";

        return $sHtml;
    }

    /**
     * 创建mobile_merchant_paychannel接口
    */
    function mobile_merchant_paychannel($pms0, $merchant_key) {
        $_key = $merchant_key;                       //MD5校验码
        $sign_type    = $pms0['sign_type'];          //签名类型，此处为MD5
        $parameter = $this->para_filter($pms0);      //除去数组中的空值和签名参数
        $sort_array = $this->arg_sort($parameter);   //得到从字母a到z排序后的签名参数数组
        $mysign = $this->build_mysign($sort_array, $_key, $sign_type); //生成签名
        $req_data = $this->create_linkstring($parameter).'&sign='.urlencode($mysign).'&sign_type='.$sign_type;  //配置post请求数据，注意sign签名需要urlencode

        //模拟get请求方法
        //$result = $this->get($this->gateway_paychannel,$req_data);
        $url = $this->gateway_paychannel . $req_data;
        $result = kernel::single('base_httpclient')->get($url);
        //调用处理Json方法
        $alipay_channel = $this->getJson($result,$_key,$sign_type);
        return $alipay_channel;
    }

    /**
     * 验签并反序列化Json数据
     */
    function getJson($result,$m_key,$m_sign_type){
        //获取返回的Json
        $json = $this->getDataForXML($result,'/alipay/response/alipay/result');
        //拼装成待签名的数据
        $data = "result=" . $json . $m_key;
        //$json="{\"payChannleResult\":{\"supportedPayChannelList\":{\"supportTopPayChannel\":{\"name\":\"储蓄卡快捷支付\",\"cashierCode\":\"DEBITCARD\",\"supportSecPayChannelList\":{\"supportSecPayChannel\":[{\"name\":\"农行\",\"cashierCode\":\"DEBITCARD_ABC\"},{\"name\":\"工行\",\"cashierCode\":\"DEBITCARD_ICBC\"},{\"name\":\"中信\",\"cashierCode\":\"DEBITCARD_CITIC\"},{\"name\":\"光大\",\"cashierCode\":\"DEBITCARD_CEB\"},{\"name\":\"深发展\",\"cashierCode\":\"DEBITCARD_SDB\"},{\"name\":\"更多\",\"cashierCode\":\"DEBITCARD\"}]}}}}}";
        //获取返回sign
        $aliSign = $this->getDataForXML($result,'/alipay/sign');
        //转换待签名格式数据，因为此mapi接口统一都是用GBK编码的，所以要把默认UTF-8的编码转换成GBK，否则生成签名会不一致
        $data_GBK = mb_convert_encoding($data, "GBK", "UTF-8");
        //生成自己的sign
        $mySign = $this->sign($data_GBK,$m_sign_type);
        //判断签名是否一致
        if($mySign==$aliSign){
            //echo "签名相同";
            //php读取json数据
            return json_decode($json);
        }else{
            //echo "验签失败";
            return "验签失败";
        }
    }


    /**
     * 创建alipay.wap.trade.create.direct接口
     */
    public function alipay_wap_trade_create_direct($pms1, $merchant_key){
        $_key       = $merchant_key;                  //MD5校验码
        $sign_type  = $pms1['sec_id'];              //签名类型，此处为MD5
        $parameter  = $this->para_filter($pms1);      //除去数组中的空值和签名参数
        $req_data   = $pms1['req_data'];
        $format     = $pms1['format'];                //编码格式，此处为utf-8
        $sort_array = $this->arg_sort($parameter);    //得到从字母a到z排序后的签名参数数组
        $mysign     = $this->build_mysign($sort_array, $_key, $sign_type);    //生成签名
        $req_data   = $this->create_linkstring($parameter).'&sign='.urlencode($mysign);    //配置post请求数据，注意sign签名需要urlencode

        //Post提交请求
        $res = kernel::single('base_httpclient')->post($this->gateway,$req_data);
        //$url = $this->gateway.$req_data;
        //$res = kernel::single('base_httpclient')->get($url);
        //调用GetToken方法，并返回token
        return $this->getToken($res,$_key,$sign_type);
    }

    /**
     * 返回token参数
     * 参数 result 需要先urldecode
     */
    function getToken($result,$_key,$gt_sign_type){
        $result = urldecode($result);               //URL转码
        $Arr = explode('&', $result);               //根据 & 符号拆分

        $temp = array();                            //临时存放拆分的数组
        $myArray = array();                         //待签名的数组
        //循环构造key、value数组
        for ($i = 0; $i < count($Arr); $i++) {
            $temp = explode( '=' , $Arr[$i] , 2 );
            $myArray[$temp[0]] = $temp[1];
        }

        $sign = $myArray['sign'];                                               //支付宝返回签名
        $myArray = $this->para_filter($myArray);                                       //拆分完毕后的数组
        $sort_array = $this->arg_sort($myArray);                                       //排序数组
        $mysign = $this->build_mysign($sort_array,$_key,$gt_sign_type); //构造本地参数签名，用于对比支付宝请求的签名

        if($mysign == $sign)  //判断签名是否正确
        {
            return $this->getDataForXML($myArray['res_data'],'/direct_trade_create_res/request_token');    //返回token
        }else{
            echo('签名不正确');      //当判断出签名不正确，请不要验签通过
            return '签名不正确';
        }
    }


    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv)
    {
        $seller_id_save = $this->getConf('seller_id', __CLASS__);
        if(!empty($seller_id_save)){
            $this->seller_id = $seller_id_save;
        }

        $zfb_public_key_save = $this->getConf('zfb_public_key', __CLASS__);
        if(!empty($zfb_public_key_save)){
            $this->zfb_public_key = $zfb_public_key_save;
        }

        if($this->rsaCheck($recv,$this->sign_type)){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $this->seller_id;
            $ret['bank'] = app::get('wap')->_('手机支付宝');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_amount'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_amount'];
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = (strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time());
            $ret['pay_app_id'] = $this->app_key;
            $ret['pay_type'] = 'online';
            $ret['memo'] = $recv['body'];

            //新版同步无此字段
//            if($recv['result'] == 'success') {
//                $ret['status'] = 'succ';
//            }else {
//                $ret['status'] =  'failed';
//            }
            $ret['status'] = 'succ';
            \Neigou\Logger::General('pay.malipay2', array('action' => 'callback_succ', 'data' => $recv));

        }else{
            $message = 'Invalid Sign';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('pay.malipay2', array('action' => 'sign_err', 'data' => $recv));

        }

        return $ret;
    }


    /**
     * 检验返回数据合法性
     * @param mixed $form 包含签名数据的数组
     * @param mixed $key 签名用到的私钥
     * @access private
     * @return boolean
     */
    public function is_return_vaild($form,$key,$secu_id){
        $_key      = $key;
        $sign_type = $secu_id;
        $get       = $this->para_filter($form);     //对所有GET反馈回来的数据去空
        $sort_get  = $this->arg_sort($get);         //对所有GET反馈回来的数据排序
        $mysign    = $this->build_mysign($sort_get,$_key,$sign_type);    //生成签名结果

        if ($mysign == $form['sign']) {
            return true;
        }
        #记录返回失败的情况
        logger::error(app::get('wap')->_('支付单号：') . $form['out_trade_no'] . app::get('wap')->_('签名验证不通过，请确认！')."\n");
        logger::error(app::get('wap')->_('本地产生的加密串：') . $mysign);
        logger::error(app::get('wap')->_('手机支付宝传递打过来的签名串：') . $form['sign']);
        $str_xml .= "<alipayform>";
        foreach ($form as $key=>$value){
            $str_xml .= "<$key>" . $value . "</$key>";
        }
        $str_xml .= "</alipayform>";

        return false;
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
     * 生成支付表单 - 自动提交
     * @params null
     * @return null
     */
    public function gen_form()
    {
      return '';
    }


//↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓公共函数部分↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓↓

    /**生成签名结果
     * $array要签名的数组
     * return 签名结果字符串
     */
    public function build_mysign($sort_array,$key,$sign_type = "MD5") {
        $prestr = $this->create_linkstring($sort_array);         //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
        $prestr = $prestr.$key;                            //把拼接后的字符串再与安全校验码直接连接起来
        $mysgin = $this->sign($prestr,$sign_type);                //把最终的字符串签名，获得签名结果
        return $mysgin;
    }


    /**把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * $array 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    public function create_linkstring($array) {
        $arg  = "";
        while (list ($key, $val) = each ($array)) {
            $arg.=$key."=".$val."&";
        }
        $arg = substr($arg,0,count($arg)-2);             //去掉最后一个&字符
        return $arg;
    }


    /**除去数组中的空值和签名参数
     * $parameter 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    public function para_filter($parameter) {
        $para = array();
        while (list ($key, $val) = each ($parameter)) {
            if($key == "sign" || $key == "sign_type" || $val == "")continue;
            else    $para[$key] = $parameter[$key];
        }
        return $para;
    }


    /**对数组排序
     * $array 排序前的数组
     * return 排序后的数组
     */
    public function arg_sort($array) {
        ksort($array);
        reset($array);
        return $array;
    }




    /**
     * 通过节点路径返回字符串的某个节点值
     * $res_data——XML 格式字符串
     * 返回节点参数
     */
    function getDataForXML($res_data,$node)
    {
        $xml = simplexml_load_string($res_data);
        $result = $xml->xpath($node);

        while(list( , $node) = each($result))
        {
            return $node;
        }
    }

//↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑公共函数部分↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑


//--------------bin
    public function generateSign($params, $signType = "RSA") {
        return $this->sign($this->getSignContent($params), $signType);
    }
    protected function sign($data, $signType = "RSA") {

        $priKey=$this->rsa_private_key;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";

        if(!$res){
            return false;
        }
        if ("RSA2" == $signType) {
            $res = openssl_sign($data, $sign, $res,'sha256');
            if(!$res){
                \Neigou\Logger::General('pay.malipay2', array('action' => 'sign_err', 'data' => json_encode($data)));
                return false;
                //echo openssl_error_string();
            }
        } else {
            openssl_sign($data, $sign, $res);
        }
        $sign = base64_encode($sign);
        return $sign;
    }

    /**
     * 转换字符集编码
     * @param $data
     * @param $targetCharset
     * @return string
     */
    function characet($data, $targetCharset) {

        if (!empty($data)) {
            $fileType = $this->submit_charset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
            }
        }
        return $data;
    }

    public function getSignContent($params) {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->submit_charset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value) {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }
    /** rsaCheckV1 & rsaCheckV2
     *  验证签名
     **/
    public function rsaCheck($params,$signType='RSA') {
        $sign = $params['sign'];
        $params['sign_type'] = null;
        $params['sign'] = null;
        $ret = $this->verify($this->getSignContent($params), $sign,$signType);
        return $ret;
    }
    function verify($data, $sign, $signType = 'RSA') {

        $pubKey= $this->zfb_public_key;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";


        if(!$res){
            return false;
        }

        if ("RSA2" == $signType) {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res, 'sha256');
        } else {
            $result = (bool)openssl_verify($data, base64_decode($sign), $res);
        }
        if(!$this->checkEmpty($this->zfb_public_key)) {
            //释放资源
            openssl_free_key($res);
        }
        return $result;
    }
}
