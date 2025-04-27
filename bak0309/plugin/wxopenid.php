<?php
require dirname(__FILE__)."/configwx.php";

if(isset($_SERVER['HTTP_USER_AGENT'])&&(stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false) && stripos($_SERVER['HTTP_USER_AGENT'], 'wxwork')===false)
    define('ISWX', true);
else
    define('ISWX', false);

class wxopenid{

    function __construct(){
        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
        define('HOST', $host);
    }

    function gettoken(){
        $callback = $_GET['callback'] ? '&callback=' . $_GET['callback'] : '';
        $data['appid'] = WX_APPID;
        $data['redirect_uri'] = 'http://' . HOST . '/plugin/wxopenid.php?act=getopenid' . $callback;
        $data['response_type'] = 'code';
        $data['scope'] = 'snsapi_base';
        $data['state'] = 123;
        $data['to_url'] = $to_url = WX_TO_CODE . http_build_query($data) . '#wechat_redirect';
        header("Location: $to_url");
    }
    
    function getopenid(){
        $data['appid'] = WX_APPID;
        $data['secret'] = WX_SECRET;
        $data['grant_type'] = 'authorization_code';
        $data['code'] = $_GET['code'] ? trim($_GET['code']) : '';
        $data['to_url'] = $to_url = WX_TO_OPENID . http_build_query($data);
        $res = actionPost($to_url, $data);
        $res = json_decode($res,true);
        if($res && $res['openid'] && $_GET['callback']){
            $callback = urldecode($_GET['callback']);
            $callback_arr = parse_url($callback);
            $callback .= isset($callback_arr['query']) ? '&' : '?';
            $temp = array();
            $temp['openid'] = base64_encode($res['openid']);
            $url = $callback . http_build_query($temp);
            header('Location: ' . $url);
            exit;
        }else{
            echo 'error';
            exit;
        }
    }
    
}

function actionPost($http_url, $postdata){
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

$wx = new wxopenid();

if(ISWX){
    $act = isset($_GET['act']) ? trim($_GET['act']) : 'gettoken';
    if($act){
        if(method_exists($wx, $act)){
            $wx->$act();
        }
    }
}
