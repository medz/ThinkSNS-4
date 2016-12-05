<?php

/**
 * 后台应用管理.
 *
 * @author jason
 */
class AppsAction extends AdministratorAction
{
    public $pageTitle = array(
        'index'         => '已安装应用列表',
        'install'       => '待安装应用列表',
        'onLineApp'     => '在线应用',
        'setCreditNode' => '积分节点设置',
        'setPermNode'   => '权限节点设置',
        'setFeedNode'   => '分享模板设置',
    );

    private $appStatus = array('0' => '关闭', '1' => '开启');    //应用状态
    private $host_type_alias = array(0 => '本地应用', 1 => '远程应用');    //托管状态
    private $RemoteAppURL = '';

    public function _initialize()
    {
        $this->pageTitle['index'] = L('PUBLIC_INSTALLED_APPLIST');
        $this->pageTitle['install'] = L('PUBLIC_UNINSTALLED_APPLIST');
        $this->pageTitle['setCreditNode'] = L('PUBLIC_POINTS_SETTING');
        $this->pageTitle['setPermNode'] = L('PUBLIC_AUTHORITY_SETTING');
        $this->pageTitle['setFeedNode'] = L('PUBLIC_WEIBO_TEMPLATE_SETTING');
        $this->appStatus[0] = L('PUBLIC_CLOSE');
        $this->appStatus[1] = L('PUBLIC_OPEN');
        $this->host_type_alias[0] = L('PUBLIC_LOCAL_APP');
        $this->host_type_alias[1] = L('PUBLIC_REMOTE_APP');
        $this->RemoteAppURL = C('TS_UPDATE_SITE');
        parent::_initialize();
    }

    //已安装的应用
    public function index()
    {

        //列表key值 DOACTION表示操作
        $this->pageKeyList = array('app_id', 'icon_url', 'app_name', 'app_alias', 'status', 'DOACTION');

        $listData = model('App')->findPage(20);

        $inNav = $this->navList;

        foreach ($listData['data'] as &$v) {

            // $v['icon_url'] = empty($v['icon_url']) ? '<img src="'.APPS_URL.'/'.$v['app_name'].'/Appinfo/icon_app.png" >' : "<img src='{$v['icon_url']}'>";
            $v['icon_url'] = '<img src="'.APPS_URL.'/'.$v['app_name'].'/Appinfo/icon_app.png" >';
            !empty($v['author_homepage_url']) && $v['author_name'] = "<a href='{$v['author_homepage_url']}'>{$v['author_name']}</a>";
            $v['DOACTION'] = "<a href='".U('admin/Apps/preinstall', array('app_name' => $v['app_name']))."'>".L('PUBLIC_EDIT').'</a>&nbsp;-&nbsp;';
            $v['DOACTION'] .= $v['status'] == 0
                            ? "<a href='javascript:admin.setAppStatus({$v['app_id']},1)'>".L('PUBLIC_OPEN').'</a>&nbsp;-&nbsp;'
                            : "<a href='javascript:admin.setAppStatus({$v['app_id']},0)'>".L('PUBLIC_CLOSE').'</a>&nbsp;-&nbsp;';
            $v['DOACTION'] .= "<a href='".U('admin/Apps/uninstall', array('app_id' => $v['app_id']))."'>".L('PUBLIC_SYSTEM_APP_UNLODING').'</a>&nbsp;-&nbsp;';
            if (!empty($v['admin_entry'])) {
                $name = L('PUBLIC_APPNAME_'.strtoupper($v['app_name']));
                $v['DOACTION'] .= in_array($v['app_name'], array_keys($inNav)) ?
                ' <a href="javascript:;" onclick="admin.appnav(this,\''.$name.'\',\''.$v['admin_entry'].'\')" add="1">'.L('PUBLIC_REMOVE_NAV').'</a>'
                : ' <a href="javascript:;" onclick="admin.appnav(this,\''.$name.'\',\''.$v['admin_entry'].'\')" add="0">'.L('PUBLIC_ADD_NAV').'</a>';

                $v['DOACTION'] .= '&nbsp;-&nbsp;<a href="'.U($v['app_name'].'/'.$v['app_entry']).'" target= _blank>访问应用</a>';
            }

            $v['status'] = $this->appStatus[$v['status']];    //语义化
        }

        $this->pageButton[] = array('title' => L('PUBLIC_OPEN'), 'onclick' => "admin.setAppStatus('', 1)");
        $this->pageButton[] = array('title' => L('PUBLIC_CLOSE'), 'onclick' => "admin.setAppStatus('', 0)");

        $this->_listpk = 'app_id';
        $this->displayList($listData);
    }

