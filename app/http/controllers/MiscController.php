<?php

namespace app\http\controllers;

use Yii;
use yii\web\Controller;

class MiscController extends Controller
{

    // 验证码
    public function captcha() {
        Image::buildImageVerify();
    }

    // 地区筛选
    public function region() {
        $type = I('request.type', 0, 'intval');
        $parent = I('request.parent', 0, 'intval');

        $arr['regions'] = model('RegionBase')->get_regions($type, $parent);
        $arr['type'] = $type;
        $arr['target'] = I('request.target', '', 'trim,stripslashes');
        $arr['target'] = htmlspecialchars($arr['target']);

        echo json_encode($arr);
    }

}
