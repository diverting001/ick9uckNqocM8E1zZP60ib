<?php
class weixin_wechat{

    public function __construct($app){
        $this->weixinObject = kernel::single('weixin_object');
        $this->weixinMsg = kernel::single('weixin_message');
    }


    public function checkSignature($signature, $timestamp, $nonce, $token){
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
        if( strtoupper($tmpStr) == strtoupper($signature) ){
            return true;
        }else{
            return false;
        }
    }

    /**
     *  发消息
     */
    public function send_msg($postData,$paramsData){
        $data = array();
        if( !empty($paramsData) ){
            $commData['ToUserName'] = $postData['FromUserName'];
            $commData['FromUserName'] = $postData['ToUserName'];
            $commData['CreateTime'] = time();
            $data = array_merge($commData,$paramsData);
        }
        $this->weixinObject->send($data);
    }

    /**
     * 订阅事件处理
     **/
    public function subscribe($postData) {
        if (empty($postData['FromUserName']) || empty($postData['eid'])) {
            return false;
        }
        $wxUid = $postData['FromUserName'];
        $bindId = $this->weixinObject->get_bind_id($postData['eid']);
        $userInfo = $this->userInfo($wxUid, $bindId);
        if (empty($userInfo['openid']) || empty($userInfo['unionid'])) {
            return false;
        }
        $this->saveOpenIdInfo($userInfo['openid'], $userInfo['unionid'], 1);

        \Neigou\Logger::General("promotion.action.bind_member.subscribe", array("action" => "subscribe_data", "data" => json_encode($postData)));
        //自动绑定
        if (isset($postData['EventKey']) && $postData['EventKey']) {
            $memberId = explode('_', $postData['EventKey']);
            $memberId = $memberId[1];
            \Neigou\Logger::General("promotion.action.bind_member.subscribe", array("action" => "subscribe", "data" => json_encode($userInfo), 'member_id' => $memberId));
            if ($userInfo['subscribe'] == 1 && $memberId && is_numeric($memberId)) {
                $openid = $userInfo['openid'];
                $unionid = $userInfo['unionid'];
                $data = array('wxopenid' => $openid, 'wxunionid' => $unionid);
                $this->weixinObject->bind_member($memberId, $data);
            }
        }

        //发送关注自动回复
        $messageData = app::get('weixin')->model('message')->getList('message_id,message_type', array('bind_id' => $postData['bind_id'], 'reply_type' => 'attention'));
        $paramsData = $this->get_message($messageData[0]['message_id'], $messageData[0]['message_type'], $postData);
        $this->send_msg($postData, $paramsData);
    }

    /**
     * 取消订阅事件处理
     * */
    public function unsubscribe($postData) {
        if (empty($postData['FromUserName']) || empty($postData['eid'])) {
            return false;
        }
        $wxUid = $postData['FromUserName'];
        $bindId = $this->weixinObject->get_bind_id($postData['eid']);
        $userInfo = $this->userInfo($wxUid, $bindId);
        if (empty($userInfo['openid']) || empty($userInfo['unionid'])) {
            return false;
        }
        $this->saveOpenIdInfo($userInfo['openid'], $userInfo['unionid'], 0);
    }

    /**
     * 添加／更新 微信openID 绑定状态
     *
     * @param type $openId
     * @param type $unionid
     * @param type $status
     */
    public function saveOpenIdInfo($openId, $unionid, $status) {
        $openIdMdl = app::get("weixin")->model("openid");
        $filter = array(
            "wxopenid" => $openId,
            "wxunionid" => $unionid
        );
        $openIdInfo = $openIdMdl->dump($filter);
        if ($openIdInfo) {
            $openIdInfo['status'] = $status;
            $openIdInfo['update_time'] = time();
            $openIdMdl->save($openIdInfo);
        } else {
            $newOpenIdInfo = array(
                "wxopenid" => $openId,
                "wxunionid" => $unionid,
                "status" => $status,
                "create_time" => time(),
                "update_time" => time()
            );
            $openIdMdl->insert($newOpenIdInfo);
        }
    }

