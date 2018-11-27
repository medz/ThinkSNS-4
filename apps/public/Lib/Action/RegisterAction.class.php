<?php
/**
 * RegisterAction 注册模块.
 *
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.0
 */
class RegisterAction extends Action
{
    private $_config; // 注册配置信息字段
    private $_register_model; // 注册模型字段
    private $_user_model; // 用户模型字段
    private $_invite; // 是否是邀请注册
    private $_invite_code; // 邀请码
    private $_default_method = array('changeActivationEmail', 'activate', 'isEmailAvailable', 'isValidVerify', 'isPhoneAvailable', 'isUnameAvailable', 'sendReigterCode', 'resendActivationEmail');

    /**
     * 模块初始化，获取注册配置信息、用户模型对象、注册模型对象、邀请注册与站点头部信息设置.
     */
    protected function _initialize()
    {
        $this->_invite = false;
        // 未激活与未审核用户
        if ($this->mid > 0 && !in_array(ACTION_NAME, $this->_default_method)) {
            $GLOBALS['ts']['user']['is_audit'] == 0 && ACTION_NAME != 'waitForAudit' && U('public/Register/waitForAudit', array('uid' => $this->mid), true);
            $GLOBALS['ts']['user']['is_audit'] == 1 && $GLOBALS['ts']['user']['is_active'] == 0 && ACTION_NAME != 'waitForActivation' && U('public/Register/waitForActivation', array('uid' => $this->mid), true);
            // 激活，审核，初始化过的用户进登录页面跳转到首页
            $GLOBALS['ts']['user']['is_audit'] == 1 && $GLOBALS['ts']['user']['is_active'] == 1 && $GLOBALS['ts']['user']['is_init'] == 1 && U('public/Index/index', '', true);
        }
        // 登录后，将不显示注册页面
        // $this->mid > 0 && $GLOBALS['ts']['user']['is_init'] == 1 && redirect($GLOBALS['ts']['site']['home_url']);

        $this->_config = model('Xdata')->get('admin_Config:register');
        $this->_user_model = model('User');
        $this->_register_model = model('Register');
        $this->setTitle(L('PUBLIC_REGISTER'));
    }

