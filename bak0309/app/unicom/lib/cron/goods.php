<?php
/**
 * 联通商品 crontab
 * @package     neigou_store
 * @author      xupeng
 * @since       Version
 * @filesource
 */
class unicom_cron_goods
{
    /**
     * 拉取地址列表
     *
     * @return string
     */
    public function pullGoodsCategory()
    {
        $errMsg = '';
        $request = kernel::single('unicom_request');

        // 获取所有一级商品分类
        $result = $request->request(array('method' => 'allFirstCategory'), $errMsg);

        if ($result === false)
        {
            return $errMsg;
        }

        if ($result['success'] != 'true')
        {
            return  ! empty($result['resultMessage']) ? $result['resultMessage'] : '未知错误';
        }

        if (empty($result['result']) OR  ! is_array($result['result']))
        {
            return '无一级分类数据';
        }
        $goodsCategoryModel = app::get('unicom')->model('goods_category');

        $errMsgList = array();
        foreach ($result['result'] as $category)
        {
            // 保存一级分类
            if (! $goodsCategoryModel->saveGoodsCategoryData($category['code'], $category['name'], $category['pcode'],
                $category['level'], $category['orderSort'], $category['isLeaf'], $category['path'], $category['pathName'], $category['attrs']))
            {
                $errMsgList[] = 'save first level goods category failed '. $category['code'];
                continue;
            }

            if ($category['isLeaf'] == 1)
            {
                continue;
            }

            // 拉取并保存子级分类
            $this->_pullChildGoodsCategory($category['code'], $errMsgList);
        }

        return implode("\n", $errMsgList);
    }

    // --------------------------------------------------------------------

    /**
     * 拉取地址信息
     *
     * @param   $pcode          string      上级分类编码
     * @param   $errMsgList     array       错误信息列表
     * @return  boolean
     */
    public function _pullChildGoodsCategory($pcode, & $errMsgList = array())
    {
        if (empty($pcode))
        {
            return true;
        }

        $errMsg = '';

        $categoryList = kernel::single('unicom_request')->request(array('method' => 'queryCategoryInfoByPcode', 'data' => array('pcode' => $pcode)), $errMsg);

        if ($categoryList['success'] != 'true' OR empty($categoryList['result']))
        {
            return true;
        }

        $goodsCategoryModel = app::get('unicom')->model('goods_category');
        foreach ($categoryList['result'] as $category)
        {
            if ( ! $goodsCategoryModel->saveGoodsCategoryData($category['code'], $category['name'], $category['pcode'], $category['level'],
                $category['orderSort'], $category['isLeaf'], $category['path'], $category['pathName'], $category['attrs']))
            {
                $errMsgList[] = 'save goods child category failed '. $category['code'];
            }

            if ($category['isLeaf'] != 1)
            {
                $this->_pullChildGoodsCategory($category['code'], $errMsgList);
            }
        }

        return true;
    }

}
