<?php
/**
 * @author shihuiqian<shihuiqian@neigou.com>
 * @date 2019-02-26
 * @description OpenAPI自动登录
 */

use Neigou\ApiClient;

define('ROOT_DIR', realpath(dirname(__FILE__)) . '/');

require_once(ROOT_DIR . 'config/config.php');
require_once(ROOT_DIR . 'config/neigou_config.php');
require_once(ROOT_DIR . 'app/b2c/lib/base.php');
require_once(ROOT_DIR . 'app/b2c/lib/cas/client.php');
require_once(ROOT_DIR . 'app/getstock/cronjob/config.php');

class MySQL
{
    /**
     * 已连接实例
     *
     * @var array
     */
    protected $link = array();

    /**
     * 数据库配置
     *
     * @var array
     */
    protected $config = array(
        'store' => array(
            'host' => DB_HOST,
            'user' => DB_USER,
            'pass' => DB_PASSWORD,
            'name' => DB_NAME
        ),
        'club' => array(
            'host' => CLUB_DB_MASTER_HOST,
            'user' => CLUB_DB_MASTER_USER,
            'pass' => CLUB_DB_MASTER_PASSWORD,
            'name' => CLUB_DB_MASTER_NAME
        )
    );

    /**
     * 连接数据库
     *
     * @param $db
     * @return mixed
     */
    protected function link($db)
    {
        if (!isset($this->link[$db]) && isset($this->config[$db])) {
            $config = $this->config[$db];
            $this->link[$db] = mysql_connect($config['host'], $config['user'], $config['pass']);
            mysql_query("set names utf8", $this->link[$db]);
            mysql_selectdb($config['name'], $this->link[$db]);
        }

        return $this->link[$db];
    }

    /**
     * SQL查询
     *
     * @param $sql
     * @param string $db
     * @return array|bool
     */
    public function query($sql, $db = 'store')
    {
        $link = $this->link($db);
        if ($link) {
            $res = mysql_query($sql, $link);
            if ($res) {
                $data = array();
                while ($row = mysql_fetch_assoc($res)) {
                    $data[] = $row;
                }
                return $data;
            }
        }
        return false;
    }

    /**
     * 查询1条数据
     *
     * @param $sql
     * @param string $db
     * @return bool|mixed|null
     */
    public function queryOne($sql, $db = 'store')
    {
        $data = $this->query($sql, $db);
        if (is_array($data)) {
            return isset($data[0]) ? $data[0] : null;
        }

        return false;
    }

    /**
     * 查询一个字段
     *
     * @param $sql
     * @param $field
     * @param string $db
     * @return bool|mixed|null
     */
    public function queryFiled($sql, $field, $db = 'store')
    {
        $data = $this->queryOne($sql, $db);
        if ($data !== false) {
            return is_array($data) ? $data[$field] : null;
        }

        return false;
    }

    /**
     * 根据公司ID查询gcorpID
     *
     * @param $companyId
     * @return bool|mixed|null
     */
    public function getCompanyCorpId($companyId)
    {
        return $this->queryFiled(
            'SELECT gcorp_id FROM club_cas_company WHERE company_id = ' . (int)$companyId . ' LIMIT 1',
            'gcorp_id',
            'club'
        );
    }

    /**
     * 获取
     *
     * @param $companyId
     * @return bool|mixed|null|string
     */
    public function getCompanyChannel($companyId)
    {
        $channel = $this->queryFiled(
            'SELECT channel FROM club_third_company WHERE internal_id = ' . (int)$companyId . ' AND channel !="aiguanhuai" LIMIT 1',
            'channel',
            'club');

        return $channel ? $channel : 'aiguanhuai';
    }

    /**
     * 获取当前可用账号信息
     *
     * @param $memberId
     * @return bool|mixed|null
     */
    public function getMemberInfo($memberId)
    {
        $guid = $this->queryFiled('SELECT guid FROM sdb_b2c_cas_members WHERE member_id=' . (int)$memberId . ' LIMIT 1', 'guid');

        return $this->queryOne('SELECT m.member_id,m.company_id FROM sdb_b2c_cas_members AS c LEFT JOIN sdb_b2c_members AS m ON m.member_id = c.member_id WHERE c.guid = ' . (int)$guid . ' AND m.status="active" LIMIT 1');
    }
}


