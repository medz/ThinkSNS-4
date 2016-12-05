<?php
/*
 * 游客访问的黑/白名单，不需要开放的，可以注释掉
 * 应用的游客配置，转移到apps/app_name/Conf/access.inc.php下
 * 此处只配置不能后台修改的项目
 */
return array(
    'access' => array(
        'public/Register/*'           => true, // 注册
        'public/Passport/*'           => true, // 登录
        'public/Widget/*'             => true, // 插件
        'page/Index/index'            => true, // 自定义页面
        'public/Tool/*'               => true, // 升级查询
        'api/*/*'                     => true, // API
        'wap/*/*'                     => true, // wap版
        'w3g/*/*'                     => true, // 3G版
        'public/Account/alipayNotify' => true, //支付成功的通知，去掉之后无法成功充值
        'public/Account/alipayReturn' => true,
    ),
);
