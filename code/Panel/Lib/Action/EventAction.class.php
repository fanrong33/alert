<?php
/**
 * 告警管理 控制器类
 * @author 蔡繁荣
 * @version  1.0.0 build 20171003
 */
class EventAction extends CommonAction{


    public function index(){
        $name = $this->getActionName();
        $model = D($name);

        // 请求参数
        $keyword    = isset($_GET['keyword']) ? $_GET['keyword'] : '';
        $order_by   = $_GET['order_by'] ? $_GET['order_by'] : 'id';
        $direction  = $_GET['direction'] ? $_GET['direction'] : 'desc';

        $cond = array();
        if($keyword != ''){
            $where['title']   = array('like', '%'.$keyword.'%');
            $where['id']      = $keyword;
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

        $this->assign('list', $list);
        $this->assign('order_by', $order_by);
        $this->assign('direction', $direction);
        $this->assign('page', $page->shows());
        $this->display();
    }

    public function get_event(){
        $name = $this->getActionName();
        $model = D($name);

        $id = intval($this->_get('id'));

        $event = $model->find($id);
        $event['app'] = cache_get('App', $event['app_id'], 60);

        $this->ajaxReturn($event, 'success', 1);
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