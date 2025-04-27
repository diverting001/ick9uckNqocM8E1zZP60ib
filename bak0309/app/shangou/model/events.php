<?php
class shangou_mdl_events extends dbeav_model {

	var $defaultOrder = array('p_order',' ASC ','  ,evt_id' ,' DESC ');
	
    public function __construct( &$app ) {
        $this->app = $app;
        parent::__construct( $app );
    }


}
