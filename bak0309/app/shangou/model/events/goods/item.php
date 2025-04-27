<?php

class shangou_mdl_events_goods_item extends dbeav_model {

	var $defaultOrder = array('d_order',' ASC',',egi_id',' DESC');
	
    public function batch_insert($data = array(),$order= array(),$evt_id = null){
    	if (empty($data) ||empty($order)||empty($evt_id) ) {
    		return  false;
    	}
        //组写sql字符串
        $sql = 'insert into '.$this->table_name(true).'(`evt_id`,`goods_id`,`d_order`) values';
        foreach ($data as $val) {
            $sql .= '('.$this->db->quote($evt_id).','.$this->db->quote($val['goods_id']).','.$this->db->quote($order[$val['goods_id']]).'),';
        }
        $sql = substr($sql, 0, -1).";";
        return $this->db->exec($sql);
    }
    
    public function batch_update($data = array(),$order= array(),$evt_id = null){
    	if (empty($data) ||empty($order)||empty($evt_id) ) {
    		return  false;
    	}
    	//组写sql字符串
    	foreach ($data as $val) {
    		$sql ='';
    		$sql = 'UPDATE '.$this->table_name(true). ' SET `d_order` = '.$this->db->quote($order[$val]).'  WHERE `evt_id` = '.$this->db->quote($evt_id).' AND `goods_id` = '.$this->db->quote($val).';' ;
    		$rs = $this->db->exec($sql);
    		if ($rs ==false) {
    			return false;
    		}
    	}
    	return true;
    }

}
