<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/7/22
 * Time: 下午4:23
 */
require_once __DIR__.'/NGThrift/SMSClient/client/SMSClient.php';

class sms189_rpc_request_smsutil {
    public static function sendSMS($mobile, $sms_content,$message_channel = "") {
        //logger::log('sms189_rpc_request_smsutil mobile = '.$mobile);
        logger::log("neigou=sms189_rpc_request_smsutil::sendSMS mobile=".$mobile);

        \Neigou\Logger::General("sms_bn_counon",array('data'=>json_encode(array('sms_content'=>$sms_content,'mobile'=>$mobile,'message_channel'=>$message_channel))));

        $smsresult = ThriftSMSClient::sendSMS($mobile, $sms_content,$message_channel);

        $resultArray = json_decode($smsresult, true);

        $result = $resultArray['data'];
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
        return $data;
    }
        
    public static function diandiSendSMS($mobile, $sms_content,$message_channel = "") {
        //logger::log('sms189_rpc_request_smsutil mobile = '.$mobile);
        logger::log("neigou=sms189_rpc_request_smsutil::dianDiSendSMS mobile=".$mobile);
        $smsresult = ThriftSMSClient::diandiSendSMS($mobile, $sms_content,$message_channel);

        $resultArray = json_decode($smsresult, true);

        $result = $resultArray['data'];
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
        return $data;
    }
    
    public static function diandiB2cSendSMS($mobile, $sms_content,$type,$message_channel = "") {
        //logger::log('sms189_rpc_request_smsutil mobile = '.$mobile);
        logger::log("neigou=sms189_rpc_request_smsutil::dianDiB2cSendSMS mobile=".$mobile);
        $smsresult = ThriftSMSClient::diandiB2cSendSMS($mobile, $sms_content,$type,$message_channel);

        $resultArray = json_decode($smsresult, true);

        $result = $resultArray['data'];
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
        return $data;
    }
}
