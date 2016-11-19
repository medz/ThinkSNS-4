<?php

/**
 * web端聊天消息模型
 * @author xiewei <master@xiew.net>
 * @version TS4.0
 */
class WebMessageModel
{
    protected $userId = null;

    protected $room = null;

    protected $member = null;

    protected $models = array();

    public function __construct()
    {
        $this->setUserId($_SESSION['mid']);
    }

    /**
     * @param $toUid
     * @return array|bool
     */
    public function getMessageRoom($toUid)
    {
        if (!$this->userId) {
            return false;
        }

        $toUid = (int) $toUid;

        if ($this->userId == $toUid) {
            return false;
        }

        if (!model('User')->hasUser($toUid, true)) {
            return false;
        }

        if ($this->userId > $toUid) {
            $minMax = $toUid.'_'.$this->userId;
        } else {
            $minMax = $this->userId.'_'.$toUid;
        }

        $map['min_max'] = $minMax;
        $map['type'] = 1;
        $result = $this->room()->where($map)->find();

        if (!$result) {
            $time = time();
            $data = array(
                'from_uid' => $this->userId,
                'type' => 1,
                'member_num' => 2,
                'min_max' => $minMax,
                'mtime' => $time,
                'last_message' => '',
            );
            $listId = $this->room()->add($data);
            if ($listId) {
                $data['list_id'] = $listId;
                $table = $this->table('message_member');
                $sql = "INSERT INTO `{$table}` (`list_id`,`member_uid`,`new`,`message_num`,`ctime`,`list_ctime`) ".
                    "VALUES ({$listId},{$this->userId},0,0,{$time},{$time}),({$listId},{$toUid},0,0,{$time},{$time})";
                if (M()->execute($sql)) {
                    return $data;
                }
            }

            return false;
        } else {
            unset($result['last_message']);
        }

        return $result;
    }

    /**
     * @param $uids
     * @param  null       $title
     * @return array|bool
     */
    public function createGroupRoom($uids, $title = null)
    {
        if (!$this->userId) {
            return false;
        }
        $uids = $this->formatInList($uids);
        if (false !== strpos(",{$uids},", ",{$this->userId},")) {
            $uids = str_replace(",{$this->userId},", ',', ",{$uids},");
            $uids = trim($uids, ',');
        }
        $users = $this->getUserList("{$this->userId},$uids");
        $count = count($users);
        if ($count <= 1) {
            return false;
        }
        $title = isset($title) ? $this->htmlEncode($title) : null;
        $uids_sort = array_keys($users);
        asort($uids_sort);
        // 组装数据并添加到room表中,得到room_id
        $room = array(
            'from_uid' => $this->userId,
            'type' => 2,
            'title' => $title,
            'member_num' => $count,
            'min_max' => implode('_', $uids_sort),
            'mtime' => time(),
        );
        $roomId = $this->room()->add($room);
        if ($roomId) {
            $room['list_id'] = $roomId;
            $time = $room['mtime'];
            $table = $this->table('message_member');
            $sql = "INSERT INTO `{$table}` (`list_id`,`member_uid`,`new`,`message_num`,`ctime`,`list_ctime`) VALUES ";
            foreach ($users as $uid => $user) {
                $sql .= "({$roomId},{$uid},0,0,{$time},{$time}),";
            }
            if (M()->execute(rtrim($sql, ','))) {
                $room['member_list'] = $users;
                $this->sendMessage(array(
                    'room_id' => $roomId,
                    'attach' => array(
                        'notify_type' => 'create_group_room',
                        'member_list' => $users,
                        'room_member_num' => count($users),
                    ),
                ), true);

                return $room;
            }
        }

        return false;
    }

