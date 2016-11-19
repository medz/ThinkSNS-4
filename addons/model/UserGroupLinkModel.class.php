<?php
/**
 * 用户组关联模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class UserGroupLinkModel extends Model
{
    protected $tableName = 'user_group_link';
    protected $fields = array(0 => 'id', 1 => 'uid', 2 => 'user_group_id');

    /**
     * 转移用户的用户组
     * @param  string $uids          用户UID，多个用“，”分割
     * @param  string $user_group_id 用户组ID，多个用“，”分割
     * @return bool   是否转移成功
     */
    public function domoveUsergroup($uids, $user_group_id)
    {
        // 验证数据
        if (empty($uids) && empty($user_group_id)) {
            $this->error = L('PUBLIC_USER_GROUP_EMPTY');            // 用户组或用户不能为空
            return false;
        }
        $uids = explode(',', $uids);
        $user_group_id = explode(',', $user_group_id);
        $uids = array_unique(array_filter($uids));
        $user_group_id = array_unique(array_filter($user_group_id));
        // 过滤掉认证组
        if (!$uids || !$user_group_id) {
            return false;
        }
        $map['uid'] = array('IN', $uids);
        // 认证用户组
        $veritfiedHash = model('UserGroup')->getHashUserGroupVertified();
        if (!empty($veritfiedHash)) {
            $map['user_group_id'] = array('NOT IN', array_keys($veritfiedHash));
        }
        $this->where($map)->delete();
        foreach ($uids as $v) {
            $save = array();
            $save['uid'] = $v;
            foreach ($user_group_id as $gv) {
                $save['user_group_id'] = $gv;
                $this->add($save);
            }
            // 清除权限缓存
            model('Cache')->rm('perm_user_'.$v);
            model('Cache')->rm('user_group_'.$v);
        }
        model('User')->cleanCache($uids);

        return true;
    }

    /**
     * 获取用户的用户组信息
     * @param  array $uids 用户UID数组
     * @return array 用户的用户组信息
     */
    public function getUserGroup($uids)
    {
        $uids = !is_array($uids) ? explode(',', $uids) : $uids;
        $uids = array_unique(array_filter($uids));
        if (!$uids) {
            return false;
        }

        $return = array();
        foreach ($uids as $uid) {
            $return[$uid] = model('Cache')->get('user_group_'.$uid);
            if ($return[$uid] == false) {
                $map['uid'] = $uid;
                $list = $this->where($map)->findAll();
                $return[$uid] = getSubByKey($list, 'user_group_id');
                model('Cache')->set('user_group_'.$uid, $return[$uid]);
            }
        }

        return $return;
    }

    /**
     * 获取用户所在用户组详细信息
     * @param  array $uids 用户UID数组
     * @return array 用户的用户组详细信息
     */
    public function getUserGroupData($uids)
    {
        $uids = !is_array($uids) ? explode(',', $uids) : $uids;
        $uids = array_unique(array_filter($uids));
        if (!$uids) {
            return false;
        }
        $userGids = $this->getUserGroup($uids);
        //return $userGids;exit;
        $uresult = array();
        foreach ($userGids as $ug) {
            if ($uresult) {
                $ug && $uresult = array_merge($uresult, $ug);
            } else {
                $uresult = $ug;
            }
        }
        //把所有用户组信息查询出来
        $ugresult = model('UserGroup')->getUserGroupByGids(array_unique($uresult));
        $groupresult = array();
        foreach ($ugresult as $ur) {
            $groupresult[$ur['user_group_id']] = $ur;
        }
        foreach ($userGids as $k => $v) {
            $ugroup = array();
            foreach ($userGids[$k] as $userg) {
                $ugroup[] = $groupresult[$userg];
            }
            $userGroupData[$k] = $ugroup;
            foreach ($userGroupData[$k] as $key => $value) {
                if (isset($value['user_group_icon']) && $value['user_group_icon'] == -1) {
                    unset($userGroupData[$k][$key]);
                    continue;
                }
                $userGroupData[$k][$key]['user_group_icon_url'] = THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'];
            }
        }

        return $userGroupData;
    }
}
