<?php

define('ASYNC_CALL_SIGN','73n7s0zm5iihb6l4xuotjrc0qdkhwx1w');

class promotion_invite_register {

    function __construct( &$app ) {
        $this->app = $app;
    }

    /**
     * 注册后触发，邀请送券
     *
     * @param int $member_id
     * @return void
     */
    public function registerActive($member_id) {
        \Neigou\Logger::General("invitation.action", array("action"=>"registerActive", "member_id"=>$member_id));
        if (!$member_id) {
            return;
        }

        $obj_mem = app::get('b2c')->model('members');
        $member_info = $obj_mem->getRow("company_id",array('member_id'=>$member_id));

        // 判断公司是否开启强认证
        if (!$member_info || is_null($member_info['company_id'])) {
            \Neigou\Logger::General("invitation.action", array("action"=>"registerActive", "member_id"=>$member_id, "member_info"=>json_encode($member_info), "state"=>"failed"));
            return;
        }
        $obj_birthday = kernel::single('birthday_birthday');
        $is_verify = $obj_birthday->check_company_verify($member_info['company_id']);

        \Neigou\Logger::General("invitation.action", array("action"=>"registerActive", "member_id"=>$member_id, "company_id"=>$member_info['company_id'], "is_verify"=>($is_verify?"1":"0")));

        if ($is_verify) {

            $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] :
                (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

            $token = md5(sprintf("member_id=%s&sign=%s", $member_id, ASYNC_CALL_SIGN));
            $data=array(
                'member_id'=>$member_id,
                'token'=>$token,
            );
            $post_data = array(
                'callback_url'=>$host."/openapi/invitation/register_success",
                'data'=>json_encode($data)
            );

            \Neigou\Logger::General("invitation.action", array("action"=>"publish", "member_id"=>$member_id));    // member_id为受邀人的会员ID
            $remotequeue_service = kernel::service("remotequeue.service");
            $remotequeue_service->dispatchScriptCommandTaskSimpleNoReply('common.service.httpForward',json_encode($post_data),array('node_neigou','realtime'));
        }
    }

    /**
     * 强认证后触发，邀请送券
     *
     * @param int $member_id
     * @return void
     */
    public function reverifyActive($member_id) {
        $this->registerActive($member_id);
    }
}