<?php
class weixin_finder_alert{

    public function __construct($app)
    {
        $this->app = $app;
    }
     

    var $column_edit_order = 10;
    public function detail_basic($id){
        $render = $this->app->render();
        $data = app::get('weixin')->model('alert')->getRow('*',array('id'=>$id));

        $render->pagedata['data'] = $data;
        return $render->fetch('admin/shop/alert_detail.html');
    }
}
