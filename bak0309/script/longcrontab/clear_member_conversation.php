<?php
require(dirname(__FILE__) . '/config.php');

$_session = kernel::single('base_session');

$_conversation = app::get('b2c') -> model('member_conversation');
$result = true;
$i = 0;
while ($result) {
    $result = $_conversation -> getList('*',array(),$i * 100,100,array('id','asc'));
    $i++;
    if($result){
        foreach ($result as $row) {
            $id = $row['id'];
            $time = $_session -> get_ttl($row['token']);
            if($time <= 0){
                $filter = array('id' => $id);
                $_conversation -> delete($filter);
            }
        }
    }else{
        $result = false;
    }
}