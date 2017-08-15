<?php
/**
 * 后台，用户管理控制器.
 *
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.0
 */
// 加载后台控制器
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class UserAction extends AdministratorAction
{
    public $pageTitle = array();

    /**
     * 初始化，初始化页面表头信息，用于双语.
     */
    public function _initialize()
    {
        $this->pageTitle['index'] = L('PUBLIC_USER_MANAGEMENT');
        $this->pageTitle['pending'] = L('PUBLIC_PENDING_LIST');
        $this->pageTitle['profile'] = L('PUBLIC_PROFILE_SETTING');
        $this->pageTitle['profileCategory'] = L('PUBLIC_PROFILE_SETTING');
        $this->pageTitle['dellist'] = L('PUBLIC_DISABLE_LIST');
        $this->pageTitle['disableSendList'] = '禁言用户';
        $this->pageTitle['online'] = '在线用户列表';
        $this->pageTitle['addUser'] = L('PUBLIC_ADD_USER_INFO');
        $this->pageTitle['editUser'] = L('PUBLIC_EDIT_USER');
        $this->pageTitle['addProfileField'] = L('PUBLIC_ADD_FIELD');
        $this->pageTitle['editProfileField'] = L('PUBLIC_EDIT_FIELD');
        $this->pageTitle['addProfileCategory'] = L('PUBLIC_ADD_FIELD_CLASSIFICATION');
        $this->pageTitle['editProfileCategory'] = L('PUBLIC_EDITCATEOGRY');
        $this->pageTitle['verify'] = '待认证用户';
        $this->pageTitle['verifyGroup'] = '待认证机构';
        $this->pageTitle['verified'] = '已认证用户';
        $this->pageTitle['verifiedGroup'] = '已认证机构';
        $this->pageTitle['addVerify'] = '添加认证';
        $this->pageTitle['category'] = '推荐标签';
        $this->pageTitle['verifyCategory'] = '认证分类';
        $this->pageTitle['verifyConfig'] = '认证配置';
        $this->pageTitle['official'] = '官方用户配置';
        $this->pageTitle['officialCategory'] = '官方用户分类';
        $this->pageTitle['officialList'] = '官方用户列表';
        $this->pageTitle['officialAddUser'] = '添加官方用户';
        $this->pageTitle['findPeopleConfig'] = '全局配置';

        parent::_initialize();
    }

    /**
     * 用户管理 - 用户列表.
     */
    public function index()
    {
        $_REQUEST['tabHash'] = 'index';
        // 初始化用户列表管理菜单
        $this->_initUserListAdminMenu('index');
        // 数据的格式化与listKey保持一致
        $listData = $this->_getUserList('20', array(), 'index');
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_USER'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('PUBLIC_TRANSFER_USER_GROUP'), 'onclick' => 'admin.changeUserGroup()');
        $this->pageButton[] = array('title' => '禁用用户', 'onclick' => 'admin.delUser()');
        // 转移用户部门，如果需要请将下面的注释打开
        // $this->pageButton[] = array('title'=>L('PUBLIC_TRANSFER_DEPARTMENT'),'onclick'=>"admin.changeUserDepartment()");
        $this->displayList($listData);
    }

    /**
     * 用户管理 - �
     * 审列表.
     */
    public function pending()
    {
        $_REQUEST['tabHash'] = 'pending';
        // 初始化审核列表管理菜单
        $this->_initUserListAdminMenu('pending');
        // 数据的格式化与listKey保持一致
        $listData = $this->_getUserList(20, array('is_audit' => 0, 'is_del' => '0'), 'pending');
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_USER'), 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => L('PUBLIC_AUDIT_USER_SUCCESS'), 'onclick' => "admin.auditUser('',1)");

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 禁用列表.
     */
    public function dellist()
    {
        $this->allSelected = false;

        $_REQUEST['tabHash'] = 'dellist';
        // 初始化禁用列表管理菜单
        $this->_initUserListAdminMenu('dellist');
        // 数据的格式化与listKey保持一致
        $listData = $this->_getDisableUserList(20, 'dellist');
        // 列表批量操作按钮
        // $this->pageButton[] = array('title'=>L('PUBLIC_SEARCH_USER'),'onclick'=>"admin.fold('search_form')");
        // $this->pageButton[] = array('title'=>L('PUBLIC_RECOVER_ACCOUNT'),'onclick'=>"admin.rebackUser()");

        $this->displayList($listData);
    }

    public function disableSendList()
    {
        $this->allSelected = false;

        $_REQUEST['tabHash'] = 'disableSendList';

        $this->_initUserListAdminMenu('disableSendList');

        $listData = $this->_getDisableUserList(20, 'disableSendList');

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 在线用户列表.
     */
    public function online()
    {
        $_REQUEST['tabHash'] = 'online';
        // tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_USER_LIST'), 'tabHash' => 'index', 'url' => U('admin/User/index'));
        $this->pageTab[] = array('title' => L('PUBLIC_PENDING_LIST'), 'tabHash' => 'pending', 'url' => U('admin/User/pending'));
        $this->pageTab[] = array('title' => L('PUBLIC_DISABLE_LIST'), 'tabHash' => 'dellist', 'url' => U('admin/User/dellist'));
        // $this->pageTab[] = array('title'=>'在线用户列表','tabHash'=>'online','url'=>U('admin/User/online'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_USER_INFO'), 'tabHash' => 'addUser', 'url' => U('admin/User/addUser'));
        // 搜索选项的key值
        $this->searchKey = array('uid', 'uname', 'email', 'sex', 'user_group', array('ctime', 'ctime1'));
        // 针对搜索的特殊选项
        $this->opt['sex'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        $this->opt['identity'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_PERSONAL'), '2' => L('PUBLIC_ORGANIZATION'));
        $this->opt['user_group'] = array_merge(array('0' => L('PUBLIC_SYSTEMD_NOACCEPT')), model('UserGroup')->getHashUsergroup());
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_USER'), 'onclick' => "admin.fold('search_form')");

        $this->opt['user_group'] = array_merge(array('0' => L('PUBLIC_SYSTEMD_NOACCEPT')), model('UserGroup')->getHashUsergroup());

        $this->pageKeyList = array('uid', 'uname', 'user_group', 'location', 'ctime', 'last_operating_ip');

        $listData = $this->_getUserOnlineList(20, $map);

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 查看IP列表.
     */
    public function viewIP()
    {
        $_REQUEST['tabHash'] = 'viewIP';
        $uid = intval($_REQUEST['uid']);
        $userInfo = model('User')->getUserInfo($uid);
        $this->pageTitle['viewIP'] = '查看IP - 用户：'.$userInfo['uname'].'（'.$userInfo['email'].'）';
        // tab选项
        $this->pageTab[] = array('title' => '查看IP', 'tabHash' => 'viewIP', 'url' => U('admin/User/viewIP', array('tabHash' => 'viewIP', 'uid' => $uid)));
        $this->pageTab[] = array('title' => '登录知识', 'tabHash' => 'loginLog', 'url' => U('admin/User/loginLog', array('tabHash' => 'loginLog', 'uid' => $uid)));
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('id', 'day', 'action', 'ip', 'DOACTION');
        // 获取相关数据
        $listData = model('Online')->getUserOperatingList($uid);
        // foreach($listData['data'] as $k => $v) {
        // $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.disableIP(\''.$v['ip'].'\')">禁用IP</a>';
        // }

        $this->displayList($listData);
    }

    /**
     * 用户管理 - 登录知识.
     */
    public function loginLog()
    {
        $_REQUEST['tabHash'] = 'loginLog';
        $uid = intval($_REQUEST['uid']);
        $userInfo = model('User')->getUserInfo($uid);
        $this->pageTitle['loginLog'] = '登录知识 - 用户：'.$userInfo['uname'].'（'.$userInfo['email'].'）';
        // tab选项
        $this->pageTab[] = array('title' => '查看IP', 'tabHash' => 'viewIP', 'url' => U('admin/User/viewIP', array('tabHash' => 'viewIP', 'uid' => $uid)));
        $this->pageTab[] = array('title' => '登录知识', 'tabHash' => 'loginLog', 'url' => U('admin/User/loginLog', array('tabHash' => 'loginLog', 'uid' => $uid)));
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('login_logs_id', 'ip', 'ctime', 'DOACTION');
        // 获取相关数据
        $map['uid'] = $uid;
        $listData = D('login_logs')->where($map)->findPage(20);
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            // $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.disableIP(\''.$v['ip'].'\')">禁用IP</a>';
        }

        $this->displayList($listData);
    }

    /**
     * 获取在线用户列表数据.
     */
    private function _getUserOnlineList($limit, $map)
    {
        // 设置列表主键
        $this->_listpk = 'uid';
        // 取用户列表
        $listData = model('User')->getUserList($limit, $map);
        $uids = getSubByKey($listData['data'], 'uid');
        $ipData = D('Online')->getLastOnlineInfo($uids);
        $ipKey = array_keys($ipData);
        // 数据格式化
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['uname'] = '<a href="'.U('admin/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])).'">'.$v['uname'].'</a> ('.$v['email'].')';
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            // 用户组数据
            if (!empty($v['user_group'])) {
                $group = array();
                foreach ($v['user_group'] as $gid) {
                    $group[] = $this->opt['user_group'][$gid];
                }
                $listData['data'][$k]['user_group'] = implode('<br/>', $group);
            } else {
                $listData['data'][$k]['user_group'] = '';
            }
            $this->opt['user_group'][$v['user_group_id']];
            // 最后操作IP
            $listData['data'][$k]['last_operating_ip'] = empty($ipData) ? $v['reg_ip'] : (in_array($v['uid'], $ipKey) ? $ipData[$v['uid']] : $v['reg_ip']);
        }

        return $listData;
    }

    /**
     * 初始化用户列表管理菜单.
     *
     * @param string $type 列表类型，index、pending、dellist
     */
    private function _initUserListAdminMenu($type = null)
    {
        // tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_USER_LIST'), 'tabHash' => 'index', 'url' => U('admin/User/index'));
        $this->pageTab[] = array('title' => L('PUBLIC_PENDING_LIST'), 'tabHash' => 'pending', 'url' => U('admin/User/pending'));
        $this->pageTab[] = array('title' => L('PUBLIC_DISABLE_LIST'), 'tabHash' => 'dellist', 'url' => U('admin/User/dellist'));
        $this->pageTab[] = array('title' => '禁言用户', 'tabHash' => 'disableSendList', 'url' => U('admin/User/disableSendList'));
        // $this->pageTab[] = array('title'=>'在线用户列表','tabHash'=>'online','url'=>U('admin/User/online'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_USER_INFO'), 'tabHash' => 'addUser', 'url' => U('admin/User/addUser'));
        // 搜索选项的key值
        // $this->searchKey = array('uid','uname','email','sex','department','user_group',array('ctime','ctime1'));
        $this->searchKey = array('uid', 'uname', 'email', 'mobile', 'sex', 'user_group', 'user_category', array('ctime', 'ctime1'));
        // 针对搜索的特殊选项
        $this->opt['sex'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        $this->opt['identity'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => L('PUBLIC_PERSONAL'), '2' => L('PUBLIC_ORGANIZATION'));
        //$this->opt['user_group'] = array_merge(array('0'=>L('PUBLIC_SYSTEMD_NOACCEPT')),model('UserGroup')->getHashUsergroup());
        $this->opt['user_group'] = model('UserGroup')->getHashUsergroup();
        $this->opt['user_group'][0] = L('PUBLIC_SYSTEMD_NOACCEPT');
        $map['pid'] = array('NEQ', 0);
        $categoryList = model('UserCategory')->getAllHash($map);
        $categoryList[0] = L('PUBLIC_SYSTEMD_NOACCEPT');
        ksort($categoryList);
        $this->opt['user_category'] = $categoryList;
        //$this->opt['department_id'] = model('Department')->getHashDepartment();

        // 列表key值 DOACTION表示操作
        switch (strtolower($type)) {
            case 'index':
                $this->pageKeyList = array('uid', 'uname', 'phone', 'user_group', 'location', 'is_audit', 'is_active', 'is_init', 'ctime', 'reg_ip', 'DOACTION');
                break;
            case 'dellist':
            case 'disablesendlist':
                $this->pageKeyList = array('uid', 'uname', 'phone', 'user_group', 'location', 'is_audit', 'is_active', 'is_init', 'ctime', 'reg_ip', 'disable_time', 'DOACTION');
                break;
            case 'pending':
                $this->pageKeyList = array('uid', 'uname', 'location', 'ctime', 'reg_ip', 'DOACTION');
                break;
        }

        /*		if(!empty($_POST['_parent_dept_id'])) {
                    $this->onload[] = "admin.departDefault('".implode(',', $_POST['_parent_dept_id'])."','form_user_department')";
                }*/
    }

    /**
     * 解析用户列表数据.
     *
     * @param int    $limit 结果集数目，默认为20
     * @param array  $map   查询条件
     * @param string $type  格式化数据类型，index、pending、dellist
     *
     * @return array 解析后的用户列表数据
     */
    private function _getUserList($limit = 20, array $map = array(), $type = 'index')
    {
        // 设置列表主键
        $this->_listpk = 'uid';
        // 取用户列表
        $listData = model('User')->getUserList($limit, $map);
        //dump($listData);exit;
        // 数据格式化
        foreach ($listData['data'] as $k => $v) {
            // 获取用户身份信息
            $userTag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($v['uid']);
            $userTagString = '';
            $userTagArray = array();
            if (!empty($userTag)) {
                $userTagString .= '<br>';
                foreach ($userTag as $value) {
                    $userTagArray[] = '<span>'.$value.'</span>';
                }
                $userTagString .= implode('&nbsp;', $userTagArray);
            }
            //获取用户组信息
            $userGroupInfo = model('UserGroupLink')->getUserGroupData($v['uid']);
            foreach ($userGroupInfo[$v['uid']] as $val) {
                $userGroupIcon[$v['uid']] .= '<img style="width:auto;height:auto;display:inline;cursor:pointer;vertical-align:-2px;" src="'.$val['user_group_icon_url'].'" title="'.$val['user_group_name'].'" />&nbsp';
            }
            $listData['data'][$k]['uname'] = '<a style="color:#3589F1" href="'.U('admin/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])).'">'.$v['uname'].'</a>'.$userGroupIcon[$v['uid']].' <br/>'.$v['email'].' '.$userTagString;
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            // 屏蔽部门信息，若要开启将下面的注释打开
            /*			$department = model('Department')->getUserDepart($v['uid']);
                        $listData['data'][$k]['department'] = str_replace('|', ' - ',trim($department[$v['uid']],'|'));*/
            $listData['data'][$k]['identity'] = ($v['identity'] == 1) ? L('PUBLIC_PERSONAL') : L('PUBLIC_ORGANIZATION');
            switch (strtolower($type)) {
                case 'index':
                    // 列表数据
                    $listData['data'][$k]['is_active'] = ($v['is_active'] == 1) ? '<span style="color:#2AB284;cursor:auto;">'.L('SSC_ALREADY_ACTIVATED').'</span>' : '<a href="javascript:void(0)" onclick="admin.activeUser(\''.$v['uid'].'\',1)" style="color:red">'.L('PUBLIC_NOT_ACTIVATED').'</a>';
                    $listData['data'][$k]['is_audit'] = ($v['is_audit'] == 1) ? '<span style="color:#2AB284;cursor:auto;">'.L('PUBLIC_AUDIT_USER_SUCCESS').'</span>' : '<a href="javascript:void(0)" onclick="admin.auditUser(\''.$v['uid'].'\',1)" style="color:red">'.L('PUBLIC_AUDIT_USER_ERROR').'</a>';
                    $listData['data'][$k]['is_init'] = ($v['is_init'] == 1) ? '<span style="cursor:auto;">'.L('PUBLIC_SYSTEMD_TRUE').'</span>' : '<span style="cursor:auto;">'.L('PUBLIC_SYSTEMD_FALSE').'</span>';
                    // 用户组数据
                    if (!empty($v['user_group'])) {
                        $group = array();
                        foreach ($v['user_group'] as $gid) {
                            $group[] = $this->opt['user_group'][$gid];
                        }
                        $listData['data'][$k]['user_group'] = implode('<br/>', $group);
                    } else {
                        $listData['data'][$k]['user_group'] = '';
                    }
                    $this->opt['user_group'][$v['user_group_id']];
                    // 操作数据
                    $listData['data'][$k]['DOACTION'] = '<a href="'.U('admin/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])).'">'.L('PUBLIC_EDIT').'</a> - ';
                    // $listData['data'][$k]['DOACTION'] .= $v['is_del'] == 1 ? '<a href="javascript:void(0)" onclick="admin.rebackUser(\''.$v['uid'].'\')">'.L('PUBLIC_RECOVER').'</a> - ' : '<a href="javascript:void(0)" onclick="admin.delUser(\''.$v['uid'].'\')">'.L('PUBLIC_SYSTEM_NOUSE').'</a> - ';
                    $listData['data'][$k]['DOACTION'] .= '<a href="javascript:;" onclick="admin.disableUser(\''.$v['uid'].'\')">禁用</a>&nbsp;-&nbsp;';
                    $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.trueDelUser(\''.$v['uid'].'\')">'.L('PUBLIC_REMOVE_COMPLETELY').'</a>';
                    // $listData['data'][$k]['DOACTION'] .= '<a href="'.U('admin/User/viewIP',array('tabHash'=>'viewIP','uid'=>$v['uid'])).'">查看IP</a>';
                    break;
                case 'pending':
                    // 操作数据
                    $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.auditUser(\''.$v['uid'].'\', 1)">'.L('PUBLIC_AUDIT_USER_SUCCESS').'</a>';
                    break;
            }
        }

        return $listData;
    }

    private function _getDisableUserList($limit = 20, $type = 'dellist')
    {
        $this->_listpk = 'uid';
        $type = ($type === 'dellist') ? 'login' : ($type === 'disableSendList' ? 'post' : 'login');
        $listData = model('DisableUser')->getDisableList($type, $limit);
        // 数据格式化
        foreach ($listData['data'] as $k => $v) {
            // 获取用户身份信息
            $userTag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($v['uid']);
            $userTagString = '';
            $userTagArray = array();
            if (!empty($userTag)) {
                $userTagString .= '<p>';
                foreach ($userTag as $value) {
                    $userTagArray[] = '<span style="color:#2AB284;cursor:auto;">'.$value.'</span>';
                }
                $userTagString .= implode('&nbsp;', $userTagArray).'</p>';
            }
            //获取用户组信息
            $userGroupInfo = model('UserGroupLink')->getUserGroupData($v['uid']);
            foreach ($userGroupInfo[$v['uid']] as $val) {
                $userGroupIcon[$v['uid']] .= '<img style="width:auto;height:auto;display:inline;cursor:pointer;vertical-align:-2px;" src="'.$val['user_group_icon_url'].'" title="'.$val['user_group_name'].'" />&nbsp';
            }
            $listData['data'][$k]['uname'] = '<a href="'.U('admin/User/editUser', array('tabHash' => 'editUser', 'uid' => $v['uid'])).'">'.$v['uname'].'</a>'.$userGroupIcon[$v['uid']].' <br/>'.$v['email'].' '.$userTagString;
            $listData['data'][$k]['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $listData['data'][$k]['identity'] = ($v['identity'] == 1) ? L('PUBLIC_PERSONAL') : L('PUBLIC_ORGANIZATION');
            $listData['data'][$k]['is_active'] = ($v['is_active'] == 1) ? '<span style="color:#2AB284;cursor:auto;">'.L('SSC_ALREADY_ACTIVATED').'</span>' : '<a href="javascript:void(0)" onclick="admin.activeUser(\''.$v['uid'].'\',1)" style="color:red">'.L('PUBLIC_NOT_ACTIVATED').'</a>';
            $listData['data'][$k]['is_audit'] = ($v['is_audit'] == 1) ? '<span style="color:#2AB284;cursor:auto;">'.L('PUBLIC_AUDIT_USER_SUCCESS').'</span>' : '<a href="javascript:void(0)" onclick="admin.auditUser(\''.$v['uid'].'\',1)" style="color:red">'.L('PUBLIC_AUDIT_USER_ERROR').'</a>';
            $listData['data'][$k]['is_init'] = ($v['is_init'] == 1) ? '<span style="cursor:auto;">'.L('PUBLIC_SYSTEMD_TRUE').'</span>' : '<span style="cursor:auto;">'.L('PUBLIC_SYSTEMD_FALSE').'</span>';
            // 用户组数据
            $userGroupLink = model('UserGroupLink')->where("uid='".$v['uid']."'")->getAsFieldArray('user_group_id');
            if (!empty($userGroupLink)) {
                $group = array();
                $userGroup = model('UserGroup')->getHashUsergroup();
                foreach ($userGroupLink as $gid) {
                    $group[] = $userGroup[$gid];
                }
                $listData['data'][$k]['user_group'] = implode('<br/>', $group);
            } else {
                $listData['data'][$k]['user_group'] = '';
            }
            // $this->opt['user_group'][$v['user_group_id']];
            $listData['data'][$k]['disable_time'] = date('Y-m-d H:i:s', $v['start_time']).'&nbsp;-&nbsp;'.date('Y-m-d H:i:s', $v['end_time']);
            // 操作数据
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:;" onclick="admin.disableUser(\''.$v['uid'].'\', \''.$type.'\')">'.L('PUBLIC_EDIT').'</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:;" onclick="admin.enableUser(\''.$v['user_disable_id'].'\')">恢复</a>&nbsp;-&nbsp;';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.trueDelUser(\''.$v['uid'].'\')">'.L('PUBLIC_REMOVE_COMPLETELY').'</a>';
        }

        return $listData;
    }

    public function disableUserBox()
    {
        $uid = intval($_GET['uid']);
        if (empty($uid)) {
            return false;
        }
        $this->assign('uid', $uid);

        $type = t($_GET['t']);
        if (empty($type) || !in_array($type, array('login', 'post'))) {
            $type = 'login';
        }
        $this->assign('type', $type);

        $uname = getUserName($uid);
        $this->assign('uname', $uname);

        $data = model('DisableUser')->getDisableUser($uid);
        if (empty($data)) {
            $data['login']['start_time_format'] = '';
            $data['login']['end_time_format'] = '';
            $data['post']['start_time_format'] = '';
            $data['post']['end_time_format'] = '';
        }
        $this->assign('disable', $data);
        $this->assign('disableJson', json_encode($data));

        $this->display();
    }

    public function setDisableUser()
    {
        $uid = intval($_POST['uid']);
        $disableItem = t($_POST['disableItem']);
        $startTime = strtotime(t($_POST['startTime']));
        $endTime = strtotime(t($_POST['endTime']));

        if (empty($uid) || empty($disableItem) || !in_array($disableItem, array('login', 'post')) || empty($startTime) || empty($endTime) || $startTime > $endTime) {
            exit(json_encode(array('status' => 0, 'info' => '操作失败')));
        }

        $result = model('DisableUser')->setDisableUser($uid, $disableItem, $startTime, $endTime);
        $res = array();
        if ($result) {
            $res = array('status' => '1', 'info' => '操作成功');
        } else {
            $res = array('status' => '0', 'info' => '操作失败');
        }
        exit(json_encode($res));
    }

    public function setEnableUser()
    {
        $id = intval($_POST['id']);

        if (empty($id)) {
            exit(json_encode(array('status' => '0', 'info' => '操作失败')));
        }

        $result = model('DisableUser')->setEnableUser($id);
        $res = array();
        if ($result) {
            $res = array('status' => '1', 'info' => '操作成功');
        } else {
            $res = array('status' => '0', 'info' => '操作失败');
        }
        exit(json_encode($res));
    }

    /**
     * 用户管理 - 添加用户.
     */
    public function addUser()
    {
        // 初始化用户列表管理菜单
        $this->_initUserListAdminMenu();
        //注册配置(添加用户页隐藏审核按钮)
        $regInfo = model('Xdata')->get('admin_Config:register');
        $this->pageKeyList = array('email', 'phone', 'uname', 'password', 'sex');
        if ($regInfo['register_audit'] == 1) {
            $this->pageKeyList = array_merge($this->pageKeyList, array('is_audit'));
            $this->opt['is_audit'] = array('1' => '是', '2' => '否');
        }
        if ($regInfo['need_active'] == 1) {
            $this->pageKeyList = array_merge($this->pageKeyList, array('is_active'));
            $this->opt['is_active'] = array('1' => '是', '2' => '否');
        }
        $this->pageKeyList = array_merge($this->pageKeyList, array('user_group'));
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));
        // 字段选项配置
        $this->opt['sex'] = array('1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        $this->opt['user_group'] = model('UserGroup')->getHashUsergroupNoncertified();
        $map['pid'] = array('NEQ', 0);
        $this->opt['user_category'] = model('UserCategory')->getAllHash($map);
        // 表单URL设置
        $this->savePostUrl = U('admin/User/doAddUser');
        $this->notEmpty = array('uname', 'password', 'user_group');
        $this->onsubmit = 'admin.addUserSubmitCheck(this)';

        $this->displayConfig();
    }

    /**
     * 添加新用户操作.
     */
    public function doAddUser()
    {
        $user = model('User');
        $map = $user->create();
        // 审核与激活修改
        $map['is_active'] = ($map['is_active'] == 2) ? 0 : 1;
        $map['is_audit'] = ($map['is_audit'] == 2) ? 0 : 1;
        // 检查map返回值，有表单验证
        $result = $user->addUser($map);
        if ($result) {
            $this->assign('jumpUrl', U('admin/User/index'));
            $this->success(L('PUBLIC_ADD_SUCCESS'));
        } else {
            $this->error($user->getLastError());
        }
    }

    /**
     * 编辑用户页面.
     */
    public function editUser()
    {
        // 初始化用户列表管理菜单
        $this->_initUserListAdminMenu();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('uid', 'email', 'mobile', 'uname', 'password', 'sex', 'user_group');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));
        // 字段选项配置
        $this->opt['sex'] = array('1' => L('PUBLIC_MALE'), '2' => L('PUBLIC_FEMALE'));
        //$this->opt['identity'] = array('1'=>L('PUBLIC_PERSONAL'),'2'=>L('PUBLIC_ORGANIZATION'));
        // $user_department = model('Department')->getAllHash(0);
        $usergroupHash = model('UserGroup')->getHashUsergroupNoncertified();
        // 去除禁言组
        unset($usergroupHash[4]);
        $this->opt['user_group'] = $usergroupHash;

        $this->opt['is_active'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));

        //获取用户资料
        $uid = intval($_REQUEST['uid']);
        $userInfo = model('User')->getUserInfo($uid);

        unset($userInfo['password']);

        $hasMobile = preg_match("/^[1][358]\d{9}$/", $userInfo['phone'], $matches) !== 0;
        if ($hasMobile) {
            $userInfo['mobile'] = $userInfo['phone'];
        } else {
            $userInfo['mobile'] = '';
        }

        //获取用户组信息
        $userInfo['user_group'] = model('UserGroupLink')->getUserGroup($uid);
        $userInfo['user_group'] = $userInfo['user_group'][$uid];
        $map['pid'] = array('neq', 0);
        $this->opt['user_category'] = model('UserCategory')->getAllHash($map);
        $userInfo['user_category'] = getSubByKey(model('UserCategory')->getRelatedUserInfo($uid), 'user_category_id');

        if (!$userInfo) {
            $this->error(L('PUBLIC_GET_INFORMATION_FAIL'));
        }

        $this->assign('pageTitle', L('PUBLIC_EDIT_USER'));
        $this->savePostUrl = U('admin/User/doUpdateUser');

        // $this->notEmpty = array('email','uname','department_id');
        $this->notEmpty = array('email', 'mobile', 'uname', 'user_group');
        $this->onsubmit = 'admin.checkUser(this)';

        $this->displayConfig($userInfo);
    }

    /**
     * 更新用户信息.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function doUpdateUser(array $data = array())
    {
        $model = model('User');
        $uid = intval($_POST['uid']);
        $uname = t($_POST['uname']);
        $phone = t($_POST['mobile']);
        $email = t($_POST['email']);
        $sex = intval($_POST['sex']);
        $password = $_POST['password'];
        $group = $_POST['user_group'];

        // # 判断是否存在UID
        if (!$uid) {
            $this->error('非法操作');

            // # 判断用户名是否存在
        } elseif (!$uname) {
            $this->error('用户名不能为空');

            // # 判断是否用户标识不存在
        } elseif (!$phone and !$email) {
            $this->error('用户手机号码或者邮箱至少存在一个');

            // # 判断手机号码是否可以修改
        } elseif ($phone and !$model->isChangePhone($phone, $uid)) {
            $this->error('当前手机号码已存在');

            // # 判断用户邮箱是否可以修改
        } elseif ($email and !$model->isChangeEmail($email, $uid)) {
            $this->error('当前邮箱已存在');

            // # 判断用户性别
        } elseif (!in_array($sex, array('1', '2'))) {
            $this->error('请正确先择用户性别');

            // # 判断用户组是否选择
        } elseif (count($group) <= 0) {
            $this->error('请选择用户用户组');

            // # 生成密码
        } elseif ($password) {
            $data['login_salt'] = rand(11111, 99999);
            $data['password'] = md5(md5($password).$data['login_salt']);
        }

        $sex and $data['sex'] = $sex;
        $uname and $data['uname'] = $uname;
        $data['phone'] = $phone;
        $data['email'] = $email;

        $uname and preg_match('/[\x7f-\xff]+/', $data['search_key'] = $uname) and $data['search_key'] .= ' '.model('PinYin')->Pinyin($uname);

        $data and $model->where('`uid` = '.$uid)->save($data);

        $group = implode(',', $group);
        model('UserGroupLink')->domoveUsergroup($uid, $group);

        // # 清理用户缓存
        $model->cleanCache($uid);
        model('Cache')->rm('perm_user_'.$uid);

        $this->assign('jumpUrl', U('admin/User/editUser', array(
            'uid'     => $uid,
            'tabHash' => 'editUser',
        )));
        $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
    }

    /*
     * 新增资料字段/分类
     * @access public
     *
     */
    public function doActiveUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        //设置激活状态id可以是多个，类型只能是0或1
        $result = model('User')->activeUsers($_POST['id'], $_POST['type']);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    public function doAuditUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        //设置激活状态id可以是多个，类型只能是0或1
        $result = model('Register')->audit($_POST['id'], $_POST['type']);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = model('Register')->getLastError();
        } else {
            $return['status'] = 1;
            $return['data'] = model('Register')->getLastError();
        }
        M('user_credit')->add(array('uid' => $_POST['id'], 'score' => 0, 'experience' => 0));
        // 添加积分
        model('Credit')->setUserCredit($_POST['id'], 'init_default');
        //清除缓存
        model('User')->cleanCache($_POST['id']);
        echo json_encode($return);
        exit();
    }

    /**
     * 用户账号禁用操作.
     *
     * @return json 操作后的JSON数据
     */
    public function doDeleteUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            exit(json_encode($return));
        }

        $result = model('User')->deleteUsers(intval($_POST['id']));
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');                // 操作失败
        } else {
            // 关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');            // 操作成功
        }
        exit(json_encode($return));
    }

    /**
     * 彻底删除用户账号操作.
     *
     * @return json 操作后的JSON数据
     */
    public function doTrueDeleteUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            exit(json_encode($return));
        }
        $result = model('User')->trueDeleteUsers($_POST['id']);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_REMOVE_COMPLETELY_FAIL');                // 操作失败
        } else {
            // 关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_REMOVE_COMPLETELY_SUCCESS');            // 操作成功
        }
        exit(json_encode($return));
    }

    /**
     * 用户账号恢复操作.
     *
     * @return json 操作后的JSON数据
     */
    public function doRebackUser()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            exit(json_encode($return));
        }

        $result = model('User')->rebackUsers($_POST['id']);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');                // 操作失败
        } else {
            //关联删除用户其他信息，执行删除用户插件.
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');            // 操作成功
        }
        exit(json_encode($return));
    }

    /*
     * 用户资料配置
     * @access public
     */
    public function profile()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //字段列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'field_key', 'field_name', 'field_type', 'visiable', 'editable', 'required', 'DOACTION');

        //列表批量操作按钮ed
        $this->pageButton[] = array('title' => L('PUBLIC_ADD_FIELD'), 'onclick' => "location.href='".U('admin/User/addProfileField', array('tabHash' => 'addField'))."'");

        $map = array();

        /*数据的格式化 与listKey保持一致 */

        //取用户列表
        $listData = D('UserProfile')->table(C('DB_PREFIX').'user_profile_setting')
                                    ->where($map)
                                    ->order('type,field_type,display_order asc')
                                    ->findPage(100);
        //dump($listData);exit;
        //数据格式化
        foreach ($listData['data'] as $k => $v) {
            if ($v['type'] == 1) {
                $type[$v['field_id']] = $v;
                $listData['data'][$k]['type'] = '<b>'.L('PUBLIC_SYSTEM_CATEGORY').'</b>';
            } else {
                $listData['data'][$k]['field_type'] = $type[$v['field_type']]['field_name'];
                $listData['data'][$k]['type'] = L('PUBLIC_SYSTEM_FIELD');
            }
            $listData['data'][$k]['visiable'] = $listData['data'][$k]['visiable'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            $listData['data'][$k]['editable'] = $listData['data'][$k]['editable'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            $listData['data'][$k]['required'] = $listData['data'][$k]['required'] == 1 ? L('PUBLIC_SYSTEMD_TRUE') : L('PUBLIC_SYSTEMD_FALSE');
            //操作按钮
            $listData['data'][$k]['DOACTION'] = '<a href="'.U('admin/User/editProfileField', array('tabHash' => 'editField', 'id' => $v['field_id'])).'">'.L('PUBLIC_EDIT').'</a> '
                                                .($v['is_system'] == 1 ? '' : ' -  <a href="javascript:void(0)" onclick="admin.delProfileField(\''.$v['field_id'].'\',1)">'.L('PUBLIC_STREAM_DELETE').'</a>');

            //如果只显示字段.删除数据
            if ($field_type != 1 && $v['type'] == 1) {
                unset($listData['data'][$k]);
            }
        }

        //$this->_listpk = 'field_id';
        $this->allSelected = false;
        $this->displayList($listData);
    }

    /*
     * 用户资料分类配置
     * @access public
     */
    public function profileCategory()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //分类列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'field_key', 'field_name', 'DOACTION');

        //列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'onclick' => "location.href='".U('admin/User/addProfileCategory', array('tabHash' => 'addCateogry'))."'");
        //$this->pageButton[] = array('title'=>'删除选中','onclick'=>"admin.delProfileField()");

        $map = array();
        $map['type'] = 1;

        /*数据的格式化 与listKey保持一致 */

        //取用户列表
        $listData = D('UserProfile')->table(C('DB_PREFIX').'user_profile_setting')
                                    ->where($map)
                                    ->order('type,field_type,display_order asc')
                                    ->findPage(100);

        //数据格式化
        foreach ($listData['data'] as $k => $v) {
            if ($v['type'] == 1) {
                $type[$v['field_id']] = $v;
                $listData['data'][$k]['type'] = '<b>'.L('PUBLIC_SYSTEM_CATEGORY').'</b>';
            } else {
                $listData['data'][$k]['field_type'] = $type[$v['field_type']]['field_name'];
                $listData['data'][$k]['type'] = L('PUBLIC_SYSTEM_FIELD');
            }

            //操作按钮

            $listData['data'][$k]['DOACTION'] = '<a href="'.U('admin/User/editProfileCategory', array('tabHash' => 'addProfileCategory', 'id' => $v['field_id'])).'">'.L('PUBLIC_EDIT').'</a> '
                                                .($v['is_system'] == 1 ? ' ' : ' - <a href="javascript:void(0)" onclick="admin.delProfileField(\''.$v['field_id'].'\',0)">'.L('PUBLIC_STREAM_DELETE').'</a>');
        }

        //$this->_listpk = 'field_id';
        $this->allSelected = false;
        $this->displayList($listData);
    }

    /*
     * 新增资料字段/分类
     * @access public
     *
     */
    public function editProfileCategory()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'type', 'field_key', 'field_name', 'field_type');
        $this->opt['type'] = array('1' => L('PUBLIC_SYSTEM_CATEGORY'));

        //获取配置信息
        $id = intval($_REQUEST['id']);
        $setting = D('UserProfileSetting')->where('type=1')->find($id);
        if (!$setting) {
            $this->error(L('PUBLIC_INFO_GET_FAIL'));
        }

        $this->savePostUrl = U('admin/User/doSaveProfileField');

        $this->notEmpty = array('field_key', 'field_name');
        $this->onsubmit = 'admin.checkProfile(this)';

        $this->displayConfig($setting);
    }

    /*
     * 新增资料字段/分类
     * @access public
     *
     */
    public function addProfileField($edit = false)
    {
        $_GET['id'] = intval($_GET['id']);
        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $edit && $this->pageTab[] = array('title' => L('PUBLIC_EDIT_FIELD'), 'tabHash' => 'editField', 'url' => U('admin/User/editProfileField', array('id' => $_REQUEST['id'])));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'type', 'field_key', 'field_name', 'field_type', 'visiable', 'editable', 'required', 'privacy', 'form_type', 'form_default_value', 'validation', 'tips');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));

        //获取字段分类列表
        $category = D('UserProfileSetting')->where('type=1')->findAll();
        foreach ($category as $c) {
            $cate_array[$c['field_id']] = $c['field_name'];
        }

        //字段选项配置
        $this->opt['field_type'] = $cate_array;
        $this->opt['visiable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy'] = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type'] = model('UserProfile')->getUserProfileInputType();

        $detail = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();
        $this->savePostUrl = !empty($detail) ? U('admin/User/doSaveProfileField') : U('admin/User/doAddProfileField');

        $this->notEmpty = array('field_key', 'field_name', 'field_type');
        $this->onsubmit = 'admin.checkProfile(this)';
        $this->displayConfig($detail);
    }

    public function editProfileField()
    {
        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $edit && $this->pageTab[] = array('title' => L('PUBLIC_EDIT_FIELD'), 'tabHash' => 'editField', 'url' => U('admin/User/editProfileField', array('id' => $_REQUEST['id'])));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('field_id', 'type', 'field_key', 'field_name', 'field_type', 'visiable', 'editable', 'required', 'privacy', 'form_type', 'form_default_value', 'validation', 'tips');
        $this->opt['type'] = array('2' => L('PUBLIC_SYSTEM_FIELD'));

        //获取字段分类列表
        $category = D('UserProfileSetting')->where('type=1')->findAll();
        foreach ($category as $c) {
            $cate_array[$c['field_id']] = $c['field_name'];
        }

        //字段选项配置
        $this->opt['field_type'] = $cate_array;
        $this->opt['visiable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy'] = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type'] = model('UserProfile')->getUserProfileInputType();

        $detail = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();
        $this->savePostUrl = !empty($detail) ? U('admin/User/doSaveProfileField') : U('admin/User/doAddProfileField');

        $this->notEmpty = array('field_key', 'field_name', 'field_type');
        $this->onsubmit = 'admin.checkProfile(this)';
        $this->displayConfig($detail);
        // $this->addProfileField(true);
    }

    /*
     * 新增资料字段/分类
     * @access public
     *
     */
    public function addProfileCategory()
    {

        //tab选项
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_FIELDLIST'), 'tabHash' => 'profile', 'url' => U('admin/User/profile'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_CATEGORYLIST'), 'tabHash' => 'category', 'url' => U('admin/User/profileCategory'));
        $this->pageTab[] = array('title' => L('PUBLIC_ADD_FIELD'), 'tabHash' => 'addField', 'url' => U('admin/User/addProfileField'));
        $this->pageTab[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'tabHash' => 'addCateogry', 'url' => U('admin/User/addProfileCategory'));

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('type', 'field_key', 'field_name', 'field_type');
        $this->opt['type'] = array('1' => L('PUBLIC_SYSTEM_CATEGORY'));

        //字段选项配置
        $this->opt['field_type'] = array('0' => L('PUBLIC_SYSTEM_PCATEGORY'));
        $this->opt['visiable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['editable'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['required'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['privacy'] = array('0' => L('PUBLIC_WEIBO_COMMENT_ALL'), '1' => L('PUBLIC_SYSTEM_PARENT_SEE'), '2' => L('PUBLIC_SYSTEM_FOLLOWING_SEE'), '3' => L('PUBLIC_SYSTEM_FOLLW_SEE'));
        $this->opt['form_type'] = model('UserProfile')->getUserProfileInputType();

        $this->savePostUrl = U('admin/User/doAddProfileField');

        $detail = !empty($_GET['id']) ? D('UserProfileSetting')->where("field_id='{$_GET['id']}'")->find() : array();

        $this->notEmpty = array('field_key', 'field_name');
        $this->onsubmit = 'admin.checkProfile(this)';

        $this->displayConfig($detail);
    }

    /*
     * 添加资料字段/分类
     * @access public
     *
     */
    public function doAddProfileField()
    {
        //dump($_REQUEST);exit;
        $profile = D('UserProfileSetting');
        $map = $profile->create();
        //检查map返回值.有表单验证.
        $result = $profile->add($map);
        if ($result) {
            $jumpUrl = $_POST['type'] == 1 ? U('admin/User/profileCategory', array('tabHash' => 'category')) : U('admin/User/profile');
            $this->assign('jumpUrl', $jumpUrl);
            $this->success(L('PUBLIC_ADD_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_ADD_FAIL'));
        }
    }

    /*
     * 保存资料字段/分类
     * @access public
     *
     */
    public function doSaveProfileField()
    {
        $profile = D('UserProfileSetting');
        $map = $profile->create();
        $field_id = intval($_POST['field_id']);

        $jumpUrl = $_POST['type'] == 1 ? U('admin/User/profileCategory', array('tabHash' => 'category')) : U('admin/User/profile');
        //检查map返回值.有表单验证.
        $result = $profile->where('field_id='.$field_id)->save($map);
        if ($result) {
            $this->assign('jumpUrl', $jumpUrl);
            $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_ADMIN_OPRETING_ERROR'));
        }
    }

    /*
     * 删除资料字段/分类
     * @access public
     *
     */
    public function doDeleteProfileField()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        if (D('UserProfileSetting')->where('field_type='.intval($_POST['id']))->find()) {
            $return['status'] = 0;
            $return['data'] = '删除失败，该分类下字段不为空！';
        } else {
            $result = model('UserProfile')->deleteProfileSet($_POST['id']);
            if (!$result) {
                $return['status'] = 0;
                $return['data'] = L('PUBLIC_DELETE_FAIL');
            } else {
                //关联删除用户其他信息.执行删除用户插件.
                $return['status'] = 1;
                $return['data'] = L('PUBLIC_DELETE_SUCCESS');
            }
        }
        echo json_encode($return);
        exit();
    }

    /*
     * 资料配置预览
     * @access public
     *
     */

    /**
     * 转移用户组
     * Enter description here ...
     */
    public function moveDepartment()
    {
        $this->display();
    }

    public function domoveDepart()
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));
        if (!empty($_POST['uid']) && !empty($_POST['topid'])) {
            if ($res = model('User')->domoveDepart($_POST['uid'], $_POST['topid'])) {
                $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
                //TODO 记录知识
            } else {
                $return['data'] = model('User')->getError();
            }
        }
        echo json_encode($return);
        exit();
    }

    public function moveGroup()
    {
        $usergroupHash = model('UserGroup')->getHashUsergroupNoncertified();
        unset($usergroupHash[4]);
        $this->assign('user_group', $usergroupHash);
        $this->display();
    }

    public function domoveUsergroup()
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));
        if (!empty($_POST['uid']) && !empty($_POST['user_group_id'])) {
            if ($res = model('UserGroupLink')->domoveUsergroup($_POST['uid'], $_POST['user_group_id'])) {
                $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
                //TODO 记录知识
            } else {
                $return['data'] = model('UserGroup')->getError();
            }
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 初始化用户认证菜单.
     */
    public function _initVerifyAdminMenu()
    {
        // tab选项
        $this->pageTab[] = array('title' => '认证分类', 'tabHash' => 'verifyCategory', 'url' => U('admin/User/verifyCategory'));
        $this->pageTab[] = array('title' => '置顶用户', 'tabHash' => 'config', 'url' => U('admin/User/verifyConfig'));
        $this->pageTab[] = array('title' => '添加认证用户', 'tabHash' => 'addverify', 'url' => U('admin/User/addVerify'));
        $this->pageTab[] = array('title' => '待认证用户', 'tabHash' => 'verify', 'url' => U('admin/User/verify'));
        $this->pageTab[] = array('title' => '待认证机构', 'tabHash' => 'verifyGroup', 'url' => U('admin/User/verifyGroup'));
        $this->pageTab[] = array('title' => '已认证用户', 'tabHash' => 'verified', 'url' => U('admin/User/verified'));
        $this->pageTab[] = array('title' => '已认证机构', 'tabHash' => 'verifiedGroup', 'url' => U('admin/User/verifiedGroup'));
    }

    /**
     * 获取�
     * 认证用户列表.
     */
    public function verify()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'realname', 'idcard', 'phone', 'reason', 'info', 'attachment', 'DOACTION');
        $listData = D('user_verified')->where('verified=0 and usergroup_id!=6')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id='.$v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = '';
                foreach ($a as $key => $val) {
                    if ($val !== '') {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path']).$attachInfo['save_name'].'" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.verify('.$v['id'].',1,0)">通过</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox('.$v['id'].')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 获取�
     * 认证机构列表.
     */
    public function verifyGroup()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1,6)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'company', 'realname', 'idcard', 'phone', 'reason', 'info', 'attachment', 'DOACTION');
        $listData = D('user_verified')->where('verified=0 and usergroup_id=6')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id='.$v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = '';
                foreach ($a as $key => $val) {
                    if ($val !== '') {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.verify('.$v['id'].',1,0)">通过</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox('.$v['id'].')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 获取已认证用户列表.
     */
    public function verified()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'realname', 'idcard', 'phone', 'reason', 'info', 'attachment', 'DOACTION');
        $listData = D('user_verified')->where('verified=1 and usergroup_id!=6')->order('id DESC')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id='.$v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = '';
                foreach ($a as $key => $val) {
                    if ($val !== '') {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['info'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['info']));
            $listData['data'][$k]['DOACTION'] = '<a href="'.U('admin/User/editVerify', array('tabHash' => 'verified', 'id' => $v['id'])).'">编辑</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox('.$v['id'].')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 获取已认证机构列表.
     */
    public function verifiedGroup()
    {
        $this->_initVerifyAdminMenu();
        $this->pageButton[] = array('title' => '驳回认证', 'onclick' => "admin.verify('',-1,6)");

        $this->pageKeyList = array('uname', 'usergroup_id', 'category', 'company', 'realname', 'idcard', 'phone', 'reason', 'info', 'attachment', 'DOACTION');
        $listData = D('user_verified')->where('verified=1 and usergroup_id=6')->order('id DESC')->findpage(20);
        // 获取认证分类的Hash数组
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($listData['data'] as $k => $v) {
            $userinfo = model('user')->getUserInfo($listData['data'][$k]['uid']);
            $listData['data'][$k]['uname'] = $userinfo['uname'];
            $listData['data'][$k]['usergroup_id'] = D('user_group')->where('user_group_id='.$v['usergroup_id'])->getField('user_group_name');
            if ($listData['data'][$k]['attach_id']) {
                $a = explode('|', $listData['data'][$k]['attach_id']);
                $listData['data'][$k]['attachment'] = '';
                foreach ($a as $key => $val) {
                    if ($val !== '') {
                        $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                        $listData['data'][$k]['attachment'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                    }
                }
                unset($a);
            }
            $listData['data'][$k]['category'] = $categoryHash[$v['user_verified_category_id']];
            $listData['data'][$k]['reason'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['reason']));
            $listData['data'][$k]['info'] = str_replace(array("\n", "\r"), array('', ''), format($listData['data'][$k]['info']));
            $listData['data'][$k]['DOACTION'] = '<a href="'.U('admin/User/editVerify', array('tabHash' => 'verifiedGroup', 'id' => $v['id'])).'">编辑</a> - ';
            $listData['data'][$k]['DOACTION'] .= '<a href="javascript:void(0)" onclick="admin.getVerifyBox('.$v['id'].')">驳回</a>';
        }
        $this->displayList($listData);
    }

    /**
     * 驳回理由窗口.
     */
    public function getVerifyBox()
    {
        $id = intval($_GET['id']);
        $this->assign('id', $id);

        $this->display('verifyBox');
    }

    /**
     * 执行认证
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doVerify()
    {
        $status = intval($_POST['status']);
        $id = $_POST['id'];
        if (is_array($id)) {
            $map['id'] = array('in', $id);
        } else {
            $map['id'] = $id;
        }
        $datas['verified'] = $status;
        if ($_POST['info']) {
            $datas['info'] = t($_POST['info']);
        }
        $res = D('user_verified')->where($map)->save($datas);
        if ($res) {
            $return['status'] = 1;
            if ($status == 1) {
                $return['data'] = '认证成功';
                //$data['content'] = '';
                if (is_array($id)) {
                    foreach ($id as $k => $v) {
                        $user_group = D('user_verified')->where('id='.$v)->find();
                        $maps['uid'] = $user_group['uid'];
                        $maps['user_group_id'] = $user_group['usergroup_id'];
                        $exist = D('user_group_link')->where($maps)->find();
                        if ($exist) {
                            continue;
                        }
                        D('user_group_link')->add($maps);
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_'.$user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_'.$user_group['uid']);
                        // 删除分享信息
                        $feed_ids = model('Feed')->where('uid='.$user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_ok');
                        model('User')->cleanCache($user_group['uid']);
                        unset($user_group);
                        unset($maps);
                    }
                } else {
                    $user_group = D('user_verified')->where('id='.$id)->find();
                    $maps['uid'] = $user_group['uid'];
                    $maps['user_group_id'] = $user_group['usergroup_id'];
                    $exist = D('user_group_link')->where($maps)->find();
                    if (!$exist) {
                        D('user_group_link')->add($maps);
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_'.$user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_'.$user_group['uid']);
                        // 删除分享信息
                        $feed_ids = model('Feed')->where('uid='.$user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_ok');
                        model('User')->cleanCache($user_group['uid']);
                    }
                }
            }
            if ($status == -1) {
                $return['data'] = '驳回成功';
                $rejectInfo = array('reason' => t($_POST['reason']));
                //$data['act'] = '驳回';
                if (is_array($id)) {
                    foreach ($id as $k => $v) {
                        $user_group = D('user_verified')->where('id='.$v)->find();
                        $maps['uid'] = $user_group['uid'];
                        $maps['user_group_id'] = $user_group['usergroup_id'];
                        D('user_group_link')->where($maps)->delete();
                        // 清除用户组缓存
                        model('Cache')->rm('user_group_'.$user_group['uid']);
                        // 清除权限缓存
                        model('Cache')->rm('perm_user_'.$user_group['uid']);
                        // 删除分享信息
                        $feed_ids = model('Feed')->where('uid='.$user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                        model('Feed')->cleanCache($feed_ids);

                        model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_reject', $rejectInfo);
                        model('User')->cleanCache($user_group['uid']);
                        unset($user_group);
                        unset($maps);
                    }
                } else {
                    $user_group = D('user_verified')->where('id='.$id)->find();
                    $maps['uid'] = $user_group['uid'];
                    $maps['user_group_id'] = $user_group['usergroup_id'];
                    D('user_group_link')->where($maps)->delete();
                    // 清除用户组缓存
                    model('Cache')->rm('user_group_'.$user_group['uid']);
                    // 清除权限缓存
                    model('Cache')->rm('perm_user_'.$user_group['uid']);
                    // 删除分享信息
                    $feed_ids = model('Feed')->where('uid='.$user_group['uid'])->limit(1000)->getAsFieldArray('feed_id');
                    model('Feed')->cleanCache($feed_ids);

                    model('Notify')->sendNotify($user_group['uid'], 'admin_user_doverify_reject', $rejectInfo);
                    model('User')->cleanCache($user_group['uid']);
                }
            }
        } else {
            $return['status'] = 0;
            $return['data'] = '认证失败';
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 添加认证用户或认证企业.
     */
    public function addVerify()
    {
        $this->_initVerifyAdminMenu();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('uname', 'usergroup_id', 'user_verified_category_id', 'company', 'realname', 'idcard', 'phone', 'reason', 'info', 'attach');
        // 字段选项配置
        $auType = model('UserGroup')->where('is_authenticate=1')->select();
        foreach ($auType as $k => $v) {
            $this->opt['usergroup_id'][$v['user_group_id']] = $v['user_group_name'];
        }
        // 认证分类配置
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($categoryHash as $key => $value) {
            $this->opt['user_verified_category_id'][$key] = $value;
        }
        // 表单URL设置
        $this->savePostUrl = U('admin/User/doAddVerify');
        $this->notEmpty = array('uname', 'usergroup_id', 'company', 'realname', 'idcard', 'phone', 'reason', 'info');
        $this->onload[] = 'admin.addVerifyConfig(5)';
        //$this->onsubmit = 'admin.addVerifySubmitCheck(this)';

        $this->displayConfig();
    }

    /**
     * 执行添加认证
     */
    public function doAddVerify()
    {
        $data['uid'] = $_POST['uname'];
        $result = D('user_verified')->where('uid='.$data['uid'])->find();
        if ($result) {
            if ($result['verified'] == 1) {
                $this->error('该用户已通过认证');
            } else {
                D('user_verified')->where('uid='.$data['uid'])->delete();
            }
        }

        $data['usergroup_id'] = intval($_POST['usergroup_id']);
        if ($_POST['company']) {
            $data['company'] = t($_POST['company']);
        }
        $data['realname'] = t($_POST['realname']);
        $data['idcard'] = t($_POST['idcard']);
        $data['phone'] = t($_POST['phone']);
        $data['reason'] = t($_POST['reason']);
        $data['info'] = t($_POST['info']);
        //	$data['attachment'] = t($_POST['attach']);
        $data['attach_id'] = t($_POST['attach_ids']);
        $data['user_verified_category_id'] = intval($_POST['user_verified_category_id']);
        $Regx1 = '/^[0-9]*$/';
        $Regx2 = '/^[A-Za-z0-9]*$/';
        $Regx3 = '/^[A-Za-z|\x{4e00}-\x{9fa5}]+$/u';
        if ($data['usergroup_id'] == 6) {
            if (strlen($data['company']) == 0) {
                $this->error('企业名称不能为空');
            }
            if (strlen($data['realname']) == 0) {
                $this->error('法人姓名不能为空');
            }
            if (strlen($data['idcard']) == 0) {
                $this->error('营业执照号不能为空');
            }
            if (strlen($data['phone']) == 0) {
                $this->error('联系方式不能为空');
            }
            if (strlen($data['reason']) == 0) {
                $this->error('认证补充不能为空');
            }
            if (strlen($data['info']) == 0) {
                $this->error('认证资料不能为空');
            }
            if (preg_match($Regx2, $data['idcard']) == 0) {
                $this->error('请输入正确的营业执照号');
            }
        } else {
            if (strlen($data['realname']) == 0) {
                $this->error('真实姓名不能为空');
            }
            if (strlen($data['idcard']) == 0) {
                $this->error('身份证号码不能为空');
            }
            if (strlen($data['phone']) == 0) {
                $this->error('手机号码不能为空');
            }
            if (strlen($data['reason']) == 0) {
                $this->error('认证补充不能为空');
            }
            if (strlen($data['info']) == 0) {
                $this->error('认证资料不能为空');
            }
            if (preg_match($Regx3, $data['realname']) == 0 || strlen($data['realname']) > 30) {
                $this->error('请输入正确的姓名格式');
            }
            if (preg_match($Regx2, $data['idcard']) == 0 || preg_match($Regx1, substr($data['idcard'], 0, 17)) == 0 || strlen($data['idcard']) !== 18) {
                $this->error('请输入正确的身份证号码');
            }
            if (strlen($data['phone']) !== 11 || preg_match($Regx1, $data['phone']) == 0) {
                $this->error('请输入正确的手机号码格式');
            }
        }
        // preg_match_all('/./us', $data['reason'], $matchs);   //一个汉字也为一个字符
        // if(count($matchs[0])>140){
        // 	$this->error('认证补充不能超过140个字符');
        // }
        // preg_match_all('/./us', $data['info'], $match);   //一个汉字也为一个字符
        // if(count($match[0])>140){
        // 	$this->error('认证资料不能超过140个字符');
        // }
        $data['verified'] = 1;
        $res = D('user_verified')->add($data);
        $map['uid'] = $_POST['uname'];
        $map['user_group_id'] = intval($_POST['usergroup_id']);
        $res2 = D('user_group_link')->add($map);
        // 清除用户组缓存
        model('Cache')->rm('user_group_'.$map['uid']);
        // 清除权限缓存
        model('Cache')->rm('perm_user_'.$map['uid']);
        if ($res && $res2) {
            $this->success('添加认证成功');
        } else {
            $this->error('认证失败');
        }
    }

    /**
     * 通过时编辑认证资料.
     */
    public function editVerifyInfo()
    {
        $this->assign('id', intval($_GET['id']));
        $this->assign('status', intval($_GET['status']));
        $verifyInfo = D('user_verified')->where('id='.intval($_GET['id']))->find();
        $this->assign('info', format($verifyInfo['reason']));
        $this->display();
    }

    /**
     * 编辑认证资料.
     */
    public function editVerify()
    {
        $this->_initVerifyAdminMenu();

        $this->pageKeyList = array('uid', 'uname', 'usergroup_id', 'user_verified_category_id', 'company', 'realname', 'idcard', 'phone', 'reason', 'info', 'attach');

        $id = intval($_REQUEST['id']);
        $verifyInfo = D('user_verified')->where('id='.$id)->find();
        $userinfo = model('user')->getUserInfo($verifyInfo['uid']);
        $verifyInfo['uname'] = $userinfo['uname'];
        // 认证分类配置
        $categoryHash = model('CategoryTree')->setTable('user_verified_category')->getCategoryHash();
        foreach ($categoryHash as $key => $value) {
            $this->opt['user_verified_category_id'][$key] = $value;
        }
        // 认证组
        $auType = model('UserGroup')->where('is_authenticate=1')->select();
        foreach ($auType as $k => $v) {
            $this->opt['usergroup_id'][$v['user_group_id']] = $v['user_group_name'];
        }

        $verifyInfo['attach'] = str_replace('|', ',', substr($verifyInfo['attach_id'], 1, strlen($verifyInfo['attach_id']) - 2));

        $this->savePostUrl = U('admin/User/doEditVerify');
        $this->onsubmit = 'admin.editVerifySubmitCheck(this)';
        $this->notEmpty = array('usergroup_id', 'company', 'realname', 'idcard', 'phone', 'reason', 'info');
        $this->onload[] = "admin.addVerifyConfig({$verifyInfo['usergroup_id']})";
        $this->displayConfig($verifyInfo);
    }

    /**
     * 执行编辑认证资料.
     */
    public function doEditVerify()
    {
        $uid = intval($_POST['uid']);
        $old_group_id = D('user_verified')->where('uid='.$uid)->getField('usergroup_id');
        $data['usergroup_id'] = intval($_POST['usergroup_id']);
        if ($data['usergroup_id'] == 6) {
            $data['company'] = t($_POST['company']);
        }
        $data['realname'] = t($_POST['realname']);
        $data['idcard'] = t($_POST['idcard']);
        $data['phone'] = t($_POST['phone']);
        $data['reason'] = t($_POST['reason']);
        $data['info'] = t($_POST['info']);
        $data['attach_id'] = t($_POST['attach_ids']);
        $data['user_verified_category_id'] = intval($_POST['user_verified_category_id']);
        //dump($data);exit;
        $res = D('user_verified')->where('uid='.$uid)->save($data);
        if ($old_group_id != $data['usergroup_id']) {
            D('user_group_link')->where('uid='.$uid.' and user_group_id='.$old_group_id)->setField('user_group_id', $data['usergroup_id']);
        }
        // 清除用户组缓存
        model('Cache')->rm('user_group_'.$uid);
        // 清除权限缓存
        model('Cache')->rm('perm_user_'.$uid);
        if ($res) {
            $this->success('编辑成功');
        } else {
            $this->error('编辑失败');
        }
    }

    public function getVerifyCategory()
    {
        $category = D('user_verified_category')->where('pid='.intval($_POST['value']))->findAll();
        foreach ($category as $k => $v) {
            $option .= '<option ';
            // if(intval($_POST['category_id'])==$v['user_verified_category_id']){
            // 	$option[$v['pid']] .= 'selected';
            // }
            $option .= ' value="'.$v['user_verified_category_id'].'">'.$v['title'].'</option>';
        }
        echo $option;
    }

    /**
     * 推荐标签 - 列表显示.
     */
    public function category()
    {
        $_GET['pid'] = intval($_GET['pid']);
        $treeData = model('CategoryTree')->setTable('user_category')->getNetworkList();
        // 配置删除关联信息
        $this->displayTree($treeData, 'user_category', 2, '', '', 10);
    }

    /**
     * 认证分类展示页面.
     */
    public function verifyCategory()
    {
        // 初始化Tab信息
        $this->_initVerifyAdminMenu();
        // 分类相关数据
        //$_GET['pid'] = intval($_GET['pid']);
        //$treeData = model('CategoryTree')->setTable('user_verified_category')->getNetworkList();

        //$this->displayTree($treeData, 'user_verified_category');

        //分类列表key值 DOACTION表示操作
        $this->pageKeyList = array('user_verified_category_id', 'title', 'pCategory', 'DOACTION');

        //列表批量操作按钮
        $this->pageButton[] = array('title' => L('PUBLIC_SYSTEM_ADD_CATEGORY'), 'onclick' => 'admin.addVerifyCategory()');

        //取用户列表
        $listData = D('user_verified_category')->findpage(20);
        //数据格式化
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['pCategory'] = model('UserGroup')->where('is_authenticate=1 AND user_group_id='.$v['pid'])->getField('user_group_name');

            //操作按钮

            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0);" onclick="admin.editVerifyCategory('.$v['user_verified_category_id'].')">'.L('PUBLIC_EDIT').'</a> '
                                                .($v['is_system'] == 1 ? ' ' : ' - <a href="javascript:void(0)" onclick="admin.delVerifyCategory('.$v['user_verified_category_id'].')">'.L('PUBLIC_STREAM_DELETE').'</a>');
        }

        //$this->_listpk = 'field_id';
        $this->allSelected = false;
        $this->displayList($listData);
    }

    /**
     * 添加认证分类.
     */
    public function addVerifyCategory()
    {
        $vType = model('UserGroup')->where('is_authenticate=1')->findAll();
        $this->assign('vType', $vType);
        $this->display('editVerifyCategory');
    }

    /**
     * 编辑认证分类.
     */
    public function editVerifyCategory()
    {
        $vType = model('UserGroup')->where('is_authenticate=1')->findAll();
        $this->assign('vType', $vType);
        $user_verified_category_id = intval($_GET['user_verified_category_id']);
        $cateInfo = D('user_verified_category')->where('user_verified_category_id='.$user_verified_category_id)->find();
        $this->assign('cateInfo', $cateInfo);
        $this->display('editVerifyCategory');
    }

    /**
     * 执行添加认证分类.
     */
    public function doAddVerifyCategory()
    {
        $data['pid'] = intval($_POST['pid']);
        $data['title'] = t($_POST['title']);
        if (D('user_verified_category')->where($data)->find()) {
            $return['status'] = 0;
            $return['data'] = '此分类已存在';
        } else {
            if (D('user_verified_category')->add($data)) {
                $return['status'] = 1;
                $return['data'] = '添加成功';
            } else {
                $return['status'] = 0;
                $return['data'] = '添加失败';
            }
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 执行编辑认证分类.
     */
    public function doEditVerifyCategory()
    {
        $data['pid'] = intval($_POST['pid']);
        $data['title'] = t($_POST['title']);
        $user_verified_category_id = intval($_POST['user_verified_category_id']);
        if (D('user_verified_category')->where($data)->find()) {
            $return['status'] = 0;
            $return['data'] = '此分类已存在';
        } else {
            $old_pid = D('user_verified_category')->where('user_verified_category_id='.$user_verified_category_id)->getField('pid');
            if (D('user_verified_category')->where('user_verified_category_id='.$user_verified_category_id)->save($data) !== false) {
                if ($old_pid != $data['pid']) {
                    D('user_verified')->where('user_verified_category_id='.$user_verified_category_id)->setField('usergroup_id', $data['pid']);
                    $datas['uid'] = array('in', getSubByKey(D('user_verified')->where('user_verified_category_id='.$user_verified_category_id)->field('uid')->findAll(), 'uid'));
                    $datas['user_group_id'] = $old_pid;
                    D('user_group_link')->where($datas)->setField('user_group_id', $data['pid']);
                }
                $return['status'] = 1;
                $return['data'] = '编辑成功';
            } else {
                $return['status'] = 0;
                $return['data'] = '编辑失败';
            }
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 删除认证分类.
     */
    public function delVerifyCategory()
    {
        $user_verified_category_id = intval($_POST['user_verified_category_id']);
        if (D('user_verified_category')->where('user_verified_category_id='.$user_verified_category_id)->delete()) {
            $return['status'] = 1;
            $return['data'] = '删除成功';
        } else {
            $return['status'] = 0;
            $return['data'] = '删除失败';
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 认证用户基本�
     * �置.
     */
    public function verifyConfig()
    {
        // 配置用户基本信息
        $this->_initVerifyAdminMenu();
        // 配置用户存储基本字段
        $this->pageKeyList = array('top_user');
        // 显示配置列表
        $this->displayConfig();
    }

    /**
     * 找人�
     * �局
     */
    public function findPeopleConfig()
    {
        // tab选项
        $this->pageTab[] = array('title' => '找人配置', 'tabHash' => 'findPeopleConfig', 'url' => U('admin/User/findPeopleConfig'));
        // 配置用户存储基本字段
        $this->pageKeyList = array('findPeople');
        $findtype['tag'] = '按标签';
        $findtype['area'] = '按地区';
        $findtype['verify'] = '认证用户';
        $findtype['official'] = '官方推荐';
        $this->opt['findPeople'] = $findtype;
        // 显示配置列表
        $this->displayConfig();
    }

    /**
     * 官方用户�
     * �置.
     */
    public function official()
    {
        // 初始化
        $this->_officialInit();
        // 配置用户存储基本字段
        $this->pageKeyList = array('top_user');
        // 显示配置列表
        $this->displayConfig();
    }

    /*** 官方用户 ***/

    /**
     * 官方用户分类.
     */
    public function officialCategory()
    {
        // 初始化
        $this->_officialInit();
        // 获取分类信息
        $_GET['pid'] = intval($_GET['pid']);
        $treeData = model('CategoryTree')->setTable('user_official_category')->getNetworkList();
        // 删除分类关联信息
        $delParam['module'] = 'UserOfficial';
        $delParam['method'] = 'deleteAssociatedData';
        $this->displayTree($treeData, 'user_official_category', 1, $delParam);
    }

    /**
     * 官方用户列表.
     */
    public function officialList()
    {
        // 设置列表主键
        $this->_listpk = 'official_id';
        // 初始化
        $this->_officialInit();
        // 列表批量操作按钮
        $this->pageButton[] = array('title' => '移除', 'onclick' => 'admin.removeOfficialUser()');
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('official_id', 'uid', 'uname', 'title', 'info', 'DOACTION');
        // 获取用户列表
        $listData = model('UserOfficial')->getUserOfficialList();
        // 组装数据
        foreach ($listData['data'] as &$value) {
            $user_category = model('CategoryTree')->setTable('user_official_category')->getCategoryById($value['user_official_category_id']);
            $value['title'] = $user_category['title'];
            $value['DOACTION'] = '<a href="javascript:;" onclick="admin.removeOfficialUser('.$value['official_id'].')">移除</a>';
        }

        $this->displayList($listData);
    }

    /**
     * 添加官方用户界面.
     */
    public function officialAddUser()
    {
        $_REQUEST['tabHash'] = 'officialAddUser';
        // 初始化
        $this->_officialInit();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('uids', 'category', 'info');
        // 字段选项配置
        $this->opt['category'] = model('CategoryTree')->setTable('user_official_category')->getCategoryHash();
        // 表单URL设置
        $this->savePostUrl = U('admin/User/doOfficialAddUser');
        $this->notEmpty = array('uids', 'category');

        $this->displayConfig();
    }

    /**
     * 添加官方用户操作.
     */
    public function doOfficialAddUser()
    {
        //dump($_REQUEST);exit;
        if (empty($_REQUEST['uids']) || empty($_REQUEST['category'])) {
            $this->error('请添加用户');

            return false;
        }
        $uids = t($_REQUEST['uids']);
        $cid = intval($_REQUEST['category']);
        $info = t($_REQUEST['info']);
        $result = model('UserOfficial')->addOfficialUser($uids, $cid, $info);
        // 添加后跳转
        if ($result) {
            $this->assign('jumpUrl', U('admin/User/officialAddUser'));
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 移除官方用户操作.
     *
     * @return json 操作后返回的JSON数据
     */
    public function doRemoveOfficialUser()
    {
        $ids = t($_POST['id']);
        $res = array();
        if (empty($ids)) {
            $res['status'] = 0;
            $res['data'] = '请选择用户';
        } else {
            // 删除操作
            $result = model('UserOfficial')->removeUserOfficial($ids);
            // 返回结果集
            if ($result) {
                $res['status'] = 1;
                $res['data'] = '操作成功';
            } else {
                $res['status'] = 0;
                $res['data'] = '操作失败';
            }
        }
        exit(json_encode($res));
    }

    /**
     * 初始化官方用户Tab标签选项.
     */
    private function _officialInit()
    {
        $this->pageTab[] = array('title' => '推荐分类', 'tabHash' => 'officialCategory', 'url' => U('admin/User/officialCategory'));
        $this->pageTab[] = array('title' => '置顶用户', 'tabHash' => 'official', 'url' => U('admin/User/official'));
        $this->pageTab[] = array('title' => '添加推荐用户', 'tabHash' => 'officialAddUser', 'url' => U('admin/User/officialAddUser'));
        $this->pageTab[] = array('title' => '已推荐用户', 'tabHash' => 'officialList', 'url' => U('admin/User/officialList'));
    }
}
