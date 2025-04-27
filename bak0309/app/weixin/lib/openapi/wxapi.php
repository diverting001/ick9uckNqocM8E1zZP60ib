<?php
/**
 * 用户信息处理
 * @auther shopex ecstore dev dev@shopex.cn
 * @version 0.1
 * @package ectools.lib.api
 */


class weixin_openapi_wxapi{
    private $app;
    const SIGN = '1a445e15c32bb407ab725e517840f5b91';

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * 获取微信token接口
     */

    public function weixintoken(){

        $params =$_REQUEST;
        $retData = array();

        //logger::logtestkv("openapi.member.user_info", array("params"=>json_encode($params)));
        if((!isset($params['appid']))){
            $retData = array('result' => 'error', 'data' => array('code' => 402, 'data' => 'appid not request'));
            echo  json_encode($retData);
            return;
        }

        if(!kernel::single('b2c_safe_apitoken')->check_token($params, OPENAPI_TOKEN_SIGN)){
            $data = array('result' => 'error', 'data' => array('code' => 403, 'data' => 'TOKEN error'));
            echo json_encode($data);
            exit;
        }

        $appid = $params['appid'];
        $bindinfo = app::get('weixin')->model('bind')->getRow('appid, appsecret, id',array('appid'=>$appid));
        if( $bindinfo['appid'] && $bindinfo['appsecret']) {

        }else{
            $retData = array('result' => 'error', 'data' => array('code' => 401, 'data' => 'get data error'));
            echo json_encode($retData);
            return ;
        }

        $bind_id = $bindinfo['id'];
        $wechat = kernel::single('weixin_wechat');
        $token = $wechat->get_basic_accesstoken($bind_id);


        if(!$token){
            $retData = array('result' => 'error', 'data' => array('code' => 401, 'data' => 'get weixintoken error'));
            echo json_encode($retData);
            return ;
        }else{
            $retData['token'] = $token;
            $retData = array('result' => 'succ', 'data' => array('code' => 200, 'data' => $retData));
            echo json_encode($retData);
            return ;
        }
    }
    
    /**
     * 
     * @return is_subscribe 1-关注,0-未关注
     */
    public function isSubscribeWx(){
        $params =$_REQUEST;
        
        if(!isset($params['open_id']) || empty($params['open_id'])){
          echo  json_encode(array('result' => 'false','code'=>501,'msg'=>'openid not request ', 'data' => array()));
          return;
        }
        
        if(!kernel::single('b2c_safe_apitoken')->check_token($params, OPENAPI_TOKEN_SIGN)){
           echo  json_encode(array('result' => 'false','code'=>502,'msg'=>'token error ', 'data' => array()));
          return;
        }
        
        $res = app::get('weixin')->model('openid')->getRow('status',array('wxopenid'=>$params['open_id']));
        
        if(empty($res)){
           echo  json_encode(array('result' => 'true','code'=>0,'msg'=>'success', 'data' => array('is_subscribe'=>0)));
           return;
        }
        
        $status = ($res['status'] == 0)?0:1;
        echo  json_encode(array('result' => 'true','code'=>0,'msg'=>'success', 'data' => array('is_subscribe'=>$status)));
    }
}