<?php

class weixin_ctl_site_wxin extends b2c_frontpage {


    function __construct($app){
        parent::__construct($app);
    }

    public function qr_pay() {
        $_getParams = $this->_request->get_params();
        $qr_url_encoded = $_getParams[0];
        $order_id = $_getParams[1];
        $cur_money = number_format($_getParams[2], 2, '.', '');
        $qr_url = base64_decode($qr_url_encoded);

        \Neigou\Logger::General("wxpay.qr_pay", array("action"=>"get_params", "order_id" => $order_id, "qr_url" => $qr_url));
        if (empty($qr_url) || empty($cur_money) || empty($order_id)) {
            $order_mdl = app::get("b2c")->model("orders");
            $order_data = $order_mdl->getRow("*", array('order_id'=>$order_id));
            $app = 'b2c';
            if($order_data['platform'] == 2){
                $app = 'jifen';
            }
            $failure_url = app::get('site')->router()->gen_url(array('app'=>$app,'ctl'=>'site_paycenter','act'=>'result_failed','full'=>1,'arg0'=>$order_id,'arg1'=>'result_placeholder'));
            $this->redirect($failure_url);
        }
        $obj_qrcode = kernel::single('weixin_qrcode');
        $image_id = $obj_qrcode->store($qr_url, 9);
        $image_src_url = base_storager::image_path($image_id);

        $this->pagedata['image_src_url'] = $image_src_url;
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['cur_money'] = $cur_money;

        $this->page("/site/qrpay.html");
    }

    public function order_paystatus() {
        $order_id = $_REQUEST['order_id'];
        if (empty($order_id)) {
            $this->splash('failed',null,"订单不存在",true);
        }
        $order_mdl = app::get("b2c")->model("orders");
        $obj_members = app::get('b2c')->model('members');
        $member_info = $obj_members->get_current_member();

        $pop_order = false;
        $order_data = $order_mdl->getRow("*", array('order_id'=>$order_id, "member_id"=>$member_info['member_id']));
        if (empty($order_data)) {
            $pop_order_data = kernel::single('b2c_service_order')->getOrderInfo($order_id);
            if(false == $pop_order_data){
                $this->splash('failed',null,"订单不存在",true);
            } else {
                //@TODO maojz pop下单 兼容ec订单的处理
                if($pop_order_data['system_code'] == 'jifen'){
                    $order_data['platform'] = 2;
                }
                //@TODO maojz pop下单 兼容ec订单的处理
                if($pop_order_data['pay_status'] == 2){
                    $order_data['pay_status'] =1;
                    $order_data['status'] = 'succ';
                }

                $pop_order = true;
            }

        }
        $app = 'b2c';
        if($order_data['platform'] == 2){
            $app = 'jifen';
        }
        if($order_data['pay_status'] == '1' && $order_data['status'] != 'dead'){
            if($pop_order == true){
                //检测是否是CPS订单 如果是 跳转到支付中心
                $order_info = app::get('ectools')->model('cps_order')->getRow('*',array('service_order_id'=>$order_id));
                if($order_info['id']>0){
                    $success_url = app::get('site')->router()->gen_url(array('app'=>$app,'ctl'=>'site_paycenter2','act'=>'cps_result','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));
                } else {
                    $success_url = app::get('site')->router()->gen_url(array('app'=>$app,'ctl'=>'site_paycenter2','act'=>'result_pay','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));
                }
            } else {
                $success_url = app::get('site')->router()->gen_url(array('app'=>$app,'ctl'=>'site_paycenter','act'=>'result_pay','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));

            }
            $this->splash('success',$success_url,app::get ('b2c')->_('订单支付成功'),true, null);
        }else{
            $this->splash('failed',null,app::get ('b2c')->_('订单未支付'),true);
        }
    }

}