    public function code()
    {
        if (md5(strtoupper($_POST['verify'])) == $_SESSION['verify']) {
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 默认注册页面 - 注册表单页面.
     */
    public function index()
    {
        $this->appCssList[] = 'login.css';

        $type = t($_GET['t']);
        if (!in_array($type, array('email', 'phone'))) {
            $type = 'email';
        }
        $account_type = array();
        if ($this->_config['account_type'] == 'email') {
            $type = 'email';
        } elseif ($this->_config['account_type'] == 'phone') {
            $type = 'phone';
        }
        $this->assign('type', $type);

        $this->assign('account_type', $this->_config['account_type']);

        // 验证是否有钥匙 - 邀请注册问题
        if (empty($this->mid)) {
            if ((isset($_GET['invite']) || $this->_config['register_type'] != 'open') && !in_array(ACTION_NAME, array('isEmailAvailable', 'isUnameAvailable', 'doStep1'))) {
                // 提示信息语言
                $messageHash = array('invite' => '抱歉，本站目前仅支持邀请注册。', 'admin' => '抱歉，本站目前仅支持管理员邀请注册。', 'other' => '抱歉，本站目前仅支持第三方帐号绑定。');
                $message = $messageHash[$this->_config['register_type']];
                if (!isset($_GET['invite'])) {
                    $this->error($message);
                }
                $inviteCode = t($_GET['invite']);
                $status = model('Invite')->checkInviteCode($inviteCode, $this->_config['register_type']);
                if ($status == 1) {
                    $this->_invite = true;
                    $this->_invite_code = $inviteCode;
                } elseif ($status == 2) {
                    $this->error('抱歉，该邀请码已使用。');
                } else {
                    $this->error($message);
                }
            }
        }
        // 若是邀请注册，获取邀请人相关信息
        if ($this->_invite) {
            $inviteInfo = model('Invite')->getInviterInfoByCode($this->_invite_code);
            $this->assign('inviteInfo', $inviteInfo);
        }
        $this->assign('is_invite', $this->_invite);
        $this->assign('invite_code', $this->_invite_code);
        $this->assign('config', $this->_config);
        // $this->assign('invate_key', t($_GET['key']));
        // $this->assign('invate_uid', t($_GET['uid']));

        $this->setTitle('填写注册信息');
        $this->setKeywords('填写注册信息');
        $this->display();
    }

    /**
     * 第三方帐号集成 - 绑定本地帐号.
     */
    public function doBindStep1()
    {
        $email = t($_POST['email']);
        $password = trim($_POST['password']);

        $user = model('Passport')->getLocalUser($email, $password);
        if (isset($user['uid']) && $user['uid'] > 0) {

            //注册来源-第三方帐号绑定
            if (isset($_POST['other_type'])) {
                $other['type'] = t($_POST['other_type']);
                $other['type_uid'] = t($_POST['other_uid']);
                $other['oauth_token'] = t($_POST['oauth_token']);
                $other['oauth_token_secret'] = t($_POST['oauth_token_secret']);
                $other['uid'] = $user['uid'];
                $other['is_sync'] = 0;
                D('Login')->add($other);
                //同步到UCenter
                // if (UC_SYNC) {
                //     model('Passport')->ucBindUser($email, $password, t($_POST['other_type']), t($_POST['other_uid']));
                // }
            } else {
                $this->error('绑定失败，第三方信息不正确');
            }

            //判断是否需要审核
            D('Passport')->loginLocal($email, $password);
            if (isMobile()) {
                $this->assign('jumpUrl', U('w3g/Index/index'));
            } else {
                $this->assign('jumpUrl', U('public/Passport/login'));
            }
            $this->success('恭喜您，绑定成功');
        } else {
            $this->error('绑定失败，请确认帐号密码正确'); // 注册失败
        }
    }

    /**
     * 第三方帐号集成 - 注册新账号.
     */
    public function doOtherStep1()
    {
        $email = t($_POST['email']);
        $uname = t($_POST['uname']);
        $sex = isset($_POST['sex']) ? intval($_POST['sex']) : 1;

        $bindemail = model('AddonData')->get('login:bindemail');

        //直接绑定
        if (!$bindemail && $_POST['direct'] == 1) {
            //邮箱是空的，需要完善邮箱
            $email = null;
            //密码随机的，需要找回密码
            $login_salt = rand(11111, 99999);
            $password = md5(uniqid());
            //如果名字重复加个随机尾数
            if (M('User')->where("uname='{$uname}'")->find()) {
                $uname = $uname.rand(111, 999);
                if (M('User')->where("uname='{$uname}'")->find()) {
                    $this->error($this->_register_model->getLastError());
                }
            }
            //填写资料
        } else {
            if (!$this->_register_model->isValidName($uname)) {
                $this->error($this->_register_model->getLastError());
            }

            if (isset($_POST['email'])) {
                if (!$this->_register_model->isValidEmail($email)) {
                    $this->error($this->_register_model->getLastError());
                }
            }

            $login_salt = rand(11111, 99999);
            $password = trim($_POST['password']);
            $repassword = trim($_POST['repassword']);
            if (!$this->_register_model->isValidPassword($password, $repassword)) {
                $this->error($this->_register_model->getLastError());
            }
        }

        $map['uname'] = $uname;
        $map['sex'] = $sex;
        $map['login_salt'] = $login_salt;
        $map['password'] = md5(md5($password).$login_salt);
        $map['login'] = $email;
        $map['reg_ip'] = get_client_ip();
        $map['ctime'] = time();

        // 添加地区信息
        $map['location'] = t($_POST['city_names']);
        $cityIds = t($_POST['city_ids']);
        $cityIds = explode(',', $cityIds);
        isset($cityIds[0]) && $map['province'] = intval($cityIds[0]);
        isset($cityIds[1]) && $map['city'] = intval($cityIds[1]);
        isset($cityIds[2]) && $map['area'] = intval($cityIds[2]);

        if (!isset($map['city']) or !$map['city']) {
            $map['city'] = 0;
        }
        if (!isset($map['area']) or !$map['area']) {
            $map['area'] = 0;
        }
        if (!isset($map['is_del']) or !$map['is_del']) {
            $map['is_del'] = 0;
        }

        // 审核状态： 0-需要审核；1-通过审核
        $map['is_audit'] = $this->_config['register_audit'] ? 0 : 1;
        $map['is_active'] = $this->_config['need_active'] ? 0 : 1;
        // $map['is_init'] = 1;

        // 非强制绑定时，直接激活
        if (!$bindemail) {
            $map['is_active'] = 1;
        }
        $map['first_letter'] = getFirstLetter($uname);

        //如果包含中文将中文翻译成拼音
        if (preg_match('/[\x7f-\xff]+/', $map['uname'])) {
            //昵称和呢称拼音保存到搜索字段
            $map['search_key'] = $map['uname'].' '.model('PinYin')->Pinyin($map['uname']);
        } else {
            $map['search_key'] = $map['uname'];
        }

        $map['domain'] = '';

        $uid = $this->_user_model->add($map);
        if ($uid) {

            //保存头像
            if ($_POST['avatar'] == 1) {
                model('Avatar')->saveRemoteAvatar(t($_POST['other_face']), $uid);
            }

            // 添加积分
            model('Credit')->setUserCredit($uid, 'init_default');

            // 添加至默认的用户组
            $registerConfig = model('Xdata')->get('admin_Config:register');
            $userGroup = empty($registerConfig['default_user_group']) ? C('DEFAULT_GROUP_ID') : $registerConfig['default_user_group'];
            model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));

            // 注册来源-第三方帐号绑定
            if (isset($_POST['other_type'])) {
                $other['type'] = t($_POST['other_type']);
                $other['type_uid'] = t($_POST['other_uid']);
                $other['oauth_token'] = t($_POST['oauth_token']);
                $other['oauth_token_secret'] = t($_POST['oauth_token_secret']);
                $other['uid'] = $uid;
                $other['is_sync'] = 0;
                D('login')->add($other);

                //同步到UCenter
                // if (UC_SYNC) {
                //     model('Passport')->ucBindUser($uname, $password, t($_POST['other_type']), t($_POST['other_uid']));
                // }
            }

            //登录
            model('Passport')->loginLocalWithoutPassword($uname);

            //判断是否需要审核
            if ($this->_config['register_audit'] && !$map['is_audit']) {
                $this->redirect('public/Register/waitForAudit', array('uid' => $uid));
            } else {
                if ($this->_config['need_active'] && !$map['is_active']) {
                    $this->_register_model->sendActivationEmail($uid);
                    $this->redirect('public/Register/waitForActivation', array('uid' => $uid));
                } else {
                    if (!isMobile()) {
                        D('Passport')->loginLocal($email, $password);
                        $this->redirect('public/Index/index');
                    } else {
                        $this->assign('jumpUrl', U('w3g/Index/index'));
                        $this->success('操作成功');
                    }
                }
            }
        } else {
            $this->error(L('PUBLIC_REGISTER_FAIL')); // 注册失败
        }
    }

