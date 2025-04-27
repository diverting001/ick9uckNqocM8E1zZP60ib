<?php

/**
 * 微信小程序 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mwxmini_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');

        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        $postArray = kernel::single('site_utility_xml')->xml2array($postStr);
        $postData['weixin_postdata']  = $postArray['xml'];
        $nodify_data = array_merge($_GET,$postData);
        $recv = $nodify_data['weixin_postdata'];
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.mwxmini',array('remark'=>'notify_param','data'=>$recv,'raw_data'=>$GLOBALS["HTTP_RAW_POST_DATA"],'request_data'=>$_REQUEST));
        #键名与pay_setting中设置的一致
        if($this->is_return_vaild($recv)){
            $objMath = kernel::single('ectools_math');
            $ret = array();
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $recv['openid'];
            $ret['bank'] = app::get('weixin')->_('微信小程序支付').$recv['bank_type'];
            $ret['pay_account'] = app::get('weixin')->_('微信小程序支付');
            $ret['currency'] = 'CNY';
            $ret['money'] = $objMath->number_multiple(array($recv['total_fee'], 0.01));
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $objMath->number_multiple(array($recv['total_fee'], 0.01));
            $ret['trade_no'] = $recv['transaction_id'];
            $ret['t_payed'] = strtotime($recv['time_end']);
            $ret['pay_app_id'] = "mwxmini";
            $ret['pay_type'] = 'online';
            $ret['memo'] = '微信交易单号:'.$recv['transaction_id'];
            $ret['thirdparty_account'] = $recv['openid'];
            //校验支付结果
            if ( $recv['result_code']=="SUCCESS" or $recv['return_code']=="SUCCESS" ) {
                $this->msg(true);
                $ret['status'] = 'succ';
            }else{
                $this->msg(false);
                $ret['status']='failed';
            }
        }else{
            $this->msg(false);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mwxmini',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }

    public function mini_login(){
        $code = trim($_REQUEST['code']);
        $sign_key = trim($_REQUEST['sign_key']);
        //获取open_id
        $open_data = $this->codeToOpenId($code);
        $open_id = $open_data['openid'];
        //获取支付单信息
        $pay_data = kernel::single('wap_payment_plugin_mwxmini')->getPayData($sign_key,$open_id);
        echo json_encode($pay_data);
    }

    public function get_payment(){
        $sign_key = trim($_REQUEST['sign_key']);
        $app_id = trim($_REQUEST['app_id']);
        //获取支付单信息
        $pay_data = kernel::single('wap_payment_plugin_mwxmini')->getPayData($sign_key,$app_id);
        echo json_encode($pay_data);
    }


    /**
     * 响应输出
     * @param $bool
     */
    private function msg($bool){
        if($bool){
            $msg = 'success';
        } else {
            $msg = 'fail';
        }
        echo $msg;
    }

    /**
     * 检验返回数据合法性
     * @param $param array
     * @access private
     * @return boolean
     */
    public function is_return_vaild($postData) {

        $paySignKey = trim($this->getConf('pay_sign', 'wap_payment_plugin_mwxmini')); // PaySignKey 对应亍支付场景中的 appKey 值
        ksort($postData);
        $unSignParaString = weixin_commonUtil::formatQueryParaMap($postData, false);
        return weixin_commonUtil::verifySignature($unSignParaString, $postData['sign'], $paySignKey);
    }

    /**
     * 使用code换取open_id
     */
    public function codeToOpenId($code){
        $url = 'https://api.weixin.qq.com/sns/jscode2session?';
        $req['appid'] = $this->getConf('app_id','wap_payment_plugin_mwxmini');
        $req['secret'] = $this->getConf('app_secret','wap_payment_plugin_mwxmini');
        $req['js_code'] = $code;
        $req['grant_type'] = 'authorization_code';
        $request_url = $url.http_build_query($req);
        $json = file_get_contents($request_url);
        $res = json_decode($json,true);
        \Neigou\Logger::General('mwxmini.code_to_open_id',array(
            'data'=>$req,
            'request_url'=>$request_url,
            'json'=>$json,
            'res'=>$res
        ));
        if(empty($res)){
            return false;
        } else {
            return $res;
        }
    }
}