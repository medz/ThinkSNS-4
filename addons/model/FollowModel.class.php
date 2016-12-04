<?php
/**
 * 用户关注模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version 1.0
 */
class FollowModel extends Model
{
    protected $tableName = 'user_follow';
    protected $fields = array(
            0 => 'follow_id',
            1 => 'uid',
            2 => 'fid',
            3 => 'remark',
            4 => 'ctime',
            '_autoinc' => true,
            '_pk' => 'follow_id',
    );

    /**
     * 获取关注查询SQL语句，具体使用不清楚
     *
     * @param int $uid
     *                 用户ID
     * @return string 关注查询SQL语句
     */
    public function getFollowingSql($uid)
    {
        return 'SELECT `fid` FROM '.$this->getTableName().' WHERE `uid` = '.intval($uid);
        // return "SELECT `fid` FROM {$this->tablePrefix}user_follow WHERE `uid` = '{$uid}'";
    }

    /**
     * 获取指定用户的备注列表
     *
     * @param  int   $uid
     *                    用户ID
     * @return array 指定用户的备注列表
     */
    public function getRemarkHash($uid)
    {
        if (empty($uid)) {
            return false;
        }
        if (false !== ($list = S('follow_remark_'.$uid))) {
            return $list;
        }
        $map ['uid'] = $uid;
        // $map['remark'] = array('NEQ', '');//加了效率低,不加数据大
        $list = $this->where($map)->getHashList('fid', 'remark');
        // 全查出来，再清除空的数据，这样效率高一些，只要关注数据不太大
        foreach ($list as $k => $v) {
            if ($v ['remark'] == '') {
                unset($list [$k]);
            }
        }
        S('follow_remark_'.$uid, $list);

        return $list;
    }

    /**
     * 添加关注 (关注用户)
     *
     * @example null：参数错误
     *          11：已关注
     *          12：关注成功(且为单向关注)
     *          13：关注成功(且为互粉)
     * @param int $uid
     *                 发起操作的用户ID
     * @param int $fid
     *                   被关注的用户ID或被关注的话题ID
     * @return bool 是否关注成功
     */
    public function doFollow($uid, $fid)
    {
        if (intval($uid) <= 0 || $fid <= 0) {
            $this->error = L('PUBLIC_WRONG_DATA'); // 错误的参数
            return false;
        }

        if ($uid == $fid) {
            $this->error = L('PUBLIC_FOLLOWING_MYSELF_FORBIDDEN'); // 不能关注自己
            return false;
        }

        if (! model('User')->find($fid)) {
            $this->error = L('PUBLIC_FOLLOWING_PEOPLE_NOEXIST'); // 被关注的用户不存在
            return false;
        }

        if (model('UserPrivacy')->isInBlackList($uid, $fid)) {
            $this->error = '根据对方设置，您无法关注TA';

            return false;
        } elseif (model('UserPrivacy')->isInBlackList($fid, $uid)) {
            $this->error = '您已把对方加入黑名单';

            return false;
        }
        // 维护感兴趣的人的缓存
        model('Cache')->set('related_user_'.$uid, '', 24 * 60 * 60);
        // 获取双方的关注关系
        $follow_state = $this->getFollowState($uid, $fid);
        // 未关注状态
        if (0 == $follow_state ['following']) {
            // 添加关注
            $map ['uid'] = $uid;
            $map ['fid'] = $fid;
            $map ['ctime'] = time();
            $map['remark'] = '';
            $result = $this->add($map);
            // 通知和分享
            $config ['uname'] = getUserName($uid);
            $config ['space_url'] = U('public/Profile/index', array(
                    'uid' => $uid,
            ));
            $config ['following_url'] = U('public/Profile/following');
            model('Notify')->send($fid, 'user_follow', $config);
            // model('Feed')->put('user_follow', array('fid'=>$fid), $uid);
            S('follow_remark_'.$uid, null);
            if ($result) {
                $maps ['key'] = 'email';
                $maps ['uid'] = $fid;
                $isEmail = D('user_privacy')->where($map)->field('value')->find();
                if ($isEmail ['value'] === 0) {
                    $userInfo = model('User')->getUserInfo($fid);
                    model('Mail')->send_email($userInfo ['email'], '您增加了一个新粉丝', 'content');
                }
                $this->error = L('PUBLIC_ADD_FOLLOW_SUCCESS'); // 关注成功
                $this->_updateFollowCount($uid, $fid, true); // 更新统计
                $follow_state ['following'] = 1;

                return $follow_state;
            } else {
                $this->error = L('PUBLIC_ADD_FOLLOW_FAIL'); // 关注失败
                return false;
            }
        } else {
            $this->error = L('PUBLIC_FOLLOW_ING'); // 已关注
            return false;
        }
    }
    public function bulkDoFollow($uid, $fids)
    {
        $follow_states = $this->getFollowStateByFids($uid, $fids);
        $data = array();
        $_fids = array();
        foreach ($follow_states as $f_s_k => $f_s_v) {
            // 未关注
            if (0 == $f_s_v ['following']) {
                // 关注的字段数据
                $data [] = "({$uid}, {$f_s_k},".time().')';
                $_fids [] = $f_s_k;
                $follow_states [$f_s_k] ['following'] = 1;
                // 通知和分享
                /*
                 * model('Notify')->send($fid, 'user_follow', '', $uid);
                 * model('Feed')->put('user_follow', array('fid'=>$fid), $uid);
                 */
            } else {
                unset($follow_states [$f_s_k]);
            }
        }
        if (! empty($data)) {
            $sql = "INSERT INTO {$this->tablePrefix}{$this->tableName}(`uid`,`fid`,`ctime`) VALUES".implode(',', $data);
            $res = $this->execute($sql);
            if ($res) {
                // 关注成功
                $this->error = L('PUBLIC_ADD_FOLLOW_SUCCESS');

                // 更新统计
                $this->_updateFollowCount($uid, $_fids, true);

                return $follow_states;
            } else {
                $this->error = L('PUBLIC_ADD_FOLLOW_FAIL');

                return false;
            }
        } else {
            // 全部已关注
            $this->error = L('PUBLIC_FOLLOW_ING');

            return false;
        }
    }

