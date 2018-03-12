<?php

/**
 *  圆通速递插件
 */

$shipping_lang = BASE_PATH.'languages/' .C('lang'). '/shipping/yto.php';
if (file_exists($shipping_lang)) {
    global $_LANG;
    include_once($shipping_lang);
}


/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == true) {
    include_once(BASE_PATH . 'languages/' . C('lang') . '/admin/shipping.php');

    $i = (isset($modules)) ? count($modules) : 0;

    /* 配送方式插件的代码必须和文件名保持一致 */
    $modules[$i]['code']    = 'yto';

    $modules[$i]['version'] = '1.0.0';

    /* 配送方式的描述 */
    $modules[$i]['desc']    = 'yto_desc';

    /* 不支持保价 */
    $modules[$i]['insure']  = false;

    /* 配送方式是否支持货到付款 */
    $modules[$i]['cod']     = true;

    /* 插件的作者 */
    $modules[$i]['author']  = 'ECTouch Team';

    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ectouch.cn';

    /* 配送接口需要的参数 */
    $modules[$i]['configure'] = array(
                                    array('name' => 'item_fee',     'value'=>10),   /* 单件商品的配送价格 */
                                    array('name' => 'base_fee',    'value'=>5),    /* 1000克以内的价格 */
                                    array('name' => 'step_fee',     'value'=>5),    /* 续重每1000克增加的价格 */
                                );

    /* 模式编辑器 */
    $modules[$i]['print_model'] = 2;

    /* 打印单背景 */
    $modules[$i]['print_bg'] = '/images/receipt/dly_yto.jpg';

    /* 打印快递单标签位置信息 */
    $modules[$i]['config_lable'] = 't_shop_province,' . $_LANG['lable_box']['shop_province'] . ',132,24,279.6,105.7,b_shop_province||,||t_shop_name,' . $_LANG['lable_box']['shop_name'] . ',268,29,142.95,133.85,b_shop_name||,||t_shop_address,' . $_LANG['lable_box']['shop_address'] . ',346,40,67.3,199.95,b_shop_address||,||t_shop_city,' . $_LANG['lable_box']['shop_city'] . ',64,35,223.8,163.95,b_shop_city||,||t_shop_district,' . $_LANG['lable_box']['shop_district'] . ',56,35,314.9,164.25,b_shop_district||,||t_pigeon,' . $_LANG['lable_box']['pigeon'] . ',21,21,143.1,263.2,b_pigeon||,||t_customer_name,' . $_LANG['lable_box']['customer_name'] . ',89,25,488.65,121.05,b_customer_name||,||t_customer_tel,' . $_LANG['lable_box']['customer_tel'] . ',136,21,656,110.6,b_customer_tel||,||t_customer_mobel,' . $_LANG['lable_box']['customer_mobel'] . ',137,21,655.6,132.8,b_customer_mobel||,||t_customer_province,' . $_LANG['lable_box']['customer_province'] . ',115,24,480.2,173.5,b_customer_province||,||t_customer_city,' . $_LANG['lable_box']['customer_city'] . ',60,27,609.3,172.5,b_customer_city||,||t_customer_district,' . $_LANG['lable_box']['customer_district'] . ',58,28,696.8,173.25,b_customer_district||,||t_customer_post,' . $_LANG['lable_box']['customer_post'] . ',93,21,701.1,240.25,b_customer_post||,||';

    return;
}

class yto
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
    public function __construct($cfg = array())
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
     * @param   float   $goods_number   商品件数
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

            return $fee;
        }
    }


    /**
     * 查询发货状态
     *
     * @access  public
     * @param   string  $invoice_sn     发货单号
     * @return  string
     */
    public function query($invoice_sn)
    {
        //圆通快递查询会判断链接来源，目前的查询无法生效。
        $str = 'http://m.kuaidi100.com/index_all.html?type=yuantong&postid=' .$invoice_sn;
        return $str;
    }
}