class AutoLogin
{

    protected $originSession;
    protected $sessionKey;
    protected $session = array();
    protected $redis;
    protected $mysql;

    /**
     * AutoLogin constructor.
     *
     */
    public function __construct()
    {
        $this->mysql = new MySQL();
        $this->redis = new \Redis();

        $server = defined('REDIS_SERVER_HOST') ? REDIS_SERVER_HOST : 'localhost';
        $port = defined('REDIS_SERVER_PORT') ? REDIS_SERVER_PORT : 6379;
        $this->redis->connect($server, $port);

        if (defined('REDIS_AUTH')) {
            $this->redis->auth(REDIS_AUTH);
        }

        $this->sessionStart();
    }

    /**
     * 执行自动登录
     */
    public function autoLogin()
    {
        if (!isset($_GET['loginToken'])) {
            $this->showError('参数错误', 'ERR_INVALID_TOKEN');
        }

        $isVersionV3 = isset($_GET['version']) && $_GET['version'] === 'v3';

        if ($isVersionV3) {
            $loginInfo = $this->getLoginInfoV3($_GET['loginToken']);
        } else {
            $key = $this->getTokenRedisKey($_GET['loginToken']);
            $loginInfo = $this->redisFetch($key);
        }

        if (!($loginInfo && $loginInfo['internal_user_id'])) {
            $this->showError('登录信息不存在', 'ERR_INVALID_TOKEN');
        }

        $memberInfo = $this->mysql->getMemberInfo($loginInfo['internal_user_id']);

        if (!($memberInfo && $memberInfo['member_id'])) {
            $this->showError('账号不存在', 'ERR_INVALID_TOKEN');
        }

        \Neigou\Logger::General('autoLoginToken', array(
            'action' => 'autoLogin',
            'loginInfo' => $loginInfo,
            'memberInfo' => $memberInfo,
            'session' => $this->session,
            'cookie' => $_COOKIE
        ));

        $successUrl = $_GET['surl'] ? ($_GET['base64'] ? base64_decode($_GET['surl']) : $_GET['surl']) : ECSTORE_DOMAIN_URL_DYNPTL;
        $security = new base_security();
        $successUrl = $security->remove_xss_new(strip_tags($successUrl));

        $login_url_patt = defined("PSR_LOGIN_URL_PATT") ? PSR_LOGIN_URL_PATT : "" ;
        if(!empty($login_url_patt) && strtolower(substr($successUrl ,0,4)) == "http") {
            $url_info = parse_url($successUrl);
            if(!preg_match($login_url_patt ,$url_info['host'])) {
                $this->showError('跳转地址不合法', 'ERR_INVALID_TOKEN');
            }
        }

        $forceLogin = false;

        if ($oldMemberId = $this->session['account']['member']) {

            $oldCompanyId = $this->session['CUR_COMPANY_ID'] ? $this->session['CUR_COMPANY_ID'] : $memberInfo['company_id'];

            // 是否需要切换公司
            $switchCompany = $loginInfo['internal_company_id'] && $loginInfo['internal_company_id'] != $oldCompanyId;

            if ($_GET['force_switch']) {
                //清除老用户session，强制切换用户触发被动登录
                b2c_member_conversation::close($oldMemberId);
            } elseif (!$switchCompany && $oldMemberId == $memberInfo['member_id']) {
                // 账号和公司都一样，直接跳走
                $this->redirect($successUrl);
            } elseif (!$_GET['force'] && $oldMemberId != $memberInfo['member_id']) {
                // 已登录其他账号，但是不能强制登录
                $this->showError('您已登录其他账号');
            }

            // 已有其他账号登录时，再次登录新账号，需要通知其他平台登录
            $forceLogin = true;
        }

        // 还未登录或登录的不是指定公司
        $gcorpId = null;
        if ($loginInfo['internal_company_id']) {
            $gcorpId = $this->mysql->getCompanyCorpId($loginInfo['internal_company_id']);
        }

        // 从CAS获取tempAccessToken
        $res = b2c_cas_client::getTempAccessToken($memberInfo['member_id'], $gcorpId);

        if (!$res['success']) {

            $logContext = array(
                'action' => 'autologin.failed',
                'sparam1' => $memberInfo['member_id'],
                'sparam2' => $loginInfo['internal_company_id'],
                'login_data' => $loginInfo,
                'session' => $this->session,
                'response' => $res
            );
            $message = $res['message'];

            switch ($res['code']) {
                // 公司被禁用或员工已离职
                case b2c_cas_client::AUTH_COMPANY_UNAVAILABLE:
                    // 账号被禁用
                case b2c_cas_client::AUTH_ACCOUNT_DISABLED:
                    \Neigou\Logger::General('member.login', $logContext);
                    break;
                case b2c_cas_client::MEMBER_NOT_EXISTS:
                    if ($isVersionV3) {
                        $message = '此账号暂时不支持登录';
                        break;
                    }
                default:
                    // 其他错误，会触发告警
                    $logContext['action'] = 'autologin.error';
                    $message = '自动登录失败';
                    \Neigou\Logger::General('member.login', $logContext);
                    break;
            }
            $this->showError($message ? $message : '自动登录失败', $res['code']);
            return;
        }

        if (!$tempAccessToken = $res['data']['tempAccessToken']) {
            $this->showError('自动登录失败');
        }

        if ($loginInfo['internal_company_id']) {
            $this->session['LOGIN']['COMPANY']['UID'] = $loginInfo['internal_user_id'];
            $this->session['LOGIN']['COMPANY']['COMPANY_ID'] = $loginInfo['internal_company_id'];
            $this->session['LOGIN']['COMPANY']['FORCE'] = $loginInfo['force_switch_company'];
            //公司channel
            $isSpecialCompany = in_array($loginInfo['internal_company_id'], explode(',', NEIGOU_SPECIALL_COMPANY_IDS));
            $channel = $this->mysql->getCompanyChannel($loginInfo['internal_company_id']);
            $isSpecialChannel = in_array($channel, explode(',', NEIGOU_SPECIALL_CHANNEL_BN));
            $this->session['is_baidu'] = ($isSpecialCompany || $isSpecialChannel) ? 'true' : 'false';
        }

        //返回渠道
        $channelBack = json_decode(NEIGOU_CHANNEL_BACK, true);
        if (isset($chnnelBack[$loginInfo['source_channel']])) {
            foreach ($channelBack[$loginInfo['source_channel']] as $channelKey => $channelVal) {
                $this->session['channel_back'][$channelKey] = $channelVal;
            }
        } else {
            $this->session['channel_back'] = '';
        }

        $this->session['LOGIN']['CHANNEL'] = $loginInfo['source_channel'];
        $this->session['LOGIN']['GRANT_TYPE'] = $loginInfo['login_type'];
        $failedUrl = $_GET['furl'] ? (isset($_GET['base64']) ? base64_decode($_GET['furl']) : $_GET['furl']) : '';
        $security = new base_security();
        $failedUrl = $security->remove_xss_new(strip_tags($failedUrl));
        if(!empty($login_url_patt) && strtolower(substr($failedUrl ,0,4)) == "http") {
            $url_info = parse_url($failedUrl);
            if(!preg_match($login_url_patt ,$url_info['host'])) {
                $this->showError('跳转地址不合法', 'ERR_INVALID_TOKEN');
            }
        }

        $this->session['callback'] = str_replace('NG_ERRCODE', urlencode('ERR_TOKEN_LOGIN'), $failedUrl);

        $companyConfig = app::get('b2c') -> model('club_company')->getCompanyConfig($loginInfo['internal_company_id']);
        $defaultLoginImg = '/app/b2c/neigou_statics/mobile/images/new_loading.gif';
        $defaultLoginMessage = '正在登录...';
        $defaultLoginSuccessMessage = '登录成功，正在加载...';
        $this->showPage(array(
            'status' => true,
            'token' => $tempAccessToken,
            'successUrl' => $successUrl,
            'forceLogin' => $forceLogin,
            'login_display_image' => $companyConfig['login_display_image'] ? $companyConfig['login_display_image'] : $defaultLoginImg,
            'login_display_message' => $companyConfig['login_display_message'] ? $companyConfig['login_display_message'] : $defaultLoginMessage,
            'login_display_success_message' => $companyConfig['login_display_message'] ? $companyConfig['login_display_message'] : $defaultLoginSuccessMessage,
        ));
    }

