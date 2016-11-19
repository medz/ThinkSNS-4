<?php

include_once SITE_PATH.'/apps/event/Lib/Model/BaseModel.class.php';
/**
 * EventModel
 * 活动主数据库模型
 * @uses BaseModel
 * @package
 * @version $id$
 * @copyright 2009-2011 SamPeng
 * @author SamPeng <sampeng87@gmail.com>
 * @license PHP Version 5.2 {@link www.sampeng.cn}
 */
class EventModel extends BaseModel
{
    public $mid;
    public function getConfig($key = null)
    {
        $config = model('Xdata')->lget('event');
        $config['limitpage'] || $config['limitpage'] = 10;
        $config['canCreate'] === 0 || $config['canCreat'] = 1;
        ($config['credit'] > 0 || '0' === $config['credit']) || $config['credit'] = 100;
        $config['credit_type'] || $config['credit_type'] = 'experience';
        ($config['limittime'] || $config['limittime'] === '0') || $config['limittime'] = 10; //换算为秒

        if ($key) {
            return $config[$key];
        } else {
            return $config;
        }
    }
    public function getEventList($map = '', $order = 'id DESC', $mid)
    {
        $this->mid = $mid;
        $result = $this->where($map)->order($order)->findPage($this->getConfig('limitpage'));
        //追加必须的信息
        if (!empty($result['data'])) {
            $user = self::factoryModel('user');
               //$friendsId = $this->api->friend_get();
               $map = array();
            $map['action'] = 'joinIn';
            $map['status'] = 1;
            //$map['uid']       = $friendsId?array( 'in',$friendsId):NULL;
            foreach ($result['data'] as &$value) {
                $value = $this->appendContent($value);
                //追加参与者
                $map['eventId'] = $value['id'];
                $value['users'] = $user->where($map)->findAll();
                $value['cover'] = getCover($value['coverId']);
                //计算待审核人数
                if ($value['opts']['allow'] && $this->mid == $value['uid']) {
                    $value['verifyCount'] = $user->where("status = 0 AND action='joinIn' AND eventId =".$value['id'])->count();
                }
            }
        }

        return $result;
    }
    /**
     * appendContent
     * 追加和反解析数据
     * @param mixed $data
     */
    public function appendContent($data)
    {
        $opts = self::factoryModel('opts');
        $type = self::factoryModel('type');
        $data['type'] = $type->getTypeName($data['type']);

        //反解析时间
        $data['time'] = date('Y-m-d H:i:s', $data['sTime']).' 至 '.date('Y-m-d H:i:s', $data['eTime']);
        $data['dl'] = date('Y-m-d H:i:s', $data['deadline']);

        //追加选项内容
        $opts_list = $opts->getOpts($data['optsId']);
        //追加城市和其它选项
        $data['city'] = $opts_list['province'].' '.$opts_list['city'].' '.$opts_list['area'];
        $data['opts'] = unserialize($opts_list['opts']);
        $data['cost'] = $opts_list['cost'];
        $data['costExplain'] = $opts_list['costExplain'];
        $data['isHot'] = $opts_list['isHot'];

        //追加权限
        $data += $this->checkMember($data['uid'], $data['opts'], $this->mid);

        //追加是否已参加和是否已关注的判定
        $userDao = self::factoryModel('user');
        if ($result = $userDao->hasUser($this->mid, $data['id'], 'joinIn')) {
            $data['canJoin'] = false;
            $data['canAtt'] = false;
            $data['hasMember'] = $result['status'];

            return $data;
        } elseif ($userDao->hasUser($this->mid, $data['id'], 'attention')) {
            $data['canAtt'] = false;
        }

        return $data;
    }
    /**
     * checkRoll
     * 检查权限
     * @param mixed $uid
     */
    public function checkMember($eventAdmin, $opts, $mid)
    {
        $result = array(
                        'admin' => false,
                        'follow' => true,
                        'canJoin' => true,
                        'canAtt' => true,
                        'hasMember' => false,
                        );
        if ($mid == $eventAdmin) {
            $result['admin'] = true;
            $result['follow'] = false;
            $result['canJoin'] = false;
            $result['canAtt'] = false;

            return $result;
        }

        //如果是好友可以参加
        if (1 == $opts['friend']) {
            if ('unfollow' == getFollowState($eventAdmin, $mid)) {
                $result['canJoin'] = false;
                $result['follow'] = false;
            }
        }

        return $result;
    }

