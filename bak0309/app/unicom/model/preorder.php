<?php

class unicom_mdl_preorder extends base_db_external_model
{
    // 数据库配置
    private $db_conf = array(
        'MASTER' => array(
        'HOST'     => DB_HOST,
        'NAME'     => DB_NAME,
        'USER'     => DB_USER,
        'PASSWORD' => DB_PASSWORD
        )
    );

    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app,$this->db_conf);
    }

    /**
     * 获取订单数据
     * @param  [type] $third_order_bn
     * @return [mix]   
     */
    public function getInfo ($third_order_bn)
    {
        if (empty($third_order_bn)) {
            return array();
        }

        $sql = "SELECT * FROM unicom_preorder WHERE `third_order_bn` = '{$third_order_bn}'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    /**
     * 通过内部订单ID获取信息
     * @param  [string] $order_id       [description]
     * @return [type]                   [description]
     */
    public function getInfoByOrderId ($order_id)
    {
        if (empty($order_id)) {
            return array();
        }

        $sql = "SELECT * FROM unicom_preorder WHERE `order_id` = '{$order_id}'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }
    
    /**
     * 按条件过滤预订单
     * @param type $fileds
     * @param type $filter
     */
    public function getList($fileds = '*', $filter = array(),$limit = 100,$order = 'id desc')
    {  
        $where = 1;
        foreach($filter as $key=>$val){
            $where .= " and `{$key}`='{$val}'";
        }
        
        $sql = "SELECT {$fileds} FROM unicom_preorder WHERE {$where} order by {$order} limit {$limit}";
        $result = $this->_db->select($sql);
        return $result ? $result : array();
    }

    
    /**
     * 保存订单
     * @param [type] $order_info [description]
     */
    public function addInfo ($order_info)
    {
        $info = $this->getInfo($order_info['third_order_bn']);
        if (!empty($info)) {
            return false;
        }

        $order_info['last_modified'] = time();
        $fields = '`'. (implode('`,`', array_keys($order_info))). '`';
        $values = "'". (implode("','", $order_info)). "'";
        $sql  = "INSERT INTO unicom_preorder({$fields}) VALUES ({$values})";
        $result = $this->_db->exec($sql);
        $id = 0;
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $id;
    }

    public function updateInfo ($id, $fields)
    {
        if (empty($id) || empty($fields)) {
            return false;
        }
        $updateData = array();
        foreach ($fields as $field => $value)
        {
            if ($field != 'id' && $field != 'third_order_bn')
            {
                $updateData[] = $field. '='. "'". $value. "'";
            }
        }
        $updateData[] = '`last_modified`='.time();

        $sql = "UPDATE unicom_preorder SET ". implode(',', $updateData). " WHERE id = $id";

        $result = $this->_db->exec($sql);

        return $result['rs'] ? true : false;
    }
}