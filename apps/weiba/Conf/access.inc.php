<?php
/*
 * 游客访问的黑/白名单，不需要开放的，可以注释掉
 */
return array(
    'access' => array(
        // 微吧
        'weiba/Index/index'      => true,
        'weiba/Index/detail'     => true,
        'weiba/Index/postDetail' => true,
        'weiba/Index/postList'   => true,
        'weiba/Index/weibaList'  => true,
    ),
);
