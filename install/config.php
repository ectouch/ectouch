<?php
return array(
    /* ------系统------ */
    //系统名称
    'name'=>'ECTouch',
    //系统版本
    'version'=>'2.0',
    //系统powered
    'powered'=>'Powered by ECTouch',
    //系统脚部信息
    'footerInfo'=>'Copyright &copy; '. date('Y') .' ECTouch.cn. All Rights Reserved.',
    /* ------站点------ */
    //数据库文件
    'sqlFileName'=>array('structure.sql', 'data.sql', 'mobile.sql', 'wechat.sql'),
    //数据库配置文件
    'dbConfig'=>'data/database.php',
    //数据库名
    'dbName' => 'ectouch_db',
    //数据库表前缀
    'dbPrefix' => 'ecs_',
    //站点名称
    'siteName' => 'ECTouch微商城',
    //站点关键字
    'siteKeywords' => 'ECTouch微商城',
    //站点描述
    'siteDescription' => 'ECTouch微商城',
    //需要读写权限的目录
    'dirAccess' => array(
        '/',
        '/data',
        '/data/attached',
        '/data/backup',
        '/data/caches',
        '/data/certificate',
        '/data/migrates',
        '/data/session',
        '/themes',
    ),
    /* ------写入数据库完成后处理的文件------ */
    'handleFile' => 'main.php',
    /* ------生成数据库配置文件的模板------ */
    'dbSetFile'=> 'config.ini',
    /* ------安装验证/生成文件;非云平台安装有效------ */
    'installFile' => '../data/install.lock',
    'alreadyInstallInfo' => '你已经安装过该系统，如果想重新安装，请先删除站点data目录下的 install.lock 文件，然后再尝试安装！',
);
