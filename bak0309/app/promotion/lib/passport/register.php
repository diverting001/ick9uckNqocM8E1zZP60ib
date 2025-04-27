<?php


class promotion_passport_register {

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

        \Neigou\Logger::General("promotion_passport.action", array("action"=>"promotion_passport.registerActive", "member_id"=>$member_id, "state"=>"success", "reason"=>""));

        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] :
            (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');

        $data=array(
            'member_id'=>$member_id,
        );
        $data['token'] = kernel::single('b2c_safe_apitoken')->generate_token($data, OPENAPI_TOKEN_SIGN);
        
        $post_data = array(
            'callback_url'=>$host."/openapi/promotionregister/register_success",
            'data'=>json_encode($data)
        );

        $remotequeue_service = kernel::service("remotequeue.service");
        $remotequeue_service->dispatchScriptCommandTaskSimpleNoReply('common.service.httpForward',json_encode($post_data),array('node_neigou','realtime'));
        \Neigou\Logger::General("promotion_passport.action", array("action"=>"promotion_passport.registerActiveComplete", "member_id"=>$member_id));    // member_id为受邀人的会员ID                
    }

}