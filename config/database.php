<?php

// 数据库配置文件，目前用于合并Ts-4数据库配置

$config = array();

dirname(__FILE__).'/database/old_ts_config.php' and
$config = array_merge($config, (array) include dirname(__FILE__).'/database/old_ts_config.php');

dirname(__FILE__).'/database/config.php' and
$config = array_merge($config, (array) include dirname(__FILE__).'/database/config.php');

return $config;
