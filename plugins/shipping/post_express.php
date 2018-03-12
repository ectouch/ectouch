<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

/**
 * 邮政快递包裹费用计算方式
 * ====================================================================================
 * 运距                     首重1000克      5000克以内续重每500克   5001克以上续重500克
 * -------------------------------------------------------------------------------------
 * 500公里及500公里以内     5.00            2.00                    1.00
 * 500公里以上至1000公里    6.00            2.50                    1.30
 * 1000公里以上至1500公里   7.00            3.00                    1.60
 * 1500公里以上至2000公里   8.00            3.50                    1.90
 * 2000公里以上至2500公里   9.00            4.00                    2.20
 * 2500公里以上至3000公里   10.00           4.50                    2.50
 * 3000公里以上至4000公里   12.00           5.50                    3.10
 * 4000公里以上至5000公里   14.00           6.50                    3.70
 * 5000公里以上至6000公里   16.00           7.50                    4.30
 * 6000公里以上             20.00           9.00                    6.00
 * -------------------------------------------------------------------------------------
 * 每件挂号费               3.00
 */
class post_express
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */

    /**
     * 配置信息
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
    public function post_express($cfg=array())
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
            $fee    = $this->configure['base_fee'];
            $this->configure['fee_compute_mode'] = !empty($this->configure['fee_compute_mode']) ? $this->configure['fee_compute_mode'] : 'by_weight';

            if ($this->configure['fee_compute_mode'] == 'by_number') {
                $fee = $goods_number * $this->configure['item_fee'];
            } else {
                if ($goods_weight > 5) {
                    $fee += 8 * $this->configure['step_fee'];
                    $fee += (ceil(($goods_weight - 5) / 0.5)) * $this->configure['step_fee1'];
                } else {
                    if ($goods_weight > 1) {
                        $fee += (ceil(($goods_weight - 1) / 0.5)) * $this->configure['step_fee'];
                    }
                }
            }


            return $fee;
        }
    }

    /**
     * 查询发货状态
     * 该配送方式不支持查询发货状态
     *
     * @access  public
     * @param   string  $invoice_sn     发货单号
     * @return  string
     */
    public function query($invoice_sn)
    {
        $url = 'http://m.kuaidi100.com/query?type=youzhengguonei&id=1&postid=' .$invoice_sn. '&temp='.time();
        return $url;
    }

    public function third_party($invoice_sn)
    {
        $url = 'http://m.kuaidi100.com/index_all.html?type=youzhengguonei&postid=' .$invoice_sn;
        return $url;
    }

    /**
     *  当保价比例以%出现时，计算保价费用
     *
     * @access  public
     * @param   decimal $tatal_price  需要保价的商品总价
     * @param   decimal $insure_rate  保价计算比例
     *
     * @return  decimal $price        保价费用
     */
    public function calculate_insure($total_price, $insure_rate)
    {
        $total_price = ceil($total_price);
        $price = $total_price * $insure_rate;
        if ($price < 1) {
            $price = 1;
        }
        return ceil($price);
    }
}
