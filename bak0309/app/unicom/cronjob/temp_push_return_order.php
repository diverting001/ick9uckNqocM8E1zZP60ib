<?php

/**
 * 重新推送退货单通知
 * 
 */
require(dirname(__FILE__) . '/config.php');

/*
kernel::single("unicom_order_handle")->temp_push_return_order();
*/

/*
$data = array(
    '201809291855744',
    '201810082239519',
    '201809291855806',
    '201809261140840',
    '201810081735443',
    '201810081735368',
    '201810081735244',
    '201809291855224',
    '201809291855724',
    '201810030926494',
    '201810081433907',
    '201809301053128',
    '201810080859458',
    '201810081258164',
    '201810061012597',
    '201810011107778',
    '201810021757432',
    '201810100944129',
    '201810081342850',
    '201810040923738',
    '201810092226285',
    '201810081129839',
    '201810081211712',
    '201810081715859',
    '201810100843910',
    '201810100944705',
    '201810101438185',
    '201810101438991',
    '201810101438824',
    '201810101246719',
    '201810101619153',
);

foreach ($data as $after_sale_bn){
  $order_info = kernel::single("unicom_order_pushmessage")->pushReturnOrder($after_sale_bn);
  var_dump($order_info);
}
 * 
 */


$tmp_data = array(
    '2657293c-a2b2-4407-aae0-9ebc54efd82e',
    '887d942c-75e1-4d9c-bf5e-21fcf3d404b0',
    'c73e4931-6402-4e47-b704-dbe900737299',
    'c9c2c0ef-fc0e-4a95-90d1-68bb2bb48248',
    'd7606e21-d2a6-4a03-8da1-d4479fbe4df2',
    '0ff4c81f-d1ba-4632-a8ab-c376ba199010',
    '0a82428e-8622-4eff-a981-d444dd9162bd',
    '566954fc-64b1-4a2e-8428-7ce397bbd005'
);

// 订单重新推送
$retryOrderList = app::get('unicom')->model('order')->getRetryOrderList();
$pushLib = kernel::single("unicom_order_pushmessage");
$resList = array();
if (!empty($retryOrderList)){
    foreach ($retryOrderList as $v){
        $res = $pushLib->pushReturnOrder($v['return_id']);
        if ($res['Result'] == 'false'){
            echo '失败: '.$v['return_id'].' - '.$res['ErrorMsg'].PHP_EOL;
        }else{
            echo '成功: '.$v['return_id'].' - '.$res['ErrorMsg'].PHP_EOL;
        }
    }
}else{
    die('没有推送信息');
}



//foreach ($tmp_data as $untreadOrderNo){
//  $data = $handleLib->getUnicomReturnOrderInfo ($untreadOrderNo);
//  var_dump($data);
//}