    /**
     * 双向关注用户操作
     *
     * @param int   $uid
     *                    用户ID
     * @param array $fids
     *                     需关注用户ID数组
     * @return bool  是否双向关注成功
     */
    public function eachDoFollow($uid, $fids)
    {
        // 获取用户关组状态
        $followStates = $this->getFollowStateByFids($uid, $fids);
        $data = array();
        $_following = array();
        $_follower = array();

        foreach ($followStates as $key => $value) {
            if (0 == $value ['following']) {
                $data [] = "({$uid}, {$key}, ".time().')';
                $_following [] = $key;
            }
            if (0 == $value ['follower']) {
                $data [] = "({$key}, {$uid}, ".time().')';
                $_follower [] = $key;
            }
        }
        // 处理数据结果
        if (! empty($data)) {
            $sql = "INSERT INTO {$this->tablePrefix}{$this->tableName}(`uid`,`fid`,`ctime`) VALUES ".implode(',', $data);
            $res = $this->execute($sql);
            if ($res) {
                // 关注成功
                $this->error = L('PUBLIC_ADD_FOLLOW_SUCCESS');

                // 被关注人的关注人数+1
                foreach ($_follower as $fo) {
                    model('UserData')->setUid($fo)->updateKey('following_count', 1, true);
                }
                // 更新被关注人的粉丝数统计
                $this->_updateFollowCount($uid, $_following, true);
                // 更新关注人的粉丝数
                model('UserData')->setUid($uid)->updateKey('follower_count', count($_follower), true);

                return true;
            } else {
                $this->error = L('PUBLIC_ADD_FOLLOW_FAIL');

                return false;
            }
        } else {
            // 已经全部关注
            $this->error = L('PUBLIC_FOLLOW_ING');

            return false;
        }
    }

    /**
     * 取消关注（关注用户 / 关注话题）
     *
     * @example 00：取消失败
     *          01：取消成功
     * @param int $uid
     *                 发起操作的用户ID
     * @param int $fid
     *                   被取消关注的用户ID或被取消关注的话题ID
     * @return bool 是否取消关注成功
     */
    public function unFollow($uid, $fid)
    {
        $map ['uid'] = $uid;
        $map ['fid'] = $fid;
        // 获取双方的关注关系
        $follow_state = $this->getFollowState($uid, $fid);
        if (1 == $follow_state ['following']) {
            // 已关注
            // 清除对该用户的分组，再删除关注
            if ((false !== D('UserFollowGroupLink')->where($map)->delete()) && $this->where($map)->delete()) {
                // D('UserFollowGroupLink')->where($map)->delete();
                S('follow_remark_'.$uid, null);
                $this->error = L('PUBLIC_ADMIN_OPRETING_SUCCESS'); // 操作成功
                $this->_updateFollowCount($uid, $fid, false); // 更新统计
                $follow_state ['following'] = 0;

                return $follow_state;
            } else {
                $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR'); // 操作失败
                return false;
            }
        } else {
            // 未关注
            $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR'); // 操作失败
            return false;
        }
    }

