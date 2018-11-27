<?php
/**
 * 任务API.
 *
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class TaskApi extends OldTaskApi
{
    /**
     * 每日任务
     *
     * @var int
     **/
    const DAILY = 1;

    /**
     * 主线任务
     *
     * @var int
     **/
    const MAINLINE = 2;

    /**
     * 每日任务
     * 和主线任务复用.
     *
     * @param int $type 只有两个类型，类已经定义，self::DAILY为每日，self::MAINLINE为主线，这个方法是TS以前任务遗留定义，后续会修改，现在先这么写！
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function daily($type = self::DAILY)
    {
        $list = model('Task')->getTaskList($type, $this->mid);
        $list = $list['list'];
        $tasks = array();

        foreach ($list as $value) {
            $task = array();
            $task['name'] = $value['step_name'];
            $task['desc'] = $value['step_desc'];
            $task['status'] = $value['status'];
            $task['progress_rate'] = $value['progress_rate'];
            $task['exp'] = $value['reward']->exp;
            $task['score'] = $value['reward']->score;

            /* # 勋章 */
            if ($value['reward']->medal) {
                $task['icon'] = getImageUrl($value['reward']->medal->src);
            }

            /* # 检查是否领取了奖励，没有领取则领取 */
            if (!$value['receive'] and $task['status'] == 1) {
                $medalId = false;
                isset($value['reward']->medal->id) and $medalId = $value['reward']->medal->id;

                model('Task')->completeTask($value['task_type'], $value['task_level'], $this->mid);

                model('Task')->getReward($task['exp'], $task['score'], $medalId, $this->mid);

                D('task_user')->where('`id` = '.intval($value['id']))->save(array(
                    'receive' => '1',
                ));

                /* # 发布任务动态 */
                /* # 如果有耀卡片，则发布耀卡片 */
                $card = model('Medal')->where('`id` = '.$medalId)->field('share_card')->getField('share_card');

                $str = '我刚刚完成了任务‘'.$task['name'].'’';

                if (isset($value['reward']->medal->name)) {
                    $str .= '，获得了‘'.$value['reward']->medal->name.'’勋章，';
                }

                $str .= '快来做任务吧。'.U('public/Medal/index', 'type=1&uid='.$this->mid);

                $data = array(
                    'body' => $str,
                );
                $type = 'post';

                if ($card) {
                    $card = explode('|', $card);
                    $card = $card[0];
                    if ($card) {
                        $type = 'postimage';
                        $data['attach_id'] = $card;
                    }
                }

                /* # 发布任务动态，因为领导意思，暂时关闭 */
                // model('Feed')->put($this->mid, 'public', $type, $data);
            }

            array_push($tasks, $task);
        }

        return $tasks;
    }

    /**
     * 主线任务，代码复用每日任务
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function mainLine()
    {
        return $this->daily(self::MAINLINE);
    }

    /**
     * 自定义任务（副本任务）API.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function custom()
    {
        $list = model('TaskCustom')->getList('1=1');
        foreach ($list as &$v) {
            $condition = json_decode($v['condition']);
            $cons = array();
            foreach ($condition as $ck => $value) {
                if ($value) {
                    switch ($ck) {
                        case 'endtime':
                            $endtime = explode('|', $condition->endtime);
                            $cons[] = array('status' => $v['condition_desc']['endtime'], 'desc' => '领取时间：'.$endtime[0].' - '.$endtime[1]);
                            break;
                        case 'userlevel':
                            $cons[] = array('status' => $v['condition_desc']['userlevel'], 'desc' => '用户等级：T( '.$condition->userlevel.' )');
                            break;
                        case 'usergroup':
                            $groups = explode(',', $condition->usergroup);
                            $gname = '';
                            foreach ($groups as $g) {
                                $ginfo = model('UserGroup')->getUserGroup($g);
                                $gname .= ' '.$ginfo['user_group_name'];
                            }
                            $cons[] = array('status' => $v['condition_desc']['usergroup'], 'desc' => '用户组：'.$gname);
                            break;
                        case 'regtime':
                            $regtime = explode('|', $condition->regtime);
                            $cons[] = array('status' => $v['condition_desc']['regtime'], 'desc' => '用户注册时间：'.$regtime[0].' - '.$regtime[1]);
                            break;
                        case 'topic':
                            $topic = $condition->topic;
                            $cons[] = array('status' => $v['condition_desc']['topic'], 'desc' => '发布指定话题：'.$topic);
                            break;
                    }
                }
            }
            if ($v['task_condition_name']) {
                $cons[] = array('status' => $v['condition_desc']['task_condition'], 'desc' => '前置任务：'.$v['task_condition_name']);
            }
            if ($v['num']) {
                $v['surplus'] = '剩余领取数：'.$v['condition_desc']['medalnum'];
            }
            $v['cons'] = $cons;
        }

        $tasks = array();
        foreach ($list as $value) {
            $task = array();
            $task['id'] = $value['id'];
            $task['name'] = $value['task_name'];
            $task['desc'] = $value['task_desc'];
            $task['exp'] = $value['reward']->exp;
            $task['score'] = $value['reward']->score;
            $task['cons'] = $value['cons'];
            $task['iscomplete'] = $value['iscomplete'];
            $task['receive'] = $value['receive'];
            $task['surplus'] = $value['surplus'];

            /* # 勋章 */
            if ($value['reward']->medal->src) {
                $task['icon'] = getImageUrl($value['reward']->medal->src);
            }

            array_push($tasks, $task);

            /* # 领取奖励，已经完成的话 */
            if ($task['iscomplete'] == 1 and $task['receive'] != 1) {
                /* 数量限制检测 */
                if ($value['num'] && D('task_receive')->where('`task_type`=3 AND `task_level`='.$task['id'])->count() < $value['num']) {
                    /* # 领取奖励 */
                    if (model('TaskCustom')->completeTask($task['id'], $this->mid)) {
                        /* # 领取成功 */
                        model('Task')->getReward($value['reward']->exp, $value['reward']->score, $value['reward']->medal->id, $this->mid);

                        /* # 发布动态 */
                        $str = '我刚刚完成了任务‘'.$task['name'].'’';

                        if ($value['reward']->medal->name) {
                            $str .= '，获得了‘'.$value['reward']->medal->name.'’勋章，';
                        }

                        $str .= '快来做任务吧。'.U('public/Task/customIndex');

                        $data = array(
                            'body' => $str,
                        );
                        $type = 'post';

                        $card = model('Medal')->where('`id` = '.$value['reward']->medal->id)->field('share_card')->getField('share_card');

                        if ($card) {
                            $card = explode('|', $card);
                            $card = $card[0];
                            if ($card) {
                                $type = 'postimage';
                                $data['attach_id'] = $card;
                            }
                        }

                        /* # 发布任务动态，因为领导意思，暂时关闭 */
                        // model('Feed')->put($this->mid, 'public', $type, $data);
                    }
                }
            }
        }

        return $tasks;
    }
} // END class TaskApi extends OldTaskApi

