<?php
/**
 * 用户联盟模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version 1.0
 */
class UnionModel extends Model
{
    protected $tableName = 'user_union';
    protected $fields = array(
            0 => 'union_id',
            1 => 'uid',
            2 => 'fid',
            3 => 'remark',
            4 => 'ctime',
            '_autoinc' => true,
            '_pk' => 'union_id',
    );

    /**
     * 获取联盟查询SQL语句，具体使用不清楚
     *
     * @param  int    $uid
     *                     用户ID
     * @return string 联盟查询SQL语句
     */
    public function getUnioningSql($uid)
    {
        return "SELECT `fid` FROM {$this->tablePrefix}user_union WHERE `uid` = '{$uid}'";
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
        if (false !== ($list = S('union_remark_'.$uid))) {
            return $list;
        }
        $map ['uid'] = $uid;
        // $map['remark'] = array('NEQ', '');//加了效率低,不加数据大
        $list = $this->where($map)->getHashList('fid', 'remark');
        // 全查出来，再清除空的数据，这样效率高一些，只要联盟数据不太大
        foreach ($list as $k => $v) {
            if ($v ['remark'] == '') {
                unset($list [$k]);
            }
        }
        S('union_remark_'.$uid, $list);

        return $list;
    }

    /**
     * 添加联盟 (联盟用户)
     *
     * @example null：参数错误
     *          11：已联盟
     *          12：联盟成功(且为单向联盟)
     *          13：联盟成功(且为互粉)
     * @param  int  $uid
     *                   发起操作的用户ID
     * @param  int  $fid
     *                   被联盟的用户ID或被联盟的话题ID
     * @return bool 是否联盟成功
     */
    public function doUnion($uid, $fid)
    {
        if (intval($uid) <= 0 || $fid <= 0) {
            $this->error = L('PUBLIC_WRONG_DATA'); // 错误的参数
            return false;
        }

        if ($uid == $fid) {
            $this->error = L('PUBLIC_FOLLOWING_MYSELF_FORBIDDEN'); // 不能联盟自己
            return false;
        }

        if (! model('User')->find($fid)) {
            $this->error = L('PUBLIC_FOLLOWING_PEOPLE_NOEXIST'); // 被联盟的用户不存在
            return false;
        }

        if (model('UserPrivacy')->isInBlackList($uid, $fid)) {
            $this->error = '根据对方设置，您无法联盟TA';

            return false;
        } elseif (model('UserPrivacy')->isInBlackList($fid, $uid)) {
            $this->error = '您已把对方加入黑名单';

            return false;
        }
        // 维护感兴趣的人的缓存
        model('Cache')->set('related_user_'.$uid, '', 24 * 60 * 60);
        // 获取双方的联盟关系
        $union_state = $this->getUnionState($uid, $fid);
        // 未联盟状态
        if (0 == $union_state ['unioning']) {
            // 添加联盟
            $map ['uid'] = $uid;
            $map ['fid'] = $fid;
            $map ['ctime'] = time();
            $result = $this->add($map);
            // 通知和分享
            $config ['uname'] = getUserName($uid);
            $config ['space_url'] = U('public/Profile/index', array(
                    'uid' => $uid,
            ));
            $config ['unioning_url'] = U('public/Index/unioning');
            model('Notify')->send($fid, 'user_union', $config);
            // model('Feed')->put('user_union', array('fid'=>$fid), $uid);
            S('union_remark_'.$uid, null);
            if ($result) {
                $maps ['key'] = 'email';
                $maps ['uid'] = $fid;
                $isEmail = D('user_privacy')->where($map)->field('value')->find();
                if ($isEmail ['value'] === 0) {
                    $userInfo = model('User')->getUserInfo($fid);
                    model('Mail')->send_email($userInfo ['email'], '您增加了一个新粉丝', 'content');
                }
                $this->error = L('PUBLIC_ADD_FOLLOW_SUCCESS'); // 联盟成功
                $this->_updateUnionCount($uid, $fid, true); // 更新统计
                $union_state ['unioning'] = 1;

                return $union_state;
            } else {
                $this->error = L('PUBLIC_ADD_FOLLOW_FAIL'); // 联盟失败
                return false;
            }
        } else {
            $this->error = L('PUBLIC_FOLLOW_ING'); // 已联盟
            return false;
        }
    }
    public function bulkDoUnion($uid, $fids)
    {
        $union_states = $this->getUnionStateByFids($uid, $fids);
        $data = array();
        $_fids = array();
        foreach ($union_states as $f_s_k => $f_s_v) {
            // 未联盟
            if (0 == $f_s_v ['unioning']) {
                // 联盟的字段数据
                $data [] = "({$uid}, {$f_s_k},".time().')';
                $_fids [] = $f_s_k;
                $union_states [$f_s_k] ['unioning'] = 1;
                // 通知和分享
                /*
                 * model('Notify')->send($fid, 'user_union', '', $uid); model('Feed')->put('user_union', array('fid'=>$fid), $uid);
                 */
            } else {
                unset($union_states [$f_s_k]);
            }
        }
        if (! empty($data)) {
            $sql = "INSERT INTO {$this->tablePrefix}{$this->tableName}(`uid`,`fid`,`ctime`) VALUES".implode(',', $data);
            $res = $this->execute($sql);
            if ($res) {
                // 联盟成功
                $this->error = L('PUBLIC_ADD_FOLLOW_SUCCESS');

                // 更新统计
                $this->_updateUnionCount($uid, $_fids, true);

                return $union_states;
            } else {
                $this->error = L('PUBLIC_ADD_FOLLOW_FAIL');

                return false;
            }
        } else {
            // 全部已联盟
            $this->error = L('PUBLIC_FOLLOW_ING');

            return false;
        }
    }

