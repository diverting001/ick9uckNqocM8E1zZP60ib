<?php 
/**
 * opapi 内部调用
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
class unicom_order_customer extends unicom_order_abstract
{

    /*
    * @todo 售后申请
    */
    public function returnsApply($param,&$msg)
    {
        /*
        $param = array(
            'order_id'        => $order_id,
            'member_id'       => $member_id,
            'product_id'      => $product_id,
            'product_bn'      => $product_bn,
            'product_num'     => $product_num,
            'after_type'      => in_array(array(1,2,3),$after_type) ? $after_type : 0, // 1->退换,2->换货,3->维修
            'status'          => $status,
            'customer_reason' => $customer_reason,
            'operator_type'   => $operator_type,
            'operator_name'   => $operator_name,
            'ship_name'       => $ship_name,
            'ship_mobile'     => $ship_mobile,
            
            //换货时,传递收货地址信息
            'ship_province' => $ship_province,
            'ship_city'     => $ship_city,
            'ship_county'   => $ship_county,
            'ship_town'     => $ship_town,
            'ship_addr'     => $ship_addr,
            'pic'           => $pics,//以英文逗号分隔图片id
        );
        */
       $ret = \Neigou\ApiClient::doServiceCall('aftersale', 'AfterSale/Create', 'v1', null,$param, array('debug'=>1));
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            $after_sale_bn = $ret['service_data']['data']['after_sale_bn'];
            return $after_sale_bn;
        }
        $msg = isset($ret['service_data']['error_msg'][0]) ? $ret['service_data']['error_msg'][0] : '售后申请服务异常';
        return false;
    }

    //获取售后申请数据
    public function getReturns($after_sale_bn)
    {
        $param = array(
            'after_sale_bn' => $after_sale_bn,
        );
        $ret = \Neigou\ApiClient::doServiceCall('aftersale', 'AfterSale/Get', 'v1', null,$param);
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code'] && !empty($ret['service_data']['data'])) {
            return $ret['service_data']['data'];
        }
        return null;
    }

    // 取消售后申请
    public function cancelReturns($after_sale_bn,$operator_name,$desc = '',$operator_type = 2)
    {
        $param = array(
            'status'        => 8,
            'after_sale_bn' => $after_sale_bn,
            'operator_name' => $operator_name,
            'operator_type' => $operator_type,//1-用户,2-pop,3-mis
            'desc'          => $desc,
        );
        $ret = \Neigou\ApiClient::doServiceCall('aftersale', 'AfterSale/Update', 'v1', null,$param);
        if('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code']){
            return true;
        }
        \Neigou\Logger::General("unicom.returns.cancelReturns", array("param"=>$param,"ret"=>$ret));
        return false;
    }

}