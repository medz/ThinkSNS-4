<?php
/**
 * 微吧控制器.
 *
 * @author
 *
 * @version TS3.0
 */
class IndexAction extends Action
{
    /**
     * 微吧首页.
     */
    public function index()
    {

        //微吧推荐
        $this->_weiba_recommend(4, 100, 100);
        //帖子列表
        $post_type = in_array(t($_GET['post_type']), array('top', 'new', 'reply', 'hot', 'digest')) ? t($_GET['post_type']) : 'new';
        $this->assign('post_type', $post_type);
        $post_list = $this->index_post_list($post_type, $_GET['p']);
        $this->assign('post_list', $post_list);
        //微吧达人
        $daren_arr = $this->_weiba_daren();
        $this->assign('daren_arr', $daren_arr);
        //帖子推荐+置顶
        $post_recommend_list = $this->_post_list('topandrecomment', 10);
        $this->assign('post_recommend_list', $post_recommend_list);
        // dump($post_recommend_list);exit;
        //微吧排行榜
        // $this->_weibaOrder();
        //帖子列表
        // $this->_postList();
        //今日发帖strtotime(date('Y-m-d'))
        $day_count = M('weiba_post')->where('post_time>='.strtotime(date('Y-m-d')).' and is_del=0')->count();
        $this->assign('day_count', $day_count);

        $yesday_count = M('weiba_post')->where('post_time>='.strtotime(date('Y-m-d', strtotime('-1 day'))).' and post_time<'.strtotime(date('Y-m-d')).' and is_del=0')->count();
        $this->assign('yesday_count', $yesday_count);

        $tiezi_count = D('weiba_reply')->where('is_del=0')->count() + D('weiba_post')->where('is_del=0')->count();
        $this->assign('tiezi_count', $tiezi_count);
        //我的微吧
        $sfollow = D('weiba_follow')->where('follower_uid='.$this->mid)->findAll();
        $map['weiba_id'] = array('in', getSubByKey($sfollow, 'weiba_id'));
        $map['is_del'] = 0;
        $map['status'] = 1;
        $var = D('weiba')->where($map)->order('new_day desc, new_count desc ,recommend desc,follower_count desc,thread_count desc')->findAll();
        if ($var) {
            foreach ($var as $k => $v) {
                $var[$k]['logo'] = getImageUrlByAttachId($v['logo'], 50, 50);
                if ($v['new_day'] != date('Y-m-d', time())) {
                    $var[$k]['new_count'] = 0;
                    D('Weiba')->setNewcount($v['weiba_id'], 0);
                }
            }
            $mynum = count($var);
            $array_chunk = array_chunk($var, 2);
        } else {
            $mynum = 0;
            $array_chunk = '';
        }
        $this->assign('mynum', $mynum);
        $this->assign('mid', $this->mid);
        $this->assign('mylist', $var);
        // 微吧是否开启

        $weibaAuditConfig = model('Xdata')->get('weiba_Admin:weibaAuditConfig');
        $this->assign('is_open', $weibaAuditConfig['apply_weiba_open']);

        $this->setTitle('微吧首页');
        $this->setKeywords('微吧首页');
        $this->display();
    }

    /**
     * 帖子列表.
     */
    private function index_post_list($post_type, $p)
    {
        $maps['is_del'] = 0;
        //剔除不符合微吧ID
        $fwid = D('weiba')->where('is_del=1 OR status=0')->order($order)->select();
        $fids = getSubByKey($fwid, 'weiba_id');
        if ($fids) {
            $maps['weiba_id'] = array(
                    'not in',
                    $fids,
            );
        }
        switch ($post_type) {
            case 'reply':
                $order = 'top desc,last_reply_time desc ';
                $list = D('weiba_post')->where($maps)->order($order)->findpage(20);
                $this->assign('type', 'reply');
                $this->assign('post_count', D('weiba_post')->where($maps)->count());
                break;
            case 'hot':
                $order = 'top desc,reply_all_count desc ';
                $list = D('weiba_post')->where($maps)->order($order)->findpage(20);
                $this->assign('type', 'hot');
                $this->assign('post_count', D('weiba_post')->where($maps)->count());
                break;
            case 'digest':
                $order = 'top desc,post_time desc ';
                $maps['digest'] = 1;
                $list = D('weiba_post')->where($maps)->order($order)->findpage(20);
                $this->assign('type', 'digest');
                $this->assign('post_count', D('weiba_post')->where($maps)->count());
                break;
            case 'top':
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
                $list = D('weiba_post')->where($maps)->order($order)->findpage(20);
                $this->assign('type', 'top');
                $this->assign('post_count', D('weiba_post')->where($maps)->count());
                break;
            default:     //new
                $order = 'is_index_time desc';
                $maps['is_index'] = 1;
                $list = D('weiba_post')->where($maps)->order($order)->findpage(5);
                $this->assign('type', 'new');
                $this->assign('post_count', D('weiba_post')->where($maps)->count());
                break;
        }
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

            //dump($userinfo);avatar_small,avatar_tiny
            $list['data'][$k]['image'] = $list['data'][$k]['user']['avatar_middle'];

            //首页帖子图片换成缩略图
            // $index_img_url = getImageUrlByAttachId($list['data'][$k]['index_img'], 700, 310);
            // $index_img_info = getThumbImage($index_img_url,700,310,true,false);
            $list['data'][$k]['index_img'] = getImageUrlByAttachId($list['data'][$k]['index_img'], 700, 260);

            /* 解析emoji */
            $list['data'][$k]['title'] = formatEmoji(false, $list['data'][$k]['title']);
            $list['data'][$k]['content'] = formatEmoji(false, $list['data'][$k]['content']);
        }

