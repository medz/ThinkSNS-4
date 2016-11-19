<?php

// 用于获取Ts-4旧的数据库配置

$config = array();
$file = dirname(dirname(__FILE__)).'/config.inc.php';

if (file_exists($file)) {
    // 读取文件，使用正则取得，避免一些旧的常量使用
    $oldConfig = file_get_contents($file);
    preg_match_all('/(DB_(\w+))(\W+)=>(\W+?)[\']?(.*?)[\']?\,/is', $oldConfig, $matches);

    $oldConfig = array();
    foreach ($matches[1] as $key => $value) {
        $oldConfig[$value] = $matches[5][$key];
    }

    $config['driver'] = $oldConfig['DB_TYPE'];
    $config['host'] = $oldConfig['DB_HOST'];
    $config['database'] = $oldConfig['DB_NAME'];
    $config['username'] = $oldConfig['DB_USER'];
    $config['password'] = $oldConfig['DB_PWD'];
    $config['charset'] = $oldConfig['DB_CHARSET'];
    $config['port'] = $oldConfig['DB_PORT'];
    $config['prefix'] = $oldConfig['DB_PREFIX'];
}

return $config;
