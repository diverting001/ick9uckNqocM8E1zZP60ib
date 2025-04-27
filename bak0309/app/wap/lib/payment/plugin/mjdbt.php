<?php

/**
 * 京东白条免息支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/03/26
 * Time: 14:26
 */
final class wap_payment_plugin_mjdbt extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '京东白条免息支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '京东白条免息支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mjdbt';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mjdbt';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '京东白条免息支付';
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
    public $supportCurrency = array("CNY"=>"01");

    /**
     * @var string 通用支付
     */
    public $is_general = 1;

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mjdbt_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mjdbt', 'callback');
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
        return '京东白条免息支付配置信息';
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
            'des_key'=>array(
                'title'=>app::get('ectools')->_('商户DES密钥'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'cert_path'=>array(
                'title'=>app::get('ectools')->_('商户私钥路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'pub_cert_path'=>array(
                'title'=>app::get('ectools')->_('公钥路径'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'submit_url'=>array(
                'title'=>app::get('ectools')->_('京东支付服务地址'),
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
        return app::get('ectools')->_('京东白条免息支付');
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {
        /*交易标题*/
        $subject = $payment['account'].$payment['payment_id'];
        $subject = str_replace("'",'`',trim($subject));
        $subject = str_replace('"','`',$subject);
        if (isset($payment['subject']) && $payment['subject']){
            $subject_tmp = $payment['subject'];
        }else{
            $subject_tmp = $subject;
        }
        /*交易标题END*/
        $price = number_format($payment['cur_money'],2,".","")*100;
        $key = $this->getConf('des_key', __CLASS__);
        $des_key = base64_decode($key);
        $this->add_field('version','V2.0');//当前固定填写：V2.0
        $this->add_field('merchant',$this->getConf('mer_id', __CLASS__));//商户号（由京东分配)
        $this->add_field('tradeNum',$payment['payment_id']);//* 商户唯一交易流水号。格式：字母&数字
        $this->add_field('tradeName',$subject_tmp);//* 商户订单的标题/商品名称/关键字等
        $this->add_field('tradeTime',date('YmdHis',$payment['create_time']));//* 订单生成时间。格式：“yyyyMMddHHmmss”
        $this->add_field('amount',(string)$price);//* 商户订单的资金总额。单位：分，大于0
        $this->add_field('orderType','0');//固定值：0或者1 （0：实物，1：虚拟）
        $this->add_field('currency','CNY');//货币类型，固定填CNY

        //添加商品信息
        $order_items = kernel::single("b2c_service_order")->getOrderInfo($payment['order_id']);
        $prod_detail = array();
        foreach ($order_items['items'] as $k => $v) {
            $prod_detail[$k]['id'] = $v['bn'];//商品编号
            $prod_detail[$k]['num'] = $v['nums'];//商品数量
            $prod_detail[$k]['type'] = 'GT01';//商品数量
            $prod_detail[$k]['price'] = ($v['amount']+$v['cost_tax'])*100;//商品单价 单位 分
            $goods_id = app::get('b2c')->model('products')->getRow('goods_id',array('bn'=>$v['bn']));
            $cat_id = app::get('b2c')->model('goods')->getRow('mall_goods_cat',array('goods_id'=>$goods_id['goods_id']));
            $cat_info = app::get('b2c')->model('mall_goods_cat')->getRow('*',array('cat_id'=>$cat_id['mall_goods_cat']));
            //TODO 检测商品是否是京东商品 如果有一个不是的 返回让重新支付 不能使用白条免息
            if($cat_info['parent_id']==0){
                //自己为第一分类
                $prod_detail[$k]['cat1'] = $cat_info['cat_name'];//商品一级类目
            } else {
                $cat_id = explode(',',$cat_info['cat_path']);
                $cat_info = app::get('b2c')->model('mall_goods_cat')->getRow('cat_name',array('cat_id'=>$cat_id[1]));
                $cat_info2 = app::get('b2c')->model('mall_goods_cat')->getRow('cat_name',array('cat_id'=>$cat_id[2]));
                $prod_detail[$k]['cat1'] = $cat_info['cat_name'];//商品一级类目
                $prod_detail[$k]['cat2'] = $cat_info2['cat_name'];//商品二级类目
            }
        }

        $this->add_field('goodsInfo',json_encode($prod_detail));//商品信息列表，FORM表单中以json格式提交
        $this->add_field('callbackUrl',$this->callback_url);//支付成功后跳转的URL
        $this->add_field('notifyUrl',$this->notify_url);//支付完成后，京东异步通知商户服务相关支付结果。必须是外网可访问的url。
        $this->add_field('userId',(string)$payment['member_id']);//商户平台用户的唯一账号。注：用户账号是商户端系统的用户唯一账号。
        $spend = time()-$payment['create_time'];
        //判断公司是否是24小时可以支付的
        $_third_company = app::get('b2c') -> model('third_company');
        $company_list_for24hours = $_third_company -> getCompanyByChannel(array('louxiaoyi_beiqi'));
        $company_list_for24hours = array_merge($company_list_for24hours, (array)explode(',',CANCEL_ORDER_24));

        $company_id = kernel::single("b2c_member_company")->get_cur_company();

        if(in_array($company_id,$company_list_for24hours)){
            $expire_time = 86400;
        } else {
            $expire_time = 2380;
        }

        $expire = $expire_time-$spend;
        $this->add_field('expireTime',(string)$expire);//订单的失效时长。单位：秒，失效后则不能再支付，默认失效时间为604800秒(7天)tradeType为QR时，默认时效时间为2小时。
        $sign = $this->sign($this->fields);
        $arr = array('sign','version','merchant');
        $sign_fields = $this->fields;
        $this->add_field('sign',$sign);
        foreach($this->fields as $key=>$val){
            if(!in_array($key,$arr)){
                $this->add_field($key,$this->encrypt2HexStr($des_key,$val));
            }
        }
        \Neigou\Logger::General('pay.mjdbt', array('action' => 'dopayjd', 'sign_fields' => $sign_fields,'des_fields'=>$this->fields));
        echo $this->get_html();
        exit();
    }

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        $_POST = $recv;
        #键名与pay_setting中设置的一致
        $mer_id = $this->getConf('mer_id', __CLASS__);
        $mer_id = $mer_id == '' ? '01510084' : $mer_id;
        $valid = $this->is_return_vaild($recv);
        if($valid['status']=='true'){
            $recv = $valid['data'];
            if($recv['status']==0){
                $ret['payment_id'] = $recv['tradeNum'];
                $ret['account'] = $mer_id;
                $ret['bank'] = app::get('ectools')->_('京东支付');
                $ret['pay_account'] = $mer_id;
                $ret['currency'] = 'CNY';
                $ret['money'] = $recv['amount']/100;
                $ret['paycost'] = '0.000';
                $ret['cur_money'] = $recv['amount']/100;
                $ret['trade_no'] = $recv['tradeNum'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
                $ret['t_payed'] = $recv['tradeTime'];
                $ret['pay_app_id'] = "mjd";
                $ret['pay_type'] = 'online';
                $ret['status'] = 'succ';
                \Neigou\Logger::General('pay.mjdbt', array('action' => 'callback_succ', 'data' => $recv));
            } else {
                \Neigou\Logger::General('pay.mjdbt', array('action' => 'trade_status_err', 'data' => $recv));
                $ret['status'] = 'invalid';
            }

        }else{
            \Neigou\Logger::General('pay.mjdbt', array('action' => 'sign_err', 'data' => $recv));
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
     * @param $params
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params) {
        $key = $this->getConf('des_key', __CLASS__);
        $des_key = base64_decode($key);
        if($_POST["tradeNum"] != null && $_POST["tradeNum"]!=""){
            $param["tradeNum"]=$this->decrypt4HexStr($des_key, $_POST["tradeNum"]);
        }
        if($_POST["amount"] != null && $_POST["amount"]!=""){
            $param["amount"]=$this->decrypt4HexStr($des_key, $_POST["amount"]);
        }
        if($_POST["currency"] != null && $_POST["currency"]!=""){
            $param["currency"]=$this->decrypt4HexStr($des_key, $_POST["currency"]);
        }
        if($_POST["tradeTime"] != null && $_POST["tradeTime"]!=""){
            $param["tradeTime"]=$this->decrypt4HexStr($des_key, $_POST["tradeTime"]);
        }
        if($_POST["note"] != null && $_POST["note"]!=""){
            $param["note"]=$this->decrypt4HexStr($des_key, $_POST["note"]);
        }
        if($_POST["status"] != null && $_POST["status"]!=""){
            $param["status"]=$this->decrypt4HexStr($des_key, $_POST["status"]);
        }

        $sign =  $params["sign"];
        unset($param['sign']);
        $strSourceData = $this->signString($param, array());
        $decryptStr = $this->decryptByPublicKey($sign);
        $sha256SourceSignString = hash ( "sha256", $strSourceData);
        \Neigou\Logger::General('pay.mjdbt', array('action' => 'jdcallback', 'sign_fields' => $strSourceData,'sign_jd'=>$decryptStr,'sign_mine'=>$sha256SourceSignString,'param'=>$params,'des_param'=>$param));
        if($decryptStr!=$sha256SourceSignString){
            $return['status'] = false;
            return $return;
        }else{
            $return['data'] = $param;
            $return['status'] = true;
            return $return;
        }
    }

    function signString($data, $unSignKeyList) {
        $linkStr="";
        $isFirst=true;
        ksort($data);
        foreach($data as $key=>$value){
            if($value==null || $value==""){
                continue;
            }
            $bool=false;
            foreach ($unSignKeyList as $str) {
                if($key."" == $str.""){
                    $bool=true;
                    break;
                }
            }
            if($bool){
                continue;
            }
            if(!$isFirst){
                $linkStr.="&";
            }
            $linkStr.=$key."=".$value;
            if($isFirst){
                $isFirst=false;
            }
        }
        return $linkStr;
    }

    /**
     * 将元数据进行补位后进行3DES加密
     * <p/>
     * 补位后 byte[] = 描述有效数据长度(int)的byte[]+原始数据byte[]+补位byte[]
     *
     * @param
     *        	sourceData 元数据字符串
     * @return 返回3DES加密后的16进制表示的字符串
     */
     function encrypt2HexStr($keys, $sourceData) {
        $source = array ();

        // 元数据
        $source = $this->getBytes ( $sourceData );

        // 1.原数据byte长度
        $merchantData = count($source);
        // echo "原数据据:" . htmlspecialchars($sourceData) . "<br/>";
        // echo "原数据byte长度:" . $merchantData . "<br/>";
        // echo "原数据HEX表示:" . ByteUtils::bytesToHex ( $source ) . "<br/>";
        // 2.计算补位
        $x = ($merchantData + 4) % 8;
        $y = ($x == 0) ? 0 : (8 - $x);
        // echo ("需要补位 :" . $y . "<br/>");
        // 3.将有效数据长度byte[]添加到原始byte数组的头部
        $sizeByte = $this->integerToBytes ( $merchantData );
        $resultByte = array ();

        for($i = 0; $i < 4; $i ++) {
            $resultByte [$i] = $sizeByte [$i];
        }
        //var_dump($sizeByte);
        // 4.填充补位数据
        for($j = 0; $j < $merchantData; $j ++) {
            $resultByte [4 + $j] = $source [$j];
        }
        //var_dump($resultByte);
        for($k = 0; $k < $y; $k ++) {
            $resultByte [$merchantData + 4 + $k] = 0x00;
        }
        //var_dump($resultByte);
        //echo ("补位后的byte数组长度:" . count ( $resultByte ) . "<br/>");
        //echo ("补位后数据HEX表示:" . ByteUtils::bytesToHex ( $resultByte ) . "<br/>");
        //echo ("秘钥HEX表示:" . ByteUtils::strToHex ( $keys ) . "<br/>");
        //echo ("秘钥长度:" . count ( ByteUtils::getBytes ( $keys ) ) . "<br/>");
        //echo ByteUtils::toStr ( $resultByte );
        $desdata = $this->encrypt ( $this->toStr ( $resultByte ), $keys );
        //echo ("加密后的长度:" . strlen ( $desdata ) . "<br/>");
        return $this->strToHex ( $desdata );
    }

    // 加密算法
    public static function encrypt($input, $key) {
        $size = mcrypt_get_block_size ( 'des', 'ecb' );
        $td = mcrypt_module_open ( MCRYPT_3DES, '', 'ecb', '' );
        $iv = @mcrypt_create_iv ( mcrypt_enc_get_iv_size ( $td ), MCRYPT_RAND );
        // 使用MCRYPT_3DES算法,cbc模式
        @mcrypt_generic_init ( $td, $key, $iv );
        // 初始处理
        $data = mcrypt_generic ( $td, $input );
        // 加密
        mcrypt_generic_deinit ( $td );
        // 结束
        mcrypt_module_close ( $td );

        return $data;
    }

    /**
     * 转换一个String字符串为byte数组
     * @param $string
     * @return array
     */
     function getBytes($string) {
        $bytes = array ();
        for($i = 0; $i < strlen ( $string ); $i ++) {
            $bytes [] = ord ( $string [$i] );
        }
        return $bytes;
     }

    /**
     * 转换一个int为byte数组
     * @param $val
     * @return array
     */
    function integerToBytes($val) {
        $byt = array ();
        $byt [0] = ($val >> 24 & 0xff);
        $byt [1] = ($val >> 16 & 0xff);
        $byt [2] = ($val >> 8 & 0xff);
        $byt [3] = ($val & 0xff);
        return $byt;
    }

    /**
     * 将字节数组转化为String类型的数据
     * @param $bytes
     * @return string
     */
    function toStr($bytes) {
        $str = '';
        foreach ( $bytes as $ch ) {
            $str .= chr ( $ch );
        }

        return $str;
    }

    /**
     * 将十进制字符串转换为十六进制字符串
     * @param $string
     * @return string
     */
    function strToHex($string) {
        $hex = "";
        for($i = 0; $i < strlen ( $string ); $i ++) {
            $tmp = dechex ( ord ( $string [$i] ) );
            if (strlen ( $tmp ) == 1) {
                $hex .= "0";
            }
            $hex .= $tmp;
        }
        $hex = strtolower ( $hex );
        return $hex;
    }

    //生成签名
    private function sign($params,$unSignKeyList) {
        ksort($params);
        $sourceSignString = $this->signString( $params, $unSignKeyList );

//        echo  "sourceSignString=".htmlspecialchars($sourceSignString)."<br/>";
        //error_log("=========>sourceSignString:".$sourceSignString, 0);
        $sha256SourceSignString = hash ( "sha256", $sourceSignString);
        \Neigou\Logger::General('pay.jd', array('action' => 'dopayjd', 'source_str' => $sourceSignString,'sha_str'=>$sha256SourceSignString));
        //error_log($sha256SourceSignString, 0);
//        echo "sha256SourceSignString=".htmlspecialchars($sha256SourceSignString)."<br/>";
        return $this->encryptByPrivateKey($sha256SourceSignString);
    }

    function encryptByPrivateKey($data) {
        $pi_key =  openssl_pkey_get_private(file_get_contents($this->getConf('cert_path', __CLASS__)));//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id
        $encrypted="";
        openssl_private_encrypt($data,$encrypted,$pi_key,OPENSSL_PKCS1_PADDING);//私钥加密
        $encrypted = base64_encode($encrypted);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的
        return $encrypted;
    }

    function decryptByPublicKey($data) {
        $pu_key =  openssl_pkey_get_public(file_get_contents($this->getConf('pub_cert_path', __CLASS__)));//这个函数可用来判断公钥是否是可用的，可用返回资源id Resource id
//        echo "--->".$pu_key."\n";
        $decrypted = "";
        $data = base64_decode($data);
//        echo $data."\n";

        openssl_public_decrypt($data,$decrypted,$pu_key);//公钥解密

//        echo $decrypted."\n";
        return $decrypted;
    }

    /**
     * 3DES 解密 进行了补位的16进制表示的字符串数据
     *
     * @return
     *
     */
    function decrypt4HexStr($keys, $data) {
        $hexSourceData = array ();

        $hexSourceData = $this->hexStrToBytes ($data);
        //var_dump($hexSourceData);

        // 解密
        $unDesResult = $this->decrypt ($this->toStr($hexSourceData),$keys);
        //echo $unDesResult;
        $unDesResultByte = $this->getBytes($unDesResult);
        //var_dump($unDesResultByte);
        $dataSizeByte = array ();
        for($i = 0; $i < 4; $i ++) {
            $dataSizeByte [$i] = $unDesResultByte [$i];
        }
        // 有效数据长度
        $dsb = $this->byteArrayToInt( $dataSizeByte, 0 );
        $tempData = array ();
        for($j = 0; $j < $dsb; $j++) {
            $tempData [$j] = $unDesResultByte [4 + $j];
        }

        return $this->hexTobin ($this->bytesToHex ( $tempData ));

    }
    /**
     *
     *
     *
     *
     * 转换一个16进制hexString字符串为十进制byte数组
     *
     * @param $hexString 需要转换的十六进制字符串
     * @return 一个byte数组
     *
     */
    function hexStrToBytes($hexString) {
        $bytes = array ();
        for($i = 0; $i < strlen ( $hexString ) - 1; $i += 2) {
            $bytes [$i / 2] = hexdec ( $hexString [$i] . $hexString [$i + 1] ) & 0xff;
        }

        return $bytes;
    }
    function decrypt($encrypted, $key) {
        //$encrypted = base64_decode($encrypted);
        $td = mcrypt_module_open ( MCRYPT_3DES, '', 'ecb', '' ); // 使用MCRYPT_DES算法,cbc模式
        $iv = @mcrypt_create_iv ( mcrypt_enc_get_iv_size ( $td ), MCRYPT_RAND );
        $ks = mcrypt_enc_get_key_size ( $td );
        @mcrypt_generic_init ( $td, $key, $iv ); // 初始处理
        $decrypted = mdecrypt_generic ( $td, $encrypted ); // 解密
        mcrypt_generic_deinit ( $td ); // 结束
        mcrypt_module_close ( $td );
        //$y = TDESUtil::pkcs5Unpad ( $decrypted );
        return $decrypted;
    }
    /**
     * 将byte数组 转换为int
     *
     * @param
     *        	b
     * @param
     *        	offset 位游方式
     * @return
     *
     *
     */
    function byteArrayToInt($b, $offset) {
        $value = 0;
        for($i = 0; $i < 4; $i ++) {
            $shift = (4 - 1 - $i) * 8;
            $value = $value + ($b [$i + $offset] & 0x000000FF) << $shift; // 往高位游
        }
        return $value;
    }

    /**
     *
     * @param unknown $hexstr
     * @return Ambigous <string, unknown>
     */
    function hexTobin($hexstr)
    {
        $n = strlen($hexstr);
        $sbin="";
        $i=0;
        while($i<$n)
        {
            $a =substr($hexstr,$i,2);
            $c = pack("H*",$a);
            if ($i==0){$sbin=$c;}
            else {$sbin.=$c;}
            $i+=2;
        }
        return $sbin;
    }
    // 字符串转16进制
    function bytesToHex($bytes) {
        $str = $this->toStr ( $bytes );
        return $this->strToHex ( $str );
    }
}