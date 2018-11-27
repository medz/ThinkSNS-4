<?php

tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
/**
 * 后台用户组管理.
 *
 * @author jason
 */
class UserGroupAction extends AdministratorAction
{
    public $pageTitle = array(
                            'index'        => '用户组管理',
                            'addUsergroup' => '编辑用户组',
                            );

    public function _initialize()
    {
        $this->pageTitle['index'] = L('PUBLIC_USER_GROUP_MANAGEMENT');
        $this->pageTitle['addUsergroup'] = L('PUBLIC_EDIT_USER_GROUP');
        parent::_initialize();
    }

    public function index()
    {

         // 页面具有的字段，可以移动到配置文件中！！！
        $this->pageKeyList = array('user_group_id', 'app_name', 'user_group_name', 'user_group_type', 'user_group_icon', 'is_authenticate', 'DOACTION');

        $this->pageButton[] = array('title' => L('PUBLIC_ADD_USER_GROUP'), 'onclick' => 'admin.addUserGroup()');
        // $this->pageButton[] = array('title'=>L('PUBLIC_DELETE_USER_GROUP'),'onclick'=>"admin.delUserGroup(this)");

        $list = model('UserGroup')->findPage(10);

        foreach ($list['data'] as &$value) {
            $value['user_group_type'] = empty($value['user_group_type']) ? L('PUBLIC_ORDINARY') : L('PUBLIC_SPECIAL');
            $value['user_group_icon'] = $value['user_group_icon'] != '-1' ? '<img src="'.THEME_PUBLIC_URL.'/image/usergroup/'.$value['user_group_icon'].'">' : '';
            $value['is_authenticate'] = $value['is_authenticate'] == 1 ? '是' : '否';
            $value['DOACTION'] = "<a href='".U('admin/UserGroup/addUsergroup', array('user_group_id' => $value['user_group_id']))."'>".L('PUBLIC_EDIT').'</a>&nbsp;-&nbsp;';
            $value['DOACTION'] .= "<a href='".U('admin/Config/permissionset', array('gid' => $value['user_group_id']))."'>".L('PUBLIC_PERMISSION_GROUP_CONFIGURATION').'</a>&nbsp;';
            if ($value['user_group_id'] > 6) {
                $value['DOACTION'] .= "<a href='javascript:void(0)' onclick=\"admin.delUserGroup(this,'{$value['user_group_id']}')\">".L('PUBLIC_STREAM_DELETE').'</a> ';
            }
        }

        $this->_listpk = 'user_group_id';
        $this->allSelected = false;
        $this->displayList($list);
    }

    public function addUsergroup()
    {
        if (!empty($_POST)) { //添加&编辑积分类型

         //   dump($_POST);exit;
            $res = model('UserGroup')->addUsergroup($_POST);
            if ($res) {
                //TODO 记录知识
                $this->assign('jumpUrl', U('admin/UserGroup/index'));
                $this->success();
            } else {
                $this->error(L('PUBLIC_SAVE_FAIL'));
            }
        }

        $this->pageKeyList = array('user_group_id', 'user_group_name', 'user_group_icon', 'user_group_type', 'is_authenticate');

        $this->opt['user_group_type'] = array(0 => L('PUBLIC_ORDINARY'), 1 => L('PUBLIC_SPECIAL'));
        $this->opt['is_authenticate'] = array(1 => '是', 0 => '否');

        $dirs = new Dir(THEME_PUBLIC_PATH.'/image/usergroup');
        $dirs = $dirs->toArray();
//      $icons = array('-1'=>'无');
        $icons = array('-1' => L('PUBLIC_NO_MORE_INFO'));
        foreach ($dirs as $k => $v) {
            $icons[$v['filename']] = "<img src='".THEME_PUBLIC_URL.'/image/usergroup/'.$v['filename']."'>";
        }

        $this->opt['user_group_icon'] = $icons;

        $this->savePostUrl = U('admin/UserGroup/addUsergroup');

        $detailData = array();

        if (!empty($_REQUEST['user_group_id'])) {
            $map['user_group_id'] = $_REQUEST['user_group_id'];
            $detailData = model('UserGroup')->where($map)->find();
        } else {
            $this->pageTitle[ACTION_NAME] = L('PUBLIC_ADD_USER_GROUP');
        }

        $this->onsubmit = 'admin.checkUserGroup(this)';
        $this->displayConfig($detailData);
    }

    //删除用户
    public function delgroup()
    {
        $return = array('status' => 1, 'data' => L('PUBLIC_DELETE_SUCCESS'));
        if (empty($_POST['gid'])) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_USERGROUP_ISNOT');
            echo json_encode($return);
            exit();
        }
        if (!model('UserGroup')->delUsergroup($_POST['gid'])) {
            $return['data'] = L('PUBLIC_DELETE_FAIL');
        } else {
            //TODO 记录操作知识
        }
        echo json_encode($return);
        exit();
    }
}
