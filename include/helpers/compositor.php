<?php

/**
 * 支付插件排序文件
 */

if (isset($modules)) {
    foreach ($modules as $k =>$v) {
        if ($v['pay_code'] == 'epay') {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }

    foreach ($modules as $k =>$v) {
        if ($v['pay_code'] == 'tenpay') {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }

    foreach ($modules as $k =>$v) {
        if ($v['pay_code'] == 'syl') {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }

    foreach ($modules as $k =>$v) {
        if ($v['pay_code'] == 'alipay') {
            $tenpay = $modules[$k];
            unset($modules[$k]);
            array_unshift($modules, $tenpay);
        }
    }
}
