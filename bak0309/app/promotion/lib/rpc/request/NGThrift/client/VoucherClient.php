<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/8/11 0011
 * Time: 16:32
 */
namespace VoucherServer;
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Exception\TException;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TBufferedTransport;
use Thrift\Transport\TSocket;


define('THIRF_LIB_DIR', ROOT_DIR.'/app/base/lib/rpc/thrift/lib');
require_once THIRF_LIB_DIR.'/php/lib/Thrift/ClassLoader/ThriftClassLoader.php';

class ThriftVoucherClient
{
    private $client;
    private $transport;

    public function __construct()
    {
        try {
            $GEN_DIR = realpath(dirname(__FILE__)) . '/../gen-php';
            $loader = new ThriftClassLoader();
            $loader->registerNamespace('Thrift', THIRF_LIB_DIR.'/php/lib');
            $loader->registerDefinition('VoucherServer', $GEN_DIR);
            $loader->register(true);

            $socket=new TSocket(RPC_VOUCHER_THRIFT_IP, intval(RPC_VOUCHER_THRIFT_PORT));
            $socket->setSendTimeout(10000);
            $socket->setRecvTimeout(10000);
            $this->transport=new TBufferedTransport($socket, 1024, 1024);
            $protocol=new TBinaryProtocol($this->transport);
            $this->client=new VoucherServerClient($protocol);
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
     * @param string $json_data
     * @return string
     */
    public function addVoucher($json_data) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->addVoucher($json_data);
    }

    public function getRule($rule_id) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->getRule($rule_id);
    }
    
    /**
     * @param string $voucher_number
     * @param string $memo
     * @return string
     */
    public function disableVoucher($voucher_number, $memo) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->disableVoucher($voucher_number, $memo);
    }

    /**
     * @param int $create_id
     * @param string $memo
     * @return string
     */
    public function disableVoucherForCreateID($create_id, $memo) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->disableVoucherForCreateID($create_id, $memo);
    }

    /**
     * @param string $voucher_number
     * @param int $member_id
     * @param int $order_id
     * @param float $use_money
     * @param string $memo
     * @return string
     */
    public function useVoucher($voucher_number, $member_id, $order_id, $use_money, $memo) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->useVoucher($voucher_number, $member_id, $order_id, $use_money, $memo);
    }

    /**
     * @param string $voucher_number
     * @return string
     */
    public function queryVoucher($voucher_number) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->queryVoucher($voucher_number);
    }

    /**
     * @param int $order_id
     * @return string
     */
    public function queryOrderVoucher($order_id) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->queryOrderVoucher($order_id) ;
    }
    /**
     * @param int $member_id
     * @return string
     */
    public function queryMemberVoucher($member_id) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->queryMemberVoucher($member_id)  ;
    }

    /**
     * @param string $voucher_number
     * @param string $status
     * @param string $memo
     * @return string
     */
    public function exchangeStatus($voucher_number, $status, $memo) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->exchangeStatus($voucher_number, $status, $memo) ;
    }

    /**
     * @param int $member_id
     * @return string
     */
    public function queryMemberBindedVoucher($member_id) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->queryMemberBindedVoucher($member_id);
    }

    /**
     * @param int $member_id
     * @param string $json_data
     * @return string
     */
    public function createMemVoucher($member_id, $json_data) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->createMemVoucher($member_id, $json_data);
    }

    /**
     * @param int $member_id
     * @param string $voucher_number
     * @param string $source_type
     * @return string
     */
    public function bindMemVoucher($member_id, $voucher_number, $source_type) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->bindMemVoucher($member_id, $voucher_number, $source_type);
    }

    /**
     * @param string $json_data
     * @return string
     */
    public function largeBindMemVoucher($json_data) {
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->largeBindMemVoucher($json_data);
    }

    /**
     * @param string $json_data
     * @param string $json_filter_data
     * @return string
     */
    public function queryVoucherWithRule($json_data, $json_filter_data){
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->queryVoucherWithRule($json_data, $json_filter_data);
    }
    /**
     * @param string $json_data
     * @param string $json_filter_data
     * @return string
     */
    public function useVoucherWithRule($json_data, $json_filter_data){
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->useVoucherWithRule($json_data, $json_filter_data);
    }
    /**
     * @param int $member_id
     * @param string $json_filter_data
     * @return string
     */
    public function queryMemberBindedVoucherWithRule($member_id, $json_filter_data){
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->queryMemberBindedVoucherWithRule($member_id, $json_filter_data);
    }
    /**
     * @param string $type
     * @param string $json_data
     * @return string
     */
    public function createRule($type, $json_data){
        if (!$this->transport->isOpen()) {
            return false;
        }
        return $this->client->createRule($type, $json_data);
    }
    
    public function applyVoucherPkg($apply_params_string) {
        return $this->client->applyVoucherPkg($apply_params_string);
    }

    /**
     * @param string $action
     * @param string $json_data
     * @return string
     */
    public function freeShippingCouponServer($action, $json_data){
        if (!$this->isConnected()) {
            return false;
        }
        return $this->client->freeShippingCouponServer($action, $json_data);
    }

    public function transferVoucher($member_id, $json_data)
    {
        if (!$this->isConnected()) {
            return false;
        }
        try {
            \Neigou\Logger::General('action.VoucherServerClient', array('action'=>'transferVoucher','member_id' => $member_id,'success'=>1,'data'=>$json_data,'sparam1'=>$action));
            return $this->client->transferVoucher($member_id, $json_data) ;
        } catch (TException $e) {
            $errorMsg = $e->getMessage();
            \Neigou\Logger::General('action.VoucherServerClient', array('action'=>'transferVoucher','member_id' => $member_id,'success'=>0,'data'=>$json_data,'sparam1'=>$action,'sparam2'=>$errorMsg));
            return false;
        }
    }

    public function queryMemberBindedVoucherByGuid($guid, $json_data)
    {
        if (!$this->isConnected()) {
            return false;
        }
        try {
            \Neigou\Logger::General('action.VoucherServerClient', array('action'=>'queryMemberBindedVoucherByGuid','guid' => $guid,'success'=>1,'data'=>$json_data,'sparam1'=>$action));
            return $this->client->queryMemberBindedVoucherByGuid($guid, $json_data) ;
        } catch (TException $e) {
            $errorMsg = $e->getMessage();
            \Neigou\Logger::General('action.VoucherServerClient', array('action'=>'queryMemberBindedVoucherByGuid','guid' => $guid,'success'=>0,'data'=>$json_data,'sparam1'=>$action,'sparam2'=>$errorMsg));
            return false;
        }
    }
}
