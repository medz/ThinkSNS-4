<?php
/**
 * 地区模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class AreaModel extends Model
{
    protected $tableName = 'area';

    /**
     * 当指定pid时，查询该父地区的所有子地区；否则查询所有地区
     * @param  int   $pid   父地区ID
     * @param  array $where 额外条件
     * @return array 相应地区列表
     */
    public function getAreaList($pid = -1, array $where = array())
    {
        $pid != -1 and $where['pid'] = intval($pid);

        $name = 'ts_area_'.$pid.'_'.md5(json_encode($where));
        $data = S($name);
        if (!$data) {
            $data = $this->where($where)->order('`area_id` ASC')->select();
            S($name, $data);
        }

        return $data;

        // $map = array();
        // $pid != -1 && $map['pid'] = $pid;
        // $data = $this->where($map)->order('`area_id` ASC')->findAll();
        // return $data;
    }

    /**
     * 获取地区的树形结构 - 目前为两级结构 - TODO
     * @param  int   $pid 地区的父级ID
     * @return array 指定父级ID的树形结构
     */
    public function getAreaTree($pid)
    {
        $output = array();
        $list = $this->getAreaList();
        // 获取省级
        foreach ($list as $k1 => $p) {
            if ($p['pid'] == 0) {
                // 获取当前省的市
                $city = array();
                foreach ($list as $k2 => $c) {
                    if ($c['pid'] == $p['area_id']) {
                        $city[] = array($c['area_id'] => $c['title']);
                        unset($list[$k2]);
                    }
                }
                $output['provinces'][] = array(
                    'id' => $p['area_id'],
                    'name' => $p['title'],
                    'citys' => $city,
                );
                unset($list[$k1], $city);
            }
        }
        unset($list);

        return $output;
    }

    /**
     * 获取指定地区ID下的地区信息
     * @param  int   $id 地区ID
     * @return array 指定地区ID下的地区信息
     */
    public function getAreaById($id)
    {
        $result = array();
        if (!empty($id)) {
            $name = 'ts_area_aid_'.$id;
            $result = S($name);
            if (!$result) {
                $map['area_id'] = $id;
                $result = $this->where($map)->find();
                S($name, $result);
            }
        }

        return $result;
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
        $list = S('city');
        if (empty($list)) {
            set_time_limit(0);
            $list = $this->_MakeTree($pid);
            S('city', $list);
        }

        return $list;
    }

    /**
     * 清除地区数据PHP文件
     */
    public function remakeCityCache()
    {
        S('city', null);
    }

    /**
     * 递归形成树形结构
     * @param  int   $pid   父级ID
     * @param  int   $level 等级
     * @return array 树形结构
     */
    private function _MakeTree($pid, $level = '0')
    {
        $name = 'ts_area_tree_'.$pid.'_'.$level;
        $list = S($name);
        if (!$list) {
            $result = $this->where('pid='.$pid)->findAll();
            if ($result) {
                foreach ($result as $key => $value) {
                    $id = $value['area_id'];
                    $list[$id]['id'] = $value['area_id'];
                    $list[$id]['pid'] = $value['pid'];
                    $list[$id]['title'] = $value['title'];
                    $list[$id]['level'] = $level;
                    $list[$id]['child'] = $this->_MakeTree($value['area_id'], $level + 1);
                }
            }
            S($name, $list);
        }

        return $list;
    }
}
