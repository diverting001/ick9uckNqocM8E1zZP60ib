<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
/**
* 该类是系统基本的验证类，必须实现 pam_interface_passport 这个接口
*/
class pam_passport_token implements pam_interface_passport{

	/**
	* 构造方法,初始化配置信息
	*/
    function __construct(){
        kernel::single('base_session')->start();
        $this->init();
    }
    /**
	* 获取配置信息
	* @return array 返回配置信息数组
	*/
    function init(){
        if($ret = app::get('pam')->getConf('passport.'.__CLASS__)){
            return $ret;
        }else{
            $ret = $this->get_setting();
            $ret['passport_id']['value'] = __CLASS__;
            $ret['passport_name']['value'] = $this->get_name();
            $ret['shopadmin_passport_status']['value'] = 'false';
            $ret['site_passport_status']['value'] = 'true';
            $ret['passport_version']['value'] = '1.5';
            app::get('pam')->setConf('passport.'.__CLASS__,$ret);
            return $ret;
        }
    }
	/**
	* 获取认证方式名称
	* @return string 返回名称
	*/
    function get_name(){
        return app::get('pam')->_('LOGIN_TOKEN用户登录');
    }
	/**
	* 生成认证表单,包括用户名,密码,验证码等input
	* @param object $auth pam_auth 对象
	* @param string $appid app_id
	* @return string 返回HTML页面
	*/
    function get_login_form($auth, $appid, $view, $ext_pagedata=array()){
        $render = app::get('pam')->render();
        $internal_array=array(
            'login_token'=>$ext_pagedata['login_token'],
        );
        $real_submit_url = $auth->get_callback_url(__CLASS__);
        $render->pagedata['real_submit_url']    = $real_submit_url;
        $render->pagedata['internal_array']    = $internal_array;
        return $render->fetch('wap/passport/auto_login.html',$appid);
//        $internal_array=array(
//            'login_token'=>$ext_pagedata['login_token'],
//        );
//
//        $real_submit_url = $auth->get_callback_url(__CLASS__);
//
//        // 简单的form的自动提交的代码。
//        header("Content-Type: text/html;charset=utf-8");
//        $strHtml ="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
//		<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\" dir=\"ltr\">
//		<head>
//		<title>登录中...</title>
//		</head><body><div>正在进行自动登录，请稍后...</div>";
//        $strHtml .= '<form action="' . $real_submit_url . '" method="POST" name="login_form" id="login_form">';
//        foreach($internal_array as $key => $val)
//        {
//            $strHtml.="<input type='hidden' name='".$key."' value='".$val."'>";
//        }
//        $strHtml .= '<input type="submit" name="btn_login" value="'.app::get('ectools')->_('登录').'" style="display:none;" />';
//        $strHtml .= '</form><script type="text/javascript">
//						window.onload=function(){
//							document.getElementById("login_form").submit();
//						}
//					</script>';
//        $strHtml .= '</body></html>';
//        return $strHtml;

    }

    private function parseLoginTokenData(&$usrdata){
        $login_token = $_POST['login_token'];
        $login_token_decoded = base64_decode($login_token);
        if (empty($login_token_decoded) || !is_string($login_token_decoded) || strlen($login_token_decoded) > 256){
            $usrdata['log_data'] = app::get('pam')->_('验证失败！(E01)');
            $_SESSION['error'] = app::get('pam')->_('无效的login_token');
            return false;
        }

        /* @var base_sharedkvstore $shared_kv */
        $shared_kv = kernel::single('base_sharedkvstore');
        $login_token_data = $shared_kv->tokenHelper_AtomGetAndDestroy('token-logintoken', $login_token);

        if ($login_token_data === false){
            $usrdata['log_data'] = app::get('pam')->_('验证失败！(E02)');
            $_SESSION['error'] = app::get('pam')->_('无法操纵的login_token');
            return false;
        }

        return $login_token_data;
    }

