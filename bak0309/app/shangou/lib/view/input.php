<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class shangou_view_input{

    function __construct($app){
        $this->app = $app;
    }
    
    function input_eventcat($params){
    	$render = new base_render(app::get('shangou'));
    	$mdl = app::get('shangou')->model('cat');
    	$render->pagedata['category'] = array();
    	$render->pagedata['params'] = $params;
    	if($params['value']){
    		$row = $mdl->getList('*',array('cat_id'=>$params['value']));
    		$render->pagedata['category'] = $row[0];
    	}
    	return $render->fetch('admin/events/category/input_category.html');
    }
}