    /**
     * doAddEvent
     * 添加活动
     * @param mixed $map
     * @param mixed $feed
     */
    public function doAddEvent($eventMap, $optsMap, $cover)
    {
        $eventMap['cTime'] = isset($eventMap['cTime']) ? $eventMap['cTime'] : time();
        $eventMap['coverId'] = $cover['status'] ? $cover['info'][0]['attach_id'] : 0;
        $eventMap['limitCount'] = 0 == intval($eventMap['limitCount']) ? 999999999 : $eventMap['limitCount'];
        $has_friend = $optsMap['opts']['friend'];
        $optsMap['opts'] = serialize($optsMap['opts']);

        //false
        $optsDao = self::factoryModel('opts');
        if ($eventMap['optsId'] = $optsDao->add($optsMap)) {
            $addId = $this->add($eventMap);
        } else {
            return false;
        }

        //添加参与动作
        $user = self::factoryModel('user');
        $map['uid'] = $eventMap['uid'];
        $map['eventId'] = $addId;
        $map['contact'] = $eventMap['contact'];
        $map['action'] = 'admin';
        $map['cTime'] = time();
        $user->add($map);

        //如果是只有我关注的人可参与，给所有我关注的人发送通知
        // if( 1 == $has_friend  ){
        // 	//我关注的人的ID
        // 	$fids = M('user_follow')->field('fid')->where("uid={$eventMap['uid']}")->findAll();
        // 	foreach($fids as $k=>&$v){
        // 		$fids[$k] = $v['fid'];
        // 	}

  //           $data['url']     =  U('//eventDetail',array('id'=>$addId,'uid'=>$eventMap['uid']));
  //           $data['title']   = "<a href=\"{$data['url']}\" target=\"_blank\">{$eventMap['title']}</a>";
  //           $data['content'] = t(getBlogShort($eventMap['explain'],40));
  //           X('Notify')->send($fids,'event_add',$data, $eventMap['uid']);
        // }

        //发布到微薄
        $_SESSION['new_event'] = 1;

        return $addId;
    }

    /**
     * getEventContent
     * 获得活动具体类容页
     * @param mixed $eventId
     * @param mixed $uid
     * @param mixed $mid
     */
    public function getEventContent($eventId, $uid)
    {
        //分别获得用户和照片数据库模型
        $user = self::factoryModel('user');
        //$photo = self::factoryModel( 'photo' );
        $map['id'] = $eventId;

        $result = $this->where($map)->find();

        //检查是否正确的管理员id
        if ($uid != $result['uid']) {
            return false;
        }

        //追加相册图片
        //$result['photolist'] = $photo->getPhotos( $eventId,10 );
        //追加参与者和关注者
        $join = $att = array();
        $att['action'] = 'attention';
        $att['eventId'] = $result['id'];
        $att['status'] = 1;
        $join = $att;
        $join['action'] = 'joinIn';

        $result['attention'] = $user->getUserList($att, 4);

        $result['member'] = $user->getUserList($join, 16);

        $result['lc'] = 5000000 < $result['limitCount'] ? '无限制' : $result['limitCount'];
        $result['cover'] = getCover($result['coverId'], 200, 200);
        $result = $this->appendContent($result);

        return $result;
    }

    public function doEditEvent($eventMap, $optsMap, $cover, $id)
    {
        $eventMap['cTime'] = isset($eventMap['cTime']) ? $eventMap['cTime'] : time();
        $eventMap['coverId'] = $cover['info'][0]['attach_id'] > 0 ? $cover['info'][0]['attach_id'] : 0;
        $eventMap['limitCount'] = 0 == intval($eventMap['limitCount']) ? 999999999 : $eventMap['limitCount'];

        $has_friend = $optsMap['opts']['friend'];
        $optsMap['opts'] = serialize($optsMap['opts']);

        //false
        $optsDao = self::factoryModel('opts');
        if (false !== $optsDao->where('id='.$id['optsId'])->save($optsMap)) {
            $addId = $this->where('id ='.$id['id'])->save($eventMap);
        } else {
            return false;
        }

        return $addId;
    }

    /**
     * factoryModel
     * 工厂方法
     * @param mixed $name
     * @static
     */
    public static function factoryModel($name)
    {
        return D('Event'.ucfirst($name), 'event');
    }

