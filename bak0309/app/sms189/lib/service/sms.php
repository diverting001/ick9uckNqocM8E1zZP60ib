<?php
class sms189_service_sms{
    
    public function delivery($order_id){
        if( empty($order_id) ) return false;
        //获取相关信息
        $orders_mdl = app::get('ome')->model('orders');
        $orders_data = $orders_mdl->getlist('*',array('order_id'=>$order_id));
        
        $delivery_order_mdl = app::get('ome')->model('delivery_order');
        $delivery_mdl = app::get('ome')->model('delivery');
        
        $_d = $delivery_order_mdl->getlist('delivery_id',array('order_id'=>$order_id),0,1,'delivery_id DESC');
        $delivery_data = $delivery_mdl->getlist('*',array('delivery_id'=>$_d[0]['delivery_id']),0,1);

		
        if (empty($delivery_data[0]['logi_name']) ) {
        	$logi_id = $delivery_data[0]['logi_id'];
        	if ($logi_id > 0) {
        		
        	}else{
        		$selfwms = app::get('omeselfwms')->model('delivery')->dump(array('delivery_bn'=>$delivery_data[0]['delivery_bn']),'logi_id');
        		if (!empty($selfwms)) {
        			$logi_id = $selfwms['logi_id'];
        		}
        	}
        	if ($logi_id > 0) {
        		$corp_name = app::get('ome')->model('dly_corp')->dump(array('corp_id'=>$logi_id),'name');
        		
        		if (!empty($corp_name)) {
        			$delivery_data[0]['logi_name'] = $corp_name['name'];
        		}
        	}
        	
        }
        
        #订单号
        $order_bn = $orders_data[0]['order_bn'];
        $short_order_bn = substr($orders_data[0]['order_bn'], -5);
        #收货人手机
        $mobile = $delivery_data[0]['ship_mobile'];
        #收货人姓名
        $consignee = $delivery_data[0]['ship_name'];
        #店铺名称
        $shop_mdl = app::get('ome')->model('shop');
        $shop_data = $shop_mdl->getlist('*',array('shop_id'=>$orders_data[0]['shop_id']));
        $shop_name = $shop_data[0]['name'];
        #快递单号
        $pattrn = chr(239).chr(187).chr(191);
        $express = str_replace($pattrn, '', $delivery_data[0]['logi_no']);
        #物流公司
        $logistics = $delivery_data[0]['logi_name'];

        $filter['order_id'] = $order_id;
		
        $params = array(
        		'orderbn'=>$short_order_bn,
        		'yundanhao'=>$express,
        		'wuliu'=>$logistics,
        );
        
        if($mobile){
            //获取所有开启规则
            $rule_mdl = app::get('sms189')->model('rule');
            $rule_data = $rule_mdl->getlist('*',array('disabled'=>'false'));
            foreach($rule_data as $rule){
                //规则验证
                $result = false;
                $result = kernel::single('sms189_service_rule')->verify($rule['rule_id'],$filter);
                //符合规则,插入队列
                if($result == true){
                	$template_id = $this->get_sms_template_id($rule['tmpl_id']);
                	if ($template_id >= 1) {
                		$content = json_encode($params);
                		return  $this->add_sms_queue($order_bn,$mobile,$content,$template_id);
                	}
                }
            }
        }
        return false;

    }

    /**
     * 新增短信发送队列
     * @access public
     * @params $order_bn 订单号 
     * @params $mobile 接收方手机号
     * @params $content 短信内容
     * @return bool
     */
    public function add_sms_queue($order_bn,$mobile,$content,$template_id){

        $queue_data = array(
            'order_bn' => $order_bn,
            'mobile' => $mobile,
            'content' => $content,
        	'template_id'=>$template_id,
            'createtime' => time(),
        );
        $queue_mdl = app::get('sms189')->model('queue');

        if( $queue_mdl->insert($queue_data) ) return true;
        else return false;
    }
    
    /**
     * 获取电信短信模版ID
     * @access public
     * @params $tmpl_id 短信模版id
     * @return mix | false int
     */
    public function get_sms_template_id($tmpl_id){
    	if( empty($tmpl_id) ) return false;
    	$tmpl_mdl = app::get('sms189')->model('template');
    	$tmpl_data = $tmpl_mdl->getlist('*',array('tmpl_id'=>$tmpl_id));
    	return $tmpl_data[0]['template_id'];
    
    }

}