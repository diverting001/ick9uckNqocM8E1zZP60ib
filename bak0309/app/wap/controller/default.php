<?php

//class wap_ctl_default extends wap_controller{
class wap_ctl_default extends wap_frontpage{
    function __construct(&$app) {
        parent::__construct($app);
        kernel::single('base_session')->start();
    }

    function index()
    {

        //首页数据
        /*
        $company=app::get('site')->model_for_config('index',b2c_util::get_club_db_conf());
                //Redis
        $redis_chache   = kernel::single('base_sharedkvstore');
        $prefix = 'neigous';
        $check_key = 'home_page_info';
        $indexDatas = array();
        $infoData  = $redis_chache->fetch($prefix,$check_key,$indexDatas);
        if(!$infoData){
            $company_infos=$company->getCompanyInfo(12);
            foreach ($company_infos as $key=>$info) {
                $num = 50;
                if ($info['staff_amount']=='50人以下') {
                    $num = 50;
                }
                if ($info['staff_amount']=='50-100人') {
                    $num = 100;
                }
                if ($info['staff_amount']=='100-200人') {
                    $num = 200;
                }
                if ($info['staff_amount']=='200-500人') {
                    $num = 500;
                }
                if ($info['staff_amount']=='500-1000人') {
                    $num = 1000;
                }
                if ($info['staff_amount']=='1000-5000人') {
                    $num = 5000;
                }
                if ($info['staff_amount']=='5000人及以上') {
                    $num = '5000+';
                }
                $company_infos[$key]['pass_date'] = date('m月d日', strtotime($info['pass_date']));
                $company_infos[$key]['num'] = $num;
            }
            
            $indexDatas['home_page']['company_info'] = $company_infos;            
            $indexDatas['home_page']['index_data'] = $company->getNeigouIndexNum();
            $indexDatas['home_page']['member_num'] = $company->getNeigouMembersNum();
            $indexDatas['home_page']['num'] = $company->getNumber();
            $redis_chache->store($prefix,$check_key,$indexDatas,3600*2);
        }
        
        $this->pagedata['company_num'] = $indexDatas['home_page']['num'];
        $this->pagedata['member_num'] = $indexDatas['home_page']['member_num'];
        $this->pagedata['good_brand'] = $indexDatas['home_page']['index_data']['good_brand'];
        $this->pagedata['hot_brand'] = $indexDatas['home_page']['index_data']['hot_brand'];
        $this->pagedata['company_info'] = $indexDatas['home_page']['company_info'];
        */

        //todo(xiangcai.guo)已登录状态下跳转到内购主页
        if(parent::check_login()){

            // 已登录，跳转到公司配置的地址
            $company_id = kernel::single("b2c_member_company")->get_cur_company();
            $config = kernel::single("b2c_global_scope")->getScopeByCompany('company', $company_id, 'wap_www_index');
            if(isset($config['key_value']) && !empty($config['key_value'])){
                $this->redirect($config['key_value']);
                return;
            }
            //特卖跳转逻辑,绑定数据
            \Neigou\Logger::General("login_success",array("login_success"=>'已登录，跳转到内购主页'));
            $this->redirect($this->get_domain());
            return;
        }

        if($_COOKIE['login_url']){
            // 如果有设置自定义登录页
            $this->redirect($_COOKIE['login_url']);
            exit;
        }
        // 获取平台
        $platform = $_COOKIE['from_platform'] ? $_COOKIE['from_platform'] : (defined('PSR_PLATFORM') ? PSR_PLATFORM : 'neigou');
        //没有登录,跳转到指定登录地址
        $urlInfo = kernel::single('b2c_config')->getNoLoginRedirectUrl($platform);
        if (isset($urlInfo['value']) && !empty($urlInfo['value'])){
            $this->redirect($urlInfo['value']);
        }

        if ($_REQUEST["channel"] == "checkin" && kernel::single('base_component_request') -> is_browser_tag('weixin')) {
            \Neigou\Logger::General("wxautologin", array("action"=>"entrance"));
            $this->wxchannellogin();
        }

        // 增加微信自动登录逻辑
        if(kernel::single('base_component_request') -> is_browser_tag('weixin') && !isset($_GET['wxssochecked']) && $_GET['wxssochecked'] != 1){
            $this -> wxsso();
            exit;
        }

        $this -> pagedata['ssochecked'] = intval($_GET['ssochecked']);
        $_GET['ssochecked'] = 1;
        $url_append = '?' . http_build_query($_GET);
        $url = ECSTORE_DOMAIN . $url_append;
        $this -> pagedata['redirect'] = $url;
        $this -> pagedata['getway'] = CAS_APP_GETWAY;

        $GLOBALS['runtime']['path'][] = array('title'=>app::get('wap')->_('首页'),'link'=>kernel::base_url(1));
        $this->set_tmpl('index');
        $this->title=app::get('wap')->getConf('wap.shopname');
        $this->pagedata['theme_base'] = kernel::base_url(1).'/wap_themes/'.app::get('wap')->getConf('current_theme');
        if(in_array('index', $this->weixin_share_page)){
            $this->pagedata['from_weixin'] = $this->from_weixin;
            $this->pagedata['weixin']['appid'] = $this->weixin_a_appid;
            $this->pagedata['weixin']['imgUrl'] = base_storager::image_path(app::get('weixin')->getConf('weixin_basic_setting.weixin_logo'));
            $this->pagedata['weixin']['linelink'] = app::get('wap')->router()->gen_url(array('app'=>'wap','ctl'=>'default', 'full'=>1));
            $this->pagedata['weixin']['shareTitle'] = app::get('weixin')->getConf('weixin_basic_setting.weixin_name');
            $this->pagedata['weixin']['descContent'] = app::get('weixin')->getConf('weixin_basic_setting.weixin_brief');
        }
        $this->page('index.html');
    }