    /**
     * 删除群成员
     * @param $roomId
     * @param $memberUids
     * @return bool
     */
    public function removeGroupMember($roomId, $memberUids)
    {
        $uid = intval($this->userId);
        // 检查群组权限
        if (!$this->checkGroupPermissions($roomId, true)) {
            return false;
        }
        // 处理客户端传的成员信息
        $memberUids = $this->formatInList($memberUids);
        if (!$memberUids) {
            return false;
        }
        $memberUids = explode(',', $memberUids);
        if (in_array($uid, $memberUids)) {
            $key = array_search($uid, $memberUids);
            if ($key !== false) {
                unset($memberUids[$key]);
            }
        }
        //删除群成员
        $memberUids = implode(',', $memberUids);
        if (!$memberUids) {
            return false;
        }
        $where = "`member_uid` IN($memberUids) AND `list_id`=".intval($roomId);
        if ($this->member()->where($where)->delete()) {
            //更新成员数量和min_max
            $member_num = $this->refreshRoomMember($roomId);
            // 发消息 并 返回数据
            $result = $this->sendMessage(array(
                'room_id' => $roomId,
                'attach' => array(
                    'notify_type' => 'remove_group_member',
                    'member_list' => array_values($this->getUserList($memberUids)),
                    'room_member_num' => $member_num,
                ),
            ), true, false);
            if (is_array($result)) {
                $result['to_uids'] = array_unique(array_merge(
                    $result['to_uids'], explode(',', $memberUids)
                ));
                $this->pushMessage($result['to_uids'], array($result['return']));

                return true;
            }
        }

        return false;
    }

    /**
     * 添加群成员
     * @param $room_id
     * @param $memberUids
     * @return bool
     */
    public function addGroupMember($roomId, $memberUids)
    {
        $uid = intval($this->userId);
        // 检查群组权限
        if (!$this->checkGroupPermissions($roomId, false)) {
            return false;
        }
        // 取得要添加的用户并删除自己
        $users = $this->getUserList($memberUids);
        if (isset($users[$uid])) {
            unset($users[$uid]);
        }
        // 将成员添加到房间
        $time = time();
        $table = $this->table('message_member');
        $sql_values = '';
        foreach ($users as $user) {
            if (!$this->roomHasUser($roomId, $user['uid'], false)) {
                $sql_values .= "({$roomId},{$user['uid']},0,0,{$time},{$time}),";
            }
        }
        if (!$sql_values) {
            return false;
        }
        if (false !== M()->execute("INSERT INTO `{$table}` (`list_id`,`member_uid`,`new`".
            ',`message_num`,`ctime`,`list_ctime`) VALUES '.rtrim($sql_values, ','))) {
            // 更新成员数量和min_max
            $member_num = $this->refreshRoomMember($roomId);
            // 发消息
            $this->sendMessage(array(
                'room_id' => $roomId,
                'attach' => array(
                    'notify_type' => 'add_group_member',
                    'member_list' => array_values($users),
                    'room_member_num' => $member_num,
                ),
            ), true);

            return true;
        }

        return false;
    }

    /**
     * 主动退出群房间
     * @param  int  $roomId 房间ID
     * @return bool
     */
    public function quitGroupRoom($roomId)
    {
        $roomId = intval($roomId);
        $uid = intval($this->userId);
            // 检查群组权限
            if (!$this->checkGroupPermissions($roomId, false)) {
                return false;
            }

            //清除该成员消息
            $this->clearMessage($roomId, $uid, 'all');

            // 删除这个成员
            $where = "`list_id`={$roomId} AND `member_uid`={$uid}";
        if ($this->member()->where($where)->delete()) {
            // 更新成员数量和min_max
                $member_num = $this->refreshRoomMember($roomId, true);
            if ($member_num['member_num'] > 0) {
                // 发消息
                    $data = array(
                        'room_id' => $roomId,
                        'attach' => array(
                            'notify_type' => 'quit_group_room',
                            'quit_uid' => $uid,
                            'quit_uname' => getUserName($uid),
                            'room_member_num' => $member_num['member_num'],
                            'room_master_uid' => $member_num['master_uid'],
                        ),
                    );
                $this->sendMessage($data, true);
                $this->pushMessage(array($uid), array($data));
            }

            return true;
        }

        return false;
    }

