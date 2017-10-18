<?php
/**
 * 应用管理 控制器类
 * @author 蔡繁荣
 * @version  1.0.6 build 20171018
 */
class DistributeAction extends CommonAction{


    public function index(){
        $name = $this->getActionName();
        $model = D($name);

        // 请求参数
        $keyword    = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $order_by   = $_GET['order_by'] ? $_GET['order_by'] : 'id';
        $direction  = $_GET['direction'] ? $_GET['direction'] : 'desc';

        $cond = array();
        if($keyword != ''){
            $where['name']    = array('like', '%'.$keyword.'%');
            $where['_logic']  = 'or';
            $cond['_complex'] = $where;
            $this->assign('keyword', $keyword);
        }
        $cond['user_id']    = $this->_user['id'];
        $cond['is_deleted'] = 0;



        $count = $model->where($cond)->count();
        
        import('@.ORG.Util.Page');
        $page = new Page($count, 15);
        $page->setConfig('prev' , '&laquo;');
        $page->setConfig('next' , '&raquo;');

        $list = $model->where($cond)->order($order_by.' '.$direction)->limit($page->firstRow, $page->listRows)->select();
        if($list){

            // 展示索引值为人类可以理解的文本
            foreach ($list as $key => $rs) {
                if($rs['trigger_app']){
                    $app = cache_get('App', $rs['trigger_app'], 60);
                    $rs['trigger_app'] = $app['name'];
                }
                
                $pieces = explode(',', $rs['assign']);
                $assign_list = array();
                foreach ($pieces as $val) {
                    $user = cache_get('Member', $val, 60);
                    $assign_list[] = $user['name'];
                }
                $rs['assign'] = join(', ', $assign_list);

                $list[$key] = $rs;
            }
        }else{
            $list = array();
        }


        $this->assign('list', $list);
        $this->assign('order_by', $order_by);
        $this->assign('direction', $direction);
        $this->assign('page', $page->shows());
        $this->display();
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
                array('name'            , 'require', '名称为空', 1), // 默认正则regex, email, url, integer, number, double
                array('trigger_app'     , 'require', '触发应用为空', 1),
                array('trigger_priority', 'require', '触发告警级别为空', 1),
                array('trigger_content' , 'require', '策略描述为空', 1),
                array('assign'          , 'require', '分派给为空', 1),
            );

            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }


            // 保存的时候，转化 天气 =》 20001，将人类可理解的文本转化为索引值进行保存
            $cond = array(
                'user_id' => $this->_user['id'], 
                'name'    => $data['trigger_app']
            );
            $app = D('App')->where($cond)->find();
            $data['trigger_app'] = $app['id'];

            // TODO 严格判断成员是否存在
            $pieces = explode(',', $data['assign']);
            $assign_list = array();
            foreach ($pieces as $val) {
                $cond = array(
                    'user_id' => $this->_user['id'],
                    'name'    => $val,
                );
                $member = D('Member')->where($cond)->find();
                if($member){
                    $assign_list[] = $member['id'];
                }
            }
            $data['assign'] = join(',', $assign_list);
            

            $data['user_id']     = $this->_user['id'];
            $data['update_time'] = time();
            $data['create_time'] = time();

            $insert_id = $model->add($data);
            if($insert_id){
                $this->success('添加成功', U($name.'/index').'#item_'.$insert_id); // 跳转由后端控制
            }else{
                $this->success('添加失败');
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
                array('id'              , 'require', 'id为空' , 1),
                array('name'            , 'require', '名称为空', 1), // 默认正则regex, email, url, integer, number, double
                array('trigger_app'     , 'require', '触发应用为空', 1),
                array('trigger_priority', 'require', '触发告警级别为空', 1),
                array('trigger_content' , 'require', '策略描述为空', 1),
                array('assign'          , 'require', '分派给为空', 1),
            );

            if(false === $data = $model->validate($_validate)->create()){
                $this->error($model->getError());
            }
            //TODO 是否拥有修改权限？这里可能存在bug


            // 保存的时候，转化 天气 =》 20001，将人类可理解的文本转化为索引值进行保存
            $cond = array(
                'user_id' => $this->_user['id'],
                'name'    => $data['trigger_app'],
            );
            $app = D('App')->where($cond)->find();
            $data['trigger_app'] = $app['id'];

            $pieces = explode(',', $data['assign']);
            $assign_list = array();
            foreach ($pieces as $val) {
                $cond = array(
                    'user_id' => $this->_user['id'],
                    'name'    => $val,
                );
                $member = D('Member')->where($cond)->find();
                if($member){
                    $assign_list[] = $member['id'];
                }
            }
            $data['assign'] = join(',', $assign_list);
            

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

        // 展示的时候，转化 20001 =》 天气，转化索引值为人类可以理解的文本进行展示
        if($item['trigger_app']){
            $app = cache_get('App', $item['trigger_app'], 60);
            $item['trigger_app'] = $app['name'];
        }

        $pieces = explode(',', $item['assign']);
        $assign_list = array();
        foreach ($pieces as $val) {
            $member = cache_get('Member', $val, 60);
            if($member){
                $assign_list[] = $member['name'];
            }
        }
        $item['assign'] = join(',', $assign_list);



        $this->assign('model', $item);
        $this->display($name.'/add');
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