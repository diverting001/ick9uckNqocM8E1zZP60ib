<?php


interface storage
{

    public function uploadFile($bucket,$cloud_file,$local_file);

}