/*====================下面是老的API接口======================*/
/**
 * @author jason
 */
class OldTaskApi extends Api
{
    /**
     * 获取当前用户积分	--using.
     *
     * @return array 任务列表
     */
    public function task_list()
    {
        $uid = $this->mid;
        $list = M('task_user')->where(' uid='.$this->mid)->findAll();
        foreach ($list as $u) {
            $my[$u['tid']] = $u;
            if ($u['status'] == 1) {
                if ($u['receive'] == 1) {
                    $my[$u['tid']]['status'] = 2; // 已领取奖励
                } else {
                    $my[$u['tid']]['status'] = 1; // 已完成，未领取奖励
                }
            } else {
                $my[$u['tid']]['status'] = 0; // 未完成
            }
        }

        $list = M('task')->where('is_del=0')->order('task_type asc, task_level asc')->findAll();
        $has_del = false;
        // 增加用户数据
        foreach ($list as $k => $vo) {
            if (isset($my[$vo['id']])) {

                // 每日任务数据初始化
                $time = strtotime(date('Ymd'));
                if ($vo['task_type'] == 1 && $my[$vo['id']]['ctime'] < $time && $has_del == false) {
                    // 删除历史
                    $dmap['task_type'] = 1;
                    $dmap['uid'] = $uid;
                    M('task_user')->where($dmap)->delete();
                    $has_del = true;
                    $list[$k]['status'] = 0;
                } else {
                    $list[$k]['status'] = $my[$vo['id']]['status'];
                }
            } else {
                $list[$k]['status'] = 0;

                // 每日任务数据初始化
                if ($vo['task_type'] == 1 && $has_del == false) {
                    // 删除历史
                    $dmap['task_type'] = 1;
                    $dmap['uid'] = $uid;
                    M('task_user')->where($dmap)->delete();
                    $has_del = true;
                }
                // 初始化新的数据
                $udata['uid'] = $uid;
                $udata['tid'] = $vo['id'];
                $udata['task_level'] = $vo['task_level'];
                $udata['task_type'] = $vo['task_type'];
                $udata['ctime'] = $_SERVER['REQUEST_TIME'];
                $udata['status'] = 0;
                $udata['desc'] = '';
                $udata['receview'] = 0;
                // 加入任务表
                M('task_user')->add($udata);
            }
        }

        // 更新未完成的任务
        $userdata = model('UserData')->getUserData($uid);
        $model = model('Task');
        foreach ($list as $k => $t) {
            if ($t['status'] != 0) {
                continue;
            }

            $condition = json_decode($t['condition']);
            $conkey = key($condition);
            // 判断任务是否完成
            $res = $model->_executeTask($conkey, $condition->$conkey, $uid, $t['task_type'], $userdata);
            $list[$k]['conkey'] = array_shift(array_keys((array) json_decode($t['condition'])));
            $list[$k]['progress_rate'] = $model->getAmountHash($list[$k]['conkey']);
            if ($res) {
                // 刷新用户任务执行状态
                M('task_user')->setField('status', 1, 'tid='.$t['id'].' and uid='.$uid);
                $list[$k]['status'] = 1;
            }
        }

        // dump($list);
        $task_list = array();
        foreach ($list as $vo) {
            $key = $vo['task_type'].$vo['task_level'];

            $task_list[$key]['title'] = $vo['task_name'];
            $task_list[$key]['task_type'] = $vo['task_type'];
            $task_list[$key]['task_level'] = $vo['task_level'];

            $task['task_id'] = $vo['id'];
            $task['task_name'] = $vo['step_name'];
            $task['step_desc'] = $vo['step_desc'];
            $score = json_decode($vo['reward'], true);
            $task['reward'] = $score['score'];
            $task['status'] = $vo['status'];

            $task['conkey'] = $vo['conkey'];
            $task['progress_rate'] = empty($vo['progress_rate']) ? '0 / 1' : $vo['progress_rate'];
            $score['medal'] = (array) $score['medal'];
            if (!empty($vo['headface'])) {
                $attach = explode('|', $vo['headface']);
                // $task ['img'] = getImageUrl ( $attach [1] );
                $task['img'] = (string) getImageUrlByAttachId($attach[0]);
            } else {
                $task['img'] = '';
            }

            $task_list[$key]['list'][] = $task;
        }
        $res = array();
        foreach ($task_list as $k => $v) {
            $count = $num = 0;
            foreach ($v['list'] as $vv) {
                $num += $vv['status'] > 0 ? 1 : 0;
                $num2 += $vv['status'] == 2 ? 1 : 0;
                $count += 1;
            }
            $v['receive'] = 0; // 未领取完
            $v['status'] = 0; // 未开始
            if ($k < 22) {
                if ($count == $num) {
                    $v['status'] = 2; // 已完成
                    $num2 == $count && $v['receive'] = 1;
                } elseif ($num == 0) {
                    $v['status'] = 0; // 未开始
                } else {
                    $v['status'] = 1; // 进行中
                }
                $res[] = $v;
            } else {
                $i = count($res) - 1;
                if ($count == $num) {
                    $v['status'] = 2; // 已完成
                    $num2 == $count && $v['receive'] = 1;
                    $res[] = $v;
                } elseif ($res[$i]['status'] == 2) {
                    $v['status'] = 1;
                    $res[] = $v;
                }
            }
        }

        return $res;
    }

