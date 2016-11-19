<?php
/**
 * 任务Dao类
 * @author Stream
 *
 */
class TaskModel extends Model
{
    protected $tableName = 'task';
    // 用于存储日常任务统计数据
    private $amountHash = array();

    public function getUserTask($type, $uid)
    {
        //用户任务执行情况   主要判断 ：是新手任务，进阶任务 ......
        $data = D('task_user')->where('task_type='.$type.' and uid='.$uid)->order('task_level desc')->find();
        //返回用户当前执行的任务名称及任务等级 和类型
        return $data;
    }
    public function getTaskList($tasktype, $uid, $show = '')
    {
        $tasklevel = 1;
        //判断主线任务执行阶段
        if ($tasktype == 2) {
            $task = $this->getUserTask($tasktype, $uid);
            $tasklevel = $task['task_level'] ? $task['task_level'] : 1;
        }
        //刷新执行任务状态
        $this->isComplete($tasktype, $uid, $tasklevel);
        //根据类型返回任务列表
        $list = D('task_user')->where('task_type='.$tasktype.' and task_level='.$tasklevel.' and uid='.$uid)->findAll();
// 		dump(D('task_user')->getLastSql());
        $map['id'] = array('in', getSubByKey($list, 'tid'));
        if (!empty($show)) {
            $map['show'] = array('like', "%$show%");
        }
// 		$map['is_del'] = 0;
        $steplist = $this->where($map)->field('id,task_name,step_name,step_desc,`condition`,action,reward,headface')->findAll();
// 		dump($steplist);
// 		dump($this->getLastSql());
        $steps = array();
        foreach ($steplist as $s) {
            if (! empty($s ['headface'])) {
                $attach = explode('|', $s ['headface']);
                $steps [$s ['id']] ['headface'] = getImageUrl($attach [1]);
            } else {
                $steps [$s ['id']] ['headface'] = '';
            }
            $steps[$s['id']]['step_name'] = $s['step_name'];
            $steps[$s['id']]['step_desc'] = $s['step_desc'];
            $steps[$s['id']]['action'] = $s['action'] ? U($s['action']) : '';
            $steps[$s['id']]['reward'] = json_decode($s['reward']);
            $steps[$s['id']]['conkey'] = array_shift(array_keys((array) json_decode($s['condition'])));
        }
        $iscomplete = 1;
        foreach ($list as $k => &$v) {
            // 增加 - 删除应用后，任务的显示
            if (is_null($steps[$v['tid']]['reward'])) {
                unset($list[$k]);
            }
            if (!$v['status'] || !$v['receive']) {
                $iscomplete = 0;
            }
            $v['headface'] = $steps[$v['tid']]['headface'];
            $v['step_name'] = $steps[$v['tid']]['step_name'];
            $v['step_desc'] = $steps[$v['tid']]['step_desc'];
            $v['action'] = $steps[$v['tid']]['action'];
            $v['reward'] = $steps[$v['tid']]['reward'];
            $v['progress_rate'] = $this->getAmountHash($steps[$v['tid']]['conkey']);
        }
        $redata['list'] = $list;
        $redata['task_name'] = $steplist[0]['task_name'];
        $redata['task_type'] = $tasktype;
        $redata['task_level'] = $tasklevel;
        $redata['iscomplete'] = $iscomplete;
        $redata['receive'] = false;
        //每日任务
        if ($iscomplete) {
            //是否领取奖励
            $rmap['task_type'] = $tasktype;
            $rmap['task_level'] = $tasklevel;
            $rmap['uid'] = $uid;
            if ($tasktype == 1) {
                $rmap['ctime'] = array('gt', strtotime(date('Ymd')));
            }
            $redata['receive'] = D('task_receive')->where($rmap)->limit(1)->count();
        }
// 		dump($redata);
        return $redata;
    }

    private function isComplete($tasktype, $uid, $tasklevel)
    {
        $map['task_type'] = $tasktype;
        $map['uid'] = $uid;
        $map['task_level'] = $tasklevel;
        //每日任务判断
        $list = true;
        if ($tasktype == 1) {
            $map['ctime'] = array('gt', strtotime(date('Ymd')));
        }
        //判断任务是否存在
        $list = D('task_user')->where($map)->find();
        if ($list) {
            //更新未完成的任务
            $map['status'] = 0;
            $nocomplete = D('task_user')->where($map)->findAll();

            $tids = getSubByKey($nocomplete, 'tid');
            $task_map['id'] = array('in', $tids);
            $tasks = $this->where($task_map)->findAll();
            $userdata = model('UserData')->getUserData($uid);
            foreach ($tasks as $t) {
                $condition = json_decode($t['condition']);
                $conkey = key($condition);
// 				$tasktype==2 && dump($conkey);
                //判断任务是否完成
                $res = $this->_executeTask($conkey, $condition->$conkey, $uid, $tasktype, $userdata);
                if ($res) {
                    //刷新用户任务执行状态
                    D('task_user')->setField('status', 1, 'tid='.$t['id'].' and uid='.$uid);
                }
            }
        } else {
            //每日任务数据初始化
            if ($tasktype == 1) {
                //删除历史
                $dmap['task_type'] = $tasktype;
                $dmap['uid'] = $uid;
                D('task_user')->where($dmap)->delete();
            }
            //初始化新的数据
            $tmap['task_type'] = $tasktype;
            $tmap['task_level'] = 1;
            if ($this->addTask($tmap, $uid)) {
                //重新判断任务进程
                $this->isComplete($tasktype, $uid, $tasklevel);
            }
        }
    }

