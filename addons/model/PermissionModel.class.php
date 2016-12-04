<?php
/**
 * 权限模型 - 业务逻辑模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class PermissionModel
{
    protected static $permission = array();            // 当前用户所具有的权限列表

    /**
     * 验证权限
     * @param string $action 动作节点
     * @return bool   是否具有该动作节点的权限
     */
    public function check($action)
    {
        // 验证时载入当前应用 - 模块的权限
        if (empty($this->option['app']) || empty($this->option['module'])) {
            return false;
        }
        // 判断是否为扩展应用
        if (!in_array($this->option['app'], array('core'))) {
            // 判断应用是否关闭
            $isOpen = model('App')->isAppNameOpen(strtolower($this->option['app']));
            if (!$isOpen) {
                return false;
            }
        }

        $permission = $this->loadRule($GLOBALS['ts']['mid']);
        if (isset($permission[$this->option['app']][$this->option['module']][$action])) {
            return true;
        }

        return false;
    }

    /**
     * 设置需要加载权限的 应用 - 模块
     * @param string $type 应用 - 模块
     * @return object 配置后的权限对象
     */
    public function load($type)
    {
        $type = explode('_', $type, 2);
        $this->option['app'] = $type[0];
        $this->option['module'] = $type[1];

        return $this;
    }

    /**
     * 设置自定义组信息
     * @param  string $group 自定义用户组名称，例如：群组内成员必须设置为member，否则按其当前用户组权限判断
     * @return object 配置后的权限对象
     */
    public function group($group = false)
    {
        $this->option['group'] = $group;

        return $this;
    }

    /**
     * 获取指定用户的权限集合
     * @param  int   $uid 用户ID
     * @return array 指定用户的权限集合
     */
    public function loadRule($uid)
    {
        if (empty($uid)) {
            return false;
        }

        if (empty(self::$permission[$uid])) {
            $permission = model('Cache')->get('perm_user_'.$uid);
            if (!$permission) {
                $userGroupids = model('UserGroupLink')->getUserGroup($uid);
                $userGroupids[$uid] && $userGroup = model('UserGroup')->getUserGroup($userGroupids[$uid]);
                $permission = array();
                // 先处理应用内的用户组
                if (!empty($this->option['group'])) {
                    $permission = $this->getGroupPermission($this->option['app'].'_'.$this->option['group']);
                }
                foreach ($userGroup as $k => $v) {
                    if ($v['user_group_type'] == 1) {
                        // 特殊组
                        $permission = $this->getGroupPermission($v['user_group_id']);
                        break;
                    }
                    $_p = $this->getGroupPermission($v['user_group_id']);
                    // 追加到已有权限中
                    foreach ($_p as $app => $models) {
                        foreach ($models as $model => $d) {
                            if (isset($permission[$app][$model])) {
                                $permission[$app][$model] = array_merge($permission[$app][$model], $d);
                            } else {
                                $permission[$app][$model] = $d;
                            }
                        }
                    }
                }
                model('Cache')->set('perm_user_'.$uid, $permission, 600);
            }
            self::$permission[$uid] = $permission;
        }

        return self::$permission[$uid];
    }

    /**
     * 清除权限缓存
     * @param  string $key 权限相关Key值
     * @return bool   是否清除缓存成功
     */
    public function cleanCache($key = '')
    {
        if (empty($key)) {
            $groupList = model('UserGroup')->getHashUsergroup();
            foreach ($groupList as $k => $v) {
                model('Cache')->rm('perm_'.$k);
            }
        } else {
            model('Cache')->rm('perm_'.$key);
        }

        return true;
    }

    /**
     * 获取权限节点列表，供后台使用
     * @param  int    $gid      用户组ID
     * @param  string $app      应用名称字段
     * @param  string $appgroup 应用中用户组字段
     * @return array  权限节点列表
     */
    public function getRuleList($gid, $app, $appgroup)
    {
        // $permissionFile = ADDON_PATH.'/lang/zh-cn/permission.xml';
        // $xml = simplexml_load_file( $permissionFile );
        // 权限节点获取
        $permData = D('permission_node')->order('module DESC')->findAll();
        $appHash = $permNode = $appGroup = array();

        foreach ($permData as $v) {
            $permNode[$v['appname']][$v['module']][] = $v;
            $appHash[$v['appname']] = $v['appinfo'];
        }
        // 应用内部的权限组
        $appGroupData = D('permission_group')->findAll();
        foreach ($appGroupData as $v) {
            $appGroup[$v['appname']][$v['appgroup']] = $v['appgroup_name'] ;
        }

        if (!empty($app) && !empty($appgroup)) {
            // 取出应用下的权限设置
            foreach ($permNode as $a => $v) {
                if ($a == $app) {
                    // 对应的APP下面
                    foreach ($appGroup[$a] as $group => $groupname) {
                        if ($group == $appgroup) {
                            // 所查的组权限
                            $groupInfo = array('user_group_name' => $groupname, 'user_group_id' => $app.'_'.$group);
                        }
                    }

                    $permission[$a]['info'] = $appHash[$a];

                    foreach ($v as $rules) {
                        foreach ($rules as $rule) {
                            $permission[$a]['module'][$rule['module']]['info'] = $rule['module'];
                            $permission[$a]['module'][$rule['module']]['rule'][$rule['rule']] = $rule['ruleinfo'];
                        }
                    }
                    break;
                }
            }
            $grouppermission = $this->getGroupPermission($app.'_'.$appgroup);
        } else {
            $groupInfo = model('UserGroup')->getUserGroup($gid);
            foreach ($permNode as $a => $v) {
                $permission[$a]['info'] = $appHash[$a];
                foreach ($v as $rules) {
                    foreach ($rules as $rule) {
                        $permission[$a]['module'][$rule['module']]['info'] = $rule['module'];
                        $permission[$a]['module'][$rule['module']]['rule'][$rule['rule']] = $rule['ruleinfo'];
                    }
                }
            }
            $grouppermission = $this->getGroupPermission($gid);
        }

        return array('groupInfo' => $groupInfo, 'permission' => $permission, 'grouppermission' => $grouppermission);
    }

    /**
     * 获取指定用户组的权限
     * @param  string $key 用户组ID或者特殊应用下面的appname_appgroupname
     * @return array 指定用户组的权限信息
     */
    public function getGroupPermission($key)
    {
        static $permissionCache;
        if (!$permissionCache[$key]) {
            $cacheRule = model('Cache')->get('perm_'.$key);
            if (!$cacheRule) {
                $cacheRule = model('Xdata')->get('permission:'.$key);
                model('Cache')->set('perm_'.$key, $cacheRule);
            }
            $permissionCache[$key] = $cacheRule;
        }

        return $permissionCache[$key];
    }

    /**
     * 设置指定用户组的权限信息
     * @param string $key  用户组ID或者特殊应用下面的appname_appgroupname
     * @param array  $data 相关权限信息
     */
    public function setGroupPermission($key, $data)
    {
        model('Xdata')->put('permission:'.$key, $data);
        model('Cache')->set('perm_'.$key, $data);
        $userIds = D('user_group_link')->where('user_group_id='.$key)->field('uid')->findAll();
        foreach ($userIds as $v) {
            model('Cache')->rm('perm_user_'.$v['uid']);
        }
    }
}
