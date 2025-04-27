<?php
/**
 * 联通商品 crontab
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_region
{
    /**
     * 获取地区映射
     *
     * @param   $province   string      省
     * @param   $city       mixed       市
     * @param   $county     mixed       区县
     * @param   $town       mixed       乡镇
     * @return  array
     */
    public function getRegionMappingByName($province, $city, $county, $town = '')
    {
        $return = array();

        $regionsModel = app::get('unicom')->model('regions');

        $provinceInfo = $regionsModel->getRegionInfoByName($province);

        if (empty($provinceInfo))
        {
            return $return;
        }

        $regionId = $provinceInfo['mapping_region_id'];

        $cityInfo = $regionsModel->getRegionInfoByName($city, $provinceInfo['region_id']);

        if ( ! empty($cityInfo))
        {
            $regionId = $cityInfo['mapping_region_id'];

            $countyInfo =  $regionsModel->getRegionInfoByName($county, $cityInfo['region_id']);

            if ( ! empty($countyInfo))
            {
                $regionId = $countyInfo['mapping_region_id'];

                $town && $townInfo = $regionsModel->getRegionInfoByName($town, $countyInfo['region_id']);

                if ( ! empty($townInfo) && ! empty($townInfo['mapping_region_id']))
                {
                    $regionId = $townInfo['mapping_region_id'];
                }
            }
        }

        $regionInfo = app::get('b2c')->model('region')->getRegionById($regionId);

        if (empty($regionInfo))
        {
            return $return;
        }

        $regionIdList = array_filter(explode(',', $regionInfo['region_path']));
        $regionList = app::get('b2c')->model('region')->getRegionById($regionIdList);

        if (empty($regionList))
        {
            return $return;
        }

        foreach ($regionList as $region)
        {
            if ($region['region_grade'] == 1)
            {
                $return['province'] = $region['local_name'];
                $return['provinceRegionId'] = $region['region_id'];
            }
            elseif ($region['region_grade'] == 2)
            {
                $return['city'] = $region['local_name'];
                $return['cityRegionId'] = $region['region_id'];
            }
            elseif ($region['region_grade'] == 3)
            {
                $return['country'] = $region['local_name'];
                $return['countryRegionId'] = $region['region_id'];
            }
            elseif ($region['region_grade'] == 4)
            {
                $return['town'] = $region['local_name'];
                $return['townRegionId'] = $region['region_id'];
            }
        }

        return $return;
    }

    // --------------------------------------------------------------------

    /**
     * 获取地区映射
     *
     * @param   $provinceId   int      省ID
     * @param   $cityId       int      市ID
     * @param   $countyId     int      区县ID
     * @param   $townId       int      乡镇ID
     * @return  array
     */
    public function getRegionMapping($provinceId, $cityId, $countyId, $townId = 0)
    {
        $return = array();

        $regionsModel = app::get('unicom')->model('regions');

        $provinceInfo = $regionsModel->getRegionInfo($provinceId);

        if (empty($provinceInfo))
        {
            return $return;
        }

        $regionId = $provinceInfo['mapping_region_id'];

        $cityInfo = $regionsModel->getRegionInfo($cityId, $provinceInfo['region_id']);

        if ( ! empty($cityInfo))
        {
            $regionId = $cityInfo['mapping_region_id'];

            $countyInfo =  $regionsModel->getRegionInfo($countyId, $cityInfo['region_id']);

            if ( ! empty($countyInfo) && !empty($countyInfo['mapping_region_id']))
            {
                $regionId = $countyInfo['mapping_region_id'];

                $townId && ($townInfo = $regionsModel->getRegionInfo($townId, $countyInfo['region_id']));

                if ( ! empty($townInfo) && !empty($townInfo['mapping_region_id']))
                {
                    $regionId = $townInfo['mapping_region_id'];
                }
            }
        }

        $regionInfo = app::get('b2c')->model('region')->getRegionById($regionId);

        if (empty($regionInfo))
        {
            return $return;
        }

        $regionIdList = array_filter(explode(',', $regionInfo['region_path']));
        $regionList = app::get('b2c')->model('region')->getRegionById($regionIdList);

        if (empty($regionList))
        {
            return $return;
        }

        foreach ($regionList as $region)
        {
            if ($region['region_grade'] == 1)
            {
                $return['province'] = $region['local_name'];
                $return['provinceRegionId'] = $region['region_id'];
            }
            elseif ($region['region_grade'] == 2)
            {
                $return['city'] = $region['local_name'];
                $return['cityRegionId'] = $region['region_id'];
            }
            elseif ($region['region_grade'] == 3)
            {
                $return['country'] = $region['local_name'];
                $return['countryRegionId'] = $region['region_id'];
            }
            elseif ($region['region_grade'] == 4)
            {
                $return['town'] = $region['local_name'];
                $return['townRegionId'] = $region['region_id'];
            }
        }

        return $return;
    }

}
