<?php
/**
 * 首页控制器.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class IndexAction extends Action
{
    /**
     * 我的首页 - 分享页面.
     */
    public function index()
    {
        /* # 微吧和频道开关检测 */
        $weibaIfOpen = model('App')->getAppByName('weiba');
        $weibaIfOpen = $weibaIfOpen['status'];
        $channelIfOpen = model('App')->getAppByName('channel');
        $channelIfOpen = $channelIfOpen['status'];
        $this->assign('weibaIfOpen', $weibaIfOpen);
        $this->assign('channelIfOpen', $channelIfOpen);
        unset($weibaIfOpen, $channelIfOpen);

        // 安全过滤
        $d['type'] = 'all';
        if (isset($_GET['type']) && t($_GET['type'])) {
            $d['type'] = $_GET['type'];
        }

        $d['feed_type'] = '';
        if (isset($_GET['feed_type']) && t($_GET['feed_type'])) {
            $d['feed_type'] = $_GET['feed_type'];
        }

        $d['feed_key'] = '';
        if (isset($_GET['feed_key']) && t($_GET['feed_key'])) {
            $d['feed_key'] = $_GET['feed_key'];
        }

        // 关注的人
        if ($d['type'] === 'following') {
            $d['groupname'] = L('PUBLIC_ACTIVITY_STREAM'); // 我关注的
            $d['followGroup'] = model('FollowGroup')->getGroupList($this->mid);
            foreach ($d['followGroup'] as $v) {
                if ($v['follow_group_id'] == t($_REQUEST['fgid'])) {
                    $d['groupname'] = $v['title'];
                    break;
                }
            }
        }
        // 判断频道是否开启
        $isChannelOpen = model('App')->isAppNameOpen('channel');
        $this->assign('isChannelOpen', $isChannelOpen);
        // 关注的频道
        if ($isChannelOpen && $d['type'] === 'channel') {
            $d['channelname'] = '我关注的频道';
            $d['channelGroup'] = D('ChannelFollow', 'channel')->getFollowList($this->mid);
            foreach ($d['channelGroup'] as $v) {
                if ($v['channel_category_id'] == t($_REQUEST['fgid'])) {
                    $d['channelname'] = $v['title'];
                    break;
                }
            }
        }
        $this->assign($d);
        // 设置默认话题
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $initHtml = $weiboSet['weibo_default_topic']; // 分享框默认话题
        if ($initHtml) {
            $initHtml = '#'.$initHtml.'#';
        }
        $this->assign('initHtml', $initHtml);
        if ($d['type'] == 'weiba') {
            $sfollow = D('weiba_follow')->where('follower_uid='.$this->mid)->findAll();
            if ($sfollow) {
                $idlist = getSubByKey($sfollow, 'weiba_id');
                foreach ($idlist as $k => $vo) {
                    if (in_array($vo, $fids)) {
                        unset($idlist[$k]);
                    }
                }
                $maps['weiba_id'] = array('in', $idlist);
            }

            $order = ' top desc, post_time desc';
            $list = D('weiba_post')->where($maps)->order($order)->findpage(10);

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

                //dump($userinfo);avatar_small,avatar_tiny
                $list['data'][$k]['image'] = $list['data'][$k]['user']['avatar_middle'];
            }

            //dump($list);exit;
            $this->assign('post_recommend_list', $list);
        }

        $title = empty($weiboSet['weibo_send_info']) ? '随时记录' : $weiboSet['weibo_send_info'];
        $this->assign('title', $title);
        // 设置标题与关键字信息
        switch ($d['type']) {
            case 'all':
                $this->setTitle('朋友圈');
                $this->setKeywords('朋友圈');
                break;
            case 'recommend':
                $this->setTitle('朋友圈-推荐动态');
                $this->setKeywords('朋友圈-推荐动态');
                break;
            case 'following':
                $this->setTitle('朋友圈-我关注的动态');
                $this->setKeywords('朋友圈-我关注的动态');
                break;
            case 'weiba':
                $this->setTitle('朋友圈-我关注的微吧');
                $this->setKeywords('朋友圈-我关注的微吧');
                break;
            case 'channel':
                $this->setTitle('朋友圈-我关注的频道');
                $this->setKeywords('朋友圈-我关注的频道');
                break;
            case 'newall':
                $this->setTitle('朋友圈-最新动态');
                $this->setKeywords('朋友圈-最新动态');
                break;
            default:
                $this->setTitle(L('PUBLIC_INDEX_INDEX'));
                $this->setKeywords(L('PUBLIC_INDEX_INDEX'));
        }

