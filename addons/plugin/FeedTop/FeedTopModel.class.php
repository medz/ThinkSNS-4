<?php
/**
 * 分享置顶插件模型 - 数据对象模型
 * @author feebas <reedgu@163.com>
 * @version TS3.0
 */
class FeedTopModel extends Model
{
    protected $tableName = 'feed_top';
    protected $_error;
    /**
     * 添加分享置顶数据
     * @param  array $data 广告位相关数据
     * @return bool  是否插入成功
     */
    public function doAddFeedTop($data)
    {
        $res = $this->add($data);

        return (boolean) $res;
    }
    /**
     * 获取分享置顶数据
     * @return array 广告位列表数据
     */
    public function getFeedTopList($type)
    {
        if ($type == 1) {
            $data = $this->where('status = 0')->order('id DESC')->findAll();
        } elseif ($type == 0) {
            $data = $this->limit(6)->order('id DESC')->findAll();
        } else {
            $data = $this->order('id DESC')->findpage(20);
        }

        return $data;
    }
    public function doEditFeedTop($id, $data)
    {
        if (empty($id)) {
            return false;
        }
        $map['id'] = $id;
        $res = $this->where($map)->save($data);

        return (boolean) $res;
    }
    /**
     * 删除分享置顶操作
     * @param  string|array $ids 广告位ID
     * @return bool         是否删除广告位成功
     */
    public function doDelFeedTop($id)
    {
        if (empty($id)) {
            return false;
        }

        $map['id'] = $id;
        $data['status'] = 1;
        $res = $this->where($map)->save($data);

        return (boolean) $res;
    }
    public function doFeedTop($id)
    {
        if (empty($id)) {
            return false;
        }

        $map['id'] = $id;
        $data['status'] = 0;
        $res = $this->where($map)->save($data);

        return (boolean) $res;
    }
    public function doDel($id)
    {
        if (empty($id)) {
            return false;
        }
        $map['id'] = $id;
        $res = $this->where($map)->delete();

        return (boolean) $res;
    }

    public function checkedFeedTop($uid, $feedId)
    {
        if (empty($uid) || empty($feedId)) {
            return false;
        }

        $map['b.uid'] = $uid;
        $map['a.feed_id'] = $feedId;
        $count = $this->table(C('DB_PREFIX').'feed_top AS a LEFT JOIN '.C('DB_PREFIX').'feed AS b ON a.feed_id = b.feed_id')
                      ->where($map)
                      ->count();

        return ($count > 0) ? true : false;
    }

    public function setFeedTop($uid, $feedId)
    {
        if (empty($uid) || empty($feedId)) {
            return false;
        }

        $count = $this->checkedFeedTop($uid, $feedId);
        if ($count) {
            return false;
        }

        $data['feed_id'] = $feedId;
        $data['ctime'] = time();
        $result = $this->add($data);

        return (boolean) $result;
    }

    public function delFeedTop($uid, $feedId)
    {
        $checked = $this->checkedFeedTop($uid, $feedId);
        if (!$checked) {
            return false;
        }
        $map['feed_id'] = $feedId;
        $result = $this->where($map)->delete();

        return (boolean) $result;
    }
}
