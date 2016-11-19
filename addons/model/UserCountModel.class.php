<?php
/**
 * 用户统计模型 - 数据对象模型
 * @author 小川
 * @version TS3.0
 */
class UserCountModel extends Model
{
    /**
     * 获取指定用户的通知统计数目
     * @param  int   $uid 用户UID
     * @return array 指定用户的通知统计数目
     */
    public function getUnreadCount($uid)
    {
        $msg_model = model('Message');
        $data_model = model('UserData');

        $user_data = $data_model->setUid($uid)->getUserData();
        // 未读通知数目
        $return['unread_notify'] = model('Notify')->getUnreadCount($uid);
        // 未读点赞
        $return['unread_digg'] = intval($user_data['unread_digg']);
        // 未读微吧点赞
        $return['unread_digg_weibapost'] = intval($user_data['unread_digg_weibapost']);
        $return['unread_digg_weibareply'] = intval($user_data['unread_digg_weibareply']);
        // 点赞总数
        $return['unread_digg_total'] = $return['unread_digg'] + $return['unread_digg_weibapost'] + $return['unread_digg_weibareply'];
        // 未读@Me数目
        $return['unread_atme'] = intval($user_data['unread_atme']);
        // 未读评论数目
        $return['unread_comment'] = intval($user_data['unread_comment']);
        // 未读短信息数目
        $return['unread_message'] = (int) $msg_model->getUnreadMessageCount($uid, array(MessageModel::ONE_ON_ONE_CHAT, MessageModel::MULTIPLAYER_CHAT));
        // 新的关注数目
        $return['new_folower_count'] = intval($user_data['new_folower_count']);
        $group = model('App')->getAppByName('group');
        if ($group['status']) {
            $groupatme = D('GroupUserCount', 'group')->where('uid='.$uid)->findAll();
            $gatme = 0;
            $gcomment = 0;
            foreach ($groupatme as $v) {
                $gatme += intval($v['atme']);
                $gcomment += intval($v['comment']);
            }
            $return['unread_group_atme'] = $gatme;
            $return['unread_group_comment'] = $gcomment;
        }
        // 合计的未读数目
        $return['unread_total'] = array_sum($return) - $return['unread_digg_total'];

        return $return;
    }

    /**
     * 更新指定用户的通知统计数目
     * @param int    $uid  用户UID
     * @param string $key  统计数目的Key值
     * @param int    $rate 数目变动的值
     */
    public function updateUserCount($uid, $key, $rate)
    {
        $data_model = model('UserData');
        $data_model->setUid($uid)->updateKey($key, $rate);
    }

    /**
     * 重置指定用户的通知统计数目
     * @param int    $uid   用户UID
     * @param string $key   统计数目的Key值
     * @param int    $value 统计数目变化的值，默认为0
     */
    public function resetUserCount($uid, $key, $value = 0)
    {
        $data_model = model('UserData');
        $data_model->setKeyValue($uid, $key, $value);
    }
}
