<?php

//视频接口
class VideoApi extends Api
{
    //获取视频列表
    public function video_list()
    {
        // $list = D('video')->order('video_id desc')->findPage(10);
        // $map['type'] = 'postvideo';
        // $list = model('Feed')->getList($map);
        // foreach ($list['data'] as $k => &$v) {
        // 	$v['image_path'] = SITE_URL.$v['image_path'];
        // 	$v['video_path'] = SITE_URL.$v['video_path'];
        // }
        $list = model('Feed')->video_list('postvideo', $this->since_id, $this->max_id, $this->count, $this->page, '', true) ;
        foreach ($list as $k => &$v) {
            $timeline = D('video')->where('video_id='.$v['video_id'])->getField('timeline');
            $v['timeline'] = model('Video')->timeline_format($timeline);
        }

        return $list;
    }

    public function showVideo()
    {
        $feed = model('Feed')->getFeedInfo($this->id, true);    //getFeedInfo获取指定分享的信息，用于资源模型输出???
        $diggarr = model('FeedDigg')->checkIsDigg($this->id, $this->mid);
        $feed['is_digg'] = $diggarr[$this->id] ? 1 : 0;

        return $feed;
    }

    //获取视频列表
    public function my_video_list()
    {
        // $list = D('video')->order('video_id desc')->findPage(10);
        // $map['type'] = 'postvideo';
        // $list = model('Feed')->getList($map);
        // foreach ($list['data'] as $k => &$v) {
        // 	$v['image_path'] = SITE_URL.$v['image_path'];
        // 	$v['video_path'] = SITE_URL.$v['video_path'];
        // }
        $this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
        $sql = ' AND uid='.$this->user_id;
        $list = model('Feed')->video_list('postvideo', $this->since_id, $this->max_id, $this->count, $this->page, $sql, 'feed_id DESC') ;
        foreach ($list as $k => &$v) {
            $timeline = D('video')->where('video_id='.$v['video_id'])->getField('timeline');
            $v['timeline'] = model('Video')->timeline_format($timeline);
        }

        return $list;
    }
}
