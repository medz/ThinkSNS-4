<?php
/*
 * 游客访问的黑/白名单，不需要开放的，可以注释掉
 * 此处只配置不能后台修改的项目
 */
return array(
    'access' => array(
        'Oauth/*'                            => true,
        'ProductShare/*'                     => true,
        'Public/*'                           => true,
        'LiveUser/postUser'                  => true,
        'Information/reader'                 => true,
        'LiveOauth/ZB_User_Get_AuthByTicket' => true,
        'LiveGift/*'                         => true,
        'LiveUser/*'                         => true,
        'Application/getVersion'             => true,
        'Application/test'                   => true,
        'Application/getZBConfig'            => true,
        'Credit/alipayNotify'                => true,
        'Credit/weixinNotify'                => true,
        //极铺登录TS账户
        'Jipu/authorize'      => true,
        'Jipu/register'       => true,
        'Jipu/save_user_info' => true,
    ),
);