    public function completeTask($tasktype, $tasklevel = 1, $uid)
    {
        $complete = D('task_user')->where('status=0 and task_type='.$tasktype.' and task_level='.$tasklevel.' and uid='.$uid)->find();
            //是否完成
            if (!$complete) {
                //是否重复领取
                $remap['uid'] = $uid;
                $remap['task_type'] = $tasktype;
                $remap['task_level'] = $tasklevel;
                if ($tasktype == 1) {
                    $remap['ctime'] = array('gt', strtotime(date('Ymd')));
                }
                $receive = D('task_receive')->where($remap)->find();
                if ($receive) {
                    return false;
                }
                //记录领奖记录
                $data['uid'] = $uid;
                $data['task_type'] = $tasktype;
                $data['task_level'] = $tasklevel;
                $data['ctime'] = $_SERVER['REQUEST_TIME'];
                $res = D('task_receive')->add($data);

                //初始化新的任务
                $map['task_level'] = $tasklevel + 1;
                $map['task_type'] = $tasktype;
                $this->addTask($map, $uid);

                return $res;
            }
    }
    public function addTask($map, $uid)
    {
        //查询新的任务进程
        $list = $this->where($map)->findAll();
        if (!$list) {
            return false;
        }
        foreach ($list as $v) {
            $udata['uid'] = $uid;
            $udata['tid'] = $v['id'];
            $udata['task_level'] = $v['task_level'];
            $udata['task_type'] = $v['task_type'];
            $udata['ctime'] = $_SERVER['REQUEST_TIME'];
            $udata['status'] = 0;
            $udata['desc'] = '';
            $udata['receview'] = 0;
            //加入任务表
            D('task_user')->add($udata);
        }

        return true;
    }

