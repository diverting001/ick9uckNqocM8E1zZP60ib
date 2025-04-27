<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/11 0011
 * Time: 16:32
 */
namespace ThriftCenter;
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Exception\TException;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Thrift\Transport\TSocket;

define('THIRF_LIB_DIR', ROOT_DIR.'/app/base/lib/rpc/thrift/lib');
require_once THIRF_LIB_DIR.'/php/lib/Thrift/ClassLoader/ThriftClassLoader.php';
$GEN_DIR = realpath(dirname(__FILE__)) . '/../gen-php';
$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', THIRF_LIB_DIR.'/php/lib');
$loader->registerDefinition('ThriftCenter', $GEN_DIR);
$loader->register(true);
class ThriftCenterClientAdapter
{
    private $client;
    private $transport;

    public function __construct()
    {
        try {
            $socket=new TSocket(RPC_ADAPTER_THRIFT_IP, RPC_ADAPTER_THRIFT_PORT);
            $socket->setSendTimeout(10000);
            $socket->setRecvTimeout(10000);
            $this->transport=new TBufferedTransport($socket, 1024, 1024);
            $protocol=new TBinaryProtocol($this->transport);
            $this->client=new ThriftCenterClient($protocol);
            $this->transport->open();
        } catch (TException $e) {

        }
    }

    public function __destruct()
    {
        if ($this->transport) {
            $this->transport->close();
        }
    }

    public function isConnected() {
        if ($this->transport) {
            return $this->transport->isOpen();
        }
        return false;
    }

    /**
     * @param string $action
     * @param string $json_data
     * @return string
     */
    public function BalancePointServer($action, $json_data) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->BalancePointServer($action, $json_data);
    }

    /**
     * @param string $action
     * @param string $json_data
     * @return string
     */
    public function WeixinMessageServer($action, $json_data) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->WeixinMessageServer($action, $json_data);
    }
    /**
     * @param string $action
     * @param string $json_data
     * @return string
     */
    public function SendMessageServer($action, $json_data){
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->AppMessageServer($action,$json_data);
    }
    /**
     * @param string $action
     * @param string $json_data
     * @return string
     */
    public function WelfareCardServer($action, $json_data){
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->WelfareCardServer($action,$json_data);
    }

}