    /**
     * 设置房间信息
     * @param $roomId
     * @param $data
     * @return bool
     */
    public function setRoom($roomId, $data)
    {
        if ($roomId > 0 && !isset($data['title'])) {
            return false;
        }
        $uid = intval($this->userId);
        // 检查权限
        if (!$this->checkGroupPermissions($roomId, false, null)) {
            return false;
        }
        // 设置房间信息
        $where = '`list_id`='.intval($roomId);
        $sets = array('title' => $this->htmlEncode(trim($data['title'])), 'mtime' => time());
        if ($this->room()->where($where)->save($sets)) {
            // 发消息
            $this->sendMessage(array(
                'room_id' => $roomId,
                'attach' => array(
                    'notify_type' => 'set_room',
                    'room_info' => array(
                        'title' => trim($data['title']),
                        'mtime' => $sets['mtime'],
                    ),
                ),
            ), true);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 检查群组权限
     * @param  int  $roomId      房间ID
     * @param  bool $checkMaster 是否检查uid为群主
     * @param  int  $type        群房间类型 null 为不限制
     * @return bool
     */
    public function checkGroupPermissions($roomId, $checkMaster = false, $type = 2)
    {
        $uid = intval($this->userId);
        $where = '`list_id`='.intval($roomId);
        if ($checkMaster) {
            $where .= ' AND `from_uid`='.$uid;
        }
        if (null !== $type) {
            $where .= ' AND `type`='.intval($type);
        }
        $uids = $this->room()->where($where)->getField('min_max');

        return $uids && ($checkMaster || false !== strpos("_{$uids}_", "_{$uid}_"));
    }

    /**
     * 刷新房间成员信息
     * @param  int  $roomId       房间ID
     * @param  bool $changeMaster
     * @return int  返回群成员数量
     */
    public function refreshRoomMember($roomId, $changeMaster = false)
    {
        $roomId = intval($roomId);
        $where = "`list_id`={$roomId}";
        $members = $this->member()->field('member_uid')->where($where)->order('id')->select();
        if (!$members) { // 没有成员了，删除这个房间
            $this->room()->where($where)->delete();
            $this->push()->where($where)->delete();
            $this->content()->where($where)->delete();

            return 0;
        } else { // 更新群成员信息
            $members = array_column($members, 'member_uid');
            if ($changeMaster) {
                $save['from_uid'] = current($members);
            }
            asort($members);
            $save['member_num'] = count($members);
            $save['min_max'] = implode('_', $members);
            $this->room()->where($where)->save($save);
            if ($changeMaster) {
                return array(
                    'master_uid' => $save['from_uid'],
                    'member_num' => $save['member_num'],
                );
            } else {
                return $save['member_num'];
            }
        }
    }

    public function getRoomList($limit = null, $page = true, $appendMember = false)
    {
        if (!$this->userId) {
            return false;
        }
        $member = $this->table('message_member');
        $list = $this->table('message_list');

        $where = "`{$list}`.`list_id`=`{$member}`.`list_id` AND `{$member}`.".
            "`member_uid`={$this->userId} AND `{$member}`.`message_num`>0";
        $limit = intval($limit);
        $field = "`{$list}`.*,`{$member}`.`new` as `msg_new`";
        $field .= ",`{$member}`.`message_num` as `msg_num`";
        $field .= ",`{$member}`.`ctime` as `join_time`";
        $field .= ",`{$member}`.`list_ctime` as `send_time`";
        $field .= ",`{$member}`.`member_uid` as `member_uid`";
        $order = "`{$list}`.`mtime` DESC";
        $sql = "SELECT {$field} FROM `{$list}`,`{$member}` WHERE {$where} ORDER BY {$order}";
        if ($page) {
            $data = M()->findPageBySql($sql, null, $limit < 0 ? null : $limit);
            if (isset($data['data'])) {
                $list = & $data['data'];
            } else {
                $list = null;
            }
        } else {
            if ($limit > 0) {
                $sql .= " LIMIT {$limit}";
            }
            $data = M()->query($sql);
            $list = & $data;
        }
        if ($list) {
            foreach ($list as $key => &$val) {
                if (empty($val['last_message'])) {
                    $val['last_message'] = array();
                    continue;
                }
                $lastMessage = @unserialize($val['last_message']);
                if (is_array($lastMessage)) {
                    $val['last_message'] = array(
                        'message_id' => isset($lastMessage['message_id']) ? $lastMessage['message_id'] : null,
                        'content' => isset($lastMessage['content']) ? $lastMessage['content'] : '',
                        'type' => isset($lastMessage['type']) ? $lastMessage['type'] : 'text',
                        'mtime' => isset($lastMessage['mtime']) ? $lastMessage['mtime'] : '0',
                        'from_uid' => isset($lastMessage['from_uid']) ? $lastMessage['from_uid'] : '0',
                    );
                } else {
                    $val['last_message'] = array();
                }
            }
            if ($appendMember) {
                $list = $this->appendRoomListMember($list);
            }
        }

        return $data;
    }

    public function getRoomMember($roomId)
    {
        $map['list_id'] = intval($roomId);

        return $this->member()->where($map)->order('id')->findAll();
    }

    /**
     * @param  array       $data
     * @return array|mixed
     */
    public function appendRoomListMember(array $data)
    {
        if (empty($data)) {
            return $data;
        }
        $single = isset($data['list_id']);
        if ($single) {
            $data = array($single);
        }
        $roomIds = array_column($data, 'list_id');
        if (!$roomIds) {
            return $data;
        }
        $map['list_id'] = array('in', implode(',', $roomIds));
        $members = $this->member()->where($map)->order('id')->findAll();
        $appends = array();
        if ($members) {
            foreach ($members as $member) {
                if (isset($appends[$member['list_id']])) {
                    $appends[$member['list_id']][] = $member;
                } else {
                    $appends[$member['list_id']] = array($member);
                }
            }
        }
        foreach ($data as $key => &$val) {
            $val['member_list'] = isset($appends[$val['list_id']]) ? $appends[$val['list_id']] : array();
        }
        if ($single) {
            return current($data);
        } else {
            return $data;
        }
    }

    public function getMessageList($roomId, $messageId = null, $direction = 'lt', $limit = null)
    {
        if (!$this->userId) {
            return false;
        }

        $roomId = intval($roomId);
        /*if(!$this->hasRoom($roomId)){
            return false;
        }*/

        $map = array(
            'list_id' => $roomId,
            'member_uid' => $this->userId,
        );
        $field = '`new`,`message_num`,`ctime`';
        $member = $this->member()->field($field)->where($map)->find();
        if (!$member) {
            return false;
        }
        if ($member['message_num'] <= 0) {
            return array();
        }

        $direction = $direction == 'lt' ? 'lt' : 'gt';
        $map = array('list_id' => $roomId, );
        $messageId = intval($messageId);
        if ($messageId > 0) {
            $map['message_id'] = array($direction, $messageId);
        }
        if ($member['ctime'] > 0) {
            $map['mtime'] = array('egt', $member['ctime']);
        }
        if ($limit !== null) {
            $limit = intval($limit);
            $limit = $limit > 0 ? $limit : intval(C('LIST_NUMBERS'));
        }
        $order = 'message_id '.($direction == 'lt' ? 'DESC' : 'ASC');
        $data = $this->content()->where($map)->order($order)->limit($limit)->select();
        if ($data) {
            if ($direction == 'lt') {
                $data = array_reverse($data);
            }

            return $this->parseMessage($data);
        }

        return $data;
    }

    /**
     * 将消息列表整理为标准的返回格式
     * @param  array $list 需要整理的消息列表
     * @return array 返回整理好的消息列表
     */
    public function parseMessage($list)
    {
        if (!$list) {
            return array();
        }
        $array = array();
        foreach ($list as $key => $rs) {
            $array[$key]['message_id'] = (int) $rs['message_id'];
            $array[$key]['from_uid'] = (int) $rs['from_uid'];
            $array[$key]['type'] = $rs['type'];
            $array[$key]['content'] = (string) $rs['content'];
            $array[$key]['list_id'] = (int) $rs['list_id'];
            $array[$key]['mtime'] = (int) $rs['mtime'];
            if (empty($rs['attach_ids'])) {
                $attach = array();
            } else {
                $attach = @unserialize($rs['attach_ids']);
            }

            if ($rs['type'] == 'notify') {
                if ($attach) {
                    $array[$key] = array_merge($attach, $array[$key]);
                }
            } elseif ($rs['type'] == 'voice') {
                $array[$key]['length'] = $attach['length'];
                $array[$key]['attach_id'] = $attach['attach_id'];
            } elseif ($rs['type'] == 'image') {
                $array[$key]['attach_id'] = $attach['attach_id'];
            } elseif ($rs['type'] == 'position') {
                $array[$key]['latitude'] = $attach['latitude'];
                $array[$key]['longitude'] = $attach['longitude'];
                $array[$key]['location'] = $attach['location'];
                $array[$key]['attach_id'] = $attach['attach_id'];
            } elseif ($rs['type'] == 'card') {
                $array[$key]['uid'] = (int) $attach['uid'];
            } else {
                $array[$key]['type'] = 'text';
            }
        }

        return $array;
    }

    public function sendMessage(array $message, $isNotify = false, $isPush = true)
    {
        if (!$this->userId) {
            return false;
        }

        // 检查消息类型
        if ($isNotify) {
            $type = 'notify';
        } else {
            $type = isset($message['message_type']) ? $message['message_type'] : false;
            if (!$type || !in_array($type, array('text', 'voice', 'image', 'position', 'card'))) {
                return false;
            }
        }
        $roomId = isset($message['room_id']) ? intval($message['room_id']) : 0;
        $toUids = $this->member()->field('member_uid')->where('list_id='.$roomId)->select();
        if ($toUids) {
            $toUids = array_column($toUids, 'member_uid');
        }
        if (!$isNotify && (!$toUids || !in_array($this->userId, $toUids))) {
            return false;
        }
        // TODO 私信隐私检查
        // 准备消息数据
        $data['from_uid'] = $this->userId;
        $data['type'] = $type;
        $data['list_id'] = $roomId;
        $data['mtime'] = time();
        $return = $data;
        if ($type == 'notify') { // 通知动态信息，仅内部发送
            $data['content'] = @trim($message['content']) ?: '[动态]';
            $data['content'] = $this->htmlEncode($data['content']);
            $return['content'] = $data['content'];
            if (@is_array($message['attach'])) {
                $return = array_merge($message['attach'], $return);
                $data['attach_ids'] = @serialize($message['attach']);
            } else {
                $data['attach_ids'] = '';
            }
        } elseif ($type == 'text') { // 普通消息
            $content = $message['content'];
            if ($message['content'] != '0') {
                $content = @trim($message['content']) ? @trim($message['content']) : null;
            }
            if (empty($content) && $message['content'] != '0') {
                return false;
            }
            $data['content'] = $this->htmlEncode($content);
            $return['content'] = $data['content'];
            $data['attach_ids'] = '';
        } elseif ($type == 'card') { // 发名片消息
            if (!isset($message['uid']) || !model('User')->hasUser($message['uid'], true)) {
                return false;
            }
            $data['content'] = $return['content'] = '[名片]';
            $return['uid'] = $message['uid'];
            $data['attach_ids'] = serialize(array(
                'uid' => $message['uid'],
            ));
        } else { // 发送 带附件的消息
            $attachId = intval($message['attach_id']);
            if ($attachId <= 0) {
                return false;
            }
            $return['attach_id'] = $attachId;
            if ($type == 'voice') { //语音消息
                if (!@is_numeric($message['length'])) {
                    return false;
                }
                $data['attach_ids'] = serialize(array(
                    'attach_id' => $attachId,
                    'length' => $message['length'],
                ));
                $return['length'] = $message['length'];
                $data['content'] = $return['content'] = '[语音]';
            } elseif ($type == 'position') { // 位置消息
                $latitude = @trim($message['latitude']) ?: null;
                $longitude = @trim($message['longitude']) ?: null;
                $location = @trim($message['location']) ?: null;
                if (!is_numeric($latitude) || !is_numeric($longitude) || !$location) {
                    return false;
                }
                $data['attach_ids'] = serialize(array(
                    'attach_id' => $attachId,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'location' => $this->htmlEncode($location),
                ));
                $return['latitude'] = $latitude;
                $return['longitude'] = $longitude;
                $return['location'] = $this->htmlEncode($location);
                $data['content'] = $return['content'] = '[位置]';
            } else {
                $data['attach_ids'] = serialize(array(
                    'attach_id' => $attachId,
                ));
                $data['content'] = $return['content'] = '[图片]';
            }
        }

        $messageId = $this->content()->add($data);
        if ($messageId) {
            $return['message_id'] = $messageId;
            // 更新其他成员新消息数量
            $where = "`list_id`={$roomId} AND `member_uid`<>{$this->userId}";
            $this->member()->where($where)->save(array(
                'new' => array('exp', '`new`+1'),
                'message_num' => array('exp', '`message_num`+1'),
            ));
            // 更新自己消息总数和最后发布时间
            $where = "`list_id`={$roomId} AND `member_uid`={$this->userId}";
            $this->member()->where($where)->save(array(
                'message_num' => array('exp', '`message_num`+1'),
                'list_ctime' => $return['mtime'],
            ));
            // 更新最后一条消息
            $this->room()->where("`list_id`={$roomId}")->save(array(
                'mtime' => $return['mtime'],
                'last_message' => serialize($return),
            ));
            // 加入推送暂存表
            $insert = '';
            foreach ($toUids as $toUid) {
                $insert .= "('{$messageId}',{$roomId},{$toUid},{$return['mtime']}),";
            }
            if ($insert) {
                $tableName = $this->table('message_push');
                $insert = "INSERT INTO `{$tableName}` (`message_id`,`list_id`,`uid`,`ctime`) VALUES ".$insert;
                if (M()->execute(rtrim($insert, ','))) {
                    // 推送消息
                    $isPush && $this->pushMessage($toUids, array($return));
                }
            }
            // 返回数据
            if ($isPush) {
                return $return;
            } else {
                return array('to_uids' => $toUids, 'return' => $return);
            }
        }

        return false;
    }

    //TODO JPush Code
    public function pushMessage($toUids, $data)
    {
        $clients = $this->getClientByUser($toUids);
        if ($clients) {
            if (!class_exists('Gateway', false)) {
                require ADDON_PATH.'/library/GatewayClient/Gateway.php';
            }
            foreach ($data as &$rs) {
                $rs['message_id'] = (int) $rs['message_id'];
                $rs['from_uid'] = (int) $rs['from_uid'];
                $rs['room_id'] = (int) $rs['list_id'];
                $rs['mtime'] = (int) $rs['mtime'];
                $rs['from_uname'] = (string) getUserName($rs['from_uid']);
                if (isset($rs['attach_id'])) {
                    $rs['attach_id'] = @desencrypt($rs['attach_id'], C('SECURE_CODE'));
                }
                $rs['content'] = $this->htmlDecode($rs['content']);
                if (isset($rs['location'])) {
                    $rs['location'] = $this->htmlDecode($rs['location']);
                }
                if (isset($rs['title'])) {
                    $rs['title'] = $this->htmlDecode($rs['title']);
                }
                unset($rs['list_id']);
            }
            $data = json_encode(array(
                'type' => 'push_message',
                'result' => array(
                    'from' => 'web',
                    'length' => count($data),
                    'list' => $data,
                ),
                'status' => 0,
                'msg' => '',
            ));
            Gateway::sendToAll($data, $clients);
        }
    }

    public function clearMessage($roomId, $type = 'unread')
    {
        $uid = (int) $this->userId;
        $update = "`member_uid`={$uid}";
        $delete = "`uid`={$uid}";
        if ($roomId != 'all') {
            $roomId = $this->formatInList($roomId);
            if (!$roomId) {
                return true;
            }
            $update = "`list_id` IN({$roomId}) AND $update";
            $delete = "`list_id` IN({$roomId}) AND $delete";
        }
        if ($type == 'unread') {
            $sets = array('new' => 0);
        } else {
            $time = time();
            $sets = array(
                'new' => 0,
                'message_num' => 0,
                'ctime' => $time,
                'list_ctime' => $time,
            );
        }
        if (false !== $this->member()->where($update)->save($sets)) {
            return false !== $this->push()->where($delete)->delete();
        }

        return false;
    }

    /**
     * 根据用户Id，获取全部客户端连接ID
     * @param  string|array $uids              用户ID列表，逗号分隔或一个数组
     * @param  bool         $removeCurrentUser 如果为false，那么如果查询结果有当前用户将会保留
     * @return array        返回一个包含指定用户id的客户端连接Id数组
     */
    public function getClientByUser($uids, $removeCurrentUser = true)
    {
        if (is_array($uids)) {
            $uids = implode(',', $uids);
        }
        if (!$uids) {
            return array();
        }
        $where = "uid IN({$uids})";
        if ($removeCurrentUser) {
            $uid = intval($this->userId);
            if ($uid) {
                $where .= " AND uid<>{$uid}";
            }
        }
        $data = $this->ucmap()->where($where)->findAll();
        if ($data) {
            return array_column($data, 'client_id');
        } else {
            return array();
        }
    }

    public function hasRoom($roomId)
    {
        $map['list_id'] = intval($roomId);

        return $this->room()->where($map)->count() > 0;
    }

    public function roomHasUser($roomId, $uid, $checkRoom = false)
    {
        if ($checkRoom && !$this->hasRoom($roomId)) {
            return false;
        }

        $map['list_id'] = intval($roomId);
        $map['member_uid'] = intval($uid);

        return $this->member()->where($map)->count() > 0;
    }

    public function setUserId($userId)
    {
        $userId = @intval($userId);
        if ($userId > 0) {
            $this->userId = $userId;

            return true;
        } else {
            $this->userId = null;

            return false;
        }
    }

    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param $uids
     */
    protected function getUserList($uids)
    {
        $uids = $this->formatInList($uids);
        if (!$uids) {
            return array();
        }
        $where = "uid IN({$uids}) AND is_del=0";
        $order = "FIELD(`uid`,{$uids})";
        $users = M('User')->field('uid,uname')->where($where)->order($order)->findAll();
        // 查询内容，并把uid设置为键名，并返回新数组
        return array_column($users ?: array(), null, 'uid');
    }

    /**
     * @return Model
     */
    public function room()
    {
        return $this->model('MessageList', 'MessageList');
    }

    /**
     * @return Model
     */
    public function member()
    {
        return $this->model('MessageMember', 'MessageMember');
    }

    /**
     * @return Model
     */
    public function content()
    {
        return $this->model('MessageContent', 'MessageContent');
    }

    public function push()
    {
        return $this->model('MessagePush', 'MessagePush');
    }

    public function ucmap()
    {
        return $this->model('MessageUcmap', 'MessageUcmap');
    }

    /**
     * @param $name
     * @param  null  $propName
     * @return Model
     */
    public function model($name, $propName = null)
    {
        if ($propName) {
            if (!isset($this->models[$propName])) {
                $this->models[$propName] = M($name);
            }

            return $this->models[$propName];
        } else {
            return M($name);
        }
    }

    public function table($table)
    {
        return C('DB_PREFIX').$table;
    }

    /**
     * 将一个包含id列表的数组或字符串格式化为标准的逗号分隔值
     * @param  array|string $ints    需要整理的id列表
     * @param  string       $default 如果列表中没有符合的ID，则返回此值
     * @param  bool         $unique  是否需要去除重复
     * @return string       整理好的字符串，如果没有则返回默认值
     */
    protected static function formatInList($ints, $default = '', $unique = true)
    {
        if (!is_array($ints)) {
            $ints = explode(',', $ints);
        }
        $list = array();
        foreach ($ints as $int) {
            $int = intval(trim($int));
            if ($int > 0) {
                $list[] = $int;
            }
        }
        if (!$list) {
            return $default;
        }
        if ($unique) {
            $list = array_unique($list);
        }

        return implode(',', $list);
    }

    /**
     * html编码，默认包括单引号
     * @param  string $string
     * @param  int    $flags
     * @return string
     */
    protected static function htmlEncode($string, $flags = ENT_QUOTES, $charset = 'UTF-8')
    {
        return htmlspecialchars($string, $flags, $charset);
    }

    /**
     * html编码，默认包括单引号
     * @param  string $string
     * @param  int    $flags
     * @return string
     */
    protected static function htmlDecode($string, $flags = ENT_QUOTES)
    {
        return htmlspecialchars_decode($string, $flags);
    }
}
