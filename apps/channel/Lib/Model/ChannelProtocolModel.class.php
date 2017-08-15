<?php
/**
 * ChannelProtocolModel
 * 提供给TS核心调用的协议类.
 */
class ChannelProtocolModel extends Model
{
    // 假删除用户数据
    public function deleteUserAppData($uidArr)
    {
    }

    // 恢复假删除的用户数据
    public function rebackUserAppData($uidArr)
    {
    }

    // 彻底删除用户数据
    public function trueDeleteUserAppData($uidArr)
    {
        if (empty($uidArr)) {
            return false;
        }

        $map['uid'] = array(
                'in',
                $uidArr,
        );

        M('channel')->where($map)->delete();
        M('channel_follow')->where($map)->delete();
    }

    /**
     * 在个人空间里查看该应用的�
     * 容列表.
     *
     * @param int $uid 用户UID
     *
     * @return array 个人空间数据列表
     */
    public function profileContent($uid)
    {
        $map['uid'] = $uid;
        //$list = D('Channel', 'channel')->getChannelList($map);
        $list = M('channel')->where($map)->field('feed_id')->findPage(20);
        $feed_id = getSubByKey($list['data'], 'feed_id');
        $list['data'] = model('Feed')->getFeeds($feed_id);
        $list['titleshort'] = 200;
        $list['suffix'] = '......';
        $tpl = APPS_PATH.'/channel/Tpl/default/Index/profileContent.html';

        return fetch($tpl, $list);
    }
}