    /**
     * 双向联盟用户操作
     *
     * @param  int   $uid
     *                     用户ID
     * @param  array $fids
     *                     需联盟用户ID数组
     * @return bool  是否双向联盟成功
     */
    public function eachDoUnion($uid, $fids)
    {
        // 获取用户关组状态
        $unionStates = $this->getUnionStateByFids($uid, $fids);
        $data = array();
        $_unioning = array();
        $_unioner = array();

        foreach ($unionStates as $key => $value) {
            if (0 == $value ['unioning']) {
                $data [] = "({$uid}, {$key}, ".time().')';
                $_unioning [] = $key;
            }
            if (0 == $value ['unioner']) {
                $data [] = "({$key}, {$uid}, ".time().')';
                $_unioner [] = $key;
            }
        }
        // 处理数据结果
        if (! empty($data)) {
            $sql = "INSERT INTO {$this->tablePrefix}{$this->tableName}(`uid`,`fid`,`ctime`) VALUES ".implode(',', $data);
            $res = $this->execute($sql);
            if ($res) {
                // 联盟成功
                $this->error = L('PUBLIC_ADD_FOLLOW_SUCCESS');

                // 被联盟人的联盟人数+1
                foreach ($_unioner as $fo) {
                    model('UserData')->setUid($fo)->updateKey('unioning_count', 1, true);
                }
                // 更新被联盟人的粉丝数统计
                $this->_updateUnionCount($uid, $_unioning, true);
                // 更新联盟人的粉丝数
                model('UserData')->setUid($uid)->updateKey('unioner_count', count($_unioner), true);

                return true;
            } else {
                $this->error = L('PUBLIC_ADD_FOLLOW_FAIL');

                return false;
            }
        } else {
            // 已经全部联盟
            $this->error = L('PUBLIC_FOLLOW_ING');

            return false;
        }
    }

