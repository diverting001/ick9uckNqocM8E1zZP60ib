<?php
class supplier_ctl_admin_supplier extends desktop_controller{
    function index(){
        $this->title = '供货商列表';        
        $params = array(
            'title'=>$this->title,
            'actions' => array(
            	 /*array(
            		'label' => '新增供货商',
            		'href' => 'index.php?app=supplier&ctl=admin_supplier&act=add',
            		'target' => "dialog::{width:500,height:300,title:'新增供货商'}",
            	),
            	array(
            		'label' => '删除',
            		'submit' => 'index.php?app=supplier&ctl=admin_supplier&act=supplier_delete',
            		'target' => "_self",
            		'confirm' => "供货商一旦删除无法回恢复!\n是否确认删除?",
            	),
            	
               array(
                    'label' => '导出',
                    'submit' => 'index.php?app=omeio&ctl=admin_task&act=create_export&_params[app]=supplier&_params[mdl]=supplier&_params[view]='.$_GET['view'],
                    'target' => "dialog::{width:400,height:170,title:'队列导出'}",
                ),
                */
            ),
            'use_buildin_recycle'=>false,
            'use_buildin_filter'=>true,
        );
        $this->finder('supplier_mdl_supplier',$params);
    }
    function add(){
    	$this->_edit();
    }
    
    function edit($sp_id){
    	$this->_edit($sp_id);
    }
    
    private function _edit($sp_id=NULL){
    	if(!empty($sp_id)){
    		$obj_supplier = &$this->app->model('supplier');
    		$this->pagedata['supplier'] = $obj_supplier->dump($sp_id);
    	}
    	$co_type_list = array(
    		'nego'=>'洽谈',
    		'cooper' => '合作',
  			'stop' => '停止合作',
    	);
    	$co_mode_list = array(
    		'self' => '自营',
  	   	    'joint' => '联营',
    	);
    	$user_filter =array('super'=>'0','status'=>'1'); 
    	$this->pagedata['user_filter'] = $user_filter;
    	$this->pagedata['co_type_list'] = $co_type_list;
    	$this->pagedata['co_mode_list'] = $co_mode_list;
    	$this->display("supplier/add_supplier.html");
    }
    
    function saveSupplier(){
    	$url = 'index.php?app=supplier&ctl=admin_supplier&act=index';
    	$this->begin($url);
    	$save_data = $_POST['supplier'];
    	$obj_supplier = &$this->app->model('supplier');
    	#新建供应商  供应商编码由系统自动生成
    	if(empty($save_data['sp_id'])){
    		$save_data['sp_bn'] = $this->generateSpBn();
    	}
    
    	$rt = $obj_supplier->save($save_data);
   		$rt = $rt ? true : false;
   		$this->end($rt,app::get('base')->_($rt?'保存成功':'保存失败'));
    }
    
    /**
     * 系统自动生成供应商编号
     * @return string
     */
    function generateSpBn(){
    	$str="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ123456789";//没有数字0
    	$obj_supplier = app::get('supplier')->model('supplier');
    	do{
    		$padStr = '';
    		for ($i=0;$i<5;$i++){
    			$random = rand(0,59);
    			$padStr .= substr($str,$random,1);
    		}
    		$spbn = $padStr;
    		$row = $obj_supplier->db->selectrow('SELECT sp_id from '.$obj_supplier->table_name(1).'  where sp_bn ="'.$spbn.'"');
    	}while($row);
    	return $spbn;
    }
    

    public function supplier_delete(){
    	$this->begin('javascript:finderGroup["'.$_GET['finder_id'].'"].refresh();');
    	$rs['status'] = 'success';
    	$mdl_supplier = $this->app->model('supplier');
    	$mdl_goods = app::get('ome')->model('goods');
    	$supplier_count = $mdl_supplier->count($_POST);
    	if( $supplier_count>500 ){
    		$rs['status'] = 'fail';
    		$rs['msg'] = '一次最多只能删除500个供货商';
    	}else{
    		if (isset($gcols['sp_id'])) {
    			$suppliers = $mdl_supplier->getlist('sp_id,sp_name',$_POST);
    			#判断供应商下是否存在商品
    			foreach($suppliers as $v){
    				$g_count =  $mdl_goods->count(array('sp_id'=>$v['sp_id']));
    				if($g_count >= 1){
    					$rs['status'] = 'fail';
    					$rs['msg'] = $v['sp_name']."下还有存在商品，删除失败";
    				}
    			}
    		}
    	}
    	$mdl_supplier->delete($_POST);
    	if($rs['status'] == 'success') $this->end(true);
    	else $this->end(false,$rs['msg']);
    }
    
    
}