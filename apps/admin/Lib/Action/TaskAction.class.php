<?php

tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
/**
 * 任务后台管理.
 *
 * @author Stream
 */
class TaskAction extends AdministratorAction
{
    public function _initialize()
    {
        $this->pageTitle['index'] = '任务列表';
        $this->pageTitle['mainIndex'] = '任务列表';
        $this->pageTitle['customIndex'] = '任务列表';
        $this->pageTitle['editCustomTask'] = '编辑副本任务';
        $this->pageTitle['editTask'] = '编辑任务';
        $this->pageTitle['addTask'] = '添加任务';
        $this->pageTitle['reward'] = '任务奖励';
        $this->pageTitle['editReward'] = '编辑奖励';
        $this->pageTitle['taskConfig'] = '任务配置';
        parent::_initialize();
    }

    /**
     * 初始化列表管理菜单.
     *
     * @param string $type 列表类型，index、pending、dellist
     */
    private function _initListAdminMenu()
    {
        // tab选项
        $this->pageTab[] = array('title' => '每日任务', 'tabHash' => 'index', 'url' => U('admin/Task/index'));
        $this->pageTab[] = array('title' => '主线任务', 'tabHash' => 'mainIndex', 'url' => U('admin/Task/mainIndex'));
        $this->pageTab[] = array('title' => '副本任务', 'tabHash' => 'customIndex', 'url' => U('admin/Task/customIndex'));
    }

    /**
     * 任务列表.
     */
    public function index()
    {
        $this->_initListAdminMenu();

        $this->pageKeyList = array('task_name', 'step_name', 'step_desc', 'count', 'reward', 'medal', 'DOACTION');
        $list = model('Task')->where('task_type=1 and is_del=0')->field('id,task_name,step_name,step_desc,reward')->order('task_type,task_level')->findPage();
        // dump(D()->getLastSql());
        // dump($list);exit;
        $ids = getSubByKey($list['data'], 'id');
        $users = D('task_user')->query('select tid,count(tid) as tcount from '.C('DB_PREFIX').'task_user where tid in('.implode(',', $ids).') group by tid');
        $iduser = array();
        foreach ($users as $u) {
            $iduser[$u['tid']] = $u['tcount'];
        }
        foreach ($list['data'] as &$v) {
            $v['count'] = $iduser[$v['id']] ? $iduser[$v['id']] : 0;
            $reward = json_decode($v['reward']);
            $src = $reward->medal->src;
            $v['reward'] = '经验+'.$reward->exp.' 积分+'.$reward->score;
            $src && $v['medal'] = '<img width="100px" height="100px" src="'.getImageUrl($src).'" />';
            $v['DOACTION'] = "<a href='".U('admin/Task/editTask', array('id' => $v['id']))."' >编辑</a>";
        }
        $this->allSelected = false;
        $this->displayList($list);
    }

