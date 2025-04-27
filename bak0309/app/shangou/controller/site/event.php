<?php
/**
 * 闪购活动详情页面
 * @author Ethan[senpan@vip.qq.com]
 * @version  1.0  2014-07-22 13:55:03
 */
class shangou_ctl_site_event extends shangou_frontpage{


    function __construct($app){
        parent::__construct($app);
        $shopname = app::get('site')->getConf('site.name');
        $this->shopname = $shopname;
        if(isset($shopname)){
            $this->title = app::get('shangou')->_('闪购活动详情_').$shopname;
            $this->keywords = app::get('shangou')->_('闪购活动详情_').$shopname;
            $this->description = app::get('shangou')->_('闪购活动详情_').$shopname;
        }
        
        $this->header .= '<meta name="robots" content="noindex,noarchive,nofollow" />';
        $this->_response->set_header('Cache-Control', 'no-store');
        //$this->verify_member();
        $this->pagedata['res_url'] = $this->app->res_url;
    }

    public function  index($evt_id = null){
    	if (empty($evt_id)) {
    		kernel::single('site_router')->http_status(404);return;
    	}
    	$week = array(
    		'1'=>'一',
    		'2'=>'二',
    		'3'=>'三',
    		'4'=>'四',
    		'5'=>'五',
    		'6'=>'六',
    		'7'=>'七',
    	);
    	$obj_events = &$this->app->model('events');
    	$obj_events_goods_item = app::get('shangou')->model('events_goods_item');
    	$obj_events_lv = app::get('shangou')->model('events_lv');
    	$goods_mdl = &app::get('b2c')->model('goods');
    	#闪购活动基础信息
    	$event = $obj_events->dump($evt_id);
    	$shopname = app::get('site')->getConf('site.name');
    	$shopname = isset($shopname) ? $shopname : '乐货';
    	$this->title = $event['evt_name'].'_'.$shopname;
    	$this->keywords = $event['evt_name'].'_'.$shopname;
    	$this->description = $event['evt_name'].'_'.$shopname;
    	
    	$startflag = true;
    	if (intval($event['s_time']) > time()) {
    		#$lefttime = "活动将于星期".$week[date('N',$event['e_time'])]." ".date('h',$event['e_time']).' '.date('A',$event['e_time'])."开始";
    		$lefttime = "活动将于 ".date("Y-m-d H:i",$event['s_time'])." 开始销售";
    		$startflag = false;
    	}else {
    	
	    	$lefttime = '还有';
	    	$lt = intval(intval($event['e_time']) - time());
	    	
	    	$day = intval($lt/(24*60*60));
	    	
	    	if ($day > 0) {
	    		$lefttime .= $day."天结束";
	    	}else{
	    		$hour =  intval($lt/(60*60));
	    		if ($hour >= 3) {
	    			$lefttime .= $hour."小时结束";
	    		}else{
	    			#$lefttime = "活动于星期".$week[date('N',$event['e_time'])]." ".date('h',$event['e_time']).' '.date('A',$event['e_time'])."结束";
	    			$lefttime = "活动于".date("Y-m-d H:i",$event['e_time'])." 已经结束";
	    		}
	    	}
    	}
    	$this->pagedata['lefttime'] = $lefttime;
    	$this->pagedata['startflag'] = $startflag;
    	if (intval($event['e_time']) < time() ) {
    		//活动已经结束
    	}else{
	    	
	    	#闪购活动商品信息
	    	$goods_array = $obj_events_goods_item->getList('goods_id,d_order',array('evt_id'=>$evt_id));

	    	$goodids = array();
	    	$order = array();
	    	foreach ($goods_array as $item){
	    		$goodids[] = intval($item['goods_id']);
	    		$order[intval($item['goods_id'])] = intval($item['d_order']);
	    	}
	    	$goodslist = $goods_mdl->getlist('cat_id,goods_id,bn,name,price,mktprice,image_default_id,nostore_sell,spec_desc,type_id',array('goods_id'=>$goodids),0,9999999,' uptime desc ');
	    	$goodslist = $this->get_product($goodids, $goodslist,$order);
	    	$catids = array();
	    	foreach ($goodslist as $item){
	    		$catids[] = $item['cat_id'];
	    	}
	    	$catids = array_unique($catids);
	    	
	    	$cat_id = $catids[0];
	    	$params['params'] = array();
	    	$screen = $this->screen($cat_id,$params['params'],$goodslist);
	    	#echo '<pre>';
	    	#echo '$cat_id=>'.var_export($catids,1);
	    	#var_export($screen);
	    	#exit;
	    	$this->pagedata['screen'] = $screen['screen'];
	    	$this->pagedata['orderby_sql'] = $params['orderby'];
	    	$this->pagedata['goodslist'] = $goodslist;
	    	$this->pagedata['goodsnum'] = count($goodslist);
	    	#会员等级信息
	    	$member_lv_ids = $obj_events_lv->getList('level_id',array('evt_id'=>$evt_id));
	    	$level_ids = array();
	    	foreach ($member_lv_ids as $mitem){
	    		$level_ids[] = $mitem['level_id'];
	    	}
	    	$event['member_lv_ids'] = $level_ids;
    	}
    	$this->pagedata['event'] = $event;
    	$this->set_tmpl('events_detail');
    	
    	//判断是否设置了模版文件
    	if($event['enevt_template']){
    		$this->set_tmpl_file($event['enevt_template']);                 //添加模板
    	}
    	
    	$this->page('site/detail/index.html');
    }
    
