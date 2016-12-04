<?php
/*
 * OpenSociax 核心流程控制文件
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version ST1.0
 */

include dirname(__FILE__).'/functions.inc.php';

/*  全局配置  */

//记录开始运行时间
$GLOBALS['_beginTime'] = microtime(true);

// 记录内存初始使用
define('MEMORY_LIMIT_ON', function_exists('memory_get_usage'));

//载入全局配置
tsconfig(include dirname(__FILE__).'/convention.php');
tsconfig(include CONF_PATH.'/config.inc.php');
tsconfig(include CONF_PATH.'/access.inc.php');
tsconfig(include CONF_PATH.'/html.inc.php');
tsconfig(include CONF_PATH.'/router.inc.php');
tsconfig(include CONF_PATH.'/thinksns.conf.php');

if (!isset($_REQUEST['app']) && !isset($_REQUEST['mod']) && !isset($_REQUEST['act'])) {
    $ts['_app'] = 'public';
    $ts['_mod'] = 'Passport';
    $ts['_act'] = 'login';
} else {
    $ts['_app'] = isset($_REQUEST['app']) && !empty($_REQUEST['app']) ? $_REQUEST['app'] : tsconfig('DEFAULT_APP');
    $ts['_mod'] = isset($_REQUEST['mod']) && !empty($_REQUEST['mod']) ? $_REQUEST['mod'] : tsconfig('DEFAULT_MODULE');
    $ts['_act'] = isset($_REQUEST['act']) && !empty($_REQUEST['act']) ? $_REQUEST['act'] : tsconfig('DEFAULT_ACTION');
}
$ts['_widget_appname'] = isset($_REQUEST['widget_appname']) && !empty($_REQUEST['widget_appname']) ? $_REQUEST['widget_appname'] : '';

//APP的常量定义
tsdefine('APP_NAME', $ts['_app']);
tsdefine('TRUE_APPNAME', !empty($ts['_widget_appname']) ? $ts['_widget_appname'] : APP_NAME);
tsdefine('MODULE_NAME', $ts['_mod']);
tsdefine('ACTION_NAME', $ts['_act']);

//新增一些CODE常量.用于简化判断操作
tsdefine('MODULE_CODE', $ts['_app'].'/'.$ts['_mod']);
tsdefine('ACTION_CODE', $ts['_app'].'/'.$ts['_mod'].'/'.$ts['_act']);

//增加静态化机制 - 页面中可变元素需要ajax配合
if (file_exists(CORE_RUN_PATH.'/htmlcache/'.str_replace('/', '_', ACTION_CODE).'.html') && !($ts['_app'] == 'public' && $ts['_mod'] == 'Passport' && $ts['_act'] == 'login')) {
    readfile(CORE_RUN_PATH.'/htmlcache/'.str_replace('/', '_', ACTION_CODE).'.html');
    exit;
}

//session初始化
//兼容swfupload重塑session
if (isset($_POST['PHPSESSID'])) {
    session_id($_POST['PHPSESSID']);
}
//session 设置
ini_set('session.cookie_httponly', 1);

ini_set('session.gc_maxlifetime', 3600);
ini_set('session.cookie_lifetime', 7 * 86400);
//设置session路径到本地
if (strtolower(ini_get('session.save_handler')) == 'files') {
    $session_dir = DATA_PATH.'/session';
    if (!is_dir($session_dir)) {
        mkdir($session_dir, 0777, true);
    }
    session_save_path($session_dir);
}
session_start();

//参数处理 If already slashed, strip.
if (get_magic_quotes_gpc()) {
    $_GET = stripslashes_deep($_GET);
    $_POST = stripslashes_deep($_POST);
    $_COOKIE = stripslashes_deep($_COOKIE);
}

//解析关键参数 todo:参数过滤 preg_match("/^([a-zA-Z_\/0-9]+)$/i", $ts, $url);
$_REQUEST = array_merge($_GET, $_POST);

//参数处理 控制不合规格的参数
check_gpc($_GET);
check_gpc($_REQUEST);

/*  应用配置  */
//载入应用配置
tsdefine('APP_PATH', APPS_PATH.'/'.TRUE_APPNAME);
tsdefine('APP_URL', SITE_URL.'/apps/'.TRUE_APPNAME);
tsdefine('APP_COMMON_PATH', APP_PATH.'/Common');
tsdefine('APP_CONFIG_PATH', APP_PATH.'/Conf');
tsdefine('APP_LIB_PATH', APP_PATH.'/Lib');
tsdefine('APP_ACTION_PATH', APP_LIB_PATH.'/Action');
tsdefine('APP_MODEL_PATH', APP_LIB_PATH.'/Model');
tsdefine('APP_WIDGET_PATH', APP_LIB_PATH.'/Widget');
tsdefine('APP_API_PATH', APP_LIB_PATH.'/Api');

//定义语言缓存文件路径常量
tsdefine('LANG_PATH', DATA_PATH.'/lang');
tsdefine('LANG_URL', DATA_URL.'/lang');

//默认风格包名称
if (C('THEME_NAME')) {
    tsdefine('THEME_NAME', C('THEME_NAME'));
} else {
    tsdefine('THEME_NAME', 'stv1');
}

if (!defined('THEME_ROOT')) {
    define('THEME_ROOT', SITE_PATH.'/resources/theme/');
}

//默认静态文件、模版文件目录
tsdefine('THEME_PATH', THEME_ROOT.THEME_NAME);
tsdefine('THEME_URL', SITE_URL.'/resources/theme/'.THEME_NAME);
tsdefine('THEME_PUBLIC_PATH', THEME_PATH.'/_static');
tsdefine('THEME_PUBLIC_URL', THEME_URL.'/_static');
tsdefine('APP_PUBLIC_PATH', APP_PATH.'/_static');
tsdefine('APP_TPL_PATH', APP_PATH.'/Tpl/default');

/* 临时兼容代码，新方法开发中 */
$timer = sprintf('%s%s/app/timer', TS_ROOT, TS_STORAGE);
if (
    !file_exists($timer) ||
    (time() - file_get_contents($timer)) > 604800 // 七天更新一次
) {
    \Ts\Helper\AppInstall::moveAllApplicationResources(); // 移动应用所有的资源
    \Medz\Component\Filesystem\Filesystem::mkdir(dirname($timer), 0777);
    file_put_contents($timer, time());
}
define('APP_PUBLIC_URL', sprintf('%s%s/app/%s', SITE_URL, TS_STORAGE, strtolower(APP_NAME)));
