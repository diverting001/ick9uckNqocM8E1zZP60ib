<?php
/**
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2020-04-30
 * Time: 10:22
 */

class unicom_mdl_blackListRule extends base_db_external_model
{
    // 数据库配置
    private $db_conf = array(
        'MASTER' => array('HOST' => DB_HOST,
            'NAME' => DB_NAME,
            'USER' => DB_USER,
            'PASSWORD' => DB_PASSWORD)
    );

    /**
     * 公开构造方法
     * @params app object
     * @return mixed
     */
    public function __construct($app)
    {
        parent::__construct($app, $this->db_conf);
    }

    /**
     * 获取黑名单规则ID
     * @return array
     */
    public function allowRule(){
        //获取满足条件的规则
        $now = time();
        $sql = "SELECT * FROM unicom_goods_black_rule WHERE start_time <{$now} AND end_time >{$now}";
        $rules = $this->_db->select($sql);
        $rule_list = array();
        if($rules){
            $blackIdA = $this->_array_column($rules,'id');
            //获取规则对应的

            $id_str = implode(',',$blackIdA);
            $sql_rel = "SELECT goods_rule_bn FROM unicom_goods_black_rule_rel WHERE black_rule_id in ({$id_str})";
            $res = $this->_db->select($sql_rel);
            $rule_bn = $this->_array_column($res,'goods_rule_bn');
            $ret = $this->RuleBn2RuleId($rule_bn);
            $rule_list = $this->_array_column($ret,'channel_rule_bn');
        }
        return $rule_list;
    }

    /**
     * 获取黑名单商品ID
     * @param $goodsData
     * @return array
     */
    public function BlackProductId($goodsData){
        //对数据进行黑名单检测
        $allowRule = $this->allowRule();
        $req['product'] = $goodsData;
        $res = $this->WithRule($allowRule,$req);
        //黑名单商品ID
        $black_id_list = array();
        if(is_array($res)){
            foreach ($res['product'] as $k=>$v){
                foreach ($v['product_list'] as $tmp_good){
                    $black_id_list[] = $tmp_good['id'];
                }
            }
        }
        return $black_id_list;
    }

    /**
     * 规则BN换内部BN
     * @param $rule_bns
     * @return array
     */
    public function RuleBn2RuleId($rule_bns){
        $ret = \Neigou\ApiClient::doServiceCall(
            'rule',
            'Rule/ChannelRuleBn/Query',
            'v1',
            null,
            array(
                'channel'  => 'NEIGOU_SHOPING',
                'rule_bns' => $rule_bns
            ),
            array()
        );

        $ruleIdArr = array();
        if ('OK' == $ret['service_status'] && 'SUCCESS' == $ret['service_data']['error_code']) {
            return $ret['service_data']['data'];
        }
        return $ruleIdArr;
    }

    /**
     * 规则适配
     * @param $rule_list
     * @param $filter_data
     * @return bool
     */
    public function WithRule($rule_list,$filter_data){
        if(!is_array($filter_data['product'])){
            return false;
        }
        foreach ($filter_data['product'] as $key=>$value){
            if(empty($filter_data['product'][$key]['goods_bn'])){
                $filter_data['product'][$key]['goods_bn'] = $value['product_bn'];
            }
            $filter_data['product'][$key]['product_bn'] = $value['product_bn'];
        }
        $send_data['rule_list'] = $rule_list;
        $send_data['filter_data'] = $filter_data;
        $res = \Neigou\ApiClient::doServiceCall(
            'rule',
            'NeigouRule/WithRule',
            'v1',
            null,
            $send_data,array('debug'=>false)

        );
        if ('OK' == $res['service_status'] && 'SUCCESS' == $res['service_data']['error_code'] && !empty($res['service_data']['data'])) {
            return $res['service_data']['data'];
        } else {
            return false;
        }
    }

    public function _array_column($data,$column){
        $array = array();
        foreach ($data as $val){
            $array[] = $val[$column];
        }
        return array_unique($array);
    }

}