    private function get_product($gids, $goodsData,$order) {
    	$productModel = app::get('b2c')->model('products');
    	$products =  $productModel->getList('*',array('goods_id'=>$gids,'is_default'=>'true','marketable'=>'true'));
    	$sdf_product = array();
    	$prenext = array();
    	$pre = 0;
    	foreach($products as $key=>$row){
    		$sdf_product[$row['goods_id']] = $row;
    	}
    	$siteMember = $this->get_current_member();
    	$store_array = array();
    	$underzore  =array();
    	foreach ($goodsData as $gk=>$goods_row) {
    		
    		//排序
    		
    		$goodsData[$gk]['d_order'] = $order[$goods_row['goods_id']];
    		
    		$product_row = $sdf_product[$goods_row['goods_id']];
    		$goodsData[$gk]['products'] = $product_row;
    		
    		//市场价
    		if($product_row['mktprice'] == '' || $product_row['mktprice'] == null)
    			$goodsData[$gk]['products']['mktprice'] = $productModel->getRealMkt($product_row['price']);
    		$priceinfo[$product_row['product_id']] = $this->_get_product_price($product_row['product_id'], $goodsData,$siteMember['member_lv']);
    		
    		//所有SKU的库存
    		if ($goods_row['nostore_sell']) {
    			$goodsData[$gk]['skustore'] = 999999;
    		}else{
	    		$skudata = $productModel->getList('store,freez',array('goods_id'=>$goods_row['goods_id'],'marketable'=>'true'));
	    		$_stroe = 0;
	    		foreach ($skudata as $skuitem){
	    			$skuitem['freez']  = empty($skuitem['freez']) ? 0 : $skuitem['freez'];
	    			$_stroe += $skuitem['store'] - $skuitem['freez'];
	    			$_stroe = $_stroe <= 0 ? 0:$_stroe;
	    		}
	    		$goodsData[$gk]['skustore'] = $_stroe;
	    		if ($_stroe <= 0) {
	    			$underzore[] = $goodsData[$gk];
	    			unset( $goodsData[$gk]);
	    		}
	    		
    		}
    		/*if($goods_row['nostore_sell']  || $product_row['store'] === null){
    			$goodsData[$gk]['products']['store'] = 999999;
    		}else{
    			$store = $product_row['store'] - $product_row['freez'];
    			$goodsData[$gk]['products']['store'] = $store > 0 ? $store : 0;
    		}
    		if ($goodsData[$gk]['products']['store'] <= 0) {
    			$underzore[] = $goodsData[$gk];
    			unset( $goodsData[$gk]);
    		}*/
    	}
    	
    	if (!empty($order)) {
    		$d_order = array();
	    	foreach ( $goodsData as $key => $value){
	    		$d_order[$key] = $value['d_order'];
	    	}
	    	
	    	array_multisort($d_order,SORT_ASC,$goodsData);
    	}
    
    	$goodsData = array_merge($goodsData,$underzore);
    	
    	
    	foreach ($goodsData as $gk=>$goods_row) {
    		#pre  next
    		$prenext[]  = intval($goods_row['products']['product_id']);
    	}
    	$prenextkey  = array_flip($prenext);
    	$this->pagedata['prenextpid'] = $prenext;
    	$this->pagedata['prenextkey'] = $prenextkey;
    	$this->pagedata['priceinfo'] = $priceinfo;
    	return $goodsData;
    }
    
    
   
    
    function _get_product_price($productId,$aGoods,$member_lv){
    	$goodsPrice = array();
    	$productsModel =  app::get('b2c')->model("products");
    	//市场价
    		if( $aGoods['product']['mktprice'] == '' || $aGoods['product']['mktprice'] == null ){
    			$mktprice = $aGoods['mktprice'];
    		}else{
    			$mktprice = $aGoods['product']['mktprice'];
    		}
    		if( $mktprice == '' || $mktprice == null ){
    			$productMemberPrice['mktprice'] = $productsModel->getRealMkt($aGoods['price']);
    		}else{
    			$productMemberPrice['mktprice'] = $mktprice;
    		}
    
    		//会员价
    		$memberLv = app::get('b2c')->model('member_lv')->getList('member_lv_id,name,dis_count');
    		$customMemberPrice = app::get('b2c')->model('goods_lv_price')->getList('*',array('product_id'=>$productId));
    		if(!empty($customMemberPrice) ){
    			foreach($customMemberPrice as $value){
    				$tempCustom[$value['level_id']] = $value;
    			}
    		}
    		$minPrice = null;
    		$i = 0;
    		foreach($memberLv as $memberValue){
    			if( !empty($tempCustom[$memberValue['member_lv_id']]) ){
    				$productMemberPrice['mlv_price'][$i]['name'] = $memberValue['name'];
    				$productMemberPrice['mlv_price'][$i]['price'] = $tempCustom[$memberValue['member_lv_id']]['price'];
    			}else{
    				$productMemberPrice['mlv_price'][$i]['name'] = $memberValue['name'];
    				$productMemberPrice['mlv_price'][$i]['price'] = $aGoods['product']['price'] * $memberValue['dis_count'];
    				if($memberValue['member_lv_id'] == $member_lv){
    					$productMemberPrice['price'] = $productMemberPrice['mlv_price'][$i]['price'];
    				}
    			}
    			if($memberValue['member_lv_id'] == $member_lv){
    				$productMemberPrice['current_price'] = $productMemberPrice['mlv_price'][$i];
    			}
    			if($minPrice === null ){
    				$minPrice = $productMemberPrice['mlv_price'][$i]['price'];
    			}else{
    				if($minPrice >= $productMemberPrice['mlv_price'][$i]['price']){
    					$minPrice = $productMemberPrice['mlv_price'][$i]['price'];
    				}
    			}
    			$i++;
    		}
    	$productMemberPrice['minprice'] = $minPrice;
    	return $productMemberPrice;
    }
    
