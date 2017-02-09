<?php

namespace app\assets;

use yii\web\AssetBundle;

class AuthAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        'css/admin/app.css',
    ];
    public $js = [
        'js/admin/app.js',
    ];
}
