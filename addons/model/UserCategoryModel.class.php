<?php
/**
 * 用户身份模型 - 数据对象模型
 * @zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class UserCategoryModel extends Model
{
    protected $tableName = 'user_category';
    protected $fields = array(0 => 'user_category_id', 1 => 'title', 2 => 'pid');

    /**
     * 当指定pid时，查询该父用户身份的所有子用户身份；否则查询所有用户身份
     * @param  int   $pid 父用户身份ID
     * @return array 相应用户身份列表
     */
    public function getUserCategoryListr($pid = -1)
    {
        $map = array();
        $pid != -1 && $map['pid'] = $pid;
        $data = $this->where($map)->order('`user_category_id` ASC')->findAll();

        return $data;
    }

    /**
     * 清除用户身份缓存
     */
    public function remakeUserCategoryCache()
    {
        model('Cache')->rm('UserCategoryTree');
    }

    /**
     * 获取指定父身份的树形结构
     * @param  int   $pid 父身份ID
     * @return array 指定树形结构
     */
    public function getNetworkList($pid = '0')
    {
        // 子身份树形结构
        if ($pid != 0) {
            return $this->_MakeTree($pid);
        }
        // 全部身份树形结构
        $list = model('Cache')->get('UserCategoryTree');
        if (empty($list)) {
            set_time_limit(0);
            $list = $this->_MakeTree($pid);
            model('Cache')->set('UserCategoryTree', $list);
        }

        return $list;
    }

    /**
     * 递归形成树形结构
     * @param  int   $pid   父级ID
     * @param  int   $level 等级
     * @return array 树形结构
     */
    private function _MakeTree($pid, $level = '0')
    {
        $result = $this->where('pid='.$pid)->order('sort ASC')->findAll();
        if ($result) {
            foreach ($result as $key => $value) {
                $id = $value['user_category_id'];
                $list[$id]['id'] = $value['user_category_id'];
                $list[$id]['pid'] = $value['pid'];
                $list[$id]['title'] = $value['title'];
                $list[$id]['level'] = $level;
                $list[$id]['child'] = $this->_MakeTree($value['user_category_id'], $level + 1);
            }
        }

        return $list;
    }

    /**
     * 添加用户与用户身份的关联信息
     * @param  int  $uid 用户ID
     * @param  int  $cid 用户身份ID
     * @return bool 是否添加成功
     */
    public function addRelatedUser($uid, $cid)
    {
        $map['uid'] = $add['uid'] = $uid;
        $map['user_category_id'] = $add['user_category_id'] = $cid;
        $count = D('user_category_link')->where($map)->count();
        if ($count > 0) {
            return false;
        }
        $result = D('user_category_link')->add($add);

        return (boolean) $result;
    }

    /**
     * 删除用户与用户身份的关联信息
     * @param  int  $uid 用户ID
     * @param  int  $cid 用户身份ID
     * @return bool 是否删除成功
     */
    public function deleteRelatedUser($uid, $cid)
    {
        $map['uid'] = $uid;
        $map['user_category_id'] = $cid;
        $count = D('user_category_link')->where($map)->count();
        if ($count < 1) {
            return false;
        }
        $result = D('user_category_link')->where($map)->delete();

        return (boolean) $result;
    }

    /**
     * 更改用户与用户身份的关联信息
     * @param  int   $uid  用户ID
     * @param  array $cids 用户身份ID数组
     * @return bool  是否修改成功
     */
    public function updateRelateUser($uid, $cids)
    {
        $map['uid'] = $uid;
        // 删除原有的数据
        D('user_category_link')->where($map)->delete();
        // 添加新的身份关联数据
        $add['uid'] = $uid;
        foreach ($cids as $value) {
            $add['user_category_id'] = $value;
            D('user_category_link')->add($add);
        }

        return true;
    }

    /**
     * 获取指定用户的身份信息
     */
    public function getRelatedUserInfo($uid)
    {
        $map['ucl.uid'] = $uid;
        $data = D('')->table('`'.$this->tablePrefix.'user_category` AS uc LEFT JOIN `'.$this->tablePrefix.'user_category_link` AS ucl ON uc.user_category_id = ucl.user_category_id')
                     ->field('uc.*')
                     ->where($map)
                     ->findAll();

        return $data;
    }

    /**
     * 获取身份的哈希列表
     * @param  array $map 查询条件
     * @return array 身份的哈希列表数组
     */
    public function getAllHash($map)
    {
        $data = $this->where($map)->getHashList('user_category_id', 'title');

        return $data;
    }

    /**
     * 获取指定分类下的用户ID
     * @param  int   $cid            分类ID
     * @param  int   $isAuthenticate 是否是认证用户，1表示是，0表示不是
     * @param  int   $limit          每页显示多少个
     * @return array 指定分类下的用户ID
     */
    public function getUidsByCid($cid, $isAuthenticate, $limit = 20)
    {
        if ($isAuthenticate == 1) {
            // 由于认证用户的用户组为5 - 将不会被改变
            $map['a.user_category_id'] = intval($cid);
            $map['b.user_group_id'] = 5;
            $data = D('')->table('`'.$this->tablePrefix.'user_category_link` AS a LEFT JOIN `'.$this->tablePrefix.'user_group_link` AS b ON a.uid = b.uid')
                         ->field('a.uid')
                         ->where($map)
                         ->findPage();
        } else {
            $umap['is_active'] = 1;
            $umap['is_audit'] = 1;
            $umap['is_init'] = 1;
            if ($cid == 0) {
                // 				$count = D('')->table($this->tablePrefix.'user_category_link')->count(array(), 'DISTINCT `uid`');
// 				$data = D('')->table($this->tablePrefix.'user_category_link')->field('DISTINCT `uid`')->findPage(20, $count);
                $data = model('User')->where($umap)->field('uid')->order('last_post_time DESC,last_login_time DESC')->findPage($limit);

                return $data;
            } else {
                // 按分类查找
                // $pid = $this->where('user_category_id='.$cid)->getField('pid');
                // if($pid == 0) {
                // 	$cids = $this->where('pid='.$cid)->getAsFieldArray('user_category_id');
                // 	$map['user_category_id'] = array('IN', $cids);
                // } else {
                // 	$map['user_category_id'] = intval($cid);
                // }
                // $uids = D('')->table($this->tablePrefix.'user_category_link')->field('`uid`')->where($map)->findAll();

                // $umap['uid'] = array( 'in' , getSubByKey( $uids , 'uid') );
                // $data = model('User')->where($umap)->field('uid')->order('last_post_time DESC,last_login_time DESC')->findPage($limit);
                // return $data;

                // 按标签查找
                $pid = M('UserCategory')->where('user_category_id='.$cid)->getField('pid');
                if ($pid == 0) {
                    $cids = M('UserCategory')->where('pid='.$cid)->getAsFieldArray('user_category_id');

                    $cmap['user_category_id'] = array('IN', $cids);

                    $title = M('UserCategory')->where($cmap)->findAll();

                    foreach ($title as $key => $value) {
                        $amap['name'] = array('LIKE', $value['title']);
                        $tag = M('tag')->where($amap)->getField('tag_id');
                        if ($tag) {
                            $tag_id[] = $tag;
                        }
                    }
                    $tmap['tag_id'] = array('IN', $tag_id);
                } else {
                    $cmap['user_category_id'] = intval($cid);
                    $title = M('UserCategory')->where($cmap)->find();
                    $amap['name'] = array('LIKE', $title['title']);
                    $tag_id[] = M('tag')->where($amap)->getField('tag_id');
                    $tmap['tag_id'] = array('IN', $tag_id);
                }
                $uids = M('app_tag')->field('`row_id`')->where($tmap)->findAll();
                $umap['uid'] = array('in', getSubByKey($uids, 'row_id'));
                $data = model('User')->where($umap)->field('uid')->order('last_post_time DESC,last_login_time DESC')->findPage($limit);

                return $data;
            }
        }
        $ordermap['uid'] = array('in', getSubByKey($data['data'], 'uid'));
        $uiddata = model('User')->where($ordermap)->field('uid')->order('last_post_time DESC,last_login_time DESC')->findAll();
        $data['data'] = $uiddata;

        return $data;
    }

    /**
     * 获取指定分类下的用户ID
     * @param  array $post           分类数据
     * @param  int   $isAuthenticate 是否是认证用户，1表示是，0表示不是
     * @return array 指定分类下的用户ID
     */
    public function w3g_getUidsByCid($data, $isAuthenticate)
    {
        $cid = intval($data['cid']);
        $lastUid = intval($data['lastUid']);
        $limit = intval($data['limit']);
        if ($isAuthenticate == 1) {
            // 由于认证用户的用户组为5 - 将不会被改变
            $lastUid && $map['a.uid'] = array('lt', $lastUid);
            $map['a.user_category_id'] = intval($cid);
            $map['b.user_group_id'] = 5;
            $data['data'] = D('')->table('`'.$this->tablePrefix.'user_category_link` AS a LEFT JOIN `'.$this->tablePrefix.'user_group_link` AS b ON a.uid = b.uid')
                         ->field('a.uid')
                         ->where($map)
                         ->order('a.uid desc')
                         ->limit($limit)
                         ->findALL();
        } else {
            $umap['is_active'] = 1;
            $umap['is_audit'] = 1;
            $umap['is_init'] = 1;
            $lastUid && $umap['uid'] = array('lt', $lastUid);
            if ($cid == 0) {
                // 				$count = D('')->table($this->tablePrefix.'user_category_link')->count(array(), 'DISTINCT `uid`');
// 				$data = D('')->table($this->tablePrefix.'user_category_link')->field('DISTINCT `uid`')->findPage(20, $count);
                $data['data'] = model('User')->where($umap)
                                        ->field('uid')
                                        ->order('uid desc')
                                        ->limit($limit)
                                        ->findAll();
                                        //->order('last_post_time DESC,last_login_time DESC')
                return $data;
            } else {
                // 按分类查找
                // $pid = $this->where('user_category_id='.$cid)->getField('pid');
                // if($pid == 0) {
                // 	$cids = $this->where('pid='.$cid)->getAsFieldArray('user_category_id');
                // 	$map['user_category_id'] = array('IN', $cids);
                // } else {
                // 	$map['user_category_id'] = intval($cid);
                // }
                // $uids = D('')->table($this->tablePrefix.'user_category_link')->field('`uid`')->where($map)->findAll();

                // $umap['uid'] = array( 'in' , getSubByKey( $uids , 'uid') );
                // $data = model('User')->where($umap)->field('uid')->order('last_post_time DESC,last_login_time DESC')->findPage($limit);
                // return $data;

                // 按标签查找
                $pid = M('UserCategory')->where('user_category_id='.$cid)->getField('pid');
                if ($pid == 0) {
                    $cids = M('UserCategory')->where('pid='.$cid)->getAsFieldArray('user_category_id');

                    $cmap['user_category_id'] = array('IN', $cids);

                    $title = M('UserCategory')->where($cmap)->findAll();

                    foreach ($title as $key => $value) {
                        $amap['name'] = array('LIKE', $value['title']);
                        $tag = M('tag')->where($amap)->getField('tag_id');
                        if ($tag) {
                            $tag_id[] = $tag;
                        }
                    }
                    $tmap['tag_id'] = array('IN', $tag_id);
                } else {
                    $cmap['user_category_id'] = intval($cid);
                    $title = M('UserCategory')->where($cmap)->find();
                    $amap['name'] = array('LIKE', $title['title']);
                    $tag_id[] = M('tag')->where($amap)->getField('tag_id');
                    $tmap['tag_id'] = array('IN', $tag_id);
                }
                $lastUid && $tmap['row_id'] = array('lt', $lastUid);
                $uids = M('app_tag')->field('`row_id`')->where($tmap)->order('row_id desc')->findAll();
                $umap['uid'] = array('in', getSubByKey($uids, 'row_id'));
                $data['data'] = model('User')->where($umap)
                                     ->field('uid')
                                     ->order('uid desc')
                                     ->limit($limit)
                                     ->findAll();

                return $data;
            }
        }
        $ordermap['uid'] = array('in', getSubByKey($data['data'], 'uid'));
        $uiddata = model('User')->where($ordermap)
                                ->field('uid')
                                ->order('uid desc')
                                ->limit($limit)
                                ->findAll();
        $data['data'] = $uiddata;

        return $data;
    }

    /**
     * 获取所有身份分类ID
     * @return array 所有身份分类ID
     */
    public function getAllUserCategoryIds()
    {
        $data = $this->getAsFieldArray('user_category_id');

        return $data;
    }
}
