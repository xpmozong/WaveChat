<?php
$config = array(
    'projectName'           => 'client',
    'modelName'             => 'server',
    'projectTitle'          => 'Wave聊天',
    'defaultController'     => 'site',

    'smarty'                => array(
        'is_on'             => true,    // 是否使用smarty模板
        'left_delimiter'    => '{%',
        'right_delimiter'   => '%}',
        'debugging'         => false,
        'caching'           => false,
        'cache_lifetime'    => 120,
        'compile_check'     => true,
        'template_dir'      => 'templates',
        'config_dir'        => 'templates/config',
        'cache_dir'         => 'data/templates/cache/index',
        'compile_dir'       => 'data/templates/compile/index'
    ),
    
    'debuger'               => false,       // 显示debug信息
    'crash_show_sql'        => true,
    'write_log_dir'         => ROOT_PATH.'/logs/',

    'write_sql_log'         => false,
    'write_sql_dir'         => ROOT_PATH.'/logs/',

    'ini_set'               => array(
        'upload_max_filesize'       => '5M',
        'post_max_size'             => '10M',        
        'memory_limit'              => '256M',
        'session.cache_expire'      => '',
        'session.use_cookies'       => 1,
        'session.auto_start'        => 0,
        'session.cookie_lifetime'   => 86400,
        'session.gc_maxlifetime'    => 86400,
        'display_errors'            => 1,
        'date.timezone'             => 'Asia/Shanghai',
        'max_execution_time'        => 3600
    ),

    'database'              => array(
        'driver'            => 'wmysqli',
        'master'            => array(
            'dbhost'        => '42.51.161.239',
            'dbport'        => 3306,
            'username'      => 'gongsilian',
            'password'      => 'gongsilian',
            'dbname'        => 'gsim',
            'charset'       => 'utf8',
            'table_prefix'  => 'k_',
            'pconnect'      => false
        )
    ),

    'session'               => array(
        'driver'            => 'redis',
        'timeout'           => 86400
    ),

    'session_redis'         => array(
        'master'            => array(
            'host'          => '42.51.161.239',
            'port'          => 6379
        ),
        'slave'             => array(
            array(
                'host'      => '42.51.161.239',
                'port'      => 6379
            )
        )
    ),

    'redis'                 => array(
        'master'            => array(
            'host'          => '42.51.161.239',
            'port'          => 6379
        ),
        'slave'             => array(
            array(
                'host'      => '42.51.161.239',
                'port'      => 6379
            )
        )
    ),

    'chat_key'              => 'wavechat_key_!@#',
);
?>
