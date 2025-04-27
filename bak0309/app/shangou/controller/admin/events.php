<?php

class shangou_ctl_admin_events extends desktop_controller{

    public $workground = 'shangou_ctl_admin_events';



    public  function index(){
        $this->title = '闪购活动列表';        
        $params = array(
            'title'=>$this->title,
            'actions' => array(
            	 array(
            		'label' => '新增闪购活动',
            		'href' => 'index.php?app=shangou&ctl=admin_events&act=add',
            		'target' => "dialog::{width:800,height:600,title:'新增闪购活动'}",
            	),
            ),
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
        );
        $this->finder('shangou_mdl_events',$params);
    }

    function add(){
    	$this->_edit();
    }
    
    function edit($evt_id){
    	$this->_edit($evt_id);
    }
    
    private function _edit($evt_id=NULL){
    	if(!empty($evt_id)){
    		$obj_events = &$this->app->model('events');
    		$obj_events_goods_item = $this->app->model('events_goods_item');
    		$obj_events_lv = $this->app->model('events_lv');
    		$goods_mdl = &app::get('b2c')->model('goods');
    		$brand_mdl = &app::get('b2c')->model('brand');
    		$event = $obj_events->dump($evt_id);
    		$goods_array = $obj_events_goods_item->getList('goods_id,d_order',array('evt_id'=>$evt_id));
    		$goodids = array();
    		$goodslist = array();
    		$orderarray = array();
    		if ($goods_array) {
    			foreach ($goods_array as $item){
    				$goodids[] = $item['goods_id'];
    				$orderarray[$item['goods_id']] = $item['d_order'];
    			}
    			$goodslist = $goods_mdl->getlist('goods_id,bn,name,brand_id,price',array('goods_id'=>$goodids));
    			foreach ( $goodslist as &$item){
    				$brand_name=$brand_mdl->dump($item['brand_id'],'brand_name');
    				$item['brand_name'] = $brand_name['brand_name'];
    				$item['order'] = $orderarray[$item['goods_id']];
    			}
    		}
    		$d_order =array();
    		foreach ($goodslist as $key=>$value){
    			$d_order[$key]=$value['order'];
    		}
    		array_multisort($d_order,SORT_ASC,$goodslist);
    		$this->pagedata['goodslist'] = $goodslist;
    		$member_lv_ids = $obj_events_lv->getList('level_id',array('evt_id'=>$evt_id));
    		$level_ids = array();
    		if ($member_lv_ids) {
    			foreach ($member_lv_ids as $mitem){
    				$level_ids[] = $mitem['level_id'];
    			}
    		}
    		
			$event['member_lv_ids'] = $level_ids;
			$this->pagedata['event'] = $event;
    	}
    	$evt_type_list = array(
    			'noshow'=>'暂不显示',
    			'show' => '预告显示',
    	);
    	$act_type_list = array(
    			'normal'=>'普通活动',
    			'company' => '企业专场',
    	);
    	$index_show_list = array(
    			'true' => '是',
    			'false' => '否',
    	);
    	$leho_type_list = array(
    			'true' => '是',
    			'false' => '否',
    	);
    	//////////////////////////// 会员等级 //////////////////////////////
    	$oMemberLevel = app::get('b2c')->model('member_lv');
    	$this->pagedata['member_level'] = $oMemberLevel->getList('*', array(), 0, 10000, 'member_lv_id ASC');
    	
    	$this->pagedata['evt_type_list'] = $evt_type_list;
    	$this->pagedata['act_type_list'] = $act_type_list;
    	$this->pagedata['index_show_list'] = $index_show_list;
    	$this->pagedata['leho_type_list'] = $leho_type_list;
    	$this->display("admin/events/add_event.html");
    }
    
