<?php

define('ASYNC_CALL_SIGN','73n7s0zm5iihb6l4xuotjrc0qdkhwx1w');

class promotion_openapi_invitecallback {

    public function register_success() {
        $data = $_REQUEST['params'];
        $params = json_decode($data, true);

        if (!$params || is_null($params['member_id']) || is_null($params['token'])) {
            \Neigou\Logger::General("invitation.action", array("action"=>"invitecallback", "invite_result"=>0, "reason"=>"params error"));
            echo "fail";
            return;
        }

        $token = md5(sprintf("member_id=%s&sign=%s", $params['member_id'], ASYNC_CALL_SIGN));
        if ($token != $params['token']) {
            \Neigou\Logger::General("invitation.action", array("action"=>"invitecallback", "invite_result"=>0, "reason"=>"token error"));
            echo "fail";
            return;
        }

        $member_id = $params['member_id'];  // 受邀人的会员ID
        $members_model = app::get('b2c')->model('members');
        $member_info  = $members_model->getRow('name, email', array('member_id' => $member_id));
        if (!$member_info) {
            \Neigou\Logger::General("invitation.action", array("action"=>"invitecallback", "invited_member_id"=>$member_id, "invite_result"=>0, "reason"=>"invitee_info error"));
            echo "fail";
            return;
        }

        $name = $member_info['name'];   // 受邀人的姓名
        $member_invite_model = app::get('b2c')->model('member_invite');
        $member_invite_info = $member_invite_model->getRow('member_id, state', array('invited_email'=>$member_info['email']));
        if (!$member_invite_info || $member_invite_info['state'] == 'true') {
            \Neigou\Logger::General("invitation.action", array("action"=>"invitecallback", "invited_member_id"=>$member_id, "invite_result"=>0, "reason"=>"inviter_info error"));
            echo "fail";
            return;
        }

        $result = kernel::single('promotion_voucher_memvoucher')->dispatch_voucher_for_register($member_id);  // 给受邀人送注册券
        \Neigou\Logger::General("invitation.action", array("action"=>"dispatch_voucher_for_register", "member_id"=>$member_id, "state"=>($result?"success":"failed")));
        if (!$result) {
            echo "fail";
            return;
        }
        $result = kernel::single('promotion_voucher_memvoucher')->dispatch_voucher_for_invitation($member_invite_info['member_id'], $member_id); // 给邀请人送邀请券
        if (!$result) {
            echo "fail";
            return;
        }

        $cur_time = time();
        $data = array('finish_time'=>$cur_time, 'state'=>'true');
        $member_invite_model->update($data, array('invited_email'=>$member_info['email']));
        \Neigou\Logger::General("invitation.action", array("action"=>"invitecallback", "invite_member_id"=>$member_invite_info['member_id'], "invited_member_id"=>$member_id, "invite_result"=>1));

        echo "success";
        return;
    }

}