    /*
     * 根据分类ID提供筛选条件，并且返回已选择的条件数据
    *
    * @params int $cat_id 分类ID
    * @params array $filter 已选择的条件
    * */
    private function screen($cat_id,$filter,$goodsList){
    	$goods_type = app::get('b2c') ->model('goods_type');
    	
    	if ( empty($cat_id) ) {
    		$screen = array();
    	}
    	
    	$screen['cat_id'] = $cat_id;
    	$cat_id = $cat_id ?  $cat_id : $this->pagedata['show_cat_id'];
    	//搜索时的分类
    	if(!$screen['cat_id'] && count($this->pagedata['catArr']) > 1){
    		$searchCat = app::get('b2c')->model('goods_cat')->getList('cat_id,cat_name',array('cat_id'=>$this->pagedata['catArr']));
    		$i=0;
    		foreach($this->catCount as $catid=>$num){
    			$sort[$catid] = $i;
    			if($i == 9) break;
    			$i++;
    		}
    		foreach($searchCat as $row){
    			$screen['search_cat'][$sort[$row['cat_id']]] = $row;
    		}
    		ksort($screen['search_cat']);
    	}
    
    	$sCatData = app::get('b2c')->model('goods_cat')->getList('cat_id,cat_name',array('parent_id'=>$cat_id));
    	$screen['cat'] = $sCatData;
    
    	cachemgr::co_start();
    	if(!cachemgr::get("eventObjectCat".$cat_id, $catInfo)){
    		$goodsInfoCat = app::get("b2c")->model("goods_cat")->getList('*',array('cat_id'=>$cat_id) );
    		$catInfo = $goodsInfoCat[0];
    		cachemgr::set("eventObjectCat".$cat_id, $catInfo, cachemgr::co_end());
    	}
    	$this->goods_cat = $catInfo['cat_name'];//seo
    	
    
    	cachemgr::co_start();
    	if(!cachemgr::get("eventObjectType".$catInfo['type_id'], $typeInfo)){
    		$typeInfo = app::get("b2c")->model("goods_type")->dump2(array('type_id'=>$catInfo['type_id']) );
    		cachemgr::set("eventObjectType".$catInfo['type_id'], $typeInfo, cachemgr::co_end());
    	}
    	$this->goods_type = $typeInfo['name'];//seo
    	$typeInfo['price']['price_row']['min'] = 100;
    	$typeInfo['price']['price_row']['max'] = 500;
    	
    	if($typeInfo['price'] && $filter['price'][0]){
    		$active_filter['price']['title'] = $this->app->_('价格');
    		$active_filter['price']['label'] = 'price';
    		$active_filter['price']['options'][0]['data'] =  $filter['price'][0];
    		foreach($typeInfo['price'] as $key=>$price){
    			$price_filter = implode('~',$price);
    			if($filter['price'][0] == $price_filter){
    				$typeInfo['price'][$key]['active'] = 'active';
    				$active_arr['price'] = 'active';
    			}
    			$active_filter['price']['options'][0]['name'] = $filter['price'][0];
    		}
    	}
    	if(count($typeInfo['price']) == 0){
    		$typeInfo['price'] = true;
    	}
    	$screen['price'] = $typeInfo['price'];
    	
    	
    	//整理商品类型
    	$type_ids = array();
    	foreach ($goodsList as $v){
    		if(in_array($v['type_id'],$type_ids)){
    			continue;
    		}else{
    			$type_ids[] = $v['type_id'];
    		}
    	}
    	//获取集合中所有商品类型
    	$typeList = $goods_type->getList('*',array('type_id|in'=>$type_ids));//集合中的商品类型
    	//获取集合中的所有规格值
    	$allSpecV = array();
    	foreach ($goodsList as $g){
    		$value_id = $g['spec_desc'];
    		foreach ($value_id as $k=>$v){
    			foreach ($v as $k2=>$v2){
    				if(in_array($v2['spec_value_id'],$allSpecV)){
    					continue;
    				}else{
    					$allSpecV[] = $v2['spec_value_id'];
    				}
    			}			
    		}
    	}
    	//根据商品类型获取价格区间 
    	foreach ($typeList as $v){
    		foreach ($v['price'] as $vp){
    			$priceList[] = $vp;
    		}
    	}
    	$screen['price'] = $priceList;
    	//品牌
//     	if ( $typeInfo['setting']['use_brand'] ){
//     		$type_brand = app::get('b2c')->model('type_brand')->getList('brand_id',array('type_id'=>$catInfo['type_id']));
//     		if ( $type_brand ) {
//     			foreach ( $type_brand as $brand_k=>$brand_row ) {
//     				$brand_ids[$brand_k] = $brand_row['brand_id'];
//     			}
//     		}
//     		$brands = app::get('b2c')->model('brand')->getList('brand_id,brand_name',array('brand_id'=>$brand_ids,'disabled'=>'false'));
//     		//是否已选择
//     		foreach($brands as $b_k=>$row){
//     			if(in_array($row['brand_id'],$filter['brand_id'])){
//     				$brands[$b_k]['active'] = 'active';
//     				$active_arr['brand'] = 'active';
//     				$active_filter['brand']['title'] = $this->app->_('品牌');;
//     				$active_filter['brand']['label'] = 'brand_id';
//     				$active_filter['brand']['options'][$row['brand_id']]['data'] =  $row['brand_id'];
//     				$active_filter['brand']['options'][$row['brand_id']]['name'] = $row['brand_name'];
//     			}
//     		}
//     		$screen['brand'] = $brands;
//     	}
    	
    	//扩展属性
//     	if ( $typeInfo['setting']['use_props'] && $typeInfo['props'] ){
//     		foreach ( $typeInfo['props'] as $p_k => $p_v){
//     			if ( $p_v['search'] != 'disabled' ) {
//     				$props[$p_k]['name'] = $p_v['name'];
//     				$props[$p_k]['goods_p'] = $p_v['goods_p'];
//     				$props[$p_k]['type'] = $p_v['type'];
//     				$props[$p_k]['search'] = $p_v['search'];
//     				$props[$p_k]['show'] = $p_v['show'];
//     				$propsActive = array();
//     				if($p_v['options']){
//     					foreach($p_v['options'] as $propItemKey=>$propItemValue){
//     						$activeKey = 'p_'.$p_v['goods_p'];
//     						if($filter[$activeKey] && in_array($propItemKey,$filter[$activeKey])){
//     							$active_filter[$activeKey]['title'] = $p_v['name'];
//     							$active_filter[$activeKey]['label'] = $activeKey;
//     							$active_filter[$activeKey]['options'][$propItemKey]['data'] =  $propItemKey;
//     							$active_filter[$activeKey]['options'][$propItemKey]['name'] = $propItemValue;
//     							$propsActive[$propItemKey] = 'active';
//     						}
//     					}
//     				}
//     				$props[$p_k]['options'] = $p_v['options'];
//     				$props[$p_k]['active'] = $propsActive;
//     			}
//     		}
    
//     		$screen['props'] = $props;
//     	}
    
    	//规格
//     	$gType = &app::get('b2c')->model('goods_type');
//     	$SpecList = $gType->getSpec($catInfo['type_id'],1);//获取关联的规格
//     	if($SpecList){
//     		foreach($SpecList as $speck=>$spec_value){
//     			if($spec_value['spec_value']){
//     				foreach($spec_value['spec_value'] as $specKey=>$SpecValue){
//     					$activeKey = 's_'.$speck;
//     					if($filter[$activeKey] && in_array($specKey,$filter[$activeKey])){
//     						$active_filter[$activeKey]['title'] = $spec_value['name'];
//     						$active_filter[$activeKey]['label'] = $activeKey;
//     						$active_filter[$activeKey]['options'][$specKey]['data'] =  $specKey;
//     						$active_filter[$activeKey]['options'][$specKey]['name'] = $SpecValue['spec_value'];
//     						$specActive[$specKey] = 'active';
//     					}
//     				}
//     			}
//     			$SpecList[$speck]['active'] = $specActive;
//     		}
//     	}
// 		//根据商品类型获取规格
//     	$specsR = app::get('b2c')->model('goods_type_spec')->getList('*',array('type_id|in'=>$type_ids));//集合中商品类型相关的规格id
//     	$spec_ids = array();
//     	foreach ($specsR as $v){
//     		if(in_array($v['spec_id'],$spec_ids)){
//     			continue;
//     		}else{
//     			$spec_ids[] = $v['spec_id'];
//     		}
//     	}
//     	$allSpecs = app::get('b2c')->model('specification')->getList('*',array('spec_id|in'=>$spec_ids));//规格信息
//     	//获取集合中所有商品类型的值
//     	$specValues = app::get('b2c')->model('spec_values')->getList('*',array('spec_id|in'=>$spec_ids));//规格值
//     	//组合商品规格值
    	
//     	格式如下
//		[1] => Array
//     	(
//     			[name] => 颜色
//     			[spec_type] => text
//     			[spec_memo] =>
//     			[spec_style] => flat
//     			[spec_value] => Array
//     			(
//     					[1] => Array
//     					(
//     							[spec_value] => 黑色
//     							[spec_image] => 5b0a1c5e1d310d816a92c277406c44c9
//     					)
//				)
//    			[type] => spec
//		)
		
    	
//     	foreach ($specsR as $v){
//     		if($v['spec_style']<>'disabled'){
//     			 $itemArray['spec_style'] = $v['spec_style'];
//     			 $itemArray['type'] = 'spec';
//     			foreach ($allSpecs as $va){
//     				if($va['spec_id'] == $v['spec_id']){
//     					$itemArray['name'] = $va['alias'];
//     					$itemArray['spec_type'] = $va['spec_type'];
//     					$itemArray['spec_memo'] = $va['spec_memo'];
//     					$specValue = array();
//     					foreach ($specValues as $vs){
//     						foreach ($allSpecV as $ak=>$av){
//     							if(($vs['spec_value_id'] == $av) && ($vs['spec_id'] == $va['spec_id'])){
//     								if(in_array($vs, $specValue)){
//     									continue;
//     								}else{
//     									$specValue[] = $vs;
//     								}
//     							}
//     						}
//     					}
//     					$itemArray['spec_value'] = $specValue;
//     				}
//     			}
//     			$SpecList[$v['spec_id']] = $itemArray;
//     		}
//     	}
    	
    	//print_r($SpecList);
    	
//根据商品筛选规格
    	$type_ids = null;
    	$SpecList = array();
    	foreach ($goodsList as $v){
    		if(in_array($v['type_id'],$type_ids)){
    			continue;
    		}else{
    			$type_ids[] = $v['type_id'];
    			$spec = $goods_type->getSpec($v['type_id'],1);
    			foreach ($spec as $vv){
    				$SpecList[$v['type_id']][] = $vv;
    			}
    		}
    	}
    	foreach ($SpecList as $typeId=>$spec){
    		foreach ($spec as $ks=>$s){
    			$specValue = $s['spec_value'];
    			foreach ( $specValue as $k=>$v){
    				if(in_array($k, $allSpecV)){
    					continue;
    				}else{
    					unset($SpecList[$typeId][$ks]['spec_value'][$k]);
    				}
    			}
    		}
    	}
    	foreach ($SpecList as $typeId=>$spec){
    		foreach ($spec as $ks=>$s){
    			if(count($s['spec_value']) > 0){
    				$SpecListRes[] = $s;
    			}	
    		}
    	}
    	
    	$screen['spec'] = $SpecListRes; 
    	
    	//print_r($allSpecV);
    
    	//标签
    	$tagFilter['app_id'][] = 'b2c';
    	$tags = app::get('desktop')->model('tag')->getList('*',$tagFilter);
    	if($filter['pTag']){
    		$active_arr['pTags'] = 'active';
    	}
    	foreach($tags as $tag_key=>$tag_row){
    		if($tag_row['tag_type'] == 'goods'){//商品标签
    			if(in_array($tag_row['tag_id'],$filter['gTag'])){
    				$screen['tags']['goods'][$tag_key]['active'] = 'checked';
    			}
    			$screen['tags']['goods'][$tag_key]['tag_id'] = $tag_row['tag_id'];
    			$screen['tags']['goods'][$tag_key]['tag_name'] = $tag_row['tag_name'];
    		}
    	}
    	//排序
    	$orderBy = app::get('b2c')->model('goods')->orderBy();
    	$screen['orderBy'] = $orderBy;
    	
    	$this->pagedata['active_arr'] = $active_arr;
    	$return['screen'] = $screen;
    	$return['active_filter'] = $active_filter;
    	$return['seo_info'] = $catInfo['seo_info'];
    	return $return;
    }
    
    
    /*
     * 前台筛选商品ajax调用
    * */
    public function ajax_get_goods(){
    	$tmp_params = $this->filter_decode($_POST);
    	$params = $tmp_params['filter'];
    	$orderby = $tmp_params['orderby'];
    	$order = $tmp_params['d_order'];
    	unset($tmp_params['d_order']);
    	$goodsData = $this->get_goods($params,$orderby,array());
    	if($goodsData){
    		$this->pagedata['goodslist'] = $goodsData;
    		$view = 'site/detail/type/grid.html';
    		echo $this->fetch($view);
    	}else{
    		//后台站点设置搜索为空页面
    		echo $this->fetch('site/detail/empty.html');
    	}
    }
    
  
    
