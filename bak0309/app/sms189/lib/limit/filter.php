<?php
/**
 * Created by PhpStorm.
 * User: licong
 * Date: 15/10/22
 * Time: 下午4:01
 */

class sms189_limit_filter {
    static private $limit_express_time = 86400;//24*60*60;
    static private $limit_short_count = 20;         // 改回2
    static private $limit_long_count = 50;          // 改回5

    static function limit_and_update_for_phonenumber($mobile) {
        $kv_sms_server = base_kvstore::instance('distribute');
        $kv_sms_key = 'ecstore-limit_for_phonenumber-'.$mobile;
        $kv_sms_value = '';
        $kv_sms_server->fetch($kv_sms_key, $kv_sms_value);
        $sms_list = json_decode($kv_sms_value, true);

        $list_count = count($sms_list);
        $need_send = false;

        if ($list_count < self::$limit_short_count) {
            $need_send = true;
        } else if ($list_count >= self::$limit_short_count &&
                    $list_count < self::$limit_long_count &&
                    $sms_list[$list_count-self::$limit_short_count] < strtotime("-1 hours")) {
            $need_send = true;
        } else if ($list_count >= self::$limit_long_count &&
            $sms_list[$list_count-self::$limit_short_count] < strtotime("-1 hours") &&
            $sms_list[$list_count-self::$limit_long_count] < strtotime("-1 day")) {
            $need_send = true;
        }

        if ($need_send) {
            if ($list_count >= self::$limit_long_count) {
                array_shift($sms_list);
                $sms_list[]=time();
            } else {
                $sms_list[]=time();
            }
            $kv_sms_server->store($kv_sms_key, json_encode($sms_list), self::$limit_express_time);
            return true;
        } else {
            return false;
        }
    }
}