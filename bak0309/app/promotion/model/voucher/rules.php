<?php

class promotion_mdl_voucher_rules extends base_db_external_model {

    function __construct($app,$dbconfig){
        parent::__construct($app,$dbconfig);
    }

    // 根据规则名称获取相应的规则ID
    function get_rule_id($rule_name) {
        $rule_name = trim($rule_name);
        if (!$rule_name) {
            return false;
        }

        $sql = "select rule_id from promotion_voucher_rules where name = '{$rule_name}'";
        $res = $this->_db->selectrow($sql);

        if (is_array($res)) {
            return $res['rule_id'];
        } else {
            return false;
        }
    }

    function getRulesList(){
        $sql = "select * from promotion_voucher_rules where disabled = 0 order by create_time desc";
        $res = $this->_db->select($sql);
        return $res;
    } 
}