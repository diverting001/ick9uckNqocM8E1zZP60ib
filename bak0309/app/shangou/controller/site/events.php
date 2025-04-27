<?php
/**
 * 闪购活动列表页面
 * @author Ethan[senpan@vip.qq.com]
 * @version  1.0  2014-07-22 13:55:03
 */
class shangou_ctl_site_events extends shangou_frontpage{


    function __construct($app){
        parent::__construct($app);
        $shopname = app::get('site')->getConf('site.name');
        $this->shopname = $shopname;
        if(isset($shopname)){
            $this->title = app::get('shangou')->_('闪购活动列表_').$shopname;
            $this->keywords = app::get('shangou')->_('闪购活动列表_').$shopname;
            $this->description = app::get('shangou')->_('闪购活动列表_').$shopname;
        }
        $this->header .= '<meta name="robots" content="noindex,noarchive,nofollow" />';
        $this->_response->set_header('Cache-Control', 'no-store');
    }
    
    public function  index($cat_id = null){
    	if($cat_id){
    		$_SESSION['jumptoshangou']['app'] = 'shangou';
    		$_SESSION['jumptoshangou']['ctl'] = 'events';
    		$_SESSION['jumptoshangou']['parameter'] = $cat_id;
    	}
    	//$this->verify_member();
    	
    	if (empty($cat_id)) {
    		$url = $this->gen_url(array('app'=>'site', 'ctl'=>'default', 'act'=>'index'));
    		$this->_response->set_redirect($url)->send_headers();
    	}
    	
    	
    	// 先获其子类
    	$mdl_event_cat = app::get('shangou')->model('cat');
    	
    	$subCat = $mdl_event_cat->getList('cat_id',array('parent_id'=>$cat_id));
    	
    	$filter_catids  =array();
    	foreach ($subCat as $item){
    		$filter_catids[] = intval($item['cat_id']);
    	}
    	$filter_catids[] = intval($cat_id);
    	
    	$filter_catids = array_unique($filter_catids);
     	
    	$mdl_event = app::get('shangou')->model('events');
    	
    	//获取
    	$endingtime = app::get('shangou')->getConf('shangou.event.ending.time');
    	$endingtime = empty($endingtime) ? 24 : $endingtime;
    	$time = time();
    	
    	$etime = $time + $endingtime*60*60;
    	
    	$filter = array('leho_type'=>'false','act_type'=>'normal','evt_type'=>'show','s_time|lthan'=>$time,'e_time|than'=>$etime,'cat_id'=>$filter_catids);
    	$data = $mdl_event->getList('*',$filter);
    	
    	$this->pagedata['eventslist'] = $data;
    	
    	$endetime =  array(
    			'0'=>$time,
    			'1'=>$time  +  $endingtime*60*60,
    	);
    	
    	$endfilter = array('leho_type'=>'false','act_type'=>'normal','evt_type'=>'show','s_time|lthan'=>$time,'e_time|between'=>$endetime,'cat_id'=>$filter_catids);
    	$enddata = $mdl_event->getList('*',$endfilter);
    	
    	$this->pagedata['eventslist_end'] = $enddata;
    	
    	$result = $mdl_event_cat->dump($cat_id,'gallery_setting');
    	if( $result['gallery_setting']['gallery_template'] ){
    		$this->set_tmpl_file($result['gallery_setting']['gallery_template']);                 //添加模板
    	}
    	$this->set_tmpl('events_list');
    	$this->pagedata['request_time_now'] =  app::get('site')->router()->gen_url( array('app'=>'site', 'ctl'=>'default', 'act'=>'request_time_now' ) );
    	$this->page('site/list/index.html');
    }

    
    public function eventsending(){
    	 
    	if(1|| !cachemgr::get('ajax_index_events_ending', $html)) {
    		cachemgr::co_start();
    
    		$mdl_event = app::get('shangou')->model('events');
    		//获取
    		$endingtime = app::get('shangou')->getConf('shangou.event.ending.time');
    		$endingtime = empty($endingtime) ? 24 : $endingtime;
    		$time = time();
    
    		$etime =  array(
    				'0'=>$time,
    				'1'=>$time  +  $endingtime*60*60,
    		);
    
    		$filter = array('leho_type'=>'false','act_type'=>'normal','evt_type'=>'show','s_time|lthan'=>$time,'e_time|between'=>$etime);
    		$data = $mdl_event->getList('*',$filter);
    
    		$url =  app::get('site')->router()->gen_url( array('app'=>'site', 'ctl'=>'default', 'act'=>'request_time_now' ) );
    		$eventdata['url'] = $url;
    		$eventdata['data'] =$data;
    		$this->pagedata['data'] = $eventdata;
    		$this->set_tmpl('events_empty');
    		$html = $this->fetch('site/index/ending.html');
    
    		cachemgr::set('ajax_index_events_ending', $html, cachemgr::co_end());
    	}
    	 
    	echo $html;
    }
    