    /**
     * 注册流程 - 执行第一步骤.
     */
    public function doStep1()
    {
        $regType = t($_POST['regType']);
        if (!in_array($regType, array('email', 'phone'))) {
            $this->error('注册参数错误');
        }

        // 增强注册验证方式
        if ($this->_config['account_type'] == 'email') {
            $regType = 'email';
        } elseif ($this->_config['account_type'] == 'phone') {
            $regType = 'phone';
        }

        $invite = t($_POST['invate']);
        $inviteCode = t($_POST['invate_key']);
        $email = t($_POST['email']);
        $phone = t($_POST['phone']);
        $regCode = t($_POST['regCode']);
        $uname = t($_POST['uname']);
        $sex = 1 == $_POST['sex'] ? 1 : 2;
        $password = trim($_POST['password']);
        $repassword = trim($_POST['repassword']);
        if (!$this->_register_model->isValidPassword($password, $repassword)) {
            $this->error($this->_register_model->getLastError());
        }
        if ($regType === 'email') {
            //检查验证码
            if (md5(strtoupper($_POST['verify'])) != $_SESSION['verify'] && false) {
                //已关闭
                $this->error('验证码错误');
            }

            if (!$this->_register_model->isValidName($uname)) {
                $this->error($this->_register_model->getLastError());
            }

            if (!$this->_register_model->isValidEmail($email)) {
                $this->error($this->_register_model->getLastError());
            }
        } elseif ($regType === 'phone') {

            /* # 验证手机号码 or 验证用户名 */
            if (!$this->_register_model->isValidPhone($phone) or !$this->_register_model->isValidName($uname)) {
                $this->error($this->_register_model->getLastError);

                /* # 验证手机验证码 */
            } elseif (($sms = model('Sms')) and !$sms->CheckCaptcha($phone, $regCode)) {
                $this->error($sms->getMessage());
            }
            unset($sms);

            $this->_config['register_audit'] = 0;
            $this->_config['need_active'] = 0;
        }

        $login_salt = rand(11111, 99999);
        $map['uname'] = $uname;
        $map['sex'] = $sex;
        $map['login_salt'] = $login_salt;
        $map['password'] = md5(md5($password).$login_salt);
        if ($regType === 'email') {
            $map['email'] = $email;
            $login = $email;
        } elseif ($regType === 'phone') {
            $map['phone'] = $phone;
            $login = $phone;
        } else {
            $login = $uname;
        }
        $map['reg_ip'] = get_client_ip();
        $map['ctime'] = time();

        // 审核状态： 0-需要审核；1-通过审核
        $map['is_audit'] = $this->_config['register_audit'] ? 0 : 1;

        // 需求添加 - 若后台没有填写邮件配置，将直接过滤掉激活操作
        $isActive = $this->_config['need_active'] ? 0 : 1;

        if ($isActive == 0) {
            $emailConf = model('Xdata')->get('admin_Config:email');
            if (empty($emailConf['email_host']) || empty($emailConf['email_account']) || empty($emailConf['email_password'])) {
                $isActive = 1;
            }
        }
        $map['is_active'] = $isActive;
        $map['first_letter'] = getFirstLetter($uname);
        //如果包含中文将中文翻译成拼音
        if (preg_match('/[\x7f-\xff]+/', $map['uname'])) {
            //昵称和呢称拼音保存到搜索字段
            $map['search_key'] = $map['uname'].' '.model('PinYin')->Pinyin($map['uname']);
        } else {
            $map['search_key'] = $map['uname'];
        }

        $map['domain'] = '';
        $map['city'] = 0;
        $map['area'] = 0;
        $map['is_del'] = 0;

        $uid = $this->_user_model->add($map);

        if ($uid) {
            // 添加积分
            model('Credit')->setUserCredit($uid, 'init_default');
            // 如果是邀请注册，则邀请码失效
            if ($invite) {
                $receiverInfo = model('User')->getUserInfo($uid);
                //验证码使用
                model('Invite')->setInviteCodeUsed($inviteCode, $receiverInfo);
                //添加用户邀请码字段
                model('User')->where('uid='.$uid)->setField('invite_code', $inviteCode);
                //邀请人操作
                $codeInfo = model('Invite')->getInviteCodeInfo($inviteCode);
                $inviteUid = $codeInfo['inviter_uid'];
                //添加积分
                if ($this->_config['register_type'] == 'open') {
                    model('Credit')->setUserCredit($codeInfo['inviter_uid'], 'invite_friend');
                }
                // 相互关注操作
                model('Follow')->doFollow($uid, intval($inviteUid));
                model('Follow')->doFollow(intval($inviteUid), $uid);
                // 发送通知
                $config['name'] = $receiverInfo['uname'];
                $config['space_url'] = $receiverInfo['space_url'];
                model('Notify')->sendNotify($inviteUid, 'register_invate_ok', $config);
                if ($this->_config['welcome_notify']) {
                    model('Notify')->sendNotify($uid, 'register_welcome', $config);
                }
                //清除缓存
                $this->_user_model->cleanCache($uid);
            }

            // 添加至默认的用户组
            $userGroup = model('Xdata')->get('admin_Config:register');
            $userGroup = empty($userGroup['default_user_group']) ? C('DEFAULT_GROUP_ID') : $userGroup['default_user_group'];
            model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));

