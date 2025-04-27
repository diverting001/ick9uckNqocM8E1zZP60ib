<?php

/**
 * 中粮商品 crontab
 * @package     neigou_store
 * @author      guke
 * @since       Version
 * @filesource
 */
class wmqy_goods
{
    private $_curl;

    public function __construct()
    {
        // curl
        $this->_curl = new \Neigou\Curl();
        $this->_curl->time_out = 10;
    }

    /**
     * 推送商品
     *
     * @param   $sku_info  array      货品info
     * @param   $errMsg     string      错误信息
     * @return  mixed
     */
    public function pushSku($sku_info, &$errMsg = '')
    {
        /* @var wmqy_mdl_goods $goods_model */
        $goods_model = app::get('wmqy')->model('goods');
        /* @var wmqy_mdl_log $log_model */
        $log_model = app::get('wmqy')->model('log');
        $product_info = app::get('b2c')->model('products')->GetProductAllInfoByBn($sku_info['bn']);
        $price_info = $this->_getProductPrice($sku_info['bn']);
        $pars = array(
            'name' => mb_substr($product_info['name'], 0, 32, 'utf-8') . '_' . $product_info['product_id'],
            'productArea' => '北京', //商品产地
            'brandsId' => 50340, // 品牌编码（通过品牌获取接口获取）
            'safedays' => 30, // 保质期。单位：天
            // 分类
            'c1' => 4,
            'c2' => 401,
            'c3' => 40101,
            'c4' => 4010101,
            'c5' => 401010101,
            'barcode' => substr(md5($product_info['bn']), 8, 16), // 商品条码。必须唯一
            'weightunit' => 'g', // 重量单位
            'weight' => 1, // 商品重量
            'pkspec' => 'pkspec', // 件装规格
            'pkname' => 'pkname', // 件装单位
            'pknum' => 1, // 件装数
//            'enName' => '', // 英文名
            'spec' => 'spec', // 商品规格
            'packStandart' => '袋', // 包装单位
            'length' => 1, // 长
            'width' => 1, // 宽
            'height' => 1, // 高
            'isorganicfood' => 0, // 是否有机食品。0否1是
            'isgreenfood' => 0, // 是否绿色食品。0否1是
            'ishealthfood' => 0, // 是否保健食品。0否1是
            'ishygiene' => 0, // 是否卫生用品。0否1是
            'suppliergoodsid' => $product_info['bn'], // 供货商的商品编码
            'cost' => $product_info['cost'], // 进价
            'price' => $price_info['price'],
            'intputtax' => $product_info['invoice_tax'] * 100, // 进项税
            'outputtax' => $product_info['invoice_tax'] * 100, // 销项税
        );
        if ($pars['price'] < $pars['cost']) {
            $pars['price'] = $pars['cost'] + 1;
        }

        // 过滤名称中的换行符号 制表符
        $pars['name'] = str_replace(array("/r/n", "/r", "/n", "\r", "\n", "\r\n", "\t", "　", "	"), " ", $pars['name']);
        $res = $this->_curl->Post(OPENAPI_DOMAIN . '/ChannelInterop/V1/WMQY/Web/PushProduct?mall_id=' . $sku_info['mall_id'], $pars);
//        print_r($res);die;
        $res = json_decode($res, true);
        $log_model->add(array(
            'type' => 'sku',
            'data_bn' => $product_info['bn'],
            'pars' => addslashes(json_encode($res['Data']['pars'])),
            'resp' => $res['Data']['resp'],
            'created_at' => time(),
        ));
        $data = array(
            'bn' => $sku_info['bn'],
            'updated_at' => time(),
        );
        $res = json_decode($res['Data']['resp'], true);
        if ($res['resultCode'] !== '0' || $res['success'] !== true) {
            $data['status'] = 'failed';
            $data['status_msg'] = $res['msg'];
            $data['fail_count'] = 'fail_count+1';
            $goods_model->updateSku($sku_info['bn'], $data);
            return false;
        } else {
            $data['status'] = 'checking';
            $data['status_msg'] = '推送成功，待审核';
            return $goods_model->updateSku($sku_info['bn'], $data);
        }
    }

