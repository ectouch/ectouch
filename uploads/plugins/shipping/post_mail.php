<?php

/* 访问控制 */
defined('IN_ECTOUCH') or die('Deny Access');

$shipping_lang = ROOT_PATH . 'plugins/shipping/language/' . C('lang') . '/' . basename(__FILE__);
if (file_exists($shipping_lang))
{
    global $_LANG;
    include_once($shipping_lang);
}

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $i = (isset($modules)) ? count($modules) : 0;

    /* 配送方式插件的代码必须和文件名保持一致 */
    $modules[$i]['code']    = basename(__FILE__, '.php');

    $modules[$i]['version'] = '1.0.0';

    /* 配送方式的描述 */
    $modules[$i]['desc']    = 'post_mail_desc';

    /* 配送方式是否支持货到付款 */
    $modules[$i]['cod']     = false;

    /* 插件的作者 */
    $modules[$i]['author']  = 'ECSHOP TEAM';

    /* 插件作者的官方网站 */
    $modules[$i]['website'] = 'http://www.ecshop.com';

    /* 配送接口需要的参数 */
    $modules[$i]['configure'] = array(
                                    array('name' => 'item_fee',          'value'=>4),
                                    array('name' => 'base_fee',          'value'=>3.5),
                                    array('name' => 'step_fee',          'value'=>2),
                                    array('name' => 'step_fee1',          'value'=>2.5),
                                    array('name' => 'pack_fee',           'value'=>0),
                                );

    /* 模式编辑器 */
    $modules[$i]['print_model'] = 2;

    /* 打印单背景 */
    $modules[$i]['print_bg'] = '';

   /* 打印快递单标签位置信息 */
    $modules[$i]['config_lable'] = '';

    return;
}

/**
 * 邮局平邮费用计算方式: 每公斤资费 × 包裹重量 + 挂号费3.00 + 邮单费0.5 + 包装费(按实际收取) ＋ 保价费
 *
 * 保价费 由客户自愿选择，保价费为订单产品价值的1％。客户选择不保价，则保价费＝0
 *
 */
class post_mail
{
    /*------------------------------------------------------ */
    //-- PUBLIC ATTRIBUTEs
    /*------------------------------------------------------ */

    /**
     * 配置信息
     */
    var $configure;

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
    function post_mail($cfg=array())
    {
        foreach ($cfg AS $key=>$val)
        {
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
    function calculate($goods_weight, $goods_amount, $goods_number)
    {
        if ($this->configure['free_money'] > 0 && $goods_amount >= $this->configure['free_money'])
        {
            return 0;
        }
        else
        {
            /* 基本费用 */
            $fee = $this->configure['base_fee'] + $this->configure['pack_fee'];
            $this->configure['fee_compute_mode'] = !empty($this->configure['fee_compute_mode']) ? $this->configure['fee_compute_mode'] : 'by_weight';

            if ($this->configure['fee_compute_mode'] == 'by_number')
            {
                $fee = $goods_number * ($this->configure['item_fee'] + $this->configure['pack_fee']);
            }
            else
            {
                if ($goods_weight > 5)
                {
                    $fee += 4 * $this->configure['step_fee'];
                    $fee += (ceil(($goods_weight - 5))) * $this->configure['step_fee1'];
                }
                else
                {
                    if ($goods_weight > 1)
                    {
                        $fee += (ceil(($goods_weight - 1))) * $this->configure['step_fee'];
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
    function query($invoice_sn)
    {
        return $invoice_sn;
    }
    
    /**
     * 返回快递100查询链接 by wang 
     * URL：https://code.google.com/p/kuaidi-api/wiki/Open_API_Chaxun_URL
     */
    function kuaidi100($invoice_sn){
        $url = 'http://m.kuaidi100.com/query?type=youzhengguonei&id=1&postid=' .$invoice_sn. '&temp='.time();
        return $url;
    }
}

?>