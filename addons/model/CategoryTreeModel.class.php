<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Pid型的树形结构的分类模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class CategoryTreeModel extends Model
{
    private $_app;                // 分类对应的应用名称
    private $_talbe;            // 分类对应的数据表名称
    private $_model;            // 分类对应的模型操作对象
    private $_message;            // 提示信息

    /**
     * 设置分类应用名称
     * @param string $app 分类应用名称
     */
    public function setApp($app)
    {
        $this->_app = strtolower($app);

        return $this;
    }

    /**
     * 获取分类应用名称
     * @return string 分类应用名称
     */
    public function getApp()
    {
        return $this->_app;
    }

    /**
     * 设置分类表名
     * @param string $table 分类数据表名
     */
    public function setTable($table)
    {
        $this->_talbe = strtolower($table);
        $this->_model = D($this->_talbe);

        return $this;
    }

    /**
     * 获取分类表名
     * @return string 分类表名
     */
    public function getTable()
    {
        return $this->_talbe;
    }

    /**
     * 设置提示信息
     */
    public function setMessage($msg)
    {
        $this->_message = $msg;
    }

    /**
     * 获取提示信息
     * @return string 提示信息
     */
    public function getMessage()
    {
        return $this->_message;
    }

    /**
     * 当指定pid时，查询该父分类的所有子分类；否则查询所有分类
     * @param  int    $pid   父分类ID
     * @param  string $field 显示的字段，默认为空
     * @return array  相应的分类列表
     */
    public function getCategoryList($pid = -1, $field = '')
    {
        $map = array();
        $pid != -1 && $map['pid'] = $pid;
        empty($field) && $field = $this->_talbe.'_id, title, pid';
        $data = $this->_model->field($field)->where($map)->order('`sort` ASC')->findAll();

        return $data;
    }

    /**
     * 获取指定分类ID下的分类信息
     * @param  int   $id 分类ID
     * @return array 指定分类ID下的分类信息
     */
    public function getCategoryById($id)
    {
        $result = array();
        if (!empty($id)) {
            $map[$this->_talbe.'_id'] = $id;
            $result = $this->_model->where($map)->find();
            $result['id'] = $result[$this->_talbe.'_id'];
            unset($result[$this->_talbe.'_id']);
        }

        return $result;
    }

    /**
     * 获取指定父分类的树形结构
     * @return int   $pid 父分类ID
     * @return array 指定父分类的树形结构
     */
    public function getNetworkList($pid = 0)
    {
        if ($pid != 0) {
            $list = $this->_MakeTree($pid);
        } elseif (!$list and !($list = S('category_cache_'.$this->_talbe))) {
            set_time_limit(0);
            $list = $this->_MakeTree($pid);
            S('category_cache_'.$this->_talbe, $list);
        }

        return $list;

        // // 子分类树形结构
        // if($pid != 0) {
        // 	return $this->_MakeTree($pid);
        // }
        // // 全部分类树形结构
        // $list = S('category_cache_'.$this->_talbe);
        // if(!$list) {
        // 	set_time_limit(0);
        // 	$list = $this->_MakeTree($pid);
        // 	S('category_cache_'.$this->_talbe, $list);
        // }

        // return $list;
    }

    /**
     * 递归形成树形结构
     * @param  int   $pid   父分类ID
     * @param  int   $level 等级
     * @return array 树形结构
     */
    private function _MakeTree($pid, $level = 0)
    {
        $result = $this->_model->where('pid='.$pid)->order('sort ASC')->findAll();
        if ($result) {
            foreach ($result as $key => $value) {
                $id = $value[$this->_talbe.'_id'];
                $list[$id]['id'] = $value[$this->_talbe.'_id'];
                $list[$id]['pid'] = $value['pid'];
                $list[$id]['title'] = $value['title'];
                $list[$id]['level'] = $level;
                $list[$id]['child'] = $this->_MakeTree($value[$this->_talbe.'_id'], $level + 1);
            }
        }

        return $list;
    }

    /**
     * 清除分类数据PHP文件
     */
    public function remakeTreeCache()
    {
        S('category_cache_'.$this->_talbe, null);
    }

    /**
     * 移动分类操作
     * @param  int    $id   移动分类ID
     * @param  string $type 移动类型：上移(up)，下移(down)
     * @return bool   是否移动成功
     */
    public function moveTreeCategory($id, $type)
    {
        // 判断数据的正确性
        if (!is_numeric($id) || !in_array($type, array('up', 'down'))) {
            return false;
        }
        // 移动数据
        $pid = $this->_model->where($this->_talbe.'_id='.$id)->getField('pid');
        $data = $this->_model->field($this->_talbe.'_id, sort')->where('pid='.$pid)->order('sort ASC')->findAll();
        // 存储前后值
        $before = $in = $after = array();
        $keys = getSubByKey($data, $this->_talbe.'_id');
        $key = array_search($id, $keys);
        $in = $data[$key];
        if ($key == 0) {
            $after = $data[$key + 1];
        } elseif ($key == count($data)) {
            $before = $data[$key - 1];
        } else {
            $before = $data[$key - 1];
            $after = $data[$key + 1];
        }
        // 判断类型
        $result = false;
        switch ($type) {
            case 'up':
                if (!empty($before)) {
                    $this->_model->where($this->_talbe.'_id='.$id)->setField('sort', $before['sort']);
                    $this->_model->where($this->_talbe.'_id='.$before[$this->_talbe.'_id'])->setField('sort', $in['sort']);
                    $result = true;
                }
                break;
            case 'down':
                if (!empty($after)) {
                    $this->_model->where($this->_talbe.'_id='.$id)->setField('sort', $after['sort']);
                    $this->_model->where($this->_talbe.'_id='.$after[$this->_talbe.'_id'])->setField('sort', $in['sort']);
                    $result = true;
                }
                break;
        }

        $result && $this->remakeTreeCache();
        $result && model('Cache')->rm('UserCategoryTree');

        return $result;
    }

    /**
     * 更新排序字段，仅仅用于刷新历史数据
     */
    public function updateSort()
    {
        set_time_limit(0);
        $pids = $this->_model->field('DISTINCT pid')->findAll();
        $pids = getSubByKey($pids, 'pid');
        foreach ($pids as $pid) {
            $map['pid'] = $pid;
            $data = $this->_model->where($map)->order($this->_talbe.'_id ASC')->findAll();
            $sort = 1;
            foreach ($data as $value) {
                $smap[$this->_talbe.'_id'] = $value[$this->_talbe.'_id'];
                $save['sort'] = $sort;
                $this->_model->where($smap)->save($save);
                $sort++;
            }
        }
    }

    /**
     * 添加子分类操作
     * @param int    $pid   父级分类ID
     * @param string $title 分类名称
     * @param  array  $extra 插入数据时，带入的相关信息
     * @return bool 添加分类是否成功
     */
    public function addTreeCategory($pid, $title, $extra = array())
    {
        // 判断是否有重复的值
        $isExist = $this->_model->where("pid={$pid} AND title='{$title}'")->count();
        if ($isExist != 0) {
            return false;
        }
        // 添加分类操作
        $data['title'] = $title;
        $data['pid'] = $pid;
        // 获取排序值
        $map['pid'] = $pid;
        $maxSort = $this->_model->where($map)->order('sort DESC')->getField('sort');
        $data['sort'] = $maxSort + 1;
        // 添加额外信息
        if (!empty($extra)) {
            $data = array_merge($data, $extra);
        }
        $result = $this->_model->add($data);
        // 清除缓存
        $result && $this->remakeTreeCache();
        $result && model('Cache')->rm('UserCategoryTree');
        // 删除city.php 文件
        $result && model('Area')->remakeCityCache();

        return (boolean) $result;
    }

    /**
     * 更新分类信息操作
     * @param int    $cid   分类ID
     * @param string $title 分类名称
     * @param  array  $extra 插入数据时，带入的相关信息
     * @return bool 更新分类是否成功
     */
    public function upTreeCategory($cid, $title, $extra = array())
    {
        $area = (object) Capsule::table($this->getTable())->where($this->getTable().'_id', $cid)->first();

        if (!$area) {
            return false;
        } elseif ($area->title == $title) {
            return true;
        } elseif (Capsule::table($this->getTable())->where('pid', $area->pid)->where('title', $title)->exists() && empty($extra)) {
            return false;
        }

        $data = array(
            'title' => $title,
        );
        if (!empty($extra)) {
            $data = array_merge($data, $extra);
        }

        $result = Capsule::table($this->getTable())->where($this->getTable().'_id', $cid)->update($data);

        // 清除缓存
        $result && $this->remakeTreeCache();
        $result && model('Cache')->rm('UserCategoryTree');
        // 删除city.php 文件
        $result && model('Area')->remakeCityCache();

        return (boolean) $result;
    }

    /**
     * s删除分类下的子分类信息
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function rmRfCate($cid)
    {
        $list = $this->_model->where('`pid` = '.intval($cid))->field('`area_id`')->select();
        foreach ($list as $value) {
            $this->rmTreeCategory($value['area_id']);
        }
    }

    /**
     * 删除分类信息操作
     * @param  int    $cid     分类ID
     * @param  string $_module 模型名称，默认为null
     * @param  string $_method 方法名称，默认为null
     * @return bool   删除分类信息是否成功
     */
    public function rmTreeCategory($cid, $_module = null, $_method = null)
    {
        if (empty($cid)) {
            return false;
        }
        // 判断是否是包含子分类
        $isExist = $this->_model->where('pid='.$cid)->count();
        if ($isExist != 0) {
            // # 删除该分类下的子分类
            $this->rmRfCate($cid);
        }

        // 删除分类操作
        $map[$this->_talbe.'_id'] = $cid;
        $result = $this->_model->where($map)->delete();
        // 执行删除后的关联操作
        if (!empty($_module) && !empty($_method)) {
            if (empty($this->_app)) {
                model($_module)->$_method($cid);
            } else {
                D($_module, $this->_app)->$_method($cid);
            }
        }
        // 清除缓存
        if ($result) {
            $this->setMessage('删除分类成功');
            $this->remakeTreeCache();
            model('Cache')->rm('UserCategoryTree');
        } else {
            $this->setMessage('删除分类失败');
        }

        return (boolean) $result;
    }

    /**
     * 获取全部分类Hash数组
     * @param int $pid 父级分类ID
     * @return array 全部分类Hash数组
     */
    public function getCategoryHash($pid = -1)
    {
        $map = array();
        $pid != -1 && $map['pid'] = $pid;
        $data = $this->_model->where($map)->order('sort ASC')->getHashList($this->_talbe.'_id', 'title');

        return $data;
    }

    /**
     * 获取指定分类的信息
     * @param  int   $cid 分类ID
     * @return array 分类信息
     */
    public function getCategoryInfo($cid)
    {
        $map[$this->_talbe.'_id'] = $cid;
        $data = $this->_model->where($map)->find();

        return $data;
    }

    /**
     * 存储分类配置项操作
     * @param int $cid 分类ID
     * @param  array $extra 分类配置数据数组
     * @return bool 是否存储成功
     */
    public function doSetCategoryConf($cid, $ext)
    {
        if (empty($cid) || !is_array($ext)) {
            return false;
        }
        $map[$this->_talbe.'_id'] = $cid;
        $data['ext'] = serialize($ext);
        $result = $this->_model->where($map)->save($data);

        return (boolean) $result;
    }

    /**
     * 获取指定分类的相关配置信息
     * @param int $cid 分类ID
     * @return array 指定分类的相关配置信息
     */
    public function getCatgoryConf($cid)
    {
        if (empty($cid)) {
            return array();
        }
        $category = $this->getCategoryById($cid);
        $extra = unserialize($category['ext']);

        return $extra;
    }

    /**
     * 判断分类名称是否重复
     * @param  string $title 分类名称
     * @return bool   分类名称是否重复
     */
    public function isTitleExist($title)
    {
        $map['title'] = t($title);
        $count = $this->_model->where($map)->count();
        $result = ($count == 0) ? false : true;

        return $result;
    }

    /**
     * 获取详细的分类Hash数组 - 主要为了显示ext中的内容
     * @param int $pid 父级分类ID
     * @return array 详细的分类Hash数组 - 主要为了显示ext中的内容
     */
    public function getCategoryAllHash($pid = -1, $order = 'sort ASC')
    {
        $map = array();
        $pid != -1 && $map['pid'] = $pid;
        $list = $this->_model->where($map)->order($order)->getHashList($this->_talbe.'_id', '*');
        $data = array();
        foreach ($list as $key => $value) {
            if (!empty($value['ext'])) {
                $ext = unserialize($value['ext']);
                $value = array_merge($value, $ext);
            }
            $data[$key] = $value;
        }

        return $data;
    }
}
