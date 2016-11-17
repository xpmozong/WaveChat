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
$wave->run();

