<?php
/**
 * 账号管理 控制器类
 * @author 蔡繁荣
 * @version 1.0.2 build 20171010
 */
class AccountAction extends CommonAction{

    public function _initialize(){
        parent::_initialize();

        $this->addPath('账号信息');
    }


    public function index(){
        $model = D('User');

        if($this->isPost()){

            $_validate = array(
                array('email'   , 'require'   , '邮箱地址为空', 1), // 默认正则regex, email, url, integer, number, double
            );

            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }
            

            $data['update_time'] = time();
            $effect = $model->where(array('id'=>$this->_user['id']))->save($data);
            if($effect){
                $this->success('保存成功');
            }else{
                $this->error('保存失败');
            }
        }

        $item = $model->find($this->_user['id']);
        $this->assign('model', $item);
        $this->display();
    }


    /**
     * 修改密码
     */
    public function edit_password(){
        $model = D('User');

        if($this->isPost()){

            $_validate = array(
                array('current_password', 'require'  , '当前密码为空', 1),
                array('new_password'    , 'require'  , '新的密码为空', 1),
                array('confirm_password', 'require'  , '确认密码不能为空', 1),
                array('new_password'    , '6,20'     , '密码必须6-20位字符串', 1, 'length'), // in  , between,  length,  等...
                array('confirm_password', 'new_password', '确认密码与新的密码不一致', 1, 'confirm'),
            );

            $model->setProperty('autoCheckFields', false);
            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }


            $developer = $model->find($this->_user['id']);
            if(encrypt_pwd($data['current_password']) != $developer['password']){
                $this->error('当前密码错误');
            }

            $hash_password = encrypt_pwd($data['new_password']);


            $data = array();
            $data['password']   = $hash_password;
            $data['update_time'] = time();
            $effect = $model->where(array('id'=>$this->_user['id']))->save($data);
            
            if($effect){
                $this->success('修改成功');
            }else{
                $this->error('修改失败');
            }

        }
    }


}

?>