<meta content="text/html;charset=utf-8" http-equiv="Content-Type">
<?php
set_time_limit(0);

require(dirname(__FILE__) . '/config.php');

$_club_company = app::get('b2c') -> model('company');
$_member = kernel::single('b2c_member');

{
    $_log_model = app::get('b2c') -> model('cas_sync_log');
    $member_id = 0;
    $result = $_log_model -> getRow('*',array('type' => 'company'),array('log_key','desc'));
    if($result){
        $member_id = $result['log_key'];
    }
}

$condition = '>';
if(isset($argv[1]) && is_numeric($argv[1])){
    $member_id = $argv[1];
    $condition = '=';
}


$result = true;
while ($result) {
    $sql = "select * from club_company where company_type_id != 7 and member_id {$condition} {$member_id} order by member_id asc";
    $result = $_club_company -> _sync_db -> selectrow($sql);
    if($result){
        $member_id = $result['member_id'];

        $post_data = array();
        $post_data['company_id'] = $member_id;
        $post_data['company_name'] = $result['company_name'];
        $post_data['company_name_short'] = $result['company_name_short'];
        $post_data['company_logo'] = $result['company_logo'];
        $post_data['state'] = 1;
        $post_data['class_obj'] = 'Third';
        $post_data['method'] = 'casCreateCompany';
        $token = kernel::single('b2c_safe_apitoken')->generate_token($post_data, OPENAPI_TOKEN_SIGN);
        $post_data['token'] = $token;

        $curl   = new \Neigou\Curl();
        $ret = $curl->Post(CLUB_DOMAIN . '/OpenApi/apirun',$post_data);
        $ret = json_decode($ret,true);
        $ret['post_data'] = $post_data;

        $log_data = array();
        $log_data['log_key'] = $member_id;
        $log_data['log_data'] = serialize($ret);
        $log_data['type'] = 'company';
        $log_data['last_time'] = time();
        $_log_model -> insert($log_data);

        echo "\n";
        if($ret['Result'] == 'true'){
            echo 'SUCCESS:' , $member_id;
        }else{
            echo 'ERROR:' , $member_id;
            exit;
        }
    }else{
        $result = false;
    }

    if($condition == '='){
        break;
    }
}