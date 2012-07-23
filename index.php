<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
//exit('why null.');
define('WIND_DEBUG', 1);
require_once dirname(__FILE__) . '/../Framework/windframework/wind/Wind.php';


Wind::application('blog', 'conf/config.php')->run();