    public function eventslist(){
    
    	if(1||!cachemgr::get('ajax_index_events_list', $html)) {
    		cachemgr::co_start();
    
    		$mdl_event = app::get('shangou')->model('events');
    
    		//获取
    		$endingtime = app::get('shangou')->getConf('shangou.event.ending.time');
    		$endingtime = empty($endingtime) ? 24 : $endingtime;
    		$time = time();
    
    		$etime = $time + $endingtime*60*60;
    			
    		$filter = array('leho_type'=>'false','act_type'=>'normal','evt_type'=>'show','s_time|lthan'=>$time,'e_time|than'=>$etime);
    		$data = $mdl_event->getList('*',$filter);
    		$this->pagedata['data'] =$data;
    		$this->set_tmpl('events_empty');
    		$html = $this->fetch('site/index/list.html');
    
    		cachemgr::set('ajax_index_events_list', $html, cachemgr::co_end());
    	}
    
    	echo $html;
    }
    
    //返回系统当前时间
    public function request_time_now() {
    	echo time();exit;
    }
    
    public function eventscategory(){
    
    	if(1||!cachemgr::get('ajax_index_events_category', $html)) {
    		cachemgr::co_start();
    
    		$mdl_event = app::get('shangou')->model('events');
    		$cat_mdl = app::get('shangou')->model('cat');
    		$cat_list =$cat_mdl->get_cat_list();
    		$tree = array();
    		$catids = array();
    		foreach ($cat_list as $cat){
    			if ($cat['pid'] == 0) {
    				$tree[$cat['cat_id']] = $cat;
    				$catids[$cat['cat_id']][] = intval($cat['cat_id']);
    			}
    			if ($cat['pid'] != 0) {
    				$tree[$cat['pid']]['child'][] = $cat;
    				$catids[$cat['pid']][] = intval($cat['cat_id']);
    			}
    		}
    		
    		$time = time();
    		$basefilter = array('evt_type'=>'show','s_time|lthan'=>$time,'e_time|than'=>$time);
    		
    		foreach ($catids as $key=>$item){
    			$filter = array_merge($basefilter,array('cat_id'=>$item));
    			$data = $mdl_event->getList('*',$filter,0,8);
    			$tree[$key]['events'] = $data;
    		}
    		unset($catids);
    		$this->pagedata['data'] =$tree;
    		$this->set_tmpl('events_empty');
    		$html = $this->fetch('site/index/eventscategory.html');
    
    		cachemgr::set('ajax_index_events_category', $html, cachemgr::co_end());
    	}
    
    	echo $html;
    }
    
    public function faxian(){
    
    	
    		$obj_goods_single = app::get('b2c')->model('goods_single');
    $obj_goods =app::get('b2c')->model('goods');
	//获取
	$filter = array('sshow'=>'show','platform'=>array('pc','both'));
	$data = $obj_goods_single->getList('*',$filter);
	
	if ( isset( $_COOKIE['MLV'] ) ) {
		if(!cachemgr::get('member_lv_disCount'.$_COOKIE['MLV'],$dis_count)){
			cachemgr::co_start();
			$member_level = $_COOKIE['MLV'];
			(array)$arr = app::get('b2c')->model('member_lv')->getList('dis_count', array('member_lv_id' => $member_level));
			$dis_count = $arr[0]['dis_count'];
			cachemgr::set('member_lv_disCount'.$_COOKIE['MLV'], $dis_count, cachemgr::co_end());
		}
	}
	$objLvprice     = app::get('b2c')->model('goods_lv_price');
	$objProduct     = app::get('b2c')->model('products');
	$gids = array();
	$result = array();
	$order = array();
	foreach ($data as $item){
		$_goods = $obj_goods->dump(array('goods_id'=>$item['goods_id']),'name,price,mktprice');
		$item['name'] = $_goods['name'];
		$item['price'] = $_goods['price'];
		$item['mktprice'] = $_goods['mktprice'];
		$lv_price = $objLvprice->getList('price',array('goods_id'=>$item['goods_id'],'level_id'=>$member_level));
		if ( isset( $dis_count ) ) {
			if(count($lv_price) > 0){
				$lv_price = end($lv_price);
				$item['memprice'] = $lv_price['price'];
			}else{
				$item['memprice'] = $item['price'] * $dis_count;
			}
		}else{
			$item['memprice'] =  $item['price'];
		}
		
		$result[$item['goods_id']] = $item;
		$gids[] = $item['goods_id'];
		$order[$item['goods_id']] = intval($item['sorder']);
	}
	
	$rs = array();
	if($gids){
		$productData = $objProduct->getList('goods_id,product_id,marketable',array('goods_id'=>$gids,'is_default'=>'true'));
		foreach($productData as $k=>$val){
			$rs[$val['goods_id']] = $result[$val['goods_id']];
			$url_params = array('app'=>'b2c','ctl'=>'site_product','act'=>'index','args'=>array($val['product_id']));
			$rs[$val['goods_id']]['goodsLink'] = app::get('site')->router()->gen_url($url_params);
		}
	}
	unset($gids,$result);
	
	logger::info(var_export($order,1));
	logger::info(var_export($rs,1));
	array_multisort($order,SORT_ASC,$rs);
	logger::info(var_export($rs,1));
    		$this->pagedata['data'] =$rs;
    		
    		$this->set_tmpl('events_empty');
    		$html = $this->fetch('site/index/faxian.html');
    
    	echo $html;
    }
    
}

