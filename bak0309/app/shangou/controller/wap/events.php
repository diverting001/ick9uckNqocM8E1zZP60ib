<?php

class shangou_ctl_wap_events extends wap_frontpage{


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
    	//$this->verify_member();
    	
    	if (empty($cat_id)) {
    		$url = $this->gen_url(array('app'=>'wap', 'ctl'=>'default', 'act'=>'index'));
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
    	$this->page('wap/list/index.html');
    }

    
    public function eventsending(){
    	 
    	if(1|| !cachemgr::get('ajax_index_events_ending_wap', $html)) {
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
    		$html = $this->fetch('wap/index/ending.html');
    
    		cachemgr::set('ajax_index_events_ending_wap', $html, cachemgr::co_end());
    	}
    	 
    	echo $html;
    }
    
    public function eventslist(){
    
    	if(1||!cachemgr::get('ajax_index_events_list_wap', $html)) {
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
    		$html = $this->fetch('wap/index/list.html');
    
    		cachemgr::set('ajax_index_events_list_wap', $html, cachemgr::co_end());
    	}
    
    	echo $html;
    }
    
    //返回系统当前时间
    public function request_time_now() {
    	echo time();exit;
    }
}