    /**
     * 获取指定用户的关注与粉丝数
     *
     * @param array $uids
     *                    用户ID数组
     * @return array 指定用户的关注与粉丝数
     */
    public function getFollowCount($uids)
    {
        $count = array();
        foreach ($uids as $u_v) {
            $count [$u_v] = array(
                    'following' => 0,
                    'follower' => 0,
            );
        }

        $following_map ['uid'] = $follower_map ['fid'] = array(
                'IN',
                $uids,
        );
        // 关注数
        $following = $this->field('COUNT(1) AS `count`,`uid`')->where($following_map)->group('`uid`')->findAll();
        foreach ($following as $v) {
            $count [$v ['uid']] ['following'] = $v ['count'];
        }
        // 粉丝数
        $follower = $this->field('COUNT(1) AS `count`,`fid`')->where($follower_map)->group('`fid`')->findAll();
        foreach ($follower as $v) {
            $count [$v ['fid']] ['follower'] = $v ['count'];
        }

        return $count;
    }

    /**
     * 获取指定用户的关注列表 分页
     *
     * @param int $uid
     *                 用户ID
     * @param int $gid
     *                      关注组ID，默认为空
     * @param int $limit
     *                   结果集数目，默认为10
     * @return array 指定用户的关注列表
     */
    public function getFollowingList($uid, $gid = null, $limit = 10)
    {
        $limit = intval($limit) > 0 ? $limit : 10;
        if (is_numeric($gid)) {
            // 关组分组
            if ($gid == 0) {
                // $list = $this->table("{$this->tablePrefix}{$this->tableName} AS follow LEFT JOIN {$this->tablePrefix}user_follow_group_link AS link ON link.follow_id = follow.follow_id")
                // ->field('follow.*')
                // ->where("follow.uid={$uid} AND link.follow_id IS NULL")
                // ->order('follow.uid DESC')
                // ->findPage($limit);
                $list = $this->where("`uid`={$uid}")->order('`follow_id` DESC')->findPage($limit);
            } elseif ($gid == - 1) {
                $list = $this->table('`ts_user_follow` a, ts_user_follow b')->where("a.uid={$uid} AND b.fid={$uid} AND a.fid=b.uid")->order('`follow_id` DESC')->field('a.*')->findPage($limit);
            } elseif ($gid == - 2) {
                $map ['uid'] = $uid;
                $fids = M('user_follow_group_link')->where($map)->field('DISTINCT fid')->findAll();
                $fids = getSubByKey($fids, 'fid');
                if (! empty($fids)) {
                    $map ['fid'] = array(
                            'not in',
                            $fids,
                    );
                }
                $list = $this->where($map)->order('`follow_id` DESC')->findPage($limit);
            } else {
                $list = $this->field('follow.*')->table("{$this->tablePrefix}user_follow_group_link AS link LEFT JOIN {$this->tablePrefix}{$this->tableName} AS follow ON link.follow_id=follow.follow_id AND link.uid=follow.uid")->where("follow.uid={$uid} AND link.follow_group_id={$gid}")->order('follow.uid DESC')->findPage($limit);
            }
        } else {
            // 没有指定关组分组的列表
            $list = $this->where("`uid`={$uid}")->order('`follow_id` DESC')->findPage($limit);
        }

        return $list;
    }

    /**
     * 获取指定用户的关注列表 不分页
     *
     * @param int $uid
     *                 用户ID
     * @param int $gid
     *                      关注组ID，默认为空
     * @param int $limit
     *                   结果集数目，默认为10
     * @return array 指定用户的关注列表
     */
    public function getFollowingListAll($uid, $gid = null)
    {
        if (is_numeric($gid)) {
            // 关组分组
            if ($gid == 0) {
                $list = $this->table("{$this->tablePrefix}{$this->tableName} AS follow LEFT JOIN {$this->tablePrefix}user_follow_group_link AS link ON link.follow_id = follow.follow_id")->field('follow.*')->where("follow.uid={$uid} AND link.follow_id IS NULL")->order('follow.uid DESC')->findAll();
            } else {
                $list = $this->field('follow.*')->table("{$this->tablePrefix}user_follow_group_link AS link LEFT JOIN {$this->tablePrefix}{$this->tableName} AS follow ON link.follow_id=follow.follow_id AND link.uid=follow.uid")->where("follow.uid={$uid} AND link.follow_group_id={$gid}")->order('follow.uid DESC')->findAll();
            }
        } else {
            // 没有指定关组分组的列表
            $list = $this->where("`uid`={$uid}")->order('`follow_id` DESC')->findAll();
        }

        return $list;
    }

