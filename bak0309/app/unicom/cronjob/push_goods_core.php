<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 * 联通平台商品价格&上下架推送
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM PUSH GOODS PRICE START '. $mantissa.  "\n";
$mantissa = '';
if (isset($argv[1]))
{
    $mantissa = $argv[1];
}
const PUSH_COUNT = 1000;

$goodsLib = kernel::single("unicom_goods");
$goodsModel = app::get('unicom')->model('goods');

// 获取待同步的数据
$goodsData = $goodsModel->getUnPushGoodsCoreData(PUSH_COUNT, $mantissa);

$BlackListModel = app::get('unicom')->model('blackListRule');

//对数据进行黑名单检测
$black_id_list = $BlackListModel->BlackProductId($goodsData);

if ( ! empty($goodsData))
{
    foreach($goodsData as $goods)
    {
        // 获取商品价格&上下架
        $goodsCoreData = $goodsLib->getGoodsCoreData($goods['product_bn']);

        $lastSyncCoreData = $goods['last_sync_core_data'] ? json_decode($goods['last_sync_core_data'], true) : array();

        $updateData = array(
            'update_time' => time(),
        );

        echo $goods['product_bn'];
        if(in_array($goods['id'],$black_id_list)){
            $goodsCoreData['marketable'] = false;
            $result = $goodsLib->pushGoods($goods['product_bn'], 'market_disable', $errMsg);
        }
        $errMsg = '';
        if ($goods['core_sync_force'] == 1 OR $lastSyncCoreData != $goodsCoreData)
        {
            if ($goods['core_sync_force'] == 1 OR $lastSyncCoreData['prices'] != $goodsCoreData['prices'])
            {
                $result = $goodsLib->pushGoods($goods['product_bn'], 'price', $errMsg);
            }

            if ($goods['core_sync_force'] == 1 OR $lastSyncCoreData['marketable'] != $goodsCoreData['marketable'])
            {
                if(in_array($goods['id'],$black_id_list)){
                    $result = $goodsLib->pushGoods($goods['product_bn'], 'market_disable', $errMsg);
                } else {
                    $result = $goodsLib->pushGoods($goods['product_bn'], 'marketable', $errMsg);
                }

            }

            if ($errMsg == '同步成功')
            {
                $updateData['last_sync_core_data'] = json_encode($goodsCoreData);
                $updateData['last_sync_core_result'] = json_encode($result);
                $updateData['core_sync_force'] = 0;
            }

            $updateData['sync_core_last_time'] = time();
        }

        $goodsModel->saveGoodsSync($goods['product_bn'], $updateData);
        echo ' ====== '. ($errMsg ? $errMsg : 'success'). "\n";
    }
}

echo 'UNICOM PUSH GOODS END '.$mantissa. "\n";