	/**
	* 认证用户名密码以及验证码等
	* @param object $auth pam_auth对象
	* @param array $usrdata 认证提示信息
	* @return bool|int返回认证成功与否
	*/
    function login($auth,&$usrdata)
    {
        $login_token_data = $this->parseLoginTokenData($usrdata);
        if (false === $login_token_data){
            return false;
        }

        $login_type = $login_token_data['login_type'];
        switch($login_type){
            case 'channel_grant':
            case 'channel_union_login':

                $rows = app::get('pam')->model('account')->getList('*',array(
                    'account_id'=>$login_token_data['internal_user_id'],
                    'account_type' => $auth->type,
                    'disabled' => 'false',
                ),0,1);

                break;
        }

        //if($objs = kernel::servicelist('pam_login_data_pre')){
        //    foreach ($objs as $obj){
        //        if (method_exists($obj,'get_login_name')){
        //            $obj->get_login_name($_POST);
        //        }
        //    }
        //}

		//$password_string = pam_encrypt::get_encrypted_password($_POST['password'],$auth->type,array('login_name'=>$_POST['uname']));
        //if (!$password_string) {
        //    $usrdata['log_data'] = app::get('pam')->_('你输入的密码和账号不匹配');
        //    $_SESSION['error'] = app::get('pam')->_('你输入的密码和账号不匹配');
        //    $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
        //    return false;
        //}
//
        //if(!$_POST['uname'] || ($_POST['password']!=='0' && !$_POST['password']))
        //{
        //    $usrdata['log_data'] = app::get('pam')->_('验证失败！');
        //    $_SESSION['error'] = app::get('pam')->_('验证失败！');
        //    $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
        //    return false;
        //}

        //$rows = app::get('pam')->model('account')->getList('*',array(
        //'login_name'=>$_POST['uname'],
        //'login_password'=>$password_string,
        //'account_type' => $auth->type,
        //'disabled' => 'false',
        //),0,1);

        if($rows[0]){
            if($_POST['remember'] === "true") setcookie('pam_passport_basic_uname',$_POST['uname'],time()+365*24*3600,'/');
            else setcookie('pam_passport_basic_uname','',0,'/');
            $usrdata['log_data'] = app::get('pam')->_('用户').$rows[0]['login_name'].app::get('pam')->_('验证成功！');
            unset($_SESSION['error_count'][$auth->appid]);
			if(substr($rows[0]['login_password'],0,1) !== 's')
			{
                $pam_filter = array(
                    'account_id'=>$rows[0]['account_id']
                );
				$string_pass = md5($rows[0]['login_password'].$rows[0]['login_name'].$rows[0]['createtime']);
				$update_data['login_password'] = 's'.substr($string_pass,0,31);
				app::get('pam')->model('account')->update($update_data,$pam_filter);
			}

            if (!empty($login_token_data['internal_company_id'])){
                $_SESSION['LOGIN_TMP']['COMPANY']['UID'] = $rows[0]['account_id'];
                $_SESSION['LOGIN_TMP']['COMPANY']['COMPANY_ID'] = $login_token_data['internal_company_id'];
                $_SESSION['LOGIN_TMP']['COMPANY']['FORCE'] = $login_token_data['force_switch_company'];
                //公司channel
                $member_company_info = kernel::single("b2c_member_company")->get_valid_company_forlogin($rows[0]['account_id'], $login_token_data['internal_company_id']);
                //设置是否为特殊公司标识
                $is_speciall_company    = in_array($login_token_data['internal_company_id'],explode(',',NEIGOU_SPECIALL_COMPANY_IDS));
                $is_speciall_channel    = in_array($member_company_info['channel'],explode(',',NEIGOU_SPECIALL_CHANNEL_BN));
                $_SESSION['is_baidu']   = $is_speciall_channel || $is_speciall_company?'true':'false';
            }

            //返回渠道
            $chnnel_back    = json_decode(NEIGOU_CHANNEL_BACK,true);
            if(isset($chnnel_back[$login_token_data['source_channel']])){
                foreach ($chnnel_back[$login_token_data['source_channel']] as $channel_key  => $channel_val){
                    $_SESSION['channel_back'][$channel_key]   = $channel_val;
                }
            }else{
                $_SESSION['channel_back']   = '';
            }

            $_SESSION['LOGIN_TMP']['CHANNEL'] = $login_token_data['source_channel'];
            $_SESSION['LOGIN_TMP']['GRANT_TYPE'] = $login_token_data['login_type'];
            return $rows[0]['account_id'];
        }
        else{
            $usrdata['log_data'] = app::get('pam')->_('用户').$_POST['uname'].app::get('pam')->_('验证失败！');
            $_SESSION['error'] = app::get('pam')->_('TOKEN登录失败');
            $_SESSION['error_count'][$auth->appid] = $_SESSION['error_count'][$auth->appid]+1;
            return false;
        }
    }
    /**
	* 退出相关操作
	* @param object $autn pam_auth对象
	* @param string $backurl 跳转地址
	*/
    function loginout($auth,$backurl="index.php"){
        unset($_SESSION['account'][$auth->type]);
        unset($_SESSION['last_error']);
        #Header('Location: '.$backurl);
    }

    function get_data(){
    }

    function get_id(){
    }

    function get_expired(){
    }

    /**
	* 得到配置信息
	* @return  array 配置信息数组
	*/
    function get_config(){
        $ret = app::get('pam')->getConf('passport.'.__CLASS__);
        if($ret && isset($ret['shopadmin_passport_status']['value']) && isset($ret['site_passport_status']['value'])){
            return $ret;
        }else{
            $ret = $this->get_setting();
            $ret['passport_id']['value'] = __CLASS__;
            $ret['passport_name']['value'] = $this->get_name();
            $ret['shopadmin_passport_status']['value'] = 'false';
            $ret['site_passport_status']['value'] = 'true';
            $ret['passport_version']['value'] = '1.5';
            app::get('pam')->setConf('passport.'.__CLASS__,$ret);
            return $ret;
        }
    }
    /**
	* 设置配置信息
	* @param array $config 配置信息数组
	* @return  bool 配置信息设置成功与否
	*/
    function set_config(&$config){
        $save = app::get('pam')->getConf('passport.'.__CLASS__);
        if(count($config))
            foreach($config as $key=>$value){
                if(!in_array($key,array_keys($save))) continue;
                $save[$key]['value'] = $value;
            }
            $save['shopadmin_passport_status']['value'] = 'true';

        return app::get('pam')->setConf('passport.'.__CLASS__,$save);

    }
   /**
	* 获取finder上编辑时显示的表单信息
	* @return array 配置信息需要填入的项
	*/
    function get_setting(){
        return array(
            'passport_id'=>array('label'=>app::get('pam')->_('通行证id'),'type'=>'text','editable'=>false),
            'passport_name'=>array('label'=>app::get('pam')->_('通行证'),'type'=>'text','editable'=>false),
            'shopadmin_passport_status'=>array('label'=>app::get('pam')->_('后台开启'),'type'=>'bool','editable'=>false),
            'site_passport_status'=>array('label'=>app::get('pam')->_('前台开启'),'type'=>'bool'),
            'passport_version'=>array('label'=>app::get('pam')->_('版本'),'type'=>'text','editable'=>false),
        );
    }




}
