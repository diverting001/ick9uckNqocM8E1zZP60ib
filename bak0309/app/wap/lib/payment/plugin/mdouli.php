<?php

/**
 * 兜礼支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/11/21
 * Time: 15:39
 */
final class wap_payment_plugin_mdouli extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '兜礼支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '兜礼支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mdouli';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mdouli';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '兜礼支付';
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
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdouli_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mdouli', 'callback');
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
        return '兜礼支付配置信息';
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
                'title'=>app::get('ectools')->_('合作者身份(parterID)'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'company_id'=>array(
                'title'=>app::get('ectools')->_('公司ID 对账使用'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ssl_cert'=>array(
                'title'=>app::get('ectools')->_('ssl证书路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ssl_key'=>array(
                'title'=>app::get('ectools')->_('ssl私钥路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ssl_pass'=>array(
                'title'=>app::get('ectools')->_('ssl私钥密码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'daily_prefix' =>array(
                'title'=>app::get('ectools')->_('日对账单前缀'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'month_prefix' =>array(
                'title'=>app::get('ectools')->_('月对账单前缀'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'file_path' =>array(
                'title'=>app::get('ectools')->_('账单存储路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ftp_server' =>array(
                'title'=>app::get('ectools')->_('FTP 服务器地址'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ftp_port' =>array(
                'title'=>app::get('ectools')->_('FTP 服务器端口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ftp_user' =>array(
                'title'=>app::get('ectools')->_('FTP 用户名'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ftp_pwd' =>array(
                'title'=>app::get('ectools')->_('FTP 登录密码'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'url_code' =>array(
                'title'=>app::get('ectools')->_('积分消费获取验证码接口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('支付请求API'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'coupon_url'=>array(
                'title'=>app::get('ectools')->_('兑换码核销API'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sync_url'=>array(
                'title'=>app::get('ectools')->_('已支付订单同步接口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'ship_url'=>array(
                'title'=>app::get('ectools')->_('物流信息同步接口'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'refund_url'=>array(
                'title'=>app::get('ectools')->_('退换货接口'),
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
        return app::get('ectools')->_('福优支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
//        print_r($payment);die;
        $order_info = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
//        print_r($order_info);die;
        \Neigou\Logger::General('pay.douli', array('action' => 'payment_douli', 'result' => $payment));
        //third member info
        $members_mdl = app::get('b2c')->model('third_members');
        $member_id = $_SESSION['account'][pam_account::get_account_type('b2c')];
        $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));
        //check if code exist
        if(empty($payment['verify_code'])){
            //request mobile verify code
            $this->add_field('businessId',$this->getConf('mer_id', __CLASS__));//商家唯一编号(兜礼提供)
            $this->add_field('storesId','A001');//商家门店或设备编号【电商、线上默认填 写"A001"即可，线下则按实际门店编号填 写】
            $this->add_field('cardNumber',$member_info['external_bn']);//会员卡号 或 会员手机号
//            $this->add_field('cardNumber','15201531894');//TODO TEST DATA 会员卡号 或 会员手机号
            $this->add_field('amount',number_format($payment['cur_money'],2,".",""));//交易的积分数量【单位:元，保留两位小 数，如"10.20"】
            $this->add_field('orderNumber',$payment['order_id']);//Common 服务器时间戳 误差120s之内
            $this->add_field('serialNumber',$payment['payment_id']);//Common 随机字符串 不长于30位
            $this->add_field('orderDate',date('Y-m-d H:i:s',$payment['t_confirm']));//企业编号
            $this->add_field('price',number_format($order_info['cur_money'],2,".",""));//交易的积分数量【单位:元，保留两位小 数，如"10.20"】
            //CURL code API
            $ret = $this->request($this->getConf('url_code', __CLASS__),$this->fields,0);
            if($ret['code']==0 && $ret){
                if($payment['verify_code_agin']==1){
                    echo json_encode($ret);
                    exit();
                } else {
                    $arr['code'] = 200;
                    $arr['msg'] = '短信下发成功';
                    $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'pay_gateway','arg0'=>$payment['order_id']));
                    //redirect to code input page
                    \Neigou\Logger::General('pay.douli', array('action' => 'code_succ','to_url'=>$url));
                    header('Location: '.$url);
                }
                exit();
            } else {
                echo json_encode($ret);
                exit();
            }
        } else {
            $this->add_field('businessId',$this->getConf('mer_id', __CLASS__));//商家唯一编号(兜礼提供)
            $this->add_field('storesId','A001');//商家门店或设备编号【电商、线上默认填 写"A001"即可，线下则按实际门店编号填 写】
            $this->add_field('cardNumber',$member_info['external_bn']);//会员卡号 或 会员手机号
//            $this->add_field('cardNumber','15201531894');//TODO TEST DATA 会员卡号 或 会员手机号
            $this->add_field('verificationCode',$payment['verify_code']);//会员验证码【手机短信验证码(获取积分 交易短信验证码接口)、条形码、动态识 别码等】
            $this->add_field('payPassword',0);//支付密码【默认不需要，填写 "0"。若需 要则根据支付验证码接口返回的 isPayPassword 状态】
            $this->add_field('amount',number_format($payment['cur_money'],2,".",""));//交易的积分数量【单位:元，保留两位小 数，如"10.20"】
            $this->add_field('orderNumber',$payment['order_id']);//Common 服务器时间戳 误差120s之内
            $this->add_field('serialNumber',$payment['payment_id']);//Common 随机字符串 不长于30位
            $this->add_field('orderDate',date('Y-m-d H:i:s',$payment['t_confirm']));//企业编号
            //order detail Get
            $order_items = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
            $prod_detail = array();
            foreach ($order_items['items'] as $k => $v) {
                $prod_detail[$k]['code'] = $v['bn'];//商品编号(唯一识别码)
                $prod_detail[$k]['goods'] = $v['name'];//商品名称
                $prod_detail[$k]['number'] = $v['nums'];//商品数量(2 位小数)
                $prod_detail[$k]['amount'] = $v['amount'];//实收金额(单位:元 2 位小数)
                $prod_detail[$k]['category'] = 'ng001';//商品品类【用于商家返佣，0000:不计算返 佣 ，需计算返佣则需要与兜礼平台协商提 供品类编号】
                $prod_detail[$k]['price'] = $v['mktprice']*$v['nums'];//应收金额(单位:元 2 位小数)
                $prod_detail[$k]['tax'] = 0;//TODO null 税率【保留 2 位小数。商品返佣计算含税 则填 0，不含税则按商品实际税点填写】
                $goods_id = app::get('b2c')->model('products')->getRow('goods_id',array('bn'=>$v['bn']));
                $cat_id = app::get('b2c')->model('goods')->getRow('mall_goods_cat',array('goods_id'=>$goods_id['goods_id']));
                $cat_info = app::get('b2c')->model('mall_goods_cat')->getRow('*',array('cat_id'=>$cat_id['mall_goods_cat']));
                if($cat_info['parent_id']==0){
                    //自己为第一分类
                    $prod_detail[$k]['categoryOne'] = $cat_info['cat_name'];//商品一级类目
                } else {
                    $cat_id = explode(',',$cat_info['cat_path']);
                    $cat_info = app::get('b2c')->model('mall_goods_cat')->getRow('cat_name',array('cat_id'=>$cat_id[1]));
                    $cat_info2 = app::get('b2c')->model('mall_goods_cat')->getRow('cat_name',array('cat_id'=>$cat_id[2]));
                    $prod_detail[$k]['categoryOne'] = $cat_info['cat_name'];//商品一级类目
                    $prod_detail[$k]['categoryTwo'] = $cat_info2['cat_name'];//商品二级类目
                }
            }
            if($order_info['cost_freight']>0){
                //添加运费
                $ship[0]['goods'] = '运费';
                $ship[0]['number'] = 1;
                $ship[0]['tax'] = 0;

                $ship[0]['amount'] = $order_info['cost_freight'];
                $ship[0]['price'] = $order_info['cost_freight'];
                $ship[0]['category'] = 'ng001';
                $prod_detail = array_merge($prod_detail,$ship);
            }

            if($order_info['pmt_amount']>0){
                //添加优惠券信息
                $voucher[0]['goods'] = '优惠券';
                $voucher[0]['price'] = 0;
                $voucher[0]['tax'] = 0;
                $voucher[0]['number'] = 1;
                $voucher[0]['amount'] = 0-$order_info['pmt_amount'];
                $voucher[0]['category'] = '0000';
                $prod_detail = array_merge($prod_detail,$voucher);
            }
            $this->add_field('productDetail',json_encode($prod_detail));//企业编号
            //CURL Consume API
            $ret = $this->request($this->getConf('submit_url', __CLASS__),$this->fields,0);
            if($ret['code']==0 && $ret){
                //save db and callback request
                $insert['payment_id'] = $payment['payment_id'];// payment_id
                $insert['order_id'] = $payment['order_id'];// payment_id
                $insert['customNo'] = $this->getConf('mer_id', __CLASS__);//商户订单号
                $insert['settleAmt'] = number_format($payment['cur_money'],2,".","");//支付金额
                $insert['trade_no'] = $payment['payment_id'];//支付流水号
                $insert['payed_time'] = time();//支付成功时间
                $insert['notify_time'] = 0;
                $insert['res'] = 'ready';
                $insert['sync_time'] = 0;
                $insert['sync_res'] = 'ready';
                $db_info = app::get('ectools')->model('order_notify')->insert($insert);

                $ret['data']['salt'] = 'neigou*douli*salt';
                $ret['data']['orderNo'] = $payment['payment_id'];
                $ret['data']['orderNoFlx'] = $payment['payment_id'];
                $ret['data']['customNo'] = $this->getConf('mer_id', __CLASS__);
                $ret['data']['settleAmt'] = number_format($payment['cur_money'],2,".","");
                $ret['data']['sign'] = $this->genSign($ret['data']);
                unset($ret['data']['salt']);
                $query_str = http_build_query($ret['data']);
                \Neigou\Logger::General('pay.douli', array('action' => 'pay_succ', 'result' => $ret,'insert'=>$insert,'db_info'=>$db_info));
                header('Location:'.$this->callback_url.'?'.$query_str);
                exit();
            } else {
                //redirect to pay err page
                $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
                //redirect to pay err page
                \Neigou\Logger::General('pay.douli', array('action' => 'pay_fail', 'result' => $ret));
                header('Location: '.$url);die;
            }
        }
        exit;
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
            $ret['payment_id'] = $recv['orderNo'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('兜礼支付');//TODO 这里是支付方式名称 确认
            $ret['pay_account'] = $recv['customNo'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['settleAmt'];//TODO 确认是否是这个字段 清算金额
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['settleAmt'];
            $ret['trade_no'] = $recv['orderNoFlx'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mdouli";
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
     * <生成签名方法>
     * <功能详细描述>
     * @param map 参数集合
     * @return signResult签名后的字符串
     * @see [类、类#方法、类#成员]
     */
    public function genSign($postData) {
        ksort($postData);
        $data = '';
        foreach ($postData as $kk => $vv) {
            $urlData = '';
            $urlData = $kk . '=' . $vv . '&';
            $data .=$urlData;
        }
        return strtoupper(sha1($data));
    }


    public function request($api = '', $post_data = array(), $sign = 1) {
        $url = $api;
        if ($sign) {
            $post_data['sign'] = $this->genSign($post_data);
        }
        $curl = new \Neigou\Curl();
        //For douli ssl request
        $proxyServer = NEIGOU_HTTP_PROXY;
        $opt_config = array(
            CURLOPT_POST => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => true,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1,
            CURLOPT_SSLCERT => $this->getConf('ssl_cert', __CLASS__),
            CURLOPT_SSLKEY => $this->getConf('ssl_key', __CLASS__),
            CURLOPT_SSLCERTPASSWD => $this->getConf('ssl_pass', __CLASS__),
            CURLOPT_SSLKEYPASSWD => $this->getConf('ssl_pass', __CLASS__),
            CURLOPT_PROXYTYPE => CURLPROXY_HTTP,
            CURLOPT_PROXY => $proxyServer,
        );
        $curl->SetOpt($opt_config);
        $curl->save_log = true;
        $curl->SetHeader('Content-Type', 'application/json');
        $result = $curl->Post($url, json_encode($post_data));
        \Neigou\Logger::General('pay.mdouli', array('action' => 'req', 'opt_config' => $opt_config,'req_url'=>$url,'post_data'=>$post_data,'response_data'=>$result));
        $resultData = json_decode($result, true);
        return $resultData;
    }

    public function gen_form(){
        return '';
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
    public function is_return_vaild($params) {
        $signature_str = $params ['sign'];
        unset ( $params ['sign'] );
        $params['salt'] = 'neigou*douli*salt';
        $sign = $this->genSign($params);
        if ($sign==$signature_str) {
            return true;
        } else {
            return false;
        }
    }
}