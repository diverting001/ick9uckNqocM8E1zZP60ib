<?php
/**
 * 好生活支付 notify 验证接口
 * User: chuanbin
 * Date: 2018/5/28
 * Time: 11:18
 */
class wap_payment_plugin_mhaoshenghuo_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        \Neigou\Logger::General('ecstore.mhaoshenghuo.notify', array('remark' => 'notify_param', 'post_data'=>$recv));
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        if($this->is_return_vaild($recv)){
            $ret['payment_id'] = $recv['out_trade_no'];
            $ret['account'] = $recv['account'];
            $ret['bank'] = app::get('ectools')->_('好生活支付');
            $ret['pay_account'] = $recv['account'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['total_fee']/100;
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['total_fee']/100;
            $ret['trade_no'] = $recv['trade_no'];
            $ret['t_payed'] = $recv['notify_time'];
            $ret['pay_app_id'] = "mhaoshenghuo";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            echo 'success';
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.mhaoshenghuo.err',array('remark'=>'callback_sign_err','data'=>$recv));
        }
        return $ret;

    }

    /**
     * 检验返回数据合法性
     * @param $data
     * @return mixed
     */
    public function is_return_vaild($data) {
        $sign = $data['sign'];
        unset($data['sign']);
        $data['salt'] = $this->getConf('salt','wap_payment_plugin_mhaoshenghuo');
        $str = $this->_create_link_string($data,true,true);
        \Neigou\Logger::General('ecstore.mhaoshenghuo.notify.verify_sign',array('remark'=>'link_str','link_str'=>$str,'data'=>$data,'sign'=>md5($str)));
        if($sign == md5($str)){
            return true;
        } else {
            return false;
        }

    }

    /**
     * 将数组转换成String
     * @param $para array 参数
     * @param $sort bool 是否排序
     * @param $encode string 是否urlencode
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