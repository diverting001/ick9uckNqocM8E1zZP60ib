<?php

class shangou_theme_tmpl
{

    /*
     * return tmpl
     */
    public function __get_tmpl_list()
    {
        $ctl = array(
            'events_list' => '闪购活动列表',
            'events_detail' => '闪购活动详情', 
        	'events_empty' => '闪购异步加载模版',
        );
    
        return $ctl;
    }
    #End Func
}