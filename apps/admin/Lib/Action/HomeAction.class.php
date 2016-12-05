<?php
/**
 * 后台，系统配置控制器.
 *
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.O
 */
use Illuminate\Database\Capsule\Manager as Capsule;

tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');

class HomeAction extends AdministratorAction
{
    /**
     * 初始化，页面标题，用于双语.
     */
    public function _initialize()
    {
        $this->pageTitle['logs'] = '日志列表';
        $this->pageTitle['logsArchive'] = L('PUBLIC_SYSTEM_LOGSUM');
        $this->pageTitle['schedule'] = L('PUBLIC_SCHEDULED_TASK');
        $this->pageTitle['newschedule'] = L('PUBLIC_SCHEDULED_TASK_NEWCREATE');
        $this->pageTitle['systemdata'] = L('PUBLIC_SYSTEM_DATA_SQL');
        $this->pageTitle['message'] = L('PUBLIC_SYSTEM_MESSAGE');
        $this->pageTitle['invatecount'] = L('PUBLIC_INVITE_CALCULATION');
        $this->pageTitle['invateTop'] = L('PUBLIC_INVITE_TOP');
        $this->pageTitle['tag'] = L('PUBLIC_TAG_MANAGEMENT');
        $this->pageTitle['addTag'] = L('PUBLIC_TAG_MANAGEMENT');
        $this->pageTitle['feedbackType'] = L('PUBLIC_FEEDBACK_CLASSIFICATION');
        $this->pageTitle['cacheConfig'] = '缓存配置';
        $_GET = array_map('t', $_GET);
        $_POST = array_map('t', $_POST);
        parent::_initialize();
    }

    /**
     * 系统信息 - 基本信息.
     */
    public function statistics()
    {
        $statistics = array();

        /*
         * 重要: 为了防止与应用别名重名，“服务器信息”、“用户信息”、“开发团队”作为key前面有空格
         */

        // 服务器信息
        //$site_version = model('Xdata')->get('siteopt:site_system_version');
        $serverInfo[L('PUBLIC_CORE_VERSION')] = 'TS V'.C('VERSION');
        $serverInfo[L('PUBLIC_SERVER_PHP')] = PHP_OS.' / PHP v'.PHP_VERSION;
        $serverInfo[L('PUBLIC_SERVER_SOFT')] = $_SERVER['SERVER_SOFTWARE'];
        $serverInfo[L('PUBLIC_UPLOAD_PERMISSION')] = (@ini_get('file_uploads')) ? ini_get('upload_max_filesize') : '<font color="red">no</font>';

        // 数据库信息
        $mysqlinfo = Capsule::selectOne('SELECT VERSION() AS version');
        $serverInfo[L('PUBLIC_MYSQL')] = $mysqlinfo['version'];

        $t = D('')->query("SHOW TABLE STATUS LIKE '".C('DB_PREFIX')."%'");
        $dbsize = 0;
        foreach ($t as $k) {
            $dbsize += $k['Data_length'] + $k['Index_length'];
        }

        $umap['is_del'] = 0;
        $userInfo['totalUser'] = model('User')->where($umap)->count();                    // 用户总数
        $aumap['ctime'] = array('GT', time() - 24 * 3600 * 30);                            // 1个月内登录过的用户
        $userInfo['activeUser'] = D('login_record')->where($aumap)->count();

        $ymap['day'] = date('Y-m-d', strtotime('-1 day'));
        $d = D('online_stats')->where($ymap)->find();
        $userInfo['yesterdayUser'] = $d['most_online'];

        $onmap['uid'] = array('GT', 0);
        $onmap['activeTime'] = array('GT', time() - 1800);
        $userInfo['onlineUser'] = count(D()->table(C('DB_PREFIX').'online')->where($onmap)->findAll());
        $onmap['uid'] = 0;
        $userInfo['onlineUser'] += count(D()->table(C('DB_PREFIX').'online')->where($onmap)->findAll());        // 加上游客

        $ymap['day'] = array('GT', date('Y-m-d', strtotime('-7 day')));
        $d = D('online_stats')->where($ymap)->field('max(most_online) AS most_online')->find();
        $userInfo['weekAvg'] = $d['most_online'];

        $this->assign('userInfo', $userInfo);

        $ymap['day'] = array('GT', date('Y-m-d', strtotime('-7 day')));
        $d = D('online_stats')->where($ymap)->getHashList('day', '*');

        $visitCount = array();
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $visitCount['today'] = array('pv' => $d[$today]['total_pageviews'], 'pu' => $d[$today]['total_users'], 'guest' => $d[$today]['total_guests']);
        $visitCount['yesterday'] = array('pv' => $d[$yesterday]['total_pageviews'], 'pu' => $d[$yesterday]['total_users'], 'guest' => $d[$yesterday]['total_guests']);
        $apv = 0;
        $apu = 0;
        $agu = 0;
        foreach ($d as $v) {
            $apv += $v['total_pageviews'];
            $apu += $v['total_users'];
            $agu += $v['total_guests'];
        }

        $visitCount['weekAvg'] = array('pv' => ceil($apv / count($d)), 'pu' => ceil($apu / count($d)), 'guest' => ceil($agu / count($d)));
        $this->assign('visitCount', $visitCount);

        $serverInfo[L('PUBLIC_DATABASE_SIZE')] = byte_format($dbsize);
        $statistics[L('PUBLIC_SERVER_INFORMATION')] = $serverInfo;
        unset($serverInfo);

        // 开发团队
        $statistics[L('PUBLIC_DEV_TEAM')] = array(
            L('PUBLIC_COPYRIGHT') => '<a href="http://www.zhishisoft.com" target="_blank">'.L('PUBLIC_COMPANY').'</a>',
        );

        $this->assign('statistics', $statistics);
        $this->display();
    }

