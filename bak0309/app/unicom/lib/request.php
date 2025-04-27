<?php

/**
 * 联通请求
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_request
{
    /**
     * 拉取地址列表
     *
     * @param   array       $requestData    请求信息
     * @param   $errMsg     string          错误信息
     * @return  mixed
     */
    public function request($requestData, & $errMsg = '')
    {
        $logic_openapi = kernel::single('b2c_openapi');
        $result = $logic_openapi->CurlOAuth2OpenAPI(OPENAPI_DOMAIN. '/ChannelInterop/V1/Unicom/Gateway/forward', $requestData);
        \Neigou\Logger::Debug('unicom_request', array('request' => $requestData, 'result' => $result));
        $result = json_decode($result, true);

        if (empty($result['Result']) OR $result['Result'] !== 'true')
        {
            $errMsg =  ! empty($result['ErrorMsg']) ? $result['ErrorMsg'] : '请求错误';
            return false;
        }

        return $result['Data'];
    }

    /** 请求内购openapi获取登录信息
     *
     * @param $requestData
     * @return mixed
     * @author liuming
     */
    public function requestOpenapi($requestData,$path = '')
    {
        if (empty($path)){
            $path = '/ChannelInterop/V1/Unicom/O2OGateway/forward';
        }

        $logic_openapi = kernel::single('b2c_openapi');

        $res = $logic_openapi->CurlOAuth2OpenAPI(OPENAPI_DOMAIN.$path,$requestData);

        \Neigou\Logger::Debug('unicom_request_openapi', array('action'=>'o2o','request' => $requestData, 'result' => $res ,'remark'=>$path));

        if (is_array($res)){
            return $res;
        }
        return json_decode($res,true);

        //return $logic_openapi->CurlOAuth2OpenAPI(OPENAPI_DOMAIN.'/ChannelInterop/V1/Unicom/user/login',$requestData);

    }

}

