<?php
/**
 * 安�
 * 频道应用.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
if (!defined('SITE_PATH')) {
    exit();
}
// 头文件设置
header('Content-Type:text/html;charset=utf-8;');
// 安装SQL文件
$sql_file = APPS_PATH.'/channel/Appinfo/install.sql';
// 执行sql文件
$res = D('')->executeSqlFile($sql_file);
// 错误处理
if (!empty($res)) {
    echo $res['error_code'];
    echo '<br />';
    echo $res['error_sql'];
    // 清除已导入的数据
    include_once APPS_PATH.'/channel/Appinfo/uninstall.php';
    exit;
}