    public function _executeTask($excutetype, $num, $uid, $type, $userdata)
    {
        //每日任务判断
        if ($type == 1) {
            $starttime = strtotime(date('Ymd'));
            switch ($excutetype) {
                case 'weibopost':
                    $rescount = model('Feed')->where('uid='.$uid.' and is_repost=0 and publish_time>'.$starttime)->limit($num)->count();
                    break;
                case 'weiborepost':
                    $rescount = model('Feed')->where('uid='.$uid." and type='repost' and publish_time>".$starttime)->limit($num)->count();
                    break;
                case 'weibocomment':
                    $rescount = model('Comment')->where('uid='.$uid." and `table`='feed' and ctime>".$starttime)->limit($num)->count();
                    break;
                case 'weibodigg':
                    $rescount = model('FeedDigg')->where('uid='.$uid.' and cTime>'.$starttime)->limit($num)->count();
                    break;
                case 'checkin':
                    $rescount = D('check_info')->where('uid='.$uid.' and ctime>'.$starttime)->limit($num)->count();
                    break;
            }
        } else {
            switch ($excutetype) {
                    //原创分享
                case 'weibopost':
                    $rescount = model('Feed')->where('uid='.$uid.' and is_repost=0')->limit($num)->count();
                    break;
                    //转发分享
                case 'weiborepost':
                    $rescount = model('Feed')->where('uid='.$uid." and type='repost'")->limit($num)->count();
                    break;
                    //分享评论
                case 'weibocomment':
                    $rescount = model('Comment')->where('uid='.$uid." and `table`='feed'")->limit($num)->count();
                    break;
                    //上传头像
                case 'uploadface':
                    $res = model('Avatar')->hasAvatar();

                    return $res ? true : false;
                    break;
                    //粉丝数
                case 'following':
                    $rescount = $userdata['follower_count'];
                    break;
                    //签到
                case 'checkin':
                    $rescount = 0;
                    $checkinfo = D('check_info')->where('uid='.$uid)->order('ctime DESC')->find();
                    if ($checkinfo['ctime'] > (strtotime(date('Ymd')) - 86400)) {
                        $rescount = $checkinfo['con_num'];
                    }
                    break;
                    //用户信息
                case 'userinfo':
                    //个人兴趣
                    $tags = implode(',', model('Tag')->setAppName('public')->setAppTable('user')->getAppTags($uid));
                    $userinfo = model('User')->getUserInfo($uid);
                    if ($tags && $userinfo['intro']) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                    //关注感兴趣的人
                case 'followinterest':
                    $rescount = $userdata['following_count'];
                    break;
                    //发表分享通知好友
                case 'weibotofriend':
                    $fids = D('Feed')->where('uid='.$uid)->field('feed_id')->findAll();
                    $map['row_id'] = array('in', getSubByKey($fids, 'feed_id'));
                    $res = D('atme')->where($map)->find();

                    return $res ? true : false;
                    break;
                    //用户等级
                case 'userlevel':
                    $credit = model('Credit')->getUserCredit($uid);
                    $rescount = $credit['level']['level'];
                    break;
                    //分享被转发
                case 'weibotranspost':
                    $rescount = model('Feed')->where('uid='.$uid.' and repost_count>0')->limit($num)->count();
                    break;
                    //分享被评论
                case 'weiboreceivecomment':
                    $rescount = model('Feed')->where('uid='.$uid.' and comment_count>0')->limit($num)->count();
                    break;
                    //单条分享被转发
                case 'weiboonetranspost':
                    $res = model('Feed')->where('uid='.$uid.' and repost_count>'.$num)->find();

                    return $res ? true : false;
                    break;
                    //单条分享被评论
                case 'weiboonecomment':
                    $res = model('Feed')->where('uid='.$uid.' and comment_count>'.$num)->find();

                    return $res ? true : false;
                    break;
                    //转发指定分享
                case 'weiboappoint':
                    $map['uid'] = $uid;
                    $map['type'] = 'repost';
                    $map['app_row_id'] = $num;
                    $res = model('Feed')->where($map)->find();

                    return $res ? true : false;
                    break;
                    //关注微吧
                case 'weibafollow':
                    $res = D('weiba_follow')->where('follower_uid='.$uid)->find();

                    return $res ? true : false;
                    break;
                    //微吧发布帖子
                case 'weibapost':
                    $rescount = D('weiba_post')->where('post_uid='.$uid)->limit($num)->count();
                    break;
                    //微吧精华
                case 'weibamarrow':
                    $rescount = D('weiba_post')->where('post_uid='.$uid.' and digest=1')->limit($num)->count();
                    break;
                    //发布知识
                case 'blogpost':
                    break;
                    //转发知识
                case 'blogrepost':
                    break;
                    //知识精华
                case 'blogmarrow':
                    break;
                    //相册上传真实图片
                case 'phototrueimg':
                    break;
                    //喜欢一张相册图片
                case 'photolove':
                    break;
                    //相互关注
                case 'followmutual':
                    $res = model('Follow')->getFriendsList($uid);
                    $rescount = $res['count'];
                    break;
                    //频道投稿
                case 'channelcontribute':
                    $rescount = D('channel')->where('uid='.$uid.' and status=1')->limit($num)->count();
                    break;
                    //管理员
                case 'manager':
                    $res = D('user_group_link')->where('uid='.$uid.' and user_group_id='.$num)->find();

                    return $res ? true : false;
                    break;
                    //绑定手机-new
                case 'bindtel':
                    $login = model('User')->where('uid='.$uid)->getField('login');
                    if (preg_match("/^[1][34578]\d{9}$/", $login) !== 0) {
                        return true;
                    } else {
                        return false;
                    }
                    break;
                    //绑定手机-new
                case 'inviteuser':
                    $res = D('invite_code')->where('inviter_uid='.$uid)->count();

                    return $res ? true : false;
                    break;
                    //商城送礼-new
                case 'sendgift':
                    $res = D('gift_user')->where('fromUserId='.$uid)->count();

                    return $res ? true : false;
                    break;
                    //分享被赞100次-new
                case 'weibodigged':
                    $rescount = model('Feed')->where('is_del=0 and uid='.$uid)->sum('digg_count');
                    break;
                    //收到10个礼物-new
                case 'giftreceive':
                    $rescount = D('gift_user')->where('toUserId='.$uid)->count();
                    break;
                    //邀请10个新用户-new
                case 'inviteusers':
                    $rescount = D('invite_code')->where('receiver_uid>0 and inviter_uid='.$uid)->count();
                    break;
            }
        }
        // 存储数据值Hash数组
        $this->setAmountHash($excutetype, intval($rescount).' / '.$num);
        // dump($excutetype.'-'.intval($rescount).' / '.$num);
        return $rescount >= $num;
    }
    /**
     * 领取奖励
     * @param unknown_type $exp
     * @param unknown_type $score
     * @param unknown_type $medal
     */
    public function getReward($exp, $score, $medal, $uid)
    {
        if ($uid) {
            model('Credit')->addTaskCredit($exp, $score, $uid);
            if ($medal) {
                //添加勋章
                $data['uid'] = $uid;
                $data['medal_id'] = $medal;
                $data['ctime'] = $_SERVER['REQUEST_TIME'];
                D('medal_user')->add($data);
                //清除用户缓存
                model('User')->cleanCache($uid);
            }
        }
    }

    /**
     * 设置统计数据Hash数据
     * @param string key 键值
     * @param string progressRate 进度比
     */
    private function setAmountHash($key, $progressRate)
    {
        $this->amountHash[$key] = $progressRate;
    }

    /**
     * 获取统计数据Hash数据
     * @param string key 键值
     * @return array 统计数据Hash数据
     */
    private function getAmountHash($key)
    {
        return $this->amountHash[$key];
    }
}
