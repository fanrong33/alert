<?php
/**
 * 通行证 控制器类
 * @author 蔡繁荣
 * @version 1.0.10 build 20171018
 */
class PassportAction extends Action{

    /**
     * 注册帐号
     */
    public function register(){
        $model = D('User');

        if($this->isPost()){

            $_validate = array(
                array('username' , 'require', '用户名为空', 1),
                array('password' , 'require', '密码为空', 1),
                array('email'    , 'require', '邮箱为空', 1),
                array('username' , '/^([a-zA-Z]){1}([a-zA-Z0-9_\-]){5,17}$/', '用户名格式错误', 1),
                array('username' , ''       , '用户名已存在', 1, 'unique'),
                array('email'    , 'email'  , '邮箱格式错误', 1),
                array('email'    , ''       , '该邮箱已经存在', 1, 'unique'), // in  , between,  length,  等...
                array('password' , '6,20'   , '密码必须为6-20位字符串', 1, 'length'),
                array('confirm_password', 'password', '确认密码与新的密码不一致', 1, 'confirm'),
            );
            // 关闭字段信息的自动检测
            // $model->setProperty('autoCheckFields', false);
            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }
            

            $data['password']    = encrypt_pwd($data['password']);
            $data['update_time'] = time();
            $data['create_time'] = time();

            $user_id = $model->add($data);
            if($user_id){
                
                $url = U('Passport/active_account', array(), true, false, true);
                $url .= '?verify_code='.urlencode(authcode($data['email'],  'ENCODE', 'LW$lgXSyUc*6', 60*30));

                
                // 发送激活账号邮件
                import('@.ORG.Email.EmailApiClient', '', '.php');
                $client = new EmailApiClient(C('EMAIL_APP_KEY'), C('EMAIL_APP_SECRET'));

                $method = 'xiaobai.email.send_email';
                $email_params = array(
                    'name' => $data['username'],
                    'url'  => $url,
                );
                $params = array(
                    'email_address'  => $data['email'],
                    'subject'        => '激活账号 - alert',        // 邮件主题
                    'template_alias' => 'register_user',          // 模板别名
                    'email_params'   => json_encode($email_params),
                );
                $json = $client->post($method, $params);

                
                $this->success('帐号创建成功', U('Passport/register', array('send_success'=>1)));
            }else{
                $this->error('帐号创建失败');
            }
            exit;
        }

