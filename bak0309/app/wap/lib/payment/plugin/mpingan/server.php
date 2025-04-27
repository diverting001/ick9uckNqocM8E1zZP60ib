<?php
/**
 * 平安银行支付 notify 验证接口
 * User: chuanbin
 * Date: 2018/1/8
 * Time: 16:25
 */
class wap_payment_plugin_mpingan_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        $post = $GLOBALS["HTTP_RAW_POST_DATA"];
        $recv = json_decode($post,true);
        \Neigou\Logger::General('notify.mpingan.req', array('remark' => 'recv_param', 'post_data'=>$recv));
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_mpingan');
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['orderId'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('平安支付');
            $ret['pay_account'] = $mer_id;
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['amount']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['amount']/100;
            $ret['trade_no'] = $recv['paySeq'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = strtotime($recv['reqDate'].$recv['reqTime']);
            $ret['pay_app_id'] = "mpingan";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';

            $return_to_pingan['retCode'] = '0000';
            $return_to_pingan['retMsg'] = 'success';
            $return_to_pingan['sign'] = $this->genSign($return_to_pingan);
            echo json_encode($return_to_pingan);
            \Neigou\Logger::General('ecstore.notify.mpingan', array('remark' => 'trade_succ', 'data' => $ret));
        }else{
            $return_to_pingan['retCode'] = '0001';
            $return_to_pingan['retMsg'] = 'sign err';
            $return_to_pingan['sign'] = $this->genSign($return_to_pingan);
            echo json_encode($return_to_pingan);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mpingan',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;

    }

    /**
     * 退款通知
     */
    public function refund(){

        $post = $GLOBALS["HTTP_RAW_POST_DATA"];
        $recv = json_decode($post,true);
        if($this->is_return_vaild($recv)){
            //入库 TODO 额外字段添加
            $set['payment_id'] = $recv['orderId'];
            $set['payment'] = 'mpingan';
            $set['trade_no'] = $recv['paySeq'];
            $set['notify_time'] = time();
            if($recv['retCode']=='0000'){
                //退款成功
                $set['notify_status'] = 1;
                //校验金额是否一致
                app::get('ectools')->model('order_channel_refund')->save($set);
            } else {
                $set['notify_status'] = 0;
                app::get('ectools')->model('order_channel_refund')->save($set);
            }
            $return_to_pingan['retCode'] = '0000';
            $return_to_pingan['retMsg'] = 'success';
            echo json_encode($return_to_pingan);
            \Neigou\Logger::General('ecstore.refund.mpingan', array('remark' => 'trade_succ', 'data' => $recv));
        }else{
            $return_to_pingan['retCode'] = '0001';
            $return_to_pingan['retMsg'] = 'sign err';
            echo json_encode($return_to_pingan);
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.refund.mpingan',array('remark'=>'sign_err','data'=>$recv));
        }
    }

    /**
     * 平安银行生成签名方法
     * <功能详细描述>
     * @param $data
     * @return string
     */
    public function genSign($data) {
        $client_secret     = $this->getConf('salt','wap_payment_plugin_mpingan');  //签名密码（由内购网分配）
        ksort($data);   //进行排序
        $sign_arr = array();
        foreach ($data as $key => $value) {
            $sign_arr[] = $key . '=' . $value;
        }
        $sign_str = implode('&' , $sign_arr);   //client_id=102103f90901c29a798586cc64&external_user_id=test&name=测试用户1&surl=http://test.neigou.com/&time=1509372694
        $sign_str   = $sign_str.$client_secret;
        $sign = md5($sign_str); //462699cf49a5876592cd7703cc62ce13
        //按照文档顺序顺序排列好
        \Neigou\Logger::Debug('notify.paysign.mpingan',array('linkS'=>$sign_str));
        return $sign;
    }

    /**
     * 检验返回数据合法性
     * @param $param
     * @return mixed
     */
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $calc_sign = $this->genSign($param);
        if($sign==$calc_sign){
            return true;
        } else {
            \Neigou\Logger::General('notify.mnewstar.sign.err',array('sign'=>$calc_sign,'sign_req'=>$sign));
            return false;
        }

    }
}