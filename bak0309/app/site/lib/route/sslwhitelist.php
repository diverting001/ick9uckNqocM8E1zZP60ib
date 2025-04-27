<?php
/**
 * Created by PhpStorm.
 * User: gaoyanlong
 * Date: 16/8/22
 * Time: 下午2:13
 */

class site_route_sslwhitelist{
    private $__check_white_list    = true;
    private $white_list = array(
        'passport-login',
        'passport-post_login',
        'fake_statistics/visit/passport-login',
        'index-gen_vcode-b2c-4',
        'passport-login-not_auto',
        'passport-tencent_login',
        'passport-tencent_email_validate',
        'jifenmember-httpsCreateLoginTokenForChannelUser',
        'jifenmember-httpsCreateLoginUrl',
        'express-getOrderList',
        'autologin_token',
    );

    /*
     * @todo 检查请求地址是否在白名单里
     */
    public function ChekSSl($query){
        if($this->__check_white_list == false) return -1;
        if(in_array($query,$this->white_list)) return true;
        else if(stristr($query,'passport-post_login-')) return true;

        else if(stristr($query,'passport-autologin-')) return true;

        else if(stristr($query,'jifencart-checkout')) return true;
        else if(stristr($query,'jifencart-stocks')) return true;
        else if(stristr($query,'jifencart-total')) return true;
        else if(stristr($query,'jifenorder-create')) return true;
        else if(stristr($query,'jifenorder-check')) return true;
        else if(stristr($query,'jifenmember-set_default')) return true;
        else if(stristr($query,'passport-autologin_token-')) return true;

        else if(stristr($query,'jifenmember-setpaypwd') && stristr($query,'jifenmember-setpaypwdpage') === false) return true;
        else if(stristr($query,'jifenmember-setjifenpaypwd') && stristr($query,'jifenmember-setjifenpaypwdpage') === false) return true;
        else if(stristr($query,'jifenmember-set_or_reset_paypwd')) return true;
        else if(stristr($query,'openapi/cas')) return true;

        else return false;
    }

}