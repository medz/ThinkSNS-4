<?php
/**
 * 邀请控制器.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class InviteAction extends Action
{
    private $_invite_model;
    private $_invite_config;
    private $_register_config;
    private $_profile_model; // 用户档案模型对象字段

    public function _initialize()
    {
        // 获取后台注册配置
        $this->_register_config = model('Xdata')->get('admin_Config:register');
        $registerType = $this->_register_config['register_type'];
        // 获取后台邀请配置
        $this->_invite_config = model('Xdata')->get('admin_Config:invite');

        if (!in_array($registerType, array('open', 'invite'))) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                exit($this->ajaxReturn(null, '您没有邀请权限', 0));
            } else {
                exit(redirect(U('square/Index/index')));
            }
        }
        $this->_invite_model = model('Invite');

        $this->_profile_model = model('UserProfile');
        // 从数据库读取
        $profile_category_list = $this->_profile_model->getCategoryList();

        $tab_list[] = array(
            'field_key'  => 'index',
            'field_name' => L('PUBLIC_PROFILESET_INDEX'),
        ); // 基本资料
        $tab_list[] = array(
            'field_key'  => 'tag',
            'field_name' => L('PUBLIC_PROFILE_TAG'),
        ); // 基本资料
        $tab_lists = $profile_category_list;

        foreach ($tab_lists as $v) {
            $tab_list[] = $v; // 后台添加的资料配置分类
        }
        $tab_list[] = array(
            'field_key'  => 'avatar',
            'field_name' => L('PUBLIC_IMAGE_SETTING'),
        ); // 头像设置
        $tab_list[] = array(
            'field_key'  => 'domain',
            'field_name' => L('PUBLIC_DOMAIN_NAME'),
        ); // 个性域名
        $tab_list[] = array(
            'field_key'  => 'authenticate',
            'field_name' => '申请认证',
        ); // 申请认证
        $tab_list_score[] = array(
            'field_key'  => 'scoredetail',
            'field_name' => L('积分规则'),
        ); // 积分规则
        $tab_list_preference[] = array(
            'field_key'  => 'privacy',
            'field_name' => L('PUBLIC_PRIVACY'),
        ); // 隐私设置
        $tab_list_preference[] = array(
            'field_key'  => 'notify',
            'field_name' => '通知设置',
        ); // 通知设置
        $tab_list_preference[] = array(
            'field_key'  => 'blacklist',
            'field_name' => '黑名单',
        ); // 黑名单
        $tab_list_security[] = array(
            'field_key'  => 'security',
            'field_name' => L('PUBLIC_ACCOUNT_SECURITY'),
        ); // 帐号安全
        // 插件增加菜单
        $tab_list_security[] = array(
            'field_key'  => 'bind',
            'field_name' => '帐号绑定',
        ); // 帐号绑定

        $tab_list_invite[] = array(
            'field_key'  => 'invite',
            'field_name' => '邮件邀请',
        ); // 邮件邀请

        $tab_list_invite[] = array(
            'field_key'  => 'linvite',
            'field_name' => '链接邀请',
        ); // 链接邀请

        $this->assign('tab_list', $tab_list);
        $this->assign('tab_list_score', $tab_list_score);
        $this->assign('tab_list_preference', $tab_list_preference);
        $this->assign('tab_list_security', $tab_list_security);
        $this->assign('tab_list_invite', $tab_list_invite);
    }

    /**
     * 邀请页面 - 页面.
     */
    public function invite()
    {
        if (!CheckPermission('core_normal', 'invite_user')) {
            $this->error('对不起，您没有权限进行该操作！');
        }
        $this->_getInviteEmail();
        //若为邀请注册
        if ($this->_register_config['register_type'] == 'invite') {
            //邀请好友积分规则
            $creditRule = model('Credit')->getCreditRuleByName('core_code');
            $applyCredit['score'] = abs($creditRule['score']);
            $applyCredit['experience'] = abs($creditRule['experience']);
            $this->assign('applyCredit', $applyCredit);

            //好友注册成功积分规则
            $_creditRule = model('Credit')->getCreditRuleByName('invite_friend');
            $_applyCredit['score'] = abs($_creditRule['score']);
            $_applyCredit['experience'] = abs($_creditRule['experience']);

            $this->assign('_applyCredit', $_applyCredit);
        } else { //开放注册
            //积分规则
            $creditRule = model('Credit')->getCreditRuleByName('invite_friend');
            $applyCredit['score'] = abs($creditRule['score']);
            $applyCredit['experience'] = abs($creditRule['experience']);

            $this->assign('applyCredit', $applyCredit);
        }

        // 后台配置邮件邀请数目
        $this->assign('emailNum', $this->_invite_config['send_email_num']);
        // 注册配置
        $this->assign('registerType', $this->_register_config['register_type']);

        $this->display('invite');
    }

    /**
     * 邀请页面 - 页面.
     */
    public function linvite()
    {
        if (!CheckPermission('core_normal', 'invite_user')) {
            $this->error('对不起，您没有权限进行该操作！');
        }
        $this->_getInviteLink();
        //若为邀请注册
        if ($this->_register_config['register_type'] == 'invite') {
            //邀请好友积分规则
            $creditRule = model('Credit')->getCreditRuleByName('core_code');
            $applyCredit['score'] = abs($creditRule['score']);
            $applyCredit['experience'] = abs($creditRule['experience']);

            //好友注册成功积分规则
            $_creditRule = model('Credit')->getCreditRuleByName('invite_friend');
            $_applyCredit['score'] = abs($_creditRule['score']);
            $_applyCredit['experience'] = abs($_creditRule['experience']);

            $this->assign('_applyCredit', $_applyCredit);
        } else { //开放注册
            //积分规则
            $creditRule = model('Credit')->getCreditRuleByName('invite_friend');
            $applyCredit['score'] = abs($creditRule['score']);
            $applyCredit['experience'] = abs($creditRule['experience']);

            $this->assign('applyCredit', $applyCredit);
        }

        // 后台配置邮件邀请数目
        $this->assign('emailNum', $this->_invite_config['send_email_num']);
        // 注册配置
        $this->assign('registerType', $this->_register_config['register_type']);
        $this->display('linvite');
    }

    /**
     * 邮箱邀请相关数据.
     */
    private function _getInviteEmail()
    {
        // 获取邮箱后缀
        $config = model('Xdata')->get('admin_Config:register');
        $this->assign('emailSuffix', $config['email_suffix']);
        // 获取已邀请用户信息
        $inviteList = $this->_invite_model->getInviteUserList($this->mid, 'email');
        $this->assign('inviteList', $inviteList);
        // 获取有多少可用的邀请码
        $count = $this->_invite_model->getAvailableCodeCount($this->mid, 'email');
        $this->assign('count', $count);
    }

    /**
     * 链接邀请相关数据.
     */
    private function _getInviteLink()
    {
        // 获取邀请码列表
        $codeList = $this->_invite_model->getInviteCode($this->mid, 'link');
        $this->assign('codeList', $codeList);
        // 获取已邀请用户信息
        $inviteList = $this->_invite_model->getInviteUserList($this->mid, 'link');
        $this->assign('inviteList', $inviteList);
        // 获取有多少可用的邀请码
        $count = $this->_invite_model->getAvailableCodeCount($this->mid, 'link');
        $this->assign('count', $count);
    }

    /**
     * 邀请页面 - 弹窗.
     */
    public function inviteBox()
    {
        $userInfo = model('User')->getUserInfo($this->mid);
        $this->assign('invite', $userInfo);
        $this->assign('config', model('Xdata')->get('admin_Config:register'));
        $this->display();
    }

    /**
     * 邀请操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doInvite()
    {
        if (!CheckPermission('core_normal', 'invite_user')) {
            return false;
        }
        $email = t($_POST['email']);
        $detial = !isset($_POST['detial']) ? L('PUBLIC_INVATE_MESSAGE', array('uname' => $GLOBALS['ts']['user']['uname'])) : h($_POST['detial']);            // Hi，我是 {uname}，我发现了一个很不错的网站，我在这里等你，快来加入吧。
        $map['inviter_uid'] = $this->mid;
        $map['ctime'] = time();
        // 发送邮件邀请
        $result = model('Invite')->doInvite($email, $detial, $this->mid);
        $this->ajaxReturn(null, model('Invite')->getError(), $result);
    }

    /**
     * 验证邮箱地址是否可用.
     *
     * @return json 验证后的相关数据
     */
    public function checkInviteEmail()
    {
        $email = t($_POST['email']);
        $result = model('Register')->isValidEmail($email);
        $this->ajaxReturn(null, model('Register')->getLastError(), $result);
    }

    /**
     * 获取邀请码接口.
     *
     * @return json 操作后的相关数据
     */
    public function applyInviteCode()
    {
        // 获取相关数据
        $uid = intval($_POST['uid']);
        $type = t($_POST['type']);
        $result = $this->_invite_model->applyInviteCode($uid, $type);
        $res = array();
        if ($result) {
            $res['status'] = true;
            $res['info'] = '邀请码领取成功';
        } else {
            $res['status'] = false;
            $res['info'] = '邀请码领取失败';
        }

        exit(json_encode($res));
    }
}
