<?php
/**
 * 后台通用 控制器类
 * @author 蔡繁荣
 * @version 1.0.0 build 20170401
 */
class CommonAction extends Action{

	private $_title = null; //当前页面标题
	private $_trail = array();

	protected $_user = null;

	public function _initialize(){
		$this->_user = session('user');
		if(!$this->_user){
			$current_url = get_url(); // 记住当前访问的地址，用于登录后返回
			$url = U('Passport/login').'?referer_url='.urlencode($current_url);
			redirect($url);
		}

		$this->assign('_user', $this->_user);
		$this->addPath('alert');
	}

	/**
	 * 取得操作成功后要返回的URL地址
	 * 默认返回当前模块的默认操作
	 * 可以在action控制器中重载
	 */
	public function getReturnUrl(){
		return __URL__.'?'.C('VAR_MODULE').'='.MODULE_NAME.'&'.C('VAR_ACTION').'='.C('DEFAULT_ACTION');
	}

	public function index(){
		// 列表过滤器，生成查询map对象
		$map = $this->_search();
		if(method_exists($this, '_filter')){
			$this->_filter($map);
		}
		$name = $this->getActionName();
		$model = D($name);
		if(!empty($model)){
			$this->_list($model, $map);
		}
		$this->display();
	}
	
	/**
	 * 根据表单生成查询条件
	 * @param string $name 数据对象名称
	 * @return HashMap
	 */
	protected function _search($name=''){
		// 生成查询条件
		if (empty($name)){
			$name = $this->getActionName();
		}
		$model = D($name);
		$map = array();
		$fields = $model->getDbFields();
		foreach($fields as $key => $val){
			if(isset($_REQUEST[$val]) && $_REQUEST[$val]!= ''){
				$map[$val] = $_REQUEST[$val];
			}
		}
		return $map;
	}
	
	/**
	 * 根据表单生成的查询条件进行过滤
	 * @param mixed $model 模型对象
	 * @param array $map 查询条件
	 * @param string $orderBy 排序字段
	 * @param string $sortBy 顺序
	 */
	protected function _list($model, $map, $orderBy='', $sortBy='desc'){
		// 排序字段 默认为主键名
		if(isset($_REQUEST['order'])){
			$order = $_REQUEST['order'];
		}else{
			$order = !empty($orderBy) ? $orderBy : $model->getPk();
		}
		
		// 排序方式默认倒序 desc
		// 接受参数 sort参数 1升序 0倒序
		if(isset($_REQUEST['sort'])){
			$sort = $_REQUEST['sort'] ? 'asc' : 'desc';
		}else{
			$sort = in_array($sortBy, array('desc', 'asc')) ? $sortBy : 'desc';
		}
		
		// 取得满足条件的记录数
		$count = $model->where($map)->count();
		
		$list = array();
		if($count > 0){
			import('ORG.Util.Page');
			// 创建分页对象
			if(!empty($_REQUEST['listRows'])){
				$listRows = $_REQUEST['listRows'];
			}else{
				$listRows = '';
			}
			$p = new Page($count, $listRows);
			// 分页查询
			$list = $model->where($map)->order($order.' '.$sort)->limit($p->firstRow.','.$p->listRows)->select();
			
			// 跳转分页的是保证查询条件
			foreach($map as $key=>$val){
				if(!is_array($val)){
					$p->parameter .= "$key=" . urlencode($val) . '&';
				}
			}
			// 分页显示
			$page = $p->show();
			// 列表排序显示
			// 模板赋值显示
			$this->assign('list', $list);
			$this->assign('page', $page);
		}
		cookie('_currentUrl_', __SELF__);
		return $list;
	}
	
	public function add(){
		if($this->isPost()){
			$name = $this->getActionName();
			$model = D($name);
			if(false === $model->create()){
				$this->error($model->getError());
			}
			// 保存当前数据对象
			if(false !== $model->add()){
				$this->success('新增成功!', cookie('_currentUrl_'));
			}else{
				$this->error('新增失败!');
			}
		}
		$this->display('edit');
	}
	
	public function edit(){
		$name = $this->getActionName();
		$model = D($name);
		if($this->isPost()){
			if(false === $model->create()){
				$this->error($model->getError());
			}
			// 更新数据
			if(false !== $model->save()){
				$this->success('编辑成功!', cookie('_currentUrl_'));
			}else{
				$this->error('编辑失败!');
			}
		}
		$id = $_REQUEST[$model->getPk()];
		$vo = $model->getById($id);
		if(!$vo) $this->error('数据不存在');
		$this->assign('_model', $vo);
		$this->display();
	}
	
	/**
	 * 删除指定数据
	 */
	public function delete(){
		$name = $this->getActionName();
		$model = D($name);
		if(!empty($model)){
			$pk = $model->getPk();
			$id = $_REQUEST[$pk];
			if(isset($id)){
				$cond[$pk] = array('in', is_array($id) ? $id : explode(',', $id));
				if(false !== $model->where($cond)->delete()){
					$this->success('删除成功!');
				}else{
					$this->error('删除失败!');
				}
			}else{
				$this->error('非法操作');
			}
		}
		$this->forward();
	}
	
	/**
	 * 页面导航 -》 增加导航至
	 * 
	 * @param type $title
	 * @param type $url
	 * @return \CommonAction 
	 */
	protected function addPath($title, $url = '', $breadcrumb = ''){
		$this->_title = empty($this->_title) ? $title : $title . ' - ' . $this->_title;
		if($breadcrumb == '') $breadcrumb = $title;
		$this->_trail[] = array('title' => $title, 'url' => $url, 'breadcrumb'=>$breadcrumb);
		return $this;
	}

	/**
	 * 实现显示页面方法
	 * 
	 * @param String $templateFile	模板文件路径
	 * @param String $charset		模板文件编码
	 * @param String $contentType	模板文件类型
	 */
	protected function display($templateFile = '', $charset = '', $contentType = 'text/html'){
		if($this->_title){
			$this->assign('_title', $this->_title);
		}
		$this->assign('_trail', $this->_trail);
		parent::display($templateFile, $charset, $contentType);
	}

}

?>