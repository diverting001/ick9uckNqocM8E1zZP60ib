<?php 

/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 *
 *
 * @package default
 * @author kxgsy163@163.com
 */
class timedbuy_cart_object_goods
{
    function __construct(&$app) {
        $this->app = &$app;
        $this->arr_member_info = kernel::single('b2c_cart_objects')->get_current_member();
        $this->member_ident = kernel::single("base_session")->sess_id();
    }

    //检查限时抢购商品是否超出限购范围
    function check($gid,$pid,$quantity=0,&$msg) {
        if( !$gid ) {
            $msg = app::get('b2c')->_('商品ID丢失');
            return false;
        }
        $arr_sales_info = kernel::single('timedbuy_info')->get_sales_goods_info( $gid );

        \Neigou\Logger::Debug("timebuy.check_begin", array("member_id"=>$this->arr_member_info['member_id'], "quantity"=>$quantity));
        //$msg = app::get('b2c')->_("商品[".$arr_sales_info['goods_name']."]");
        $flag = $this->_get_kvstore( $arr_sales_info,$gid,$member_num,$num,$config );
        \Neigou\Logger::Debug("timebuy.check_begin", array("member_id"=>$this->arr_member_info['member_id'], "quantity"=>$quantity,'sparam1'=>json_encode($flag),'sparam2'=>'flag','sparam3'=>json_encode(array('gid'=>$gid,'pid'=>$pid))));

        if( $arr_sales_info['from_time']>time() ) {
            $msg .= app::get('b2c')->_(' 抢购活动还未开始');
            \Neigou\Logger::Debug("timebuy.check_begin", array("member_id"=>$this->arr_member_info['member_id'], "quantity"=>$quantity,'sparam1'=>json_encode($flag),'sparam2'=>'抢购活动还未开始','sparam3'=>json_encode(array('gid'=>$gid,'pid'=>$pid))));

            return false;
        }
        
        if( $arr_sales_info['to_time']<time() ) {
            $msg .= app::get('b2c')->_(' 活动已经结束');
            \Neigou\Logger::Debug("timebuy.check_begin", array("member_id"=>$this->arr_member_info['member_id'], "quantity"=>$quantity,'sparam1'=>json_encode($flag),'sparam2'=>'活动已经结束','sparam3'=>json_encode(array('gid'=>$gid,'pid'=>$pid))));
            return true;
        }

        if( !$arr_sales_info || !is_array($arr_sales_info) ) return true;
        
        if( !$this->arr_member_info || !$this->arr_member_info['member_id'] ) {
            $msg .= app::get('b2c')->_(' 只限会员抢购');
            \Neigou\Logger::Debug("timebuy.check_begin", array("member_id"=>$this->arr_member_info['member_id'], "quantity"=>$quantity,'sparam1'=>json_encode($msg),'sparam2'=>'只限会员抢购','sparam3'=>json_encode(array('gid'=>$gid,'pid'=>$pid))));
            $jump_to_url = app::get('site')->router()->gen_url( array('app'=>'b2c','ctl'=>'site_cart','act'=>'loginBuy','arg0'=>1) );
            echo json_encode(array('error'=>true,'url'=>$jump_to_url) );exit;
            if($_POST['mini_cart']){
                echo json_encode( array('url'=>$jump_to_url) );exit;
            } else {
                header('Location:'.$jump_to_url);exit;
            }
        }
        
        if( $arr_sales_info['member_lv_ids'] ) {
            if( !in_array($this->arr_member_info['member_lv'],(array)explode(',',$arr_sales_info['member_lv_ids'])) ) {
                $msg .= app::get('b2c')->_(' 您所属的会员等级不符');
                return true;//如果不符合等级，则为普通商品购买，返回true
            }
        }
        
        //针对 货品情况 判断整个购物车
        $filter = array('member_id'=>$this->arr_member_info['member_id'],'member_ident'=>$this->member_ident);
        \Neigou\Logger::Debug("timebuy.check_begin", array("member_id"=>$this->arr_member_info['member_id'], "quantity"=>$quantity,'sparam1'=>json_encode($flag),'sparam2'=>'arr_cart_objects_before'));
        $arr_cart_objects = app::get('b2c')->model('cart_objects')->getList( '*',$filter );
        \Neigou\Logger::Debug("timebuy.check_begin", array("member_id"=>$this->arr_member_info['member_id'], "quantity"=>$quantity,'sparam1'=>json_encode($arr_cart_objects),'sparam2'=>'arr_cart_objects_end'));

        foreach( (array)$arr_cart_objects as $cart_objects ) {
            if( $cart_objects['params']['goods_id']==$gid && $cart_objects['params']['product_id']!=$pid ) {
                $quantity += $cart_objects['quantity'];
            } 
        }
        \Neigou\Logger::General("timebuy.check_end", array("member_id"=>$this->arr_member_info['member_id'], "member_num"=>$member_num, "quantity"=>$quantity));

        if($config['min'] && $quantity < $config['min']){
            $msg .= app::get('b2c')->_(" 此商品至少购买【{$config['min']}】件");
            return false;
        }

        //限购数量留空时不限制(quantity:购物车中添加的商品数量)
        if( $config['limit'] && $config['limit']<$member_num+$quantity ) {
            $msg .= app::get('b2c')->_(' 已超出限购数量');
            return false;
        }
        
        if( $config['quantity'] && $config['quantity']<$num+$quantity ) {
            $msg .= app::get('b2c')->_(' 库存不足');
            return false;
        }
        return true;
    }
    
    
    /* 
     * 系统存在问题，当促销id为1的促销做过限抢后再次开启限抢功能，计算问题。
     * ：把last_modify 改为下单时间。根据下单时间和促销时间为依据做判断
     * 针对以上。追加了ctime字段
     */
    public function _get_kvstore( $arr_sales_info,$gid,&$member_num,&$num,&$config ) {
        //开始时间
        $from_time = $arr_sales_info['from_time'];
        //结束时间
        $to_time = $arr_sales_info['to_time'];
        //会员id
        $member_id = $this->_member_id = $this->arr_member_info['member_id'];
        //促销id
        $rule_id = $arr_sales_info['rule_id'];

        $solution = @unserialize($arr_sales_info['action_solution']);
        
        $config = $solution['timedbuy_promotion_solution_timedbuy'];//配置

        $filter= array();
        $filter['goods_id'] = $gid;
        $filter['sales_rule_id'] = $arr_sales_info['rule_id'];
        $filter['status'] = 1;
        
        $stock_freez_time = app::get('b2c')->getConf('system.goods.freez.time');
        $data = $this->app->model('objitems')->getList('*',$filter);
        //todo(xiangcai.guo)增加获取订单Model @TODO maojz pop订单服务上线后原来EC不在起作用，因为按订单查询不到购买数量
        $order_mdl = app::get('b2c')->model('orders');

        if( !$data || !is_array($data) ) return true;
        foreach( $data as $row ) {
            if( !$row['ctime'] ) $row['ctime'] = $row['last_modify'];
            if( $row['ctime']<$from_time || $row['ctime']>=$to_time ) continue;

            // !=1 的时候在循环查老订单状态
            if($row['order_pay_status'] != '1'){
                //todo(xiangcai.guo)获取订单状态
                $order_filter['order_id'] = $row['order_id'];
                $orders = $order_mdl->getList('status',$order_filter,0,1);
                $order_status = $orders[0]['status'];
            }

            if($row['order_pay_status'] == '1' || $order_status != 'dead'){
                if($row['member_id'] == $member_id){
                    // 用户限购
                    $member_num += $row['quantity'];
                }
                //限购总数
                $num += $row['quantity'];
            }
        }
        return true;
    }

    private function get_error_msg( $msg ) {
        return array('status'=>'false','msg'=>$msg);
    }
    
}