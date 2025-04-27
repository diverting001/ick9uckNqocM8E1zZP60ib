<?php

class wmqy_mdl_companymapping extends wmqy_mdl_base
{
    private $_table = 'wmqy_company_mapping';

    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app, $this->_table);
    }
}
