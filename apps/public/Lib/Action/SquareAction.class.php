<?php

class SquareAction extends Action
{
    public function _initialize()
    {
        header('Content-Type:application/x-javascript; charset=UTF8');
    }

    // PC端广场页面
    public function home()
    {
        $this->display('Index/share');
    }

    // 全站分享
    public function index()
    {
        $map['count'] = 20;
        $result = api('WeiboStatuses')->data($map)->public_timeline();
        // dump($result);
        foreach ($result as $k => $v) {
            $v['uname'] = preg_replace("/\s/", '', $v['uname']);
            $v['content'] = preg_replace("/\s/", '', $v['content']);
            $html = '<li><div class="img"><img src="'.$v['avatar_small'].'" /><strong><a href="/sns/index.php?app=public&mod=Profile&act=feed&feed_id='.$v['feed_id'].'&uid='.$v['uid'].'" target="_blank">    '.$v['uname'].'</a></strong></div><div class="msg"><p><a style="color:#666;font-size:12px;" href="/sns/index.php?app=public&mod=Profile&act=feed&feed_id='.$v['feed_id'].'&uid='.$v['uid'].'" target="_blank"> '.htmlspecialchars($v['content']).'</a></p><p class="from">'.$v['ctime'].'</p></div></li>';
            echo 'document.write(\''.$html.'\');';
        }
    }

    // 频道分享
    public function channel()
    {
        $map['category_id'] = intval($_GET['cid']);
        $map['count'] = 20;
        // $map['page'] = 1;
        $result = api('Channel')->data($map)->get_channel_feed();
        // dump(M()->getLastSql());
        foreach ($result as $k => $v) {
            $v['source_body'] = preg_replace("/\s/", '', $v['source_body']);
            $html = ' <li><a href="/sns/index.php?app=public&mod=Profile&act=feed&feed_id='.$v['feed_id'].'&uid='.$v['uid'].'" target="_blank">'.strip_tags($v['source_body']).'</a><span>'.substr($v['ctime'], 6, 10).'</span></li>	';
            echo 'document.write(\''.$html.'\');';
        }
    }

    // 话题分享
    public function topic()
    {
        $topic = t($_GET['k']);
        $feed_ids = model('FeedTopic')->getFeedIdByTopic($topic);
        $result = model('Feed')->getFeeds($feed_ids);
        dump($result);
    }
}