    /**
     * 取消联盟（联盟用户 / 联盟话题）
     *
     * @example 00：取消失败
     *          01：取消成功
     * @param  int  $uid
     *                   发起操作的用户ID
     * @param  int  $fid
     *                   被取消联盟的用户ID或被取消联盟的话题ID
     * @return bool 是否取消联盟成功
     */
    public function unUnion($uid, $fid)
    {
        $map ['uid'] = $uid;
        $map ['fid'] = $fid;
        // 获取双方的联盟关系
        $union_state = $this->getUnionState($uid, $fid);
        if (1 == $union_state ['unioning']) {
            // 已联盟
            // 清除对该用户的分组，再删除联盟
            if (false !== $this->where($map)->delete()) {
                S('union_remark_'.$uid, null);
                $this->error = L('PUBLIC_ADMIN_OPRETING_SUCCESS'); // 操作成功
                $this->_updateUnionCount($uid, $fid, false); // 更新统计
                $union_state ['unioning'] = 0;

                return $union_state;
            } else {
                $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR'); // 操作失败
                return false;
            }
        } else {
            // 未联盟
            $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR'); // 操作失败
            return false;
        }
    }

    /**
     * 获取指定用户的联盟与粉丝数
     *
     * @param  array $uids
     *                     用户ID数组
     * @return array 指定用户的联盟与粉丝数
     */
    public function getUnionCount($uids)
    {
        $count = array();
        foreach ($uids as $u_v) {
            $count [$u_v] = array(
                    'unioning' => 0,
                    'unioner' => 0,
            );
        }

        $unioning_map ['uid'] = $unioner_map ['fid'] = array(
                'IN',
                $uids,
        );
        // 联盟数
        $unioning = $this->field('COUNT(1) AS `count`,`uid`')->where($unioning_map)->group('`uid`')->findAll();
        foreach ($unioning as $v) {
            $count [$v ['uid']] ['unioning'] = $v ['count'];
        }
        // 粉丝数
        $unioner = $this->field('COUNT(1) AS `count`,`fid`')->where($unioner_map)->group('`fid`')->findAll();
        foreach ($unioner as $v) {
            $count [$v ['fid']] ['unioner'] = $v ['count'];
        }

        return $count;
    }

    /**
     * 获取指定用户的联盟列表 分页
     *
     * @param  int   $uid
     *                      用户ID
     * @param  int   $gid
     *                      联盟组ID，默认为空
     * @param  int   $limit
     *                      结果集数目，默认为10
     * @return array 指定用户的联盟列表
     */
    public function getUnioningList($uid, $gid = null, $limit = 10)
    {
        $limit = intval($limit) > 0 ? $limit : 10;

        // 没有指定关组分组的列表
        $list = $this->where("`uid`={$uid}")->order('`union_id` DESC')->findPage($limit);

        return $list;
    }

