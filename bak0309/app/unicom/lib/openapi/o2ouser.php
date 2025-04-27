<?php
/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/26
 * Time: 6:43 PM
 */

class unicom_openapi_o2ouser
{
    public function __construct()
    {
        $this->data = $_POST; //测试用
    }


    public function setUser()
    {
        $data = $this->data;
        try{
            $userModel = app::get('unicom')->model('o2ouser');
            $userInfo = $userModel->getUserInfoRawByMemberId($data['member_id']);
            $newData = array(
                'member_id' => $data['member_id'],
                'userId' => $data['userId'],
                'name' => $data['name'],
                'createId' => $data['createId'],
                'createName' => !empty($data['createName']) ? $data['createName'] : $data['name'],
                'comCode' => $data['comCode'],
                'comName' => $data['comName'],
                'ou' => $data['ou'],
                'ouName' => $data['ouName'],
                'company_value' => $data['company_value'],
                'company_name' => $data['company_name'],
                'extendInfo' => json_encode($data['extendInfo']),
                'wap_extendInfo' => json_encode($data['wap_extendInfo']),
            );

            foreach ($newData as $key => $v){
                if (empty($v)) throw new Exception('参数:'.$key.',不能为空!');
            }
            if (empty($userInfo)) {
                $res = $userModel->addUserInfo($newData);
                if (!$res) throw new Exception('创建用户失败!');
            }else{
                $res = $userModel->updateUserInfo(array('member_id' => $newData['member_id']),$newData);
                if (!$res) throw new Exception('更新用户失败!');
            }
            static :: _apiReturn(0,'保存用户信息成功!');
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