    /**
     * 启动session
     */
    protected function sessionStart()
    {
        $sessionName = defined('SESS_NAME') ? SESS_NAME : 's_v2';
        $sessionKey = $_GET['sess_id'] ? $_GET['sess_id'] : $_COOKIE[$sessionName];

        if (!$sessionKey) {
            $sessionKey = md5(microtime() . $_SERVER['REMOTE_ADDR'] . mt_rand(10000, 99999));
            $cookieExpires = defined('ECSTORE_SESSION_COOKIE_EXPIRES_SEC') ? ECSTORE_SESSION_COOKIE_EXPIRES_SEC : 60 * 60 * 24 * 30;
            $cookieDomain = defined('SESSION_COOKIE_DOMAIN') ? SESSION_COOKIE_DOMAIN : '';
            setcookie($sessionName, $sessionKey, time() + $cookieExpires, '/', $cookieDomain);
        }

        if (strlen($sessionKey) === 32) {
            $prefix = defined('REDIS_SERVER_PREFIX') ? REDIS_SERVER_PREFIX : '';

            $this->sessionKey = $prefix . md5('defaultsessions' . $sessionKey);
            $this->originSession = $this->redisFetch($this->sessionKey);
            $this->session = $this->originSession['value'] ? $this->originSession['value'] : array();

            register_shutdown_function(array($this, 'sessionClose'));
        }
    }

