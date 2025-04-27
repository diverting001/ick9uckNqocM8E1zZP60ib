<?php

/**
 * Description of weChatOpenApi
 *
 * @author zhaolong
 */
class weChatOpenApi {

    public function gettokenCommon($dataFrom) {
        $redirect_uri = "";
        $dataRedirect = array();
        if (!empty($dataFrom['data'])) {
            foreach ($dataFrom['data'] as $dk => $dv) {
                $dataRedirect[$dk] = $dv;
            }
        }
        if ($dataFrom['scope'] == 'snsapi_userinfo') {
            $redirect_uri = 'http://' . HOST_REDIRECT . '/plugin/weChatOpenApi.php?act=getopenid&' . http_build_query($dataRedirect);
        } elseif ($dataFrom['scope'] == 'snsapi_base') {
            $redirect_uri = 'http://' . HOST_REDIRECT . '/plugin/weChatOpenApi.php?act=getopenidbase&' . http_build_query($dataRedirect);
        }
        $wxData = array(
            "appid" => $dataFrom['appid'],
            "redirect_uri" => $redirect_uri,
            "response_type" => $dataFrom['response_type'],
            "scope" => $dataFrom['scope'],
            "state" => $dataFrom['state']
        );
        $to_url = WX_TO_CODE . http_build_query($wxData) . '#wechat_redirect';
        header("Location:" . $to_url);
    }

    public function getopenid($dataFrom) {
        $toUrl = $dataFrom['data']['toUrl'];
        $dataRedirect = array();
        if (!empty($dataFrom['data'])) {
            foreach ($dataFrom['data'] as $dk => $dv) {
                $dataRedirect[$dk] = $dv;
            }
            unset($dataRedirect['toUrl']);
        }
        //组装数据
        $data['appid'] = $dataFrom['appid'];
        $data['secret'] = $dataFrom['secret'];
        $data['grant_type'] = 'authorization_code';
        $data['code'] = $_GET['code'] ? trim($_GET['code']) : '';
        $state = $_GET['state'] ? trim($_GET['state']) : '';
        $wxToUrl = WX_TO_OPENID . http_build_query($data);
        $res = $this->httpPost($wxToUrl, $data = '');
        if ($res) {
            $result = json_decode($res, true);
            $accessToken = $result['access_token'];
            $openId = $result['openid'];
            $dataUser['access_token'] = $accessToken;
            $dataUser['openid'] = $openId;
            $dataUser['to_url'] = $wxGetUserToUrl = WX_USER_INFO . http_build_query($dataUser);
            $resUserInfo = $this->httpPost($wxGetUserToUrl, $dataUser);
            $server = defined('REDIS_SERVER_HOST') ? REDIS_SERVER_HOST : "localhost";
            $port = defined('REDIS_SERVER_PORT') ? REDES_SERVER_PORT : 6379;
            $timeout = defined('REDIS_SERVER_TIMEOUT') ? REDIS_SERVER_TIMEOUT : 1;

            $redisClient = new Redis();
            $redisConSta = $redisClient->connect($server, $port, $timeout);
            if ($redisConSta && defined('REDIS_AUTH')) {
                $redisClient->auth(REDIS_AUTH);
            }

            $key = md5("wx-data-" . $openId . "neigou");
            $res = $redisClient->setex($key, 1800, $resUserInfo);
            header("Location:" . $toUrl . "?key=" . $key);
        } else {
            header("Location:" . $toUrl . "?key=");
        }
    }

    public function getopenidbase($dataFrom) {
        //组装url
        $toUrl = $dataFrom['toUrl'];
        $dataRedirect = array();
        if (!empty($dataFrom['data'])) {
            foreach ($dataFrom['data'] as $dk => $dv) {
                $dataRedirect[$dk] = $dv;
            }
            $cookie_redirect = $toUrl . '?' . http_build_query($dataRedirect);
        } else {
            $cookie_redirect = $toUrl;
        }
        //组装数据
        $data['appid'] = $dataFrom['appid'];
        $data['secret'] = $dataFrom['secret'];
        $data['grant_type'] = 'authorization_code';
        $data['code'] = $_GET['code'] ? trim($_GET['code']) : '';
        $state = $_GET['state'] ? trim($_GET['state']) : '';

        //发起请求
        $to_url = WX_TO_OPENID . http_build_query($data);
        //得到值
        $res = $this->httpPost($to_url, $data = '');
        if ($res) {
            $result = json_decode($res, true);
            $accessToken = $result['access_token'];
            $openId = $result['openid'];

            $dataUser['access_token'] = $accessToken;
            $dataUser['openid'] = $openId;
            $dataUser['to_url'] = $to_url = WX_USER_INFO . http_build_query($dataUser);

            $resUserInfo = $this->httpPost($to_url, $dataUser);
            //result 就是得到的信息
            $result = json_decode($resUserInfo, true);

            //入库 然后跳转
            if (!empty($dataFrom['data'])) {
                $redirect_url = $cookie_redirect . '&openid=' . $openId;
            } else {
                $redirect_url = $cookie_redirect . '?openid=' . $openId;
            }

            header("Location:" . $redirect_url);
        } else
            header("Location:" . $cookie_redirect . '?openid=error');
    }

    private function httpPost($http_url, $postdata) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $http_url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Errno' . curl_error($ch);
        }
        curl_close($ch);
        return $result;
    }

}

require dirname(__FILE__) . "/configwx.php";
$root_dir = realpath(dirname(__FILE__) . '/../');
require($root_dir . "/config/config.php");

$wx = new weChatOpenApi();
$dataFrom = array(
    'appid' => WX_APPID,
    'response_type' => RESPONSE_TYPE,
    'secret' => WX_SECRET,
    'scope' => WX_SCOPE, //snsapi_userinfo   snsapi_base
    'state' => "STATE",
);
if (!empty($_REQUEST)) {
    foreach ($_REQUEST as $rek => $rev) {
        if (in_array($rek, array("appid", "secret"))) {
            $dataFrom[$rek] = $rev;
        }
        $dataFrom['data'][$rek] = $rev;
    }
}
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : 'gettokenCommon';
if ($act) {
    if (method_exists($wx, $act)) {
        $wx->$act($dataFrom);
    }
}