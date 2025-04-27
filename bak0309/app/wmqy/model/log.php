<?php

class wmqy_mdl_log extends wmqy_mdl_base
{
    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app, 'wmqy_log');
    }
}
