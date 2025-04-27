<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/10/29
 * Time: 下午9:10
 */

class promotion_mdl_remindqueue extends base_db_external_model
{
    private $db_conf = array(
        'MASTER'=>array('HOST'=>DB_HOST,
        'NAME'=>DB_NAME,
        'USER'=>DB_USER,
        'PASSWORD'=>DB_PASSWORD));
    function __construct($app){
        parent::__construct($app,$this->db_conf);
    }

    function addToQueue($member_id,$company_id, $remind_action, $priority, $valid_time, $tmpl_data="") {
        $create_time = time();
        $sql = "INSERT INTO mall_remind_queue (member_id,remind_action,priority,create_time,valid_time,tmpl_data,company_id)
            VALUES({$member_id},'{$remind_action}',{$priority},{$create_time}, {$valid_time}, '{$tmpl_data}',$company_id)";
        $result = $this->_db->exec($sql);

        if (empty($result)) {
            return false;
        } else {
            return true;
        }
    }

    function getFromQueue($cond) {
        $sql = "SELECT * FROM mall_remind_queue WHERE ".$cond;
        $result = $this->_db->select($sql);

        return $result;
    }

    function delFromQueue($id) {
        $sql = "DELETE FROM mall_remind_queue WHERE id = ".$id;
        $result = $this->_db->exec($sql);

        return ($result ? true : false);
    }


    function getMallRemindQueue($member_id,$company_id){
        $member_id = intval($member_id);
        $sql="select tmpl_data from mall_remind_queue where remind_action='point_award' and member_id=$member_id and company_id = $company_id";
        $tmpl_data=$this->_db->select($sql);
        $countpoint=0;
        if($tmpl_data){
            $name_list = unserialize($tmpl_data[0]['tmpl_data']);
            $countpoint = count($name_list);
        }
        return $countpoint;
    }

}