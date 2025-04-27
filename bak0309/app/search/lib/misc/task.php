<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class search_misc_task implements base_interface_task{

    function rule() {
	    return '30 2 * * *';
    }

    function exec() {
	    $this->clear_cart_objects();
    }

    function description() {
	    return '删除增量索引表拉圾数据';
    }

    /*
     * 删除cart_objects表垃圾数据 一周以前针对于非登录用户
     */
    private function clear_cart_objects()
    {
        $sql = "DELETE FROM sdb_search_detla WHERE last_modify<time()-86400 and index_name='b2c_goods'";
        app::get('b2c')->model('cart_objects')->db->exec( $sql );
    }//End Function

}
