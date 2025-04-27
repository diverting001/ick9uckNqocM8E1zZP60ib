<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class promotion_mdl_package extends base_db_external_model{
    private $db_conf = array(
        'MASTER'=>array('HOST'=>DB_HOST,
        'NAME'=>DB_NAME,
        'USER'=>DB_USER,
        'PASSWORD'=>DB_PASSWORD)
    );
    
    function __construct($app){
        parent::__construct($app,$this->db_conf);
    }
    
    public function getPkgCompanyInfo($company_id){
        $sql = "SELECT * FROM promotion_voucher_package_company WHERE company_id = {$company_id} AND disabled = 0";
        $res = $this->_db->select($sql);        
        return $res;
    }
    
    public function getAllPkgCompany(){
        $sql = "SELECT * FROM promotion_voucher_package_company WHERE disabled = 0";
        $res = $this->_db->select($sql);        
        return $res;
    }
    
}
