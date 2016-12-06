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
