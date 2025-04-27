<?php
/**
 * 联通商品分类
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_mdl_goods_category extends base_db_external_model
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
     * @param   $code       string      分类编码
     * @param   $name       string      名称
     * @param   $pcode      string      上级编码
     * @param   $level      int         等级
     * @param   $orderSort  int         排序
     * @param   $isleaf     int         是否为叶子节点
     * @param   $path       string      分类路径
     * @param   $pathName   string      分类路径名称
     * @param   $attrs      array       属性
     * @return  boolean
     */
    public function saveGoodsCategoryData($code, $name, $pcode, $level, $orderSort, $isleaf, $path, $pathName, $attrs)
    {
        $categoryInfo = $this->getCategoryInfo($code);

        if (empty($categoryInfo))
        {
            $result = $this->addCategory($code, $name, $pcode, $level, $orderSort, $isleaf, $path, $pathName, $attrs);
        }
        else
        {
            $updateData = array(
                'code'      => $code,
                'name'      => $name,
                'pcode'     => $pcode,
                'level'     => $level,
                'orderSort' => $orderSort,
                'isleaf'    => $isleaf,
                'path'      => $path,
                'pathName'  => $pathName,
                'attrs'     => ! empty($attrs) && is_array($attrs) ? serialize($attrs) : '',
            );

            foreach ($updateData as $key => $val)
            {
                if ($val == $categoryInfo[$key])
                {
                    unset($updateData[$key]);
                }
            }

            if (empty($updateData))
            {
                return true;
            }

            $updateData['update_time'] = time();

            $result = $this->updateCategory($code, $updateData);
        }

        return $result ? true : false;
    }

    // --------------------------------------------------------------------

    /**
     * 获取分类信息
     *
     * @param   $code       string      编码
     * @return  array
     */
    public function getCategoryInfo($code)
    {
        if (empty($code))
        {
            return array();
        }

        $sql = "SELECT * FROM unicom_goods_category WHERE code = '{$code}'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    // --------------------------------------------------------------------

    /**
     * 更新分类信息
     *
     * @param   $code           string      编码
     * @param   $data           array       数据
     * @return  boolean
     */
    public function updateCategory($code, $data)
    {
        if (empty($code))
        {
            return true;
        }

        $updateData = array();
        foreach ($data as $field => $value)
        {
            if ($field != 'id' && $field != 'code')
            {
                $updateData[] = $field. '='. "'". $value. "'";
            }
        }

        $sql = "UPDATE unicom_goods_category SET ". implode(',', $updateData). " WHERE code = $code";
        $result = $this->_db->exec($sql);

        return $result ? true : false;
    }

    // --------------------------------------------------------------------

    /**
     * 保存商品分类信息
     *
     * @param   $code       string      分类编码
     * @param   $name       string      名称
     * @param   $pcode      string      上级编码
     * @param   $level      int         等级
     * @param   $orderSort  int         排序
     * @param   $isleaf     int         是否为叶子节点
     * @param   $path       string      分类路径
     * @param   $pathName   string      分类路径名称
     * @param   $attrs      array       属性
     * @return  boolean
     */
    public function addCategory($code, $name, $pcode, $level, $orderSort, $isleaf, $path, $pathName, $attrs)
    {
        $insertData = array(
            'code'      => $code,
            'name'      => $name,
            'pcode'     => $pcode,
            'level'     => $level,
            'orderSort' => $orderSort,
            'isleaf'    => $isleaf,
            'path'      => $path,
            'pathName'  => $pathName,
            'attrs'     => ! empty($attrs) && is_array($attrs) ? serialize($attrs) : '',
            'update_time'   => time(),
        );

        $fields = '`'. (implode('`,`', array_keys($insertData))). '`';
        $values = "'". (implode("','", $insertData)). "'";
        $sql  = "INSERT INTO unicom_goods_category({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);

        return $result ? $this->_db->lastinsertid() : false;
    }

}
