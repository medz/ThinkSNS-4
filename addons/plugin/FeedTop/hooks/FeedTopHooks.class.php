<?php

class FeedTopHooks extends Hooks
{
    private $isRefresh = 1;

    /**
     * 主页右钩子
     */
    public function home_index_left_feedtop()
    {
        $list = $this->model('FeedTop')->getFeedTopList(1);
        $close_feeds = $_SESSION['feed_top_'.$this->mid];
        foreach ($list as $k => $v) {
            if (!in_array($v['feed_id'], $close_feeds)) {
                $list[$k]['feed_info'] = model('Feed')->get($v['feed_id']);
            } else {
                unset($list[$k]);
            }
        }

        foreach ($list as $k => &$v) {
            if ($v['feed_info']['is_del'] == 1) {
                $this->model('FeedTop')->doDel($v['id']);
                unset($list[$k]);
                continue;
            }
            switch ($v['feed_info']['app']) {
                case 'weiba':
                    $v['feed_info']['from'] = getFromClient(0, $v['feed_info']['app'], '微吧');
                    break;
                case 'tipoff':
                $v['feed_info']['from'] = getFromClient(0, $v['feed_info']['app'], '爆料');
                break;
                default:
                    $v['feed_info']['from'] = getFromClient($v['feed_info']['from'], $v['feed_info']['app']);
                    break;
            }
            !isset($uids[$v['feed_info']['uid']]) && $v['feed_info']['uid'] != $GLOBALS['ts']['mid'] && $uids[] = $v['feed_info']['uid'];
        }
        $this->assign('data', $list);

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

        // 赞分享
        $feed_ids = getSubByKey($list, 'feed_id');
        $diggArr = model('FeedDigg')->checkIsDigg($feed_ids, $GLOBALS['ts']['mid']);
        $this->assign('diggArr', $diggArr);
        $this->display('feedtop');
    }
    //public function home_index_right_top(){
    // 	$list = $this->model('FeedTop')->getFeedTopList(0);
    // 	foreach($list as $k =>$v){
    // 		$list[$k]['feed_info'] = model('Feed')->get($v['feed_id']);
    // 	}
    // 	$this->assign('recomment_lists',$list);
    // 	$this->display('recomment');
    // }
     //用户删除指定分享置顶
    public function close_feed_top()
    {
        $feed_top_id = t($_POST['feed_id']);
        $has_del_feed = $_SESSION['feed_top_'.$this->mid];
        if (!is_array($has_del_feed)) {
            $has_del_feed = array();
        }
        $has_del_feed[] = $feed_top_id;
        $has_del_feed = array_unique($has_del_feed);
        $_SESSION['feed_top_'.$this->mid] = $has_del_feed;
        echo 1;
    }
    //后台列表
    public function config()
    {
        // 列表数据
        $list = $this->model('FeedTop')->getFeedTopList(2);
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['feed_info'] = model('Feed')->get($v['feed_id']);
        }
        //dump($list);exit;
        $this->assign('list', $list);
        $this->display('config');
    }

    /**
     * 添加置顶页面
     */
    public function addFeedTop()
    {
        $this->display('addFeedTop');
    }
    /**
     * 添加置顶操作
     */
    public function doAddFeedTop()
    {
        $data['title'] = t($_POST['title']);
        $data['feed_id'] = intval($_POST['feed_id']);
        $data['status'] = intval($_POST['status']);
        $data['ctime'] = time();
        $res = $this->model('FeedTop')->doAddFeedTop($data);

        return false;
    }
    /**
     * 编辑广告位页面
     */
    public function editFeedTop()
    {
        // 获取广告位信息
        $id = intval($_GET['id']);
        $data = $this->model('FeedTop')->find($id);
        $this->assign('data', $data);
        $this->assign('editPage', true);
        $this->display('addFeedTop');
    }
    /**
     * 编辑广告位操作
     */
    public function doEditFeedTop()
    {
        $id = intval($_POST['id']);
        $data['title'] = t($_POST['title']);
        $data['feed_id'] = intval($_POST['feed_id']);
        $data['status'] = intval($_POST['status']);
        $data['ctime'] = time();
        $res = $this->model('FeedTop')->doEditFeedTop($id, $data);

        return false;
    }
    /**
     * 取消置顶操作
     * @return json 是否删除成功
     */
    public function doDelFeedTop()
    {
        $result = array();
        $id = t($_POST['id']);
        if (empty($id)) {
            $result['status'] = 0;
            $result['info'] = '参数不能为空';
            exit(json_encode($result));
        }
        $res = $this->model('FeedTop')->doDelFeedTop($id);
        if ($res) {
            $result['status'] = 1;
            $result['info'] = '删除成功';
        } else {
            $result['status'] = 0;
            $result['info'] = '删除失败';
        }
        exit(json_encode($result));
    }
    /**
     * 重新置顶操作
     * @return json 是否成功
     */
    public function doFeedTop()
    {
        $result = array();
        $id = t($_POST['id']);
        if (empty($id)) {
            $result['status'] = 0;
            $result['info'] = '参数不能为空';
            exit(json_encode($result));
        }
        $res = $this->model('FeedTop')->doFeedTop($id);
        if ($res) {
            $result['status'] = 1;
            $result['info'] = '置顶成功';
        } else {
            $result['status'] = 0;
            $result['info'] = '置顶失败';
        }
        exit(json_encode($result));
    }
    public function doDel()
    {
        $result = array();
        $id = t($_POST['id']);
        if (empty($id)) {
            $result['status'] = 0;
            $result['info'] = '参数不能为空';
            exit(json_encode($result));
        }
        $res = $this->model('FeedTop')->doDel($id);
        if ($res) {
            $result['status'] = 1;
            $result['info'] = '删除成功';
        } else {
            $result['status'] = 0;
            $result['info'] = '删除失败';
        }
        exit(json_encode($result));
    }

    public function check_feed_manage($params)
    {
        $data = array();
        $isAdmin = model('UserGroup')->isAdmin($this->mid);
        if ($isAdmin > 0) {
            $data['status'] = true;
        } else {
            $data['status'] = false;
        }
        $data['plugin'] = 'feed_top';
        $params['plugin_list'][] = $data;
    }

    public function feed_manage_li($data)
    {
        $isAdmin = model('UserGroup')->isAdmin($this->mid);
        if ($isAdmin <= 0) {
            return '';
        }
        $checked = $this->model('FeedTop')->checkedFeedTop($data['uid'], $data['feed_id']);
        if ($checked) {
            echo '<li><a href="javascript:;" rel="feed_top" onclick="delFeedTop('.$data['uid'].', '.$data['feed_id'].', '.$this->isRefresh.')">取消全局置顶</a></li>';
        } else {
            echo '<li><a href="javascript:;" rel="feed_top" onclick="addFeedTop('.$data['uid'].', '.$data['feed_id'].', '.$this->isRefresh.')">全局分享置顶</a></li>';
        }
    }

    public function add_feed_top_home()
    {
        $uid = intval($_POST['uid']);
        $feedId = intval($_POST['feed_id']);
        if (empty($uid) || empty($feedId)) {
            exit(json_encode(array('status' => 0, 'info' => '设置失败')));
        }
        $result = $this->model('FeedTop')->setFeedTop($uid, $feedId);
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
        $result = $this->model('FeedTop')->delFeedTop($uid, $feedId);
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
}
