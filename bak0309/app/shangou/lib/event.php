<?php

class shangou_event{
    
    
	function checkGoodsInCompany($gid){
		//获取出所有符合条件的企业活动
		
		$mdl_event = app::get('shangou')->model('events');
		
		$filter = array('act_type'=>'company','e_time|than'=>time());
		$data = $mdl_event->getList('*',$filter,0,9999999999,'s_time DESC');
		//判断该商品是否在符合条件的企业活动中
		
		if (empty($data)){
			return FALSE;
		}else {
			$obj_events_goods_item = app::get('shangou')->model('events_goods_item');
			$evtids = array();
			foreach ($data as $item){
				$evtids[] = $item['evt_id'];
				$count = $obj_events_goods_item->count(array('evt_id'=>$item['evt_id'],'goods_id'=>$gid));
				if ($count > 0) {
					return $item;
				}
			}	
			return  FALSE;
		}
		
	}
	
	
}
