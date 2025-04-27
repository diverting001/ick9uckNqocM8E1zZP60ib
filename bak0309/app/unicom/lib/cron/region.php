<?php
/**
 * 联通地区 crontab
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_cron_region
{
    /**
     * 拉取地址列表
     *
     * @return string
     */
    public function pullRegion()
    {
        $errMsg = '';
        $request = kernel::single('unicom_request');
        // 获取所有一级地址
        $result = $request->request(array('method' => 'allProvincesAddress'), $errMsg);

        if ($result === false)
        {
            return $errMsg;
        }

        if ($result['success'] != 'true')
        {
            return  ! empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误';
        }

        // 保存所有一级地址
        if (empty($result['result']) OR  ! is_array($result['result']))
        {
            return '无一级地址数据';
        }

        $errMsgList = array();
        foreach ($result['result'] as $region)
        {
            // 保存一级地址
            if ( ! app::get('unicom')->model('regions')->saveRegionData($region['id'], $region['name'], $region['pid'], $region['level']))
            {
                $errMsgList[] = 'save first level region failed '. $region['id'];
                continue;
            }

            // 获取二级地址
            $cityData = $this->_pullChildRegion($region['id']);

            if ( ! empty($cityData))
            {
                foreach ($cityData as $city)
                {
                    // 保存非一级地址
                    if ( ! app::get('unicom')->model('regions')->saveRegionData($city['id'], $city['name'], $city['pid'], $city['level']))
                    {
                        $errMsgList[] = 'save second level region failed '. $city['id'];
                    }
                }

                foreach ($cityData as $city)
                {
                    $countyData = $this->_pullChildRegion($city['id']);

                    if ( ! empty($countyData))
                    {
                        foreach ($countyData as $county)
                        {
                            // 保存非一级地址
                            if ( ! app::get('unicom')->model('regions')->saveRegionData($county['id'], $county['name'], $county['pid'], $county['level']))
                            {
                                $errMsgList[] = 'save third level region failed '. $county['id'];
                            }

                            $townData = $this->_pullChildRegion($county['id']);

                            if ( ! empty($townData))
                            {
                                foreach ($townData as $town)
                                {
                                    // 保存非一级地址
                                    if ( ! app::get('unicom')->model('regions')->saveRegionData($town['id'], $town['name'], $town['pid'], $town['level']))
                                    {
                                        $errMsgList[] = 'save fourth level region failed ' . $town['id'];
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }

        return implode("\n", $errMsgList);
    }

    // --------------------------------------------------------------------

    /**
     * 拉取地址信息
     *
     * @param   $pid        int     上级ID
     * @return  mixed
     */
    private function _pullChildRegion($pid)
    {
        $return = array();

        if ($pid <= 0)
        {
            return $return;
        }

        $errMsg = '';

        $cityData = kernel::single('unicom_request')->request(array('method' => 'citysByProvinceId', 'data' => array('id' => $pid)), $errMsg);

        if ($cityData['success'] == 'true' && ! empty($cityData['result']))
        {
            $return = $cityData['result'];
        }

        return $return;
    }

}