    /**
     * 获取指定用户的粉丝列表
     *
     * @param  int   $uid
     *                      用户ID
     * @param  int   $limit
     *                      结果集数目，默认为10
     * @return array 指定用户的粉丝列表
     */
    public function getFollowerList($uid, $limit = 10)
    {
        $limit = intval($limit) > 0 ? $limit : 10;
        // 粉丝列表
        $list = $this->where("`fid`={$uid}")->order('`follow_id` DESC')->findPage($limit);
        $fids = getSubByKey($list ['data'], 'uid');
        // 格式化数据
        foreach ($list ['data'] as $key => $value) {
            $uid = $value ['uid'];
            $fid = $value ['fid'];
            $list ['data'] [$key] ['uid'] = $fid;
            $list ['data'] [$key] ['fid'] = $uid;
        }

        return $list;
    }

    /**
     * 获取用户uid与用户fid的关注状态，已uid为主
     *
     * @param int $uid
     *                 用户ID
     * @param int $fid
     *                 用户ID
     * @return int 用户关注状态，格式为array('following'=>1,'follower'=>1)
     */
    public function getFollowState($uid, $fid)
    {
        $follow_state = $this->getFollowStateByFids($uid, $fid);

        return $follow_state [$fid];
    }

    /**
     * 批量获取用户uid与一群人fids的彼此关注状态
     *
     * @param int   $uid
     *                    用户ID
     * @param array $fids
     *                    用户ID数组
     * @return array 用户uid与一群人fids的彼此关注状态
     */
    public function getFollowStateByFids($uid, $fids)
    {
        if (is_string($fids)) {
            $fids = explode(',', $fids);
        }
        $fids = (array) $fids;

        foreach ($fids as $key => $value) {
            $fids[$key] = intval($value);
        }

        $_fids = implode(',', $fids);
        $uid = intval($uid);

        $follow_data = $this->where(" ( uid = '{$uid}' AND fid IN({$_fids}) ) OR ( uid IN({$_fids}) and fid = '{$uid}')")->findAll();
        $follow_states = $this->_formatFollowState($uid, $fids, $follow_data);

        return $follow_states [$uid];
    }

    /**
     * 获取朋友列表数据 - 分页
     *
     * @param  int   $uid
     *                    用户ID
     * @return array 朋友列表数据
     */
    public function getFriendsList($uid)
    {
        $data = D()->table('`'.$this->tablePrefix.'user_follow` AS a LEFT JOIN `'.$this->tablePrefix.'user_follow` AS b ON a.uid = b.fid AND b.uid = a.fid')->field('a.fid')->where('a.uid = '.$uid.' AND b.uid IS NOT NULL')->order('a.follow_id DESC')->findPage();

        return $data;
    }

    /**
     * 获取朋友列表数据 - 不分页
     *
     * @param  int   $uid
     *                    用户ID
     * @return array 朋友列表数据
     */
    public function getFriendsData($uid)
    {
        $data = D()->table('`'.$this->tablePrefix.'user_follow` AS a LEFT JOIN `'.$this->tablePrefix.'user_follow` AS b ON a.uid = b.fid AND b.uid = a.fid')->field('a.fid')->where('a.uid = '.$uid.' AND b.uid IS NOT NULL')->findAll();

        return $data;
    }

    /**
     * 获取所有关注用户数据
     *
     * @param int $uid
     *                 用户ID
     * @return array 所有关注用户数据
     */
    public function getFollowingsList($uid)
    {
        $data = $this->field('fid')->where('uid='.$uid)->order('follow_id DESC')->findPage();

        return $data;
    }