/* 		$category = D ( 'BlogCategory' )->getCategory ();
        $this->assign ( 'blog_category', $category ); */
        $category = model('CategoryTree')->setTable('channel_category')->getCategoryList();
        $this->assign('channel_category', $category);
        $wtype = array('at', 'topic', 'contribute');
        $actions = array();
        foreach ($wtype as $value) {
            $actions[$value] = false;
        }
        $this->assign('actions', $actions);
        // 获取后台配置数据 -- 提示语
        $channelConf = model('Xdata')->get('channel_Admin:index');
        $prompt = '';
        if ($channelConf['is_audit'] == 1) {
            $prompt = '投稿成功';
        } else {
            $prompt = '投稿正在审核中';
        }

        $sfollow = D('weiba_follow')->where('follower_uid='.$this->mid)->findAll();
        unset($map);
        $map['weiba_id'] = array(
                'in',
                getSubByKey($sfollow, 'weiba_id'),
        );
        $map['is_del'] = 0;
        $map['status'] = 1;
        $wcategory = D('Weiba')->where($map)->field('weiba_id,weiba_name')->findAll();
        $this->assign('weiba_category', $wcategory);
        $this->display();
    }

    public function loginWithoutInit()
    {
        $this->index();
    }

    /**
     * 我的分享页面.
     */
    public function myFeed()
    {
        // 获取用户统计数目
        $userData = model('UserData')->getUserData($this->mid);
        $this->assign('feedCount', $userData['weibo_count']);
        // 分享过滤内容
        $feedType = t($_REQUEST['feed_type']);
        $this->assign('feedType', $feedType);
        // 搜索使用
        $this->assign('feed_key', t($_REQUEST['feed_key']));
        $this->assign('feed_type', t($_REQUEST['feed_type']));
        // 是否有返回按钮
        $this->assign('isReturn', 1);
        $this->setTitle('我的分享');
        $this->setKeywords('我的分享');
        $this->display();
    }

    /**
     * 我的关注页面.
     */
    public function following()
    {
        // 获取关组分组ID
        $gid = intval($_REQUEST['gid']);
        $this->assign('gid', $gid);
        // 获取指定用户的关注分组
        $groupList = model('FollowGroup')->getGroupList($this->mid);
        $key = t($_REQUEST['follow_key']);
        if ($key === '') {
            // 获取用户ID
            switch ($gid) {
                case 0:
                    $followGroupList = model('Follow')->getFollowingsList($this->mid);
                    break;
                case -1:
                    $followGroupList = model('Follow')->getFriendsList($this->mid);
                    break;
                case -2:
                    $followGroupList = model('FollowGroup')->getDefaultGroupByPage($this->mid);
                    break;
                default:
                    $followGroupList = model('FollowGroup')->getUsersByGroupPage($this->mid, $gid);
            }
        } else {
            $followGroupList = model('Follow')->searchFollows($key, 'following', 20, $this->mid, $gid);
            $this->assign('follow_key', $key);
            $this->assign('jsonKey', json_encode($key));
        }
        $fids = getSubByKey($followGroupList['data'], 'fid');
        // 获取用户信息
        $followUserInfo = model('User')->getUserInfoByUids($fids);
        // 获取用户的统计数目
        $userData = model('UserData')->getUserDataByUids($fids);
        // 获取用户用户组信息
        $userGroupData = model('UserGroupLink')->getUserGroupData($fids);
        $this->assign('userGroupData', $userGroupData);
        // 获取用户的最后分享数据
        // $lastFeedData = model('Feed')->getLastFeed($fids);
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
        foreach ($followGroupList['data'] as $key => $value) {
            $followGroupList['data'][$key] = $followUserInfo[$value['fid']];
            $followGroupList['data'][$key] = array_merge($followGroupList['data'][$key], $userData[$value['fid']]);
            $followGroupList['data'][$key] = array_merge($followGroupList['data'][$key], array(
                    'feedInfo' => $lastFeedData[$value['fid']],
            ));
            $followGroupList['data'][$key] = array_merge($followGroupList['data'][$key], array(
                    'followState' => $followState[$value['fid']],
            ));
            $followGroupList['data'][$key] = array_merge($followGroupList['data'][$key], array(
                    'remark' => $remarkInfo[$value['fid']],
            ));
        }
        $this->assign($followGroupList);
        // 获取登录用户的所有分组
        $userGroupList = model('FollowGroup')->getGroupList($this->mid);
        $userGroupListFormat = array();
        foreach ($userGroupList as $value) {
            $userGroupListFormat[] = array(
                    'gid'   => $value['follow_group_id'],
                    'title' => $value['title'],
            );
        }
        $groupList = array(
                array(
                        'gid'   => 0,
                        'title' => '全部',
                ),
                array(
                        'gid'   => -1,
                        'title' => '相互关注',
                ),
                array(
                        'gid'   => -2,
                        'title' => '未分组',
                ),
        );
        !empty($userGroupListFormat) && $groupList = array_merge($groupList, $userGroupListFormat);
        $this->assign('groupList', $groupList);
        // 前5个的分组ID
        $this->assign('topGroup', array_slice(getSubByKey($groupList, 'gid'), 0, 3));
        foreach ($groupList as $value) {
            if ($value['gid'] == $gid) {
                $this->assign('gTitle', $value['title']);
                break;
            }
        }
        // 关注人数
        $midData = model('UserData')->getUserData($this->mid);
        $this->assign('followingCount', $midData['following_count']);
        // 显示的分类个数
        $this->assign('groupNums', 3);
        // 是否有返回按钮
        $this->assign('isReturn', 1);

        $userInfo = model('User')->getUserInfo($this->mid);
        $lastFeed = model('Feed')->getLastFeed(array(
                $fids[0],
        ));
        $this->setTitle('我的关注');
        $this->setKeywords($userInfo['uname'].'的关注');
        $this->display();
    }

    /**
     * 我的粉丝页面.
     */
    public function follower()
    {
        // 清空新粉丝提醒数字
        if ($this->uid == $this->mid) {
            $udata = model('UserData')->getUserData($this->mid);
            $udata['new_folower_count'] > 0 && model('UserData')->setKeyValue($this->mid, 'new_folower_count', 0);
        }
        // 获取用户的粉丝列表
        $key = t($_REQUEST['follow_key']);
        if ($key === '') {
            $followerList = model('Follow')->getFollowerList($this->mid, 20);
        } else {
            $followerList = model('Follow')->searchFollows($key, 'follower', 20, $this->mid);
            $this->assign('follow_key', $key);
            $this->assign('jsonKey', json_encode($key));
        }
        $fids = getSubByKey($followerList['data'], 'fid');
        // 获取用户信息
        $followerUserInfo = model('User')->getUserInfoByUids($fids);
        // 获取用户统计数目
        $userData = model('UserData')->getUserDataByUids($fids);
        // 获取用户标签
        $this->_assignUserTag($fids);
        // 获取用户用户组信息
        $userGroupData = model('UserGroupLink')->getUserGroupData($fids);
        $this->assign('userGroupData', $userGroupData);
        // 获取用户的最后分享数据
        // $lastFeedData = model('Feed')->getLastFeed($fids);
        // 获取用户的关注信息状态
        $followState = model('Follow')->getFollowStateByFids($this->mid, $fids);
        // 组装数据
        foreach ($followerList['data'] as $key => $value) {
            $followerList['data'][$key] = array_merge($followerList['data'][$key], $followerUserInfo[$value['fid']]);
            $followerList['data'][$key] = array_merge($followerList['data'][$key], $userData[$value['fid']]);
            $followerList['data'][$key] = array_merge($followerList['data'][$key], array(
                    'feedInfo' => $lastFeedData[$value['fid']],
            ));
            $followerList['data'][$key] = array_merge($followerList['data'][$key], array(
                    'followState' => $followState[$value['fid']],
            ));
        }
        $this->assign($followerList);
        // 是否有返回按钮
        $this->assign('isReturn', 1);
        // 粉丝人数
        $midData = model('UserData')->getUserData($this->mid);
        $this->assign('followerCount', $midData['follower_count']);

        $userInfo = model('User')->getUserInfo($this->mid);
        $lastFeed = model('Feed')->getLastFeed(array(
                $fids[0],
        ));
        $this->setTitle('我的粉丝');
        $this->setKeywords($userInfo['uname'].'的粉丝');
        $this->display();
    }

    /**
     * 意见反馈页面.
     */
    public function feedback()
    {
        $feedbacktype = model('Feedback')->getFeedBackType();
        $this->assign('type', $feedbacktype);
        $this->display();
    }

    /**
     * 获取验证码图片操作.
     */
    public function verify()
    {
        Image::buildImageVerify();
    }

    /**
     * 获取指定用户小名片所需要的数据.
     *
     * @return string 指定用户小名片所需要的数据
     */
    public function showFaceCard()
    {
        if (empty($_REQUEST['uid'])) {
            exit(L('PUBLIC_WRONG_USER_INFO')); // 错误的用户信息
        }

        $this->assign('follow_group_status', model('FollowGroup')->getGroupStatus($GLOBALS['ts']['mid'], $GLOBALS['ts']['uid']));
        $this->assign('remarkHash', model('Follow')->getRemarkHash($GLOBALS['ts']['mid']));

        $uid = intval($_REQUEST['uid']);
        $data['userInfo'] = model('User')->getUserInfo($uid);
        $data['userInfo']['groupData'] = model('UserGroupLink')->getUserGroupData($uid); // 获取用户组信息
        $data['user_tag'] = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($uid);
        $data['follow_state'] = model('Follow')->getFollowState($this->mid, $uid);
        $data['union_state'] = model('Union')->getUnionState($this->mid, $uid);

        $depart = model('Department')->getAllHash();
        $data['department'] = isset($depart[$data['userInfo']['department_id']]) ? $depart[$data['userInfo']['department_id']] : '';

        $count = model('UserData')->getUserData($uid);
        if (empty($count)) {
            $count = array(
                    'following_count' => 0,
                    'follower_count'  => 0,
                    'feed_count'      => 0,
                    'favorite_count'  => 0,
                    'unread_atme'     => 0,
                    'weibo_count'     => 0,
            );
        }
        $data['count_info'] = $count;

        // 用户字段信息
        $profileSetting = D('UserProfileSetting')->where('type=2')->getHashList('field_id');
        $profile = model('UserProfile')->getUserProfile($uid);
        $data['profile'] = array();
        foreach ($profile as $k => $v) {
            if (isset($profileSetting[$k])) {
                $data['profile'][$profileSetting[$k]['field_key']] = array(
                        'name'  => $profileSetting[$k]['field_name'],
                        'value' => $v['field_data'],
                );
            }
        }

        // 判断隐私
        if ($this->uid != $this->mid) {
            $UserPrivacy = model('UserPrivacy')->getPrivacy($this->mid, $this->uid);
            $this->assign('UserPrivacy', $UserPrivacy);
        }
        // 判断用户是否已认证
        $isverify = D('user_verified')->where('verified=1 AND uid='.$uid)->find();
        if ($isverify) {
            $this->assign('verifyInfo', $isverify['info']);
        }
        $this->assign($data);
        $this->display();
    }

    /**
     * 公告详细页面.
     */
    public function announcement()
    {
        $map['type'] = 1;
        $map['id'] = intval($_GET['id']);
        $d['announcement'] = model('Xarticle')->where($map)->find();
        // 组装附件信息
        $attachIds = explode('|', $d['announcement']['attach']);
        $attachInfo = model('Attach')->getAttachByIds($attachIds);
        $d['announcement']['attachInfo'] = $attachInfo;
        $this->assign($d);
        $this->display();
    }

    /**
     * 公告列表页面.
     */
    public function announcementList()
    {
        $map['type'] = 1;
        $list = model('Xarticle')->where($map)->findPage(20);
        // 获取附件类型
        $attachIds = array();
        foreach ($list['data'] as &$value) {
            $value['hasAttach'] = !empty($value['attach']) ? true : false;
        }

        $this->assign($list);
        $this->display();
    }

    /**
     * 自动提取标签操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function getTags()
    {
        $text = t($_REQUEST['text']);
        $format = !empty($_REQUEST['format']) ? t($_REQUEST['format']) : 'string';
        $limit = !empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : '3';
        $tagX = model('Tag');
        $tagX->setText($text); // 设置text
        $result = $tagX->getTop($limit, $format); // 获取前10个标签
        exit($result);
    }

    /**
     * 根据指定应用和表获取指定用户的标签,同个人空间中用户标签.
     *
     * @param
     *        	array uids 用户uid数组
     */
    private function _assignUserTag($uids)
    {
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($uids);
        $this->assign('user_tag', $user_tag);
    }

    /**
     * 弹窗发布分享.
     */
    public function sendFeedBox()
    {
        $initHtml = t($_REQUEST['initHtml']);
        if (!empty($initHtml)) {
            $data['initHtml'] = $initHtml;
        }
        // 投稿数据处理
        $channelID = h($_REQUEST['channelID']);
        if (!empty($channelID)) {
            $data['channelID'] = $channelID;
            $data['type'] = 'submission';
        }

        $this->assign($data);
        $this->display();
    }

    /**
     * 消息弹出层
     */
    public function messageBox()
    {
        $count = model('UserCount')->getUnreadCount($GLOBALS['ts']['mid']);
        $this->assign('count', $count);
        $this->display();
    }

    /**
     * 消息弹出层内容获取.
     */
    public function messageContent($type)
    {
        if (!$type) {
            $type = t(empty($_POST['type']) ? $_GET['type'] : $_POST['type']);
        }
        $_POST['type'] = $type;
        switch ($type) {
            //@我的
            case 'at':
                // 获取未读@Me的条数
                $this->assign('unread_atme_count', model('UserData')->where('uid='.$this->mid." and `key`='unread_atme'")->getField('value'));
                // 拼装查询条件
                $map['uid'] = $this->mid;

                $d['tab'] = model('Atme')->getTab(null);
                foreach ($d['tab'] as $key => $vo) {
                    if ($key == 'feed') {
                        $d['tabHash']['feed'] = L('PUBLIC_WEIBO');
                    } elseif ($key == 'comment') {
                        $d['tabHash']['comment'] = L('PUBLIC_STREAM_COMMENT');
                    } else {
                        $langKey = 'PUBLIC_APPNAME_'.strtoupper($key);
                        $lang = L($langKey);
                        if ($lang == $langKey) {
                            $d['tabHash'][$key] = ucfirst($key);
                        } else {
                            $d['tabHash'][$key] = $lang;
                        }
                    }
                }
                $this->assign($d);

                !empty($_POST['t']) && $map['table'] = t($_POST['t']);
                //at类型
                $this->assign('tt', $_POST['t']);

                // 设置应用名称与表名称
                $app_name = isset($_GET['app_name']) ? t($_GET['app_name']) : 'public';
                // $app_table = isset($_GET['app_table']) ? t($_GET['app_table']) : '';
                // 获取@Me分享列表
                $at_list = model('Atme')->setAppName($app_name)->setAppTable($app_table)->getAtmeList($map, $order = 'atme_id DESC', $limit = 20);

                // 赞功能
                $feed_ids = getSubByKey($at_list['data'], 'feed_id');
                $diggArr = model('FeedDigg')->checkIsDigg($feed_ids, $GLOBALS['ts']['mid']);
                $this->assign('diggArr', $diggArr);

                // dump($at_list);exit;
                // 添加Widget参数数据
                foreach ($at_list['data'] as &$val) {
                    if ($val['source_table'] == 'comment') {
                        $val['widget_sid'] = $val['sourceInfo']['source_id'];
                        $val['widget_style'] = $val['sourceInfo']['source_table'];
                        $val['widget_sapp'] = $val['sourceInfo']['app'];
                        $val['widget_suid'] = $val['sourceInfo']['uid'];
                        $val['widget_share_sid'] = $val['sourceInfo']['source_id'];
                    } elseif ($val['is_repost'] == 1) {
                        $val['widget_sid'] = $val['source_id'];
                        $val['widget_stype'] = $val['source_table'];
                        $val['widget_sapp'] = $val['app'];
                        $val['widget_suid'] = $val['uid'];
                        $val['widget_share_sid'] = $val['app_row_id'];
                        $val['widget_curid'] = $val['source_id'];
                        $val['widget_curtable'] = $val['source_table'];
                    } else {
                        $val['widget_sid'] = $val['source_id'];
                        $val['widget_stype'] = $val['source_table'];
                        $val['widget_sapp'] = $val['app'];
                        $val['widget_suid'] = $val['uid'];
                        $val['widget_share_sid'] = $val['source_id'];
                    }
                    // 获取转发与评论数目
                    if ($val['source_table'] != 'comment') {
                        $feedInfo = model('Feed')->get($val['widget_sid']);
                        $val['repost_count'] = $feedInfo['repost_count'];
                        $val['comment_count'] = $feedInfo['comment_count'];
                    }
                    // 解析数据成网页端显示格式(@xxx 加链接)
                    $val['source_content'] = parse_html($val['source_content']);
                    $val['from'] = getFromClient($val['from'], $val['app']);
                }
                // 获取分享设置
                $weiboSet = model('Xdata')->get('admin_Config:feed');
                $this->assign($weiboSet);
                // 用户@Me未读数目重置
                // model('UserCount')->resetUserCount($this->mid, 'unread_atme', 0);
                $userInfo = model('User')->getUserInfo($this->mid);
                // 分页链接重写
                $at_list['html'] = $this->messagePage($at_list['html']);
                $this->assign($at_list);
                //消息类型
                $this->assign('type', $type);
                $html = $this->fetch('at');
                break;
            //我的评论
            case 'comment':
                $stype = t($_POST['stype']);
                if (empty($_POST['stype'])) {
                    $stype = $_POST['stype'] = 'receive';
                }
                if ($stype == 'send') {
                    $map['uid'] = $this->uid;
                } else {
                    // 分享配置
                    $weiboSet = model('Xdata')->get('admin_Config:feed');
                    $this->assign('weibo_premission', $weiboSet['weibo_premission']);
                    $keyword = '收到';
                    //获取未读评论的条数
                    $this->assign('unread_comment_count', model('UserData')->where('uid='.$this->mid." and `key`='unread_comment'")->getField('value'));
                    // 收到的
                    $map['_string'] = " (to_uid = '{$this->uid}' OR app_uid = '{$this->uid}') AND uid !=".$this->uid;
                }
                //消息类型
                $this->assign('type', $type);
                //发出的或者收到的
                $this->assign('stype', $stype);
                //		$d['tab'] = model('Comment')->getTab($map);
                $d['tab'] = model('Comment')->getTabForApp($map);
                foreach ($d['tab'] as $key => $vo) {
                    if ($key == 'feed') {
                        $d['tabHash']['feed'] = L('PUBLIC_WEIBO');
                    } elseif ($key == 'webpage') {
                        $d['tabHash']['webpage'] = '评论箱';
                    } else {
                        // 微吧
                        strtolower($key) === 'weiba_post' && $key = 'weiba';

                        $langKey = 'PUBLIC_APPNAME_'.strtoupper($key);
                        $lang = L($langKey);
                        if ($lang == $langKey) {
                            $d['tabHash'][$key] = ucfirst($key);
                        } else {
                            $d['tabHash'][$key] = $lang;
                        }
                    }
                }
                $this->assign($d);
                // 安全过滤
                $t = t($_POST['t']);
                // 	评论类型
                $this->assign('tt', $t);
                !empty($t) && $map['app'] = $t;
                if ($t == 'feed') {
                    $map['app'] = 'public';
                }
                $list = model('Comment')->setAppName(t($_GET['app_name']))->getCommentList($map, 'comment_id DESC', 20, true);
                foreach ($list['data'] as $k => $v) {
                    if ($v['sourceInfo']['app'] == 'weiba') {
                        $list['data'][$k]['sourceInfo']['source_body'] = str_replace($v['sourceInfo']['row_id'], $v['comment_id'], $v['sourceInfo']['source_body']);
                    }
                    if ($v['table'] === 'webpage') {
                        $list['data'][$k]['hasComment'] = false;
                    } else {
                        $list['data'][$k]['hasComment'] = true;
                    }
                }
                model('UserCount')->resetUserCount($this->mid, 'unread_comment', 0);
                //分页链接重写
                $list['html'] = $this->messagePage($list['html']);
                $this->assign('list', $list);
                $userInfo = model('User')->getUserInfo($this->mid);
                $html = $this->fetch('comment');
                break;
            //我的私信
            case 'message':
                $dao = model('Message');
                $list = $dao->getMessageListByUid($this->mid, array(MessageModel::ONE_ON_ONE_CHAT, MessageModel::MULTIPLAYER_CHAT), 20);
                // 设置信息已读(在右上角提示去掉),
                model('Message')->setMessageIsRead(t($POST['id']), $this->mid, 1);
                if ($list['nowPage'] <= 1 && is_array($list['data']) && isset($list['data'][0])) {
                    $only = !isset($list['data'][1]) || $list['data'][1]['new'] <= 0;
                    if ($list['data'][0]['new'] > 0 && $only) {
                        $this->redirect('public/Index/messageContent', array(
                            'type'  => 'message_detail',
                            'stype' => $list['data'][0]['type'],
                            'id'    => $list['data'][0]['list_id'],
                        ));
                        exit;
                    }
                }
                //重写分页链接
                $list['html'] = $this->messagePage($list['html']);
                $this->assign($list);
                $this->assign('type', $type);
                $userInfo = model('User')->getUserInfo($this->mid);
                $html = $this->fetch('message');
                break;
            case 'message_detail':
                $_POST['id'] = intval(empty($_POST['id']) ? $_GET['id'] : $_POST['id']);
                $_POST['stype'] = t(empty($_POST['stype']) ? $_GET['stype'] : $_POST['stype']);
                $message = model('Message')->isMember(t($_POST['id']), $this->mid, true);
                // 验证数据
                if (empty($message)) {
                    $this->error(L('PUBLIC_PRI_MESSAGE_NOEXIST'));
                }
                $message['member'] = model('Message')->getMessageMembers(t($_POST['id']), 'member_uid');
                $message['to'] = array();
                // 添加发送用户ID
                foreach ($message['member'] as $v) {
                    $this->mid != $v['member_uid'] && $message['to'][] = $v;
                }
                // 设置信息已读(私信列表页去掉new标识)
                model('Message')->setMessageIsRead(t($_POST['id']), $this->mid, 0);
                $message['since_id'] = model('Message')->getSinceMessageId($message['list_id'], $message['message_num']);

                $this->assign('message', $message);
                $this->assign('type', intval($_POST['type']));

                $this->setTitle('与'.$message['to'][0]['user_info']['uname'].'的私信对话');
                $this->setKeywords('与'.$message['to'][0]['user_info']['uname'].'的私信对话');
                $html = $this->fetch('message_detail');
                break;
            case 'notify':
                $map['uid'] = $this->mid;
                if ($_POST['t'] == 'digg') {
                    $map['node'] = array('eq', 'digg');
                } else {
                    $map['node'] = array('neq', 'digg');
                }
                $list = D('notify_message')->where($map)->order('ctime desc')->findpage(20);
                //重写分页链接
                $list['html'] = $this->messagePage($list['html']);
                foreach ($list['data'] as $k => $v) {
                    $list['data'][$k]['body'] = parse_html($v['body']);
                    if ($appname != 'public') {
                        $list['data'][$k]['app'] = model('App')->getAppByName($v['appname']);
                    }
                    //如果为点赞消息，获取发送人的头像
                    if ($_POST['t'] == 'digg') {
                        $from_user = model('User')->getUserInfo($list['data'][$k]['from_uid']);
                        //头像
                        $list['data'][$k]['from_user_avatar'] = $from_user['avatar_middle'];
                        $list['data'][$k]['from_user'] = $from_user;
                    }
                }
                if ($_POST['t'] == 'digg') {
                    $node = 'digg';
                    model('UserCount')->resetUserCount($this->mid, 'unread_digg', 0);
                } else {
                    $node = array('neq', 'digg');
                }
                model('Notify')->setRead($this->mid, '', $node);
                $this->assign('list', $list);
                $this->assign('type', $type);
                $this->assign('t', $_POST['t']);
                $html = $this->fetch('notify');
                break;

        }
        $data['html'] = $html;
        $this->ajaxReturn($data, '获取内容成功', 1);
    }

    /**
     * 重写消息分页链接.
     */
    public function messagePage($html)
    {
        $pattern = "/href=[\"\']?([^\"\']+)?[\"\'].*?/";
        $replacement = 'href=javascript:; onclick=message.page(this);';
        $html = preg_replace($pattern, $replacement, $html);

        return $html;
    }

    /**
     * 老版消息弹出框.
     *
     * @return [type] [description]
     */
    public function getMessage()
    {
        $type = t($_GET['type']);
        if (!in_array($type, array(
            'msg',
            'at',
            'com',
            'pmsg',
        ))) {
            $this->ajaxReturn(null, '信息获取失败', 0);
        }
        $limit = 10;
        $html = '';
        switch ($type) {
            case 'msg':
                $map['uid'] = $this->mid;
                $notifyList = D('notify_message')->where($map)->order('ctime DESC')->findPage($limit);
                foreach ($notifyList['data'] as $item) {
                    $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($item['body']));
                    if ($item['node'] == 'user_follow') {
                        $html .= '<li onclick="location.href=\''.U('public/Index/follower').'\'">'.$content.'</li>';
                    } elseif ($item['node'] == 'digg') {
                        preg_match_all('%<a[\s\S]*?href="([^"]+)[^>]+>([\s\S]*?)</a>%', $content, $match, PREG_PATTERN_ORDER);
                        $url = $match[1][count($match[0]) - 1];
                        $html .= '<li onclick="location.href=\''.$url.'\'">'.$content.'</li>';
                    } else {
                        $html .= '<li>'.$content.'</li>';
                    }
                }
                model('Notify')->setRead($this->mid);
                break;
            case 'at':

                // $map['uid'] = $this->mid;
                // $atList = model('Atme')->where($map)->order('atme_id DESC')->findPage($limit);
                $table = "( SELECT a.`atme_id`, a.`app`, a.`table`, CASE a.`table` WHEN 'comment' THEN b.`row_id` ELSE a.`row_id` END AS `row_id`, a.`uid`, a.`row_id` AS `old_row_id` FROM ".C('DB_PREFIX').'atme AS a LEFT JOIN '.C('DB_PREFIX').'comment AS b ON a.row_id = b.comment_id WHERE a.uid = '.$this->mid.' ORDER BY a.`atme_id` DESC) AS NEW ';
                $atList = D()->table($table)->group('`app`, `table`, `row_id`, `uid`')->order('`atme_id` DESC')->findPage($limit);
                if (!empty($atList)) {
                    $space = $content = '';
                    foreach ($atList['data'] as $item) {
                        $item['row_id'] = $item['old_row_id'];
                        switch (strtolower($item['table'])) {
                            case 'feed':
                                $data = model('Feed')->getFeedInfo($item['row_id']);
                                $space = '<a href="'.U('public/Profile/index', array(
                                        'uid' => $data['uid'],
                                )).'">'.getUserName($data['uid']).'</a>';
                                if ($data['is_audit'] == 0) {
                                    $content = '内容正在审核';
                                } else {
                                    $data['content'] = explode('//', $data['content']);
                                    $data['content'] = $data['content'][0];
                                    $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($data['content']));
                                }
                                $html .= '<li source-id="'.$item['atme_id'].'">'.$space.'在分享中@我：'.$content.'</li>';
                                break;
                            case 'comment':
                                $data = model('Comment')->getCommentInfo($item['row_id']);
                                $space = '<a href="'.U('public/Profile/index', array(
                                        'uid' => $data['uid'],
                                )).'">'.getUserName($data['uid']).'</a>';
                                if ($data['is_audit'] == 0) {
                                    $content = '内容正在审核';
                                } else {
                                    $data['content'] = explode('//', $data['content']);
                                    $data['content'] = $data['content'][0];
                                    if (preg_match("/^<a\s+href=[^h]*http:$/", $data['content'])) {
                                        $data['content'] .= $data['content'][1];
                                    }
                                    $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($data['content']));
                                }
                                $html .= '<li source-id="'.$item['atme_id'].'">'.$space.'在评论中@我：'.$content.'</li>';
                                break;
                        }
                    }
                }
                model('UserCount')->resetUserCount($this->mid, 'unread_atme', 0);
                break;
            case 'com':

                // 收到的
                // $map['_string'] = " (to_uid = '{$this->mid}' OR app_uid = '{$this->mid}') AND is_del = 0 AND uid !=".$this->mid;

                $tab = model('Comment')->getTab($map);
                $tabHash = array();
                foreach ($tab as $key => $val) {
                    if ($key === 'feed') {
                        $tabHash['feed'] = '分享';
                    } elseif ($key === 'webpage') {
                        $tabHash['webpage'] = '评论箱';
                    } else {
                        strtolower($key) === 'weiba_post' && $key = 'weiba';
                        $langKey = 'PUBLIC_APPNAME_'.strtoupper($key);
                        $lang = L($langKey);
                        if ($lang == $langKey) {
                            $tabHash[$key] = ucfirst($key);
                        } else {
                            $tabHash[$key] = $lang;
                        }
                    }
                }
                $table = '(SELECT * FROM `'.C('DB_PREFIX')."comment` WHERE ((`to_uid` = '".$this->mid."' OR `app_uid` = '".$this->mid."') AND `is_del` = 0 AND `uid` != '".$this->mid."') AND `table` != 'webpage' ORDER BY `ctime` DESC) AS NEW ";
                $commentList = D()->table($table)->group('`app` , `table` , `row_id` , `app_uid` , `uid`')->order('`ctime` DESC')->findPage($limit);
                foreach ($commentList['data'] as $item) {
                    $space = '<a href="'.U('public/Profile/index', array(
                            'uid' => $item['uid'],
                    )).'">'.getUserName($item['uid']).'</a>';
                    $feed = model('Feed')->get($item['row_id']);
                    if ($feed['is_audit'] == 0) {
                        $content = '内容正在审核';
                    } else {
                        if (($item['table'] === 'feed' && $item['app'] === 'public') || $feed['is_repost'] == 1) {
                            $content = unserialize($feed['feed_data']);
                            $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html(getShort($content['content'], 20, '...')));
                        } else {
                            $source = model('Source')->getSourceInfo($item['table'], $item['row_id']);
                            $title = empty($source['api_source']['title']) ? '内容已被删除' : $source['api_source']['title'];
                            $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($title));
                        }
                    }
                    $html .= '<li source-id="'.$item['comment_id'].'">'.$space.'评论了'.$tabHash[$item['table']].'：'.$content.'</li>';
                }
                model('UserCount')->resetUserCount($this->mid, 'unread_comment', 0);
                break;
            case 'pmsg':
                $messageList = model('Message')->getMessageListByUid($this->mid, array(
                        MessageModel::ONE_ON_ONE_CHAT,
                        MessageModel::MULTIPLAYER_CHAT,
                ), $limit);
                foreach ($messageList['data'] as $item) {
                    if ($item['last_message']['from_uid'] == $this->mid) {
                        $to = model('User')->getUserInfoByUids($item['last_message']['to_uid']);
                        $to = getSubByKey($to, 'space_link_no');
                        $space = implode('、', $to);
                        $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($item['last_message']['content']));
                        $html .= '<li source-id="'.$item['list_id'].'">我发送给'.$space.'：'.$content.'</li>';
                    } else {
                        $space = $item['last_message']['user_info']['space_link_no'];
                        $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($item['last_message']['content']));
                        $html .= '<li source-id="'.$item['list_id'].'">'.$space.'说：'.$content.'</li>';
                    }
                }
                model('Message')->setAllIsRead($this->mid);
                break;
        }

        if (!empty($html)) {
            $data['html'] = $html;
            $this->ajaxReturn($data, '获取内容成功', 1);
        } else {
            $this->ajaxReturn(null, '<p class="no-info">暂无此信息</p>', 0);
        }
    }

    public function showTalkBox()
    {
        $type = t($_GET['t']);
        $id = intval($_GET['id']);
        if (empty($id) || empty($type)) {
            return;
        }

        $share = array();
        $limit = 10;

        if (in_array($type, array(
                'at',
                'com',
        ))) {
            if ($type === 'at') {
                $map['atme_id'] = $id;
                $source = model('Atme')->where($map)->find();
                switch (strtolower($source['table'])) {
                    case 'feed':
                        $ownFeed = model('Feed')->get($source['row_id']);
                        $share['row_id'] = $ownFeed['feed_id'];
                        $share['app'] = $ownFeed['app'];
                        $share['table'] = 'feed';
                        $share['to_uid'] = $ownFeed['uid'];
                        break;
                    case 'comment':
                        $ownComment = model('Comment')->getCommentInfo($source['row_id']);
                        $share['row_id'] = $ownComment['row_id'];
                        $share['app'] = $ownComment['app'];
                        $share['table'] = $ownComment['table'];
                        $share['to_uid'] = $ownComment['uid'];
                        break;
                }
            } elseif ($type === 'com') {
                $ownComment = model('Comment')->getCommentInfo($id);
                $share['row_id'] = $ownComment['row_id'];
                $share['app'] = $ownComment['app'];
                $share['table'] = $ownComment['table'];
                $share['to_uid'] = $ownComment['uid'];
            }

            $source = model('Feed')->get($share['row_id']);
            $source['url'] = U('public/Profile/feed', array(
                    'feed_id' => $source['feed_id'],
                    'uid'     => $source['uid'],
            ));
            if ($source['is_audit'] == 0 && $source['uid'] != $this->mid) {
                $source['body'] = '内容正在审核';
            }
            $this->assign('source', $source);

            $this->assign('row_id', $source['feed_id']);
            $this->assign('app_uid', $source['uid']);
            $this->assign('app_row_id', $source['app_row_id']);
            $this->assign('app_row_table', $source['app_row_table']);
            $this->assign('app_name', $source['app']);
            $this->assign('table', 'feed');
            // 转发权限判断
            $canrepost = 1;
            $weiboSet = model('Xdata')->get('admin_Config:feed');
            if (!CheckPermission('core_normal', 'feed_share') || !in_array('repost', $weiboSet['weibo_premission'])) {
                $canrepost = 0;
            }
            $this->assign('canrepost', $canrepost);

            $cancomment = intval(CheckPermission('core_normal', 'feed_comment'));
            $this->assign('cancomment', $cancomment);

            $cmap['app'] = $share['app'];
            $cmap['table'] = $share['table'];
            $cmap['row_id'] = $share['row_id'];
            $cmap['_string'] = '( (uid = '.$this->mid.' AND to_comment_id = 0) OR (uid = '.$share['to_uid'].' AND to_comment_id = 0) OR (uid = '.$this->mid.' AND to_uid = '.$share['to_uid'].') OR (uid = '.$share['to_uid'].' AND to_uid = '.$this->mid.') OR (uid = '.$this->mid.' AND to_uid = '.$this->mid.') OR (uid = '.$share['to_uid'].' AND to_uid = '.$share['to_uid'].') )';
            $talkList = model('Comment')->getCommentList($cmap, 'comment_id ASC', $limit);
            foreach ($talkList['data'] as &$value) {
                if ($value['is_audit'] == 0 && $value['uid'] != $this->mid) {
                    $value['content'] = '内容正在审核';
                }
            }
            $this->assign('talkList', $talkList);

            $this->assign('sourceId', $share['row_id']);
            $sinceId = end($talkList['data']);
            $sinceId = $sinceId['comment_id'];
            $this->assign('sinceId', $sinceId);
            $this->assign('toUid', $share['to_uid']);
            $this->assign('maxId', 0);

            $initNums = model('Xdata')->getConfig('weibo_nums', 'feed');
            $this->assign('initNums', $initNums);
        } elseif ($type === 'pmsg') {
            $message = model('Message')->isMember($id, $this->mid, true);
            // 验证数据
            if (empty($message)) {
                $this->error(L('PUBLIC_PRI_MESSAGE_NOEXIST'));
            }
            $message['member'] = model('Message')->getMessageMembers($id, 'member_uid');
            $message['to'] = array();
            // 添加发送用户ID
            foreach ($message['member'] as $v) {
                $this->mid != $v['member_uid'] && $message['to'][] = $v;
            }
            // 设置信息已读(私信列表页去掉new标识)
            model('Message')->setMessageIsRead($id, $this->mid, 0);
            $message['since_id'] = model('Message')->getSinceMessageId($message['list_id'], $message['message_num']);

            $this->assign('message', $message);

            $userinfo = model('User')->getUserInfo($this->mid);
            $this->assign('userinfo', $userinfo);

            $talkList = model('Message')->getMessageByListId($id, $this->mid, $message['since_id'], 0, 10);
            foreach ($talkList['data'] as &$value) {
                $value['content'] == t($value['content']) && $value['content'] = replaceUrl($value['content']);
            }

            $this->assign('talkList', $talkList);

            $this->assign('sourceId', $id);
            $this->assign('sinceId', $message['since_id']);
            $maxId = end($talkList['data']);
            $maxId = $maxId['message_id'];
            $this->assign('maxId', $maxId);
            $this->assign('toUid', 0);
            $this->assign('max_since_id', $talkList['data'][0]['message_id']);
        }

        $this->assign('type', $type);

        $this->display('talkBox');
    }

    public function loadMoreShowTalk()
    {
        $type = t($_GET['type']);
        $id = intval($_GET['id']);
        $sinceId = intval($_GET['since_id']);
        $toUid = intval($_GET['to_uid']);
        $maxId = intval($_GET['max_id']);

        $status = 0;
        $sinceId = 0;
        $html = '';
        $count = 0;

        switch ($type) {
            case 'at':
            case 'com':
                $feed = model('Feed')->getFeedInfo($id);

                $cmap['app'] = $feed['app'];
                $cmap['table'] = 'feed';
                $cmap['row_id'] = $id;
                // $cmap['comment_id'] = array('ELT', $sinceId);
                $cmap['comment_id'] = array(
                        'EGT',
                        $sinceId,
                );
                $cmap['_string'] = '( (uid = '.$this->mid.' AND to_comment_id = 0) OR (uid = '.$toUid.' AND to_comment_id = 0) OR (uid = '.$this->mid.' AND to_uid = '.$toUid.') OR (uid = '.$toUid.' AND to_uid = '.$this->mid.') )';
                $list = model('Comment')->getCommentList($cmap, 'comment_id ASC', 10);
                if (!empty($list['data'])) {
                    $status = 1;
                    $sinceId = end($list['data']);
                    $sinceId = $sinceId['comment_id'] + 1;
                    foreach ($list['data'] as $vo) {
                        $html .= '<dl class="msg-dialog" model-node="comment_list">';
                        $class = ($this->mid == $vo['uid']) ? 'right' : 'left';
                        $html .= '<dt class="'.$class.'">';
                        $html .= '<a target="_self" href="'.U('public/Profile/index', array(
                                'uid' => $vo['uid'],
                        )).'"><img src="'.$vo['user_info']['avatar_tiny'].'" /></a>';
                        $html .= '</dt>';
                        if ($class == 'right') {
                            $html .= '<dd class="dialog-r">';
                            $html .= '<i class="arrow-mes-r"></i>';
                        } elseif ($class == 'left') {
                            $html .= '<dd class="dialog-l">';
                            $html .= '<i class="arrow-mes-l"></i>';
                        }
                        if ($vo['is_audit'] == 0 && $vo['uid'] != $this->mid) {
                            $content = '内容正在审核';
                        } else {
                            $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($vo['content']));
                        }
                        $html .= '<p class="info">'.getUserSpace($vo['user_info']['uid'], '', $vo['user_info']['uname']).'：'.$content.'</p>';
                        $html .= '<p class="date">';
                        $html .= '<span class="right">';
                        if (($this->mid == $vo['uid'] && CheckPermission('core_normal', 'comment_del')) || CheckPermission('core_admin', 'comment_del')) {
                            $style = '';
                            if ($vo['uid'] != $this->mid && CheckPermission('core_admin', 'comment_del')) {
                                $style = 'style="color:red;"';
                            }
                            $html .= '<a href="javascript:;" event-node="comment_del" event-args="comment_id='.$vo['comment_id'].'" '.$style.'>'.L('PUBLIC_STREAM_DELETE').'</a>';
                        }
                        if ((($vo['uid'] == $mid && CheckPermission('core_normal', 'comment_del')) || CheckPermission('core_admin', 'comment_del')) && CheckPermission('core_normal', 'feed_comment')) {
                            $html .= '<i class="vline">|</i>';
                        }
                        if (CheckPermission('core_normal', 'feed_comment')) {
                            $args = array();
                            $args[] = 'row_id='.$vo['row_id'];
                            $args[] = 'app_uid='.$vo['app_uid'];
                            $args[] = 'to_comment_id='.$vo['comment_id'];
                            $args[] = 'to_uid='.$vo['uid'];
                            $args[] = 'to_comment_uname='.$vo['user_info']['uname'];
                            $args[] = 'app_name=public';
                            $args[] = 'table=feed';
                            $html .= '<a href="javascript:;" event-args="'.implode('&', $args).'" event-node="reply_comment" >回复</a>';
                        }
                        $html .= '</span>'.friendlyDate($vo['ctime']).'</p>';
                        $html .= '</dd>';
                        $html .= '</dl>';
                    }
                }
                break;
            case 'pmsg':
                $list = model('Message')->getMessageByListId($id, $this->mid, $sinceId, $maxId, 10);
                if (!empty($list['data'])) {
                    $status = 1;
                    $sinceId = $list['since_id'];
                    $maxId = $list['max_id'];
                    foreach ($list['data'] as $vo) {
                        $html .= '<dl class="msg-dialog" model-node="comment_list">';
                        $class = ($this->mid == $vo['from_uid']) ? 'right' : 'left';
                        $html .= '<dt class="'.$class.'">';
                        $html .= '<a target="_self" href="'.U('public/Profile/index', array(
                                'uid' => $vo['from_uid'],
                        )).'"><img src="'.$vo['user_info']['avatar_tiny'].'" /></a>';
                        $html .= '</dt>';
                        if ($class == 'right') {
                            $html .= '<dd class="dialog-r">';
                            $html .= '<i class="arrow-mes-r"></i>';
                        } elseif ($class == 'left') {
                            $html .= '<dd class="dialog-l">';
                            $html .= '<i class="arrow-mes-l"></i>';
                        }
                        $content = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($vo['content']));
                        $html .= '<p class="info mb5">'.getUserSpace($vo['user_info']['uid'], '', $vo['user_info']['uname']).'：'.$content.'</p>';

                        if ($vo['attach_type'] == 'message_image') {
                            $html .= '<div class="feed_img_lists">';
                            $html .= '<ul class="small">';
                            foreach ($vo['attach_infos'] as $v) {
                                $html .= '<li class="left"><a><img src="'.getImageUrl($v['file'], 100, 100, true).'" width="100" height="100" /></a></li>';
                            }
                            $html .= '</ul>';
                            $html .= '</div>';
                        } elseif ($vo['attach_type'] == 'message_file') {
                            $html .= '<div class="input-content attach-file">';
                            $html .= '<ul class="feed_file_list">';
                            foreach ($vo['attach_infos'] as $v) {
                                $html .= '<li><a href="'.U('widget/Upload/down', array(
                                        'attach_id' => $v['attach_id'],
                                )).'" class="current right" title="下载"><i class="ico-down"></i></a><i class="ico-'.$v['extension'].'-small"></i><a href="'.U('widget/Upload/down', array(
                                        'attach_id' => $v['attach_id'],
                                )).'">'.$v['attach_name'].'</a><span class="tips">('.byte_format($v['size']).')</span></li>';
                            }
                            $html .= '</ul>';
                            $html .= '</div>';
                        }

                        $html .= '<p class="date">'.friendlyDate($vo['mtime']).'</p>';
                        $html .= '</dd>';
                        $html .= '</dl>';
                    }
                }
                $count = $list['count'];
                break;
        }

        $result['status'] = $status;
        $result['since_id'] = $sinceId;
        $result['max_id'] = $maxId;
        $result['count'] = $count;
        $result['html'] = $html;

        exit(json_encode($result));
    }

    public function fixed()
    {
        $fixed = $_POST['fixed'];
        $map['is_fixed'] = $fixed == 1 ? 0 : 1;
        $result = D('user')->where('uid='.$this->uid)->save($map);
        D('user')->cleanCache($this->uid);
        if ($result == 1) {
            $this->ajaxReturn(null, '操作成功', 1);
        } else {
            $this->ajaxReturn(null, '操作失败', 0);
        }
    }
}
