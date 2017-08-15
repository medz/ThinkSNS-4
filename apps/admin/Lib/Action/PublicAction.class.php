<?php
/**
 * 后台�
 * ��
 * �方法.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class PublicAction extends AdministratorAction
{
    public function _initialize()
    {
        if (!in_array(ACTION_NAME, array('login', 'doLogin', 'logout', 'selectDepartment'))) {
            parent::_initialize();
        }
        $this->assign('isAdmin', 1);    //是否后台
    }

    /**
     * 登录
     * Enter description here ...
     */
    public function login()
    {
        if ($_SESSION['adminLogin']) {
            redirect(U('admin/Index/index'));
            exit();
        }
        $this->setTitle(L('ADMIN_PUBLIC_LOGIN'));
        $this->display();
    }

    public function doLogin()
    {
        //检查验证码
        if (md5(strtoupper($_POST['verify'])) != $_SESSION['verify']) {
            $this->error('验证码错误');
        }
        $login = model('Passport')->adminLogin();
        if ($login) {
            if (CheckPermission('core_admin', 'admin_login')) {
                $this->success(L('PUBLIC_LOGIN_SUCCESS'));
            } else {
                $this->assign('jumpUrl', SITE_URL);
                $this->error(L('PUBLIC_NO_FRONTPLATFORM_PERMISSION_ADMIN'));
            }
        } else {
            unset($_SESSION['verify']);
            session_destroy($_SESSION['verify']);
            $this->error(model('Passport')->getError());
        }
    }

    /**
     * 退出登录
     * Enter description here ...
     */
    public function logout()
    {
        model('Passport')->adminLogout();
        U('admin/Public/login', '', true);
    }

    /**
     * 通用部门选择数据接口.
     */
    public function selectDepartment()
    {
        $return = array('status' => 1, 'data' => '');

        if (empty($_POST['pid'])) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_SYSTEM_CATEGORY_ISNOT');
            echo json_encode($return);
            exit();
        }

        $_POST['pid'] = intval($_POST['pid']);
        $_POST['sid'] = intval($_POST['sid']);
        $ctree = model('Department')->getDepartment($_POST['pid']);
        if (empty($ctree['_child'])) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_SYSTEM_SONCATEGORY_ISNOT');
        } else {
            $return['data'] = "<select name='_parent_dept_id[]' onchange='admin.selectDepart(this.value,$(this))' id='_parent_dept_{$_POST['pid']}'>";
            $return['data'] .= "<option value='-1'>".L('PUBLIC_SYSTEM_SELECT').'</option>';
            $sid = !empty($_POST['sid']) ? $_POST['sid'] : '';
            foreach ($ctree['_child'] as $key => $value) {
                $return['data'] .= "<option value='{$value['department_id']}' ".($value['department_id'] == $sid ? " selected='selected'" : '').">{$value['title']}</option>";
            }
            $return['data'] .= '</select>';
        }
        echo json_encode($return);
        exit();
    }

    /*** 分类模板接口 ***/

    /**
     * 移动分类顺序API.
     *
     * @return json 返回相�
     * �的JSON信息
     */
    public function moveTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $type = t($_POST['type']);
        $stable = t($_POST['stable']);
        $result = model('CategoryTree')->setTable($stable)->moveTreeCategory($cid, $type);
        // 处理返回结果
        if ($result) {
            $res['status'] = 1;
            $res['data'] = '分类排序成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '分类排序失败';
        }

        exit(json_encode($res));
    }

    /**
     * 添加分类窗口API.
     */
    public function addTreeCategory()
    {
        $cid = intval($_GET['cid']);
        $this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);

        $this->display('categoryBox');
    }

    /**
     * 添加分类操作API.
     *
     * @return json 返回相�
     * �的JSON信息
     */
    public function doAddTreeCategory()
    {
        $pid = intval($_POST['pid']);
        $title = t($_POST['title']);
        $stable = t($_POST['stable']);
        $data['attach_id'] = intval($_POST['attach_id']);
        $result = model('CategoryTree')->setTable($stable)->addTreeCategory($pid, $title, $data);
        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['data'] = '添加分类成功';
            S('api_discover_system', null);
        } else {
            $res['status'] = 0;
            $res['data'] = '添加分类失败';
        }

        exit(json_encode($res));
    }

    /**
     * 编辑分类窗口API.
     */
    public function upTreeCategory()
    {
        $cid = intval($_GET['cid']);
        $this->assign('pid', $cid);
        $stable = t($_GET['stable']);
        $this->assign('stable', $stable);
        $limit = intval($_GET['limit']);
        $this->assign('limit', $limit);
        $isAttach = t($_GET['attach']);
        $this->assign('isAttach', $isAttach);
        // 获取该分类的信息
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        if (isset($category['attach_id']) && !empty($category['attach_id'])) {
            $attach = model('Attach')->getAttachById($category['attach_id']);
            $this->assign('attach', $attach);
        }
        $this->assign('category', $category);

        $this->display('categoryBox');
    }

    /**
     * 编辑分类操作API.
     *
     * @return json 返回相�
     * �的JSON信息
     */
    public function doUpTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $title = t($_POST['title']);
        $stable = t($_POST['stable']);
        if ($_POST['attach_id'] != 'NaN') {
            $data['attach_id'] = intval($_POST['attach_id']);
        }
        $result = model('CategoryTree')->setTable($stable)->upTreeCategory($cid, $title, $data);
        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['data'] = '编辑分类成功';
            S('api_discover_system', null);
        } else {
            $res['status'] = 0;
            $res['data'] = '编辑分类失败，编辑的名称可能已经存在于当前级别。';
        }

        exit(json_encode($res));
    }

    /**
     * 删除分类API.
     *
     * @return json 返回相�
     * �的JSON信息
     */
    public function rmTreeCategory()
    {
        $cid = intval($_POST['cid']);
        $stable = t($_POST['stable']);
        $app = t($_POST['_app']);
        $module = t($_POST['_module']);
        $method = t($_POST['_method']);
        $result = model('CategoryTree')->setApp($app)->setTable($stable)->rmTreeCategory($cid, $module, $method);
        $msg = model('CategoryTree')->setApp($app)->setTable($stable)->getMessage();
        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['data'] = $msg;
            S('api_discover_system', null);
        } else {
            $res['status'] = 0;
            $res['data'] = $msg;
        }

        exit(json_encode($res));
    }

    /**
     * 设置分类�
     * �置页面.
     */
    public function setCategoryConf()
    {
        unset($_GET['is_weixin']);
        $cid = intval($_GET['cid']);
        $stable = t($_GET['stable']);
        $ext = t($_GET['ext']);
        $ext = urldecode($ext);
        $category = model('CategoryTree')->setTable($stable)->getCategoryById($cid);
        // 设置标题
        $pageTitle = '分类配置&nbsp;-&nbsp;'.$category['title'];
        $this->assign('pageTitle', $pageTitle);
        // 页面字段配置存在system_data表中的页面唯一key值
        $this->pageKey = 'category_conf_'.$stable;
        // 配置项字段设置
        $ext = array_map('t', $_GET); //需要过滤
        unset($ext['app']);
        unset($ext['mod']);
        unset($ext['act']);
        unset($ext['cid']);
        unset($ext['stable']);
        unset($ext['desc']);
        $pageKeyList = array();
        $data = array();
        foreach ($ext as $key => $val) {
            $fields = explode('_', $key);
            $fields[] = $val;
            $data[$fields[1]][$fields[0]] = (strpos($fields[2], '-') === false) ? $fields[2] : explode('-', $fields[2]);
        }
        foreach ($data as $value) {
            $pageKeyList[] = $value['ext'];
            isset($value['arg']) && $this->opt[$value['ext']] = $value['arg'];
            isset($value['def']) && $detailData[$value['ext']] = $value['def'];
            $this->assign('defaultS', $value['def']);
        }
        $this->pageKeyList = $pageKeyList;
        // 提交表单URL设置
        $this->savePostUrl = U('admin/Public/doSetCategoryConf', array('cid' => $cid, 'stable' => $stable));
        // 获取配置信息
        $extend = empty($category['ext']) ? $detailData : unserialize($category['ext']);

        $this->displayConfig($extend);
    }

    /**
     * 存储分类�
     * �置操作.
     */
    public function doSetCategoryConf()
    {
        $cid = intval($_GET['cid']);
        $stable = t($_GET['stable']);
        // 去除多余的数据
        $data = $_POST;
        unset($data['systemdata_list']);
        unset($data['systemdata_key']);
        unset($data['pageTitle']);
        unset($data['avoidSubmitByReturn']);
        foreach ($data as &$value) {
            $value = t($value);
        }
        $result = model('CategoryTree')->setTable($stable)->doSetCategoryConf($cid, $data);
        if ($result) {
            $this->success('分类配置成功');
        } else {
            $this->error('分类配置失败');
        }
    }

    //发送测试邮件
    public function test_email()
    {
        //$data['sendto_email'] = t($_POST['sendto_email']);
        $data = $_POST;
        $result = model('Mail')->test_email($data);
        if ($result === false) {
            echo model('Mail')->message;
        } else {
            echo 1;
        }
    }

    /**
     * 删除用户脏数据（昵称重复）.
     */
    public function delTrashUser()
    {
        $sql = 'SELECT `uname` FROM `ts_user` GROUP BY `uname` HAVING count(`uname`) >1';
        $rs = D()->query($sql);
        foreach ($rs as $key => $value) {
            $_rs = D('User')->where(array('uname' => $value['uname']))->select();
            $uids = getSubByKey($_rs, 'uid');
            $pos = array_search(min($uids), $uids);
            unset($uids[$pos]);
            D('User')->trueDeleteUsers($uids);
        }
    }
}
