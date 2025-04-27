<?php
require dirname(__FILE__)."/commonFun.php";
if(isset($_SERVER['HTTP_USER_AGENT'])&&(stripos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')!==false) && stripos($_SERVER['HTTP_USER_AGENT'], 'wxwork')===false)
	define('ISWX', true);
else
	define('ISWX', false);

if(isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], '_APP_'))
    define('ISAPP', true);
else
    define('ISAPP', false);

class wxauth {
	function __construct(){
		define('WX_APPID', 'wxf3249ddedebd8b4d');//neigou
		define('WX_SECRET', '784df07d2aaa9cb6676df17f897c6e61');
		define('WX_SCOPE', 'snsapi_base');//snsapi_userinfo
		define('RESPONSE_TYPE', 'code');
		
		define('WX_TO_CODE', 'https://open.weixin.qq.com/connect/oauth2/authorize?');
		define('WX_TO_OPENID', 'https://api.weixin.qq.com/sns/oauth2/access_token?');
		
		$host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
		define('HOST', $host);
		define('COOKIE_EXP', time()+86400*30);//cookie缓存周期
	}
	/**
	 * @author	xu.sun
	 * @desc	第一步验证
	 */
	function gettoken(){
		$host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

		$this->callback_location(false);//存储回调地址
		
		$data['appid'] = WX_APPID;
		$data['scope'] = WX_SCOPE;
		$data['response_type'] = RESPONSE_TYPE;
		$data['redirect_uri'] = $redirect_uri = $_GET['redirect_uri'] ? trim($_GET['redirect_uri']) : 'http://' . $host . '/plugin/wxauth.php?act=getopenid';
		$data['state'] = 123;
		$data['to_url'] = $to_url = WX_TO_CODE . http_build_query($data) . '#wechat_redirect';
		header("Location: $to_url");
        exit;
	}
	/**
	 * @author	xu.sun
	 * @desc	第二部验证，获取用户access_token、openid
	 */
	function getopenid(){
		$data['appid'] = WX_APPID;
		$data['secret'] = WX_SECRET;
		$data['grant_type'] = 'authorization_code';
		$data['code'] = $_GET['code'] ? trim($_GET['code']) : '';

		$data['to_url'] = $to_url = WX_TO_OPENID . http_build_query($data);

		$redirect = $this->callback_location(true);
		$res = $this->actionPost($to_url, $data='');
		if($res){
			$result = json_decode($res, true);
            $wxunionid = $result['unionid'];
            if (empty($wxunionid)) {
                // get user's unionid
				/*
                $access_token_url = sprintf("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s", WX_APPID, WX_SECRET);
                $access_response = file_get_contents($access_token_url);
                $access_response_data = json_decode($access_response, true);
				*/
				$token = wxCommonFun::wx_get_token(WX_APPID);
				$access_response_data["access_token"] = $token;
                if (!empty($access_response_data["access_token"])) {
                    $get_unionid_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".
                        $access_response_data['access_token'].
                        "&openid=".$result['openid']."&lang=zh_CN";
                    $union_data = file_get_contents($get_unionid_url);
                    $union_data_array = json_decode($union_data, true);
                    if (isset($union_data_array["unionid"])) {
                        $wxunionid = $union_data_array["unionid"];
                    }
                }
            }
			if(!empty($result) && $result['openid'] && $wxunionid){
				$redirect = "http://" . HOST ."/plugin/wxauth.php?act=authMark&wxopenid=".$result['openid'].
                    "&wxencrypt=".md5($result['openid'])."&wxunionid=".$wxunionid.
                    "&wxunionencrypt=".md5($wxunionid);
			}
		}
		header("Location: $redirect");
        exit;
	}
	function authMark(){
		$wxopenid = $_GET['wxopenid'];
		$wxencrypt = $_GET['wxencrypt'];
        $wxunionid = $_GET['wxunionid'];
        $wxunionencrypt = $_GET['wxunionencrypt'];
		if(($wxopenid && $wxencrypt && $wxunionid && $wxunionencrypt)){
			setcookie('LEHO_wx_openid', $wxopenid, COOKIE_EXP, '/', HOST);
            setcookie('LEHO_wx_unionid', $wxunionid, COOKIE_EXP, '/', HOST);
			SetCookie('LEHO_save_time', time(), COOKIE_EXP, '/', HOST);
			$callback = $this->callback_location(true);
			header("Location: $callback");
            exit;
		}else{
			header("Location: http://" . HOST . "?error_location_redirect");
            exit;
		}
	}
	function actionPost($http_url, $postdata){
		$ch = curl_init();//初始化curl
		curl_setopt($ch, CURLOPT_URL, $http_url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
		$result = curl_exec($ch);
		if (curl_errno($ch)) {
			echo 'Errno'.curl_error($ch);
		}
		curl_close($ch);
		return $result;
	}
	/**
	 * @author	xu.sun
	 * @desc	存储进入回调路径
	 * @param  $bo
	 * @return string
	 */
	function callback_location($bo=false){
		if($bo){
			return $_COOKIE['callback_location'] ? $_COOKIE['callback_location'] : '/';
		}else{
			$url = 'http://' . HOST . $_SERVER['REQUEST_URI'];
            //微信bug导致第一条cookie会被忽略，这里占位一下
            SetCookie('micro_placeholder', $url, time()+3600*24, '/', HOST);//24小时回调地址
			SetCookie('callback_location', $url, time()+3600*24, '/', HOST);//24小时回调地址
		}
	}







}
$yes_openid = isset($_COOKIE['LEHO_wx_openid']) ? $_COOKIE['LEHO_wx_openid'] : '';
$yes_unionid = isset($_COOKIE['LEHO_wx_unionid']) ? $_COOKIE['LEHO_wx_unionid'] : '';
$wx = new wxauth();
if(ISWX && !isset($_GET['callback']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest'){
	if(empty($yes_openid) || empty($yes_unionid)){
		$act = isset($_GET['act']) ? trim($_GET['act']) : 'gettoken';
		if($act){
			if(method_exists($wx, $act)){
				$wx->$act();
			}
		}
	}
}
