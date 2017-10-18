<?php
/**
 * 模型演示 类
 * @author fanrong33
 * @version v0.1.3 Build 20121017
 */
class UserModel extends Model{
	
	/**
	 * 自动验证
	 * 
	 * 验证字段，验证规则，错误提示，验证条件，附加规则，验证时间
	 *  const EXISTS_VAILIDATE     =   0; // 表单存在字段则验证(*)
	 *  const MUST_VALIDATE        =   1; // 必须验证
     *  const VALUE_VALIDATE       =   2; // 表单值不为空则验证
     * 
	 * 附加规则
     *  regex 使用正则进行验证(*)  【内置正则规则 require/email/url/currency/number】
     *  function 使用函数验证，前面定义的验证规则是一个函数名
     *  callback 使用方法验证，前面定义的验证规则是当前Model类的一个方法
     *  confirm 验证表单中的两个字段是否相同，前面定义的验证规则是一个字段名
     *  equal 验证是否等于某个值，该值由前面的验证规则定义
     *  in 验证是否在某个范围内，前面定义的验证规则必须是一个数组
     *  unique 验证是否唯一，系统会根据自动目前的值查询数据库来判断是否存在相同的值
     * 
     * const MODEL_INSERT    =   1;      //  插入模型数据
     * const MODEL_UPDATE    =   2;      //  更新模型数据
     * const MODEL_BOTH      =   3;      //  包含上面两种方式(*)
     * 
	 */
	protected $_validate = array(
		array('email', 'require', '邮箱不能为空', self::MUST_VALIDATE),
		array('email', '/^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/i', '邮箱格式错误'),
		array('email', '', '该邮箱已被使用', self::EXISTS_VAILIDATE, 'unique'),
		array('password', 'require', '密码不能为空', self::EXISTS_VAILIDATE, 'regex', self::MODEL_INSERT),
		array('password', '/^[a-z0-9!#$%&*+-=?^_`{|}~]{8,20}$/i', '密码格式错误', self::VALUE_VAILIDATE),
		
		array('verify', 'require', '验证码不能为空'), // 默认情况下用正则进行验证
		array('name', '', '账号名称已经存在', self::EXISTS_VALIDATE, 'unique', self::MODEL_INSERT), // 在新增的时候验证 name 字段是否唯一
		array('value', array(1,2,3), '值的范围不正确', self::VALUE_VALIDATE, 'in'), // 当值不为空的时候判断是否在一个范围内
		array('repassword', 'password', '确认密码不正确', self::EXISTS_VAILIDATE, 'confirm'), // 验证确认密码是否和密码一致
		array('password', 'checkPwd', '密码格式不正确', self::EXISTS_VAILIDATE, 'function'), // 自定义函数验证密码格式
		
		array('num','10,100','必须在10到100之间',0,'between'),
		array('num','1,2,5','只能选择1,2,5',0,'in'),
		array('任意字段','2011-10-1,2011-12-31','已经过了投票时间',self::MUST_VALIDATE,'expire',self::MODEL_INSERT), // 操作有效期验证 支持时间戳和日期格式定义
		array('username','3,6','用户名长度必须大于等于3小于等于6',self::MUST_VALIDATE,'length',self::MODEL_INSERT), // 长度验证 
		array('mobile','11','手机号码长度必须11位',self::MUST_VALIDATE,'length',self::MODEL_INSERT), // 指定长度定义
		
		 	
	);

	// 是否自动检测数据表字段信息
	protected $autoCheckFields  = true; // 因xxx模型并没有对应的数据表，要定义虚拟模型。

	/**
	 * 跨库操作
	 * ThinkPHP 支持模型的同一数据库服务器的跨库操作
	 * M方法也支持跨库操作，$User = M('user.User','other_');
	 */
	protected $dbName = 'other_dbname';
	
	protected $connection = 'DB_CONFIG_OA';
		/*
		'DB_CONFIG_OA'	=> array(
			'DB_TYPE'           	=> 'mysql',     // 数据库类型
			'DB_HOST'           	=> 'localhost', // 服务器地址
			'DB_NAME'               => 'db',          // 数据库名
			'DB_USER'               => '',      // 用户名
			'DB_PWD'                => '',      // 密码
			'DB_PORT'               => 3306,        // 端口
		),
		*/

	/**
	 * 字段合法性检测
	 * 也可以动态调用，可以在调用create方法之前直接调用field方法
	 * 	$User->field('nickname,email')->create();
	 * 	$User->where($map)->save();
	 */
	protected $insertFields = array('account','password','nickname','email');
    protected $updateFields = array('nickname','email');

	
	protected function _after_find(&$result,$options) {}
	
	protected function _before_insert(&$data, $options){
		$data['regtime']	= time();
		$data['password']	= encrypt_pwd($password);
	}
	
	protected function _before_update(&$data, $options) {
		
	}
	
	protected function _before_write(&$data) {
		// 更新也会执行，尽量少用这个方法，不容易控制

	}
	
	protected function _after_insert($data,$options) {}
	
	// 在更新时删除内存缓存
	protected function _after_update($data,$options) {}

	protected function _after_delete($data,$options) {}

	/**
	 * 验证名称是否存在
	 */
	protected function checkName($name){
		if($_POST['id']){
			$cond['id'] 	= array('neq', $_POST['id']); // 过滤自身	
		}
		$cond['hotel_id'] 	= cookie('now_hotel');
		$cond['name']		= $name;
		$result = $this->field('id')->where($cond)->find();
		return $result ? false : true;
	}
}

/**
 * 更新日志 http://se.360.cn/uplog.htm
 * 新增：
 * 优化：
 * 修复：
 */

?>