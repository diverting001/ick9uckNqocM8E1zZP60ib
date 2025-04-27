<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

class wap_ctl_admin_route_static extends desktop_controller
{

    function __construct(&$app)
    {
        parent::__construct($app);
        $this->statics_route_limit = 100;
    }//End Function

    public function index()
    {
        $this->finder('wap_mdl_route_statics', array(
            'title' => app::get('wap')->_('自定义URL'),
            'base_filter' => array(),
            'actions'=>array(
                array(
                    'label' => app::get('wap')->_('添加规则'),
                    'href' => 'index.php?app=wap&ctl=admin_route_static&act=add',
                    'target' => 'dialog::{frameable:true, title:\''.app::get('wap')->_('添加规则').'\', width:537, height:200}',
                ),
            ),
        ));
    }//End Function

    public function add()
    {
        $this->pagedata['close_win'] = 1;
        $this->page('admin/route/static/edit.html');
    }//End Function

    public function save()
    {
        $statics = $_POST['statics'];
        $this->begin();
        $count = app::get('wap')->model('route_statics')->count();
        if($count >= $this->statics_route_limit){
            $this->end(false, app::get('wap')->_('静态路由最高设置为'.$this->statics_route_limit.'条，请调整现有路由设置'));
        }
        if($row = app::get('wap')->model('route_statics')->has_static($statics['static'])){
            if($row['id']!=$statics['id']) $this->end(false, app::get('wap')->_('静态规则已经存在'));
        }
        if($row = app::get('wap')->model('route_statics')->has_url($statics['url'])){
            if($row['id']!=$statics['id']) $this->end(false, app::get('wap')->_('目标链接已经存在'));
        }
        if($statics['id'] > 0){
            $id = $statics['id'];
            unset($statics['id']);
            if(app::get('wap')->model('route_statics')->update($statics, array('id'=>$id))){
                $this->end(true, app::get('wap')->_('保存成功'));
            }else{
                $this->end(false, app::get('wap')->_('保存失败'));
            }
        }else{
            if(app::get('wap')->model('route_statics')->insert($statics)){
                $this->end(true, app::get('wap')->_('添加成功'));
            }else{
                $this->end(false, app::get('wap')->_('添加失败'));
            }
        }
    }//End Function

}//End Class