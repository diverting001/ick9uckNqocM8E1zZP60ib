<?php
class promotion_mdl_hongbao extends base_db_external_model
{
    private $db_conf = array(
        'MASTER'=>array('HOST'=>HD_DB_SLAVE_HOST,
        'NAME'=>HD_DB_SLAVE_NAME,
        'USER'=>HD_DB_SLAVE_USER,
        'PASSWORD'=>HD_DB_SLAVE_PASSWORD));

    function __construct($app){
        parent::__construct($app,$this->db_conf);
    }

    public function getGrabLogByMobile($mobile = 0){
        \Neigou\Logger::General("hongbao.action", array("action"=>"getGrabLogByMobile","mobile" => $mobile, "state"=>"success", "reason"=>""));
        if(!$mobile) return array();
        $sql = "select * from hd_hongbao_grab_log where member_id = 0 and mobile = '{$mobile}'";
        \Neigou\Logger::General("hongbao.action", array("action"=>"getGrabLogByMobile.SQL","sql" => $sql , "state"=>"success", "reason"=>""));
        $result = $this->_db->select($sql);
        return $result;
    }

    public function updateGrabInfoByGrabId($grab_id,$member_id){
        if(!$grab_id || !$member_id) return false;
        $sql = "update hd_hongbao_grab_log set member_id = $member_id , create_time=" . time() . " where grab_id = $grab_id and member_id = 0";
        return $this -> _db -> exec($sql);
    }

    public function getRuleIdByShareId($share_id){
        $sql = "select * from `hd_hongbao_share` shares left join `hd_hongbao_strategy_rule` rules on shares.`strategy_rule_id` = rules.`strategy_rule_id` where shares.`share_id` = $share_id limit 1";
        $result = $this->_db->select($sql);
        if($result){
            $result = current($result);
            return $result['voucher_rule_id'];
        }
        return 0;
    }

}
