<?php

class sms189_rpc_bridge {

        /** 中介方法，可在此处对是否发起请求做判断
         *
         * @param varchar $which 请求资源类型
         * @param array $params 参数数组
         * @param string $style 接口类型，默认手机短信
         * @return bool
         */
    public static function request($which, $params, &$msg, $style='mobile')
    {
        $class = 'sms189_rpc_request_' . $style;
        $method = 'active_' . $which;
        if (isset($params['use_type'])) {
            $type = $params['use_type'];
            unset($params['use_type']);
            $return = kernel::single($class)->$method($params, $type);
        } else
            $return = kernel::single($class)->$method($params);
        $msg = kernel::single($class)->result['idertifier'];

        return $return;
    }

        /** 中介方法，可在此处对接口回写请求做处理
         *
         * @param string $which 请求资源类型
         * @param array $params 参数数组
         * @param string $style 接口类型，默认手机短信
         * @return bool
         */
    public static function response($which, $result, $msg, $style='mobile')
    {
        $class = 'sms189_' . $style;
        $method = 'callback_' . $which;
        $return = kernel::single($class)->$method($result, $msg);

        return $return;
    }

}
