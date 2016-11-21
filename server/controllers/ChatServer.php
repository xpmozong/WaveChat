<?php
use Swoole\Filter;
class ChatServer extends Swoole\Protocol\CometServer
{
    /**
     * @var Store\File;
     */
    protected $store;
    protected $users;

    const MESSAGE_MAX_LEN     = 1024; //单条消息不得超过1K
    const WORKER_HISTORY_ID   = 0;

    function __construct($config = array())
    {
        //将配置写入config.js
        $config_js = <<<HTML
var wavechat = {
    'server' : '{$config['server']['url']}'
}
HTML;
        file_put_contents(WEBPATH . '/client/config.js', $config_js);

        //检测日志目录是否存在
        $log_dir = dirname($config['wavechat']['log_file']);
        if (!is_dir($log_dir))
        {
            mkdir($log_dir, 0777, true);
        }
        if (!empty($config['wavechat']['log_file']))
        {
            $logger = new Swoole\Log\FileLog($config['wavechat']['log_file']);
        }
        else
        {
            $logger = new Swoole\Log\EchoLog;
        }
        $this->setLogger($logger);   //Logger

        /**
         * 使用文件或redis存储聊天信息
         */
        $this->setStore();
        $this->origin = $config['server']['origin'];
        
        parent::__construct($config);
    }

    function setStore()
    {
        $this->store = new Users();
    }

    /**
     * 下线时，通知所有人
     */
    function onExit($client_id)
    {
        $userInfo = $this->store->getUser($client_id);
        if ($userInfo)
        {
            $resMsg = array(
                'cmd' => 'offline',
                'fd' => $client_id,
                'from' => $client_id,
                'channal' => 0,
                'content' => $userInfo['username'] . "下线了",
            );
            $this->store->logout($client_id);
            //将下线消息发送给所有人
            $this->broadcastJson($client_id, $resMsg);
        }
        $this->log("onOffline: " . $client_id);
    }

    function onTask($serv, $task_id, $from_id, $data)
    {
        $req = unserialize($data);
        if ($req)
        {
            switch($req['cmd'])
            {
                case 'getHistory':
                    $history = array('cmd'=> 'getHistory', 'history' => $this->store->getHistory($req['fd'], $req['to'], $req['channal'], $req['offset'], $req['pagesize']));
                    if ($this->isCometClient($req['fd']))
                    {
                        return $req['fd'].json_encode($history);
                    }
                    //WebSocket客户端可以task中直接发送
                    else
                    {
                        $this->sendJson(intval($req['fd']), $history);
                    }
                    break;
                case 'addHistory':
                    if (empty($req['content']))
                    {
                        $req['content'] = '';
                    }
                    $this->store->addHistory($req['fd'], $req['to'], $req['channal'], $req['type'], $req['content']);
                    break;
                default:
                    break;
            }
        }
    }

    function onFinish($serv, $task_id, $data)
    {
        $this->send(substr($data, 0, 32), substr($data, 32));
    }

    /**
     * 获取在线列表
     */
    function cmd_getOnline($client_id, $msg)
    {
        $resMsg = array(
            'cmd' => 'getOnline',
        );
        $info = $this->store->getUsers(array_slice($this->users, 0, 100));
        $resMsg['users'] = $users;
        $resMsg['list'] = $info;
        $this->sendJson($client_id, $resMsg);
    }

    /**
     * 获取历史聊天记录
     */
    function cmd_getHistory($client_id, $msg)
    {
        $task['fd'] = $client_id;
        $task['to'] = isset($msg['to']) ? $msg['to'] : 0;
        $task['cmd'] = 'getHistory';
        $task['channal'] = isset($msg['channal']) ? $msg['channal'] : 0;
        $page = 1;
        $pageSize = 20;
        $offset = ($page - 1) * $pageSize;
        $task['offset'] = $offset;
        $task['pagesize'] = $pageSize;
        //在task worker中会直接发送给客户端
        $this->getSwooleServer()->task(serialize($task), self::WORKER_HISTORY_ID);
    }

