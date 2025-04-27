<?php

/**
 * 农行网上支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2017/12/28
 * Time: 15:25
 */
final class wap_payment_plugin_micbc extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '工行B2C在线支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '工行网上支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'micbc';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'micbc';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '工行网上支付';
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


    const BIZCRT_PATH = '/nfs/share/cert/neigou_store/biz_public.crt';

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);

        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_micbc', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)){
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        }else{
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }


        $this->submit_url = 'https://mywap2.icbc.com.cn/ICBCWAPBank/servlet/ICBCWAPEBizServlet';
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '工行B2C在线支付';
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
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro(){
        return app::get('wap')->_('工行B2C在线支付');
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


    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('wap')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sign_url'=>array(
                'title'=>app::get('wap')->_('签名地址'),//'https://test.xuebank.com/eduboot/neigou/sign'
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'verify_sign_url'=>array(
                'title'=>app::get('wap')->_('校验签名地址'), //https://test.xuebank.com/eduboot/neigou/verify

                'type'=>'string',
                'validate_type' => 'required',
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
            'status'=>array(
                'title'=>app::get('wap')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'status',
            ),
        );
    }

    /**
     * 将xml转换为数组
     * @param string $xml:xml文件或字符串
     * @return array
     */
    public function xml2arr($xml){
        //考虑到xml文档中可能会包含<![CDATA[]]>标签，第三个参数设置为LIBXML_NOCDATA
        if (file_exists($xml)) {
            libxml_disable_entity_loader(false);
            $xml_string = simplexml_load_file($xml,'SimpleXMLElement', LIBXML_NOCDATA);
        }else{
            libxml_disable_entity_loader(true);
            $xml_string = simplexml_load_string($xml,'SimpleXMLElement', LIBXML_NOCDATA);
        }
        $result = json_decode(json_encode($xml_string),true);
        return $result;
    }

    //转换函数
    public function arr2xml($arr,$parentNode=null){
        //如果父节点为null，则创建root节点，否则就使用父节点
        if($parentNode === null){
            $simxml = new SimpleXMLElement('<?xml version="1.0" encoding="GBK" standalone="no"?><B2CReq></B2CReq>');
        }else{
            $simxml = $parentNode;
        }
        //遍历数组
        foreach($arr as $k=>$v){
            if(is_array($v)){//如果是数组的话则继续递归调用，并以该键值创建父节点
                $this->arr2xml($v,$simxml->addChild($k));
            }else if(is_numeric($k)){//如果键值是数字，不能使用纯数字作为XML的标签名，所以此处加了'item'字符，这个字符可以自定义
                $simxml->addChild('item'.$k,$v);
            }else{//添加节点
                $simxml->addChild($k,$v);
            }
        }
        //返回数据
        //header('Content-type:text/xml;charset=utf-8');
        return $simxml->saveXML();
    }

    /**
     * 生成支付方式提交的表单的请求
     * @params null
     * @return string
     */
    protected function icbc_get_html()
    {
        // 简单的form的自动提交的代码。
        header("Content-Type: text/html;charset=".$this->submit_charset);
        $strHtml ="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
		<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\" dir=\"ltr\">
		<head>
		</head><body><div>Redirecting...</div>";
        $strHtml .= '<form action="' . $this->submit_url . '" method="' . $this->submit_method . '" id="pay_form">';

        // Generate all the hidden field.
        foreach ($this->fields as $key=>$value)
        {
            $strHtml .= '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        $strHtml .= '</form><script type="text/javascript">
						window.onload=function(){
							document.getElementById("pay_form").submit();
						}
					</script>';

        $strHtml .= '</form>';
        $strHtml .= '</body></html>';
        return $strHtml;
    }

    public function getTranData($data){
        $timeNow = date('YmdHis');
        return array(
            'interfaceName' => 'ICBC_WAPB_B2C',
            'interfaceVersion' => '1.0.0.6',
            'orderInfo' => array(
                'orderDate' => $timeNow,
                'orderid' => $data['payment_id'],
                'amount' => $data['cur_money'] * 100,
                'installmentTimes' => '1',
                'curType' => '001',
                'merID' => '0200EE20167021',
                'merAcct' => '0200208619200105347'
            ),
            'custom' => array(
                'verifyJoinFlag' => 0,
                'Language' => 'ZH_CN',
            ),
            'message' => array(
                'merURL' => $this->callback_url,
                'merVAR' => 'asd',
                'notifyType' => 'HS',
                'resultType' => '0'
            ),
        );
    }

    /**
     * 获取公钥
     * @return string
     */
    public function getPublicKey(){
        //读取商户公钥文件
        $fp2 = fopen(self::BIZCRT_PATH, "rb");
        if($fp2 == NULL)
        {
            echo "open public file error<br/>";
            exit();
        }
        fseek($fp2,0,SEEK_END);
        $filelen2=ftell($fp2);
        fseek($fp2,0,SEEK_SET);
        $cert = fread($fp2,$filelen2);
        fclose($fp2);
        return $cert;
    }

    /**
     * 获取私钥
     * @return string
     */
    public function getPrivateKey(){
        //读取商户私钥文件
        $fp = fopen("./upload/" . $_FILES["merCertKey"]["name"],"rb");
        if($fp == NULL)
        {
            echo "open private file error<br/>";
            exit();
        }

        fseek($fp,0,SEEK_END);
        $filelen=ftell($fp);
        fseek($fp,0,SEEK_SET);
        $contents = fread($fp,$filelen);
        fclose($fp);

        $key = substr($contents,2);
        return $key;
    }

    public function dopay($payment){
        $tranDataArr = $this->getTranData($payment);

        $tranData = $this->arr2xml($tranDataArr);
        $tranData=str_replace("\n","",$tranData);

        //读取商户公钥文件
        $cert = $this->getPublicKey();

        /*签名*/
        $merSignMsg = $this->sign($tranData);//签名
        $tranDataBase64 = base64_encode($tranData);//对表单数据BASE64编码
        $merCertBase64 = base64_encode($cert);//对证书BASE64编码


        $this->fields = array(
            'interfaceName'     => 'ICBC_WAPB_B2C',
            'interfaceVersion'  => '1.0.0.6',
            'tranData'          => $tranDataBase64,
            'merSignMsg'        => $merSignMsg,
            'merCert'           => $merCertBase64,
            'clientType'        => 0
        );
        if(empty($merSignMsg)){
            exit('<h1>sign error</h1>');
        }
        echo $this->icbc_get_html();

        exit();
    }


    /**
     * curl post 请求
     * @param $url
     * @param $post_data
     * @return mixed
     */
    public function getResult($url, $post_data){
        $curlPost = function($request_url, $request_data){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,            $request_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );
            curl_setopt($ch, CURLOPT_POST,           1 );
            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($request_data));// 必须为字符串
            curl_setopt($ch, CURLOPT_HTTPHEADER,     array('Content-Type: application/json'));// 必须声明请求头
            $result = curl_exec($ch);
            curl_close($ch);
            \Neigou\Logger::General('pay.icbc.getResult', array(
                'action' => 'getResult',
                'sender'=>json_encode(array('url'=>$request_url,'data'=>$request_data)),
                'result'=>$result
            ));
            return $result;
        };

        $result = $curlPost($url, $post_data);
        $result = json_decode($result, true);
        if(isset($result['code']) && $result['code'] == '000001'){
            return $result['result'];
        }else{
            return false;
        }
    }

    /**
     * 第三方签名
     * @param $tranData
     * @return bool
     */
    public function sign($tranData){
        $sign_url = $this->getConf('sign_url', __CLASS__);
        return $this->getResult($sign_url, array('tranData'=>$tranData));
    }


    /**
     * 第三方验证签名
     * @param $notifyData
     * @param $sign
     * @return mixed
     */
    public function checkSign($notifyData, $sign){
        $verify_sign_url = $this->getConf('verify_sign_url', __CLASS__);
        return $this->getResult($verify_sign_url, array('notifyData'=>$notifyData,'sign'=>$sign));
    }

    public function decodeNotifyData($notifyData){
        $notifyData = base64_decode($notifyData);
        $notifyData = $this->xml2arr($notifyData);

        return $notifyData;
    }


    public function callback(&$recv){
        header('HTTP/1.1 200 OK');
        header('Content-type: text/plain;charset=UTF-8');
        header('Content-Length: 100');
        header('Access-Control-Expose-Headers:');
        header('Connection');
        header('Set-Cookie');
        header('X-Powered-By');
        header('X-Upstreami-ori');


        //echo 'https://test.xuebank.com/html/campusCode/icbcPay/close.html';
        //exit;
        //$recv['status'] = 'succ';
        //return $recv;
//        header('HTTP/1.1 200 OK');
//        header('Server: Apache/1.39');
//        header('Content-Length: 59');
//        header('Content-type: text/plain');
        \Neigou\Logger::General('pay.icbc.recv111', array('action' => 'callback', 'result'=>json_encode($recv)));

        $status = false;
        if(isset($recv['notifyData']) && $recv['signMsg']){
            $status = $this->checkSign($recv['notifyData'], $recv['signMsg']);
        }

        $ret = array();
        if($status){
            $notify = $this->decodeNotifyData($recv['notifyData']);
            $total_fee = $notify['orderInfo']['amount'] / 100;
            $ret['payment_id'] = $notify['orderInfo']['orderid'];
            $ret['account'] = $notify['orderInfo']['merID'];
            $ret['bank'] = app::get('wap')->_('工行网上支付');
            $ret['pay_account'] = app::get('wap')->_('付款帐号');
            $ret['currency'] = 'CNY';
            $ret['money'] = $total_fee;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $total_fee;
            $ret['trade_no'] = $notify['bank']['TranSerialNo'];//$recv['trade_no'];
            $ret['t_payed'] = $notify['bank']['notifyDate'];//(strtotime($recv['notify_time']) ? strtotime($recv['notify_time']) : time());
            $ret['pay_app_id'] = "micbc";
            $ret['pay_type'] = 'online';
            $ret['memo'] = $notify['bank']['comment'];//$recv['body'];

            \Neigou\Logger::General('pay.icbc.notify', array('action' => 'callback', 'result'=>json_encode($notify)));
            if($notify['bank']['tranStat'] == 1) {
                \Neigou\Logger::General('pay.icbc.recv.success', array('action' => 'callback', 'result'=>json_encode($recv)));
                $ret['status'] = 'succ';
                echo ECSTORE_DOMAIN . "/m/paycenter2-result_pay-{$ret['payment_id']}.html";
            }else {
                $ret['status'] =  'failed';
            }
        }else{
            $message = 'Invalid Sign';
            $ret['status'] = 'invalid';
        }
        $ret['callback_source'] = 'server';
        //echo 'https://test.xuebank.com/html/campusCode/icbcPay/close.html';
        //exit;
        return $ret;
    }

    /**
     * 获取返回商户按钮链接
     * @param $payment_id
     * @return string
     */
    public function getBackMeUrl($payment_id){
        //return ECSTORE_DOMAIN . '/m/paycenter2-result_pay-'.$payment_id.'.html';
        return 'https://test.xuebank.com/html/campusCode/icbcPay/close.html';
        //return 'https://test.neigou.com/product-3774390.html';
    }



//↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑公共函数部分↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑

}