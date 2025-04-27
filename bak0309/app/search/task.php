<?php

class search_task
{

    public function pre_install()
    {
        logger::info('Initial search');
        kernel::single('base_initial', 'search')->init();
    }//End Function

}//End Class
