<?php
/**
 * 自定义任务Dao类
 * @author Stream
 *
 */
class TaskCustomModel extends Model
{
    protected $tableName = 'task_custom';
    /**
     * 返回自定义任务列表
     * @param  unknown_type $map
     * @return unknown
     */
    public function getList($map)
    {
        $list = $this->where($map)->order('id desc')->findAll();
        $mid = $GLOBALS['ts']['mid'];
        foreach ($list as &$v) {
            $condition = json_decode($v['condition']);
            $complete = true;
            $taskcondition = true;
            if ($v['task_condition']) {
                $cond = explode('-', $v['task_condition']);
                $taskmap['task_type'] = $cond[0];
                $taskmap['task_level'] = $cond[1];
                $taskmap['status'] = 0;
                $exist = D('task_user')->where($taskmap)->find();
                $exist && $taskcondition = false;

                $taskname = model('Task')->where('task_type='.$cond[0].' and task_level='.$cond[1])->getField('task_name');
                $v['task_condition_name'] = $taskname;
            }
            $v['isfull'] = false;
            if ($v['num']) {
                //是否已经领完
                $count = D('task_receive')->where('task_type=3 and task_level='.$v['id'])->count();
                $v['condition_desc']['medalnum'] = $v['num'] - $count;
                if ($count >= $v['num']) {
                    $v['isfull'] = true;
                }
            }
            //是否过期
            $v['status'] = 'ing';
            $etime = explode('|', $condition->endtime);
            if ((strtotime($etime[0]) >= $_SERVER['REQUEST_TIME']) && $etime[0]) {
                $v['status'] = 'coming';
            } elseif ((strtotime($etime[1]) < $_SERVER['REQUEST_TIME']) && $etime[1]) {
                $v['status'] = 'over';
            }
            foreach ($condition as $k => $c) {
                $status = model('Cache')->get('customtask_'.$mid.'_'.$v['id'].'_'.$k);
                if (!$status && $c) {
                    $status = $this->iscomplete($k, $c);
                    if ($status) {
                        model('Cache')->set('customtask_'.$mid.'_'.$v['id'].'_'.$k, $status);
                    } else {
                        $complete = false;
                    }
                }
                if ($c) {
                    $v['condition_desc'][$k] = $status;
                }
            }
            //前置任务
            if (!$taskcondition || $v['isfull']) {
                $complete = false;
            }
            $v['condition_desc']['task_condition'] = $taskcondition;
            $v['reward'] = json_decode($v['reward']);
            $v['iscomplete'] = $complete;
            $v['receive'] = false;
            if ($complete) {
                $rmap['task_type'] = 3;
                $rmap['task_level'] = $v['id'];
                $rmap['uid'] = $mid;
                //是否领取奖励
                $v['receive'] = D('task_receive')->where($rmap)->limit(1)->count();
            }
            unset($condition);
        }

        return $list;
    }
    /**
     * 判断是否完成
     * @param  unknown_type $key
     * @param  unknown_type $value
     * @return bool
     */
    private function iscomplete($key, $value)
    {
        $mid = $GLOBALS['ts']['mid'];
        switch ($key) {
            case 'endtime':
                $times = explode('|', $value);

                return strtotime($times[0]) <= $_SERVER['REQUEST_TIME'] && strtotime($times[1]) >= $_SERVER['REQUEST_TIME'];
                break;
            case 'regtime':
                $times = explode('|', $value);
                $ctime = model('User')->where('uid='.$mid)->getField('ctime');

                return strtotime($times[0]) <= $ctime && strtotime($times[1]) >= $ctime;
                break;
            case 'userlevel':
                $credit = model('Credit')->getUserCredit($mid);

                return in_array($credit['level']['level'], explode(',', $value));
                break;
            case 'usergroup':
                $usergroup = model('UserGroupLink')->where('uid='.$mid)->field('user_group_id')->findAll();
                $usergroup = getSubByKey($usergroup, 'user_group_id');
                $gids = explode(',', $value);
                $res = array_intersect($usergroup, $gids);

                return $res ? true : false;
                break;
            case 'topic':
                $topicid = model('FeedTopic')->where("topic_name='".$value."'")->getField('topic_id');
                $topicid && $res = model('Feed')->join('as a inner join '.C('DB_PREFIX').'feed_topic_link b on a.feed_id=b.feed_id')->where('b.topic_id='.$topicid.' and a.uid='.$mid)->find();

                return $res ? true : false;
                break;
        }
    }
    /**
     * 领取任务奖励
     * @param  unknown_type $id
     * @param  unknown_type $uid
     * @return bool
     */
    public function completeTask($id, $uid)
    {
        $map['task_type'] = 3;
        $map['task_level'] = $id;
        $map['uid'] = $uid;
        $receive = D('task_receive')->where($map)->find();
        //是否重复领取
        if (!$receive) {
            //记录领奖记录
            $data['uid'] = $uid;
            $data['task_type'] = 3;
            $data['task_level'] = $id;
            $data['ctime'] = $_SERVER['REQUEST_TIME'];
            $res = D('task_receive')->add($data);

            return true;
        } else {
            return false;
        }
    }
}