    /**
     * doAddUser
     * 添加用户行为
     * @param mixed $data
     * @param mixed $allow
     */
    public function doAddUser($data, $allow)
    {
        $userDao = self::factoryModel('user');
        $optsDao = self::factoryModel('opts');

        //检查这个id是否存在
        if (false == $event = $this->where('id ='.$data['id'])->find()) {
            return -1;
        }

        if ($data['action'] === 'joinIn') {
            //自动获取已填写的联系方式
            $contact_fields = M('user_set')->where('module=\'contact\'')->findAll();
            $contact_info = M('user_profile')->getField('data', "uid={$data['uid']} AND module='contact'");
            $contact_info = unserialize($contact_info);

            $need_fields = array('手机', 'QQ', 'MSN');
            foreach ($contact_fields as $field) {
                if (in_array($field['fieldname'], $need_fields) && !empty($contact_info[$field['fieldkey']])) {
                    $contacts .= $field['fieldname'].':'.$contact_info[$field['fieldkey']].' ';
                }
            }
        }

        //检查仅好友参与选项
        $opts = $optsDao->where('id='.$event['optsId'])->find();
        $opt = unserialize($opts['opts']);
        $role = $this->checkMember($event['uid'], $opt, $data['uid']);

        //检查是否已经加入或者关注
        if ($userDao->hasUser($data['uid'], $data['id'], $data['action'])) {
            return -2;
        }
        //关注的话，再检查是否已经加入
        if ($data['action'] == 'attention' && $userDao->hasUser($data['uid'], $data['id'], 'joinIn')) {
            return -2;
        }

        $map = $data;
        $map['eventId'] = $data['id'];
        unset($map['id']);
        $map['cTime'] = time();
        $map['contact'] = $contacts;
        switch ($data['action']) {
            case 'attention':
                if (false === $opts['canAtt']) {
                    return -2;
                }
                if ($userDao->add($map)) {
                    $this->setInc('attentionCount', 'id='.$map['eventId']);

                    return 1;
                } else {
                    return 0;
                }
                break;
            case 'joinIn':
                if (false === $role['canJoin']) {
                    return -2;
                }
                if (!$role['follow']) {
                    return 0;
                }
                $map['status'] = $opt['allow'] ? 0 : 1;

                //如果有关注的情况，直接更新为参与
                $temp_map['uid'] = $map['uid'];
                $temp_map['action'] = 'attention';
                $temp_map['eventId'] = $map['eventId'];
                if ($id = $userDao->where($temp_map)->getField('id')) {
                    if ($res = $userDao->where("id={$id}")->save($map)) {
                        $this->setDec('attentionCount', 'id='.$map['eventId']);
                    }
                } else {
                    $res = $userDao->add($map);
                }
                if ($res) {
                    if ($map['status']) {
                        $this->setInc('joinCount', 'id='.$map['eventId']);
                        $this->setDec('limitCount', 'id='.$map['eventId']);
                        model('Credit')->setUserCredit($map['uid'], 'join_event');
                    }

                    return 1;
                } else {
                    return 0;
                }
                break;
            default:
                return -3;
        }
    }

    /**
     * doArgeeUser
     * 同意申请
     * @param mixed $data
     */
    public function doArgeeUser($data)
    {
        $userDao = self::factoryModel('user');
        if ($userDao->where('id='.$data['id'])->setField('status', 1)) {
            $this->setInc('joinCount', 'id='.$data['eventId']);
            $this->setDec('limitCount', 'id='.$data['eventId']);
            model('Credit')->setUserCredit($data['uid'], 'join_event');
            //如果有参与的情况。删除参与的数据集
            $data['action'] = 'attention';
            if ($id = $userDao->where($data)->getField('id')) {
                $userDao->delete($id);
                $this->setDec('attentionCount', 'id='.$data['eventId']);
            }

            return 1;
        }

        return 0;
    }

    /**
     * doDelUser
     * 取消关注或参加
     * @param mixed $data
     */
    public function doDelUser($data)
    {
        $userDao = self::factoryModel('user');
        //检查这个id是否存在
        if (false == $event = $this->where('id ='.$data['id'])->find()) {
            return -1;
        }
        //检查是否存在。如果存在，删除这条记录
        $map['uid'] = $data['uid'];
        $map['eventId'] = $data['id'];
        $map['action'] = $data['action'];
        //检测是否存在这个用户
        if ($event_user = $userDao->hasUser($data['uid'], $data['id'], $data['action'])) {
            //删除用户操作记录
            if ($userDao->where($map)->delete()) {
                //记录数相应减1
                $deleteMap['id'] = $map['eventId'];
                switch ($map['action']) {
                    case 'attention':
                        $delete = 'attentionCount';
                        $this->setDec($delete, $deleteMap);
                        break;
                    case 'joinIn':
                        if ($event_user['status']) {
                            $delete = 'joinCount';
                            $this->setInc('limitCount', $deleteMap);
                            $this->setDec($delete, $deleteMap);
                            model('Credit')->setUserCredit($data['uid'], 'cancel_join_event');
                        }
                        break;
                }

                return 1;
            }
        } else {
            return -2;
        }
    }

