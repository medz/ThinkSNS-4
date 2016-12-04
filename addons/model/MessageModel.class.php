<?php
/**
 * 私信发送模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class MessageModel extends Model
{
    const ONE_ON_ONE_CHAT = 1;         // 1对1聊天
    const MULTIPLAYER_CHAT = 2;         // 多人聊天
    const SYSTEM_NOTIFY = 3;         // 系统私信

    protected $tableName = 'message_content';
    protected $fields = array(0 => 'message_id', 1 => 'list_id', 2 => 'from_uid', 3 => 'content', 4 => 'is_del', 5 => 'mtime', '_pk' => 'message_id');

    private $reversible_type = array();

    /**
     * 初始化方法，
     */
    public function _initialize()
    {
        $this->reversible_type = array(self::ONE_ON_ONE_CHAT, self::MULTIPLAYER_CHAT);
    }

    /**
     * 获取消息列表 - 分页型
     * @param  array  $map   查询条件
     * @param  string $field 显示字段，默认为*
     * @param  string $order 排序条件，默认为message_id DESC
     * @param  int    $limit 结果集数目，默认为20
     * @return array  消息列表信息
     */
    // public function getMessageByMap($map = array(), $field = '*', $order = 'message_id DESC', $limit = 20) {
    //     $list = $this->where($map)->field($field)->order($order)->findPage($limit);
    //     return $list;
    // }

    /**
     * 获取私信列表 - 分页型
     * @param  int   $uid  用户UID
     * @param  int   $type 私信类型，1表示一对一私信，2表示多人聊天，默认为1
     * @return array 私信列表信息
     */
    public function getMessageListByUid($uid, $type = 1, $limit = 20)
    {
        $uid = intval($uid);
        $type = is_array($type) ? ' IN ('.implode(',', $type).')' : "={$type}";
        $list = D('message_member')->Table("`{$this->tablePrefix}message_member` AS `mb`")
                ->join("`{$this->tablePrefix}message_list` AS `li` ON `mb`.`list_id`=`li`.`list_id`")
                ->where("`mb`.`member_uid`={$uid} AND `li`.`type`{$type} AND `mb`.`is_del` = 0 AND mb.message_num > 0")
                ->order('`mb`.`new` DESC,`mb`.`list_ctime` DESC')
                ->findPage($limit);

        $this->_parseMessageList($list['data'], $uid); // 引用

        return $list;
    }

    /**
     * 格式化，私信列表数据
     * @param  array $list        私信列表数据
     * @param  int   $current_uid ???
     * @return array 返回格式化后的私信列表数据
     */
    private function _parseMessageList(&$list, $current_uid)
    {
        foreach ($list as &$v) {
            $v['last_message'] = unserialize($v['last_message']);
            $v['last_message']['to_uid'] = $this->_parseToUidByMinMax($v['min_max'], $v['last_message']['from_uid']);
            $v['last_message']['user_info'] = model('User')->getUserInfo($v['last_message']['from_uid']);
            $v['to_user_info'] = model('User')->getUserInfoByUids($v['last_message']['to_uid']);
        }
    }

    /**
     * 获取私信详细内容
     * @param int $uid 用户UID
     * @param int $id  私信ID
     * @param  bool  $show_cascade 是否获取回话内容
     * @return array 私信详细内容
     */
    public function getDetailById($uid, $id, $show_cascade = true)
    {
        $uid = intval($uid);
        $id = intval($id);
        if ($show_cascade) {
            // 验证该用户是否为该私信成员
            if (!$this->isMember($id, $uid, false)) {
                return false;
            }
            $map['list_id'] = $id;
            $map['is_del'] = 0;
            $res = D('message_content')->where($map)->order('message_id DESC')->findAll();
            foreach ($res as $r_k => $r_v) {
                $res[$r_k]['user_info'] = model('User')->getUserInfo($r_v['from_uid']);
            }

            return $res;
        } else {
            // `mb`.`member`={$uid} 可验证当前用户是否依然为该私信成员
            return D('message_content')->Table("`{$this->tablePrefix}message_content` AS `ct`")
                                       ->join("`{$this->tablePrefix}message_member` AS `mb` ON `ct`.`list_id`=`mb`.`list_id` AND `ct`.`from_uid`=`mb`.`member_uid`")
                                       ->where("`mb`.`member_uid`={$uid} AND `ct`.`message_id`={$id} AND `ct`.`is_del`=0")
                                       ->find();
        }
    }

    /**
     * 获取所有私信内容的列表
     * @param  array  $map   查询条件
     * @param  int    $limit 结果集数目，默认为20
     * @param  string $order 排序条件，默认为a.message_id DESC
     * @return [type] [description]
     */
    public function getDetailList($map, $limit = 20, $order = 'a.message_id DESC')
    {
        $field = 'a.*, a.from_uid AS fuid, b.type, b.min_max';
        $table = '`'.$this->tablePrefix.'message_content` AS a LEFT JOIN `'.$this->tablePrefix.'message_list` AS b ON a.list_id = b.list_id LEFT JOIN `'.$this->tablePrefix.'message_member` AS c ON a.list_id = c.list_id';
        $count = count($this->table($table)->where($map)->field('a.message_id')->group('a.message_id')->order($order)->findAll());
        $list = $this->table($table)->where($map)->field($field)->group('a.message_id')->order($order)->findPage($limit, $count);

        return $list;
    }

    /**
     * 获取指定私信列表中的私信内容
     * @param int $list_id  私信列表ID
     * @param int $uid      用户ID
     * @param int $since_id 最早会话ID
     * @param int $max_id   最新会话ID
     * @param int $count    旧会话加载条数，默认为20
     * @return array 指定私信列表中的私信内容
     */
    public function getMessageByListId($list_id, $uid, $since_id = null, $max_id = null, $count = 20)
    {
        $list_id = intval($list_id);
        $uid = intval($uid);
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $count = intval($count);

        // 验证该用户是否为该私信成员
        if (!$list_id || !$uid || !$messageInfo = $this->isMember($list_id, $uid, false)) {
            return false;
        }

        $where = "`list_id`={$list_id} AND `is_del`=0";
        if ($since_id > 0) {
            $where .= " AND `message_id` > {$since_id}";
            $max_id > 0 && $where .= " AND `message_id` < {$max_id}";
            $limit = intval($count) + 1;
        } else {
            $max_id > 0 && $where .= " AND `message_id` < {$max_id}";
            // 多查询一条验证，是否还有后续信息
            $limit = intval($count) + 1;
        }
        $res['data'] = D('message_content')->where($where)->order('message_id DESC')->limit($limit)->findAll();

        foreach ($res['data'] as $r_d_k => $r_d_v) {
            $res['data'][$r_d_k]['user_info'] = model('User')->getUserInfo($r_d_v['from_uid']);
            // 处理附件信息
            $attachType = '';
            $attachArr = array();
            $attachIds = unserialize($r_d_v['attach_ids']);
            foreach ($attachIds as &$attachId) {
                // 获取附件信息
                $attachInfo = model('Attach')->getAttachById($attachId);
                $attachType = $attachInfo['attach_type'];
                switch ($attachType) {
                    case 'message_image':
                        $tmp['attach_id'] = $attachInfo['attach_id'];
                        $tmp['width'] = $attachInfo['width'];
                        $tmp['file'] = $attachInfo['save_path'].$attachInfo['save_name'];
                        $tmp['url'] = getImageUrl($attachInfo['save_path'].$attachInfo['save_name']);
                        break;
                    case 'message_file':
                        $tmp['attach_id'] = $attachInfo['attach_id'];
                        $tmp['extension'] = strtolower($attachInfo['extension']);
                        $tmp['attach_name'] = $attachInfo['name'];
                        $tmp['size'] = $attachInfo['size'];
                        break;
                }
                $attachArr[] = $tmp;
                unset($tmp);
            }
            $res['data'][$r_d_k]['attach_infos'] = $attachArr;
            $res['data'][$r_d_k]['attach_type'] = $attachType;
            unset($attachInfos);
        }
        $res['count'] = count($res['data']);

        if ($since_id > 0) {
            $res['since_id'] = $res['data'][0]['message_id'];
            $res['max_id'] = $res['data'][$res['count'] - 1]['message_id'];
            if ($res['count'] < $limit) {
                $res['max_id'] = 0;
            }
        } else {
            $res['since_id'] = $res['data'][0]['message_id'];
            // 结果数等于查询数，则说明还有后续message
            if ($res['count'] == $limit) {
                // 去除结果的最后一条
                array_pop($res['data']);
                // 计数减一
                $res['count']--;
                // 取最后一条结果message_id
                $res['max_id'] = $res['data'][$res['count'] - 1]['message_id'];
            } elseif ($res['count'] < $limit) {
                // 取最后一条结果message_id设置为0，表示结束
                $res['max_id'] = 0;
            }
        }

        return $res;
    }

    /**
     * 获取指定用户未读的私信数目
     * @param  int $uid  用户ID
     * @param  int $type 私信类型，1表示一对一私信，2表示多人聊天，默认为1
     * @return int 指定用户未读的私信数目
     */
    public function getUnreadMessageCount($uid, $type)
    {
        $map['a.member_uid'] = intval($uid);
        $map['a.new'] = array('EQ', 2);
        $type && $map['b.type'] = array('IN', $type);
        $table = $this->tablePrefix.'message_member AS a LEFT JOIN '.$this->tablePrefix.'message_list AS b ON a.list_id = b.list_id';
        $unread = $this->table($table)->where($map)->count();

        return intval($unread);
    }

    /**
     * 发送私信
     * @param  array $data       私信信息，包括to接受对象、title私信标题、content私信正文
     * @param  int  $from_uid   发送私信的用户ID
     * @param  bool $send_email 是否同时发送邮件，默认为false
     * @return bool 是否发送成功
     */
    public function postMessage($data, $from_uid, $send_email = false)
    {
        $from_uid = intval($from_uid);
        $data['to'] = is_array($data['to']) ? $data['to'] : explode(',', $data['to']);
        $data['member'] = array_filter(array_merge(array($from_uid), $data['to']));     // 私信成员
        $data['mtime'] = time();       // 发起时间

        if ($data['type'] != self::SYSTEM_NOTIFY && $from_uid > 1) {
            // 判断接受者能否接受私信
            foreach ($data['to'] as $v) {
                $privacy = model('UserPrivacy')->getPrivacy($from_uid, $v);
                $userInfo = model('User')->getUserInfo($v);
                if ($privacy['message'] == 1) {
                    $this->error = '根据对方的隐私设置，您无法给TA发送私信';           // 私信发送失败
                    return false;
                }
            }
        }

        // 添加或更新私信list
        if (false == $data['list_id'] = $this->_addMessageList($data, $from_uid)) {
            $this->error = L('PUBLIC_PRIVATE_MESSAGE_SEND_FAIL');                   // 私信发送失败
            return false;
        }

        // 存储私信成员
        if (false === $this->_addMessageMember($data, $from_uid)) {
            $this->error = L('PUBLIC_PRIVATE_MESSAGE_SEND_FAIL');                   // 私信发送失败
            return false;
        }

        // 存储内容
        if (false == $this->_addMessage($data, $from_uid)) {
            $this->error = L('PUBLIC_PRIVATE_MESSAGE_SEND_FAIL');                  // 私信发送失败
            return false;
        }

        $author = model('User')->getUserInfo($from_uid);
        $config['name'] = $author['uname'];
        $config['space_url'] = $author['space_url'];
        $config['face'] = $author['avatar_small'];
        $config['content'] = $data['content'];
        $config['ctime'] = date('Y-m-d H:i:s', $data['mtime']);
        $config['source_url'] = U('public/Message/index');
        foreach ($data['to'] as $uid) {
            model('Notify')->sendNotify($uid, 'new_message', $config);
        }

        return $data['list_id'];
    }

    /**
     * 回复私信
     * @param int $list_id 回复的私信list_id
     * @param  string $content    回复内容
     * @param  int    $from_uid   回复者ID
     * @param  array $attach_ids 附件ID数组
     * @return mix   回复失败返回false，回复成功返回本条新回复的message_id
     */
    public function replyMessage($list_id, $content, $from_uid, $attach_ids)
    {
        $list_id = intval($list_id);
        $from_uid = intval($from_uid);
        $time = time();

        // 获取当前私信列表list的type、min_max
        $list_map['list_id'] = $list_id;
        $list_info = D('message_list')->field('type,member_num,min_max')->where($list_map)->find();
        if (!in_array($list_info['type'], $this->reversible_type)) {
            return false;
        } elseif (!$this->isMember($list_id, $from_uid, false)) {
            return false;
        }

        // 添加新记录
        $data['list_id'] = $list_id;
        $data['content'] = $content;
        $data['attach_ids'] = $attach_ids;
        $data['mtime'] = $time;
        $new_message_id = $this->_addMessage($data, $from_uid);
        unset($data);

        if (!$new_message_id) {
            return false;
        } else {
            $list_data['list_id'] = $list_id;
            $list_data['last_message'] = serialize(array('from_uid' => $from_uid, 'content' => t($content)));
            if (1 == $list_info['type']) {
                // 一对一
                $list_data['member_num'] = 2;
                // 重置最新记录
                D('message_list')->save($list_data);
                // 重置其他成员信息
                if ($list_info['member_num'] < 2) {
                    $member_data = array(
                        'list_id' => $list_id,
                        'member' => array_diff(explode('_', $list_info['min_max']), array($from_uid)),
                        'mtime' => $time,
                    );
                    $this->_addMessageMember($member_data, $from_uid);
                } else {
                    // 重置其他成员信息
                    $member_data['new'] = 2;
                    $member_data['message_num'] = array('exp', '`message_num`+1');
                    $member_data['list_ctime'] = $time;
                    D('message_member')->where("`list_id`={$list_id} AND `member_uid`!={$from_uid}")->save($member_data);
                }
            } else {
                // 多人
                // 重置最新记录
                D('message_list')->save($list_data);
                // 重置其他成员信息
                $member_data['new'] = 2;
                $member_data['message_num'] = array('exp', '`message_num`+1');
                $member_data['list_ctime'] = $time;
                D('message_member')->where("`list_id`={$list_id} AND `member_uid`!={$from_uid}")->save($member_data);
            }
            // 重置回复者的成员信息
            $from_data['message_num'] = array('exp', '`message_num`+1');
            $from_data['ctime'] = $time;
            $from_data['list_ctime'] = $time;
            D('message_member')->where("`list_id`={$list_id} AND `member_uid`={$from_uid}")->save($from_data);
            unset($from_data);
        }

        $members = D('message_member')->where("`list_id`={$list_id}")->getAsFieldArray('member_uid');
        $members = array_diff($members, array($from_uid));

        $author = model('User')->getUserInfo($from_uid);
        $config['name'] = $author['uname'];
        $config['space_url'] = $author['space_url'];
        $config['face'] = $author['avatar_small'];
        $config['content'] = $content;
        $config['ctime'] = date('Y-m-d H:i:s', $time);
        $config['source_url'] = U('public/Message/index');
        foreach ($members as $uid) {
            model('Notify')->sendNotify($uid, 'new_message', $config);
        }

        return $new_message_id;
    }

    /**
     * 设置指定用户指定私信为已读
     * @param array  $list_ids   私信列表ID数组
     * @param [type] $member_uid 成员用户ID
     * @param int val 要设置的值
     * @return bool 是否设置成功
     */
    public function setMessageIsRead($list_ids = null, $member_uid, $val = 0)
    {
        if (!$member_uid) {
            return false;
        }
        if (!empty($list_ids)) {
            !is_array($list_ids) && $list_ids = explode(',', $list_ids);
            $list_ids = array_map('intval', $list_ids);
            $map['list_id'] = array('IN', $list_ids);
        } else {
            $map['new'] = 2;
        }
        $map['member_uid'] = intval($member_uid);

        return false !== D('message_member')->where($map)->setField('new', $val);
    }

    /**
     * 设置指定用户所有的私信为已读
     * @param  int  $member_uid 用户ID
     * @return bool 是否设置成功
     */
    public function setAllIsRead($member_uid)
    {
        $member_uid = intval($member_uid);
        if ($member_uid <= 0) {
            return false;
        }
        $map['member_uid'] = $member_uid;

        return D('message_member')->where($map)->setField('new', 0);
    }

    /**
     * 指定用户删除指定的私信列表
     * @param  int   $member_uid 用户ID
     * @param  array $list_ids   私信列表ID
     * @return bool  是否删除成功
     */
    public function deleteMessageByListId($member_uid, $list_ids)
    {
        if (!is_array($list_ids)) {
            $list_ids = explode(',', $list_ids);
        }
        $list_ids = array_map('intval', $list_ids);

        if (!$list_ids || !$member_uid) {
            return false;
        }

        $member_map['list_id'] = array('IN', $list_ids);
        $member_map['member_uid'] = intval($member_uid);
        $save['message_num'] = 0;
        if (false == D('')->table($this->tablePrefix.'message_member')->where($member_map)->save($save)) {
            $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR');               // 操作失败
            return false;
        }
        $content_map['list_id'] = array('IN', $list_ids);
        $content_map['from_uid'] = intval($member_uid);
        $content_save['is_del'] = 1;
        D()->table($this->tablePrefix.'message_content')->where($content_map)->save($content_save);

        return true;
    }

    /**
     * 直接删除私信列表，管理员操作
     * @param  array $list_ids 私信列表ID数组
     * @return bool  是否删除成功
     */
    public function deleteMessageList($list_ids)
    {
        if (!$list_ids) {
            return false;
        }
        $map['list_id'] = array('IN', $list_ids);

        return false !== D('message_content')->where($map)->delete()
               && false !== D('message_member')->where($map)->delete()
               && false !== D('message_list')->where($map)->delete();
    }

    /**
     * 指定用户删除指定会话
     * @param  int   $member_uid  用户ID
     * @param  array $message_ids 会话ID数组
     * @return bool  是否删除成功
     */
    public function deleteSessionById($member_uid, $message_ids)
    {
        $message_ids = intval($message_ids);
        $member_uid = intval($member_uid);
        if (!$message_ids || !$member_uid) {
            return false;
        }
        $where = "`message_id`={$message_ids}";
        $list = D('message_content')->field('`list_id`')->where($where)->find();
        if (false === D('message_content')->where($where." AND `is_del` > 0 AND `is_del`!={$member_uid}")->delete()
            || false === D('message_content')->setField('is_del', $member_uid, $where.' AND `is_del`=0')) {
            return false;
        } else {
            $member_map['list_id'] = $list['list_id'];
            $member_map['member_uid'] = $member_uid;
            $res = D('message_member')->setDec('message_num', $member_map);
        }

        return $res;
    }

    /**
     * 直接删除会话操作，管理员操作
     * @param  array $message_ids 会话ID数组
     * @return bool  是否删除成功
     */
    public function deleteSessionByAdmin($message_ids)
    {
        $message_ids = intval($message_ids);
        if (!$message_ids) {
            return false;
        }
        $content_map['message_id'] = $message_ids;
        $list = D('message_content')->field('`list_id`')->where($content_map)->find();
        if (false == D('message_content')->where($content_map)->delete()) {
            return false;
        } else {
            $member_map['list_id'] = $list['list_id'];
            $res = D('message_member')->setDec('message_num', $member_map);
        }

        return $res;
    }

    /**
     * 获取指定私信列表中的成员信息
     * @param  int    $list_id 私信列表ID
     * @param  string $field   私信成员表中的字段
     * @return array  指定私信列表中的成员信息
     */
    public function getMessageMembers($list_id, $field = null)
    {
        $list_id = intval($list_id);
        static $_members = array();

        if (!isset($_members[$list_id])) {
            $_members[$list_id] = D('message_member')->field($field)->where("`list_id`={$list_id}")->findAll();
            foreach ($_members[$list_id] as $_m_l_k => $_m_l_v) {
                $_members[$list_id][$_m_l_k]['user_info'] = model('User')->getUserInfo($_m_l_v['member_uid']);
            }
        }

        return $_members[$list_id];
    }

    /**
     * 验证指定用户是否是指定私信列表的成员
     * @param int  $list_id     私信列表ID
     * @param int  $uid         用户ID
     * @param bool $show_detail 是否显示详细，默认为false
     * @return array 如果是成员返回相关信息，不是则返回空数组
     */
    public function isMember($list_id, $uid, $show_detail = false)
    {
        $list_id = intval($list_id);
        $uid = intval($uid);
        $show_detail = $show_detail ? 1 : 0;
        static $_is_member = array();

        if (!isset($_is_member[$list_id][$uid][$show_detail])) {
            $map['list_id'] = $list_id;
            $map['member_uid'] = $uid;
            if ($show_detail) {
                $_is_member[$list_id][$uid][$show_detail] = D('message_member')->where($map)->find();
            } else {
                $_is_member[$list_id][$uid][$show_detail] = D('message_member')->getField('member_uid', $map);
            }
        }

        return $_is_member[$list_id][$uid][$show_detail];
    }

    /**
     * 添加新的私信列表
     * @param  array $data     私信列表相关数据
     * @param  int $from_uid 发布人ID
     * @return mix 添加失败返回false，成功返回新的私信列表ID
     */
    private function _addMessageList($data, $from_uid)
    {
        if (!$data['content'] || !is_array($data['member']) || !$from_uid) {
            return false;
        }

        $list['from_uid'] = $from_uid;
        $list['title'] = $data['title'] ? t($data['title']) : t(getShort($data['content'], 20));
        $list['member_num'] = count($data['member']);
        $list['type'] = is_numeric($data['type']) ? $data['type'] : (2 == $list['member_num'] ? 1 : 2);
        $list['min_max'] = $this->_getUidMinMax($data['member']);
        $list['mtime'] = $data['mtime'];
        $list['last_message'] = serialize(array(
            'from_uid' => $from_uid,
            'content' => h($data['content']),
        ));

        $list_map['type'] = $list['type'];
        $list_map['min_max'] = $list['min_max'];
        if (1 == $list['type'] && $list_id = D('message_list')->getField('list_id', $list_map)) {
            $list_map['list_id'] = $list_id;
            $_list['member_num'] = $list['member_num'];
            $_list['last_message'] = $list['last_message'];
            false === D('message_list')->where($list_map)->data($_list)->save() && $list_id = false;
        } else {
            $list_id = D('message_list')->data($list)->add();
        }

        return $list_id;
    }

    /**
     * 添加私信列表的成员
     * @param  array $data     添加私信成员相关信息；私信列表ID：list_id，私信成员ID数组：member，当前时间：mtime
     * @param  int $from_uid 发布人ID
     * @return mix 添加成功返回新的私信成员表ID，添加失败返回false
     */
    private function _addMessageMember($data, $from_uid)
    {
        if (!$data['list_id'] || !is_array($data['member']) || !$from_uid) {
            return false;
        }

        $member['list_id'] = $data['list_id'];
        $member['list_ctime'] = $data['mtime'];

        foreach ($data['member'] as $k => $m) {
            $map['list_id'] = $data['list_id'];
            $map['member_uid'] = $m;
            $memberInfo = D('')->table($this->tablePrefix.'message_member')->where($map)->find();
            if (!empty($memberInfo)) {
                $member['ctime'] = $memberInfo['ctime'];
                $member['new'] = $m == $from_uid ? $memberInfo['new'] : 2;
                $member['message_num'] = $memberInfo['message_num'] + 1;
                D('')->table($this->tablePrefix.'message_member')->where($map)->save($member);
            } else {
                $member['ctime'] = $m == $from_uid ? time() : 0;
                $member['new'] = $m == $from_uid ? 0 : 2;
                $member['message_num'] = 1;
                $member['member_uid'] = $m;
                D('')->table($this->tablePrefix.'message_member')->add($member);
            }
        }
    }

    /**
     * 添加会话
     * @param  array $data     会话相关数据
     * @param  int $from_uid 发布人ID
     * @return mix 添加失败返回false，添加成功返回新的会话ID
     */
    private function _addMessage($data, $from_uid)
    {
        if (!$data['list_id'] || !$from_uid) {
            return false;
        }
        if (!$data['attach_ids'] && !$data['content']) {
            return false;
        }
        $message['list_id'] = $data['list_id'];
        $message['from_uid'] = $from_uid;
        $message['content'] = $data['content'];
        $message['attach_ids'] = serialize($data['attach_ids']);
        $message['is_del'] = 0;
        $message['mtime'] = $data['mtime'];

        return D('message_content')->data($message)->add();
    }

    /**
     * 输出从小到大用“_”连接的字符串
     * @param  array  $uids 用户ID数组
     * @return string 从小到大用“_”连接的字符串
     */
    private function _getUidMinMax($uids)
    {
        sort($uids);

        return implode('_', $uids);
    }

    /**
     * 格式化用户数组，去除指定用户
     * @param  string $min_max_uids 从小到大用“_”的用户ID字符串
     * @param  int    $from_uid     指定用户ID
     * @return array  用户数组，去除指定用户
     */
    private function _parseToUidByMinMax($min_max_uids, $from_uid)
    {
        $min_max_uids = explode('_', $min_max_uids);
        // 去除当前用户UID
        return array_values(array_diff($min_max_uids, array($from_uid)));
    }

    /**
     * 编辑会话，彻底删除，假删除，恢复
     * @param int $message_id 会话ID
     * @param  string $type       操作类型，彻底删除：deleteMessage，假删除：delMessage，恢复：其他字符串
     * @param  string $title      知识内容，功能待完成
     * @return array 返回操作后的信息数据
     */
    public function doEditMessage($message_id, $type, $title)
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));            // 操作失败
        if (empty($message_id)) {
            $return['data'] = L('PUBLIC_WRONG_DATA');           // 错误的参数
        } else {
            $map['message_id'] = is_array($message_id) ? array('IN', $message_id) : intval($message_id);
            $save['is_del'] = $type == 'delMessage' ? 1 : 0;
            if ($type == 'deleteMessage') {
                // 彻底删除操作
                $res = $this->where($map)->delete();
            } else {
                // 删除或者恢复
                $res = $this->where($map)->save($save);
                $this->_afterDeleteMessage($message_id);
            }
            if ($res) {
                // TODO:是否记录知识,以及后期缓存处理
                $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
            }
        }

        return $return;
    }

    /**
     * 删除私信后的数据处理操作
     * @param int|array $message_id 删除的私信ID
     */
    private function _afterDeleteMessage($message_id)
    {
        if (empty($message_id)) {
            return false;
        }
        // 获取删除私信数组
        $message_id = is_array($message_id) ? $message_id : explode(',', $message_id);
        $map['message_id'] = array('IN', $message_id);
        $list_id = $this->where($map)->findAll();
        $list_id = getSubByKey($list_id, 'list_id');
        $list_id = array_filter($list_id);
        $list_id = array_unique($list_id);
        foreach ($list_id as $value) {
            // 重新整理数据
            $count = $this->where('list_id='.$value.' AND is_del=0')->count();
            D('message_member')->where('list_id='.$value)->setField('message_num', $count);
            // 更新最后的数据
            $last_message = $this->where('list_id='.$value.' AND is_del=0')->order('message_id DESC')->find();
            $last_message = serialize($last_message);
            D('message_list')->where('list_id='.$value)->setField('last_message', $last_message);
        }
    }

    /**
     * 获取指定私信列表，指定结果集的最早会话ID，用于动态加载
     * @param  int $list_id 私信列表ID
     * @param  int $nums    结果集数目
     * @return int 最早会话ID
     */
    public function getSinceMessageId($list_id, $nums)
    {
        $map['list_id'] = $list_id;
        $map['is_del'] = 0;
        $info = $this->where($map)->order('message_id DESC')->field('message_id')->limit($nums)->findAll();
        if ($nums > 0) {
            return intval($info[$nums - 1]['message_id'] - 1);
        } else {
            return 0;
        }
    }

    /*** API使用 ***/
    /**
     * 私信列表，API专用
     * @param int $uid 用户ID
     * @param  string $type     all:全部消息,is_read:阅读过的,is_unread:为阅读  默认'all'
     * @param  int    $since_id 范围起始ID，默认0
     * @param  int    $max_id   范围结束ID，默认0
     * @param  int    $count    单页读取条数，默认20
     * @param  int    $page     页码，默认1
     * @param  string $order    排序，默认以消息ID倒叙排列
     * @return array  私信列表数据
     */
    public function getMessageListByUidForAPI($uid, $type = 1, $since_id = 0, $max_id = 0, $count = 20, $page = 1, $order = '`mb`.`new` DESC,`mb`.`list_id` DESC')
    {
        $uid = intval($uid);
        $type = is_array($type) ? ' IN ('.implode(',', $type).')' : "={$type}";
        $where = "`mb`.`member_uid`={$uid} AND `li`.`type`{$type} AND mb.is_del = 0 AND mb.message_num > 0";
        if ($since_id) {
            $where .= "  AND `li`.`list_id`>{$since_id}";
        }
        if ($max_id) {
            $where .= "  AND `li`.`list_id`<{$max_id}";
        }
        $limit = ($page - 1) * $count.','.$count;
        $list = D('message_member')->table("`{$this->tablePrefix}message_member` AS `mb`")
                ->join("`{$this->tablePrefix}message_list` AS `li` ON `mb`.`list_id`=`li`.`list_id`")
                ->where($where)
                ->order('`mb`.`list_ctime` DESC')
                ->limit($limit)
                ->findAll();
        $this->_parseMessageList($list, $uid); // 引用
        foreach ($list as &$_l) {
            $_l['from_uid'] = $_l['last_message']['from_uid'];
            $_l['content'] = $_l['last_message']['content'];
        }

        return $list;
    }

    /**
     * 未读私信列表，API专用
     * @param int $uid 用户ID
     * @param  string $type     all:全部消息,is_read:阅读过的,is_unread:为阅读  默认'all'
     * @param  int    $since_id 范围起始ID，默认0
     * @param  int    $max_id   范围结束ID，默认0
     * @param  int    $count    单页读取条数，默认20
     * @param  int    $page     页码，默认1
     * @param  string $order    排序，默认以消息ID倒叙排列
     * @return array  未读私信列表数据
     */
    public function getMessageListByUidForAPIUnread($uid, $type = 1, $since_id = 0, $max_id = 0, $count = 20, $page = 1, $order = '`mb`.`new` DESC,`mb`.`list_id` DESC')
    {
        $uid = intval($uid);
        $type = is_array($type) ? ' IN ('.implode(',', $type).')' : "={$type}";
        if ($since_id) {
            $since_id = " `li`.`list_id`>{$since_id}";
        }
        if ($max_id) {
            $max_id = " `li`.`list_id`<{$max_id}";
        }
        $limit = ($page - 1) * $count.','.$count;
        $list = D('message_member')->table("`{$this->tablePrefix}message_member` AS `mb`")
                                   ->join("`{$this->tablePrefix}message_list` AS `li` ON `mb`.`list_id`=`li`.`list_id`")
                                   ->where("`mb`.`member_uid`={$uid} AND `li`.`type`{$type} AND `mb`.`new`> 0 AND mb.is_del=0")
                                   ->order('`mb`.`list_id` DESC')
                                   ->limit($limit)
                                   ->findAll();
        $this->_parseMessageList($list, $uid); // 引用
        foreach ($list as &$_l) {
            $_l['from_uid'] = $_l['last_message']['from_uid'];
            $_l['content'] = $_l['last_message']['content'];
            $_l['mtime'] = $_l['list_ctime'];
        }

        return $list;
    }

    /**
     * 获取用户的最后一条私信，API专用
     * @param int 用户UID
     * @return array 用户的最后一条私信数据
     */
    public function getLastMessageByUidForAPI($uid)
    {
        $sql = "SELECT a.*,b.* FROM {$this->tablePrefix}message_member AS a LEFT JOIN {$this->tablePrefix}message_list AS b ON a.list_id = b.list_id 
                WHERE a.member_uid = {$uid} AND a.new > 0 ORDER BY a.list_ctime DESC";
        $data = $this->findPageBySql($sql);

        return $data;
    }

    /**
     * 发送提醒邮件接口 - 计划任务使用
     */
    public function doSendFeedMail()
    {
        // 设置查询条件，激活，审核，初始化后的用户
        $map['is_audit'] = 1;
        $map['is_active'] = 1;
        $map['is_init'] = 1;
        // 获取用户列表
        $list = model('User')->where($map)->order('feed_email_time')->field('uid,uname,email,feed_email_time,send_email_time')->limit(100)->findAll();
        // 获取uid列表
        $uids = getSubByKey($list, 'uid');
        $pmap['uid'] = array('IN', $uids);
        $pmap['key'] = 'feed_email';
        $plist = model('UserPrivacy')->where($pmap)->field('uid, value')->findAll();
        $emailset = array();
        foreach ($plist as $p) {
            $emailset[$p['uid']] = $p['value'];
        }
        $time = $_SERVER['REQUEST_TIME'];
        foreach ($list as $v) {
            // 更新最新检查邮件时间
            model('User')->setField('feed_email_time', $time, 'uid='.$v['uid']);
            // 邮件频率判断
            !$emailset[$v['uid']] && $emailset[$v['uid']] = 3;
            if ($emailset[$v['uid']] == 1) {
                model('User')->setField('feed_email_time', $time, 'uid='.$v['uid']);
                continue;
            } elseif ($emailset[$v['uid']] == 2) {
                if ($time - $v['send_email_time'] < 86400) {
                    continue;
                }
            } elseif ($emailset[$v['uid']] == 3) {
                if ($time - $v['send_email_time'] < 604800) {
                    continue;
                }
            } elseif ($emailset[$v['uid']] == 4) {
                if ($time - $v['send_email_time'] < 2592000) {
                    continue;
                }
            }
            $user_data = model('UserData')->setUid($v['uid'])->getUserData();
            // 未读@Me数目
            $unread_atme = intval($user_data['unread_atme']);
            // 未读评论数目
            $unread_comment = intval($user_data['unread_comment']);
            // 未读短信息数目
            $unread_message = (int) model('Message')->getUnreadMessageCount($v['uid'], array(MessageModel::ONE_ON_ONE_CHAT, MessageModel::MULTIPLAYER_CHAT));
            // 新的关注数目
            $new_folower_count = intval($user_data['new_folower_count']);
            // 添加判断，若没有消息，将不发送邮件提醒
            $all_count = $unread_atme + $unread_comment + $unread_message + $new_folower_count;
            if ($all_count < 20) {
                continue;
            }
            // 设置邮件发送模板
            $config['uname'] = '<a href="'.U('public/Profile/index', array('uid' => $v['uid'])).'" style="color:#0000ff;text-decoration:none;">'.$v['uname'].'</a>';
            $config['website'] = '<a style="text-decoration:none;color:#3366cc" href="'.SITE_URL.'">'.$GLOBALS['ts']['site']['site_name'].'</a>';
            $config['unread_atme'] = $unread_atme;
            $config['unread_comment'] = $unread_comment;
            $config['unread_message'] = $unread_message;
            $config['new_folower_count'] = $new_folower_count;
            $config['visit_web'] = '<a href="'.SITE_URL.'" style="background:#0f8ca8;width:80px;display:block;padding:5px 10px;color:#fff;text-align:center;font-size:12px;text-decoration:none;">快去看看吧</a>';
            // 获取模板信息
            $notifyTmp = model('Notify')->getDataByNode('remind_mail', $config);
            // 设置邮件模板
            $mailtitle = $notifyTmp['title'];
            $body = $notifyTmp['body'];
            $mailbody = '<style>a.email_btn,a.email_btn:link,a.email_btn:visited{background:#0F8CA8;padding:5px 10px;color:#fff;width:80px;text-align:center;}</style><div style="width:540px;border:#0F8CA8 solid 2px;margin:0 auto"><div style="color:#bbb;background:#0f8ca8;padding:5px;overflow:hidden;zoom:1"><div style="float:right;height:15px;line-height:15px;padding:10px 0;display:none">2012年07月15日</div>
                    <div style="float:left;overflow:hidden;position:relative"><a><img style="border:0 none" src="'.$GLOBALS['ts']['site']['logo'].'"></a></div></div>
                    <div style="background:#fff;padding:20px;min-height:300px;position:relative">       <div style="font-size:14px;">
                                        <p style="padding:0 0 20px;margin:0;font-size:12px">'.$body.'</p>
                                    </div></div><div style="background:#fff;">
                        <div style="text-align:center;height:18px;line-height:18px;color:#999;padding:6px 0;font-size:12px">若不想再收到此类邮件，请点击<a href="'.U('public/Account/notify').'" style="text-decoration:none;color:#3366cc">设置</a></div>
                        <div style="line-height:18px;text-align:center"><p style="color:#999;font-size:12px">'.$GLOBALS['ts']['site']['site_footer'].'</p></div>
                    </div></div>';

            // 原始模板，屏蔽，保留，参考
/*    		$body = 'Hi , <a href="'.U('public/Profile/index',array('uid'=>$v['uid'])).'" style="color:#0000ff;text-decoration:none;">'.$v['uname'].'</a><br /><br />自从您上次登录<a style="text-decoration:none;color:#3366cc" href="'.SITE_URL.'">'.
                    $GLOBALS['ts']['site']['site_name']."</a>以后，发生了很多事：<br /><br />您共收到";


            $body.=$unread_atme.'条@提到你的、'.$unread_comment.'条新的评论、'.$unread_message.'条私信，<br /><br />共有'.$new_folower_count.'人关注了你。<br/><br/><a href="'.SITE_URL.'" style="background:#0f8ca8;width:80px;display:block;padding:5px 10px;color:#fff;text-align:center;font-size:12px;text-decoration:none;">快去看看吧</a>';

            $mailtitle = '您有新的动态';
            $mailbody = '<style>a.email_btn,a.email_btn:link,a.email_btn:visited{background:#0F8CA8;padding:5px 10px;color:#fff;width:80px;text-align:center;}</style><div style="width:540px;border:#0F8CA8 solid 2px;margin:0 auto"><div style="color:#bbb;background:#0f8ca8;padding:5px;overflow:hidden;zoom:1"><div style="float:right;height:15px;line-height:15px;padding:10px 0;display:none">2012年07月15日</div>
                    <div style="float:left;overflow:hidden;position:relative"><a><img style="border:0 none" src="'.$GLOBALS['ts']['site']['logo'].'"></a></div></div>
                    <div style="background:#fff;padding:20px;min-height:300px;position:relative">		<div style="font-size:14px;">
                                        <p style="padding:0 0 20px;margin:0;font-size:12px">'.$body.'</p>
                                    </div></div><div style="background:#fff;">
                        <div style="text-align:center;height:18px;line-height:18px;color:#999;padding:6px 0;font-size:12px">若不想再收到此类邮件，请点击<a href="'.U('public/Account/notify').'" style="text-decoration:none;color:#3366cc">设置</a></div>
                        <div style="line-height:18px;text-align:center"><p style="color:#999;font-size:12px">'.$GLOBALS['ts']['site']['site_footer'].'</p></div>
                    </div></div>';*/
            // 更新邮件发送时间
            model('User')->setField('send_email_time', $time, 'uid='.$v['uid']);

            model('Mail')->send_email($v['email'], $mailtitle, $mailbody);
        }
    }
}
