<?php
error_reporting(0);
set_time_limit(0);
date_default_timezone_set('PRC');

require(dirname(__FILE__) . '/config.php');
{
    $_cas_member = app::get('b2c') -> model('cas_members');;
    $_club_company = app::get('b2c') -> model('company');
    $guid = 0;
}

$start = $argv[1];
$end = $argv[2];
$window = "Sync guid >= {$start} && < {$end} ";

if(!is_numeric($start) || !is_numeric($end)){
    echo "xxxx\n\n";
    exit;
}

echo $window;
echo "Date:" , date('Y-m-d H:i:s',time());
echo  "\n\n";

$fail_count = 0;
$result_while = true;
while($result_while){
    echo "+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++";
    echo "\n";
    $sql = "select * from club_agh_member_tmp where  guid > {$guid} and guid >= {$start} and guid < {$end} group by guid order by guid asc";
    $result = $_club_company -> _sync_db -> selectrow($sql);


    if( $result && $result['guid']){
        $guid = $result['guid'];
        $cas_member = $_cas_member -> getRow('*',array('guid' => $guid));
        if($cas_member){
            echo date('Y-m-d H:i:s',time()) , " HAS:" , $cas_member['guid'];
            echo "\n";
            continue;
        }
        $post_data = array();
        $post_data['guid'] = $result['guid'];
        $post_data['gcorp_id'] = $result['gcorp_id'];
        $post_data['rights_code'] = "corp.org";
        $post_data['class_obj'] = 'Rights';
        $post_data['method'] = 'getMemberInquireRights';
        $token = kernel::single('b2c_safe_apitoken')->generate_token($post_data, OPENAPI_TOKEN_SIGN);
        $post_data['token'] = $token;

        $curl   = new \Neigou\Curl();
        $ret = $curl->Post(CLUB_DOMAIN . '/Qiye/OpenApi/apirun',$post_data);
        $ret = json_decode($ret,true);
        $ret_data = $ret;
        $ret_data['post_data'] = $post_data;


        $log_data = array();
        $log_data['log_key'] = $guid;
        $log_data['log_data'] = serialize($ret_data);
        $log_data['type'] = 'guid';
        $log_data['last_time'] = time();
        echo serialize($log_data);
        echo "\n";

        if($ret['result'] == 'ok'){
            echo date('Y-m-d H:i:s',time()) , ' SUCCESS:' , $guid;
            echo "\n";
        }else{
            $fail_count++;
            echo date('Y-m-d H:i:s',time()) , ' ERROR:' , $guid, "fail_count", $fail_count;
            echo "\n";
            if ($fail_count>20) {
                $result_while = false;
            }
        }
    }else{
        echo date('Y-m-d H:i:s',time()) , 'NO MORE';
        echo "\n";
        $result_while = false;
    }
}