<?php

class FeedTopHomeHooks extends Hooks
{
    private $isRefresh = 1;

    public function __construct()
    {
        if (strtolower(ACTION_NAME) === 'index' && strtolower(MODULE_NAME) === 'profile' && strtolower(APP_NAME) === 'public') {
            $this->isRefresh = 1;
        }

        parent::__construct();
    }

    public function check_feed_manage($params)
    {
        $data = array();
        if ($this->mid == $params['uid']) {
            $data['status'] = true;
        } else {
            $data['status'] = false;
        }
        $data['plugin'] = 'feed_top_home';
        $params['plugin_list'][] = $data;
    }

    public function feed_manage_li($data)
    {
        if ($this->mid != $data['uid']) {
            return '';
        }
        $checked = $this->model('FeedTopHome')->checkedFeedTop($data['uid'], $data['feed_id']);
        if ($checked) {
            echo '<li><a href="javascript:;" rel="feed_top" onclick="delFeedHomeTop('.$data['uid'].', '.$data['feed_id'].', '.$this->isRefresh.')">取消空间置顶</a></li>';
        } else {
            echo '<li><a href="javascript:;" rel="feed_top" onclick="addFeedHomeTop('.$data['uid'].', '.$data['feed_id'].', '.$this->isRefresh.')">空间分享置顶</a></li>';
        }
    }

    public function add_feed_top_home()
    {
        $uid = intval($_POST['uid']);
        $feedId = intval($_POST['feed_id']);
        if (empty($uid) || empty($feedId)) {
            exit(json_encode(array('status' => 0, 'info' => '设置失败')));
        }
        $result = $this->model('FeedTopHome')->setFeedTopHome($uid, $feedId);
        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['info'] = '设置成功';
        } else {
            $res['status'] = 0;
            $res['info'] = '设置失败';
        }
        exit(json_encode($res));
    }

    public function del_feed_top_home()
    {
        $uid = intval($_POST['uid']);
        $feedId = intval($_POST['feed_id']);
        if (empty($uid) || empty($feedId)) {
            exit(json_encode(array('status' => 0, 'info' => '设置失败')));
        }
        $result = $this->model('FeedTopHome')->delFeedTopHome($uid, $feedId);
        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['info'] = '设置成功';
        } else {
            $res['status'] = 0;
            $res['info'] = '设置失败';
        }
        exit(json_encode($res));
    }

    public function feed_top_home()
    {
        $uid = intval($_GET['uid']);
        $list = $this->model('FeedTopHome')->getFeedTopHome($uid);
        $this->assign('uid', $uid);
        $this->assign('mid', $this->mid);
        foreach ($list as &$value) {
            $value['feed_info'] = model('Feed')->get($value['feed_id']);
            switch ($value['feed_info']['app']) {
                case 'weiba':
                    $value['feed_info']['from'] = getFromClient(0, $value['feed_info']['app'], '微吧');
                    break;
                case 'tipoff':
                    $value['feed_info']['from'] = getFromClient(0, $value['feed_info']['app'], '爆料');
                    break;
                default:
                    $value['feed_info']['from'] = getFromClient($value['feed_info']['from'], $value['feed_info']['app']);
            }
        }
        $this->assign('data', $list);

        $feed_ids = getSubByKey($list, 'feed_id');
        $diggArr = model('FeedDigg')->checkIsDigg($feed_ids, $this->mid);
        $this->assign('diggArr', $diggArr);

        $cancomment_old_type = array(
            'post', 'repost', 'postimage', 'postfile',
            'weiba_post', 'weiba_repost',
            'blog_post', 'blog_repost',
            'event_post', 'event_repost',
            'vote_post', 'vote_repost',
            'photo_post', 'photo_repost', );
        $this->assign('cancomment_old_type', $cancomment_old_type);

        $uids = array();
        foreach ($list as $item) {
            $uids[] = $item['feed_info']['uid'];
        }
        if (!empty($uids)) {
            $map = array();
            $map['uid'] = $GLOBALS['ts']['mid'];
            $map['fid'] = array('in', $uids);
            $followUids = model('Follow')->where($map)->getAsFieldArray('fid');
            $this->assign('followUids', $followUids);

            $remarkHash = model('Follow')->getRemarkHash($GLOBALS['ts']['mid']);
            $this->assign('remarkHash', $remarkHash);
        }

        $this->display('feedTop');
    }

    public function close_feed_top()
    {
        $feedId = intval($_POST['feed_id']);
        $this->model('FeedTopHome')->delFeedTopHome($this->mid, $feedId);
        echo 1;
    }
}
