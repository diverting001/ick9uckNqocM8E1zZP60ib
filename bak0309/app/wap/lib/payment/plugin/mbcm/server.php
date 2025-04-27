<?php

/**
 * 京东支付 notify 验证接口
 * @auther wuchuanbin <wuchuanbin3013@163.com>
 * @version 1.0
 * @package ectools.lib.payment.plugin
 */
class wap_payment_plugin_mbcm_server extends ectools_payment_app
{

    /**
     * 支付后返回后处理的事件的动作
     * @params array - 所有返回的参数，包括POST和GET
     * @return null
     */
    public function callback( &$recv )
    {
        $recv = file_get_contents( 'php://input', 'r' );

//        $recv = '{"biz_content":"kaV4ISJqk8uKdUJyuNLGo+dU2YCXzXWen9j2O5pKuQXWZJNcOHD7A235Ax3P2y4EnmN95JrDyd1u+rVMynk3THSTiYoiwuEbQWbpVTSk8QSHcQvziUWqiwSdm9EYPnQmNibRrh9CRntbP1aUsDGiSz1Jo+EwnmNgmPyf2uDAWKGfXDyy0Y0HxNnCGl2nA/Q/+5hIXqTh6QME+3y5sPEZZ8+h/vvjIgpORMDPaV5JsghT42DAY1YRmpHpb54LskA/w2qzSQdeFxmHr+RCdgR6lb/Jd3IQrJXQ5cdoWHUyBXqQ8+HHXIuc/IQ+ULnVeYbFr7y4pRv7G8RKtEfu6d3o//g4XFhkP/D8jigekArAnIvayHfkWI3ZyTeDrFC9lpRwm8Lx6vXoUee1RviU4mpqShbJDaXeoWa7ZxPvpZwsqdzeTTDwHEOGqSzDE7qaVp2un02UqAZRya91jZVkFQBTus0GSUXRVHPQS2OR7U7R14j0/TJ+NVpY52RZJFIANL/rZt/KPRtcG2hwdzkOkTMH21itV4jEM+poz2hlMnDzIvS0TL1/TQNiG97n1cCQ6fWCpEqFDanoLNJrdOWU2t4ZUgqosMn15IEbdRHfx0a6qR8gzXtm3Hmux5kFWyrOz4pj","msg_id":"acd10347303a474f8883cd6ee5ec018c","timestamp":"2021-04-25 18:10:28","encrypt_key":"dKKbqUv4X7TGTozqfG01uh8SOTCK19i0iaTkv26enZP9BCwXw6Y2N5Xh8IVN8vLRt1qiufggIxSDc6r1s9Bp3kIsoDl80tcmjmTcRnqK92lkwajFUrunwClDULLw+xtJFkry0891NXCS+8AeHUIMOjs8xzgau85sCRX+imBfEYNt3cDlqQlkTL4UBrQereh1twwoImW7tYiCBeM8Nu3hlWHGc8wNZgAcQM7qqOzsfyFNu34pRLkc8Xy6XvaqLABm8tJbmBWsgdTeIFyjzLMm1xE3t3v59Cr47vmNuzXHbPIg4tVU2MyaFVvTW47RdAIxrJ9c2F2MASS+D1m0taGAmQ==","sign":"DSAoQQyHU1OTUn8HbrWICe9bxJFGdCnYlagarSM8Ng5UvRz+SY7KhF3INscRFttnBvrUuY8MNRwzdwgyK2rCFGV6oI8aUy24XXvLMo2e+M1EYurmzSp+t/GD3/Z7Rsa4IqJkPWMOPzjuV7YxPjirLiVsHiOizfy9AjBMz9xFTNoKN/xh1ZvGdlwD+Bos6NnU/vohS9VMX1lmtTBzkcnmKOu7s6i3mID7hxACeZo/6z2JXfOw8wlA99h9HigROC2Q69z2oK/zeursN8UMrBaL3NqO6o0cG+YFkoF2G16nFYdWUxxF0qtLm0VM9NEM/11cvMNGMLUU6VLEYCGHiyWbaw=="}';

//        $recv = '{"biz_content":"N4e2ta2ZYGu0XQ1GYW/dwrYF/4UUp1DDSPM4IS54oJ6IDDOrYPw8+Cfq8oqu8Faq9Um5X4gfrqS1WRTrwpJ4jFzHTUF5ZsCjxtCYRnxJzXkM5ozbtQ3bXgLfcRX7ZmNKw8ky026cujp6Yl8m7zylQNmPgobSQiXTN86NVTPK8gdPX0T3KiwhzhWqsyyce7A7pDSOs59DNfBBgVkE0X7jjAPp0OlAwSDniJXwWY8Cnb5sB8/nUPMbPFgUTuSxMims21kh5nJKT9fdezjThLEKYMM5he6tTlObg/YbcY8JETjB6POv8pkDU6OsACqWnqg7nPorjlrov0/GYJGr/VE+NeIUSr3HjPdHwoh9PHm8VvwWkkvZXQg9U1eVZ/Qw88yIU2yLMlAtH5Wzwitp1iiPQ6WeAWWmlRmanrPE7UOnjvHjZg49mP6O/6/PVUuONeSVZIKkmYkDe92e0jQhjR7DBw==","msg_id":"284e1dca0ce7489cb9e7fd88a8a90ca7","timestamp":"2020-10-31 17:59:11","encrypt_key":"OQGKpNQTFhS9NOmUhQwqRTaqdtVrIHonONtMJxVrb7HPZd7zhDsuRdN6rfA/+Mdf/nNMDnIntYUb6OoqSZx/WSWX6WLGDkuXc74uaz42QdQiBA9WsjYBqv9Hmz5chhU758mUewS8sfsAnF+y4jwi+PeuholewH2InJfzL2al5x0pRCMWDMO40K3CP1FODiU2CC59VOwyb4YLzRgRfWOtlXleDn3PX1CLDw1fuEoSz0mZ0kBxCrk7HINLl2Mc8HFbZNPFeDCLMmrtSTcUwSsvuxP0YyYH1iHh31MX94ZKtBaVPrasmxkE/meFFnHjvA2i7i5ZfmfdyIgsLELSsRGx/A==","sign":"VctRdjETAL1G+F0h8/xnD/S7V4+UpNzx3NvEdl29Pii+aidJ+2AF2zXP3U0sa9stJoj14iHPdM+5kd+4D2ku1trZnPirpR5soXVneUL/tOrPvRsCpjPapzXyUwCYub4YEQRf4ccqVahZmfIA/zMMUCCkUZnxnLmDEnrRnEHsUD0hjNp3R8PMjJ4QbJfN1g71CpBFuIlHVjp5bgellrwpb+qhAhz/3f7JU90hja/J+aJYMJzzJDInLq4QCF4TqbvDsxdBnP21mQbovyn+JBumhEuNzTK/V5WMK+fUzWxSddTFrSpDNrTej9dveEQpqre7iZRtQLAx3wnt6S5psVPkCg=="}';

        header( 'Content-Type:text/html; charset=utf-8' );

        $ret['callback_source'] = 'server';

        //解密
        $error = '';
        $post = array(
            'response_str' => $recv,
            'public_rsa_key' => $this->getConf( 'public_rsa_key', 'wap_payment_plugin_mbcm' ),
            'private_rsa_key' => $this->getConf( 'private_rsa_key', 'wap_payment_plugin_mbcm' ),
        );

        $checkData = $this->is_return_vaild( $post, $error );

        \Neigou\Logger::General( 'mbcm', array( 'action' => 'notify_callback.init', 'data' => $recv, 'remark' => $checkData, 'error' => $error ) );

        if ( $checkData['decrypt_data'] )
        {
            $decrypt_data = $checkData['decrypt_data'];

            if ( $decrypt_data['trade_state'] == 'SUCCESS' )
            {
                \Neigou\Logger::General( 'mbcm', array( 'action' => 'notify_success', 'data' => $recv ) );

                $total_amount = (int)$decrypt_data['total_amount'];

                $ret['payment_id'] = $decrypt_data['out_trade_no'];//payment_id
                $ret['account'] = $decrypt_data['mch_id'];
                $ret['bank'] = app::get( 'ectools' )->_( '交行数字人民币' );
                $ret['pay_account'] = $decrypt_data['mch_id'];
                $ret['currency'] = 'CNY';
                $ret['money'] = $total_amount / 100;
                $ret['paycost'] = '0.000';
                $ret['cur_money'] = $total_amount / 100;
                $ret['trade_no'] = $decrypt_data['order_id'];//交行订单号
                $ret['t_payed'] = strtotime( $decrypt_data['pay_time'] );
                $ret['pay_app_id'] = 'mbcm';
                $ret['pay_type'] = 'online';
                $ret['status'] = 'succ';

                \Neigou\Logger::General( 'mbcm', array( 'action' => 'notify_callback.success', 'data' => $recv, 'remark' => $ret ) );

                $this->msg( $checkData['response_sign'] );
            }
            else
            {
                \Neigou\Logger::General( 'mbcm', array( 'action' => 'notify_trade_status_err', 'data' => $recv ) );

                $ret['status'] = 'invalid';
            }
        }
        else
        {
            $ret['status'] = 'invalid';
            \Neigou\Logger::General( 'mbcm', array( 'action' => 'sign err', 'data' => $recv ) );
        }

        return $ret;
    }

