<?php

/**
 * 中粮订单表
 * @package     neigou_store
 * @author      guke
 * @since       Version
 * @filesource
 */
class wmqy_mdl_order extends wmqy_mdl_base
{
    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app, 'wmqy_orders');
    }

    public function addWmqyOrderItem($data)
    {
        $fields = '';
        $vals = '';
        foreach ($data as $key => $val) {
            $fields .= "`$key`,";
            $vals .= "'$val',";
        }
        $fields = trim($fields, ',');
        $vals = trim($vals, ',');
        $sql = "INSERT INTO wmqy_order_items ($fields) VALUES ($vals)";
        $result = $this->_db->exec($sql);
        return $result ? $result : array();
    }
}