    //待安装的应用
    public function install()
    {
        $this->pageKeyList = array('icon_url', 'app_name', 'app_alias', 'description', 'host_type_alias', 'company_name', 'DOACTION');

        $listData['data'] = model('App')->getUninstallList();

        foreach ($listData['data'] as &$v) {
            $v['host_type_alias'] = $this->host_type_alias[$v['host_type']];
            !empty($v['author_homepage_url']) && $v['author_name'] = "<a href='{$v['author_homepage_url']}'>{$v['author_name']}</a>";
            $v['icon_url'] = empty($v['icon_url']) ? '<img src="'.APPS_URL.'/'.$v['app_name'].'/Appinfo/icon_app.png" >' : "<img src='{$v['icon_url']}'>";
            $v['DOACTION'] = "<a href='".U('admin/Apps/preinstall', array('app_name' => $v['app_name'], 'install' => 1))."'>".L('PUBLIC_SYSTEM_APP_INSTALL').'</a>';
        }
        $this->allSelected = false;
        $this->displayList($listData);
    }

    //安装 编辑 应用
    public function preinstall($app_name = '', $install = '')
    {
        if (empty($app_name)) {
            $app_name = t($_GET['app_name']);
        }
        if (empty($install)) {
            $install = t($_GET['install']);
        }
        if (empty($app_name)) {
            $this->error(L('PUBLIC_SYSTEM_APP_SELECTINSTALL'));
        }

        $this->pageKeyList = array('app_id', 'app_name', 'app_alias', 'app_entry', 'description', 'status', 'host_type', 'icon_url', 'large_icon_url',
                                'admin_entry', 'statistics_entry', 'company_name', 'display_order', 'version', 'api_key', 'secure_key', 'add_front_top', 'add_front_applist',
                                );

        if (!empty($install)) {
            $this->submitAlias = L('PUBLIC_SYSTEM_APP_INSTALL');
            $info = model('App')->__getAppInfo($app_name);
            $this->assign('pageTitle', L('PUBLIC_SYSTEM_APP_INSTALL'));
            !empty($info['admin_entry']) && $this->pageKeyList[] = 'add_tonav';
        } else {
            $map['app_name'] = $app_name;
            $info = model('App')->where($map)->find();
            $this->assign('pageTitle', L('PUBLIC_SYSTEM_APP_EDIT'));
        }

        $this->savePostUrl = U('admin/Apps/saveApp');
        $this->opt['status'] = $this->appStatus;
        $this->opt['host_type'] = $this->host_type_alias;
        $this->opt['add_tonav'] = array(0 => L('PUBLIC_SYSTEMD_FALSE'), 1 => L('PUBLIC_SYSTEMD_TRUE'));
        $this->opt['add_front_top'] = array(0 => L('PUBLIC_SYSTEMD_FALSE'), 1 => L('PUBLIC_SYSTEMD_TRUE'));
        $this->opt['add_front_applist'] = array(0 => L('PUBLIC_SYSTEMD_FALSE'), 1 => L('PUBLIC_SYSTEMD_TRUE'));
        $this->notEmpty = array('app_name', 'app_alias', 'app_entry');
        $this->onsubmit = 'admin.checkAppInfo(this)';
        $this->displayConfig($info);
    }

    //安装保存应用
    public function saveApp()
    {
        //app_id 为空为安装 ，否则为修改
        if (empty($_POST['app_name']) || empty($_POST['app_alias']) || empty($_POST['app_entry'])) {
            $this->error(L('PUBLIC_SYSTEM_APP_INSTALLERROR'));
        }

        if (!isset($_POST['display_order']) || !$_POST['display_order']) {
            $_POST['display_order'] = 1;
        }

        $status = model('App')->saveApp($_POST);

        if ($status === true) {
            $log['app_name'] = $_POST['app_name'];
            if (!empty($_POST['app_id'])) {
                $log['app_id'] = $_POST['app_id'];
            }
            $log['app_alias'] = $_POST['app_alias'];
            $log['k'] = L('PUBLIC_SYSTEM_APP_FILEDS');
            LogRecord('admin_extends', 'appManage', $log, true);

            if (!empty($_POST['admin_entry']) && $_POST['add_tonav'] == 1) {
                $this->navList[$_POST['app_name']] = $_POST['admin_entry'];
                model('Xdata')->put('admin_nav:top', $this->navList);
            }
            if ($_POST['app_name'] == 'tipoff') {
                model('UserGroup')->cleanCache();
            }
            $this->assign('jumpUrl', !empty($_POST['app_id']) ? U('admin/Apps/index') : U('admin/Apps/install'));
            $this->success();
        } else {
            $this->error($status);
        }
    }