    /**
     * 获取指定用户的联盟列表 不分页
     *
     * @param  int   $uid
     *                      用户ID
     * @param  int   $gid
     *                      联盟组ID，默认为空
     * @param  int   $limit
     *                      结果集数目，默认为10
     * @return array 指定用户的联盟列表
     */
    public function getUnioningListAll($uid, $gid = null)
    {
        // 没有指定关组分组的列表
        $list = $this->where("`uid`={$uid}")->order('`union_id` DESC')->findAll();

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
    public function getUnionerList($uid, $limit = 10)
    {
        $limit = intval($limit) > 0 ? $limit : 10;
        // 粉丝列表
        $list = $this->where("`fid`={$uid}")->order('`union_id` DESC')->findPage($limit);
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
     * 获取用户uid与用户fid的联盟状态，已uid为主
     *
     * @param  int $uid
     *                  用户ID
     * @param  int $fid
     *                  用户ID
     * @return int 用户联盟状态，格式为array('unioning'=>1,'unioner'=>1)
     */
    public function getUnionState($uid, $fid)
    {
        $union_state = $this->getUnionStateByFids($uid, $fid);

        return $union_state [$fid];
    }

    /**
     * 批量获取用户uid与一群人fids的彼此联盟状态
     *
     * @param  int   $uid
     *                     用户ID
     * @param  array $fids
     *                     用户ID数组
     * @return array 用户uid与一群人fids的彼此联盟状态
     */
    public function getUnionStateByFids($uid, $fids)
    {
        array_map('intval', $fids);
        $_fids = is_array($fids) ? implode(',', $fids) : $fids;
        if (empty($_fids)) {
            return array();
        }
        $union_data = $this->where(" ( uid = '{$uid}' AND fid IN({$_fids}) ) OR ( uid IN({$_fids}) and fid = '{$uid}')")->findAll();
        $union_states = $this->_formatUnionState($uid, $fids, $union_data);

        return $union_states [$uid];
    }

    /**
     * 获取朋友列表数据 - 分页
     *
     * @param  int   $uid
     *                    用户ID
     * @return array 朋友列表数据
     */
    public function getFriendsList($uid, $key = '')
    {
        $table = '`'.$this->tablePrefix.'user_union` AS a LEFT JOIN `'.$this->tablePrefix.'user_union` AS b ON a.uid = b.fid AND b.uid = a.fid ';
        $where = 'a.uid = '.$uid.' AND b.uid IS NOT NULL ';
        if (! empty($key)) {
            $table .= ' left join ts_user u on u.uid=a.fid ';
            $where .= ' and u.uname like \'%'.t($key).'%\' ';
        }
        $data = D()->table($table)->field('a.fid')->where($where)->order('a.union_id DESC')->findPage();

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
        $data = D()->table('`'.$this->tablePrefix.'user_union` AS a LEFT JOIN `'.$this->tablePrefix.'user_union` AS b ON a.uid = b.fid AND b.uid = a.fid')->field('a.fid')->where('a.uid = '.$uid.' AND b.uid IS NOT NULL')->findAll();

        return $data;
    }

    /**
     * 获取所有联盟用户数据
     *
     * @param  int   $uid
     *                    用户ID
     * @return array 所有联盟用户数据
     */
    public function getUnioningsList($uid)
    {
        $data = $this->field('fid')->where('uid='.$uid)->order('union_id DESC')->findPage();

        return $data;
    }

    /**
     * 格式化，用户的联盟数据
     *
     * @param  int   $uid
     *                           用户ID
     * @param  array $fids
     *                           用户ID数组
     * @param  array $union_data
     *                           联盟状态数据
     * @return array 格式化后的用户联盟状态数据
     */
    private function _formatUnionState($uid, $fids, $union_data)
    {
        ! is_array($fids) && $fids = explode(',', $fids);
        foreach ($fids as $fid) {
            $union_states [$uid] [$fid] = array(
                    'unioning' => 0,
                    'unioner' => 0,
            );
        }
        foreach ($union_data as $r_v) {
            if ($r_v ['uid'] == $uid) {
                $union_states [$r_v ['uid']] [$r_v ['fid']] ['unioning'] = 1;
            } elseif ($r_v ['fid'] == $uid) {
                $union_states [$r_v ['fid']] [$r_v ['uid']] ['unioner'] = 1;
            }
        }

        return $union_states;
    }

    /**
     * 更新联盟数目
     *
     * @param int   $uid
     *                    操作用户ID
     * @param array $fids
     *                    被操作用户ID数组
     * @param bool  $inc
     *                    是否为加数据，默认为true
     */
    private function _updateUnionCount($uid, $fids, $inc = true)
    {
        ! is_array($fids) && $fids = explode(',', $fids);
        $data_model = model('UserData');
        // 添加联盟数
        $data_model->setUid($uid)->updateKey('unioning_count', count($fids), $inc);
        foreach ($fids as $f_v) {
            // 添加粉丝数
            $data_model->setUid($f_v)->updateKey('unioner_count', 1, $inc);
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
    public function getUnionerListForApi($mid, $uid, $since_id = 0, $max_id = 0, $limit = 20, $page = 1)
    {
        $uid = intval($uid);
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = " fid = '{$uid}'";
        if (! empty($since_id) || ! empty($max_id)) {
            ! empty($since_id) && $where .= " AND union_id > {$since_id}";
            ! empty($max_id) && $where .= " AND union_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $list = $this->where($where)->limit("{$start},{$end}")->order('union_id DESC')->findAll();
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
     * 获取指定用户联盟列表，API使用
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
     * @return array 指定用户的联盟列表数据
     */
    public function getUnioningListForApi($mid, $uid, $since_id = 0, $max_id = 0, $limit = 20, $page = 1)
    {
        $uid = intval($uid);
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = " uid = '{$uid}'";
        if (! empty($since_id) || ! empty($max_id)) {
            ! empty($since_id) && $where .= " AND union_id > {$since_id}";
            ! empty($max_id) && $where .= " AND union_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $list = $this->where($where)->limit("{$start},{$end}")->order('union_id DESC')->findAll();
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
            ! empty($since_id) && $where .= " AND a.union_id > {$since_id}";
            ! empty($max_id) && $where .= " AND a.union_id > {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $list = D()->table('`'.$this->tablePrefix.'user_union` AS a LEFT JOIN `'.$this->tablePrefix.'user_union` AS b ON a.uid = b.fid AND b.uid = a.fid')->field('a.fid, a.union_id')->where($where)->limit("{$start}, {$end}")->order('a.union_id DESC')->findAll();

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
    public function getunionList($uid)
    {
        $list = $this->field('fid')->where("uid=$uid ")->findall();

        return $list;
    }

    /**
     * 数据库搜索联盟用户
     *
     * @param string $key
     *                       关键字
     * @param string $type
     *                       关键字，unioning,unioner
     * @param  int   $limit
     *                      结果集数目
     * @param  int   $uid
     *                      指定用户UID
     * @param  int   $gid
     *                      分组ID
     * @return array 搜索后的数据
     */
    public function searchUnions($key, $type, $limit = 20, $uid, $gid)
    {
        // 数据参数不正确
        if ($key === '' || empty($uid)) {
            return array();
        }
        // 获取查询数据
        if ($type === 'unioning') {
            $map ['a.`uid`'] = $uid;
            $map ['b.`uname`'] = array(
                    'LIKE',
                    '%'.$key.'%',
            );
            $gid = empty($gid) ? 0 : intval($gid);
            switch ($gid) {
                case 0:
                    $list = D()->table('`'.C('DB_PREFIX').'user_union` AS a LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`fid` = b.`uid`')->field('a.`fid`')->where($map)->findPage($limit);
                    break;
                case - 1:
                    $map ['c.`uid`'] = array(
                            'EXP',
                            'IS NOT NULL',
                    );
                    $list = D()->table('`'.C('DB_PREFIX').'user_union` AS a LEFT JOIN `'.C('DB_PREFIX').'user_union` AS c ON a.`uid` = c.`fid` AND c.`uid` = a.`fid` LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`fid` = b.`uid`')->field('a.fid')->where($map)->order('a.union_id DESC')->findPage($limit);
                    break;
                case - 2:

                    // $uid = intval ( $uid );
                    // $map ['c.`fid`'] = array (
                    // 'EXP',
                    // 'IS NULL'
                    // );
                    // $list = $this->table ( '`' . C ( 'DB_PREFIX' ) . 'user_union` AS a LEFT JOIN `' . C ( 'DB_PREFIX' ) . 'user_union_group_link` AS c ON a.`fid` = c.`fid` LEFT JOIN `' . C ( 'DB_PREFIX' ) . 'user` AS b ON a.`fid` = b.`uid`' )->field ( 'a.fid' )->where ( $map )->order ( 'a.union_id DESC' )->findPage ( $limit );
                    break;
                default:
                // $map ['c.`union_group_id`'] = $gid;
                // $list = $this->table ( '`' . C ( 'DB_PREFIX' ) . 'user_union` AS a LEFT JOIN `' . C ( 'DB_PREFIX' ) . 'user_union_group_link` AS c ON a.union_id=c.union_id AND a.uid=c.uid LEFT JOIN `' . C ( 'DB_PREFIX' ) . 'user` AS b ON a.`fid` = b.`uid`' )->field ( 'a.fid' )->where ( $map )->order ( 'a.union_id DESC' )->findPage ( $limit );
            }
        } elseif ($type === 'unioner') {
            $map ['a.`fid`'] = $uid;
            $map ['b.`uname`'] = array(
                    'LIKE',
                    '%'.$key.'%',
            );
            $list = D()->table('`'.C('DB_PREFIX').'user_union` AS a LEFT JOIN `'.C('DB_PREFIX').'user` AS b ON a.`uid` = b.`uid`')->field('a.`uid` AS `fid`')->where($map)->findPage($limit);
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
