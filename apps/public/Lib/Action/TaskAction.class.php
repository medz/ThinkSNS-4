<?php
/**
 * 任务操作类.
 *
 * @author Stream
 */
class TaskAction extends Action
{
    public function __construct()
    {
        parent::__construct();
        if (!CheckTaskSwitch()) {
            $this->error('该页面不存在！');
        }
    }

    public function index()
    {
        $type = $_REQUEST['type'] ? intval($_REQUEST['type']) : 1;
        //传入任务参数  type
        //查询当前 任务的 执行状态调用
        $list = model('Task')->getTaskList($type, $this->mid);
        if (!$list['list']) {
            $this->error('没有该任务数据！');
        }
        if ($list['task_type'] == 2 && $list['task_leve'] == 5 && $list['iscomplete']) {
            $list['task_level'] = 6;
        }
        $this->assign('type', $type);
        $this->assign($list);
        $this->display();
        //页面显示列表
    }

    public function customIndex()
    {
        $list = model('TaskCustom')->getList();
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
        $this->assign('list', $list);
        $this->display();
    }

    /**
     * 完成自定义任务领取奖励.
     */
    public function completeCustom()
    {
        $id = intval($_POST['id']);
        $mid = $GLOBALS['ts']['mid'];
        $task = model('TaskCustom')->where('id='.$id)->find();
        $condition = json_decode($task['condition']);
        foreach ($condition as $k => $v) {
            $status = model('Cache')->get('customtask_'.$mid.'_'.$id.'_'.$k);
            if (!$status) {
                //未完成
                exit(0);
            }
        }
        //领取数量限制
        $count = D('task_receive')->where('task_type=3 and task_level='.$id)->count();
        if ($task['num']) {
            if ($count >= $task['num']) {
                exit(3);
            }
        }
        //领取奖励
        $res = model('TaskCustom')->completeTask($id, $mid);
        if ($res) {
            $reward = json_decode($task['reward']);

            //获得奖励
            model('Task')->getReward($reward->exp, $reward->score, $reward->medal->id, $GLOBALS['ts']['mid']);

            $reward->exp && $exp = '经验+'.$reward->exp;
            $reward->score && $score = ' 积分+'.$reward->score;
            $reward->medal->name && $medalname = ' 获得一枚’'.$reward->medal->name.'‘勋章<img src="'.getImageUrl($reward->medal->src).'" class="ico-badge" width="100" height="100"/>';

            $content = '已完成'.$task['task_name'].$exp.$score.$medalname;
            $button = '<span><input type="checkbox" id="taskfeed" value="1" class="checkbox"/>同时发表一条分享</span><a href="javascript:postfeed('.$id.');" class="btn-grey-white">确定</a>';

            $content = '<div class="task-box"><a href="javascript:ui.box.close();" class="ico-close right"></a><div class="content"><h3>恭喜你：</h3><p>已完成'.$task['task_name'].','.$exp.$score.$medalname.'</p>
						'.$button.'</div></div>';
            echo $content;
        } else {
            //重复领取
            exit(2);
        }
    }

    /**
     * 领取奖励.
     */
    public function complete_task()
    {
        //任务类型
        $type = intval($_POST['type']);
        //任务等级
        $level = intval($_POST['level']);
        //领取奖励
        $res = model('Task')->completeTask($type, $level, $this->mid);
        if ($res) {
            $map['task_type'] = $type;
            $map['task_level'] = $level;
            $jsreward = D('task_reward')->where($map)->getField('reward');
            $reward = json_decode($jsreward);

            $reward->exp && $exp = '经验+'.$reward->exp;
            $reward->score && $score = ' 积分+'.$reward->score;

            $reward->medal->name && $medalname = ' 获得一枚’'.$reward->medal->name.'‘勋章<img src="'.getImageUrl($reward->medal->src).'" class="ico-badge" width="100" height="100"/>';
            //获得奖励
            model('Task')->getReward($reward->exp, $reward->score, $reward->medal->id, $this->mid);

            if ($type == 1) {
                //任务奖励
                $content = '<div class="task-box"><a href="javascript:ui.box.close();" class="ico-close right"></a><div class="content"><h3>恭喜你：</h3><p>已完成每日任务,'.$exp.$score.$medalname.'</p></div></div>';
            } else {
                $task_name = model('Task')->where($map)->getField('task_name');
                //任务奖励
// 				if ( $level < 5 ){
                    $button = '<span><input type="checkbox" id="taskfeed" value="1" checked="checked" class="checkbox"/>同时发表一条分享</span><a href="javascript:gonext('.$type.','.$level.')" class="btn-grey-white">确定</a>';
// 				}
                $content = '<div class="task-box"><a href="javascript:ui.box.close();" class="ico-close right"></a><div class="content"><h3>恭喜你：</h3><p>已完成所有的'.$task_name.','.$exp.$score.$medalname.'</p>
						'.$button.'</div></div>';
            }

            echo $content;
        } else {
            echo 0;
        }
    }

