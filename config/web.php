<?php

$params = require(__DIR__ . '/app.php');
$routes_web = require(dirname(__DIR__) . '/routes/web.php');
$routes_api = require(dirname(__DIR__) . '/routes/api.php');
$routes = array_merge($routes_web, $routes_api);

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__) . '/app',
    'viewPath' => dirname(__DIR__) . '/resources/views/default',
    'runtimePath' => dirname(__DIR__) . '/storage/framework',
    'vendorPath' => dirname(__DIR__) . '/vendor',
    'language' => 'zh-CN',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\http\controllers',
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'test',
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => dirname(__DIR__) . '/storage/logs/app.log'
                ],
            ],
        ],
        'db' => require(__DIR__ . '/database.php'),
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => $routes,
        ],
    ],
    'modules' => require(__DIR__ . '/module.php'),
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
