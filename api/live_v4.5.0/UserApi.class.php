<?php
/**
 * @author jason
 */
class UserApi extends Api
{
    /**
     * 获取用户管理权限列表.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function getManageList()
    {
        $manage = array();

        /* 删除分享权限 */
        $manage['manage_del_feed'] = (bool) CheckPermission('core_admin', 'feed_del');

        /* 删除分享评论权限 */
        $manage['manage_del_feed_comment'] = (bool) CheckPermission('core_admin', 'comment_del');

        /* 删除微吧帖子权限 */
        $manage['manage_del_weiba_post'] = (bool) CheckPermission('weiba_admin', 'weiba_del');

        return $manage;
    }

    /**
     * undocumented function.
     *
     * @author
     **/
    public function test()
    {
        var_dump(CheckPermission('weiba_admin', 'weiba_del'));
        var_dump(model('Permission')->loadRule($this->mid), $this->mid);
        exit;
    }

    /**
     * 上传自定义封面.
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function uploadUserCover()
    {
        if (!$this->mid) {
            $this->error(array(
                'status' => '-1',
                'msg'    => '没有登陆',
            ));
        }

        $info = model('Attach')->upload(array('upload_type' => 'image'));

        if (count($info['info']) <= 0) {
            $this->error(array(
                'status' => '-2',
                'msg'    => '没有上传任何文件',
            ));
        }

        $info = array_pop($info['info']);

        if (D('user_data')->where('`uid` = '.$this->mid.' AND `key` LIKE "application_user_cover"')->count()) {
            D('user_data')->where('`uid` = '.$this->mid.' AND `key` LIKE "application_user_cover"')->save(array(
                'value' => $info['attach_id'],
            ));
        } else {
            D('user_data')->add(array(
                'uid'   => $this->mid,
                'key'   => 'application_user_cover',
                'value' => $info['attach_id'],
            ));
        }

        return array(
            'status' => '1',
            'msg'    => '更新成功！',
            'image'  => getImageUrlByAttachId($info['attach_id']),
        );
    }

    /**
     * 用户个人主页 --using.
     *
     * @param int     $user_id
     *                         用户UID
     * @param varchar $uname
     *                         用户名
     *
     * @return array 状态+提示 或 用户信息
     */
    public function show()
    {
        $num = $_REQUEST['num'];
        $num = intval($num);
        $num or $num = 10;

        if (empty($this->user_id) && empty($this->data['uname'])) {
            $uid = $this->mid;
        } else {
            if ($this->user_id) {
                $uid = intval($this->user_id);
            } else {
                $uid = model('User')->where(array(
                        'uname' => $this->data['uname'],
                ))->getField('uid');
            }
        }
        if ($this->mid != $uid) {
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $uid);
            if ($privacy['space'] == 1) {
                return array(
                        'status' => 0,
                        'msg'    => '您没有权限进入TA的个人主页',
                );
            }
        }
        $userInfo = $this->get_user_info($uid);
        if (!$userInfo['uname']) {
            return array(
                    'status' => 0,
                    'msg'    => '该用户不存在或已被删除',
            );
        }
        // $userInfo['can_'] = CheckPermission('core_normal','feed_del');
        $user_info['is_admin'] = CheckPermission('core_admin', 'feed_del') ? '1' : '0';
        $user_info['uid'] = $userInfo['uid'];
        $user_info['uname'] = $userInfo['uname'];
        $user_info['remark'] = $userInfo['remark'];
        $user_info['sex'] = $userInfo['sex'] == 1 ? '男' : '女';
        $user_info['intro'] = $userInfo['intro'] ? formatEmoji(false, $userInfo['intro']) : '';
        $user_info['location'] = $userInfo['location'] ? $userInfo['location'] : '';
        $user_info['avatar'] = $userInfo['avatar']['avatar_big'];
        $user_info['experience'] = t($userInfo['user_credit']['credit']['experience']['value']);
        $user_info['charm'] = t($userInfo['user_credit']['credit']['charm']['value']);
        $user_info['weibo_count'] = t(intval($userInfo['user_data']['weibo_count']));
        $user_info['follower_count'] = t(intval($userInfo['user_data']['follower_count']));
        $user_info['following_count'] = t(intval($userInfo['user_data']['following_count']));
        //用户空间隐私判断
        $privacy = model('UserPrivacy')->getPrivacy($this->mid, $userInfo['uid']);
        $user_info['space_privacy'] = $privacy['space'];

        $follower = model('Follow')->where('fid='.$user_info['uid'])->order('follow_id DESC')->field('uid')->limit($num)->findAll();
        $following = model('Follow')->where('uid='.$user_info['uid'])->order('follow_id DESC')->field('fid')->limit($num)->findAll();
        $follower_arr = $following_arr = array();
        foreach ($follower as $k => $v) {
            $follower_info = $this->get_user_info($v['uid']);
            $follower_arr[$k]['uid'] = $follower_info['uid'];
            $follower_arr[$k]['uname'] = $follower_info['uname'];
            $follower_arr[$k]['remark'] = $follower_info['remark'];
            $follower_arr[$k]['avatar'] = $follower_info['avatar']['avatar_big'];
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $follower_info['uid']);
            $follower_arr[$k]['space_privacy'] = $privacy['space'];
        }
        foreach ($following as $k1 => $v1) {
            $following_info = $this->get_user_info($v1['fid']);
            $following_arr[$k1]['uid'] = $following_info['uid'];
            $following_arr[$k1]['uname'] = $following_info['uname'];
            $following_arr[$k1]['remark'] = $following_info['remark'];
            $following_arr[$k1]['avatar'] = $following_info['avatar']['avatar_big'];
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $following_info['uid']);
            $following_arr[$k1]['space_privacy'] = $privacy['space'];
        }
        $user_info['follower'] = $follower_arr;
        $user_info['following'] = $following_arr;
        $user_info['follow_status'] = model('Follow')->getFollowState($this->mid, $uid);
        $user_info['is_in_blacklist'] = t(D('user_blacklist')->where('uid='.$this->mid.' and fid='.$uid)->count());

        $user_info['photo_count'] = model('Attach')->where(array(
                'is_del'      => 0,
                'attach_type' => 'feed_image',
                'uid'         => $uid,
        ))->count();
        $user_info['photo'] = $this->user_photo($uid);

        $map['uid'] = $uid;
        $map['type'] = 'postvideo';
        $map['is_del'] = 0;
        $user_info['video_count'] = M('feed')->where($map)->count();
        $user_info['video'] = $this->user_video($uid);
        $user_info['level_src'] = $userInfo['user_credit']['level']['src'];

        // 用户认证图标
        $groupIcon = array();
        $userGroup = model('UserGroupLink')->getUserGroupData($uid);
        foreach ($userGroup[$uid] as $g) {
            $g['is_authenticate'] == 1 && $groupArr[] = $g['user_group_name'];
        }
        $user_info['authenticate'] = empty($groupArr) ? '无' : implode(' , ', $groupArr);

        /* # 获取用户认证理由 */
        $user_info['certInfo'] = D('user_verified')->where('verified=1 AND uid='.$uid)->field('info')->getField('info');

        /* # 获取用户封面 */
        $user_info['cover'] = D('user_data')->where('`key` LIKE "application_user_cover" AND `uid` = '.$uid)->field('value')->getField('value');
        $user_info['cover'] = getImageUrlByAttachId($user_info['cover']);

        // 用户组
        $user_group = model('UserGroupLink')->where('uid='.$uid)->field('user_group_id')->findAll();
        foreach ($user_group as $v) {
            $user_group_icon = D('user_group')->where('user_group_id='.$v['user_group_id'])->getField('user_group_icon');
            if ($user_group_icon != -1) {
                $user_info['user_group'][] = THEME_PUBLIC_URL.'/image/usergroup/'.$user_group_icon;
            }
        }

        // 勋章
        $list = M()->query('select b.small_src from '.C('DB_PREFIX').'medal_user a inner join '.C('DB_PREFIX').'medal b on a.medal_id=b.id where a.uid='.$uid.' order by a.ctime desc limit 10');
        foreach ($list as $v) {
            $smallsrc = explode('|', $v['small_src']);
            $user_info['medals'][] = $smallsrc[1];
        }

        $user_info['gift_count'] = M('gift_user')->where($map)->count();
        $user_info['gift_list'] = $gift_list;

        $user_info['user_credit'] = $userInfo['user_credit'];
        $user_info['tags'] = (array) model('Tag')->setAppName('public')->setAppTable('user')->getAppTags($uid, true);

        // 没登陆过智播没有usid的 直接生成一个
        $live_user_mod = M('live_user_info');
        if (!$usid = $live_user_mod->where(array('uid' => $userInfo['uid']))->getField('usid')) {
            $live_user_info = file_get_contents(SITE_URL.'/api.php?api_type=live&mod=LiveUser&act=postUser&uid='.$userInfo['uid']);
            $live_user_info = json_decode($live_user_info, true);
            $live_user_info['status'] == 1 && $user_info['usid'] = $live_user_info['data']['usid'];
        } else {
            $user_info['usid'] = $usid;
        }
        //可查看自己的绑定账户
        if ($uid == $this->mid) {
            $userAccountinfo = D('user_account')->where(array('uid' => $this->mid))->find();
            if (!$userAccountinfo) {
                $user_info['account'] = '';
                $user_info['account_type'] = 0;
            } else {
                $length = strlen($userAccountinfo['account']);
                $user_info['account'] = substr_replace($userAccountinfo['account'], '****', 3, $length - 3);
                $user_info['account_type'] = $userAccountinfo['type'];
            }
        }

        return $user_info;
    }

    //获取用户勋章
    public function get_user_medal()
    {
        if (isset($this->data['uid'])) {
            $uid = intval($this->data['uid']);
        } elseif (isset($this->data['uname'])) {
            $map['uname'] = t($this->data['uname']);
            $uid = M('user')->where($map)->getField('uid');
        } else {
            $uid = $this->mid;
        }
        $list = M()->query('select b.* from '.C('DB_PREFIX').'medal_user a inner join '.C('DB_PREFIX').'medal b on a.medal_id=b.id where a.uid='.$uid.' order by a.ctime desc');
        foreach ($list as &$v) {
            $src = explode('|', $v['src']);
            $v['src'] = getImageUrl($src[1]);
            $smallsrc = explode('|', $v['small_src']);
            $v['small_src'] = $smallsrc[1];
            //$v ['small_src'] = getImageUrl ( $smallsrc [1] );
            unset($v['type']);
        }

        return $list;
    }

    /**
     * 获取用户信息 --using.
     *
     * @param int $uid
     *                 用户UID
     *
     * @return array 用户信息
     */
    public function get_user_info($uid)
    {
        $user_info = model('Cache')->get('user_info_api_'.$uid);
        if (!$user_info) {
            $user_info = model('User')->where('uid='.$uid)->field('uid,uname,sex,location,province,city,area,intro')->find();
            // 头像
            $avatar = model('Avatar')->init($uid)->getUserAvatar();
            // $user_info ['avatar'] ['avatar_middle'] = $avatar ["avatar_big"];
            // $user_info ['avatar'] ['avatar_big'] = $avatar ["avatar_big"];
            $user_info['avatar'] = $avatar;
            // 用户组
            $user_group = model('UserGroupLink')->where('uid='.$uid)->field('user_group_id')->findAll();
            foreach ($user_group as $v) {
                $user_group_icon = D('user_group')->where('user_group_id='.$v['user_group_id'])->getField('user_group_icon');
                if ($user_group_icon != -1) {
                    $user_info['user_group'][] = THEME_PUBLIC_URL.'/image/usergroup/'.$user_group_icon;
                }
            }
            model('Cache')->set('user_info_api_'.$uid, $user_info);
        }
        // 积分、经验
        $user_info['user_credit'] = model('Credit')->getUserCredit($uid);
        $user_info['intro'] && $user_info['intro'] = formatEmoji(false, $user_info['intro']);
        // 用户统计
        $user_info['user_data'] = model('UserData')->getUserData($uid);
        // 用户备注
        $user_info['remark'] = model('UserRemark')->getRemark($this->mid, $uid);
        //个人空间隐私权限
        $privacy = model('UserPrivacy')->getPrivacy($this->mid, $uid);
        $user_info['space_privacy'] = $privacy['space'];

        return $user_info;
    }

    /**
     * 用户粉丝列表 --using.
     *
     * @param int     $user_id
     *                         用户UID
     * @param varchar $uname
     *                         用户名
     * @param varchar $key
     *                         搜索�
     * �键字
     * @param int $max_id
     *                         上次返回的最后一条�
     * �注ID
     * @param int $count
     *                   粉丝个数
     *
     * @return array 用户信息+�
     * �注状态
     */
    public function user_follower()
    {
        model('UserData')->setKeyValue($this->mid, 'new_folower_count', 0);
        if (empty($this->user_id) && empty($this->data['uname'])) {
            $uid = $this->mid;
            // 如果是本人,清空新粉丝提醒数字
            $udata = model('UserData')->getUserData($this->mid);
            $udata['new_folower_count'] > 0 && model('UserData')->setKeyValue($this->mid, 'new_folower_count', 0);
        } else {
            if ($this->user_id) {
                $uid = intval($this->user_id);
            } else {
                $uid = model('User')->where(array(
                        'uname' => $this->data['uname'],
                ))->getField('uid');
            }
        }
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        if (t($this->data['key'])) {
            $map['f.`fid`'] = $uid;
            !empty($max_id) && $map['follow_id'] = array(
                    'lt',
                    $max_id,
            );
            $_map['u.`uname`'] = array(
                    'LIKE',
                    '%'.$this->data['key'].'%',
            );
            //通过备注名搜索
            $ruid_arr = D('UserRemark')->searchRemark($this->mid, t($this->data['key']));
            if ($ruid_arr) {
                $_map['u.`uid`'] = array('IN', $ruid_arr);
                $_map['_logic'] = 'OR';
            }

            $map['_complex'] = $_map;

            $follower = D()->table('`'.C('DB_PREFIX').'user_follow` AS f LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON f.`uid` = u.`uid`')->field('f.`follow_id` AS `follow_id`,f.`uid` AS `uid`')->where($map)->order('follow_id DESC')->limit($count)->findAll();
        } else {
            $where = 'fid = '.$uid;
            !empty($max_id) && $where .= " AND follow_id < {$max_id}";
            $follower = model('Follow')->where($where)->order('follow_id DESC')->field('follow_id,uid')->limit($count)->findAll();
        }
        $follow_status = model('Follow')->getFollowStateByFids($this->mid, getSubByKey($follower, 'uid'));
        $follower_arr = array();
        foreach ($follower as $k => $v) {
            $follower_arr[$k]['follow_id'] = $v['follow_id'];
            $follower_info = $this->get_user_info($v['uid']);
            $follower_arr[$k]['user_group'] = $follower_info['user_group'];
            $follower_arr[$k]['uid'] = $v['uid'];
            $follower_arr[$k]['uname'] = $follower_info['uname'];
            $follower_arr[$k]['remark'] = $follower_info['remark'];
            $follower_arr[$k]['intro'] = $follower_info['intro'] ? formatEmoji(false, $follower_info['intro']) : '';
            $follower_arr[$k]['avatar'] = $follower_info['avatar']['avatar_big'];
            $follower_arr[$k]['follow_status'] = $follow_status[$v['uid']];
            //个人空间隐私权限
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['uid']);
            $follower_arr[$k]['space_privacy'] = $privacy['space'];
        }

        return $follower_arr;
    }

    /**
     * 用户�
     * �注列表 --using.
     *
     * @param int     $user_id
     *                         用户UID
     * @param varchar $uname
     *                         用户名
     * @param varchar $key
     *                         搜索�
     * �键字
     * @param int $max_id
     *                         上次返回的最后一条�
     * �注ID
     * @param int $count
     *                         �
     * �注个数
     *
     * @return array 用户信息+�
     * �注状态
     */
    public function user_following()
    {
        if (empty($this->user_id) && empty($this->data['uname'])) {
            $uid = $this->mid;
        } else {
            if ($this->user_id) {
                $uid = intval($this->user_id);
            } else {
                $uid = model('User')->where(array(
                        'uname' => $this->data['uname'],
                ))->getField('uid');
            }
        }
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        if (t($this->data['key'])) {
            $map['f.`uid`'] = $uid;
            !empty($max_id) && $map['follow_id'] = array(
                    'lt',
                    $max_id,
            );

            $_map['u.`uname`'] = array(
                    'LIKE',
                    '%'.$this->data['key'].'%',
            );

            //通过备注名搜索
            $ruid_arr = D('UserRemark')->searchRemark($this->mid, t($this->data['key']));
            if ($ruid_arr) {
                $_map['u.`uid`'] = array('IN', $ruid_arr);
                $_map['_logic'] = 'OR';
            }
            $map['_complex'] = $_map;

            $following = D()->table('`'.C('DB_PREFIX').'user_follow` AS f LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON f.`fid` = u.`uid`')->field('f.`follow_id` AS `follow_id`,f.`fid` AS `fid`')->where($map)->order('follow_id DESC')->limit($count)->findAll();
        } else {
            $where = 'uid = '.$uid;
            !empty($max_id) && $where .= " AND follow_id < {$max_id}";
            $following = model('Follow')->where($where)->order('follow_id DESC')->field('follow_id,fid')->limit($count)->findAll();
        }
        $follow_status = model('Follow')->getFollowStateByFids($this->mid, getSubByKey($following, 'fid'));
        $following_arr = array();
        foreach ($following as $k => $v) {
            $following_arr[$k]['follow_id'] = $v['follow_id'];
            $following_info = $this->get_user_info($v['fid']);
            $following_arr[$k]['user_group'] = $following_info['user_group'];
            $following_arr[$k]['uid'] = $v['fid'];
            $following_arr[$k]['uname'] = $following_info['uname'];
            $following_arr[$k]['remark'] = $following_info['remark'];
            $following_arr[$k]['intro'] = $following_info['intro'] ? formatEmoji(false, $following_info['intro']) : '';
            $following_arr[$k]['avatar'] = $following_info['avatar']['avatar_big'];
            $following_arr[$k]['follow_status'] = $follow_status[$v['fid']];
            //个人空间隐私权限
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['fid']);
            $following_arr[$k]['space_privacy'] = $privacy['space'];
        }

        return $following_arr;
    }

    /**
     * 用户好友列表(相互�
     * �注) --using.
     *
     * @param int     $user_id
     *                         用户UID
     * @param varchar $uname
     *                         用户名
     * @param varchar $key
     *                         搜索�
     * �键字
     * @param int $max_id
     *                         上次返回的最后一条�
     * �注ID
     * @param int $count
     *                   好友个数
     *
     * @return array 用户信息+�
     * �注状态
     */
    public function user_friend()
    {
        if (empty($this->user_id) && empty($this->data['uname'])) {
            $uid = $this->mid;
        } else {
            if ($this->user_id) {
                $uid = intval($this->user_id);
            } else {
                $uid = model('User')->where(array(
                        'uname' => $this->data['uname'],
                ))->getField('uid');
            }
        }
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        $where = " a.uid = '{$uid}' AND b.uid IS NOT NULL";
        if (t($this->data['key'])) {
            $uid_arr = getSubByKey(model('User')->where(array(
                    'uname' => array(
                            'like',
                            '%'.t($this->data['key']).'%',
                    ),
            ))->field('uid')->findAll(), 'uid');

            //通过备注名搜索
            $ruid_arr = D('UserRemark')->searchRemark($this->mid, t($this->data['key']));
            //合并去重
            if (!is_array($uid_arr)) {
                $uid_arr = array();
            }
            if (!is_array($ruid_arr)) {
                $ruid_arr = array();
            }
            $_uid_arr = array_unique(array_merge($uid_arr, $ruid_arr));

            $where .= ' AND b.uid IN ('.implode(',', $_uid_arr).')';
        }
        !empty($max_id) && $where .= " AND a.follow_id < {$max_id}";
        $friend = D()->table('`'.C('DB_PREFIX').'user_follow` AS a LEFT JOIN `'.C('DB_PREFIX').'user_follow` AS b ON a.uid = b.fid AND b.uid = a.fid')->field('a.fid, a.follow_id')->where($where)->limit($count)->order('a.follow_id DESC')->findAll();
        $follow_status = model('Follow')->getFollowStateByFids($this->mid, getSubByKey($friend, 'fid'));
        $friend_arr = array();
        foreach ($friend as $k => $v) {
            $friend_arr[$k]['follow_id'] = $v['follow_id'];
            $friend_info = $this->get_user_info($v['fid']);
            $friend_arr[$k]['uid'] = $friend_info['uid'];
            $friend_arr[$k]['uname'] = $friend_info['uname'];
            $friend_arr[$k]['remark'] = $friend_info['remark'];
            $friend_arr[$k]['intro'] = $friend_info['intro'] ? formatEmoji(false, $friend_info['intro']) : '';
            $friend_arr[$k]['avatar'] = $friend_info['avatar']['avatar_big'];
            $friend_arr[$k]['follow_status'] = $follow_status[$v['fid']];
            //个人空间隐私权限
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['fid']);
            $friend_arr[$k]['space_privacy'] = $privacy['space'];
        }

        return $friend_arr;
    }

    /**
     * 按字母返回用户好友列表(相互�
     * �注) --using.
     *
     * @param int    $user_id
     *                        用户UID
     * @param string $uname
     *                        用户名
     * @param string $key
     *                        �
     * �键字
     * @param
     *        	integer max_id 上次返回的最后一条uid
     *
     * @return array 用户信息+�
     * �注状态
     */
    public function user_friend_by_letter()
    {
        if (empty($this->user_id) && empty($this->data['uname'])) {
            $uid = $this->mid;
        } else {
            if ($this->user_id) {
                $uid = intval($this->user_id);
            } else {
                $uid = model('User')->where(array(
                        'uname' => $this->data['uname'],
                ))->getField('uid');
            }
        }

        $letters = array(
                'A' => array(),
                'B' => array(),
                'C' => array(),
                'D' => array(),
                'E' => array(),
                'F' => array(),
                'G' => array(),
                'H' => array(),
                'I' => array(),
                'J' => array(),
                'K' => array(),
                'L' => array(),
                'M' => array(),
                'N' => array(),
                'O' => array(),
                'P' => array(),
                'Q' => array(),
                'R' => array(),
                'S' => array(),
                'T' => array(),
                'U' => array(),
                'V' => array(),
                'W' => array(),
                'X' => array(),
                'Y' => array(),
                'Z' => array(),
        );

        $where = " a.uid = '{$uid}' AND b.uid IS NOT NULL";
        $friend = D()->table('`'.C('DB_PREFIX').'user_follow` AS a LEFT JOIN `'.C('DB_PREFIX').'user_follow` AS b ON a.uid = b.fid AND b.uid = a.fid')->field('a.fid, a.follow_id')->where($where)->order('a.follow_id DESC')->findAll();
        $follow_status = model('Follow')->getFollowStateByFids($this->mid, getSubByKey($friend, 'fid'));
        if (!t($this->data['key'])) { // 无搜索
            foreach ($friend as $k => $v) {
                $friend_info = $this->get_user_info($v['fid']);

                //如果有备注，按照备注来算首字母
                $first_letter = $friend_info['remark'] != '' ? getFirstLetter($friend_info['remark']) : getFirstLetter($friend_info['uname']);
                $letters[$first_letter][$v['follow_id']]['uid'] = $friend_info['uid'];
                $letters[$first_letter][$v['follow_id']]['uname'] = $friend_info['uname'];
                $letters[$first_letter][$v['follow_id']]['remark'] = $friend_info['remark'];
                $letters[$first_letter][$v['follow_id']]['intro'] = $friend_info['intro'] ? formatEmoji(false, $friend_info['intro']) : '';
                $letters[$first_letter][$v['follow_id']]['avatar'] = $friend_info['avatar']['avatar_original'];
                $letters[$first_letter][$v['follow_id']]['follow_status'] = $follow_status[$v['fid']];
                //个人空间隐私权限
                $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['fid']);
                $letters[$first_letter][$v['follow_id']]['space_privacy'] = $privacy['space'];
            }

            return $letters;
        } else {
            $where = ' `uid` IN ('.implode(',', getSubByKey($friend, 'fid')).')';
            $max_id = $this->max_id ? intval($this->max_id) : 0;
            $count = $this->count ? intval($this->count) : 20;
            !empty($max_id) && $where .= " AND `uid`<{$max_id}";

            //通过备注名搜索
            $ruid_arr = D('UserRemark')->searchRemark($this->mid, t($this->data['key']));
            if ($ruid_arr) {
                $where .= " AND (`uname` like '%".t($this->data['key'])."%' OR ".'`uid` IN ('.implode(',', $ruid_arr).'))';
            } else {
                $where .= " AND `uname` like '%".t($this->data['key'])."%'";
            }

            $user = model('User')->where($where)->limit($count)->field('uid')->order('uid desc')->findAll();
            // dump(D()->getLastSql());
            $user_list = array();
            foreach ($user as $k => $v) {
                $friend_info = $this->get_user_info($v['uid']);
                $user_detail['uid'] = $friend_info['uid'];
                $user_detail['uname'] = $friend_info['uname'];
                $user_detail['remark'] = $friend_info['remark'];
                $user_detail['intro'] = $friend_info['intro'] ? formatEmoji(false, $friend_info['intro']) : '';
                $user_detail['avatar'] = $friend_info['avatar']['avatar_original'];
                $user_detail['follow_status'] = $follow_status[$v['uid']];
                //个人空间隐私权限
                $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['uid']);
                $user_detail['space_privacy'] = $privacy['space'];
                $user_list[] = $user_detail;
            }

            return $user_list;
        }
    }

    /**
     * 用户礼物列表 --using.
     *
     * @param int     $user_id
     *                         用户UID
     * @param varchar $uname
     *                         用户名
     *
     * @return array 礼物列表
     */
    // public function user_gift() {
    // 	if (empty ( $this->user_id ) && empty ( $this->data ['uname'] )) {
    // 		$uid = $this->mid;
    // 	} else {
    // 		if ($this->user_id) {
    // 			$uid = intval ( $this->user_id );
    // 		} else {
    // 			$uid = model ( 'User' )->where ( array (
    // 					'uname' => $this->data ['uname']
    // 			) )->getField ( 'uid' );
    // 		}
    // 	}

    // 	$max_id = $this->max_id ? intval ( $this->max_id ) : 0;
    // 	$count = $this->count ? intval ( $this->count ) : 20;

    // 	! empty ( $max_id ) && $map ['id'] = array (
    // 			'lt',
    // 			$max_id
    // 	);

    // 	$map ['toUserId'] = $uid;
    // 	$map ['status'] = 1;
    // 	$gifts = M ( 'gift_user' )->field ( 'id,fromUserId,toUserId,giftPrice,giftImg' )->where ( $map )->order ( 'id DESC' )->limit ( $count )->findAll (); // giftId,giftName,giftNum,

    // 	$gift_list = array ();
    // 	foreach ( $gifts as $k => $v ) {
    // 		$map3 ['img'] = $v ['giftImg'];
    // 		$gift_detail = D ( 'gift' )->where ( $map3 )->find ();
    // 		$gift_list [$k] ['name'] = $gift_detail ['name'];
    // 		if ($v ['giftPrice']) {
    // 			$gift_list [$k] ['price'] = $v ['giftPrice'] . $credit_type;
    // 		} else {
    // 			$gift_list [$k] ['price'] = '免费';
    // 		}
    // 		$gift_list [$k] ['id'] = $v ['id'];
    // 		$gift_list [$k] ['giftId'] = $gift_detail ['id'];
    // 		$gift_list [$k] ['giftName'] = $gift_detail ['name'];
    // 		$gift_list [$k] ['num'] = '1';
    // 		$gift_list [$k] ['image'] = api('Gift')->realityImageURL($gift_detail ['img']); //SITE_URL . '/apps/gift/Tpl/default/Public/gift/' . $gift_detail ['img']; // http://dev.thinksns.com/t4/apps/gift/Tpl/default/Public/gift
    // 	}

    // 	return $gift_list;
    // }

    /**
     * 用户相册 --using.
     *
     * @param int $user_id
     *                     用户UIDuname
     * @param varchar $
     *        	用户名
     * @param int $max_id
     *                    上次返回的最后一条附件ID
     * @param int $count
     *                    图片个数
     *
     * @return array �
     * �片列表
     */
    public function user_photo($uid_param)
    {
        if ($uid_param) {
            $uid = $uid_param;
            $this->count = 4;
        } else {
            if (empty($this->user_id) && empty($this->data['uname'])) {
                $uid = $this->mid;
            } else {
                if ($this->user_id) {
                    $uid = intval($this->user_id);
                } else {
                    $uid = model('User')->where(array(
                            'uname' => $this->data['uname'],
                    ))->getField('uid');
                }
            }
        }

        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        $map['uid'] = $uid;
        $map['attach_type'] = 'feed_image';
        $map['is_del'] = 0;
        !empty($max_id) && $map['attach_id'] = array(
                'lt',
                $max_id,
        );

        $list = model('Attach')->where($map)->order('attach_id Desc')->limit($count)->findAll();
        $photo_list = array();
        foreach ($list as $k => $value) {
            $attachInfo = model('Attach')->getAttachById($value['attach_id']);
            $photo_list[$k]['image_id'] = $value['attach_id'];
            $photo_list[$k]['image_url'] = getImageUrl($attachInfo['save_path'].$attachInfo['save_name']);
        }

        return $photo_list;
    }

    /**
     * 用户视频 --using.
     *
     * @param int     $user_id
     *                         用户UID
     * @param varchar $uname
     *                         用户名
     * @param int     $max_id
     *                         上次返回的最后一条微博ID
     * @param int     $count
     *                         视频个数
     *
     * @return array 视频列表
     */
    public function user_video($uid_param)
    {
        if ($uid_param) {
            $uid = $uid_param;
            $this->count = 4;
        } else {
            if (empty($this->user_id) && empty($this->data['uname'])) {
                $uid = $this->mid;
            } else {
                if ($this->user_id) {
                    $uid = intval($this->user_id);
                } else {
                    $uid = model('User')->where(array(
                            'uname' => $this->data['uname'],
                    ))->getField('uid');
                }
            }
        }

        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        $map['a.uid'] = $uid;
        $map['a.type'] = 'postvideo';
        $map['a.is_del'] = 0;

        !empty($max_id) && $map['a.feed_id'] = array(
                'lt',
                $max_id,
        );

        $list = D()->table('`'.C('DB_PREFIX').'feed` AS a LEFT JOIN `'.C('DB_PREFIX').'feed_data` AS b ON a.`feed_id` = b.`feed_id`')->field('a.`feed_id`, a.`publish_time`, b.`feed_data`')->where($map)->order('feed_id DESC')->limit($count)->findAll();
        $video_config = model('Xdata')->get('admin_Content:video_config');
        $video_server = $video_config['video_server'] ? $video_config['video_server'] : SITE_URL;
        $video_list = array();
        foreach ($list as $k => $value) {
            $tmp = unserialize($value['feed_data']);
            $video_list[$k]['feed_id'] = $value['feed_id'];
            $video_id = $tmp['video_id'];
            if ($video_id) {
                $video_list[$k]['video_id'] = $video_id;
                $video_list[$k]['flashimg'] = $video_server.$tmp['image_path'];
                if ($tmp['transfer_id'] && !D('video_transfer')->where('transfer_id='.$tmp['transfer_id'])->getField('status')) {
                    $video_list[$k]['transfering'] = 1;
                } else {
                    $video_list[$k]['flashvar'] = $tmp['video_mobile_path'] ? $video_server.$tmp['video_mobile_path'] : $video_server.$tmp['video_path'];
                }
            } else {
                $video_list[$k]['flashimg'] = UPLOAD_URL.'/'.$tmp['flashimg'];
                $pos = stripos($tmp['body'], 'http');
                $video_list[$k]['flashvar'] = substr($tmp['body'], $pos);
            }
        }

        return $video_list;
    }

    /**
     * ************ 个人设置 ****************.
     */

    /**
     * 获取用户黑名单列表 --using.
     *
     * @param int $max_id
     *                    上次返回的最后一个用户UID
     * @param int $count
     *                    用户个数
     * @param
     *        	array 黑名单用户列表
     */
    public function user_blacklist()
    {
        $count = $this->count ? intval($this->count) : 20;
        if ($this->max_id) {
            $ctime = D('user_blacklist')->where('uid='.$this->mid.' and fid='.intval($this->max_id))->getField('ctime');
            $map['ctime'] = array(
                    'lt',
                    $ctime,
            );
        }
        $map['uid'] = $this->mid;
        $user_blacklist = array();
        $list = D('user_blacklist')->where($map)->field('fid')->order('ctime desc')->limit($count)->findAll();
        foreach ($list as $k => $v) {
            $blacklist_info = $this->get_user_info($v['fid']);
            $user_blacklist[$k]['uid'] = $blacklist_info['uid'];
            $user_blacklist[$k]['uname'] = $blacklist_info['uname'];
            $user_blacklist[$k]['remark'] = $blacklist_info['remark'];
            $user_blacklist[$k]['intro'] = $blacklist_info['intro'] ? formatEmoji(false, $blacklist_info['intro']) : '';
            $user_blacklist[$k]['avatar'] = $blacklist_info['avatar']['avatar_big'];
            //个人空间隐私权限
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['fid']);
            $user_blacklist[$k]['space_privacy'] = $privacy['space'];
        }

        return $user_blacklist;
    }

    /**
     * 将指定用户添加到黑名单 --using.
     *
     * @param int $user_id
     *                     黑名单用户UID
     *
     * @return array 状态+提示
     */
    public function add_blacklist()
    {
        $uid = intval($this->user_id);

        if (empty($uid)) {
            return array(
                    'status' => 0,
                    'msg'    => '请指定用户',
            );
        }
        if ($uid == $this->mid) {
            return array(
                    'status' => 0,
                    'msg'    => '不能把自己加入黑名单',
            );
        }
        if (D('user_blacklist')->where(array(
                'uid' => $this->mid,
                'fid' => $uid,
        ))->count()) {
            return array(
                    'status' => 0,
                    'msg'    => '用户已经在黑名单中了',
            );
        }

        $data['uid'] = $this->mid;
        $data['fid'] = $uid;
        $data['ctime'] = time();
        if (D('user_blacklist')->add($data)) {
            model('Follow')->unFollow($this->mid, $uid);
            model('Follow')->unFollow($uid, $this->mid);
            model('Cache')->set('u_blacklist_'.$this->mid, '');

            return array(
                    'status' => 1,
                    'msg'    => '添加成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '添加失败',
            );
        }
    }

    /**
     * 将指定用户移出黑名单 --using.
     *
     * @param int $user_id
     *                     黑名单用户UID
     *
     * @return array 状态+提示
     */
    public function remove_blacklist()
    {
        $uid = intval($this->user_id);

        if (empty($uid)) {
            return array(
                    'status' => 0,
                    'msg'    => '请指定用户',
            );
        }
        if (!D('user_blacklist')->where(array(
                'uid' => $this->mid,
                'fid' => $uid,
        ))->count()) {
            return array(
                    'status' => 0,
                    'msg'    => '用户不在黑名单中',
            );
        }

        $map['uid'] = $this->mid;
        $map['fid'] = $uid;
        if (D('user_blacklist')->where($map)->delete()) {
            model('Cache')->set('u_blacklist_'.$this->mid, '');

            return array(
                    'status' => 1,
                    'msg'    => '移出成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '移出失败',
            );
        }
    }

    /**
     * 上传头像 --using
     * 传�
     * �的头像变量 $_FILES['Filedata'].
     *
     * @return array 状态+提示
     */
    public function upload_avatar()
    {
        $dAvatar = model('Avatar');
        $dAvatar->init($this->mid); // 初始化Model用户id
        $res = $dAvatar->upload(true);
        // Log::write(var_export($res,true));
        if ($res['status'] == 1) {
            model('User')->cleanCache($this->mid);
            $data['picurl'] = $res['data']['picurl'];
            $data['picwidth'] = $res['data']['picwidth'];
            $scaling = 5;
            $data['w'] = $res['data']['picwidth'] * $scaling;
            $data['h'] = $res['data']['picheight'] * $scaling;
            $data['x1'] = $data['y1'] = 0;
            $data['x2'] = $data['w'];
            $data['y2'] = $data['h'];
            $r = $dAvatar->dosave($data);

            return array(
                    'status' => 1,
                    'msg'    => '修改成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '修改失败',
            );
        }
    }

    /**
     * 获取地区 --using.
     *
     * @return array 地区列表
     */
    public function get_area_list()
    {
        $letters = array(
                'A' => array(),
                'B' => array(),
                'C' => array(),
                'D' => array(),
                'E' => array(),
                'F' => array(),
                'G' => array(),
                'H' => array(),
                'I' => array(),
                'J' => array(),
                'K' => array(),
                'L' => array(),
                'M' => array(),
                'N' => array(),
                'O' => array(),
                'P' => array(),
                'Q' => array(),
                'R' => array(),
                'S' => array(),
                'T' => array(),
                'U' => array(),
                'V' => array(),
                'W' => array(),
                'X' => array(),
                'Y' => array(),
                'Z' => array(),
        );
        $provinces = D('area')->where('pid=0')->findAll();
        $map['pid'] = array(
                'in',
                getSubByKey($provinces, 'area_id'),
        );
        $citys = D('area')->where($map)->findAll();
        $map1['pid'] = array(
                'in',
                getSubByKey($citys, 'area_id'),
        );
        $map1['title'] = array(
                'exp',
                'not in("市辖区","县","市","省直辖县级行政单位" ,"省直辖行政单位")',
        );
        $countys = D('area')->where($map1)->findAll(); // 所有的县
        foreach ($countys as $k => $v) {
            $first_letter = getFirstLetter($v['title']);
            $letters[$first_letter][$v['area_id']]['city_id'] = $v['area_id'];
            $letters[$first_letter][$v['area_id']]['city_name'] = $v['title'];
            unset($first_letter);
        }

        return $letters;
    }

    /**
     * 修改用户信息 --using.
     *
     * @param string $uname
     *                             用户名
     * @param int    $sex
     *                             性别(1-男,2-女)
     * @param string $intro
     *                             个人简介
     * @param string $city_id
     *                             地区ID
     * @param string $password
     *                             新密码
     * @param string $old_password
     *                             旧密码
     * @param string $tags
     *                             标签(多个标签之间用逗号隔开)
     */
    public function save_user_info()
    {
        $save = array();
        // 修改用户昵称
        if (isset($this->data['uname'])) {
            $uname = t($this->data['uname']);
            $save['uname'] = filter_keyword($uname);
            $oldName = t($this->data['old_name']);
            $res = model('Register')->isValidName($uname);
            if (!$res) {
                $error = model('Register')->getLastError();

                return array(
                        'status' => 0,
                        'msg'    => $error,
                );
            }
            // 如果包含中文将中文翻译成拼音
            if (preg_match('/[\x7f-\xff]+/', $save['uname'])) {
                // 昵称和呢称拼音保存到搜索字段
                $save['search_key'] = $save['uname'].' '.model('PinYin')->Pinyin($save['uname']);
            } else {
                $save['search_key'] = $save['uname'];
            }
        }
        // 修改性别
        if (isset($this->data['sex'])) {
            $save['sex'] = (1 == intval($this->data['sex'])) ? 1 : 2;
        }
        // 修改个人简介
        if (isset($this->data['intro'])) {
            $save['intro'] = formatEmoji(true, t($this->data['intro']));
        }
        // 修改地区
        if ($this->data['city_id']) {
            $area_id = intval($this->data['city_id']);
            $area = D('area')->where('area_id='.$area_id)->find();
            $city = D('area')->where('area_id='.$area['pid'])->find();
            $province = D('area')->where('area_id='.$city['pid'])->find();
            $save['province'] = intval($province['area_id']);
            $save['city'] = intval($city['area_id']);
            $save['area'] = t($area['area_id']);
            $save['location'] = $province['title'].' '.$city['title'].' '.$area['title'];
        }
        // 修改密码
        if ($this->data['password']) {
            $regmodel = model('Register');
            // 验证格式
            if (!$regmodel->isValidPassword($this->data['password'], $this->data['password'])) {
                $msg = $regmodel->getLastError();
                $return = array(
                        'status' => 0,
                        'msg'    => $msg,
                );

                return $return;
            }
            // 验证新密码与旧密码是否一致
            if ($this->data['password'] == $this->data['old_password']) {
                $return = array(
                        'status' => 0,
                        'msg'    => L('PUBLIC_PASSWORD_SAME'),
                );

                return $return;
            }
            // 验证原密码是否正确
            $user = model('User')->where('`uid`='.$this->mid)->find();
            if (md5(md5($this->data['old_password']).$user['login_salt']) != $user['password']) {
                $return = array(
                        'status' => 0,
                        'msg'    => L('PUBLIC_ORIGINAL_PASSWORD_ERROR'),
                ); // 原始密码错误
                return $return;
            }
            $login_salt = rand(11111, 99999);
            $save['login_salt'] = $login_salt;
            $save['password'] = md5(md5($this->data['password']).$login_salt);
        }

        if (!empty($save)) {
            $res = model('User')->where('`uid`='.$this->mid)->save($save);
            $res !== false && model('User')->cleanCache($this->mid);
            $user_feeds = model('Feed')->where('uid='.$this->mid)->field('feed_id')->findAll();
            if ($user_feeds) {
                $feed_ids = getSubByKey($user_feeds, 'feed_id');
                model('Feed')->cleanCache($feed_ids, $this->mid);
            }
        }
        // 修改用户标签
        if (isset($this->data['tags'])) {
            if (empty($this->data['tags'])) {
                return array(
                        'status' => 0,
                        'msg'    => L('PUBLIC_TAG_NOEMPTY'),
                );
            }
            $nameList = t($this->data['tags']);
            $nameList = explode(',', $nameList);
            $tagIds = array();
            foreach ($nameList as $name) {
                $tagIds[] = model('Tag')->setAppName('public')->setAppTable('user')->getTagId($name);
            }
            $rowId = intval($this->mid);
            if (!empty($rowId)) {
                $registerConfig = model('Xdata')->get('admin_Config:register');
                if (count($tagIds) > $registerConfig['tag_num']) {
                    return array(
                            'status' => 0,
                            'msg'    => '最多只能设置'.$registerConfig['tag_num'].'个标签',
                    );
                }
                model('Tag')->setAppName('public')->setAppTable('user')->updateTagData($rowId, $tagIds);
            }
        }

        return array(
                'status' => 1,
                'msg'    => '修改成功',
        );
    }

    /**
     * 发送短信验证码绑定手机号 --using.
     *
     * @param
     *        	string phone 手机号
     *
     * @return array 状态+提示
     */
    // public function send_bind_code() {
    // 	$phone = t ( $this->data ['phone'] );
    // 	if (! model ( 'Register' )->isValidPhone ( $phone )) {
    // 		return array (
    // 				'status' => 0,
    // 				'msg' => model ( 'Register' )->getLastError ()
    // 		);
    // 	}
    // 	$smsDao = model ( 'Sms' );
    // 	$status = $smsDao->sendLoginCode ( $phone );
    // 	if ($status) {
    // 		$msg = '发送成功！';
    // 	} else {
    // 		$msg = $smsDao->getError ();
    // 	}
    // 	$return = array (
    // 			'status' => intval ( $status ),
    // 			'msg' => $msg
    // 	);
    // 	return $return;
    // }

    /**
     * 发送绑定手机的短信验证码
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function send_bind_code()
    {
        $phone = floatval($this->data['phone']);
        $userPhone = model('User')->where('`uid` = '.intval($this->mid))->field('phone')->getField('phone');
        /* 判断是否传输的不是手机号码 */
        if (!MedzValidator::isTelNumber($phone)) {
            return array(
                'status' => 0,
                'msg'    => '不是正确的手机号码',
            );
            /* # 判断是否已经被使用，排除自己 */
        } elseif (!model('Register')->isValidPhone($phone, $userPhone)) {
            return array(
                'status' => 0,
                'msg'    => model('Register')->getLastError(),
            );

            /* # 判断是否发送验证码失败 */
        } elseif (!model('Sms')->sendCaptcha($phone, true)) {
            return array(
                'status' => 0,
                'msg'    => model('Sms')->getMessage(),
            );
        }

        return array(
            'status' => 1,
            'msg'    => '发送成功！',
        );
    }

    /**
     * 执行绑定手机号 --using.
     *
     * @param
     *        	string phone 手机号
     * @param
     *        	string code 验证码
     *
     * @return array 状态+提示
     */
    public function do_bind_phone()
    {
        $phone = t($this->data['phone']);
        $userPhone = model('User')->where('`uid` = '.intval($this->mid))->field('phone')->getField('phone');
        if (!model('Register')->isValidPhone($phone, $userPhone)) {
            return array(
                    'status' => 0,
                    'msg'    => model('Register')->getLastError(),
            );
        }
        $smsDao = model('Sms');
        $code = t($this->data['code']);
        if (!$smsDao->CheckCaptcha($phone, $code)) {
            return array(
                    'status' => 0,
                    'msg'    => $smsDao->getMessage(),
            );
        }
        $map['uid'] = $this->mid;

        $result = model('User')->where($map)->setField('phone', $phone);
        if ($result !== false) {
            model('User')->cleanCache($this->mid);

            return array(
                    'status' => 1,
                    'msg'    => '绑定成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '绑定失败',
            );
        }
    }

    /**
     * 获取用户隐私设置 --using.
     *
     * @return array 隐私设置信息
     */
    public function user_privacy()
    {
        $user_privacy = model('UserPrivacy')->getUserSet($this->mid);
        $data['message'] = $user_privacy['message'] ? $user_privacy['message'] : 0;
        $data['space'] = $user_privacy['space'] ? $user_privacy['space'] : 0;
        $data['comment_weibo'] = $user_privacy['comment_weibo'] ? $user_privacy['comment_weibo'] : 0;

        return $data;
    }

    /**
     * 保存用户隐私设置 --using.
     *
     * @param
     *        	integer message 私信 0或1
     * @param
     *        	integer comment_weibo 评论微博 0或1
     * @param
     *        	integer space 空间 0或1
     *
     * @return array 状态+提示
     */
    public function save_user_privacy()
    {
        $map['uid'] = $this->mid;
        if (isset($this->data['message'])) {
            $map['key'] = 'message';
            $key = 'message';
            $value = intval($this->data['message']);
            D('user_privacy')->where($map)->delete();
            $map['value'] = $value;
            $res = D('user_privacy')->add($map);
        }
        if (isset($this->data['comment_weibo'])) {
            $map['key'] = 'comment_weibo';
            $key = 'comment_weibo';
            $value = intval($this->data['comment_weibo']);
            D('user_privacy')->where($map)->delete();
            $map['value'] = $value;
            $res = D('user_privacy')->add($map);
        }
        if (isset($this->data['space'])) {
            $map['key'] = 'space';
            $key = 'space';
            $value = intval($this->data['space']);
            D('user_privacy')->where($map)->delete();
            $map['value'] = $value;
            $res = D('user_privacy')->add($map);
        }
        // if($res){
        return array(
                'status' => 1,
                'msg'    => '设置成功',
        );
        // }else{
        // return array('status'=>0,'msg'=>'设置失败');
        // }
    }

    /**
     * �
     * �注一个用户 --using.
     *
     * @param
     *          integer user_id 要�
     * �注的用户ID
     *
     * @return array 状态+提示+�
     * �注状态
     */
    public function follow()
    {
        if (empty($this->mid) || empty($this->user_id)) {
            return array(
                    'status' => 0,
                    'msg'    => '参数错误',
            );
        }
        $uids = explode(',', $this->user_id);
        foreach ($uids as $key => $value) {
            $r = model('Follow')->doFollow($this->mid, $value);
        }

        if ($r) {
            $r['status'] = 1;
            $r['msg'] = '关注成功';

            return $r;
        } else {
            return array(
                    'status' => 0,
                    'msg'    => model('Follow')->getLastError(),
            );
        }
    }

    /**
     * 取消�
     * �注一个用户 --using.
     *
     * @param
     *          integer user_id 要�
     * �注的用户ID
     *
     * @return array 状态+提示+�
     * �注状态
     */
    public function unfollow()
    {
        if (empty($this->mid) || empty($this->user_id)) {
            return array(
                    'status' => 0,
                    'msg'    => '参数错误',
            );
        }
        $uids = explode(',', $this->user_id);
        foreach ($uids as $key => $value) {
            $r = model('Follow')->unFollow($this->mid, $value);
        }
        if ($r) {
            $r['status'] = 1;
            $r['msg'] = '取消成功';

            return $r;
        } else {
            return array(
                    'status' => 0,
                    'msg'    => model('Follow')->getLastError(),
            );
        }
    }

    /**
     * 用户第三方帐号绑定�
     * 况 --using.
     *
     * @return 第三方列表及是否绑定
     */
    public function user_bind()
    {
        // 可同步平台
        $validPublish = array(
                'sina',
                'qq',
                'qzone',
        );
        // 可绑定平台
        $validAlias = array(
                'sina'  => '新浪微博',
                'qzone' => 'QQ互联',
                // 'qq' => '腾讯微博',
                // 'renren' => "人人网",
                // 'douban' => "豆瓣",
                // 'baidu' => "百度",
                // 'taobao' => "淘宝网",
                'weixin' => '微信',
        );
        $bind = M('login')->where('uid='.$this->mid)->findAll(); // 用户已绑定数据
        $config = model('AddonData')->lget('login'); // 检查可同步的平台的key值是否可用
        foreach ($validAlias as $k => $v) {
            // 检查是否在后台config设置好
            if (!in_array($k, $config['open']) && $k != 'weixin') {
                continue;
            }
            if (in_array($k, $validPublish)) {
                $can_sync = true;
            } else {
                $can_sync = false;
            }
            $is_bind = false;
            $is_sync = false;
            foreach ($bind as $value) {
                if ($value['type'] == $k) {
                    $is_bind = true;
                }
                if ($value['type'] == $k && $value['is_sync']) {
                    $is_sync = true;
                }
                if ($value['type'] == $k && $value['bind_time']) {
                    $bind_time = $value['bind_time'];
                }
                if ($value['type'] == $k && $value['bind_user']) {
                    $bind_user = $value['bind_user'];
                }
            }
            $bindInfo[] = array(
                    'type'   => $k,
                    'name'   => $validAlias[$k],
                    'isBind' => $is_bind ? 1 : 0,
            );
        }
        // 手机号
        $tel_bind[0]['type'] = 'phone';
        $tel_bind[0]['name'] = '手机号';
        $login = model('User')->where('uid='.$this->mid)->field('phone')->getField('phone');
        if (MedzValidator::isTelNumber($login)) {
            $tel_bind[0]['isBind'] = 1;
        } else {
            $tel_bind[0]['isBind'] = 0;
        }
        $bindInfo = array_merge($tel_bind, $bindInfo);

        return $bindInfo;
    }

    /**
     * 解绑第三方帐号 --using.
     *
     * @param
     *        	string type 第三方类型
     *
     * @return 状态+提示
     */
    public function unbind()
    {
        $type = t($this->data['type']);
        if ($type == 'phone') {
            // $uname = model ( 'User' )->where ( 'uid=' . $this->mid )->getField ( 'uname' );
            $res = model('User')->where('uid='.$this->mid)->setField('phone', '');
            if ($res !== false) {
                model('User')->cleanCache($this->mid);

                return array(
                        'status' => 1,
                        'msg'    => '解绑成功',
                );
            } else {
                return array(
                        'status' => 0,
                        'msg'    => '解绑失败',
                );
            }
        } else {
            if (D('login')->where("uid={$this->mid} AND type='{$type}'")->delete()) {
                S('user_login_'.$this->mid, null);

                return array(
                        'status' => 1,
                        'msg'    => '解绑成功',
                );
            } else {
                return array(
                        'status' => 0,
                        'msg'    => '解绑失败',
                );
            }
        }
    }

    /**
     * 第三方帐号绑定 --using.
     *
     * @param
     *        	varchar type 帐号类型
     * @param
     *        	varchar type_uid 第三方用户标识
     * @param
     *        	varchar access_token 第三方access token
     * @param
     *        	varchar refresh_token 第三方refresh token（选填，根据第三方返回值）
     * @param
     *        	varchar expire_in 过期时间（选填，根据第三方返回值）
     *
     * @return array 状态+提示
     */
    public function bind_other()
    {
        $type = addslashes($this->data['type']);
        $type_uid = addslashes($this->data['type_uid']);
        $access_token = addslashes($this->data['access_token']);
        $refresh_token = addslashes($this->data['refresh_token']);
        $expire = intval($this->data['expire_in']);
        if (!empty($type) && !empty($type_uid)) {
            $syncdata['uid'] = $this->mid;
            $syncdata['type_uid'] = $type_uid;
            $syncdata['type'] = $type;
            $syncdata['oauth_token'] = $access_token;
            $syncdata['oauth_token_secret'] = $refresh_token;
            $syncdata['is_sync'] = 0;
            S('user_login_'.$this->mid, null);
            if ($info = M('login')->where("type_uid={$type_uid} AND type='".$type."'")->find()) {
                return array(
                        'status' => 0,
                        'msg'    => '该帐号已绑定',
                );
            } else {
                if (M('login')->add($syncdata)) {
                    return array(
                            'status' => 1,
                            'msg'    => '绑定成功',
                    );
                }
            }
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '参数错误',
            );
        }
    }

    /**
     * 设置用户备注.
     *
     * @param
     *          varchar uid 用户ID
     * @param
     *          varchar name 备注名
     *
     * @return array 状态+提示
     */
    public function set_remark()
    {
        $uid = $this->data['uid'];
        $remark = $this->data['remark'];

        //判断长度
        $length = mb_strlen($remark, 'utf-8');
        $res = ($length >= 2 && $length <= 10);
        if (!$res) {
            return array(
                'status' => 0,
                'msg'    => '备注长度必须在2-10个字之间',
            );
        }

        if (!empty($uid) && !empty($remark)) {
            $rm['mid'] = $this->mid;
            $rm['uid'] = $uid;

            $rs = D('UserRemark')->setRemark($uid, $remark);

            if ($rs !== false) {
                return array(
                        'status' => 1,
                        'msg'    => '设置成功',
                );
            } else {
                return array(
                        'status' => 0,
                        'msg'    => '设置失败',
                );
            }
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '参数错误',
            );
        }
    }

    /*
     * ******** 反馈 *********
     */

    /*
     * 获取反馈类型 --using
     *
     * @return array 反馈类型
     */
    // public function get_feedback_type() {
    // 	$feedbacktype = D ( 'feedback_type' )->order ( 'type_id asc' )->findAll ();
    // 	if ($feedbacktype) {
    // 		return $feedbacktype;
    // 	} else {
    // 		return array ();
    // 	}
    // }

    /*
     * 增加反馈 --using
     *
     * @param
     *        	integer type_id 反馈类型ID
     * @param
     *        	string content 反馈内容
     * @return 状态+提示
     */
    // public function add_feedback() {
    // 	$map ['feedbacktype'] = intval ( $this->data ['type_id'] );
    // 	if (! $map ['feedbacktype'])
    // 		return array (
    // 				'status' => 0,
    // 				'msg' => '请选择反馈类型'
    // 		);
    // 	$map ['feedback'] = t ( $this->data ['content'] );
    // 	if (! $map ['feedback'])
    // 		return array (
    // 				'status' => 0,
    // 				'msg' => '请输入反馈内容'
    // 		);
    // 	$map ['uid'] = $this->mid;
    // 	$map ['cTime'] = time ();
    // 	$map ['type'] = 0;
    // 	$res = model ( 'Feedback' )->add ( $map );
    // 	if ($res) {
    // 		return array (
    // 				'status' => 1,
    // 				'msg' => '反馈成功'
    // 		);
    // 	} else {
    // 		return array (
    // 				'status' => 0,
    // 				'msg' => '反馈失败'
    // 		);
    // 	}
    // }
}
