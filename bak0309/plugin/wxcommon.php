<?php
require dirname(__FILE__)."/configwx.php";
class wxcommon {

    function __construct(){

    }
    /**
     * @author	bin
     * @desc
     */
    function gettoken($key,$view){

        $host = HOST_REDIRECT;
        $data['appid'] = WX_APPID;
        $data['redirect_uri'] =  'http://' . $host . '/plugin/wxcommon.php?act=getopenid&key='.$key.'&view='.$view;
        $data['response_type'] = RESPONSE_TYPE;
        $data['scope'] = WX_SCOPE;
        $data['state'] = 123;
        //$data['to_url'] = $to_url = WX_TO_CODE . http_build_query($data) . '#wechat_redirect';
        $to_url = WX_TO_CODE . http_build_query($data) . '#wechat_redirect';

        header("Location:". $to_url);
    }
    /**
     * @author	bin
     * @desc
     */
    function getopenid($key,$view){

        $cookie_redirect = HOST_REDIRECT_TO.'?key='.$key.'&view='.$view;
        $data['appid'] = WX_APPID;
        $data['secret'] = WX_SECRET;
        $data['grant_type'] = 'authorization_code';
        $data['code'] = $_GET['code'] ? trim($_GET['code']) : '';
        $data['to_url'] = $to_url = WX_TO_OPENID . http_build_query($data);
        $state = $_GET['state'] ? trim($_GET['state']) : '';

        $res = $this->actionPost($to_url, $data='');
        if(!$res){
            //给出显示的跳转页
            $redirect = HOST_REDIRECT.'/plugin/wxhongbao.php?key='.$key.'&view=1';
            $redirect = base64_encode($redirect);
            $wxopenid="netErr";
            header("Location:". $cookie_redirect.'&redirect='.$redirect.'&wxopenid='.$wxopenid);
            return;
        }

        $result = json_decode($res, true);

        $accessToken = !empty($result['access_token'])?$result['access_token']:'';
        if($accessToken && !empty($result['openid'])) {
            //$result = json_decode($res, true);
            //$accessToken = $result['access_token'];
            $openId = $result['openid'];

            $datauser['access_token'] = $accessToken;
            $datauser['openid'] = $openId;
            $datauser['to_url'] = $to_url = WX_USER_INFO . http_build_query($datauser);

            $resuserinfo = $this->actionPost($to_url, $datauser);

            $weiXinData = json_decode($resuserinfo,true);
            $weiXinDatato['nickname'] = $this->emojiFilter($weiXinData['nickname']);
            $weiXinDatato['openid'] = 	$weiXinData['openid'];
            $weiXinDatato['sex'] =  !empty($weiXinData['sex'])?$weiXinData['sex']:"";
            $weiXinDatato['language'] = !empty($weiXinData['language'])?$weiXinData['language']:"";
            $weiXinDatato['city'] = !empty($weiXinData['city'])?$weiXinData['city']:"";
            $weiXinDatato['province'] = !empty($weiXinData['province'])?$weiXinData['province']:"";
            $weiXinDatato['country'] = !empty($weiXinData['country'])?$weiXinData['country']:"";
            $weiXinDatato['headimgurl'] = !empty($weiXinData['headimgurl'])?$weiXinData['headimgurl']:"";
            $weiXinDatato['unionid'] = !empty($weiXinData['unionid'])?$weiXinData['unionid']:"";

            $weiXinDatatoJson = json_encode($weiXinDatato);
            $resuserinfo = base64_encode($weiXinDatatoJson);

            $redirect_url =  $cookie_redirect."&data=".$resuserinfo.'&wxopenid='.$openId;
            header("Location:". $redirect_url);
        }
        else {
            //给出显示的跳转页
            $redirect = HOST_REDIRECT.'/plugin/wxhongbao.php?key='.$key.'&view=1';
            $redirect = base64_encode($redirect);
            $wxopenid="noAuth";
            header("Location:". $cookie_redirect.'&redirect='.$redirect.'&wxopenid='.$wxopenid);
        }
    }

    public function select($sql,$conn)
    {
        $data = array();
        $res=mysql_query($sql,$conn);
        if ($res) {
            while ($row=mysql_fetch_assoc($res)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    //匹配emoji表情
    function emojiFilter($text){
        $text = json_encode($text);
        preg_match_all("/(\\\\ud83c\\\\u[0-9a-f]{4})|(\\\\ud83d\\\u[0-9a-f]{4})|(\\\\u[0-9a-f]{4})/", $text, $matchs);
        if(!isset($matchs[0][0])) { return json_decode($text, true); }

        $emoji = $matchs[0];
        foreach($emoji as $ec) {
            $hex = substr($ec, -4);
            if(strlen($ec)==6) {
                if($hex>='2600' and $hex<='27ff') {
                    $text = str_replace($ec, '*', $text);
                }
            } else {
                if($hex>='dc00' and $hex<='dfff') {
                    $text = str_replace($ec, '*', $text);
                }
            }
        }

        return json_decode($text, true);
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
$wx = new wxcommon();
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
