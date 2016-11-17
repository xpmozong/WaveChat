<?php
/**
 * 用户表
 */
class Users extends Model
{
    static $prefix = "wavechat2_";

    protected function init() {
        $this->_tableName = $this->getTablePrefix().'users';
        $this->redis = Wave::app()->redis;
    }

    /**
     * 用户名是否重复
     */
    public function checkUsername($username) 
    {
        return $this->getCount('*', array('username'=>$username));
    }

    /**
     * 登录，将用户信息存到缓存
     */
    public function login($client_id, $info)
    {
        $this->redis->set(self::$prefix.'client_'.$client_id, serialize($info));
        $this->redis->sadd(self::$prefix.'online', $client_id);
    }

    /**
     * 退出，删除缓存
     */
    public function logout($client_id)
    {
        $this->redis->delete(self::$prefix.'client_'.$client_id);
        $this->redis->sremove(self::$prefix.'online', $client_id);
    }

    /**
     * 获得在线用户
     */
    public function getOnlineUsers()
    {
        return $this->redis->smembers(self::$prefix.'online');
    }

    /**
     * 读缓存列表用户信息
     */
    public function getUsers($users)
    {
        $keys = array();
        $ret = array();

        foreach($users as $v)
        {
            $keys[] = self::$prefix.'client_'.$v;
        }

        $info = $this->redis->get($keys);
        foreach($info as $v)
        {
            $ret[] = unserialize($v);
        }
        return $ret;
    }

    /**
     * 读缓存，获取用户信息
     */
    public function getUser($client_id)
    {
        $ret = $this->redis->get(self::$prefix.'client_'.$client_id);
        $info = unserialize($ret);

        return $info;
    }

    /**
     * 读缓存，获取用户ID
     */
    public function getUserid($client_id)
    {
        $ret = $this->redis->get(self::$prefix.'client_'.$client_id);
        $info = unserialize($ret);

        return $info['user_id'];
    }

    /**
     * 存储消息记录
     */
    public function addHistory($client_id, $to_client_id, $channal, $type, $content)
    {
        $from_id = $this->getUserid($client_id);
        $to_id = 0;
        if ($to_client_id) {
            $to_id = $this->getUserid($to_client_id);
        }
        $data = array('from_id' => $from_id,
                    'to_id'     => $to_id,
                    'channal'   => $channal,
                    'type'      => $type,
                    'content'   => $content, 
                    'add_time'  => time());

        return $this->from('k_messages')->insert($data);
    }

    /**
     * 获得历史消息记录
     */
    public function getHistory($client_id, $to_client_id, $channal, $offset = 0, $pagesize = 20)
    {
        $channal = (int)$channal;
        $where1 = $where2 = array();
        $where1['channal'] = $channal;
        $info = $this->getUser($client_id);
        $from = $info['user_id'];
        $where1['from_id'] = $from;
        if ($channal != 0) {
            $to = $this->getUserid($to_client_id);
            $where1['to_id'] = $to;
            $where2['from_id'] = $to;
            $where2['to_id'] = $from;
        }

        $history = $this->from('k_messages')->where($where1)->where($where2, 'OR')->limit($offset, $pagesize)->getAll();
        foreach ($history as $key => $value) {
            $history[$key]['username'] = $info['username'];
            $history[$key]['avatar'] = $info['avatar'];
            $history[$key]['time'] = $value['add_time'];
            if ($value['channal'] != 0) {
                $history[$key]['from'] = $to_client_id;
            }else{
                $history[$key]['from'] = $client_id;
            }
        }

        return $history;
    }



}

?>