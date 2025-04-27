<?php


class storageAdapter
{
    public $service;

    public function __construct($app)
    {
        $this->service = $app;

    }

    public function uploadFile($bucket,$cloud_file,$local_file)
    {
        return $this->service->uploadFile($bucket,$cloud_file,$local_file);
    }

    public function getFilterStr($h,$w,$watermark_str){
        return $this->service->getFilterStr($h,$w,$watermark_str);
    }

    public function getWaterRemarkStr($size,$text){
        return $this->service->getWaterRemarkStr($size,$text);
    }

}