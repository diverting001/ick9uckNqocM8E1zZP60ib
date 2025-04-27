<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 16/6/23
 * Time: 下午1:33
 */

require_once __DIR__.'/../rpc/request/NGThrift/client/ThriftCenterClient.php';
// 福利套餐业务
class thriftcenter_welfarecard_package {
    public function get_member_package_count($member_id, $company_id = null) {
        \Neigou\Logger::Debug("thriftcenter_welfarecard_package",array('action'=>'get_member_package_count', 'member_id'=>$member_id, 'company_id'=>$company_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id' => $member_id,
            'company_id' => $company_id
        );
        $results = $handler->WelfareCardServer('welfarePackageController/getMemberWelfarePackageCount', json_encode($data));
        return $results;
//        if ($results['code'] == 0) {
//            return json_decode($results['data'], true);
//        } else {
//            return false;
//        }
    }
    public function get_member_package($member_id, $company_id = null) {
        \Neigou\Logger::Debug("thriftcenter_welfarecard_package",array('action'=>'get_member_package', 'member_id'=>$member_id, 'company_id'=>$company_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id' => $member_id,
            'company_id' => $company_id
        );
        $results = $handler->WelfareCardServer('welfarePackageController/getMemberWelfarePackage', json_encode($data));
        return $results;
//        if ($results['code'] == 0) {
//            return json_decode($results['data'], true);
//        } else {
//            return false;
//        }
    }
}