    /*
     * 返回搜索条件
    *
    * @params array $params 已有条件
    * @params int   $cat_id 分类ID
    * @params nit   $virtual_cat_id 虚拟分类ID
    * @return array
    */
    public function filter_decode($params=null,$cat_id,$virtual_cat_id=null){
    	
    	$filter['params'] = $params;
    	#分类
    	$params['cat_id'] = $cat_id ? $cat_id : $params['cat_id'];
    	#if(!$params['cat_id'])
    	 unset($params['cat_id']);
    
    	$params['marketable'] = 'true';
    	
    	$evt_id = $params['evt_id'];
    	$obj_events_goods_item = app::get('shangou')->model('events_goods_item');
    	$goods_array = $obj_events_goods_item->getList('goods_id,d_order',array('evt_id'=>$evt_id));
    	$goodids = array();
    	$order = array();
    	foreach ($goods_array as $item){
    		$goodids[] = $item['goods_id'];
    		$order[$item['goods_id']] = $item['d_order'];
    	}
    	$params['goods_id'] = $goodids;
        $tmp_filter = $params;
    
    			#价格区间筛选
        if($tmp_filter['price']){
    			$tmp_filter['price'] = explode('~',$tmp_filter['price'][0]);
    	}
    					#商品标签筛选条件
    	if($tmp_filter['gTag']){
    			$tmp_filter['tag'] = $tmp_filter['gTag'];unset($tmp_filter['gTag']);
   		}	
    
   		 #$is_store = $params['is_store'];
   		#排序
   		$orderby = $params['orderBy'];unset($params['orderBy']);
    
        $filter['filter'] = $tmp_filter;
        $filter['orderby'] = $orderby;
        $filter['showtype'] = $showtype;
        $filter['is_store'] = $is_store;
        $filter['page'] = 1;
        $filter['d_order'] = $order;
        return $filter;
    }
    
