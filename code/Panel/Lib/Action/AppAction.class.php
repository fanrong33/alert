<?php
/**
 * 应用管理 控制器类
 * @author 蔡繁荣
 * @version  1.0.5 build 20170928
 */
class AppAction extends CommonAction{

    public function _initialize(){
        parent::_initialize();

        $this->addPath('应用');
    }
    

    public function index(){
        $name = $this->getActionName();
        $model = D($name);

        // 请求参数
        $keyword    = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $is_actived = (isset($_GET['is_actived']) && $_GET['is_actived']!=='') ? intval($_GET['is_actived']) : ''; // 01类型
        $is_deleted = (isset($_GET['is_deleted']) && $_GET['is_deleted']!=='') ? intval($_GET['is_deleted']) : ''; // 01类型
        $order_by   = $_GET['order_by'] ? $_GET['order_by'] : 'id';
        $direction  = $_GET['direction'] ? $_GET['direction'] : 'desc';

        $cond = array();
        if($keyword != ''){
            $where['name']    = array('like', '%'.$keyword.'%');
            $where['app_key'] = $keyword;
            $where['_logic']  = 'or';
            $cond['_complex'] = $where;
            $this->assign('keyword', $keyword);
        }
        if($is_actived !== ''){
            $cond['is_actived'] = $is_actived;
            $this->assign('is_actived', $is_actived);
        }
        $is_actived_map = array(
            ''  => '启用状态',
            '1' => '启用',
            '0' => '禁用',
        );
        $this->assign('is_actived_map', $is_actived_map);
        $cond['user_id']    = $this->_user['id'];
        $cond['is_deleted'] = 0;



        $count = $model->where($cond)->count();
        
        import('@.ORG.Util.Page');
        $page = new Page($count, 15);
        $page->setConfig('prev' , '&laquo;');
        $page->setConfig('next' , '&raquo;');

        if($count > 0){
            $list = $model->where($cond)->order($order_by.' '.$direction)->limit($page->firstRow, $page->listRows)->select();
        }else{
            $list = array();
        }

        $this->assign('list', $list);
        $this->assign('page', $page->shows());
        $this->assign('order_by', $order_by);
        $this->assign('direction', $direction);
        $this->display();
    }


    /**
     * 切换启用状态
     */
    public function toggle_is_actived(){
        $name = $this->getActionName();
        $model = D($name);

        if($this->isPost()){
            $id         = intval($_POST['id']);
            $is_actived = intval($_POST['is_actived']);

            $data = array(
                'is_actived'  => $is_actived,
                'update_time' => time(),
            );
            $effect = $model->where(array('id'=>$id))->save($data);
            if($effect){
                $this->success('更新成功');
            }else{
                $this->error('更新失败');
            }
        }
    }


    /**
     * 添加数据
     */
    public function add(){
        $name = $this->getActionName();
        $model = D($name);

        if($this->isPost()){

            $_POST['name'] = trim($_POST['name']);

            $_validate = array(
                array('name'    , 'require'   , '名称为空', 1), // 默认正则regex, email, url, integer, number, double
            );

            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }


            // 判断名称不能相同
            $cond = array(
                'user_id'    => $this->_user['id'],
                'name'       => $data['name'],
                'is_deleted' => 0,
            );
            if($model->where($cond)->find()){
                $this->error('名称已存在');
            }

            $data['app_key'] = generate_guid();

            $data['user_id']     = $this->_user['id'];
            $data['update_time'] = time();
            $data['create_time'] = time();

            $insert_id = $model->add($data);
            if($insert_id){
                $this->success('添加成功', U('App/index').'#item_'.$insert_id); // 跳转由后端控制
            }else{
                $this->error('添加失败');
            }
            exit;
        }

        $this->display();
    }


    public function edit(){
        $name = $this->getActionName();
        $model = D($name);

        if($this->isPost()){

            $_POST['name'] = trim($_POST['name']);

            $_validate = array(
                array('id'      , 'require'   , 'id为空' , 1),
                array('name'    , 'require'   , '名称为空', 1), // 默认正则regex, email, url, integer, number, double
            );

            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }
            //TODO 是否拥有修改权限？这里可能存在bug

            // 判断名称不能相同
            $cond = array(
                'id'         => array('neq', $data['id']),
                'user_id'    => $this->_user['id'],
                'name'       => $data['name'],
                'is_deleted' => 0,
            );
            if($model->where($cond)->find()){
                $this->error('名称已存在');
            }

            $data['update_time'] = time();

            $effect = $model->where(array('id'=>$data['id']))->save($data);
            if($effect){
                $this->success('保存成功', $_POST['referer_url'].'#item_'.$data['id']);
            }else{
                $this->error('保存失败');
            }
            exit;
        }


        $id = intval($_GET['id']);

        $item = $model->find($id);
        if(!$item || $item['is_deleted']){
            $this->error('数据不存在');
        }


        $this->assign('model', $item);
        $this->display('App/add');
    }


    public function delete(){
        $name = $this->getActionName();
        $model = D($name);

        if($this->isPost()){

            $id = intval($this->_post('id'));

            $item = $model->find($id);
            if(!$item || $item['is_deleted']){
                $this->error('数据不存在');
            }


            $data = array(
                'is_deleted'  => 1,
                'update_time' => time(),
            );
            $effect = $model->where(array('id'=>$id))->save($data);
            if($effect){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }
    }


}

?>