<?php
/**
 * @copyright shopex.cn
 * @author ymz lymz.86@gmail.com
 * @version ocs
 */

class sms189_rpc_request {

    protected static function fashe($params, $url, $method='post')
    {
        if (!in_array(strtolower($method), array('post', 'get'))) {
            trigger_error('error method', E_ERROR);
            return false;
        }
        $return = kernel::single('base_httpclient')->$method($url, $params);

        //todo(xiangcai.guo)增加对XML的处理
        $p = xml_parser_create();
        xml_parse_into_struct($p, $return, $value, $index);
        xml_parser_free($p);
        $result = $value[0]['value'];
        $len = strlen($result);
        $msg = 'Success';
        $status = 0;

        if($len < 10){
            $status = 1;
            $msg = 'Fail';
        }

        $data = array(
            'res_code'=>$status,
            'res_message'=>$msg,
            'idertifier'=>$result,
        );

        //logger::log("neigou=sms189_rpc_request::fashe index=".print_r($result,true),3);
        return $data;
    }

}
