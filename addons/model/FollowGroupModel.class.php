<?php
/**
 * 用户关注分组模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class FollowGroupModel extends Model
{
    const CACHE_PREFIX = 'follow';

    protected $tableName = 'user_follow_group';
    protected $fields = array(0 => 'follow_group_id', 1 => 'title', 2 => 'uid', 3 => 'ctime');

    /**
     * 获取指定用户所有的关组分组
     * @param int $uid 用户ID
     * @return array 指定用户所有的关组分组
     */
    public function getGroupList($uid)
    {
        // if(!is_numeric($uid)) throw new ThinkException(L("arg_number_only"));
        if (false == ($follow_group_list = S(self::CACHE_PREFIX.'list_'.$uid))) {
            $follow_group_list = $this->where("uid={$uid}")->order('ctime ASC')->findAll();
            if (empty($follow_group_list)) {
                S(self::CACHE_PREFIX.'list_'.$uid, array());
            } else {
                S(self::CACHE_PREFIX.'list_'.$uid, $follow_group_list);
            }
        }

        return $follow_group_list;
    }
    /**
     * 获取指定用户指定关注用户的所在分组信息
     * @param int $uid 用户ID
     * @param  int   $fid 关注用户ID
     * @return array 关注用户所在分组的信息
     */
    public function getGroupStatus($uid, $fid)
    {
        $map['uid'] = intval($uid);
        $map['fid'] = intval($fid);
        $follow_id = D('UserFollow')->getField('follow_id', $map);
        if ($follow_id) {
            $follow_group_status = $this->field('link.follow_group_id AS gid,group.title')
                                        ->table("{$this->tablePrefix}user_follow_group_link AS link LEFT JOIN {$this->tablePrefix}{$this->tableName} AS `group` ON link.follow_group_id=group.follow_group_id AND link.uid=group.uid")
                                        ->where("link.follow_id={$follow_id} AND group.uid={$uid}")
                                        ->order('group.follow_group_id ASC')
                                        ->findAll();
            if (empty($follow_group_status)) {
                $follow_group_status[0] = array('gid' => 0, 'title' => L('PUBLIC_UNGROUP'));            // 未分组
            }

            return $follow_group_status;
        } else {
            return false;
        }
    }
    /**
     * 获取指定用户与多个指定关注用户的所在分组信息
     * @param int $uid 用户ID
     * @param  string $fids 关注用户ID，多个用“,”分割
     * @return array  指定用户与多个指定关注用户的所在分组信息
     */
    public function getGroupStatusByFids($uid, $fids)
    {
        $follow_group_status = $this->field('link.follow_group_id AS gid,link.uid,link.fid,group.title')
                                    ->table("{$this->tablePrefix}user_follow_group_link AS link LEFT JOIN {$this->tablePrefix}{$this->tableName} AS `group` ON link.follow_group_id=group.follow_group_id AND link.uid=group.uid")
                                    ->where("link.uid={$uid} AND link.fid IN (".implode(',', $fids).") AND group.uid={$uid}")
                                    ->order('group.follow_group_id ASC')
                                    ->findAll();
        $_follow_group_status = array();
        foreach ($follow_group_status as $f_g_s_k => $f_g_s_v) {
            $_follow_group_status[$f_g_s_v['uid']][$f_g_s_v['fid']][] = $f_g_s_v;
        }
        foreach ($fids as $fid) {
            empty($_follow_group_status[$uid][$fid]) && $_follow_group_status[$uid][$fid][] = array('gid' => 0, 'title' => L('PUBLIC_UNGROUP'));            // 未分组
        }

        return $_follow_group_status[$uid];
    }
    /**
     * 设置好友的分组状态
     * @param int $uid 操作用户ID
     * @param int $fid 被操作用户ID
     * @param int    $gid    关注分组ID
     * @param string $action 操作状态类型，空、add、delete
     */
    public function setGroupStatus($uid, $fid, $gid, $action = null)
    {
        S(self::CACHE_PREFIX.'list_'.$uid, null);
        S(self::CACHE_PREFIX."usergroup_{$uid}_{$gid}", null);
        $map = array('uid' => intval($uid), 'fid' => intval($fid));
        $follow_id = D('UserFollow')->getField('follow_id', $map);
        $gid = $this->getField('follow_group_id', "uid={$map['uid']} AND follow_group_id={$gid}");
        if ($follow_id && $gid) {
            $linkModel = D('UserFollowGroupLink');
            $data = array('follow_group_id' => $gid, 'follow_id' => $follow_id, 'fid' => $map['fid'], 'uid' => $map['uid']);
            if ($action == null) {
                $linkModel->where($data)->delete() || $linkModel->add($data);
            } elseif ($action == 'add') {
                $linkModel->where($data)->find() || $linkModel->add($data);
            } elseif ($action == 'delete') {
                $linkModel->where($data)->delete();
            }
        }
    }

    /**
     * 清除关注分组缓存操作
     * @param int $uid 用户ID
     * @param int $gid 关注分组ID
     */
    public function cleanCache($uid, $gid = '')
    {
        S(self::CACHE_PREFIX.'list_'.$uid, null);
        if (!empty($gid)) {
            S(self::CACHE_PREFIX."usergroup_{$uid}_{$gid}", null);
        }
    }
    /**
     * 添加，修改制定用户的分组
     * @param int    $uid   用户ID
     * @param string $title 分组名称
     * @param  int    $gid   关注分组ID
     * @return int 是否添加或修改成功
     */
    public function setGroup($uid, $title, $gid = null)
    {
        S(self::CACHE_PREFIX.'list_'.$uid, null);
        S(self::CACHE_PREFIX."usergroup_{$uid}_{$gid}", null);
        $uid = intval($uid);
        $title = t($title);
        if ($title === '') {
            return 0;
        }
        // 验证分组是否存在
        $map = array('uid' => $uid, 'title' => $title);
        $_gid = $this->getField('follow_group_id', $map);
        if (!$_gid) {
            if ($gid == null) {
                $data = array('uid' => $uid, 'title' => $title, 'ctime' => time());
                $gid = $this->add($data);

                return $gid;
            } else {
                $gid = intval($gid);
                if (!$gid) {
                    return 0;
                }
                $data = array('follow_group_id' => $gid, 'uid' => $uid, 'title' => $title);
                $res = $this->save($data);

                return 1;
            }
        } elseif ($_gid == $gid) {
            return 1;
        } else {
            return 0;
        }
    }
    /**
     * 删除指定用户的指定关注分组
     * @param  int $uid 用户ID
     * @param  int $gid 分组ID
     * @return int 是否删除成功
     */
    public function deleteGroup($uid, $gid)
    {
        S(self::CACHE_PREFIX.'list_'.$uid, null);
        S(self::CACHE_PREFIX."usergroup_{$uid}_{$gid}", null);
        $uid = intval($uid);
        $gid = intval($gid);
        $res = $this->where("uid={$uid} AND follow_group_id={$gid}")->delete();
        if ($res) {
            // 清除相应分组信息
            D('UserFollowGroupLink')->where("uid={$uid} AND follow_group_id={$gid}")->delete();

            return 1;
        } else {
            return 0;
        }
    }
    /**
     * 获取指定用户指定分组下的关注用户ID
     * @param int $uid 用户ID
     * @param  int   $gid 关注分组ID
     * @return array 指定用户指定分组下的关注用户ID
     */
    public function getUsersByGroup($uid, $gid)
    {
        $uid = intval($uid);
        $gid = intval($gid);
        if (($_fid = S(self::CACHE_PREFIX."usergroup_{$uid}_{$gid}")) == false) {
            $follow_group_id_sql = ($gid == 0) ? ' AND link.follow_group_id IS NULL' : " AND link.follow_group_id={$gid}";
            $fid = $this->field('follow.fid')
                        ->table("{$this->tablePrefix}user_follow AS `follow` LEFT JOIN {$this->tablePrefix}user_follow_group_link AS link ON follow.follow_id=link.follow_id AND follow.uid=link.uid")
                        ->where("follow.uid={$uid}".$follow_group_id_sql)
                        ->findAll();
            foreach ($fid as $v) {
                $_fid[] = $v['fid'];
            }
            S(self::CACHE_PREFIX."usergroup_{$uid}_{$gid}", $_fid);
        }

        return $_fid;
    }
    /**
     * 获取指定用户指定分组下的关注用户ID - 分页型
     * @param int $uid 用户ID
     * @param  int   $gid 关注分组ID
     * @return array 指定用户指定分组下的关注用户ID
     */
    public function getUsersByGroupPage($uid, $gid)
    {
        $uid = intval($uid);
        $gid = intval($gid);
        // 全部用户分组
        $follow_group_id_sql = ($gid == 0) ? ' AND link.follow_group_id IS NULL' : " AND link.follow_group_id={$gid}";
        // 获取用户ID
        $data = $this->field('follow.fid')
                    ->table("{$this->tablePrefix}user_follow AS `follow` LEFT JOIN {$this->tablePrefix}user_follow_group_link AS link ON follow.follow_id=link.follow_id AND follow.uid=link.uid")
                    ->where("follow.uid={$uid}".$follow_group_id_sql)
                    ->order('follow.follow_id DESC')
                    ->findPage();

        return $data;
    }
    /**
     * 获取指定用户的未分组用户ID - 分页型
     * @param  int   $uid 用户ID
     * @return array 指定用户的未分组用户ID
     */
    public function getDefaultGroupByPage($uid)
    {
        $uid = intval($uid);
        $data = $this->table('`'.$this->tablePrefix.'user_follow` AS a LEFT JOIN `'.$this->tablePrefix.'user_follow_group_link` AS b ON a.fid = b.fid AND a.uid = b.uid')
                     ->field('a.fid')
                     ->where('a.uid = '.$uid.' AND b.fid IS NULL')
                     ->order('a.follow_id DESC')
                     ->findPage();

        return $data;
    }
    /**
     * 获取指定用户的未分组用户ID - 所有
     * @param  int   $uid 用户ID
     * @return array 指定用户的未分组用户ID
     */
    public function getDefaultGroupByAll($uid)
    {
        $uid = intval($uid);
        $data = $this->table('`'.$this->tablePrefix.'user_follow` AS a LEFT JOIN `'.$this->tablePrefix.'user_follow_group_link` AS b ON a.follow_id = b.follow_id')
        ->field('a.fid')
        ->where('a.uid = '.$uid.' AND b.fid IS NULL')
        ->findAll();

        return $data;
    }
}
