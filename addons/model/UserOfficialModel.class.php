<?php
/**
 * 官方用户模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class UserOfficialModel extends Model
{
    protected $tableName = 'user_official';
    protected $fields = array(0 => 'uid', 1 => 'info', 2 => 'user_official_category_id');
    /**
     * 获取指定官方用户的信息
     * @param  array $uids 用户ID
     * @return array 指定官方用户的信息
     */
    public function getUserOfficialInfo($uids)
    {
        if (empty($uids)) {
            return array();
        }
        $map['uid'] = array('IN', $uids);
        $data = $this->where($map)->getHashList('uid', 'info');

        return $data;
    }

    /**
     * 添加官方用户信息
     * @param array $uids 添加用户ID数组
     * @param int   $cid  官方用户分类ID
     * @param  string $info 相关信息
     * @return bool 是否添加成功
     */
    public function addOfficialUser($uids, $cid, $info)
    {
        $uids = is_array($uids) ? $uids : explode(',', $uids);
        if (empty($uids) || empty($cid)) {
            return false;
        }
        // 添加用户信息
        $data['user_official_category_id'] = $cid;
        $data['info'] = $info;
        foreach ($uids as $uid) {
            // 判断是否添加
            $map['user_official_category_id'] = $cid;
            $map['uid'] = $uid;
            $isExist = $this->where($map)->count();
            if ($isExist == 0) {
                $data['uid'] = $uid;
                $this->add($data);
            }
        }

        return true;
    }

    /**
     * 获取官方用户列表
     * @return array 官方用户列表
     */
    public function getUserOfficialList()
    {
        // 获取列表
        $list = $this->where($map)->findPage();
        // 获取用户ID数组
        $uids = getSubByKey($list['data'], 'uid');
        // 获取用户信息
        $userInfos = model('User')->getUserInfoByUids($uids);
        // 获取分类信息
        $category = model('CategoryTree')->setTable('user_official_category')->getCategoryHash();
        foreach ($list['data'] as &$value) {
            $value = array_merge($value, $userInfos[$value['uid']]);
            $value['title'] = $category[$value['user_official_category_id']];
        }

        return $list;
    }

    /**
     * 移除官方用户
     * @param  array $ids 官方用户表主键ID
     * @return bool  是否成功移除官方用户
     */
    public function removeUserOfficial($ids)
    {
        // 格式化数据
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        // 验证数据的正确性
        if (empty($ids)) {
            return false;
        }
        // 移除用户
        $map['official_id'] = array('IN', $ids);
        $res = $this->where($map)->delete();

        return (boolean) $res;
    }

    /**
     * 删除分类关联信息
     * @param  int  $cid 分类ID
     * @return bool 是否删除成功
     */
    public function deleteAssociatedData($cid)
    {
        if (empty($cid)) {
            return false;
        }
        // 删除官方用户分类下的数据
        $map['user_official_category_id'] = $cid;
        $this->where($map)->delete();

        return true;
    }
}
