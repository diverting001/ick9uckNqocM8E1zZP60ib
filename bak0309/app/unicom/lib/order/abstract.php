<?php
/**
 * 联通订单结算 
 * @package     neigou_store
 * @author      lidong
 * @since       Version
 * @filesource
 */
abstract class unicom_order_abstract
{
    /**
     * 返回标准信息
     * @param  [type] $code [description]
     * @param  [type] $msg  [description]
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public function makeMsg ($code, $msg, $data = array())
    {
        $Result = $code==10000?'true':'false';
        $response_data = array(
            "Result" => $Result,
            "ErrorId" => (string)$code,
            "ErrorMsg" => $msg,
            "Data" => empty($data) ? null : $data,
        );
        return $response_data;
    }

    /**
     * 删除订单推送信息
     * @param  [type] $msgIdArr [description]
     * @return [type]           [description]
     */
    public function delUnicomPushMessage($msg, $msgIdArr)
    {
        if ($msg['ErrorId'] != 10000) {
            return false;
        }
        if (is_string($msgIdArr)) {
            $msgIdArr = array($msgIdArr);
        }
        $errMsg = '';
        $request = kernel::single('unicom_request');
        $result = $request->request(array(
            'method' => 'delOrderPushMsg'
            ,'data'  => array('msgIds'  => implode(',', $msgIdArr))
        ), $errMsg);
        if ($result === false)
        {
            return $this->makeMsg('60002', $errMsg);
        }

        if ($result['success'] != 'true')
        {
            return  $this->makeMsg('60002',(!empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误'));
        }

        return $this->makeMsg(10000,'success', $result['result']);
    }

}