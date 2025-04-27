<?php
        //define('WX_APPID', 'wx745c077a3a20f305');//内购的appid
        //define('WX_SECRET', '31a7da095552f26bfdd714f9f4a73fb3');//内购的secret
        define('WX_APPID', 'wxf3249ddedebd8b4d');//上线后换成 内购的appid
        define('WX_SECRET', '784df07d2aaa9cb6676df17f897c6e61');
        // define('WX_APPID', 'wx745c077a3a20f305'); //内购联盟
        // define('WX_SECRET', '31a7da095552f26bfdd714f9f4a73fb3');

        define('WX_SCOPE', 'snsapi_userinfo');//snsapi_base
        define('RESPONSE_TYPE', 'code');

        define('WX_TO_CODE', 'https://open.weixin.qq.com/connect/oauth2/authorize?');
        define('WX_TO_OPENID', 'https://api.weixin.qq.com/sns/oauth2/access_token?');
        define('WX_USER_INFO', 'https://api.weixin.qq.com/sns/userinfo?');


        //define('HOST_REDIRECT', 'test.neigou.com'); // 上线后换成www.neigou.com
        define('HOST_REDIRECT', PSR_WEB_NEIGOU_DOMAIN); // 上线后换成www.neigou.com
        define('HOST_REDIRECT_TO', PSR_WEB_NEIGOU_HD.'/Home/Hongbao/weixinInfoBridge');// 上线后换成hd.neigou.com/Home/Hongbao/weixinInfoBridge
        define('HOST_REDIRECT_TO_V', PSR_WEB_NEIGOU_HD.'ht/Home/Hongbao/getVoucherVerify');// 上线后换成hd.neigou.com/Home/Hongbao/getVoucherVerify
        define('SIGN_KEY','1a445e15c32bb407ab725e517840f5b91');
?>
