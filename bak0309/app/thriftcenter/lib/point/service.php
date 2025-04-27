<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/9/22
 * Time: 下午8:21
 */


require_once __DIR__.'/../rpc/request/NGThrift/client/ThriftCenterClient.php';
class thriftcenter_point_service
{
    private $rate=100.00;
    /**
     * @param $point
     * @return float
     * 积点 现金比率
     */
    public  function point_total($point){

        return $point/$this->rate;//现金:积点  1:100
    }

    public function total_point($total){
        $number = $total*$this->rate;//现金:积点  1:100
        return $number;
    }

    /**
     * @param $order_id 订单id
     * @return mixed
     * 根据订单查询 积点，和积点 对应的现金
     */
    public function orderdetail_point($order_id){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'orderdetail_point','order_id'=>$order_id));
        $results=$this->memberQueryOrderPoint($order_id);
        $results=json_decode($results);
        $data=json_decode($results->data);
        if($data->point){
            $payment=$this->point_total($data->point);//对应的现金
            $response_data=array('payment'=>$payment,'point'=>$data->point);
            return json_encode($response_data);
        }else{
            return false;
        }
    }

    /**
     * @param $member_id 用户id
     * @param $payment 价格
     * @return string
     * 查询用户积点，算订单抵扣金额
     */
    public function use_order_total($member_id, $company_id,$payment){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'use_order_total','member_id'=>$member_id,'payment'=>$payment));
        $results=$this->memberQueryMemberPoint($member_id, $company_id);
        $results=json_decode($results);
        $data=json_decode($results->data);
        $point=0;
        $payment_point=0;
        $use_point=0;
        if($data){
            $point=$data->point;
            $payment_point=$this->point_total($data->point);
            $payment_point= min($payment,$payment_point);
            $use_point=$payment_point*$this->rate;
        }
        $response_data=array('member_point'=>$point,'use_point'=>$use_point,'payment_point'=>$payment_point,'rate'=>$this->rate);
        return json_encode($response_data);
    }
    /**
     * @param $member_id 用户ID
     * @param $point  积点
     * @return bool  返回现金
     * 订单创建之前算现金
     */
    public function cart_order_total($member_id, $company_id,$point){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'cart_order_total','member_id'=>$member_id,'point'=>$point));
        $results=$this->memberQueryMemberPoint($member_id, $company_id);
        $results=json_decode($results);
        $data=json_decode($results->data);
        if($data){
            $pointdata=$data->point;
            if($pointdata){//有积点
                 if(bccomp(intval(strval($point)),$pointdata,4)!=1){//能转换，返回现金
                       return $this->point_total($point);
                 }else{
                     \Neigou\Logger::Debug('c2c.point.calc',array('remark'=>'cart_order_total_fail','point_data'=>$pointdata,'point'=>$point,'thrift_data'=>$data));
                     return false;
                 }
            }else{//没有积点，或者积点为0
                return false;
            }
        }else{//没有数据
            return false;
        }
    }

    /**
     * @param $order_id 订单号
     * @param $member_id 用户
     * @param $point 积点
     * @return bool  返回现金
     * 创建订单
     */
    public function order_create($order_id,$member_id, $company_id,$point,$total_amount){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'order_create','order_id'=>$order_id,'member_id'=>$member_id,'point'=>$point));
        $results=$this->cart_order_total($member_id,$company_id, $point);
        if($results){
            $point_money= min($results, $total_amount);
            if($point_money){
                $point=$point_money*$this->rate;
                $create_results=$this->memberUsePoint($order_id,$member_id, $company_id,$point,$member_id,"创建订单".$order_id);
                $create_results=json_decode($create_results);
                if($create_results->code){
                    return false;
                }else{
                    return $point_money;
                }
            }else{
                return $point_money;
            }
        }else{
            return false;
        }
    }

    /**
     * c2c 订单锁定积分
     * @param $order_id 订单号
     * @param $member_id 用户
     * @param $point 积点
     * @return bool  返回现金
     * 创建订单
     */
    public function c2c_order_create($order_id,$member_id, $company_id,$point){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'order_create','order_id'=>$order_id,'member_id'=>$member_id,'point'=>$point));
        $results=$this->cart_order_total($member_id,$company_id, $point);
        if($results){
            $point=$results*$this->rate;
            $create_results=$this->memberUsePoint($order_id,$member_id, $company_id,$point,$member_id,"售卖积分订单：".$order_id." 冻结 ",'c2c');
            $create_results=json_decode($create_results);
            if($create_results->code){
                return false;
            }else{
                return true;
            }
        }else{
            return false;
        }
    }

    public function send_member_point($member_id,$company_id,$order_id,$to_member_id,$to_company_id){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'send_member_point','member_id'=>$member_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'    => $member_id,
            'company_id'      => $company_id,
            'order_id'      => $order_id,
            'reason'     => 'c2c_send',
            'to_member_id'     => $to_member_id,
            'to_company_id'     => $to_company_id,//920603
            'op_name'    => 'openapi',
            'memo'       => 'c2c 积分订单 '.$order_id
        );
        return $handler->BalancePointServer('c2c_sendPoint', json_encode($data));
    }




    /**
     * @param $member_id
     * @return string
     * 查询该用户，积点记录条数
     */
    public function memberQueryMemberHistoryPointCount($member_id, $company_id){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'memberQueryMemberHistoryPointCount','member_id'=>$member_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'=>$member_id,
        );
        if (!empty($company_id)) {
            $data['company_id'] = $company_id;
        }
        return $handler->BalancePointServer('memberQueryMemberHistoryPointCount', json_encode($data));

    }

    /**
     * @param $member_id
     * @return string
     * 查询该用户，积点记录条数
     */
    public function queryMemberHistoryListCountByGuid($guid = 0, $company_id){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'queryMemberHistoryListCountByGuid','member_id'=>$member_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'guid'=>$guid,
        );
        if (!empty($company_id)) {
            $data['company_id'] = $company_id;
        }
        return $handler->BalancePointServer('memberQueryMemberHistoryPointCountByGuid', json_encode($data));

    }

    /**
     * @param $member_id 用户ID
     * @param int $page    开始条数
     * @param int $pageNum   结束条数
     * @return string
     * 查询用户，积点记录，分页
     */
    public function memberQueryMemberHistoryPoint($member_id, $company_id,$page=0,$pageNum=20){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'memberQueryMemberHistoryPoint','member_id'=>$member_id,'page'=>$page,'pageNum'=>$pageNum));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'=>$member_id,
            'begin_row'=>$page,
            'rows'=>$pageNum
        );
        if (!empty($company_id)) {
            $data['company_id'] = $company_id;
        }
        return $handler->BalancePointServer('memberQueryMemberHistoryPoint', json_encode($data));
    }

    /**
     * @param $member_id 用户ID
     * @param int $page    开始条数
     * @param int $pageNum   结束条数
     * @return string
     * 查询用户，积点记录，分页
     */
    public function queryMemberHistoryListByGuid($guid = 0, $company_id,$page=0,$pageNum=20){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'queryMemberHistoryListByGuid','member_id'=>$member_id,'page'=>$page,'pageNum'=>$pageNum));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'guid'=>$guid,
            'begin_row'=>$page,
            'rows'=>$pageNum
        );
        if (!empty($company_id)) {
            $data['company_id'] = $company_id;
        }
        return $handler->BalancePointServer('memberQueryMemberHistoryPointByGuid', json_encode($data));
    }

    /**
     * @param $member_id  用户id
     * @return string
     * 查询该用户的总积点，已用积点，剩余积点
     */
    public function memberQueryMemberPoint($member_id, $company_id){
        //\Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'memberQueryMemberPoint','member_id'=>$member_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'=>$member_id
        );
        if (!empty($company_id)) {
            $data['company_id'] = $company_id;
        }
        return $handler->BalancePointServer('memberQueryMemberPoint', json_encode($data));
    }

    /**
     * @param $member_id  用户id
     * @param $company_id 公司id
     * @return string
     * 退还积点
     */
    public function refundPoint($member_id, $company_id){
        //\Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'refundPoint','member_id'=>$member_id,'company_id' => $company_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'=>$member_id,
            'company_id' => $company_id
        );
        return $handler->BalancePointServer('refundPoint', json_encode($data));
    }

    /**
     * @param $order_id  订单ID
     * @param $member_id  用户ID
     * @param $point      积点
     * @param $memo        备注
     * @return string
     * 创建订单，订单积分绑定
     */
    public function memberUsePoint($order_id,$member_id, $company_id,$point,$op_name,$memo,$type='sell'){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'memberUsePoint','order_id'=>$order_id,'member_id'=>$member_id,'point'=>$point,'op_name'=>$op_name,'memo'=>$memo));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'=>$member_id,
            'point'=>$point,
            'order_id'=>$order_id,
            'op_name'=>$op_name,
            'memo'=>$memo,
            'trade_type'=>$type
        );
        if (!empty($company_id)) {
            $data['company_id'] = $company_id;
        }
        return $handler->BalancePointServer('memberUsePoint', json_encode($data));
    }

    /**
     * @param $order_id  订单id
     * @param $type 取消订单，支付完成
     * @return string
     * 订单完成，取消修改积点
     */
    public function memberOrderStatusChanged($order_id,$type,$op_name,$memo){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'memberOrderStatusChanged','order_id'=>$order_id,'type'=>$type,'op_name'=>$op_name,'memo'=>$memo));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'order_id'=>$order_id,
            'status'=>$type,
            'op_name'=>$op_name,
            'memo'=>$memo
        );

        return $handler->BalancePointServer('memberOrderStatusChanged', json_encode($data));
    }

    /**
     * @param $order_id  订单id
     * @param $type 取消订单，支付完成
     * @return string
     * c2c 订单完成，取消修改积点
     */
    public function c2c_memberOrderStatusChanged($order_id,$type,$op_name,$memo){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'memberOrderStatusChanged','order_id'=>$order_id,'type'=>$type,'op_name'=>$op_name,'memo'=>$memo));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'order_id'=>$order_id,
            'status'=>$type,
            'op_name'=>$op_name,
            'memo'=>$memo,
            'trade_type'=>'c2c'
        );

        return $handler->BalancePointServer('memberOrderStatusChanged', json_encode($data));
    }

    /**
     * @param $order_id
     * @return string
     * 根据订单查询 积点
     */
    public function memberQueryOrderPoint($order_id){
        \Neigou\Logger::Debug("thriftcenter_point_service",array('action'=>'memberQueryOrderPoint','order_id'=>$order_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'order_id'=>$order_id
        );
        return $handler->BalancePointServer('memberQueryOrderPoint', json_encode($data));
    }

    /**
     * @param $member_id
     * @param $member_name
     * @param $card_number
     * @return string
     * 使用积点卡
     */
    public function usePointCard($member_id, $member_name, $company_id, $card_number) {
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'=>$member_id,
            'company_id'=>$company_id,
            'member_name'=>$member_name,
            'number'=>$card_number,
        );
        return $handler->BalancePointServer('memberusePointCard', json_encode($data));
    }

    /**
     * @param $card_number
     * @return string
     * 查询积点卡
     */
    public function queryPointCard($card_number) {
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'number'=>$card_number,
        );
        return $handler->BalancePointServer('memberqueryPointCard', json_encode($data));
    }

    public function transferPoint($member_id = 0,$merge_member_id = array()) {
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id'=>$member_id,
            'merge_member_id' => $merge_member_id
        );
        return $handler->BalancePointServer('transferPoint', json_encode($data));
    }

    /**
     * @todo   退款积分返还
     *
     * @return [type]
     */
    public function returnPoint($point,$order_id,$member_id,$memo = '',$member_name = '',$op_name = ''){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();

        $data = array();
        $data['point'] = $point;
        $data['order_id'] = $order_id;
        $data['member_id'] = $member_id;
        $data['member_name'] = $member_name ? $member_name : $member_id;
        $data['op_name'] = $op_name ? $op_name : $member_id;
        $data['memo'] = $memo;

        return $handler->BalancePointServer('memberReturnPoint', json_encode($data));
    }

    public function testasdf($data = array()){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        return $handler->BalancePointServer('companyAwardPoint', json_encode($data));
    }

    /** 批量返回积分
     * @param array $data[] = $arr['point']       = $v['point'];
                              $arr['order_id']    = $v['order_id'];
                              $arr['member_id']   = $member_id;
                              $arr['member_name'] = $member_name ? $member_name : $member_id;
                              $arr['op_name']     = $op_name ? $op_name : $member_id;
                              $arr['memo']        = "订单号：{$v['order_id']}退还{$v['point']}积点";
     * @return string
     * @author liuming
     */
    public function returnPointBetch($data = array()){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        return $handler->BalancePointServer('returnPointBetch', json_encode($data));
    }


    /** 查询该用户下所有公司相加的总积点，已用积点，剩余积点
     * @param array $data array(array(company_id=>1,member_id=>1),array())
     * @return string
     * @author liuming
     */
    public function getMemberPoint($data = array()){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        return $handler->BalancePointServer('memberQueryMemberAllPoint', json_encode($data));
    }


    public function findCompanyIdByMemberId($member_id = 0){
        return kernel::database()->select('SELECT company_id FROM sdb_b2c_member_company WHERE member_id = ' . $member_id);
    }

    /** 获取用户和公司的积分
     * @param $data
     * @return string
     * @author liuming
     */
    public function getUserAndCompanyPoint($data){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        return $handler->BalancePointServer('getUserAndCompanyPoint', json_encode($data));
    }

    /** 批量取消积分锁定
     * @param $data
     * @return string
     * @author liuming
     */
    public function unlockBetch($data){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        return $handler->BalancePointServer('orderStatusChangedBetch', json_encode($data));
    }

    /** 获取用户积分记录列表--酒钢
     * @param $data
     * @return string
     * @author liuming
     */
    public function getMemberMultiRecordList($data){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        return $handler->BalancePointServer('getMemberMultiCompanyRecordList', json_encode($data));
    }

    /** 获取用户积分记录列表
     * @param $data
     * @return string
     * @author liuming
     */
    public function getMemberRecordList($data){
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        return $handler->BalancePointServer('getUserPointRecordList', json_encode($data));
    }

}