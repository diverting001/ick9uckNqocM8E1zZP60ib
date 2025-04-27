<?php
$setting['author']='tylerchao.sh@gmail.com';
$setting['name']='专题分类菜单';
$setting['version']='v1.0';
$setting['description'] = '展示模板使用的分类菜单挂件';
$setting['userinfo']='';
$setting['catalog']='辅助信息';
$setting['usual']= '0';
$setting['stime']='2012-08';
$setting['template'] = array(
                            'default.html'=>app::get('b2c')->_('默认'),
                        );
$setting['limit'] = 5;
$_arr = app::get('b2c')->model('topic_cat')->getList('topic_cat_id,topic_cat_name');
foreach ($_arr as $_cat){
	$setting['topic_cat'][$_cat['topic_cat_id']] = $_cat['topic_cat_name'];
}

?>
