<?php

class shangou_mdl_events_lv extends dbeav_model {

	var $defaultOrder = array('el_id','  DESC');
	
    public function batch_insert($data = array(),$evt_id = null){
    	if (empty($data) ||empty($evt_id) ) {
    		return  false;
    	}
        //组写sql字符串
        $sql = 'insert into '.$this->table_name(true).'(`evt_id`,`level_id`) values';
        foreach ($data as $val) {
            $sql .= '('.$this->db->quote($evt_id).','.$this->db->quote($val['level_id']).'),';
        }
        $sql = substr($sql, 0, -1).";";
        return $this->db->exec($sql);
    }

}
