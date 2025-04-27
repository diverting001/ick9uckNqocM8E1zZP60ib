<?php

use Neigou\Logger;

class Obs
{
    public function uploadFile($bucket, $cloud_file, $local_file)
    {
        $file_info = pathinfo($cloud_file);
        $file_pars = array(
            'name' => $file_info['basename'],
            'path' => str_replace('public/', '', $file_info['dirname']),
            'platform' => 'obs',
            'file' => '@' . $local_file,
        );
        $ret = \Neigou\ApiClient::doServiceCall('tools', 'Image/Upload', 'v3', null, null, array('debug' => FALSE), $file_pars);
        return true;
    }

    public function getFilterStr($h, $w, $watermark_str)
    {
        return '?x-image-process=image/resize,m_lfit,h_' . $h . ',w_' . $w . $watermark_str;
    }

    public function getWaterRemarkStr($size, $text)
    {
        return '/watermark,size_' . $size . ',text_' . $text;
    }
}