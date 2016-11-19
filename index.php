<?php

//设置错误级别
error_reporting(E_ERROR ^ E_NOTICE ^ E_WARNING);

/* ///调试、找错时请去掉///前空格
ini_set('display_errors', true);
error_reporting(E_ALL);
set_time_limit(0);
*/

define('DEBUG', false);

$mem_run_end = memory_get_usage();
$time_run_end = microtime(true);

/* # 检查PHP版本是否符合运行要求 */
if (version_compare(PHP_VERSION, '5.3.12', '<')) {
    header('Content-Type:text/html;charset=utf-8');
    echo '您的PHP版本为：', PHP_VERSION,
         '<br>',
         'ThinkSNS程序运行版本不得低于：PHP 5.3.12'
    ;
    exit;

/* # 检查是否安装过ThinkSNS */
} elseif (is_dir(__DIR__.'/install') and !file_exists(__DIR__.'/data/install.lock')) {
    header('location:install/install.php');
    exit;
}

//网站根路径设置 // 兼容旧的地方。
define('SITE_PATH', dirname(__FILE__));

/* 新系统需要的一些配置 */
define('TS_ROOT', dirname(__FILE__));        // Ts根
define('TS_APPLICATION', TS_ROOT.'/apps'); // 应用存在的目录
define('TS_CONFIGURE', TS_ROOT.'/config'); // 配置文件存在的目录
define('TS_STORAGE', '/storage');            // 储存目录，需要可以公开访问，相对于域名根
/* 应用开发中的配置 */
define('TS_APP_DEV', false);
// 新的系统核心接入
require TS_ROOT.'/src/Build.php';
Ts::import(TS_ROOT, 'src', 'old', 'core', '.php');

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

App::run();

if (C('APP_DEBUG')) {

    //数据库查询信息
    echo '<div align="left">';
    //缓存使用情况
    $log = Log::$log;
    $sqltime = 0;
    $sqllog = '';
    foreach ($log as $l) {
        $l = explode('SQL:', $l);
        $l = $l[1];
        preg_match('/RunTime\:([0-9\.]+)s/', $l, $match);
        $sqltime += floatval($match[1]);
        $sqllog .= $l.'<br/>';
    }
    //print_r(Cache::$log);
    echo '<hr>';
    echo sprintf('PHP version: PHP %s', PHP_VERSION);
    echo ' Memories: '.'<br/>';
    echo 'ToTal: ',number_format(($mem_run_end - $mem_include_start) / 1024),'k','<br/>';
    echo 'Include:',number_format(($mem_run_start - $mem_include_start) / 1024),'k','<br/>';
    echo 'Run:',number_format(($mem_run_end - $mem_run_start) / 1024),'k<br/><hr/>';
    echo 'Time:<br/>';
    echo 'ToTal: ',$time_run_end - $time_include_start,'s<br/>';
    echo 'Include:',$time_run_start - $time_include_start,'s','<br/>';
    echo 'SQL:',$sqltime,'s<br/>';
    echo 'Run:',$time_run_end - $time_run_start,'s<br/>';
    echo 'RunDetail:<br />';
    $last_run_time = 0;
    foreach ($time_run_detail as $k => $v) {
        if ($last_run_time > 0) {
            echo '==='.$k.' '.floatval($v - $time_run_start).'s<br />';
            $last_run_time = floatval($v);
        } else {
            echo '==='.$k.' '.floatval($v - $last_run_time).'s<br />';
            $last_run_time = floatval($v);
        }
    }
    echo '<hr>';
    echo 'Run '.count($log).'SQL, '.$sqltime.'s <br />';
    echo $sqllog;
    echo '<hr>';
    $files = get_included_files();
    echo 'Include '.count($files).'files';
    dump($files);
    echo '<hr />';
}

// # The end
