<?php
/**
 * 频道分类模型 - 数据对象模型.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class ChannelModel extends Model
{
    protected $tableName = 'channel';

    /**
     * 获取资源列表.
     *
     * @param array $map 查询条件
     *
     * @return array 获取资源列表
     */
    public function getChannelList($map)
    {
        // 获取资源分页结构
        $data = $this->field('DISTINCT `feed_id`, `feed_channel_link_id`, `status`')->where($map)->order('`feed_channel_link_id` DESC')->findPage();
        // 获取分享ID
        $feedIds = getSubByKey($data['data'], 'feed_id');
        // 获取分享分类频道信息
        $cmap['c.feed_id'] = array('IN', $feedIds);
        $categoryInfo = D()->table('`'.$this->tablePrefix.'channel` AS c LEFT JOIN `'.$this->tablePrefix.'channel_category` AS cc ON cc.channel_category_id = c.channel_category_id')
                           ->field('c.`feed_id`,c.`feed_channel_link_id`, c.`status`, cc.channel_category_id, cc.`title`')
                           ->where($cmap)
                           ->findAll();
        $categoryInfos = array();
        foreach ($categoryInfo as $val) {
            $categoryInfos[$val['feed_id']][] = $val;
        }
        // 获取分享信息
        $feedInfo = model('Feed')->getFeeds($feedIds);
        $feedInfos = array();
        foreach ($feedInfo as $val) {
            $feedInfos[$val['feed_id']] = $val;
        }
        // 组装信息
        foreach ($data['data'] as &$value) {
            $value['uid'] = $feedInfos[$value['feed_id']]['user_info']['uid'];
            $value['uname'] = $feedInfos[$value['feed_id']]['user_info']['uname'];
            $value['content'] = $feedInfos[$value['feed_id']]['body'];
            $value['categoryInfo'] = $categoryInfos[$value['feed_id']];
            $value['app_row_id'] = $feedInfos[$value['feed_id']]['app_row_id'];
            $value['app_row_table'] = $feedInfos[$value['feed_id']]['app_row_table'];
            $value['app'] = $feedInfos[$value['feed_id']]['app'];
            $value['type'] = $feedInfos[$value['feed_id']]['type'];
            $value['is_repost'] = $feedInfos[$value['feed_id']]['is_repost'];
            $value['digg_count'] = $feedInfos[$value['feed_id']]['digg_count'];
        }

        return $data;
    }

    /**
     * 删除指定资源信息.
     *
     * @param array $rowId 资源ID数组
     *
     * @return bool 是否删除成功
     */
    public function cancelRecommended($rowId)
    {
        $map['feed_id'] = array('IN', $rowId);
        $result = $this->where($map)->delete();

        return (bool) $result;
    }

    /**
     * 审核资源操作.
     *
     * @return array $rowId 资源ID数组
     * @return bool  是否审核成功
     */
    public function auditChannelList($rowId)
    {
        $map['feed_id'] = array('IN', $rowId);
        $save['status'] = 1;
        $result = $this->where($map)->save($save);

        return (bool) $result;
    }

    /**
     * 获取指定分类的记录数目.
     *
     * @param int $cid 频道分类ID
     *
     * @return int 指定分类的记录数目
     */
    public function getChannelCount($cid)
    {
        !empty($cid) && $map['channel_category_id'] = $cid;
        $map['status'] = 1;
        $count = $this->where($map)->count();

        return $count;
    }

    /**
     * 获取指定频道分类下的列表数据.
     *
     * @param int $cid 分类ID
     *
     * @return array 指定频道分类下的列表数据
     */
    public function getChannelFindPage($cid)
    {
        !empty($cid) && $map['channel_category_id'] = $cid;
        $map['status'] = 1;
        $data = $this->where($map)->field('feed_id')->order('feed_channel_link_id DESC')->findPage(40);

        return $data;
    }

    /**
     * 获取指定分类下，用户贡献最高的用户数组.
     *
     * @param int $cid   频道分类ID
     * @param int $limit 结果集数目，默认为10
     *
     * @return array 用户贡献最高数组
     */
    public function getTopList($cid, $limit = 10)
    {
        // 验证数据
        if (empty($cid)) {
            return array();
        }
        // 获取排行榜数据
        $map['channel_category_id'] = $cid;
        $map['status'] = 1;
        $map['uid'] = array('EXP', 'IS NOT NULL');
        $data = D('channel')->field('`uid`, COUNT(`uid`) AS `count`')->where($map)->group('uid')->order('`count` DESC')->limit($limit)->findAll();
        // 获取用户信息
        $userModel = model('User');
        foreach ($data as &$value) {
            // 已使用缓存，单个获取
            $userInfo = $userModel->getUserInfo($value['uid']);
            !empty($userInfo) && $value = array_merge($value, $userInfo);
        }

        return $data;
    }

    /**
     * 获取指定分享已经加�
     * �的频道分类.
     *
     * @param int $feedId 分享ID
     *
     * @return array 已加�
     * �频道的分类数组
     */
    public function getSelectedChannels($feedId)
    {
        $map['feed_id'] = $feedId;
        $data = $this->where($map)->getAsFieldArray('channel_category_id');

        return $data;
    }

    /**
     * 添加频道与分享的�
     * �联信息.
     *
     * @param int   $sourceId   分享ID
     * @param array $channelIds 频道分类ID数组
     * @param bool  $isAdmin    是否需要审核，默认为true
     *
     * @return bool 是否添加成功
     */
    public function setChannel($feedId, $channelIds, $isAdmin = true)
    {
        // 格式化数据
        !is_array($channelIds) && $channelIds = explode(',', $channelIds);
        // 检验数据
        if (empty($feedId)) {
            return false;
        }
        // 删除分享的全部关联
        $map['feed_id'] = $feedId;
        $res = $this->where($map)->delete();
        // 删除成功
        if ($res !== false) {
            $data['feed_id'] = $feedId;
            // 获取图片的高度与宽度
            $feedInfo = model('Feed')->get($feedId);
            if ($feedInfo['type'] == 'postimage') {
                $feedData = unserialize($feedInfo['feed_data']);
                $imageAttachId = is_array($feedData['attach_id']) ? $feedData['attach_id'][0] : $feedData['attach_id'];
                $attach = model('Attach')->getAttachById($imageAttachId);
                $imageInfo = getImageInfo($attach['save_path'].$attach['save_name']);
                if ($imageInfo !== false) {
                    $data['width'] = ceil($imageInfo[0]);
                    $data['height'] = ceil($imageInfo[1]);
                }
            } elseif ($feedInfo['type'] == 'postvideo') {
                $feedData = unserialize($feedInfo['feed_data']);
                $imageInfo = getimagesize($feedData['flashimg']);
                if ($imageInfo !== false) {
                    $data['width'] = $imageInfo[0];
                    $data['height'] = $imageInfo[1];
                }
            }
            // 用户UID
            $data['uid'] = $feedInfo['uid'];
            // 获取后台配置数据
            $channelConf = model('Xdata')->get('channel_Admin:index');
            $isAudit = ($channelConf['is_audit'] == 1) ? false : true;
            foreach ($channelIds as $channelId) {
                $data['channel_category_id'] = $channelId;
                if ($isAdmin) {
                    $data['status'] = 1;
                } else {
                    if ($isAudit) {
                        $data['status'] = 0;
                    } else {
                        $data['status'] = 1;
                    }
                }
                $this->add($data);
            }

            return true;
        }

        return false;
    }

    /**
     * 获取指定频道分类下的相�
     * �数据 - 分页数据.
     *
     * @param int $cid 频道分类ID
     *
     * @return array 指定频道分类下的相�
     * �数据
     */
    public function getDataWithCid($cid, $loadId, $limit, $order, $loadCount)
    {
        $map['status'] = $countmap['status'] = 1;
        !empty($cid) && $map['channel_category_id'] = $countmap['channel_category_id'] = $cid;
        if ($order == 0) {
            //获取分页
            $result = $this->where($countmap)->order('feed_channel_link_id DESC')->findPage($limit * 4);
            if ($_REQUEST['newload']) {
                $loadId = $result['data'][0]['feed_channel_link_id'] - 1;
            }
            !empty($loadId) && $map['feed_channel_link_id'] = array('LT', $loadId);
            //获取数据
            $data = $this->where($map)->order('feed_channel_link_id DESC')->limit($limit)->findAll();
            // 设置指定的宽高
        } else {
            $result = $this->field('ts_channel.*,ts_feed.comment_count')->join('ts_feed on ts_channel.feed_id = ts_feed.feed_id')->where($countmap)->order('comment_count DESC,feed_channel_link_id DESC')->findPage($limit * 4);
            //获取数据
            $limit = ($loadCount - 1) * $limit + ((intval($_REQUEST['p']) - 1) * $limit * 4).','.$limit;
            $data = $this->field('ts_channel.*,ts_feed.comment_count')->join('ts_feed on ts_channel.feed_id = ts_feed.feed_id')->where($map)->order('comment_count DESC,feed_channel_link_id DESC')->limit($limit)->findAll();
        }

        $data = $this->_formatImageSize($data);
        $result['data'] = $data;

        return $result;
    }

    /**
     * 格式化图片的大小，使瀑布流图片显示正常.
     *
     * @param array $data  频道数据数组，�
     * 含宽高数据
     * @param int $width 格式化后的宽度，默认300px
     *
     * @return array 格式化宽高后的数据
     */
    private function _formatImageSize($data, $width = 236)
    {
        if (empty($data)) {
            return array();
        }
        foreach ($data as &$value) {
            $value['height'] = ceil($width * $value['height'] / $value['width']);
            $value['width'] = $width;
        }

        return $data;
    }

    /**
     * 删除分享与频道的�
     * �联.
     *
     * @param int $feedId 分享ID
     *
     * @return bool 是否删除成功
     */
    public function deleteChannelLink($feedId)
    {
        // 判断参数
        if (empty($feedId)) {
            return false;
        }
        // 删除数据
        $map['feed_id'] = intval($feedId);
        $result = $this->where($map)->delete();

        return (bool) $result;
    }

    /**
     * 获取指定用户所绑定频道分类的数组.
     *
     * @param int $uid 用户ID
     *
     * @return array 指定用户所绑定频道分类的数组
     */
    public function getCategoryByUserBind($uid)
    {
        $extraHash = D('channel_category')->where('ext IS NOT NULL')->getHashList('channel_category_id', 'ext');
        $data = array();
        foreach ($extraHash as $key => $val) {
            $extra = unserialize($val);
            if (!empty($extra['user_bind'])) {
                in_array($uid, explode(',', $extra['user_bind'])) && $data[] = $key;
            }
        }

        return $data;
    }

    /**
     * 获取指定话题所绑定频道分类的数组.
     *
     * @param array $topics 话题名称数组
     *
     * @return array 指定话题所绑定频道分类的数组
     */
    public function getCategoryByTopicBind($topics)
    {
        $extraHash = D('channel_category')->where('ext IS NOT NULL')->getHashList('channel_category_id', 'ext');
        $data = array();
        foreach ($extraHash as $key => $val) {
            $extra = unserialize($val);
            if (!empty($extra['topic_bind'])) {
                foreach ($topics as $value) {
                    in_array($value, explode(',', $extra['topic_bind'])) && $data[] = $key;
                }
            }
        }
        $data = array_unique($data);

        return $data;
    }

    /**
     * 删除分类�
     * �联信息.
     *
     * @param int $cid 分类ID
     *
     * @return bool 是否删除成功
     */
    public function deleteAssociatedData($cid)
    {
        if (empty($cid)) {
            return false;
        }
        // 删除频道分类下的数据
        $map['channel_category_id'] = $cid;
        $this->where($map)->delete();
        // 删除频道关注下的数据
        D('ChannelFollow', 'channel')->where($map)->delete();

        return true;
    }
}
