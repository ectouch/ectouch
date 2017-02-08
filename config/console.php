<?php
Yii::setAlias('@app/migrations', dirname(__DIR__) . '/database/migrations');

$params = require(__DIR__ . '/app.php');
$db = require(__DIR__ . '/database.php');

$config = [
    'id' => 'console',
    'basePath' => dirname(__DIR__) . '/app',
    'runtimePath' => dirname(__DIR__) . '/storage/framework',
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\console',
    'components' => [
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logFile' => dirname(__DIR__) . '/storage/logs/app.log'
                ],
            ],
        ],
        'db' => $db,
    ],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
    ];
}

return $config;
