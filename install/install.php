<?php
/**
 * ThinkSNS安装文件，修改自pbdigg。
 */
error_reporting(0);
set_time_limit(600);

define('THINKSNS_INSTALL', true);
define('THINKSNS_ROOT', str_replace('\\', '/', substr(dirname(__FILE__), 0, -7)));

//session 设置
ini_set('session.cookie_httponly', 1);
//设置session路径到本地
if (strtolower(ini_get('session.save_handler')) == 'files') {
    $session_dir = THINKSNS_ROOT.'/data/session/';
    if (!is_dir($session_dir)) {
        mkdir($session_dir, 0777, true);
    }
    session_save_path($session_dir);
}
session_start();

$_TSVERSION = '4-R2（4.3.3）';

include 'install_function.php';
include 'install_lang.php';

$timestamp = time();
$ip = getip();
$installfile = 'ThinkSNS.sql';
$installdbname = 'thinksns_4_r2';
$thinksns_config_file = 'config.inc.php';
$_SESSION['thinksns_install'] = $timestamp;

/* # 设置响应头 */
header('Content-Type: text/html; charset=utf-8');

/* # 判断是否存在安装锁 */
if (file_exists(THINKSNS_ROOT.'/data/install.lock')) {
    echo $i_message['install_lock'];
    exit;

/* # 判断安装sql文件是否可读 */
} elseif (!is_readable($installfile)) {
    echo $i_message['install_dbFile_error'];
    exit;
}

$quit = false;
$msg = $alert = $link = $sql = $allownext = '';