    // public function task_list1() {
    // 	$task_list = array ();
    // 	// 每日任务
    // 	$daily_task = model ( 'Task' )->getTaskList ( '1', $this->mid, 1 );
    // 	// dump($daily_task);exit;
    // 	$task_list [0] ['title'] = $daily_task ['task_name'];
    // 	$task_list [0] ['task_type'] = $daily_task ['task_type'];
    // 	$task_list [0] ['task_level'] = $daily_task ['task_level'];
    // 	foreach ( $daily_task ['list'] as $k => $v ) {
    // 		$task ['task_id'] = $v ['id'];
    // 		$task ['task_name'] = $v ['step_name'];
    // 		$task ['step_desc'] = $v ['step_desc'];
    // 		$score = ( array ) $v ['reward'];
    // 		$task ['reward'] = $score ['score'];
    // 		if ($v ['status'] == 1) {
    // 			if ($v ['receive'] == 1) {
    // 				$task ['status'] = 2; // 已领取奖励
    // 			} else {
    // 				$task ['status'] = 1; // 已完成，未领取奖励
    // 			}
    // 		} else {
    // 			$task ['status'] = 0; // 未完成
    // 		}
    // 		$task ['progress_rate'] = $v ['progress_rate'] ? $v ['progress_rate'] : '0 / 1';
    // 		$score ['medal'] = ( array ) $score ['medal'];
    // 		$task ['img'] = $v ['headface'];
    // 		$task_list [0] ['list'] [] = $task;
    // 	}
    // 	// return $task_list;
    // 	// 主线任务
    // 	// $task_level = model('Task')->getUserTask('2',$this->mid);; //获取用户当前的任务level
    // 	// $user_task_level = $task_level['task_level'] ? $task_level['task_level'] : 1;
    // 	// //刷新执行任务状态
    // 	// // model('Task')->isComplete($tasktype, $uid , $tasklevel);

