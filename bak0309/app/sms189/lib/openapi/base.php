<?php
/**
 * 通用接口API 基础类
 * @author Ethan [pansen@leho.com]
 * @version 1.0 2015-03-25 18:19:45
 */
class sms189_openapi_base{

	 private  static   $token= 'LEHO_SMS189_MOBILE_TOKEN_2015';
	
	/**
	 *  数据验证
	 * @param array $data
	 */
	function validate($data = array()){
		if(empty($data)){
			self::error('DATA_EMPTY','数据信息丢失,拒绝访问');
		}
		//添加api_test 如果API参数存在此参数，不进行API签名验证
		$isvalidata = true;
		$status = app::get('sms189')->getConf('sms189.app.leho.api.test.status');
		if($status == 'true'){
			$apitestvalue = app::get('sms189')->getConf('sms189.app.leho.api.test.value');
			if (isset($data['api_test']) && !empty($apitestvalue) && ($data['api_test'] == $apitestvalue)) {
				$isvalidata = false;
			}
		}
		if ($isvalidata) {
			$sign = $data['sign'];
			unset($data['sign']);
			if($sign!=self::gen_sign($data)){
				//验证失败
				self::error('TOKEN_VALIDATION_FAILED','Token validation failed');
			}
		}
		
	}
	
	/**
	 *  generate sign
	 * @param array $params
	 * @param string|NULL $token
	 * @return string
	 */
	function gen_sign($params, $token = NULL){
		if(is_null($token)) $token = self::$token;
		return strtoupper(md5(strtoupper(md5(self::assemble($params))) . $token));
	}
	
	
	/**
	 * assemble parameter for building sign
	 * @param array $params
	 * @return NULL|string
	 */
	function assemble($params) {
		if (!is_array($params))
			return null;
		ksort($params,SORT_STRING);
		$sign = '';
		foreach($params as $key => $val) {
			if (is_null($val))
				continue;
			if (is_bool($val))
				$val = ($val) ? 1 : 0;
			$sign .= $key . (is_array($val) ? self::assemble($val) : $val);
		}
		return $sign;
	}
	
	/**
	 *  get UTF-8 BOM header mark
	 * @param string $result
	 * @return string
	 */
	function bomheader($result) {
		$bomheader = base_convert(ord($result[0]),10,16) . base_convert(ord($result[1]),10,16) . base_convert(ord($result[2]),10,16);
		return $bomheader;
	}
	
	/**
	 * 输出错误信息 
	 * @param string $errorno 错误代码
	 * @param string $msg  错误信息
	 */
	function error($errorno,$msg=''){
		$rs = array(
			'status'=>'fail',
			'data'=>$errorno,
			'msg'=>$msg
		);
		echo json_encode($rs);
		exit;
	}
	/**
	 * 信息输出
	 * @param array|boolean $data
	 */
	function success($data){
		$rs = array(
			'status'=>'succ',
			'data'=>$data,
			'msg'=>''
		);
		echo json_encode($rs);
		exit;
	}
}

