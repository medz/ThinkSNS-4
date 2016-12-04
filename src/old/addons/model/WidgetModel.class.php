<?php
/**
 * 自定义Widget模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version 1.0
 */
class WidgetModel extends Model
{
    protected $tableName = 'widget';
    protected $fields = array(0 => 'id', 1 => 'name', 2 => 'desc', 3 => 'attrs', 4 => 'diyattrs', 5 => 'appname', '_autoinc' => true, '_pk' => 'id');

    /**
     * 获取自定义Widget列表 - 未分页型
     * @return array 自定义Widget列表信息
     */
    public function getDiyList()
    {
        $list = $this->table($this->tablePrefix.'Widget_diy')->findAll();

        return $list;
    }

    /**
     * 获取所有可用的Widget列表 - 未分页型
     * @return array 所有可用的Widget列表信息
     */
    public function getWidgetList()
    {
        $list = $this->table($this->tablePrefix.'widget')->findAll();

        return $list;
    }

    /**
     * 获取指定自定义Widget下的Diy数据
     * @param  int                            $id 自定义Widget下的DiyID
     * @return 自定义Widget下的Diy数据
     */
    public function getDiyWidgetById($id)
    {
        $id = intval($id);
        if (empty($id)) {
            return false;
        }
        if (($info = model('Cache')->get('Diy_Widget_'.$id)) === false) {
            $map['id'] = $id;
            $info = $this->table($this->tablePrefix.'widget_diy')->where($map)->find();
            model('Cache')->set('Diy_Widget_'.$id, $info);
        }

        return $info;
    }

