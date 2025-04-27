<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/10/29
 * Time: 下午12:05
 */

define('ASYNC_CALL_SIGN','c4f1cde9f44341f584f9ddc86b1ce516');

class promotion_pam_login
{
    private $white_list;	// 白名单

    public function __construct() {
        $this->white_list = array(
            790,    // 长青腾  创业营
        );
    }

    public function listener_login(&$arr_params)
    {
        \Neigou\Logger::Debug("promotion.action", array("action"=>"listener_login", "member_id"=>$arr_params['member_id']));
        if (empty($arr_params['member_id'])) {
            return;
        }
        $member_id = $arr_params['member_id'];

        $obj_mem = app::get('b2c')->model('members');
        $member_data = $obj_mem->getList("*",array('member_id'=>$member_id));
        if (empty($member_data) || !isset($member_data[0]['company_id'])) {
            return ;
        }
        $company_id = $member_data[0]['company_id'];


        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] :
            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

        $data=array(
            'member_id'=>$member_id,
        );
        
        $data['company_id'] = $company_id;
        
        //判断公司是否开启强认证
        $welfare_lib = kernel::single('birthday_birthday');
        $is_verify = $welfare_lib->check_company_verify($company_id);
        if ($is_verify) {
            $data['from_company'] = 'verified_company';
        } else if (!$is_verify && in_array($company_id, $this->white_list)) {
            // 判断公司是否为非强认证且在白名单中
            $data['from_company'] = 'nonverified_company';
        }

        if (isset($_COOKIE['LEHO_wx_unionid']) &&
            !empty($_COOKIE['LEHO_wx_unionid']) &&
            isset($_COOKIE['LEHO_wx_openid']) &&
            !empty($_COOKIE['LEHO_wx_openid']) &&
            kernel::single('base_component_request') -> is_browser_tag('weixin')) {
            $data['weixin_login'] = 'true';
            $data['wxopenid'] = $_COOKIE['LEHO_wx_openid'];
        }
        if (isset($_COOKIE['thirdparty_cookie_key']) &&
            !empty($_COOKIE['thirdparty_cookie_key'])) {
            $data['thirdparty_cookie_key'] = $_COOKIE['thirdparty_cookie_key'];
        }
        \Neigou\Logger::Debug("promotion.action", array("action"=>"weixinpublish", "member_id"=>$member_id,
            "wx_unionid"=>$_COOKIE['LEHO_wx_unionid'], "wx_openid"=>$_COOKIE['LEHO_wx_openid'], "weixin_login"=>$data['weixin_login'], "cookie_data"=>json_encode($_COOKIE)));

        $data['token'] = $this->generate_token_info($data);

        $post_data = array(
            'callback_url'=>$host."/openapi/promotion/login_success",
            'data'=>json_encode($data)
        );

        \Neigou\Logger::Debug("promotion.action", array("action"=>"publish", "member_id"=>$member_id,
            "from_company"=>$data['from_company'], "weixin_login"=>$data['weixin_login']));
        $remotequeue_service = kernel::service("remotequeue.service");
        $remotequeue_service->dispatchScriptCommandTaskSimpleNoReply('common.service.httpForward',json_encode($post_data));
    }

    private function generate_token_info($arr) {
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
        return  strtoupper(md5($sign_ori_string));
    }
}