    /**
     * 已关注用户的扫码事件
     **/
    public function scan($postData){
        \Neigou\Logger::General("promotion.action.bind_member.scan", array("action"=>"scan_data", "data"=>json_encode($postData)));
        $_object = kernel::single('weixin_object');
        if(isset($postData['EventKey']) && $postData['EventKey']){
            $memberId = $postData['EventKey'];
            //确定该用户是否已经绑定过微信
            $_member = app::get('b2c') -> model('members');
            $member_info = $_member -> getRow('*',array('member_id' => $memberId));
            if(!$member_info){
                $_object -> send('');
                exit;
            }
            if(!$member_info['wxopenid'] || !$member_info['wxunionid']){
                $wxUid = $postData['FromUserName'];
                $bindId = $this -> weixinObject -> get_bind_id($postData['eid']);
                $userInfo = $this -> userInfo($wxUid,$bindId);
                if($userInfo['subscribe'] == 1 && $memberId){
                    $openid = $userInfo['openid'];
                    $unionid = $userInfo['unionid'];
                    $data = array('wxopenid' => $openid,'wxunionid' => $unionid);
                    $this -> weixinObject -> bind_member($memberId,$data);
                }
            }
        }
        $_object -> send('');
        exit;
    }

    /**
     *  获取用户信息
     */
    public function userInfo($openid,$bindId){
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=%s&openid=%s&lang=zh_CN";
        $token = $this -> get_basic_accesstoken($bindId);
        $url = sprintf($url,$token,$openid);
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->set_timeout(6)->get($url);
        $result = json_decode($response, true);
        return $result;
    }

    /**
     * 自定义菜单消息回复
     */
    public function click($postData){
        $EventKey = explode('_',$postData['EventKey']);
        $paramsData = $this->get_message($EventKey[1], $EventKey[0], $postData);
        $this->send_msg($postData,$paramsData);
    }

    /**
     * 普通消息公共调用
     * MsgType ： text image voice video 等
     */
    public function commonMsg($postData){

        $msgData = array();
        if( $postData['MsgType'] == 'text' && !empty($postData['Content']) ){
            $msgData = app::get('weixin')->model('message')->getList('message_id,message_type',array('bind_id'=>$postData['bind_id'],'keywords'=>$postData['Content']));
        }
        if( empty($msgData) ){
            $msgData = app::get('weixin')->model('message')->getList('message_id,message_type',array('bind_id'=>$postData['bind_id'],'reply_type'=>'message'));
        }
        $paramsData = $this->get_message($msgData[0]['message_id'], $msgData[0]['message_type'], $postData);
        $this->send_msg($postData,$paramsData);
    }

    /**
     * 获取回复消息 文字|图文
     *
     * @params $msg_id   int    消息ID
     * @params $msg_type string 消息内容
     * @params $postData array  微信POST数据
     */
    public function get_message($msg_id, $msg_type, $postData){
        $urlParams = $this->weixinObject->set_wechat_sign($postData);
        if( $msg_type == 'text' ){
            $messageData = app::get('weixin')->model('message_text')->getList('is_check_bind',array('id'=>$msg_id) );
        }else{
            $messageData = app::get('weixin')->model('message_image')->getList('is_check_bind',array('id'=>$msg_id));
        }
        //表以及module不存在暂时注释掉 todo.zfc
        // $shopBindWeixin = app::get('pam')->model('bind_tag')->getList('id',array('open_id'=>$postData['FromUserName']));
        $shopBindWeixin = 0;
        if( $messageData[0]['is_check_bind'] == 'true' && empty($shopBindWeixin) ){
            $content = app::get('weixin')->getConf('weixin_sso_setting');
            $arrUrl = preg_match_all("/href[\s]*?=[\s]*?[\'|\"](.+?)[\'|\"]/",$content['noBindText'],$match);
            foreach((array)$match[1] as $url){
                if( stristr($url, '?' ) ){
                    $tmp_url = $url.'&'.$urlParams;
                }else{
                    $tmp_url = $url.'?'.$urlParams;
                }
                $content = str_replace( $url, $tmp_url, $content['noBindText']);
            }
            $paramsData['Content'] = $content;
            $paramsData['MsgType'] = 'text';
        }else{
            $paramsData = $this->weixinMsg->get_message($msg_id, $msg_type, $urlParams);
        }
        return $paramsData;
    }