    /**
     * 系统信息 - 访问统计
     */
    public function visitorCount()
    {
        model('Online')->dostatus();        // 执行统计 TODO 以后放入计划任务中

        !$_GET['type'] && $_GET['type'] = 'week';
        switch ($_GET['type']) {
            case 'today':
                $where = "day ='".date('Y-m-d')."'";
                break;
            case 'yesterday':
                $where = "day ='".date('Y-m-d', strtotime('-1 day'))."'";
                break;
            case 'week':
                $where = " day >= '".date('Y-m-d', strtotime('-7 day'))."'";
                break;
            case '30d':
                $where = " day >= '".date('Y-m-d', strtotime('-30 day'))."'";
                break;
            case 'month':
                $where = " day >= '".date('Y-m-01')."'";
                break;
        }

        $this->assign('type', t($_GET['type']));

        if (!empty($_GET['start_day']) || !empty($_GET['end_day'])) {
            $where = '1';
            if (!empty($_GET['start_day'])) {
                $where .= " AND day > '{$_GET['start_day']}'";
            }
            if (!empty($_GET['end_day'])) {
                $where .= " AND day < '{$_GET['end_day']}'";
            }
            $this->assign('type', '');
        }

        $list = model('Online')->getStatsList($where);
        $this->assign($list);
        $this->display();
    }

    /**
     * 系统信息 - 管理知识 - 知识列表.
     */
    public function logs()
    {
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('id', 'uid', 'uname', 'app_name', 'ip', 'data', 'ctime', 'isAdmin', 'type_info', 'DOACTION');
        // 搜索key值
        $this->searchKey = array('uname', 'app_name', array('ctime', 'ctime1'), 'isAdmin', 'keyword');
        // 针对搜索的特殊选项
        $this->opt['isAdmin'] = array('0' => L('PUBLIC_USER_LOGS'), '1' => L('PUBLIC_MANAGEMENT_LOG'));
        $this->opt['app_name'] = array('0' => L('PUBLIC_ALL_STREAM'), 'admin' => L('PUBLIC_SYSTEM_BACK'));    //TODO 从目录读取 或者应用表里读取
        // Tab选项
        $this->pageTab[] = array('title' => '日志列表', 'tabHash' => 'list', 'url' => U('admin/Home/logs'));
        $this->pageTab[] = array('title' => '日志归档', 'tabHash' => 'down', 'url' => U('admin/Home/logsArchive'));
        // 指定查询的表尾
        $table = isset($_REQUEST['table']) ? t($_REQUEST['table']) : '';
        // 列表分页栏按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_INDEX'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('PUBLIC_SYSTEM_DELALL'), 'onclick' => "admin.delselectLog('{$table}')");
        // 数据的格式化 与pageKeyList保持一致
        $listData = $this->_getLogsData($table);
        $this->displayList($listData);
    }

    /**
     * 系统信息 - 管理知识 - 知识归档.
     */
    public function logsArchive()
    {
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('Name', 'Engine', 'Version', 'Rows', 'Data_length', 'Data_free', 'Create_time', 'Update_time', 'Collation', 'DOACTION');
        // Tab选项
        $this->pageTab[] = array('title' => '日志列表', 'tabHash' => 'list', 'url' => U('admin/Home/logs'));
        $this->pageTab[] = array('title' => '日志归档', 'tabHash' => 'down', 'url' => U('admin/Home/logsArchive'));
        // 列表分页栏按钮
        $this->pageButton[] = array('title' => L('PUBLIC_LOGS_REMOVE_SEX'), 'onclick' => 'admin.cleanLogs(6)');
        $this->pageButton[] = array('title' => L('PUBLIC_LOGS_REMOVE_SET'), 'onclick' => 'admin.cleanLogs(12)');
        $this->pageButton[] = array('title' => L('PUBLIC_LOGS_REMOVE_LOG'), 'onclick' => 'admin.logsArchive()');

        $data['data'] = D('')->query("SHOW TABLE STATUS LIKE '".C('DB_PREFIX')."x_logs%'");

        foreach ($data['data'] as &$v) {
            foreach ($v as $vk => $vv) {
                $vk == 'Data_length' && $v[$vk] = byte_format($vv);
            }
            $date = ltrim(str_replace(C('DB_PREFIX').'x_logs', '', $v['Name']), '_');
            $upTime = D('')->query('SELECT max( ctime ) AS Update_time FROM `'.$v['Name'].'`');
            $v['Update_time'] = !empty($upTime[0]['Update_time']) ? date('Y-m-d H:i:s', $upTime[0]['Update_time']) : $v['Create_time'];
            $v['DOACTION'] = '<a href="'.U('admin/Home/logs', array('table' => $date)).'">'.L('PUBLIC_VIEW').'</a>';
        }

        $this->allSelected = false;
        $this->displayList($data);
    }