    /**
     * 副本任务
     */
    public function customIndex()
    {
        $this->_initListAdminMenu();

        $this->pageKeyList = array('task_name', 'task_desc', 'condesc', 'count', 'reward', 'medal', 'DOACTION');
        $list = D('task_custom')->field('id,task_name,task_desc,task_condition,`condition`,reward')->order('id')->findPage();
        $ids = getSubByKey($list['data'], 'id');
        $users = D('task_receive')->query('select task_level,count(task_level) as tcount from '.C('DB_PREFIX').'task_receive where task_level in('.implode(',', $ids).') and task_type=3 group by task_level');
        $iduser = array();
        foreach ($users as $u) {
            $iduser[$u['task_level']] = $u['tcount'];
        }
        foreach ($list['data'] as &$v) {
            $v['count'] = $iduser[$v['id']];
            $reward = json_decode($v['reward']);
            $src = $reward->medal->src;
            $v['reward'] = '经验+'.$reward->exp.' 积分+'.$reward->score;
            $src && $v['medal'] = '<img width="100px" height="100px" src="'.getImageUrl($src).'" />';
            $condesc = '';
            if ($v['task_condition']) {
                $c = explode('-', $v['task_condition']);
                $tname = D('Task')->where('task_type='.$c[0].' and task_level='.$c[1])->getField('task_name');
                $condesc .= '前置任务:'.$tname.'</br>';
            }
            $condition = json_decode($v['condition']);
            foreach ($condition as $k => $con) {
                switch ($k) {
                        case 'endtime':
                            $endtime = explode('|', $condition->endtime);
                            $condesc .= '领取时间：'.$endtime[0].' - '.$endtime[1].'</br>';
                            break;
                        case 'userlevel':
                            $condesc .= '用户等级：T( '.$condition->userlevel.' )'.'</br>';
                            break;
                        case 'usergroup':
                            $groups = explode(',', $condition->usergroup);
                            $gname = '';
                            foreach ($groups as $g) {
                                $ginfo = model('UserGroup')->getUserGroup($g);
                                $gname .= ' '.$ginfo['user_group_name'];
                            }
                            $condesc .= '用户组：'.$gname.'</br>';
                            break;
                        case 'regtime':
                            $regtime = explode('|', $condition->regtime);
                            $condesc .= '用户注册时间：'.$regtime[0].' - '.$regtime[1].'</br>';
                            break;
                        case 'topic':
                            $topic = $condition->topic;
                            $condesc .= '发布指定话题：'.$topic.'</br>';
                            break;
                    }
            }
            $v['condesc'] = $condesc;
            $v['DOACTION'] = "<a href='".U('admin/Task/editCustomTask', array('id' => $v['id'], 'tabHash' => 'customIndex'))."' >编辑</a>";
            $v['DOACTION'] .= " <a href='javascript:void(0)' onclick='admin.delcustomtask(".$v['id'].")'>删除</a>";
        }

        $this->pageButton[] = array('title' => '添加任务', 'onclick' => "javascript:location.href='".U('admin/Task/addTask', array('tabHash' => 'customIndex'))."';");
        $this->pageButton[] = array('title' => '删除', 'onclick' => 'admin.delcustomtask()');
        $this->displayList($list);
    }

    /**
     * 删除任务
     */
    public function doDeleteCustomTask()
    {
        $ids = $_POST['id'];
        $map['id'] = array('in', explode(',', $ids));
        $res = model('TaskCustom')->where($map)->delete();
        if ($res) {
            $return['status'] = 1;
            $return['data'] = '删除成功';
        } else {
            $return['status'] = 0;
            $return['data'] = '删除失败';
        }
        exit(json_encode($return));
    }

    public function mainIndex()
    {
        $this->_initListAdminMenu();

        $this->pageKeyList = array('task_name', 'step_name', 'step_desc', 'count', 'reward', 'medal', 'DOACTION');
        $list = model('Task')->where('task_type=2')->field('id,task_name,step_name,step_desc,reward')->order('task_type,task_level')->findPage();
        $ids = getSubByKey($list['data'], 'id');
        $users = D('task_user')->query('select tid,count(tid) as tcount from '.C('DB_PREFIX').'task_user where tid in('.implode(',', $ids).') group by tid');
        $iduser = array();
        foreach ($users as $u) {
            $iduser[$u['tid']] = $u['tcount'];
        }
        foreach ($list['data'] as &$v) {
            $v['count'] = $iduser[$v['id']] ? $iduser[$v['id']] : 0;
            $reward = json_decode($v['reward']);
            $src = $reward->medal->src;
            $v['reward'] = '经验+'.$reward->exp.' 积分+'.$reward->score;
            $src && $v['medal'] = '<img width="100px" height="100px" src="'.getImageUrl($src).'" />';
            $v['DOACTION'] = "<a href='".U('admin/Task/editTask', array('id' => $v['id'], 'type' => 2, 'tabHash' => 'mainIndex'))."' >编辑</a>";
        }
        $this->allSelected = false;
        $this->displayList($list);
    }

    public function addTask()
    {
        $this->_initListAdminMenu();
        $groups = model('UserGroup')->getAllGroup();
        $this->pageKeyList = array('task_name', 'task_desc',
                array('end_time1', 'end_time2'),
                'userlevel',
                'usergroup', array('reg_time1', 'reg_time2'), 'topic', 'task_condition', 'num', 'exp', 'score', 'attach_id', 'share_card', 'medal_name', 'medal_desc', );

        $this->opt['userlevel'] = array(1 => 'T1', 2 => 'T2', 3 => 'T3', 4 => 'T4', 5 => 'T5', 6 => 'T6', 7 => 'T7', 8 => 'T8', 9 => 'T9', 10 => 'T10');
        $this->opt['usergroup'] = $groups;
        $this->opt['task_condition'] = array(0 => '无', '1-1' => '每日任务', '2-1' => '新手任务', '2-2' => '进阶任务', '2-3' => '达人任务', '2-4' => '高手任务 ', '2-5' => '终极任务 ');

        $this->notEmpty = array('task_name');
        $this->savePostUrl = U('admin/Task/doAddTask');

        $data['userlevel'] = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $data['usergroup'] = array(1, 2, 3, 5, 6, 7);
        $this->displayConfig($data);
    }

