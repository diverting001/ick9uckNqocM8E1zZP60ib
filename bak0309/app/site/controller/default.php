<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class site_ctl_default extends b2c_frontpage {

    function index(){

        if(defined('APP_SITE_INDEX_MAXAGE') && APP_SITE_INDEX_MAXAGE > 1){
            $this->set_max_age(APP_SITE_INDEX_MAXAGE);
        }//todo: 首页max-age设定
        //首页数据
        /*
        $company=app::get('site')->model_for_config('index',b2c_util::get_club_db_conf());
        //Redis
        $redis_chache   = kernel::single('base_sharedkvstore');
        $prefix = 'neigous';
        $check_key = 'home_page_info';
        $indexDatas = array();
        $infoData  = $redis_chache->fetch($prefix,$check_key,$indexDatas);
        if(!$infoData){
            $company_infos=$company->getCompanyInfo(12);
            foreach ($company_infos as $key=>$info) {
                $num = 50;
                if ($info['staff_amount']=='50人以下') {
                    $num = 50;
                }
                if ($info['staff_amount']=='50-100人') {
                    $num = 100;
                }
                if ($info['staff_amount']=='100-200人') {
                    $num = 200;
                }
                if ($info['staff_amount']=='200-500人') {
                    $num = 500;
                }
                if ($info['staff_amount']=='500-1000人') {
                    $num = 1000;
                }
                if ($info['staff_amount']=='1000-5000人') {
                    $num = 5000;
                }
                if ($info['staff_amount']=='5000人及以上') {
                    $num = '5000+';
                }
                $company_infos[$key]['pass_date'] = date('m月d日', strtotime($info['pass_date']));
                $company_infos[$key]['num'] = $num;
            }            
            $indexDatas['home_page']['company_info'] = $company_infos;
            $indexDatas['home_page']['index_data']  = $company->getNeigouIndexNum();
            $indexDatas['home_page']['member_num'] = $company->getNeigouMembersNum();
            $indexDatas['home_page']['num'] = $company->getNumber();
            $redis_chache->store($prefix,$check_key,$indexDatas,3600*2);
        }

        $this->pagedata['company_num'] = $indexDatas['home_page']['num'];
        $this->pagedata['member_num'] = $indexDatas['home_page']['member_num'];
        $this->pagedata['good_brand'] = $indexDatas['home_page']['index_data']['good_brand'];
        $this->pagedata['hot_brand'] = $indexDatas['home_page']['index_data']['hot_brand'];
        $this->pagedata['company_info'] = $indexDatas['home_page']['company_info'];        
         */
        
        if(kernel::single('site_theme_base')->theme_exists()){
            //TODO(yi.qian)如果已经登录的情况下，直接跳转到内购主页
            if(parent::check_login()){
                // 已登录，跳转到公司配置的地址
                $company_id = kernel::single("b2c_member_company")->get_cur_company();
                $config = kernel::single("b2c_global_scope")->getScopeByCompany('company', $company_id, 'pc_www_index');
                if(isset($config['key_value']) && !empty($config['key_value'])){
                    $this->redirect($config['key_value']);
                    return;
                }

                $this->redirect($this->get_domain());
                return;
            }

            if($_COOKIE['login_url']){
                // 如果有设置自定义登录页
                $this->redirect($_COOKIE['login_url']);
                exit;
            }
            // 获取平台
            $platform = $_COOKIE['from_platform'] ? $_COOKIE['from_platform'] : (defined('PSR_PLATFORM') ? PSR_PLATFORM : 'neigou');
            //没有登录,跳转到指定登录地址
            $urlInfo = kernel::single('b2c_config')->getNoLoginRedirectUrl($platform);
            if (isset($urlInfo['value']) && !empty($urlInfo['value'])){
                $this->redirect($urlInfo['value']);
            }


            $obj = kernel::service('site_index_seo');

            if(is_object($obj) && method_exists($obj, 'title')){
                $title = $obj->title();
            }else{
                $title = (app::get('site')->getConf('site.name')) ? app::get('site')->getConf('site.name') : app::get('site')->getConf('page.default_title');
            }

            if(is_object($obj) && method_exists($obj, 'keywords')){
                $keywords = $obj->keywords();
            }else{
                $keywords = (app::get('site')->getConf('page.default_keywords')) ? app::get('site')->getConf('page.default_keywords') : $title;
            }

            if(is_object($obj) && method_exists($obj, 'description')){
                $description = $obj->description();
            }else{
                $description = (app::get('site')->getConf('page.default_description')) ? app::get('site')->getConf('page.default_description') : $title;
            }

            $this -> pagedata['ssochecked'] = intval($_GET['ssochecked']);
            $_GET['ssochecked'] = 1;
            $url_append = '?' . http_build_query($_GET);
            $url = ECSTORE_DOMAIN . $url_append;
            $this -> pagedata['redirect'] = $url;
            $this -> pagedata['getway'] = CAS_APP_GETWAY;

            $this->pagedata['headers'][] = '<title>' . htmlspecialchars($title) . '</title>';
            $this->pagedata['headers'][] = '<meta name="keywords" content="' . htmlspecialchars($keywords). '" />';
            $this->pagedata['headers'][] = '<meta name="description" content="' . htmlspecialchars($description) . '" />';

            $GLOBALS['runtime']['path'][] = array('title'=>app::get('b2c')->_('首页'),'link'=>kernel::base_url(1));
            $this->set_tmpl('index');
            $this->page('index.html');
        }else{

            $this->display('splash/install_template.html');
        }
    }
    public function checkUAToken() {}

    //验证码组件调用
    function gen_vcode($key='vcode',$len=4){
        $vcode = kernel::single('base_vcode');
        $vcode->length($len);
        $vcode->verify_key($key);
        $vcode->display();
    }
    
    //返回系统当前时间
    public function request_time_now() {
    	echo time();exit;
    }
//
//    //TODO cong.li 获取首页统计数据
//    private function get_index_statistic() {
//        $statistic_array = array(
//            'products_num'=>0,
//            'company_num'=>0,
//            'member_num'=>0,
//            'today_store_num'=>0,
//            'newadd_company_num'=>0,
//        );
//
//        return $statistic_array;
//    }
}
