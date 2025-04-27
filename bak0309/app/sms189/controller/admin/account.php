<?php
class sms189_ctl_admin_account extends desktop_controller{

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

             app::get('sms189')->_('电信应用设置')=>array(
                'ecos.leho.sms189.appid',
                'ecos.leho.sms189.appsecret',
             	'ecos.leho.sms189.template_id',
            ),
        	app::get('sms189')->_('通信测试设置')=>array(
        		'sms189.app.leho.api.test.status',
        		'sms189.app.leho.api.test.value',
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
                    'helpinfo'=>$setlib[$set]['helpinfo'],
                    'value'=>$current_set,
                    'options'=>$setlib[$set]['options'],
                    'vtype' => $setlib[$set]['vtype'],
                    'class' => $setlib[$set]['class'],
                    'id' => $setlib[$set]['id'],
                    'default' => $setlib[$set]['default'],
                );
                
                if($input_type=='image'){
                    
                   $form_input = array_merge($form_input,array(
                   
                      'width'=>$setlib[$set]['width'],
                      'height'=>$setlib[$set]['height']
                   
                   ));
                
                }

                $html.=$this->ui->form_input($form_input);
            }
        }
        return $html .= $this->ui->form_end(1, app::get('ectools')->_('保存设置'));
    }

    public function refresh()
    {
    	$tokeninfo = app::get('sms189')->getConf('ecos.leho.sms189.tokeninfo');
    	if (empty($tokeninfo)) {
    		$tokeninfo = array();
    	}
    	$this->pagedata['app_id'] = app::get('sms189')->getConf('ecos.leho.sms189.appid'); //APP ID
    	$this->pagedata['app_secret'] = app::get('sms189')->getConf('ecos.leho.sms189.appsecret'); //APP 密钥
    	$this->pagedata['template_id'] = app::get('sms189')->getConf('ecos.leho.sms189.template_id'); //验证码短信模版ID
    	$this->pagedata['tokeninfo'] = $tokeninfo;
    	$this->page('admin/setting/account/index.html');
    }//End Function
    
    public function refresh_token()
    {
    	$this->begin('');
    	//重新获取token
    	$params['grant_type'] = 'client_credentials'; //授权模式 目前是CC授权
    	$params['app_id']     =app::get('sms189')->getConf('ecos.leho.sms189.appid'); //APP ID
    	$params['app_secret'] =app::get('sms189')->getConf('ecos.leho.sms189.appsecret'); //APP 密钥
    	$tokeninfo = json_decode(sms189_mobile::curl_post($params), 1);
    	$rs = true;
    	if ($tokeninfo['res_code']==='0') {
    		$tokeninfo['expires_in'] = time() + $tokeninfo['expires_in'];
    		$tokeninfo['app_id'] = $params['app_id'];
    		$tokeninfo['app_secret'] = $params['app_secret'];
    		app::get('sms189')->setConf('ecos.leho.sms189.tokeninfo',$tokeninfo);
    	}else{
    		$rs =  false;
    	}
    	$this->end($rs,$rs == true ? '重新获取成功':'重新获取失败,请再试一次','index.php?app=sms189&ctl=admin_account&act=refresh');
    }//End Function
}

