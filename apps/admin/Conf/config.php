<?php

$menu = array(
    //后台头部TAB配置
    'admin_channel' => array(
        'index' => '首页', //L('PUBLIC_SYSTEM'),
        'system' => L('PUBLIC_SYSTEM'),
        'user' => L('PUBLIC_USER'),
        'content' => L('PUBLIC_CONTENT'),
        'task' => L('PUBLIC_TASK'),
        'apps' => L('PUBLIC_APPLICATION'),
        'extends' => '插件', //L('PUBLIC_EXPANSION'),
        'mobile' => '手机版',
        'application' => 'APP设置',
        // 'weixin'    => '微信管理',
    ),
    //后台菜单配置
    'admin_menu' => array(
        'index' => array(
            '首页' => array(
                L('PUBLIC_BASIC_INFORMATION') => U('admin/Home/statistics'),
                L('PUBLIC_VISIT_CALCULATION') => U('admin/Home/visitorCount'),
                //'资源统计'                         => U('admin/Home/sourcesCount'),
                // L('PUBLIC_MANAGEMENT_LOG')        => U('admin/Home/logs'),
                '管理日志' => U('admin/Home/logs'),
                '群发消息' => U('admin/Home/message'),
                // L('PUBLIC_MESSAGE_NOTIFY')         => U('admin/Home/message'),
                L('PUBLIC_SCHEDULED_TASK_NEWCREATE') => U('admin/Home/schedule'),
                //'数据字典'                         => U('admin/Home/systemdata'),
                L('PUBLIC_CLEANCACHE') => U('admin/Tool/cleancache'),
                '缓存配置' => U('admin/Home/cacheConfig'),
                '数据备份' => U('admin/Tool/backup'),
                '异常处理' => U('admin/Tool/index'),
                '在线升级' => U('admin/Upgrade/check'),
            ),
        ),

        'system' => array(
            L('PUBLIC_SYSTEM_SETTING') => array(
                L('PUBLIC_WEBSITE_SETTING') => U('admin/Config/site'),
                L('PUBLIC_NAVIGATION_SETTING') => U('admin/Config/nav'),
                L('PUBLIC_REGISTER_SETTING') => U('admin/Config/register'),
                '邀请配置' => U('admin/Config/invite'),
                // '游客配置'	=> U('admin/Config/guest'),
                L('PUBLIC_WEIBO_SETTING') => U('admin/Config/feed'),
                L('PUBLIC_EMAIL_SETTING') => U('admin/Config/email'),
                '短信配置' => U('admin/Config/sms'),
                L('PUBLIC_FILE_SETTING') => U('admin/Config/attach'),
                // L('PUBLIC_FILTER_SETTING')	=>	U('admin/Config/audit'),
                // '访问配置' => U('admin/Config/access'),
                L('PUBLIC_FILTER_SETTING') => U('admin/Config/sensitive'),
                L('PUBLIC_POINT_SETTING') => U('admin/Global/credit'),
                '充值配置' => U('admin/Config/charge'),
                '地区配置' => U('admin/Config/area'),
                // L('PUBLIC_LANGUAGE')	=>	U('admin/Config/lang'),
                 // L('PUBLIC_MAILTITLE_ADMIN')	=>	U('admin/Config/notify'),
                //L('PUBLIC_POINTS_SETTING')	=>  U('admin/Apps/setCreditNode'),
                    '部门配置' => U('admin/Department/index'),
                L('PUBLIC_AUTHORITY_SETTING') => U('admin/Apps/setPermNode'),
                // L('PUBLIC_WEIBO_TEMPLATE_SETTING')	=>  U('admin/Apps/setFeedNode'),
                'SEO配置' => U('admin/Config/setSeo'),
                // '页面配置同步' => U('admin/Config/updateAdminTab'),
                // 'UCenter配置' => U('admin/Config/setUcenter'),

                // '第三方视频接口配置' => U('admin/Config/youkuOutside'),
            ),
        ),

        'user' => array(
            L('PUBLIC_USER') => array(

                L('PUBLIC_USER_MANAGEMENT') => U('admin/User/index'),
                L('PUBLIC_USER_GROUP_MANAGEMENT') => U('admin/UserGroup/index'),
                L('PUBLIC_PROFILE_SETTING') => U('admin/User/profile'),
                '用户标签' => U('admin/User/category'),
                '用户认证' => U('admin/User/verifyCategory'),
                '找人配置' => U('admin/User/findPeopleConfig'),
                '找人推荐' => U('admin/User/officialCategory'),
            ),
        ),

        'content' => array(
            L('PUBLIC_CONTENT_MANAGEMENT') => array(
                // L('PUBLIC_ANNOUNCEMENT_SETTING')	=>	U('admin/Config/announcement'),
                L('PUBLIC_WEIBO_MANAGEMENT') => U('admin/Content/feed'),
                '话题管理' => U('admin/Content/topic'),
                L('PUBLIC_COMMENT_MANAGEMENT') => U('admin/Content/comment'),
                L('PUBLIC_PRIVATE_MESSAGE_MANAGEMENT') => U('admin/Content/message'),
                L('PUBLIC_FILE_MANAGEMENT') => U('admin/Content/attach'),
                '视频管理' => U('admin/Content/video'),
                L('PUBLIC_REPORT_MANAGEMENT') => U('admin/Content/denounce'),
                L('PUBLIC_TAG_MANAGEMENT') => U('admin/Home/tag'),
                L('PUBLIC_INVITE_CALCULATION') => U('admin/Home/invatecount'),
                // '模板管理'	=>	U('admin/Content/template'),
            ),
        ),
        'task' => array(
            L('PUBLIC_TASK_INFO') => array(
                L('PUBLIC_TASK_LIST') => U('admin/Task/index'),
                L('PUBLIC_TASK_REWARD') => U('admin/Task/reward'),
                '勋章列表' => U('admin/Medal/index'),
                '用户勋章' => U('admin/Medal/userMedal'),
                '任务配置' => U('admin/Task/taskConfig'),
            ),
        ),
        'apps' => array(
            L('PUBLIC_APP_MANAGEMENT') => array(
                L('PUBLIC_INSTALLED_APPLIST') => U('admin/Apps/index'),
                L('PUBLIC_UNINSTALLED_APPLIST') => U('admin/Apps/install'),
                //'在线应用'	=>	U('admin/Apps/onLineApp'),
            ),
        ),
        'extends' => array(
            '插件管理' => array(
                '所有插件列表' => U('admin/Addons/index'),
            ),
        ),
      //   'weixin'		=> array(
        // 	'微信管理' => array(
   //  			'所有插件列表' => U('admin/Addons/index','is_weixin=1'),
   //  		),
        // ),
        'mobile' => array(
            '3G版设置' => array(
                '常规设置' => U('admin/Mobile/setting'),
                '广场轮播' => U('admin/Mobile/w3gSlideShow'),
                '关于我们' => U('admin/Mobile/w3gAbout'),
            ),
        ),

        /* # APP seting */
        'application' => array(
            'seting' => array(
                '发现轮播' => U('admin/Application/index'),
                'Socket设置' => U('admin/Application/socket'),
                '极光推送' => U('admin/Application/jpush'),
                '关于我们' => U('admin/Application/about'),
                '反馈管理' => U('admin/Application/feedback'),
                '提现管理' => U('admin/Application/ZB_credit_order'),
            ),
        ),
    ),
);

$app_list = model('App')->getConfigList();
foreach ($app_list as $k => $v) {
    if ($k == '找伙伴') {
        continue;
    }
    $menu['admin_menu']['apps'][L('PUBLIC_APP_MANAGEMENT')][$k] = $v;
}

// $is_weixin = intval($_GET ['is_weixin']);
$_GET ['is_weixin'] = 0;
$plugin_list = model('Addon')->getAddonsAdminUrl();
foreach ($plugin_list as $k => $v) {
    $menu['admin_menu']['extends']['插件管理'][$k] = $v;
}
// $_GET ['is_weixin'] = 1;
// $plugin_list = model('Addon')->getAddonsAdminUrl();
// foreach($plugin_list as $k=>$v){
// 	$menu['admin_menu']['weixin']['微信管理'][$k] = $v;
// }
// $_GET ['is_weixin'] = $is_weixin;

return $menu;
