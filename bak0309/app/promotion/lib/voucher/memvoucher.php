<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/10/29
 * Time: 下午3:03
 */
// 用户代金券绑定关系管理
class promotion_voucher_memvoucher {
    // 两周后的23:59:59秒过期
    const valid_time = "15 days";
    const register_money = 20;
    const nonverified_login_money = 20;
    const invitation_money = 20;
    const weixinbind_money = 30;  // 微信绑定送券
    const weixinqy_register_money = 50;  // 微信企业号注册送券

    public function dispatch_voucher_for_register($member_id) {
        \Neigou\Logger::General("promotion.action", array("action"=>"dispatch_voucher_for_register", "member_id"=>$member_id));

        $members_model = app::get('b2c')->model('members');
        $company_members  = $members_model->getList('name,company_id,mobile',array('member_id'=>$member_id));
        // 两周后的23:59:59秒过期
        $valid_time = strtotime(date("Y-m-d",strtotime(self::valid_time)))-1;
        $data = array(
            'money' => self::register_money,
            'count' => 1,
            'valid_time' => $valid_time,
            'company_id' => $company_members[0]['company_id'],
            'op_id' => $member_id,
            'op_name' => $company_members[0]['mobile'],
            'comment' => '用户注册送代金券',
            'num_limit' => 10,
            'exclusive' => 0,
            'source_type'=>'register',
            'voucher_name'=>'注册代金券'
        );

        $service = kernel::service('voucher.service');

        $return_data = $service->create_mem_voucher($member_id, $data);
        if (empty($return_data)) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_register", "member_id"=>$member_id, "state"=>"failed"));
            return false;
        } else {
            $tmpl_data = addslashes(serialize(array('money'=>self::register_money)));
            $remindqueue_model = app::get('promotion')->model_for_config('remindqueue');
            $remindqueue_model->addToQueue($member_id,$company_members[0]['company_id'], 'login_voucher', 1, strtotime('60 day'), $tmpl_data);
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_register", "member_id"=>$member_id, "state"=>"success"));
            return true;
        }
    }

    public function dispatch_voucher_for_nonverified($member_id) {
        \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_nonverified", "member_id"=>$member_id));

        $members_model = app::get('b2c')->model('members');
        $member_info  = $members_model->getRow('name, company_id, mobile', array('member_id' => $member_id));

        $db_conf = array(
            'MASTER'=>array(
                'HOST'=>DB_HOST,
                'NAME'=>DB_NAME,
                'USER'=>DB_USER,
                'PASSWORD'=>DB_PASSWORD
            )
        );
        $promotion_voucher_rules_model = app::get('promotion')->model_for_config('voucher_rules', $db_conf);
        $rule_id = $promotion_voucher_rules_model->get_rule_id('满100元可用');
        if (!$rule_id) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_nonverified", "member_id"=>$member_id, "state"=>"failed", "reason"=>"no_rule_id"));
            return false;
        }
        $data = array(
            'money' => self::nonverified_login_money,
            'count' => 1,
            'valid_time' => strtotime(date("Y-m-d",strtotime(self::valid_time)))-1, // 两周后的23:59:59秒过期
            'company_id' => $member_info['company_id'],
            'op_id' => $member_id,
            'op_name' => $member_info['mobile'],
            'comment' => '非强认证公司用户登录送代金券',
            'num_limit' => 10,
            'exclusive' => 0,
            'source_type'=>'register',
            'voucher_name'=>'非强认证登录代金券'
        );

        $service = kernel::service('voucher.service');

        $return_data = $service->create_mem_voucher($member_id, $data);
        if (empty($return_data)) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_nonverified", "member_id"=>$member_id, "state"=>"failed"));
            return false;
        }

        $data = array(
            'title' => '大王',
            'money'=>self::nonverified_login_money,
            'rule' => '全场通用',
        );
        $tmpl_data = addslashes(serialize($data));
        $remindqueue_model = app::get('promotion')->model_for_config('remindqueue');
        $remindqueue_model->addToQueue($member_id,$member_info['company_id'], 'login_voucher', 1, strtotime('60 day'), $tmpl_data);

        \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_nonverified", "member_id"=>$member_id, "state"=>"success"));
        return true;
    }

    public function dispatch_voucher_for_invitation($member_id, $invited_member_id) {
        \Neigou\Logger::General("invitation.action", array("action"=>"dispatch_voucher_for_invitation", "member_id"=>$member_id, "invited_member_id"=>$invited_member_id));

        $members_model = app::get('b2c')->model('members');
        $member_info  = $members_model->getRow('name, company_id, mobile', array('member_id' => $member_id));
        if (!$member_info) {
            \Neigou\Logger::General("invitation.action", array("action"=>"dispatch_voucher_for_invitation", "member_id"=>$member_id, "invited_member_id"=>$invited_member_id, "state"=>"failed"));
            return false;
        }
        $data = array(
            'money' => self::invitation_money,
            'count' => 1,
            'valid_time' => strtotime(date("Y-m-d",strtotime(self::valid_time)))-1, // 两周后的23:59:59秒过期
            'company_id' => $member_info['company_id'],
            'op_id' => $member_id,
            'op_name' => $member_info['mobile'],
            'comment' => '用户邀请送代金券',
            'num_limit' => 10,
            'exclusive' => 0,
            'source_type'=>'invitation',
            'voucher_name'=>'邀请注册代金券'
        );

        $service = kernel::service('voucher.service');
        $return_data = $service->create_mem_voucher($member_id, $data);

        if (!$return_data) {
            \Neigou\Logger::General("invitation.action", array("action"=>"dispatch_voucher_for_invitation", "member_id"=>$member_id, "invited_member_id"=>$invited_member_id, "state"=>"failed"));
            return false;
        }

        $remindqueue_model = app::get('promotion')->model_for_config('remindqueue');
        $cond = "remind_action=\"invite_voucher\" AND member_id=".$member_id . " and company_id = " . $member_info['company_id'];
        $result = $remindqueue_model->getFromQueue($cond);

        if (!$result) {
            $tmpl_data = addslashes(serialize(array($invited_member_id)));
            $remindqueue_model->addToQueue($member_id,$member_info['company_id'], 'invite_voucher', 1, strtotime('60 day'), $tmpl_data);
        } else {
            $tmpl_data = unserialize($result[0]['tmpl_data']);
            $tmpl_data[] = $invited_member_id;

            $tmpl_data = addslashes(serialize($tmpl_data));
            $remindqueue_model->delFromQueue($result[0]['id']);
            $remindqueue_model->addToQueue($member_id,$member_info['company_id'], 'invite_voucher', 1, strtotime('60 day'), $tmpl_data);
        }

        \Neigou\Logger::General("invitation.action", array("action"=>"dispatch_voucher_for_invitation", "member_id"=>$member_id, "invited_member_id"=>$invited_member_id, "state"=>"success"));
        return true;
    }

    public function dispatch_voucher_for_weixinbind($member_id) {
        \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_weixinbind", "member_id"=>$member_id));

        $members_model = app::get('b2c')->model('members');
        $company_members  = $members_model->getList('name,company_id,mobile',array('member_id'=>$member_id));
        // 两周后的23:59:59秒过期
        $valid_time = strtotime(date("Y-m-d",strtotime(self::valid_time)))-1;

        $db_conf = array(
            'MASTER'=>array(
                'HOST'=>DB_HOST,
                'NAME'=>DB_NAME,
                'USER'=>DB_USER,
                'PASSWORD'=>DB_PASSWORD
            )
        );
        $promotion_voucher_rules_model = app::get('promotion')->model_for_config('voucher_rules', $db_conf);
        $rule_id = $promotion_voucher_rules_model->get_rule_id('满200使用(除午休特卖）');
        if (!$rule_id) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_nonverified", "member_id"=>$member_id, "state"=>"failed", "reason"=>"no_rule_id"));
            return false;
        }
        $data = array(
            'money' => self::weixinbind_money,
            'count' => 1,
            'valid_time' => $valid_time,
            'company_id' => $company_members[0]['company_id'],
            'op_id' => $member_id,
            'op_name' => $company_members[0]['mobile'],
            'comment' => '微信绑定送代金券',
            'num_limit' => 10,
            'exclusive' => 0,
            'rule_id' => $rule_id,
            'source_type'=>'weixinbind',
            'voucher_name'=>'微信绑定代金券',
            'voucher_nature'=>1
        );

        $service = kernel::service('voucher.service');

        $return_data = $service->create_mem_voucher($member_id, $data);
        if (empty($return_data)) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_weixinbind", "member_id"=>$member_id, "state"=>"failed"));
            return false;
        } else {
            $tmpl_data = addslashes(serialize(array('money'=>self::weixinbind_money)));
            $remindqueue_model = app::get('promotion')->model_for_config('remindqueue');
            $remindqueue_model->addToQueue($member_id,$company_members[0]['company_id'], 'weixinbind_voucher', 1, strtotime('60 day'), $tmpl_data);
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_weixinbind", "member_id"=>$member_id, "state"=>"success"));
            return true;
        }
    }

    public function dispatch_voucher_for_hongbao($member_id,$data){
        if(!isset($data['money']) || !isset($data['valid_time'])){
           return false; 
        }

        $members_model = app::get('b2c')->model('members');
        $company_members  = $members_model->getList('name,company_id,mobile',array('member_id'=>$member_id));

        $datas = array(
            'money' => $data['money'],
            'count' => 1,
            'valid_time' => $data['valid_time'],
            'company_id' => $company_members[0]['company_id'],
            'op_id' => $member_id,
            'op_name' => $company_members[0]['mobile'],
            'comment' => '订单分享抢红包',
            'num_limit' => 10,
            'exclusive' => 0,
            'rule_id' => $data['rule_id'],
            'source_type'=>'common',
            'voucher_name'=>'红包分享券'
        );

        \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_hongbao.0", "datas"=>json_encode($datas),"member_id" => $member_id, "state"=>"success"));
        $service = kernel::service('voucher.service');
        $return_data = $service->create_mem_voucher($member_id, $datas);
        \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_hongbao.1", "data"=>$return_data, "state"=>"success"));
        if(!empty($return_data))
            return true;
        return false;
    }

    // 微信企业号送券
    public function dispatch_voucher_for_weixinqy($member_id, $money ,$rule_id){
        if (empty($member_id) || empty($money)) {
            return false;
        }
        $members_model = app::get('b2c')->model('members');
        $company_members  = $members_model->getList('name,company_id,mobile',array('member_id'=>$member_id));

        // 两周后的23:59:59秒过期
        $valid_time = strtotime(date("Y-m-d",strtotime(self::valid_time)))-1;

        if (!$rule_id) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_weixinqy.result", "success"=>0,
                "member_id"=>$member_id, "reason"=>"no_rule_id"));
            return false;
        }
        $op_name =  !empty($company_members[0]['mobile']) ? $company_members[0]['mobile'] : $company_members[0]['name'];
        if (empty($op_name)) {
            $op_name = "b2cmember";
        }

        $datas = array(
            'money' => $money,
            'count' => 1,
            'valid_time' => $valid_time,
            'company_id' => $company_members[0]['company_id'],
            'op_id' => $member_id,
            'op_name' => $op_name,
            'comment' => '微信企业号注册送券',
            'num_limit' => 10,
            'exclusive' => 0,
            'rule_id' => $rule_id,
            'source_type'=>'register',
            'voucher_name'=>'微信企业号注册'
        );

        \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_weixinqy.voucherbefore",
            "datas"=>json_encode($datas),"member_id" => $member_id, "state"=>"success"));
        $service = kernel::service('voucher.service');
        $return_data = $service->create_mem_voucher($member_id, $datas);
        \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_weixinqy.voucherafter", "data"=>$return_data));
        if(!empty($return_data)) {
            \Neigou\Logger::Debug("promotion.action", array("action"=>"dispatch_voucher_for_weixinqy.result", "success"=>0,
                "member_id" => $member_id, "data"=>$return_data, "state"=>"success"));
            return true;
        }
        return false;
    }
    
    public function applyMemberPkg($member_id,$company_id){
        $pkg_obj = app::get('promotion') -> model('package');
        $pkg_company_data = $pkg_obj ->getPkgCompanyInfo($company_id);
        if(empty($pkg_company_data)){
           return false;
        }
        //派送优惠券
        $this->applyPkg($member_id,$company_id,$pkg_company_data[0]['pkg_rule_id']);
    }
    
    public function applyAllcompanyPkg(){
        $pkg_obj = app::get('promotion') -> model('package');
        $res = $pkg_obj->getAllPkgCompany();
        $company_list = array();
        foreach($res as $v){
            $company_list[] = $v['company_id'];
        }
        foreach($company_list as $v){
            $this->ApplyAllMemberPkg($v);
        }
    }
    
    //推送全体已注册成员
    private function ApplyAllMemberPkg($company_id){
        $pkg_obj = app::get('promotion') -> model('package');
        $pkg_company_data = $pkg_obj ->getPkgCompanyInfo($company_id);
        if(empty($pkg_company_data)){
            return false;
        }
        //公司所有成员
       $member_company_mdl = app::get('b2c')->model('member_company');
       $member_valid_company_list = $member_company_mdl->getList("*", array("company_id"=>$company_id, "state"=>0));               
       $all_members = array();
       foreach($member_valid_company_list as $value){
           $all_members[] = $value['member_id'];
       }
        //派券
        foreach($all_members as $v){
            $this->applyPkg($v,$company_id,$pkg_company_data[0]['pkg_rule_id']);
        }
    }
    
    //派送优惠券
    private function applyPkg($member_id,$company_id,$pkg_rule_id){                   
       //派送优惠券
       $service = kernel::service('voucher.service');
       $service ->dispatch_package_voucher($member_id,$company_id,$pkg_rule_id,'register');
       \Neigou\Logger::Debug("promotion.action", array("action"=>"promotion_openapi_pkg_apply", "member_id"=>$member_id,"company_id"=>$company_id,'pkg_rule_id'=>$pkg_rule_id));
    }           
}