    public function initGoods($sku_info, &$msg = '')
    {
        $res = $this->pullSku($sku_info);

//        print_r($res);
//        die;
        /* @var wmqy_mdl_goods $goods_model */
        $goods_model = app::get('wmqy')->model('goods');
        if ($res === false) {
            $data = array(
                'updated_at' => time(),
                'status' => 'failed',
            );
            $msg = '拉取失败';
            $goods_model->updateSku($sku_info['bn'], $data);
            return false;
        } else {
            $data = array(
                'updated_at' => time(),
                'sku_id' => $res['sku_id'],
            );
            if ($res['sku_id'] === '0') {
                $data['status'] = 'checking';
                $data['status_msg'] = '审核中';
                unset($data['sku_id']);
                $msg = '审核中';
            } else {
                $data['status'] = 'succ';
                $data['status_msg'] = '审核成功';
                $msg = '审核成功';
            }
            return $goods_model->updateSku($sku_info['bn'], $data);
        }
    }

    private function pullSku($sku_info)
    {
        $pars = array(
            'suppliergoodsid' => $sku_info['bn'],
            'page' => 1,
            'page_size' => 10,
        );
        $res = $this->_curl->Post(OPENAPI_DOMAIN . '/ChannelInterop/V1/WMQY/Web/GetProduct?mall_id=' . $sku_info['mall_id'], $pars);
        $res = json_decode($res, true);
        $res = json_decode($res['Data']['resp'], true);
        if (empty($res['product_response'])) {
            return false;
        } else {
            $sku_info = json_decode($res['product_response'], true);
            if (empty($sku_info['datas'])) {
                return false;
            }
            return $sku_info['datas'][0]['skus'][0];
        }

    }

    /**
     * 获取货品价格
     *
     * @param   $bn      string      货品编码
     * @return  array
     */
    public function _getProductPrice($bn)
    {
        $return = array();

        if (empty($bn)) {
            return $return;
        }

//        $data = array(
//            'products_bns' => is_array($bn) ? $bn : array($bn),
//            'stages' => array('company' => WMQY_COMPANY_ID),
//        );
//
//        $postData = array(
//            'data' => base64_encode(json_encode($data)),
//        );
//        $postData['token'] = kernel::single('b2c_safe_apitoken')->generate_token($postData, OPENAPI_TOKEN_SIGN);
//
//        $url = ECSTORE_DOMAIN . '/openapi/productsV2/GetProductPrice';


        $sendData = array(
            'filter' => array(
                "product_bn_list" => is_array($bn) ? $bn : array($bn),
            ),
            'environment' => array(
                'company_id' => WMQY_COMPANY_ID,
            )
        );
        $ret = \Neigou\ApiClient::doServiceCall('price', '/Price/GetList', 'v3', null, $sendData);
        if (empty($ret['service_data']['data'])) {
            return $return;
        }
        if (!is_array($bn)) {
            $return = array(
                'price' => $ret['service_data']['data'][$bn]['price'],
                'mktprice' => $ret['service_data']['data'][$bn]['mktprice'],
            );
        } else {
            foreach ($ret['service_data']['data'] as $bn => $v) {
                $return[$bn] = array(
                    'price' => $v['price'],
                    'mktprice' => $v['mktprice'],
                );
            }
        }
        return $return;
    }

    public function tranSkuID($bn_arr)
    {
        /* @var wmqy_mdl_goods $goods_model */
        $goods_model = app::get('wmqy')->model('goods');
        $list = $goods_model->getByBns($bn_arr);
        $return = array();
        foreach ($list as $item) {
            $return[$item['bn']] = $item['sku_id'];
        }
        return $return;
    }
}
