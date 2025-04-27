<?php
class wxauth {
	function __construct(){
		define('WX_APPID', 'wxf3249ddedebd8b4d');//fuli!
		define('WX_SECRET', '784df07d2aaa9cb6676df17f897c6e61');
		define('WX_SCOPE', 'snsapi_userinfo');
		define('RESPONSE_TYPE', 'code');
		
		define('WX_TO_CODE', 'https://open.weixin.qq.com/connect/oauth2/authorize?');
		define('WX_TO_OPENID', 'https://api.weixin.qq.com/sns/oauth2/access_token?');
		
		//define('HOST', $_SERVER['HTTP_HOST']);
		//define('COOKIE_EXP', time()+86400*30);//cookie缓存周期
	}
	/**
	 * @author	xu.sun
	 * @desc	第一步验证
	 */
	function gettoken(){
		setcookie('redirect_weixin_url', $_GET['redirect_url']);
		$data['appid'] = WX_APPID;
		$data['scope'] = WX_SCOPE;
		$data['response_type'] = RESPONSE_TYPE;
		$data['redirect_uri'] =  'http://' . $_SERVER['HTTP_HOST'] . '/plugin/wxfuliauth_user.php?act=getopenid';
		$data['state'] = 123;
		$data['to_url'] = $to_url = WX_TO_CODE . http_build_query($data) . '#wechat_redirect';
		header("Location:". $to_url);
	}
	/**
	 * @author	xu.sun
	 * @desc	第二部验证，获取用户access_token、openid
	 */
	function getopenid(){
        $cookie_redirect = isset($_COOKIE['redirect_weixin_url']) ? trim($_COOKIE['redirect_weixin_url']) : 'http://fuli.neigou.com/WxAuth/getopenid';
		$data['appid'] = WX_APPID;
		$data['secret'] = WX_SECRET;
		$data['grant_type'] = 'authorization_code';
		$data['code'] = $_GET['code'] ? trim($_GET['code']) : '';
		$data['to_url'] = $to_url = WX_TO_OPENID . http_build_query($data);
		//var_export($data);
		$res = $this->actionPost($to_url, $data='');
		if($res){
			$result = json_decode($res, true);
			header("Location:". $cookie_redirect.'?openid='.$result['openid'].'&token='.$result['access_token']);
		}
		else
			header("Location:". $cookie_redirect.'?openid=error');
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

}
$wx = new wxauth();

		$act = isset($_GET['act']) ? trim($_GET['act']) : 'gettoken';
		if($act){
			if(method_exists($wx, $act)){
				$wx->$act();
			}
         }