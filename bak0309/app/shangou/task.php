<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class shangou_task 
{
    function post_install()
    {
    	logger::info('Register shangou cat  meta');
    	$obj_cat = app::get('shangou')->model('cat');
    	$col = array(
    			'seo_info' => array(
    					'type' => 'serialize',
    					'label' => app::get('b2c')->_('seo设置'),
    					'width' => 110,
    					'editable' => false,
    			),
    	);
    	$obj_cat->meta_register($col);
    	
        logger::info('Initial shangou');
        kernel::single('base_initial', 'shangou')->init();
    }//End Function
}//End Class