$PHP_SELF = addslashes(htmlspecialchars($_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']));
set_magic_quotes_runtime(0);
if (!get_magic_quotes_gpc()) {
    addS($_POST);
    addS($_GET);
}
@extract($_POST);
@extract($_GET);
?>
<html>
<head>
<title><?php echo $i_message['install_title']; ?></title>
<meta http-equiv=Content-Type content="text/html; charset=utf-8">
<link href="images/style.css" rel="stylesheet" type="text/css" />
<body>
<div id='content'>
<div id='pageheader'>
	<div id="logo">
		<img src="images/thinksns.gif" border="0" alt="ThinkSNS" />
		<span class="h1"><?php echo $i_message['install_wizard']; ?></span>
	</div>
</div>
<div id='innercontent'>
<?php
if (!$v) {
    ?>

<h2><?php echo $i_message['install_license_title']; ?></h2>
<p>
<textarea class="textarea" readonly cols="50">
<?php echo $i_message['install_license']; ?>
</textarea>
</p>
<form action="install.php?v=2" method="post">
<p class="center"><input type="submit" style="width:200px;" class="submit" name="next" value="同意协议并安装" /></p>
</form>
<?php

} elseif ($v == '2') {
    if ($agree == 'no') {
        echo '<script>alert('.$i_message['install_disagree_license'].');history.go(-1)</script>';
    }
    $dirarray = array(
        'data',
        'storage',
        'install',
        'config',
    );
    $writeable = array();
    foreach ($dirarray as $key => $dir) {
        if (writable($dir)) {
            $writeable[$key] = $dir.result(1, 0);
        } else {
            $writeable[$key] = $dir.result(0, 0);
            $quit = true;
        }
    } ?>
<h2><?php echo $i_message['install_env']; ?></h2>
<div class="shade">
<h5><?php echo $i_message['php_os']; ?>&nbsp;&nbsp;<span class="p"><?php echo PHP_OS;
    result(1, 1); ?></span></h5>

<h5><?php echo $i_message['php_version']; ?>&nbsp;&nbsp;<span class="p"><?php
echo PHP_VERSION;
    if (version_compare(PHP_VERSION, '5.3.12', '<')) {
        result(0, 1);
        $quit = true;
    } else {
        result(1, 1);
    } ?></span></h5>

<h5><?php echo $i_message['php_memory']; ?>&nbsp;&nbsp;<span class="p"><?php
echo $i_message['support'],'/',@ini_get('memory_limit');
    if ((int) @ini_get('memory_limit') < (int) '32M') {
        result(0, 1);
        $quit = true;
    } else {
        result(1, 1);
    } ?></span></h5>

<h5><?php echo $i_message['php_session']; ?>&nbsp;&nbsp;<span class="p"><?php
$session_path = @ini_get('session.save_path');
    if (!isset($_SESSION['thinksns_install'])) {
        echo '<span class="red">'.$i_message['php_session_error'].': '.$session_path.'</span>';
        result(0, 1);
        $quit = true;
    } else {
        echo $i_message['support'];
        result(1, 1);
    } ?></span></h5>

<h5><?php echo $i_message['file_upload']; ?>&nbsp;&nbsp;<spam class="p"><?php
if (@ini_get('file_uploads')) {
        echo $i_message['support'],'/',@ini_get('upload_max_filesize');
    } else {
        echo '<span class="red">'.$i_message['unsupport'].'</span>';
    }
    result(1, 1); ?></spam></h5>

<h5><?php echo $i_message['mysql']; ?>&nbsp;&nbsp;<span class="p"><?php
if (function_exists('mysql_connect')) {
        echo $i_message['support'];
        result(1, 1);
    } else {
        echo '<span class="red">'.$i_message['mysql_unsupport'].'</span>';
        result(0, 1);
        $quit = true;
    } ?></span></h5>

<h5><?php echo $i_message['php_extention']; ?></h5>
<p>&nbsp;&nbsp;
<?php
if (extension_loaded('mysql')) {
        echo 'mysql:'.$i_message['support'];
        result(1, 1);
    } else {
        echo '<span class="red">'.$i_message['php_extention_unload_mysql'].'</span>';
        result(0, 1);
        $quit = true;
    } ?></p>
<p>&nbsp;&nbsp;
<?php
if (extension_loaded('gd')) {
        echo 'gd:'.$i_message['support'];
        result(1, 1);
    } else {
        echo '<span class="red">'.$i_message['php_extention_unload_gd'].'</span>';
        result(0, 1);
        $quit = true;
    } ?></p>
<p>&nbsp;&nbsp;
<?php
if (extension_loaded('curl')) {
        echo 'curl:'.$i_message['support'];
        result(1, 1);
    } else {
        echo '<span class="red">'.$i_message['php_extention_unload_curl'].'</span>';
        result(0, 1);
        $quit = true;
    } ?></p>
<p>&nbsp;&nbsp;
<?php
if (extension_loaded('mbstring')) {
        echo 'mbstring:'.$i_message['support'];
        result(1, 1);
    } else {
        echo '<span class="red">'.$i_message['php_extention_unload_mbstring'].'</span>';
        result(0, 1);
        $quit = true;
    } ?></p>

</div>
<h2><?php echo $i_message['dirmod']; ?></h2>
<div class="shade">
<?php
foreach ($writeable as $value) {
        echo '<p>'.$value.'</p>';
    } ?>

</div>
<p class="center">
	<form method="get" action='install.php?v=3' style="text-align: center;">
	<input type="hidden" name="v" value="3">
	<input style="width:200px;" type="submit" class="submit" value="<?php echo $i_message['install_next']; ?>" <?php if ($quit) {
        echo 'disabled="disabled"';
    } ?>>
	</form>
</p>
<?php

} elseif ($v == '3') {
    ?>
<!-- <h2><?php echo $i_message['install_setting']; ?></h2> -->
<form method="post" action="install.php?v=4" id="install" onSubmit="return check(this);">
	<h2><?php echo $i_message['install_mysql']; ?></h2>
<div class="shade">

<h5><?php echo $i_message['install_mysql_host']; ?></h5>

<p><input type="text" name="db_host" value="localhost" size="40" class='input' placeholder="<?php echo $i_message['install_mysql_host_intro']; ?>" /></p>

<h5><?php echo $i_message['install_mysql_username']; ?></h5>
<p><input type="text" name="db_username" value="root" size="40" class='input' /></p>

<h5><?php echo $i_message['install_mysql_password']; ?></h5>
<p><input type="password" name="db_password" value="" size="40" class='input' /></p>

<h5><?php echo $i_message['install_mysql_name']; ?></h5>
<p><input type="text" name="db_name" value="<?php echo $installdbname; ?>" size="40" class='input' />
</p>

<h5><?php echo $i_message['install_mysql_prefix']; ?></h5>

<p><input type="text" name="db_prefix" value="ts_" size="40" class='input' placeholder="<?php echo $i_message['install_mysql_prefix_intro']; ?>" /></p>

<h5><?php echo $i_message['site_url']; ?></h5>
<p><?php echo $i_message['site_url_intro']; ?></p>
<p><input type="text" name="site_url" value="<?php echo 'http://'.$_SERVER['HTTP_HOST'].rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/'); ?>" size="40" class='input' /></p>

</div>

<h2><?php echo $i_message['founder']; ?></h2>
<div class="shade">

<h5><?php echo $i_message['auto_increment']; ?></h5>
<p><input type="text" name="first_user_id" value="1" size="40" class='input' /></p>

<h5><?php echo $i_message['install_founder_email']; ?></h5>
<p><input type="text" name="email" value="admin@admin.com" size="40" class='input' /></p>

<h5><?php echo $i_message['install_founder_password']; ?></h5>
<p><input type="password" name="password" value="" size="40" class='input' /></p>

<h5><?php echo $i_message['install_founder_rpassword']; ?></h5>
<p><input type="password" name="rpassword" value="" size="40" class='input' /></p>


</div>
<div class="">
	<input type="submit" class="submit" name="next" value="<?php echo $i_message['install_next']; ?>" style="width:200px;margin-left: 23px;">
</form>
</div>
<script type="text/javascript" language="javascript">
function check(obj)
{
	if (!obj.db_host.value)
	{
		alert('<?php echo $i_message['install_mysql_host_empty']; ?>');
		obj.db_host.focus();
		return false;
	}
	else if (!obj.db_username.value)
	{
		alert('<?php echo $i_message['install_mysql_username_empty']; ?>');
		obj.db_username.focus();
		return false;
	}
	else if (!obj.db_name.value)
	{
		alert('<?php echo $i_message['install_mysql_name_empty']; ?>');
		obj.db_name.focus();
		return false;
	}
	else if (obj.password.value.length < 6)
	{
		alert('<?php echo $i_message['install_founder_password_length']; ?>');
		obj.password.focus();
		return false;
	}
	else if (obj.password.value != obj.rpassword.value)
	{
		alert('<?php echo $i_message['install_founder_rpassword_error']; ?>');
		obj.rpassword.focus();
		return false;
	}
	else if (!obj.email.value)
	{
		alert('<?php echo $i_message['install_founder_email_empty']; ?>');
		obj.email.focus();
		return false;
	}
	return true;
}
</script>
<?php

} elseif ($v == '4') {
    if (empty($db_host) || empty($db_username) || empty($db_name) || empty($db_prefix)) {
        $msg .= '<p>'.$i_message['mysql_invalid_configure'].'<p>';
        $quit = true;
    } elseif (!@mysql_connect($db_host, $db_username, $db_password)) {
        $msg .= '<p>'.mysql_error().'</p>';
        $quit = true;
    }
    if (strstr($db_prefix, '.')) {
        $msg .= '<p>'.$i_message['mysql_invalid_prefix'].'</p>';
        $quit = true;
    }

    if (strlen($password) < 6) {
        $msg .= '<p>'.$i_message['founder_invalid_password'].'</p>';
        $quit = true;
    } elseif ($password != $rpassword) {
        $msg .= '<p>'.$i_message['founder_invalid_rpassword'].'</p>';
        $quit = true;
    } elseif (!preg_match('/^[_\.0-9a-zA-Z-]+@([0-9a-zA-Z][0-9a-zA-Z-]+\.)+[a-zA-Z]{2,3}$/i', $email)) {
        $msg .= '<p>'.$i_message['founder_invalid_email'].'</p>';
        $quit = true;
    } else {
        $forbiddencharacter = array('\\', '&', ' ', "'", '"', '/', '*', ',', '<', '>', "\r", "\t", "\n", '#', '$', '(', ')', '%', '@', '+', '?', ';', '^');
        foreach ($forbiddencharacter as $value) {
            if (strpos($username, $value) !== false) {
                $msg .= '<p>'.$i_message['	'].'</p>';
                $quit = true;
                break;
            }
        }
    }

    if ($quit) {
        $allownext = 'disabled="disabled"'; ?>
		<?php
        echo $msg;
    } else {
        $config_file_content = array();
        $config_file_content['db_host'] = $db_host;
        $config_file_content['db_name'] = $db_name;
        $config_file_content['db_username'] = $db_username;
        $config_file_content['db_password'] = $db_password;
        $config_file_content['db_prefix'] = $db_prefix;
        $config_file_content['db_pconnect'] = 0;
        $config_file_content['db_charset'] = 'utf8';
        $config_file_content['dbType'] = 'MySQL';

        $default_manager_account = array();
        $default_manager_account['email'] = $email;
        $default_manager_account['password'] = md5(md5($password).'11111');

        $_SESSION['config_file_content'] = $config_file_content;
        $_SESSION['default_manager_account'] = $default_manager_account;
        $_SESSION['first_user_id'] = $first_user_id;
        $_SESSION['site_url'] = $site_url; ?>
	<div class="botBorder">
		<p><?php echo $i_message['install_founder_name'], ': ', $email?></p>
		<p><?php echo $i_message['install_founder_password'], ': ', $password; ?></p>
	</div>
<?php

    } ?>
	<div class="botBorder">

<?php
if (!$quit) {
        //写配置文件
$randkey = uniqid(rand());
        $fp = fopen(THINKSNS_ROOT.'/config/'.$thinksns_config_file, 'wb');
        $configfilecontent = <<<EOT
<?php
if (!defined('SITE_PATH')) exit();
return array(
	// 数据库常用配置
	'DB_TYPE'       => 'mysql',       // 数据库类型

	'DB_HOST'       => '$db_host',    // 数据库服务器地址
	'DB_NAME'       => '$db_name',    // 数据库名
	'DB_USER'       => '$db_username',// 数据库用户名
	'DB_PWD'        => '$db_password',// 数据库密码

	'DB_PORT'       => 3306,        // 数据库端口
	'DB_PREFIX'     => '$db_prefix',// 数据库表前缀（因为漫游的原因，数据库表前缀必须写在本文件）
	'DB_CHARSET'    => 'utf8',      // 数据库编码
	'SECURE_CODE'   => '$randkey',  // 数据加密密钥
	'COOKIE_PREFIX' => 'TS4_',      // # cookie
);
EOT;
        $configfilecontent = str_replace('SECURE_TEST', 'SECURE'.rand(10000, 20000), $configfilecontent);
        chmod(THINKSNS_ROOT.'/config/'.$thinksns_config_file, 0777);
        $result_1 = fwrite($fp, trim($configfilecontent));
        @fclose($fp);

        if ($result_1 && file_exists(THINKSNS_ROOT.'/config/'.$thinksns_config_file)) {
            ?>
	<p><?php echo $i_message['config_log_success']; ?></p>
<?php

        } else {
            ?>
	<p><?php echo $i_message['config_read_failed'];
            $quit = true; ?></p>
<?php

        }
    } ?>
	</div>
	<div class="center">
		<form method="post" action="install.php?v=5">
		<input type="button" class="submit" name="prev" value="<?php echo $i_message['install_prev']; ?>" onClick="history.go(-1)">&nbsp;
		<input type="submit" class="submit" name="next" value="<?php echo $i_message['install_next']; ?>" <?php echo $allownext; ?> >
		</form>
	</div>
<?php

} elseif ($v == '5') {
    $db_config = $_SESSION['config_file_content'];

    if (!$db_config['db_host'] && !$db_config['db_name']) {
        $msg .= '<p>错误:'.$i_message['configure_read_failed'].'</p>';
        $quit = true;
    } else {
        mysql_connect($db_config['db_host'], $db_config['db_username'], $db_config['db_password']);
        $sqlv = mysql_get_server_info();
        if (version_compare($sqlv, '5.0.0', '<')) {
            $msg .= '<p>错误:'.$i_message['mysql_version_402'].'</p>';
            $quit = true;
        } else {
            $db_charset = $db_config['db_charset'];
            $db_charset = (strpos($db_charset, '-') === false) ? $db_charset : str_replace('-', '', $db_charset);

            mysql_query(" CREATE DATABASE IF NOT EXISTS `{$db_config['db_name']}` DEFAULT CHARACTER SET $db_charset ");

            if (mysql_errno()) {
                $errormsg = mysql_error();
                $msg .= '<p>错误:'.($errormsg ? $errormsg : $i_message['database_errno']).'</p>';
                $quit = true;
            } else {
                mysql_select_db($db_config['db_name']);
            }

            //判断是否有用同样的数据库前缀安装过
            $re = mysql_query("SELECT COUNT(1) FROM {$db_config['db_prefix']}user");
            $link = @mysql_fetch_row($re);

            if (intval($link[0]) > 0) {
                $thinksns_rebuild = true;
                $msg .= '<p>错误:'.$i_message['thinksns_rebuild'].'</p>';
                $alert = ' onclick="return confirm(\''.$i_message['thinksns_rebuild'].'\');"';
            }
        }
    }

    if ($quit) {
        $allownext = 'disabled="disabled"'; ?>
<?php
    echo $msg;
    } else {
        ?>
<div class="botBorder">
<?php
if ($thinksns_rebuild) {
            ?>
<p style="color:red;font-size:16px;"><?php echo $i_message['thinksns_rebuild']; ?></p>
<?php

        } ?>
<p><?php echo $i_message['mysql_import_data']; ?></p>
</div>
<?php

    } ?>
<div class="center">
	<form method="post" action="install.php?v=6">
	<input type="button" class="submit" name="prev" value="<?php echo $i_message['install_prev']; ?>" onClick="history.go(-1)">&nbsp;
	<input type="submit" class="submit" name="next" value="<?php echo $i_message['install_next']; ?>" <?php echo $allownext,$alert?>>
	</form>
</div>
<?php

} elseif ($v == '6') {
    $db_config = $_SESSION['config_file_content'];

    mysql_connect($db_config['db_host'], $db_config['db_username'], $db_config['db_password']);
    if (mysql_get_server_info() > '5.0') {
        mysql_query("SET sql_mode = ''");
    }
    $db_config['db_charset'] = (strpos($db_config['db_charset'], '-') === false) ? $db_config['db_charset'] : str_replace('-', '', $db_config['db_charset']);
    mysql_query("SET character_set_connection={$db_config['db_charset']}, character_set_results={$db_config['db_charset']}, character_set_client=binary");
    mysql_select_db($db_config['db_name']);
    $tablenum = 0;

    $fp = fopen($installfile, 'rb');
    $sql = fread($fp, filesize($installfile));
    fclose($fp); ?>
<div class="botBorder">
<h4><?php echo $i_message['import_processing']; ?></h4>
<div style="overflow-y:scroll;height:100px;width:715px;padding:5px;border:1px solid #ccc;">
<?php
    $db_charset = $db_config['db_charset'];
    $db_prefix = $db_config['db_prefix'];
    $sql = str_replace("\r", "\n", str_replace('`'.'ts_', '`'.$db_prefix, $sql));
    foreach (explode(";\n", trim($sql)) as $query) {
        $query = trim($query);
        if ($query) {
            if (substr($query, 0, 12) == 'CREATE TABLE') {
                $name = preg_replace('/CREATE TABLE ([A-Z ]*)`([a-z0-9_]+)` .*/is', '\\2', $query);
                echo '<p>'.$i_message['create_table'].' '.$name.' ... <span class="blue">OK</span></p>';
                @mysql_query(createtable($query, $db_charset));
                $tablenum++;
            } else {
                @mysql_query($query);
            }
        }
    } ?>
</div>
</div>
<div class="botBorder">
<h4><?php echo $i_message['create_founder']; ?></h4>

<?php
    //设置网站用户起始ID
    if (intval($_SESSION['first_user_id']) > 0) {
        $admin_id = intval($_SESSION['first_user_id']);
        $sql0 = "ALTER TABLE `{$db_config['db_prefix']}user` AUTO_INCREMENT=".$admin_id.';';
        if (mysql_query($sql0)) {
            echo '<p>'.$i_message['set_auto_increment_success'].'... <span class="blue">OK..</span></p>';
        } else {
            echo '<p>'.$i_message['set_auto_increment_error'].'... <span class="red">ERROR</span></p>';
            $admin_id = 1;
        }
    } else {
        $admin_id = 1;
    }
    //添加管理员
    $siteFounder = $_SESSION['default_manager_account'];

    $sql1 = 'INSERT INTO `%s` (`uid`, `password`, `login_salt`, `uname`, `email`, `phone`, `sex`, `location`, `is_audit`, `is_active`, `is_init`, `ctime`, `identity`, `api_key`, `domain`, `province`, `city`, `area`, `reg_ip`, `lang`, `timezone`, `is_del`, `first_letter`, `intro`, `last_login_time`, `last_feed_id`, `last_post_time`, `search_key`, `invite_code`, `feed_email_time`, `send_email_time`, `openid`, `input_city`, `is_fixed`) VALUES (%d, "%s", "11111", "管理员", "%s", NULL, 1, "北京 北京市 海淀区", 1, 1, 1, "%s", 1, NULL, "", 1, 2, 10, "127.0.0.1", "zh-cn", "PRC", 0, "G", "", "", 0, 0, "管理员 guanliyuan", NULL, 0, 0, NULL, NULL, 1);';
    $sql1 = sprintf($sql1, $db_config['db_prefix'].'user', $admin_id, $siteFounder['password'], $siteFounder['email'], time());
    // echo $sql1;
    if (mysql_query($sql1)) {
        echo '<p>'.$i_message['create_founderpower_success'].'... <span class="blue">OK</span></p>';
    } else {
        echo '<p>'.$i_message['create_founderpower_error'].'... <span class="red">ERROR</span></p>';
        $quit = true;
    }

    //将管理员加入“管理员”用户组
    $sql_user_group = "INSERT INTO `{$db_config['db_prefix']}user_group_link` (`id`,`uid`,`user_group_id`) VALUES ('1', ".$admin_id.",'1');";
    if (mysql_query($sql_user_group)) {
    } else {
        $quit = true;
    }

    if (!$quit) {
        /* # 写入锁文件 */
        file_put_contents(THINKSNS_ROOT.'/data/install.lock', 'ThinkSNS lock file');
    } else {
        echo '请重新安装';
    } ?>
</div>
<div class="botBorder">
<h4><?php echo $i_message['install_success']; ?></h4>
<?php echo $i_message['install_success_intro']; ?>
</div>
<iframe src="<?php echo $_SESSION['site_url']; ?>/cleancache.php?all" height="0" width="0" style="display: none;"></iframe>
<?php

}
?>
</div>
<div class='copyright'>ThinkSNS <?php echo $_TSVERSION; ?> &#169; copyright 2008-<?php echo date('Y') ?> www.ThinkSNS.com All Rights Reserved</div>
</div>
<div style="display:none;">
<script src="http://s79.cnzz.com/stat.php?id=1702264&web_id=1702264" language="JavaScript" charset="gb2312"></script>
</div>
</body>
</html>