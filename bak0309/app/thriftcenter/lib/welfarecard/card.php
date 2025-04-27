<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 16/6/23
 * Time: 下午1:33
 */

require_once __DIR__.'/../rpc/request/NGThrift/client/ThriftCenterClient.php';
// 福利套餐业务
class thriftcenter_welfarecard_card {

    public function get_valid_card($card_password, $type_sign, &$message) {
        \Neigou\Logger::General("thriftcenter_welfarecard_card",array('action'=>'get_valid_card', 'card_password'=>$card_password));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'card_password' => $card_password,
        );
        if (!empty($type_sign)) {
            $data['type_sign'] = $type_sign;
        }
        $results = $handler->WelfareCardServer('welfareCardController/getValidWelfareCard', json_encode($data));
        return $results;
    }

    public function lock_card($card_password, &$message) {
        \Neigou\Logger::General("thriftcenter_welfarecard_card",array('action'=>'lock_card', 'card_password'=>$card_password));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'card_password' => $card_password,
        );
        $results = $handler->WelfareCardServer('welfareCardController/lockWelfareCard', json_encode($data));
        return $results;
    }

    public function use_card($card_password, $member_id, $company_id, &$message) {
        \Neigou\Logger::General("thriftcenter_welfarecard_card",array('action'=>'use_card', 'card_password'=>$card_password, 'member_id'=>$member_id, 'company_id'=>$company_id));
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data = array(
            'member_id' => $member_id,
            'company_id' => $company_id,
            'card_password' => $card_password,
        );

        //TODO 自定义短信渠道
        $data['message_channel'] = kernel::single("b2c_global_scope")->getCompanyMessageChannel($company_id);

        $results = $handler->WelfareCardServer('welfareCardController/useWelfareCard', json_encode($data));
        return $results;
    }

    /**
     * @param $card_number
     * @param $card_password
     * @param $type_sign
     * @param $message
     *
     * @return string
     */
    public function getWelfareCardRow($card_number, $card_password, $type_sign, &$message)
    {
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data    = array(
            'card_number'   => $card_number,
            'card_password' => $card_password,
        );
        if ( ! empty($type_sign)) {
            $data['type_sign'] = $type_sign;
        }
        $results = $handler->WelfareCardServer('welfareCardController/getWelfareCardRow', json_encode($data));
        \Neigou\Logger::General("thriftCenterGetWelfareCardRow",
            array('action' => 'getWelfareCardRow', 'card_number' => $card_number, 'card_password' => $card_password, 'results' => $results));

        return $results;
    }

    /**
     * @param $card_id
     * @param $message
     *
     * @return string
     */
    public function getWelfareCardUseRecordRow($card_id, &$message)
    {
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data    = array(
            'card_id' => $card_id,
        );
        $results = $handler->WelfareCardServer('welfareCardController/getWelfareCardUseRecordRow', json_encode($data));

        \Neigou\Logger::General("thriftCenterGetWelfareCardUseRecordRow",
            array('action' => 'getWelfareCardUseRecordRow', 'card_id' => $card_id, 'results' => $results));

        return $results;
    }

    /**
     *
     * 检测卡密及返回卡的所有套餐
     *
     * @param $card_password
     * @return false|string
     */
    public function checkCard($card_password)
    {
        $handler = new ThriftCenter\ThriftCenterClientAdapter();
        $data    = array(
            'card_password' => $card_password,
        );

        $results = $handler->WelfareCardServer('welfareCardController/checkCard', json_encode($data));

        return $results;
    }
}
