<?php
/**
* Power By ShopEx Jxwinter
* Time  2012-04-10  NO.193

*/

function theme_widget_topic($setting,&$smarty){
	
	$_obj_topic_cat = app::get('b2c')->model('topic_cat');;
	$data = $setting['top_cat'];
	foreach ($data as $k=>$_arr){
		$_tmp = $_obj_topic_cat->getList('topic_cat_name',array('topic_cat_id'=>$_arr['topic_cat_id']));
		$data[$k]['topic_cat_name'] = $_tmp[0]['topic_cat_name'];
	}

    return $data;
}
?>
