<?php
class shangou_ctl_wap_time{

   public function get_ltstr(){
            $endtime = $_POST['endtime'];
            header('Content-Type:text/html; charset=utf-8');
            echo implode(',', $this->_get_ltstr(explode(',', $endtime)));
    }

    private function _get_ltstr($arr_endtime){
        foreach ($arr_endtime as $endtime) {
            
             $week = array(
                    '1'=>'一',
                    '2'=>'二',
                    '3'=>'三',
                    '4'=>'四',
                    '5'=>'五',
                    '6'=>'六',
                    '7'=>'七',
            );
            $lefttime = '还有';
            $lt = intval(intval($endtime) - time());
            
            $day = intval($lt/(24*60*60));
            
            if ($day > 0) {
                $lefttime .= $day."天结束";
            }else{
                $hour =  intval($lt/(60*60));
                if ($hour >= 3) {
                    $lefttime .= $hour."小时结束";
                }else{
                    $lefttime = "活动于星期".$week[date('N',$endtime)]." ".date('h',$endtime).' '.date('A',$endtime)."结束";
                }
            }

            $_return[] = $lefttime;

        }

        return $_return;

    }

}