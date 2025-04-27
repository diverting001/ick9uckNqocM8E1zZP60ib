<?php

require_once ROOT_DIR . '/plugin/aliyun-oss-php-sdk-2.3.0.phar';
class Oss
{
    public $resize_str = '';
    public function uploadFile($bucket,$oss_file,$local_file)
    {
        $s = new \OSS\OssClient(OSS_ASSESS_KEY_ID, OSS_ACCESS_KEY_SECRET, OSS_ENDPOINT);
        try{
            $s->uploadFile($bucket,$oss_file,$local_file);
            return true;
        } catch (\OSS\Core\OssException $e){
            \Neigou\Logger::Debug('ecstore.oss.err',array('image_id' => $local_file, 'allsize' => $e->getMessage()));
            return false;
        }

    }

    public function getFilterStr($h,$w,$watermark_str){
        return '?x-oss-process=image/resize,m_lfit,h_' . $h . ',w_' . $w . $watermark_str;
    }

    public function getWaterRemarkStr($size,$text){
        return '/watermark,size_' . $size . ',t_25,g_center,text_' . $text;
    }

}