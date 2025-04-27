<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
class shangou_ctl_admin_events_config extends desktop_controller{

    var $require_super_op = true;

    function __construct($app){
        parent::__construct($app);
        $this->ui = new base_component_ui($this);
        $this->app = $app;
		header("cache-control: no-store, no-cache, must-revalidate");
    }

    function index(){
        $this->basic();
    }

    public function basic(){
        $all_settings = array(
             app::get('shangou')->_('闪购活动配置')=>array(
                'shangou.event.ending.time',
            ),
        );
        $this->pagedata['_PAGE_CONTENT'] = $this->_process($all_settings);
        $this->page();        
    }

    function _process($all_settings){
        $setting = new base_setting($this->app);
        $setlib = $setting->source();
        $typemap = array(
            SET_T_STR=>'text',
            SET_T_INT=>'number',
            SET_T_ENUM=>'select',
            SET_T_BOOL=>'bool',
            SET_T_TXT=>'text',
            SET_T_FILE=>'file',
            SET_T_IMAGE=>'image',
            SET_T_DIGITS=>'number',
        );

        $tabs = array_keys($all_settings);
        $html = $this->ui->form_start(array('tabs'=>$tabs, 'method'=>'POST'));
        foreach($tabs as $tab=>$tab_name){
            foreach($all_settings[$tab_name] as $set){
                $current_set = $this->app->getConf($set);
                if($_POST['set'] && array_key_exists($set,$_POST['set'])){
                    if($current_set!=$_POST['set'][$set]){
                        $current_set = $_POST['set'][$set];
                        $this->app->setConf($set,$_POST['set'][$set]);
                    }
                }
                
                $input_type = $typemap[$setlib[$set]['type']];
                
                $form_input = array(
                    'title'=>$setlib[$set]['desc'],
                    'type'=>$input_type,
                    'name'=>"set[".$set."]",
                    'tab'=>$tab,
                    'value'=>$current_set,
                    'options'=>$setlib[$set]['options'],
                	'helpinfo'=>$setlib[$set]['helpinfo'],
                );
                
                if($input_type=='image'){
                    
                   $form_input = array_merge($form_input,array(
                   
                      'width'=>$setlib[$set]['width'],
                      'height'=>$setlib[$set]['height']
                   
                   ));
                
                }
                $arr_js[] = $setlib[$set]['javascript'];
                $html.=$this->ui->form_input($form_input);
            }
        }
        	 $html .= $this->ui->form_end(1, app::get('ectools')->_('保存设置')) . '<script type="text/javascript">window.addEvent(\'domready\',function(){';
        
        	$str_js = '';
        	if (is_array($arr_js) && $arr_js)
        	{
        		foreach ($arr_js as $str_javascript)
        		{
        			$str_js .= $str_javascript;
        		}
        	}
        
        	$html .= $str_js . '});</script>';
        
        return $html;
    }

}