    /* 根据条件返回搜索到的商品
     * @params array $filter 搜索条件
    * @params int   $page   页码
    * @params string $orderby 排序
    * @return array
    * */
    public function get_goods($filter,$orderby,$order){
    	$goodsObject = kernel::single('b2c_goods_object');
    	$goodsModel = app::get('b2c')->model('goods');
    	$siteMember = $this->get_current_member();
   		unset($filter['cat_id']);
   		unset($filter['evt_id']);
   		unset($filter['virtual_cat_id']);
   		$filter['filter_sql'] = '  goods_id in ('.implode(',', $filter['goods_id']).')';
   		unset($filter['goods_id']);
    	$goodsData = $goodsModel->getList('*',$filter,0,99999999,$orderby);
    	
    	foreach($goodsData as $key=>$goods_row){
    		if($goods_row['udfimg'] == 'true' && $goods_row['thumbnail_pic']){
    			$goodsData[$key]['image_default_id'] = $goods_row['thumbnail_pic'];
    		}
    		$gids[$key] = $goods_row['goods_id'];
    	}
    
    	//搜索时的分类
    	if(!empty($catCount) && count($catCount) != 1){
    		arsort($catCount);
    		$this->pagedata['show_cat_id'] = key($catCount);
    		$this->pagedata['catArr'] = array_keys($catCount);
    		$this->catCount = $catCount;
    	}else{
    		$this->pagedata['show_cat_id'] = key($catCount);
    	}
    
    	//货品
    	$goodsData = $this->get_product($gids,$goodsData,$order);
    
    	//商品标签信息
    	foreach( kernel::servicelist('tags_special.add') as $services ) {
    		if ( is_object($services)) {
    			if ( method_exists( $services, 'add') ) {
    				$services->add( $gids, $goodsData);
    			}
    		}
    	}
    	return $goodsData;
    }
    
