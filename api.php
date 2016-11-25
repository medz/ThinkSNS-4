<?php

error_reporting(E_ERROR ^ E_NOTICE ^ E_WARNING);

/*ini_set('display_errors', true);
error_reporting(E_ALL);
set_time_limit(0);*/

//网站根路径设置
define('SITE_PATH', dirname(__FILE__));

//默认应用设置为API
$_GET['app'] = 'api';

define('APP_NAME', 'api');
$api_version = !empty($_REQUEST['api_version']) ? $_REQUEST['api_version'] : '4.5.0';
$api_type = !empty($_REQUEST['api_type']) ? $_REQUEST['api_type'] : 'sociax';
define('API_VERSION', $api_type.'_v'.$api_version);

/* 新系统需要的一些配置 */
define('TS_ROOT', dirname(__FILE__));        // Ts根
define('TS_APPLICATION', TS_ROOT.'/apps'); // 应用存在的目录
define('TS_CONFIGURE', TS_ROOT.'/config'); // 配置文件存在的目录
define('TS_STORAGE', '/storage');            // 储存目录，需要可以公开访问，相对于域名根
define('TS_APP_DEV', false);
// 新的系统核心接入
require SITE_PATH.'/src/Build.php';
Ts::import(TS_ROOT, 'src', 'old', 'core', '.php');

Api::run();

/* # The end */