    /**
     * 保存用户自定义Widget下的Diy数据
     * @param  int   $diyId      自定义Widget下的DiyID
     * @param  int   $uid        用户ID
     * @param  array $targetList 目标Widget名称列表，[应用名:Widget名称]
     * @return bool  是否保存成功
     */
    public function saveUserWigdet($diyId, $uid, $targetList)
    {
        if (!$widget_list = $this->getUserWidget($diyId, $uid)) {
            return false;
        }
        $targetList = explode(',', trim($targetList));
        $targetList = array_unique($targetList);

        $wl = $wattr = array();
        foreach ($widget_list['widget_list'] as $k => $v) {
            if (in_array($v['appname'].':'.$v['name'], $targetList)) {
                // 已经存在
                $wl[] = array('name' => $v['name'], 'appname' => $v['appname']);
                $wattr[$v['appname'].':'.$v['name']] = $widget_list['widget_diyatts'][$v['appname'].':'.$v['name']];
                $key = array_search($v['appname'].':'.$v['name'], $targetList);
                unset($targetList[$key]);
            }
        }

        foreach ($targetList as $v) {
            if (empty($v)) {
                continue;
            }
            $info = $this->getWidget($v);
            $wl[] = array('name' => $info['name'], 'appname' => $info['appname']);
            $wattr[$info['appname'].':'.$info['name']] = unserialize($info['diyattrs']);
        }

        $map['diy_id'] = $diyId;
        $map['uid'] = $uid;
        $save['widget_list'] = serialize($wl);
        $save['widget_diyatts'] = serialize($wattr);
        if (D('')->table($this->tablePrefix.'widget_user')->where($map)->save($save)) {
            model('Cache')->rm('User_Widget_'.$diyId.'_'.$uid);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加自定义Widget
     * @param  array $add 自定义Widget相关数据
     * @return mix 添加失败返回false，添加成功返回新的Widget的ID
     */
    public function addDiyWidget($add)
    {
        $add['desc'] = t($add['desc']);
        empty($add['widget_list']) && $add['widget_list'] = array();
        $add['widget_list'] = serialize($add['widget_list']);

        return D('widget_diy')->add($add);
    }

    /**
     * 自定义Widget排序
     * @param int    $id
     * @param int    $uid    用户ID
     * @param string $target 目标Widget名称，[应用名:Widget名称]
     */
    public function dosort($id, $uid, $target)
    {
        $target = explode(',', $target);
        $s = array();
        foreach ($target as $v) {
            $t = explode(':', $v);
            $s[] = array('name' => $t[1], 'appname' => $t[0]);
        }
        $save['widget_list'] = serialize($s);
        $map['uid'] = $uid;
        $map['diy_id'] = $id;
        D('')->table($this->tablePrefix.'widget_user')->where($map)->save($save);
        model('Cache')->rm('User_Widget_'.$id.'_'.$uid);
    }

    /**
     * 用户主动更新某个位置的某个Widget属性
     * @param int    $diyId  用户自定义Widget的DiyID
     * @param int    $uid    用户ID
     * @param string $target 目标Widget名称，[应用名:Widget名称]
     * @param  array  $data   更新的相关数据
     * @return bool 是否更新成功
     */
    public function updateUserWidget($diyId, $uid, $target, $data)
    {
        if (!$widget_list = $this->getUserWidget($diyId, $uid)) {
            return false;
        }

        if (empty($widget_list['widget_diyatts'])) {
            $widget_list['widget_diyatts'][$target] = $data;
        } else {
            foreach ($widget_list['widget_diyatts'] as $k => $v) {
                if ($k == $target) {
                    $widget_list['widget_diyatts'][$k] = $data;
                }
            }
        }

        $save['widget_diyatts'] = serialize($widget_list['widget_diyatts']);
        $map['diy_id'] = $diyId;
        $map['uid'] = $uid;
        if (D('')->table($this->tablePrefix.'widget_user')->where($map)->save($save)) {
            model('Cache')->rm('User_Widget_'.$diyId.'_'.$uid);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 从指定的Diy中删除指定的Widget
     * @param  int    $diyId  自定义Widget的DiyID
     * @param  int    $uid    用户ID
     * @param  string $target 目标Widget名称，[应用名:Widget名称]
     * @return bool   是否删除成功
     */
    public function deleteUserWidget($diyId, $uid, $target)
    {
        if (!$widget_list = $this->getUserWidget($diyId, $uid)) {
            return false;
        }

        $wl = array();
        foreach ($widget_list['widget_list'] as $k => $v) {
            if ($v['appname'].':'.$v['name'] != $target) {
                $wl[] = array('name' => $v['name'], 'appname' => $v['appname']);
            }
        }

        $save['widget_list'] = serialize($wl);
        foreach ($widget_list['widget_diyatts'] as $k => $v) {
            if ($k == $target) {
                unset($widget_list['widget_diyatts'][$k]);
            }
        }

        $save['widget_diyatts'] = serialize($widget_list['widget_diyatts']);
        $map['diy_id'] = $diyId;
        $map['uid'] = $uid;

        if (D('')->table($this->tablePrefix.'widget_user')->where($map)->save($save)) {
            model('Cache')->rm('User_Widget_'.$diyId.'_'.$uid);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取指定Widget的具体内容
     * @param string $target 目标Widget名称，[应用名:Widget名称]
     * @return array  指定自定义Widget的具体内容
     */
    public function getWidget($target)
    {
        if (($info = model('Cache')->get('widget_'.$target)) === false) {
            $v = explode(':', $target);
            $map['appname'] = $v[0];
            $map['name'] = $v[1];
            $info = D('')->table($this->tablePrefix.'widget')->where($map)->find();
            model('Cache')->set('widget_'.$target, $info);
        }

        return $info;
    }

    /**
     * 获取指定用户指定自定义的Widget具体内容
     * @param int $diyId 自定义Widget的DiyId
     * @param int $uid   用户ID
     * @return array 指定用户指定自定义的Widget具体内容
     */
    public function getUserWidget($diyId, $uid)
    {
        $diyId = intval($diyId);
        $uid = intval($uid);

        if (empty($diyId) || empty($uid)) {
            return false;
        }

        if (($info = model('Cache')->get('User_Widget_'.$diyId.'_'.$uid)) === false) {
            $map['diy_id'] = $diyId;
            $map['uid'] = $uid;
            if (!$info = $this->table($this->tablePrefix.'widget_user')->where($map)->find()) {
                // 不存在，则初始化
                $diyInfo = $this->getDiyWidgetById($diyId);
                $map['widget_list'] = $diyInfo['widget_list'];
                $info = $map;
                $info['widget_user_id'] = D('')->table($this->tablePrefix.'widget_user')->add($map);
            }

            $info['widget_list'] = unserialize($info['widget_list']);
            $info['widget_diyatts'] = empty($info['widget_diyatts']) ? array() : unserialize($info['widget_diyatts']);
            foreach ($info['widget_list'] as &$v) {
                $attrs = $this->getWidget($v['appname'].':'.$v['name']);
                $attrDesc = unserialize($attrs['attrs']);
                $diy = unserialize($attrs['diyattrs']);
                empty($attrDesc) && $attrDesc = array();
                empty($diy) && $diy = array();        // 默认

                $userset = isset($info['widget_diyatts'][$v['appname'].':'.$v['name']]) ? $info['widget_diyatts'][$v['appname'].':'.$v['name']] : array();
                $attr = array_merge($diy, $userset);
                $v['attrs'] = $attrDesc;
                $v['diyattrs'] = $attr;
                $v['widget_desc'] = $attrs['desc'];
            }
            model('Cache')->set('User_Widget_'.$diyId.'_'.$uid, $info);
        }

        return $info;
    }

    /*** 后台操作 ***/
    /**
     * 后台配置单个Widget
     * @param int   $id         自定义Widget的DiyId
     * @param array $targetList 目标Widget名称列表，[应用名:Widget名称]
     * @return bool  后台配置单个Widget是否成功
     */
    public function configWidget($id, $targetList)
    {
        $targetList = explode(',', trim($targetList));
        $targetList = array_unique($targetList);

        $t = array();
        foreach ($targetList as $v) {
            if (empty($v)) {
                continue;
            }
            $v = explode(':', $v);
            $t[] = array('name' => $v[1], 'appname' => $v[0]);
        }
        $map['id'] = $id;
        $save['widget_list'] = serialize($t);
        if (D('')->table($this->tablePrefix.'widget_diy')->where($map)->save($save)) {
            model('Cache')->rm('Diy_Widget_'.$id);

            return true;
        }

        return false;
    }
}