    /*
     * QuickView
    * */
    public function quickview_get_goods($params){
    	$tmp_params = $this->filter_decode($params);
    	$params = $tmp_params['filter'];
    	$orderby = $tmp_params['orderby'];
    	$prenext = $this->get_goods_quickview($params,$orderby);
    	return $prenext;
    }
    
    /* 根据条件返回搜索到的商品
     * @params array $filter 搜索条件
    * @params int   $page   页码
    * @params string $orderby 排序
    * @return array
    * */
    public function get_goods_quickview($filter,$orderby){
    	$goodsObject = kernel::single('b2c_goods_object');
    	$goodsModel = app::get('b2c')->model('goods');
    	$siteMember = $this->get_current_member();
    	unset($filter['cat_id']);
    	unset($filter['evt_id']);
    	unset($filter['virtual_cat_id']);
    	unset($filter['product_id']);
    	$filter['filter_sql'] = '  goods_id in ('.implode(',', $filter['goods_id']).')';
    	unset($filter['goods_id']);
    	$goodsData = $goodsModel->getList('goods_id',$filter,0,99999999,$orderby);
    	 
    	foreach($goodsData as $key=>$goods_row){
    		$gids[$key] = $goods_row['goods_id'];
    	}
    	//货品
    	$goodsData = $this->get_product_quickview($gids,$goodsData);
    
    	return $goodsData;
    }
    
