<?php

use Ts\Helper\AutoJumper\Jumper;

// PC to h5 | weiba帖子详情
Jumper::add(function ($app, $mod, $act, array $args) {
    if (strtolower($app) == 'weiba' && strtolower($mod) == 'index' && strtolower($act) == 'postdetail') {
        return sprintf('%s/%s?app=h5#/weiba/post/%s', SITE_URL, ROOT_FILE, $args['post_id']);
    }

    return false;
});

// pc to h5 | 分享详情
Jumper::add(function ($app, $mod, $act, array $args) {
    list($app, $mod, $act) = array_map(
        function ($key) {
            return strtolower($key);
        },
        array($app, $mod, $act)
    );

    if ($app == 'public' && $mod == 'profile' && $act == 'feed') {
        return sprintf('%s/%s?app=h5#/feed/reader/%d', SITE_URL, ROOT_FILE, intval($args['feed_id']));
    }

    return false;
});

// all to h5
Jumper::add(function () {
    return sprintf('%s/%s?app=h5', SITE_URL, ROOT_FILE);
});
