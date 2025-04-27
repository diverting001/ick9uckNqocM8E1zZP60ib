<?php
require dirname(__FILE__)."/configwx.php";
class wxhongbao {
	function __construct(){

	}
	/**
	 * @author	xu.sun
	 * @desc	��һ����֤
	 */
	function gettoken($key,$view){

		$host = HOST_REDIRECT;
		$data['appid'] = WX_APPID;
		$data['redirect_uri'] =  'http://' . $host . '/plugin/wxhongbao.php?act=getopenid&key='.$key.'&view='.$view;
		$data['response_type'] = RESPONSE_TYPE;
		$data['scope'] = "snsapi_base";
		$data['state'] = 123;
		//$data['to_url'] = $to_url = WX_TO_CODE . http_build_query($data) . '#wechat_redirect';
		$to_url = WX_TO_CODE . http_build_query($data) . '#wechat_redirect';

		header("Location:". $to_url);
	}
	/**
	 * @author	xu.sun
	 * @desc	�ڶ�����֤����ȡ�û�access_token��openid
	 */
	function getopenid($key,$view){
        //$cookie_redirect = isset($_COOKIE['redirect_weixin_url']) ? trim($_COOKIE['redirect_weixin_url']) : 'http://weixin.leho.com/WxAuth/getopenid';
		//$cookie_redirect = 'http://hd.test.neigou.com/Home/Hongbao/getVoucherVerify?key='.$key.'&view='.$view;
		$cookie_redirect = HOST_REDIRECT_TO_V.'?key='.$key.'&view='.$view;
		$data['appid'] = WX_APPID;
		$data['secret'] = WX_SECRET;
		$data['grant_type'] = 'authorization_code';
		$data['code'] = $_GET['code'] ? trim($_GET['code']) : '';
		$data['to_url'] = $to_url = WX_TO_OPENID . http_build_query($data);
		//var_export($data);
		$res = $this->actionPost($to_url, $data='');
		if($res){
			$result = json_decode($res, true);
			header("Location:". $cookie_redirect.'&openid='.$result['openid']);
		}
		else
			header("Location:". $cookie_redirect.'&openid=error');
	}

	function actionPost($http_url, $postdata){
		$ch = curl_init();
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
/*
$wx = new wxauth();

		$act = isset($_GET['act']) ? trim($_GET['act']) : 'gettoken';
		if($act){
			if(method_exists($wx, $act)){
				$wx->$act();
			}
         }
*/
$wx = new wxhongbao();
$key = $_REQUEST['key']?$_REQUEST['key']:'';
$view = $_REQUEST['view']?$_REQUEST['view']:'2';
if(empty($key)){
	header("Location:".PSR_WEB_NEIGOU_DOMAIN);
}
$act = isset($_GET['act']) ? trim($_GET['act']) : 'gettoken';

if($act){
	if(method_exists($wx, $act)){
		$wx->$act($key,$view);
	}
}
