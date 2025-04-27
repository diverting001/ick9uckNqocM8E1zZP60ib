<?php
/**
 * O2O 扩展属性
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_openapi_goods
{
    /**
     * construct
     * @return  mixed
     */
    public function __construct()
    {
        $this->data = json_decode(base64_decode(trim($_POST['data'])),true);
        $_check = kernel::single('b2c_safe_apitoken');
        if ($_check->check_token($_POST, OPENAPI_TOKEN_SIGN) === false)
        {
            self::_apiReturn(false, 70001, '内部请求签名错误');
        }
    }

    // --------------------------------------------------------------------

    /*
     * 获取商品库存
     *
     * @return  string
     */
    public function getGoodsStock()
    {
        $skus = $this->data['skus'];
        $area = $this->data['area'];

        $regions = explode('_', $area);
        $productBns = array_filter(explode(',', $skus));

        if (empty($productBns) OR ! is_array($productBns))
        {
            self::_apiReturn(false, 70002, '请求参数错误');
        }

        $unicomRegionModel = app::get('unicom')->model('regions');

        // 获取地区信息
        $regionInfo = $unicomRegionModel->getRegionInfo($regions[0]);

        $stocks = array();
        if ( ! empty($productBns) && ! empty($regionInfo) && ! in_array($regionInfo['name'], array('新疆', '台湾', '香港', '澳门','西藏', '青海')))
        {
            // 获取二级地址
            $cityRegionInfo = $unicomRegionModel->getRegionInfo($regions[1]);

            // 获取三级地址
            $countyRegionInfo = $unicomRegionModel->getRegionInfo($regions[2]);

            if (in_array($regionInfo['name'], array('北京', '天津', '上海', '重庆')))
            {
                $area = array(
                    'province'  => $regionInfo['name'],
                    'city'      => $regionInfo['mapping_name'],
                    'county'    => ! empty($cityRegionInfo) ? $cityRegionInfo['mapping_name'] : '',
                );
            }
            else
            {
                $area = array(
                    'province'  => $regionInfo['mapping_name'],
                    'city'      => ! empty($cityRegionInfo) ? $cityRegionInfo['mapping_name'] : '',
                    'county'    => ! empty($countyRegionInfo) && ! empty($cityRegionInfo) ? $countyRegionInfo['mapping_name'] : '',
                );
            }
            $stocks = kernel::single('b2c_service_stock')->getStoreNewService($productBns, $area);
        }

        $data = array();
        foreach ($productBns as $bn)
        {
            $stock = isset($stocks[$bn]) ? $stocks[$bn]['stock'] : 0;
            $data[] = array(
                'sku'               => $bn,
                'areaId'            => $area,
                'num'               => $stock,
                'stockStateId'      => $stock > 0 ? 33 : 34,
                'stockStateDesc'    => $stock > 0 ? '有货' : '无货',
                'stockDesc'         => '',
            );
        }

        self::_apiReturn(true, 10000, '请求成功', $data);
    }

    // --------------------------------------------------------------------

    /*
     * 获取指定数量的商品库存
     *
     * @return  string
     */
    public function getGoodsStockByNum()
    {
        $skus = json_decode($this->data['skuNums'], true);
        $area = $this->data['area'];

        $regions = explode('_', $area);

        if (empty($skus) OR ! is_array($skus))
        {
            self::_apiReturn(false, 70002, '请求参数错误');
        }

        $productBns = array();

        foreach ($skus as $sku)
        {
            $productBns[] = $sku['sku'];
        }
        $unicomRegionModel = app::get('unicom')->model('regions');

        // 获取地区信息
        $regionInfo = $unicomRegionModel->getRegionInfo($regions[0]);

        $stocks = array();
        if ( ! empty($regionInfo) && ! in_array($regionInfo['name'], array('新疆', '台湾', '香港', '澳门','西藏', '青海')))
        {
            // 获取二级地址
            $cityRegionInfo = $unicomRegionModel->getRegionInfo($regions[1]);
            // 获取三级地址
            $countyRegionInfo = $unicomRegionModel->getRegionInfo($regions[2]);

            if (in_array($regionInfo['name'], array('北京', '天津', '上海', '重庆')))
            {
                $area = array(
                    'province'  => $regionInfo['name'],
                    'city'      => $regionInfo['mapping_name'],
                    'county'    => ! empty($cityRegionInfo) ? $cityRegionInfo['mapping_name'] : '',
                );
            }
            else
            {
                $area = array(
                    'province'  => $regionInfo['mapping_name'],
                    'city'      => ! empty($cityRegionInfo) ? $cityRegionInfo['mapping_name'] : '',
                    'county'    => ! empty($countyRegionInfo) && ! empty($cityRegionInfo) ? $countyRegionInfo['mapping_name'] : '',
                );
            }

            $stocks = kernel::single('b2c_service_stock')->getStoreNewService($productBns, $area);
        }

        $data = array();
        foreach ($skus as $sku)
        {
            $stock = isset($stocks[$sku['sku']]) ? $stocks[$sku['sku']]['stock'] : 0;
            $num = isset($sku['num']) && $sku['num'] > 1 ? $sku['num'] : 1;
            $state = $stock >= $num ? 33 : ($stock > 0 ? 35 : 34);
            $data[] = array(
                'sku'               => $sku['sku'],
                'state'             => $state,
                'desc'              => $state == 33 ? '有货' : ($state == 35 ? '库存不足' : '无货'),
                'remainNum'         => $stock,
                'stockDesc'         => '',
            );
        }
        if (in_array($regionInfo['name'], array('西藏', '青海'))) {
            foreach ($data as &$datum) {
                $arr = explode('-', $datum['sku']);
                $supplier_bn = $arr[0];
                if (in_array($supplier_bn, array('SHOP', 'SHOPNG'))) {
                    $datum['state'] = 34;
                    $datum['desc'] = '无货';
                    $datum['remainNum'] = 0;
                }
            }
        }

        self::_apiReturn(true, 10000, '请求成功', $data);
    }

    // --------------------------------------------------------------------

    /*
     * 手动同步
     *
     * @return  string
     */
    public function syncGoods()
    {
        $productBn = $this->data['product_bn'];
        $type = $this->data['type'] ? $this->data['type'] : 'all';

        $errMsg = '';
        $result = kernel::single("unicom_goods")->pushGoods($productBn, $type, $errMsg);

        if ( ! $result)
        {
            self::_apiReturn(false, 10001, '同步失败'. ($errMsg ? ':'. $errMsg : ''));
        }

        self::_apiReturn(true, 10000, '同步成功');
    }

    // --------------------------------------------------------------------

    /*
     * 接口返回
     *
     * @param   $result     boolean     返回状态
     * @param   $errId      int         错误ID
     * @param   $errMsg     string      错误描述
     * @param   $data       mixed       返回内容
     * @return  string
     */
    private static function _apiReturn($result = true, $errId = '10000', $errMsg = '', $data = null)
    {
        echo json_encode(array('Result' => $result === true ? 'true' : 'false', 'ErrorId' => $errId, 'ErrorMsg' => $errMsg, 'Data' => $data));
        exit;
    }

}
