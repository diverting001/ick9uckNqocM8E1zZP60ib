<?php
/**
 * Created by PhpStorm.
 * User: Liuming
 * Date: 2019/3/26
 * Time: 7:52 PM
 */
class unicom_mdl_o2ouser extends base_db_external_model
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
        parent::__construct($app, $this->db_conf);
    }

    /** 获取某一行记录
     *
     * @param $memberId
     * @return mixed
     * @author liuming
     */
    public function getUserInfoRawByMemberId($memberId){
        $sql = 'select * from unicom_o2o_user where member_id = '.$memberId;
        return $this->_db->selectrow($sql);
    }


    /** 增加表信息
     *
     * @param array $data
     * @return bool|int
     * @author liuming
     */
    public function addUserInfo($data = array()){
        if (empty($data)) return false;
        if (!isset($data['createTime']) || empty($data['createTime']))
        $data['createTime'] = time();

        $fields = '`'.implode('`,`',array_keys($data)).'`';
        $values = "'".implode("','",$data)."'";
        $sql = 'insert into unicom_o2o_user ('.$fields.') '.'values('.$values.')';
        $result = $this->_db->exec($sql);
        $id     = 0;
        if ($result['rs']) {
            $id = $this->_db->lastinsertid();
        }
        return $id;
    }

    /** 更新用户信息
     *
     * @param array $whereArr
     * @param array $data
     * @return bool
     * @author liuming
     */
    public function updateUserInfo($whereArr = array(),$data = array()){
        if (empty($whereArr) || empty($data)) return false;

        if (!isset($data['updateTime']) || empty($data['update_time'])){
            $data['updateTime'] = time();
        }

        if ($data['extendInfo']){
            $extendInfo = json_encode($data['extendInfo']);
            unset($data['extendInfo']);
        }

        if ($data['wap_extendInfo']){
            $wap_extendInfo = json_encode($data['wap_extendInfo']);
            unset($data['wap_extendInfo']);
        }

        $where = $set = '';
        foreach ($data as $k => $v){
            $set .= ' `'.$k.'`="'.$v.'",';
        }
        $set = rtrim($set,',');

        if (isset($extendInfo)){
            $set .= ' ,`extendInfo` ='.$extendInfo;
        }

        if (isset($wap_extendInfo)){
            $set .= ' ,`wap_extendInfo` ='.$wap_extendInfo;
        }

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
        $sql = 'UPDATE `unicom_o2o_user` SET '.$set.$where;
        $result = $this->_db->exec($sql);
        if ($result['rs']) {
            return true;
        }
        return false;
    }


}