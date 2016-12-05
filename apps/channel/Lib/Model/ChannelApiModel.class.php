<?php
/**
 * 频道API模型.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version ts3.0
 */
class ChannelApiModel
{
    /**
     * 获取所有频道分类数据.
     *
     * @return array 所有频道分类数据
     */
    public function getAllChannel($uid)
    {
        $data = model('CategoryTree')->setTable('channel_category')->getCategoryAllHash();
        // 组装附件信息
        $attachIds = getSubByKey($data, 'attach');
        $attachIds = array_filter($attachIds);
        $attachIds = array_unique($attachIds);
        $attachInfos = model('Attach')->getAttachByIds($attachIds);
        $attachData = array();

        foreach ($attachInfos as $attach) {
            $attachData[$attach['attach_id']] = $attach;
        }

        foreach ($data as &$value) {
            if (!empty($value['attach']) && !empty($attachData[$value['attach']])) {
                $value['icon_url'] = getImageUrl($attachData[$value['attach']]['save_path'].$attachData[$value['attach']]['save_name']);
            } else {
                $value['icon_url'] = null;
            }
            unset($value['ext'], $value['attach'], $value['user_bind'], $value['topic_bind']);
            if ($uid) {
                $value['followStatus'] = intval(D('ChannelFollow', 'channel')->getFollowStatus($uid, $value['channel_category_id']));
            }
        }

        return $data;
    }

    /**
     * 获取频道分类数据.
     *
     * @return array 所有频道分类数据
     */
    public function getChannels($uid, $count = 6, $page = 1, $order = 'sort asc', $sql = '')
    {
        $start = ($page - 1) * $count;
        $end = $count;
        $data = D('channel_category')->where($sql)->order($order)->limit("$start, $end")->findAll();
        foreach ($data as $k => $v) {
            if (!empty($v['ext'])) {
                $ext = unserialize($v['ext']);
                $v = array_merge($v, $ext);
            }
            $data[$k] = $v;
        }
        // 组装附件信息
        $attachIds = getSubByKey($data, 'attach');
        $attachIds = array_filter($attachIds);
        $attachIds = array_unique($attachIds);
        $attachInfos = model('Attach')->getAttachByIds($attachIds);
        $attachData = array();

        foreach ($attachInfos as $attach) {
            $attachData[$attach['attach_id']] = $attach;
        }

        foreach ($data as &$value) {
            if (!empty($value['attach']) && !empty($attachData[$value['attach']])) {
                $value['icon_url'] = getImageUrl($attachData[$value['attach']]['save_path'].$attachData[$value['attach']]['save_name']);
            } else {
                $value['icon_url'] = null;
            }
            unset($value['ext'], $value['attach'], $value['user_bind'], $value['topic_bind']);
            if ($uid) {
                $value['followStatus'] = intval(D('ChannelFollow', 'channel')->getFollowStatus($uid, $value['channel_category_id']));
            }
        }

        return $data;
    }

    /**
     * 获取频道下的分享.
     */
    public function getChannelFeed($cid, $type, $count, $page, $order, $sql = '')
    {
        // $cid = intval($cid);
        $count = intval($count);
        $page = intval($page);
        // 组装查询条件
        $where = 'c.status = 1';
        if ($cid) {
            if (is_array($cid)) {
                $where .= ' AND c.channel_category_id in ('.implode(',', $cid).')';
            } else {
                $where .= ' AND c.channel_category_id = '.intval($cid);
            }
        }
        if ($type) {
            $where .= " AND f.type='".$type."'";
        }
        if (!$order) {
            $order = 'c.feed_id DESC';
        }
        if ($sql) {
            $where .= $sql;
        }
        $start = ($page - 1) * $count;
        $end = $count;
        $sql = 'SELECT distinct c.feed_id FROM `'.C('DB_PREFIX').'channel` c LEFT JOIN `'.C('DB_PREFIX').'feed` f ON c.feed_id = f.feed_id WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$start.', '.$end.'';
        $feedIds = getSubByKey(D()->query($sql), 'feed_id');
        $data = model('Feed')->formatFeed($feedIds, true);

        return $data;
    }
}
