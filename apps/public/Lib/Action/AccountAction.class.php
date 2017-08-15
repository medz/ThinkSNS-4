<?php

/**
 * 账号设置控制器.
 *
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.0
 */
class AccountAction extends Action
{
    private $_profile_model; // 用户档案模型对象字段

    /**
     * 控制器初始化，实例化用户档案模型对象
     */
    protected function _initialize()
    {
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
            'field_name' => '积分规则',
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
            'field_key'  => 'invite',
            'field_name' => '链接邀请',
        ); // 链接邀请

        $this->assign('tab_list', $tab_list);
        $this->assign('tab_list_score', $tab_list_score);
        $this->assign('tab_list_preference', $tab_list_preference);
        $this->assign('tab_list_security', $tab_list_security);
        $this->assign('tab_list_invite', $tab_list_invite);
    }

    /**
     * 基本设置页面.
     */
    public function index()
    {
        $this->appCssList[] = 'account.css';
        $user_info = model('User')->getUserInfo($this->mid);
        $data = $this->_getUserProfile();
        $data['langType'] = model('Lang')->getLangType();
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user_info['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $this->assign('user_info', $user_info);
        $this->assign($data);
        $this->setTitle(L('PUBLIC_PROFILESET_INDEX')); // 个人设置
        $this->setKeywords(L('PUBLIC_PROFILESET_INDEX'));
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user_info['category'].$user_info['location'].','.implode(',', $user_tag[$this->mid]).','.$user_info['intro']));
        $this->display();
    }

    /**
     * 扩展信息设置页面.
     *
     * @param string $extend
     *                       扩展类目名称(为插件准备)
     */
    public function _empty($extend)
    {
        $cid = D('user_profile_setting')->where("field_key='".ACTION_NAME."'")->getField('field_id');
        $data = $this->_getUserProfile();
        $data['cid'] = $cid;
        $this->assign($data);
        $this->display('extend');
    }

    /**
     * 获取登录用户的档案信息.
     *
     * @return 登录用户的档案信息
     */
    private function _getUserProfile()
    {
        $data['user_profile'] = $this->_profile_model->getUserProfile($this->mid);
        $data['user_profile_setting'] = $this->_profile_model->getUserProfileSettingTree();

        return $data;
    }

    /**
     * 保存基本信息操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doSaveProfile()
    {
        $res = true;
        // 保存用户表信息
        if (!empty($_POST['sex'])) {
            $save['sex'] = 1 == intval($_POST['sex']) ? 1 : 2;
            // $save['lang'] = t($_POST['lang']);
            $save['intro'] = $_POST['intro'] ? formatEmoji(true, t($_POST['intro'])) : '';

            /* # 检查用户简介是否超出字数限制 */
            if (get_str_length($save['intro']) > 150) {
                $this->ajaxReturn(null, '个人简介不得超过150字', 0);
            }

            // 添加地区信息
            $save['location'] = t($_POST['city_names']);
            $cityIds = t($_POST['city_ids']);
            $cityIds = explode(',', $cityIds);
            /* if (! $cityIds [0] || ! $cityIds [1] || ! $cityIds [2])
              $this->error ( '请选择完整地区' ); */
            isset($cityIds[0]) && $save['province'] = intval($cityIds[0]);
            if ($_POST['input_city'] != '') {
                $save['input_city'] = t($_POST['input_city']);
                $save['city'] = 0;
                $save['area'] = 0;
            } else {
                isset($cityIds[1]) && $save['city'] = intval($cityIds[1]);
                isset($cityIds[2]) && $save['area'] = intval($cityIds[2]);
            }
            // 修改用户昵称
            $uname = t($_POST['uname']);
            $oldName = t($_POST['old_name']);
            $save['uname'] = filter_keyword($uname);
            $res = model('Register')->isValidName($uname, $oldName);
            if (!$res) {
                $error = model('Register')->getLastError();

                return $this->ajaxReturn(null, model('Register')->getLastError(), $res);
            }
            // 如果包含中文将中文翻译成拼音
            if (preg_match('/[\x7f-\xff]+/', $save['uname'])) {
                // 昵称和呢称拼音保存到搜索字段
                $save['search_key'] = $save['uname'].' '.model('PinYin')->Pinyin($save['uname']);
            } else {
                $save['search_key'] = $save['uname'];
            }

            /* 用户首字母 */
            $save['first_letter'] = getShortPinyin($save['uname']);

            $res = model('User')->where("`uid`={$this->mid}")->save($save);
            $res && model('User')->cleanCache($this->mid);
            $user_feeds = model('Feed')->where('uid='.$this->mid)->field('feed_id')->findAll();
            if ($user_feeds) {
                $feed_ids = getSubByKey($user_feeds, 'feed_id');
                model('Feed')->cleanCache($feed_ids, $this->mid);
            }
        }
        // 保存用户资料配置字段
        (false !== $res) && $res = $this->_profile_model->saveUserProfile($this->mid, $_POST);
        // 保存用户标签信息
        $tagIds = t($_REQUEST['user_tags']);
        // 注册配置信息
        $this->_config = model('Xdata')->get('admin_Config:register');
        if (!empty($tagIds)) {
            $tagIds = explode(',', $tagIds);
            $rowId = intval($this->mid);
            if (!empty($rowId)) {
                $registerConfig = model('Xdata')->get('admin_Config:register');
                if (count($tagIds) > $registerConfig['tag_num']) {
                    return $this->ajaxReturn(null, '最多只能设置'.$registerConfig['tag_num'].'个标签', false);
                }
                model('Tag')->setAppName('public')->setAppTable('user')->updateTagData($rowId, $tagIds);
            }
        } elseif (empty($tagIds) && isset($_REQUEST['user_tags'])) {
            return $this->ajaxReturn(null, '请至少选择一个标签', false);
        }
        $result = $this->ajaxReturn(null, $this->_profile_model->getError(), $res);

        return $this->ajaxReturn(null, $this->_profile_model->getError(), $res);
    }

    /**
     * 头像设置页面.
     */
    public function avatar()
    {
        model('User')->cleanCache($this->mid);
        $user_info = model('User')->getUserInfo($this->mid);
        $this->assign('user_info', $user_info);

        $this->setTitle(L('PUBLIC_IMAGE_SETTING')); // 个人设置
        $this->setKeywords(L('PUBLIC_IMAGE_SETTING'));
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user_info['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user_info['category'].$user_info['location'].','.implode(',', $user_tag[$this->mid]).','.$user_info['intro']));
        $this->display();
    }

    /**
     * 保存登录用户的头像设置操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doSaveAvatar()
    {
        $dAvatar = model('Avatar');
        $dAvatar->init($this->mid); // 初始化Model用户id
        // 安全过滤
        $step = t($_GET['step']);
        if ('upload' == $step) {
            $result = $dAvatar->upload();
        } elseif ('save' == $step) {
            $result = $dAvatar->dosave();
        }
        model('User')->cleanCache($this->mid);
        $user_feeds = model('Feed')->where('uid='.$this->mid)->field('feed_id')->findAll();
        if ($user_feeds) {
            $feed_ids = getSubByKey($user_feeds, 'feed_id');
            model('Feed')->cleanCache($feed_ids, $this->mid);
        }
        $this->ajaxReturn($result['data'], $result['info'], $result['status']);
    }

    /**
     * 保存微吧图标.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doSaveAvatars()
    {
        $dAvatar = model('Avatar');
        $dAvatar->init($this->mid); // 初始化Model用户id
        // 安全过滤
        $step = t($_GET['step']);
        if ('upload' == $step) {
            $result = $dAvatar->uploadAvatars();
        } elseif ('save' == $step) {
            $result = $dAvatar->dosaveAvatars();
        }
        model('User')->cleanCache($this->mid);
        $this->ajaxReturn($result['data'], $result['info'], $result['status']);
    }

    /**
     * 保存登录用户的头像设置操作，Flash上传.
     *
     * @return string 操作后的反馈信息
     */
    public function doSaveUploadAvatar()
    {
        $data['big'] = base64_decode($_POST['png1']);
        $data['middle'] = base64_decode($_POST['png2']);
        $data['small'] = base64_decode($_POST['png3']);
        if (empty($data['big']) || empty($data['middle']) || empty($data['small'])) {
            exit('error='.L('PUBLIC_ATTACHMENT_UPLOAD_FAIL')); // 图片上传失败，请重试
        }
        if (model('Avatar')->init($this->mid)->saveUploadAvatar($data, $this->user)) {
            exit('success='.L('PUBLIC_ATTACHMENT_UPLOAD_SUCCESS')); // 附件上传成功
        } else {
            exit('error='.L('PUBLIC_ATTACHMENT_UPLOAD_FAIL')); // 图片上传失败，请重试
        }
    }

    /**
     * 标签设置页面.
     */
    public function tag()
    {
        $registerConfig = model('Xdata')->get('admin_Config:register');
        $this->assign('tag_num', $registerConfig['tag_num']);
        $this->display();
    }

    /**
     * 隐私设置页面.
     */
    public function privacy()
    {
        $user_privacy = D('UserPrivacy')->getUserSet($this->mid);
        $this->assign('user_privacy', $user_privacy);

        $user = model('User')->getUserInfo($this->mid);
        $this->setTitle(L('PUBLIC_PRIVACY'));
        $this->setKeywords(L('PUBLIC_PRIVACY'));
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user['category'].$user['location'].','.implode(',', $user_tag[$this->mid]).','.$user['intro']));
        $this->display();
    }

    /**
     * 保存登录用户隐私设置操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doSavePrivacy()
    {
        // dump($_POST);exit;
        $res = model('UserPrivacy')->dosave($this->mid, $_POST);
        $this->ajaxReturn(null, model('UserPrivacy')->getError(), $res);
    }

    /**
     * 个性域名设置页面.
     */
    public function domain()
    {
        // 是否启用个性化域名
        $user = model('User')->getUserInfo($this->mid);
        $data['user_domain'] = $user['domain'];
        $this->assign($data);

        $this->setTitle(L('PUBLIC_DOMAIN_NAME')); // 个人设置
        $this->setKeywords(L('PUBLIC_DOMAIN_NAME'));
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user['category'].$user['location'].','.implode(',', $user_tag[$this->mid]).','.$user['intro']));
        $this->display();
    }

    /**
     * 保存用户个性域名操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doSaveDomain()
    {
        $domain = t($_POST['domain']);
        // 验证信息
        if (strlen($domain) < 5) {
            $this->ajaxReturn(null, '域名长度不能少于5个字符', 0); // 仅限5个字符以上20个字符以内的英文/数字/下划线，以英文字母开头，不能含有特殊字符，一经设置，无法更改。
        }
        if (strlen($domain) > 20) {
            $this->ajaxReturn(null, L('PUBLIC_SHORT_DOMAIN_CHARACTERLIMIT'), 0); // 域名长度不能超过20个字符
        }
        if (!ereg('^[a-zA-Z][_a-zA-Z0-9]+$', $domain)) {
            $this->ajaxReturn(null, '仅限于英文/数字/下划线，以英文字母开头，不能含有特殊字符', 0); // 仅限5个字符以上20个字符以内的英文/数字/下划线，以英文字母开头，不能含有特殊字符，一经设置，无法更改。
        }

        $keywordConfig = model('Xdata')->get('keywordConfig');
        $keywordConfig = explode(',', $keywordConfig);
        if (!empty($keywordConfig) && in_array($domain, $keywordConfig)) {
            $this->ajaxReturn(null, L('PUBLIC_DOMAIN_DISABLED'), 0); // 该个性域名已被禁用
        }

        // 预留域名使用
        $sysDomin = model('Xdata')->getConfig('sys_domain', 'site');
        $sysDomin = explode(',', $sysDomin);
        if (!empty($sysDomin) && in_array($domain, $sysDomin)) {
            $this->ajaxReturn(null, L('PUBLIC_DOMAIN_DISABLED'), 0); // 该个性域名已被禁用
        }

        if (model('User')->where("uid!={$this->mid} AND domain='{$domain}'")->count()) {
            $this->ajaxReturn(null, L('PUBLIC_DOMAIN_OCCUPIED'), 0); // 此域名已经被使用
        } else {
            $user_info = model('User')->getUserInfo($this->mid);
            !$user_info['domian'] && model('User')->setField('domain', "$domain", 'uid='.$this->mid);
            model('User')->cleanCache($this->mid);
            $this->ajaxReturn(null, L('PUBLIC_DOMAIN_SETTING_SUCCESS'), 1); // 域名设置成功
        }
    }

    /**
     * 账号安�
     * �设置页面.
     */
    public function security()
    {
        $user = model('User')->getUserInfo($this->mid);
        $mobile = $user['phone'];
        $email = $user['email'];

        $matchMobile = preg_match('/^[1][358]\d{9}$/', $mobile);
        $bindingMobile = ($matchMobile === 1) ? true : false;
        $this->assign('bindingMobile', $bindingMobile);
        $this->assign('mobile', $mobile);

        $matchEmail = preg_match('/[_a-zA-Z\d\-\.]+(@[_a-zA-Z\d\-\.]+\.[_a-zA-Z\d\-]+)+$/i', $email);
        $bindingEmail = ($matchEmail === 1) ? true : false;
        $this->assign('bindingEmail', $bindingEmail);
        $this->assign('email', $email);

        $this->setTitle(L('PUBLIC_ACCOUNT_SECURITY'));
        $this->setKeywords(L('PUBLIC_ACCOUNT_SECURITY'));
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user['category'].$user['location'].','.implode(',', $user_tag[$this->mid]).','.$user['intro']));
        $this->display();
    }

    /**
     * 修改登录用户账号密码操作.
     *
     * @return json 返回操作后的JSON信息数据
     */
    public function doModifyPassword()
    {
        $_POST['oldpassword'] = t($_POST['oldpassword']);
        $_POST['password'] = t($_POST['password']);
        $_POST['repassword'] = t($_POST['repassword']);
        // 验证信息
        if ($_POST['oldpassword'] === '') {
            $this->error('请填写原始密码');
        }
        if ($_POST['password'] === '') {
            $this->error('请填写新密码');
        }
        if ($_POST['repassword'] === '') {
            $this->error('请填写确认密码');
        }
        if ($_POST['password'] != $_POST['repassword']) {
            $this->error(L('PUBLIC_PASSWORD_UNSIMILAR')); // 新密码与确认密码不一致
        }
        if (strlen($_POST['password']) < 6) {
            $this->error('密码太短了，最少6位');
        }
        if (strlen($_POST['password']) > 15) {
            $this->error('密码太长了，最多15位');
        }
        if ($_POST['password'] == $_POST['oldpassword']) {
            $this->error(L('PUBLIC_PASSWORD_SAME')); // 新密码与旧密码相同
        }

        $user_model = model('User');
        $map['uid'] = $this->mid;
        $user_info = $user_model->where($map)->find();

        if ($user_info['password'] == $user_model->encryptPassword($_POST['oldpassword'], $user_info['login_salt'])) {
            $data['login_salt'] = rand(11111, 99999);
            $data['password'] = $user_model->encryptPassword($_POST['password'], $data['login_salt']);
            $res = $user_model->where("`uid`={$this->mid}")->save($data);
            $info = $res ? L('PUBLIC_PASSWORD_MODIFY_SUCCESS') : L('PUBLIC_PASSWORD_MODIFY_FAIL'); // 密码修改成功，密码修改失败
        } else {
            $info = L('PUBLIC_ORIGINAL_PASSWORD_ERROR'); // 原始密码错误
        }

        return $this->ajaxReturn(null, $info, $res);
    }

    /**
     * 申请认证
     */
    public function authenticate()
    {
        $auType = model('UserGroup')->where('is_authenticate=1')->findall();
        $this->assign('auType', $auType);
        $verifyInfo = D('user_verified')->where('uid='.$this->mid)->find();
        if ($verifyInfo['attach_id']) {
            $a = explode('|', $verifyInfo['attach_id']);
            foreach ($a as $key => $val) {
                if ($val !== '') {
                    $attachInfo = D('attach')->where("attach_id=$a[$key]")->find();
                    $verifyInfo['attachment'] .= $attachInfo['name'].'&nbsp;<a href="'.getImageUrl($attachInfo['save_path'].$attachInfo['save_name']).'" target="_blank">下载</a><br />';
                }
            }
        }
        // 获取认证分类信息
        if (!empty($verifyInfo['user_verified_category_id'])) {
            $verifyInfo['category']['title'] = D('user_verified_category')->where('user_verified_category_id='.$verifyInfo['user_verified_category_id'])->getField('title');
        }

        switch ($verifyInfo['verified']) {
            case '1':
                $status = '<i class="ico-ok"></i>已认证 <a href="javascript:void(0);" onclick="delverify()">注销认证</a>';
                break;
            case '0':
                $status = '<i class="ico-wait"></i>已提交认证，等待审核';
                break;
            case '-1':
                // 安全过滤
                $type = t($_GET['type']);
                if ($type == 'edit') {
                    $status = '<i class="ico-no"></i>未通过认证，请修改资料后重新提交';
                    $this->assign('edit', 1);
                    $verifyInfo['attachIds'] = str_replace('|', ',', substr($verifyInfo['attach_id'], 1, strlen($verifyInfo['attach_id']) - 2));
                } else {
                    $status = '<i class="ico-no"></i>未通过认证，<a href="'.U('public/Account/authenticate', array(
                                'type' => 'edit',
                            )).'">请修改资料后重新提交</a>';
                }
                break;
            default:
                // $verifyInfo['usergroup_id'] = 5;
                $status = '未认证';
                break;
        }
        // 附件限制
        $attach = model('Xdata')->get('admin_Config:attachimage');
        $imageArr = array(
            'gif',
            'jpg',
            'jpeg',
            'png',
            'bmp',
        );
        foreach ($imageArr as $v) {
            if (strstr($attach['attach_allow_extension'], $v)) {
                $imageAllow[] = $v;
            }
        }
        $attachOption['attach_allow_extension'] = implode(', ', $imageAllow);
        $attachOption['attach_max_size'] = $attach['attach_max_size'];
        $this->assign('attachOption', $attachOption);

        // 获取认证分类
        $category = D('user_verified_category')->findAll();
        foreach ($category as $k => $v) {
            $option[$v['pid']] .= '<option ';
            if ($verifyInfo['user_verified_category_id'] == $v['user_verified_category_id']) {
                $option[$v['pid']] .= 'selected';
            }
            $option[$v['pid']] .= ' value="'.$v['user_verified_category_id'].'">'.$v['title'].'</option>';
        }
        $this->assign('option', json_encode($option));
        $this->assign('options', $option);
        $this->assign('category', $category);
        $this->assign('status', $status);
        $this->assign('verifyInfo', $verifyInfo);
        // dump($verifyInfo);exit;

        $user = model('User')->getUserInfo($this->mid);
        $this->setTitle('申请认证');
        $this->setKeywords('申请认证');
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user['category'].$user['location'].','.implode(',', $user_tag[$this->mid]).','.$user['intro']));
        $this->display();
    }

    /**
     * 提交申请认证
     */
    public function doAuthenticate()
    {
        //检查认证类型
        $data['usergroup_id'] = intval($_POST['usergroup_id']);
        $hasUserGroup = model('UserGroup')->where(array('user_group_id' => $data['usergroup_id'], 'is_authenticate' => 1))->count() > 0;
        if (!$hasUserGroup) {
            exit('认证的分类不存在');
        }
        //检查认证分类
        $data['user_verified_category_id'] = intval($_POST['verifiedCategory']);
        $hasVCatId = D('user_verified_category')->where("pid={$data['usergroup_id']} and user_verified_category_id={$data['user_verified_category_id']}")->count() > 0;
        if (!$hasVCatId) {
            $data['user_verified_category_id'] = 0;
        }
        //取得认证信息
        $data['company'] = trim(t($_POST['company']));
        $data['realname'] = trim(t($_POST['realname']));
        $data['idcard'] = trim(t($_POST['idcard']));
        $data['phone'] = trim(t($_POST['phone']));
        $data['reason'] = trim(t($_POST['reason']));
        $data['info'] = trim(t($_POST['info']));
        $data['attach_id'] = trim(t($_POST['attach_ids']));

        $Regx1 = '/^[0-9]*$/';
        $Regx2 = '/^[A-Za-z0-9]*$/';
        $Regx3 = '/^[A-Za-z|\x{4e00}-\x{9fa5}]+$/u';

        if ($data['usergroup_id'] == 6) {
            if (!$data['company']) {
                exit('机构名称不能为空');
            }
        }
        if (!$data['realname']) {
            exit(($data['usergroup_id'] == 5 ? '负责人' : '真实').'姓名不能为空');
        }
        if (!$data['idcard']) {
            exit('身份证号码不能为空');
        }
        if (!$data['phone']) {
            exit('联系方式不能为空');
        }
        if (preg_match($Regx3, $data['realname']) == 0 || strlen($data['realname']) > 30) {
            exit('请输入正确的姓名格式');
        }
        if (preg_match($Regx2, $data['idcard']) == 0 || preg_match($Regx1, substr($data['idcard'], 0, 17)) == 0 || strlen($data['idcard']) !== 18) {
            exit('请输入正确的身份证号码');
        }
        if (preg_match($Regx1, $data['phone']) == 0) {
            exit('请输入正确的手机号码格式');
        }
        preg_match_all('/./us', $data['reason'], $matchs); // 一个汉字也为一个字符
        if (count($matchs[0]) > 255) {
            exit('认证补充不能超过255个字符');
        }
        preg_match_all('/./us', $data['info'], $match); //一个汉字也为一个字符
        if (count($match[0]) > 140) {
            exit('认证资料不能超过255个字符');
        }

        $data['verified'] = 0; //认证状态为未认证
        $verifyInfo = D('user_verified')->where('uid='.$this->mid)->count() > 0;
        if ($verifyInfo) {
            $res = D('user_verified')->where('uid='.$this->mid)->save($data);
        } else {
            $data['uid'] = $this->mid;
            $res = D('user_verified')->add($data);
        }
        if (false !== $res) {
            model('Notify')->sendNotify($this->mid, 'public_account_doAuthenticate');
            $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
            foreach ($touid as $k => $v) {
                model('Notify')->sendNotify($v['uid'], 'verify_audit');
            }
            echo '1';
            exit;
        } else {
            exit('认证信息提交失败');
        }
    }

    /**
     * 注销认证
     *
     * @return bool 操作是否成功 1:成功 0:失败
     */
    public function delverify()
    {
        $verified_group_id = D('user_verified')->where('uid='.$this->mid)->getField('usergroup_id');
        $res = D('user_verified')->where('uid='.$this->mid)->delete();
        $res2 = D('user_group_link')->where('uid='.$this->mid.' and user_group_id='.$verified_group_id)->delete();
        if ($res && $res2) {
            // 清除权限组 用户组缓存
            model('Cache')->rm('perm_user_'.$this->mid);
            model('Cache')->rm('user_group_'.$this->mid);
            model('Notify')->sendNotify($this->mid, 'public_account_delverify');
            echo 1;
        } else {
            echo 0;
        }
    }

    /**
     * 黑名单设置.
     */
    public function blacklist()
    {
        $user = model('User')->getUserInfo($this->mid);
        $this->setTitle('黑名单');
        $this->setKeywords('黑名单');
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user['category'].$user['location'].','.implode(',', $user_tag[$this->mid]).','.$user['intro']));
        $this->display();
    }

    /**
     * 通知设置.
     */
    public function notify()
    {
        $user_privacy = D('UserPrivacy')->getUserSet($this->mid);
        $this->assign('user_privacy', $user_privacy);

        $user = model('User')->getUserInfo($this->mid);
        $this->setTitle('通知设置');
        $this->setKeywords('通知设置');
        // 获取用户职业信息
        $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
        $userCateArray = array();
        if (!empty($userCategory)) {
            foreach ($userCategory as $value) {
                $user['category'] .= '<a href="#" class="link btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
            }
        }
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags(array(
            $this->mid,
                ));
        $this->setDescription(t($user['category'].$user['location'].','.implode(',', $user_tag[$this->mid]).','.$user['intro']));
        $this->display();
    }

    /**
     * 修改用户身份.
     */
    public function editUserCategory()
    {
        $this->assign('mid', $this->mid);
        $this->display();
    }

    /**
     * 执行修改用户身份操作.
     */
    public function doEditUserCategory()
    {
        $userCategoryIds = t($_POST['user_category_ids']);
        empty($userCategoryIds) && exit($this->error('请至少选择一个职业信息'));
        $userCategoryIds = explode(',', $userCategoryIds);
        $userCategoryIds = array_filter($userCategoryIds);
        $userCategoryIds = array_unique($userCategoryIds);
        $result = model('UserCategory')->updateRelateUser($this->mid, $userCategoryIds);
        if ($result) {
            // 获取用户身份信息
            $userCategory = model('UserCategory')->getRelatedUserInfo($this->mid);
            $userCateArray = array();
            if (!empty($userCategory)) {
                foreach ($userCategory as $value) {
                    $category .= '<a href="#" class="btn-cancel"><span>'.$value['title'].'</span></a>&nbsp;&nbsp;';
                }
            }
            $this->ajaxReturn($category, L('PUBLIC_SAVE_SUCCESS'), $result);
        } else {
            $this->ajaxReturn(null, '职业信息保存失败', $result);
        }
    }

    /**
     * 帐号绑定.
     */
    public function bind()
    {
        // 邮箱绑定
        // $user = M('user')->where('uid='.$this->mid)->field('email')->find();
        // $replace = substr($user['email'],2,-3);
        // for ($i=1;$i<=strlen($replace);$i++){
        // $replacestring.='*';
        // }
        // $data['email'] = str_replace( $replace, $replacestring ,$user['email'] );
        // 站外帐号绑定
        $bindData = array();
        Addons::hook('account_bind_after', array(
            'bindInfo' => &$bindData,
        ));
        $data['bind'] = $bindData;
        $this->assign($data);
        $user = model('User')->getUserInfo($this->mid);
        $this->setTitle('帐号绑定');
        $this->setKeywords('帐号绑定');
        $this->setDescription(t(implode(',', getSubByKey($data['bind'], 'name'))));
        $this->display();
    }

    /**
     * 手机绑定设置.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function doBindingMobile()
    {
        $phone = floatval($_POST['mobile']);
        $code = intval($_POST['mobile_code']);

        /* # 检查用户是否不可以更改为当前手机号码 */
        if (!model('User')->isChangePhone($phone, $this->mid)) {
            $this->ajaxReturn(null, '当前手机号码不能用于绑定', 0);

            /* # 检查验证码是否不正确 */
        } elseif (($sms = model('Sms')) and !$sms->CheckCaptcha($phone, $code)) {
            $this->ajaxReturn(null, $sms->getMessage(), 0);

            /* # 验证是否修改成功 */
        } elseif (model('User')->where('`uid` = '.$this->mid)->setField('phone', $phone)) {
            model('User')->cleanCache($this->mid);
            $this->ajaxReturn(null, '设置成功', 1);
        }

        $this->ajaxReturn(null, '设置失败', 0);
    }

    /*public function doBindingMobile() {
        $mobile = t($_POST ['mobile']);
        if (!model('Register')->isValidPhone($mobile)) {
            $this->ajaxReturn(null, model('Register')->getLastError(), 0);
        }
        $code = t($_POST ['mobile_code']);
        if (!model('Captcha')->checkLoginCode($mobile, $code)) {
            $this->ajaxReturn(null, '验证码错误，请检查验证码', 0);
        }
        $map ['uid'] = $this->mid;
        $result = model('User')->where($map)->setField('phone', $mobile);
        if ($result) {
            model('User')->cleanCache($this->mid);
            $data ['mobile'] = hideContactInformation($mobile, 'mobile');
            $data ['type'] = 'mobile';
            $this->ajaxReturn($data, '设置成功', 1);
        } else {
            $this->ajaxReturn(null, '设置失败', 0);
        }
    }*/

    /**
     * 绑定|更换邮箱.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function doBindingEmail()
    {
        $email = t($_POST['email']);
        $code = intval($_POST['email_code']);

        /* # 验证是否不可以修改 */
        if (!model('User')->isChangeEmail($email, $this->mid)) {
            $this->ajaxReturn(null, '该邮箱无法用于账户绑定', 0);

            /* # 验证验证码是否不正确 */
        } elseif (($sms = model('Sms')) and !$sms->checkEmailCaptcha($email, $code)) {
            $this->ajaxReturn(null, $sms->getMessage(), 0);

            /* # 重新设置email */
        } elseif (model('User')->where('`uid` = '.$this->mid)->setField('email', $email)) {
            model('User')->cleanCache($this->mid);
            $this->ajaxReturn(null, '设置成功', 1);
        }

        $this->ajaxReturn(null, '设置失败', 0);
    }

    /*public function doBindingEmail2() {
        $email = t($_POST ['email']);
        if (!model('Register')->isValidEmail($email)) {
            $this->ajaxReturn(null, model('Register')->getLastError(), 0);
        }
        $code = t($_POST ['email_code']);
        if (!model('Captcha')->checkEmailCode($email, $code)) {
            $this->ajaxReturn(null, '验证码错误，请检查验证码', 0);
        }
        $map ['uid'] = $this->mid;
        $result = model('User')->where($map)->setField('email', $email);
        if ($result) {
            model('User')->cleanCache($this->mid);
            $data ['email'] = hideContactInformation($mobile, 'email');
            $data ['type'] = 'email';
            $this->ajaxReturn(null, '设置成功', 1);
        } else {
            $this->ajaxReturn(null, '设置失败', 0);
        }
    }*/

    /**
     * 获取验证码
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getCaptcha()
    {
        $type = t($_POST['type']);
        $sms = model('Sms');

        /* # 判断是否类型错误 */
        if (!in_array($type, array('mobile', 'email'))) {
            $this->ajaxReturn(null, '参数错误', 0);

            /* # 手机验证码获取 */
        } elseif ($type == 'mobile') {
            $phone = floatval($_POST['mobile']);

            /* # 验证手机号是否存在 */
            model('User')->where('`phone` = '.$phone.' AND `is_del` = 0')->field('`uid`')->count() and $this->ajaxReturn(null, '该手机号无法用于绑定', 0);

            /* # 发送验证码 */
            $sms->sendCaptcha($phone, true) and $this->ajaxReturn(null, '验证码已经发送到您手机，请注意查收', 1);
            $this->ajaxReturn(null, $sms->getMessage(), 0);

            /* # 获取邮箱验证码 */
        } elseif ($type == 'email') {
            $email = t($_POST['email']);

            /* # 验证邮箱是否被使用 */
            model('User')->where('`email` LIKE \''.$email.'\' AND `is_del` = 0')->field('`uid`')->count() and $this->ajaxReturn(null, '该邮箱无法用于账户绑定', 0);

            /* # 发送验证码 */
            $sms->sendEmaillCaptcha($email, true) or $this->ajaxReturn(null, $sms->getMessage(), 0);

            /* # 发送邮件 */
            model('Notify')->sendNotifyChangeEmail($this->mid, 'email_verification', array(
                'uname' => getUserName($this->mid),
                'rand'  => $sms->getCode(),
                'date'  => date('Y-m-d', time()),
            ), $email);

            /* # 返回状态 */
            $this->ajaxReturn(null, '验证码已经发送到您邮箱', 1);
        }

        unset($sms);
    }

    /*public function getCaptcha2() {
        $type = t($_POST ['type']);
        if (!in_array($type, array(
                    'mobile',
                    'email'
                ))) {
            $this->ajaxReturn(null, '参数错误', 0);
        }
        $msg = '';
        $result = false;
        $model = model('Captcha');
        switch ($type) {
            case 'mobile' :
                $mobile = t($_POST ['mobile']);
                $result = $model->sendLoginCode($mobile);
                $msg    = $model->getError();
                break;
            case 'email' :
                $email = t($_POST ['email']);
                $result = $model->sendEmailCode($email);
                $msg = $model->getError();
                if ($result) {
                    $map ['communication'] = $email;
                    $map ['type'] = 5;
                    $rand = $model->where($map)->order('captcha_id DESC')->getField('rand');
                    $config ['uname'] = getUserName($this->mid);
                    $config ['rand'] = $rand;
                    $config ['date'] = date('Y-m-d', time());
                    // model('Notify')->sendNotify($this->mid, 'email_verification', $config);
                    model('Notify')->sendNotifyChangeEmail($this->mid, 'email_verification', $config, $email);
                }
                break;
        }

        if ($result) {
            empty($msg) && $msg = '发生成功';
            $this->ajaxReturn(null, $msg, 1);
        } else {
            empty($msg) && $msg = '发送失败';
            $this->ajaxReturn(null, $msg, 0);
        }
    }*/

    public function scoredetail()
    {
        $user_info = model('User')->getUserInfo($this->mid);
        $this->assign('user_info', $user_info);

        // 获取用户积分信息
        $userCredit = model('Credit')->getUserCredit($this->mid);
        $this->assign('userCredit', $userCredit);

        // 积分变化记录
        $credit_record = D('credit_record')->where('uid='.$this->mid)->order('ctime DESC')->findPage(100);
        $this->assign('credit_record', $credit_record);
        $this->display();
    }

    public function scorerule()
    {
        $list = M('credit_setting')->order('type ASC')->findPage(100);
        $creditType = M('credit_type')->order('id ASC')->findAll();
        $this->assign('creditType', $creditType);
        $this->assign($list);
        // dump($creditType);exit;
        // dump($list);exit;
        $this->display();
    }

    public function scorelevel()
    {
        $list = model('Credit')->getLevel();
        $this->assign('list', $list);
        $this->display();
    }

    public function scorecharge()
    {
        // 删除7天前还没支付的记录
        D('credit_charge')->where('status=0 AND ctime<'.(time() - (86400 * 7)));
        $data = model('Xdata')->get('admin_Config:charge');
        $charge_record = D('credit_charge')->where('status>0 and uid='.$this->mid)->order('charge_id desc')->findPage(100);
        $this->assign('chargeConfigs', $data);
        $this->assign('charge_record', $charge_record);
        $this->display();
    }

    public function scoretransfer()
    {
        if ($_POST) {
            $_POST['fromUid'] = $this->mid;
            $result = model('Credit')->startTransfer();
            if ($result) {
                $this->success('积分转账成功！');

                return;
            }
            $this->error('积分转账失败');
        }
        $map['uid'] = $this->mid;
        $map['action'] = '积分转出';
        $credit_record = D('credit_record')->where($map)->order('ctime DESC')->findPage(100);
        $this->assign('credit_record', $credit_record);
        $this->display();
    }

    public function do_scorecharge()
    {
        $price = intval($_POST['charge_value']);
        if ($price < 1) {
            exit(json_encode(array('status' => 0, 'info' => '充值金额不正确')));
        }
        $type = intval($_POST['charge_type']);
        $types = array('alipay', 'weixin');
        if (!isset($types[$type])) {
            exit(json_encode(array('status' => 0, 'info' => '充值方式不支持')));
        }
        $chargeConfigs = model('Xdata')->get('admin_Config:charge');
        if (!in_array($types[$type], $chargeConfigs['charge_platform'])) {
            exit(json_encode(array('status' => 0, 'info' => '充值方式不支持')));
        }

        $data['serial_number'] = 'CZ'.date('YmdHis').rand(0, 9).rand(0, 9);
        $data['charge_type'] = $type;
        $data['charge_value'] = $price;
        $data['uid'] = $this->mid;
        $data['ctime'] = time();
        $data['status'] = 0;
        $data['charge_sroce'] = intval($price * abs(intval($chargeConfigs['charge_ratio'])));
        $data['charge_order'] = '';
        $result = D('credit_charge')->add($data);
        $res = array();
        if ($result) {
            $data['charge_id'] = $result;
            $res['status'] = 1;
            $res['info'] = 'OK';
            switch ($type) {
                case 0: $res['request_url'] = $this->alipay($data); break;
                case 1: $res['request_url'] = $this->weixin($data); break;
                default: $res['request_url'] = '';
            }
        } else {
            $res['status'] = 0;
            $res['info'] = '充值创建失败';
        }

        exit(json_encode($res));
    }

    protected function alipay(array $data)
    {
        $chargeConfigs = model('Xdata')->get('admin_Config:charge');
        $configs = $parameter = array();
        $configs['partner'] = $chargeConfigs['alipay_pid'];
        $configs['seller_email'] = $chargeConfigs['alipay_email'];
        $configs['key'] = $chargeConfigs['alipay_key'];
        $parameter = array(
            'notify_url'   => SITE_URL.'/alipay_notify.php',
            'return_url'   => SITE_URL.'/alipay_return.php',
            'out_trade_no' => $data['serial_number'],
            'subject'      => '积分充值:'.$data['charge_sroce'].'积分',
            'total_fee'    => $data['charge_value'],
            //"total_fee"	=> 0.01,
            'body'     => '',
            'show_url' => '',
            'app'      => 'public',
            'mod'      => 'Account',
            'act'      => 'scorecharge',
        );

        return createAlipayUrl($configs, $parameter);
    }

    public function alipayReturn()
    {
        unset($_GET['app'], $_GET['mod'], $_GET['act']);
        unset($_REQUEST['app'], $_REQUEST['mod'], $_REQUEST['act']);
        $chargeConfigs = model('Xdata')->get('admin_Config:charge');
        $configs = array(
            'partner'      => $chargeConfigs['alipay_pid'],
            'seller_email' => $chargeConfigs['alipay_email'],
            'key'          => $chargeConfigs['alipay_key'],
        );
        if (verifyAlipayReturn($configs)) {
            if (model('Credit')->charge_success(t($_GET['out_trade_no']))) {
                $this->assign('jumpUrl', U('public/Account/scoredetail'));
                $this->success('积分充值成功');
            } else {
                $this->redirect('public/Account/scoredetail');
            }
        } else {
            $map = array(
                'uid'           => $this->mid,
                'serial_number' => t($_GET['out_trade_no']),
                'status'        => 0, // 这个条件不能删，删了就有充值漏洞
            );
            D('credit_charge')->where($map)->setField('status', 2);
            $this->assign('jumpUrl', U('public/Account/scoredetail'));
            $this->error('积分充值失败');
        }
    }

    public function alipayNotify()
    {
        unset($_GET['app'], $_GET['mod'], $_GET['act']);
        unset($_REQUEST['app'], $_REQUEST['mod'], $_REQUEST['act']);
        header('Content-type:text/html;charset=utf-8');
        $chargeConfigs = model('Xdata')->get('admin_Config:charge');
        $configs = array(
            'partner'      => $chargeConfigs['alipay_pid'],
            'seller_email' => $chargeConfigs['alipay_email'],
            'key'          => $chargeConfigs['alipay_key'],
        );
        if (verifyAlipayNotify($configs)) {
            model('Credit')->charge_success(t($_POST['out_trade_no']));
        }
        exit;
    }
}