    /**
     * 响应输出
     * @param $bool
     */
    private function msg( $msg )
    {
        echo $msg;
    }

    /**
     * 检验返回数据合法性
     * @param string $params
     * @param string $error
     * @return bool
     */
    public function is_return_vaild( $params = '', &$error = '' )
    {
        if ( empty( $params['response_str'] ) || empty( $params['public_rsa_key'] ) || empty( $params['private_rsa_key'] ) )
        {
            $error = '参数错误';
            return false;
        }

        //系统间加密
        $post['body'] = self::authcode( base64_encode( json_encode( $params ) ), 'ENCODE' );

        $curl = new \Neigou\Curl();
        $result = $curl->Post( PAY_DOMAIN . '/tools/wmNotifyCheck', $post );

        \Neigou\Logger::General( 'mbcm', array( 'action' => 'pay.notify.check', 'data' => json_encode( $params ), 'remark' => json_encode( $result ), 'sender' => json_encode( $post ) ) );

        $result = json_decode( $result, true );
        if ( empty( $result ) || $result['result'] == 'false' || empty( $result['data'] ) || empty( $result['data']['body'] ) )
        {
            $error = $result['message'];
            return false;
        }

        $body = $result['data']['body'];
        $decode = self::authcode( $body, 'DECODE' );
        $content = json_decode( base64_decode( $decode ), true );

        return $content;
    }

