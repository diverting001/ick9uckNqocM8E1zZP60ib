<?php

/**
 * 信用 支付
 * Created by PhpStorm.
 * User: chuanbin
 * Date: 2018/08/06
 * Time: 11:12
 */
final class wap_payment_plugin_mcredit extends ectools_payment_app implements ectools_interface_payment_app
{
    /**
     * @var string 支付方式名称
     */
    public $name = '信用 支付';
    /**
     * @var string 支付方式接口名称
     */
    public $app_name = '信用 支付';
    /**
     * @var string 支付方式key
     */
    public $app_key = 'mcredit';
    /**
     * @var string 中心化统一的key
     */
    public $app_rpc_key = 'mcredit';
    /**
     * @var string 统一显示的名称
     */
    public $display_name = '信用 支付';
    /**
     * @var string 货币名称
     */
    public $curname = 'CNY';
    /**
     * @var string 当前支付方式的版本号
     */
    public $ver = '1.0';
    /**
     * @var string 当前支付方式所支持的平台
     */
    public $platform = 'iswap';

    /**
     * @var array 扩展参数
     */
    public $supportCurrency = array("CNY"=>"01");

    /**
     * 构造方法
     * @param null
     * @return boolean
     */
    public function __construct($app){
        parent::__construct($app);
        $this->notify_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mcredit_server', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->notify_url, $matches)) {
            $this->notify_url = str_replace('http://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "http://" . $this->notify_url;
        } else {
            $this->notify_url = str_replace('https://','',$this->notify_url);
            $this->notify_url = preg_replace("|/+|","/", $this->notify_url);
            $this->notify_url = "https://" . $this->notify_url;
        }
        $this->callback_url = kernel::openapi_url('openapi.ectools_payment/parse/wap/wap_payment_plugin_mcredit', 'callback');
        if (preg_match("/^(http):\/\/?([^\/]+)/i", $this->callback_url, $matches)) {
            $this->callback_url = str_replace('http://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "http://" . $this->callback_url;
        } else {
            $this->callback_url = str_replace('https://','',$this->callback_url);
            $this->callback_url = preg_replace("|/+|","/", $this->callback_url);
            $this->callback_url = "https://" . $this->callback_url;
        }
        $this->submit_url = $this->getConf('submit_url', __CLASS__);
        $this->submit_method = 'POST';
        $this->submit_charset = 'utf-8';
    }

    /**
     * 后台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function admin_intro(){
        return '信用 支付配置信息';
    }

    /**
     * 后台配置参数设置
     * @param null
     * @return array 配置参数列表
     */
    public function setting(){
        return array(
            'pay_name'=>array(
                'title'=>app::get('ectools')->_('支付方式名称'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'app_id'=>array(
                'title'=>app::get('ectools')->_('商户AppID'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'sign_key'=>array(
                'title'=>app::get('ectools')->_('sign_key'),
                'type'=>'string',
                'validate_type' => 'required',
            ),
            'order_by' =>array(
                'title'=>app::get('ectools')->_('排序'),
                'type'=>'string',
                'label'=>app::get('ectools')->_('整数值越小,显示越靠前,默认值为1'),
            ),
            'pay_type'=>array(
                'title'=>app::get('wap')->_('支付类型(是否在线支付)'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('wap')->_('否'),'true'=>app::get('wap')->_('是')),
                'name' => 'pay_type',
            ),
            'status'=>array(
                'title'=>app::get('ectools')->_('是否开启此支付方式'),
                'type'=>'radio',
                'options'=>array('false'=>app::get('ectools')->_('否'),'true'=>app::get('ectools')->_('是')),
                'name' => 'status',
            ),
        );
    }
    /**
     * 前台支付方式列表关于此支付方式的简介
     * @param null
     * @return string 简介内容
     */
    public function intro(){
        return app::get('ectools')->_('信用 支付配置信息');
    }


    /**
     * 检验用户密码是否正确
     * @param $company_id
     * @param $member_id
     * @param $password
     * @return bool
     */
    public function checkPass($company_id,$member_id,$password){
        $map['member_id'] = $member_id;
        $map['company_id'] = $company_id;
        $info = app::get('ectools')->model('credit_member')->getRow('*',$map);
        if(md5($info['salt'].$password.$info['salt'])==$info['password']){
            return true;
        } else {
            \Neigou\Logger::General('ecstore.mcredit.check',array('remark'=>'支付密码错误','where'=>$map,'enter'=>$password));
            return false;
        }
    }

    /**
     * 提交支付信息的接口
     * @param array 提交信息的数组
     * @return mixed false or null
     */
    public function dopay($payment) {

        if(empty($payment['verify_password'])){
            //显示输入密码页面
            $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'pay_credit','arg0'=>$payment['order_id']));
            //redirect to code input page
            \Neigou\Logger::General('pay.douli', array('action' => 'code_succ','to_url'=>$url));
            header('Location: '.$url);
            die;
        }



        $credit_server = kernel::single('b2c_service_credit');
        $cre_req['rmb_amount'] = $payment['cur_money'];
        //获取公司channel company_id
        $company_id = kernel::single("b2c_member_company")->get_cur_company();
        $channel = app::get('b2c')->model('club_company')->getCompanyRealChannel($company_id);

        //TODO 验证密码
        $check_status = $this->checkPass($company_id,$payment['member_id'],$payment['verify_password']);
        if(!$check_status){
            //支付密码错误 跳转到支付失败
            $out['msg'] = '支付密码错误';
            $out['code'] = 1001;
            echo json_encode($out);die;
        }


        $cre_req['channel'] = $channel;
        $cre_req['company_id'] = $company_id;
        $cre_req['order_id'] = $payment['order_id'];
        $cre_req['part'] = $this->getConf('app_id',__CLASS__);
        $credit_id = 0;
        $credit_res = $credit_server->record($cre_req,$credit_id);

        \Neigou\Logger::General('ecstore.mcredit.dopay',array('action'=>'req','req'=>$cre_req,'response'=>$credit_res));
        if($credit_res!==true){
            //跳转支付失败
            $url = app::get('wap')->router()->gen_url(array('app'=>'b2c','ctl'=>'wap_paycenter2','act'=>'result_failed','arg0'=>$payment['order_id']));
            //redirect to pay err page
            \Neigou\Logger::General('pay.mcredit', array('action' => 'credit pay fail', 'req'=>$cre_req,'response'=>$credit_res));
            $out['msg'] = '扣款失败';
            $out['code'] = 1002;
            $out['url'] = $url;
            echo json_encode($out);
            die;
        } else {
            //支付成功 跳转到支付回调页面 需要重新定义回调页面跳转成功地址
            $callback['trade_no'] = $payment['payment_id'];
            $callback['cur_money'] = $payment['cur_money'];
            $callback['credit_id'] = $credit_id;
            $callback['sign'] = md5($payment['payment_id'].$this->getConf('sign_key',__CLASS__).$callback['cur_money'].$credit_id);
            $query_url = http_build_query($callback);
            $localtion = $this->callback_url.'?'.$query_url;
            //发送credit支付成功消息 ##MQ
            $mq = new \Neigou\AMQP();
            $callback['notify_url'] = $this->notify_url;
            $mq->PublishMessage('service','ecstore.credit_pay.success',$callback);

            $out['msg'] = '支付成功';
            $out['code'] = 1;
            $out['url'] = $localtion;
            echo json_encode($out);
        }
        die;
    }

    /**
     * 支付后返回后处理的事件的动作
     * @param $recv
     * @return mixed
     */
    public function callback(&$recv) {
        if($this->is_return_vaild($recv)){
            //获取订单详细信息
            $ret['payment_id'] = $recv['trade_no'];
            $ret['account'] = $this->getConf('app_id',__CLASS__);
            $ret['bank'] = app::get('ectools')->_('信用 支付');
            $ret['pay_account'] = $recv['appId'];
            $ret['currency'] = 'CNY';
            $ret['money'] = $recv['cur_money'];
            $ret['paycost'] = '0.000';
            $ret['cur_money'] = $recv['cur_money'];
            $ret['trade_no'] = $recv['credit_id'];
            $ret['t_payed'] =  time();
            $ret['pay_app_id'] = "mcredit";
            $ret['pay_type'] = 'online';
            $ret['status'] = 'succ';
            \Neigou\Logger::General('ecstore.callback.mcredit', array('remark' => 'trade_succ', 'data' => $ret));
        }else{
            $ret['status'] = 'invalid';
            \Neigou\Logger::General('ecstore.callback.mcredit',array('remark'=>'sign_err','data'=>$recv));
        }
        return $ret;
    }

    /**
     * 校验方法
     * @param null
     * @return boolean
     */
    public function is_fields_valiad(){
        return true;
    }

    public function is_return_vaild($data){
        $sign = $data['sign'];
        unset($data['sign']);
        if($sign==md5($data['trade_no'].$this->getConf('sign_key',__CLASS__).$data['cur_money'].$data['credit_id'])){
            return true;
        } else {
            \Neigou\Logger::General('ecstore.callback.mcredit.sign_err',array('remark'=>'sign_err','data'=>$data));
            return false;
        }
    }

    public function gen_form(){
        return '';
    }
}