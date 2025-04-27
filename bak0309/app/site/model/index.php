<?php

/**
 * Created by PhpStorm.
 * User: hanyun
 * Date: 2015/10/21
 * Time: 11:23
 */
class site_mdl_index extends base_db_external_model
{
    function __construct($app, $dbconfig)
    {
        parent::__construct($app, $dbconfig);
    }

    /**
     * 获得最新加入的十家企业
     * @param int $limit
     * @return mixed
     */
    public function getCompanyInfo($limit = 10)
    {
        $sql = "SELECT member_id,company_name,company_logo,pass_date,staff_amount FROM club_company WHERE company_status=2 AND company_logo LIKE '/Uploads/company/%' ORDER BY pass_date DESC LIMIT {$limit}";
        return $this->_db->select($sql);
    }

    /**
     * 首页数据
     * @return mixed
     */
    public function getNeigouIndexNum()
    {
        $sql = "SELECT * FROM club_statistics_neigou_index limit 1";
        $res=$this->_db->select($sql);
        return $res[0]; 
    }
    
    public function getNeigouMembersNum()
    {
        return  app::get('b2c')->model('members')->count();
    }

    public function getNumber()
    {
        $sql = "SELECT count(company_status) count FROM club_company WHERE company_status=2";
        return $this->_db->count($sql);
    }
}