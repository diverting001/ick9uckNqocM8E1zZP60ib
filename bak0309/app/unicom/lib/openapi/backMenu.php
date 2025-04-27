<?php
/**
 *Create by PhpStorm
 *User:liangtao
 *Date:2021-9-16
 */

class unicom_openapi_backMenu
{
    public function __construct()
    {
        $this->data = $_POST;

        $_check = kernel::single('b2c_safe_apitoken');
        if ($_check->check_token($_POST, OPENAPI_TOKEN_SIGN) === false) {
            $this->_apiReturn(40200, '签名错误');
        }
    }

    //每家公司不一致暂时不做适配
    public function create()
    {
        $data = $this->data;
        try{

            $company_id = $data['company_id'];
            if(!$company_id)
            {
                throw new Exception('公司ID错误');
            }

            $requestData = array(
                'class_obj' => 'BackMenu',
                'method' => 'create',
                'company_id' => $company_id,
                'back_url' => ECSTORE_DOMAIN_URL_DYNPTL.'/m/thirdsso-unicom.html',
                'name' => '退出',
                'comment'=>'unicom'
            );

            $requestData['token'] = kernel::single('b2c_safe_apitoken')->generate_token($requestData, OPENAPI_TOKEN_SIGN);

            $curl = new \Neigou\Curl();
            $resultJson = $curl->Post(CLUB_DOMAIN.'/Home/OpenApi/apirun', $requestData);
            $resultJson = json_decode($resultJson, true);
            if($resultJson['Result'] == 'true')
            {
                static :: _apiReturn(0,'创建成功');
            }

            static :: _apiReturn(5000,'创建失败');
        }catch (Exception $e){
            static :: _apiReturn(5000,$e->getMessage());
        }
    }




    /*
     * 接口返回
     *
     * @param   $result     boolean     返回状态
     * @param   $errId      int         错误ID
     * @param   $errMsg     string      错误描述
     * @param   $data       mixed       返回内容
     * @return  string
     */
    private static function _apiReturn($errId = 0, $errMsg = '', $data = null)
    {
        echo json_encode(array('Result' => $errId == 0 ? 'true' : 'false', 'ErrorId' => $errId, 'ErrorMsg' => $errMsg, 'Data' => $data));
        exit;
    }

}