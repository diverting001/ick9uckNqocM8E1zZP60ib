<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/9/20
 * Time: 下午9:42
 */

//用于本地测试接口的可用性

require_once(__DIR__ . "/ThriftCenterClient.php");

function test_function($params) {
    $handler = new ThriftCenter\ThriftCenterClientAdapter();
    switch($params) {
        case "companyCreatePoint":
            $data = array(
                'company_id'=>12,
                'point'=>10000,
                'reason'=>'charge',
                'op_name'=>'test_name',
                'memo'=>'测试公司积点发送'
            );
            echo $handler->BalancePointServer('companyCreatePoint', json_encode($data));
            break;
        case "companyAwardPoint":
            $data = array(
                'company_id'=>12,
                'point'=>2000,
                'member_point_list'=>array(
                    array(
                        'member_id'=>130,
                        'member_name'=>'李聪',
                        'award_point'=>1000,
                    ),
                    array(
                        'member_id'=>130,
                        'member_name'=>'李聪',
                        'award_point'=>1000,
                    )
                ),
                'op_name'=>'test_name',
                'memo'=>'测试公司积点发送'
            );
            echo $handler->BalancePointServer('companyAwardPoint', json_encode($data));
            break;
        case "companyQueryPoint":
            $data = array(
                'company_id'=>12
            );
            echo $handler->BalancePointServer('companyQueryPoint', json_encode($data));
            break;
        case "companyQueryHistoryPoint":
            $data = array();
            echo $handler->BalancePointServer('companyQueryHistoryPoint', json_encode($data));
            break;
        case "memberAwardPoint":
            $data = array(
                'member_id'=>130,
                'member_name'=>'李聪',
                'award_point'=>200,
                'reason'=>'return',
                'op_name'=>'test_name',
                'memo'=>'测试公司积点发送'
            );
            echo $handler->BalancePointServer('memberAwardPoint', json_encode($data));
            break;
        case "memberUsePoint":
            $data = array(
                'member_id'=>130,
                'point'=>50,
                'order_id'=>20151117123457,
                'op_name'=>'test_name',
                'memo'=>'测试使用积点'
            );
            echo $handler->BalancePointServer('memberUsePoint', json_encode($data));
            break;
        case "memberOrderStatusChanged":
            $data = array(
                'member_id'=>130,
                'order_id'=>20151117123457,
                'status'=>'finish',
                'op_name'=>'test_name',
                'memo'=>'测试订单取消');
            echo $handler->BalancePointServer('memberOrderStatusChanged', json_encode($data));
            break;
        case "memberQueryMemberPoint":
            $data = array(
                'member_id'=>130
            );
            echo $handler->BalancePointServer('memberQueryMemberPoint', json_encode($data));
            break;
        case "memberQueryOrderPoint":
            $data = array(
                'order_id'=>20151117123456
            );
            echo $handler->BalancePointServer('memberQueryOrderPoint', json_encode($data));
            break;
        case "memberQueryMemberHistoryPoint":
            $data = array();
            echo $handler->BalancePointServer('memberQueryMemberHistoryPoint', json_encode($data));
            break;
        default:
            break;
    }
}

test_function($argv[1]);

//$data = array(
//    'money'=>'100',
//    'count'=>2,
//    'valid_time'=>1444446000,
//    'company_id'=>50,
//    'op_id'=>1,
//    'op_name'=>'licong',
//    'comment'=>'test valid_time',
//    'num_limit'=>1,
//    'exclusive'=>1
//);
//
//echo $client->addVoucher(json_encode($data));
//echo $client->queryMemberVoucher(130);

//echo $client->useVoucher(json_encode(array('ADF8638D2B95F433')), 130, 64, 50, 'interface test');

