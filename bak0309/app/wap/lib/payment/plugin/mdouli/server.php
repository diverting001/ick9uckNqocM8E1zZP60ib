<?php

/**
 * 兜礼支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mdouli_server extends ectools_payment_app {

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback(&$recv) {
        header('Content-Type:text/html; charset=utf-8');
        $ret['callback_source'] = 'server';
        $mer_id = $this->getConf('mer_id', 'wap_payment_plugin_mdouli');
        if($this->is_return_vaild($recv)){
            //优惠券核销

//            $voucher = array();
//            $coupon_info = app::get('b2c')->model('pop_preferential')->getList('*',array('order_id'=>$recv['order_id']));
//            foreach($coupon_info as $key=>$val){
//                if($val['type']=='voucher'){
//                    $voucher[] = $val['code'];
//                }
//            }
//            if(count($voucher)>0){
//                //获取订单详情
//                $order_items = kernel::single("b2c_service_order")->getOrderInfo($recv['order_id']);
//                $prod_detail = array();
//                foreach ($order_items['items'] as $k => $v) {
//                    $prod_detail[$k]['code'] = $v['bn'];//商品编号(唯一识别码)
//                    $prod_detail[$k]['goods'] = $v['name'];//商品名称
//                    $prod_detail[$k]['number'] = $v['nums'];//商品数量(2 位小数)
//                    $prod_detail[$k]['amount'] = $v['amount'];//实收金额(单位:元 2 位小数)
//                    $prod_detail[$k]['category'] = $v['name'];//TODO null 商品品类【用于商家返佣，0000:不计算返 佣 ，需计算返佣则需要与兜礼平台协商提 供品类编号】
//                    $prod_detail[$k]['price'] = $v['amount'];//应收金额(单位:元 2 位小数)
//                    $prod_detail[$k]['tax'] = 0;//TODO null 税率【保留 2 位小数。商品返佣计算含税 则填 0，不含税则按商品实际税点填写】
//                    $prod_detail[$k]['categoryOne'] = $v['name'];//TODO null 商品一级类目
//                    $prod_detail[$k]['categoryTwo'] = $v['name'];//TODO null 商品二级类目
//                }
//                //获取第三方用户bn
//                $coupon_url = $this->getConf('coupon_url', 'wap_payment_plugin_mdouli');
//                $members_mdl = app::get('b2c')->model('third_members');
//                $member_id = $order_items['member_id'];
//                $member_info = $members_mdl->getRow("*", array("internal_id"=>$member_id,'source' => 1));
//                $param['businessId'] = $this->getConf('mer_id', 'wap_payment_plugin_mdouli');
//                $param['storesId'] = 'A001';
//                $param['telephone'] = $member_info['external_bn'];
//
//                $param['modifyDateTime'] = $order_items['create_time'];
//                $param['orderNumber'] = $recv['order_id'];
//                $param['serialNumber'] = $recv['orderNoFlx'];
//                $param['amount'] = $recv['settleAmt'];
//                $param['orderDetail'] = json_encode($prod_detail);
//                foreach($voucher as $voucher_id){
//                    $param['couponCode'] = $voucher_id;
//                    //请求核销
//                    $r = $this->request($coupon_url,$param);
//                    \Neigou\Logger::General('mdouli.coupon',array('request_param'=>$param,'req_url'=>$coupon_url,'response_data'=>$r));
//                }
//            }
            $ret['payment_id'] = $recv['orderNo'];
            $ret['account'] = $mer_id;
            $ret['bank'] = app::get('ectools')->_('兜礼支付');
            $ret['pay_account'] = $recv['customNo'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['settleAmt'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['settleAmt'];
            $ret['trade_no'] = $recv['orderNoFlx'];//queryId 交易流水号 traceNo 系统跟踪号
            $ret['t_payed'] = time();
            $ret['pay_app_id'] = "mdouli";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            echo 'succ';
        }else{
            echo 'fail';
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.notify.mdouli',array('remark'=>'pay rzt err','data'=>$recv));
        }
        return $ret;

    }

    /**
     * <生成签名方法>
     * <功能详细描述>
     * @param map 参数集合
     * @return signResult签名后的字符串
     * @see [类、类#方法、类#成员]
     */
    public function genSign($postData) {
        ksort($postData);
        $data = '';
        foreach ($postData as $kk => $vv) {
            $urlData = '';
            $urlData = $kk . '=' . $vv . '&';
            $data .=$urlData;
        }
        return strtoupper(sha1($data));
    }

    /**
     * 检验返回数据合法性
     * @param mixed $params 包含签名数据的数组
     * @access private
     * @return boolean
     */
    public function is_return_vaild($params) {
        $signature_str = $params ['sign'];
        unset ( $params ['sign'] );
        $params['salt'] = 'neigou*douli*salt';
        $sign = $this->genSign($params);
//        echo $sign."\n".$signature_str;
        if ($sign==$signature_str) {
            return true;
        } else {
            return false;
        }
    }

    public function request($api = '', $post_data = array()) {
        $url = $api;
        $curl = new \Neigou\Curl();
        //For douli ssl request
        $opt_config = array(
            CURLOPT_POST => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_POSTFIELDS => json_encode($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => true,
            CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1,
            CURLOPT_SSLCERT => $this->getConf('ssl_cert', 'wap_payment_plugin_mdouli'),
            CURLOPT_SSLKEY => $this->getConf('ssl_key', 'wap_payment_plugin_mdouli'),
            CURLOPT_SSLCERTPASSWD => $this->getConf('ssl_pass', 'wap_payment_plugin_mdouli'),
            CURLOPT_SSLKEYPASSWD => $this->getConf('ssl_pass', 'wap_payment_plugin_mdouli'),
        );
        $curl->SetOpt($opt_config);
        $curl->SetHeader('Content-Type', 'application/json');
        $result = $curl->Post($url, json_encode($post_data));
        \Neigou\Logger::General('pay.mdouli', array('action' => 'req', 'opt_config' => $opt_config,'req_url'=>$url,'post_data'=>$post_data,'response_data'=>$result));
        $resultData = json_decode($result, true);
        return $resultData;
    }
}