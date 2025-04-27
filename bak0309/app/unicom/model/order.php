<?php
/**
 * 联通订单表
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
class unicom_mdl_order extends base_db_external_model
{
    // 数据库配置
    private $db_conf = array(
        'MASTER' => array(
        'HOST'     => DB_HOST,
        'NAME'     => DB_NAME,
        'USER'     => DB_USER,
        'PASSWORD' => DB_PASSWORD
        )
    );

    private $member_id  = 0;//联通定义唯一用户
    private $company_id = 0;//联通用户所属公司
    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app,$this->db_conf);
        $this->company_id = UNICOM_COMPANY_ID;
        $this->member_id = UNICOM_MEMBER_ID;
    }

    /**
     * 获取订单数据
     * @param  [type] $orderNo [description]
     * @return [type]          [description]
     */
    public function getInfo ($orderNo)
    {
        if (empty($orderNo)) {
            return array();
        }

        $sql = "SELECT * FROM unicom_order WHERE orderNo = '".$orderNo."'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }


    /** 获取订单详细信息
     *
     * @param $orderId
     * @return array|mixed
     * @author liuming
     */
    public function getInfoRawByPlatformOrderId ($orderId)
    {
        if (empty($orderId)) {
            return array();
        }

        $sql = "SELECT * FROM unicom_order WHERE platform_order_id = '".$orderId."'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }


    /**
     * 通过内部订单ID获取信息
     * @param  [type] $platformOrderId [description]
     * @return [type]                  [description]
     */
    public function getInfoByOrderId ($platformOrderId)
    {
        if (empty($platformOrderId)) {
            return array();
        }

        $sql = "SELECT * FROM unicom_order WHERE platform_order_id = '".$platformOrderId."'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }
    
    /**
     * 通过发货单号获取订单信息
     * @param  [type] $p_sendOrderNo [description]
     * @return [type]                  [description]
     */
    public function getInfoByPSendNo ($p_sendOrderNo)
    {
        if (empty($p_sendOrderNo)) {
            return array();
        }

        $sql = "SELECT * FROM unicom_order WHERE p_sendOrderNo = '".$p_sendOrderNo."'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    /**
     * 获取平台ID
     * @param  [type] $orderNo [description]
     * @return [type]          [description]
     */
    public function getOrderId ($orderNo)
    {
        if (empty($orderNo)) {
            return 0;
        }

        $sql = "SELECT platform_order_id FROM unicom_order WHERE orderNo = '{$orderNo}'";
        $result = $this->_db->selectrow($sql);
        return $result ? $result['platform_order_id'] : 0;
    }

    /**
     * 获取订单详情
     * @param  [type] $orderNo [description]
     * @return [type]          [description]
     */
    public function getDetailList ($orderNo)
    {
        if (empty($orderNo)) {
            return array();
        }

        $sql = "SELECT * FROM unicom_order_detail WHERE orderNo = '{$orderNo}'";
        $result = $this->_db->select($sql);

        return $result ? $result : array();
    }

    public function getDetailInfo ($unicom_order_detail_id)
    {
        if ($unicom_order_detail_id <= 0) {
            return array();
        }

        $sql = "SELECT * FROM unicom_order_detail WHERE unicom_order_detail_id = '{$unicom_order_detail_id}'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    /**
     * 保存订单
     * @param [type] $order_info [description]
     */
    public function addInfo ($order_info)
    {
        $info = $this->getInfo($order_info['orderNo']);
        if (!empty($info)) {
            return false;
        }
        
        //只筛选部分字段保存
        $data = array();
        $allow_columns = array(
            'platform_order_id','orderNo','p_sendOrderNo','orderState','hangupReason','comName','comCode','contractNo','ouName',
            'createName','createNameMobile','name','province','city','county','town','address','fullAddress',
            'zip','mobile','companyAddress','companyTel','city','phone','email','remark','paymentType',
            'invoiceType','invoiceState','orderPrice','orderNakedPrice','orderTaxPrice','bill_toer','bill_to_contact','bill_to_address','bill_to_email',
            'bill_taxcode','bill_address','bill_tel','bill_bank','bill_bankno','pay_status','ship_status','is_delivery',
        );
        
        foreach($order_info as $key=>$val){
            if(in_array($key,$allow_columns)){
                $data[$key] = $val;
            }
        }
        
        $data['member_id']     = $this->member_id;
        $data['company_id']    = $this->company_id;
        $data['last_modified'] = time();
        
        $fields = '`'. (implode('`,`', array_keys($data))). '`';
        $values = "'". (implode("','", $data)). "'";
        $sql  = "INSERT INTO unicom_order({$fields}) VALUES ({$values})";
        $result = $this->_db->exec($sql);
        $id = 0;
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $id;
    }

    public function updateInfo ($orderNo, $orderInfo)
    {
        if (empty($orderNo) || empty($orderInfo)) {
            return false;
        }
        $updateData = array();
        $orderInfo['last_modified'] = time();
        foreach ($orderInfo as $field => $value)
        {
            if ($field != 'unicom_order_id' && $field != 'orderNo')
            {
                $updateData[] = $field. '='. "'". $value. "'";
            }
        }

        $sql = "UPDATE unicom_order SET ". implode(',', $updateData). " WHERE orderNo = '{$orderNo}'";
        $result = $this->_db->exec($sql);

        return $result['rs'] ? true : false;
    }

    public function addDetail($info)
    {
        //只筛选部分字段保存
        $data = array();
        $allow_columns = array(
            'unicom_order_id',
            'orderNo',
            'sku',
            'p_sku',
            'goods_code',
            'goods_name',
            'spec',//数组格式
            'num',
            'price',
            'platform_price',
            'nakedPrice',
            'platform_nakedPrice',
            'taxPrice',
            'taxRate',
        );
        
        foreach($info as $key=>$val){
            if(in_array($key,$allow_columns)){
                if($key == 'spec'){
                    $data[$key] = str_replace("'",' ',serialize($val));
                }else{
                    $data[$key] = $val;
                }
            }
        }
        $data['update_time'] = time();
        
        $fields = '`'. (implode('`,`', array_keys($data))). '`';
        $values = "'". (implode("','", $data)). "'";
        $sql  = "INSERT INTO unicom_order_detail({$fields}) VALUES ({$values})";

        $id = 0;
        $result = $this->_db->exec($sql);
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $id;
    }

    public function getGoodsInfoByPlatformSku ($platform_order_id, $platform_sku)
    {
        $sql = "SELECT * FROM unicom_order_detail WHERE platform_order_id = '{$platform_order_id}' AND sku = '{$platform_sku}'";
        $result = $this->_db->selectrow($sql); 
        return $result ? $result : array();
    }

    public function getGoodsInfoBySku ($orderNo, $platform_sku)
    {
        $sql = "SELECT * FROM unicom_order_detail WHERE orderNo = '{$orderNo}' AND sku = '{$platform_sku}'";
        $result = $this->_db->selectrow($sql); 
        return $result ? $result : array();
    }

    public function insertReturnOrderInfo ($returnOrderInfo)
    {
        if (is_array($returnOrderInfo['untreadDetails'])) {
            $returnOrderInfo['untreadDetails'] = str_replace("'",' ',serialize($returnOrderInfo['untreadDetails']));
        }
        
        $data = array();
        $allow_columns = array(
            'untreadDetails',
            'orderNo',
            'untreadOrderNo',
            'untreadOrderState',
            'createName',
            'createNameMobile',
            'createTime',
            'untreadReason',
        );
        
        foreach($returnOrderInfo as $key=>$val){
            if(in_array($key,$allow_columns)){
                $data[$key] = $val;
            }
        }
        
        $data['update_time'] = time();
        $fields = '`'. (implode('`,`', array_keys($data))). '`';
        $values = "'". (implode("','", $data)). "'";
        $sql  = "INSERT INTO `unicom_order_return`({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $result['rs'] ? $id : 0;
    }

    /** 创建履约-快递信息表
     *
     * @param array $data
     * @return int
     * @author liuming
     */
    public function insertDeliveryExpressInfo($data = array()){
        $allowColumns = array(
            'deliveredId','orderNo','p_sendOrderNo','deliveredName','deliveredMobile','deliveredTime','remark','signer','signMobile','attachment'
        );

        foreach ($allowColumns as $k => $v){
            if (isset($data[$v])){
                $insertData[$v] = $data[$v];
            }
        }
        if (empty($insertData)) return false;

        $insertData['create_at'] = time();
        $fields = '`'. (implode('`,`', array_keys($insertData))). '`';
        $values = "'". (implode("','", $insertData)). "'";
        $sql  = "INSERT INTO `unicom_delivery_express_push_info`({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $result['rs'] ? $id : 0;
    }

    /** 获取快递推送信息
     *
     * @param string $orderNo
     * @return array|mixed
     * @author liuming
     */
    public function getDeliveryExpressInfoByOrderNo($orderNo = ''){
        $sql = "SELECT * FROM unicom_delivery_express_push_info WHERE `orderNo` = '{$orderNo}'";
        $result = $this->_db->selectrow($sql);
        return $result ? $result : array();
    }

    /** 获取快递推送信息 by p_send_order_no
     * @param string $pSendNo
     * @return array|mixed
     * @author liuming
     */
    public function getDeliveryExpressInfoByPSendOrderNo($pSendNo = ''){
        $sql = "SELECT * FROM unicom_delivery_express_push_info WHERE `p_sendOrderNo` = '{$pSendNo}'";
        $result = $this->_db->selectrow($sql);
        return $result ? $result : array();
    }

    /**
     * 搜索
     * @param  [type] $orderNo [description]
     * @return [type]         [description]
     */
    public function searchReturnOrder($untreadOrderNo)
    {
        $sql = "SELECT * FROM `unicom_order_return_relation_records` WHERE `untreadOrderNo` = '{$untreadOrderNo}'";
        $result = $this->_db->select($sql);

        return $result ? $result : array();
    }
    
    /**
     * 
     * @param type $untreadOrderState
     * @return type
     */
    public function getUnicomReturnList($untreadOrderState = 1)
    {
        $sql = "SELECT * FROM `unicom_order_return` WHERE `untreadOrderState` = '{$untreadOrderState}'";
        $result = $this->_db->select($sql);

        return $result ? $result : array();
    }

    /**
     * 联通退货申请映射内购退货申请记录表
     * @param  [type] $returnInfo   [description]
     * @param  [type] $platformInfo [description]
     * @param  [type] $orderNo      [description]
     * @return [type]               [description]
     */
    public function insertReturnOrder($platformInfo)
    {
        $platformInfo['create_time'] = time();
        $platformInfo['update_time'] = time();
        $fields = '`'. (implode('`,`', array_keys($platformInfo))). '`';
        $values = "'". (implode("','", $platformInfo)). "'";
        $sql  = "INSERT INTO `unicom_order_return_relation_records`({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);
        $id = 0;
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $id;
    }

    public function updateReturnInfo($untreadOrderNo,$info)
    {
        if (empty($untreadOrderNo) || empty($info)) {
            return false;
        }
        $updateData = array();
        $info['update_time'] = time();
        foreach ($info as $field => $value)
        {
            if ($field != 'untreadOrderNo')
            {
                $updateData[] = $field. '='. "'". $value. "'";
            }
        }

        $sql = "UPDATE `unicom_order_return_relation_records` SET ". implode(',', $updateData). " WHERE untreadOrderNo = '{$untreadOrderNo}'";
        $result = $this->_db->exec($sql);

        return $result['rs'] ? true : false;
    }

    /**
     * 搜索售后接口
     * @param  [type] $return_id [description]
     * @return [type]            [description]
     */
    public function getReturnInfoByid ($return_id)
    {
        $sql = "SELECT * FROM `unicom_order_return_relation_records` WHERE `return_id` = '{$return_id}'";
        $result = $this->_db->selectrow($sql); 
        return $result ? $result : array();
    }


    public function insertUnicomMessage ($type, $msgId, $info)
    {
        // @TODO 区分信息处理
        if (empty($type) || empty($msgId) || empty($info)) {
            return true;
        }
        
         //只筛选部分字段保存
        $data = array();
        $allow_columns = array(
            'orderNo',
            'sendOrderNo',
            'untreadOrderNo',
            'billNo',
            'provinceId',
            'stype',
            'time',
        );
        
        foreach($info as $key=>$val){
            if(in_array($key,$allow_columns)){
                $data[$key] = $val;
            }
            
        }

        $data['type'] = $type;
        $data['msgId'] = $msgId;
        $data['create_time'] = date('Y-m-d H:i:s', time());
        $data['update_time'] = time();
        $fields = '`'. (implode('`,`', array_keys($data))). '`';
        $values = "'". (implode("','", $data)). "'";
        $sql  = "INSERT INTO `unicom_message`({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);
        $id = 0;
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $id;
    }

    /**
     * 保存推送日志
     * @param type $orderNo
     * @param type $category
     * @param type $type
     * @param type $request
     * @param type $response
     * @return boolean
     */
    public function insertPushLog ($orderNo, $category, $type, $request, $response)
    {
        if (empty($type) || empty($orderNo)) {
            return true;
        }
        $info['orderNo']     = $orderNo;
        $info['category']        = $category;
        $info['type']        = $type;
        $info['request']     = str_replace("'",' ',$request);
        $info['response']    = str_replace("'",' ',$response);
        $info['time']        = date('Y-m-d H:i:s');
        $info['update_time'] = time();
        $fields = '`'. (implode('`,`', array_keys($info))). '`';
        $values = "'". (implode("','", $info)). "'";
        $sql  = "INSERT INTO `unicom_push_log`({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);
        $id = 0;
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $id;
    }
    
    /**
     * 获取推送日志
     */
    public function getPushLog($category,$type)
    {
        $sql = "SELECT * FROM unicom_push_log WHERE `category` = '{$category}' and `type`='{$type}'";
        $result = $this->_db->select($sql); 
        return $result ? $result : array();
    }
    
    /**
     * 获取已推送发货单列表
     */
    public function getDeliveryOrders($platform_order_id)
    {
        $sql = "SELECT * FROM unicom_delivery_order_push_info WHERE `platform_order_id` = '{$platform_order_id}'";
        $result = $this->_db->select($sql); 
        return $result ? $result : array();
    }
    
     /**
     * 获取推送失败订单重新推送
     */
    public function getPushFailDeliveryOrders($filter = array(),$limit = 100)
    {
        if(isset($filter['send_order_no'])){
            $str_sql = "`send_order_no` = '{$filter['send_order_no']}'";
        }elseif(isset($filter['p_send_order_no'])){
            $str_sql = "`p_send_order_no` = '{$filter['p_send_order_no']}'";
        }else{
            $str_sql = "`push_status` != 2";
        }
        $sql = "SELECT * FROM unicom_delivery_order_push_info WHERE `status` = 'NORMAL' and {$str_sql} order by id desc limit {$limit}";
        $result = $this->_db->select($sql); 
        return $result ? $result : array();
    }
    
    /**
     * 获取发货单信息
     */
    public function getDeliveryOrder($send_order_no)
    {
        $sql = "SELECT * FROM unicom_delivery_order_push_info WHERE `send_order_no` = '{$send_order_no}'";
        $result = $this->_db->selectrow($sql); 
        return $result ? $result : array();
    }
    
     /**
     * 获取发货单信息
     */
    public function getDeliveryOrderWithSku($send_order_no_with_sku)
    {
        $sql = "SELECT * FROM unicom_delivery_order_push_info_by_sku WHERE `send_order_no_with_sku` = '{$send_order_no_with_sku}'";
        $result = $this->_db->selectrow($sql); 
        return $result ? $result : array();
    }

    /** 通过sendOrderId获取订单信息
     *
     * @param $orderId
     * @return array|mixed
     * @author liuming
     */
    public function getDeliveryOrderPushListBySendOrderId($orderId){
        $sql = "SELECT * FROM unicom_delivery_order_push_info_by_sku WHERE `send_order_no` = '{$orderId}'";
        $result = $this->_db->select($sql);
        return $result ? $result : array();
    }

    public function getDeliveryOrderByOrderId($orderId)
    {
        $sql = "SELECT * FROM unicom_delivery_order_push_info_by_sku WHERE `platform_order_id` = '{$orderId}'";
        $result = $this->_db->selectrow($sql);
        return $result ? $result : array();
    }
    
    /**
     * 通过联通发货单号查询发货单信息
     * @param type $p_send_order_no
     * @return type
     */
    public function getDeliveryOrderByPson($p_send_order_no)
    {
        $sql = "SELECT * FROM unicom_delivery_order_push_info_by_sku WHERE `p_send_order_no` = '{$p_send_order_no}'";
        $result = $this->_db->selectrow($sql); 
        if(empty($result)){
            $sql = "SELECT * FROM unicom_delivery_order_push_info WHERE `p_send_order_no` = '{$p_send_order_no}'";
            $result = $this->_db->selectrow($sql); 
        }
        return $result ? $result : array();
    }

    /**
     * 新增的发货单
     * 
     * @param type $params
     */
    public function insertDeliveryOrder($params)
    {
        
        $ret = TRUE;
        foreach($params as $param){
            $info = array();
            $info['platform_order_id'] = $param['platform_order_id'];
            $info['send_order_no']    = $param['send_order_no'];
            $info['send_order_items']    = str_replace("'",' ',$param['send_order_items']);
            if(isset($param['status'])){
                $info['status'] = $param['status'];
            }
            if(isset($param['push_status'])){
                $info['push_status'] = $param['push_status'];
            }
            if(isset($param['p_send_order_no'])){
                $info['p_send_order_no'] = $param['p_send_order_no'];
            }
            $info['create_at'] = $info['update_at'] = time();
            $fields = '`'. (implode('`,`', array_keys($info))). '`';
            $values = "'". (implode("','", $info)). "'";
            $sql  = "INSERT INTO unicom_delivery_order_push_info({$fields}) VALUES ({$values})";
            $result = $this->_db->exec($sql);
            $id = 0;
            if ($result['rs']) {
                $id = $this->_db->lastinsertid();
            }
            
            if(!($id > 0)) $ret = FALSE; 
        }
        
        return $ret;
    }
    
    /**
     * 新增的发货单(每个SKU一单)
     * 
     * @param type $params
     */
    public function insertDeliveryOrderBySku($params)
    {
        
        $ret = TRUE;
        foreach($params as $param){
            $info = array();
            $info['platform_order_id'] = $param['platform_order_id'];
            $info['send_order_no_with_sku']    = $param['send_order_no_with_sku'];
            $info['send_order_no']    = $param['send_order_no'];
            $info['send_order_items']    = str_replace("'",' ',$param['send_order_items']);
            if(isset($param['status'])){
                $info['status'] = $param['status'];
            }
            if(isset($param['push_status'])){
                $info['push_status'] = $param['push_status'];
            }
            if(isset($param['p_send_order_no'])){
                $info['p_send_order_no'] = $param['p_send_order_no'];
            }
            $info['create_at'] = $info['update_at'] = time();
            $fields = '`'. (implode('`,`', array_keys($info))). '`';
            $values = "'". (implode("','", $info)). "'";
            $sql  = "INSERT INTO unicom_delivery_order_push_info_by_sku({$fields}) VALUES ({$values})";
            $result = $this->_db->exec($sql);
            $id = 0;
            if ($result['rs']) {
                $id = $this->_db->lastinsertid();
            }
            
            if(!($id > 0)) $ret = FALSE; 
        }
        
        return $ret;
    }
    
     /**
     * 更新发货单推送信息 By SKU
     * @param type $id
     * @param type $params
     * @return type
     */
    public function updateDeliverOrderBySku($id, $params)
    {
        $updateData = array();
        $params['update_at'] = time();
        
        foreach ($params as $field => $value)
        {
            if ($field != 'id')
            {
                $updateData[] = $field. '='. "'". $value. "'";
            }
        }

        $sql = "UPDATE unicom_delivery_order_push_info_by_sku SET ". implode(',', $updateData). " WHERE `id` = '{$id}'";
        
        $result = $this->_db->exec($sql);

        return $result['rs'] ? true : false;
    }
    /**
     * 更新发货单推送信息
     * @param type $id
     * @param type $params
     * @return type
     */
    public function updateDeliverOrder($id,$params)
    {
        $updateData = array();
        $params['update_at'] = time();
        
        foreach ($params as $field => $value)
        {
            if ($field != 'id')
            {
                $updateData[] = $field. '='. "'". $value. "'";
            }
        }

        $sql = "UPDATE unicom_delivery_order_push_info SET ". implode(',', $updateData). " WHERE `id` = '{$id}'";
        
        $result = $this->_db->exec($sql);

        return $result['rs'] ? true : false;
    }


    /** 获取2天之前没有推送成功的订单列表
     *
     * @return array|bool
     * @author liuming
     */
    public function getRetryOrderList ()
    {
        $currentTime = time();
        $findTime = $currentTime - 3600*24*2; //2天的时间差
        $sql = "SELECT * FROM `unicom_order_return_relation_records` WHERE `status` = 3 and platform_order_id like '202%' and create_time <= {$findTime}";
        $result = $this->_db->select($sql);
        return $result ? $result : array();
    }
}