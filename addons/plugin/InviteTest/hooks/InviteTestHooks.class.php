<?php
/**
 * 天气预报钩子
 * @author 程序_小时代
 * @version TS3.0
 */
class InviteTestHooks extends Hooks
{
    protected static $checked = false;

    public function core_filter_init_action()
    {
        $this->check();
    }

    public function check()
    {
        if (APP_NAME == 'admin' || APP_NAME == 'w3g' || CheckPermission('core_admin', 'admin_login')) {
            return ;
        }

        if (self::$checked) {
            return;
        } //避免重复检查
        self::$checked = true;     //标注已经检查

        $uid = intval($_SESSION['mid']); //当前用户
        //获取唯一测试编号，针对局域网IP重复
        $uniqid = t(cookie('_testrand'));
        if (!$uniqid) {
            $uniqid = uniqid();
            cookie('_testrand', $uniqid, 86400 * 365);
        }

        //取得当前的邀请码
        if (!empty($_SESSION['InviteTest'])) {
            $invitecode = $_SESSION['InviteTest'];
        } elseif (!empty($_REQUEST['invitecode'])) {
            $invitecode = t($_REQUEST['invitecode']);
            cookie('invitetest', $invitecode, 86400 * 365);
        }

        $model = $this->model('InviteTest');
        //检查邀请码是否可用
        if (!empty($invitecode)) {
            if (!$model->check($invitecode, $uid, $uniqid)) {
                $this->assign('errorMsg', $model->getError());
                $_SESSION['InviteTest'] = null;
            } else {
                $_SESSION['InviteTest'] = $invitecode;
            }
            $this->assign('invitecode', $invitecode);
        } else {
            $this->assign('invitecode', cookie('invitetest'));
        }

        //没有邀请码
        if (empty($_SESSION['InviteTest'])) {
            $this->assign('config', $model->getConfig());
            $this->display('index');
            exit; //结束后面的代码
        }
    }

    public function invite()
    {
        $model = $this->model('InviteTest');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!empty($_POST['isdel'])) { //删除
                $map['id'] = array('in', t($_POST['id']));
                if ($model->where($map)->delete()) {
                    echo strpos($_POST['id'], ',') === false ? 2 : 1;
                    exit;
                }
                exit;
            }
            if (!empty($_POST['isset'])) { //设置状态
                $map['id'] = array('in', t($_POST['id']));
                $save = array('is_disable' => $_POST['status'] ? 0 : 1);
                if ($model->where($map)->save($save)) {
                    echo strpos($_POST['id'], ',') === false ? 2 : 1;
                    exit;
                }
                exit;
            }
        }
        $data = $model->order('id DESC')->findPage();
        $this->assign('data', $data);
        $this->display('invite');
    }

    public function addinvite()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $model = $this->model('InviteTest');
            $num = $model->create(intval($_POST['number']));
            $this->assign('isAdmin', 1);
            $this->success('成功生成'.$num.'个邀请码');
            exit;
        }
        $this->display('addinvite');
    }

    public function config()
    {
        $model = $this->model('InviteTest');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->assign('isAdmin', 1);
            if ($model->saveConfig($_POST)) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
            exit;
        }
        $this->assign('config', $model->getConfig());
        $this->display('config');
    }
}
