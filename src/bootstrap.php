<?php

/* 功能不完善，后续根据开发需要，慢慢完善，替换以前系统底层 */
/* 一步一步替换掉 */

//设置错误级别
error_reporting(E_ERROR ^ E_NOTICE ^ E_WARNING);

// 调试模式代码
// ini_set('display_errors', true);
// error_reporting(E_ALL);
// set_time_limit(0);

define('DEBUG', false);

$mem_run_end = memory_get_usage();
$time_run_end = microtime(true);

/* 新系统需要的一些配置 */
define('TS_ROOT', dirname(dirname(__FILE__)));        // Ts根
define('TS_APPLICATION', TS_ROOT.'/apps'); // 应用存在的目录
define('TS_CONFIGURE', TS_ROOT.'/config'); // 配置文件存在的目录
define('TS_STORAGE', '/storage');            // 储存目录，需要可以公开访问，相对于域名根
/* 应用开发中的配置 */
define('TS_APP_DEV', false);

// 老的常量设置
// 网站根路径设置 // 兼容旧的地方。
define('SITE_PATH', TS_ROOT);

/**
 * 自动加载.
 */
$file = dirname(__FILE__).'/vendor/autoload.php';
if (!file_exists($file)) {
    echo '<pre>';
    echo 'You must set up the project dependencies, run the following commands:', PHP_EOL,
         'curl -sS https://getcomposer.org/installer | php', PHP_EOL,
         'php composer.phar install', PHP_EOL;
    echo '</pre>';
    exit;
}

$loader = include $file;

if (isset($_GET['debug'])) {
    C('APP_DEBUG', true);
    C('SHOW_RUN_TIME', true);
    C('SHOW_ADV_TIME', true);
    C('SHOW_DB_TIMES', true);
    C('SHOW_CACHE_TIMES', true);
    C('SHOW_USE_MEM', true);
    C('LOG_RECORD', true);
    C('LOG_RECORD_LEVEL', array(
        'EMERG',
        'ALERT',
        'CRIT',
        'ERR',
        'SQL',
    ));
}

/* Run */
Ts::run($loader);

// 下面的代码是加载appp配置的～真尴尬。。不需要的东西～后期删除
/*
 * 新应用入口文件
 */
if (file_exists(sprintf('%s/bootstrap.php', APP_PATH))) {
    Ts::import(APP_PATH, 'bootstrap', '.php');

/*
 * 兼容旧的应用
 */
} elseif (file_exists(sprintf('%s/common.php', APP_COMMON_PATH))) {
    Ts::import(APP_COMMON_PATH, 'common', '.php');
}

//合并应用配置
if (file_exists(APP_CONFIG_PATH.'/config.php')) {
    tsconfig(include APP_CONFIG_PATH.'/config.php');
}
