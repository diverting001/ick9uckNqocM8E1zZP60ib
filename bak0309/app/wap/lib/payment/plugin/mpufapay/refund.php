<?php

class wap_payment_plugin_mpufapay_refund extends ectools_payment_app
{
    private $refundId;
    private $paymentId;

    public function __construct()
    {
        $this->refundId = '200147';
        $this->paymentId = '1644283959003652';
    }

	public function refund()
    {
        /** @var ectools_newrefund_plugin_mpufapay */
        $refundObject = kernel::single('ectools_newrefund_plugin_mpufapay');
        $info = array(
            'status' => 'none',
            'payment_id' => $this->paymentId,
            'cur_money' => '1',
            'refund_id' => $this->refundId,
            'trade_no' => '588071817216',
        );
        $ret = $refundObject->dorefund($info);
        var_dump($ret, $info);
        
    }

    public function status()
    {
        /** @var ectools_newrefund_plugin_mpufapay */
        $refundObject = kernel::single('ectools_newrefund_plugin_mpufapay');
        $info = array(
            'payment_id' => $this->paymentId,
            'refund_id' => $this->refundId,
        );
        $ret = $refundObject->getRefundStatus($info);
        var_dump($ret, $info);
    }
}
