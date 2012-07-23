<?php
return array(
	//重载了系统组件中的db组件的定义，将db组件的config指向应用根目录下的db_config.php
	//我们可以通过这种方式重载任何系统组件的定义，也可以定义新的组件。组件名称不能重复。
	//支持resource的配置方式
	'components' => array(
		'db' => array(
			'config' =>  array(
				'resource' => 'conf.db_config.php',
			)
		),
		/** 比如路由的组件配置 **/
        /*配置default应用的路由规则*/
        //router' => array(
            /*当开启多应用时候，路由组件需指向WindMultiAppRouter*/
            //'path' => 'WIND:router.WindMultiAppRouter',
        //    'config' => array(
        //        'resource' => 'conf.router_config.php',
        //    ),
       // ),
	),
	//应用配置，支持多个应用配置。一个应用支持多个modules（业务模块），每个modules都有一个别名用于访问。
	//当不输入任何modules时访问‘default’默认模块
	'web-apps' => array(
		'blog' => array(
			'modules' => array(
				'default' => array(
					//应用控制器访问路径定义，当前定义的路径是当前应用根目录下的‘controller/’
					'controller-path' => 'controller', 
					//应用控制器后缀定义
					'controller-suffix' => 'Controller', 
					//模板目录定义
					'template-path' => 'template',
					//编译文件目录定义
					'compile-path' => 'data.compile',
					//错误处理句柄定义
					'error-handler' => 'controller.ErrorController',
					//自定义组件
					'component-path' => 'component',
				)
			), 
			//过滤器配置，在这里部署了一个form表单过滤器
			'filters' => array(
				'user' => array(
					'class' => 'WIND:web.filter.WindFormFilter', 
					'pattern' => 'default/Index/(login|dreg)', 
					'form' => 'service.UserForm'
				)
			)
		)
	)
);
