<?php
/**
 * 频道版本信息.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
if (!defined('SITE_PATH')) {
    exit();
}

return array(
    // 应用名称 [必填]
    'NAME' => '频道',
    // 应用简介 [必填]
    'DESCRIPTION' => '可以通过投稿、管理员推荐、绑定用户等方式，聚合优质分享内容',
    // 托管类型 [必填]（0:本地应用，1:远程应用）
    'HOST_TYPE' => '0',
    // 前台入口 [必填]（格式：Action/act）
    'APP_ENTRY' => 'Index/index',
    // 为空
    'ICON_URL' => '',
    // 为空
    'LARGE_ICON_URL' => '',
    // 版本号 [必填]
    'VERSION_NUMBER' => '1',
    // 后台入口 [选填]
    'ADMIN_ENTRY' => 'channel/Admin/index',
    // 统计入口 [选填]（格式：Model/method）
    'STATISTICS_ENTRY' => 'Statistics/statistics',
    // 公司名称
    'COMPANY_NAME' => '智士软件',

    // 是否有移动端
    'HAS_MOBILE' => '1',
);
