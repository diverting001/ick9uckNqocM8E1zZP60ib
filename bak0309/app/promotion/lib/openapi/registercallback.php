<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 16/4/22
 * Time: 下午2:18
 */

class promotion_openapi_registercallback {
    const weixinqy_register_money = 20;  // 微信企业号注册送券

    public function register_success() {
        $data = $_REQUEST['params'];
        $params = json_decode($data, true);
        \Neigou\Logger::Debug("promotion.action", array("action"=>"register_success.begin", "third_member_data"=>json_encode($params)));
        if(empty($params) ||
            !isset($params['member_id']) ||
            !isset($params['token'])) {
            echo "fail";
            return;
        }
        if (!kernel::single('b2c_safe_apitoken')->check_token($params, OPENAPI_TOKEN_SIGN)) {
            echo "fail";
            return;
        }

        $obj_mem = app::get('b2c')->model('members');
        $member_data = $obj_mem->getList("*",array('member_id'=>$params['member_id']));
        if (empty($member_data)) {
            return ;
        }

        // 微信企业号注册运营活动
        $third_member_mdl = app::get("b2c")->model('third_members');
        $third_member_data = $third_member_mdl->getRow("*", array('internal_id'=>$params['member_id'], 'channel'=>'weixin_qy'));
        if (!empty($third_member_data)) {
            $voucher_money = self::weixinqy_register_money;
            \Neigou\Logger::Debug("promotion.action", array("action"=>"register_success.weixinqy.begin", "third_member_data"=>json_encode($third_member_data)));

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

            $voucher_result  = kernel::single("promotion_voucher_memvoucher")->dispatch_voucher_for_weixinqy($params['member_id'], $voucher_money, $rule_id);
            if (!empty($voucher_result)) {
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
                    \Neigou\Logger::Debug("promotion.action", array("action"=>"register_success.weixinqy.message", "message_data"=>json_encode($message_data)));
                    $remotequeue_service->dispatchScriptCommandTaskSimpleNoReply('logic.wxqy.sendMessage', json_encode($message_data),array('node_neigou','normal'));
                }
            }
        }

        //给指定公司派发礼包
        $pkg = kernel::single("promotion_voucher_memvoucher");
        $pkg->applyMemberPkg($params['member_id'],$member_data[0]['company_id']);  
        
        echo "success";
        return;
    }
}