    /**
     * @param unknown_type $data
     */
    private function validate($data)
    {
        if (trim($data['task_name']) == '') {
            $this->error('任务名称不能为空！');
        }
    }

    public function doAddTask()
    {
        $taskname = t($_POST['task_name']);
        $taskdesc = t($_POST['task_desc']);
        $num = intval($_POST['num']);
        $taskcondition = intval($_POST['task_condition']);
        $exp = abs(intval($_POST['exp']));
        $score = abs(intval($_POST['score']));
        $medal = intval($_POST['attach_id']);
        $share_card = intval($_POST['share_card']);

        $this->validate($_POST);

        if (strtotime($_POST['end_time1'][0]) > strtotime($_POST['end_time1'][1])) {
            $this->error('领取的开始时间不能大于结束时间');
        }
        if (strtotime($_POST['reg_time1'][0]) > strtotime($_POST['reg_time1'][1])) {
            $this->error('注册的开始时间不能大于结束时间');
        }

        if ($medal) {
            $data['name'] = t($_POST['medal_name']);
            $exist = model('Medal')->where($data)->find();
            if ($exist) {
                $this->error('已存在相同的勋章名称');
            } else {
                $data['desc'] = t($_POST['medal_desc']);

                //大图
                $attach = model('Attach')->getAttachById($medal);
                $src = $attach['save_path'].$attach['save_name'];
                $data['src'] = $medal.'|'.$src;

                //小图
                $small_src = getImageUrl($src, 50, 50);
                $data['small_src'] = $medal.'|'.$small_src;

                //炫耀卡片
                if ($share_card) {
                    $sharecard_src = model('Attach')->getAttachById($share_card);
                    $data['share_card'] = $share_card.'|'.$sharecard_src['save_path'].$sharecard_src['save_name'];
                }

                $data['type'] = 3; //自定义任务勋章
                $medal_id = model('Medal')->add($data);
            }
        }

        $task['task_name'] = $taskname;
        $task['task_desc'] = $taskdesc;
        $task['num'] = $num;
        $task['task_condition'] = $taskcondition;
        $task['medal_id'] = $medal_id;
        //$task['reward'] = json_encode(array('exp' => $exp, 'score' => $score, 'medal' => array('id' => $medal_id, 'name' => $data['name'], 'src' => $src)));
        $task['reward'] = addslashes(json_encode(array('exp' => $exp, 'score' => $score, 'medal' => array('id' => $medal_id, 'name' => $data['name'], 'src' => $src))));

        $condition = array();
        if ($_POST['end_time1'][0] || $_POST['end_time1'][1]) {
            $condition['endtime'] = t($_POST['end_time1'][0]).'|'.t($_POST['end_time1'][1]);
        }
        if ($_POST['reg_time1'][0] || $_POST['reg_time1'][1]) {
            $condition['regtime'] = t($_POST['reg_time1'][0]).'|'.t($_POST['reg_time1'][1]);
        }
        if ($_POST['userlevel']) {
            $condition['userlevel'] = implode(',', $_POST['userlevel']);
        }
        if ($_POST['usergroup']) {
            $condition['usergroup'] = implode(',', $_POST['usergroup']);
        }
        if ($_POST['topic']) {
            $condition['topic'] = t($_POST['topic']);
        }

        //$task['condition'] = json_encode($condition);
        $task['condition'] = addslashes(json_encode($condition));

        $res = D('task_custom')->add($task);
        if ($res) {
            $this->assign('jumpUrl', U('admin/Task/customIndex', array('tabHash' => 'customIndex')));
            $this->success('添加成功');
        } else {
            $this->error('添加失败');
        }
    }

