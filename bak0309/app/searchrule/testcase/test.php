<?php
class test extends PHPUnit_Framework_TestCase
{

    function test2(){
        $i = 2;
        echo $i."\r\n";
        $this->test1(5);

    }

    function test1($i){
        if(!$i){
            return ;
        }
        echo $i."\r\n";
    }

}
?>
