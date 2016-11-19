<?php
/**
 * 用户组模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class UserGroupModel extends Model
{
    protected $tableName = 'user_group';
    protected $fields = array(0 => 'user_group_id', 1 => 'user_group_name', 2 => 'ctime', 3 => 'user_group_icon', 4 => 'user_group_type', 5 => 'app_name', 6 => 'is_authenticate');

    /**
     * 添加或修改用户组信息
     * @param  array $d 相关用户组信息
     * @return int   相关用户组ID
     */
    public function addUsergroup($d)
    {
        $data['ctime'] = time();
        !empty($d['user_group_name']) && $data['user_group_name'] = t($d['user_group_name']);
        !empty($d['user_group_icon']) && $data['user_group_icon'] = t($d['user_group_icon']);
        isset($d['user_group_type']) && $data['user_group_type'] = intval($d['user_group_type']);
        isset($d['is_authenticate']) && $data['is_authenticate'] = intval($d['is_authenticate']);
        //dump($data);exit;
        if (!empty($d['user_group_id'])) {
            // 修改用户组
            $amap['user_group_id'] = $d['user_group_id'];
            $res = $this->where($amap)->save($data);
        } else {
            // 添加用户组
            $res = $this->add($data);
        }
        // 清除相关缓存
        $this->cleanCache();

        return $res;
    }

    /**
     * 删除指定的用户组
     * @param  int  $gid 用户组ID
     * @return bool 是否删除成功
     */
    public function delUsergroup($gid)
    {
        // 验证数据
        if (empty($gid)) {
            $this->error = L('PUBLIC_USERGROUP_ISNOT');            // 用户组不能为空
            return false;
        }
        // 系统默认的用户组不能进行删除
        if (!is_array($gid) && $gid <= 3) {
            return false;
        }
        if (is_array($gid)) {
            foreach ($gid as $v) {
                if ($v <= 3) {
                    return false;
                }
            }
        }
        // 删除指定用户组
        $map = array();
        $map['user_group_id'] = is_array($gid) ? array('IN', $gid) : intval($gid);
        if ($this->where($map)->delete()) {
            // TODO:后续操作
            D('user_group_link')->where('user_group_id='.$gid)->delete();  //删除用户关联
            //删除认证用户表数据
            model('UserVerified')->rmVerifiedGroup($gid);
            $this->cleanCache();

            return true;
        }

        return false;
    }

    /**
     * 返回用户组信息
     * @param  int   $gid 用户组ID，默认为空字符串 - 显示全部用户组信息
     * @return array 用户组信息
     */
    public function getUserGroup($gid = '')
    {
        if (($data = model('Cache')->get('AllUserGroup')) == false) {
            $list = $this->findAll();
            foreach ($list as $k => $v) {
                $data[$v['user_group_id']] = $v;
            }
            model('Cache')->set('AllUserGroup', $data);
        }
        if (empty($gid)) {
            // 返回全部用户组
            return $data;
        } else {
            // 返回指定的用户组
            if (is_array($gid)) {
                $r = array();
                foreach ($gid as $v) {
                    $r[$v] = $data[$v];
                }

                return $r;
            } else {
                return $data[$gid];
            }
        }
    }

    /**
     * 获取用户组的Hash数组
     * @param  string $k Hash数组的Key值字段
     * @param  string $v Hash数组的Value值字段
     * @return array  用户组的Hash数组
     */
    public function getHashUsergroup($k = 'user_group_id', $v = 'user_group_name')
    {
        $list = $this->getUserGroup();
        $r = array();
        foreach ($list as $lv) {
            $r[$lv[$k]] = $lv[$v];
        }

        return $r;
    }

    /**
     * 获取非认证用户组Hash数组
     * @param  string $k Hash数组的Key值字段
     * @param  string $v Hash数组的Value值字段
     * @return array  非认证用户组Hash数组
     */
    public function getHashUsergroupNoncertified($k = 'user_group_id', $v = 'user_group_name')
    {
        $list = $this->getUserGroup();
        $r = array();
        foreach ($list as $lv) {
            $lv['is_authenticate'] != 1 && $r[$lv[$k]] = $lv[$v];
        }

        return $r;
    }

    /**
     * 获取认证用户组Hash数组
     * @param  string $k Hash数组的Key值字段
     * @param  string $v Hash数组的Value值字段
     * @return array  认证用户组Hash数组
     */
    public function getHashUserGroupVertified($k = 'user_group_id', $v = 'user_group_name')
    {
        $list = $this->getUserGroup();
        $r = array();
        foreach ($list as $lv) {
            $lv['is_authenticate'] == 1 && $r[$lv[$k]] = $lv[$v];
        }

        return $r;
    }

    /**
     * 清除用户组缓存
     */
    public function cleanCache($param)
    {
        model('Cache')->rm('AllUserGroup');
    }

    /**
     * 通过指定用户组ID获取用户组信息
     * @param  string|array $gids 用户组ID
     * @return array        指定用户组ID获取用户组信息
     */
    public function getUserGroupByGids($gids)
    {
        $data = S('UserGroupByGid'.implode(',', $gids));
        if ($data) {
            return $data;
        }
        if (empty($gids)) {
            return false;
        }
        !is_array($gids) && $gids = explode(',', $gids);

        $map['user_group_id'] = array('IN', $gids);
        $data = $this->where($map)->findAll();
        S('UserGroupByGid'.implode(',', $gids), $data);

        return $data;
    }
    /**
     * 判断用户是否是管理员
     * @param unknown_type $uid
     */
    public function isAdmin($uid)
    {
        $res = model('UserGroupLink')->where('uid='.$uid.' and user_group_id=1')->getField('uid');

        return $res;
    }
    /**
     * 返回所以用户组 id为key值
     */
    public function getAllGroup()
    {
        $list = $this->findAll();
        $idkeylist = array();
        foreach ($list as $v) {
            $idkeylist[$v['user_group_id']] = $v['user_group_name'];
        }

        return $idkeylist;
    }

    /**
     * 获取指定用户的用户组图标
     *
     * @param  int    $uid 用户ID
     * @return string 返回用户组图标的img标签
     */
    public function getUserGroupIcon($uid)
    {
        $user_group = $this->getAllUserGroup();
        $user_group_link = $this->getAllUserGroupLink();
        $user_group_link = $user_group_link[$uid];

        $html = '';

        foreach ($user_group_link as $v) {
            if ($user_group[$v['user_group_id']]['icon']) {
                $html .= "<img class='ts_icon' src=".THEME_URL.'/images/'.$user_group[$v['user_group_id']]['icon'].' title='.$user_group[$v['user_group_id']]['title'].'>';
            }
        }

        return $html;
    }

    /**
     * 兼容2.8版块函数
     * 按照查询条件获取用户组
     *
     * @param  array  $map   查询条件
     * @param  string $field 字段 默认*
     * @param  string $order 排序 默认 以用户组ID升序排列
     * @return array  用户组信息
     */
    public function getUserGroupByMap($map = '', $field = '*', $order = 'user_group_id ASC')
    {
        return $this->field($field)->where($map)->order($order)->findAll();
    }
}
