<?php
/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/26
 * Time: 7:52 PM
 */
class unicom_mdl_o2oOrderCancelRecord extends base_db_external_model
{
    const UNICOM_ORDER_UPDATE_RECORD_TABLE = 'unicom_o2o_order_cancel_record';
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
        parent::__construct($app, $this->db_conf);
    }

    /** 获取某一行记录
     *
     * @param $memberId
     * @return mixed
     * @author liuming
     */
    public function getOrderRaw($whereArr = array()){
        $where = '';
        foreach($whereArr as $k => $v){
            if (empty($k)) continue;
            if (!empty($where)){
                $where .=' AND ';
            }
            $where .= ' `'.$k.'`="'.$v.'" ';
        }
        if ($where){
            $where = ' WHERE '.$where;
        }

        if (empty($where)) return false;

        $sql = 'select * from unicom_o2o_order_cancel_record '.$where;
        return $this->_db->selectrow($sql);
    }


    /** 增加表信息
     *
     * @param array $data
     * @return bool|int
     * @author liuming
     */
    public function add($data = array()){
        if (empty($data)) return false;

        $fields = '`'.implode('`,`',array_keys($data)).'`';
        $values = "'".implode("','",$data)."'";
        $sql = 'insert into unicom_o2o_order_cancel_record ('.$fields.') '.'values('.$values.')';
        $result = $this->_db->exec($sql);

        if ($result['rs']) {
            return true;
        }
        return false;
    }

    /** 更新信息
     *
     * @param array $whereArr
     * @param array $data
     * @return bool
     * @author liuming
     */
    public function update($whereArr = array(),$data = array()){
        if (empty($whereArr) || empty($data)) return false;

        $where = $set = '';
        foreach ($data as $k => $v){
            $set .= ' `'.$k.'`="'.$v.'",';
        }
        $set = rtrim($set,',');

        foreach($whereArr as $k => $v){
            if (empty($k)) continue;
            if (!empty($where)){
                $where .=' AND ';
            }
            $where .= ' `'.$k.'`="'.$v.'" ';
        }
        if ($where){
            $where= ' WHERE '.$where;
        }
        $sql = 'UPDATE `unicom_o2o_order_cancel_record` SET '.$set.$where;
        $result = $this->_db->exec($sql);
        if ($result['rs']) {
            return true;
        }
        return false;
    }


}