    /**
     * 存储session
     *
     * @return bool
     */
    protected function sessionClose()
    {
        if (!$this->sessionKey) {
            return false;
        }
        $data = $this->originSession;
        $data['value'] = $this->session;
        $data['dateline'] = time();

        $ttl = $this->redis->ttl($this->sessionKey);

        $ttl = empty($ttl) ? 1 : $ttl;
        $this->redis->setex($this->sessionKey, $ttl, json_encode($data));

        return true;
    }

    /**
     * 获取redis中的用户信息
     *
     * @param string $token
     * @return bool|string
     */
    protected function getTokenRedisKey($token)
    {
        $decodeBase64 = base64_decode($token);
        if (empty($decodeBase64)) {
            return false;
        }

        return 'token-logintoken-' . $decodeBase64 . md5('#$%^YGVFR%^&*()PLKJHGFD@WSDT^TGUJKO' . $token . '#$%^YGVFR%^&*()PLKJHGFD@WSDT^TGUJKO');
    }

    /**
     * 从redis中获取数据
     *
     * @param string $key
     * @return bool|mixed
     */
    protected function redisFetch($key)
    {
        $data = $this->redis->get($key);
        if ($data && !is_null($data)) {
            $store = json_decode($data, true);
            if (is_array($store)) {
                return $store;
            }
        }

        return false;
    }