    /**
     * 获取知识的分组情况.
     */
    public function _getLogGroup()
    {
        $app = $_POST['app_name'];
        $data = model('Logs')->getMenuList($app);
        $this->assign('list', $data['_group']);
        $this->assign('def', $_POST['def']);
        $this->display();
    }

    /**
     * 清除知识操作.
     */
    public function _cleanLogs()
    {
        // TODO:验证清理权限
        $return = array('status' => 1, 'data' => '');
        if (model('Logs')->cleanLogs($_POST['m'])) {
            $return['data'] = L('PUBLIC_SYSTEM_LOG_REMOVE');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_SYSTEM_LOG_REMOVE_IS');
        }

        LogRecord('admin_system', 'cleanlog', array('date' => $_POST['m'], 'k' => L('PUBLIC_SYSTEM_LOG_REMOVE_DEL')), true);
        exit(json_encode($return));
    }

    /**
     * 知识归档.
     */
    public function _logsArchive()
    {
        // TODO:验证权限
        $return = array('status' => 1, 'data' => '');
        if (model('Logs')->logsArchive()) {
            $return['data'] = L('PUBLIC_SYSTEM_LOGSUM_SUCCESS');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_SYSTEM_LOGSUM_SUCCESS_IS');
        }

        LogRecord('admin_system', 'logsArchive', array('msg' => $return['data'], 'k' => L('PUBLIC_SYSTEM_LOGSUM')), true);
        exit(json_encode($return));
    }

    /**
     * 删除知识操作.
     */
    public function _delLogs()
    {
        $return = array('status' => 1, 'data' => '');
        if (model('Logs')->dellogs($_REQUEST['id'], t($_REQUEST['table']))) {
            $return['data'] = '删除成功';
        } else {
            $return['status'] = 0;
            $return['data'] = '删除失败';
        }
        !is_array($_POST['id']) && $_POST['id'] = array($_POST['id']);

        LogRecord('admin_system', 'dellog', array('nums' => count($_POST['id']), 'ids' => implode(',', $_POST['id'])), true);
        exit(json_encode($return));
    }

