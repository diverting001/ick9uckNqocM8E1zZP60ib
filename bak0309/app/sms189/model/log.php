<?php

class sms189_mdl_log extends dbeav_model {

	var $defaultOrder = array('log_id','  DESC');
	
    public function batch_insert($data, $msg, $status)
    {
        $msgId = $status == 'succ' ? $msg : 0;
        $msg = $status == 'succ' ? '' : $msg;

         //组写日志sql字符串
        $sql = 'insert into '.$this->table_name(true).'(mobile,template_id,content,mobile_code,status,sendtime,msg,msgid) values';
        foreach ($data as $val) {
        	$_params = json_decode($val['content'],1);
        	$mobile_code = $_params['code'];
        	$val['template_id'] = empty($val['template_id']) ? 0 : $val['template_id'];
            $sql .= '('.$this->db->quote($val['mobile']).','.$this->db->quote($val['template_id']).','.$this->db->quote($val['content']).','.$this->db->quote($mobile_code).','.$this->db->quote($status).','.time().','.$this->db->quote($msg).','.$this->db->quote($msgId).'),';
        }
        $sql = substr($sql, 0, -1);
        //logger::log('neigou=sms189_mdl_log::batch_insert'.print_r($sql,true),3);
		
        return $this->db->exec($sql);
    }

    
    public function gen_code($mobile = '', $code_length = 6){
    	if (empty($mobile)) {
    		return  false;
    	}
        if ($code_length == 0) {
            $code_length=6;
        }

        $code = '';
        for ($i=0; $i<$code_length; $i++) {
            $code .= rand(1, 9);
        }
    	return $code;
    }
}