    // 	// 新手任务
    // 	$xinshou_task_list = model ( 'Task' )->getTaskList ( '2', $this->mid, 1 );
    // 	// dump($xinshou_task_list);exit;
    // 	$task_list [1] ['title'] = $xinshou_task_list ['task_name'];
    // 	$task_list [1] ['task_type'] = $xinshou_task_list ['task_type'];
    // 	$task_list [1] ['task_level'] = $xinshou_task_list ['task_level'];
    // 	foreach ( $xinshou_task_list ['list'] as $k => $v ) {
    // 		$task ['task_id'] = $v ['id'];
    // 		$task ['task_name'] = $v ['step_name'];
    // 		$task ['step_desc'] = $v ['step_desc'];
    // 		$score = ( array ) $v ['reward'];
    // 		$task ['reward'] = $score ['score'];
    // 		if ($v ['status'] == 1) {
    // 			if ($v ['receive'] == 1) {
    // 				$task ['status'] = 2; // 已领取奖励
    // 			} else {
    // 				$task ['status'] = 1; // 已完成，未领取奖励
    // 			}
    // 		} else {
    // 			$task ['status'] = 0; // 未完成
    // 		}
    // 		$task ['progress_rate'] = $v ['progress_rate'] ? $v ['progress_rate'] : '0 / 1';
    // 		$score ['medal'] = ( array ) $score ['medal'];
    // 		$task ['img'] = $v ['headface'];
    // 		$task_list [1] ['list'] [] = $task;
    // 	}
    // 	// 晋级任务
    // 	/*
    // 	 * $jinji_task_list = model('Task')->getTaskList('3',$this->mid, 1);
    // 	 * // dump($jinji_task_list);exit;
    // 	 * $task_list[2]['title'] = $jinji_task_list['task_name'];
    // 	 * $task_list[2]['task_type'] = $jinji_task_list['task_type'];
    // 	 * $task_list[2]['task_level'] = $jinji_task_list['task_level'];
    // 	 * foreach ($jinji_task_list['list'] as $k => $v) {
    // 	 * $task['task_id'] = $v['id'];
    // 	 * $task['task_name'] = $v['step_name'];
    // 	 * $task['step_desc'] = $v['step_desc'];
    // 	 * $score = (array)$v['reward'];
    // 	 * $task['reward'] = $score['score'];
    // 	 * if($v['status']==1){
    // 	 * if($v['receive']==1){
    // 	 * $task['status'] = 2; //已领取奖励
    // 	 * }else{
    // 	 * $task['status'] = 1; //已完成，未领取奖励
    // 	 * }
    // 	 * }else{
    // 	 * $task['status'] = 0; //未完成
    // 	 * }
    // 	 * $task['progress_rate'] = $v['progress_rate']?$v['progress_rate']:'0 / 1';
    // 	 * $score['medal'] = (array)$score['medal'];
    // 	 * $task['img'] = $v['headface'];
    // 	 * $task_list[2]['list'][] = $task;
    // 	 * }
    // 	 */
    // 	return $task_list;
    // }