    /**
     * 获取知识数据.
     *
     * @param string $table 知识表名
     *
     * @return array 知识数据
     */
    private function _getLogsData($table = '')
    {
        // 条件过滤
        $map = $this->getSearchPost();
        !empty($map['app_name']) && $_map['app_name'] = t($map['app_name']);
        !empty($map['uname']) && $_map['uname'] = t($map['uname']);
        !empty($map['keyword']) && $_map['keyword'] = array('LIKE', '%'.t($map['keyword']).'%');

        if (!empty($map['ctime'][0]) && !empty($map['ctime'][1])) {
            $_map['ctime'] = array('BETWEEN', array(strtotime($map['ctime'][0]), strtotime($map['ctime'][1])));
        } else {
            !empty($map['ctime'][0]) && $_map['ctime'] = array('GT', strtotime($map['ctime'][0]));
            !empty($map['ctime'][1]) && $_map['ctime'] = array('LT', strtotime($map['ctime'][1]));
        }

        if (!empty($map['group_action'])) {
            list($group, $action) = explode('-', $map['group_action']);
            $_map['group'] = $group;
            $_map['action'] = $action;
            $this->onload[] = "admin.selectLog('{$map['app_name']}','{$map['group_action']}')";
        }

        // TODO:下面的in也许会很慢，可能需要分情况
        (!empty($map['isAdmin']) && is_array($map['isAdmin'])) && $_map['isAdmin'] = array('IN', $map['isAdmin']);

        // 知识归档表的查询处理
        $this->searchPostUrl .= '&table='.$table;

        $listData = model('Logs')->get($_map, 20, $table);

        foreach ($listData['data'] as &$v) {
            foreach ($v as $vk => $vv) {
                if (!in_array($vk, $this->pageKeyList)) {
                    unset($vk);
                }
                $vk == 'app_name' && $v[$vk] = $this->opt['app_name'][$vv];
                $vk == 'ctime' && $v[$vk] = date('Y-m-d H:i:s', $vv);
                $vk == 'isAdmin' && $v[$vk] = $this->opt['isAdmin'][$vv];
            }
            $v['app_name'] .= '-'.$v['type_info'];
            $v['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.dellog(\''.$v['id'].'\',\''.$table.'\')">'.L('PUBLIC_STREAM_DELETE').'</a>';
        }

        return $listData;
    }

    /**
     * 系统工具 - 计划任务 - 计划任务列表.
     */
    public function schedule()
    {
        $this->pageKeyList = array('id', 'method', 'schedule_type', 'modifier', 'dirlist', 'month', 'start_datetime', 'end_datetime', 'last_run_time', 'info');
        $this->pageTab[] = array('title' => L('PUBLIC_SCHEDULED_TASK_LIST'), 'tabHash' => 'list', 'url' => U('admin/Home/schedule'));
        $this->pageTab[] = array('title' => L('PUBLIC_SCHEDULED_TASK_CREATE'), 'tabHash' => 'new', 'url' => U('admin/Home/newschedule'));
        $this->pageButton[] = array('title' => L('PUBLIC_SCHEDULED_TASK_DELETE'), 'onclick' => 'admin.delschedule()');

        $list = model('Schedule')->getScheduleList();
        $listdata['data'] = array();
        foreach ($list as $k => $v) {
            $list[$k]['method'] = $v['task_to_run'];
            $listdata['data'][] = $list[$k];
        }
        $this->displayList($listdata);
    }

    /**
     * 系统工具 - 计划任务 - 新建计划任务
     */
    public function newschedule()
    {
        $this->pageKeyList = array('task_to_run', 'schedule_type', 'modifier', 'dirlist', 'month', 'start_datetime', 'end_datetime', 'info');
        $this->opt['schedule_type'] = array('ONCE' => '只执行一次', 'MINUTE' => '每分钟', 'HOURLY' => '每小时', 'DAILY' => '每小时', 'WEEKLY' => '每周', 'MONTHLY' => '每月');
        // 计划任务保存地址
        $this->savePostUrl = U('admin/Home/saveschedule');
        $this->displayConfig(array());
    }

    /**
     * 保存计划任务操作.
     */
    public function saveschedule()
    {
        $res = model('Schedule')->addSchedule($_POST);
        if ($res) {
            // TODO:记录知识
            $this->assign('jumpUrl', U('admin/Home/schedule'));
            $this->success(L('PUBLIC_SAVE_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_SAVE_FAIL'));
        }
    }

    /**
     * 删除计划任务操作.
     */
    public function doDeleteSchedule()
    {
        $return = array('status' => 1, 'data' => L('PUBLIC_DELETE_SUCCESS'));
        $ids = is_array($_REQUEST['id']) ? $_REQUEST['id'] : array(intval($_REQUEST['id']));
        $res = model('Schedule')->delSchedule($ids);
        if ($res) {
            //TODO:记录知识
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_DELETE_FAIL');
        }
        exit(json_encode($return));
    }

    /**
     * 资源统计 ？？？ 未完成.
     */
    public function sourcesCount()
    {
    }

    /**
     * 数据字典.
     */
    public function systemdata()
    {
        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('name', 'key', 'value', 'DOACTION');

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_DATA_SQLLIST'), 'tabHash' => 'list', 'url' => U('admin/Home/addsystemdata'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_DATA'), 'tabHash' => 'add', 'url' => U('admin/Home/addsystemdata'));

        /*数据的格式化 与listKey保持一致 */
        $data = model('Xdata')->lget('dict');
        foreach ($data as $k => &$v) {
            $v['key'] = $k;
            $v['DOACTION'] = '<a href="'.U('admin/Home/addsystemdata', array('key' => $v['key'])).'">'.L('PUBLIC_EDIT').'</a>
				 <a href="javascript:admin.delsystemdata(\''.$v['key'].'\')" >'.L('PUBLIC_STREAM_DELETE').'</a>';
        }

        $this->allSelected = false;