    /**
     * 格式化，用户的关注数据
     *
     * @param int   $uid
     *                           用户ID
     * @param array $fids
     *                           用户ID数组
     * @param array $follow_data
     *                            关注状态数据
     * @return array 格式化后的用户关注状态数据
     */
    private function _formatFollowState($uid, $fids, $follow_data)
    {
        ! is_array($fids) && $fids = explode(',', $fids);
        foreach ($fids as $fid) {
            $follow_states [$uid] [$fid] = array(
                    'following' => 0,
                    'follower' => 0,
            );
        }
        foreach ($follow_data as $r_v) {
            if ($r_v ['uid'] == $uid) {
                $follow_states [$r_v ['uid']] [$r_v ['fid']] ['following'] = 1;
            } elseif ($r_v ['fid'] == $uid) {
                $follow_states [$r_v ['fid']] [$r_v ['uid']] ['follower'] = 1;
            }
        }

        return $follow_states;
    }

    /**
     * 更新关注数目
     *
     * @param int   $uid
     *                    操作用户ID
     * @param array $fids
     *                    被操作用户ID数组
     * @param bool  $inc
     *                    是否为加数据，默认为true
     */
    private function _updateFollowCount($uid, $fids, $inc = true)
    {
        ! is_array($fids) && $fids = explode(',', $fids);
        $data_model = model('UserData');
        // 添加关注数
        $data_model->setUid($uid)->updateKey('following_count', count($fids), $inc);
        foreach ($fids as $f_v) {
            // 添加粉丝数
            $data_model->setUid($f_v)->updateKey('follower_count', 1, $inc);
            $data_model->setUid($f_v)->updateKey('new_folower_count', 1, $inc);
        }
    }

