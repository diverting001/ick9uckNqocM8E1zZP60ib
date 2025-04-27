<?php 
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 *
 *
 * 修改订单信息
 * @package default
 * @author kxgsy163@163.com
 */
class timedbuy_order_create
{
    private $_kv_data;
    private $_kv_sales_ref_data;
    
    function __construct($app)
    {
        $this->app = $app;
        $this->o_objitems = $this->app->model('objitems');
    }

    /*
     * 修改订单信息
     */
    public function generate( &$sdf )
    {
        //do nothing currently
        return true;
    }
    
    /*
     * 修改订单信息
     */
    public function create( &$sdf )
    {
        $arr_member = kernel::single('b2c_cart_objects')->get_current_member();
        #$arr_member = array('member_lv'=>3,'member_id'=>10);
        $member_lv_id = $arr_member['member_lv'];
        $member_id = $arr_member['member_id'];
        if( !$member_id || !$member_lv_id ) return false;
        $filter = array(
                    'status'=>'true',
                    'to_time|bthan'=>time(),
                    'from_time|sthan'=>time(),
                    's_template'=>'timedbuy_promotion_solution_timedbuy',
        );
        $cols = 'rule_id,member_lv_ids,stop_rules_processing,apply_time';
        //获取促销信息
        
        $arr_sales_rule_goods = app::get('b2c')->model('sales_rule_goods')->getList( $cols,$filter );
        
        //没有相关促销信息 直接返回
        if( !$arr_sales_rule_goods || !is_array($arr_sales_rule_goods) ) return true;
        
        $_kv_rule_id = array();
        foreach( $arr_sales_rule_goods as $rule ) {
            $arr_sales[$rule['rule_id']] = $rule;
            if( $this->_diff($rule) ) $_kv_rule_id[] = $rule['rule_id'];
            else $_db_rule_id[] = $rule['rule_id'];
        }
        
        $this->_kv_data();
        
        
        if( $_kv_rule_id )
            $this->_kv_sales_ref_data = kernel::single('timedbuy_info')->get('_kv_sales_ref_data');
        
        if( $_db_rule_id ) {
            $order = kernel::single('b2c_cart_prefilter_promotion_goods')->order();
            $arr_goods_promotion_ref = app::get('b2c')->model('goods_promotion_ref')->getList( 'goods_id,rule_id,member_lv_ids',array('rule_id'=>$_db_rule_id),0,-1,$order );
            if( $arr_goods_promotion_ref && is_array($arr_goods_promotion_ref) ) {
                foreach( $arr_goods_promotion_ref as $key => $row ) {
                    if( !$key ) $this->_kv_sales_ref_data[$row['rule_id']]['goods'] = array();
                    $this->_kv_sales_ref_data[$row['rule_id']]['goods'][] = $row['goods_id'];
                }
            }
        }
        
        //多余的数据删掉
		if ($this->_kv_sales_ref_data)
			foreach( $this->_kv_sales_ref_data as $key => $row ) {
				if( !isset($arr_sales[$key]) ) unset($this->_kv_sales_ref_data[$key]);
			}
        
        
        //促销信息 走到这里应该肯定有的 安全
        if( !$this->_kv_sales_ref_data || !is_array($this->_kv_sales_ref_data) ) return true;
        foreach( $this->_kv_sales_ref_data as $rule_id => $row ) {
            if( !$row['goods'] || !is_array($row['goods']) ) continue;
            $arr_member_lv = explode(',',$arr_sales[$rule_id]['member_lv_ids']);
            if( !is_array($arr_member_lv) ) continue;
            if( !in_array($member_lv_id,$arr_member_lv) ) continue;
            foreach( $row['goods'] as $gid ) {
                if( !isset($arr_goods_rule[$gid]) )
                    $arr_goods_rule[$gid] = $rule_id;
            }
        }
        
        //商品 限抢关联数组
        if( !$arr_goods_rule || !is_array($arr_goods_rule) ) return true;
        if( !$sdf['order_objects'] || !is_array($sdf['order_objects']) ) return true;
        $obj_timebuy_pay = kernel::single("timedbuy_order_pay");
        if(!$obj_timebuy_pay) return true;

        foreach ($sdf['order_objects'] as $key => $order_objects) {
            if( !$order_objects['order_items'] || !is_array($order_objects['order_items']) || $order_objects['obj_type'] != 'goods' ) continue;
            foreach ($order_objects['order_items'] as $order_items) {
                if( $arr_goods_rule[$order_items['goods_id']] ) {

                    $arr_sales_info = $obj_timebuy_pay->get_sales_info_by_ruleid($arr_goods_rule[$order_items['goods_id']]);
                    $action_solution = unserialize($arr_sales_info['action_solution']);
                    $config = $action_solution['timedbuy_promotion_solution_timedbuy'];//配置

                    // TODO(yi.qian) 锁该商品涉及的行，保证并发情况下，不超出限制销售
                    $quantity_array = $this->o_objitems->db->select('SELECT `quantity` FROM `sdb_timedbuy_objitems` WHERE `sales_rule_id`='.$arr_goods_rule[$order_items['goods_id']].' and `goods_id` ='.$order_items['goods_id'].' for update');
                    $quantity_sum = 0;
                    foreach($quantity_array as $quantity)
                    {
                        $quantity_sum += $quantity['quantity'];
                    }
                    if($config['quantity'] > 0 && $quantity_sum + $order_items['quantity'] > $config['quantity']){
                        return false;
                    }

                    $aSave = array(
                                'order_id'=>$sdf['order_id'],
                                'sales_rule_id'=>$arr_goods_rule[$order_items['goods_id']],
                                'goods_id'=>$order_items['goods_id'],
                                'member_id'=>$member_id,
                                'quantity'=>$order_items['quantity'],
                                'order_pay_status'=>0,
                                'ctime' => time(),
                            );
                    $this->o_objitems->save( $aSave );
                }
            }
        }
        $this->destruct();
        return true;
    }
    #End Func
    
    
    private function _kv_data() {
        $this->_kv_data = $this->_tmp_kv_data;
        $this->_tmp_kv_data = array();
    }
    