    //卸载应用
    public function uninstall()
    {
        $app_id = intval($_GET['app_id']);
        if (empty($app_id)) {
            $this->error(L('PUBLIC_ID_NOEXIST'));
        }
        $appInfo = model('App')->getAppById($app_id);
        $status = model('App')->uninstall($app_id);
        if ($status === true) {
            unset($this->navList[$appInfo['app_name']]);
            model('Xdata')->put('admin_nav:top', $this->navList);
            LogRecord('admin_extends', 'appUninstall', array('app_id' => $app_id, 'k1' => L('PUBLIC_SYSTEM_APP_UNLODING')), true);
            $this->success();
        } else {
            $this->error($status);
        }
    }

    //权限节点设置
    public function setPermNode()
    {
        $this->pageKeyList = array('id', 'appname', 'appinfo', 'module', 'rule', 'ruleinfo', 'DOACTION');
        //列表分页栏 按钮
        $this->pageButton[] = array('title' => L('PUBLIC_ADD'), 'onclick' => "location.href = '".U('admin/Apps/editPermNode')."'");
        $this->pageButton[] = array('title' => L('PUBLIC_STREAM_DELETE'), 'onclick' => 'admin.delPermNode()');
        $listData = D('permission_node')->findPage(10);
        foreach ($listData['data'] as &$v) {
            $v['DOACTION'] = "<a href='".U('admin/Apps/editPermNode', array('id' => $v['id']))."'>".L('PUBLIC_EDIT')."</a> | 
				<a href='javascript:void(0)' onclick='admin.delPermNode(".$v['id'].")'>".L('PUBLIC_STREAM_DELETE').'</a>';
        }
        $this->displayList($listData);
    }

    public function editPermNode()
    {
        if (!empty($_GET['id'])) {
            $info = D('permission_node')->find(intval($_GET['id']));
            $this->assign('pageTitle', '编辑权限节点');
        } else {
            $this->submitAlias = L('PUBLIC_ADD');
            $this->assign('pageTitle', L('PUBLIC_SYSTEM_ADMINJUR_ADD'));
        }

        $this->pageKeyList = array('id', 'appname', 'appinfo', 'module', 'rule', 'ruleinfo');

        $this->opt['module'] = array('normal' => L('PUBLIC_SYSTEM_NORMAL_USER'), 'admin' => L('PUBLIC_SYSTEM_ADMIN_USER'));

        $this->savePostUrl = U('admin/Apps/savePermNode');

        $this->notEmpty = array('appname', 'module', 'rule');
        $this->onsubmit = 'admin.checkPermNode(this)';

        $this->displayConfig($info);
    }

    public function savePermNode()
    {
        if (!empty($_POST)) {
            $data['appname'] = t($_POST['appname']);
            $data['appinfo'] = t($_POST['appinfo']);
            $data['module'] = t($_POST['module']);
            $data['rule'] = t($_POST['rule']);
            $data['ruleinfo'] = t($_POST['ruleinfo']);

            if (empty($data['appname']) || empty($data['module']) || empty($data['rule'])) {
                $this->error(L('PUBLIC_APPLICATIONS_MODULE_RULES_NOEMPTY'));
                exit();
            }
            if (!empty($_POST['id'])) {
                $map['id'] = intval($_POST['id']);
                $act = 'editPermNode';
                $res = D('permission_node')->where($map)->save($data);
            } else {
                $act = 'addPermNode';
                $res = D('permission_node')->add($data);
            }
            LogRecord('admin_extends', $act, $data, true);
        }

        if ($res) {
            $this->assign('jumpUrl', U('admin/Apps/setPermNode'));
            $this->success();
        } else {
            $this->error();
        }
    }

    public function delPermNode()
    {
        $return = array('status' => 1, 'data' => L('PUBLIC_DELETE_SUCCESS'));
        $id = $_POST['id'];
        $map['id'] = is_array($id) ? array('in', $id) : $id;
        if ($data = D('permission_node')->where($map)->findAll()) {
            if (D('permission_node')->where($map)->delete()) {
                $d['log'] = var_export($data, true);
                $d['k'] = L('PUBLIC_SYSTEM_ADMINJUR_DELETE');
                LogRecord('admin_extends', 'delPermNode', $d, true);
                echo json_encode($return);
                exit();
            }
        }
        $return['status'] = 0;
        $return['data'] = L('PUBLIC_DELETE_FAIL');
        echo json_encode($return);
        exit();
    }

    //动态节点设置
    public function setAppStatus()
    {
        $app_id = $_POST['app_id'];
        $map['app_id'] = is_array($app_id) ? array('in', $app_id) : intval($app_id);
        $data['status'] = intval($_POST['status']);
        if ($data = D('App')->where($map)->save($data)) {

            // 设置缓存
            $appname = D('App')->where($map)->getField('app_name');
            model('Cache')->set('Appinfo_'.$appname, null);
            model('App')->cleanCache();
            $return['status'] = 1;
            $return['data'] = '设置成功！';
            echo json_encode($return);
            exit();
        } else {
            $return['status'] = 0;
            $return['data'] = '设置失败！';
            echo json_encode($return);
            exit();
        }
    }
}
