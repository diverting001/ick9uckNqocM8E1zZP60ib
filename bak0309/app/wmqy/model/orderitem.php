<?php

class wmqy_mdl_orderitem extends wmqy_mdl_base
{
    private $_table = 'wmqy_order_items';

    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app, $this->_table);
    }

    public function getByOrderID($order_id)
    {
        $sql = "SELECT * FROM {$this->_table} WHERE order_id='$order_id' group by product_bn,package_bn,item_type, p_bn";
        $result = $this->_db->select($sql);
        return $result ? $result : array();
    }
}