    /**
     * * API使用 **
     */
    /**
     * 获取指定用户粉丝列表，API使用
     *
     * @param  int   $mid
     *                         当前登录用户ID
     * @param  int   $uid
     *                         指定用户ID
     * @param  int   $since_id
     *                         主键起始ID，默认为0
     * @param  int   $max_id
     *                         主键最大ID，默认为0
     * @param  int   $limit
     *                         结果集数目，默认为20
     * @param  int   $page
     *                         页数ID，默认为1
     * @return array 指定用户的粉丝列表数据
     */
    public function getFollowerListForApi($mid, $uid, $since_id = 0, $max_id = 0, $limit = 20, $page = 1)
    {
        $uid = intval($uid);
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = " fid = '{$uid}'";
        if (! empty($since_id) || ! empty($max_id)) {
            ! empty($since_id) && $where .= " AND follow_id > {$since_id}";
            ! empty($max_id) && $where .= " AND follow_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $list = $this->where($where)->limit("{$start},{$end}")->order('follow_id DESC')->findAll();
        if (empty($list)) {
            return array();
        } else {
            $r = array();
            foreach ($list as $key => $value) {
                $uid = $value ['uid'];
                $fid = $value ['fid'];
                $r [$key] = model('User')->formatForApi($value, $uid, $mid);
                unset($r [$key] ['fid']);
            }

            return $r;
        }
    }

    /**
     * 获取指定用户关注列表，API使用
     *
     * @param int $mid
     *                      当前登录用户ID
     * @param int $uid
     *                      指定用户ID
     * @param int $since_id
     *                      主键起始ID，默认为0
     * @param int $max_id
     *                      主键最大ID，默认为0
     * @param int $limit
     *                      结果集数目，默认为20
     * @param int $page
     *                      页数ID，默认为1
     * @return array 指定用户的关注列表数据
     */
    public function getFollowingListForApi($mid, $uid, $since_id = 0, $max_id = 0, $limit = 20, $page = 1)
    {
        $uid = intval($uid);
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = " uid = '{$uid}'";
        if (! empty($since_id) || ! empty($max_id)) {
            ! empty($since_id) && $where .= " AND follow_id > {$since_id}";
            ! empty($max_id) && $where .= " AND follow_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $list = $this->where($where)->limit("{$start},{$end}")->order('follow_id DESC')->findAll();
        if (empty($list)) {
            return array();
        } else {
            $r = array();
            foreach ($list as $key => $value) {
                $uid = $value ['fid'];
                $value ['uid'] = $uid;
                $r [$key] = model('User')->formatForApi($value, $uid, $mid);
                unset($r [$key] ['fid']);
            }

            return $r;
        }
    }

    /**
     * 获取指定用户的朋友列表，API专用
     *
     * @param  int   $mid
     *                         当前登录用户ID
     * @param  int   $uid
     *                         指定用户ID
     * @param  int   $since_id
     *                         主键起始ID，默认为0
     * @param  int   $max_id
     *                         主键最大ID，默认为0
     * @param  int   $limit
     *                         结果集数目，默认为20
     * @param  int   $page
     *                         页数ID，默认为1
     * @return array 指定用户的朋友列表
     */
    public function getFriendsForApi($mid, $uid, $since_id = 0, $max_id = 0, $limit = 20, $page = 1)
    {
        $uid = intval($uid);
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = " a.uid = '{$uid}' AND b.uid IS NOT NULL";
        if (! empty($since_id) || ! empty($max_id)) {
            ! empty($since_id) && $where .= " AND a.follow_id > {$since_id}";
            ! empty($max_id) && $where .= " AND a.follow_id > {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $list = D()->table('`'.$this->tablePrefix.'user_follow` AS a LEFT JOIN `'.$this->tablePrefix.'user_follow` AS b ON a.uid = b.fid AND b.uid = a.fid')->field('a.fid, a.follow_id')->where($where)->limit("{$start}, {$end}")->order('a.follow_id DESC')->findAll();

        if (empty($list)) {
            return array();
        } else {
            $r = array();
            foreach ($list as $key => $value) {
                $uid = $value ['fid'];
                $value ['uid'] = $uid;
                $r [$key] = model('User')->formatForApi($value, $uid, $mid);
                unset($r [$key] ['fid']);
            }

            return $r;
        }
    }

    // ***************************************************ts2.XX 应用移动新增函数
    public function getfollowList($uid)
    {
        $list = $this->field('fid')->where("uid=$uid AND type=0")->findall();

        return $list;
    }

    /**
     * 数据库搜索关注用户
     *
     * @param string $key
     *                       关键字
     * @param string $type
     *                       关键字，following,follower
     * @param  int   $limit
     *                      结果集数目
     * @param  int   $uid
     *                      指定用户UID
     * @param  int   $gid
     *                      分组ID
     * @return array 搜索后的数据
     */
    public function searchFollows($key, $type, $limit = 20, $uid, $gid)
    {
        // 数据参数不正确
        if ($key === '' || empty($uid)) {
            return array();
        }
        // 获取查询数据
        if ($type === 'following') {
            $map ['a.`uid`'] = $uid;
            $map ['b.`uname`'] = array(
                    'LIKE',
                    '%'.$key.'%',
            );
            $gid = empty($gid) ? 0 : intval($gid);
            switch ($gid) {
                case 0:
                    $list = D()->table('`'.C('DB_PREFIX').'user_follow` AS a LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`fid` = b.`uid`')->field('a.`fid`')->where($map)->findPage($limit);
                    break;
                case - 1:
                    $map ['c.`uid`'] = array(
                            'EXP',
                            'IS NOT NULL',
                    );
                    $list = D()->table('`'.C('DB_PREFIX').'user_follow` AS a LEFT JOIN `'.C('DB_PREFIX').'user_follow` AS c ON a.`uid` = c.`fid` AND c.`uid` = a.`fid` LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`fid` = b.`uid`')->field('a.fid')->where($map)->order('a.follow_id DESC')->findPage($limit);
                    break;
                case - 2:
                    $uid = intval($uid);
                    $map ['c.`fid`'] = array(
                            'EXP',
                            'IS NULL',
                    );
                    $list = $this->table('`'.C('DB_PREFIX').'user_follow` AS a LEFT JOIN `'.C('DB_PREFIX').'user_follow_group_link` AS c ON a.`fid` = c.`fid` LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`fid` = b.`uid`')->field('a.fid')->where($map)->order('a.follow_id DESC')->findPage($limit);
                    break;
                default:
                    $map ['c.`follow_group_id`'] = $gid;
                    $list = $this->table('`'.C('DB_PREFIX').'user_follow` AS a LEFT JOIN `'.C('DB_PREFIX').'user_follow_group_link` AS c ON a.follow_id=c.follow_id AND a.uid=c.uid LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`fid` = b.`uid`')->field('a.fid')->where($map)->order('a.follow_id DESC')->findPage($limit);
            }
        } elseif ($type === 'follower') {
            $map ['a.`fid`'] = $uid;
            $map ['b.`uname`'] = array(
                    'LIKE',
                    '%'.$key.'%',
            );
            $list = D()->table('`'.C('DB_PREFIX').'user_follow` AS a LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`uid` = b.`uid`')->field('a.`uid` AS `fid`')->where($map)->findPage($limit);
        }

        return $list;
    }

    /**
     * 获取最后的错误信息
     *
     * @return string 最后的错误信息
     */
    public function getLastError()
    {
        return $this->error;
    }
}
