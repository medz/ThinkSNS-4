<?php

define('ROOT_FILE', 'index.php');

$_GET['mod'] = 'Credit';
$_GET['act'] = 'alipayNotify';

$_REQUEST = array_merge($_REQUEST, $_GET);

require __DIR__.'/api.php';