    /**
     * @param $loginToken
     * @return bool|array
     */
    protected function getLoginInfoV3($loginToken)
    {
        $data = array('login_token' => $loginToken);
        $ret = ApiClient::doServiceCall('account', 'Member/GetInfoByToken', 'v3', null, $data, array('debug'=>true));
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code']) {
            $info = $ret['service_data']['data'];
            return array(
                'internal_user_id' => $info['member_id'],
                'internal_company_id' => $info['company_id'],
                'force_switch_company'=>true,
                'source_channel' => '',
                'login_type' => 'sso'
            );
        } else {
            return false;
        }
    }

    /**
     * 跳转
     *
     * @param $url
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * 显示错误消息
     *
     * @param $message
     * @param $code
     */
    protected function showError($message, $code = false)
    {
        \Neigou\Logger::General('autoLoginToken', array(
            'action' => 'showError',
            'message' => $message,
            'code' => $code,
            'get' => $_GET,
            'session' => $this->session,
            'cookie' => $_COOKIE
        ));

        if ($code && $_GET['furl']) {
            // 跳转回失败的页面
            if ($failedUrl = isset($_GET['base64']) ? base64_decode($_GET['furl']) : $_GET['furl']) {
                $failedUrl = str_replace('NG_ERRCODE', urlencode($code), $failedUrl);
                $login_url_patt = defined("PSR_LOGIN_URL_PATT") ? PSR_LOGIN_URL_PATT : "" ;
                if(!empty($login_url_patt) && strtolower(substr($failedUrl ,0,4)) == "http") {
                    $url_info = parse_url($failedUrl);
                    if(!preg_match($login_url_patt ,$url_info['host'])) {
                        $this->showPage(array(
                            'status' => false,
                            'error' => "跳转地址不符合规则"
                        ));
                    }
                } else {
                    $this->redirect($failedUrl);
                }
            }
        }
        // 显示错误消息
        $this->showPage(array(
            'status' => false,
            'error' => $message
        ));
    }

    /**
     * 显示HTML页面
     *
     * @param array $params
     */
    protected function showPage(array $params)
    {
        $status = (isset($params['status']) && $params['status'] === true) ? 'success' : 'failed';
        header('Content-Type: text/html;charset=UTF-8');
        echo '
<!DOCTYPE HTML>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
    <title>正在登录...</title>
    <style>
        html, body{
            height: 100%;
            margin: 0;
            padding: 0;
            background-color: #fff;
        }
        #img{
            margin:0 auto;
        }
    </style>
    <script>
    ;(function(win, lib) {
        var doc = win.document;
        var docEl = doc.documentElement;
        var metaEl = doc.querySelector("meta[name=\'viewport\']");
        var flexibleEl = doc.querySelector("meta[name=\'flexible\']");
        var dpr = 0;
        var scale = 0;
        var tid;
        var flexible = lib.flexible || (lib.flexible = {});
    
        if (metaEl) {
            var match = metaEl.getAttribute("content").match(/initial\-scale=([\d\.]+)/);
            if (match) {
                scale = parseFloat(match[1]);
                dpr = parseInt(1 / scale);
            }
        } else if (flexibleEl) {
            var content = flexibleEl.getAttribute("content");
            if (content) {
                var initialDpr = content.match(/initial\-dpr=([\d\.]+)/);
                var maximumDpr = content.match(/maximum\-dpr=([\d\.]+)/);
                if (initialDpr) {
                    dpr = parseFloat(initialDpr[1]);
                    scale = parseFloat((1 / dpr).toFixed(2));
                }
                if (maximumDpr) {
                    dpr = parseFloat(maximumDpr[1]);
                    scale = parseFloat((1 / dpr).toFixed(2));
                }
            }
        }
    
        if (!dpr && !scale) {
            var isAndroid = win.navigator.appVersion.match(/android/gi);
            var isIPhone = win.navigator.appVersion.match(/iphone/gi);
            var devicePixelRatio = win.devicePixelRatio;
            if (isIPhone) {
                if (devicePixelRatio >= 3 && (!dpr || dpr >= 3)) {
                    dpr = 3;
                } else if (devicePixelRatio >= 2 && (!dpr || dpr >= 2)){
                    dpr = 2;
                } else {
                    dpr = 1;
                }
            } else {
                dpr = 1;
            }
            scale = 1 / dpr;
        }
    
        docEl.setAttribute("data-dpr", dpr);
        if (!metaEl) {
            metaEl = doc.createElement("meta");
            metaEl.setAttribute("name", "viewport");
            metaEl.setAttribute("content", "initial-scale=" + scale + ", maximum-scale=" + scale + ", minimum-scale=" + scale + ", user-scalable=no");
            if (docEl.firstElementChild) {
                docEl.firstElementChild.appendChild(metaEl);
            } else {
                var wrap = doc.createElement("div");
                wrap.appendChild(metaEl);
                doc.write(wrap.innerHTML);
            }
        }
    
        function refreshRem(){
            var width = docEl.getBoundingClientRect().width;
            if (width / dpr > 540) {
                width = 540 * dpr;
            }
            var rem = width / 10;
            docEl.style.fontSize = rem + "px";
            flexible.rem = win.rem = rem;
        }
    
        win.addEventListener("resize", function() {
            clearTimeout(tid);
            tid = setTimeout(refreshRem, 300);
        }, false);
        win.addEventListener("pageshow", function(e) {
            if (e.persisted) {
                clearTimeout(tid);
                tid = setTimeout(refreshRem, 300);
            }
        }, false);
    
        if (doc.readyState === "complete") {
            doc.body.style.fontSize = 12 * dpr + "px";
        } else {
            doc.addEventListener("DOMContentLoaded", function(e) {
                doc.body.style.fontSize = 12 * dpr + "px";
            }, false);
        }
    
    
        refreshRem();
    
        flexible.dpr = win.dpr = dpr;
        flexible.refreshRem = refreshRem;
        flexible.rem2px = function(d) {
            var val = parseFloat(d) * this.rem;
            if (typeof d === "string" && d.match(/rem$/)) {
                val += "px";
            }
            return val;
        }
        flexible.px2rem = function(d) {
            var val = parseFloat(d) / this.rem;
            if (typeof d === "string" && d.match(/px$/)) {
                val += "rem";
            }
            return val;
        }
    
    })(window, window["lib"] || (window["lib"] = {}));
    try {
        var src = "'.$params['login_display_image'].'"
        var newImg = new Image();
        newImg.onload=function(){
            var w =  newImg.width;
            var winW = document.body.clientWidth;
            if(winW>750){
                winW = 750
            }
            window.imgWidth= w * (winW/750) +"px"
        }
        newImg.src = src
    } catch (error) {
        
    }
    </script>