    /**
     * 登录
     * @param $client_id
     * @param $msg
     */
    function cmd_login($client_id, $msg)
    {
        $info['user_id'] = Filter::escape($msg['user_id']);
        $info['username'] = Filter::escape($msg['username']);
        $info['avatar'] = Filter::escape($msg['avatar']);
        $info['chat_key'] = Filter::escape($msg['chat_key']);

        if (md5(Wave::app()->config['chat_key'].$info['username'].$info['user_id']) === $info['chat_key']) {
            $count = $this->store->checkUsername($info['username']);
            if ($count > 0) {
                // 回复给登录用户
                $resMsg = array(
                    'cmd' => 'login',
                    'fd' => $client_id,
                    'user_id' => $info['user_id'],
                    'username' => $info['username'],
                    'avatar' => $info['avatar'],
                );

                //把会话存起来
                $this->users[] = $client_id;

                $this->store->login($client_id, $resMsg);
                $this->sendJson($client_id, $resMsg);

                //广播给其它在线用户
                $resMsg['cmd'] = 'newUser';
                //将上线消息发送给所有人
                $this->broadcastJson($client_id, $resMsg);
                //用户登录消息
                $loginMsg = array(
                    'cmd' => 'fromMsg',
                    'from' => $client_id,
                    'channal' => 0,
                    'content' => $info['username'] . "上线了",
                );
                $this->broadcastJson($client_id, $loginMsg);
            }else{
                // 回复给登录用户
                $resMsg = array(
                    'cmd' => 'login_error',
                    'fd' => $client_id,
                    'content' => '用户不存在'
                );
                $this->sendJson($client_id, $resMsg);
            }
        }else{
            // 回复给登录用户
            $resMsg = array(
                'cmd' => 'login_error',
                'fd' => $client_id,
                'content' => '登录出错'
            );
            $this->sendJson($client_id, $resMsg);
        }
    }

    /**
     * 发送信息请求
     */
    function cmd_message($client_id, $msg)
    {
        $resMsg = $msg;
        $resMsg['cmd'] = 'fromMsg';

        if (strlen($msg['content']) > self::MESSAGE_MAX_LEN)
        {
            $this->sendErrorMessage($client_id, 102, 'message max length is '.self::MESSAGE_MAX_LEN);
            return;
        }

        //表示群发
        if ($msg['channal'] == 0)
        {
            $this->broadcastJson($client_id, $resMsg);
            $this->getSwooleServer()->task(serialize(array(
                'cmd' => 'addHistory',
                'fd' => $client_id,
                'to' => 0,
                'channal' => $msg['channal'],
                'type' => $msg['type'],
                'content' => $msg['content']
            )), self::WORKER_HISTORY_ID);
        }
        //表示私聊
        elseif ($msg['channal'] == 1)
        {
            $this->sendJson($msg['to'], $resMsg);
            $this->store->addHistory($client_id, $msg['to'], $msg['channal'], $msg['type'], $msg['content']);
        }
    }

    /**
     * 接收到消息时
     * @see WSProtocol::onMessage()
     */
    function onMessage($client_id, $ws)
    {
        $this->log("onMessage #$client_id: " . $ws['message']);
        $msg = json_decode($ws['message'], true);
        if (empty($msg['cmd']))
        {
            $this->sendErrorMessage($client_id, 101, "invalid command");
            return;
        }
        $func = 'cmd_'.$msg['cmd'];
        if (method_exists($this, $func))
        {
            $this->$func($client_id, $msg);
        }
        else
        {
            $this->sendErrorMessage($client_id, 102, "command $func no support.");
            return;
        }
    }

    /**
     * 发送错误信息
    * @param $client_id
    * @param $code
    * @param $msg
     */
    function sendErrorMessage($client_id, $code, $msg)
    {
        $this->sendJson($client_id, array('cmd' => 'error', 'code' => $code, 'msg' => $msg));
    }

    /**
     * 发送JSON数据
     * @param $client_id
     * @param $array
     */
    function sendJson($client_id, $array)
    {
        $msg = json_encode($array);
        if ($this->send($client_id, $msg) === false)
        {
            $this->close($client_id);
        }
    }

    /**
     * 广播JSON数据
     * @param $client_id
     * @param $array
     */
    function broadcastJson($sesion_id, $array)
    {
        $msg = json_encode($array);
        $this->broadcast($sesion_id, $msg);
    }

    function broadcast($current_session_id, $msg)
    {
        foreach ($this->users as $key => $client_id)
        {
            if ($current_session_id != $client_id)
            {
                $this->send($client_id, $msg);
            }
        }
    }
}