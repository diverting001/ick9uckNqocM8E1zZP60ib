<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/9/22
 * Time: 下午8:21
 */

require_once __DIR__.'/../rpc/request/NGThrift/client/VoucherClient.php';

class promotion_voucher_service
{
    // 查询代金券是否可用
    public function query_voucher_with_rule($voucher_number, $filter_data, $company_id, &$msg='') {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        if(!$this->check_company_voucher($voucher_number)){
            return false;
        }

        if(!$this->check_user_limit(1)){
            return false;
        }
        // $data=array(
        //     'voucher_number'=>$voucher_number
        // );

        // $result = $voucherClient->queryVoucherWithRule(json_encode($data), json_encode($filter_data));
        // $result_array = json_decode($result, true);
        // if ($result_array['code']==0 && isset($result_array['data'])) {
        //     return json_decode($result_array['data'], true);
        // }
        // $msg=$result_array['message'];
        // return false;

        // Voucher/GetWithRule
        $data=array(
            'voucher_number'=>$voucher_number
        );
        $query_params = array();
        $query_params['json_data'] = $data;
        $query_params['filter_data'] = $filter_data;

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/GetWithRule', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"query_voucher_with_rule", "request"=>$query_params, "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                return $res['service_data']['data'];
            }
        }
        $msg = $res['service_data']['message'];
        return false;

    }

    // 查询代金券列表是否可用，排他，限制数量等的使用
    public function query_voucher_list_with_rule(&$voucher_number_list, $filter_data, $company_id)
    {
        $ret = true;
        $total_money = 0;
        foreach ($voucher_number_list as $voucher_number) {
            $result_data = $this->query_voucher_with_rule($voucher_number, $filter_data, $company_id);
            if (!empty($result_data)) {
                $voucher = $result_data;
                $voucher['voucher_number'] = $voucher_number;
                $total_money +=$result_data['match_use_money'];
                $voucher_detail_list[] = $voucher;
            }
        }
        $voucher_number_list = $voucher_detail_list;
        if ($ret) {
            return $total_money;
        } else {
            return false;
        }
    }

    // 使用代金券
    public function use_voucher_with_rule($voucher_number_list, $member_id, $order_id, $use_money, $memo, $filter_data)
    {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        // if(!$this->check_company_voucher_list($voucher_number_list)){
        //     return false;
        // }

        // if(!$this->check_user_limit(count($voucher_number_list))){
        //     return false;
        // }

        // $data = array(
        //     'voucher_list'=>$voucher_number_list,
        //     'member_id'=>$member_id,
        //     'order_id'=>$order_id,
        //     'use_money'=>$use_money,
        //     'memo'=>$memo
        // );

        // $result_used = $voucherClient->useVoucherWithRule(json_encode($data), json_encode($filter_data));
        // $result_used_array = json_decode($result_used, true);
        // if (!empty($result_used_array) && $result_used_array['code']==0) {
        //     return json_decode($result_used_array['data'], true);
        // }
        // return false;

        if(!$this->check_company_voucher_list($voucher_number_list)){
            return false;
        }

        if(!$this->check_user_limit(count($voucher_number_list))){
            return false;
        }

        $data = array(
            'voucher_list'=>$voucher_number_list,
            'member_id'=>$member_id,
            'order_id'=>$order_id,
            'use_money'=>$use_money,
            'memo'=>$memo
        );

        $query_params = array();
        $query_params['data'] = $data;
        $query_params['filter_data'] = $filter_data;

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/UseWithRule', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"use_voucher_with_rule", "request"=>$query_params, "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                return $res['service_data']['data'];
            }
        }
        return false;
    }

    // 代金券状态变更
    public function exchange_status($voucher_number_list, $status, $memo="")
    {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        // $voucher_result = $voucherClient->exchangeStatus(json_encode($voucher_number_list), $status, $memo);
        // if (!empty($voucher_result) && $voucher_result['code']==0 ) {
        //     return true;
        // }
        // return false;

        // Voucher/exchangeStatus
        $query_params = array();
        $query_params['voucher_number_list'] = $voucher_number_list;
        $query_params['status'] = $status;
        $query_params['memo'] = $memo;

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/exchangeStatus', 'v1', null, $query_params, $extend_config);  
        \Neigou\Logger::Debug("voucher.new", array("action"=>"exchange_status", "request"=>$query_params, "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && $res['service_data']['data']){
                return true;
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    //查询规则信息
    public function getRule($rule_id){
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
//        $result = $voucherClient -> getRule($rule_id);
//       if($result){
//            $result = json_decode($result,true);
//            return $result['data'];
//        }
//        return array();
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/Rule/Get', 'v1', null, $rule_id, $extend_config);  
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS'){
               if($res['service_data']['data']){
                   return json_encode($res['service_data']['data']);
               }else{
                   return array();
               }               
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    //查询规则信息
    public function getRuleList($rule_ids){
        if(!is_array($rule_ids))
            $rule_ids = explode(',',$rule_ids);
        //获取到券的规则
        $extend_config = array();
        $voucher_rules = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/Rule/GetList', 'v1', null, array('rule_id_list'=>$rule_ids), $extend_config);  

        \Neigou\Logger::Debug("voucher.new", array("action"=>"getRuleList", "request"=>$rule_ids, "response" => $res));
        
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS' && $res['service_data']['data'] && $res['service_data']['error_code'] == 'SUCCESS'){
            $voucher_rules = $res['service_data']['data'];
        }
        return $voucher_rules;
    }

    //查询免邮规则信息
    public function getFreeshippingRule($rule_ids){
        if(!is_array($rule_ids))
            $rule_ids = explode(',',$rule_ids);
        //获取到券的规则
        $extend_config = array();
        $voucher_rules = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/FreeshippingRule/GetList', 'v1', null, array('rule_id_list'=>$rule_ids), $extend_config);

        \Neigou\Logger::Debug("voucher.freeshippingrule", array("action"=>"getRuleList", "request"=>$rule_ids, "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS' && $res['service_data']['data'] && $res['service_data']['error_code'] == 'SUCCESS'){
            $voucher_rules = $res['service_data']['data'];
        }
        return $voucher_rules;
    }

    // 查询订单使用代金券
    public function queryOrderVoucher($order_id)
    {
//        $voucherClient=new \VoucherServer\ThriftVoucherClient();
//        if (!$voucherClient) {
//            return false;
//        }
//        $voucher_result = $voucherClient->queryOrderVoucher($order_id);
//        $voucher_result = json_decode($voucher_result, true);
//
//        if ($voucher_result['code']==0 && isset($voucher_result['data'])) {
//            return json_decode($voucher_result['data'], true);
//        }
//        return false;

        if(!$order_id) return false;
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/GetOrderVoucher', 'v1', null, $order_id, $extend_config);  
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
               return $res['service_data']['data'];
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    // 查询用户使用过的代金券
    public function queryMemberUsedVoucher($member_id)
    {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        // $voucher_result = $voucherClient->queryMemberVoucher($member_id);
        // $voucher_result = json_decode($voucher_result, true);

        // if ($voucher_result['code']==0 && isset($voucher_result['data'])) {
        //     return json_decode($voucher_result['data'], true);
        // }
        // return false;

        // Voucher/MemberVoucher/Used
        if(!$member_id) return false;

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/MemberVoucher/Used', 'v1', null, $member_id, $extend_config);  

        \Neigou\Logger::Debug("voucher.new", array("action"=>"queryMemberUsedVoucher", "request"=>$member_id, "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
               return $res['service_data']['data'];
           }else{
               return false;
           }
        }else{
            return false;
        }

    }

    // 查询用户账户代金券
    public function queryMemberVoucher($member_id)
    {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        // $voucher_result = $voucherClient->queryMemberBindedVoucher($member_id);
        // $voucher_result = json_decode($voucher_result, true);

        // if ($voucher_result['code']==0 && isset($voucher_result['data'])) {
        //     return json_decode($voucher_result['data'], true);
        // }
        // return false;

        // Voucher/MemberVoucher/GetBinded
        if(!$member_id) return false;

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/MemberVoucher/GetBinded', 'v1', null, $member_id, $extend_config);  

        \Neigou\Logger::Debug("voucher.new", array("action"=>"queryMemberVoucher", "request"=>$member_id, "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
               return $res['service_data']['data'];
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    // 查询用户使用过的代金券
    public function queryMemberUsedVoucherByCompany($member_id, $company_id)
    {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        
//        $voucher_result = $voucherClient->queryMemberVoucher($member_id);
//        $voucher_result = json_decode($voucher_result, true);
//
//        if ($voucher_result['code']==0 && isset($voucher_result['data'])) {
//            $voucher_list_temp = json_decode($voucher_result['data'], true);
//            foreach($voucher_list_temp as $ind => $item) {
//                $voucher_list[] = $item;
//            }
//            return $voucher_list;
//        }
//        return false;
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/MemberVoucher/Used', 'v1', null, $member_id, $extend_config);  
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
               return $res['service_data']['data'];
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    // 查询用户账户代金券
    public function queryMemberVoucherByCompany($member_id, $company_id)
    {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        $_cas = kernel::single('b2c_member');
        $guid = $_cas -> _cas -> getGuidByMemberId($member_id);
        
//        $voucher_result = $voucherClient->queryMemberBindedVoucherByGuid($guid);
//        $voucher_result = json_decode($voucher_result, true);
//            if ($voucher_result['code']==0 && isset($voucher_result['data'])) {
//            $voucher_list_temp = json_decode($voucher_result['data'], true);
//            foreach($voucher_list_temp as $ind => $item) {
//                $voucher_list[] = $item;
//            }
//            return $voucher_list;
//        }
//        return false;
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/MemberVoucher/GetByGuid', 'v1', null, $guid, $extend_config);  
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
                foreach($res['service_data']['data'] as $ind => $item) {
                    $voucher_list[] = $item;
                }
                return $voucher_list;
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    public function queryOrderVoucherMoney($order_id) {
        $voucher_list = $this->queryOrderVoucher($order_id);

        $total_money = 0;
        if (!empty($voucher_list)) {
            foreach ($voucher_list as $voucher_item) {
                $total_money += $voucher_item['use_money'];
            }
        }
        return $total_money;
    }

    /**
     * @param $voucher_number
     * @return mixed
     * 查询是否存在公司ID
     */
    public  function  query_company($voucher_number){
        $db = kernel::database();
        $sql="select company_id FROM op_tx_voucher where voucher_number='".$voucher_number."'";
        \Neigou\Logger::General("check_company_voucher_db",array('db'=>$db,'sql'=>$sql));
        return $db->select($sql);
    }

    // check 代金劵是否是这个公司
    public function check_company_voucher_list(&$voucher_number_list)
    {
        $ret = true;
        $companyModel = app::get('b2c')->model_for_config('company',b2c_util::get_club_db_conf());
        $members_model = app::get('b2c')->model('members');
        foreach ($voucher_number_list as $voucher_number) {
            \Neigou\Logger::General("check_company_voucher_list",array("voucher_number"=>$voucher_number));
            $company= $this->query_company($voucher_number);
            if ($company) {
                $company_members  = $members_model->getList('company_id',array('member_id'=>$_SESSION['account']['member']));
                $companyconut = $companyModel->getCompanyByIds($company_members[0]['company_id'],$company[0]['company_id']);
                \Neigou\Logger::General("check_company_voucher_list_foreach",array("voucher_number"=>$voucher_number,"company"=>$company,"company_members"=>$company_members,"company"=>$company));
                if(!$companyconut[0]['count']){
                    $ret=false;
                    return $ret;
                }
            }
        }
        return $ret;
    }

    // check 代金劵是否是这个公司
    public function check_company_voucher($voucher_number)
    {
        $ret = true;
        $companyModel = app::get('b2c')->model_for_config('company',b2c_util::get_club_db_conf());
        $members_model = app::get('b2c')->model('members');
        $company= $this->query_company($voucher_number);
        \Neigou\Logger::General("check_company_voucher",array("voucher_number"=>$voucher_number,"company"=>$company));
        if ($company) {
            $company_members  = $members_model->getList('company_id',array('member_id'=>$_SESSION['account']['member']));
            $companyconut = $companyModel->getCompanyByIds($company_members[0]['company_id'],$company[0]['company_id']);
            \Neigou\Logger::General("check_company_voucher_",array("voucher_number"=>$voucher_number,"company"=>$company,"company_members"=>$company_members,"company"=>$company));
            if(!$companyconut[0]['count']){
                $ret=false;
                return $ret;
            }
        }
        return $ret;
    }

    // check 代金券用户使用限制，不同公司分别限制
    public function check_user_limit($add_count,$member_id = 0)
    {
        $member_id = $member_id ? $member_id : $_SESSION['account']['member'];
        $ret = true;
        // 限制张数的公司列表
        // key:company_id,value每个人限制的张数，不存在则不限制
        $company_list_permember = array(
        );
        $members_model = app::get('b2c')->model('members');
        $company_members  = $members_model->getList('company_id',array('member_id'=>$member_id));
        $limitcount=$company_list_permember[$company_members[0]['company_id']];
        if (!empty($limitcount)) {
            $tmp_used_voucher_list = $this->queryMemberUsedVoucher($member_id);
            if (!empty($tmp_used_voucher_list)) {
                foreach($tmp_used_voucher_list as $voucher_item) {
                    if ($voucher_item['status']=='finish' or
                        $voucher_item['status']=='lock') {
                        $valid_used_voucher_list[] = $voucher_item;
                    }
                }
                if (count($valid_used_voucher_list) + $add_count >$limitcount) {
                    $ret = false;
                }
            }
        }
        return $ret;
    }

    public function create_mem_voucher($member_id, $data) {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient ||
        //     empty($member_id) ||
        //     empty($data)) {
        //     return false;
        // }
//        $voucher_result = $voucherClient->createMemVoucher($member_id, json_encode($data));
//        $voucher_result = json_decode($voucher_result, true);
//
//        if ($voucher_result['code']==0 && isset($voucher_result['data'])) {
//            return true;
//        }
//        return false;

        if (empty($member_id) ||
            empty($data)) {
            return false;
        }
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/MemberVoucher/Create', 'v1', null, array('member_id'=>$member_id,'json_data'=>$data), $extend_config);  

        \Neigou\Logger::Debug("voucher.new", array("action"=>"create_mem_voucher", "request"=>array('member_id' => $member_id,'data' => $data), "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS'){
               return true;
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    // 查询用户账户代金券
    public function query_mem_binded_with_rule($member_id, $company_id, $filter_data)
    {

        $query_params = array();
        $query_params['member_id'] = $member_id;
        $query_params['json_data'] = $filter_data;
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/MemberVoucher/GetBindedWithRule', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"query_mem_binded_with_rule", "request"=>$query_params, "response" => $res));

        $voucher = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $voucher = $res['service_data']['data'];
            }
        }

        return $voucher;
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // if (!$voucherClient) {
        //     return false;
        // }
        // $voucher_result = $voucherClient->queryMemberBindedVoucherWithRule($member_id, json_encode($filter_data));
        // $voucher_result = json_decode($voucher_result, true);
        // if ($voucher_result['code']==0 && isset($voucher_result['data'])) {
        //     return json_decode($voucher_result['data'], true);
        // }
        // return false;
    }
    
     //派发礼包
    public  function dispatch_package_voucher($member_id,$company_id,$pkg_rule_id,$type){
        //发送礼包
        $apply_data = array(
            "member_id"=>$member_id,
            "company_id"=>$company_id,
            "pkg_rule_id"=>$pkg_rule_id,
            "source_type"=>$type
        );
        
//        $voucherClient=new \VoucherServer\ThriftVoucherClient();
//        $res = $voucherClient->applyVoucherPkg(json_encode($apply_data));   
//       print_r($res);
//        $extend_config = array('debug'=>true);
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/Package/Apply', 'v1', null, $apply_data, $extend_config);

        \Neigou\Logger::Debug("voucher.new", array("action"=>"dispatch_package_voucher", "request"=>$apply_data, "response" => $res));
        
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
                return $res['service_data']['data'];
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    // 创建用户免邮券
    public function create_member_freeshipping_coupon($create_params) {
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $create_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/createMemberCoupon",json_encode($create_params));
        // $create_result = json_decode($create_result, true);

        // if ($create_result['code']==0 && isset($create_result['data'])) {
        //     return true;
        // } else {
        //     return false;
        // }

        // Voucher/FreeShipping/MemberCoupon/Create
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/FreeShipping/MemberCoupon/Create', 'v1', null, $create_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"create_member_freeshipping_coupon", "request"=>$create_params, "response" => $res));

        $result = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                return true;
            }
        }

        return $result;
    }

    // 创建订单使用免邮券
    public function create_order_for_freeshipping_coupon($coupon_id, $member_id, $order_id, $filter_data, $company_id = null) {
        // $create_params = array(
        //     "coupon_id"=>$coupon_id,
        //     "member_id"=>$member_id,
        //     'company_id'=>$company_id,
        //     "order_id"=>$order_id,
        //     "filter_data"=>$filter_data,
        // );
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $create_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/createOrderForCoupon",json_encode($create_params));
        // $create_result = json_decode($create_result, true);

        // if ($create_result['code']==0 && isset($create_result['data'])) {
        //     return true;
        // } else {
        //     return false;
        // }

        $create_params = array(
            "coupon_id"=>$coupon_id,
            "member_id"=>$member_id,
            'company_id'=>$company_id,
            "order_id"=>$order_id,
            "filter_data"=>$filter_data,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/FreeShipping/CreateOrderForCoupon', 'v1', null, $create_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"create_order_for_freeshipping_coupon", "request"=>$create_params, "response" => $res));

        $result = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                return true;
            }
        }

        return $result;
    }

    // 取消订单使用免邮券
    public function cancel_order_for_freeshipping_coupon($order_id) {
        // $cancel_params = array(
        //     "order_id"=>$order_id,
        // );
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $cancel_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/cancelOrderForCoupon",json_encode($cancel_params));
        // $cancel_result = json_decode($cancel_result, true);

        // if ($cancel_result['code']==0 && isset($cancel_result['data'])) {
        //     return true;
        // } else {
        //     return false;
        // }
        // Voucher/FreeShipping/CancelOrderForCoupon

        $cancel_params = array(
            "order_id"=>$order_id,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/FreeShipping/CancelOrderForCoupon', 'v1', null, $cancel_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"cancel_order_for_freeshipping_coupon", "request"=>$cancel_params, "response" => $res));

        $result = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $result = $res['service_data']['data'] ? true : false;
            }
        }

        return $result;

    }

    // 支付订单使用免邮券
    public function finish_order_for_freeshipping_coupon($order_id) {
        // $finish_params = array(
        //     "order_id"=>$order_id,
        // );
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $finish_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/finishOrderForCoupon",json_encode($finish_params));
        // $finish_result = json_decode($finish_result, true);

        // if ($finish_result['code']==0 && isset($finish_result['data'])) {
        //     return true;
        // } else {
        //     return false;
        // }
        // Voucher/FreeShipping/FinishOrderForCoupon

        $finish_params = array(
            "order_id"=>$order_id,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/FreeShipping/FinishOrderForCoupon', 'v1', null, $finish_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"cancel_order_for_freeshipping_coupon", "request"=>$query_params, "response" => $res));

        $result = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $result = $res['service_data']['data'] ? true : false;
            }
        }

        return $result;
    }

    // 查询订单使用免邮券
    public function query_order_for_freeshipping_coupon($order_id) {
        // $query_params = array(
        //     "order_id"=>$order_id,
        // );
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $query_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/queryOrderCoupon",json_encode($query_params));
        // $query_result = json_decode($query_result, true);

        // if ($query_result['code']==0 && isset($query_result['data'])) {
        //     $freeshipping_list = json_decode($query_result['data'], true);
        //     return $freeshipping_list;
        // } else {
        //     return false;
        // }

        // Voucher/FreeShipping/GetOrderForCoupon
        $query_params = array(
            "order_id"=>$order_id,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/FreeShipping/GetOrderForCoupon', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"query_order_for_freeshipping_coupon", "request"=>$query_params, "response" => $res));

        $result = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $result = $res['service_data']['data'];
            }
        }

        return $result;

    }

    // 查询用户免邮券
    public function query_member_freeshipping_coupon($member_id, $coupon_type = null, $offset = null, $length = null) {
        $query_params = array(
            "member_id"=>$member_id,
        );

        if (!is_null($coupon_type)) {
            $query_params['coupon_type'] = $coupon_type;
        }

        if (is_numeric($offset) && is_numeric($length)) {
            $query_params['offset'] = $offset;
            $query_params['length'] = $length;
        }
//        $voucherClient=new \VoucherServer\ThriftVoucherClient();
//        $query_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/queryMemberCoupon",json_encode($query_params));
//        $query_result = json_decode($query_result, true);
//
//        if ($query_result['code']==0 && isset($query_result['data'])) {
//            $freeshipping_list = json_decode($query_result['data'], true);
//            return $freeshipping_list;
//        } else {
//            return false;
//        }
        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/Freeshipping/MemberCoupon/Get', 'v1', null, $query_params, $extend_config);
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
                return $res['service_data']['data'];
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    // 查询用户可用免邮券
    public function query_order_freeshipping_coupon_with_rule($member_id, $filter_data, $company_id = null) {
        // $query_params = array(
        //     "member_id"=>$member_id,
        //     "company_id"=>$company_id,
        //     "filter_data"=>$filter_data,
        // );
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $query_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/queryMemberCouponWithRule",json_encode($query_params));
        // $query_result = json_decode($query_result, true);

        // if ($query_result['code']==0 && isset($query_result['data'])) {
        //     $freeshipping_list = json_decode($query_result['data'], true);
        //     return $freeshipping_list;
        // } else {
        //     return false;
        // }

        $query_params = array(
            "member_id"=>$member_id,
            "company_id"=>$company_id,
            "filter_data"=>$filter_data,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/Freeshipping/MemberCoupon/GetWithRule', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"query_order_freeshipping_coupon_with_rule", "request"=>$query_params, "response" => $res));

        $voucher = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $voucher = $res['service_data']['data'];
            }
        }

        return $voucher;
    }

    // 查询订单是否可以使用该免邮券
    public function check_order_freeshipping_coupon_with_rule($coupon_id, $member_id, $filter_data, $company_id = null) {
        // $query_params = array(
        //     "coupon_id"=>$coupon_id,
        //     "member_id"=>$member_id,
        //     "company_id"=>$company_id,
        //     "filter_data"=>$filter_data,
        // );
        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $query_result = $voucherClient->freeShippingCouponServer("FreeShippingCoupon/queryCouponWithRule",json_encode($query_params));
        // $query_result = json_decode($query_result, true);

        // if ($query_result['code']==0 && isset($query_result['data'])) {
        //     $freeshipping_coupon_info = json_decode($query_result['data'], true);
        //     return $freeshipping_coupon_info;
        // } else {
        //     return false;
        // }

        $query_params = array(
            "coupon_id"=>$coupon_id,
            "member_id"=>$member_id,
            "company_id"=>$company_id,
            "filter_data"=>$filter_data,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/Freeshipping/GetCouponWithRule', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.new", array("action"=>"check_order_freeshipping_coupon_with_rule", "request"=>$query_params, "response" => $res));

        $result = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $result = $res['service_data']['data'] ? ture : false;
            }
        }

        return $result;
    }

    public function transferVoucher($member_id = 0,$to = array()) {
        if(!$member_id || !is_numeric($member_id) || !$to) return false;

        // $voucherClient=new \VoucherServer\ThriftVoucherClient();
        // $query_result = $voucherClient->transferVoucher($member_id,json_encode($to));
        // $query_result = json_decode($query_result, true);

        // if ($query_result['code']==0 && isset($query_result['data'])) {
        //     return true;
        // } else {
        //     return false;
        // }

        // Voucher/Transfer
        $query_params = array(
            "member_id"=>$member_id,
            "data"=>json_encode($to),
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/Transfer', 'v1', null, $query_params, $extend_config); 
        \Neigou\Logger::Debug("voucher.new", array("action"=>"transferVoucher", "request"=>$query_params, "response" => $res));

        $result = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $result = $res['service_data']['data'] ? ture : false;
            }
        }

        return $result;
    }

    /**
     * 获取用户免税券
     */
    public function getMemberDutyFreeList($member_id, $coupon_type = null, $offset = null, $length = null){
        $query_params = array(
            "member_id"=>$member_id,
        );

        if (!is_null($coupon_type)) {
            $query_params['coupon_type'] = $coupon_type;
        }

        if (is_numeric($offset) && is_numeric($length)) {
            $query_params['offset'] = $offset;
            $query_params['length'] = $length;
        }

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/DutyFree/MemberCoupon/Get', 'v1', null, $query_params, $extend_config);
        \Neigou\Logger::Debug("voucher.dutyfree", array("action"=>"getMemberDutyFreeList", "request"=>$query_params, "response" => $res));
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
           if($res['service_data']['error_code'] =='SUCCESS' && !empty($res['service_data']['data'])){
                return $res['service_data']['data'];
           }else{
               return false;
           }
        }else{
            return false;
        }
    }

    /**
     * @todo   获取免税规则信息
     */
    public function getDutyFreeRuleList($rule_ids = array()){
        if(!is_array($rule_ids))
            $rule_ids = explode(',',$rule_ids);
        //获取到券的规则
        $extend_config = array();
        $voucher_rules = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/DutyFreeRule/GetList', 'v1', null, array('rule_id_list'=>$rule_ids), $extend_config);

        \Neigou\Logger::Debug("voucher.dutyfree", array("action"=>"getDutyFreeRuleList", "request"=>$rule_ids, "response" => $res));

        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS' && $res['service_data']['data'] && $res['service_data']['error_code'] == 'SUCCESS'){
            $voucher_rules = $res['service_data']['data'];
        }
        return $voucher_rules;
    }

    /**
     * @todo   根据规则获取适用的免税券列表
     */
    public function getDutyFreeWithRule($member_id, $goods_list, $company_id = null){
        $query_params = array(
            "member_id"=>$member_id,
            "company_id"=>$company_id,
            "goods_list"=>$goods_list,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/DutyFree/MemberCoupon/GetWithRule', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.dutyfree", array("action"=>"getDutyFreeWithRule", "request"=>$query_params, "response" => $res));

        $voucher = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $voucher = $res['service_data']['data'];
            }
        }

        return $voucher;
    }

    /**
     * 根据条件获取可用免税券及可用金额
     */
    public function getDutyFreeMoneyWithCoupon($coupon ,$member_id, $goods_list, $company_id = null){
        $query_params = array(
            "coupon_ids"=>is_array($coupon)?$coupon:array($coupon),
            "member_id"=>$member_id,
            "company_id"=>$company_id,
            "goods_list"=>$goods_list,
        );

        $extend_config = array();
        $res = \Neigou\ApiClient::doServiceCall('voucher', '/Voucher/DutyFree/MemberCoupon/GetWithList', 'v1', null, $query_params, $extend_config); 

        \Neigou\Logger::Debug("voucher.dutyfree", array("action"=>"getDutyFreeMoneyWithCoupon", "request"=>$query_params, "response" => $res));

        $voucher = false;
        if($res['service_status'] == 'OK' && $res['service_result']['error_code'] == 'SUCCESS'){
            if($res['service_data']['error_code'] == 'SUCCESS' && isset($res['service_data']['data'])){
                $voucher = $res['service_data']['data'];
            }
        }
        return $voucher;
    }
}