<?php
header('Content-Type:text/html;charset=utf-8');
// error_reporting(0);

define('ROOT_PATH', dirname(__FILE__));
define('DEBUG', 'on');
define('WEBPATH', __DIR__);

require ROOT_PATH.'/vendor/autoload.php';

require ROOT_PATH.'/environment.php';
$envConfig = 'product';
if ($environment === 2) {
    $envConfig = 'local_dev';
}elseif ($environment === 3) {
    $envConfig = 'test_dev';
}

$configfile = ROOT_PATH.'/server/config/'.$envConfig.'/main.php';
$wave = new Wave($configfile);

/**
 * Swoole框架自动载入器初始化
 */
Swoole\Loader::vendor_init();

$serverConfig = require ROOT_PATH.'/server/config/'.$envConfig.'/server_config.php';
$wavechat = new ChatServer($serverConfig);
$wavechat->loadSetting(__DIR__."/swoole.ini"); //加载配置文件

/**
 * 必须使用swoole扩展
 */
$server = new Swoole\Network\Server($serverConfig['server']['host'], $serverConfig['server']['port']);
$server->setProtocol($wavechat);
$server->run($serverConfig['swoole']);

