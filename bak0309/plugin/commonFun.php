<?php

class wxCommonFun {
    static $OpenapiSign = OPENAPI_SIGN;
    static $HOST_REDIRECT = PSR_WEB_NEIGOU_DOMAIN;

    public static function wx_get_token($app_id) {

        $url = self::$HOST_REDIRECT.'/openapi/wxapi/weixintoken';
        $data = array("appid"=>$app_id);
        $retToken = self::CurlOpenApi($url,$data);

        $TokenArr = json_decode($retToken,true);
        $token = "";
        if($TokenArr["data"]["code"]==200){
            $token = $TokenArr["data"]["data"]["token"];
            return $token;
        }else{
            return $token;
        }

    }

    private static function check_token($arr) {
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
        $sign_ori_string .= ("&key=".self::$OpenapiSign);
        return  $token == strtoupper(md5($sign_ori_string)) ? true : false;
    }

    private static function get_token($arr) {
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
        $sign_ori_string .= ("&key=".self::$OpenapiSign);
        return  strtoupper(md5($sign_ori_string));
    }

    /**
     * @param $arr
     *
     */
    private static function CurlOpenApi($api_url,$arr){
        $arr['token']=self::get_token($arr);
        $result=self::actionPost($api_url,http_build_query($arr));
        return $result;
    }


    private static function actionPost($http_url, $postdata){
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $http_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Errno'.curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }
    
}


?>
