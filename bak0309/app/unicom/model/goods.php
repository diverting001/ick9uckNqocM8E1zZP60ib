<?php
/**
 * 联通商品分类
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_mdl_goods extends base_db_external_model
{
    // 数据库配置
    private $db_conf = array(
        'MASTER' => array('HOST' => DB_HOST,
            'NAME' => DB_NAME,
            'USER' => DB_USER,
            'PASSWORD' => DB_PASSWORD)
    );

    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app, $this->db_conf);
    }

    // --------------------------------------------------------------------

    /**
     * 保存商品分类信息
     *
     * @param   $productBn  string  货品编码
     * @return  array
     */
    public function getGoodsAllInfo($productBn)
    {
        static $goodsList;
        if (isset($goodsList[$productBn]))
        {
            return $goodsList[$productBn];
        }

        $goodsExtra = $this->getGoodsExtraInfo($productBn);

        if (empty($goodsExtra))
        {
            return array();
        }

        $goodsBaseInfo = $this->getGoodsBaseInfo($goodsExtra['product_bn']);

        if (empty($goodsBaseInfo))
        {
            return array();
        }

        $goodsList[$productBn] = array(
            'base'  => $goodsBaseInfo,
            'extra' => $goodsExtra,
        );

        return $goodsList[$productBn];
    }

    // --------------------------------------------------------------------

    /**
     * 获取货品信息
     *
     * @param   $productBn    string     货品编码
     * @return  array
     */
    public function getGoodsBaseInfo($productBn)
    {
        static $goodsList;

        if (isset($goodsList[$productBn]))
        {
            return $goodsList[$productBn];
        }

        $productInfo = app::get('b2c')->model('products')->GetProductAllInfoByBn($productBn);
        if (empty($productInfo))
        {
            return array();
        }

        // 获取商品定价服务价格
        $prices = $this->_getProductPrice($productBn);

        if ( ! empty($prices))
        {
            $productInfo['price'] = $prices['price'];
            $productInfo['mktprice'] = $prices['mktprice'];
        }

        $goodsBaseInfo = app::get('b2c')->model('goods')->GetGoodsAllInfo($productInfo['goods_id']);

        if (empty($goodsBaseInfo))
        {
            return array();
        }

        // 获取货品的规格图片
        $productSpecImage = $this->_getProductSpecImage($productBn);

        // 规格图片替换首图
        if ( ! empty($productSpecImage))
        {
            $imageList = explode(',public', $productInfo['l_img_url']);
            $imageList[0] = $productSpecImage['url'];
            $productInfo['img_url'] = implode(',public', $imageList);

            $largeImageList = explode(',public', $productInfo['l_img_url']);
            $largeImageList[0] = $productSpecImage['l_url'];
            $productInfo['l_img_url'] = implode(',public', $largeImageList);

            $middleImageList = explode(',public', $productInfo['m_img_url']);
            $middleImageList[0] = $productSpecImage['m_url'];
            $productInfo['m_img_url'] = implode(',public', $middleImageList);

            $smallImageList = explode(',public', $productInfo['s_img_url']);
            $smallImageList[0] = $productSpecImage['s_url'];
            $productInfo['s_img_url'] = implode(',public', $smallImageList);
        }

        $goodsList[$productBn] = array(
            'product'   => $productInfo,
            'goods'     => $goodsBaseInfo,
        );

        return $goodsList[$productBn];
    }

    // --------------------------------------------------------------------

    /**
     * 获取货品价格信息
     *
     * @param   $productBn      array       货品编码
     * @return  array
     */
    public function getGoodsPrice($productBn)
    {
        return $this->_getProductPrice($productBn);
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品额外信息
     *
     * @param   $productBn    string     商品编码
     * @return  array
     */
    public function getGoodsExtraInfo($productBn)
    {
        static $productList;

        if (isset($productList[$productBn]))
        {
            return $productList[$productBn];
        }

        $sql = "SELECT * FROM unicom_goods WHERE product_bn = '{$productBn}'";
        $result = $this->_db->selectrow($sql);

        if (empty($result))
        {
            return array();
        }

        $productList[$productBn] = $result;

        return $result ? $result : array();
    }


    // --------------------------------------------------------------------

    /**
     * 获取商品额外信息
     *
     * @param   $productBn    mixed     商品编码
     * @return  array
     */
    public function getGoodsInfo($productBn)
    {
        $return = array();
        if (empty($productBn))
        {
            return $return;
        }
        if (is_array($productBn))
        {
            $productBnStr = implode("','", $productBn);
            $sql = "SELECT * FROM unicom_goods WHERE product_bn IN ('{$productBnStr}')";
        }
        else
        {
            $sql = "SELECT * FROM unicom_goods WHERE product_bn ='{$productBn}'";
        }

        $result = $this->_db->select($sql);

        if (empty($result))
        {
            return $return;
        }

        foreach ($result as $v)
        {
            $return[$v['product_bn']] = $v;
        }

        return is_array($productBn) ? $return : current($productBn);
    }

    // --------------------------------------------------------------------

    /**
     * 通过平台SKU获取商品额外信息
     *
     * @param   $PSku    string     商品编码
     * @return  array
     */
    public function getGoodsExtraInfoByPSku($PSku)
    {
        $sql = "SELECT * FROM unicom_goods WHERE p_sku = '{$PSku}'";

        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品规格信息
     *
     * @param   $productBn    string     货品编码
     * @return  array
     */
    public function getGoodsSpec($productBn)
    {
        $return = array();

        // 商品额外信息
        $goodsExtraInfo = $this->getGoodsExtraInfo($productBn);

        $goodsList = app::get('b2c')->model('goods')->getGoodsIdByBn($goodsExtraInfo['goods_bn'], 'forceequal');

        $productList = app::get('b2c')->model('products')->GetProductListByGoodsId($goodsList[0]);

        // 获取商品下全部货品
        $unicomProductList = $this->getProductByGoodsBn($goodsExtraInfo['goods_bn']);

        if (empty($productList) OR empty($unicomProductList))
        {
            return $return;
        }

        $bns = array();
        foreach ($unicomProductList as $product)
        {
            $bns[] = $product['product_bn'];
        }

        foreach ($productList as $product)
        {
            if ( ! in_array($product['bn'], $bns))
            {
                continue;
            }
            $spec = array();
            $specList = array_filter(explode('、', $product['spec_info']));

            if ( ! empty($specList))
            {
                foreach ($specList as $v)
                {
                    $specInfo = explode('：', $v);
                    $spec[$specInfo[0]] = $specInfo[1];
                }
            }
            $return[] = array(
                'bn' => $product['bn'],
                'spec' => $spec,
            );
        }

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 更新商品同步状态
     *
     * @param   $productBn      string      商品编码
     * @param   $status         string      同步状态
     * @param   $msg            string      同步描述
     * @return  boolean
     */
    public function updateSyncStatus($productBn, $status, $msg = '')
    {
        if (empty($productBn))
        {
            return false;
        }

        $now = time();
        $sql = "UPDATE unicom_goods SET sync_status = '{$status}', last_sync_msg = '{$msg}', sync_last_time = $now  WHERE product_bn = '{$productBn}'";
        $result = $this->_db->exec($sql);

        return $result !== false ? true : false;
    }

    // --------------------------------------------------------------------

    /**
     * 更新商品平台
     *
     * @param   $productBn      string      商品编码
     * @param   $data           array       更新商品的信息
     * @return  boolean
     */
    public function updateGoodsData($productBn, $data = array())
    {
        if (empty($productBn) OR empty($data))
        {
            return false;
        }

        $set = '';
        foreach ($data as $filed => $val)
        {
            $set .= $filed. " = '{$val}',";
        }
        $now = time();

        $sql = "UPDATE unicom_goods SET $set sync_last_time = $now  WHERE product_bn = '{$productBn}'";

        $result = $this->_db->exec($sql);

        return $result !== false ? true : false;
    }

    // --------------------------------------------------------------------

    /**
     * 获取货品价格
     *
     * @param   $productBn      string      货品编码
     * @return  array
     */
    public function _getProductPrice($productBn)
    {
        $return = array();

        if (empty($productBn))
        {
            return $return;
        }

        $data = array(
            'products_bns' => is_array($productBn) ? $productBn : array($productBn),
            'stages' => array('company' => UNICOM_COMPANY_ID),
        );

        $postData = array(
            'data' => base64_encode(json_encode($data)),
        );
        $postData['token'] = kernel::single('b2c_safe_apitoken')->generate_token($postData, OPENAPI_TOKEN_SIGN);

        $url = ECSTORE_DOMAIN . '/openapi/productsV2/GetProductPrice';
        $curl = new  \Neigou\Curl();
        $result = $curl->Post($url, $postData);
        $result = json_decode($result, true);

        if (empty($result) OR $result['code'] != 10000 OR empty($result['data']))
        {
            return $return;
        }


        if ( ! is_array($productBn))
        {
            $return = array(
                'price' => $result['data'][$productBn]['price'],
                'mktprice' => $result['data'][$productBn]['mktprice'],
            );
        }
        else
        {
            foreach ($result['data'] as $bn => $v)
            {
                $return[$bn] = array(
                    'price' => $v['price'],
                    'mktprice' => $v['mktprice'],
                );
            }
        }

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品目录信息
     *
     * @param   $categoryCode    string     商品编码
     * @return  array
     */
    public function getGoodsCategoryInfo($categoryCode)
    {
        $sql = "SELECT * FROM unicom_goods_category WHERE code = '{$categoryCode}'";

        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品开放范围
     *
     * @param   $status    mixed     状态
     * @return  array
     */
    public function getGoodsScope($status = 1)
    {
        $where = '';
        if ($status !== null)
        {
            $where .= 'WHERE status = '. $status;
        }

        $sql = "SELECT * FROM unicom_goods_scope $where";

        $result = $this->_db->select($sql);


        return $result ? $result : array();
    }

    // --------------------------------------------------------------------

    /**
     * 获取商品下的货品列表
     *
     * @param   $goodsBn    mixed     商品编码
     * @return  array
     */
    public function getProductByGoodsBn($goodsBn)
    {
        $sql = "SELECT * FROM unicom_goods WHERE goods_bn = '{$goodsBn}'";

        $result = $this->_db->select($sql);

        return  $result ? $result : array();
    }
    // --------------------------------------------------------------------

    /**
     * 获取待同步商品基本数据
     *
     * @param   $limit      int         限制数量
     * @param   $mantissa   string      尾数
     * @return  array
     */
    public function getUnPushGoodsBaseData($limit = 100, $mantissa = '')
    {
        $where = '';
        if ($mantissa)
        {
            $mantissa = implode(',', explode(',', $mantissa));
            $where .= " WHERE id MOD 10 IN (". $mantissa. ")";
        }

        $sql = "SELECT * FROM unicom_goods {$where} ORDER BY sync_last_time ASC LIMIT $limit";

        $result = $this->_db->select($sql);

        return  $result ? $result : array();
    }

    // --------------------------------------------------------------------

    /**
     * 获取待同步价格上架数据
     *
     * @param   $limit      int         限制数量
     * @param   $mantissa   string      尾数
     * @return  array
     */
    public function getUnPushGoodsCoreData($limit = 100, $mantissa = '')
    {
        $where = '';
        if ($mantissa)
        {
            $mantissa = implode(',', explode(',', $mantissa));
            $where .= " WHERE id MOD 10 IN (". $mantissa. ")";
        }

        $sql = "SELECT * FROM unicom_goods_sync {$where} ORDER BY update_time ASC LIMIT $limit";

        $result = $this->_db->select($sql);

        return  $result ? $result : array();
    }
    // --------------------------------------------------------------------

    /**
     * 获取商品同步信息
     *
     * @param   $productBn      mixed       商品编码
     * @return  mixed
     */
    public function getGoodsSync($productBn)
    {
        if (empty($productBn))
        {
            return false;
        }

        $sql = "SELECT * FROM unicom_goods_sync WHERE product_bn = '{$productBn}' ";

        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    // --------------------------------------------------------------------

    /**
     * 新增商品同步信息
     *
     * @param   $data           array       数据
     * @return  mixed
     */
    public function addGoodsSync($data)
    {
        if (empty($data))
        {
            return false;
        }

        $fields = '`'. (implode('`,`', array_keys($data))). '`';
        $values = "'". (implode("','", $data)). "'";

        $sql  = "INSERT INTO unicom_goods_sync({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);

        return $result ? $this->_db->lastinsertid() : false;
    }

    // --------------------------------------------------------------------

    /**
     * 新增商品同步日志
     *
     * @param   $productBn      string      货品编码
     * @param   $type           string      类型
     * @param   $goodsData      array       商品数据
     * @param   $requestCount   int         请求次数
     * @param   $reason         string      原因
     * @return  mixed
     */
    public function addGoodsSyncLog($productBn, $type, $goodsData, $requestCount, $reason = '')
    {

        $data = array(
            'product_bn'    => $productBn,
            'type'          => $type,
            'data'          => serialize($goodsData),
            'reason'        => $reason,
            'request_count' => $requestCount,
            'create_time'   => time(),
        );
        $fields = '`'. (implode('`,`', array_keys($data))). '`';
        $values = "'". (implode("','", $data)). "'";

        $sql  = "INSERT INTO unicom_goods_sync_log({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);

        return $result ? $this->_db->lastinsertid() : false;
    }

    // --------------------------------------------------------------------

    /**
     * 保存商品同步信息
     *
     * @param   $productBn      mixed       商品编码
     * @param   $data           array       数据
     * @return  mixed
     */
    public function saveGoodsSync($productBn, $data)
    {
        if (empty($productBn) OR empty($data))
        {
            return false;
        }

        $sql = "UPDATE unicom_goods_sync SET ";

        foreach ($data as $field => $value)
        {
            $sql .= "`{$field}` = ". "'{$value}',";
        }

        $sql = substr($sql, 0, -1);

        $sql .= " WHERE product_bn = '{$productBn}'";

        $result = $this->_db->exec($sql);

        return $result !== false ? true : false;
    }

    // --------------------------------------------------------------------

    /**
     * 获取货品规格图片
     *
     * @param   $productBn      string      货品编码
     * @return  array
     */
    private function _getProductSpecImage($productBn)
    {
        $return = array();

        if (empty($productBn))
        {
            return $return;
        }

        // 获取货品图片
        $productImage = app::get('b2c')->model('products')->GetGoodsImageByProductBns(array($productBn));

        if (empty($productImage))
        {
            return $return;
        }
        $productInfo = current($productImage);

        //货品
        $productSpecDesc = unserialize($productInfo['p_spec_desc']);
        $specValueId = current($productSpecDesc['spec_value_id']);

        //商品
        $goodsSpecDesc = unserialize($productInfo['g_spec_desc']);
        $goodsSpecDesc = current($goodsSpecDesc);
        $specImageId = '';
        foreach ($goodsSpecDesc as $g_desc)
        {
            if ($g_desc['spec_value_id'] == $specValueId)
            {
                $specImageId = current(explode(',', $g_desc['spec_goods_images']));
                break;
            }
        }

        if (empty($specImageId))
        {
            return $return;
        }
        $result = app::get('image')->model('image')->getList('*', array('image_id' => $specImageId));

        if( ! empty($result))
        {
            $return = current($result);
        }

        return $return;
    }

}