    /**
     * 领取奖励	--using.
     *
     * @param
     *        	integer task_id 任务ID
     * @param
     *        	string task_type 任务ID
     * @param
     *        	string task_level 任务ID
     *
     * @return [type] [description]
     */
    public function complete_step()
    {
        $task_id = intval($this->data['task_id']);
        $id = M('task_user')->where('uid='.$this->mid.' and tid='.$task_id)->getField('id');
        // dump ( M ( 'task_user' )->getLastSql () );
        // dump ( $id );
        if ($id) {
            $status = D('task_user')->where('uid='.$this->mid.' and ( status=0 or receive=1 ) and id='.$id)->find();
            $taskexist = D('task_user')->where('uid='.$this->mid.' and id='.$id)->find();
            if ($status || !$taskexist) {
                return array(
                        'status' => 0,
                        'msg'    => '参数错误',
                );
            }
            $res = D('task_user')->setField('receive', 1, 'id='.$id);
            if ($res) {
                $allcomplete = true;
                if ($this->data['task_type'] == 2) {
                    $tasklevel = intval($this->data['task_level']);
                    $exist = D('task_user')->where('uid='.$this->mid.' and task_type=2 and task_level='.$tasklevel.' and receive=0')->find();
                    $exist && $allcomplete = false;
                }

                // 任务奖励
                $tasklevel = D('task_user')->where('id='.$id)->getField('task_level');
                $tid = D('task_user')->where('id='.$id)->getField('tid');
                $reward = json_decode(model('Task')->where('id='.$tid)->getField('reward'));
                $info = '经验+'.$reward->exp.' 积分+'.$reward->score;
                $reward->medal->name && $info .= ' 获得勋章‘'.$reward->medal->name.'’';
                // 获得奖励
                model('Task')->getReward($reward->exp, $reward->score, $reward->medal->id, $GLOBALS['ts']['mid']);
                // $res = array('allcomplete'=> $allcomplete , 'tasktype'=>$this->data['task_type'] ,'info'=>$info);
                // echo json_encode($res);
                return array(
                        'status' => 1,
                        'msg'    => '领取成功',
                );
            } else {
                return array(
                        'status' => 0,
                        'msg'    => '领取失败',
                );
            }
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '参数错误',
            );
        }
    }
}