    private function get_product_quickview($gids, $goodsData) {
    	$productModel = app::get('b2c')->model('products');
    	$products =  $productModel->getList('product_id,goods_id',array('goods_id'=>$gids,'is_default'=>'true','marketable'=>'true'));
    	$sdf_product = array();
    	foreach($products as $key=>$row){
    		$sdf_product[$row['goods_id']] = $row;
    	}
    	$prenext = array();
    	$underzore  =array();
    	foreach ($goodsData as $gk=>$goods_row) {
    		
    		$product_row = $sdf_product[$goods_row['goods_id']];
    		$goodsData[$gk]['products'] = $product_row;
    		
    		//库存
    		if($goods_row['nostore_sell']  || $product_row['store'] === null){
    			$goodsData[$gk]['products']['store'] = 999999;
    		}else{
    			$store = $product_row['store'] - $product_row['freez'];
    			$goodsData[$gk]['products']['store'] = $store > 0 ? $store : 0;
    		}
    		if ($goodsData[$gk]['products']['store'] <= 0) {
    			$underzore[] = $goodsData[$gk];
    			unset( $goodsData[$gk]);
    		}
    	}
    	$goodsData = array_merge($goodsData,$underzore);
    	foreach ($goodsData as $gk=>$goods_row) {
    		#pre  next
    		$prenext[]  = intval($goods_row['products']['product_id']);
    	}
        return $prenext;
    }
}

