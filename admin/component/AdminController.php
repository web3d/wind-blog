<?php
/**
 * 后台默认的 controller
 *
 * @version $Id$
 */
class AdminController extends WindController {

    /**
     *
     * @var string|array array('check' => array('order', 'goods'), 'uncheck' => array('run'))
     */
    protected $checkingActions = 'all';

	/* (non-PHPdoc)
	 * @see WindSimpleController::beforeAction()
	 */
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);

        $session = $this->getSession();
        $session->set('namexxtt', 'test2233');    //等同：$_SESSION['name'] = 'test';
        echo $session->get('namexxtt');       //等同：echo $_SESSION['name'];

        $user = $this->getUser();

        Wind::import('component.UserIdentity');
        $user_identity = new UserIdentity('jimmy', '123456');

        var_dump($user->login($user_identity));


        $this->checkRights($handlerAdapter, '');
        //TODO:后台用户权限检测

		$this->setLayout('layout');
		$this->setOutput('utf8', 'charset');
		$this->setGlobal($this->getRequest()->getBaseUrl(true) . '/admin/static/images', 'images');
		$this->setGlobal($this->getRequest()->getBaseUrl(true) . '/admin/static/images', 'css');
	}

    //后台管理员权限校验
    public function checkAdminRights($handlerAdapter)
    {
        $object = $this->ctrlObj;

        $admin                    = array();
        $admin['admin_id']        = ISafe::get('admin_id');
        $admin['admin_name']      = ISafe::get('admin_name');
        $admin['admin_pwd']       = ISafe::get('admin_pwd');
        $admin['admin_role_name'] = ISafe::get('admin_role_name');

        if($admin['admin_name'] == null || $admin['admin_pwd'] == null)
        {
            $object->redirect('/systemadmin/index');
            exit;
        }

        $adminObj = new IModel('admin');
        $adminRow = $adminObj->getObj("admin_name = '{$admin['admin_name']}'");
        if(!empty($adminRow) && ($adminRow['password'] == $admin['admin_pwd']) && ($adminRow['is_del'] == 0))
        {
            //非超管角色
            if($adminRow['role_id'] != 0)
            {
                $roleObj = new IModel('admin_role');
                $where   = 'id = '.$adminRow["role_id"].' and is_del = 0';
                $roleRow = $roleObj->getObj($where);

                //角色权限校验
                if($object->checkRight($roleRow['rights']) == false)
                {
                    IError::show('503','no permission to access');
                    exit;
                }
            }
            $object->admin = $admin;
        }
        else
        {
            IError::show('503','no permission to access');
            exit;
        }
    }

    /**
     * @brief 权限校验拦截
     * @param string $ownRights 用户的权限码
     * @return bool true:校验通过; false:校验未通过
     */
    public function checkRights($handlerAdapter, $ownRights)
    {
        $action = $handlerAdapter->getAction();

        //是否需要权限校验 true:需要; false:不需要
        $isCheckRights = false;
        if($this->checkingActions == 'all')
        {
            $isCheckRights = true;
        }
        else if(is_array($this->checkingActions))
        {
            if(isset($this->checkingActions['check']) && ( ($this->checkingActions['check'] == 'all')
               || ( is_array($this->checkingActions['check']) && in_array($action,$this->checkingActions['check'])))){
                $isCheckRights = true;
            }

            if(isset($this->checkingActions['uncheck']) && is_array($this->checkingActions['uncheck'])
               && in_array($action,$this->checkingActions['uncheck']))
            {
                $isCheckRights = false;
            }
        }

        //需要校验权限
        if($isCheckRights == true)
        {
            $rightCode = $handlerAdapter->getController() . '@' . $action; //拼接的权限校验码
            $ownRights  = ','.trim($ownRights,',').',';

            if(stripos($ownRights,','.$rightCode.',') === false)
                return false;
            else
                return true;
        }
        else
            return true;
    }

	/**
	 * @return UserService
	 */
	private function load() {
		//return Wind::getApp()->getWindFactory()->createInstance(Wind::import('service.UserService'));
	}

    public function getSession(){
        return Wind::getApp()->getComponent('session');
    }

    public function getUser(){
        return Wind::getApp()->getComponent('user');
    }
}