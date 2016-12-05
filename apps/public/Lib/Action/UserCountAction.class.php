<?php
/**
 * UserCountAction 用户统计模块.
 *
 * @version TS3.0
 */
class UserCountAction extends Action
{
    /**
     * 用户的通知统计数目.
     *
     * @return mix 通知统计状态和数目
     */
    public function getUnreadCount()
    {
        $count = model('UserCount')->getUnreadCount($this->mid);
        $data['status'] = 1;
        $data['data'] = $count;
        echo json_encode($data);
    }
}
