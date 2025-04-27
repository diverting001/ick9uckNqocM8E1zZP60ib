<?php
class supplier_finder_supplier{
	
function detail_edit($id){        
        $render = app::get('supplier')->render();
        $obj_supplier = app::get('supplier')->model('supplier');
        $data = $obj_supplier->dump($id);
        $co_type_list = array(
        		'nego'=>'洽谈',
        		'cooper' => '合作',
        		'stop' => '停止合作',
        );
        $co_mode_list = array(
        		'tosell' => '经销',
        		'onsell' => '代销',
        		'joint' => '联营',
        );
        $pay_type_list = array(
        		'cash' => '现款',
        		'term' => '账期',
        );
        $goods_type_list = array(
        		'c' => '服装',
        		'a' => '配饰',
        		's' => '鞋',
        		'h' => '家居',
        		'b' => '美妆',
        		'f' => '食品',
        		'e' => '电子产品',
        		'o' => '其它',
        );
        $invoice_type_list = array(
        		'normal' => '普通发票',
        		'increment' => '增值税发票',
        );
        $render->pagedata['co_type_list'] = $co_type_list;
        $render->pagedata['co_mode_list'] = $co_mode_list;
        $render->pagedata['co_mode_list'] = $co_mode_list;
        $render->pagedata['pay_type_list'] = $pay_type_list;
        $render->pagedata['goods_type_list'] = $goods_type_list;
        $render->pagedata['invoice_type_list'] = $invoice_type_list;
        $render->pagedata['supplier'] = $data;
        $render->display('supplier/detail.html');

    }

}
