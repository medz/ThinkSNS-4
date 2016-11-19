<?php
/**
 * 官方用户分类模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class UserOfficialCategoryModel extends Model
{
    protected $tableName = 'user_official_category';

    /**
     * 当指定pid时，查询该父分类的所有子分类；否则查询所有分类
     * @param  int   $pid 父分类ID
     * @return array 相应的分类列表
     */
    public function getCategoryList($pid = -1)
    {
        $map = array();
        $pid != -1 && $map['pid'] = $pid;
        $data = $this->where($map)->order('`official_category_id` ASC')->findAll();

        return $data;
    }

    /**
     * 判断名称是否重复
     * @param  string $title 名称
     * @return bool   是否重复
     */
    public function isTitleExist($title)
    {
        $map['title'] = t($title);
        $count = $this->where($map)->count();
        $result = ($count == 0) ? false : true;

        return $result;
    }

    /**
     * 获取指定分类的信息
     * @param  int   $cid 分类ID
     * @return array 分类信息
     */
    public function getCategoryInfo($cid)
    {
        $map['official_category_id'] = $cid;
        $data = $this->where($map)->find();

        return $data;
    }

    /**
     * 获取指定父地区的树形结构
     * @param  int   $pid 父地区ID
     * @return array 指定树形结构
     */
    public function getNetworkList($pid = '0')
    {
        // 子地区树形结构
        if ($pid != 0) {
            return $this->_MakeTree($pid);
        }
        // 全部地区树形结构
        $list = S('official');
        if (!$list) {
            set_time_limit(0);
            $list = $this->_MakeTree($pid);
            S('official', $list);
        }

        return $list;
    }

    /**
     * 清除地区数据PHP文件
     */
    public function remakeOfficialCache()
    {
        S('official', null);
    }

    /**
     * 递归形成树形结构
     * @param  int   $pid   父级ID
     * @param  int   $level 等级
     * @return array 树形结构
     */
    private function _MakeTree($pid, $level = '0')
    {
        $result = $this->where('pid='.$pid)->findAll();
        if ($result) {
            foreach ($result as $key => $value) {
                $id = $value['official_category_id'];
                $list[$id]['id'] = $value['official_category_id'];
                $list[$id]['pid'] = $value['pid'];
                $list[$id]['title'] = $value['title'];
                $list[$id]['level'] = $level;
                $list[$id]['child'] = $this->_MakeTree($value['official_category_id'], $level + 1);
            }
        }

        return $list;
    }

    /**
     * 获取分类的Hash数组
     */
    public function getCategoryHash($pid = -1)
    {
        $map = array();
        $pid != -1 && $map['pid'] = $pid;
        $data = $this->where($map)->getHashList('official_category_id', 'title');

        return $data;
    }
}
