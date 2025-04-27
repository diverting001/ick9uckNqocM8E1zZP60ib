<?php

/**
 * 采贝支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_megonghui_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        \Neigou\Logger::General('ecstore.notify.megonghui',array('remark'=>'notify_param','data'=>$recv));
        #键名与pay_setting中设置的一致
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $recv['account'];
            $ret['bank'] = app::get('ectools')->_('怀谷智慧工会支付');
            $ret['pay_account'] = $recv['account'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;//TODO 确认是否是这个字段 清算金额
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['trade_no'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = $recv['notify_time']? $recv['notify_time'] : time();
            $ret['pay_app_id'] = "megonghui";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            echo 'succ';
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.megonghui',array('remark'=>'sign err','data'=>$recv));
        }
        return $ret;
    }

    /**
     * 检验返回数据合法性
     * @param $param array
     * @access private
     * @return boolean
     */
    public function is_return_vaild($param) {
        $sign = $param['sign'];
        unset($param['sign']);
        $param['salt'] = $this->getConf('salt','wap_payment_plugin_megonghui');
        $req_sign = $this->genSign($param);
        if ($sign==$req_sign) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * 计算签名
     * @param $data
     * @return string
     */
    public function genSign($data){
        $linkStr = $this->_create_link_string($data,true,true);
        \Neigou\Logger::General('ecstore.megonghui.notify.linkStr',array('action'=>'make sign','linkStr'=>$linkStr,'sign'=>md5($linkStr)));
        return md5($linkStr);
    }

    /**
     * 组合字符串
     * @param $para
     * @param $sort
     * @param $encode
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