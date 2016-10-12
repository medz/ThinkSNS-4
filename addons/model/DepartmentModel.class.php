<?php
/**
 * 部门模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class DepartmentModel extends Model
{
    const FIELD_ID = 34;                    // 部门的字段ID
    const FIELD_KEY = 'department';            // 部门的字段KEY

    protected $tableName = 'department';
    protected $fields = array(0 => 'department_id', 1 => 'title', 2 => 'parent_dept_id', 3 => 'display_order', 4 => 'ctime');

    protected $treeDo;                    // 分类树模型

    /**
     * 初始化方法，生成部门的树形对象模型
     */
    public function _initialize()
    {
        $field = array('id' => 'department_id', 'name' => 'title', 'pid' => 'parent_dept_id', 'sort' => 'display_order');
        tsload(ADDON_PATH.'/model/CateTreeModel');
        $this->treeDo = new CateTreeModel('department');
        $this->treeDo->setField($field);
    }

    /**
     * 获取部门信息的树形结构
     * @param  int   $pid 父级ID，默认为0
     * @return array 部门信息的树形结构
     */
    public function getDepartment($pid = 0)
    {
        $data = $this->treeDo->getTree($pid);

        return $data;
    }

    /**
     * 获取部门全部分类的Hash数组
     * @return array 部门分类的Hash数组
     */
    public function getAllHash()
    {
        return $this->treeDo->getAllHash();
    }

    /**
     * 获取指定子树的部门Hash数组
     * @param  int   $pid   父级ID
     * @param  int   $sid   资源节点ID
     * @param  int   $nosid 是否包含资源节点ID，默认为0
     * @return array 指定子树的部门Hash数组
     */
    public function getHashDepartment($pid = 0, $sid = '', $nosid = 0)
    {
        $treeHash = $this->treeDo->getAllHash();
        $pid == 0 && $optHash[0] = L('PUBLIC_TOP_DEPARTMENT') ;            // 顶级部门
        foreach ($treeHash as $k => $v) {
            if ($nosid == 1 && !empty($sid) && $k == $sid) {
                continue;
            }
            $v['parent_dept_id'] == $pid && $optHash[$k] = $v['title'];
        }

        return $optHash;
    }

    /**
     * 清除部门缓存
     */
    public function cleanCache()
    {
        return $this->treeDo->cleanCache();
    }

    /**
     * 添加一个部门信息
     * @param  array $data 新部门相关信息
     * @return bool  是否添加成功
     */
    public function addDepart($data)
    {
        if (empty($data)) {
            return false;
        }
        if (empty($data['title'])) {
            return false;
        }
        // 添加部门操作
        $add['title'] = t($data['title']);
        $add['parent_dept_id'] = $this->_getParent_dept($data);
        $add['display_order'] = isset($data['display_order']) && !empty($data['display_order']) ? $data['display_order'] : 255;
        $add['ctime'] = time();

        if ($this->add($add)) {
            $this->cleanCache();

            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取指定分类的上级ID
     * @param  array $data 指定分类的相关数据
     * @return int   指定分类的上级ID
     */
    public function _getParent_dept($data)
    {
        $pid = $data['parent_dept_id'];
        if (!empty($data['_parent_dept_id'])) {
            $level = count($data['_parent_dept_id']);
            $_P = $data['_parent_dept_id'][$level - 2] ? $data['_parent_dept_id'][$level - 2] : $pid;
            $pid = $data['_parent_dept_id'][$level - 1] > 0 ? $data['_parent_dept_id'][$level - 1] : $_P;
        }

        return intval($pid);
    }

    /**
     * 删除指定部门操作
     * @param  int  $id  指定分类ID
     * @param  int  $pid 父级分类ID
     * @return bool 是否删除成功
     */
    public function delDepart($id, $pid = 0)
    {
        if (empty($id)) {
            return false;
        }
        // 如果子部门不是移动到顶级下面，则判断是否移动到自己子集下面
        if ($pid != 0) {
            $data = $this->treeDo->getChildHash($id);
            if (in_array($pid, $data)) {
                return false;
            }
        }
        $map = array();
        $map['department_id'] = $id;
        // 获取子节点
        $tree = $this->treeDo->getTree($id);
        // 移动子节点
        if (!empty($tree['_child'])) {
            foreach ($tree['_child'] as $c) {
                $this->moveDepart($c['department_id'], $pid);
            }
        }
        // 移动当前部门用户到新的部门
        $oldTreeName = $this->getTreeName($id);
        $newTreeName = $this->getTreeName($pid);
        $this->editUserProfile($oldTreeName, $newTreeName);
        // 修改当前部门下用户的部门关联表信息
        $ids = $this->getTreeId($pid);
        $this->updateUserDepart(implode('|', $newTreeName).'|', $ids);

        if ($this->where($map)->delete()) {
            $this->editUserProfile();
            // 移动子集
            $upmap = array();
            $upmap['parent_dept_id'] = $id;
            $save['parent_dept_id'] = $pid;
            $this->where($upmap)->save($save);

            $fieldids = D('user_profile_setting')->where("form_type='selectDepart'")->field('field_id')->findAll();
            $fids = getSubByKey($fieldids, 'field_id');
            $profilemap['field_data'] = $id;
            $profilemap['field_id'] = array('in', $fids);
            D('user_profile')->setField('field_data', $pid, $profilemap);

            $this->cleanCache();

            return true;
        } else {
            return false;
        }
    }

    /**
     * 移动部门，将某个部门移动到新部门下面
     * @param  int  $id  预移动部门ID
     * @param  int  $pid 移动到的父级ID
     * @return bool 是否移动成功
     */
    public function moveDepart($id, $pid = 0)
    {
        if (empty($id)) {
            $this->error = L('PUBLIC_DEPARTMENT_ID_REQUIRED');            // 部门ID不能为空
            return false;
        }
        // 如果子部门不是移动到顶级下面，则判断是否移动到自己子集下面
        if ($pid != 0) {
            $data = $this->treeDo->getChildHash($id);
            if (in_array($pid, $data)) {
                return false;
            }
        }
        $map = array();
        $map['department_id'] = $id;
        $save['parent_dept_id'] = $pid;
        $oldTreeName = $this->getTreeName($id);

        if ($this->where($map)->save($save)) {
            $curName = $oldTreeName[count($oldTreeName) - 1];
            $newtreeName = array();
            if ($pid != 0) {
                $newTreeName = $this->getTreeName($pid);
            }
            $newTreeName[] = $curName;
            $this->cleanCache();
            // 替换用户字段表的冗余数据
            $this->editUserProfile($oldTreeName, $newTreeName);
            // 更新部门关联表数据
            $ids = $this->getTreeIdBySql($id);
            $this->updateUserDepart(implode('|', $newTreeName).'|', $ids);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取多个用户的部门信息
     * @param  array $uids 用户ID数组
     * @return array 多个用户的部门信息
     */
    public function getUserDepart($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        if (empty($uids)) {
            return array();
        }
        $map['uid'] = array('IN', $uids);
        $map['field_id'] = self::FIELD_ID;

        return D('user_profile')->where($map)->getHashList('uid', 'field_data');
    }

    /**
     * 获取指定用户的部门ID
     * @param  int $uid 用户ID
     * @return int 指定用户的部门ID
     */
    public function getUserDepartId($uid)
    {
        $department = $this->getUserDepart($uid);
        $d = explode('|', trim($department[$uid], '|'));
        $map['a.title'] = end($d);
        $map['b.uid'] = $uid;

        $departmentInfo = $this->table($this->tablePrefix.'department AS a LEFT JOIN '.$this->tablePrefix.'user_department AS b ON a.department_id = b.department_id')
                               ->where($map)
                               ->find();

        return $departmentInfo['department_id'];
    }

    /**
     * 更新部门为departMentTree的用户的关联表信息
     * @param string $treeName      树结构的名称
     * @param array  $departmentIds 部门ID数组
     */
    public function updateUserDepart($treeName, $departmentIds)
    {
        if (empty($treeName) && empty($departmentIds)) {
            return false;
        }
        $sql = "DELETE FROM {$this->tablePrefix}user_department
				WHERE uid IN (
						SELECT uid FROM {$this->tablePrefix}user_profile WHERE field_id ='".self::FIELD_ID."' AND field_data = '{$treeName}')";

        $this->query($sql);

        foreach ($departmentIds as $id) {
            $sql = "INSERT INTO {$this->tablePrefix}user_department
				SELECT uid,{$id} AS department_id FROM {$this->tablePrefix}user_profile WHERE field_id ='".self::FIELD_ID."' AND field_data = '{$treeName}' ";

            $this->query($sql);
        }
    }

    /**
     * 更新指定用户的部门信息
     * @param int $uid             用户ID
     * @param int $newDepartmentId 新的部门ID
     */
    public function updateUserDepartById($uid, $newDepartmentId)
    {
        if (empty($uid) || empty($newDepartmentId)) {
            return false;
        }
        $departmentTree = $this->getTreeName($newDepartmentId);
        $departmentIds = $this->getTreeId($newDepartmentId);
        if (empty($departmentIds)) {
            return false;
        }
        // TODO:后期事务处理
        $up['uid'] = $uid;
        $up['field_id'] = self::FIELD_ID;
        if (D('user_profile')->where($up)->count() > 0) {
            $sp['field_data'] = implode('|', $departmentTree).'|';
            D('user_profile')->where($up)->save($sp);
        } else {
            $up['field_data'] = implode('|', $departmentTree).'|';
            D('user_profile')->add($up);
        }

        // 修改用户部门关联表数据
        $ud['uid'] = $uid;
        D('user_department')->where($ud)->delete();
        $sql = "INSERT INTO {$this->tablePrefix}user_department (uid,department_id) VALUES ";

        foreach ($departmentIds as $department_id) {
            $sql .= "({$uid},{$department_id}),";
        }

        return $this->query(trim($sql, ','));
    }

    /**
     * 根据部门ID获取该部门的路径
     * @param  int   $id    部门ID
     * @param  array $names 部门名称
     * @return array 返回部门路径名称数组
     */
    public function getTreeName($id, $names = array())
    {
        return array_reverse($this->_getTreeName($id, $names));
    }

    /**
     * 递归方法获取父级部门名称
     * @param  int   $id    部门ID
     * @param  array $names 部门名称
     * @return array 返回部门路径名称数组
     */
    private function _getTreeName($id, $names)
    {
        $data = $this->treeDo->getTree($id);
        $names[] = $data['title'];
        if ($data['parent_dept_id'] != 0) {
            return $this->_getTreeName($data['parent_dept_id'], $names);
        }

        return $names;
    }

    /**
     * 获取从顶级到该级的父亲节点数组
     * @param  int   $id  当前节点的ID
     * @param  array $ids 附加的节点ID
     * @return array 从顶级到该级的父亲节点数组
     */
    public function getTreeId($id, $ids = array())
    {
        $ids[] = $id;
        $data = $this->treeDo->getTree($id);
        if ($data['parent_dept_id'] == 0) {
            return $ids;
        }

        return $this->getTreeId($data['parent_dept_id'], $ids);
    }

    /**
     * 获取从顶级到该级的父亲节点数组，查询数据库
     * @param  int   $id  当前节点的ID
     * @param  array $ids 附加的节点ID
     * @return array 从顶级到该级的父亲节点数组
     */
    public function getTreeIdBySql($id, $ids = array())
    {
        $ids[] = $id;
        $map['department_id'] = $id;
        $data = $this->where($map)->find();
        if ($data['parent_dept_id'] == 0) {
            return $ids;
        }

        return $this->getTreeIdBySql($data['parent_dept_id'], $ids);
    }

    /**
     * 批量修改部门名字，需要传入旧部门名称Tree和新名称Tree数组
     * @param  array $oldTreeName 旧部门名称Tree
     * @param  array $newTreeName 新部门名称Tree
     * @return mix   修改失败返回false，修改成功返回1
     */
    public function editUserProfile($oldTreeName, $newTreeName)
    {
        $old = implode('|', $oldTreeName).'|';
        $new = implode('|', $newTreeName).'|';
        $sql = "UPDATE `{$this->tablePrefix}user_profile` SET field_data = REPLACE(field_data,'{$old}','{$new}') WHERE field_id = ".self::FIELD_ID;

        return $this->query($sql);
    }

    /**
     * 测试使用
     * @return string 插入数据的SQL语句
     */
    public function initDepartMent()
    {
        // 将用户放到第一个部门下
        $data = $this->treeDo->getTree(0);
        $departName = $data['_child'][0]['title'].'|';
        $departId = $data['_child'][0]['department_id'];
        $sql = "INSERT INTO {$this->tablePrefix}user_profile SELECT uid,".self::FIELD_ID." AS field_id,'{$departName}' AS field_data,0 AS privacy FROM {$this->tablePrefix}user";
        echo $sql;
        $this->query($sql);
        $sql = "INSERT INTO {$this->tablePrefix}user_department SELECT uid,{$departId} AS department_id FROM {$this->tablePrefix}user";
        $this->query($sql);
        echo $sql;
    }

    /**
     * 定时任务同步钉钉部门信息.
     *
     * @author zhangwei
     * @date   2016-10-11
     *
     */
    public function synchronizeDepart()
    {
        $list = dingtalk_get_dept_list();
        if ($list->department) {
            foreach ($list->department as $key => $value) {
                $data[] = array(
                    'department_id' => $value->id,
                    'title' => $value->name,
                    'parent_dept_id' => $value->parentid ? $value->parentid : 0,
                );
            }

            $department = D('department')->field('department_id')->select();
            $department = getSubByKey($department, 'department_id');
            foreach ($data as $k => $v) {
                if (!in_array($v['department_id'], $department)) {
                    $diff = array(
                        'department_id' => $v['department_id'],
                        'title' => $v['title'],
                        'parent_dept_id' => $v['parent_dept_id'],
                        'ctime' => time(),
                    );
                    D('department')->add($diff);
                }
            }
        }
    }
}
