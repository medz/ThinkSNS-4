<?php

define('ROOT_FILE', 'index.php');

$_GET['app'] = 'public';
$_GET['mod'] = 'Account';
$_GET['act'] = 'alipayReturn';

$_REQUEST = array_merge($_REQUEST, $_GET);

require __DIR__.'/index.php';
