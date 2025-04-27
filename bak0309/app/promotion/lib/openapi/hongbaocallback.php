<?php

define('HONGBAO_ASYNC_CALL_SIGN','8fmd6apn7rsxhcygz2uw3eit1vkb0jo9ql45');

class promotion_openapi_hongbaocallback{

    public function register_success() {
        $data = $_REQUEST['params'];
        $params = json_decode($data, true);

        \Neigou\Logger::General("hongbao.action", array("action"=>"hongbaocallback.register_success.0",'params' => $params, "state"=>"success", "reason"=>""));

        if (!$params || is_null($params['member_id']) || is_null($params['token'])) {
            echo "fail";
            return;
        }

        $token = md5(sprintf("member_id=%s&sign=%s", $params['member_id'], HONGBAO_ASYNC_CALL_SIGN));
        if ($token != $params['token']) {
            echo "fail";
            return;
        }

        $member_id = $params['member_id'];  // 受邀人的会员ID
        $members_model = app::get('b2c')->model('members');
        $member_info  = $members_model->getRow('name,mobile', array('member_id' => $member_id));
        if (!$member_info) {
            echo "fail";
            return;
        }

        $mobile = $member_info['mobile'];

        \Neigou\Logger::General("hongbao.action", array("action"=>"hongbaocallback.register_success.1",'member_id' => $member_id,'mobile' => $mobile, "state"=>"success", "reason"=>""));

        $grab_obj = app::get('promotion') -> model('hongbao');
        $list = $grab_obj -> getGrabLogByMobile($mobile);
        $grab = array();
        if(!$list){
            echo "fail";
            return;
        }

        $voucher_obj = kernel::single('promotion_voucher_memvoucher');
        foreach ($list as $k => $v) {
            $valid_time = strtotime("+7 days",$v['create_time']);
            if($valid_time <= time())
                continue;
            $data = array();
            $data['money'] = $v['money'];
            $data['valid_time'] = $valid_time;

            $rule_id = $grab_obj -> getRuleIdByShareId($v['share_id']);
            if($rule_id)
                $data['rule_id'] = $rule_id;

            \Neigou\Logger::General("hongbao.action", array("action"=>"hongbao.update_and_voucher", "state"=>"success", "reason"=>"","data" => $v));
            $voucher_obj -> dispatch_voucher_for_hongbao($member_id, $data); 
            $grab_obj -> updateGrabInfoByGrabId($v['grab_id'],$member_id); 
        }

        echo "success";
        return;

    }

}