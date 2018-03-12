<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 顺丰速运费用计算方式: 起点到终点 * 重量(kg)
 * ====================================================================================
 * -浙江，上海，江苏地区为15元/公斤，续重(2元/公斤)
 * -续重每500克或其零数 (具体请上顺丰速运网站查询:http://www.sf-express.com/sfwebapp/price.jsp 客服电话 4008111111)
 *
 * -------------------------------------------------------------------------------------
 */

class sf_express
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */

    /**
     * 配置信息参数
     */
    public $configure;

    /*------------------------------------------------------ */
    //-- PUBLIC METHODs
    /*------------------------------------------------------ */

    /**
     * 构造函数
     *
     * @param: $configure[array]    配送方式的参数的数组
     *
     * @return null
     */
    public function sf_express($cfg=array())
    {
        foreach ($cfg as $key=>$val) {
            $this->configure[$val['name']] = $val['value'];
        }
    }

    /**
     * 计算订单的配送费用的函数
     *
     * @param   float   $goods_weight   商品重量
     * @param   float   $goods_amount   商品金额
     * @param   float   $goods_number   商品数量
     * @return  decimal
     */
    public function calculate($goods_weight, $goods_amount, $goods_number)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money']) {
            return 0;
        } else {
            @$fee = $this->configure['base_fee'];
            $this->configure['fee_compute_mode'] = !empty($this->configure['fee_compute_mode']) ? $this->configure['fee_compute_mode'] : 'by_weight';

            if ($this->configure['fee_compute_mode'] == 'by_number') {
                $fee = $goods_number * $this->configure['item_fee'];
            } else {
                if ($goods_weight > 1) {
                    $fee += (ceil(($goods_weight - 1))) * $this->configure['step_fee'];
                }
            }
            // $_SESSION['cart_weight'] = $goods_weight;
            return $fee;
        }
    }

    /**
     * 查询快递状态
     *
     * @access  public
     * @return  string  查询窗口的链接地址
     */
    public function query($invoice_sn)
    {
        $url = 'http://m.kuaidi100.com/query?type=shunfeng&id=1&postid=' .$invoice_sn. '&temp='.time();
        return $url;
    }

    public function third_party($invoice_sn)
    {
        $url = 'http://m.kuaidi100.com/index_all.html?type=shunfeng&postid=' .$invoice_sn;
        return $url;
    }
}
