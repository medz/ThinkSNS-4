<?php
/**
 * SearchAction 搜索模块.
 *
 * @version TS3.0
 */
class SearchAction extends Action
{
    private $curApp = '';
    private $curType = '';
    private $key = '';
    private $tabkey = '';
    private $tabvalue = '';
    private $searchModel = '';

    /**
     * 模块初始化.
     */
    public function _initialize()
    {
        $_GET = array_merge($_GET, $_POST);
        $this->curApp = $_GET['a'] ? strtolower(t($_GET['a'])) : 'public';
        $this->curType = intval($_GET['t']);
        $this->key = str_replace('%', '', t($_GET['k']));
        $this->tabkey = t($_GET['tk']);
        $this->tabvalue = t($_GET['tv']);
        $this->searchModel = ucfirst($this->curApp).'Search';
        $this->assign('curApp', $this->curApp);
        $this->assign('curType', $this->curType);
        $this->assign('tabkey', $this->tabkey);
        $this->assign('tabvalue', $this->tabvalue);
        $this->assign('keyword', $this->key);
        $this->assign('jsonKey', json_encode($this->key));
    }

    /**
     * 根据�
     * �键字进行搜索.
     */
    public function index()
    {
        if (!CheckPermission('core_normal', 'search_info')) {
            $this->error('对不起，您没有权限进行该操作！');
        }
        $this->setTitle('搜索'.$this->key);
        $this->setKeywords('搜索'.$this->key);
        $this->setDescription('搜索'.$this->key);

        if ($this->curType == 1) {     //搜索用户
            if ($this->key != '') {
                if (t($_GET['Stime']) && t($_GET['Etime'])) {
                    $Stime = strtotime(t($_GET['Stime']));
                    $Etime = strtotime(t($_GET['Etime']));
                    $this->assign('Stime', t($_GET['Stime']));
                    $this->assign('Etime', t($_GET['Etime']));
                }
                //关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
                $map['uname'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
                $map['uid'] = array(
                    'neq',
                    $this->mid,
                );
                $list = model('user')->where($map)->findPage(20);

                $fids = getSubByKey($list['data'], 'uid');
                // 获取用户信息
                $followUserInfo = model('User')->getUserInfoByUids($fids);
                // 获取用户的统计数目
                $userData = model('UserData')->getUserDataByUids($fids);
                // 获取用户用户组信息
                $userGroupData = model('UserGroupLink')->getUserGroupData($fids);
                $this->assign('userGroupData', $userGroupData);
                // 获取用户的最后分享数据
                //$lastFeedData = model('Feed')->getLastFeed($fids);
                // 获取用户的关注信息状态值
                $followState = model('Follow')->getFollowStateByFids($this->mid, $fids);
                // 获取用户的备注信息
                $remarkInfo = model('Follow')->getRemarkHash($this->mid);
                // 获取用户标签
                $this->_assignUserTag($fids);
                // 关注分组信息
                $followGroupStatus = model('FollowGroup')->getGroupStatusByFids($this->mid, $fids);
                $this->assign('followGroupStatus', $followGroupStatus);
                // 组装数据
                foreach ($list['data'] as $key => $value) {
                    $list['data'][$key] = $followUserInfo[$value['uid']];
                    $list['data'][$key] = array_merge($list['data'][$key], $userData[$value['uid']]);
                    $list['data'][$key] = array_merge($list['data'][$key], array('feedInfo' => $lastFeedData[$value['uid']]));
                    $list['data'][$key] = array_merge($list['data'][$key], array('followState' => $followState[$value['uid']]));
                    $list['data'][$key] = array_merge($list['data'][$key], array('remark' => $remarkInfo[$value['uid']]));
                }
                $this->assign('searchResult', $list);                 //搜索分享
            }
            $this->display('person');
        } elseif ($this->curType == 2) {     //搜索分享
            if ($this->key != '') {
                if (t($_GET['Stime']) && t($_GET['Etime'])) {
                    $Stime = strtotime(t($_GET['Stime']));
                    $Etime = strtotime(t($_GET['Etime']));
                    $this->assign('Stime', t($_GET['Stime']));
                    $this->assign('Etime', t($_GET['Etime']));
                }
                //关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
                $feed_type = !empty($_GET['feed_type']) ? t($_GET['feed_type']) : '';
                $list = model('Feed')->searchFeeds($this->key, $feed_type, 20, $Stime, $Etime);

                //赞功能
                $feed_ids = getSubByKey($list['data'], 'feed_id');
                $diggArr = model('FeedDigg')->checkIsDigg($feed_ids, $GLOBALS['ts']['mid']);
                $this->assign('diggArr', $diggArr);

                $this->assign('feed_type', $feed_type);
                $this->assign('searchResult', $list);                 //搜索分享
                $weiboSet = model('Xdata')->get('admin_Config:feed');
                $this->assign('weibo_premission', $weiboSet['weibo_premission']);
            }
            $this->display('search_feed');
        } elseif ($this->curType == 3) {     //搜索微吧
            if ($this->key != '') {
                if (t($_GET['Stime']) && t($_GET['Etime'])) {
                    $Stime = strtotime(t($_GET['Stime']));
                    $Etime = strtotime(t($_GET['Etime']));
                    $this->assign('Stime', t($_GET['Stime']));
                    $this->assign('Etime', t($_GET['Etime']));
                }
                $map['weiba_name'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
                $map['status'] = 1;
                $map['is_del'] = 0;
                $list = M('weiba')->where($map)->findPage(20);
                foreach ($list['data'] as $k => $v) {
                    if ($v['new_day'] != date('Y-m-d', time())) {
                        $list['data'][$k]['new_count'] = 0;
                        $this->setNewcount($v['weiba_id'], 0);
                    }
                }
                //dump($list);exit;
                $this->assign('searchResult', $list);                 //搜索分享
            }
            $this->display('weiba');
        } elseif ($this->curType == 4) {     //搜索用户
            if ($this->key != '') {
                if (t($_GET['Stime']) && t($_GET['Etime'])) {
                    $Stime = strtotime(t($_GET['Stime']));
                    $Etime = strtotime(t($_GET['Etime']));
                    $this->assign('Stime', t($_GET['Stime']));
                    $this->assign('Etime', t($_GET['Etime']));
                }
                $map['title'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
                $list = M('blog')->where($map)->findPage(20);
                foreach ($list['data'] as $k => $v) {
                    preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
                    foreach ($match[1] as $imgurl) {
                        $imgurl = $imgurl;
                        if (!empty($imgurl)) {
                            $list['data'][$k]['img'][] = $imgurl;
                        }
                    }
                    $is_digg = M('blog_digg')->where('post_id='.$v['id'].' and uid='.$this->mid)->find();
                    $list['data'][$k]['digg'] = $is_digg ? 'digg' : 'undigg';
                    if (count($list[$k]['img']) == '0') {
                        $list['data'][$k]['img'][] = ''; // 默认图
                    }
                    $list['data'][$k]['content'] = t($list['data'][$k]['content']);
                }
                //dump($list);exit;
                $this->assign('searchResult', $list);
            }
            $this->display('blog');
        } elseif ($this->curType == 5) {     //搜索帖子
            if ($this->key != '') {
                if (t($_GET['Stime']) && t($_GET['Etime'])) {
                    $Stime = strtotime(t($_GET['Stime']));
                    $Etime = strtotime(t($_GET['Etime']));
                    $this->assign('Stime', t($_GET['Stime']));
                    $this->assign('Etime', t($_GET['Etime']));
                }
                $map['title'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
                $map['is_del'] = 0;
                $list = M('weiba_post')->where($map)->findPage(20);
                $weiba_ids = getSubByKey($list['data'], 'weiba_id');
                $nameArr = $this->_getWeibaName($weiba_ids);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['weiba'] = $nameArr[$v['weiba_id']];
                    $list['data'][$k]['user'] = model('User')->getUserInfo($v['post_uid']);
                    $list['data'][$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
                    // $images = matchImages($v['content']);
                    // $images[0] && $list['data'][$k]['image'] = array_slice( $images , 0 , 5 );
                    $image = getEditorImages($v['content']);
                    !empty($image) && $list['data'][$k]['image'] = array($image);
                    //匹配图片的src
                    preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
                    foreach ($match[1] as $imgurl) {
                        $imgurl = $imgurl;
                        if (!empty($imgurl)) {
                            $list['data'][$k]['img'][] = $imgurl;
                        }
                    }
                    $is_digg = M('weiba_post_digg')->where('post_id='.$v['post_id'].' and uid='.$this->mid)->find();
                    $list['data'][$k]['digg'] = $is_digg ? 'digg' : 'undigg';
                    $list['data'][$k]['content'] = t($list['data'][$k]['content']);

                    //去掉微吧已经删除的
                    $is_del = D('weiba')->where('weiba_id='.$v['weiba_id'])->getField('is_del');
                    if ($is_del == 1 || $is_del === null) {
                        unset($list['data'][$k]);
                    }
                }
                //dump($list);exit;
                $this->assign('searchResult', $list);
            }
            $this->display('post');
        } elseif (false) {
            if ($this->key != '') {
                if ($this->curType == 5) {         //按标签搜索
                    $data['name'] = $this->key;
                    $tagid = D('tag')->where($data)->getField('tag_id');
                    $maps['app'] = 'public';
                    $maps['table'] = 'user';
                    $maps['tag_id'] = $tagid;
                    $user_ids = getSubByKey(D('app_tag')->where($maps)->field('row_id as uid')->order('row_id desc')->findAll(), 'uid');
                    $map['uid'] = array('in', $user_ids);
                    $map['is_active'] = 1;
                    $map['is_audit'] = 1;
                    $map['is_init'] = 1;
                    $userlist = D('user')->where($map)->field('uid')->findpage(10);
                    foreach ($userlist['data'] as &$v) {
                        $v = model('User')->getUserInfo($v['uid']);
                        unset($v);
                    }
                } else {
                    $userlist = model('User')->searchUser($this->key, 0, 100, '', '', 0, 10);
                }
                $uids = getSubByKey($userlist['data'], 'uid');
                $usercounts = model('UserData')->getUserDataByUids($uids);
                $userGids = model('UserGroupLink')->getUserGroup($uids);
                $followstatus = model('Follow')->getFollowStateByFids($this->mid, $uids);
                $unionstatus = model('Union')->getUnionStateByFids($this->mid, $uids);
                foreach ($userlist['data'] as $k => $v) {
                    $userlist['data'][$k]['usercount'] = $usercounts[$v['uid']];
                    $userlist['data'][$k]['userTag'] = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($v['uid']);
                    // 获取用户用户组信息
                    // 					$userGids = model('UserGroupLink')->getUserGroup($v['uid']);
                    $userGroupData = model('UserGroup')->getUserGroupByGids($userGids[$v['uid']]);
                    foreach ($userGroupData as $key => $value) {
                        if ($value['user_group_icon'] == -1) {
                            unset($userGroupData[$key]);
                            continue;
                        }
                        $userGroupData[$key]['user_group_icon_url'] = THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'];
                    }
                    $userlist['data'][$k]['userGroupData'] = $userGroupData;
                    // 获取用户积分信息
                    //$userlist['data'][$k]['userCredit'] = model('Credit')->getUserCredit($this->uid);
                    //关注状态
                    $userlist['data'][$k]['follow_state'] = $followstatus[$v['uid']];
                    $userlist['data'][$k]['union_state'] = $unionstatus[$v['uid']];
                }
                $this->assign('searchResult', $userlist);
            }

            $this->display('post');
        }
    }

    private function _assignUserTag($uids)
    {
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($uids);
        $this->assign('user_tag', $user_tag);
    }

    public function setNewcount($weiba_id, $num = 1)
    {
        $map['weiba_id'] = $weiba_id;
        $time = time();
        $weiba = M('weiba')->where($map)->find();
        if ($weiba['new_day'] != date('Y-m-d', $time)) {
            M('weiba')->where($map)->setField('new_day', date('Y-m-d', $time));
            M('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num == 0) {
            M('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num > 0) {
            M('weiba')->where($map)->setField('new_count', (int) $num + (int) $weiba['new_count']);
        }

        return true;
    }

    private function _getWeibaName($weiba_ids)
    {
        $weiba_ids = array_unique($weiba_ids);
        if (empty($weiba_ids)) {
            return false;
        }
        $map['weiba_id'] = array('in', $weiba_ids);
        $names = M('weiba')->where($map)->field('weiba_id,weiba_name')->findAll();
        foreach ($names as $n) {
            $nameArr[$n['weiba_id']] = $n['weiba_name'];
        }

        return $nameArr;
    }

    /**
     * 选择筛选时间.
     */
    public function selectDate()
    {
        $this->assign('app', t($_GET['app']));
        $this->assign('mod', t($_GET['mod']));
        $this->assign('t', t($_GET['t']));
        $this->assign('a', t($_GET['a']));
        $this->assign('k', t($_GET['k']));
        $this->assign('feed_type', t($_GET['feed_type']));
        $this->display();
    }

    /**
     * 模糊搜索标签.
     *
     * @return mix 标签列表
     */
    public function searchTag()
    {
        $tagid = intval($_REQUEST['tagid']);
        $map['app'] = 'public';
        $map['table'] = 'user';
        $map['tag_id'] = $tagid;
        $userlist = D('app_tag')->where($map)->field('row_id')->order('row_id')->findpage(10);

        $where[] = "name LIKE '{$q}%'";
        $where = implode(' AND ', $where);
        $list = model('Tag')->getTagList($where, 'tag_id,tag_id as short,name,name as cn', null, $limit);
        if (!$list['data']) {
            $list['data'] = '';
        }
        exit(json_encode($list['data']));
    }

    public function person()
    {
        if ($this->key != '') {
            if (t($_GET['Stime']) && t($_GET['Etime'])) {
                $Stime = strtotime(t($_GET['Stime']));
                $Etime = strtotime(t($_GET['Etime']));
                $this->assign('Stime', t($_GET['Stime']));
                $this->assign('Etime', t($_GET['Etime']));
            }
            //关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
            $map['uname'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
            $list = model('user')->where($map)->findPage(20);
            foreach ($list['data'] as $k => $vo) {
                $list['data'][$k] = model('User')->getUserInfo($vo['uid']);
            }
            $this->assign('searchResult', $list);                 //搜索分享
        }
        $this->display();
    }

    public function search_feed()
    {
        if ($this->key != '') {
            if (t($_GET['Stime']) && t($_GET['Etime'])) {
                $Stime = strtotime(t($_GET['Stime']));
                $Etime = strtotime(t($_GET['Etime']));
                $this->assign('Stime', t($_GET['Stime']));
                $this->assign('Etime', t($_GET['Etime']));
            }
            //关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
            $feed_type = !empty($_GET['feed_type']) ? t($_GET['feed_type']) : '';
            $list = model('Feed')->searchFeeds($this->key, $feed_type, 20, $Stime, $Etime);

            //赞功能
            $feed_ids = getSubByKey($list['data'], 'feed_id');
            $diggArr = model('FeedDigg')->checkIsDigg($feed_ids, $GLOBALS['ts']['mid']);
            $this->assign('diggArr', $diggArr);

            $this->assign('feed_type', $feed_type);
            $this->assign('searchResult', $list);                 //搜索分享
            $weiboSet = model('Xdata')->get('admin_Config:feed');
            $this->assign('weibo_premission', $weiboSet['weibo_premission']);
        }
        $this->display();
    }

    public function weiba()
    {
        if ($this->key != '') {
            if (t($_GET['Stime']) && t($_GET['Etime'])) {
                $Stime = strtotime(t($_GET['Stime']));
                $Etime = strtotime(t($_GET['Etime']));
                $this->assign('Stime', t($_GET['Stime']));
                $this->assign('Etime', t($_GET['Etime']));
            }
            $map['weiba_name'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
            $map['status'] = 1;
            $map['is_del'] = 0;
            $list = M('weiba')->where($map)->findPage(20);
            foreach ($list['data'] as $k => $v) {
                if ($v['new_day'] != date('Y-m-d', time())) {
                    $list['data'][$k]['new_count'] = 0;
                    $this->setNewcount($v['weiba_id'], 0);
                }
            }
            //dump($list);exit;
                $this->assign('searchResult', $list);                 //搜索分享
        }
        $this->display();
    }

    public function blog()
    {
        if ($this->key != '') {
            if (t($_GET['Stime']) && t($_GET['Etime'])) {
                $Stime = strtotime(t($_GET['Stime']));
                $Etime = strtotime(t($_GET['Etime']));
                $this->assign('Stime', t($_GET['Stime']));
                $this->assign('Etime', t($_GET['Etime']));
            }
            $map['title'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
            $list = M('blog')->where($map)->findPage(20);
            foreach ($list['data'] as $k => $v) {
                preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
                foreach ($match[1] as $imgurl) {
                    $imgurl = $imgurl;
                    if (!empty($imgurl)) {
                        $list['data'][$k]['img'][] = $imgurl;
                    }
                }
                $is_digg = M('blog_digg')->where('post_id='.$v['id'].' and uid='.$this->mid)->find();
                $list['data'][$k]['digg'] = $is_digg ? 'digg' : 'undigg';
                if (count($list[$k]['img']) == '0') {
                    $list['data'][$k]['img'][] = ''; // 默认图
                }
                $list['data'][$k]['content'] = t($list['data'][$k]['content']);
            }
            //dump($list);exit;
            $this->assign('searchResult', $list);
        }
        $this->display();
    }

    public function post()
    {
        if ($this->key != '') {
            if (t($_GET['Stime']) && t($_GET['Etime'])) {
                $Stime = strtotime(t($_GET['Stime']));
                $Etime = strtotime(t($_GET['Etime']));
                $this->assign('Stime', t($_GET['Stime']));
                $this->assign('Etime', t($_GET['Etime']));
            }
            $map['title'] = array(
                        'like',
                        '%'.$this->key.'%',
                );
            $map['is_del'] = 0;
            $list = M('weiba_post')->where($map)->findPage(20);
            $weiba_ids = getSubByKey($list['data'], 'weiba_id');
            $nameArr = $this->_getWeibaName($weiba_ids);
            foreach ($list['data'] as $k => $v) {
                $list['data'][$k]['weiba'] = $nameArr[$v['weiba_id']];
                $list['data'][$k]['user'] = model('User')->getUserInfo($v['post_uid']);
                $list['data'][$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
                // $images = matchImages($v['content']);
                // $images[0] && $list['data'][$k]['image'] = array_slice( $images , 0 , 5 );
                $image = getEditorImages($v['content']);
                !empty($image) && $list['data'][$k]['image'] = array($image);
                //匹配图片的src
                preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
                foreach ($match[1] as $imgurl) {
                    $imgurl = $imgurl;
                    if (!empty($imgurl)) {
                        $list['data'][$k]['img'][] = $imgurl;
                    }
                }
                $is_digg = M('weiba_post_digg')->where('post_id='.$v['post_id'].' and uid='.$this->mid)->find();
                $list['data'][$k]['digg'] = $is_digg ? 'digg' : 'undigg';
                $list['data'][$k]['content'] = t($list['data'][$k]['content']);

                //去掉微吧已经删除的
                $is_del = D('weiba')->where('weiba_id='.$v['weiba_id'])->getField('is_del');
                if ($is_del == 1 || $is_del === null) {
                    unset($list['data'][$k]);
                }
            }
            //dump($list);exit;
            $this->assign('searchResult', $list);
        }
        $this->display();
    }

    /**
     * 获取标签.
     *
     * @return mix 标签信息
     */
    public function getTag()
    {
        $data['name'] = t($_REQUEST['name']);
        $data['tag_id'] = model('Tag')->getTagId($data['name']);
        exit(json_encode($data));
    }
}
