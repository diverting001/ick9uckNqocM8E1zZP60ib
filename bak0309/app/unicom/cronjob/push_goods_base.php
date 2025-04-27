<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
/**
 * 联通平台商品基础信息
 */
require(dirname(__FILE__) . '/config.php');

echo 'UNICOM PUSH GOODS BASE START '. $mantissa.  "\n";
$mantissa = '';
if (isset($argv[1]))
{
    $mantissa = $argv[1];
}
const PUSH_COUNT = 100;

$hour = intval(date('H'));

if ($hour >= 8 && $hour < 22)
{
    echo 'UNICOM PUSH GOODS BASE END '. $mantissa.  "\n";
    exit;
}

$goodsLib = kernel::single("unicom_goods");
$goodsModel = app::get('unicom')->model('goods');
$BlackListModel = app::get('unicom')->model('blackListRule');

// 获取待同步的数据
$goodsData = $goodsModel->getUnPushGoodsBaseData(PUSH_COUNT, $mantissa);
//对数据进行黑名单检测
$black_id_list = $BlackListModel->BlackProductId($goodsData);

if ( ! empty($goodsData))
{
    foreach($goodsData as $goods)
    {
        if (empty($goods['category_code']))
        {
            continue;
        }
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
        if (empty($goodsSyncData) OR empty($goods['p_sku']))
        {
            //黑名单商品不同步
            if(in_array($goods['id'],$black_id_list)){
                continue;
            }
            $result = $goodsLib->pushGoods($goods['product_bn'], 'all', $errMsg);

            $goodsCoreData = $goodsLib->getGoodsCoreData($goods['product_bn']);

            if ( ! empty($goodsSyncData))
            {
                $updateData = array(
                    'update_time' => time(),
                );
                $updateData['last_sync_base_sign'] = $lastSyncBaseSign. ','. $lastSyncImageSign. ','. $lastSyncSpecSign;
                $updateData['last_sync_base_result'] = json_encode($result);
                $updateData['base_sync_force'] = 0;
                $updateData['sync_base_last_time'] = time();

                $goodsModel->saveGoodsSync($goods['product_bn'], $updateData);
            }
            else
            {
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
        }
        else
        {
            $errMsgList = '';
            if ($goodsSyncData['base_sync_force']== 1 OR empty($lastSyncSignData[0]) OR $lastSyncBaseSign != $lastSyncSignData[0])
            {
                $result = $goodsLib->pushGoods($goods['product_bn'], 'base', $errMsg);
                $errMsgList .= $errMsg. ' ';
            }

            if ($goodsSyncData['base_sync_force']== 1 OR empty($lastSyncSignData[1]) OR $lastSyncImageSign != $lastSyncSignData[1])
            {
                $result = $goodsLib->pushGoods($goods['product_bn'], 'image', $errMsg);
                $errMsgList .= $errMsg. ' ';
            }

            if ($goodsSyncData['base_sync_force']== 1 OR empty($lastSyncSignData[2]) OR $lastSyncSpecSign != $lastSyncSignData[2])
            {
                $result = $goodsLib->pushGoods($goods['product_bn'], 'spec', $errMsg);
                $result = true;
                $errMsgList .= $errMsg. ' ';

                // 更新其他规格商品的数据签名
                if($goodsSyncData['base_sync_force'] != 1 && strpos($errMsg, '同步成功') !== false)
                {
                    foreach ($goodsBaseData['spec'] as $v)
                    {
                        if ($v['sku'] != $goods['product_bn'])
                        {
                            $specGoods = $goodsModel->getGoodsSync($v['sku']);

                            if ( ! empty($specGoods))
                            {
                                $baseSign = explode(',', $specGoods['last_sync_base_sign']);
                                $baseSign[2] = $lastSyncSpecSign;
                                $goodsModel->saveGoodsSync($v['sku'], array('last_sync_base_sign' => implode(',', $baseSign)));
                            }
                        }
                    }
                }
            }
            $errMsg = $errMsgList;

            if ( ! empty($result))
            {
                $updateData = array(
                    'update_time' => time(),
                );

                if(strpos($errMsg, '同步成功') !== false)
                {
                    $updateData['last_sync_base_sign'] = $lastSyncBaseSign. ','. $lastSyncImageSign. ','. $lastSyncSpecSign;
                    $updateData['last_sync_base_result'] = json_encode($result);
                    $updateData['base_sync_force'] = 0;
                }
                $updateData['sync_base_last_time'] = time();

                $goodsModel->saveGoodsSync($goods['product_bn'], $updateData);
            }
        }

        app::get('unicom')->model('goods')->updateGoodsData($goods['product_bn'], array('sync_last_time' => time()));
        
        echo ' ====== '. ($errMsg ? $errMsg : 'success'). "\n";
    }
}

echo 'UNICOM PUSH GOODS END '.$mantissa. "\n";