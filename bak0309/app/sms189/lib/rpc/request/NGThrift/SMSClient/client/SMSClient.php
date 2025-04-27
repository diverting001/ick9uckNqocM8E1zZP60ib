<?php
define('THIRF_LIB_DIR', ROOT_DIR.'/app/base/lib/rpc/thrift/lib');
require_once THIRF_LIB_DIR.'/php/lib/Thrift/ClassLoader/ThriftClassLoader.php';

use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\THttpClient;
use Thrift\Transport\TBufferedTransport;
use Thrift\Exception\TException;

class ThriftSMSClient {
    public static function sendSMS($mobile, $sms_contens,$message_channel = "") {
        $GEN_DIR = realpath(dirname(__FILE__)) . '/../gen-php';
        $loader = new ThriftClassLoader();
        $loader->registerNamespace('Thrift', THIRF_LIB_DIR.'/php/lib');
        $loader->registerDefinition('SMSServer', $GEN_DIR);
        $loader->register(true);

        try {
            $socket = new TSocket(RPC_SMS_THRIFT_IP, intval(RPC_SMS_THRIFT_PORT));
            $transport = new TBufferedTransport($socket, 1024, 1024);
            $protocol = new TBinaryProtocol($transport);
            $client = new \SMSServer\SMSServerClient($protocol);

            $transport->open();

            $params_array = array('mobile'=>$mobile, 'sms_content'=>$sms_contens,'message_channel'=>$message_channel);
            $result = $client->sendSMS(json_encode($params_array));

            $transport->close();
            return $result;

        } catch (TException $tx) {
            return 'fail';
        }
    }
    
    public static function diandiSendSMS($mobile, $sms_contens,$message_channel = "") {
        $GEN_DIR = realpath(dirname(__FILE__)) . '/../gen-php';
        $loader = new ThriftClassLoader();
        $loader->registerNamespace('Thrift', THIRF_LIB_DIR.'/php/lib');
        $loader->registerDefinition('SMSServer', $GEN_DIR);
        $loader->register(true);

        try {
            $socket = new TSocket(RPC_SMS_THRIFT_IP, intval(RPC_SMS_THRIFT_PORT));
            $transport = new TBufferedTransport($socket, 1024, 1024);
            $protocol = new TBinaryProtocol($transport);
            $client = new \SMSServer\SMSServerClient($protocol);

            $transport->open();

            $params_array = array('mobile'=>$mobile, 'sms_content'=>$sms_contens,'message_channel'=>$message_channel);
            $result = $client->diandiSendSMS(json_encode($params_array));

            $transport->close();
            return $result;

        } catch (TException $tx) {
            return 'fail';
        }
    }
    
    public static function diandiB2cSendSMS($mobile, $sms_contens,$type,$message_channel = "") {
        $GEN_DIR = realpath(dirname(__FILE__)) . '/../gen-php';
        $loader = new ThriftClassLoader();
        $loader->registerNamespace('Thrift', THIRF_LIB_DIR.'/php/lib');
        $loader->registerDefinition('SMSServer', $GEN_DIR);
        $loader->register(true);

        try {
            $socket = new TSocket(RPC_SMS_THRIFT_IP, intval(RPC_SMS_THRIFT_PORT));
            $transport = new TBufferedTransport($socket, 1024, 1024);
            $protocol = new TBinaryProtocol($transport);
            $client = new \SMSServer\SMSServerClient($protocol);

            $transport->open();

            $params_array = array('mobile'=>$mobile, 'sms_content'=>$sms_contens,'type'=>$type,'message_channel'=>$message_channel);
            $result = $client->diandiB2cSendSMS(json_encode($params_array));

            $transport->close();
            return $result;

        } catch (TException $tx) {
            return 'fail';
        }
    }
};
?>
