<?php
/**
 * 频道关注模型 - 数据对象模型.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class ChannelFollowModel extends Model
{
    protected $tableName = 'channel_follow';

    /**
     * 获取指定分类的关注数目.
     *
     * @param int $cid 频道分类ID
     *
     * @return int 指定分类的关注数目
     */
    public function getFollowingCount($cid)
    {
        !empty($cid) && $map['channel_category_id'] = intval($cid);
        $count = $this->where($map)->count();

        return $count;
    }

    /**
     * 更新频道的关注状态
     *
     * @param int    $uid  关注用户ID
     * @param int    $cid  频道分类ID
     * @param string $type 更新频道操作，add or del
     *
     * @return bool 更新频道关注状态是否成功
     */
    public function upFollow($uid, $cid, $type)
    {
        // 验证数据的正确性
        if (empty($uid) || empty($cid)) {
            return false;
        }
        $result = false;
        // 更新状态修改
        switch ($type) {
            case 'add':
                // 验证是否已经添加关注
                $map['uid'] = $uid;
                $map['channel_category_id'] = $cid;
                $isExist = $this->where($map)->count();
                if ($isExist == 0) {
                    $data['uid'] = $uid;
                    $data['channel_category_id'] = $cid;
                    $result = $this->add($data);
                    $result = (bool) $result;
                }
                break;
            case 'del':
                $map['uid'] = $uid;
                $map['channel_category_id'] = $cid;
                $result = $this->where($map)->delete();
                $result = (bool) $result;
                break;
        }

        return $result;
    }

    /**
     * 获取指定用户与指定频道分类的关注状态
     *
     * @param int $uid 用户ID
     * @param int $cid 频道分类ID
     *
     * @return bool 返回是否关注
     */
    public function getFollowStatus($uid, $cid)
    {
        $map['uid'] = $uid;
        $map['channel_category_id'] = $cid;
        $count = $this->where($map)->count();
        $result = ($count == 0) ? false : true;

        return $result;
    }

    /**
     * 获取指定用户的关注列表.
     *
     * @param int $uid 指定用户ID
     *
     * @return array 指定用户的关注列表
     */
    public function getFollowList($uid)
    {
        if (empty($uid)) {
            return array();
        }
        $map['f.uid'] = $uid;
        $list = D()->table('`'.C('DB_PREFIX').'channel_follow` AS f LEFT JOIN `'.C('DB_PREFIX').'channel_category` AS c ON f.channel_category_id=c.channel_category_id')
                   ->field('c.`channel_category_id`, c.`title`, c.`ext`, c.`sort`')
                   ->where($map)
                   ->findAll();

        return $list;
    }

    /**
     * 获取指定用户所关注频道的所有分享，默认为当前登录用户.
     *
     * @param string $where 查询条件
     * @param int    $limit 结果集数目，默认为10
     * @param int    $uid   指定用户ID，默认为空
     * @param int    $fgid  关注频道ID，默认为空
     *
     * @return array 指定用户所关注频道的所有分享，默认为当前登录用户
     */
    public function getFollowingFeed($where = '', $limit = 10, $uid = '', $fgid = '')
    {
        $buid = empty($uid) ? $GLOBALS['ts']['mid'] : $uid;
        $fgid = intval($fgid);
        $uid = intval($uid);
        $where .= ' AND b.uid='.$buid;
        $where .= ' AND a.status=1';
        $table = "`{$this->tablePrefix}channel` AS a LEFT JOIN `{$this->tablePrefix}channel_follow` AS b ON a.channel_category_id = b.channel_category_id LEFT JOIN `{$this->tablePrefix}feed` AS c ON a.feed_id = c.feed_id";
        !empty($fgid) && $where .= ' AND b.channel_category_id = '.$fgid;
        $feedList = D()->table($table)->field('a.feed_id')->where($where)->order('c.publish_time DESC')->findPage($limit);
        $feedIds = getSubByKey($feedList['data'], 'feed_id');
        $feedList['data'] = model('Feed')->getFeeds($feedIds);

        return $feedList;
    }
}
