<?php
/**
 * 广告位插件模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class AdSpaceModel extends Model
{
    protected $tableName = 'ad';
    protected $_error;

    /**
     * 添加广告位数据
     * @param  array $data 广告位相关数据
     * @return bool  是否插入成功
     */
    public function doAddAdSpace($data)
    {
        $data['display_order'] = $this->count();
        $res = $this->add($data);

        return (boolean) $res;
    }

    /**
     * 获取广告位列表数据
     * @return array 广告位列表数据
     */
    public function getAdSpaceList()
    {
        $data = $this->order('display_order DESC')->findPage(20);

        return $data;
    }

    /**
     * 删除广告位操作
     * @param  string|array $ids 广告位ID
     * @return bool         是否删除广告位成功
     */
    public function doDelAdSpace($ids)
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        if (empty($ids)) {
            return false;
        }
        $map['ad_id'] = array('IN', $ids);
        $res = $this->where($map)->delete();

        return (boolean) $res;
    }

    /**
     * 获取指定ID的广告位信息
     * @param  int   $id 广告位ID
     * @return array 指定ID的广告位信息
     */
    public function getAdSpace($id)
    {
        if (empty($id)) {
            return array();
        }
        $map['ad_id'] = $id;
        $data = $this->where($map)->find();

        return $data;
    }

    /**
     * 编辑广告位操作
     * @param int $id 广告位ID
     * @param  array $data 广告位相关数据
     * @return bool 是否编辑成功
     */
    public function doEditAdSpace($id, $data)
    {
        if (empty($id)) {
            return false;
        }
        $map['ad_id'] = $id;
        $res = $this->where($map)->save($data);

        return (boolean) $res;
    }

    /**
     * 移动广告位操作
     * @param  int  $id     广告位ID - A
     * @param  int  $baseId 广告位ID - B
     * @return bool 是否移动成功
     */
    public function doMvAdSpace($id, $baseId)
    {
        $map['ad_id'] = array('IN', array($id, $baseId));
        $order = $this->where($map)->getHashList('ad_id', 'display_order');
        if (count($order) < 2) {
            return false;
        }
        $this->where('`ad_id`='.$id)->setField('display_order', $order[$baseId]);
        $this->where('`ad_id`='.$baseId)->setField('display_order', $order[$id]);

        return true;
    }

    /**
     * 通过位置ID获取相应的广告信息
     * @param  int   $place 位置ID
     * @return array 位置ID获取相应的广告信息
     */
    public function getAdSpaceByPlace($place)
    {
        if (empty($place)) {
            return array();
        }
        // 获取信息
        $map['place'] = $place;
        $map['is_active'] = 1;
        $data = $this->where($map)->order('display_order DESC')->findAll();

        return $data;
    }
}