        $this->assign('_title', '注册新用户 - alert');
        $this->display();
    }

    /**
     * 邮箱激活账号
     */
    public function active_account(){
        $verify_code = $_GET['verify_code'];

        $email = authcode($verify_code, $operation = 'DECODE', $key = 'LW$lgXSyUc*6');
        if($email){
            $data = array(
                'is_actived'  => 1,
                'update_time' => time()
            );
            $effect = D('User')->where(array('email'=>$email))->save($data);
        }

        $this->assign('email', $email);
        $this->assign('_title', '激活账号 - alert');
        $this->display();
    }

    /**
     * 账号登录
     */
    public function login(){
        $model = D('User');

        if($this->isPost()){

            $username    = $_POST['username'];
            $password    = $_POST['password'];
            $remeber_me  = $_POST['remeber_me'];
            $referer_url = $_POST['referer_url'];

            if($username == ''){
                $this->error('登录名为空');
            }
            if($password == ''){
                $this->error('登录密码为空');
            }

            $cond = array();
            if($model->check($username, 'email', 'regex')){
                $cond['email'] = $username;
            }else{
                $cond['username'] = $username;
            }

            $user = $model->where($cond)->find();
            if(!$user || encrypt_pwd($password) != $user['password']){
                $this->error('登录名或者密码错误');
            }

            if($user['is_actived'] == 0){
                $this->error('请先激活账号');
            }


            session('user', $user);

            // 更新开发者上次登录时间
            $data = array(
                'last_login_time' => time(),
                'update_time'     => time(),
            );
            $model->where(array('id'=>$user['id']))->save($data);

            // 记住我
            if($remeber_me == 1){
                import('@.ORG.Util.RemeberMe');
                RemeberMe::set('remeber_me', $user['id'], 3600*24*10);
            }
            // 第一次登录，跳转到添加应用页面
            $default_url = ($user['last_login_time'] == 0) ? U('App/add') : U('Event/index');
            $url = $referer_url ? $referer_url : $default_url;
            $this->success('登录成功', $url);
        }
        

        // 如果已经登录，则直接跳转到面板首页
        $user = session('user');
        if($user){
            U('Event/index', array(), array(), true);
        }

        // 未登录，则判断remeber_me cookie是否已登录
        import('@.ORG.Util.RemeberMe');
        $user_id = RemeberMe::get('remeber_me');

        if($user_id){
            $user = $model->find($user_id);
            if($user){
                session('user', $user);

                // 更新开发者上次登录时间
                $data = array(
                    'last_login_time' => time(),
                    'updateTime'      => time(),
                );
                $model->where(array('id'=>$user['id']))->save($data);

                U('Dashboard/index', array(), array(), true);
            }
        }

        $this->assign('_title', '账号登录 - alert');
        $this->display();
    }


    /**
     * 忘记密码，发送重置密码邮件
     */
    public function forget(){
        $model = D('User');

        if($this->isPost()){
            $email = trim($this->_post('email'));

            $cond = array(
                'email'      => $email,
                'is_actived' => 1,
                'is_deleted' => 0,
            );
            $item = $model->where($cond)->find();
            if(!$item){
                $this->error('账号不存在');
            }

            // 1、生成验证码
            $verify_code = urlencode(authcode($email, 'ENCODE', 'LW$lgXSyUc*6', 60*30));

            $reset_password_url = U('Passport/reset_password', array(), true, false, true);
            $reset_password_url .= '?verify_code='.$verify_code;

            // 并发送重置密码邮件到广告主邮箱
            import('@.ORG.Email.EmailApiClient', '', '.php');
            $client = new EmailApiClient(C('EMAIL_APP_KEY'), C('EMAIL_APP_SECRET'));

            $method = 'xiaobai.email.send_email';
            $email_params = array('reset_password_url'=>$reset_password_url);
            $params = array(
                'email_address'  => $email,
                'template_alias' => 'reset_password',
                'subject'        => '账户密码重置 - alert',
                'email_params'   => json_encode($email_params),
            );

            $json = $client->post($method, $params);
            if($json['status'] == 1){
                $this->success('重置密码邮件已发送成功，请查收', U('Passport/forget', array('email'=>$email,'send_success'=>1)));
            }else{
                $this->error('邮件发送失败');
            }
        }

        $send_success = $_GET['send_success'];
        $email = $_GET['email'];

        $this->assign('send_success', $send_success);
        $this->assign('email', $email);
        $this->assign('_title', '找回密码 - alert');
        $this->display();
    }


    /**
     * 重置密码
     */
    public function reset_password(){
        $model = D('User');

        $verify_code = $_GET['verify_code'];
        $email = authcode($verify_code, 'DECODE', 'LW$lgXSyUc*6');
        if(!$email){
            $this->display();
            exit;
        }

        if($this->isPost()){

            $_validate = array(
                array('password' , '6,20', '密码必须为6-20位字符串', 1, 'length'),
                array('confirm_password', 'password', '确认密码与新的密码不一致', 1, 'confirm'),
            );

            // 关闭字段信息的自动检测
            // $model->setProperty('autoCheckFields', false);
            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }

            $data = array(
                'password'    => encrypt_pwd($data['password']),
                'update_time' => time(),
            );
            $effect = $model->where(array('email'=>$email))->save($data);
            if($effect){
                $this->success('重置密码成功', U('Passport/login', array('email'=>urlencode($email),'reset_success'=>1)));
            }else{
                $this->error('重置密码失败');
            }
        }

        

        $this->assign('email', $email);
        $this->assign('_title', '重置密码 - alert');
        $this->display();
    }


    /**
     * 退出登录
     */
    public function logout(){
        session('user', null);
        cookie('remeber_me', null); // 删除记住我cookie
        $url = U('Passport/login');
        redirect($url);
    }


}

?>