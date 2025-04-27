<?php
/**
 * 中燃支付 notify 验证接口
 * User: chuanbin
 * Date: 2018/1/2
 * Time: 14:16
 */
class wap_payment_plugin_mchinagas_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        \Neigou\Logger::General('notify.mchinagas.req', array('remark' => 'recv_param', 'post_data'=>$recv));
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        if($this->is_return_vaild($recv)){
//            if($recv['status']==2){
            if(strlen($recv['out_trade_no'])<10){
                $ret['status'] = 'invalid';
                echo 'fail';
                \Neigou\Logger::General('ecstore.notify.mchinagas',array('remark'=>'order_id null','data'=>$recv));
                return $ret;
            }
            //检测payment_id对应的支付单号是否已经超过40min
            $t_begin = app::get('ectools')->model('payments')->getRow('t_begin',array('payment_id'=>$recv['out_trade_no']));
            $time_spend = time()-$t_begin['t_begin'];
            if($time_spend>2400){
                $ret['status'] = 'invalid';
                echo 'fail';
                \Neigou\Logger::General('ecstore.notify.mchinagas',array('remark'=>'pay timeout','data'=>$recv));
                return $ret;
            }

            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $recv['account'];
            $ret['bank'] = app::get('ectools')->_('中燃支付');
            $ret['pay_account'] = $recv['account'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['trade_no'];//JD 交易流水号为上报订单号 对接人称此号 在JD系统唯一
            $ret['t_payed'] = $recv['notify_time'];
            $ret['pay_app_id'] = "mchinagas";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            echo 'success';
            \Neigou\Logger::General('ecstore.notify.mchinagas', array('remark' => 'trade_succ', 'data' => $ret));
//            } else {
//                $ret['status']='invalid';
//                echo 'fail';
//                \Neigou\Logger::General('ecstore.notify.mchinagas', array('remark' => 'trade_status_err', 'data' => $ret));
//            }
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mchinagas',array('remark'=>'sign_err','data'=>$recv));
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
        $param['salt'] = 'mchinagasxneigou';
        $str = $this->_create_link_string($param,true,true);
        if($sign==md5($str)){
            return true;
        } else {
            \Neigou\Logger::General('notify.mchinagas.sign.err',array('linkS'=>$str,'sign'=>md5($str),'sign_req'=>$sign));
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
            $linkString .= $key . "=" . $value . "&";
        }

        // 去掉最后一个&字符
        $linkString = substr ( $linkString, 0, count ( $linkString ) - 2 );
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