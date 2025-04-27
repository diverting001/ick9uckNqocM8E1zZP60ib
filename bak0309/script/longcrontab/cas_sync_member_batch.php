<?php
error_reporting(0);
set_time_limit(0);
date_default_timezone_set('PRC');

require(dirname(__FILE__) . '/config.php');
{
    $_member = kernel::single('b2c_member');
    $_account = app::get('pam')->model('account');
    $_member_model = app::get('b2c') -> model('members');
    $_cas_member = app::get('b2c') -> model('cas_members');
    $_cas_company = app::get('b2c') -> model('club_cas_company');
    $_member_company = app::get('b2c') -> model('member_company');
    $_cas = kernel::single('b2c_cas_api');
    $member_id = 0;
}

$start = $argv[1];
$end = $argv[2];
$window = "Sync member >= {$start} && < {$end} ";

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
    $sql = "select * from sdb_b2c_member_company where member_id > {$member_id} and member_id >= {$start} and member_id < {$end} group by member_id order by member_id asc";
    $result1 = $_member_company -> db -> selectrow($sql);
    $result = $_member_model -> getRow('*',array('member_id' => $result1['member_id']));
    if($result1 && $result && $result['member_id']){
        $member_id = $result['member_id'];
        $cas_member = $_cas_member -> getRow('*',array('member_id' => $member_id));
        $account_info = $_account -> getRow('*',array('account_id' => $member_id));
        if($cas_member){
            echo date('Y-m-d H:i:s',time()) , " HAS:" , $cas_member['member_id'];
            echo "\n";

        }
        if($account_info && !$cas_member){

            $data = array();
            $data['b_year'] = $result['b_year'];
            $data['b_month'] = $result['b_month'];
            $data['b_day'] = $result['b_day'];
            $data['sex'] = $result['sex'];
            $data['is_verify_mail'] = 'true';
            $data['is_bind'] = 'true';
            $data['mobile'] = $result['mobile']; // guid 12
            $data['login_name'] = $account_info['login_name'];
            $data['login_password'] = (time() + time()) . rand(0,99999);
            $data['email'] = $result['email'];
            $data['name'] = $result['name'];
            $data['state'] = 0;
            $data['company_id'] = $result['company_id'];
            $data['member_id'] = $member_id;
            $data['account_state'] = $account_info['disabled'] == 'true' ? 2 : 1;
            $data['login_name_original'] = $data['login_name'];
            $data['process_data']['process_id'] = $data['login_name_original'];
            $step = 'memberCreateProcessDispatch';
            $msg = '';
            $_process = kernel::single('b2c_cas_process');
            $p = $_process -> memberDoLoop($data,$step,$msg);

            if($p){
                $member_company = $_member_company -> getList('*',array('member_id' => $member_id,'company_id|noequal' => $result['company_id']));
                if($member_company){
                    foreach ($member_company as $k => $v) {
                        $cas_company = $_cas_company -> getCasCompany($v['company_id']);
                        $datac = array();
                        $datac['login_name'] = $v['company_email'];
                        $datac['gcorp_id'] = $cas_company['gcorp_id'];
                        $datac['guid'] = $data['cas_data']['guid'];
                        $datac['mobile'] = $data['mobile'];
                        $datac['email'] = $v['company_email'];
                        $datac['state'] = $v['state'] == 2 ? 11 : 1;
                        $sync_company_user = $_cas -> corpUserBind($datac);
                        if($sync_company_user['result'] != true){
                            $data['sync_company_error'][] = array('company_id' => $v['company_id'],'data' => $datac);
                        }else{
                            $data['sync_company_success'][] = array('company_id' => $v['company_id'],'data' => $datac);
                        }
                    }
                }else{
                    $data['msg'] = 'single company';
                }
            }

            $data['result'] = $p;

            $log_data = array();
            $log_data['log_key'] = $member_id;
            $log_data['log_data'] = serialize($data);
            $log_data['type'] = 'member';
            $log_data['last_time'] = time();
            echo serialize($log_data);
            echo "\n";

            if($p){
                echo date('Y-m-d H:i:s',time()) , ' SUCCESS:' , $member_id;
                echo "\n";
            }else{
                $fail_count++;
                echo date('Y-m-d H:i:s',time()) , ' ERROR:' , $member_id, "fail_count", $fail_count;
                echo "\n";
                if ($fail_count>20) {
                    $result_while = false;
                }
            }

        } else{
            echo date('Y-m-d H:i:s',time()) , ' NO MEMBER INFO';
            echo "\n";
            continue;
        }
    }else if ($result1 && !$result){
        echo date('Y-m-d H:i:s',time()) , ' CONTINUE:' , $result1['member_id'];
        echo "\n";
        continue;
    }else{
        echo date('Y-m-d H:i:s',time()) , 'NO MORE';
        echo "\n";
        $result_while = false;
    }
}