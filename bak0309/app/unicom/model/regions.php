<?php
/**
 * 联通地区
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_mdl_regions extends base_db_external_model
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
     * 保存地区信息
     *
     * @param   $regionId           int     地区ID
     * @param   $name               string  名称
     * @param   $pid                int     上级ID
     * @param   $level              int     等级
     * @param   $mappingRegionId    mixed   映射地区ID
     * @param   $mappingName        mixed   映射地区名称
     * @return  boolean
     */
    public function saveRegionData($regionId, $name, $pid, $level, $mappingRegionId = null, $mappingName = null)
    {
        $regionInfo = $this->getRegionInfo($regionId);

        if (empty($regionInfo))
        {
            $result = $this->addRegion($regionId, $name, $pid, $level);
        }
        else
        {
            $updateData = array(
                'name'      => $name,
                'pid'       => $pid,
                'level'     => $level,
            );

            if ($mappingRegionId)
            {
                $updateData['mapping_region_id'] = $mappingRegionId;
            }

            if ($mappingName)
            {
                $updateData['mapping_name'] = $mappingName;
            }

            foreach ($updateData as $key => $val)
            {
                if ($val == $regionInfo[$key])
                {
                    unset($updateData[$key]);
                }
            }

            if (empty($updateData))
            {
                return true;
            }

            $updateData['update_time'] = time();

            $result = $this->updateRegion($regionId, $updateData);
        }

        return $result ? true : false;
    }

    // --------------------------------------------------------------------

    /**
     * 获取地区信息
     *
     * @param   $regionId           int     地区ID
     * @return  array
     */
    public function getRegionInfo($regionId)
    {
        if ($regionId <= 0)
        {
            return array();
        }

        $sql = "SELECT * FROM unicom_regions WHERE region_id = '{$regionId}'";
        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

    // --------------------------------------------------------------------

    /**
     * 获取地区信息
     *
     * @param   $regionId       int     地区ID
     * @param   $data           array   数据
     * @return  boolean
     */
    public function updateRegion($regionId, $data)
    {
        if (empty($data))
        {
            return true;
        }

        $updateData = array();
        foreach ($data as $field => $value)
        {
            if ($field != 'id' && $field != 'region_id')
            {
                $updateData[] = $field. '='. "'". $value. "'";
            }
        }

        $sql = "UPDATE unicom_regions SET ". implode(',', $updateData). " WHERE regin_id = $regionId";

        $result = $this->_db->exec($sql);

        return $result ? true : false;
    }

    // --------------------------------------------------------------------

    /**
     * 添加地区信息
     *
     * @param   $regionId           int     地区ID
     * @param   $name               string  名称
     * @param   $pid                int     上级ID
     * @param   $level              int     等级
     * @param   $mappingRegionId    mixed   映射地区ID
     * @param   $mappingName        mixed   映射地区名称
     * @return  mixed
     */
    public function addRegion($regionId, $name, $pid, $level, $mappingRegionId = null, $mappingName = null)
    {
        $insertData = array(
            'region_id'     => $regionId,
            'name'          => $name,
            'pid'           => $pid,
            'level'         => $level,
            'update_time'   => time(),
        );

        $mappingRegionId === null OR $insertData['mapping_region_id'] = $mappingRegionId;
        $mappingName === null OR $insertData['mapping_name'] = $mappingName;

        $fields = '`'. (implode('`,`', array_keys($insertData))). '`';
        $values = "'". (implode("','", $insertData)). "'";
        $sql  = "INSERT INTO unicom_regions({$fields}) VALUES ({$values})";

        $result = $this->_db->exec($sql);

        return $result ? $this->_db->lastinsertid() : false;
    }

    // --------------------------------------------------------------------

    /**
     * 获取地区信息
     *
     * @param   $name               string  名称
     * @param   $pid                int     上级ID
     * @return  mixed
     */
    public function getRegionInfoByName($name, $pid = 0)
    {
        if (empty($name))
        {
            return array();
        }

        $sql = "SELECT * FROM unicom_regions WHERE name = '{$name}' AND pid = '{$pid}'";

        $result = $this->_db->selectrow($sql);

        return $result ? $result : array();
    }

}
