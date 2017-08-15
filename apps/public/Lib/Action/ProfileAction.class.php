<?php

use Apps\Wenda\Model\Answer;
use Apps\Wenda\Model\ProFile     as ProFileModel;
use Apps\Wenda\Model\Question;

/**
 * ProfileAction 个人档案模块.
 *
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.0
 */
class ProfileAction extends Action
{
    /**
     * _initialize 模块初始化.
     */
    protected function _initialize()
    {
        // 短域名判断
        if (!isset($_GET['uid']) || empty($_GET['uid'])) {
            $this->uid = $this->mid;
        } elseif (is_numeric($_GET['uid'])) {
            $this->uid = intval($_GET['uid']);
        } else {
            $map['domain'] = t($_GET['uid']);
            $this->uid = model('User')->where($map)->getField('uid');
        }
        $this->assign('uid', $this->uid);

        // # 微吧和频道开关检测
        $weibaIfOpen = model('App')->getAppByName('weiba');
        $weibaIfOpen = $weibaIfOpen['status'];
        $channelIfOpen = model('App')->getAppByName('channel');
        $channelIfOpen = $channelIfOpen['status'];
        $wendaIfOpen = model('App')->getAppByName('wenda');
        $this->assign('wendaIfOpen', $wendaIfOpen['status']);
        $this->assign('weibaIfOpen', $weibaIfOpen);
        $this->assign('channelIfOpen', $channelIfOpen);
    }