    private function wxsso(){
        $url_tag = $_GET ? '&' : '?';
        $appid = app::get('weixin')->getConf('ecos.leho.weixin.appid');
        $param = array();
        $param['appid'] = $appid;
        $scheme =  $scheme = kernel::single('base_component_request')->get_server('HTTPS')=='on'?'HTTPS':'HTTP';
        $domain = strtoupper($scheme) . ':'.ECSTORE_DOMAIN_URL_DYNPTL;
        $param['successUrl'] = $domain . $url_tag . 'wxssochecked=1';
        $param['failUrl'] = $domain . $url_tag . 'wxssochecked=1';
        $param['logout'] = 0;
        $url = CAS_APP_GETWAY . '/v2/login/sso/wechatPlatformBase?' . http_build_query($param);
        header("Location: {$url}");
        exit; 
    }

    //验证码组件调用
    function gen_vcode($key='vcode',$len=4){
        $vcode = kernel::single('base_vcode');
        $vcode->length($len);
        $vcode->verify_key($key);
        $vcode->display();
    }

    public  function wxchannellogin(){
        $appid = app::get('weixin')->getConf('ecos.leho.weixin.appid');
        $key = '12345';
        $redirect_uri =  urlencode($this->gen_url(array('app' => 'wap','ctl' => 'default','act' => 'wxchannellogin_callback','full'=>1)));
        $url = sprintf("https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_base&state=%s#wechat_redirect",
            $appid,$redirect_uri,$key);

        \Neigou\Logger::General("wxautologin", array("action"=>"wxlogin", "url"=>$url));
        header('Location:'.$url);
    }

