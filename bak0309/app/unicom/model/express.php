<?php
class unicom_mdl_express extends base_db_external_model{

    private $db_conf = array(
        'MASTER'=>array('HOST'=>CLUB_DB_MASTER_HOST,
            'NAME'=>CLUB_DB_MASTER_NAME,
            'USER'=>CLUB_DB_MASTER_USER,
            'PASSWORD'=>CLUB_DB_MASTER_PASSWORD));

    function __construct($app){
        parent::__construct($app,$this->db_conf);
    }

    public function getExpressInfoOne($num){
        $sql = "SELECT * FROM club_express WHERE status = 3 and `num` = '{$num}'";
        $result = $this->_db->selectrow($sql);
        return $result ? $result : array();
    }

}