<?php
$config['server'] = array(
    //监听的HOST
    'host'   => '0.0.0.0',
    //监听的端口
    'port'   => '9503',
    //WebSocket的URL地址，供浏览器使用的
    'url'    => 'ws://42.51.161.239:9503',
    //用于Comet跨域，必须设置为html所在的URL
    'origin' => 'http://42.51.161.239:8899',
);

$config['swoole'] = array(
    'log_file'        => ROOT_PATH . '/logs/swoole_'.date('Y-m-d').'.log',
    'worker_num'      => 1,
    //不要修改这里
    'max_request'     => 0,
    'task_worker_num' => 1,
    //是否要作为守护进程
    'daemonize'       => 0,
);

$config['wavechat'] = array(
    //聊天记录存储的目录
    'data_dir' => ROOT_PATH . '/data/',
    'log_file' => ROOT_PATH . '/logs/wavechat_'.date('Y-m-d').'.log',
);

return $config;