<?php

/**
 *
 * @author jason
 *
 */
class NotifytionApi extends Api
{
    /**
     * 获取当前用户消息提醒类型和总数
     */
    public function get_notify_by_count()
    {
        $data = model('UserCount')->getUnreadCount($this->mid);
        $return = array();
        $return[] = array('type' => 'notify', 'name' => '系统通知', 'icon' => 'NotifyIcon', 'count' => intval($data['unread_notify']), 'data' => '');
        $return[] = array('type' => 'atme', 'name' => '@我的', 'icon' => 'AtMeIcon', 'count' => intval($data['unread_atme']), 'data' => '');
        $return[] = array('type' => 'comment', 'name' => '评论', 'icon' => 'CommentIcon', 'count' => intval($data['unread_comment']), 'data' => '');
        $return[] = array('type' => 'unread_digg', 'name' => '赞', 'icon' => 'DiggIcon', 'count' => intval($data['unread_digg']), 'data' => '');
        $return[] = array('type' => 'new_folower', 'name' => '粉丝', 'icon' => 'New_folower', 'count' => intval($data['new_folower_count']), 'data' => '');
        $return[] = array('type' => 'unread_message', 'name' => '私信', 'icon' => 'MessageIcon', 'count' => intval($data['unread_message']), 'data' => '');

        //把私信拆解成每个对话的形式，可以做仿微信的列表功能
        // if(!empty($data['unread_message'])){
        // 	$lastMessage = model('Message')->getMessageListByUidForAPIUnread($this->mid,array(1,2),$this->since_id,$this->max_id);
        // 	foreach($lastMessage as $k=>$v){
        // 		unset($v['to_user_info']);
        // 		$return[] = array('type'=>'message','name'=>$v['last_message']['user_info']['uname'],'icon'=>'MessageIcon','count'=>$v['new'],'data'=>$v);
        // 	}
        // }

        return $return;
    }

    /**
     * 设置消息为已读
     *
     */
    public function set_notify_read()
    {
        if ($this->data['type'] == 'atme') {
            model('UserCount')->resetUserCount($this->mid, 'unread_atme', 0);

            return 1;
        } elseif ($this->data['type'] == 'comment') {
            model('UserCount')->resetUserCount($this->mid, 'unread_comment', 0);

            return 1;
        } elseif ($this->data['type'] == 'new_follower') {
            $udata = model('UserData')->getUserData($this->mid);
            $udata['new_folower_count'] > 0 && model('UserData')->setKeyValue($this->mid, 'new_folower_count', 0);

            return 1;
        } else {
            return 0;
        }
    }

    /**
     *  设置某条对话为已读
     */
    public function set_message_read()
    {
        $listIds = array($this->data['list_id']);
        if (model('Message')->setMessageIsRead($listIds, $this->mid)) {
            return '1';
        } else {
            return '0';
        }
    }

    //获取提醒的消息总数
    public function get_message_count()
    {
        $data = model('UserCount')->getUnreadCount($this->mid);
        // $total = intval($data['unread_sysnotify'] + $data['unread_atme'] + $data['unread_comment'] + $data['unread_digg'] + $data['unread_message'] + $data['unread_badycare']);
        return intval($data['unread_message']);
    }

    //获取提醒的系统消息
    public function get_system_notify()
    {
        //根据新的消息机制调整
        $data = model('Notify')->getUnreadListForApi($this->mid, $this->since_id, $this->max_id, $this->count, $this->page);
        $return = array();
        foreach ($data['list'] as $v) {
            //格式化处理 给手机端用
            $d['list_id'] = $v['id'];     //消息ID
            $d['member_uid'] = $this->mid;
            $d['new'] = $v['is_read']; //是否已读 0未读，1已读
            $d['message_num'] = 1;
            $d['appname'] = $v['appname']; //来自哪个应用
            $d['ctime'] = $v['ctime'];  //发送时间
            $d['title'] = $v['body'];     //消息内容
            $fuser = model('User')->getUserInfo($v['fid']);
            $return[] = array('type' => 'notify', 'name' => '系统消息', 'icon' => 'NotifyIcon', 'count' => $data['count'], 'data' => $d, 'fid' => $v['fid'], 'fuser' => $fuser);
        }
        model('Notify')->setRead($this->mid); //设置已读
        return $return;
    }
}
