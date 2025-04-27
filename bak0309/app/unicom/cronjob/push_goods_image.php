<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 * 联通平台商品图片信息
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM PUSH GOODS IMAGE START '. $mantissa.  "\n";
$mantissa = '';
if (isset($argv[1]))
{
    $mantissa = $argv[1];
}
const PUSH_COUNT = 100000;

$goodsLib = kernel::single("unicom_goods");
$goodsModel = app::get('unicom')->model('goods');

// 获取待同步的数据
$goodsData = $goodsModel->getUnPushGoodsBaseData(PUSH_COUNT, $mantissa);

if ( ! empty($goodsData))
{

    foreach($goodsData as $goods)
    {
        // 获取商品基础信息
        $goodsBaseData = $goodsLib->getGoodsBaseData($goods['product_bn']);

        $goodsSyncData = $goodsModel->getGoodsSync($goods['product_bn']);

        $lastSyncSignData = ! empty($goodsSyncData['last_sync_base_sign']) ? explode(',', $goodsSyncData['last_sync_base_sign']) : array();

        $lastSyncBaseSign = ! empty($goodsBaseData['base']) ? md5(serialize($goodsBaseData['base'])) : '';

        $lastSyncImageSign =  ! empty($goodsBaseData['image']) ? md5(serialize($goodsBaseData['image'])) : '';

        $lastSyncSpecSign =  ! empty($goodsBaseData['spec']) ? md5(serialize($goodsBaseData['spec'])) : '';

        echo $goods['product_bn'];
        $errMsg = '';
        // 首次同步
        if (empty($goodsSyncData))
        {
            $result = $goodsLib->pushGoods($goods['product_bn'], 'all', $errMsg);

            $goodsCoreData = $goodsLib->getGoodsCoreData($goods['product_bn']);

            $insertData = array(
                'product_bn' => $goods['product_bn'],
                'last_sync_base_sign' => $lastSyncBaseSign. ','. $lastSyncImageSign. ','. $lastSyncSpecSign,
                'last_sync_base_result' => json_encode($result),
                'sync_base_last_time' => time(),
                'last_sync_core_data' => json_encode($goodsCoreData),
                'update_time' => time(),
                'create_time' => time(),
            );

            $goodsModel->addGoodsSync($insertData);
        }
        else
        {
            $errMsgList = '';
            if($goodsSyncData['base_sync_force']== 1 OR empty($lastSyncSignData[1]) OR $lastSyncImageSign != $lastSyncSignData[1])
            {
                $result = $goodsLib->pushGoods($goods['product_bn'], 'image', $errMsg);
                $errMsgList .= $errMsg. ' ';
            }

            $errMsg = $errMsgList;

            if ( ! empty($result))
            {
                $updateData = array(
                    'update_time' => time(),
                );
                $updateData['last_sync_base_sign'] = $lastSyncSignData[0]. ','. $lastSyncImageSign. ','. $lastSyncSignData[2];
                $updateData['last_sync_base_result'] = json_encode($result);
                $updateData['base_sync_force'] = 0;
                $updateData['sync_base_last_time'] = time();

                $goodsModel->saveGoodsSync($goods['product_bn'], $updateData);
            }
        }

        echo ' ====== '. ($errMsg ? $errMsg : 'success'). "\n";
    }
}

echo 'UNICOM PUSH GOODS END '.$mantissa. "\n";