        return $list;
    }

    /**
     * 微吧列表.
     */
    public function weibaList()
    {
        $list = M('weiba_category')->order('id')->findpage(20);
        $map['is_del'] = 0;
        $map['status'] = 1;
        foreach ($list['data'] as $k => $v) {
            //获取微吧
            $map['cid'] = $v['id'];
            $list['data'][$k]['list'] = D('weiba')->where($map)->order('new_day desc, new_count desc ,recommend desc,follower_count desc,thread_count desc')->select();
            if ($list['data'][$k]['list']) {
                $weiba_ids = getSubByKey($list['data'][$k]['list'], 'weiba_id');
                $followStatus = D('weiba')->getFollowStateByWeibaids($this->mid, $weiba_ids);
                foreach ($list['data'][$k]['list'] as $i => $v) {
                    $list['data'][$k]['list'][$i]['logo'] = getImageUrlByAttachId($v['logo'], 100, 100);
                    $list['data'][$k]['list'][$i]['following'] = $followStatus[$v['weiba_id']]['following'];
                    if ($v['new_day'] != date('Y-m-d', time())) {
                        $list['data'][$k]['list'][$i]['new_count'] = 0;
                        D('Weiba')->setNewcount($v['weiba_id'], 0);
                    }
                }
            }

            $count = D('weiba')->where($map)->order('new_day desc, new_count desc ,recommend desc,follower_count desc,thread_count desc')->count();
            if ($count > 6) {
                $count = 1;
            } else {
                $count = 0;
            }
            $list['data'][$k]['more'] = $count;
        }
        //dump($list);
        //exit;
        $this->assign('nav', 'weibalist');
        $this->assign('mid', $this->mid);
        $this->assign('list', $list);
        $this->setTitle('微吧列表');
        $this->setKeywords('全站微吧列表');
        $this->display();
    }

    /**
     * 帖子列表.
     */
    public function postList()
    {
        //微吧推荐
        $this->_weiba_recommend(9);
        //帖子列表
        $this->_postList();

        $this->setTitle('全站帖子列表');
        $this->setKeywords('全站帖子列表');
        $this->display();
    }

    /**
     * 我的微吧.
     */
    public function myWeiba()
    {
        $weiba_arr = getSubByKey(D('weiba')->where('is_del=0 and status=1')->field('weiba_id')->findAll(), 'weiba_id');  //未删除且通过审核的微吧
        $map['weiba_id'] = array('in', $weiba_arr);
        $map['is_del'] = 0;
        $type = in_array(t($_GET['type']), array('myPost', 'myReply', 'myWeiba', 'myFavorite', 'myFollowing')) ? t($_GET['type']) : 'myFollowing';
        switch ($type) {
            case 'myPost':
                $map['post_uid'] = $this->mid;
                $post_list = D('weiba_post')->where($map)->order('post_time desc')->findpage(20);
                break;
            case 'myReply':
                $myreply = D('weiba_reply')->where('uid='.$this->mid)->order('ctime desc')->field('post_id')->findAll();
                $map['post_id'] = array('in', array_unique(getSubByKey($myreply, 'post_id')));
                $post_list = D('weiba_post')->where($map)->order('last_reply_time desc')->findpage(20);
                break;
            case 'myFavorite':
                $myFavorite = D('weiba_favorite')->where('uid='.$this->mid)->order('favorite_time desc')->findAll();
                $map['post_id'] = array('in', getSubByKey($myFavorite, 'post_id'));
                $post_list = D('weiba_post')->where($map)->order('post_time desc')->findpage(20);
                break;
            case 'myWeiba':
                $sfollow = D('weiba_follow')->where('follower_uid='.$this->mid)->findAll();
                $sfollow = getSubByKey($sfollow, 'weiba_id');
                $map['weiba_id'] = array('in', $sfollow);
                $map['status'] = 1;
                //dump($map);
                $post_list = D('weiba')->where($map)->order('new_day desc, new_count desc ,recommend desc,follower_count desc,thread_count desc')->findpage(20);
                //dump($post_list);exit;
                break;
            default:
                $myFollow_arr = getSubByKey(D('weiba_follow')->where('follower_uid='.$this->mid)->findAll(), 'weiba_id');
                foreach ($myFollow_arr as $v) {
                    if (in_array($v, $weiba_arr)) {
                        $weibas[] = $v;
                    }
                }
                $map['weiba_id'] = array('in', $weibas);
                $post_list = D('weiba_post')->where($map)->order('last_reply_time desc')->findpage(20);
                break;
        }
        // if($postList['nowPage']==1){  //列表第一页加上全局置顶的帖子
        // 	$topPostList = D('weiba_post')->where('top=2 and is_del=0')->order('post_time desc')->findAll();
        // 	!$topPostList && $topPostList = array();
        // 	!$postList['data'] && $postList['data'] = array();
        // 	$postList['data'] = array_merge($topPostList,$postList['data']);
        // }
        $weiba_ids = getSubByKey($post_list['data'], 'weiba_id');
        $nameArr = $this->_getWeibaName($weiba_ids);
        foreach ($post_list['data'] as $k => $v) {
            $post_list['data'][$k]['weiba'] = $nameArr[$v['weiba_id']];
            $post_list['data'][$k]['user'] = model('User')->getUserInfo($v['post_uid']);
            $post_list['data'][$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
            // $images = matchImages($v['content']);
            // $images[0] && $post_list['data'][$k]['image'] = array_slice( $images , 0 , 5 );
            $image = getEditorImages($v['content']);
            !empty($image) && $post_list['data'][$k]['image'] = array($image);
        }
        $this->assign('post_list', $post_list);
        $this->assign('type', $type);
        $this->assign('nav', 'myweiba');

        $this->setTitle('我的微吧');
        $this->setKeywords('我的微吧');
        $this->display();
    }

    /**
     * 微吧详�
     * 页.
     */
    public function detail()
    {
        $weiba_id = intval($_GET['weiba_id']);
        $weiba_detail = D('weiba')->where('is_del=0 and status=1 and weiba_id='.$weiba_id)->find();
        if (!$weiba_detail) {
            $this->error('该微吧还未被审核或已被解散');
        }
        $weiba_detail['logo'] = getImageUrlByAttachId($weiba_detail['logo'], 200, 200);
        //圈主
        $map['weiba_id'] = $weiba_id;
        $map['level'] = array('in', '2,3');
        $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid,level')->findAll();
        if ($weiba_admin) {
            foreach ($weiba_admin as $k => $v) {
                // 获取用户用户组信息
                $userGids = model('UserGroupLink')->getUserGroup($v['follower_uid']);
                $userGroupData = model('UserGroup')->getUserGroupByGids($userGids[$v['follower_uid']]);
                foreach ($userGroupData as $key => $value) {
                    if ($value['user_group_icon'] == -1) {
                        unset($userGroupData[$key]);
                        continue;
                    }
                    $userGroupData[$key]['user_group_icon_url'] = THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'];
                }
                $weiba_admin[$k]['userGroupData'] = $userGroupData;
                $weiba_admin[$k]['userinfo'] = model('User')->getUserInfo($v['follower_uid']);
            }
            $weiba_admin_uids = getSubByKey($weiba_admin, 'follower_uid');
            $this->_assignFollowUidState($weiba_admin_uids);
            $this->assign('weiba_admin', $weiba_admin);
            $this->assign('weiba_admin_uids', $weiba_admin_uids);
            $this->assign('weiba_super_admin', D('weiba_follow')->where('level=3 and weiba_id='.$weiba_id)->getField('follower_uid'));
            $this->assign('weiba_admin_count', D('weiba_follow')->where($map)->count());
        }
        $isadmin = 0;
        if (in_array($this->mid, $weiba_admin_uids) || CheckPermission('core_admin', 'admin_login')) {
            $isadmin = 1;
            $this->assign('mid', $this->mid);
        }
        $this->assign('isadmin', $isadmin);
        //帖子
        $maps['is_del'] = 0;

        if ($_GET['type'] == 'digest') {
            $maps['digest'] = 1;
            $order = 'post_time desc';
            $this->assign('type', 'digest');
            $this->assign('post_count', D('weiba_post')->where('is_del=0 AND digest=1 AND weiba_id='.$weiba_id)->count());
        } else {
            // $maps['top'] = 0;
            $this->assign('type', 'all');
            $this->assign('post_count', D('weiba_post')->where('is_del=0 AND weiba_id='.$weiba_id)->count());
        }
        $order = 'top desc';
        if ($_GET['order'] == 'post_time') {
            $order .= ',post_time desc';
            $this->assign('order', 'post_time');
        } else {
            $order .= ',last_reply_time desc';
            $this->assign('order', 'reply_time');
        }
        $maps['weiba_id'] = $weiba_id;
        $list = D('weiba_post')->where($maps)->order($order)->findpage(20);
        $post_uids = getSubByKey($list['data'], 'post_uid');
        $reply_uids = getSubByKey($list['data'], 'last_reply_uid');
        !$weiba_admin_uids && $weiba_admin_uids = array();
        $uids = array_unique(array_filter(array_merge($post_uids, $reply_uids, $weiba_admin_uids)));
        $this->_assignUserInfo($uids);

        $this->_assignFollowState($weiba_id);
        foreach ($list['data'] as $k => $v) {
            //匹配图片的src
            preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
            foreach ($match[1] as $imgurl) {
                $imgurl = $imgurl;
                if (!empty($imgurl)) {
                    $list['data'][$k]['img'][] = $imgurl;
                }
            }
            $userinfo = model('User')->getUserInfo($v['post_uid']);
            //dump($userinfo);avatar_small,avatar_tiny
            $list['data'][$k]['image'] = $userinfo['avatar_middle'];
            /* 解析emoji */
            $list['data'][$k]['title'] = formatEmoji(false, $v['title']);
            $list['data'][$k]['content'] = formatEmoji(false, $v['content']);
        }

        $this->assign('list', $list);
        //dump($weiba_detail['cid']);
        if ($weiba_detail['cid'] > 0) {
            $cid = M('weiba_category')->where('id='.$weiba_detail['cid'])->find();
            $weiba_detail['cid'] = $cid['name'];
            //dump($weiba_detail['cid']);
            //exit;
        } else {
            $weiba_detail['cid'] = '';
        }
        unset($map);
        if ((int) $weiba_detail['province'] > 0 && $weiba_detail['province']) {
            $map['area_id'] = (int) $weiba_detail['province'];
            $result = M('area')->where($map)->find();
            //dump(M()->getLastSql());
            //dump('=====');dump($result);exit;
            $weiba_detail['province'] = $result['title'];
        } else {
            $weiba_detail['province'] = null;
        }
        if ($weiba_detail['city'] > 0 && $weiba_detail['city']) {
            $map['area_id'] = (int) $weiba_detail['city'];
            $result = M('area')->where($map)->find();
            $weiba_detail['city'] = $result['title'];
        } else {
            $weiba_detail['city'] = null;
        }
        if ($weiba_detail['area'] > 0 && $weiba_detail['area']) {
            $map['area_id'] = (int) $weiba_detail['area'];
            $result = M('area')->where($map)->find();
            $weiba_detail['area'] = $result['title'];
        } else {
            $weiba_detail['area'] = null;
        }

        //微吧帖子数
        $weiba_detail['tiezi_count'] = D('weiba_reply')->where('weiba_id='.$weiba_id.' AND is_del=0')->count() + D('weiba_post')->where('weiba_id='.$weiba_id.' AND is_del=0')->count();

        $this->assign('weiba_detail', $weiba_detail);

        if ($_GET['type'] == 'digest') {
            $jinghua = '精华帖';
        }
        $this->assign('nav', 'weibadetail');
        $this->assign('weiba_name', $weiba_detail['weiba_name']);
        $this->assign('weiba_id', $weiba_id);
        //微吧达人
        $daren_arr = $this->_weiba_daren($weiba_id);
        $daren_arr_uid = getSubByKey($daren_arr, 'uid');
        $daren_arr_follow = model('Follow')->getFollowStateByFids($this->mid, $daren_arr_uid);
        $this->assign('daren_arr', $daren_arr);
        $this->assign('daren_arr_follow', $daren_arr_follow);

        $daren_arrs = $this->_weiba_darens($weiba_id);
        $daren_arrs_uid = getSubByKey($daren_arrs, 'uid');
        $daren_arrs_follow = model('Follow')->getFollowStateByFids($this->mid, $daren_arrs_uid);
        $this->assign('daren_arrs', $daren_arrs);
        $this->assign('daren_arrs_follow', $daren_arrs_follow);
        //dump($daren_arrs);exit;
        //帖子推荐
        $post_recommend_list = $this->_post_list('recommend', 10);
        $this->assign('post_recommend_list', $post_recommend_list);
        $this->setTitle($weiba_detail['weiba_name'].$jinghua);
        $this->setKeywords($weiba_detail['weiba_name'].$jinghua);
        $this->setDescription($weiba_detail['weiba_name'].','.$weiba_detail['intro']);

        $this->display();
    }

    /**
     * �
     * �注微吧.
     */
    public function doFollowWeiba()
    {
        $res = D('weiba')->doFollowWeiba($this->mid, intval($_REQUEST['weiba_id']));
        //清理插件缓存
        $key = '_getRelatedGroup_'.$this->mid.'_'.date('Ymd'); //达人
        S($key, null);
        $this->ajaxReturn($res, D('weiba')->getError(), false !== $res);
    }

    /**
     * 取消�
     * �注微吧.
     */
    public function unFollowWeiba()
    {
        $res = D('weiba')->unFollowWeiba($this->mid, intval($_GET['weiba_id']));
        $this->ajaxReturn($res, D('weiba')->getError(), false !== $res);
    }

    /**
     * 检查发帖权限.
     *
     * @return bool 是否有发帖权限 0：否  1：是
     */
    public function checkPost()
    {
        $weiba_id = intval($_POST['weiba_id']);
        $map['weiba_id'] = $weiba_id;
        $map['follower_uid'] = $this->mid;
        if (D('weiba_follow')->where($map)->find()) {
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 弹窗加�
     * �微吧.
     */
    public function joinWeiba()
    {
        $weiba_id = intval($_GET['weiba_id']);
        $this->assign('weiba_id', $weiba_id);
        $this->display();
    }

    public function quickPost()
    {
        $sfollow = D('weiba_follow')->where('follower_uid='.$this->mid)->findAll();
        $map['weiba_id'] = array('in', getSubByKey($sfollow, 'weiba_id'));
        $map['is_del'] = 0;
        $map['status'] = 1;
        $list = D('Weiba')->where($map)->field('weiba_id,weiba_name')->findAll();
        $this->assign('list', $list);
        // 获取上传附件配置
        $Config_attach = model('Xdata')->get('admin_Config:attach');
        $attach_update_config['attach_max_size'] = ($Config_attach['attach_max_size'] < ini_get('post_max_size')) ? $Config_attach['attach_max_size'] : ini_get('post_max_size');
        $attach_update_config['attach_allow_extension'] = str_replace(',', '、', $Config_attach['attach_allow_extension']);
        $this->assign('attach_update_config', $attach_update_config);
        unset($Config_attach, $attach_update_config);
        $this->display();
    }

    /**
     * 检查微吧 权限.
     */
    public function checkWeibaStatus()
    {
        $weibaid = intval($_POST['weibaid']);
        $poststatus = D('weiba')->where('weiba_id='.$weibaid)->getField('who_can_post');
        switch ($poststatus) {
            case 1:
                $follow_state = D('weiba')->getFollowStateByWeibaids($this->mid, $weibaid);
                if (!$follow_state[$weibaid]['following'] && !CheckPermission('core_admin', 'admin_login')) {
                    echo 1;
                }
                break;
            case 2:
                //圈主
                $map['weiba_id'] = $weibaid;
                $map['level'] = array('in', '2,3');
                $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid,level')->findAll();

                if (!in_array($this->mid, getSubByKey($weiba_admin, 'follower_uid')) && !CheckPermission('core_admin', 'admin_login')) {
                    echo 2;
                }
                break;
            case 3:
                //圈主
                $map['weiba_id'] = $weibaid;
                $map['level'] = 3;
                $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid,level')->find();
                if ($this->mid != $weiba_admin['follower_uid'] && !CheckPermission('core_admin', 'admin_login')) {
                    echo 3;
                }
                break;
        }
    }

    /**
     * 发布帖子.
     */
    public function post()
    {
        if (!CheckPermission('weiba_normal', 'weiba_post')) {
            $this->error('对不起，您没有权限进行该操作！');
        }
        $weiba_id = intval($_GET['weiba_id']);
        $weiba = D('weiba')->where('weiba_id='.$weiba_id)->find();
        $this->assign('weiba_id', $weiba_id);
        $this->assign('weiba_name', $weiba['weiba_name']);
        // 获取上传附件配置
        $Config_attach = model('Xdata')->get('admin_Config:attach');
        $attach_update_config['attach_max_size'] = ($Config_attach['attach_max_size'] < ini_get('post_max_size')) ? $Config_attach['attach_max_size'] : ini_get('post_max_size');
        $attach_update_config['attach_allow_extension'] = str_replace(',', '、', $Config_attach['attach_allow_extension']);
        $this->assign('attach_update_config', $attach_update_config);
        unset($Config_attach, $attach_update_config);

        $this->setTitle('发表帖子 '.$weiba['weiba_name']);
        $this->setKeywords('发表帖子 '.$weiba['weiba_name']);
        $this->setDescription($weiba['weiba_name'].','.$weiba['intro']);
        $this->display();
    }

    /**
     * 执行发布帖子.
     */
    public function doPost()
    {
        //检测用户是否被禁言
        if ($isDisabled = model('DisableUser')->isDisableUser($this->mid, 'post')) {
            $this->error('您已被禁言！', $type);
        }
        if ($_GET['post_type'] == 'index') {
            $type = false;
        } else {
            $type = true;
        }
        $weibaid = intval($_POST['weiba_id']);
        if (!CheckPermission('weiba_normal', 'weiba_post')) {
            $this->error('对不起，您没有权限进行该操作！', $type);
        }
        $is_lock = M('weiba_blacklist')->where('weiba_id='.$weibaid.' and uid='.$this->mid)->find();
        if ($is_lock) {
            $this->error('您是黑名单用户没有发帖权限', $type);
        }
        $weibaid = intval($_POST['weiba_id']);
        if (!$weibaid) {
            $this->error('请选择微吧，等待返回选择微吧', $type);
        }
        $weiba = D('weiba')->where('weiba_id='.$weibaid)->find();
        //黑名单功能添加
        if (!CheckPermission('core_admin', 'admin_login')) {
            switch ($weiba['who_can_post']) {
                case 1:
                    $map['weiba_id'] = $weibaid;
                    $map['follower_uid'] = $this->mid;
                    $res = D('weiba_follow')->where($map)->find();
                    if (!$res && !CheckPermission('core_admin', 'admin_login')) {
                        $this->error('对不起，您没有发帖权限，请关注该微吧！', $type);
                    }
                    break;
                case 2:
                    $map['weiba_id'] = $weibaid;
                    $map['level'] = array('in', '2,3');
                    $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid')->findAll();
                    if (!in_array($this->mid, getSubByKey($weiba_admin, 'follower_uid')) && !CheckPermission('core_admin', 'admin_login')) {
                        $this->error('对不起，您没有发帖权限，仅限该吧管理员发帖！', $type);
                    }
                    break;
                case 3:
                    $map['weiba_id'] = $weibaid;
                    $map['level'] = 3;
                    $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid')->find();
                    if ($this->mid != $weiba_admin['follower_uid'] && !CheckPermission('core_admin', 'admin_login')) {
                        $this->error('对不起，您没有发帖权限，仅限该吧吧主发帖！', $type);
                    }
                    break;
            }
        }

        $checkContent = str_replace('&nbsp;', '', $_POST['content']);
        $checkContent = str_replace('<br />', '', $checkContent);
        $checkContent = str_replace('<p>', '', $checkContent);
        $checkContent = str_replace('</p>', '', $checkContent);
        $checkContents = preg_replace('/<img(.*?)src=/i', 'img', $checkContent);
        $checkContents = preg_replace('/<embed(.*?)src=/i', 'img', $checkContents);
        $checkContents = RemoveXSS($checkContents);
        if (strlen(t($_POST['title'])) == 0) {
            $this->error('帖子标题不能为空，等待返回添加标题', $type);
        }
        if (strlen(t($checkContents)) == 0) {
            $this->error('帖子内容不能为空，等待返回添加内容', $type);
        }
        preg_match_all('/./us', t($_POST['title']), $match);
        if (count($match[0]) > 100) {     //汉字和字母都为一个字(改为100)
            $this->error('帖子标题不能超过100个字，等待返回修改标题', $type);
        }

        /* # 帖子内容 */
        $content = h($_POST['content']);
        if (get_str_length($content) >= 20000) {
            $this->error('帖子内容过长！无法发布！');
        }
        unset($content);

        if ($_POST['attach_ids']) {
            $attach = explode('|', $_POST['attach_ids']);
            foreach ($attach as $k => $a) {
                if (!$a) {
                    unset($attach[$k]);
                }
            }
            $attach = array_map('intval', $attach);
            $data['attach'] = serialize($attach);
        }
        $data['weiba_id'] = $weibaid;
        $data['title'] = t($_POST['title']);
        $data['content'] = h($_POST['content']);
        $data['post_uid'] = $this->mid;
        $data['post_time'] = time();
        $data['last_reply_uid'] = $this->mid;
        $data['last_reply_time'] = $data['post_time'];
        $data['feed_id'] = 0;

        /* # 格式化emoji */
        $data['title'] = formatEmoji(true, $data['title']);
        $data['content'] = formatEmoji(true, $data['content']);

        $filterTitleStatus = filter_words($data['title']);
        if (!$filterTitleStatus['status']) {
            $this->error($filterTitleStatus['data'], $type);
        }
        $data['title'] = $filterTitleStatus['data'];

        $filterContentStatus = filter_words($data['content']);
        if (!$filterContentStatus['status']) {
            $this->error($filterContentStatus['data'], $type);
        }
        $data['content'] = addslashes($filterContentStatus['data']);
        $res = D('weiba_post')->add($data);
        if ($res) {
            D('Weiba')->setNewcount($weibaid);
            D('weiba')->where('weiba_id='.$data['weiba_id'])->setInc('thread_count');
            //同步到分享
            // $feed_id = D('weibaPost')->syncToFeed($res,$data['title'],t($checkContent),$this->mid);
            $feed_id = model('Feed')->syncToFeed('weiba', $this->mid, $res);
            D('weiba_post')->where('post_id='.$res)->setField('feed_id', $feed_id);
            //$this->assign('jumpUrl', U('weiba/Index/postDetail',array('post_id'=>$res)));
            //$this->success('发布成功');

            $result['id'] = $res;
            $result['feed_id'] = $feed_id;
            //添加积分
            model('Credit')->setUserCredit($this->mid, 'publish_topic');
            //更新发帖数
            D('UserData')->updateKey('weiba_topic_count', 1);
            if ($_GET['post_type'] == 'index') {
                $this->success('发布成功');
            } else {
                return $this->ajaxReturn($result, '发布成功', 1);
            }
        } else {
            $this->error('发布失败，等待返回修改发布', $type);
        }
    }

    /**
     * 帖子详�
     * 页.
     */
    public function postDetail()
    {
        $post_id = intval($_GET['post_id']);
        $post_detail = D('weiba_post')->where('is_del=0 and post_id='.$post_id)->find();
        if (!$post_detail || D('weiba')->where('weiba_id='.$post_detail['weiba_id'])->getField('is_del')) {
            $this->error('帖子不存在或已被删除');
        }
        if (D('weiba_favorite')->where('uid='.$this->mid.' AND post_id='.$post_id)->find()) {
            $post_detail['favorite'] = 1;
        }
        $is_digg = M('weiba_post_digg')->where('post_id='.$post_detail['post_id'].' and uid='.$this->mid)->find();
        $post_detail['digg'] = $is_digg ? 'digg' : 'undigg';
        if ($post_detail['attach']) {
            $attachids = unserialize($post_detail['attach']);
            $attachinfo = model('Attach')->getAttachByIds($attachids);
            foreach ($attachinfo as $ak => $av) {
                $_attach = array(
                        'attach_id'   => $av['attach_id'],
                        'attach_name' => $av['name'],
                        'attach_url'  => getImageUrl($av['save_path'].$av['save_name']),
                        'extension'   => $av['extension'],
                        'size'        => $av['size'],
                );
                $post_detail['attachInfo'][$ak] = $_attach;
            }
        }
        /* # 解析表情 */
        $post_detail['content'] = preg_replace_callback('/\[.+?\]/is', '_parse_expression', $post_detail['content']);
        /* # 解析emoji’ */
        $post_detail['content'] = formatEmoji(false, $post_detail['content']);
        $post_detail['title'] = formatEmoji(false, $post_detail['title']);

        // $post_detail['content'] = html_entity_decode($post_detail['content'], ENT_QUOTES, 'UTF-8');
        $this->assign('post_detail', $post_detail);
        //dump($post_detail);
        D('weiba_post')->where('post_id='.$post_id)->setInc('read_count');
        $weiba_name = D('weiba')->where('weiba_id='.$post_detail['weiba_id'])->getField('weiba_name');
        $this->assign('weiba_id', $post_detail['weiba_id']);
        $this->assign('weiba_name', $weiba_name);
        //获得圈主uid
        $map['weiba_id'] = $post_detail['weiba_id'];
        $map['level'] = array('in', '2,3');
        $weiba_admin = getSubByKey(D('weiba_follow')->where($map)->order('level desc')->field('follower_uid')->findAll(), 'follower_uid');
        $weiba_manage = false;
        if (CheckWeibaPermission($weiba_admin, 0, 'weiba_global_top')
            || CheckWeibaPermission($weiba_admin, 0, 'weiba_top')
            || CheckWeibaPermission($weiba_admin, 0, 'weiba_recommend')
            || CheckWeibaPermission($weiba_admin, 0, 'weiba_edit')
            || CheckWeibaPermission($weiba_admin, 0, 'weiba_del')) {
            $weiba_manage = true;
        }
        $this->assign('weiba_manage', $weiba_manage);
        $this->assign('weiba_admin', $weiba_admin);
        //该作者的其他帖子
        $this->_assignUserInfo($post_detail['post_uid']);

        $tofollow = model('Follow')->getFollowStateByFids($this->mid, array($post_detail['post_uid']));
        $this->assign('tofollow', $tofollow);

        $map1['post_id'] = array('neq', $post_id);
        $map1['post_uid'] = $this->mid;
        $map1['is_del'] = 0;
        $otherPost = D('weiba_post')->where($map1)->order('reply_count desc')->limit(5)->findAll();
        $weiba_ids = getSubByKey($otherPost, 'weiba_id');
        $nameArr = $this->_getWeibaName($weiba_ids);
        foreach ($otherPost as $k => $v) {
            $otherPost[$k]['weiba'] = $nameArr[$v['weiba_id']];
        }
        $this->assign('otherPost', $otherPost);
        // //最新10条
        // $newPost = D('weiba_post')->where('is_del=0')->order('post_time desc')->limit(10)->findAll();
        // $weiba_ids = getSubByKey($newPost, 'weiba_id');
        // $nameArr = $this->_getWeibaName($weiba_ids);
        // foreach($newPost as $k=>$v){
        // 	$newPost[$k]['weiba'] = $nameArr[$v['weiba_id']];
        // }
        // $this->assign('newPost',$newPost);
        //帖子推荐
        $post_recommend_list = $this->_post_list('recommend', 10);
        $this->assign('post_recommend_list', $post_recommend_list);

        $type = isset($_GET['type']) ? t($_GET['type']) : 'time';
        $this->assign('type', $type);

        $this->_weibaOrder();
        $this->assign('nav', 'weibadetail');
        $this->setTitle($post_detail['title'].' '.$weiba_name);
        $this->setKeywords($post_detail['title'].' '.$weiba_name);
        $this->setDescription($post_detail['title'].','.t(getShort($post_detail['content'], 100)));

        $this->assign('page', $_REQUEST['p']);
        $this->display();
    }

    /**
     * 收藏帖子.
     */
    public function favorite()
    {
        //$is_follow = $this->is_follow($_POST['weiba_id']);
        //if($is_follow){
        $data['post_id'] = intval($_POST['post_id']);
        $data['weiba_id'] = intval($_POST['weiba_id']);
        $data['post_uid'] = intval($_POST['post_uid']);
        $data['uid'] = $this->mid;
        $data['favorite_time'] = time();
        if (D('weiba_favorite')->add($data)) {
            D('UserData')->updateKey('collect_topic_count', 1);
            D('UserData')->updateKey('collect_total_count', 1);

            //添加积分
            model('Credit')->setUserCredit($this->mid, 'collect_topic');
            model('Credit')->setUserCredit($data['post_uid'], 'collected_topic');

            echo 1;
        } else {
            echo 0;
        }
        // }else{
        // 	echo 0;
        // }
    }

    public function updatetotal()
    {
        echo D('UserData')->updateUserData();
    }

    //是否加入微吧判断
    public function is_follow($weiba_id)
    {
        $weiba = M('weiba_follow')->where('weiba_id='.$weiba_id.' and follower_uid='.$this->mid)->find();
        if ($weiba) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取消收藏帖子.
     */
    public function unfavorite()
    {
        // $is_follow = $this->is_follow($_POST['weiba_id']);
        // if($is_follow){
        $map['post_id'] = intval($_POST['post_id']);
        $map['uid'] = $this->mid;
        if (D('weiba_favorite')->where($map)->delete()) {
            D('UserData')->updateKey('collect_topic_count', -1);
            D('UserData')->updateKey('collect_total_count', -1);
            echo 1;
        } else {
            echo 0;
        }
        // }else{
        // 	echo 0;
        // }
    }

    /**
     * 编辑帖子.
     */
    public function postEdit()
    {
        $post_id = intval($_GET['post_id']);

        $post_detail = D('weiba_post')->where('post_id='.$post_id)->find();
        $post_detail['title'] = htmlspecialchars($post_detail['title']);
        //获得圈主uid
        $map['weiba_id'] = $post_detail['weiba_id'];
        $map['level'] = array('in', '2,3');
        $weiba_admin = getSubByKey(D('weiba_follow')->where($map)->order('level desc')->field('follower_uid')->findAll(), 'follower_uid');
        //管理权限判断
        if (!CheckWeibaPermission($weiba_admin, 0, 'weiba_edit')) {
            //用户组权限判断
            if (!CheckPermission('weiba_normal', 'weiba_edit')) {
                $this->error('对不起，您没有权限进行该操作！');
            }
        }

        if ($this->mid == $post_detail['post_uid'] || CheckWeibaPermission($weiba_admin, 0, 'weiba_edit')) {
            $post_detail['attach'] = unserialize($post_detail['attach']);
            $this->assign('post_detail', $post_detail);
            if ($_GET['log']) {
                $this->assign('log', intval($_GET['log']));
            }
            $this->assign('weiba_name', D('weiba')->where('weiba_id='.$post_detail['weiba_id'])->getField('weiba_name'));
            // 获取上传附件配置
            $Config_attach = model('Xdata')->get('admin_Config:attach');
            $attach_update_config['attach_max_size'] = ($Config_attach['attach_max_size'] < ini_get('post_max_size')) ? $Config_attach['attach_max_size'] : ini_get('post_max_size');
            $attach_update_config['attach_allow_extension'] = str_replace(',', '、', $Config_attach['attach_allow_extension']);
            $this->assign('attach_update_config', $attach_update_config);
            unset($Config_attach, $attach_update_config);

            $this->setTitle('编辑帖子 '.$weiba['weiba_name']);
            $this->setKeywords('编辑帖子 '.$weiba['weiba_name']);
            $this->setDescription($post_detail['title'].','.t(getShort($post_detail['content'], 100)));
            $this->display();
        } else {
            $this->error('您没有权限！');
        }
    }

    /**
     * 执行编辑帖子.
     */
    public function doPostEdit()
    {
        $weiba = D('weiba_post')->where('post_id='.intval($_POST['post_id']))->field('post_uid')->find();
        if (CheckPermission('weiba_normal', 'weiba_edit')) {   //判断编辑帖子权限
            if ($weiba['post_uid'] != $this->mid) {   //判断是否本人
                if (!CheckWeibaPermission('', $weiba['weiba_id'])) {   //判断管理员或圈主
                    $this->error('对不起，您没有权限进行该操作！', true);
                }
            }
        } else {
            $this->error('对不起，您没有权限进行该操作！', true);
        }
        $is_lock = M('weiba_blacklist')->where('weiba_id='.$weiba['weiba_id'].' and uid='.$this->mid)->find();
        if ($is_lock) {
            $this->error('您是黑名单用户无法编辑帖子', true);
        }
        $checkContent = str_replace('&nbsp;', '', $_POST['content']);
        $checkContent = str_replace('<br />', '', $checkContent);
        $checkContent = str_replace('<p>', '', $checkContent);
        $checkContent = str_replace('</p>', '', $checkContent);
        $checkContents = preg_replace('/<img(.*?)src=/i', 'img', $checkContent);
        $checkContents = preg_replace('/<embed(.*?)src=/i', 'img', $checkContents);
        if (strlen(t($_POST['title'])) == 0) {
            $this->error('帖子标题不能为空', true);
        }
        if (strlen(t($checkContents)) == 0) {
            $this->error('帖子内容不能为空', true);
        }
        preg_match_all('/./us', t($_POST['title']), $match);
        if (count($match[0]) > 100) {     //汉字和字母都为一个字
            $this->error('帖子标题不能超过100个字', true);
        }
        $post_id = intval($_POST['post_id']);
        $data['title'] = t($_POST['title']);
        $data['content'] = h($_POST['content']);
        /* # 格式化emoji */
        $data['title'] = formatEmoji(true, $data['title']);
        $data['content'] = formatEmoji(true, $data['content']);
        $data['attach'] = '';
        if ($_POST['attach_ids']) {
            $attach = explode('|', $_POST['attach_ids']);
            foreach ($attach as $k => $a) {
                if (!$a) {
                    unset($attach[$k]);
                }
            }
            $attach = array_map('intval', $attach);
            $data['attach'] = serialize($attach);
        }
        $res = D('weiba_post')->where('post_id='.$post_id)->save($data);
        if ($res !== false) {
            $post_detail = D('weiba_post')->where('post_id='.$post_id)->find();
            if (intval($_POST['log']) == 1) {
                D('log')->writeLog($post_detail['weiba_id'], $this->mid, '编辑了帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”', 'posts');
            }
            //同步到分享
            $feedInfo = D('feed_data')->where('feed_id='.$post_detail['feed_id'])->find();
            $datas = unserialize($feedInfo['feed_data']);
            $datas['content'] = '【'.$data['title'].'】'.getShort(t($checkContent), 100).'&nbsp;';
            $datas['body'] = $datas['content'];
            $data1['feed_data'] = serialize($datas);
            $data1['feed_content'] = $datas['content'];
            $feed_id = D('feed_data')->where('feed_id='.$post_detail['feed_id'])->save($data1);
            model('Cache')->rm('fd_'.$post_detail['feed_id']);
            //清空转发此帖子分享的缓存
            $repost_list = model('Feed')->where(array('app_row_table' => 'weiba_post', 'app_row_id' => $post_id, 'is_repost' => 1))->field('feed_id')->findAll();
            if ($repost_list) {
                foreach ($repost_list as $value) {
                    model('Cache')->rm('fd_'.$value['feed_id']);
                }
            }

            return $this->ajaxReturn($post_id, '编辑成功', 1);
        } else {
            $this->error('编辑失败', true);
        }
    }

    /**
     * 编辑帖子回复.
     */
    /*
    public function replyEdit(){
        $reply_id = intval($_GET['reply_id']);
        $reply_detail = D('weiba_reply')->where('reply_id='.$reply_id)->find();
        $reply_detail['content'] = parse_html($reply_detail['content']);
        $this->assign('reply_detail',$reply_detail);
        $this->assign('weiba_name',D('weiba')->where('weiba_id='.$reply_detail['weiba_id'])->getField('weiba_name'));
        $this->assign('post_title',D('weiba_post')->where('post_id='.$reply_detail['post_id'])->getField('title'));
        $this->display();
    }
    */
    /**
     * 执行编辑帖子回复.
     */
    /*
    public function doReplyEdit(){
        //dump($_POST);exit;
        if(strlen(t($_POST['content']))==0) $this->error('回复内容不能为空');
        $reply_id = intval($_POST['reply_id']);
        $data['content'] = t($_POST['content']);
        $res = D('weiba_reply')->where('reply_id='.$reply_id)->save($data);
        if($res!==false){
            return $this->ajaxReturn(intval($_POST['post_id']), '编辑成功', 1);
        }else{
            $this->error('编辑失败');
        }
    }
    */

    /**
     * 删除帖子.
     */
    public function postDel()
    {
        $weiba = D('weiba_post')->where('post_id='.intval($_POST['post_id']))->field('weiba_id,post_uid')->find();
        if (CheckPermission('weiba_normal', 'weiba_del') || $weiba['post_uid'] == $this->mid || CheckWeibaPermission('', $weiba['weiba_id'])) {  //判断删帖权限
            if ($weiba['post_uid'] != $this->mid) {  //判断是否本人
                if (!CheckWeibaPermission('', $weiba['weiba_id'])) {  //判断管理员或圈主
                    echo 0;

                    return;
                }
            }
        } else {
            echo 0;

            return;
        }
        if (!CheckWeibaPermission('', $weiba['weiba_id'])) {  //判断管理员或圈主
            if (!CheckPermission('weiba_normal', 'weiba_del') || $weiba['post_uid'] != $this->mid) {
                echo 0;

                return;
            }
        }
        $post_id = $_POST['post_id'];
        $post_id = intval($post_id);
        if (D('weiba_post')->where('post_id='.$post_id)->setField('is_del', 1)) {
            $post_detail = D('weiba_post')->where('post_id='.$post_id)->find();
            if (intval($_POST['log']) == 1) {
                D('log')->writeLog($post_detail['weiba_id'], $this->mid, '删除了帖子“'.$post_detail['title'].'”', 'posts');
            }
            D('weiba')->where('weiba_id='.intval($_POST['weiba_id']))->setDec('thread_count');

            //添加积分
            model('Credit')->setUserCredit($this->mid, 'delete_topic');

            // 删除相应的分享信息
            model('Feed')->doEditFeed($post_detail['feed_id'], 'delFeed', '', $this->mid);

            /* 删除收藏 */
            D('WeibaPost')->where(array('post_id' => $post_id))->delete();

            echo 1;
        }
    }

    /**
     * 设置帖子类型(置顶或精华).
     */
    public function postSet()
    {
        $post_id = intval($_POST['post_id']);
        $type = intval($_POST['type']);
        if ($type == 1) {
            $field = 'top';
        }
        if ($type == 2) {
            $field = 'digest';
        }
        if ($type == 3) {
            $field = 'recommend';
        }
        $currentValue = intval($_POST['currentValue']);
        $targetValue = intval($_POST['targetValue']);
        if ($targetValue == '1' && $type == 1) {
            $action = 'weiba_top';
        } elseif ($targetValue == '2' && $type == 1) {
            $action = 'weiba_global_top';
        } elseif ($type == 2) {
            $action = 'weiba_marrow';
        } elseif ($type == 3) {
            $action = 'weiba_recommend';
        }
        $weiba_id = D('weiba_post')->where('post_id='.$post_id)->getField('weiba_id');
        if ($targetValue == '0' && $type == 1) {
            if (!CheckWeibaPermission('', $weiba_id, 'weiba_top') && !CheckWeibaPermission('', $weiba_id, 'weiba_global_top')) {
                $this->error('对不起，您没有权限进行该操作！');
            }
        } else {
            if (!CheckWeibaPermission('', $weiba_id, $action)) {
                $this->error('对不起，您没有权限进行该操作！');
            }
        }

        if (D('weiba_post')->where('post_id='.$post_id)->setField($field, $targetValue)) {
            $post_detail = D('weiba_post')->where('post_id='.$post_id)->find();
            $config['post_name'] = $post_detail['title'];
            $config['post_url'] = '<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>';
            if ($type == 1) {
                switch ($targetValue) {
                    case '0':      //取消置顶
                        if ($currentValue == 1) {
                            D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”取消了吧内置顶', 'posts');
                        } else {
                            D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”取消了全局置顶', 'posts');
                        }

                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'untop_topic_all');

                        break;
                    case '1':     //设为吧内置顶
                            $config['typename'] = '吧内置顶';
                            model('Notify')->sendNotify($post_detail['post_uid'], 'weiba_post_set', $config);
                            D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”设为了吧内置顶', 'posts');

                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'top_topic_weiba');

                        break;
                    case '2':     //设为全局置顶
                            $config['typename'] = '全局置顶';
                            model('Notify')->sendNotify($post_detail['post_uid'], 'weiba_post_set', $config);
                            D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”设为了全局置顶', 'posts');

                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'top_topic_all');

                        break;
                }
            }
            if ($type == 2) {
                switch ($targetValue) {
                    case '0':     //取消精华
                        D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”取消了精华', 'posts');
                        break;
                    case '1':     //设为精华
                            $config['typename'] = '精华';
                            model('Notify')->sendNotify($post_detail['post_uid'], 'weiba_post_set', $config);
                            D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”设为了精华', 'posts');

                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'dist_topic');
                        break;
                }
            }
            if ($type == 3) {
                switch ($targetValue) {
                    case '0':     //取消推荐
                        D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”取消了推荐', 'posts');
                        break;
                    case '1':
                        //设为推荐
                        $config['typename'] = '推荐';
                        model('Notify')->sendNotify($post_detail['post_uid'], 'weiba_post_set', $config);
                        D('log')->writeLog($post_detail['weiba_id'], $this->mid, '将帖子“<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>”设为了推荐', 'posts');

                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'recommend_topic');

                        break;
                }
            }
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 搜索微吧或帖子.
     */
    public function search()
    {
        $k = t($_REQUEST['k']);
        $this->setTitle('搜索'.$k);
        $this->setKeywords('搜索'.$k);
        $this->setDescription('搜索'.$k);

        // //微吧推荐
        // $this->_weiba_recommend(9,50,50);
        // //微吧排行榜
        // $this->_weibaOrder();

        //微吧达人
        $daren_arr = $this->_weiba_daren();
        $this->assign('daren_arr', $daren_arr);
        //帖子推荐
        $post_recommend_list = $this->_post_list('recommend', 10);
        $this->assign('post_recommend_list', $post_recommend_list);

        $this->assign('nav', 'search');
        if ($k == '') {
            if ($_REQUEST['type'] == '1') {
                $this->display('search_weiba');
            } else {
                $this->display('search_post');
            }
            exit;
        }
        $_POST['k'] && $_SERVER['QUERY_STRING'] = $_SERVER['QUERY_STRING'].'&k='.$k;
        $this->assign('searchkey', $k);
        $map['is_del'] = 0;
        $map['status'] = 1;
        if ($_REQUEST['type'] == '1') {
            //搜微吧
            $map['weiba_name'] = array('like', '%'.$k.'%');
            //$where['intro'] = array('like','%'.$k.'%');
            //$where['_logic'] = 'or';
            //$map['_complex'] = $where;
            $weibaList = D('weiba')->where($map)->findPage(10);
            if ($weibaList['data']) {
                foreach ($weibaList['data'] as $k => $v) {
                    $weibaList['data'][$k]['logo'] = getImageUrlByAttachId($v['logo'], 100, 100);
                }
                $weiba_ids = getSubByKey($weibaList['data'], 'weiba_id');
                $this->_assignFollowState($weiba_ids);
                $this->assign('weibaList', $weibaList);
            } else {
                //微吧推荐
                $this->_weiba_recommend(9, 50, 50);
            }
            $this->display('search_weiba');
        } else {
            //搜帖子
            $map['weiba_id'] = array('in', getSubByKey(D('weiba')->where('is_del=0')->field('weiba_id')->findAll(), 'weiba_id'));
            $map['title'] = array('like', '%'.$k.'%');
            //$where['content'] = array('like','%'.$k.'%');
            //$where['_logic'] = 'or';
            //$map['_complex'] = $where;
            $post_list = D('weiba_post')->where($map)->order('post_time desc')->findPage(20);
            if ($post_list['data']) {
                $weiba_ids = getSubByKey($post_list['data'], 'weiba_id');
                $nameArr = $this->_getWeibaName($weiba_ids);
                foreach ($post_list['data'] as $k => $v) {
                    $post_list['data'][$k]['weiba'] = $nameArr[$v['weiba_id']];
                    $post_list['data'][$k]['user'] = model('User')->getUserInfo($v['post_uid']);
                    $post_list['data'][$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
                    $images = matchImages($v['content']);
                    $images[0] && $post_list['data'][$k]['image'] = array_slice($images, 0, 5);
                    /* 解析emoji */
                    $post_list['data'][$k]['title'] = formatEmoji(false, $v['title']);
                    $post_list['data'][$k]['content'] = formatEmoji(false, $v['content']);
                }
                $this->assign('post_list', $post_list);
            } else {
                //微吧推荐
                $this->_weiba_recommend(9, 50, 50);
            }
            $this->display('search_post');
        }
    }

    /**
     * 检查是否有申请资格
     */
    public function can_apply_weiba_admin()
    {
        if (!CheckPermission('weiba_normal', 'weiba_apply_manage')) {
            echo -3;
            exit;
        }
        $weiba_id = intval($_POST['weiba_id']);

        if (intval($_POST['type']) == 3) {
            if (D('weiba_follow')->where('weiba_id='.$weiba_id.' AND level=3')->find()) {   //已经有了圈主
                echo 2;
                exit;
            }
        }
        if (D('weiba_apply')->where('weiba_id='.$weiba_id.' AND follower_uid='.$this->mid)->find()) {
            echo -1;
            exit;
        }
        if (D('weiba_follow')->where('weiba_id='.$weiba_id.' AND follower_uid='.$this->mid.' AND (level=3 OR level=2)')->find()) {
            echo -2;
            exit;
        }
        model('User')->cleanCache($this->mid);
        //关注该微吧
        if (!D('weiba_follow')->where('weiba_id='.$weiba_id.' AND follower_uid='.$this->mid)->find()) {
            echo 0;
            exit;
        }
        $weibaAdminAuditConfig = model('Xdata')->get('weiba_Admin:weibaAdminAuditConfig');
        //粉丝数
        if ($weibaAdminAuditConfig['follower_open'] == 1) {
            $user_data = model('UserData')->getUserData($this->mid);
            if ($user_data['follower_count'] < $weibaAdminAuditConfig['follower']) {
                echo 0;
                exit;
            }
        }
        //等级
        if ($weibaAdminAuditConfig['level_open'] == 1) {
            $user_level = model('Credit')->getUserCredit($this->mid);
            if ($user_level['level']['level'] < $weibaAdminAuditConfig['level']) {
                echo 0;
                exit;
            }
        }
        //发帖数
        if ($weibaAdminAuditConfig['weiba_post_open'] == 1) {
            $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and weiba_id='.$weiba_id.' and is_del=0')->count();
            if ($user_weiba_post < $weibaAdminAuditConfig['weiba_post']) {
                echo 0;
                exit;
            }
        }
        echo 1;
    }

    public function apply_weiba_admin_box()
    {
        //关注该微吧
        if (D('weiba_follow')->where('weiba_id='.intval($_GET['weiba_id']).' AND follower_uid='.$this->mid)->find()) {
            $follow['is_complete'] = '已完成';
        } else {
            $follow['is_complete'] = '未完成';
        }
        $this->assign('follow', $follow);
        $weibaAdminAuditConfig = model('Xdata')->get('weiba_Admin:weibaAdminAuditConfig');
        //粉丝数
        $user_data = model('UserData')->getUserData($this->mid);
        if ($user_data['follower_count'] < $weibaAdminAuditConfig['follower']) {
            $follower['is_complete'] = '未完成';
            $follower['follower_count'] = $user_data['follower_count'];
        } else {
            $follower['is_complete'] = '已完成';
        }
        $this->assign('follower', $follower);
        //等级
        $user_level = model('Credit')->getUserCredit($this->mid);
        if ($user_level['level']['level'] < $weibaAdminAuditConfig['level']) {
            $level['is_complete'] = '未完成';
            $level['user_level'] = $user_level['level']['level'];
        } else {
            $level['is_complete'] = '已完成';
        }
        $this->assign('level', $level);
        //发帖数
        $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and is_del=0 and weiba_id='.intval($_GET['weiba_id']))->count();
        if ($user_weiba_post < $weibaAdminAuditConfig['weiba_post']) {
            $weiba_post['is_complete'] = '未完成';
            $weiba_post['user_weiba_post'] = $user_weiba_post;
        } else {
            $weiba_post['is_complete'] = '已完成';
        }
        $this->assign('weiba_post', $weiba_post);
        $this->assign('weibaAdminAuditConfig', $weibaAdminAuditConfig);
        $this->display();
    }

    /**
     * 申请成为圈主或小主.
     */
    public function apply_weiba_admin()
    {
        if (!CheckPermission('weiba_normal', 'weiba_apply_manage')) {
            $this->error('对不起，您没有权限执行该操作！');
        }
        $weiba_id = intval($_GET['weiba_id']);
        $type = intval($_GET['type']);
        if (!D('weiba_follow')->where('weiba_id='.$weiba_id.' AND follower_uid='.$this->mid)->find()) {
            $this->error('您尚未关注该微吧');
        }
        if ($type != 2 && $type != 3) {
            $this->error('参数错误');
        }
        if ($type == 3) {
            if (D('weiba_follow')->where('weiba_id='.$weiba_id.' AND level=3')->find()) {   //已经有了圈主
                $this->error('该吧已经设置了圈主');
            }
        }
        model('User')->cleanCache($this->mid);
        $weibaAdminAuditConfig = model('Xdata')->get('weiba_Admin:weibaAdminAuditConfig');
        //粉丝数
        if ($weibaAdminAuditConfig['follower_open'] == 1) {
            $user_data = model('UserData')->getUserData($this->mid);
            if ($user_data['follower_count'] < $weibaAdminAuditConfig['follower']) {
                $this->error('您的粉丝数没达到'.$weibaAdminAuditConfig['follower'].',不能申请圈主');
            }
        }
        //等级
        if ($weibaAdminAuditConfig['level_open'] == 1) {
            $user_level = model('Credit')->getUserCredit($this->mid);
            if ($user_level['level']['level'] < $weibaAdminAuditConfig['level']) {
                $this->error('您的等级没达到'.$weibaAdminAuditConfig['level'].'级,不能申请微吧');
            }
        }
        //发帖数
        if ($weibaAdminAuditConfig['weiba_post_open'] == 1) {
            $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and weiba_id='.$weiba_id.' and is_del=0')->count();
            if ($user_weiba_post < $weibaAdminAuditConfig['weiba_post']) {
                $this->error('您的发帖数没达到'.$weibaAdminAuditConfig['weiba_post'].',不能申请圈主');
            }
        }
        $this->assign('weiba_name', D('weiba')->where('weiba_id='.$weiba_id)->getField('weiba_name'));
        $this->assign('type', $type);
        $this->assign('weiba_id', $weiba_id);
        $this->display();
    }

    /**
     * 执行申请成为圈主或小主.
     */
    public function do_apply_weiba_admin()
    {
        if (!CheckPermission('weiba_normal', 'weiba_apply_manage')) {
            $this->error('对不起，您没有权限执行该操作！');
        }
        $weiba_id = intval($_POST['weiba_id']);
        $type = intval($_POST['type']);
        if (!D('weiba_follow')->where('weiba_id='.$weiba_id.' AND follower_uid='.$this->mid)->find()) {
            $this->error('您尚未关注该微吧');
        }
        if ($type != 2 && $type != 3) {
            $this->error('参数错误');
        }
        if ($type == 3) {
            if (D('weiba_follow')->where('weiba_id='.$weiba_id.' AND level=3')->find()) {   //已经有了圈主
                $this->error('该吧已经设置了圈主');
            }
        }
        model('User')->cleanCache($this->mid);
        $weibaAdminAuditConfig = model('Xdata')->get('weiba_Admin:weibaAdminAuditConfig');
        //粉丝数
        if ($weibaAdminAuditConfig['follower_open'] == 1) {
            $user_data = model('UserData')->getUserData($this->mid);
            if ($user_data['follower_count'] < $weibaAdminAuditConfig['follower']) {
                $this->error('您的粉丝数没达到'.$weibaAdminAuditConfig['follower'].',不能申请圈主');
            }
        }
        //等级
        if ($weibaAdminAuditConfig['level_open'] == 1) {
            $user_level = model('Credit')->getUserCredit($this->mid);
            if ($user_level['level']['level'] < $weibaAdminAuditConfig['level']) {
                $this->error('您的等级没达到'.$weibaAdminAuditConfig['level'].'级,不能申请微吧');
            }
        }
        //发帖数
        if ($weibaAdminAuditConfig['weiba_post_open'] == 1) {
            $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and weiba_id='.$weiba_id.' and is_del=0')->count();
            if ($user_weiba_post < $weibaAdminAuditConfig['weiba_post']) {
                $this->error('您的发帖数没达到'.$weibaAdminAuditConfig['weiba_post'].',不能申请圈主');
            }
        }
        if (strlen(t($_POST['reason'])) == 0) {
            $this->error('申请理由不能为空');
        }
        preg_match_all('/./us', t($_POST['reason']), $match);
        if (count($match[0]) > 140) {     //汉字和字母都为一个字
            $this->error('申请理由不能超过140个字');
        }
        if (D('weiba_follow')->where('weiba_id='.intval($_POST['weiba_id']).' AND follower_uid='.$this->mid.' AND (level=3 OR level=2)')->find()) {
            $this->error('您已经是圈主，不能重复申请');
        }
        $data['follower_uid'] = $this->mid;
        $data['weiba_id'] = intval($_POST['weiba_id']);
        $data['type'] = intval($_POST['type']);
        $data['status'] = 0;
        $data['reason'] = t($_POST['reason']);
        $res = D('weiba_apply')->add($data);
        if ($res) {
            $weiba = D('weiba')->where('weiba_id='.$data['weiba_id'])->find();
            $actor = model('User')->getUserInfo($this->mid);
            $config['name'] = $actor['space_link'];
            $config['weiba_name'] = $weiba['weiba_name'];
            $config['source_url'] = U('weiba/Manage/member', array('weiba_id' => $data['weiba_id'], 'type' => 'apply'));
            if ($data['type'] == 3) {
                model('Notify')->sendNotify($weiba['uid'], 'weiba_apply', $config);
            } else {
                model('Notify')->sendNotify($weiba['admin_uid'], 'weiba_apply', $config);
            }

            return $this->ajaxReturn($data['weiba_id'], '申请成功，请等待管理员审核', 1);
        } else {
            $this->error('申请失败');
        }
    }

    /**
     * 判断是否达到申请微吧的条件.
     *
     * @return bool
     */
    public function can_apply_weiba()
    {
        $weibaAuditConfig = model('Xdata')->get('weiba_Admin:weibaAuditConfig');
        if ($weibaAuditConfig['apply_weiba_open'] == 1) {
            model('User')->cleanCache($this->mid);
            //粉丝数
            if ($weibaAuditConfig['follower_open'] == 1) {
                $user_data = model('UserData')->getUserData($this->mid);
                if ($user_data['follower_count'] < $weibaAuditConfig['follower']) {
                    echo -1;
                    exit;
                }
            }
            //等级
            if ($weibaAuditConfig['level_open'] == 1) {
                $user_level = model('Credit')->getUserCredit($this->mid);
                if ($user_level['level']['level'] < $weibaAuditConfig['level']) {
                    echo -2;
                    exit;
                }
            }
            //发帖数
            if ($weibaAuditConfig['weiba_post_open'] == 1) {
                $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and is_del=0')->count();
                if ($user_weiba_post < $weibaAuditConfig['weiba_post']) {
                    echo -3;
                    exit;
                }
            }
            //圈主或小主
            if ($weibaAuditConfig['manager_open'] == 1) {
                $is_manager = D('weiba_follow')->where(array('follower_uid' => $this->mid, 'level' => array('in', '2,3')))->count();
                if (!$is_manager) {
                    echo -4;
                    exit;
                }
            }
        }
        echo 1;
    }

    public function apply_weiba_box()
    {
        $weibaAuditConfig = model('Xdata')->get('weiba_Admin:weibaAuditConfig');
        if ($weibaAuditConfig['apply_weiba_open'] == 1) {
            //粉丝数
            $user_data = model('UserData')->getUserData($this->mid);
            if ($user_data['follower_count'] < $weibaAuditConfig['follower']) {
                $follower['is_complete'] = '未完成';
                $follower['follower_count'] = $user_data['follower_count'];
            } else {
                $follower['is_complete'] = '已完成';
            }
            $this->assign('follower', $follower);
            //等级
            $user_level = model('Credit')->getUserCredit($this->mid);
            if ($user_level['level']['level'] < $weibaAuditConfig['level']) {
                $level['is_complete'] = '未完成';
                $level['user_level'] = $user_level['level']['level'];
            } else {
                $level['is_complete'] = '已完成';
            }
            $this->assign('level', $level);
            //发帖数
            $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and is_del=0')->count();
            if ($user_weiba_post < $weibaAuditConfig['weiba_post']) {
                $weiba_post['is_complete'] = '未完成';
                $weiba_post['user_weiba_post'] = $user_weiba_post;
            } else {
                $weiba_post['is_complete'] = '已完成';
            }
            $this->assign('weiba_post', $weiba_post);
            //圈主或小主
            $is_manager = D('weiba_follow')->where(array('follower_uid' => $this->mid, 'level' => array('in', '2,3')))->count();
            if ($is_manager) {
                $manage['is_complete'] = '已完成';
            } else {
                $manage['is_complete'] = '未完成';
            }
            $this->assign('manage', $manage);
            $this->assign('weibaAuditConfig', $weibaAuditConfig);
            $this->display();
        } else {
            $this->error('申请微吧功能未开启');
        }
    }

    public function apply_weiba()
    {
        $weibaAuditConfig = model('Xdata')->get('weiba_Admin:weibaAuditConfig');
        if ($weibaAuditConfig['apply_weiba_open'] == 1) {
            model('User')->cleanCache($this->mid);
            //粉丝数
            if ($weibaAuditConfig['follower_open'] == 1) {
                $user_data = model('UserData')->getUserData($this->mid);
                if ($user_data['follower_count'] < $weibaAuditConfig['follower']) {
                    $this->error('您的粉丝数没达到'.$weibaAuditConfig['follower'].',不能申请圈主');
                }
            }
            //等级
            if ($weibaAuditConfig['level_open'] == 1) {
                $user_level = model('Credit')->getUserCredit($this->mid);
                if ($user_level['level']['level'] < $weibaAuditConfig['level']) {
                    $this->error('您的等级没达到'.$weibaAuditConfig['level'].'级,不能申请微吧');
                }
            }
            //发帖数
            if ($weibaAuditConfig['weiba_post_open'] == 1) {
                $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and is_del=0')->count();
                if ($user_weiba_post < $weibaAuditConfig['weiba_post']) {
                    $this->error('您的发帖数没达到'.$weibaAuditConfig['weiba_post'].',不能申请圈主');
                }
            }
            //圈主或小主
            if ($weibaAuditConfig['manager_open'] == 1) {
                $is_manager = D('weiba_follow')->where(array('follower_uid' => $this->mid, 'level' => array('in', '2,3')))->count();
                if (!$is_manager) {
                    $this->error('您还不是圈主或小主,不能申请微吧');
                }
            }
            $this->assign('weiba_cates', D('WeibaCategory')->getAllWeibaCate());
            $this->display();
        } else {
            $this->error('申请微吧功能未开启');
        }
    }

    public function do_apply_weiba()
    {
        $weibaAuditConfig = model('Xdata')->get('weiba_Admin:weibaAuditConfig');
        if ($weibaAuditConfig['apply_weiba_open'] == 1) {
            model('User')->cleanCache($this->mid);
            //粉丝数
            if ($weibaAuditConfig['follower_open'] == 1) {
                $user_data = model('UserData')->getUserData($this->mid);
                if ($user_data['follower_count'] < $weibaAuditConfig['follower']) {
                    echo '您的粉丝数没达到'.$weibaAuditConfig['follower'].',不能申请圈主';
                    exit;
                }
            }
            //等级
            if ($weibaAuditConfig['level_open'] == 1) {
                $user_level = model('Credit')->getUserCredit($this->mid);
                if ($user_level['level']['level'] < $weibaAuditConfig['level']) {
                    echo '您的等级没达到'.$weibaAuditConfig['level'].'级,不能申请微吧';
                    exit;
                }
            }
            //发帖数
            if ($weibaAuditConfig['weiba_post_open'] == 1) {
                $user_weiba_post = D('weiba_post')->where('post_uid='.$this->mid.' and is_del=0')->count();
                if ($user_weiba_post < $weibaAuditConfig['weiba_post']) {
                    echo '您的发帖数没达到'.$weibaAuditConfig['weiba_post'].',不能申请圈主';
                    exit;
                }
            }
            //圈主或小主
            if ($weibaAuditConfig['manager_open'] == 1) {
                $is_manager = D('weiba_follow')->where(array('follower_uid' => $this->mid, 'level' => array('in', '2,3')))->count();
                if (!$is_manager) {
                    echo '您还不是圈主或小主,不能申请微吧';
                    exit;
                }
            }
        } else {
            echo '-1';
            exit;
        }
        $data['weiba_name'] = t($_POST['weiba_name']);
        $data['cid'] = intval($_POST['cid']);
        $data['intro'] = t($_POST['intro']);
        $data['who_can_post'] = t($_POST['who_can_post']);
        // $data['info'] = t($_POST['info']);
        $data['avatar_big'] = t($_POST['avatar_big']);
        $data['avatar_middle'] = t($_POST['avatar_middle']);

        $data['uid'] = $this->mid;
        $data['ctime'] = time();
        $data['admin_uid'] = $this->mid;
        $data['follower_count'] = 1;
        $data['status'] = 0;
        $res = D('Weiba', 'weiba')->add($data);
        if ($res) {
            $follow['follower_uid'] = $this->mid;
            $follow['weiba_id'] = $res;
            $follow['level'] = 3;
            D('weiba_follow')->add($follow);
            echo '1';
        } else {
            echo '0';
        }
    }

    /**
     * 微吧推荐.
     *
     * @param int limit 获取微吧条数
     */
    private function _weiba_recommend($limit = 9, $width = 100, $height = 100)
    {
        $weiba_recommend = D('weiba')->where('recommend=1 and status=1 and is_del=0')->limit($limit)->findAll();
        foreach ($weiba_recommend as $k => $v) {
            $weiba_recommend[$k]['logo'] = getImageUrlByAttachId($v['logo'], $width, $height);
        }
        $weiba_ids = getSubByKey($weiba_recommend, 'weiba_id');
        $this->_assignFollowState($weiba_ids);
        $this->assign('weiba_recommend', $weiba_recommend);
    }

    /**
     * 热帖推荐.
     *
     * @param int limit 获取微吧条数
     */
    private function _post_recommend($limit)
    {
        $db_prefix = C('DB_PREFIX');
        $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( a.`recommend` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.recommend_time desc LIMIT ".$limit;
        $post_recommend = D('weiba_post')->query($sql);
        $weiba_ids = getSubByKey($post_recommend, 'weiba_id');
        $nameArr = $this->_getWeibaName($weiba_ids);
        foreach ($post_recommend as $k => $v) {
            $post_recommend[$k]['weiba'] = $nameArr[$v['weiba_id']];
            $post_recommend[$k]['user'] = model('User')->getUserInfo($v['post_uid']);
            $post_recommend[$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
            $images = matchImages($v['content']);
            $images[0] && $post_recommend[$k]['image'] = array_slice($images, 0, 5);
            /* 解析emoji */
            $post_recommend[$k]['title'] = formatEmoji(false, $v['title']);
            $post_recommend[$k]['content'] = formatEmoji(false, $v['content']);
        }
        // dump($post_recommend);exit;
        $this->assign('post_recommend', $post_recommend);
    }

    /**
     * 微吧排行榜.
     */
    private function _weibaOrder()
    {
        $weiba_order = D('weiba')->where('is_del=0 and status=1')->order('follower_count desc,thread_count desc')->limit(10)->findAll();
        foreach ($weiba_order as $k => $v) {
            $weiba_order[$k]['logo'] = getImageUrlByAttachId($v['logo'], 30, 30);
        }
        $map['post_uid'] = $this->mid;
        $postCount = D('weiba_post')->where($map)->count();
        $reply = D('weiba_reply')->where('uid='.$this->mid)->group('post_id')->findAll();
        $replyCount = count($reply);
        $favoriteCount = D('weiba_favorite')->where('uid='.$this->mid)->count();
        $followCount = D('weiba_follow')->where('follower_uid='.$this->mid)->count();

        $data['postCount'] = $postCount ? $postCount : 0;
        $data['replyCount'] = $replyCount ? $replyCount : 0;
        $data['favoriteCount'] = $favoriteCount ? $favoriteCount : 0;
        $data['followCount'] = $followCount ? $followCount : 0;
        $this->assign($data);
        //dump($weiba_order);exit;
        $this->assign('weiba_order', $weiba_order);
    }

    /**
     * 获取uid与微吧的�
     * �注状态
     */
    private function _assignFollowState($weiba_ids)
    {
        // 批量获取uid与微吧的关注状态
        $follow_state = D('weiba')->getFollowStateByWeibaids($this->mid, $weiba_ids);
        $this->assign('follow_state', $follow_state);
    }

    /**
     * 批量获取用户的相�
     * �信息加载.
     *
     * @param string|array $uids 用户ID
     */
    private function _assignUserInfo($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        $user_info = model('User')->getUserInfoByUids($uids);
        $this->assign('user_info', $user_info);
        //dump($user_info);exit;
    }

    /**
     * 批量获取用户uid与一群人fids的彼此�
     * �注状态
     *
     * @param array $fids 用户uid数组
     */
    private function _assignFollowUidState($fids = null)
    {
        // 批量获取与当前登录用户之间的关注状态
        $follow_state = model('Follow')->getFollowStateByFids($this->mid, $fids);
        $this->assign('follow_user_state', $follow_state);
        //dump($follow_state);exit;
    }

    /**
     * 帖子列表.
     */
    private function _postList()
    {
        $map['weiba_id'] = array('in', getSubByKey(D('weiba')->where('is_del=0 and status=1')->field('weiba_id')->findAll(), 'weiba_id'));
        $map['top'] = array('neq', 2);
        $map['is_del'] = 0;
        $postList = D('weiba_post')->where($map)->order('post_time desc')->findpage(20);
        if ($postList['nowPage'] == 1) {  //列表第一页加上全局置顶的帖子
            $map['top'] = 2;
            $topPostList = D('weiba_post')->where($map)->order('post_time desc')->findAll();
            !$topPostList && $topPostList = array();
            !$postList['data'] && $postList['data'] = array();
            $postList['data'] = array_merge($topPostList, $postList['data']);
        }

        $weiba_ids = getSubByKey($postList['data'], 'weiba_id');
        $nameArr = $this->_getWeibaName($weiba_ids);
        foreach ($postList['data'] as $k => $v) {
            $postList['data'][$k]['weiba'] = $nameArr[$v['weiba_id']];
            /* # 解析emoji */
            $postList['data'][$k]['title'] = formatEmoji(false, $v['title']);
            $postList['data'][$k]['content'] = formatEmoji(false, $v['content']);
        }
        //dump($postList);exit;
        $post_uids = getSubByKey($postList['data'], 'post_uid');
        $reply_uids = getSubByKey($postList['data'], 'last_reply_uid');
        $uids = array_unique(array_merge($post_uids, $reply_uids));
        $this->_assignUserInfo($uids);
        //微吧排行榜
        $this->_weibaOrder();
        $this->assign('postList', $postList);
    }

    private function _getWeibaName($weiba_ids)
    {
        $weiba_ids = array_unique($weiba_ids);
        if (empty($weiba_ids)) {
            return false;
        }
        $map['weiba_id'] = array('in', $weiba_ids);
        $names = D('weiba')->where($map)->field('weiba_id,weiba_name')->findAll();
        foreach ($names as $n) {
            $nameArr[$n['weiba_id']] = $n['weiba_name'];
        }

        return $nameArr;
    }

    /**
     * 帖子列表.
     */
    private function _post_list($post_type, $limit)
    {
        $db_prefix = C('DB_PREFIX');
        switch ($post_type) {
            case 'reply':
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.last_reply_time desc LIMIT ".$limit;
                break;
            case 'hot':
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`is_del` = 0 ) ORDER BY a.reply_all_count desc LIMIT ".$limit;
                break;
            case 'digest':
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`digest` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.post_time desc LIMIT ".$limit;
                break;
            case 'recommend':
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`recommend` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.recommend_time desc LIMIT ".$limit;
                break;
            case 'top':
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`top` = 2 ) AND ( a.`is_del` = 0 ) ORDER BY a.last_reply_time desc LIMIT ".$limit;
                break;
            case 'nrecommend':
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`top` = 2 ) AND ( a.`is_del` = 0 ) ORDER BY a.top_time desc LIMIT ".$limit;
                break;
            case 'topandrecomment':
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`recommend` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.top desc,a.last_reply_time desc";
                break;
            default:     //new
                $sql = "SELECT a.* FROM `{$db_prefix}weiba_post` a, `{$db_prefix}weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`is_del` = 0 ) ORDER BY a.post_time desc LIMIT ".$limit;
                break;
        }
        $post_list = D('weiba_post')->query($sql);
        $weiba_ids = getSubByKey($post_list, 'weiba_id');
        $nameArr = $this->_getWeibaName($weiba_ids);
        foreach ($post_list as $k => $v) {
            $post_list[$k]['weiba'] = $nameArr[$v['weiba_id']];
            $post_list[$k]['user'] = model('User')->getUserInfo($v['post_uid']);
            $post_list[$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
            // $images = matchImages($v['content']);
            // $images[0] && $post_list[$k]['image'] = array_slice( $images , 0 , 5 );
            $image = getEditorImages($v['content']);
            !empty($image) && $post_list[$k]['image'] = array($image);
            //匹配图片的src
            preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
            foreach ($match[1] as $imgurl) {
                $imgurl = $imgurl;
                if (!empty($imgurl)) {
                    $post_list[$k]['img'][] = $imgurl;
                }
            }
            /* 解析emoji */
            $post_list[$k]['title'] = formatEmoji(false, $v['title']);
            $post_list[$k]['content'] = formatEmoji(false, $v['content']);
        }

        return $post_list;
    }

    /**
     * 微吧达人.
     */
    private function _weiba_daren($weibaid = 0)
    {
        $uidlist = M('user_group_link')->where('user_group_id=7')->limit(1000)->select();
        $map['follower_uid'] = array('in', getSubByKey($uidlist, 'uid'));
        if ($weibaid > 0) {
            $map['weiba_id'] = $weibaid;
        }
        $list = M('weiba_follow')->where($map)->group('follower_uid')->limit($var['limit'])->select();
        $uids = getSubByKey($list, 'follower_uid');
        foreach ($uids as $v) {
            $daren_arr[] = model('User')->getUserInfo($v);
        }

        return $daren_arr;
    }

    /**
     * 微吧掌柜.
     */
    private function _weiba_darens($weibaid = 0)
    {
        $uidlist = M('user_group_link')->where('user_group_id=5')->limit(1000)->select();
        $map['follower_uid'] = array('in', getSubByKey($uidlist, 'uid'));
        if ($weibaid > 0) {
            $map['weiba_id'] = $weibaid;
        }
        $list = M('weiba_follow')->where($map)->group('follower_uid')->limit($var['limit'])->select();
        $uids = getSubByKey($list, 'follower_uid');
        foreach ($uids as $v) {
            $daren_arr[] = model('User')->getUserInfo($v);
        }

        return $daren_arr;
    }

    //刷新catelist
    public function catelist()
    {
        // 安全过滤
        $map['is_del'] = 0;
        $map['status'] = 1;
        $list = M('weiba')->where($map)->select();
        $weiba = getSubByKey($list, 'weiba_id');
        unset($map);
        $map['recommend'] = 1;
        $map['weiba_id'] = array('in', $weiba);
        $p = $_REQUEST['p'];
        if (!$p) {
            $p = 1;
        }
        $map['is_del'] = 0;
        $postList = D('weiba_post')->where($map)->order('recommend_time desc')->findpage(10);
        $postList = $postList['data'];
        $weiba_ids = getSubByKey($post_list, 'weiba_id');
        $nameArr = $this->_getWeibaName($weiba_ids);
        foreach ($postList as $k => $v) {
            $postList[$k]['weiba'] = $nameArr[$v['weiba_id']];
            $postList[$k]['user'] = model('User')->getUserInfo($v['post_uid']);
        }
        //dump($postList);
        //dump(M()->getLastSql());
        $html = '';
        foreach ($postList as $vo) {
            $html .= '<dl>';
            $html .= '<dt><a href="'.U('weiba/Index/postDetail', array('post_id' => $vo['post_id'])).'">'.getShort(t($vo['title']), 20).'</a></dt>';
            $html .= '<dd class="f8">';
            $html .= '来自&nbsp;&nbsp;'.$vo['user']['space_link'].'&nbsp;&nbsp;'.friendlyDate($vo['post_time'], 'ymd');
            $html .= '</dd>';
            $html .= '</dl>';
        }
        exit(json_encode($html));
    }

    /**
     * 添加�
     * �注操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doFollow()
    {
        // 安全过滤
        $fid = t($_POST['fid']);
        $res = model('Follow')->doFollow($this->mid, intval($fid));
        $this->ajaxReturn($res, model('Follow')->getError(), false !== $res);
    }

    /**
     * 添加�
     * �注操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function unFollow()
    {
        // 安全过滤
        $fid = t($_POST['fid']);
        $res = model('Follow')->unFollow($this->mid, intval($fid));
        $this->ajaxReturn($res, model('Follow')->getError(), false !== $res);
    }

    /**
     * 换一换数据处理.
     *
     * @return json 渲染页面所需的JSON数据
     */
    public function changeRelate()
    {
        $sql = 'SELECT post_uid,count(post_uid) as num FROM `'.C('DB_PREFIX').'weiba_post` WHERE `is_del` = 0 GROUP BY post_uid ORDER BY rand () desc LIMIT 4';
        $daren_uids = D()->query($sql);
        foreach ($daren_uids as $v) {
            $daren_arr[] = model('User')->getUserInfo($v['post_uid']);
        }
        $content = '';
        foreach ($daren_arr as $vo) {
            $content .= '<li model-node="related_li" class="mb20">';
            $content .= '<div class="user left"> <a event-node="face_card" uid="'.$vo['uid'].'" href="'.$vo['space_url'].'" title="'.$vo['uname'].'" class="face"> <img  src="'.$vo['avatar_small'].'"/> </a> </div>';
            $content .= '<div class="user-prof left"> <a class="mb10">'.$vo['uname'].'</a>';
            //$content.='<p> '.$vo['auth_icon'].' </p>';
            $content .= '</div>';
            $content .= '<div class="left" id="'.$vo['uid'].'"><a onclick="follow_user('.$vo['uid'].')"   class="btns-red mt10"><i class="ico-add"></i>关注</a></div>';
            $content .= '</li>';
        }
        exit(json_encode($content));
    }

    //创建微吧
    public function found()
    {
        $this->assign('imgurl', '__THEME__/image/circle-bg.png');
        $this->assign('weiba_cates', D('WeibaCategory')->getAllWeibaCate());
        $this->display();
    }

    public function doAdd()
    {
        $data['weiba_name'] = t($_POST['weiba_name']);
        $data['is_del'] = 0;
        if (D('weiba')->where($data)->find()) {
            $ress['info'] = '此微吧已存在';
            $ress['status'] = 0;
            exit(json_encode($ress));
        }
        if ($_POST['who_can_post'] == '') {
            $ress['info'] = '发帖权限不能为空';
            $ress['status'] = 0;
            exit(json_encode($ress));
        }
        if ($_POST['weiba_name'] == '') {
            $ress['info'] = '微吧名称不能为空';
            $ress['status'] = 0;
            exit(json_encode($ress));
        }
        if ($_POST['intro'] == '') {
            $ress['info'] = '微吧简介不能为空';
            $ress['status'] = 0;
            exit(json_encode($ress));
        }
        if ($_POST['avatar_big'] == '') {
            $ress['info'] = '微吧LOGO不能为空';
            $ress['status'] = 0;
            exit(json_encode($ress));
        }
        if ($_POST['avatar_big'] == '') {
            $_POST['avatar_big'] = '';
        }
        if ($_POST['avatar_middle'] == '') {
            $_POST['avatar_middle'] = '';
        }
        $data['cid'] = intval($_POST['cid']);
        $data['uid'] = $this->mid;
        $data['ctime'] = time();
        $data['logo'] = t($_POST['logo']);
        $data['avatar_big'] = t($_POST['avatar_big']);
        $data['avatar_middle'] = t($_POST['avatar_middle']);
        $data['intro'] = $_POST['intro'];
        $data['info'] = $_POST['info'];
        $data['province'] = $_POST['province'];
        if ($_POST['input_city'] != '') {
            $data['input_city'] = $_POST['input_city'];
            $data['province'] = 0;
            $data['city'] = 0;
            $data['area'] = 0;
        } else {
            $data['province'] = $_POST['province'];
            $data['city'] = $_POST['city'];
            $data['area'] = $_POST['area'];
        }
        $data['status'] = 0; //创建添加审核
        $data['who_can_post'] = intval($_POST['who_can_post']);
        if (true) {
            $data['admin_uid'] = $this->mid;
            $data['follower_count'] = 1;
        }
        $data['recommend'] = intval($_POST['recommend']);
        $data['status'] = 0;
        $res = M('Weiba')->add($data);
        if ($res) {
            if ($this->mid) {      //超级圈主加入微吧
                $follow['follower_uid'] = $data['admin_uid'] = $this->mid;
                $follow['weiba_id'] = $res;
                $follow['level'] = 3;
                D('weiba_follow')->add($follow);
            }
            if ($data['admin_uid'] != $this->mid) {    //创建者加入微吧
                $follows['follower_uid'] = $this->mid;
                $follows['weiba_id'] = $res;
                $follows['level'] = 1;
                D('weiba_follow')->add($follows);
                D('weiba')->where('weiba_id='.$res)->setInc('follower_count');
            }
            model('Notify')->sendNotify($this->mid, 'weiba_appeal');
            $ress['info'] = '创建成功请等待管理员审核！';
            $ress['status'] = 1;
            exit(json_encode($ress));
        } else {
            $ress['info'] = '创建失败';
            $ress['status'] = 0;
            exit(json_encode($ress));
        }
    }

    public function addPostDigg()
    {
        $maps['post_id'] = $map['post_id'] = intval($_POST['row_id']);
        $map['uid'] = $this->mid;
        $hasdigg = M('weiba_post_digg')->where($map)->find();
        $weiba = M('weiba_post')->where('post_id='.$map['post_id'])->find();
        // $is_follow = $this->is_follow($weiba['weiba_id']);
        // if(!$is_follow){
        // 	echo 0;exit;
        // }

        $map['cTime'] = time();
        $result = M('weiba_post_digg')->add($map);
        if ($result && !$hasdigg) {
            $post = M('weiba_post')->where($maps)->find();
            M('weiba_post')->where($maps)->setField('praise', $post['praise'] + 1);
            model('UserData')->updateKey('unread_digg_weibapost', 1, true, $weiba['post_uid']);
            echo 1;
        } else {
            echo 0;
        }
    }

    public function delPostDigg()
    {
        $maps['post_id'] = $map['post_id'] = intval($_POST['row_id']);
        $map['uid'] = $this->mid;
        $hasdigg = M('weiba_post')->where('post_id='.$map['post_id'])->find();
        // $is_follow = $this->is_follow($hasdigg['weiba_id']);
        // if(!$is_follow){
        // 	echo 0;exit;
        // }

        $result = M('weiba_post_digg')->where($map)->delete();
        if ($result) {
            $post = M('weiba_post')->where($maps)->find();
            M('weiba_post')->where($maps)->setField('praise', $post['praise'] - 1);
            echo 1;
        } else {
            echo 0;
        }
    }

    //下载源码
    public function checkDownload()
    {
        if (IS_POST) {
            $mobile = t($_POST['mobile']);
            $code = $_POST['verifiy'];
            if (!preg_match("/^[1][34578]\d{9}$/", $mobile)) {
                $this->ajaxReturn(null, '无效的手机号', 0);
            }
            $result = model('Sms')->CheckCaptcha($mobile, $code);
            $data = array();
            if ($result) {
                $insertArr = array();
                $insertArr['phone'] = $mobile;
                $insertArr['ctime'] = time();
                M('check_download')->add($insertArr);

                $data['url'] = 'http://korean.zhibocloud.cn/20170303.zip';
                $data['info'] = '验证成功';

                $this->ajaxReturn($data, '验证成功', 1);
            } else {
                $data['info'] = '验证码不正确';

                $this->ajaxReturn($data, '验证失败', 0);
            }
        }

        $this->display();
    }

    public function getVerifiyCode()
    {
        if (IS_POST) {
            $phone = t($_POST['mobile']);
            /* # 检查是否是手机号码 */
            if (!preg_match("/^[1][34578]\d{9}$/", $phone)) {
                $this->ajaxReturn(null, '无效的手机号', 0);
            } elseif (($sms = model('Sms')) and $sms->sendCaptcha($phone, true)) {
                $this->ajaxReturn(null, '发送成功', 1);
            } else {
                $this->ajaxReturn(null, $sms->getMessage(), 0);
            }
        }
    }
}
