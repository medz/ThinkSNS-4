<?php
/**
 * 频道前台管理控制器.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class ManageAction extends Action
{
    /**
     * 频道管理弹窗.
     */
    public function getAdminBox()
    {
        // 获取分享ID
        $data['feedId'] = intval($_REQUEST['feed_id']);
        // 频道分类ID
        $data['channelId'] = empty($_REQUEST['channel_id']) ? 0 : intval($_REQUEST['channel_id']);
        // 获取全部频道列表
        $data['categoryList'] = model('CategoryTree')->setTable('channel_category')->getCategoryList();
        // 获取该分享已经选中的频道
        $data['selectedChannels'] = D('Channel', 'channel')->getSelectedChannels($data['feedId']);
        // 是否有动态效果
        $data['clear'] = empty($_REQUEST['clear']) ? 0 : intval($_REQUEST['clear']);

        $this->assign($data);
        $this->display('manageBox');
    }

    /**
     * 添加分享进入频道.
     *
     * @return json 操作后的相关信息数据
     */
    public function doAddChannel()
    {
        // 分享ID
        $feedId = intval($_POST['feedId']);
        // 判断资源是否删除
        $fmap['feed_id'] = $feedId;
        $fmap['is_del'] = 0;
        $isExist = model('Feed')->where($fmap)->count();
        if ($isExist == 0) {
            $return['status'] = 0;
            $return['info'] = '内容已被删除，推荐失败';
            exit(json_encode($return));
        }
        // 频道ID数组
        $channelIds = t($_POST['data']);
        $channelIds = format_array_intval($channelIds);
        if (empty($feedId)) {
            $res['status'] = 0;
            $res['info'] = '推荐失败';
            exit(json_encode($res));
        }
        // 添加分享进入频道
        $result = D('Channel', 'channel')->setChannel($feedId, $channelIds);
        if ($result) {
            if (!empty($channelIds)) {
                $config['feed_content'] = getShort(D('feed_data')->where('feed_id='.$feedId)->getField('feed_content'), 10);
                $map['channel_category_id'] = array('in', $channelIds);
                $config['channel_name'] = implode(',', getSubByKey(D('channel_category')->where($map)->field('title')->findAll(), 'title'));
                $uid = D('feed')->where('feed_id='.$feedId)->getField('uid');
                $config['feed_url'] = '<a target="_blank" href="'.U('channel/Index/index', array('cid' => $channelIds[0])).'">点此查看</a>';
                model('Notify')->sendNotify($uid, 'channel_add_feed', $config);
                //添加积分
                model('Credit')->setUserCredit($this->mid, 'recommend_to_channel');
            }
            if (empty($channelIds)) {
                //添加积分
                model('Credit')->setUserCredit($this->mid, 'unrecommend_to_channel');
            }
            $res['status'] = 1;
            $res['info'] = '推荐成功';
        } else {
            $res['status'] = 0;
            $res['info'] = '推荐失败';
        }
        exit(json_encode($res));
    }
}
