<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class wap_finder_route_static 
{
    public $addon_cols='static';

    public $detail_edit = '编辑';
    public function detail_edit($id)
    {
        $render = app::get('wap')->render();
        $render->pagedata['data'] = app::get('wap')->model('route_statics')->select()->where('id = ?', $id)->instance()->fetch_row();
        return $render->fetch('/admin/route/static/edit.html');
    }//End Function

}//End Class