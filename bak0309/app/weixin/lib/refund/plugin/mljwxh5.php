<?php

final class weixin_refund_plugin_mljwxh5 extends ectools_newrefund_app {

    /**
     * @var string 支付方式名称
     */
    public $name = '微信h5支付';

    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '微信h5支付';

    /**
     * @var string 支付方式key
     */
    public $app_key = 'mljwxh5';

    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mljwxh5';

    /**
     * @var string 统一显示的名称
     */
    public $display_name = '微信h5支付';

    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';

    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';

    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'iswap';

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app) {
        parent::__construct($app);
    }

    public function dorefund(&$refundInfo) {
        $appId = trim($this->getConf('appId', 'weixin_payment_plugin_mljwxh5')); //appid公众号 ID
        $paySignKey = trim($this->getConf('paySignKey', 'weixin_payment_plugin_mljwxh5')); //PaySignKey 对应亍支付场景中的 appKey 值
        $partnerId = trim($this->getConf('partnerId', 'weixin_payment_plugin_mljwxh5')); //商户ID

        $this->add_field("mch_id", strval($partnerId));
        $this->add_field("transaction_id", strval($refundInfo['pay_trade_no']));
        $this->add_field("out_trade_no", strval($refundInfo['payment_id']));
        $this->add_field("out_refund_no", strval($refundInfo['refund_id']));
        $this->add_field("total_fee", strval(bcmul($refundInfo['money'], 100))); //@TODO maojz 使用高精度乘法函数替换
        $this->add_field("refund_fee", strval(bcmul($refundInfo['cur_money'], 100))); //@TODO maojz 使用高精度乘法函数替换
        $this->add_field("refund_desc", $refundInfo['memo']);

        $weixinCertPath = LJ_WEIXIN_CERT_PATH;
        $refundRes = weixin_commonUtil::getRefundInfo($appId, $paySignKey, $this->fields, $weixinCertPath);
        if ($refundRes['return_code'] == 'SUCCESS' && $refundRes['result_code'] == 'SUCCESS') {
            //申请退款提交成功
            $refundInfo['trade_no'] = $refundRes['refund_id'];
            $refundInfo['status'] = 'progress';
            \Neigou\Logger::Debug("mljwxh5.dorefund", array('sparam1' => json_encode($refundInfo), 'sparam2' => json_encode($refundRes)));
            return true;
        } else {
            //默认状态失败
            $refundInfo['status'] = 'failed';
            \Neigou\Logger::General("mljwxh5.dorefund", array('sparam1' => json_encode($refundInfo), 'sparam2' => json_encode($refundRes)));
            return false;
        }
    }

    public function getRefundStatus(&$refundInfo) {
        $appId = trim($this->getConf('appId', 'weixin_payment_plugin_mljwxh5')); //appid公众号 ID
        $paySignKey = trim($this->getConf('paySignKey', 'weixin_payment_plugin_mljwxh5')); //PaySignKey 对应亍支付场景中的 appKey 值
        $partnerId = trim($this->getConf('partnerId', 'weixin_payment_plugin_mljwxh5')); //商户ID

        $this->add_field("mch_id", strval($partnerId));
        $this->add_field("refund_id", strval($refundInfo['trade_no']));
        $this->add_field("out_refund_no", strval($refundInfo['refund_id']));
        $this->add_field("out_trade_no", strval($refundInfo['order_id']));

        $weixinCertPath = LJ_WEIXIN_CERT_PATH;
        $refundRes = weixin_commonUtil::getRefundStatus($appId, $paySignKey, $this->fields, $weixinCertPath);
        if ($refundRes['return_code'] == 'SUCCESS' && $refundRes['result_code'] == 'SUCCESS' && $refundRes['refund_count'] == 1 && $refundRes['refund_status_0']) {
            $refundInfo['t_payend'] = isset($refundRes['refund_success_time_0']) ? $refundRes['refund_success_time_0'] : (isset($refundInfo['t_payend']) ? $refundInfo['t_payend'] : time());
            switch ($refundRes['refund_status_0']) {
                case 'SUCCESS':
                    $refundInfo['status'] = 'succ';
                    $report_name = 'wx.getRefundStatus.info';
                    break;
                case 'PROCESSING':
                    $refundInfo['status'] = 'progress';
                    $report_name = 'wx.getRefundStatus.info';
                    break;
                case 'REFUNDCLOSE':
                    $refundInfo['status'] = 'abnormal';
                    $report_name = 'wx.getRefundStatus.error';
                    break;
                case 'CHANGE':
                    $refundInfo['status'] = 'abnormal';
                    $report_name = 'wx.getRefundStatus.error';
                    break;
                default:
                    $refundInfo['status'] = 'abnormal';
                    $report_name = 'wx.getRefundStatus.error';
                    break;
            }
            if ($report_name == 'wx.getRefundStatus.info') {
                \Neigou\Logger::Debug($report_name, array('sparam1' => json_encode($refundInfo), 'sparam2' => json_encode($refundRes)));
            } else {
                \Neigou\Logger::General($report_name, array('sparam1' => json_encode($refundInfo), 'sparam2' => json_encode($refundRes)));
            }
            return true;
        } else {
            return false;
        }
    }

}
