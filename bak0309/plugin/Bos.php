<?php

require_once ROOT_DIR . '/plugin/BaiduBce.phar';
class Bos
{
    public function uploadFile($bucket,$cloud_file,$local_file)
    {
        $config = array(
            'credentials' => array(
                'ak' => BOS_AK,
                'sk' => BOS_SK,
            ),
            'endpoint' => BOS_ENDPOINT,
        );
        $bos = new \BaiduBce\Services\Bos\BosClient($config);
        try{
            $res = $bos->putObjectFromFile($bucket,$cloud_file,$local_file);
            return true;
        } catch (Exception $e){
            \Neigou\Logger::Debug('ecstore.bos.err',array('local_file'=>$local_file,'error'=>$e->getMessage()));
            return false;
        }

//        print_r($res);

    }

    public function getFilterStr($h,$w,$watermark_str){
        return '@h_' . $h . ',w_' . $w . $watermark_str;
    }

    public function getWaterRemarkStr($size,$text){
        return '|wm_2,t_'.$text.',sz_'.$size;
    }

}