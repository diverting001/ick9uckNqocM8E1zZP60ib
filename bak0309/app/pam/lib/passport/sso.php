<?php
class pam_passport_sso implements pam_interface_passport{

    public function __construct(){
        kernel::single('base_session')->start();
        $this->init();
    }

    public function init(){
        if($ret = app::get('pam')->getConf('passport.'.__CLASS__)){
            return $ret;
        }else{
            $ret = $this->get_setting();
            $ret['passport_id']['value'] = __CLASS__;
            $ret['passport_name']['value'] = $this->get_name();
            $ret['shopadmin_passport_status']['value'] = 'true';
            $ret['site_passport_status']['value'] = 'true';
            $ret['passport_version']['value'] = '1.0';
            app::get('pam')->setConf('passport.'.__CLASS__,$ret);
            return $ret;
        }
    }

    public function get_name(){
        return app::get('pam')->_('用户CAS SSO');
    }

    public function get_login_form($auth,$appid,$view,$ext_pagedata=array()){
        return '';
    }

    public function login($auth,&$usrdata){
        $member = json_decode(base64_decode(b2c_passport_mailtmpl::decrypt($_POST['member'])),true);
        if($member && $member['member_id']){
            $usrdata['log_data'] = '用户ID [' . $member['member_id'] . ']' . '验证成功';
            $_account = app::get('pam') -> model('account');
            $account = $_account -> getRow('*',array('account_id' => $member['member_id']));
            if($account){
                $_POST['uname'] = $account['login_name'];
                $_SESSION['account']['guid'] = $member['guid'];
                return $member['member_id'];
            }
        }
        $usrdata['log_data'] = json_encode($member) . ' 登录失败';
        return false;
    }

    public function loginout($auth,$backurl="index.php"){
        unset($_SESSION['account'][$auth->type]);
        unset($_SESSION['last_error']);
    }

    public function get_data(){}

    public function get_id(){}

    public function get_expired(){}

    public function get_setting(){
        return array(
            'passport_id'=>array('label'=>app::get('pam')->_('通行证id'),'type'=>'text','editable'=>false),
            'passport_name'=>array('label'=>app::get('pam')->_('通行证'),'type'=>'text','editable'=>false),
            'shopadmin_passport_status'=>array('label'=>app::get('pam')->_('后台开启'),'type'=>'bool','editable'=>false),
            'site_passport_status'=>array('label'=>app::get('pam')->_('前台开启'),'type'=>'bool'),
            'passport_version'=>array('label'=>app::get('pam')->_('版本'),'type'=>'text','editable'=>false),
        );
    }
}