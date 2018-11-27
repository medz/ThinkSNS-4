<?php

/* # 检查PHP版本是否符合运行要求 */
if (version_compare(PHP_VERSION, '5.3.12', '<')) {
    header('Content-Type:text/html;charset=utf-8');
    echo '您的PHP版本为：', PHP_VERSION,
         '<br>',
         'ThinkSNS程序运行版本不得低于：PHP 5.3.12';
    exit;

/* # 检查是否安装过ThinkSNS */
} elseif (is_dir(__DIR__.'/install') and !file_exists(__DIR__.'/data/install.lock')) {
    header('location:install/install.php');
    exit;
}

// 新的系统核心接入
require dirname(__FILE__).'/src/bootstrap.php';

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