    /**
     * Discuz 加解密函数
     * @param $string
     * @param string $operation
     * @param string $key
     * @param int $expiry
     * @return bool|string
     */
    private static function authcode( $string = '', $operation = 'DECODE', $key = 'f0u6@X&$ssGZyJOiQ$IbfaMCtTbCkrzz', $expiry = 0 )
    {
        $ckey_length = 4;
        $key = md5( $key );
        $keya = md5( substr( $key, 0, 16 ) );
        $keyb = md5( substr( $key, 16, 16 ) );
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr( $string, 0, $ckey_length ) : substr( md5( microtime() ), -$ckey_length )) : '';

        $cryptkey = $keya . md5( $keya . $keyc );
        $key_length = strlen( $cryptkey );

        $string = $operation == 'DECODE' ? base64_decode( substr( $string, $ckey_length ) ) : sprintf( '%010d', $expiry ? $expiry + time() : 0 ) . substr( md5( $string . $keyb ), 0, 16 ) . $string;
        $string_length = strlen( $string );

        $result = '';
        $box = range( 0, 255 );

        $rndkey = array();
        for ( $i = 0 ; $i <= 255 ; $i++ )
        {
            $rndkey[$i] = ord( $cryptkey[$i % $key_length] );
        }

        for ( $j = $i = 0 ; $i < 256 ; $i++ )
        {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ( $a = $j = $i = 0 ; $i < $string_length ; $i++ )
        {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr( ord( $string[$i] ) ^ ($box[($box[$a] + $box[$j]) % 256]) );
        }

        if ( $operation == 'DECODE' )
        {
            if ( (substr( $result, 0, 10 ) == 0 || substr( $result, 0, 10 ) - time() > 0) && substr( $result, 10, 16 ) == substr( md5( substr( $result, 26 ) . $keyb ), 0, 16 ) )
            {
                return substr( $result, 26 );
            }
            else
            {
                return '';
            }
        }
        else
        {
            return $keyc . str_replace( '=', '', base64_encode( $result ) );
        }
    }

}