    // 获取微信基础信息ACCESS_TOKEN
    public function get_basic_accesstoken($bind_id){
        $_redis = kernel::single('base_sharedkvstore');
        $access_token = '';
        $_redis -> fetch('wx_access_token',$bind_id,$access_token);
        if($access_token){
            \Neigou\Logger::Debug("weixin.get_basic_accesstoken", array("action"=>"weixin_basic_accesstoken", "success"=>1, "reason"=>"from_redis", "data" => $bind_id,"access_token" => $access_token));
            return $access_token;
        }else{
            $bindinfo = app::get('weixin')->model('bind')->getRow('appid, appsecret, email',array('id'=>$bind_id));
            if( $bindinfo['appid'] && $bindinfo['appsecret']){
                $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$bindinfo['appid']}&secret={$bindinfo['appsecret']}";
                $httpclient = kernel::single('base_httpclient');
                $response = $httpclient->set_timeout(6)->get($url);
                \Neigou\Logger::Debug("weixin.get_basic_accesstoken", array("action"=>"weixingettoken", "data"=>$response));
                $result = json_decode($response, true);

                if(!empty($result['access_token'])){
                    \Neigou\Logger::Debug("weixin.get_basic_accesstoken", array("action"=>"weixin_basic_accesstoken", "data"=>$result['access_token']));
                    $_redis -> store('wx_access_token',$bind_id,$result['access_token'],30);
                    return $result['access_token'];
                }else{
                    \Neigou\Logger::Debug("weixin.get_basic_accesstoken", array("action"=>"weixin_basic_accesstoken", "success"=>0, "reason"=>"bindinfofail", "data"=>$result, "sparam1"=>$bindinfo));
                    return false;
                }
            }else{
                \Neigou\Logger::Debug("weixin.get_basic_accesstoken", array("action"=>"weixin_basic_accesstoken", "success"=>0, "reason"=>"kvstorefail", "data"=>$bind_id));
                return false;
            }
        }
    }

    // 获取微信OAUTH2的ACCESS_TOKEN
    public function get_oauth2_accesstoken($bind_id, $code, &$result){
        // if( base_kvstore::instance('weixin')->fetch('oauth2_accesstoken_'.$bind_id.'_'.$result['openid'], $oauth2_access_token) !== false ){
        //     return $oauth2_access_token;
        // }else{
            $bindinfo = app::get('weixin')->model('bind')->getRow('appid, appsecret, email',array('id'=>$bind_id));
            if( $bindinfo['appid'] && $bindinfo['appsecret']){
                $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$bindinfo['appid']}&secret={$bindinfo['appsecret']}&code={$code}&grant_type=authorization_code";
                $httpclient = kernel::single('base_httpclient');
                $response = $httpclient->set_timeout(6)->get($url);
                $result = json_decode($response, true);
                if( $result['errcode']==0 ){
                    // if( !base_kvstore::instance('weixin')->store('oauth2_accesstoken_'.$bind_id.'_'.$result['openid'], $result['access_token'], $result['expires_in']) ){ // 微信ACCESS_TOKEN的有效期,单位为秒
                    //     logger::info("KVSTORE写入公众账号登录邮箱为 {$bindinfo['email']} 的OAUTH2的ACCESS_TOKEN错误");
                    // }
            logger::info('远程获取OAUTH2_ACCESS_TOKEN'.$result['access_token']);
                    return $result['access_token'];
                }else{
                    logger::info("获取公众账号登录邮箱为 {$bindinfo['email']} 的OAUTH2认证的ACCESS_TOKEN错误,微信返回的错误码为 {$result['errcode']}");
                    return false;
                }
            }else{
                logger::info("没有取到公众账号ID为 {$bind_id} 的 appid 或者 appsecret 的信息");
                return false;
            }
        // }
    }

    // 发送微信自定义菜单
    public function createMenu($bind_id, $menu_data, &$msg){
        if(!$access_token = $this->get_basic_accesstoken($bind_id)){
            return false;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$access_token}";
        $data = json_encode ( $menu_data );
        // 由于微信不直接认json_encode处理过的带中文数据的信息，这里做个转换
        $post_menu = preg_replace ( "/\\\u([0-9a-f]{4})/ie", "iconv('UCS-2BE', 'UTF-8', pack('H*', '$1'));", $data );
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->set_timeout(6)->post($url, $post_menu);
        $result = json_decode($response, true);
        if( $result['errcode']==0 ){
            logger::info('更新微信菜单数据成功:'.print_r($data,1));
            return true;
        }else{
            $msg = "创建微信自定义菜单错误,微信返回的错误码为 {$result['errcode']}";
            logger::info($msg);
            return false;
        }
    }

    // 非OAUTH2网页授权方式获取用户基本信息
    public function get_basic_userinfo($bind_id, $openid){
        if(!$access_token = $this->get_basic_accesstoken($bind_id)){
            return false;
        }
        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$access_token}&openid={$openid}&lang=zh_CN";
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->set_timeout(6)->get($url, $post_menu);
        $result = json_decode($response, true);
        if( $result['errcode']==0 ){
            return $result;
        }else{
            $msg = "微信基本获取用户信息错误(非OAUTH2方式),微信返回的错误码为 {$result['errcode']}";
            logger::info($msg);
            return false;
        }
    }

    // 生成微信需授权页面链接
    public function gen_auth_link($appid, $eid, $redirect_uri){
        $url = sprintf('https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=%s#wechat_redirect',$appid,$redirect_uri,$eid);
        return $url;
    }

    // 获取微信分享自定义信息
    public function get_weixin_share_page(){
        return app::get('weixin')->getConf('weixin_basic_setting.share_page');
    }

    // 随机获取一个可以创建自定义菜单的微信的APPID
    public function get_a_appid(){
        $bindinfo = app::get('weixin')->model('bind')->getRow('appid',array('appid|noequal'=>''));
        return $bindinfo['appid'];
    }

    /**
     * 接受维权通知到微信
     */
    public function updatefeedback($bind_id, $openid, $feedbackid){
        $access_token = $this->get_basic_accesstoken($bind_id);
        $url = "https://api.weixin.qq.com/payfeedback/update?access_token={$access_token}&openid={$openid}&feedbackid={$feedbackid}";
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->set_timeout(6)->get($url);
        $result = json_decode($response, true);
        if( $result['errcode'] == 0){
            return true;
        } else {
            return $result;
        }
    }

    //发货通知微信
    public function delivernotify($postData){
        $payData = app::get('ectools')->getConf('weixin_payment_plugin_wxpay');
        $payData = unserialize($payData);
        $postData['appid'] = trim($payData['setting']['appId']);
        $bindData = app::get('weixin')->model('bind')->getRow('id',array('appid'=>$postData['appid']));
        $access_token = $this->get_basic_accesstoken($bindData['id']);
        $url = "https://api.weixin.qq.com/pay/delivernotify?access_token={$access_token}";

        $paySignKey = trim($payData['setting']['paySignKey']); // 财付通商户权限密钥 Key
        $sign = weixin_commonUtil::sign_sha1($postData,weixin_commonUtil::trimString($paySignKey));

        $postData['app_signature'] = $sign;
        $postData['sign_method'] = 'sha1';
        $httpclient = kernel::single('base_httpclient');
        $postData = json_encode($postData);
        $response = $httpclient->set_timeout(6)->post($url,$postData);
        $result = json_decode($response, true);
        if( $result['errcode'] == 0){
            return true;
        } else {
            $msg = "发货通知到微信,微信返回的错误码为 {$result['errcode']}\n,错误信息：{$result['errmsg']}";
            logger::info($msg);
            return false;
        }
    }

    // 判断是否来自微信浏览器
    function from_weixin() {
        return kernel::single('base_component_request') -> is_browser_tag('weixin');
    }


    public function wx_get_ticket_data($appid){
        $bindinfo = app::get('weixin')->model('bind')->getRow('appid, appsecret, id',array('appid'=>$appid));
        if( empty($bindinfo['appid']) || empty($bindinfo['appsecret'])) {
            return false;
        }

        $bind_id = $bindinfo['id'];
        $token = $this->get_basic_accesstoken($bind_id);

        $url2 = sprintf("https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=%s&type=jsapi",$token);
        $res = file_get_contents($url2);
        $res = json_decode($res, true);
        $wxticket = $res['ticket'];
        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

        $scheme = kernel::single('base_component_request')->get_server('HTTPS')=='on'?'HTTPS':'HTTP';
        $scheme = strtolower($scheme);
        $url_this = $scheme.'://'.$host.$_SERVER["REQUEST_URI"];

        $timestamp = time();
        $wxnonceStr = "Wm3WZYTPz0wzccnW";
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s",$wxticket, $wxnonceStr, $timestamp, $url_this);
        $wxSha1 = sha1($wxOri);
        return array('time'=>$timestamp, 'nonceStr'=>$wxnonceStr, 'signature'=>$wxSha1);
    }
}
