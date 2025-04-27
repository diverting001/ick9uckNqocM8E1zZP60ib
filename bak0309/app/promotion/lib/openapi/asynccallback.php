<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/10/29
 * Time: 下午8:05
 */

define('ASYNC_CALL_SIGN','c4f1cde9f44341f584f9ddc86b1ce516');

class promotion_openapi_asynccallback {
    const hd_openapi_url = "/OpenApi/apirun";
    public function login_success() {
        $data = $_REQUEST['params'];
        $params = json_decode($data, true);
        if(empty($params) ||
            !isset($params['member_id']) ||
            !isset($params['token'])) {
            echo "fail";
            return;
        }
        if (!$this->check_token_info($params)) {
            echo "fail";
            return;
        }
//        if ($params['from_company'] == 'verified_company') {
//            kernel::single("promotion_voucher_memvoucher")->dispatch_voucher_for_register($params['member_id']);
//        } else if ($params['from_company'] == 'nonverified_company') {
//            kernel::single("promotion_voucher_memvoucher")->dispatch_voucher_for_nonverified($params['member_id']);
//        }

        // 发送微信绑定券
//        if ($params['weixin_login'] == 'true' && !empty($params['wxopenid'])) {
//            $members_model = app::get('b2c')->model('members');
//            $member_info  = $members_model->getList('member_id,wxopenid',array('member_id'=>$params['member_id']));
//            $appid = app::get('weixin')->getConf('ecos.leho.weixin.appid');
//            $appsecret = app::get('weixin')->getConf('ecos.leho.weixin.appsecret');
//
//            /*
//            $access_token_url = sprintf("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s", $appid, $appsecret);
//            $access_response = file_get_contents($access_token_url);
//            $access_response_data = json_decode($access_response, true);
//            */
//            $access_response_data['access_token'] = $this->wx_get_token($appid);
//
//            if (!empty($access_response_data["access_token"])) {
//                $get_user_info_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".
//                    $access_response_data['access_token'].
//                    "&openid=".$params['wxopenid']."&lang=zh_CN";
//                $weixin_user_info = file_get_contents($get_user_info_url);
//                $weixin_user_info_array = json_decode($weixin_user_info, true);
//                logger::logtestkv("promotion.action", array("action"=>"promotion_openapi_asynccallback", "member_id"=>$params['member_id'], "params"=>json_encode($params), "weixin_user_info"=>$weixin_user_info));
//                if (isset($weixin_user_info_array["subscribe"]) && $weixin_user_info_array["subscribe"]==1) {
//                    kernel::single("promotion_voucher_memvoucher")->dispatch_voucher_for_weixinbind($params['member_id']);
//                }
//            }
//        }

        // 通知第三方微信绑定，如分众
        if (!empty($params['thirdparty_cookie_key'])) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"promotion_openapi_asynccallback",
                "exec_step"=>"thirdparty_weixin", "member_id"=>$params['member_id'], "thirdparty_cookie_key"=>$params['thirdparty_cookie_key']));
            $post_data = array(
                'class_obj'=>'ThirdpartyWeixinBind',
                'method'=>'bindMember',
                'member_id'=>$params['member_id'],
                'uniqid_key'=>$params['thirdparty_cookie_key'],
            );

            $token = kernel::single('b2c_safe_apitoken')->generate_token($post_data, OPENAPI_TOKEN_SIGN);
            $post_data['token'] = $token;

            $url = HD_DOMAIN.self::hd_openapi_url;
            $httpclient = kernel::single('base_httpclient');
            $response = $httpclient->set_timeout(6)->post($url,$post_data);
        }
        
        //给指定公司派发礼包
        $pkg = kernel::single("promotion_voucher_memvoucher");
        $pkg->applyMemberPkg($params['member_id'],$params['company_id']);    
        
        //更新模块
        $curl   = new \Neigou\Curl();
        $tokenData = array();
        $tokenData['class_obj'] = 'Module';
        $tokenData['method'] = 'EnsureDefaultPackageState';
        $tokenData['company_id'] = $params['company_id'];
        $token = kernel::single('b2c_safe_apitoken')->generate_token($tokenData, OPENAPI_TOKEN_SIGN);
        $tokenData['token'] = $token;
        $curl->Post(LIFE_DOMAIN . '/OpenApi/apirun',$tokenData);
        
        //根据公司类型同步默认模块
        $setTokenData = array();
        $setTokenData['class_obj'] = 'Module';
        $setTokenData['method'] = 'CheckDefaultModule';
        $setTokenData['company_id'] = $params['company_id'];
        $getToken = kernel::single('b2c_safe_apitoken')->generate_token($setTokenData, OPENAPI_TOKEN_SIGN);
        $setTokenData['token'] = $getToken;
        $curl->Post(LIFE_DOMAIN . '/OpenApi/apirun',$setTokenData);

        echo "success";
        return;
    }
    
    private function wx_get_token($appid){

        $bindinfo = app::get('weixin')->model('bind')->getRow('appid, appsecret, id',array('appid'=>$appid));
        if( $bindinfo['appid'] && $bindinfo['appsecret']) {

        }else{
            return  false;
        }

        $bind_id = $bindinfo['id'];
        $wechat = kernel::single('weixin_wechat');
        $token = $wechat->get_basic_accesstoken($bind_id);

        return $token;
    }
    private function check_token_info($arr) {
        $token = $arr["token"];
        unset($arr["token"]);
        ksort($arr);
        $sign_ori_string = "";
        foreach($arr as $key=>$value) {
            if (!empty($value) && !is_array($value)) {
                if (!empty($sign_ori_string)) {
                    $sign_ori_string .= "&$key=$value";
                } else {
                    $sign_ori_string = "$key=$value";
                }
            }
        }
        $sign_ori_string .= ("&key=".ASYNC_CALL_SIGN);
        return  $token == strtoupper(md5($sign_ori_string)) ? true : false;
    }    
}