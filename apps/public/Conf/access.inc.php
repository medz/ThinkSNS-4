<?php
/*
 * 游客访问的黑/白名单，不需要开放的，可以注释掉
 */
return array(
    'access' => array(

        //搜索
        'public/Search/*' => true,

        //网站公告
        'public/Index/announcement' => true,

        // 个人主页
        'public/Profile/index'     => true,
        'public/Profile/following' => true,
        'public/Profile/follower'  => true,
        'public/Profile/data'      => true,
        //分享配图
        'public/Profile/get_feed_img' => true,

        // 分享内容
        'public/Profile/feed' => true,

        // 分享话题
        'public/Topic/index' => true,

        // 分享排行榜
        'public/Rank/*' => true,

        'public/Feed/addDigg' => true,
        'public/Feed/delDigg' => true,
        // 分享话题
        'public/Index/index' => true,
        //查看大图
        'public/Feed/showBigImage'  => true,
        'public/Feed/ajaxWeiboInfo' => true,
        'public/Feed/ajaxImageInfo' => true,

        'public/Feed/video_exist' => true, /* # 视频状态 */
    ),

);