        $this->displayList(array('data' => $data));
    }

    //添加编辑数据
    public function addsystemdata()
    {
        if (!empty($_GET['key'])) {
            $this->assign('pageTitle', L('PUBLIC_SYSTEM_EDIT_DATA'));
            $map['key'] = t($_GET['key']);
            $map['list'] = 'dict';
            $detail = model('Xdata')->where($map)->find();
            $d = unserialize($detail['value']);
            $detail['name'] = $d['name'];    //中文名
            $detail['value'] = $d['value'];    //内容
        } else {
            $this->assign('pageTitle', L('PUBLIC_SYSTEM_ADD_DATA'));
            $detail = array();
        }
        $this->pageKeyList = array('id', 'name', 'key', 'value');
        $this->savePostUrl = U('admin/Home/doaddsystemdata');
        $this->displayConfig($detail);
    }

    //保存数据
    public function doaddsystemdata()
    {
        if (empty($_POST['key']) || empty($_POST['name'])) {
            $this->error(L('PUBLIC_SYSTEM_KEYCN_IS'));
            exit();
        }

        //DAN TENG
        $s['value'] = serialize(array('name' => $_POST['name'], 'value' => $_POST['value']));
        $s['list'] = 'dict';
        $s['mtime'] = date('Y-m-d H:i:s');
        $s['key'] = t($_POST['key']);
        if (!empty($_POST['id'])) {
            $m['id'] = t($_POST['id']);
            $res = model('Xdata')->where($m)->save($s);
        } else {
            $res = model('Xdata')->add($s);
        }

        F('_xdata_lget_dict', null);

        if ($res == true) {
            //TODO  记录知识
            $this->assign('jumpUrl', U('admin/Home/systemdata'));
            $this->success(L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
        } else {
            $this->error(model('Xdata')->getError());
        }
    }

    //删除数据
    public function deladdsystemdata()
    {
        $return = array('status' => 1, 'data' => L('PUBLIC_DELETE_SUCCESS'));
        if (empty($_POST['key'])) {
            $return = array('status' => 0, 'data' => L('PUBLIC_ID_NOEXIST'));
            echo json_encode($return);
            exit();
        }
        $map['key'] = t($_POST['key']);
        $map['list'] = 'dict';
        if ($res = model('Xdata')->where($map)->delete()) {
            F('_xdata_lget_dict', null);
            //TODO 记录知识
        } else {
            $error = model('Xdata')->getError();
            empty($error) && $error = L('SSC_DELETE_FAIL');
            $return = array('status' => 0, 'data' => $error);
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 运营工具 - 意见反馈 - 意见反馈列表.
     */
    public function feedback()
    {
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('id', 'feedbacktype', 'feedback', 'uid', 'cTime', 'type', 'DOACTION');

        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_LIST'), 'tabHash' => 'list', 'url' => U('admin/Home/feedback'));
        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_TYPE'), 'tabHash' => 'type', 'url' => U('admin/Home/feedbackType'));

        $this->pageButton[] = array('title' => L('PUBLIC_ALREADY_PROCESSED'), 'onclick' => "location.href = '".U('admin/Home/feedback', array('type' => 'true'))."'");
        $this->pageButton[] = array('title' => L('PUBLIC_WAIT_PROCESSE'), 'onclick' => "location.href = '".U('admin/Home/feedback', array('type' => 'false'))."'");
        // 列表分页栏 按钮
        $this->allSelected = false;
        $this->assign('pageTitle', L('PUBLIC_FEEDBACK_MANAGE'));
        // 数据的格式化 与listKey保持一致
        if ($_GET['type']) {
            if ($_GET['type'] == 'true') {
                $listData = model('Feedback')->where('type = 1')->order('cTime desc')->findPage(20);
            } else {
                $listData = model('Feedback')->where('type = 0')->order('cTime desc')->findPage(20);
            }
        } else {
            $listData = model('Feedback')->order('cTime desc')->findPage(20);
        }

        foreach ($listData['data'] as &$v) {
            // TODO:附件处理
            $userInfo = model('User')->getUserInfo($v['uid']);
            $feedbacktype = model('Feedback')->getFeedBackType();
            $v['feedbacktype'] = $feedbacktype[$v['feedbacktype']];
            $v['cTime'] = friendlyDate($v['cTime']);
            $v['uid'] = $userInfo['space_link'];
            if ($v['type'] != 1) {
                $v['type'] = L('PUBLIC_WAIT_PROCESSE');
                $v['DOACTION'] = '<a href="'.U('admin/Home/feedback_list', array('id' => $v['id'])).'">'.L('PUBLIC_VIEW').'</a><a href="'.U('admin/Home/delfeedback', array('id' => $v['id'])).'" >'.L('PUBLIC_MARK_PROCESSED').'</a>';
            } else {
                $v['type'] = L('PUBLIC_ALREADY_PROCESSED');
                $v['DOACTION'] = '<a href="'.U('admin/Home/feedback_list', array('id' => $v['id'])).'">'.L('PUBLIC_VIEW').'</a>';
            }
        }

        $this->allSelected = false;
        $this->displayList($listData);
    }

    /**
     * 运营工具 - 意见反馈 - 意见反馈类型.
     */
    public function feedbackType()
    {
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('type_id', 'type_name', 'DOACTION');
        // 列表分页栏 按钮
        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_LIST'), 'tabHash' => 'list', 'url' => U('admin/Home/feedback'));
        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_TYPE'), 'tabHash' => 'type', 'url' => U('admin/Home/feedbackType'));

        $this->pageButton[] = array('title' => L('PUBLIC_FEEDBACK_ADD_TYPE'), 'onclick' => "location.href = '".U('admin/Home/addFeedbackType', array('tabHash' => 'type'))."'");

        $this->assign('pageTitle', L('PUBLIC_FEEDBACK_CATEGORY_MANAGE'));
        // 数据的格式化与listKey保持一致
        $listData = D('')->table(C('DB_PREFIX').'feedback_type')->findPage(20);

        foreach ($listData['data'] as &$v) {
            //TODO:附件处理
            $v['DOACTION'] = '<a href="'.U('admin/Home/addfeedbackType', array('type_id' => $v['type_id'], 'tabHash' => 'type')).'">'.L('PUBLIC_MODIFY').'</a><a href="'.U('admin/Home/delFeedbackType', array('type_id' => $v['type_id'])).'" >'.L('PUBLIC_STREAM_DELETE').'</a>';
        }

        $this->allSelected = false;
        $this->displayList($listData);
    }

    public function feedback_list()
    {
        if (!empty($_GET['id'])) {
            $detail = model('Feedback')->where('id='.intval($_GET['id']))->find();
            $feedbacktype = model('Feedback')->getFeedBackType();
            $detail['feedbacktype'] = $feedbacktype[$detail['feedbacktype']];
        } else {
            $detail = array();
        }
        $this->pageKeyList = array('feedbacktype', 'uid', 'feedback', 'cTme');
        $this->savePostUrl = U('admin/Home/delfeedback', array('id' => intval($_GET['id'])));
        $this->submitAlias = L('PUBLIC_MARK_PROCESSED');

        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_LIST'), 'tabHash' => 'list', 'url' => U('admin/Home/feedback'));
        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_TYPE'), 'tabHash' => 'type', 'url' => U('admin/Home/feedbackType'));

        $this->assign('pageTitle', L('PUBLIC_DETAILS_LIST'));

        $this->displayConfig($detail);
    }

    //添加反馈类型
    public function addFeedbackType()
    {
        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_LIST'), 'tabHash' => 'list', 'url' => U('admin/Home/feedback'));
        $this->pageTab[] = array('title' => L('PUBLIC_FEEDBACK_TYPE'), 'tabHash' => 'type', 'url' => U('admin/Home/feedbackType'));
        if (!empty($_GET['type_id'])) {
            $this->assign('pageTitle', L('PUBLIC_FEEDBACK_EDIT_TYPE'));
            $detail = D('')->table(C('DB_PREFIX').'feedback_type')->where('type_id='.intval($_GET['type_id']))->find();
            $this->pageKey .= '_edit';
        } else {
            $this->assign('pageTitle', L('PUBLIC_FEEDBACK_ADD_TYPE'));
            $detail = array();
        }

        $this->pageKeyList = array('type_id', 'type_name');
        $this->savePostUrl = U('admin/Home/doaddFeedbackType');
        $this->displayConfig($detail);
    }

    //添加分类
    public function doaddFeedbackType()
    {
        if (!empty($_POST['type_id'])) {
            //save $res
            $add['type_name'] = t($_POST['type_name']);
            if ($add['type_name'] == '') {
                $this->error(L('PUBLIC_ADMIN_OPRETING_ERROR'));
            } else {
                $res = D('')->table(C('DB_PREFIX').'feedback_type')->where('type_id = '.$_POST['type_id'])->save($add);
            }
        } else {
            //add $res
            $add['type_name'] = t($_POST['type_name']);
            if ($add['type_name'] == '') {
                $this->error(L('PUBLIC_ADMIN_OPRETING_ERROR'));
            } else {
                $res = D('')->table(C('DB_PREFIX').'feedback_type')->add($add);
            }
        }

        if ($res) {
            $this->assign('jumpUrl', U('admin/Home/feedbackType', array('tabHash' => 'type')));
            $this->success(L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_DATA_UPGRADE_FAIL'));
        }
    }

    //删除公告
    public function delFeedbackType()
    {
        $map['type_id'] = intval($_GET['type_id']);
        $res = D('')->table(C('DB_PREFIX').'feedback_type')->where($map)->delete();
        if ($res) {
            $this->assign('jumpUrl', U('admin/Home/feedbackType', array('tabHash' => 'type')));
            $this->success(L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
        } else {
            $this->error(model()->getError());
        }
    }

    public function delFeedback()
    {
        $map['id'] = $_GET['id'];
        $add['type'] = 1;
        $add['mTime'] = time();

        $res = model('Feedback')->where($map)->save($add);

        if ($res) {
            $this->assign('jumpUrl', U('admin/Home/feedback'));
            $this->success(L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
        } else {
            $this->error(model()->getError());
        }
    }

    public function message()
    {
        //$this->pageKeyList = array('user_group_id','type','content');
        $this->pageKeyList = array('user_group_id', 'content');    //现在后台只支持发送系统消息
        $this->opt['type'] = array('0' => L('PUBLIC_MAIL_INLOCALHOST'), '1' => 'Email');
        $groupHash = model('UserGroup')->getHashUsergroup();
        $this->opt['user_group_id'] = array_merge(array(0 => L('PUBLIC_ALL_USERS')), $groupHash);
        $this->savePostUrl = U('admin/Home/dosendmsg');
        $this->notEmpty = array('content');
        // $this->onsubmit = 'admin.checkMessage(this)';
        $this->displayConfig();
    }

    /**
     * 全站发送系统消息 + 邮件.
     */
    public function dosendmsg()
    {
        // 格式化数据
        $checkContent = str_replace('&nbsp;', '', $_POST['content']);
        $checkContent = str_replace('<br />', '', $checkContent);
        $checkContent = str_replace('<p>', '', $checkContent);
        $checkContent = str_replace('</p>', '', $checkContent);
        $checkContents = preg_replace('/<img(.*?)src=/i', 'img', $checkContent);
        $checkContents = preg_replace('/<embed(.*?)src=/i', 'img', $checkContents);
        if (strlen(t($checkContents)) == 0) {
            $this->error('系统信息内容不能为空');
        }
        $this->assign('jumpUrl', U('admin/Home/message'));
        if (model('Notify')->sendSystemMessage($_POST['user_group_id'], h($_POST['content']))) {
            $this->success();
        }
        $this->error();
    }

    /**
     * 邀请列表展示.
     */
    public function invatecount()
    {
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('invite_record_id', 'receiver_uid', 'inviter_uid', 'is_audit', 'is_active', 'is_init', 'ctime', 'recived_email');
        // 搜索key值
        $this->searchKey = array('inviter_uid', 'receiver_uid');
        // tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_INVITE_LIST'), 'tabHash' => 'list', 'url' => U('admin/Home/invatecount'));
        $this->pageTab[] = array('title' => L('PUBLIC_INVITE_TOP'), 'tabHash' => 'top', 'url' => U('admin/Home/invateTop'));
        // 列表分页栏 按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_INDEX'), 'onclick' => "admin.fold('search_form')");
        // 数据的格式化 与listKey保持一致
        $map = array();
        !empty($_POST['inviter_uid']) && $map['inviter_uid'] = intval($_POST['inviter_uid']);
        !empty($_POST['receiver_uid']) && $map['receiver_uid'] = intval($_POST['receiver_uid']);
        $listData = model('Invite')->getPage($map, 20);
        foreach ($listData['data'] as &$v) {
            $v['invite_record_id'] = $v['invite_code_id'];
            $inviterInfo = model('User')->getUserInfo($v['inviter_uid']);
            $receiverInfo = model('User')->getUserInfo($v['receiver_uid']);
            $v['inviter_uid'] = $inviterInfo['space_link'];
            $v['receiver_uid'] = ($receiverInfo['is_audit'] == 0 || $receiverInfo['is_active'] == 0 || $receiverInfo['is_init'] == 0) ? $receiverInfo['uname'] : $receiverInfo['space_link'];
            $v['is_audit'] = $receiverInfo['is_audit'] == 1 ? '已审核' : '未审核';
            $v['is_active'] = $receiverInfo['is_active'] == 1 ? '已激活' : '未激活';
            $v['is_init'] = $receiverInfo['is_init'] == 1 ? '已初始化' : '未初始化';
            $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $v['register_time'] = date('Y-m-d H:i:s', $v['register_time']);
            $v['recived_email'] = $v['receiver_email'];
        }

        $this->allSelected = false;
        $this->displayList($listData);
    }

    /**
     * 邀请排行榜展示.
     */
    public function invateTop()
    {
        $this->pageKeyList = array('sort', 'inviter_uid', 'nums', 'DOACTION');
        // 搜索key值
        $this->searchKey = array('inviter_uid');
        $_REQUEST['tabHash'] = 'top';
        $this->pageTab[] = array('title' => L('PUBLIC_INVITE_LIST'), 'tabHash' => 'list', 'url' => U('admin/Home/invatecount'));
        $this->pageTab[] = array('title' => L('PUBLIC_INVITE_TOP'), 'tabHash' => 'top', 'url' => U('admin/Home/invateTop'));
        //列表分页栏 按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_INDEX'), 'onclick' => "admin.fold('search_form')");
        $_POST = $this->getSearchPost();
        $uids = empty($_POST['inviter_uid']) ? '' : explode(',', $_POST['inviter_uid']);
        $where = !empty($uids) ? " inviter_uid in ('".implode("','", $uids)."')" : '';
        $listData = model('Invite')->getTopPage($where, 20);
        $s = intval($_REQUEST['p']) * 20 + 1;
        foreach ($listData['data'] as &$v) {
            $inviterInfo = model('User')->getUserInfo($v['inviter_uid']);
            $v['sort'] = $s;
            $v['DOACTION'] = '<a href="'.U('admin/Home/invateDetail', array('inviter_uid' => $v['inviter_uid'])).'">'.L('PUBLIC_VIEW_DETAIL').'</a>';
            $v['inviter_uid'] = $inviterInfo['space_link'];
            $s++;
        }

        $this->allSelected = false;
        $this->displayList($listData);
    }

    /**
     * 邀请查看详情展示.
     */
    public function invateDetail()
    {
        // 判断参数是否正确
        if (empty($_GET['inviter_uid'])) {
            exit($this->error(L('PUBLIC_WRONG_USER_INFO')));
        }
        $_GET['inviter_uid'] = intval($_GET['inviter_uid']);
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('invite_record_id', 'receiver_uid', 'is_audit', 'is_active', 'is_init', 'ctime', 'recived_email');
        $this->pageButton[] = array('title' => L('PUBLIC_BACK'), 'onclick' => "window.location.href='".U('admin/Home/invateTop')."'");
        $map['inviter_uid'] = intval($_GET['inviter_uid']);
        // 获取相关数据
        $listData = model('Invite')->getPage($map, 20);
        foreach ($listData['data'] as &$v) {
            $v['invite_record_id'] = $v['invite_code_id'];
            $inviterInfo = model('User')->getUserInfo($v['receiver_uid']);
            $v['receiver_uid'] = ($inviterInfo['is_audit'] == 0 || $inviterInfo['is_active'] == 0 || $inviterInfo['is_init'] == 0) ? $inviterInfo['uname'] : $inviterInfo['space_link'];
            $v['is_audit'] = $inviterInfo['is_audit'] == 1 ? '已审核' : '未审核';
            $v['is_active'] = $inviterInfo['is_active'] == 1 ? '已激活' : '未激活';
            $v['is_init'] = $inviterInfo['is_init'] == 1 ? '已初始化' : '未初始化';
            $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $v['register_time'] = date('Y-m-d H:i:s', $v['register_time']);
            $v['recived_email'] = $v['receiver_email'];
        }
        $inviterInfo_uid = model('User')->getUserInfo($_GET['inviter_uid']);
        $this->assign('pageTitle', $inviterInfo_uid['uname']."的邀请详情列表 (共{$listData['count']}个)");

        $this->allSelected = false;
        $this->displayList($listData);
    }

    public function tag()
    {
        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('tag_id', 'table', 'name', 'DOACTION');
        //搜索key值
        $this->searchKey = array('name', 'table');
        $this->opt['table'] = model('Tag')->getTableHash();

        //列表分页栏 按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_INDEX'), 'onclick' => "admin.fold('search_form')");

        /*数据的格式化 与listKey保持一致 */
        $map = array();
        !empty($_POST['name']) && $map['b.name'] = array('like', '%'.t($_POST['name']).'%');
        !empty($_POST['table']) && $map['_string'] = "`table` = '".t($_POST['table'])."'";

        $listData = model('Tag')->getAppTagList($map);

        foreach ($listData['data'] as &$v) {
            $v['DOACTION'] = '<a href="javascript:;" onclick="admin.delTag(this,'.$v['tag_id'].',\''.$v['table'].'\','.$v['row_id'].')">'.L('PUBLIC_STREAM_DELETE').'</a>';
        }

        $this->allSelected = false;
        $this->displayList($listData);
    }

    public function deltag()
    {
        $map['tag_id'] = intval($_REQUEST['tag_id']);
        $map['_string'] = "`table` = '".t($_REQUEST['table'])."'";
        $map['row_id'] = intval($_REQUEST['row_id']);
        $return = array('status' => 0, 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));
        if ($map['tag_id'] > 0 && D('')->table(C('DB_PREFIX').'app_tag')->where($map)->delete()) {
            $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
        }
        echo json_encode($return);
    }

    public function addNav()
    {
        $appname = t($_REQUEST['appname']);
        $url = t($_REQUEST['url']);
        if (is_array($this->navList)) {
            $this->navList[$appname] = $url;
        } else {
            $this->navList = array($appname => $url);
        }
        model('Xdata')->put('admin_nav:top', $this->navList);
    }

    public function removeNav()
    {
        $appname = t($_REQUEST['appname']);
        if (is_array($this->navList)) {
            unset($this->navList[$appname]);
        } else {
            $this->navList = array();
        }
        model('Xdata')->put('admin_nav:top', $this->navList);
    }

    //缓存配置
    public function cacheConfig()
    {
        if ($_POST) {
            $cachetype = t($_POST['cachetype']);

            //已测试通过
            if ($cachetype == 'Memcache' && !extension_loaded('memcache')) {
                $this->error('无法启用该服务，服务器没有安装Memcache扩展。');
            }

            //已测试通过
            if ($cachetype == 'APC' && !function_exists('apc_cache_info')) {
                $this->error('无法启用该服务，服务器没有安装APC扩展。');
            }

            //已测试通过
            if ($cachetype == 'Xcache' && !function_exists('xcache_info')) {
                $this->error('无法启用该服务，服务器没有安装Xcache扩展。');
            }

            //没环境测试
            if ($cachetype == 'Redis' && !extension_loaded('Redis')) {
                $this->error('无法启用该服务，服务器没有安装Redis扩展。');
            }

            //没环境测试
            if ($cachetype == 'WinCache' && !function_exists('wincache_ucache_info')) {
                $this->error('无法启用该服务，服务器没有安装WinCache扩展。');
            }

            //貌似不靠谱还没搞定
            if ($cachetype == 'Eaccelerator' && !function_exists('eaccelerator_get')) {
                $this->error('无法启用该服务，服务器没有安装eAccelerator扩展。');
            }

            $cachesetting = t($_POST['cachesetting']);

            model('Xdata')->saveKey('cacheconfig:cachetype', $cachetype);
            model('Xdata')->saveKey('cacheconfig:cachesetting', $cachesetting);
            $this->success('保存成功');
        }

        $this->pageKeyList = array('cachetype', 'cachesetting', 'status');
        $this->opt['cachetype'] = array(
                'File' => '文件缓存',
                //'Db'=>'数据库缓存',
                'Xcache'   => 'Xcache',
                'APC'      => 'APC',
                'Memcache' => 'Memcache',
                //'Redis'=>'Redis',
                //'WinCache'=>'WinCache',
                //'Eaccelerator'=>'Eaccelerator',
                );

        model('Cache')->set('testCacheStatus', '123456789');
        $status = model('Cache')->get('testCacheStatus');
        model('Cache')->rm('testCacheStatus');
        $this->opt['status'] = $status == '123456789' ? array('正常') : array('不正常');

        $data['cachetype'] = model('Xdata')->get('cacheconfig:cachetype');
        !$data['cachetype'] && $data['cachetype'] = 'file';

        $data['cachesetting'] = model('Xdata')->get('cacheconfig:cachesetting');

        $this->savePostUrl = U('admin/Home/cacheConfig');
        $this->displayConfig($data);
    }
}
