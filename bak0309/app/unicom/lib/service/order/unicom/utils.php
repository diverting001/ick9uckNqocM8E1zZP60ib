<?php

class unicom_service_order_unicom_utils
{
    public function getLogicLock($key_lock,$timeout = 100)
    {
        $db = kernel::database();
        $sql = "select get_lock('{$key_lock}',$timeout) as `lock`;";
        $result = $db->select($sql);
        return $result[0]['lock'] == 1 ? true : false;
    }
    
    public function releaseLogicLock($key_lock)
    {
        $db = kernel::database();
        $sql = "select release_lock('{$key_lock}') as `lock`;";
        $result = $db->select($sql);
        return $result[0]['lock'] == 1 ? true : false;
    }
    
    
}
