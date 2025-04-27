<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 16/8/12
 * Time: 下午2:47
 */
class promotion_weixinqy_voucher
{
    const weixinqy_register_money = 20;  // 微信企业号注册送券
    public function dispatchvoucher() {
        // 微信企业号注册运营活动
        $third_member_mdl = app::get("b2c")->model('third_members');
        // 脚本发放指定公司，公司id 1971
        $third_member_list_data = $third_member_mdl->db->select("select * from sdb_b2c_third_members where channel='weixin_qy' and external_bn like 'wxb3124c33eeca8ec2%'");
        $voucher_money = self::weixinqy_register_money;

        $db_conf = array(
            'MASTER'=>array(
                'HOST'=>DB_HOST,
                'NAME'=>DB_NAME,
                'USER'=>DB_USER,
                'PASSWORD'=>DB_PASSWORD
            )
        );
        $promotion_voucher_rules_model = app::get('promotion')->model_for_config('voucher_rules', $db_conf);
        $rule_id = $promotion_voucher_rules_model->get_rule_id('全场通用（特卖商品除外）');

        echo count($third_member_list_data);
        echo "\r\n";


        $dispatch_success = 0;
        $dispatch_failed = 0;
        foreach ($third_member_list_data as $third_member_data) {
            $member_mdl = app::get("b2c")->model('members');
            $member_info = $member_mdl->getRow("member_id, company_id", array('member_id'=>$third_member_data['internal_id']));
            if ($member_info['company_id'] == 68 || $member_info['company_id'] == 254) {
                continue;
            }
            \Neigou\Logger::General("promotion.action", array("action"=>"dispatchvoucher.weixinqy.begin", "third_member_data"=>json_encode($third_member_data)));
            $voucher_result  = kernel::single("promotion_voucher_memvoucher")->dispatch_voucher_for_weixinqy($third_member_data['internal_id'], $voucher_money, $rule_id);

            echo json_encode($voucher_result);
            echo "\r\n";

            if (!empty($voucher_result)) {
                $dispatch_success++;
                list($company_bn, $member_bn) = explode('-', $third_member_data['external_bn'], 2);

                if (!empty($company_bn) && !empty($member_bn)) {
                    $voucher_url=HD_DOMAIN."/Hongbao/enterprise?company_bn={$company_bn}";

                    $message_data = array(
                        $company_bn=>array(
                            "user_list"=>
                                array(
                                    $member_bn
                                ),
                            "title"=>"【点击领取】新员工福利券",
                            "description"=>"恭喜您获得1张{$voucher_money}元内购券，请查收！
有效期：15天
使用规则：可在购物、支付时抵扣等额现金，全场通用（特卖商品除外）",
                            "url"=>$voucher_url,
                        )
                    );
                    $remotequeue_service = kernel::service("remotequeue.service");
                    \Neigou\Logger::General("promotion.action", array("action"=>"dispatchvoucher.weixinqy.message", "message_data"=>json_encode($message_data)));
                    $remotequeue_service->dispatchScriptCommandTaskSimpleNoReply('logic.wxqy.sendMessage', json_encode($message_data),array('node_neigou','normal'));
                }
            } else {
                $dispatch_failed++;
            }

            echo "success {$dispatch_success}"."\r\n";
            echo "failed {$dispatch_failed}"."\r\n";
        }
    }
}