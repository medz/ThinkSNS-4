<?php

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
define('API_VERSION', 'live_v4.5.0');

require dirname(__FILE__).'/src/bootstrap.php';
Api::run();