    /**
     * 编辑自定义任务
     */
    public function editCustomTask()
    {
        $this->_initListAdminMenu();
        $groups = model('UserGroup')->getAllGroup();
        $this->pageKeyList = array('id', 'task_name', 'task_desc',
                array('end_time1', 'end_time2'),
                'userlevel',
                'usergroup', array('reg_time1', 'reg_time2'), 'topic', 'task_condition', 'num', 'exp', 'medal_id', 'medal_name', 'medal_src', 'score', );

        $this->opt['userlevel'] = array(1 => 'T1', 2 => 'T2', 3 => 'T3', 4 => 'T4', 5 => 'T5', 6 => 'T6', 7 => 'T7', 8 => 'T8', 9 => 'T9', 10 => 'T10');
        $this->opt['usergroup'] = $groups;
        $this->opt['task_condition'] = array(0 => '无', '1-1' => '每日任务', '2-1' => '新手任务', '2-2' => '进阶任务', '2-3' => '达人任务', '2-4' => '高手任务 ', '2-5' => '终极任务 ');
        $this->notEmpty = array('task_name');
        $this->savePostUrl = U('admin/Task/doAddTask');

        $task = D('task_custom')->where('id='.intval($_GET['id']))->find();

        $condition = json_decode($task['condition']);
        $reward = json_decode($task['reward']);
        $task['end_time1'] = explode('|', $condition->endtime);
        $task['reg_time1'] = explode('|', $condition->regtime);
        $task['topic'] = $condition->topic;
        $task['userlevel'] = explode(',', $condition->userlevel);
        $task['usergroup'] = explode(',', $condition->usergroup);
        $task['exp'] = $reward->exp;
        $task['score'] = $reward->score;
        $task['medal_id'] = $reward->medal->id;
        $task['medal_name'] = $reward->medal->name;
        $task['medal_src'] = $reward->medal->src;
        $this->savePostUrl = U('admin/Task/doEditCustomTask');
        $this->displayConfig($task);
    }

