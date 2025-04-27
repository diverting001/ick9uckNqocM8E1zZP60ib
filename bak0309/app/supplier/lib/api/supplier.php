<?php
/**
 * 相响OCS系统的请求
 * @author pansen@leho.com
 * @version 1.0 
 */
class supplier_api_supplier
{
    /**
     * 公开构造方法
     * @params app object
     * @return null
     */
    public function __construct($app)
    {        
        $this->app = $app;
    }
    
   /**
    * 添加或更新供应商信息
    * @param array $sdf
    * @param object $thisObj
    * @return string
    */
    public  function update(&$sdf, &$thisObj){
    	
    	if (!isset($sdf['data']['sp']) || empty($sdf['data']['sp'])) {
    		$thisObj->send_user_error(app::get('supplier')->_('供应商数据参数不完整'),array(
    				'sp_bn' => ''
    		));
    	}else{
    		$sp = $sdf['data']['sp'];
    		unset($sp['sp_id']);
	    	$obj_supplier = &$this->app->model('supplier');
	    	$supplier =  $obj_supplier->dump(array('sp_bn' =>$sp['sp_bn'] ));
	    	
	    	if (!empty($supplier)) {
	    		#没有，直接创建保存
	    		$sp['sp_id'] = $supplier['sp_id'];
	    	}
	    	$flag = $obj_supplier->save($sp);
	    	if ($flag) {
	    		return json_encode(array(
	    				'rsp' => 'succ' ,
	    				'data' => $flag,
	    				'res' => ''
	    		));
	    	}else{
	    		$thisObj->send_user_error('Ec端更新数据失败',array(
	    				'sp_bn' => $sp['sp_bn']
	    		));
	    	}
	    }
    }
    /**
     *  删除指定的供应商
     * @param array $sdf
     * @param object $thisObj
     * @return  string
     */
    public  function delete(&$sdf, &$thisObj){
    	if (!isset($sdf['data']['sp']) || empty($sdf['data']['sp'])) {
    		$thisObj->send_user_error(app::get('supplier')->_('供应商数据参数不完整'),array(
    				'sp_bn' => ''
    		));
    	}else{
    		$obj_supplier = &$this->app->model('supplier');
    		$sp = $sdf['data']['sp'];
    		$db = kernel::database();
    		$transaction_status = $db->beginTransaction();
    		$rs = $obj_supplier->delete(array('sp_bn'=>$sp['sp_bn']));
    		if ($rs) {
    			$db->commit($transaction_status);
    			return json_encode(array(
    					'rsp' => 'succ' ,
    					'data' => true,
    					'res' => ''
    			));
    		}else{
    			$db->rollback();
    			$thisObj->send_user_error('Ec端删除数据失败',array(
    					'sp_bn' => $sp['sp_bn']
    			));
    		}
    	}
    }
}