<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class b2c_o2o_productspay
{
    /**
     * @param $order_id
     * 支付成功，发o2o码
     */
    public function order_pay_finish($order){
        \Neigou\Logger::Debug("o2o_productspay",array('action'=>'order_pay_finish','order'=>$order));
        include ROOT_DIR.'/NG_PHP_LIB/AutoCIUtils.php';
        $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'neigou_unknow');
        $current = \Neigou\AutoCIUtils::getCurrentVersion($host);
        $data = array();
        $data['params'] = array('order' => $order);
        $data['class_obj'] = 'b2c_o2o_productspay';
        $data['method'] = 'do_order_pay_finish';
        $rpc_client = kernel::single('remotequeue_service');
        $res    = $rpc_client -> dispatchWebShellCallTask($host,json_encode($data),$current,array('node_neigou','realtime'));
        \Neigou\Logger::Debug("o2o_productspay.rpc",array('action'=>'order_pay_finish','order'=>$order,'iparam1' => $order['order_id'],'sparam1' => $res));
    }

    public function do_order_pay_finish($order){
        \Neigou\Logger::Debug("o2o_productspay.rpc.do.start",array('action'=>'order_pay_finish','order'=>$order));
        if(!$order || !$order['order_id']) return false;
        $model = app::get('b2c') -> model('o2o_products');
        $result = $model -> order_id_count_count($order['order_id']);
        if($result){
            \Neigou\Logger::Debug("o2o_productspay.rpc.o2o.send.err",array('action'=>'do_order_pay_finish','order'=>$order,'iparam1' => $order['order_id']));
            return false;
        }

        $result = $model -> get_third_order($order['order_id']);
        if($result){
            \Neigou\Logger::Debug("o2o_productspay.rpc.third.o2o.send.err",array('action'=>'do_order_pay_finish','order'=>$order,'iparam1' => $order['order_id']));
            return false;
        }

        $o2o_manage = kernel::single("b2c_o2o_o2omanage");
        $o2o_manage->sendCoupon($order);
        \Neigou\Logger::Debug("o2o_productspay.rpc.do",array('action'=>'order_pay_finish','order'=>$order,'iparam1' => $order['order_id']));
    }

}