    public  function wxchannellogin_callback()
    {
        $appid = app::get('weixin')->getConf('ecos.leho.weixin.appid');
        $appsecret = app::get('weixin')->getConf('ecos.leho.weixin.appsecret');
        $code = $_GET['code'];
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$appsecret}&code={$code}&grant_type=authorization_code";
        \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "url"=>$url));
        $httpclient = kernel::single('base_httpclient');
        $response = $httpclient->set_timeout(6)->get($url);
        $result = json_decode($response, true);
        \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "access_token_data"=>$response));
        $index_uri =  $this->gen_url(array('app' => 'wap','ctl' => 'default','act' => 'index'));
        if (isset($result['errcode'])) {
            \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "sns_data"=>$response));
            header('Location:' . $index_uri);
        } else {
            $wxunionid = $result['unionid'];
            if (empty($wxunionid)) {

                /*
                $access_token_url = sprintf("https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=%s&secret=%s", $appid, $appsecret);
                $access_response = file_get_contents($access_token_url);
                $access_response_data = json_decode($access_response, true);
                */

                $access_response_data['access_token'] = $this->wx_get_token($appid);

                if (!empty($access_response_data["access_token"])) {
                    $get_unionid_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".
                        $access_response_data['access_token'].
                        "&openid=".$result['openid']."&lang=zh_CN";

                    $union_data = file_get_contents($get_unionid_url);

                    $union_data_array = json_decode($union_data, true);
                    if (isset($union_data_array["unionid"])) {
                        $wxunionid = $union_data_array["unionid"];
                    }
                }
            }
            if (!empty($wxunionid)) {
                $member_mdl = app::get('b2c')->model("members");

                $member_info = $member_mdl->getList("*", array('wxunionid'=>$wxunionid));

                $birthday_model = app::get('birthday')->model_for_config('birthday',b2c_util::get_club_db_conf());
                $result = $birthday_model->getCompanyCertInfo($member_info[0]['company_id']);

                if (!empty($result)) {
                    if ($member_info[0]['is_import']=="1" && $member_info[0]['is_bind']=="true") {
                        $_SESSION['verify_member_id'] = $member_info[0]['member_id'];
                        $type = 'import';        // 用户导入标识
                        $param = urlencode(b2c_passport_mailtmpl::encrypt(base64_encode($type."&".$member_info[0]['member_id']."&".time())));
                        $link_url = $this->gen_url(array('app'=>'b2c', 'ctl'=>'wap_passport', 'act'=>'cellphone_binding', 'arg0'=>$param, 'full'=>1));

                        \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "success"=>1, "cellphone_binding_url"=>$link_url));
                        header('Location:' . $link_url);
                    } else if ($member_info[0]['is_import']=="1" && $member_info[0]['is_bind']=="false") {
                        $_SESSION['verify_member_id'] = $member_info[0]["member_id"];
                        $random_numbers = $this->generate_password(6);
                        $param = base64_encode(b2c_passport_mailtmpl::encrypt("reset_pwd".'&'.$random_numbers.'&'.time()));
                        $very_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'olduser_mailedit','arg0'=>$param,'full'=>1));
                        \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "success"=>1, "olduser_mailedit_url"=>$very_url));
                        header('Location:' . $very_url);
                    } else {
                        \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "success"=>0, "reason"=>"member_neednot_verify"));
                        header('Location:' . $index_uri);
                    }
                } else {
                    \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "success"=>0, "reason"=>"member_error"));
                    header('Location:' . $index_uri);
                }
            } else {
                \Neigou\Logger::General("wxautologin", array("action"=>"wxchannellogin_callback", "success"=>0, "reason"=>"invalid_unionid"));
                header('Location:' . $index_uri);
            }
        }
    }

    public  function wxcheck_and_autologin(){
        $members_mdl = app::get('b2c')->model('members');

        if($user =$members_mdl->getRow('*',array('wxunionid'=>$_COOKIE['LEHO_wx_unionid'],'disabled' => 'false'))){
            $very_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'login'));
            \Neigou\Logger::General("wxautologin", array("action"=>"wxlogin", "success"=>1));
            header('Location:' . $very_url);
            //用户已经存在
            exit;
        } else {
            return false;
        }
    }
    public function wx_get_token($appid){

        $bindinfo = app::get('weixin')->model('bind')->getRow('appid, appsecret, id',array('appid'=>$appid));
        if( $bindinfo['appid'] && $bindinfo['appsecret']) {

        }else{
            return  false;
        }

        $bind_id = $bindinfo['id'];
        $wechat = kernel::single('weixin_wechat');
        $token = $wechat->get_basic_accesstoken($bind_id);

        return $token;
    }
    // 重写父类方法
    public function checkUAToken() {}


    //生成随机数
    function generate_password($length=6){
        //密码字符集
        $chars = '1234567890';
        $password = '';
        for ( $i = 0; $i < $length; $i++ ){
            $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
        }
        return $password;
    }
}