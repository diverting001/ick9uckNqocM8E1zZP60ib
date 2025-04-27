<?php
/**
 * 民生纽斯达支付 notify 验证接口
 * User: chuanbin
 * Date: 2018/7/16
 * Time: 11:25
 */
class wap_payment_plugin_mminshengyixiang_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_mminshengyixiang');
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['order_id'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('民生易享支付');
            $ret['pay_account'] = $recv['card_id'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['txn_amt']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['txn_amt']/100;
            $ret['trade_no'] = $recv['trans_no'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = $recv['txn_time'];
            $ret['pay_app_id'] = "mminshengyixiang";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            echo 'success';
            \Neigou\Logger::General('ecstore.notify.mminshengyixiang', array('remark' => 'trade_succ', 'data' => $ret));
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mminshengyixiang',array('remark'=>'sign_err','post_data'=>$recv));
        }
        return $ret;

    }

    /**
     * 检验返回数据合法性
     * @param $param
     * @return mixed
     */
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $str = $this->getConf('sign_key','wap_payment_plugin_mminshengyixiang').$this->_create_link_string($param,true,true).$this->getConf('sign_key','wap_payment_plugin_mminshengyixiang');
        if($sign==strtoupper(md5($str))){
            return true;
        } else {
            \Neigou\Logger::General('notify.mminshengyixiang.sign.err',array('linkS'=>$str,'sign'=>strtoupper(md5($str)),'sign_req'=>$sign));
            return false;
        }

    }

    /**
     * 【新】将数组转换成String
     * @return string
     */
    public function _create_link_string($para, $sort, $encode){
        if($para == NULL || !is_array($para))
            return "";
        $linkString = "";
        if($sort){
            $para = $this->argSort ( $para );
        }
        while ( list ( $key, $value ) = each( $para ) ) {
            if ($encode) {
                $value = urlencode ( $value );
            }
            $linkString .= $key . $value ;
        }

        // 去掉最后一个&字符
        return $linkString;
    }

    /**
     * 数组排序
     * @param $para
     * @return mixed
     */
    function argSort($para) {
        ksort ( $para );
        reset ( $para );
        return $para;
    }
}