    /**
     * 隐私设置.
     */
    public function privacy($uid)
    {
        if ($this->mid != $uid) {
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $uid);

            return $privacy;
        } else {
            return true;
        }
    }

    public function relationship()
    {
        // 获取用户信息
        $user_info = model('User')->getUserInfo($this->uid);
        // 用户为空，则跳转用户不存在
        if (empty($user_info)) {
            $this->error(L('PUBLIC_USER_NOEXIST'));
        }
        // 个人空间头部
        $this->_top();
        // 判断隐私设置
        $userPrivacy = $this->privacy($this->uid);
        if ($userPrivacy['space'] !== 1) {
            $key = t($_REQUEST['follow_key']);
            $follower_list = model('Union')->getFriendsList($this->uid, $key);
            $this->assign('follow_key', $key);
            $this->assign('jsonKey', json_encode($key));
            $fids = getSubByKey($follower_list['data'], 'fid');
            if ($fids) {
                $uids = array_merge($fids, array(
                        $this->uid,
                ));
            } else {
                $uids = array(
                        $this->uid,
                );
            }
            // 获取用户用户组信息
            $this->_assignFollowState($uids);
            $this->_assignUserInfo($uids);
            $this->_assignUserProfile($uids);
            $this->_assignUserTag($uids);
            $this->_assignUserCount($fids);

            $this->assign('follower_list', $follower_list);
        } else {
            $this->_assignUserInfo($this->uid);
        }
        $this->assign('userPrivacy', $userPrivacy);

        $this->display();
    }

    public function collection()
    {
        $this->_top();
        $this->_assignUserInfo($this->uid);

        // 获取信息
        if ($_GET['feed_type'] == 'weiba') {
            $map['uid'] = $this->uid;
            $post = M('weiba_favorite')->where($map)->select();
            $maps['post_id'] = array(
                    'in',
                    getSubByKey($post, 'post_id'),
            );
            $weiba = M('weiba_post')->where($maps)->select();
            $weiba_ids = getSubByKey($weiba, 'weiba_id');
            $nameArr = $this->_getWeibaName($weiba_ids);
            foreach ($weiba as $k => $v) {
                $weiba[$k]['weiba'] = $nameArr[$v['weiba_id']];
                $weiba[$k]['user'] = model('User')->getUserInfo($v['post_uid']);
                $weiba[$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
                // $images = matchImages($v['content']);
                // $images[0] && $weiba[$k]['image'] = array_slice( $images , 0 , 5 );
                $image = getEditorImages($v['content']);
                !empty($image) && $weiba[$k]['image'] = array(
                        $image,
                );
                // 匹配图片的src
                preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
                foreach ($match[1] as $imgurl) {
                    $imgurl = $imgurl;
                    if (!empty($imgurl)) {
                        $weiba[$k]['img'][] = $imgurl;
                    }
                }
                $is_digg = M('weiba_post_digg')->where('post_id='.$v['post_id'].' and uid='.$this->mid)->find();
                $weiba[$k]['digg'] = $is_digg ? 'digg' : 'undigg';
                $weiba[$k]['content'] = t($weiba[$k]['content']);
            }
            $this->assign('weiba', $weiba);
        }
        $this->display();
    }

    private function _getWeibaName($weiba_ids)
    {
        $weiba_ids = array_unique($weiba_ids);
        if (empty($weiba_ids)) {
            return false;
        }
        $map['weiba_id'] = array(
                'in',
                $weiba_ids,
        );
        $names = D('weiba')->where($map)->field('weiba_id,weiba_name')->findAll();
        foreach ($names as $n) {
            $nameArr[$n['weiba_id']] = $n['weiba_name'];
        }

        return $nameArr;
    }

    public function photo()
    {
        if ($this->uid != $this->mid) {
            // $this->error ( '无权限查看' );
        }

        $this->_top();
        $this->_assignUserInfo($this->uid);

        // 晒物
        $map['uid'] = $this->uid;
        $products = M('shop_product_share')->where($map)->order('cTime desc')->findPage();

        $ids = getSubByKey($products['data'], 'product_id');
        $map['product_id'] = array(
                'in',
                $ids,
        );
        $goods = M('shop_product')->where($map)->findAll();
        foreach ($goods as $g) {
            $goodArr[$g['product_id']] = $g;
        }

        foreach ($products['data'] as $k => &$p) {
            if (isset($goodArr[$p['product_id']])) {
                $p = array_merge($p, (array) $goodArr[$p['product_id']]);
            } else {
                $arr = model('Shop')->GetGoodsInfo($p['product_id']);
                $p = array_merge($p, (array) $arr['result']);
            }
        }
        $this->assign('products', $products);
        // dump($products);exit;

        $this->display();
    }

    public function photo_del()
    {
        $map['uid'] = 1;
        $map['id'] = intval($_POST['id']);

        $res = M('shop_product_share')->where($map)->delete();

        echo $res ? 1 : 0;
    }

    /**
     * 个人档案展示页面.
     */
    public function index()
    {
        // $list = M('user')->where('uid!=2')->field('uid')->limit(100)->findAll();
        // $data['cTime'] = time();
        // foreach ($list as $v){
        // $data['uid'] = $v['uid'];
        // $data['fid'] = 2;
        // M('user_union')->add($data);
        // }
        // dump($list);exit;
        // 获取用户信息
        $user_info = model('User')->getUserInfo($this->uid);
        // 用户为空，则跳转用户不存在
        if (empty($user_info)) {
            $this->error(L('PUBLIC_USER_NOEXIST'));
        }
        // 个人空间头部
        $this->_top();
        $this->_tab_menu();

        // 判断隐私设置
        $userPrivacy = $this->privacy($this->uid);
        if ($userPrivacy['space'] !== 1) {
            $this->_sidebar();
            // 加载分享筛选信息
            $d['feed_type'] = t($_REQUEST['feed_type']) ? t($_REQUEST['feed_type']) : '';
            $d['feed_key'] = t($_REQUEST['feed_key']) ? t($_REQUEST['feed_key']) : '';
            $this->assign($d);
        } else {
            $this->_assignUserInfo($this->uid);
        }

        // 添加积分
        model('Credit')->setUserCredit($this->uid, 'space_access');

        $this->assign('userPrivacy', $userPrivacy);
        // seo
        $seo = model('Xdata')->get('admin_Config:seo_user_profile');
        $replace['uname'] = $user_info['uname'];
        if ($feed_id = model('Feed')->where('uid='.$this->uid)->order('publish_time desc')->limit(1)->getField('feed_id')) {
            $replace['lastFeed'] = D('feed_data')->where('feed_id='.$feed_id)->getField('feed_content');
        }
        $replaces = array_keys($replace);
        foreach ($replaces as &$v) {
            $v = '{'.$v.'}';
        }
        $seo['title'] = str_replace($replaces, $replace, $seo['title']);
        $seo['keywords'] = str_replace($replaces, $replace, $seo['keywords']);
        $seo['des'] = str_replace($replaces, $replace, $seo['des']);
        !empty($seo['title']) && $this->setTitle($seo['title']);
        !empty($seo['keywords']) && $this->setKeywords($seo['keywords']);
        !empty($seo['des']) && $this->setDescription($seo['des']);
        $this->display();
    }

    /**
     * 获取指定用户的应用数据列表.
     *
     * @return array 指定用户的应用数据列表
     */
    public function appList()
    {
        // 获取用户信息
        $user_info = model('User')->getUserInfo($this->uid);
        // 用户为空，则跳转用户不存在
        if (empty($user_info)) {
            $this->error(L('PUBLIC_USER_NOEXIST'));
        }
        // 个人空间头部
        $this->_top();
        $this->_assignUserInfo($this->uid);

        $appArr = $this->_tab_menu();
        $type = t($_GET['type']);
        if (!isset($appArr[$type])) {
            $this->error('参数出错！！');
        }
        $this->assign('type', $type);
        $className = ucfirst($type).'Protocol';
        $content = D($className, $type)->profileContent($this->uid);
        if (empty($content)) {
            $content = '暂无内容';
        }
        $this->assign('profileContent', $content);
        // 判断隐私设置
        $userPrivacy = $this->privacy($this->uid);
        if ($userPrivacy['space'] !== 1) {
            $this->_sidebar();
            // 档案类型
            $ProfileType = model('UserProfile')->getCategoryList();
            $this->assign('ProfileType', $ProfileType);
            // 个人资料
            $this->_assignUserProfile($this->uid);
            // 获取用户职业信息
            $userCategory = model('UserCategory')->getRelatedUserInfo($this->uid);
            if (!empty($userCategory)) {
                foreach ($userCategory as $value) {
                    $user_category .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
                }
            }
            $this->assign('user_category', $user_category);
        } else {
            $this->_assignUserInfo($this->uid);
        }
        $this->assign('userPrivacy', $userPrivacy);
        $this->setTitle($user_info['uname'].'的'.L('PUBLIC_APPNAME_'.$type));
        $this->setKeywords($user_info['uname'].'的'.L('PUBLIC_APPNAME_'.$type));
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
                $this->uid,
        ));
        $this->setDescription(t($user_category.$user_info['location'].','.implode(',', $user_tag[$this->uid]).','.$user_info['intro']));

        $this->display();
    }

    /**
     * 获取指定应用的信息.
     */
    public function appprofile()
    {
        $user_info = model('User')->getUserInfo($this->uid);
        if (empty($user_info)) {
            $this->error(L('PUBLIC_USER_NOEXIST'));
        }

        $d['widgetName'] = ucfirst(t($_GET['appname'])).'Profile';
        foreach ($_GET as $k => $v) {
            $d['widgetAttr'][$k] = t($v);
        }
        $d['widgetAttr']['widget_appname'] = t($_GET['appname']);
        $this->assign($d);

        $this->_assignUserInfo(array(
                $this->uid,
        ));
        ($this->mid != $this->uid) && $this->_assignFollowState($this->uid);
        $this->display();
    }

    /**
     * 获取用户详细资料.
     */
    public function data()
    {
        if (!CheckPermission('core_normal', 'read_data') && $this->uid != $this->mid) {
            $this->error('对不起，您没有权限浏览该内容!');
        }
        // 获取用户信息
        $user_info = model('User')->getUserInfo($this->uid);
        // 用户为空，则跳转用户不存在
        if (empty($user_info)) {
            $this->error(L('PUBLIC_USER_NOEXIST'));
        }
        // 个人空间头部
        $this->_top();
        // 判断隐私设置
        $userPrivacy = $this->privacy($this->uid);
        if ($userPrivacy['space'] !== 1) {
            $this->_sidebar();
            // 档案类型
            $ProfileType = model('UserProfile')->getCategoryList();
            $this->assign('ProfileType', $ProfileType);
            // 个人资料
            $this->_assignUserProfile($this->uid);
            // 获取用户职业信息
            $userCategory = model('UserCategory')->getRelatedUserInfo($this->uid);
            if (!empty($userCategory)) {
                foreach ($userCategory as $value) {
                    $user_category .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
                }
            }
            $this->assign('user_category', $user_category);
        } else {
            $this->_assignUserInfo($this->uid);
        }
        $this->assign('userPrivacy', $userPrivacy);

        $this->setTitle($user_info['uname'].'的资料');
        $this->setKeywords($user_info['uname'].'的资料');
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
                $this->uid,
        ));
        $this->setDescription(t($user_category.$user_info['location'].','.implode(',', $user_tag[$this->uid]).','.$user_info['intro']));
        $this->display();
    }

    /**
     * 获取指定用户的某条动态
     */
    public function feed()
    {
        $feed_id = intval($_GET['feed_id']);
        if (empty($feed_id)) {
            $this->error(L('PUBLIC_INFO_ALREADY_DELETE_TIPS'));
        }

        // 获取用户信息
        $user_info = model('User')->getUserInfo($this->uid);

        // 个人空间头部
        $this->_top();
        // 判断隐私设置
        $userPrivacy = $this->privacy($this->uid);
        if ($userPrivacy['space'] !== 1) {
            $this->_sidebar();
            $feedInfo = model('Feed')->get($feed_id);
            if (!$feedInfo) {
                $this->error('该分享不存在或已被删除');
            }
            // if (intval ( $_GET ['uid'] ) != $feedInfo ['uid'])
            // $this->error ( '参数错误' );
            if ($feedInfo['is_audit'] == '0' && $feedInfo['uid'] != $this->mid) {
                $this->error('此分享正在审核');
                exit();
            }
            if ($feedInfo['is_del'] == '1') {
                $this->error(L('PUBLIC_NO_RELATE_WEIBO'));
                exit();
            }

            $weiboSet = model('Xdata')->get('admin_Config:feed');
            $a['initNums'] = $weiboSet['weibo_nums'];
            $a['weibo_type'] = $weiboSet['weibo_type'];
            $a['weibo_premission'] = $weiboSet['weibo_premission'];
            $this->assign($a);
            if ($feedInfo['from'] == '1') {
                $feedInfo['from'] = getFromClient(6, $feedInfo['app'], '3G版');
            } else {
                switch ($feedInfo['app']) {
                    case 'weiba':
                        $feedInfo['from'] = getFromClient(0, $feedInfo['app'], '微吧');
                        break;
                    default:
                        $feedInfo['from'] = getFromClient($feedInfo['from'], $feedInfo['app']);
                        break;
                }
            }
            // $feedInfo['from'] = getFromClient( $feedInfo['from'] , $feedInfo['app']);
            // 分享图片
            if ($feedInfo['type'] === 'postimage') {
                $var = unserialize(formatEmoji(true, $feedInfo['feed_data']));
                $feedInfo['image_body'] = $var['body'];
                if (!empty($var['attach_id'])) {
                    $var['attachInfo'] = model('Attach')->getAttachByIds($var['attach_id']);
                    foreach ($var['attachInfo'] as $ak => $av) {
                        $_attach = array(
                                'attach_id'   => $av['attach_id'],
                                'attach_name' => $av['name'],
                                'attach_url'  => getImageUrl($av['save_path'].$av['save_name'], 580),
                                'extension'   => $av['extension'],
                                'size'        => $av['size'],
                        );
                        // $_attach ['attach_small'] = getImageUrl ( $av ['save_path'] . $av ['save_name'], 100, 100, true );
                        // $_attach ['attach_middle'] = getImageUrl ( $av ['save_path'] . $av ['save_name'], 740 );
                        $feedInfo['attachInfo'][$ak] = $_attach;
                    }
                }
            } elseif ($feedInfo['type'] === 'postvideo') {
                $var = unserialize($feedInfo['feed_data']);
                $feedInfo['videoInfo'] = $var;
            } elseif ($feedInfo['type'] === 'postfile') {
                $var = unserialize($feedInfo['feed_data']);
                $feedInfo['fileInfo'] = $var;
                $feedInfo['fileInfo']['files'] = array();
                $files = model('Attach')->getAttachByIds($var['attach_id']);
                if ($files) {
                    $feedInfo['fileInfo']['files'] = $files;
                }
            }
            $this->assign('feedInfo', $feedInfo);
        } else {
            $this->_assignUserInfo($this->uid);
        }
        // seo
        $feedContent = unserialize($feedInfo['feed_data']);
        $seo = model('Xdata')->get('admin_Config:seo_feed_detail');
        $replace['content'] = $feedContent['content'];
        $replace['uname'] = $feedInfo['user_info']['uname'];
        $replaces = array_keys($replace);
        foreach ($replaces as &$v) {
            $v = '{'.$v.'}';
        }
        $seo['title'] = str_replace($replaces, $replace, $seo['title']);
        $seo['keywords'] = str_replace($replaces, $replace, $seo['keywords']);
        $seo['des'] = str_replace($replaces, $replace, $seo['des']);
        !empty($seo['title']) && $this->setTitle($seo['title']);
        !empty($seo['keywords']) && $this->setKeywords($seo['keywords']);
        !empty($seo['des']) && $this->setDescription($seo['des']);
        $this->assign('userPrivacy', $userPrivacy);
        // 赞功能
        $diggArr = model('FeedDigg')->checkIsDigg($feed_id, $this->mid);
        $this->assign('diggArr', $diggArr);

        $cancomment_old_type = array(
                'post',
                'repost',
                'postimage',
                'postfile',
                'weiba_post',
                'weiba_repost',
                'blog_post',
                'blog_repost',
                'event_post',
                'event_repost',
                'vote_post',
                'vote_repost',
                'photo_post',
                'photo_repost',
        );
        $this->assign('cancomment_old_type', $cancomment_old_type);

        $appRow = model('Source')->getSourceInfo($feedInfo['app_row_table'], $feedInfo['app_row_id']);
        $appUid = $appRow['source_user_info']['uid'];
        $this->assign('appUid', $appUid);

        $this->display();
    }

    /**
     * 获取用户�
     * �注列表.
     */
    public function following()
    {
        // 获取用户信息
        $user_info = model('User')->getUserInfo($this->uid);
        // 用户为空，则跳转用户不存在
        if (empty($user_info)) {
            $this->error(L('PUBLIC_USER_NOEXIST'));
        }
        // 个人空间头部
        $this->_top();
        // 判断隐私设置
        $userPrivacy = $this->privacy($this->uid);
        if ($userPrivacy['space'] !== 1) {
            $key = t($_REQUEST['follow_key']);
            if ($key === '') {
                $following_list = model('Follow')->getFollowingList($this->uid, intval($_GET['gid']), 20);
            } else {
                $following_list = model('Follow')->searchFollows($key, 'following', 20, $this->uid);
                $this->assign('follow_key', $key);
                $this->assign('jsonKey', json_encode($key));
            }
            $fids = getSubByKey($following_list['data'], 'fid');

            if ($fids) {
                $uids = array_merge($fids, array(
                        $this->uid,
                ));
            } else {
                $uids = array(
                        $this->uid,
                );
            }
            // 获取用户组信息
            $userGroupData = model('UserGroupLink')->getUserGroupData($uids);
            $this->assign('userGroupData', $userGroupData);
            $this->_assignFollowState($uids);
            $this->_assignUserInfo($uids);
            $this->_assignUserProfile($uids);
            $this->_assignUserTag($uids);
            $this->_assignUserCount($fids);
            // 关注分组
            ($this->mid == $this->uid) && $this->_assignFollowGroup($fids);
            $this->assign('following_list', $following_list);
        } else {
            $this->_assignUserInfo($this->uid);
        }
        $this->assign('userPrivacy', $userPrivacy);

        $this->setTitle(L('PUBLIC_TA_FOLLOWING', array(
                'user' => $GLOBALS['ts']['_user']['uname'],
        )));
        $this->setKeywords(L('PUBLIC_TA_FOLLOWING', array(
                'user' => $GLOBALS['ts']['_user']['uname'],
        )));

        // 获取登录用户的所有分组
        if ($this->mid == $this->uid) {
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
            $this->assign('gid', intval($_GET['gid']));

            $groupList_ids = getSubByKey($groupList, 'gid');
            $this->assign('groupList_ids', $groupList_ids);
            $gTitle = D('FollowGroup')->where('follow_group_id='.intval($_GET['gid']))->getField('title');
            $this->assign('gTitle', $gTitle);
            // dump($groupList);

            // 显示的分类个数
            $this->assign('groupNums', 5);
        }
        $this->display();
    }

    /**
     * 获取用户粉丝列表.
     */
    public function follower()
    {
        // 获取用户信息
        $user_info = model('User')->getUserInfo($this->uid);
        // 用户为空，则跳转用户不存在
        if (empty($user_info)) {
            $this->error(L('PUBLIC_USER_NOEXIST'));
        }
        // 个人空间头部
        $this->_top();
        // 判断隐私设置
        $userPrivacy = $this->privacy($this->uid);
        if ($userPrivacy['space'] !== 1) {
            $key = t($_REQUEST['follow_key']);
            if ($key === '') {
                $follower_list = model('Follow')->getFollowerList($this->uid, 20);
            } else {
                $follower_list = model('Follow')->searchFollows($key, 'follower', 20, $this->uid);
                $this->assign('follow_key', $key);
                $this->assign('jsonKey', json_encode($key));
            }
            $fids = getSubByKey($follower_list['data'], 'fid');
            if ($fids) {
                $uids = array_merge($fids, array(
                        $this->uid,
                ));
            } else {
                $uids = array(
                        $this->uid,
                );
            }
            // 获取用户用户组信息
            $userGroupData = model('UserGroupLink')->getUserGroupData($uids);
            $this->assign('userGroupData', $userGroupData);
            $this->_assignFollowState($uids);
            $this->_assignUserInfo($uids);
            $this->_assignUserProfile($uids);
            $this->_assignUserTag($uids);
            $this->_assignUserCount($fids);
            // 更新查看粉丝时间
            if ($this->uid == $this->mid) {
                $t = time() - intval($GLOBALS['ts']['_userData']['view_follower_time']); // 避免服务器时间不一致
                model('UserData')->setUid($this->mid)->updateKey('view_follower_time', $t, true);
            }
            $this->assign('follower_list', $follower_list);
        } else {
            $this->_assignUserInfo($this->uid);
        }
        model('UserCount')->resetUserCount($this->mid, 'new_folower_count', 0);
        $this->assign('userPrivacy', $userPrivacy);

        $this->setTitle(L('PUBLIC_TA_FOLLWER', array(
                'user' => $GLOBALS['ts']['_user']['uname'],
        )));
        $this->setKeywords(L('PUBLIC_TA_FOLLWER', array(
                'user' => $GLOBALS['ts']['_user']['uname'],
        )));
        $this->display();
    }

    /**
     * 批量获取用户的相�
     * �信息加载.
     *
     * @param string|array $uids
     *                           用户ID
     */
    private function _assignUserInfo($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        $user_info = model('User')->getUserInfoByUids($uids);
        foreach ($user_info as $k => $v) {
            if ($v['input_city'] != '') {
                $user_info[$k]['location'] = '海外   '.$v['input_city'];
            }
        }
        $user_info[$this->uid]['certInfo'] = D('user_verified')->where('verified=1 AND uid='.$this->uid)->find();
        if ($user_info[$this->uid]['certInfo']) {
            $icon = getSubByKey($user_info[$this->uid]['user_group'], 'user_group_icon',
                            array('user_group_id', $user_info[$this->uid]['certInfo']['usergroup_id']));
            $icon = array_pop($icon);
            $user_info[$this->uid]['certInfo']['icon'] = basename(substr($icon, 0, strpos($icon, '.')));
        }
        $this->assign('user_info', $user_info);
        // dump($user_info[$this->uid]);
        // exit;
    }

    /**
     * 获取用户的档案信息和资料�
     * �置信息.
     *
     * @param
     *        	mix uids 用户uid
     */
    private function _assignUserProfile($uids)
    {
        $data['user_profile'] = model('UserProfile')->getUserProfileByUids($uids);
        $data['user_profile_setting'] = model('UserProfile')->getUserProfileSetting(array(
                'visiable' => 1,
        ));
        // 用户选择处理 uid->uname
        foreach ($data['user_profile_setting'] as $k => $v) {
            if ($v['form_type'] == 'selectUser') {
                $field_ids[] = $v['field_id'];
            }
            if ($v['form_type'] == 'selectDepart') {
                $field_departs[] = $v['field_id'];
            }
        }
        foreach ($data['user_profile'] as $ku => &$uprofile) {
            foreach ($uprofile as $key => $val) {
                if (in_array($val['field_id'], $field_ids)) {
                    $user_info = model('User')->getUserInfo($val['field_data']);
                    $uprofile[$key]['field_data'] = $user_info['uname'];
                }
                if (in_array($val['field_id'], $field_departs)) {
                    $depart_info = model('Department')->getDepartment($val['field_data']);
                    $uprofile[$key]['field_data'] = $depart_info['title'];
                }
            }
        }
        $this->assign($data);
    }

    /**
     * 根据指定应用和表获取指定用户的标签.
     *
     * @param
     *        	array uids 用户uid数组
     */
    private function _assignUserTag($uids)
    {
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($uids);
        // dump($user_tag);
        $this->assign('user_tag', $user_tag);
    }

    /**
     * 批量获取多个用户的统计数目.
     *
     * @param array $uids
     *                    用户uid数组
     */
    private function _assignUserCount($uids)
    {
        $user_count = model('UserData')->getUserDataByUids($uids);
        $this->assign('user_count', $user_count);
    }

    /**
     * 批量获取用户uid与一群人fids的彼此�
     * �注状态
     *
     * @param array $fids
     *                    用户uid数组
     */
    private function _assignFollowState($fids = null)
    {
        // 批量获取与当前登录用户之间的关注状态
        $follow_state = model('Follow')->getFollowStateByFids($this->mid, $fids);
        $this->assign('follow_state', $follow_state);
        // dump($follow_state);exit;

        $union_state = model('Union')->getUnionStateByFids($this->mid, $fids);
        $this->assign('union_state', $union_state);
        // dump($union_state);exit;
    }

    /**
     * 获取用户最后一条分享数据.
     *
     * @param
     *        	mix uids 用户uid
     * @param
     *        	void
     */
    private function _assignUserLastFeed($uids)
    {
        return true; // 目前不需要这个功能
        $last_feed = model('Feed')->getLastFeed($uids);
        $this->assign('last_feed', $last_feed);
    }

    /**
     * 调整分组列表.
     *
     * @param array $fids
     *                    指定用户�
     * �注的用户列表
     */
    private function _assignFollowGroup($fids)
    {
        $follow_group_list = model('FollowGroup')->getGroupList($this->mid);
        // 调整分组列表
        if (!empty($follow_group_list)) {
            $group_count = count($follow_group_list);
            for ($i = 0; $i < $group_count; $i++) {
                if ($follow_group_list[$i]['follow_group_id'] != $data['gid']) {
                    $follow_group_list[$i]['title'] = (strlen($follow_group_list[$i]['title']) + mb_strlen($follow_group_list[$i]['title'], 'UTF8')) / 2 > 8 ? getShort($follow_group_list[$i]['title'], 3).'...' : $follow_group_list[$i]['title'];
                }
                if ($i < 2) {
                    $data['follow_group_list_1'][] = $follow_group_list[$i];
                } else {
                    if ($follow_group_list[$i]['follow_group_id'] == $data['gid']) {
                        $data['follow_group_list_1'][2] = $follow_group_list[$i];
                        continue;
                    }
                    $data['follow_group_list_2'][] = $follow_group_list[$i];
                }
            }
            if (empty($data['follow_group_list_1'][2]) && !empty($data['follow_group_list_2'][0])) {
                $data['follow_group_list_1'][2] = $data['follow_group_list_2'][0];
                unset($data['follow_group_list_2'][0]);
            }
        }

        $data['follow_group_status'] = model('FollowGroup')->getGroupStatusByFids($this->mid, $fids);

        $this->assign($data);
    }

    /**
     * 个人主页头部数据.
     */
    public function _top()
    {
        // 获取用户组信息
        $userGroupData = model('UserGroupLink')->getUserGroupData($this->uid);
        $this->assign('userGroupData', $userGroupData);
        // 获取用户积分信息
        $userCredit = model('Credit')->getUserCredit($this->uid);
        $this->assign('userCredit', $userCredit);
        // 加载用户关注信息
        ($this->mid != $this->uid) && $this->_assignFollowState($this->uid);
        // 获取用户统计信息
        $userData = model('UserData')->getUserData($this->uid);
        $this->assign('userData', $userData);

        $disableUser = model('DisableUser')->getDisableUserStatus($this->uid);
        $this->assign('disableUser', $disableUser);

        // 用户兴趣
        $tags = model('Tag')->setAppName('public')->setAppTable('user')->getAppTags($this->uid);
        // dump($tags);
        $this->assign('userTags', $tags);
        // 联盟申请
        // $unions = D ()->table ( '`ts_user_union` AS a LEFT JOIN `ts_user_union` AS b ON a.uid = b.fid AND b.uid = a.fid' )->field ( 'a.*' )->where ( 'a.fid = ' . $this->uid . ' AND b.fid IS NULL' )->findAll ();
        // dump(D ()->getLastSql());
        // dump($unions);exit;
        // $this->assign ( 'unions', $unions );
    }

    /**
     * 个人主页标签导航.
     */
    public function _tab_menu()
    {
        // 取全部APP信息
        $map['status'] = 1;
        $appList = model('App')->where($map)->field('app_name')->findAll();
        // 获取APP的HASH数组
        foreach ($appList as $app) {
            $appName = strtolower($app['app_name']);
            $className = ucfirst($appName);
            $dao = D($className.'Protocol', strtolower($className), false);
            if (method_exists($dao, 'profileContent')) {
                $appArr[$appName] = L('PUBLIC_APPNAME_'.$appName);
            }
            unset($dao);
        }
        $this->assign('appArr', $appArr);

        return $appArr;
    }

    /**
     * 个人主页右侧.
     */
    public function _sidebar()
    {
        // 判断用户是否已认证
        $isverify = D('user_verified')->where('verified=1 AND uid='.$this->uid)->find();
        if ($isverify) {
            $this->assign('verifyInfo', $isverify['info']);
        }
        // 判断访问用户是否已认证
        if ($this->mid == $this->uid) {
            $isMidVerify = true;
        } else {
            $isMidVerify = D('user_verified')->where('verified=1 AND uid='.$this->mid)->find();
            $isMidVerify = (bool) $isMidVerify;
        }
        $this->assign('isMidVerify', $isMidVerify);
        // 加载用户标签信息
        $this->_assignUserTag(array(
                $this->uid,
        ));
        // 加载关注列表
        $sidebar_following_list = model('Follow')->getFollowingList($this->uid, null, 12);
        $this->assign('sidebar_following_list', $sidebar_following_list);
        // dump($sidebar_following_list);exit;
        // 加载粉丝列表
        $sidebar_follower_list = model('Follow')->getFollowerList($this->uid, 12);
        $this->assign('sidebar_follower_list', $sidebar_follower_list);
        // 加载用户信息
        $uids = array(
                $this->uid,
        );

        $followingfids = getSubByKey($sidebar_following_list['data'], 'fid');
        $followingfids && $uids = array_merge($uids, $followingfids);

        $followerfids = getSubByKey($sidebar_follower_list['data'], 'fid');
        $followerfids && $uids = array_merge($uids, $followerfids);

        $this->_assignUserInfo($uids);
    }

    public function getDisableBox()
    {
        $uid = intval($_GET['uid']);
        if (empty($uid)) {
            return false;
        }
        $this->assign('uid', $uid);

        $type = t($_GET['t']);
        if (empty($type) || !in_array($type, array(
                'login',
                'post',
        ))) {
            $type = 'login';
        }
        $this->assign('type', $type);

        $uname = getUserName($uid);
        $this->assign('uname', $uname);

        $data = model('DisableUser')->getDisableUser($uid);
        $this->assign('disable', $data);
        $this->assign('disableJson', json_encode($data));

        $this->display('disableUserBox');
    }

    public function setDisableUser()
    {
        $uid = intval($_POST['uid']);
        $disableItem = t($_POST['disableItem']);
        $startTime = strtotime(t($_POST['startTime']));
        $endTime = strtotime(t($_POST['endTime']));

        if (empty($uid) || empty($disableItem) || !in_array($disableItem, array(
                'login',
                'post',
        )) || empty($startTime) || empty($endTime) || $startTime > $endTime || !CheckPermission('core_admin', 'admin_login')) {
            exit(json_encode(array(
                    'status' => 0,
                    'info'   => '操作失败',
            )));
        }

        $result = model('DisableUser')->setDisableUser($uid, $disableItem, $startTime, $endTime);
        $res = array();
        if ($result) {
            $res = array(
                    'status' => '1',
                    'info'   => '操作成功',
            );
        } else {
            $res = array(
                    'status' => '0',
                    'info'   => '操作失败',
            );
        }
        exit(json_encode($res));
    }

    public function setEnableUser()
    {
        $uid = intval($_POST['uid']);
        $type = t($_POST['t']);
        $map['uid'] = $uid;
        $map['type'] = $type;
        $id = model('DisableUser')->where($map)->getField('user_disable_id');

        if (empty($id) && !CheckPermission('core_admin', 'admin_login')) {
            exit(json_encode(array(
                    'status' => '0',
                    'info'   => '操作失败',
            )));
        }

        $result = model('DisableUser')->setEnableUser($id);
        $res = array();
        if ($result) {
            $res = array(
                    'status' => '1',
                    'info'   => '操作成功',
            );
        } else {
            $res = array(
                    'status' => '0',
                    'info'   => '操作失败',
            );
        }
        exit(json_encode($res));
    }

    public function get_feed_img()
    {
        $html = '<dl class="weiboPic clearfix">';
        $type = intval($_POST['type']);
        $max_id = intval($_POST['max_id']);

        $map['a.uid'] = intval($_POST['uid']);
        $map['a.attach_type'] = 'feed_image';
        $map['a.is_del'] = 0;
        $map['f.is_del'] = 0;

        $dao = M()->table(C('DB_PREFIX').'attach as a left join '.C('DB_PREFIX').'feed as f on a.row_id=f.feed_id');

        $res['count'] = intval(M()->table(C('DB_PREFIX').'attach as a left join '.C('DB_PREFIX').'feed as f on a.row_id=f.feed_id')->where($map)->count());

        $order = 'a.attach_id desc';
        if ($max_id > 0) {
            if ($type == 0) {
                $map['attach_id'] = array(
                        'lt',
                        $max_id,
                );
            } else {
                $map['attach_id'] = array(
                        'gt',
                        $max_id,
                );
                $order = 'attach_id asc';
            }
        }

        $lists = M()->table(C('DB_PREFIX').'attach as a left join '.C('DB_PREFIX').'feed as f on a.row_id=f.feed_id')->where($map)->order($order)->limit(5)->findAll();
        // lastsql ();
        // dump ( $lists );
        if ($type == 1) { // 倒序
            $count = count($lists);
            for ($i = 0; $i < $count; $i++) {
                $arr[] = $lists[$count - 1 - $i];
            }
            $lists = $arr;
        }

        foreach ($lists as $k => &$v) {
            $ids[] = intval($v['attach_id']);
            if ($k == 0) {
                $html .= '<dt class="left"><a href="'.U('public/Profile/feed', array(
                        'feed_id' => $v['row_id'],
                        'uid'     => $v['uid'],
                )).'"><img src="'.getImageUrl($v['save_path'].$v['save_name'], 260, 260, true).'" /></a></dt>';
            } else {
                $html .= '<dd><a href="'.U('public/Profile/feed', array(
                        'feed_id' => $v['row_id'],
                        'uid'     => $v['uid'],
                )).'"><img src="'.getImageUrl($v['save_path'].$v['save_name'], 120, 120, true).'" /></a></dd>';
            }
        }
        $html .= '</dl>';

        $max_id2 = max($ids);
        $map['attach_id'] = array(
                'gt',
                $max_id2,
        );
        if ($max_id > 0 && M()->table(C('DB_PREFIX').'attach as a left join '.C('DB_PREFIX').'feed as f on a.row_id=f.feed_id')->where($map)->getField('attach_id')) {
            $html .= '<a class="next-page mt20 left" onClick="get_feed_img(1)"><i class="arrow-left"></i>上一页</a>';
        }

        $max_id = $max_id2;
        $min_id = min($ids);

        $map['attach_id'] = array(
                'lt',
                $min_id,
        );

        if (M()->table(C('DB_PREFIX').'attach as a left join '.C('DB_PREFIX').'feed as f on a.row_id=f.feed_id')->where($map)->getField('attach_id')) {
            $html .= '<a class="next-page mt20 right" onClick="get_feed_img(0)">下一页<i class="arrow-right"></i></a>';
        }

        $res['html'] = $html;
        $res['max_id'] = intval($max_id);
        $res['min_id'] = intval($min_id);

        echo json_encode($res);
    }

    //***************************** 问答start ***********************************//

    public function wenda()
    {
        array_push($this->appCssList, 'answer.css');
        $uid = $_REQUEST['uid'] ? $_REQUEST['uid'] : $this->mid;
        $this->_top();
        $this->_assignUserInfo($uid);
        if ($uid == $this->mid) {
            $asw_type = $_REQUEST['asw_type'] ? $_REQUEST['asw_type'] : 'read';
        } else {
            $asw_type = $_REQUEST['asw_type'] ? $_REQUEST['asw_type'] : 'my_question';
        }
        $ajax = $_REQUEST['ajax'];

        if ($asw_type == 'my_question') {
            $list = $this->questionList();
        } elseif ($asw_type == 'my_answer') {
            $list = $this->answerList();
        } elseif ($asw_type == 'my_collect') {
            $list = $this->questionListByCollect();
        } elseif ($asw_type == 'collect_answer') {
            $list = $this->answersByCollect();
        } else {
            if ($uid == $this->mid) {
                $list = $this->answerNoRead();
            }
        }

        if ($ajax) {
            exit(json_encode($list));
        }
        if ($uid == $this->mid) {
            // 获取未阅回答数
            $noReplyCount = $list['noReplyCount'] ?: ProFileModel::getInstance()
                ->setUid($uid)
                ->getNoReadCount();
            $this->assign('noReplyCount', $noReplyCount);
        }

        // 获取采纳、提问、问题被关注数量
        $user = \Apps\Wenda\Model\User::getInstance()
            ->setUid($uid)
            ->userByUid();
        $this->assign('user', $user);
        $list['nowPage'] = $list['nowPage'] < 1 ? 1 : $list['nowPage'];
        $this->assign('uid', $uid);
        $this->assign('asw_type', $asw_type);
        if ($list['totalPages'] > $list['nowPage']) {
            $this->assign('addPage', ($list['nowPage'] + 1));
            $this->assign('loadmore', 1);
        }
        $this->assign('list', $list['data']);
        $this->display();
    }

    /**
     * 未�
     * 回答.
     */
    public function answerNoRead()
    {
        $uid = $_REQUEST['uid'] ? $_REQUEST['uid'] : $this->mid;
        $list = ProFileModel::getInstance()
            ->setUid($uid)
            ->setLimit(10)
            ->answerUnReadList();

        return $list;
    }

    /**
     * 我提的问题.
     */
    public function questionList()
    {
        $uid = $_REQUEST['uid'] ? $_REQUEST['uid'] : $this->mid;
        $list = Question::getInstance()
            ->setUid($uid)
            ->setOrderField('question_id')
            ->setOrderAsc('DESC')
            ->setLimit(10)
            ->setFields('question_id', 'title')
            ->questionTplList($this->mid);

        return $list;
    }

    /**
     * 我�
     * �注的问题.
     */
    public function questionListByCollect()
    {
        $uid = $_REQUEST['uid'] ? $_REQUEST['uid'] : $this->mid;
        $list = ProFileModel::getInstance()
            ->setType(1)
            ->setUid($uid)
            ->setFields('uid', 'row_id')
            ->setLimit(10)
            ->questionListByCollect();

        if ($list['data'][0]) {
            foreach ($list['data'] as &$v) {
                $v = '<div class="asw-question-item"><h4 class="post-title hide-txt"><a href="'.U('Wenda/Index/question', array('question_id' => $v['question_id'])).'">'.$v['title'].'</a></h4></div><div class="asw-xline"></div>';
            }
        }

        return $list;
    }

    /**
     * 我的回答列表.
     */
    public function answerList()
    {
        $uid = $_REQUEST['uid'] ? $_REQUEST['uid'] : $this->mid;
        $list = Answer::getInstance()
            ->setUid($uid)
            ->setOrderField('answer_id')
            ->setOrderAsc('DESC')
            ->setFields('answer_id', 'reply_count', 'uid', 'question_id', 'content', 'is_adopt', 'rtime')
            ->setLimit(10)
            ->answerListTpl($this->mid);

        return $list;
    }

    /**
     * 我收藏的回答列表.
     */
    public function answersByCollect()
    {
        $uid = $_REQUEST['uid'] ? $_REQUEST['uid'] : $this->mid;
        $list = ProFileModel::getInstance()
            ->setType(2)
            ->setUid($uid)
            ->setFields('uid', 'row_id')
            ->setLimit(10)
            ->answerListByCollect();

        return $list;
    }

    //***************************** 问答end ***********************************//
}
