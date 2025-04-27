<?php
/**
 * 联通商品 crontab
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_goods
{
    const COMCODE = 'chinaunicom_flsc';  //合作企业编号
    const POOLNO = '939952';  //商品池编号
    
    static $_pushGoodsTypes = array(
        'all'           => '全部',
        'base'          => '基础信息',
        'price'         => '价格',
        'marketable'    => '上架状态',
        'image'         => '商品图片',
        'spec'          => '规格',
        'related'       => '相关商品',
    );

    private $_requestCount = 0;

    // 商品信息
    private $_goodsInfo = array();

    private $_productNamePrefix = array(
        'SF' => '【顺丰优选】',
        'MI' => '【小米有品】',
        'KL' => '【考拉】',
        'WM' => '【中粮我买网】',
        'WMJC' => '【中粮我买网】',
        'WMNG' => '【中粮我买网】',
        'WMNGX' => '【中粮我买网】',
    );

    // 供应商名称
    private $_supplierName = array(
        'YHD' => '一号店', //1号店goods_type对应root分类name
        'YJ' => '西街网', //云聚goods_type对应root分类name
        'DRL' => '西街网', //岱润霖goods_type对应root分类name
        'JD' => '京东', //京东goods_type对应root分类name
        'YGSX' => '易果生鲜', //易果生鲜对应root分类name
        'LDPW'  => '联动票务', // 联动票务对应root分类name
        'YKQ'  => '一块去', // 一块去对应root分类name
        'KL' => '网易考拉', // 网易考拉对应root分类name
        'OF' => '欧飞', // 欧飞对应root分类name
        'MRYX' => '每日优鲜', // 每日优鲜对应root分类name
        'ZP' => '珍品网', // 珍品网对应root分类name
        'OKBUY' => '好乐买', // 好乐买对应root分类name
        'VIP' => '唯品会', // 唯品会 supplier_bn对应支持品牌列表
        'WM' => '我买网', // 我买网 supplier_bn对应支持品牌列表
        'WMX' => '我买网', // 我买网 supplier_bn对应支持品牌列表
        'WMNG' => '我买网',
        'WMNGX' => '我买网',
        'WMJC' => '我买网',
        'WMJCX' => '我买网',
        'XMLY' => '喜马拉雅', // 喜马拉雅对应的sdb_b2c_goods_type分类名
        'OFHF' => '欧飞话费', // 店铺对应root分类name
        'SF' => '顺丰优选', // 顺丰优选 对应root分类name
        'MI' => '小米有品', // 小米对应的sdb_b2c_goods_type分类名
        'JDHD' => '京东华东', // 京东华东对应的sdb_b2c_goods_type分类名
        'WMQY' => '我买网', // 我买网企业对应的sdb_b2c_goods_type分类名
        'NG' => '内购',
        'JY' => '聚优福利',
        'FX' => '福喜',
        'SN' => '苏宁', // 苏宁
    );

    // 品牌名称
    private $_supplierBrandName = array(
        'WMNG'  => '中粮',
        'WM'    => '中粮',
        'WMJC'  => '中粮',
        'MI'    => '小米有品',
    );

    /**
     * 推送商品
     *
     * @param   $productBn  string      货品BN
     * @param   $type       string      推送类型
     * @param   $errMsg     string      错误信息
     * @return  mixed
     */
    public function pushGoods($productBn, $type = 'all', & $errMsg = '')
    {
        if ( ! isset(self::$_pushGoodsTypes[$type]))
        {
            $errMsg = '不支持的推送类型';
            return false;
        }

        $this->_requestCount = 0;

        $errMsg = '';

        $goodsExtraInfo = app::get('unicom')->model('goods')->getGoodsExtraInfo($productBn);

        if (empty($goodsExtraInfo))
        {
            return true;
        }

        $this->_goodsInfo = $goodsExtraInfo;

        if (empty($goodsExtraInfo['p_sku']) && $type != 'all')
        {
            $errMsg = '商品未推送';
            return false;
        }

        $result = false;

        // 更新同步状态
        app::get('unicom')->model('goods')->updateSyncStatus($productBn, 'process', '同步中');

        $reason = '';
        switch ($type)
        {
            // 全部
            case 'all':
                $goodsData = $this->_getGoodsAll($productBn, $errMsg);
                $reason = $errMsg;
                $result = $this->_pushGoodsAll($goodsData, $errMsg);
                // 商品池推送
                $this->_pushGoodsPool($productBn);
                break;
            // 基础信息
            case 'base':
                $goodsData = $this->_getGoodsBase($productBn, $errMsg);
                $reason = $errMsg;
                $result = $this->_pushGoodsBase($goodsData, $errMsg);
                break;
            // 价格
            case 'price':
                $goodsData = $this->_getGoodsPrice($productBn, $errMsg);
                $reason = $errMsg;
                $result = $this->_pushGoodsPrice($goodsData, $errMsg);
                $this->_pushGoodsPoolPrice($goodsData);
                break;
            // 上架状态
            case 'marketable':
                $marketable = $this->_getGoodsMarketable($productBn, $reason);
                $goodsData = $marketable;
                $result = $this->_pushGoodsMarketable($productBn, $marketable, $errMsg);
                break;
            // 下架
            case 'market_disable':
                $result = $this->_pushGoodsMarketable($productBn, false, $errMsg);
                break;
            // 商品图片
            case 'image':
                $goodsData = $this->_getGoodsImage($productBn, $errMsg);
                $reason = $errMsg;
                $result = $this->_pushGoodsImage($goodsData, $errMsg);
                break;
            // 规格
            case 'spec':
                $goodsData = $this->_getGoodsSpec($productBn);
                $result = $this->_pushGoodsSpec($goodsData, $productBn, $errMsg);
                break;
            // 相关商品
            case 'related':
                $goodsData = $this->_getGoodsRelated($productBn, $errMsg);
                $reason = $errMsg;
                $result = $this->_pushGoodsRelated($goodsData, $errMsg);
                break;
        }

        // 更新同步状态
        if ( ! $errMsg)
        {
            $status = 'success';
            $errMsg = '同步成功';
        }
        else
        {
            $status = 'failed';
            $errMsg = '同步失败'. '('. $errMsg. ')';
        }

        app::get('unicom')->model('goods')->updateSyncStatus($productBn, $status, $errMsg);

        // 添加同步日志
        app::get('unicom')->model('goods')->addGoodsSyncLog($productBn, $type, $goodsData, $this->_requestCount, $reason);

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 通过平台SKU获取商品信息
     *
     * @param   $PSku       string      平台商品编码
     * @return  array
     */
    public function getGoodsInfoByPSku($PSku)
    {
        $return = array();

        $godsModel = app::get('unicom')->model('goods');

        // 通过平台SKU获取商品信息
        $goodsInfo = $godsModel->getGoodsExtraInfoByPSku($PSku);

        if (empty($goodsInfo))
        {
            return $return;
        }

        $goodsInfo = $godsModel->getGoodsAllInfo($goodsInfo['product_bn']);

        if (empty($goodsInfo))
        {
            return $return;
        }

        // 税率
        $tax = self::number2Price($goodsInfo['extra']['tax'], 2);

        // 税额
        $taxPrice = $tax > 0 ? self::number2Price($goodsInfo['base']['product']['price'] / (1 + $tax) * $tax) : 0;

        $return = array(
            'product_id'        => $goodsInfo['base']['product']['product_id'],
            'product_bn'        => $goodsInfo['base']['product']['bn'],
            'bizPrice'          => self::number2Price($goodsInfo['base']['product']['price']), // 售价
            'nakedPrice'        => self::number2Price($goodsInfo['base']['product']['price'] - $taxPrice), // 净价
            'bizTaxPrice'       => $taxPrice, // 税价
            'taxRate'           => $tax, // 税率
        );

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 检查商品是否开放
     *
     * @param   $productBn       mixed      货品编码
     * @return  mixed
     */
    public function checkGoodsScope($productBn)
    {
        $return = array();

        if (empty($productBn))
        {
            return $return;
        }

        // 获取商品开通范围
        $scopeList = app::get('unicom')->model('goods')->getGoodsScope();

        if (empty($scopeList))
        {
            return $return;
        }

        if ( ! empty($scopeList))
        {
            foreach ($scopeList as $key => $scope)
            {
                $scopeList[$scope['scope']] = $scope['scope_items'] ? explode(',', $scope['scope_items']) : array();
            }
        }

        $bnList = is_array($productBn) ? $productBn : array($productBn);

        $productList = app::get('b2c')->model('products')->getProductByBn($bnList, 'bn,pop_shop_id');

        foreach ($productList as $product)
        {
            $prefix = substr($product['bn'], 0, strpos($product['bn'], '-'));

            if ($prefix == 'SHOP' OR $prefix == 'SHOPNG' )
            {
                if ( ! empty($scopeList['POP']) && in_array($product['pop_shop_id'], $scopeList['POP']))
                {
                    $return[$product['bn']] = true;
                    continue;
                }
            }
            elseif ( ! empty($scopeList['THIRD-PARTY']) && in_array($prefix, $scopeList['THIRD-PARTY']))
            {
                $return[$product['bn']] = true;
                continue;
            }

            $return[$product['bn']] = false;
        }

        return is_array($productBn) ? $return : current($return);
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品基本信息
     *
     * @param   $productBn       mixed      货品编码
     * @return  mixed
     */
    public function getGoodsBaseData($productBn)
    {
        // 获取商品基础信息
        $goodsBase = $this->_getGoodsBase($productBn, $errMsg);
        if ( ! empty($goodsBase))
        {
            unset($goodsBase['price'], $goodsBase['nakedPrice'], $goodsBase['taxPrice'], $goodsBase['taxRate'], $goodsBase['state']);
        }

        // 获取商品图片信息
        $goodsImage = $this->_getGoodsImage($productBn, $errMsg);

        // 获取商品规格信息
        $goodsSpec = $this->_getGoodsSpec($productBn, $errMsg);

        return array('base' => $goodsBase, 'image' => $goodsImage, 'spec' => $goodsSpec);
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品价格和上架状态
     *
     * @param   $productBn       mixed      货品编码
     * @return  mixed
     */
    public function getGoodsCoreData($productBn)
    {
        $errMsg = '';
        // 获取商品价格信息
        $goodsPrice = $this->_getGoodsPrice($productBn, $errMsg);

        if (empty($goodsPrice))
        {
            return false;
        }
        // 获取商品上下架状态
        $marketable = $this->_getGoodsMarketable($productBn);

        return array('prices' => $goodsPrice, 'marketable' => $marketable ? 'true' : 'false');
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品全部信息
     *
     * @param   $productBn      string   商品编码
     * @param   $errMsg         string  错误信息
     * @return  array
     */
    private function _getGoodsAll($productBn, & $errMsg = '')
    {
        $return = array();

        // 获取商品基础信息
        $goodsBase = $this->_getGoodsBase($productBn, $errMsg);
        if (empty($goodsBase))
        {
            return $return;
        }

        // 获取商品价格信息
        $goodsPrice = $this->_getGoodsPrice($productBn, $errMsg);
        if (empty($goodsPrice))
        {
            return $return;
        }

        // 获取商品图片信息
        $goodsImage = $this->_getGoodsImage($productBn, $errMsg);
        if (empty($goodsImage))
        {
            return $return;
        }

        // 获取商品规格信息
        $goodsSpec = $this->_getGoodsSpec($productBn, $errMsg);

        $return = array_merge($goodsBase, $goodsPrice);

        // 售价
        $return['price'] = $goodsPrice['bizPrice'];

        // 市场价
        $return['marketPrice'] = $goodsPrice['supplierPrice'];

        // 税额
        $return['taxPrice'] = $goodsPrice['bizTaxPrice'];

        // 图片
        $return['img'] = ! empty($goodsImage['imgInfo']) ? $goodsImage['imgInfo'] : array();

        // 规格属性
        $return['specInfo'] = ! empty($goodsSpec) && $goodsSpec[0]['specInfo'] ? $goodsSpec[0]['specInfo'] : array();
        $return['specSku'] = ! empty($goodsSpec) && $goodsSpec[0]['specSku'] ? $goodsSpec[0]['specSku'] : array();

        // 销售受限（0 不受限）
        $return['salLimitState'] = 0;

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品基础信息
     *
     * @param   $productBn      string   商品编码
     * @param   $errMsg         string  错误信息
     * @return  array
     */
    private function _getGoodsBase($productBn, & $errMsg = '')
    {
        $return = array();
        // 获取商品的所有信息
        $goodsInfo = app::get('unicom')->model('goods')->getGoodsAllInfo($productBn);

        if (empty($goodsInfo['base']) OR empty($goodsInfo['extra']))
        {
            $errMsg = '获取商品信息失败';
            return $return;
        }

        // 获取商品上下架状态
        $marketable = $this->_getGoodsMarketable($productBn);

        // 税率
        $tax = self::number2Price($goodsInfo['extra']['tax'], 2);

        // 税额
        $taxPrice = $tax > 0 ? self::number2Price($goodsInfo['base']['product']['price'] / (1 + $tax) * $tax) : 0;

        $detailStyle = '';
        $detailExtra = '';
        $titleExplain = '';
        if (strpos($productBn, 'YXKA-') or strpos($productBn, 'YXSRBT-') !== false OR strpos($productBn, 'OKBUY-') !== false)
        {
            $detailStyle = '<style>.yx_detail_spec{overflow: hidden;}.yx_detail_spec li{padding: 8px 30px;float: left;min-width: 49%;line-height: 24px;font-size: 14px;border-bottom: 1px dashed #e8e8e8;width:  100%}.yx_detail_spec li.title{font-size: 16px;color: #333;padding: 20px 30px 10px;}.yx_detail_spec li span.label{background:#fff;text-align:left;float:left;width: 105px;color:#333;font-size:14px;font-family: "Heiti SC","Lucida Grande","Hiragino Sans GB","Hiragino Sans GB W3",verdana;}.yx_detail_spec li p.text{float:left;color: #999;font-size:14px;font-family: "Heiti SC","Lucida Grande","Hiragino Sans GB","Hiragino Sans GB W3",verdana;}#tab_1 p img{vertical-align: middle;}</style>';
        }
        elseif (strpos($productBn, 'VIP-') !== false)
        {
            $detailStyle .= '<style>.vip-table{display:block; width:750px;margin-left:0px;font-size:14px;margin-bottom:15px;}.vip-table tr{border: 1px solid #C7C7C7;}.vip-table tr:first-child th{width:750px; background:#EEF7FE;}.vip-table th {padding:5px;text-align:center;}.vip-table td {padding:2px 5px;background:#fff; border-left: 1px solid #C7C7C7;}.vip-table td:first-child{border-left:0;}.vip-table .tdTitle {text-align:right;width:110px; background:#F5FAFE;}.vip-table th.tdTitle{text-align:center;}#tab_1 img{vertical-align: middle;}</style>';
            $detailExtra .= '<img width="750px" src="//cdn.neigou.com/public/v2/images/0a/14/b1/57c19111a3df0f430d4817307f81fc43.jpg?1539057072#h">';
            $goodsInfo['base']['product']['name'] = '【唯品会】'. $goodsInfo['base']['product']['name'];

            // 双十一发货提示
            if (time() < 1575129600 && in_array($goodsInfo['extra']['category_code'], array(102070201,102070202,102070203,102070204,102070205,102070206,102070207,102070208,102070209,102070301,102070302,102070303,102070304,102070305,102070306,102070307,102070401,102070402,102070403,102070404,102070405,102070406,102070407,102070408,1021306080,1021306081,1021306082,1021306083,1021306084,1021306085,1021306086,1021306087,1021306088,1021306089,1021306090,1021306091,1021306092,1021306093,1021306094,1021306095,1021306096,1021306097,102080101,102080102,102080201,102080202,102080203,102080301,102080302,102080401,102080402,102080403,102080404,102080405,102080501,102080502,102080503,102080601,102080602,102080603,102080604,102080701,102080702,102080703,102080704,102080705,102080706,102080707,102080799,102080801,102080802,102080803,102080901,102080902,102080903,102070308,102070309,102070310,102070311,102070312,102070313,102070314,102070315,102070316,102070317,102070399,102070409,102070410,102070411,102070412,102070413,102070414,102070415,102070416,102070501,102070502,102070503,102070504,102070505,102070506,102070507,102070508,102070509,102070510,102070511,102070512,102070513,102070514,102070515,102070516,102070517,102070518,102070519,102070520,102070521,102070522,102070523,102070524,102070525,102070526,102070527,102070528,102070529,102080103,102080204,102080205,102080206,102080207,102080208,102080406,102080407,102080408,102080409,102080410,102080411,102080412,102080413,102080414,102080415,102080416,102080417,102080418,102080419,102080420,102080421,102080422,102080423,102080424,102080425,102080504,102080505,102080506,102080507,102080508,102080708,102080709,102080804,102070210,102070211,102070318,102070319,102070320,102070321,102070601,102070602,102070603,102070604,102070605,102070606,102070607,102070608,102070609,102070610,102070611,102070612,102070613,102070614,102070615,102070616,102070617,102070618,102070701,102070702,102070703,102070704,102070705,102070706,102070707,102070708,102070709,102070710,102070711,102070712,102070713,102070714,102070715,102070716,102070717,102070718,102070719,102070720,102070721,102070722,102070801,102070802,102070803,102070804,102070805,102070806,102070807,102070808,102070809,102070810,102070811,102070812,102070813,102070814,102070815,102070816,102070817,102070818,102070819,102070820,102070821,102070822,102080426,102080427,102080428,102080429,102080430,102080431,102080432,102070101,102070102,102070103,102070104,102070105,102070106,102070107,102070723,102070724,102070823,102070824)))
            {
                $titleExplain = "【因临近双11，发货周期3-7天不等】";
            }
        }

        // 商品名称追加前缀
        $supplierBn = current(explode('-', $productBn));

        if (isset($this->_productNamePrefix[$supplierBn]))
        {
            $goodsInfo['base']['product']['name'] = $this->_productNamePrefix[$supplierBn]. $goodsInfo['base']['product']['name'];
        }

        if (strpos($productBn, 'YXKA-') !== false || strpos($productBn, 'YXSRBT-') !== false)
        {
            $brandName = '其他';
            $goodsInfo['base']['product']['name'] = ''. $goodsInfo['base']['product']['name'];
        }
        else
        {
            $brandInfo = app::get('b2c')->model('brand')->getBrandInfo($goodsInfo['base']['goods']['brand_id']);
            $brandName = $brandInfo ? $brandInfo['brand_name'] : '-';
        }
        
        if ($brandName == '-' && $this->_supplierBrandName[$supplierBn])
        {
            $brandName = $this->_supplierBrandName[$supplierBn];
        }

        $specInfo  = '';

        if ($goodsInfo['base']['product']['spec_info'])
        {
            $specList = explode('、', $goodsInfo['base']['product']['spec_info']);
            foreach($specList as & $spec)
            {
                $spec = substr($spec, strpos($spec, '：') + 3);
            }
            $specInfo = '（'. implode(',', $specList). '）';
        }

        if ($supplierBn == 'VDG') {
            $supplierBn = 'VIP';
        } elseif ($supplierBn == 'SF2') {
            $supplierBn = 'SF';
        }
        $supplierName = $this->_supplierName[$supplierBn] ? $this->_supplierName[$supplierBn] : '点滴';
        $return = array(
            'sku'           => $goodsInfo['base']['product']['bn'],
            'name'          => ($goodsInfo['extra']['rename'] ? $goodsInfo['extra']['rename'] : $goodsInfo['base']['product']['name']). $specInfo. $titleExplain,
            'upc'           => $goodsInfo['extra']['upc'],
            'unit'          => $goodsInfo['extra']['unit'],
            'price'         => self::number2Price($goodsInfo['base']['product']['price']), // 售价
            'nakedPrice'    => self::number2Price($goodsInfo['base']['product']['price']) - $taxPrice, // 净价,
            'marketPrice'   => self::number2Price($goodsInfo['base']['product']['mktprice']),
            'taxPrice'      => $taxPrice,
            'taxRate'       => $tax,
            'minBuyNum'     => 1,
            'categoryCode'  => $goodsInfo['extra']['category_code'],
            'categoryName'  => $goodsInfo['extra']['category_name'],
            'brandCn'       => $brandName,
            'details'       => $detailStyle. $goodsInfo['base']['goods']['intro']. $detailExtra,
            'state'         => $marketable ? 1 : 0,
            'recommend'     => $goodsInfo['extra']['recommend'],
            'originalProviderCode' => $supplierBn,
            'originalProviderName' => $supplierName,
        );

        // 过滤名称中的换行符号 制表符
        $return['name'] = str_replace(array("/r/n", "/r", "/n", "\r", "\n", "\r\n", "\t", "　", "	"), " ", $return['name']);

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品价格
     *
     * @param   $productBn      string   商品编码
     * @param   $errMsg         string  错误信息
     * @return  array
     */
    private function _getGoodsPrice($productBn, & $errMsg = '')
    {
        $return = array();

        // 获取商品的所有信息
        $goodsInfo = app::get('unicom')->model('goods')->getGoodsAllInfo($productBn);

        if (empty($goodsInfo['base']) OR empty($goodsInfo['extra']))
        {
            $errMsg = '获取商品信息失败';
            return $return;
        }

        // 税率
        $tax = self::number2Price($goodsInfo['extra']['tax'], 2);

        // 税额
        $taxPrice = $tax > 0 ? self::number2Price($goodsInfo['base']['product']['price'] / (1 + $tax) * $tax) : 0;

        $return = array(
            'sku'               => $goodsInfo['base']['product']['bn'],
            'bizPrice'          => self::number2Price($goodsInfo['base']['product']['price']), // 售价
            'supplierPrice'     => self::number2Price($goodsInfo['base']['product']['mktprice']), // 市场价
            'bizNakedPrice'     => self::number2Price($goodsInfo['base']['product']['price'] - $taxPrice), // 裸价
            'bizTaxPrice'       => $taxPrice, // 税价
            'taxRate'           => $tax, // 税率
        );

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品上下架状态
     *
     * @param   $productBn      string   商品编码
     * @param   $errMsg         string  错误信息
     * @return  boolean
     */
    private function _getGoodsMarketable($productBn, & $errMsg = '')
    {
        // 获取商品的所有信息
        $goodsInfo = app::get('unicom')->model('goods')->getGoodsAllInfo($productBn);

        if (empty($goodsInfo) OR $goodsInfo['extra']['status'] != 1)
        {
            $errMsg = '未获取到商品信息或为禁售状态';
            return false;
        }

        // 检查商品开放状态
        if ( ! $this->checkGoodsScope($productBn))
        {
            $errMsg = '商品未开放';
            return false;
        }

        // 获取商品的目录信息
        $categoryInfo = app::get('unicom')->model('goods')->getGoodsCategoryInfo($goodsInfo['extra']['category_code']);

        if (empty($categoryInfo) OR $categoryInfo['status'] != 1)
        {
            $errMsg = '商品目录不存在或目录不可用';
            return false;
        }

        if ($goodsInfo['base']['product']['marketable'] != 'true' OR $goodsInfo['base']['goods']['marketable'] != 'true')
        {
            $errMsg = '商品下架';
            return false;
        }

        // 价格
        if ($goodsInfo['base']['product']['price'] <= 0 OR $goodsInfo['base']['product']['cost'] <= 0 OR $goodsInfo['base']['product']['price'] >= 4000)
        {
            $errMsg = '价格';
            return false;
        }

        // YXSRBT 如果没有规格则下架
        if (in_array(current(explode('-', $productBn)), array('YXSRBT')))
        {
            $goodsData = $this->_getGoodsSpec($productBn, $errMsg);

            if (empty($goodsData))
            {
                return false;
            }

            $specStatus = false;
            foreach ($goodsData as $goods)
            {
                if ($goods['sku'] == $productBn)
                {
                    if ( ! empty($goods['specInfo']))
                    {
                        $specStatus = true;
                    }
                }
            }

            if ( ! $specStatus)
            {
                $errMsg = 'YXSRBT 无规格下架';
            }

            return $specStatus;
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品图片
     *
     * @param   $productBn      string   商品编码
     * @param   $errMsg         string  错误信息
     * @return  array
     */
    private function _getGoodsImage($productBn, & $errMsg = '')
    {
        $return = array();

        // 获取商品的所有信息
        $goodsInfo = app::get('unicom')->model('goods')->getGoodsAllInfo($productBn);

        if (empty($goodsInfo['base']) OR empty($goodsInfo['extra']))
        {
            $errMsg = '获取商品信息失败';
            return $return;
        }

        $imgInfo = array();
        $largeImageList = explode(',public', $goodsInfo['base']['product']['l_img_url']);
        $middleImageList = explode(',public', $goodsInfo['base']['product']['m_img_url']);
        $smallImageList = explode(',public', $goodsInfo['base']['product']['s_img_url']);
        $imageCount = count($largeImageList);
        $prefix = '';
        for ($i = 0; $i < $imageCount; $i++)
        {
            if (strpos($largeImageList[$i], '.mp4') !== false)
            {
                continue;
            }
            if ($i == 1)
            {
                $prefix = 'public';
            }
            $imgInfo[] = array(
                'path'          => 'http:'. CDN_BASE_URL. '/'. $prefix. str_replace(array('h_600', 'w_600'), array('h_435', 'w_435'), $largeImageList[$i]). (strpos($largeImageList[$i], '?') ? ',.jpg' : ''),
                'isPrimary'     => $i == 0 ? 1 : 0,
                'orderSort'     => 0,
                'listImgPath'   => 'http:'. CDN_BASE_URL. '/'. $prefix. $middleImageList[$i]. (strpos($middleImageList[$i], '?') ? ',.jpg' : ''),
                'smallImgPath'  => 'http:'.CDN_BASE_URL. '/'. $prefix. $smallImageList[$i]. (strpos($smallImageList[$i], '?') ? ',.jpg' : ''),
                'bigImgPath'    => 'http:'.CDN_BASE_URL. '/'. $prefix. $largeImageList[$i]. (strpos($largeImageList[$i], '?') ? ',.jpg' : ''),
            );
        }

        $return = array(
            'sku'           => $goodsInfo['base']['product']['bn'],
            'imgInfo'       => $imgInfo
        );

        return $return;
    }


    // --------------------------------------------------------------------

    /**
     * 获取商品规格
     *
     * @param   $productBn      string   商品编码
     * @param   $errMsg         string  错误信息
     * @return  array
     */
    private function _getGoodsSpec($productBn, & $errMsg = '')
    {
        $return = array();

        $goodsSpec = app::get('unicom')->model('goods')->getGoodsSpec($productBn);

        if (empty($goodsSpec))
        {
            return $return;
        }

        foreach ($goodsSpec as $product)
        {
            $spec = array(
                'sku' => $product['bn'],
            );

            $specValue = array();
            if (empty($product['spec']))
            {
                $spec['specState'] = 0;
                $spec['specInfo'] = array();
            }
            else
            {
                $spec['specState'] = 1;
                foreach ($product['spec'] as $k => $v)
                {
                    if ( ! isset($specInfo[$k]))
                    {
                        $specInfo[$k] = array('name' => $k, 'value' => $v);
                    }
                    else
                    {
                        $specInfo[$k]['value'] .= ',' . $v;
                    }

                    $specValue[] = array('name' => $k, 'value' => $v);
                }
            }

            $specSku[] = array('specValue' => $specValue, 'sku' => $product['bn']);

            $spec['specInfo'] = & $specInfo;
            $spec['specSku'] = & $specSku;

            $return[] = $spec;
        }

        if ( ! empty($return))
        {
            if ( ! empty($specInfo))
            {
                foreach ($specInfo as & $specInfoItem)
                {
                    $specInfoItem['value'] = implode(',', array_unique(explode(',', $specInfoItem['value'])));
                }
            }

            foreach ($return as & $v)
            {
                if ( ! empty($v['specInfo']))
                {
                    $v['specInfo'] = array_values($v['specInfo']);
                }
            }
        }

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品全部信息
     *
     * @param   $goodsData      array   商品信息
     * @param   $errMsg         string  错误信息
     * @return  mixed
     */
    private function _pushGoodsAll($goodsData, & $errMsg = '')
    {
        if (empty($goodsData))
        {
            $errMsg = '商品信息错误';
            return false;
        }

        // 推送商品数据
        $result = kernel::single('unicom_request')->request(array('method' => 'importGoodsInfo', 'data' => array('sku' => $goodsData['sku'], 'goodsInfo' => json_encode($goodsData))), $errMsg);

        $this->_requestCount++;

        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
            return $result;
        }

        // 获取商品信息
        $goodsInfo = app::get('unicom')->model('goods')->getGoodsExtraInfo($goodsData['sku']);

        // 保存商品平台SKU
        if ( ! empty($goodsInfo) && empty($goodsInfo['p_sku']))
        {
            app::get('unicom')->model('goods')->updateGoodsData($goodsData['sku'], array('p_sku' => $result['result']['sku']));
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品基础信息
     *
     * @param   $goodsData      array   商品信息
     * @param   $errMsg         string  错误信息
     * @return  mixed
     */
    private function _pushGoodsBase($goodsData, & $errMsg = '')
    {
        if (empty($goodsData))
        {
            $errMsg = '商品信息错误';
            return false;
        }

        // 推送商品数据
        $result = kernel::single('unicom_request')->request(array('method' => 'updateGoodsInfo', 'data' => array('sku' => $goodsData['sku'], 'goodsInfo' => json_encode($goodsData))), $errMsg);

        $this->_requestCount++;

        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
            return $result;
        }

        $productBn = $goodsData['sku'];
        // 价格更新
        $coreData = $this->getGoodsCoreData($productBn);

        $goodsModel = app::get('unicom')->model('goods');

        // 更新缓存
        $goodsSync = $goodsModel->getGoodsSync($productBn);

        $lastSyncCoreData = $goodsSync['last_sync_core_data'] ? json_decode($goodsSync['last_sync_core_data'], true) : array();

        if (json_encode($lastSyncCoreData) != json_encode($coreData))
        {
            if (json_encode($lastSyncCoreData['prices']) != json_encode($coreData['prices']))
            {
                $coreData['prices'] = array();
            }

            $goodsModel->saveGoodsSync($productBn, array('last_sync_core_data' => json_encode($coreData)));
        }

        // 推送商品池上架
        if ($this->_getGoodsMarketable($productBn))
        {
            $this->_pushGoodsPoolState($productBn, 1);
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品基础信息
     *
     * @param   $goodsData      string   商品信息
     * @param   $errMsg         string  错误信息
     * @return  mixed
     */
    private function _pushGoodsPrice($goodsData, & $errMsg = '')
    {
        if (empty($goodsData))
        {
            $errMsg = '商品信息错误';
            return false;
        }
        // 推送商品数据
        $result = kernel::single('unicom_request')->request(array('method' => 'updateSkuPrice', 'data' => array('skuPriceInfo' => json_encode(array($goodsData)))), $errMsg);

        $this->_requestCount++;

        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
            return $result;
        }


        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品上架状态
     *
     * @param   $productBn      mixed   商品编码
     * @param   $marketable     mixed   上下架状态
     * @param   $errMsg         string  错误信息
     * @return  mixed
     */
    private function _pushGoodsMarketable($productBn, $marketable = null, & $errMsg = '')
    {
        if ($marketable === null)
        {
            $marketable = $this->_getGoodsMarketable($productBn);
        }
        $goodsData = array(
            'sku' => $productBn,
            'state' => $marketable ? 1 : 0,
        );

        // 避免重复下架
        if ($marketable === false && $this->_goodsInfo['marketable'] === 'false')
        {
            return true;
        }

        $result = kernel::single('unicom_request')->request(array('method' => 'updateSkuStateInfo', 'data' => array('skuStateInfo' => json_encode(array($goodsData)))), $errMsg);

        $this->_requestCount++;
        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
            if (strpos($errMsg, '商品已上架') === false && strpos($errMsg, '商品已下架') === false)
            {
                return $result;
            }
            else
            {
                $errMsg = '';
            }
        }

        if ($marketable)
        {
            $this->_pushGoodsPoolState($productBn, 1);
        }

        $goodsModel = app::get('unicom')->model('goods');

        // 更新联通上下架状态
        $goodsModel->updateGoodsData($productBn, array('marketable' => $marketable ? 'true' : 'false'));
        // 更新缓存
        $goodsSync = $goodsModel->getGoodsSync($productBn);

        $lastSyncCoreData = $goodsSync['last_sync_core_data'] ? json_decode($goodsSync['last_sync_core_data'], true) : array();

        $marketable = $marketable ? 'true' : 'false';

        if ($lastSyncCoreData['marketable'] !== $marketable)
        {
            $lastSyncCoreData['marketable'] = $marketable;
            $goodsModel->saveGoodsSync($productBn, array('last_sync_core_data' => json_encode($lastSyncCoreData)));
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品图片
     *
     * @param   $goodsData      string   商品信息
     * @param   $errMsg         string  错误信息
     * @return  mixed
     */
    private function _pushGoodsImage($goodsData, & $errMsg = '')
    {
        if (empty($goodsData))
        {
            $errMsg = '商品信息错误';
            return false;
        }

        // 推送商品数据
        $result = kernel::single('unicom_request')->request(array('method' => 'updateSkuImgInfo', 'data' => array('sku' => $goodsData['sku'], 'skuImgInfo' => json_encode(array($goodsData)))), $errMsg);

        $this->_requestCount++;
        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品规格
     *
     * @param   $goodsData      array   商品信息
     * @param   $errMsg         string  错误信息
     * @param   $productBn      string
     * @return  mixed
     */
    private function _pushGoodsSpec($goodsData, $productBn, & $errMsg = '')
    {
        if (empty($goodsData))
        {
            $errMsg = '商品信息错误';
            return false;
        }

        $goodsData = array($goodsData[0]);

        // 推送商品数据
        $result = kernel::single('unicom_request')->request(array('method' => 'updateSkuSpecInfo', 'data' => array('skuSpecInfo' => json_encode($goodsData))), $errMsg);

        $this->_requestCount++;
        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
        }

        return true;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品至商品池
     *
     * @param   $productBn      string      商品编码
     * @param   $type           int         类型 1:增加 2:删除
     * @param   $errMsg         string      错误信息
     * @return  mixed
     */
    private function _pushGoodsPool($productBn, $type = 1, & $errMsg = '')
    {
        if (empty($productBn))
        {
            $errMsg = '商品信息错误';
            return false;
        }

        $goodsPrice = $this->_getGoodsPrice($productBn);

        $goodsInfo = array(
            'sku' => $productBn,
            'price' => $goodsPrice['bizPrice'], // 售价
            'marketPrice' => $goodsPrice['supplierPrice'], // 市场价
            'taxRate' => $goodsPrice['taxRate'], // 税率
            'taxPrice' => $goodsPrice['bizTaxPrice'], // 税价
            'nakedPrice' => $goodsPrice['bizNakedPrice'], // 裸价
        );

        $result = kernel::single('unicom_request')->request(array('method' => 'updateSkuToGoodsPool', 'data' => array('comCode' => self::COMCODE, 'poolNo' => self::POOLNO, 'skus' => json_encode(array($goodsInfo)), 'actType' => $type)), $errMsg);

        $this->_requestCount++;

        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 推送商品池状态
     *
     * @param   $productBn      string      商品编码
     * @param   $status         int         类型 1:上架 0:下架
     * @param   $errMsg         string      错误信息
     * @return  mixed
     */
    private function _pushGoodsPoolState($productBn, $status = 1, & $errMsg = '')
    {
        if (empty($productBn))
        {
            $errMsg = '商品信息错误';
            return false;
        }

        $data = array(
            'comCode' => self::COMCODE,
            'poolNo' => self::POOLNO,
            'skus' => json_encode(array(array('sku' => $productBn, 'state' => $status))),
        );
        $result = kernel::single('unicom_request')->request(array('method' => 'updateGoodsPoolSkuState', 'data' => $data), $errMsg);

        $this->_requestCount++;
        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 更新商品池价格
     *
     * @param   $priceData      array       价格
     * @param   $errMsg         string      错误信息
     * @return  mixed
     */
    private function _pushGoodsPoolPrice($priceData, & $errMsg = '')
    {
        if (empty($priceData))
        {
            $errMsg = '商品信息错误';
            return false;
        }

        $data = array(
            'sku'               => $priceData['sku'],
            'price'             => $priceData['bizPrice'], // 售价
            'marketPrice'       => $priceData['supplierPrice'], // 售价
            'nakedPrice'        => $priceData['bizNakedPrice'], // 售价
            'taxPrice'          => $priceData['bizTaxPrice'], // 税价
            'taxRate'           => $priceData['taxRate'], // 税率
        );

        $data = array(
            'comCode' => self::COMCODE,
            'poolNo' => self::POOLNO,
            'skus' => json_encode(array($data)),
        );

        $result = kernel::single('unicom_request')->request(array('method' => 'updateGoodsPoolSkuPrice', 'data' => $data), $errMsg);

        $this->_requestCount++;
        if ($result['success'] != 'true' OR empty($result['result']))
        {
            $errMsg = ! empty($result['resultMessage']) ? $result['resultMessage'] : '推送失败';
        }

        return $result;
    }

    // --------------------------------------------------------------------

    /**
     * 数字转为价格
     *
     * @param   $number     float       数字
     * @param   $decimals   int         小数位数
     * @return  float
     */
    private static function number2Price($number, $decimals = 3)
    {
        return (double) number_format($number, $decimals, '.', '');
    }

}
