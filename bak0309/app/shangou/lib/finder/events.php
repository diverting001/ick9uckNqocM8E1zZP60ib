<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class shangou_finder_events {

    function __construct(&$app)
    {
        $this->app = $app;
        $this->router = app::get('site')->router();
    }//End

   var $column_edit = "操作";
	var $column_edit_width = "75";
	function column_edit($row){
		$finder_id = $_GET['_finder']['finder_id'];
		$button = '<a href="index.php?app=shangou&ctl=admin_events&act=edit&p[0]='.$row['evt_id'].'&finder_id='.$finder_id.'" target="dialog::{width:800,height:600,title:\'更新闪购活动\'}">编辑</a>';
		return $button;
	}
	
	public $column_operator = '商品下架操作';
	public $column_operator_width = 110;
	public $column_operator_order = COLUMN_IN_HEAD;
	public function column_operator($row)
	{
		$finder_id = $_GET['_finder']['finder_id'];
		$button= ' <a href="index.php?app=shangou&ctl=admin_events&act=undercarriage&p[0]='.$row['evt_id'].'&finder_id='.$finder_id.'" target="dialog::{width:350,height:150,title:\'活动商品下架\'}">商品下架</a>';
		return $button;
	}
	
    var $column_detail = 'detail';
    function detail_edit($evt_id){
    	$render = app::get('shangou')->render();
    	$obj_events = app::get('shangou')->model('events');
    	$obj_events_goods_item = app::get('shangou')->model('events_goods_item');
    	$obj_events_lv = app::get('shangou')->model('events_lv');
    	$goods_mdl = &app::get('b2c')->model('goods');
    	$brand_mdl = &app::get('b2c')->model('brand');
    	$event = $obj_events->dump($evt_id);
    	$goods_array = $obj_events_goods_item->getList('goods_id,d_order',array('evt_id'=>$evt_id));
    	$goodslist = $goodids = array();
    	
    	if ($goods_array) {
    		foreach ($goods_array as $item){
    			$goodids[] = $item['goods_id'];
    			$order[$item['goods_id']] = $item['d_order'];
    		}
    		$goodslist = $goods_mdl->getlist('goods_id,bn,name,brand_id,price',array('goods_id'=>$goodids));
    		foreach ( $goodslist as &$item){
    			$brand_name=$brand_mdl->dump($item['brand_id'],'brand_name');
    			$item['brand_name'] = $brand_name['brand_name'];
    			$item['order'] = $order[$item['goods_id']];
    		}
    	}
    	$d_order =array();
    	foreach ($goodslist as $key=>$value){
    		$d_order[$key]=$value['order'];
    	}
    	array_multisort($d_order,SORT_ASC,$goodslist);
    	$render->pagedata['goodslist'] = $goodslist;
    	$member_lv_ids = $obj_events_lv->getList('level_id',array('evt_id'=>$evt_id));
    	$level_ids = array();
    	if ($member_lv_ids) {
    		foreach ($member_lv_ids as $mitem){
    			$level_ids[] = $mitem['level_id'];
    		}
    	}
    	
    	$event['member_lv_ids'] = $level_ids;
    	$render->pagedata['event'] = $event;
    	$evt_type_list = array(
    			'noshow'=>'暂不显示',
    			'show' => '预告显示',
    	);
    	$index_show_list = array(
    			'true' => '是',
    			'false' => '否',
    	);
    	$act_type_list = array(
    			'normal'=>'普通活动',
    			'company' => '企业专场',
    	);
    	
    	//////////////////////////// 会员等级 //////////////////////////////
    	$oMemberLevel = app::get('b2c')->model('member_lv');
    	$render->pagedata['member_level'] = $oMemberLevel->getList('*', array(), 0, 10000, 'member_lv_id ASC');
    	$render->pagedata['url'] = app::get('site')->router()->gen_url(array('app'=>'shangou','ctl'=>'site_event','full'=>1,'act'=>'index','arg'=>$evt_id));
    	$render->pagedata['evt_type_list'] = $evt_type_list;
    	$render->pagedata['act_type_list'] = $act_type_list;
    	$render->pagedata['index_show_list'] = $index_show_list;
    	$render->display('admin/events/detail.html');
    
    }


}
