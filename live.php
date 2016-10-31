<?php

error_reporting(E_ERROR);

/*///调试、找错时请去掉///前空格
ini_set('display_errors',true);
error_reporting(E_ALL);
set_time_limit(0);
// */
//网站根路径设置
define('SITE_PATH', dirname(__FILE__));

//默认应用设置为API
$_REQUEST['app'] = 'api';

if (!$_REQUEST['mod']) {
    define('MODULE_NAME', 'LiveOauth');
}
$api = array('ZB_User_Get_AuthByTicket', 'ZB_User_Get_List', 'ZB_User_Follow', 'ZB_User_Get_Info', 'ZB_User_Get_ticket', 'ZB_Trade_Get_Pretoken', 'ZB_Trade_Create', 'ZB_Trade_Get_Status', 'ZB_Trade_Get_list');
if (!$_REQUEST['api']) {
    define('ACTION_NAME', 'ZB_User_Get_AuthByTicket');
} else {
    define('ACTION_NAME', $_REQUEST['api']);
    !in_array($_REQUEST['api'], $api) && define('ACTION_NAME', 'ZB_User_Get_AuthByTicket');
}

define('APP_NAME', 'api');
define('API_VERSION', 'live');

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