    //使用kv return true
    public function _diff( $rule ) {
        $this->_tmp_kv_data[$rule['rule_id']] = $rule;
        if( !$this->_kv_data ) {
            $this->_kv_data = kernel::single('timedbuy_info')->get('_kv_data');
        }
        if( $this->_kv_data[$rule['rule_id']] && is_array($this->_kv_data[$rule['rule_id']]) ) {
            $row = $this->_kv_data[$rule['rule_id']];
            if( $row['rule_id']==$rule['rule_id'] ) {
                if( $row['apply_time'] == $rule['apply_time'] ) return true;
                return false;
            }
        } else {
            $this->_kv_data[$rule['rule_id']] = $rule;
            return false;
        }
    }
    
    public function destruct() {
        #kernel::single('timedbuy_info')->set('_kv_data',$this->_kv_data);
        #kernel::single('timedbuy_info')->set('_kv_sales_ref_data',$this->_kv_sales_ref_data);
    }


    /*
     * 修改订单信息
     */
    public function createV2($order_id, $member_id, $sdf, &$error_data = array())
    {
//        $arr_member = kernel::single('b2c_cart_objects')->get_current_member();
//        #$arr_member = array('member_lv'=>3,'member_id'=>10);
//        $member_lv_id = $arr_member['member_lv'];
//        $member_id = $arr_member['member_id'];
        if(!$member_id) return false;
        $filter = array(
            'status'=>'true',
            'to_time|bthan'=>time(),
            'from_time|sthan'=>time(),
            's_template'=>'timedbuy_promotion_solution_timedbuy',
        );
        $cols = 'rule_id,member_lv_ids,stop_rules_processing,apply_time';
        //获取促销信息

        $arr_sales_rule_goods = app::get('b2c')->model('sales_rule_goods')->getList( $cols,$filter );

        //没有相关促销信息 直接返回
        if( !$arr_sales_rule_goods || !is_array($arr_sales_rule_goods) ) return true;

        $_kv_rule_id = array();
        foreach( $arr_sales_rule_goods as $rule ) {
            $arr_sales[$rule['rule_id']] = $rule;
            if( $this->_diff($rule) ) $_kv_rule_id[] = $rule['rule_id'];
            else $_db_rule_id[] = $rule['rule_id'];
        }

        $this->_kv_data();


        if( $_kv_rule_id )
            $this->_kv_sales_ref_data = kernel::single('timedbuy_info')->get('_kv_sales_ref_data');

        if( $_db_rule_id ) {
            $order = kernel::single('b2c_cart_prefilter_promotion_goods')->order();
            $arr_goods_promotion_ref = app::get('b2c')->model('goods_promotion_ref')->getList( 'goods_id,rule_id,member_lv_ids',array('rule_id'=>$_db_rule_id),0,-1,$order );
            if( $arr_goods_promotion_ref && is_array($arr_goods_promotion_ref) ) {
                foreach( $arr_goods_promotion_ref as $key => $row ) {
                    if( !$key ) $this->_kv_sales_ref_data[$row['rule_id']]['goods'] = array();
                    $this->_kv_sales_ref_data[$row['rule_id']]['goods'][] = $row['goods_id'];
                }
            }
        }

        //多余的数据删掉
        if ($this->_kv_sales_ref_data)
            foreach( $this->_kv_sales_ref_data as $key => $row ) {
                if( !isset($arr_sales[$key]) ) unset($this->_kv_sales_ref_data[$key]);
            }


        //促销信息 走到这里应该肯定有的 安全
        if( !$this->_kv_sales_ref_data || !is_array($this->_kv_sales_ref_data) ) return true;
        foreach( $this->_kv_sales_ref_data as $rule_id => $row ) {
            if( !$row['goods'] || !is_array($row['goods']) ) continue;
            $arr_member_lv = explode(',',$arr_sales[$rule_id]['member_lv_ids']);
            if( !is_array($arr_member_lv) ) continue;
            //if( !in_array($member_lv_id,$arr_member_lv) ) continue;
            foreach( $row['goods'] as $gid ) {
                if( !isset($arr_goods_rule[$gid]) )
                    $arr_goods_rule[$gid] = $rule_id;
            }
        }

        //商品 限抢关联数组
        if( !$arr_goods_rule || !is_array($arr_goods_rule) ) return true;
        if(!is_array($sdf) || count($sdf) < 1) return true;
        $obj_timebuy_pay = kernel::single("timedbuy_order_pay");
        if(!$obj_timebuy_pay) return true;

        foreach ($sdf as $key => $order_items) {
            if( $arr_goods_rule[$order_items['goods_id']] ) {

                $arr_sales_info = $obj_timebuy_pay->get_sales_info_by_ruleid($arr_goods_rule[$order_items['goods_id']]);
                $action_solution = unserialize($arr_sales_info['action_solution']);
                $config = $action_solution['timedbuy_promotion_solution_timedbuy'];//配置

                // TODO(yi.qian) 锁该商品涉及的行，保证并发情况下，不超出限制销售
                $quantity_array = $this->o_objitems->db->select('SELECT `quantity` FROM `sdb_timedbuy_objitems` WHERE `sales_rule_id`='.$arr_goods_rule[$order_items['goods_id']].' and `goods_id` ='.$order_items['goods_id']. ' and status = 1 ' .  ' for update');
                $quantity_sum = 0;
                foreach($quantity_array as $quantity)
                {
                    $quantity_sum += $quantity['quantity'];
                }
                if($config['quantity'] > 0 && $quantity_sum + $order_items['nums'] > $config['quantity']){
                    $error_data[] = $key;
                    return false;
                }

                $aSave = array(
                    'order_id'=>$order_id,
                    'sales_rule_id'=>$arr_goods_rule[$order_items['goods_id']],
                    'goods_id'=>$order_items['goods_id'],
                    'member_id'=>$member_id,
                    'quantity'=>$order_items['nums'],
                    'order_pay_status'=>0,
                    'status'=> 1,
                    'ctime' => time(),
                );
                $this->o_objitems->save( $aSave );
            }
        }
        $this->destruct();
        return true;
    }

    /**
     * @param $order_id
     * @return bool
     */
    public function cancelOrder($order_id)
    {
        if(empty($order_id)){
            return false;
        }

        $where['order_id'] = $order_id;
        $update['status'] = '2';
        return $this->o_objitems->update($update,$where);;
    }
}
