# WaveChat
swoole 网页聊天，只要是支持WebSocket的浏览器，都可以测试，参照webim。

### 数据库

**用户表**

    CREATE TABLE `k_users` (
        `user_id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(100) DEFAULT NULL,
        `avatar` varchar(100) DEFAULT NULL,
        `password` varchar(32) DEFAULT NULL,
        `add_time` bigint(15) DEFAULT NULL,
        PRIMARY KEY (`user_id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=100001 DEFAULT CHARSET=utf8;

**消息表**

    CREATE TABLE `k_messages` (
        `id` bigint(20) NOT NULL AUTO_INCREMENT,
        `from_id` bigint(20) DEFAULT NULL,
        `to_id` bigint(20) DEFAULT NULL,
        `channal` bigint(20) DEFAULT NULL,
        `type` varchar(10) DEFAULT NULL,
        `content` text,
        `add_time` bigint(15) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
    
### 配置文件

区别本地，测试，线上环境配置，根据environment.php来区别

具体配置文件在wavechat\server\config\local_dev\main.php

### 运行

首先到当前目录执行 composer update，将wavephp2框架和swoole框架下载下来

**服务器端**

执行

    php server.php

永久执行，创建一个shell文件

    #!/bin/bash
    bigdir=/data/www/wwwroot/wavechat
    file=server.php
    filepath=$bigdir/server/$file
    logfile=$bigdir/logs/server.log

    case $1 in
        start)
            nohup php $filepath > $logfile &
            echo "服务已启动..."
            sleep 1
        ;;
        stop)
            for i in `ps -ef |grep $file|awk '{print $2}'`
                do
                    kill -9 $i > /dev/null 2>&1
                done
            echo "服务已停止..."
            sleep 1
        ;;
        restart)
            for i in `ps -ef |grep $file|awk '{print $2}'`
                do
                    kill -9 $i > /dev/null 2>&1
                done
            echo "服务已停止..."
            sleep 1
            
            nohup php $filepath > $logfile &

            echo "服务已重启..."
            sleep 1
        ;;
        *)
            echo "$0 {start|stop|restart}"
            exit 4
        ;;
    esac

所以用shell启动php文件，让php文件后端执行

    # 启动
    sh server.sh start
    # 重启
    sh server.sh restart
    # 关闭
    sh server.sh stop

记得要将目录设为可写权限哦

**客户端**

nginx配置

    server {
        listen       80;
        server_name  127.0.0.1;
        index index.php index.html index.htm;
        root /data/www/wwwroot/swoole/wavechat;
    
        access_log /data/logs/gsim.com-access_log main;
        error_log /data/logs/gsim.com-error_log;
    
        # redirect server error pages to the static page /50x.html
        error_page   500 502 503 504  /50x.html;
        location = /50x.html {
            root   html;
        }
    
        location ~ .*\.(gif|jpg|jpeg|png|bmp|swf)$
        {
            expires 30d;
        }
    
        location ~ .*\.(js|css)?$
        {
            expires 24h;
        }
    
        if ($request_filename !~* (\.xml|\.rar|\.html|\.htm|\.php|\.swf|\.css|\.js|\.gif|\.png|\.jpg|\.jpeg|robots\.txt|index\.php|\.jnlp|\.jar|\.eot|\.woff|\.ttf|\.svg)) {
            rewrite ^/(.*)$ /index.php/$1 last;
        }
    
        location ~ .*\.php {
            fastcgi_pass   127.0.0.1:9000;
            fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
            fastcgi_index  index.php;
            fastcgi_split_path_info ^(.+\.php)(.*)$;
            fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param   PATH_INFO $fastcgi_path_info;
            fastcgi_param   PATH_TRANSLATED $document_root$fastcgi_path_info;
            include fastcgi_params;
        }
    }


在浏览器打开 http://127.0.0.1/

登录

![ScreenShot](https://raw.github.com/xpmozong/WaveChat/master/login.png)

注册

![ScreenShot](https://raw.github.com/xpmozong/WaveChat/master/regist.png)

聊天
![ScreenShot](https://raw.github.com/xpmozong/WaveChat/master/chat.png)