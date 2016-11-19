<?php
/*
 * OpenSociax 核心入口文件
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version ThinkSNS v4.
 */

if (!defined('SITE_PATH')) {
    exit();
}

@ini_set('magic_quotes_runtime', 0);

/* # 设置时区 */
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Shanghai');
}

$time_include_start = microtime(true);
$mem_include_start = memory_get_usage();

//设置全局变量ts
$ts['_debug'] = false;        //调试模式
$ts['_define'] = array();    //全局常量
$ts['_config'] = array();    //全局配置
$ts['_access'] = array();    //访问配置
$ts['_router'] = array();    //路由配置

tsdefine('IS_CGI', substr(PHP_SAPI, 0, 3) == 'cgi' ? 1 : 0);
tsdefine('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
tsdefine('IS_HTTPS', 0);

// # 设置API版本常量
if (isset($_REQUEST['api_version'])) {
    $apiVersion = $_REQUEST['api_version'];
    $apiVersion = preg_replace('/[^a-zA-Z0-9_\.\-]/is', '', $apiVersion);
    tsdefine('API_VERSION', $apiVersion);
    unset($apiVersion);
} else {
    tsdefine('API_VERSION', 'thinksns');
}

// 当前文件名
if (!defined('_PHP_FILE_')) {
    if (IS_CGI) {
        // CGI/FASTCGI模式下
        $_temp = explode('.php', $_SERVER['PHP_SELF']);
        define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0].'.php'), '/'));
    } else {
        define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
    }
}

// 网站URL根目录
if (!defined('__ROOT__')) {
    $_root = dirname(_PHP_FILE_);
    define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : rtrim($_root, '/')));
}

//基本常量定义
tsdefine('ROOT_FILE', basename(_PHP_FILE_) == 'api.php' ? 'index.php' : basename(_PHP_FILE_));
tsdefine('CORE_PATH', dirname(__FILE__));
tsdefine('THINK_PATH', CORE_PATH.'/ThinkPHP');

tsdefine('SITE_DOMAIN', strip_tags($_SERVER['HTTP_HOST']));
tsdefine('SITE_URL', (IS_HTTPS ? 'https:' : 'http:').'//'.SITE_DOMAIN.__ROOT__);

tsdefine('CONF_PATH', SITE_PATH.'/config');

tsdefine('APPS_PATH', SITE_PATH.'/apps');
tsdefine('APPS_URL', SITE_URL.'/apps');    # 应用内部图标 等元素

tsdefine('ADDON_PATH', SITE_PATH.'/addons');
tsdefine('ADDON_URL', SITE_URL.'/addons');

tsdefine('DATA_PATH', SITE_PATH.'/data');
tsdefine('DATA_URL', SITE_URL.'/data');

tsdefine('UPLOAD_PATH', SITE_PATH.'/data/upload');
tsdefine('UPLOAD_URL', SITE_URL.'/data/upload');

tsdefine('PUBLIC_PATH', SITE_PATH.'/public');
tsdefine('PUBLIC_URL', SITE_URL.'/public');

//载入核心模式: 默认是OpenSociax. 也支持ThinkPHP
if (!defined('CORE_MODE')) {
    define('CORE_MODE', 'OpenSociax');
}

tsdefine('CORE_LIB_PATH', CORE_PATH.'/'.CORE_MODE);
tsdefine('CORE_RUN_PATH', TS_ROOT.TS_STORAGE.'/temp');
tsdefine('LOG_PATH', TS_ROOT.TS_STORAGE.'/logs/');

//注册AUTOLOAD方法
if (function_exists('spl_autoload_register')) {
    spl_autoload_register(function ($classname) {
        tsautoload($classname);
    });
}

//载入核心运行时文件
include CORE_LIB_PATH.'/'.CORE_MODE.'.php';