            //注册来源-第三方帐号绑定
            if (isset($_POST['other_type'])) {
                $other['type'] = t($_POST['other_type']);
                $other['type_uid'] = t($_POST['other_uid']);
                $other['oauth_token'] = t($_POST['oauth_token']);
                $other['oauth_token_secret'] = t($_POST['oauth_token_secret']);
                $other['uid'] = $uid;
                $other['is_sync'] = 0;
                D('login')->add($other);
            }
            //判断是否需要审核
            if ($this->_config['register_audit']) {
                $this->redirect('public/Register/waitForAudit', array('uid' => $uid));
            } else {
                if (!$isActive) {
                    $this->_register_model->sendActivationEmail($uid);
                    $this->redirect('public/Register/waitForActivation', array('uid' => $uid));
                } else {
                    D('Passport')->loginLocal($login, $password);
                    // //注册后需要登录
                    // $this->assign('jumpUrl', U('public/Passport/login'));
                    //直接跳到初始化页面
                    // $this->assign('jumpUrl', U('public/Register/step2'));
                    // $this->success('恭喜您，注册成功');
                    if ($this->_config['personal_open'] == 1) {
                        $this->redirect('public/Register/step2');
                    } else {
                        $this->assign('jumpUrl', U('public/Index/index'));
                        $this->success('恭喜您，注册成功');
                    }
                }
            }
        } else {
            $this->error(L('PUBLIC_REGISTER_FAIL')); // 注册失败
        }
    }

    /**
     * 等待审核页面.
     */
    public function waitForAudit()
    {
        $user_info = $this->_user_model->where("uid={$this->uid}")->find();
        $email = model('Xdata')->getConfig('sys_email', 'site');
        if (!$user_info || $user_info['is_audit']) {
            $this->redirect('public/Passport/login');
        }
        $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
        foreach ($touid as $k => $v) {
            model('Notify')->sendNotify($v['uid'], 'register_audit');
        }
        $this->assign('email', $email);
        $this->setTitle('帐号等待审核');
        $this->setKeywords('帐号等待审核');
        $this->display();
    }

    /**
     * 等待激活页面.
     */
    public function waitForActivation()
    {
        $this->appCssList[] = 'login.css';
        $user_info = $this->_user_model->where("uid={$this->uid}")->find();
        // 判断用户信息是否存在
        if ($user_info) {
            if ($user_info['is_audit'] == '0') {
                // 审核
                exit(U('public/Register/waitForAudit', array('uid' => $this->uid), true));
            } elseif ($user_info['is_active'] == '1') {
                // 激活
                exit(U('public/Register/step2', array(), true));
            }
        } else {
            // 注册
            $this->redirect('public/Passport/login');
        }

        $email_site = 'http://mail.'.preg_replace('/[^@]+@/', '', $user_info['email']);

        $this->assign('email_site', $email_site);
        $this->assign('email', $user_info['email']);
        $this->assign('config', $this->_config);
        $this->setTitle('等待激活帐号');
        $this->setKeywords('等待激活帐号');
        $this->display();
    }

    /**
     * 发送激活邮件.
     */
    public function resendActivationEmail()
    {
        $res = $this->_register_model->sendActivationEmail($this->uid);
        $this->ajaxReturn(null, $this->_register_model->getLastError(), $res);
    }

    /**
     * 修改激活邮箱.
     */
    public function changeActivationEmail()
    {
        $email = t($_POST['email']);
        // 验证邮箱是否为空
        if (!$email) {
            $this->ajaxReturn(null, '邮箱不能为空！', 0);
        }
        // 验证邮箱格式
        $checkEmail = $this->_register_model->isValidEmail($email);
        if (!$checkEmail) {
            $this->ajaxReturn(null, $this->_register_model->getLastError(), 0);
        }
        $res = $this->_register_model->changeRegisterEmail($this->mid, $email);
        $res && $this->_register_model->sendActivationEmail($this->mid);
        $this->ajaxReturn(null, $this->_register_model->getLastError(), $res);
    }

    /**
     * 通过链接激活帐号.
     */
    public function activate()
    {
        $user_info = $this->_user_model->getUserInfo($this->uid);

        $this->assign('user', $user_info);

        if (!$user_info || $user_info['is_active']) {
            $this->redirect('public/Passport/login');
        }

        $active = $this->_register_model->activate($this->uid, t($_GET['code']));

        if ($active) {
            // 登陆
            model('Passport')->loginLocalWithoutPassword($user_info['email']);
            $this->setTitle('成功激活帐号');
            $this->setKeywords('成功激活帐号');
            // 跳转下一步
            $this->assign('jumpUrl', U('public/Register/step2'));
            $this->success($this->_register_model->getLastError());
        } else {
            $this->redirect('public/Passport/login');
            $this->error($this->_register_model->getLastError());
        }
    }

    /**
     * 第二步注册.
     */
    public function step2()
    {
        //未登录
        empty($_SESSION['mid']) && $this->redirect('public/Passport/login');

        $required = $this->_config['personal_required'];
        $this->assign('required', $required);

        if (in_array('face', $this->_config['personal_required'])) {
            $this->assign('skip', 'false');
        }
        $this->setTitle('上传头像');
        $this->setKeywords('上传头像');
        $this->display();
    }

    public function doStep2()
    {
        $required = $this->_config['personal_required'];
        if (in_array('face', $required) && !model('Avatar')->hasAvatar()) {
            $this->ajaxReturn(null, '想跳过，没门！请上传头像', 0);
        } else {
            $this->ajaxReturn(null, '', 1);
        }
    }

    /**
     * 注册流程 - 第三步骤
     * 设置个人兴趣.
     */
    public function step3()
    {
        //未登录
        empty($_SESSION['mid']) && $this->redirect('public/Passport/login');

        $this->setTitle('填写基本信息');
        $this->setKeywords('填写基本信息');
        if (in_array('tag', $this->_config['personal_required']) || in_array('location', $this->_config['personal_required']) || in_array('intro', $this->_config['personal_required'])) {
            $this->assign('skip', 'false');
        }
        $registerConfig = model('Xdata')->get('admin_Config:register');
        $this->assign('tag_num', $registerConfig['tag_num']);
        $this->display();
    }

    /**
     * 注册流程 - 执行第三步骤
     * 添加标签.
     */
    public function doStep3()
    {
        $required = $this->_config['personal_required'];
        if (in_array('location', $required) && empty($_POST['city_names'])) {
            $this->ajaxReturn(null, '想跳过，没门！请选择地区', 0);
        }
        if (in_array('tag', $required) && empty($_POST['user_tags'])) {
            $this->ajaxReturn(null, '想跳过，没门！请选择标签', 0);
        }
        if (in_array('intro', $required) && empty($_POST['intro'])) {
            $this->ajaxReturn(null, '想跳过，没门！请填写简介', 0);
        }

        $data['sex'] = intval($_POST['sex']);

        $data['location'] = t($_POST['city_names']);
        $cityIds = t($_POST['city_ids']);
        $cityIds = explode(',', $cityIds);
        if ($_POST['input_city'] != '') {
            isset($cityIds[0]) && $data['province'] = intval($cityIds[0]);
            $data['input_city'] = t($_POST['input_city']);
            $data['city'] = 0;
            $data['area'] = 0;
        } else {
            isset($cityIds[0]) && $data['province'] = intval($cityIds[0]);
            isset($cityIds[1]) && $data['city'] = intval($cityIds[1]);
            isset($cityIds[2]) && $data['area'] = intval($cityIds[2]);
        }
        $data['intro'] = t($_POST['intro']);
        $map['uid'] = $this->mid;
        model('User')->where($map)->save($data);

        // 保存用户标签信息 - 前期用user_category_link现在修改为app_tag,此user_tag是选中的user_category_id
        $tagIds = t($_POST['user_tags']);
        !empty($tagIds) && $tagIds = explode(',', $tagIds);
        $rowId = intval($this->mid);
        if (!empty($rowId)) {
            if (count($tagIds) > $this->_config['tag_num']) {
                $this->ajaxReturn(null, '最多只能设置'.$this->_config['tag_num'].'个标签', 0);
            }
            // tag_id
            $categoryHash = model('CategoryTree')->setTable('user_category')->getCategoryHash();
            $tagIdArr = array();
            foreach ($tagIds as $tagId) {
                $name = $categoryHash[$tagId];
                $tagInfo = model('Tag')->setAppName($appName)->setAppTable($appTable)->getTagId($name);
                $tagIdArr[] = $tagInfo;
            }
            model('Tag')->setAppName('public')->setAppTable('user')->updateTagData($rowId, $tagIdArr);
        }

        $this->ajaxReturn(null, '', 1);
    }

    /**
     * 注册流程 - 第四步骤.
     */
    public function step4()
    {
        //未登录
        empty($_SESSION['mid']) && $this->redirect('public/Passport/login');
        //$this->appCssList[] = 'login.css';

        $list = D('Weiba', 'weiba')->interestingWeiba($this->mid, 4);
        $this->assign('list', $list);
        $this->assign('mid', $this->mid);

        //按推荐用户
        $sql = 'SELECT uid FROM `ts_user_verified` WHERE usergroup_id=5 AND verified=1 order by rand() limit 8';
        $list = M()->query($sql);
        $uids = getSubByKey($list, 'uid');
        $userInfos = model('User')->getUserInfoByUids($uids);
        foreach ($list as $v) {
            $key = $v['uid'];
            $arr[$key]['userInfo'] = $userInfos[$key];
        }
        $this->assign('related_recommend_user', $arr);
        //按标签
        if (in_array('tag', $this->_config['interester_rule'])) {
            $related_tag_user = model('RelatedUser')->getRelatedUserByType(4, 18);
            //dump($related_tag_user);exit;
            $this->assign('related_tag_user', $related_tag_user);
        }
        //按地区
        if (in_array('area', $this->_config['interester_rule'])) {
            $related_city_user = model('RelatedUser')->getRelatedUserByType(3, 18);
            $this->assign('related_city_user', $related_city_user);
        }
        $userInfo = model('User')->getUserInfo($this->mid);
        $location = explode(' ', $userInfo['location']);
        $this->assign('location', $location[0]);
        $this->setTitle('选择感兴趣的人');
        $this->setKeywords('选择感兴趣的人');
        $this->display();
    }

    /**
     * 注册流程 - 第四步骤.
     */
    public function getNRelatedUser()
    {
        $type = intval($_POST['type']);
        if ($type == '5') {
            //按推荐用户
            $sql = 'SELECT uid FROM `ts_user_verified` WHERE usergroup_id=5 AND verified=1 order by rand() limit 8';
            $list = M()->query($sql);
            $uids = getSubByKey($list, 'uid');
            $userInfos = model('User')->getUserInfoByUids($uids);
            foreach ($list as $v) {
                $key = $v['uid'];
                $arr[$key]['userInfo'] = $userInfos[$key];
            }
        } else {
            $arr = model('RelatedUser')->getRelatedUserByType($type, 18);
        }
        $html = '';
        $i = 18 * $type;
        foreach ($arr as $vo) {
            $html .= '<li>';
            $html .= '<div class="person-pic"><img src="'.$vo['userInfo']['avatar_middle'].'" height="80px" width="80px"/></div>';
            $html .= '<h class="person-nickname">'.getShort($vo['userInfo']['uname'], 5).'</h>';
            $html .= '<div class="checkbox-area">';
            $html .= '<input type="checkbox" name="fids[]" value="'.$vo['userInfo']['uid'].'" class="checkbox" id="check-box-'.$i.'"/>';
            $html .= '<label for="check-box-'.$i.'"></label>';
            $html .= '</div>';
            $html .= '</li>';
            $i++;
        }
        echo $html;
    }

    /**
     * 注册流程 - 第四步骤.
     */
    public function getGroup()
    {
        $map['status'] = 1;
        $map['is_del'] = 0;
        $list = D('Group')->where($map)->order('rand()')->limit('4')->select();
        $cids = getSubByKey($list, 'cid0');
        $cmap['id'] = array('in', $cids);
        $cateinfos = D('Category')->where($cmap)->field('id,title')->findAll();
        $cnames = array();
        foreach ($cateinfos as $cate) {
            $cnames[$cate['id']] = $cate['title'];
        }
        foreach ($list as $k => $v) {
            $list[$k]['logo'] = getImageUrl($v['logo'], 100, 100, true);
            $list[$k]['catename'] = $cnames[$v['cid0']];
        }
        foreach ($list as $vo) {
            $html .= '<li>';
            $html .= '<div class="circle-pic"><img src="'.$vo['logo'].'"/></div>';
            $html .= '<div class="circle-info">';
            $html .= '<h>'.$vo['name'].'</h>';
            $html .= '<p>'.$vo['intro'].'</p>';
            $html .= '<a href="javascript:joingroup('.$vo['id'].')" class="act-but  joingroup_'.$vo['id'].' tojoin">加入</a> </div>';
            $html .= '</li>';
        }
        echo $html;
    }

    /**
     * 获取推荐用户.
     */
    public function getRelatedUser()
    {
        $type = intval($_POST['type']);
        $related_user = model('RelatedUser')->getRelatedUserByType($type, 8);
        $html = '';
        foreach ($related_user as $k => $v) {
            $html .= '<li><div style="position:relative;width:80px;height:80px"><div class="selected"><i class="ico-ok-mark"></i></div>
					  <a event-node="bulkDoFollowData" value="'.$v['userInfo']['uid'].'" class="face_part" href="javascript:void(0);">
					  <img src="'.$v['userInfo']['avatar_big'].'" /></a></div><span class="name">'.$v['userInfo']['uname'].'</span></li>';
        }
        echo $html;
    }

    /**
     * 注册流程 - 执行第四步骤.
     */
    public function doStep4()
    {
        set_time_limit(0);

        // 添加默认关注用户
        $defaultFollow = $this->_config['default_follow'];
        $defaultFollow = array_diff(explode(',', $defaultFollow), explode(',', $eachFollow));
        // 初始化完成
        $this->_register_model->overUserInit($this->mid);
        // 添加双向关注用户
        $eachFollow = $this->_config['each_follow'];
        if (!empty($eachFollow)) {
            model('Follow')->eachDoFollow($this->mid, $eachFollow);
        }

        if (!empty($defaultFollow)) {
            model('Follow')->bulkDoFollow($this->mid, $defaultFollow);
        }

        //添加关注人员$defaultFollow = $_POST['fids']
        if ($_POST['fids']) {
            model('Follow')->bulkDoFollow($this->mid, $_POST['fids']);
        }
        $this->redirect('public/Index/index');
    }

    /**
     * 注册流程 - 执行第四步骤.
     */
    public function setStep4()
    {
        set_time_limit(0);

        // 添加默认关注用户
        $defaultFollow = $this->_config['default_follow'];
        $defaultFollow = array_diff(explode(',', $defaultFollow), explode(',', $eachFollow));
        // 初始化完成
        $this->_register_model->overUserInit($this->mid);
        // 添加双向关注用户
        $eachFollow = $this->_config['each_follow'];
        if (!empty($eachFollow)) {
            model('Follow')->eachDoFollow($this->mid, $eachFollow);
        }

        if (!empty($defaultFollow)) {
            model('Follow')->bulkDoFollow($this->mid, $defaultFollow);
        }

        redirect($GLOBALS['ts']['site']['home_url']);
    }

    /**
     * 验证邮箱是否已被使用.
     */
    public function isEmailAvailable()
    {
        $email = t($_POST['email']);
        $result = $this->_register_model->isValidEmail($email);
        $this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
    }

    public function isPhoneAvailable()
    {
        $phone = t($_POST['phone']);
        $result = $this->_register_model->isValidPhone($phone);
        $this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
    }

    /* # 注册的时候验证的验证码 */
    public function isRegCodeAvailable()
    {
        $code = intval($_POST['regCode']);
        $phone = floatval($_POST['phone']);
        $sms = model('Sms');

        if ($sms->CheckCaptcha($phone, $code)) {
            echo json_encode(array(
                'status' => true,
                'info'   => '验证通过',
            ));
            exit;
        }

        echo json_encode(array(
            'status' => false,
            'info'   => $sms->getMessage(),
        ));
        exit;
    }

    /**
     * 验证邀请邮件.
     */
    public function isEmailAvailable_invite()
    {
        $email = t($_POST['email']);
        if (empty($email)) {
            exit($this->ajaxReturn(null, '', 1));
        }
        $result = $this->_register_model->isValidEmail_invite($email);
        $this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
    }

    /**
     * 验证昵称是否已被使用.
     */
    public function isUnameAvailable()
    {
        $uname = t($_POST['uname']);
        $oldName = t($_POST['old_name']);
        $result = $this->_register_model->isValidName($uname, $oldName);
        $this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
    }

    /**
     * 添加用户关注信息.
     */
    public function bulkDoFollow()
    {
        $res = model('Follow')->bulkDoFollow($this->mid, t($_POST['fids']));
        $this->ajaxReturn($res, model('Follow')->getError(), false !== $res);
    }

    /**
     *  设置用户为已初始化.
     */
    public function doAuditUser()
    {
        $this->_register_model->overUserInit($this->mid);
        echo 1;
    }

    /**
     * 判断验证码是否正确.
     *
     * @return bool 若正确返回true，否则返回false
     */
    public function isValidVerify()
    {
        $code = intval($_POST['verify']);
        $sms = model('Sms');
        $phone = $_SESSION['phone'];

        /* # 检查验证码是否正确 */
        if ($sms->CheckCaptcha($phone, $code)) {
            echo json_encode(array(
                'status' => 1,
                'info'   => '验证通过！',
            ));
            exit;
        }

        echo json_encode(array(
            'status' => 0,
            'info'   => $sms->getMessage(),
        ));
        unset($sms);
        exit;
    }

    public function sendReigterCode()
    {
        //检查验证码
        if (md5(strtoupper($_POST['verify'])) != $_SESSION['verify']) {
            echo json_encode(array(
                'status' => 0,
                'data'   => '图像验证码错误！',
            ));
            exit;
        }

        $phone = floatval($_POST['phone']);

        /* # session记录手机号码 */
        $_SESSION['phone'] = $phone;

        /* # 验证是否是手机号码 */
        if (0 >= preg_match('/^\+?[0\s]*[\d]{0,4}[\-\s]?\d{4,12}$/', $phone)) {
            echo json_encode(array(
                'status' => 0,
                'data'   => '不是正确的手机号码！',
            ));

            /* # 验证该手机号码是否已经注册 */
        } elseif (!model('User')->isChangePhone($phone)) {
            echo json_encode(array(
                'status' => 0,
                'data'   => '该手机已经被注册成用户，您无法发送验证码！',
            ));

            /* # 检查是否发送成功 */
        } elseif (($sms = model('Sms')) and !$sms->sendCaptcha($phone, true)) {
            echo json_encode(array(
                'status' => 0,
                'data'   => $sms->getMessage(),
            ));
        } else {
            echo json_encode(array(
                'status' => 1,
                'data'   => '发送成功，请注意查收！',
            ));
        }
        exit;
    }
}
