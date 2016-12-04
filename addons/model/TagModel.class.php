<?php
/**
 * 标签模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class TagModel extends Model
{
    protected $tableName = 'tag';
    protected $fields = array(0 => 'tag_id', 1 => 'name', '_pk' => 'tag_id');

    private $_app = null;                    // 所属应用
    private $_app_table = null;                // 所属资源表

    /**
     * 设置所属应用
     * @param  string $app 应用名称
     * @return object 标签对象
     */
    public function setAppName($app)
    {
        $this->_app = $app;

        return $this;
    }

    /**
     * 设置相关内容所存储的表格
     * @param string $app_table 数据表名
     */
    public function setAppTable($app_table)
    {
        $this->_app_table = $app_table;

        return $this;
    }

    /**
     * 通过指定的应用资源ID，获取应用的内容标签
     * @param  array|int $row_ids 应用内容编号
     * @return array     标签内容列表
     */
    public function getAppTags($row_ids, $isUserTag = false)
    {
        $map['row_id'] = array('IN', $row_ids);
        $map['table'] = $this->_app_table;
        D('app_tag')->where('`table` LIKE "'.$this->_app_table.'" AND `tag_id` <= 0')->delete();
        $app_tags = D('app_tag')->where($map)->findAll();
        // 获取标签名称
        $names = $this->getTagNames(getSubByKey($app_tags, 'tag_id'));
        if ($isUserTag) {
            return $names;
        }
        // 重组结果
        foreach ($app_tags as $a_t_k => $a_t_v) {
            // 如果为用户标签，则用user_category_id
            if ($this->_app_table == 'user') {
                $cmap['title'] = $names[$a_t_v['tag_id']];
                $user_category_id = D('user_category')->where($cmap)->getField('user_category_id');
                $result[$a_t_v['row_id']][$user_category_id] = $names[$a_t_v['tag_id']];
            } else {
                $result[$a_t_v['row_id']][$a_t_v['tag_id']] = $names[$a_t_v['tag_id']];
            }
            unset($app_tags[$a_t_k]);
        }
        if (is_numeric($row_ids)) {
            return $result[$row_ids];
        } else {
            return $result;
        }
    }

    /**
     * 设置指定应用下的应用内容标签
     * @param  int   $row_id 应用内容编号
     * @param  array $tags 标签
     * @param  int   $max  最多标签数量
     * @return bool  是否设置成功
     */
    public function setAppTags($row_id, $tags, $max = 9)
    {
        $row_id = intval($row_id);
        if (!$this->_app || !$this->_app_table) {
            $this->error = L('PUBLIC_WRONG_DATA');            // 错误的参数
            return false;
        } elseif (!$row_id) {
            $this->error = L('PUBLIC_WRONG_DATA');            // 错误的参数
            return false;
        }
        // 标签
        if (!$tags) {
            $tags = array();
        } elseif (is_string($tags)) {
            $tags = explode(',', preg_replace('/[，,]+/u', ',', $tags));
        } elseif (!is_array($tags)) {
            $this->error = L('PUBLIC_WRONG_DATA');            // 错误的参数
            return false;
        }
        // 限制最大标签数
        $tags = array_slice($tags, 0, intval($max));
        // 删除历史设置
        $del_map['table'] = $this->_app_table;
        $del_map['row_id'] = $row_id;
        $res = D('app_tag')->where($del_map)->delete();
        // 添加新设置
        $data = array();
        foreach ($tags as $t_i_v) {
            $tag_id = $this->getTagId($t_i_v);
            $data[] = "('{$this->_app}', '{$this->_app_table}', {$row_id}, {$tag_id})";
        }
        $data = implode(',', $data);

        $sql = 'INSERT INTO '.$this->tablePrefix.'app_tag("app", "table", "row_id", "tag_id") VALUES'.$data;
        (false !== $res) && $res = $this->query($sql);

        return false !== $res;
    }

    /**
     * 一次添加多个应用内容的标签
     * @param  int    $row_id 应用内容编号
     * @param  string $tags 标签
     * @return bool   是否添加成功
     */
    public function addAppTags($row_id, $tags)
    {
        if (!is_array($tags)) {
            $tags = str_replace('，', ',', $tags);
            $tags = explode(',', $tags);
        }
        // 获取结果集
        $r = array();
        foreach ($tags as $t) {
            if (empty($t)) {
                continue;
            }
            if ($id = $this->addAppTag($row_id, $t)) {
                $r[] = array('tag_id' => $id, 'name' => $t);
            }
        }

        return $r;
    }

    /**
     * 添加应用内容的标签
     * @param  int    $row_id 应用内容编号
     * @param  string $tag 标签
     * @return bool   是否添加成功
     */
    public function addAppTag($row_id, $tag)
    {
        $data['app'] = $this->_app;
        $data['table'] = $this->_app_table;
        $data['row_id'] = intval($row_id);
        $data['tag_id'] = $this->getTagId($tag);
        if (empty($row_id)) {
            $ids = model('Cache')->get('temp_'.$data['table'].$GLOBALS['ts']['mid']);

            if (!empty($ids)) {
                if (in_array($data['tag_id'], $ids)) {
                    $this->error = L('PUBLIC_TAG_EXIST');            // 标签已经存在
                    return false;
                } else {
                    $ids[] = $data['tag_id'];
                }
            } else {
                $ids = array($data['tag_id']);
            }
            $this->error = L('PUBLIC_TAG').L('PUBLIC_ADD_SUCCESS');            // 标签，添加成功
            model('Cache')->set('temp_'.$data['table'].$GLOBALS['ts']['mid'], $ids, 60);

            return $data['tag_id'];
        }

        if ($data['tag_id'] && 0 == $result = D('app_tag')->where($data)->count()) {
            $result = D('app_tag')->add($data);
            if ($result) {
                $this->error = L('PUBLIC_TAG').L('PUBLIC_ADD_SUCCESS');        // 标签，添加成功
                return $data['tag_id'];
            } else {
                $this->error = L('PUBLIC_TAG').L('PUBLIC_ADD_FAIL');        // 标签，添加失败
                return false;
            }
        } else {
            $this->error = L('PUBLIC_TAG_EXIST');            // 标签已经存在
            return false;
        }
    }

    /**
     * 删除应用内容的标签
     * @param  int  $row_id 应用内容编号
     * @param  int  $tag_id 标签编号
     * @return bool 是否删除成功
     */
    public function deleteAppTag($row_id, $tag_id)
    {
        $map['table'] = $this->_app_table;
        $map['row_id'] = intval($row_id);
        $map['tag_id'] = intval($tag_id);
        if (empty($map['row_id'])) {
            $ids = model('Cache')->get('temp_'.$map['table'].$GLOBALS['ts']['mid']);
            if (!empty($ids)) {
                if (in_array($map['tag_id'], $ids)) {
                    unset($ids[array_search($map['tag_id'], $ids)]);
                    model('Cache')->set('temp_'.$map['table'].$GLOBALS['ts']['mid'], $ids);
                }
            }
            $this->error = L('PUBLIC_DELETE_FAIL');                // 删除失败
            return  true;
        }
        if (false !== D('app_tag')->where($map)->delete()) {
            $this->error = L('PUBLIC_TAG').L('PUBLIC_DELETE_SUCCESS');            // 删除成功
            return true;
        } else {
            $this->error = L('PUBLIC_TAG').L('PUBLIC_DELETE_FAIL');                // 删除失败
            return false;
        }
    }

    /**
     * 通过标签名称，获取标签编号
     * @param  string $name 标签
     * @return int    标签编号
     */
    public function getTagId($name)
    {
        // $name = t(getShort(preg_replace('/^[\s　]+|[\s　]+$/', '', $name), 15));
        $name = getShort(t($name), 15);
        if (!$name || !is_string($name)) {
            $this->error = 'Tag_Empty';

            return false;
        }
        $result = $this->getField('`tag_id`', "`name` = '{$name}'");
        if (!$result) {
            $result = $this->add(array('name' => $name));
        }

        return $result;
    }

    /**
     * 通过标签编号，获取标签内容
     * @param array $tag_ids 标签ID数组
     * @return array 标签内容列表
     */
    public function getTagNames($tag_ids)
    {
        if (is_array($tag_ids)) {
            $tag_ids = implode(',', $tag_ids);
        }
        if (strpos($tag_ids, ',')) {
            $where = "`tag_id` IN ({$tag_ids})";
        } elseif (intval($tag_ids) > 0) {
            $where = "`tag_id`={$tag_ids} ";
        } else {
            return '';
        }
        $sql = "SELECT `tag_id`,`name` FROM `{$this->tablePrefix}tag` WHERE {$where}";
        $result = $this->query($sql);
        $_result = array();
        foreach ($result as $k => $v) {
            $_result[$v['tag_id']] = $v['name'];
            unset($result[$k]);
        }

        return $_result;
    }

    /**
     * 获取全局标签列表 - 分页型
     * @param array  $map   查询条件
     * @param string $field 显示字段名称，多个用“,”分割
     * @param string $order 排序条件，默认tag_id DESC
     * @param int    $limit 结果集数目，默认为20
     * @return array  全局标签列表
     */
    public function getTagList($map = null, $field = null, $order = 'tag_id DESC', $limit = 20)
    {
        $result = $this->field($field)->where($map)->order($order)->findPage($limit);

        return $result;
    }

    /**
     * 获取应用标签列表 - 分页型
     * @param  array $map   查询条件
     * @param  int   $limit 结果集数目，默认为20
     * @return array 应用列表标签列表
     */
    public function getAppTagList($map, $limit = 20)
    {
        $table = $this->tablePrefix.'app_tag AS a LEFT JOIN '.$this->tablePrefix.'tag AS b ON a.tag_id = b.tag_id';
        $result = $this->Table($table)->where($map)->findPage($limit);

        return $result;
    }

    /**
     * 获取应用标签的Hash数组
     * @return array 应用标签的Hash数组
     */
    public function getTableHash()
    {
        $list = $this->table($this->tablePrefix.'app_tag')->field('DISTINCT(`table`) AS t')->getAsFieldArray('t');
        $r = array();
        foreach ($list as $v) {
            $r[$v] = $v;
        }

        return $r;
    }

    /**
     * 获取热门标签
     * @param  int   $limit  结果集数目，默认为15
     * @param  int   $expire 缓存时间，默认为3600
     * @return array 热门标签列表
     */
    public function getHotTags($limit = 15, $expire = 3600)
    {
        $hot_tag_list = array();
        $cache_id = $this->_app.$this->_app_table.'_hot_tag';
        if (($hot_tag_list = S($cache_id)) === false) {
            $limit = is_numeric($limit) ? $limit : 20;
            $hot_tag_ids = D('app_tag')->field('`tag_id`, COUNT(`tag_id`) AS `count`')
                           ->group('`tag_id`')->order('`count` DESC')
                           ->limit($limit)->findAll();
            // 获得标签文字
            $hot_names = $this->getTagNames(getSubByKey($hot_tag_ids, 'tag_id'));
            $hot_tag_list = array();
            // 指针引用
            foreach ($hot_tag_ids as $h_v) {
                $hot_tag_list[$h_v['tag_id']] = array(
                    'name' => $hot_names[$h_v['tag_id']],
                    'count' => $h_v['count'],
                );
            }
            unset($hot_tag_ids, $hot_tag_list);
            // 缓存结果
            S($cache_id, $hot_tag_list, $expire);
        }

        return $hot_tag_list;
    }

    /**
     * 添加全局标签
     * @param  string $tags 标签
     * @return bool   是否添加成功
     */
    public function addTags($tags)
    {
        if (empty($tags)) {
            $this->error = L('PUBLIC_TAG_NOEMPTY');            // 标签不能为空
            return false;
        }
        !is_array($tags) && $tags = explode(',', $tags);
        $tags = array_filter($tags);
        foreach ($tags as $k => $v) {
            $tags[$k] = mysql_escape_string(t(preg_replace('/^[\s　]+|[\s　]+$/', '', $tags[$k])));
            if (!$tags[$k]) {
                unset($tags[$k]);
            }
        }

        if (empty($tags)) {
            $this->error = L('PUBLIC_TAG_NOEMPTY');            // 标签不能为空
            return false;
        }
        // 检测已有标签
        $sql = 'SELECT `name` FROM '.$this->tablePrefix.'tag WHERE `name` IN (\''.implode("','", $tags).'\')';
        $existing_tags = $this->query($sql);
        $existing_tags = getSubByKey($existing_tags, 'name');
        $existing_tags = array_map('mysql_escape_string', $existing_tags);  // 数据安全转义

        // 过滤已有标签
        $tags = array_diff($tags, $existing_tags);
        if (empty($tags)) {
            $this->error = L('PUBLIC_TAG_EXIST');            // 标签已经存在
            return false;
        }

        $sql = 'INSERT INTO '.$this->tablePrefix.'tag(`name`) VALUES (\''.implode("'),('", $tags).'\')';
        if (false !== $this->execute($sql)) {
            $this->error = L('PUBLIC_TAG').L('PUBLIC_SAVE_SUCCESS');        // 标签，保存成功
            return true;
        } else {
            $this->error = L('PUBLIC_TAG').L('PUBLIC_SAVE_FAIL');            // 标签，保存失败
            return false;
        }
    }

    /**
     * 删除应用指定资源的标签信息
     * @param  int $row_id 应用内容编号
     * @return int 0表示删除失败，1表示删除成功
     */
    public function deleteSourceTag($row_id)
    {
        $map['app'] = $this->_app;
        $map['table'] = $this->_app_table;
        $map['row_id'] = intval($row_id);
        $res = D('app_tag')->where($map)->delete();

        return $res;
    }

    /**
     * @param int   $row_id 资源ID
     * @param array $tagIds 标签ID数组
     */
    public function updateTagData($row_id, $tagIds)
    {
        // 删除原有数据
        $row_id = intval($row_id);
        $map['row_id'] = $row_id;
        D('app_tag')->where($map)->delete();
        if (!empty($tagIds)) {
            // 添加新的标签信息
            $data['app'] = $this->_app;
            $data['table'] = $this->_app_table;
            $data['row_id'] = $row_id;
            foreach ($tagIds as $value) {
                $data['tag_id'] = intval($value);
                D('app_tag')->add($data);
            }
        }
    }
}
