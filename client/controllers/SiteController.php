<?php
/**
 * 网站默认入口控制层
 */
class SiteController extends Controller
{
       
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 默认函数
     */
    public function actionIndex()
    {
        $this->userinfo = Wave::app()->session->getState('userinfo');
        if(empty($this->userinfo)){
            $this->redirect(Wave::app()->homeUrl.'site/login');
        }
        // $this->userinfo['username'] = 111;
        // $this->userinfo['avatar'] = 'ddddd';

        $this->chat_key = md5(Wave::app()->config['chat_key'].$this->userinfo['username'].$this->userinfo['user_id']);
    }

    /**
     * 校验用户名
     */
    public function actionCheckUsername()
    {
        $username = $this->getRequest()->getAddslashes('username');
        $usersModel = new Users();
        $count = $usersModel->checkUsername($username);
        WaveCommon::exportResult($count, 'success');
    }

    /**
     * 登录
     */
    public function actionLogin()
    {
        $userinfo = Wave::app()->session->getState('userinfo');
        if(!empty($userinfo)){
            $this->redirect(Wave::app()->homeUrl);
        }
        if ($this->getRequest()->isPost()) {
            $this->error_msg = '';
            $this->data = WaveCommon::getFilter($_POST);
            $usersModel = new Users();
            $array = $usersModel->getOne('*', array('username'=>$this->data['username']));
            if(!empty($array)){
                if ($array['password'] == md5($this->data['password'])) {
                    unset($this->data['password']);
                    Wave::app()->session->setState('userinfo', $array);
                    $this->jumpBox('登录成功！', Wave::app()->homeUrl, 1);
                }else{
                    $this->error_msg = '用户名或密码错误！';
                }
            }else{
                $this->error_msg = '没有该用户！';
            }
        }
    }

    /**
     * 退出
     */
    public function actionLogout()
    {
        Wave::app()->session->logout('userinfo');
        $this->jumpBox('退出成功！', Wave::app()->homeUrl, 1);
    }

    /**
     * 注册
     */
    public function actionRegist()
    {
        if ($this->getRequest()->isPost()) {
            $this->error_msg = '';
            $this->data = WaveCommon::getFilter($_POST);
            $usersModel = new Users();
            $count = $usersModel->checkUsername($this->data['username']);
            if ($count > 0) {
                $this->error_msg = '用户已存在';
            }else{
                if (empty($this->data['username']) || empty($this->data['password']) || empty($this->data['avatar'])) {
                    $this->jumpBox('参数不全', Wave::app()->homeUrl.'site/regist', 1);
                }
                $this->data['add_time'] = time();
                $this->data['password'] = md5($this->data['password']);
                $id = $usersModel->insert($this->data);
                if ($id) {
                    $msg = '注册成功！';
                }else{
                    $msg = '注册失败！';
                }
                $this->jumpBox($msg, Wave::app()->homeUrl, 1);
            }
        }
    }

    /**
     * 上传图片
     */
    public function actionUpload()
    {
        $month = WaveCommon::getYearMonth();
        $projectPath = Wave::app()->projectPath;
        $saveDir = 'data/img/'.$month;
        $uploadConfig = array();
        $uploadConfig['mimes'] = WaveCommon::getImageTypes();
        $uploadConfig['savePath'] = $projectPath.$saveDir;
        $uploadConfig['maxSize'] = 1024*1024*5;
        $uploadConfig['exts'] = array('jpg', 'gif', 'png', 'jpeg');
        $uploadConfig['isFormerName'] = false; // 是否保存原文件名 默认为false
        $waveUpload = new WaveUpload($uploadConfig);
        $updateInfo = $waveUpload->upload($_FILES);
        if (!empty($updateInfo)) {
            $code = 1;
            $imgurl = $saveDir.'/'.$updateInfo['file_data']['savename'];
            $msg = 'http://'.$this->hostInfo.'/'.$imgurl;
            
            
        }else{
            $code = 0;
            $msg = $waveUpload->getError();
        }

        WaveCommon::exportResult($code, $msg);
    }

}

?>