tsdefine('NOW_TIME', $_SERVER ['REQUEST_TIME']);
tsdefine('REQUEST_METHOD', $_SERVER ['REQUEST_METHOD']);
tsdefine('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
tsdefine('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
tsdefine('TOKEN', 'ts_wx');

/* 核心方法 */

/**
 * 载入文件 去重\缓存.
 * @param  string $filename 载入的文件名
 * @return bool
 */
function tsload($filename)
{
    return Ts::import($filename, '');
}

/**
 * 系统自动加载函数
 * @param string $classname 对象类名
 */
function tsautoload($classname)
{
    // 检查是否存在别名定义
    if (tsload($classname)) {
        return ;
    }

    // 自动加载当前项目的Actioon类和Model类
    if (substr($classname, -5) == 'Model') {
        tsload(APP_LIB_PATH.'/Model/'.$classname.'.class.php');
    } elseif (substr($classname, -6) == 'Action') {
        tsload(APP_LIB_PATH.'/Action/'.$classname.'.class.php');
    } elseif (substr($classname, -6) == 'Widget') {
        $filename = sprintf('%s/Widget/%s/%s.class.php', APP_LIB_PATH, $classname, $classname);
        tsload($filename);
    } elseif (substr($classname, -6) == 'Addons') {
        tsload(APP_LIB_PATH.'/Plugin/'.$classname.'.class.php');
    }

    return ;
}

/**
 * 定义常量,判断是否未定义.
 *
 * @param  string $name  常量名
 * @param  string $value 常量值
 * @return string $str 返回常量的值
 */
function tsdefine($name, $value)
{
    global $ts;
    //定义未定义的常量
    if (!defined($name)) {
        //定义新常量
        define($name, $value);
    } else {
        //返回已定义的值
        $value = constant($name);
    }
    //缓存已定义常量列表
    $ts['_define'][$name] = $value;

    return $value;
}

/**
 * 返回16位md5值
 *
 * @param  string $str 字符串
 * @return string $str 返回16位的字符串
 */
function tsmd5($str)
{
    return substr(md5($str), 8, 16);
}

/**
 * 载入配置 修改自ThinkPHP:C函数 为了不与tp冲突
 *
 * @param  string              $name  配置名/文件名.
 * @param  string|array|object $value 配置赋值
 * @return void|null
 */
function tsconfig($name = null, $value = null)
{
    global $ts;
    // 无参数时获取所有
    if (empty($name)) {
        return $ts['_config'];
    }
    if (!isset($ts['_config'])) {
        $ts['_config'] = array();
    }
    // 优先执行设置获取或赋值
    if (is_string($name)) {
        if (!strpos($name, '.')) {
            $name = strtolower($name);
            if (is_null($value)) {
                return isset($ts['_config'][$name]) ? $ts['_config'][$name] : null;
            }
            $ts['_config'][$name] = $value;

            return;
        }
        // 二维数组设置和获取支持
        $name = explode('.', $name);
        $name[0] = strtolower($name[0]);
        if (is_null($value)) {
            return isset($ts['_config'][$name[0]][$name[1]]) ? $ts['_config'][$name[0]][$name[1]] : null;
        }
        $ts['_config'][$name[0]][$name[1]] = $value;

        return;
    }
    // 批量设置
    if (is_array($name)) {
        return $ts['_config'] = array_merge((array) $ts['_config'], array_change_key_case($name));
    }

    return null; // 避免非法参数
}

/**
 * 执行钩子方法
 *
 * @param  string       $name   钩子方法名.
 * @param  array        $params 钩子参数数组.
 * @return array|string Stripped array (or string in the callback).
 */
function tshook($name, $params = array())
{
    global $ts;
    $hooks = $ts['_config']['hooks'][$name];
    if ($hooks) {
        foreach ($hooks as $call) {
            if (is_callable($call)) {
                $result = call_user_func_array($call, $params);
            }
        }

        return $result;
    }

    return false;
}

/**
 * Navigates through an array and removes slashes from the values.
 *
 * If an array is passed, the array_map() function causes a callback to pass the
 * value back to the function. The slashes from this value will removed.
 * @param  array|string $value The array or string to be striped.
 * @return array|string Stripped array (or string in the callback).
 */
function stripslashes_deep($value)
{
    if (is_array($value)) {
        $value = array_map('stripslashes_deep', $value);
    } elseif (is_object($value)) {
        $vars = get_object_vars($value);
        foreach ($vars as $key => $data) {
            $value->{$key} = stripslashes_deep($data);
        }
    } else {
        $value = stripslashes($value);
    }

    return $value;
}

/**
 * GPC参数过滤
 * @param  array|string $value The array or string to be striped.
 * @return array|string Stripped array (or string in the callback).
 */
function check_gpc($value = array())
{
    if (!is_array($value)) {
        return;
    }
    foreach ($value as $key => $data) {
        //对get、post的key值做限制,只允许数字、字母、及部分符号_-[]#@~
        // if(!preg_match('/^[a-zA-Z0-9,_\;\-\/\*\.#!@~\[\]]+$/i',$key)){
        // 	die('wrong_parameter: not safe get/post/cookie key.');
        // }
        //如果key值为app\mod\act,value只允许数字、字母下划线
        if (($key == 'app' || $key == 'mod' || $key == 'act') && !empty($data)) {
            if (!preg_match('/^[a-zA-Z0-9_]+$/i', $data)) {
                die('wrong_parameter: not safe app/mod/act value.');
            }
        }
    }
}

//全站静态缓存,替换之前每个model类中使用的静态缓存
//类似于s和f函数的使用
function static_cache($cache_id, $value = null, $clean = false)
{
    static $cacheHash = array();
    if ($clean) { //清空缓存 其实是清不了的 程序执行结束才会自动清理
        unset($cacheHash);
        $cacheHash = array(0);

        return $cacheHash;
    }
    if (empty($cache_id)) {
        return false;
    }
    if ($value === null) {
        //获取缓存数据
        return isset($cacheHash[$cache_id]) ? $cacheHash[$cache_id] : false;
    } else {
        //设置缓存数据
        $cacheHash[$cache_id] = $value;

        return $cacheHash[$cache_id];
    }
}
