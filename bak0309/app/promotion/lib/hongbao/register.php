<?php

define('HONGBAO_ASYNC_CALL_SIGN','8fmd6apn7rsxhcygz2uw3eit1vkb0jo9ql45');

class promotion_hongbao_register {

    function __construct( &$app ) {
        $this->app = $app;
    }

    /**
     * 注册后触发，红包送钱
     *
     * @param int $member_id
     * @return void
     */
    public function registerActive($member_id) {
        if (!$member_id) {
            return;
        }
            
        \Neigou\Logger::General("hongbao.action", array("action"=>"hognbao.registerActive.0", "member_id"=>$member_id, "state"=>"success", "reason"=>""));

        $obj_mem = app::get('b2c')->model('members');
        $member_info = $obj_mem->getRow("company_id,mobile",array('member_id'=>$member_id));
        $mobile = $member_info['mobile'];

        \Neigou\Logger::General("hongbao.action", array("action"=>"hognbao.registerActive.1", "mobile"=>$mobile, "state"=>"success", "reason"=>""));

        $grab_obj = app::get('promotion') -> model('hongbao');
        $list = $grab_obj -> getGrabLogByMobile($mobile);
        \Neigou\Logger::General("hongbao.action", array("action"=>"hognbao.registerActive.2","list" => $list, "member_id"=>$member_id, "state"=>"success", "reason"=>""));

        if($list){

            $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] :
                (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

            $token = md5(sprintf("member_id=%s&sign=%s", $member_id, HONGBAO_ASYNC_CALL_SIGN));
            $data=array(
                'member_id'=>$member_id,
                'token'=>$token,
            );
            $post_data = array(
                'callback_url'=>$host."/openapi/hongbao/register_success",
                'data'=>json_encode($data)
            );

            \Neigou\Logger::General("hongbao.action", array("action"=>"hognbao.registerActive.3", "member_id"=>$member_id));    // member_id为受邀人的会员ID
			$remotequeue_service = kernel::service("remotequeue.service");
			$remotequeue_service->dispatchScriptCommandTaskSimpleNoReply('common.service.httpForward',json_encode($post_data),array('node_neigou','realtime'));

        }
    }

}