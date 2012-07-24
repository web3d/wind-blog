<?php
/**
 * 默认的 controller
 *
 * @author Shi Long <long.shi@alibaba-inc.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id$
 * @package demos.blog.controller
 */
class Controller extends WindController {

	/* (non-PHPdoc)
	 * @see WindSimpleController::beforeAction()
	 */
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
		$this->setLayout('layout');
		$this->setOutput('utf8', 'charset');
		$this->setGlobal($this->getRequest()->getBaseUrl(true) . '/static/images', 'images');
		$this->setGlobal($this->getRequest()->getBaseUrl(true) . '/static/images', 'css');
	}

	/**
	 * @return UserService
	 */
	private function load() {
		return Wind::getApp()->getWindFactory()->createInstance(Wind::import('service.UserService'));
	}
}