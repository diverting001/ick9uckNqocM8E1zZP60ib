<?php

/**
 * 中粮售后
 * @package     neigou_store
 * @author      guke
 * @since       Version
 * @filesource
 */
class wmqy_mdl_base extends base_db_external_model
{
    // 数据库配置
    private $db_conf = array(
        'MASTER' => array(
            'HOST' => DB_HOST,
            'NAME' => DB_NAME,
            'USER' => DB_USER,
            'PASSWORD' => DB_PASSWORD
        )
    );
    private $_table;

    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app, $table)
    {
        parent::__construct($app, $this->db_conf);
        $this->_table = $table;
    }

    public function add($data)
    {
        $fields = '';
        $vals = '';
        foreach ($data as $key => $val) {
            $fields .= "`$key`,";
            $val = str_replace("'", "\'", $val);
            $vals .= "'$val',";
        }
        $fields = trim($fields, ',');
        $vals = trim($vals, ',');
        $sql = "INSERT INTO {$this->_table} ($fields) VALUES ($vals)";
        $result = $this->_db->exec($sql);
        return $result ? $result : array();
    }

    public function save($data, $where, &$sql = '')
    {
        $set = '';
        foreach ($data as $key => $val) {
            if ($key === 'fail_count' && !is_numeric($val)) {
                $set .= "$key=$key+1,";
                continue;
            }
            $val = str_replace("'", "\'", $val);
            $set .= "$key='$val',";
        }
        $set = trim($set, ',');
        $sql = "UPDATE {$this->_table} SET $set WHERE $where";
        return $this->_db->exec($sql);
    }

    public function getByWhere($where)
    {
        $sql = "SELECT * FROM {$this->_table} WHERE $where";
        $result = $this->_db->selectrow($sql);
        return $result ? $result : array();
    }

    public function getListByStatus($status, $limit = 100)
    {
        $sql = "SELECT * FROM {$this->_table} WHERE status='$status' ORDER BY created_at ASC LIMIT $limit ";
        $result = $this->_db->select($sql);
        return $result ? $result : array();
    }
}
