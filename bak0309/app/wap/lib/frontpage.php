<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wap_frontpage extends wap_controller{
    //todo
    protected $member = array();
    protected $company_platform;

    function __construct(&$app){
        parent::__construct($app);

        kernel::single('base_session')->start();


        if(!empty($_SESSION['AUTOLOGIN']) && $_SESSION['AUTOLOGIN']){

            $should_clear_session = true;

            $autologin_expires = $_SESSION['AUTOLOGIN']['EXPIRES'];
            if (is_numeric($autologin_expires) && $autologin_expires > 0){

                $should_clear_session = false;

                if ($autologin_expires - time() > 60 * 10) {

                    $left_minutes = ($autologin_expires - time()) / 60;

                    kernel::single('base_session')->set_sess_expires($left_minutes, false);
                }
            }

            if (empty($_COOKIE['S']['MEMBER'])) {

                if ($this->app->member_id = $_SESSION['account'][pam_account::get_account_type($this->app->app_id)]) {
                    $this->bind_member($this->app->member_id);
                }
            }

        }

        $this->pagedata['site_b2c_remember'] = $_COOKIE['S']['SIGN']['REMEMBER'];
        //todo(xiangcai.guo)增加记录前一页URL
        if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || ($_SERVER['HTTP_X_REQUESTED_WITH'] !='XMLHttpRequest')){
            if ($_SERVER['SCRIPT_NAME'] != $_SERVER['PHP_SELF']) {
                //todo fuchun.zhao 去除登录后回退getsms输出jsonp
                if(!strpos($_SERVER['PHP_SELF'],'jsonpDynamic') &&
                    !strpos($_SERVER['PHP_SELF'],'attentionAndLogin') &&
                    !strpos($_SERVER['PHP_SELF'],'getsms') &&
                    !strpos($_SERVER['PHP_SELF'],'openapi/cas') &&
                    strpos($_REQUEST['from_vue'] != 1) &&
                    !strpos($_SERVER['PHP_SELF'],'passport') &&
                    !strpos($_SERVER['PHP_SELF'],'send_vcode_sms') &&
                    !strpos($_SERVER['PHP_SELF'],'gen_vcode') &&
                    !strpos($_SERVER['PHP_SELF'],'cart-view.') &&
                    !strpos($_SERVER['PHP_SELF'],'shipping_lsit') &&
                    !strpos($_SERVER['PHP_SELF'],'select_state') &&
                    !strpos($_SERVER['PHP_SELF'],'dispatch.html') &&
                    !strpos($_SERVER['PHP_SELF'],'index.php') &&
                    !strpos($_SERVER['PHP_SELF'],'tools-select_region_tree.html') &&
                    !strpos($_SERVER['PHP_SELF'],'getProductsPrice.html')){
                    $this->set_nextpage($_SERVER['PHP_SELF']);
                }
            }
        }
        $this->pagedata['theme_base'] = kernel::base_url(1).'/wap_themes/'.app::get('wap')->getConf('current_theme');
        $this->pagedata['home_url'] = kernel::base_url(1).'/m';
        $this->pagedata['code_tip_show'] = true ;
        //todo(xiangcai.guo)增加club域名
        $this->pagedata['club_domain'] = CLUB_DOMAIN;
        $this->pagedata['life_domain'] = LIFE_DOMAIN;

        if (defined('SESSION_COOKIE_DOMAIN')){
            $alpha_cookie_domain = SESSION_COOKIE_DOMAIN;
        }

        /*
         * ALPHA/ABTEST Cookie失效时间不一致、意外丢失（用户删除）时重置
         *
         * 逻辑：SESSION[ECSTORE_ALPHA]永远准确，跟随登录状态而变化。
         *
         * 1）如果COOKIE ECSTORE_ALPHA不存在，但SESSION[ECSTORE_ALPHA]存在，说明单条COOKIE丢失
         * 2）如果一个用户已被动logout（SESSION失效），带着原COOKIE（Alpha）访问，则check_login时才能切换回ALPHA状态
         * */
        if (empty($_COOKIE['ECSTORE_ALPHA'])){

            $ecstore_alpha = $_SESSION["ECSTORE_ALPHA"];

            if (!empty($ecstore_alpha)){
                \Neigou\Logger::General('alpha.warning', array("msg"=>"cookie_losing"));
                $this->set_cookie('ECSTORE_ALPHA', $ecstore_alpha, false, null, $alpha_cookie_domain);
            }else{
                $this->set_cookie('ECSTORE_ALPHA', 'official', false, null, $alpha_cookie_domain);
            }

        }

        $baseline = $_COOKIE['BASELINE_life'];
        if ($baseline != $_SESSION['sys_version']){
            $this->set_cookie('BASELINE_life', $_SESSION['sys_version'], false, null, $alpha_cookie_domain);
        }

        if($this -> __request_only() == false){
            $this -> set_header_data();
        }

        $this->checkUAToken() ;
        if(isset($_GET['callback'])) {
            $_GET['callback'] = strip_tags($_GET['callback']) ;
        }
        /**
         * session 同步
         */
        if(isset($_SESSION['account']['member'])){
            $_member = kernel::single('b2c_member');
            $_member -> syncSession();

            // 检查并补全用户登录场景
            if(empty($_SESSION['USER_STAGE']) || empty($_SESSION['USER_STAGE']['channel'])){

                /* @var b2c_member_company $memberCompanyLib */
                $memberCompanyLib = kernel::single("b2c_member_company");

                $companyId = $memberCompanyLib->get_cur_company();

                $memberCompany = $memberCompanyLib->get_valid_company_forlogin($_SESSION['account']['member'], $companyId);

                $_SESSION['USER_STAGE']['company'] = $companyId;
                if(!empty($memberCompany['channel'])){
                    $_SESSION['USER_STAGE']['channel'] = $memberCompany['channel'];
                }
                if(!empty($memberCompany['tag'])){
                    $_SESSION['USER_STAGE']['company_tag'] = $memberCompany['tag'];
                }
            }
        }

        //积分名称
         $companyId = kernel::single("b2c_member_company")->get_cur_company();
        $coinMdl = app::get('jifen') -> model('coin');
        $coinName = $coinMdl->getCoinName($companyId);
        $this->pagedata['coin_name'] = $coinName;

        $titleName = $coinMdl->getTitleName($companyId);
        $this->pagedata['title_name'] = $titleName;

        //是否是华夏银行
        if($companyId == HX_BANK_COMPANY_ID){
            $this->pagedata['special_company_flag'] = 1;
        }elseif($companyId == AN_BANG_COMPANY_ID){
            $this->pagedata['special_company_flag'] = 2;
        }elseif($companyId == JIN_DI_COMPANY_ID){
            $this->pagedata['special_company_flag'] = 3;
        }else{
            $this->pagedata['special_company_flag'] = 0;
        }

        // 获取公司的平台
        $company_platform = kernel::single("b2c_global_scope")->getCompanyPlatformForRedis($_SESSION['CUR_COMPANY_ID']);
        $this->company_platform = $company_platform;
        $this->pagedata['company_platform'] = $company_platform;

        //获取公司主题色
        $theme_color = kernel::single("b2c_global_scope")->getThemeColorForRedis($_SESSION['CUR_COMPANY_ID']);
        $this->pagedata['company_theme_color_rgb'] = $theme_color;

        if(!$_SESSION['product_name'] && $_SESSION['CUR_COMPANY_ID']){
            $_SESSION['product_name'] = $this->product_name($_SESSION['CUR_COMPANY_ID']);
        }

        if($this -> __request_only() == false){
            $this -> set_footer_data($companyId);
        }

    }


    public function checkUAToken() {
        $err_msg  = '' ;
        $checkUaTokenRes =  kernel::single('b2c_access')->check_ua_token($err_msg) ;
        if(empty($checkUaTokenRes)) {
            \Neigou\Logger::Debug('LoginTokenCheck',array('action' => 'store_web_front' ,'client_ip' => base_request::get_client_ip() ,
                "member_id" => $this->app->member_id ,
                'err_msg' => $err_msg ,
                'cur_url'=> $_SERVER['SERVER_NAME'] . ':' . $_SERVER['REQUEST_URI'])) ;
            $is_check_ua = defined("PSR_LOGIN_USER_ANGENT_CHECK") ? PSR_LOGIN_USER_ANGENT_CHECK :false ;
            if($is_check_ua == true) {
                if (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest") {
                    $this -> ajax_return(10001,array('redirectUrl' => ECSTORE_DOMAIN . '/passport-logout.html'),'The user login environment has changed');
                } else {
                    $this->redirect(ECSTORE_DOMAIN . '/passport-logout.html');
                }
            }
        }
    }

    /**
     * 子类根据自己的规则跳过用户信息获取等
     */
    public function __request_only(){
        return false;
    }

    public function set_footer_data(){
        //网站底通配置
        $system=app::get('b2c')->model_for_config('footer_config',b2c_util::get_club_db_conf());
        $config=$system->getFooterConfig();
        $this->pagedata['config']=$config;
        $result = $system->getFooterNavigate();
        $this->pagedata['showNavigateFooter'] =0;
        $elevatorphone = $config['telphone'];
        if($result){
            if($result['Result']=='true') {
                $showFooter = $result['Data'];
                $elevatorphone = $showFooter['showRightData']['phone'];
            }
        }

        //客服电话设置, 如果有定制的客服电话, 用定制的客服电话
        $globalScopelogic  = kernel::single("b2c_global_scope");
        $serviceTelRes = $globalScopelogic->GetCompanyChannelSetByKey($_SESSION['USER_STAGE']['company'],$_SESSION['USER_STAGE']['channel'],'custom_service_tel');

        if (!isset($serviceTelRes['key_value']) || empty($serviceTelRes['key_value'])){
            $this->pagedata['elevatorphone'] = empty($showFooter['showRightData']['phone'])?$config['telphone']:$showFooter['showRightData']['phone'];
        }else{
            $this->pagedata['elevatorphone'] = $serviceTelRes['key_value'];
        }
    }

    public function set_header_data(){
        //todo(xiangcai.guo)获取用户姓名
        $member_info = $this->get_current_member();
        $this->pagedata['userName'] = $member_info['name'];
        $this -> pagedata['member_base_info'] = $member_info;
    }

    function get_module_permission_members($module_id = '',$member_id = ''){
        //判断当前登录人员是否有权限使用该节日模块
        if(!is_numeric($module_id) || !$member_id){
            return false;
        }
        $data = array();
        $data['class_obj'] = 'Module';
        $data['method'] = 'getModulePermissionMembers';
        $data['module_id'] = $module_id;
        $token = kernel::single('b2c_safe_apitoken')->generate_token($data, OPENAPI_TOKEN_SIGN);
        $data['token'] = $token;
        $url = LIFE_DOMAIN . '/OpenApi/apirun';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($output,true);

        if(!in_array($member_id,$data['Data'])){
            return false;
        }
        return true;
    }

    //todo(xiangcai.guo)增加设置页面title公共方法
    public function set_title($title)
    {
        if(!empty($title)){
            $this->pagedata['title'] = $title;
        }
    }

    function verify_member(){
        $current_url = $this -> _request -> get_full_http_url();
        $current_url = urlencode($current_url);
        kernel::single('base_session')->start();

        if($this->app->member_id = $_SESSION['account'][pam_account::get_account_type($this->app->app_id)]){
            $this -> app -> guid = $_SESSION['account']['guid'];
            $obj_member = app::get('b2c')->model('members');
            $data = $obj_member->select()->columns('member_id')->where('member_id = ?',$this->app->member_id)->instance()->fetch_one();
            if($data){
                //登录受限检测
                $res = $this->loginlimit($this->app->member_id,$redirect);
                if($res){
                    $this->redirect($redirect);
                }else{
                    return true;
                }
            }else{
                //$url = $this -> gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'login','arg0' => $current_url,'full' => 1));
                $url = REDIRECT_NEIGOU_LOGIN_URL . '?redirect=' . $current_url;
                if($_REQUEST['from_vue'] == 1){
                    $this -> ajax_return(10001,array('redirectUrl' => $url),'请重新登录');
                }
                $this -> sceneLogin();
                //$this->redirect(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'login','arg0' => $current_url));
                $this->redirect($url);
            }
        }else{
            //$url = $this -> gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'login','arg0' => $current_url,'full' => 1));
            $url = REDIRECT_NEIGOU_LOGIN_URL . '?redirect=' . $current_url;
            if($_REQUEST['from_vue'] == 1){
                $this -> ajax_return(10001,array('redirectUrl' => $url),'请重新登录');
            }
            $this -> sceneLogin();
            $this->redirect($url);
            //$this->redirect(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'login','arg0' => $current_url));
        }

    }
    /**
    * loginlimit-登录受限检测
    *
    * @param      none
    * @return     void
    */
    function loginlimit($mid,&$redirect) {
        $services = kernel::servicelist('loginlimit.check');
        if ($services){
            foreach ($services as $service){
                $redirect = $service->checklogin($mid);
            }
        }
        return $redirect?true:false;
    }//End Function

    public function bind_member($member_id){
        $obj_member = app::get('b2c')->model('members');
        $data = $obj_member->dump($member_id,'*',array(':account@pam'=>array('*')));

        //todo 绑定失败, 退出
        $service = kernel::service('pam_account_login_name');
        if(is_object($service)){
            if(method_exists($service,'get_login_name')){
                $data['pam_account']['login_name'] = $service->get_login_name($data['pam_account']);
            }
        }

        $default_company = $data['company_id'];

        if ($_SESSION['LOGIN']['COMPANY']['UID']  == $member_id && !empty($_SESSION['LOGIN']['COMPANY']['COMPANY_ID'])){
            $default_company = $_SESSION['LOGIN']['COMPANY']['COMPANY_ID'];
        }

        // 登录完成后设置公司
        $member_company_info = kernel::single("b2c_member_company")->get_valid_company_forlogin($member_id, $default_company);
        kernel::single('b2c_member_company')->switch_company($member_id, $member_company_info['company_id']);

        //判断COOKIE是否有微信的OPEN ID及unionid 如果有将open id及unionid 保存到会员表中
        if(isset($_COOKIE['LEHO_wx_openid']) &&
            !empty($_COOKIE['LEHO_wx_openid']) &&
            isset($_COOKIE['LEHO_wx_unionid']) &&
            !empty($_COOKIE['LEHO_wx_unionid']) &&
            ($_COOKIE['LEHO_wx_openid'] != $data['wxopenid'] || $_COOKIE['LEHO_wx_unionid'] != $data['wxunionid'])){
            $wx_data = array('wxopenid'=>trim($_COOKIE['LEHO_wx_openid']),'wxunionid'=>trim($_COOKIE['LEHO_wx_unionid']));

            // 清除其他绑定用户
            $exist_member_info = $obj_member->getList("*", $wx_data);
            if (!empty($exist_member_info)) {
                foreach ($exist_member_info as $member_item) {
                    if (!empty($member_item['member_id'])) {
                        $empty_wx_data = array('wxopenid'=>'','wxunionid'=>'');
                        $obj_member->update($empty_wx_data,array('member_id'=>$member_item['member_id']));
                    }
                }
            }
            $obj_member->update($wx_data,array('member_id'=>$member_id));
        }

        $this->cookie_path = kernel::base_url().'/';
        $this->set_cookie('loginName',$data['pam_account']['login_name'],time()+31536000);
        if (defined('COOKIE_UNAME_SAME_WITH_SESSION') && (bool)COOKIE_UNAME_SAME_WITH_SESSION && defined('SESSION_COOKIE_DOMAIN')){
            $this->set_cookie('UNAME','',time()-3600);
            $this->set_cookie('UNAME',$data['pam_account']['login_name'],0, null, SESSION_COOKIE_DOMAIN);
        }else{
            $this->set_cookie('UNAME',$data['pam_account']['login_name'],time()+30*24*60*60);
        }
        //@TODO maojz UNAME写入session，当cookie中的UNAME丢失时，重新写入cookie
        $_SESSION['UNAME'] = $data['pam_account']['login_name'];
        $this->set_cookie('MLV',$data['member_lv']['member_group_id'],time()+30*24*60*60);
        $this->set_cookie('NICKNAME',$data['nickname'],time()+30*24*60*60);
        $this->set_cookie('CUR',$data['currency'],time()+30*24*60*60);
        $this->set_cookie('LANG',$data['lang'],time()+30*24*60*60);
        $this->set_cookie('S[MEMBER]',$member_id,time()+30*24*60*60);
        //todo(xiangcai.guo)增加用户姓名
        $this->set_cookie('NAME',$data['contact']['name'],time()+30*24*60*60);

        //ALPHA/ABTEST 对指定用户设置ALPHA状态
        $alpha_status = ($data['company_id'] == 68 ? "alpha" : "official");

        if (defined('SESSION_COOKIE_DOMAIN')){
            $alpha_cookie_domain = SESSION_COOKIE_DOMAIN;
        }

        if ($alpha_status == "alpha"){
            $_SESSION['ECSTORE_ALPHA'] = $alpha_status;
            $this->set_cookie("ECSTORE_ALPHA", $alpha_status, 0, null, $alpha_cookie_domain);
        }
        else{
            $_SESSION['ECSTORE_ALPHA'] = $alpha_status;
            $this->set_cookie("ECSTORE_ALPHA", null);
            $this->set_cookie("ECSTORE_ALPHA", null, 0, null, $alpha_cookie_domain);
        }

        // 设置登录cookie，为域名跳转用
        $this->set_cookie("web_router", 100, 0, null, $alpha_cookie_domain);

        //临时for php7 升级设置cookie
        if(in_array($default_company,array(2437,8691))){
            $this->set_cookie('new_mall','true',false, null,PSR_NEIGOU_COOKIE_DOMAIN);
        }

        //是否为百度公司
//        $_SESSION['is_baidu']   = $member_company_info['company_id'] == NEIGOU_BAIDU_COMPANY_ID?'true':'false';

        //BASELINE
        $this->ensure_baseline_session();
        $this->set_cookie("BASELINE_life", $_SESSION['sys_version'], false, null, $alpha_cookie_domain);

        // cas cookie
        $this -> set_cookie(CAS_TOKEN_KEY, $_SESSION[CAS_TOKEN_KEY], time() + 10*24*60*60, null, SESSION_COOKIE_DOMAIN);
        $this -> set_cookie(CAS_TOKEN_KEY, $_SESSION[CAS_TOKEN_KEY], time() + 10*24*60*60, null, DD_SESSION_COOKIE_DOMAIN);
    }

    function ensure_baseline_session(){

        if(empty($_SESSION['sys_version'])){
            $member_id = $_SESSION['account'][pam_account::get_account_type($this->app->app_id)];
            $dbconfig = array('MASTER'=>array('HOST'=>CLUB_DB_MASTER_HOST,'NAME'=>CLUB_DB_MASTER_NAME,'USER'=>CLUB_DB_MASTER_USER,'PASSWORD'=>CLUB_DB_MASTER_PASSWORD));
            $companyModel = app::get('b2c')->model_for_config('company',$dbconfig);
            $member=$this->get_current_member();
            $company_id = kernel::single("b2c_member_company")->get_cur_company();
            $memberCompany = $companyModel->getCompanyById($company_id);
            \Neigou\Logger::General("get_domain", array("member"=>$member, "dbconfig"=>$dbconfig, "member_id"=>$member_id, "memberCompany"=>$memberCompany));
            if(!empty($memberCompany[0]['sys_version'])) {
                $_SESSION['sys_version'] = $memberCompany[0]['sys_version'];
                return true;
            }
        }

        return true;
    }

    function get_domain(){

        $this->ensure_baseline_session();

        $scheme = strtolower(base_request::get_schema()).':';

        if($_SESSION['sys_version']){
            if($_SESSION['sys_version']=='m3'){
                return $scheme . LIFE_DOMAIN_URL_DYNPTL;
            }else{
                return $scheme . MALL_DOMAIN_URL_DYNPTL;
            }
        }

        return kernel::base_url(1);
    }


    public function _check_verify_member($member_id=0)
    {
        if (isset($member_id) && $member_id)
        {
            $arr_member = $this->get_current_member();
            if ($member_id != $arr_member['member_id'])
            {
                $this->begin();
                $this->end(false,  app::get('b2c')->_('订单无效！'), $this->gen_url(array('app'=>'site','ctl'=>'default','act'=>'index')));
            }
            else
            {
                return true;
            }
        }

        return false;
    }

    public function get_current_member()
    {

       if($this->member) return $this->member;
        $obj_members = app::get('b2c')->model('members');
        $this->member = $obj_members->get_current_member();
        //登录受限检测
        if(is_array($this->member)){
            $minfo = $this->member;
            $mid = $minfo['member_id'];
            $res = $this->loginlimit($mid,$redirect);
            if($res){
                $this->redirect($redirect);
            }
        }
        return $this->member;
    }

    function set_cookie($name,$value,$expire=false,$path=null, $domain=null){
        if(!$this->cookie_path){
            $this->cookie_path = kernel::base_url().'/';
            #$this->cookie_path = substr(PHP_SELF, 0, strrpos(PHP_SELF, '/')).'/';
            $this->cookie_life =  app::get('b2c')->getConf('system.cookie.life');
        }
        $this->cookie_life = $this->cookie_life > 0 ? $this->cookie_life : 315360000;
        $expire = $expire === false ? time()+$this->cookie_life : $expire;
        setcookie($name,$value,$expire,$this->cookie_path, $domain);
        $_COOKIE[$name] = $value;
    }

    function check_login(){
        kernel::single('base_session')->start();
        if($_SESSION['account'][pam_account::get_account_type('b2c')]){
            //@TODO maojz UNAME 丢失时重新写入
            if(empty($_COOKIE['UNAME'])){
                if(empty($_SESSION['UNAME'])){
                    $service = kernel::service('pam_account_login_name');
                    if(is_object($service)){
                        if(method_exists($service,'get_login_name')){
                            $_SESSION['UNAME'] = $service->get_login_name($_SESSION['account'][pam_account::get_account_type($this->app->app_id)]);
                        }
                    }
                }
                $this->set_cookie('UNAME',$_SESSION['UNAME'],0,null, SESSION_COOKIE_DOMAIN);
            }
            $this->CheckSessionStage(); //检查场景更新
            return true;
        }else{

            $_SESSION['ECSTORE_ALPHA'] = 'official';

            if (defined('SESSION_COOKIE_DOMAIN')){
                $alpha_cookie_domain = SESSION_COOKIE_DOMAIN;
            }
            $this->set_cookie('ECSTORE_ALPHA', null);
            $this->set_cookie("ECSTORE_ALPHA", null, 0, null, $alpha_cookie_domain);

            return false;
        }
    }

    protected function experienceAccountStatus()
    {
        // 获取公司的平台
        $platform = kernel::single("b2c_global_scope")->getCompanyPlatform($_SESSION['USER_STAGE']['company']);
        $configLogic = kernel::single('b2c_config');
        if(isset($_SESSION['USER_STAGE']['company'])){
            $accountJson = $configLogic->getExperienceAccount(
                $platform,
                'login_experience_account_'.$_SESSION['USER_STAGE']['company']
            );
        }
        if(!isset($accountJson) || empty($accountJson)) {
            $accountJson = $configLogic->getExperienceAccount($platform);
        }
        if ($accountJson) {
            if (
                isset($_SESSION['USER_STAGE']['company']) &&
                isset($_SESSION['account']['member'])
            ) {
                $experienceAccount = json_decode($accountJson, true);
                //是体验账号
                if (
                    $experienceAccount['company_id'] == $_SESSION['USER_STAGE']['company'] &&
                    $experienceAccount['member_id'] == $_SESSION['account']['member']
                ) {
                    return isset($experienceAccount['login_status']) ? $experienceAccount['login_status'] : true;
                }
            } else {
                //未登陆
                return false;
            }
        }
        return true;
    }

    protected function product_name($company_id = 0){
        $companyModel = app::get('b2c')->model_for_config('company',b2c_util::get_club_db_conf());
        $productNameData= $companyModel->getCompanyProductName($company_id);
        if(empty($productNameData)){
            return '内购';
        }
        return $productNameData[0]['product_name'];
    }

    /*获取当前登录会员的会员等级*/
    function get_current_member_lv()
    {
        kernel::single('base_session')->start();
        if($member_id = $_SESSION['account'][pam_account::get_account_type('b2c')]){
           $member_lv_row = app::get("pam")->model("account")->db->selectrow("select member_lv_id from sdb_b2c_members where member_id=".intval($member_id));
           return $member_lv_row ? $member_lv_row['member_lv_id'] : -1;
        }
        else{
            return -1;
        }
    }
    function setSeo($app,$act,$args=null){
        // 触屏版暂时用pc端的seo信息
        $app = str_ireplace("wap_","site_",$app);
        $seo = kernel::single('site_seo_base')->get_seo_conf($app,$act,$args);
        $this->title = $seo['seo_title'];
        $this->keywords = $seo['seo_keywords'];
        $this->description = $seo['seo_content'];
        $this->nofollow = $seo['seo_nofollow'];
        $this->noindex = $seo['seo_noindex'];
    }//End Function

    function get_member_fav($member_id=null){
        $obj_member_goods = app::get('b2c')->model('member_goods');
        return $obj_member_goods->get_member_fav($member_id);
    }

    function get_member_like($member_id=null){
        $obj_member_goods = app::get('b2c')->model('member_goods');
        return $obj_member_goods->get_member_like($member_id);
    }

    function request_form(){
        $form = 'wap';
        if(kernel::single('base_component_request') -> is_browser_tag('weixin'))
            $form = 'weixin';
        if($_COOKIE['phoneType'] == 'app_ios' || $_COOKIE['phoneType'] == 'app_android')
            $form = $_COOKIE['phoneType'];
        return $form;
    }

    public function set_nextpage($next_page) {
        $cookie_domain = defined('SESSION_COOKIE_DOMAIN') ? SESSION_COOKIE_DOMAIN : null;
        if (!empty($next_page)) {
            $this->set_cookie('before_page', $next_page, time() + 60 * 30, "/", $cookie_domain);
        } else {
            $this->set_cookie('nextpage', "", time() - 3600, "/", $cookie_domain);
            $this->set_cookie('before_page', "", time() - 3600, "/", $cookie_domain);
        }
    }

    /**
     * @todo   [todo]
     *
     * @param  string $success_url  成功跳转url
     * @param  string $login_type   指定登录页登录方式
     * @param  string $fallback    自动登录失败回调地址
     * @return [type]
     */
    public function login_url($success_url = '',$login_type = 'code',$fallback = ''){

        // $url = $this -> gen_url(array('app'=>'b2c','ctl'=>'site_passport','act'=>'dispatch','full'=>1,'arg0' => base64_encode($success_url)));
        // $_cas = kernel::single('b2c_cas_api');
        // $param = array();
        // $param['loginCallback'] = ECSTORE_DOMAIN . '/openapi/cas/sync_login';
        // $param['partnerId'] = CAS_PARTNER_ID;
        // $param['redirect'] = $url;
        // return CAS_APP_GETWAY . '/v2/accessToken/checkLoginForward/' . '?' . http_build_query($param);

        $code = 1;
        switch ($login_type) {
            case 'code':
                $code = 1;
                break;
            case 'password':
                $code = 2;
		break;
            default:
                $code = 1;
                break;
        }
        $url = $this -> gen_url(array('app'=>'b2c','ctl'=>'wap_passport','act'=>'dispatch','full'=>1,'arg0' => base64_encode($success_url)));
        $param = array();
        $param['redirect'] = $url;
        $param['failUrl'] = '';
        $param['ngLoginFallback'] = $fallback;
        $param['from'] = 'c';
        $param['loginType'] = $code;
        $param['partnerId'] = CAS_PARTNER_ID;
        $param['loginCallback'] = ECSTORE_DOMAIN . '/openapi/cas/sync_login';
        return CAS_C_LOGIN_URL . '?' . http_build_query($param);
    }
    public function logout_url($url = null){
        $param = array();
        // 默认退出后跳转到登录页
        $param['redirect'] = empty($url) ? $this -> login_url() : $url;
        return CAS_LOGOUT_URL . '?' . http_build_query($param);
    }

    //bbs 登录
    public function getBbsKeyByGuid($guid,$rand = ''){
        return hash('sha256',$guid.$rand);
    }

    public function setBbsUserInfo($guid,$json = '',$rand='',$timeout = 100){
        $key = $this->getBbsKeyByGuid($guid,$rand);
        $RedisClient = new \Neigou\RedisClient();
        if(is_object($RedisClient->_redis_connection)){
            $r = $RedisClient->_redis_connection->setex($key,$timeout,$json);
            if(!$r) return FALSE;
            else return TRUE;
        }
        return FALSE;
    }

    public function getBbsUserInfoByToken($token){
        $RedisClient = new \Neigou\RedisClient();
        if(is_object($RedisClient->_redis_connection)){
            $value = $RedisClient->_redis_connection->get($token);
            if(!$value) return FALSE;
            else{
                $data = json_decode($value);
                return $data;
            }
        }
        return FALSE;
    }

	public function getMemberCompany($companyId,$memberId){
		$obj_company = app::get('b2c')->model('member_company');
		$results = $obj_company->getList('*', array('company_id'=>$companyId,'member_id'=>$memberId), 0, 1);
		return is_array($results) ? $results[0] : null;
	}

    /*
     * todo 检查场景
     */
    protected function CheckSessionStage(){
        $time   = time();
        if(!empty($_SESSION['stage_refresh_time']) && $_SESSION['stage_refresh_time'] + 300 < $time){
            $company_id = kernel::single("b2c_member_company")->get_cur_company();
            $companyModel = app::get('b2c')->model_for_config('company',b2c_util::get_club_db_conf());
            $company_tag_list   = $companyModel->GetCompanyTag($company_id);  //公司标签列表
            if(!empty($company_tag_list)){
                $tag_list = array();
                foreach ($company_tag_list as $tag){
                    $tag_list[] = $tag['tag'];
                }
                $_SESSION['USER_STAGE']['company_tag']  = $tag_list;
            }else if(isset($_SESSION['USER_STAGE']['company_tag'])){
                unset($_SESSION['USER_STAGE']['company_tag']);
            }
            $_SESSION['stage_refresh_time']  = $time;
        }
    }

    /**
     * @todo   根据指定链接跳转对应分享链接
     */
    public function sceneShare(){
        try{
            $_scene = kernel::single('b2c_scene_scene',$_SERVER);
            if($_scene -> canShareLink()){
                $company_id = kernel::single("b2c_member_company")->get_cur_company();
                $member = $this->get_current_member();
                $param = array(
                        'member_id' => $member['member_id'],
                        'company_id' => $company_id,
                        'channel' => $_SESSION['USER_STAGE']['channel'],
                        'context' => $_SESSION['USER_STAGE']['context'],
                    );
                $url = $_scene -> getShareLink($param);
                if($url){
                    $this->redirect($url);
                    exit;
                }
            }
        }catch(\Exception $e){
          // nothing
        }
    }

    public function sceneLogin($context_url = ''){
        // 渠道用户分享链接 获取渠道登录链接
        try{
            $_scene = kernel::single('b2c_scene_scene',$_SERVER);
            if($_scene -> isShareLink()){
                $url = $_scene -> getLoginLink($context_url);
                if($url){
                    $this -> redirect($url);
                    exit;
                }
            }
        }catch(\Exception $e){
          // nothing
        }
    }
}