    public function doEditCustomTask()
    {
        $taskname = t($_POST['task_name']);
        $taskdesc = t($_POST['task_desc']);
        $num = abs(intval($_POST['num']));
        $taskcondition = t($_POST['task_condition']);
        $exp = abs(intval($_POST['exp']));
        $score = abs(intval($_POST['score']));
        $medalid = intval($_POST['medal_id']);
        $medalname = t($_POST['medal_name']);
        $medalsrc = t($_POST['medal_src']);

        $this->validate($_POST);
        if (strtotime($_POST['end_time1'][0]) > strtotime($_POST['end_time1'][1])) {
            $this->error('领取的开始时间不能大于结束时间');
        }
        if (strtotime($_POST['reg_time1'][0]) > strtotime($_POST['reg_time1'][1])) {
            $this->error('注册的开始时间不能大于结束时间');
        }
// 		$medal =  intval( $_POST['attach_id'] );
// 		if ( $medal ){
// 			$data['name'] = t ( $_POST['medal_name'] );
// 			$exist = model( 'Medal' )->where($data)->find();
// 			if ( $exist ){
// 				$this->error('已存在相同的勋章名称');
// 			} else {
// 				$data['desc'] = t ( $_POST['medal_desc'] );
// 				$attach = model('Attach')->getAttachById( $medal );
// 				$src = $attach['save_path'].$attach['save_name'];
// 				$data['src'] = $medal.'|'.$src;
// 				$medal_id = model('Medal')->add($data);
// 			}
// 		}

        $task['task_name'] = $taskname;
        $task['task_desc'] = $taskdesc;
        $task['num'] = $num;
        $task['task_condition'] = $taskcondition;
        //$task['reward'] = json_encode(array('exp' => $exp, 'score' => $score, 'medal' => array('id' => $medalid, 'name' => $medalname, 'src' => $medalsrc)));
        $task['reward'] = addslashes(json_encode(array('exp' => $exp, 'score' => $score, 'medal' => array('id' => $medalid, 'name' => $medalname, 'src' => $medalsrc))));

        $iscondition = false;
        $condition = array();
        if ($_POST['end_time1'][0] || $_POST['end_time1'][1]) {
            $condition['endtime'] = t($_POST['end_time1'][0]).'|'.t($_POST['end_time1'][1]);
            $iscondition = true;
        }
        if ($_POST['reg_time1'][0] || $_POST['reg_time1'][1]) {
            $condition['regtime'] = t($_POST['reg_time1'][0]).'|'.t($_POST['reg_time1'][1]);
            $iscondition = true;
        }
        if ($_POST['userlevel']) {
            $condition['userlevel'] = implode(',', $_POST['userlevel']);
            $iscondition = true;
        }
        if ($_POST['usergroup']) {
            $condition['usergroup'] = implode(',', $_POST['usergroup']);
            $iscondition = true;
        }
        if ($_POST['topic']) {
            $condition['topic'] = t($_POST['topic']);
            $iscondition = true;
        }

        if (!$iscondition) {
            $this->error('请选择任务条件');
        }

        //$task['condition'] = json_encode($condition);
        $task['condition'] = addslashes(json_encode($condition));

        $res = D('task_custom')->where('id='.intval($_POST['id']))->save($task);
        if ($res) {
            $this->assign('jumpUrl', U('admin/Task/customIndex', array('tabHash' => 'customIndex')));
            $this->success('编辑成功');
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 编辑任务
     */
    public function editTask()
    {
        $id = intval($_REQUEST['id']);
        $taskinfo = model('Task')->where('id='.$id)->find();
        $condition = json_decode($taskinfo['condition']);
        $conkey = key($condition);

        $this->pageKeyList = array('task_id', 'task_name', 'step_name', 'step_desc', 'task_type', 'condition', 'num', 'exp', 'score', 'act2', 'medal', 'headface', 'show');
        $this->_initListAdminMenu();

        $reward = json_decode($taskinfo['reward']);
        $taskinfo['task_id'] = $id;
        $taskinfo['act2'] = $taskinfo['action'];
        $taskinfo['condition'] = $conkey;
        $taskinfo['num'] = $condition->$conkey;
        $taskinfo['exp'] = $reward->exp;
        $taskinfo['score'] = $reward->score;
        $taskinfo['medal'] = $reward->medal->id;
        $headface = explode('|', $taskinfo['headface']);
        $taskinfo['headface'] = $headface[0];
        $taskinfo['show'] = explode(',', $taskinfo['show']);

        $medals = model('Medal')->getAllMedal();
        $medals[0] = '无';
        ksort($medals);
        $this->opt['medal'] = $medals;
        $this->opt['show'] = array('0' => '网页端', '1' => '手机端', '2' => '3G版');
        $this->notEmpty = array('task_name', 'step_name');
        $this->savePostUrl = U('admin/Task/doEditTask');
        $this->displayConfig($taskinfo);
    }

    public function doEditTask()
    {
        $exp = intval($_REQUEST['exp']);
        $score = intval($_REQUEST['score']);
        $medal = intval($_REQUEST['medal']);
        $num = abs(intval($_REQUEST['num']));

        $condition = t($_REQUEST['condition']);
        $taskname = t($_REQUEST['task_name']);
        $stepname = t($_REQUEST['step_name']);
        $stepdesc = t($_REQUEST['step_desc']);
        $action = t($_REQUEST['act2']);
        if (!$taskname) {
            $this->error('任务类型不能为空');
        }
        if (!$stepname) {
            $this->error('任务名称不能为空');
        }
        if ($_POST['headface']) {
            $smallattach = model('Attach')->getAttachById($_POST['headface']);
            $smallsrc = $smallattach['save_path'].$smallattach['save_name'];
            $data['headface'] = $_POST['headface'].'|'.$smallsrc;
        }
        if ($medal) {
            $name = model('Medal')->where('id='.$medal)->field('name,src')->find();
            $src = explode('|', $name['src']);
            $medalinfo = array('id' => $medal, 'name' => $name['name'], 'src' => $src[1]);
        }
        $reward = array('exp' => $exp, 'score' => $score, 'medal' => $medalinfo);
        $condition = array($condition => $num);

        $data['task_name'] = $taskname;
        $data['step_name'] = $stepname;
        $data['step_desc'] = $stepdesc;
        $data['condition'] = addslashes(json_encode($condition));
        $data['reward'] = addslashes(json_encode($reward));
        $data['action'] = $action;
        $data['show'] = implode(',', $_POST['show']);

        $res = model('Task')->where('id='.intval($_REQUEST['task_id']))->save($data);
        if ($res) {
            $url = $_REQUEST['task_type'] == 1 ? 'index' : 'mainIndex';
            $this->assign('jumpUrl', U('admin/Task/'.$url, array('tabHash' => $url)));
            $this->success(L('编辑成功'));
        } else {
            $this->error('编辑失败');
        }
    }

    /**
     * 任务奖励列表.
     */
    public function reward()
    {
        $this->pageKeyList = array('task_name', 'reward', 'medal', 'DOACTION');
        $list = model('Task')->group('task_name')->where('task_type=2')->field('task_name,task_level,task_type')->findPage();
        foreach ($list['data'] as &$v) {
            $taskreward = D('task_reward')->where('task_type='.$v['task_type'].' and task_level='.$v['task_level'])->getField('reward');
            $reward = json_decode($taskreward);
            $src = $reward->medal->src;
            $v['reward'] = '经验+'.intval($reward->exp).' 积分+'.intval($reward->score);
            if ($src) {
                $v['medal'] = '<img width="100px" height="100px" src="'.getImageUrl($src).'" />';
            }
            $v['DOACTION'] = '<a href="'.U('admin/Task/editReward', array('task_level' => $v['task_level'], 'task_type' => $v['task_type'])).'">编辑</a>';
        }
        $this->allSelected = false;
        $this->displayList($list);
    }

    /**
     * 编辑任务奖励.
     */
    public function editReward()
    {
        $tasklevel = intval($_GET['task_level']);
        $tasktype = intval($_GET['task_type']);

        if ($tasktype == 1) {
            $this->pageKeyList = array('task_type', 'task_level', 'exp', 'score');
        } else {
            $this->pageKeyList = array('task_type', 'task_level', 'exp', 'score', 'medal');
        }

        $info = D('task_reward')->where('task_level='.$tasklevel.' and task_type='.$tasktype)->find();
        $reward = json_decode($info['reward']);
        $data['task_type'] = $tasktype;
        $data['task_level'] = $tasklevel;
        $data['exp'] = $reward->exp;
        $data['score'] = $reward->score;
        $data['medal'] = $reward->medal->id;
        $medals = model('Medal')->getAllMedal();
        $medals[0] = '无';
        ksort($medals);
        $this->opt['medal'] = $medals;
        $this->savePostUrl = U('admin/Task/doEditReward');
        $this->displayConfig($data);
    }

    public function doEditReward()
    {
        $tasktype = $_REQUEST['task_type'];
        $tasklevel = $_REQUEST['task_level'];

        $exp = intval($_REQUEST['exp']);
        $score = intval($_REQUEST['score']);
        $medal = intval($_REQUEST['medal']);

        if ($medal) {
            $name = model('Medal')->where('id='.$medal)->field('name,src')->find();
            $src = explode('|', $name['src']);
            $medalinfo = array('id' => $medal, 'name' => $name['name'], 'src' => $src[1]);
        }
        $reward = addslashes(json_encode(array('exp' => $exp, 'score' => $score, 'medal' => $medalinfo)));
        $isexist = D('task_reward')->where('task_type='.$tasktype.' and task_level='.$tasklevel)->find();
        if ($isexist) {
            D('task_reward')->setField('reward', $reward, 'task_type='.$tasktype.' and task_level='.$tasklevel);
        } else {
            $data['task_type'] = $tasktype;
            $data['task_level'] = $tasklevel;
            $data['reward'] = $reward;
            D('task_reward')->add($data);
        }
        $this->assign('jumpUrl', U('admin/Task/reward'));
        $this->success(L('编辑成功'));
    }

    public function taskConfig()
    {
        if ($_POST) {
            $taskswitch = $_POST['task_switch'];
            model('Xdata')->saveKey('task_config:task_switch', $taskswitch);
            $this->success('保存成功');
        }
        $data['task_switch'] = model('Xdata')->get('task_config:task_switch');
        !$data['task_switch'] && $data['task_switch'] = 1;
        $this->pageKeyList = array('task_switch');
        $this->opt['task_switch'] = array(1 => '开', 2 => '关');
        $this->savePostUrl = U('admin/Task/taskConfig');
        $this->displayConfig($data);
    }
}
