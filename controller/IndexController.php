<?php
Wind::import('component.Controller');
Wind::import('service.UserService');
/**
 * 默认的 controller
 *
 * @author Shi Long <long.shi@alibaba-inc.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id$
 * @package demos.blog.controller
 */
class IndexController extends Controller {

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
		//Wind::import('service.UserForm');
		//$userService = new UserService();
		//$userInfo = $userService->isLogin();
		//$this->setOutput($userInfo, 'userInfo');
		Wind::import('model.Post');
		$model = new Post();
		$posts = $model->listPosts();
		$first_post = $model->findFirst();
		//var_dump($first_post);
		//var_dump($posts);
		$this->setOutput($posts, 'posts');
		$this->setOutput($first_post, 'first_post');
		$this->setTemplate('index');
	}
	
	public function viewAction(){
        $post_id = intval($_GET['id']);
        if(!$post_id) $this->showMessage('请指定您要访问的日志！');
        
        Wind::import('model.Post');
        $model = new Post();
        $post = $model->find($post_id);
        $this->setOutput($post, 'post');
        
        $this->setTemplate('post_view');
	}

	/**
	 * 访问用户注册页面
	 */
	public function regAction() {
		$this->setTemplate('reg');
	}

	/**
	 * 用户登录
	 */
	public function loginAction() {
		$userService =new UserService();
		$userInfo = $userService->isLogin();
		if ($userInfo) $this->showMessage('已登录~');
		
		/* @var $userForm UserForm */
		$userForm = $this->getInput("userForm");
		if (!$userForm) $this->showMessage('获取用户登录数据失败');
		
		if (!$userService->login($userForm)) $this->showMessage('登录失败.');
		$this->forwardRedirect(WindUrlHelper::createUrl('run'));
	}

	/**
	 * 处理用户注册表单
	 */
	public function dregAction() {
		$userService =new UserService();
		$userForm = $this->getInput("userForm");
		if (!$userService->register($userForm)) $this->showMessage('注册失败.');
		$this->setOutput($userForm, 'userInfo');
		$this->setTemplate('reg');
	}

	/**
	 * 用户退出
	 */
	public function logoutAction() {
        $userService =new UserService();
		$userService->logout();
		$this->forwardRedirect(WindUrlHelper::createUrl('run'));
	}

}