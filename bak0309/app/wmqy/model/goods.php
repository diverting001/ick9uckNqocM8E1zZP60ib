<?php

/**
 * 联通商品分类
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class wmqy_mdl_goods extends base_db_external_model
{
    // 数据库配置
    private $db_conf = array(
        'MASTER' => array(
            'HOST' => DB_HOST,
            'NAME' => DB_NAME,
            'USER' => DB_USER,
            'PASSWORD' => DB_PASSWORD
        )
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


    /**
     * 获取待同步商品基本数据
     *
     * @param   $mall_id      int         商城id
     * @param   $max_id      int         最大id
     * @return  array
     */
    public function initReadySku($mall_id, $max_id)
    {
        while (1) {
            $sql_mall_goods = "select id,goods_id from mall_module_mall_goods where mall_id=$mall_id and id>$max_id order by id asc limit 100";
            $list_mall_goods = $this->_db->select($sql_mall_goods);
            if (empty($list_mall_goods)) {
                echo 'over', PHP_EOL;
                return true;
            }
            $goods_ids = '';
            foreach ($list_mall_goods as $item) {
                $goods_ids .= $item['goods_id'] . ',';
                $max_id = $item['id'];
            }
            $goods_ids = trim($goods_ids, ',');
            $sql = "SELECT bn from sdb_b2c_products WHERE goods_id in($goods_ids)";
            $list = $this->_db->select($sql);
            foreach ($list as $item) {
                $sku_info = $this->getSku($item['bn']);
                if (empty($sku_info) && substr($item['bn'], 0, 5) !== 'WMQY-') {
                    $this->addSku(array(
                        'bn' => $item['bn'],
                        'mall_id' => $mall_id,
                        'status' => 'ready',
                        'created_at' => time(),
                    ));
                    echo 'mall:' . $mall_id . ',新增:' . $item['bn'], PHP_EOL;
                }
            }
            $config_model = app::get('b2c')->model('config');
            $config_model->updateConfig('wmqy_module_mall_goods_max_id_' . $mall_id, $max_id, 'neigou');
        }
    }

    // --------------------------------------------------------------------

    public function getSkusByStatus($status, $limit = 100)
    {
        $sql = "SELECT * FROM wmqy_skus WHERE status='$status' ORDER BY created_at ASC LIMIT $limit ";
        $result = $this->_db->select($sql);
        return $result ? $result : array();
    }


    public function updateSku($bn, $data)
    {
        $set = '';
        foreach ($data as $key => $val) {
            if ($key === 'fail_count' && !is_numeric($val)) {
                $set .= "$key=$val,";
                continue;
            }
            $set .= "$key='$val',";
        }
        $set = trim($set, ',');
        $sql = "UPDATE wmqy_skus SET $set WHERE bn = '$bn' ";
        $result = $this->_db->exec($sql);
        return $result ? $result : array();
    }

    public function addSku($data)
    {
        $fields = '';
        $vals = '';
        foreach ($data as $key => $val) {
            $fields .= "`$key`,";
            $vals .= "'$val',";
        }
        $fields = trim($fields, ',');
        $vals = trim($vals, ',');
        $sql = "INSERT INTO wmqy_skus ($fields) VALUES ($vals)";
        $result = $this->_db->exec($sql);
        return $result ? $result : array();
    }

    public function getSku($bn)
    {
        $sql = "SELECT * FROM wmqy_skus WHERE bn='$bn'";
        $result = $this->_db->selectrow($sql);
        return $result ? $result : array();
    }

    public function getByBns($bn_arr)
    {
        $where = "bn in('" . implode('\',\'', $bn_arr) . "')";
        $sql = "SELECT * FROM wmqy_skus WHERE $where";
        $list = $this->_db->select($sql);
        $return = array();
        foreach ($list as $item) {
            $return[$item['bn']] = $item;
        }
        return $return;
    }
}

