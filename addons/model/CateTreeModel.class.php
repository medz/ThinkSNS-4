<?php
/**
 * 无限级分类控制模型 - 业务逻辑模型
 * @example
 * 说明：利用此分类模型，需要指定数据表，相关模型字段
 * 必须属性：
 * table：表名
 * id：主键（字段名）
 * name：分类名称（字段名）
 * pid：上级节点ID（字段名）
 * sort：排序（字段名）
 * 示例：
 * $ctree = model('CateTree', $table);		表名必须指定
 * $ctree = $ctree->setField($param);		不指定字段名，将使用默认字段
 * $data = $ctree->getTree();				获取树形结构
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class CateTreeModel
{
    private static $tree = array();                // 树
    private static $hash = array();                // 树的HASH表
    public $cacheKey = '';                            // 缓存Key
    private $cacheKeyPrefix = 'CateTree_';        // 缓存前缀
    private $_table = '';                            // 默认表
    private $defaultField = array(
                                'id' => 'id',        // 主键字段名
                                'pid' => 'pid',    // 上级节点ID字段名
                                'name' => 'name',    // 分类名称字段名
                                'sort' => 'sort',   // 排序字段名
                            );
    private $tempHashData = array();                // 临时Hash数据

    /**
     * 初始化无限极树形模型对象
     * @param string $table 资源表名
     */
    public function __construct($table = '')
    {
        if (empty($table)) {
            $this->error = L('PUBLIC_CATEGORYFORM_UNSIGN');            // 分类模型表未指定
            return false;
        }
        $this->_table = C('DB_PREFIX').$table;
        $this->cacheKey = $this->cacheKeyPrefix.$table;
    }

    /**
     * 设置树形数据缓存Key
     * @param  string $key 用户自定义Key值
     * @return object 无限树形模型对象
     */
    public function setCacheKey($key)
    {
        $this->cacheKey = $this->cacheKeyPrefix.$key;

        return $this;
    }

    /**
     * 设置字段值
     * @param  array  $field 用户自定义字段值
     * @return object 无限树形模型对象
     */
    public function setField(array $field = array())
    {
        if (empty($field)) {
            return $this;
        }
        foreach ($field as $k => $v) {
            $this->defaultField[$k] = $v;
        }

        return $this;
    }

    /**
     * 获取一个树形结构数据
     * @param  int   $rootId 根目录ID
     * @return array 树形结构
     */
    public function getTree($rootId = '0')
    {
        // 减少缓存读取次数
        if (isset(self::$tree[$this->cacheKey])) {
            return self::$tree[$this->cacheKey][$rootId];
        }
        if ($tree = model('Cache')->get($this->cacheKey)) {
            // 缓存命中
            self::$tree[$this->cacheKey] = $tree;

            return $tree[$rootId];
        }
        // 重建树及hash缓存
        $this->createTree();

        return self::$tree[$this->cacheKey][$rootId];
    }

    /**
     * 获取所有分类的以主键为Key的Hash数组
     * @return arrat 分类的Hash数组
     */
    public function getAllHash()
    {
        $hash = model('Cache')->get($this->cacheKey.'_hash');
        if (!empty($hash)) {
            // 缓存命中
            self::$hash[$this->cacheKey] = $hash;

            return $hash;
        }
        // 重建树及Hash缓存
        $this->createTree();

        return self::$hash[$this->cacheKey];
    }

    /**
     * 获取指定分类ID下的所有子集ID
     * @param  int   $id 分类ID
     * @return array 指定分类ID下的所有子集ID
     */
    public function getChildHash($id)
    {
        $data = $this->getTree($id);
        $this->_getChildHash($data);
        $r = $this->tempHashData;
        $this->tempHashData = array();

        return $r;
    }

    /**
     * 递归方法获取树形结构下的所有子集ID
     * @param array $data 子集数据
     */
    private function _getChildHash($data)
    {
        $this->tempHashData[] = $data[$this->defaultField['id']];
        if (!empty($data['_child'])) {
            foreach ($data['_child'] as $v) {
                $this->_getChildHash($v);
            }
        }
    }

    /**
     * 移动一个树形节点，即更改一个节点的父节点，移动规则：父节点不能移动到自己的子树下面
     * @param int $id      节点ID
     * @param int $newPid  新的父节点ID
     * @param int $newSort 新的排序值
     * @param  array $data    表示其他需要保存的字段，由实现此model的类完成
     * @return bool 是否移动成功
     */
    public function moveTree($id, $newPid, $newSort = '255', $data = array())
    {
        // 参数验证
        if (!$id || (!$newPid && $newPid != 0)) {
            $this->error = L('PUBLIC_DATA_MOVE_ERROR');            // 分类树移动的参数出错
            return false;
        }
        // 更新数据库信息
        $map[$this->defaultField['id']] = $id;
        $data[$this->defaultField['pid']] = $newPid;
        $data[$this->defaultField['sort']] = $newSort;

        if (D('')->table($this->_table)->where($map)->save($data)) {
            // TODO:可优化成更新缓存而不是清除缓存
            $this->cleanCache();
        }

        return true;
    }

    /**
     * 批量移动树的节点，移动规则：父节点不能移动到自己的子树下面
     * ids，newPids的Key值必须对应
     * @param  array $ids      节点ID数组
     * @param  array $newPids  新的父节点ID数组
     * @param  array $newSorts 新的排序值数组
     * @return bool  是否移动成功
     */
    public function moveTreeByArr($ids, $newPids, $newSorts = array())
    {
        foreach ($ids as $k => $v) {
            $sort = isset($newSorts[$k]) && !empty($newSorts[$k]) ? $newSorts[$k] : 255;
            $this->moveTree($v, $newPids[$k], $sort, false);
        }
        $this->cleanCache();

        return true;
    }

    /**
     * 清除树形结构的缓存，不允许删除单个子树，由于一棵树是缓存在一个缓存里面
     * @return bool 是否清除成功
     */
    public function cleanCache()
    {
        unset(self::$tree[$this->cacheKey]);
        unset(self::$tree[$this->cacheKey.'_hash']);
        self::$tree = array();
        model('Cache')->rm($this->cacheKey);
        model('Cache')->rm($this->cacheKey.'_hash');

        return true;
    }

    /*** 私有方法 ***/
    /**
     * 双数组生成整棵树，缓存操作
     */
    private function createTree()
    {
        // 从数据库取树的数据
        $data = $this->_getTreeData();
        if (empty($data)) {
            return array();
        }
        $tree = array();        // 临时树
        $child = array();        // 所有节点的子节点
        $hash = array();        // Hash缓存数组
        foreach ($data as $dv) {
            $hash[$dv[$this->defaultField['id']]] = $dv;
            $tree[$dv[$this->defaultField['id']]] = $dv;
            !isset($child[$dv[$this->defaultField['id']]]) && $child[$dv[$this->defaultField['id']]] = array();
            $tree[$dv[$this->defaultField['id']]]['_child'] = &$child[$dv[$this->defaultField['id']]];
            $child[$dv[$this->defaultField['pid']]][] = &$tree[$dv[$this->defaultField['id']]];
        }
        //整个树，其根节点ID为0
        $tree[0]['_child'] = $child[0];

        self::$tree[$this->cacheKey] = $tree;
        self::$hash[$this->cacheKey] = $hash;
        // 生成缓存
        model('Cache')->set($this->cacheKey, $tree);
        model('Cache')->set($this->cacheKey.'_hash', $hash);
    }

    /**
     * 获取树形数据，从数据库中取出
     * @return array 所有分类的数据
     */
    private function _getTreeData()
    {
        // 下面是从数据库读取的 分类很少会有大量数据
        $data = D('')->table($this->_table)->order($this->defaultField['sort'].' ASC')->findAll();

        return $data;
    }
}