</head>
<body id="body">
<table style="text-align: center;width: 100%;height: 90%;border: 0;">
    <tr>
        <td>
        <img   id="img" src="'.$params['login_display_image'].'" />
        <script>
        document.getElementById("img").style.width = window.imgWidth
        </script>
        <br/>
        <p style="font-size:10pt;line-height: 2;color: #000;" id="message">'.$params['login_display_message'].'</p>
        </td>
    </tr>
</table>
<script type="text/javascript">
try {
    var oImg = document.getElementById("img");
    var src = oImg.src;
    var newImg = new Image();
    newImg.onload=function(){
        var w =  newImg.width;
        var winW = document.body.clientWidth;
        if(winW>750){
            winW = 750
        }
        oImg.style.width= w * (winW/750) +"px"
    }
    newImg.src = src
} catch (error) {
    
}


var STATUS = "' . $status . '";
var ERROR = "' . (isset($params['error']) ? $params['error'] : '') . '";
var FAILED_IMG = "/app/b2c/neigou_statics/images/newpassport/error-loading.png";
var TOKEN = "' . (isset($params['token']) ? $params['token'] : '') . '";
var SUCCESS_URL =  "' . (isset($params['successUrl']) ? $params['successUrl'] : '') . '";
var CAS_GATEWAY="' . CAS_APP_GETWAY . '/v2/login/tempAccessToken";
var STORE_PLATFORM = 2;
var FORCE_LOGIN = ' . ((isset($params['forceLogin']) && $params['forceLogin'] === true) ? 'true' : 'false') . ';

function notify(result){
   if(result && result.errno === 0 && result.body){
       for (var i = 0; i < result.body.notify.length; i++){
           var platform = result.body.notify[i];
           if(STORE_PLATFORM === platform.platform) {
               jsonp(platform.url, {}, onNotify);
           } else if(FORCE_LOGIN){
               jsonp(platform.url);
           }
       } 
   } else {
      showError(result.error);
   }
}

function onNotify(result){
    if(result.errno === 0){
        document.getElementById("message").innerText = "'.$params['login_display_success_message'].'";
        window.location.replace(SUCCESS_URL)
        
    } else {
        showError(result.error || "登录失败");
    }
}

function showError(message, title) {
    document.getElementById("message").innerText = message || "登录失败";
    document.title = title || "登录失败";
    document.getElementById("img").src = FAILED_IMG;
    document.getElementById("img").style.display="block";
    document.getElementById("loading_circle").style.display="none";

}

function format(data) {
    var params = [];
    for (var p in data) {
        if(data.hasOwnProperty(p)){
             params.push(encodeURIComponent(p) + "=" + encodeURIComponent(data[p]))
        }
    }
    return params.join("&");
}

function jsonp (url, data, success) {
    var callbackName = "jsonp" + (~~(Math.random()*0xffffff)).toString(16);
    var script = document.createElement("script");
    window[callbackName] = success || function () {};
	
    data = data || {};
    data.callback = callbackName;
    url += url.indexOf("?") > 0 ? "&" : "?";
    script.setAttribute("src", url  + format(data));
    document.body.appendChild(script);
}

if(STATUS === "success"){
    jsonp(CAS_GATEWAY,{
        wd: "json",
        callback: "callback",
        tempAccessToken: TOKEN
    }, notify);
}else{
    showError(ERROR, "登录失败");
}

</script>
</body>
</html>';
        exit;
    }
}


$autoLogin = new AutoLogin();
$autoLogin->autoLogin();
exit;