    public function getMember($map, $uid)
    {
        $user = self::factoryModel('user') ;
        $result = $user->getUserList($map, 20, true);
        $data = $result['data'];
        //修正成员状态
        foreach ($data as $key => $value) {
            if ($value['uid'] == $uid) {
                $result['data'][$key]['role'] = '发起者';
            } else {
                if ('joinIn' == $value['action']) {
                    $result['data'][$key]['role'] = '成员';
                }
                if ('attention' == $value['action']) {
                    $result['data'][$key]['role'] = '关注中';
                }
                if ('joinIn' == $value['action'] && 0 == $value['status']) {
                    $result['data'][$key]['role'] = '待审核';
                }
            }
        }

        return $result;
    }

    public function doEditData($time, $id)
    {
        //检查安全性，防止非管理员访问
        $uid = $this->where('id='.$id)->getField('uid');
        if ($uid != $this->mid) {
            return -1;
        }

        if ($this->where('id='.$id)->setField('deadline', $time)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * getList
     * 供后台管理获取列表的方法
     * @param mixed $order
     * @param mixed $limit
     */
    public function getList($map, $order, $limit)
    {
        $result = $this->where($map)->order($order)->findPage($limit);
        //将属性追加
        foreach ($result['data'] as &$value) {
            $value = $this->appendContent($value);
        }

        return $result;
    }

    /**
     * doDeleteEvent
     * 删除活动
     * @param mixed $eventId
     */
    public function doDeleteEvent($eventId)
    {
        //TODO 检查是否是管理员

        if (empty($eventId)) {
            return false;
        }
        //取出选项ID
        $optsIds = $this->field('uid,optsId')->where($eventId)->findAll();
        $uIds = array();
        foreach ($optsIds as &$v) {
            //积分
            model('Credit')->setUserCredit($v['uid'], 'delete_event');
            $v = $v['optsId'];
        }
        $opts_map['id'] = array('in', $optsIds);

        //删除活动
        if ($this->where($eventId)->delete()) {
            //删除选项
            self::factoryModel('opts')->where($opts_map)->delete();
            //删除成员
            $user_map['eventId'] = $eventId['id'];
            self::factoryModel('user')->where($user_map)->delete();

            return true;
        }

        return false;
    }
    /**
     * getConfig
     * 获取配置
     * @param mixed $index
     */
    /*public function getConfig( $index ){
        $config = $this->config->$index;
        return $config;
    }*/

    /**
     * doIsHot
     * 设置推荐
     * @param mixed $map
     * @param mixed $act
     */
    public function doIsHot($map, $act)
    {
        if (empty($map)) {
            throw new ThinkException('不允许空条件操作数据库');
        }
        $optsIds = $this->where($map)->getField('optsId');
        $map_opts['id'] = array('in', $optsIds);

        switch ($act) {
            case 'recommend':   //推荐
                $data['isHot'] = 1;
                $data['rTime'] = time();
                $result = self::factoryModel('opts')->where($map_opts)->save($data);
            break;
            case 'cancel':   //取消推荐
                $data['isHot'] = 0;
                $data['rTime'] = 0;
                $result = self::factoryModel('opts')->where($map_opts)->save($data);
            break;
        }

        return $result;
    }

    /**
     * getHotList
     * 推荐列表
     * @param mixed $map
     * @param mixed $act
     */
    public function getHotList()
    {
        $opts_ids = self::factoryModel('opts')->field('id')->where('isHot=1')->limit(5)->findAll();
        foreach ($opts_ids as &$v) {
            $v = $v['id'];
        }
        $event_map['optsId'] = array('in', $opts_ids);
        $event_ids = $this->where($event_map)->findAll();
        $typeDao = self::factoryModel('type');
        foreach ($event_ids as &$v) {
            $v['type'] = $typeDao->getTypeName($v['type']);
            $v['address'] = getShort($v['address'], 6);
            $v['coverId'] = getCover($v['coverId'], 100, 100);
        }

        return $event_ids;
    }

    /**
     * hasMember
     * 判断是否是有这个成员
     * @param mixed $uid
     */
    public function hasMember($uid, $eventId)
    {
        $user = self::factoryModel('user');
        if ($result = $user->where('uid='.$uid.' AND eventId='.$eventId)->field('action,status')->find()) {
            return $result;
        } else {
            return false;
        }
    }
}
