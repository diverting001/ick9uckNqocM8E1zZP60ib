<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/9/22
 * Time: 下午8:21
 */


require_once __DIR__.'/rpc/request/NGThrift/client/RPCClient.php';
class remotequeue_service
{
    public function dispatchScriptCommandTaskSimpleNoReply($script_name, $param_string,$filter='') {

        $handler = new \remotequeue\RPCClient();
        $handler->dispatchScriptCommandTaskSimpleNoReply($script_name, $param_string, 0, $filter);
    }
    public function dispatchScriptCommandTaskSimple($script_name, $param_string,$timeout=120, $filter='')
    {

        $handler = new \remotequeue\RPCClient();
        return $handler->dispatchScriptCommandTaskSimple($script_name, $param_string, $timeout, $filter);
    }
    public function getJobState($token)
    {
        $handler = new \remotequeue\RPCClient();
        return $handler->getJobState($token);
    }

    public function dispatchWebShellCallTask($host,$data,$current,$filter= ''){


        $handler = new \remotequeue\RPCClient();
        return $handler->dispatchWebShellCallTask($host,$data,$current,$filter);
    }
}