    public function complete_step()
    {
        $id = intval($_POST['id']);
        if ($id) {
            $status = D('task_user')->where('uid='.$this->mid.' and ( status=0 or receive=1 ) and id='.$id)->find();
            $taskexist = D('task_user')->where('uid='.$this->mid.' and id='.$id)->find();
            if ($status || !$taskexist) {
                echo 0;

                return;
            }
            $res = D('task_user')->setField('receive', 1, 'id='.$id);
            if ($res) {
                $allcomplete = true;
                if ($_POST['tasktype'] == 2) {
                    $tasklevel = intval($_POST['tasklevel']);
                    $exist = D('task_user')->where('uid='.$this->mid.' and task_type=2 and task_level='.$tasklevel.' and receive=0')->find();
                    $exist && $allcomplete = false;
                }

                //任务奖励
                $tasklevel = D('task_user')->where('id='.$id)->getField('task_level');
                $tid = D('task_user')->where('id='.$id)->getField('tid');
                $reward = json_decode(model('Task')->where('id='.$tid)->getField('reward'));
                $info = '经验+'.$reward->exp.' 积分+'.$reward->score;
                $reward->medal->name && $info .= ' 获得勋章‘'.$reward->medal->name.'’';
                //获得奖励
                model('Task')->getReward($reward->exp, $reward->score, $reward->medal->id, $GLOBALS['ts']['mid']);
                $res = array('allcomplete' => $allcomplete, 'tasktype' => $_POST['tasktype'], 'info' => $info);
                echo json_encode($res);
            } else {
                echo 0;
            }
        }
    }

    public function postTaskFeedCustom()
    {
        $id = intval($_POST['id']);
        if (!$id) {
            return;
        }
        $task = model('TaskCustom')->where('id='.$id)->field('task_name,reward')->find();
        $taskname = $task['task_name'];

        $reward = $task['reward'];
        $reward = json_decode($reward);

        $feedtype = 'post';
        $medalname = $reward->medal->name;

        $str = '我刚刚完成了任务‘'.$taskname.'’';

        if ($medalname) {
            $str .= ',获得了‘'.$medalname.'’勋章,';
            $feedtype = 'post';
        }

        $str .= '快来做任务吧。'.U('public/Medal/index', 'type=1&uid='.$this->mid);

        $data['body'] = $str;
        model('Feed')->put($this->mid, 'public', $feedtype, $data);
    }

    public function postTaskFeed()
    {
        $type = intval($_POST['type']);
        $level = intval($_POST['level']);

        $taskname = model('Task')->where('task_type='.$type.' and task_level='.$level)->getField('task_name');
        $reward = D('task_reward')->where('task_type='.$type.' and task_level='.$level)->getField('reward');
        $reward = json_decode($reward);

        $feedtype = 'post';
        $medalname = $reward->medal->name;

        $str = '我刚刚完成了全部的‘'.$taskname.'’';

        if ($medalname) {
            $str .= '，获得了‘'.$medalname.'’勋章，';
        }

        $str .= '快来做任务吧。'.U('public/Medal/index', 'type=1&uid='.$this->mid);

        //炫耀卡片
        $map['name'] = $medalname;
        $share_card = model('Medal')->where($map)->getField('share_card');

        if ($share_card != null) {
            $share_card = explode('|', $share_card);
            $data['attach_id'] = $share_card[0];
            $feedtype = 'postimage';
        }
        $data['body'] = $str;
        model('Feed')->put($this->mid, 'public', $feedtype, $data);
    }

    public function test()
    {
        //		dump(file_exists(UPLOAD_URL.'/avatar'.model('Avatar')->convertUidToPath($GLOBALS['ts']['mid']).'/original.jpg'));
// 		$list = D('task')->findAll();
// 		foreach ( $list as $v ){
// 			$array = array( 'exp' => 10 , 'score' => 10 , 'medal' => array( 'id'=>1,'name'=>'新手勋章' ,'src'=>'' ) );
// 			D('task')->setField( 'reward' , json_encode( $array ) , 'id='.$v['id']);
// 			dump(D()->getLastSql());
//  		dump(model('Follow')->getFriendsList($this->mid));
// 		}
// 		dump(file_exists(UPLOAD_PATH.'/avatar'.model('Avatar')->convertUidToPath($GLOBALS['ts']['mid']).'/original.jpg'));
    }
}