    function saveEvent(){
    	$url = 'index.php?app=shangou&ctl=admin_events&act=index';
    	$this->begin($url);
    	$event_data = $_POST['event'];
    	#开始与结束时间
    	foreach ($_POST['_DTIME_'] as $val) {
    		$temp['s_time'][] = $val['s_time'];
    		$temp['e_time'][] = $val['e_time'];
    	}
    	
    	$event_data['s_time'] = strtotime($_POST['s_time'].' '. implode(':', $temp['s_time']));
    	$event_data['e_time'] = strtotime($_POST['e_time'].' '. implode(':', $temp['e_time']));
    	
    	#商品直供选项
    	if ($event_data['leho_type'] == 'true') {
    		//没有结束时间
    		$event_data['e_time'] =  strtotime("+10 year");
    	}
    	
    	#商品
    	
    	$goodids = array();
    	if (!empty($_POST['goods_id'])) {
    		foreach ($_POST['goods_id'] as  $val){
    			$goodids[$val] = array(
    					'goods_id'=>$val,
    			);
    		}
    	}
    	
    	$order = array();
    	if (!empty($_POST['order'])) {
    		foreach ($_POST['order'] as $key=> $val){
    			$order[$key] = $val;
    		}
    	}
    	#会员等级
    	$member_lv_ids = array();
    	foreach ($_POST['member_lv_ids'] as  $val){
    		$member_lv_ids[$val] = array(
    				'level_id'=>$val,
    		);
    	}
    	
    	$obj_events = $this->app->model('events');
    	$obj_events_goods_item = $this->app->model('events_goods_item');
    	$obj_events_lv = $this->app->model('events_lv');
    	$rs = true;
    	//先保存活动
    	if ($event_data['evt_id']||!empty($event_data['evt_id'])) {
    		//如果群组存在，匹配商品信息，delete/add
    		$evt_id = $event_data['evt_id'];
    		unset($event_data['evt_id']);
    		$obj_events->update($event_data,array('evt_id'=>$evt_id));
    		#商品
    		$old_goods = $obj_events_goods_item->getList('*',array('evt_id'=>$evt_id));
    		$del_array = array();
    		$up_array = array();
    		if ($old_goods) {
    			foreach ($old_goods as $key=>$val){
    				if (isset($goodids[$val['goods_id']])) {
    					if ($val['d_order'] != $order[$val['goods_id']]) {
    						$up_array[] = $val['goods_id'];
    					}
    					unset($goodids[$val['goods_id']]);
    				}else{
    					$del_array[] = $val['goods_id'];
    				}
    			}
    		}
    		//删除  商品
    		if (!empty($del_array)&& count($del_array)>=1) {
    			$obj_events_goods_item->delete(array('goods_id'=>$del_array,'evt_id'=>$evt_id));
    		}
    		//update 商品
    		if (!empty($up_array)&& count($up_array)>=1) {
    			$rs = $obj_events_goods_item->batch_update($up_array,$order,$evt_id);
    		}
    		
    		//新增 商品
    		if ($rs&&!empty($goodids)&& count($goodids)>=1) {
    			$rs = $obj_events_goods_item->batch_insert($goodids,$order,$evt_id);
    		}
    		
    		if ($rs) {
    			#会员等级
    			$old_member_lv_ids = $obj_events_lv->getList('*',array('evt_id'=>$evt_id));
    			$del_mlv_array = array();
    			if ($old_member_lv_ids) {
    				foreach ($old_member_lv_ids as $key=>$val){
    					if (isset($member_lv_ids[$val['level_id']])) {
    						unset($member_lv_ids[$val['level_id']]);
    					}else{
    						$del_mlv_array[] = $val['level_id'];
    					}
    				}
    			}
    			
    			//删除  会员等级
    			if (!empty($del_mlv_array)&& count($del_mlv_array)>=1) {
    				$obj_events_lv->delete(array('level_id'=>$del_mlv_array,'evt_id'=>$evt_id));
    			}
    			//新增  会员等级
    			if (!empty($member_lv_ids)&& count($member_lv_ids)>=1) {
    				$rs = $obj_events_lv->batch_insert($member_lv_ids,$evt_id);
    			}
    		}
    	}else{
    		//没有活动，add
    		unset($event_data['evt_id']);
    		$obj_events->insert($event_data);
    		$insert_id = $obj_events->db->lastinsertid();
    		$rs = true;
    		if ($goodids) {
    			$rs = $obj_events_goods_item->batch_insert($goodids,$order,$insert_id);
    		}
    		
    		if ($rs) {
    			if ($member_lv_ids) {
    				$rs = $obj_events_lv->batch_insert($member_lv_ids,$insert_id);
    			}
    		}
    	}
    	$res['status'] = 'success';
    	if ($rs === false) {
    		$res['status'] = 'fail';
    	}
    	$this->end($rs,$rs===false? '闪购活动编辑失败':'闪购活动编辑成功','',$res);
    }

    
    public function public_find_goods_list(){
    	$base_filter = array();//array('marketable'=>'true');
    	$params = array(
    			'title'=>'商品列表',
    			'use_buildin_new_dialog' => false,
    			'use_buildin_set_tag'=>false,
    			'use_buildin_recycle'=>false,
    			'use_buildin_export'=>false,
    			'use_buildin_import'=>false,
    			'use_buildin_filter'=>true,
    			'base_filter' => $base_filter,
    	);
    	$this->finder('b2c_mdl_goods', $params);
    }
    
    
    /**
     * 查询商品信息
     *
     * @access public
     * @return JSON
     */
    public  function public_find_goods_detail_json(){
    	$filter = array();
    	if($_POST['goods_id'][0] != '_ALL_'){
    		$filter['goods_id'] = $_POST['goods_id'];
    	}
    	$goods_mdl = &app::get('b2c')->model('goods');
    	$brand_mdl = &app::get('b2c')->model('brand');
    	$goods_mdl->filter_use_like = true;
    	$goods = $goods_mdl->getlist('goods_id,bn,name,brand_id,price',$filter);
    	foreach ( $goods as &$item){
    		$brand_name=$brand_mdl->dump($item['brand_id'],'brand_name');
    		$item['brand_name'] = $brand_name['brand_name'];
    	}
    	print_r(json_encode($goods)) ;
    }
    
    
    public function undercarriage($evt_id = null){
    	if (empty($evt_id)){
    		
    	}else{
    		$this->pagedata['evt_id'] = $evt_id;
    	}
    	$this->display("admin/events/goods/undercarriage.html");
    }
    
    public function do_undercarriage(){
    	$url = 'index.php?app=shangou&ctl=admin_events&act=index';
    	$this->begin($url);
    	$rs = false;
    	if (isset($_POST['evt_id'])) {
    		$obj_events = $this->app->model('events');
    		$obj_events_goods_item = $this->app->model('events_goods_item');
    		$evt_id = intval($_POST['evt_id']);
    		$event = $obj_events->dump($evt_id);
    		if (!empty($event)) {
    			$goods_array = $obj_events_goods_item->getList('goods_id',array('evt_id'=>$evt_id));
    			$goodids = array();
    			foreach ($goods_array as $item){
    				$goodids[] = $item['goods_id'];
    			}
    			if (!empty($goodids)) {
    				$goods_mdl = app::get('b2c')->model('goods');
    				$rs = $goods_mdl->update(array('marketable'=>'false'),array('goods_id'=>$goodids));
    			}
    		}
    	}
    	$this->end($rs,$rs===false? '闪购活动商品下架失败':'闪购活动商品已经成功下架');
    }
}
