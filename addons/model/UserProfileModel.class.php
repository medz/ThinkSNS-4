<?php
/**
 * 用户档案模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class UserProfileModel extends Model
{
    const DEPARTMENT_ID = 34;                        // 部门的字段ID
    const DEPARTMENT_KEY = 'department';            // 部门的字段KEY

    protected $tableName = 'user_profile';
    protected $fields = array(0 => 'uid', 1 => 'field_id', 2 => 'field_data', 3 => 'privacy');

    public static $profileSetting = array();        // 静态档案配置字段
    public static $sysProfile = array('intro', 'work_position', 'mobile', 'tel', 'work_director', 'department');            // 系统默认的字段，用户数据里面必须有的

    /**
     * 获取用户的分类信息列表
     * @return array 用户的分类信息列表
     */
    public function getCategoryList()
    {
        $map['field_type'] = 0;
        $category_list = $this->_getUserProfileSetting($map);

        return $category_list;
    }

    /**
     * 获取用户资料配置信息 - 不分页型
     * @param array  $map   查询条件
     * @param string $order 排序条件
     * @return array  用户资料配置信息
     */
    public function getUserProfileSetting($map = null, $order = 'field_key, display_order ASC')
    {
        $key = md5(implode('', $map).$order);
        if ($setting = static_cache('profile_'.$key)) {
            return $setting;
        }
        $setting = $this->_getUserProfileSetting($map, $order);
        $setting = $this->_formatUserProfileSetting($setting);
        static_cache('profile_'.$key, $setting);

        return $setting;
    }

    /**
     * 获取用户资料配置信息的树形结构，已分类进行树形分类
     * @param array  $map   查询条件
     * @param string $order 排序条件
     * @return array  用户资料配置信息的树形结构，已分类进行树形分类
     */
    public function getUserProfileSettingTree($map = null, $order = 'field_key, display_order ASC')
    {
        $setting = $this->_getUserProfileSetting($map, $order);
        $setting = $this->_makeUserProfileSettingTree($setting, 0);

        return $setting;
    }

    /**
     * 删除指定的资料配置字段
     * @param  array $filed_ids 配置字段ID数组
     * @return bool 是否删除成功
     */
    public function deleteProfileSet($filed_ids)
    {
        // 验证数据
        if (empty($filed_ids)) {
            $this->error = L('PUBLIC_FIELD_REQUIRED');            // 字段ID不可以为空
            return false;
        }
        // 删除配置字段操作
        $ids = is_array($filed_ids) ? $filed_ids : explode(',', $filed_ids);
        $map['field_id'] = array('IN', $ids);
        $reslut = D('')->table(C('DB_PREFIX').'user_profile_setting')->where($map)->delete();
        if ($reslut !== false) {
            return true;
        } else {
            $this->error = L('PUBLIC_FIELD_DELETE_FAIL');        // 字段删除失败
            return false;
        }
    }

    /**
     * 获取指定用户的档案信息
     * @param  integet $uid 用户UID
     * @return array   指定用户的档案信息
     */
    public function getUserProfile($uid)
    {
        // 验证数据
        if (empty($uid)) {
            return false;
        }
        if (($data = model('Cache')->get('user_profile_'.$uid)) === false) {
            $map['uid'] = $uid;
            $profile = $this->where($map)->findAll();
            $profile = $this->_formatUserProfile($profile);
            $data = empty($profile[$uid]) ? array() : $profile[$uid];
            model('Cache')->set('user_profile_'.$uid, $data);
        }

        return $data;
    }

    /**
     * 清除指定用户的档案缓存
     * @param array $uids 用户UID数组
     */
    public function cleanCache($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        if (empty($uids)) {
            return false;
        }
        $cache = model('Cache');
        foreach ($uids as $v) {
            $cache->rm('user_profile_'.$v);
        }
    }

    /**
     * 批量获取多个用户的档案信息
     * @param  array  $uids     用户UID数组
     * @param  string $category 字段类型，未使用
     * @return array  多个用户的档案信息
     */
    public function getUserProfileByUids($uids, $category = null)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        $cacheList = model('Cache')->getList('user_profile_', $uids);

        foreach ($uids as $v) {
            !$cacheList[$v] && $cacheList[$v] = $this->getUserProfile($v);
        }

        return $cacheList;
    }

    /**
     * 获取用户配置信息字段信息
     * @return array 用户配置信息字段信息
     */
    public function getUserProfileInputType()
    {
        $input_type = array(
            'input' => L('PUBLIC_INPUT_FORM'),                    // 输入表单
            'inputnums' => L('PUBLIC_NUM_INPUT'),                // 纯数字input输入
            'textarea' => L('PUBLIC_SEVERAL_TEXTFIELD'),            // 多行文本
            'select' => L('PUBLIC_DROPDOWN_MENU'),                // 下拉菜单
            'radio' => L('PUBLIC_RADIO_BUTTON'),                    // 单选框
            'checkbox' => L('PUBLIC_CHECK_BOX'),                    // 复选框
            'date' => L('PUBLIC_TIME_SELECT'),                    // 时间选择
            'selectUser' => L('PUBLIC_USER_SELECT'),                // 用户选择
            'selectDepart' => L('PUBLIC_DEPARTMENT_SELECT'),        // 部门选择
        );

        return $input_type;
    }

    /**
     * 保存指定用户的档案信息
     * @param  int   $uid  用户UID
     * @param  array $data 用户档案信息
     * @return bool  是否保存成功
     */
    public function saveUserProfile($uid, $data)
    {
        $field_ids = $delete_map = $save_data = array();
        $delete_map['uid'] = $uid;
        if (isset($_POST['cid'])) {
            $cmap['field_type'] = intval($_POST['cid']);
            $setting = $this->getUserProfileSetting($cmap);
            foreach ($setting as $sk => $se) {
                if (!isset($data[$se['field_key']])) {
                    $data[$se['field_key']] = '';
                }
            }
        } else {
            $setting = $this->getUserProfileSetting();
        }

        foreach ($data as $d_k => $d_v) {
            is_array($d_v) && $d_v = implode('|', $d_v);
            $field_id = $setting[$d_k]['field_id'];
            if (isset($field_id)) {
                // 判断字段是否为必填
                // 部门信息特殊处理
                if ($d_k == self::DEPARTMENT_KEY) {
                    if ($d_v == 0) {
                        continue;
                    } else {
                        model('Department')->updateUserDepartById($uid, $d_v);
// 						continue;
                    }
                }

                $d_v = t($d_v);
                if ($setting[$d_k]['required'] > 0 && empty($d_v)) {
                    $this->error = L('PUBLIC_INPUT_SOME', array('input' => $setting[$d_k]['field_name']));            // 请输入{input}
                    return false;
                }

                if ($setting[$d_k]['form_type'] == 'inputnums' && !is_numeric($d_v) && $d_v) {
                    $this->error = L('PUBLIC_SOME_NOT_RIGHT', array('input' => $setting[$d_k]['field_name']));        // {input}格式不正确
                    return false;
                }

                $field_ids[] = $field_id;
                /* # $d_v = str_replace("'", "\\'", $d_v); */
                $save_data[] = "{$uid}, {$field_id}, '".(('date' == $setting[$d_k]['form_type']) && !is_numeric($d_v) ? strtotime($d_v) : $d_v)."'";
            }
        }
        if (empty($field_ids)) {
            return true;
        }

        $this->cleanCache($uid);

        $delete_map['field_id'] = array('IN', $field_ids);
        $sql = 'INSERT INTO `'.$this->tablePrefix."{$this->tableName}` (`uid`, `field_id`, `field_data`) VALUES (".implode('), (', $save_data).')';
        // 删除历史数据
        $this->where($delete_map)->limit(count($field_ids))->delete();
        // 插入新数据
        $res = $this->query($sql);

        $res = false !== $res;

        $this->error = $res ? L('PUBLIC_ADMIN_OPRETING_SUCCESS') : L('PUBLIC_ADMIN_OPRETING_ERROR');        // 操作成功，操作失败

        return $res;
    }

    /**
     * 获取汇报关系，由上级至下级
     * @param int $uid   用户UID
     * @param int $level 显示的层级值
     * @return array 汇报关系树形结构
     */
    public function getUserWorkDirectorTree($uid, $level = 3)
    {
        // 由下级至上级
        $director_uid = $uid;
        $tree = array($director_uid);
        for ($i = 1; $i < $level; $i ++) {
            $director_uid = $this->_getWorkDirector($director_uid);
            if ($director_uid) {
                $tree[] = (int) $director_uid;
            } else {
                break;
            }
        }
        $tree = array_reverse($tree, true);

        return $tree;
    }

    /*** 私有方法 ***/
    /**
     * 获取用户资料字段信息
     * @param  array  $map   查询条件
     * @param  string $order 排序条件
     * @return array  用户资料字段信息
     */
    private function _getUserProfileSetting($map = null, $order = 'display_order,field_id ASC')
    {
        $setting = D('UserProfileSetting')->where($map)->order($order)->findAll();

        return $setting;
    }

    /**
     * 格式化用户资料字段信息
     * @param  array $setting 用户资料字段信息
     * @return array 格式化后的用户资料字段信息
     */
    private function _formatUserProfileSetting($setting)
    {
        $_setting = array();
        foreach ($setting as $s_v) {
            $_setting[$s_v['field_key']] = $s_v;
        }

        return $_setting;
    }

    /**
     * 生成用户字段配置的树形结构，递归方法
     * @param  array $setting    用户字段配置信息
     * @param int $parent_key 父级的Key值
     * @return array 用户字段配置的树形结构
     */
    private function _makeUserProfileSettingTree($setting, $parent_key = 0)
    {
        $_setting = array();
        foreach ($setting as $s_k => $s_v) {
            if ($s_v['field_type'] == $parent_key) {
                unset($setting[$s_k]);
                $s_v['child'] = $this->_makeUserProfileSettingTree($setting, $s_v['field_id']);
                $_setting[$s_v['field_key']] = $s_v;
            }
        }

        return $_setting;
    }

    /**
     * 格式化用户的档案数据
     * @param  array $profile 档案数据
     * @return array 格式化后的用户档案数据
     */
    private function _formatUserProfile($profile)
    {
        $_profile = array();
        foreach ($profile as $p_v) {
            $_profile[$p_v['uid']][$p_v['field_id']] = $p_v;
        }

        return $_profile;
    }

    /**
     * 获取指定用户的直接领导的UID
     * @param  int $uid 用户UID
     * @return int 指定用户的直接领导的UID
     */
    private function _getWorkDirector($uid)
    {
        $user_profile = $this->getUserProfileByUids($uid);
        $user_profile_setting = $this->getUserProfileSetting();
        $field_id = $user_profile_setting['work_director']['field_id'];
        $director_uid = $user_profile[$uid][$field_id]['field_data'];

        return $director_uid;
    }

    /*** API使用 ***/
    /**
     * 获取指定用户的档案信息，API使用
     * @param  int   $uid 用户UID
     * @return array 指定用户的档案信息
     */
    public function getUserProfileForApi($uid)
    {
        $r = array();
        // 用户字段信息
        $profileSetting = D('UserProfileSetting')->where('type=2')->getHashList('field_id');
        $profile = $this->getUserProfile($uid);

        foreach ($profile as $k => $v) {
            if (isset($profileSetting[$k])) {
                $r[$profileSetting[$k]['field_key']] = array('name' => $profileSetting[$k]['field_name'], 'value' => $v['field_data']);
            }
        }

        $r['department']['value'] && $r['department']['value'] = trim($r['department']['value'], '|');

        if ($r['work_director']['value']) {
            $work_director = model('User')->getUserInfo($r['work_director']['value']);
            $r['work_director']['value'] = $work_director['uname'];
        }

        return $r;
    }
}
