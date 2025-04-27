<?php

class weixin_ctl_wap_wxin extends wap_controller {


    function __construct($app){
        parent::__construct($app);
    }

    function index(){
        $this->wechatCheck = kernel::single('wap_weixin_wechatCheck');
        if( $this->wechatCheck->checkSignature($_GET["signature"], $_GET["timestamp"], $_GET["nonce"]) ){
            echo $_GET["echostr"];
            exit;
        }
        $this->responseMsg();

    }

    public function responseMsg(){
        //get post data, May be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr)){

                $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
                $fromUsername = $postObj->FromUserName;
                $toUsername = $postObj->ToUserName;
                $keyword = trim($postObj->Content);
                $time = time();
                $textTpl = "<xml>
                            <ToUserName><![CDATA[%s]]></ToUserName>
                            <FromUserName><![CDATA[%s]]></FromUserName>
                            <CreateTime>%s</CreateTime>
                            <MsgType><![CDATA[%s]]></MsgType>
                            <Content><![CDATA[%s]]></Content>
                            <FuncFlag>0</FuncFlag>
                            </xml>";
                if(!empty( $keyword ))
                {
                    $msgType = "text";
                    $contentStr = "Welcome to wechat world!";
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                }else{
                    echo "Input something...";
                }

        }else {
            echo "";
            exit;
        }
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

            $failure_url = app::get('wap')->router()->gen_url(array('app'=>$app,'ctl'=>'wap_paycenter','act'=>'result_failed','full'=>1,'arg0'=>$order_id,'arg1'=>'result_placeholder'));
            $this->redirect($failure_url);
        }
        $obj_qrcode = kernel::single('weixin_qrcode');
        $image_id = $obj_qrcode->store($qr_url);
        $image_src_url = base_storager::image_path($image_id);
        $this->pagedata['image_src_url'] = $image_src_url;
        $this->pagedata['order_id'] = $order_id;
        $this->pagedata['cur_money'] = $cur_money;

        $this->page("/wap/qrpay.html", true);
    }

    public function order_paystatus() {
        $order_id = $_REQUEST['order_id'];
        if (empty($order_id)) {
            $this->splash('failed',null,"订单不存在",'','',true);
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
                $success_url = app::get('wap')->router()->gen_url(array('app'=>$app,'ctl'=>'wap_paycenter2','act'=>'result_pay','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));

            } else {
                $success_url = app::get('wap')->router()->gen_url(array('app'=>$app,'ctl'=>'wap_paycenter','act'=>'result_pay','full'=>1,'arg0'=>$order_id,'arg1'=>'true'));

            }
            //判断如果是CPS
            $order_info = app::get('ectools')->model('cps_order')->getRow('*',array('service_order_id'=>$order_id));
            if($order_info['id']>0){
                $success_url = $this->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'cps_result','arg0'=>$order_id));
            }
            $this->splash('success',$success_url,app::get ('b2c')->_('订单支付成功'),'','',true, null);
        }else{
            $this->splash('failed',null,app::get ('b2c')->_('订单未支付'),'','',true);
        }
    }

}
