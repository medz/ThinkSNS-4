<?php
/**
 * @author Foreach
 */
class JipuApi extends Api
{
    /********** 登录 **********/

    /**
     * 认证方法 --using.
     *
     * @param varchar login 手机号或用户名
     * @param varchar password 密码
     *
     * @return array 状态+提示
     */
    public function authorize()
    {
        $_REQUEST = array_merge($_GET, $_POST);

        if (!empty($_REQUEST['login']) && !empty($_REQUEST['password'])) {
            $username = addslashes($_REQUEST['login']);
            $password = addslashes($_REQUEST['password']);

            $map = "(phone = '{$username}' or uname='{$username}' or email='{$username}') AND is_del=0";

            //根据帐号获取用户信息
            $user = model('User')->where($map)->field('uid,password,login_salt,is_audit,is_active')->find();

            //判断用户名密码是否正确
            if ($user && md5(md5($password).$user['login_salt']) == $user['password']) {
                if (model('DisableUser')->isDisableUser($user['uid'])) {
                    return array('status' => 0, 'msg' => '您的帐号被已管理员禁用');
                }
                //如果未激活提示未激活
                if ($user['is_audit'] != 1) {
                    return array('status' => 0, 'msg' => '您的帐号尚未通过审核');
                }
                if ($user['is_active'] != 1) {
                    return array('status' => 0, 'msg' => '您的帐号尚未激活,请进入邮箱激活');
                }

                //记录token
                $data['uid'] = $user['uid'];
                $data['user'] = model('User')->getUserInfo($data['uid']);
                $passport = D('User')->where(array('uid'=>$user['uid']))->field('password,login_salt')->find();
                $data['user']['login_salt'] = $passport['login_salt'];
                $data['user']['password'] = $passport['password'];

                $login = D('')->table(C('DB_PREFIX').'login')->where('uid='.$user['uid']." AND type='location'")->find();
                if (!$login) {
                    $data['oauth_token'] = getOAuthToken($user['uid']);
                    $data['oauth_token_secret'] = getOAuthTokenSecret();
                    $savedata['type'] = 'location';
                    $savedata = array_merge($savedata, $data);
                    D('')->table(C('DB_PREFIX').'login')->add($savedata);
                } else {
                    $data['oauth_token'] = $login['oauth_token'];
                    $data['oauth_token_secret'] = $login['oauth_token_secret'];
                }

                $data['status'] = 1;

                return $data;
            } else {
                return array('status' => 0, 'msg' => '用户名或密码错误');
            }
        } else {
            return array('status' => 0, 'msg' => '用户名或密码不能为空');
        }
    }

    /********** 验证 **********/

    /**
     * 认证方法 --using.
     *
     * @param varchar oauth_token
     * @param varchar oauth_token_secret
     *
     * @return array 状态+提示
     */
    public function login_check()
    {
        if ($this->mid && ($this->mid == $this->data['uid'])) {
            $user = model('User')->getUserInfo($this->mid);
            $passport = D('User')->where(array('uid'=>$this->mid))->field('password,login_salt')->find();
            $user['login_salt'] = $passport['login_salt'];
            $user['password'] = $passport['password'];

            return array('status' => 1, 'user' => $user);
        } else {
            return array('status' => 0, 'msg' => '参数错误');
        }
    }

    /********** 注册 **********/

    /**
     * 认证方法 --using.
     *
     * @param varchar oauth_token
     * @param varchar oauth_token_secret
     *
     * @return array 状态+提示
     */
    public function register()
    {
        $phone = floatval($this->data['phone']);    // 手机号码
        $username = 'jipu_'.t($this->data['username']);      // 用户名
        $password = $this->data['password'];         // 密码
        $sex = intval($this->data['sex']);
        in_array($sex, array(1, 2)) or
        $sex = 1;                               // 默认 男 1.男，2女

        $register = model('Register');
        $config = model('Xdata')->get('admin_Config:register'); // 配置

        /* 判断用户手机号码可用性 */
        if (!$register->isValidPhone($phone)) {
            return array(
                'status' => 0,
                'msg'    => $register->getLastError(),
            );

        /* 判断用户名是否可用 */
        } elseif (!$register->isValidName($username)) {
            return array(
                'status' => 0,
                'msg'    => $register->getLastError(),
            );

        /* 密码判断 */
        } elseif (!$register->isValidPasswordNoRepeat($password)) {
            return array(
                'status' => 0,
                'msg'    => $register->getLastError(),
            );
        }

        $userData = array(
            'login_salt' => rand(10000, 99999),     // 用户登录加密盐值
        );                                         // 用户基本资料数组
        $userData['password'] = model('User')->encryptPassword($password, $userData['login_salt']); // 用户密码
        $userData['uname'] = $username;         // 用户名
        $userData['phone'] = $phone;            // 用户手机号码
        $userData['sex'] = $sex;              // 用户性别
        $userData['ctime'] = time();            // 注册时间
        $userData['reg_ip'] = get_client_ip();   // 注册IP

        /* 用户是否默认审核 */
        $userData['is_audit'] = 1;
        $config['register_audit'] and
        $userData['is_audit'] = 0;

        $userData['is_active'] = 1; // 默认激活
        $userData['is_init'] = 1; // 默认初始化
        $userData['first_letter'] = getFirstLetter($username); // 用户首字母

        /* 用户搜索 */
        $userData['search_key'] = $username.' '.$userData['first_letter'];
        preg_match('/[\x7f-\xff]+/', $username) and
        $userData['search_key'] .= ' '.Pinyin::getShortPinyin($username, 'utf-8');

        $uid = model('User')->add($userData); // 添加用户数据
        if (!$uid) {
            return array(
                'status' => 0,
                'msg'    => '注册失败',
            );
        }                                     // 注册失败的提示

        /* 添加默认用户组 */
        $userGroup = $config['default_user_group'];
        empty($userGroup) and
            $userGroup = C('DEFAULT_GROUP_ID');
        is_array($userGroup) and
            $userGroup = implode(',', $userGroup);
        model('UserGroupLink')->domoveUsergroup($uid, $userGroup);

        /* 添加双向关注用户 */
        if (!empty($config['each_follow'])) {
            model('Follow')->eachDoFollow($uid, $config['each_follow']);
        }

        /* 添加默认关注用户 */
        $defaultFollow = $config['default_follow'];
        $defaultFollow = explode(',', $defaultFollow);
        $defaultFollow = array_diff($defaultFollow, explode(',', $config['each_follow']));
        empty($defaultFollow) or
            model('Follow')->bulkDoFollow($uid, $defaultFollow);

        return array(
            'status' => 1,
            'msg'    => '注册成功',
        );
    }

    /**
     * 修改用户信息 --using.
     *
     * @param string $uname
     *                             用户名
     * @param int    $sex
     *                             性别(1-男,2-女)
     * @param string $password
     *                             新密码
     * @param string $old_password
     *                             旧密码
     * @param string $tags
     *                             标签(多个标签之间用逗号隔开)
     */
    public function save_user_info()
    {
        $uid = $this->data['uid'];
        $save = array();
        // 修改用户昵称
        if (isset($this->data['uname'])) {
            $uname = t($this->data['uname']);
            $save['uname'] = filter_keyword($uname);
            $oldName = t($this->data['old_name']);
            $res = model('Register')->isValidName($uname);
            if (!$res) {
                $error = model('Register')->getLastError();

                return array(
                        'status' => 0,
                        'msg'    => $error,
                );
            }
            // 如果包含中文将中文翻译成拼音
            if (preg_match('/[\x7f-\xff]+/', $save['uname'])) {
                // 昵称和呢称拼音保存到搜索字段
                $save['search_key'] = $save['uname'].' '.model('PinYin')->Pinyin($save['uname']);
            } else {
                $save['search_key'] = $save['uname'];
            }
        }
        // 修改性别
        if (isset($this->data['sex'])) {
            $save['sex'] = (1 == intval($this->data['sex'])) ? 1 : 2;
        }
        // 修改密码
        if ($this->data['password']) {
            $regmodel = model('Register');
            // 验证格式
            if (!$regmodel->isValidPassword($this->data['password'], $this->data['password'])) {
                $msg = $regmodel->getLastError();
                $return = array(
                        'status' => 0,
                        'msg'    => $msg,
                );

                return $return;
            }
            // 验证新密码与旧密码是否一致
            if ($this->data['password'] == $this->data['old_password']) {
                $return = array(
                        'status' => 0,
                        'msg'    => L('PUBLIC_PASSWORD_SAME'),
                );

                return $return;
            }
            // 验证原密码是否正确
            $user = model('User')->where('`uid`='.$uid)->find();
            if (md5(md5($this->data['old_password']).$user['login_salt']) != $user['password']) {
                $return = array(
                        'status' => 0,
                        'msg'    => L('PUBLIC_ORIGINAL_PASSWORD_ERROR'),
                ); // 原始密码错误
                return $return;
            }
            $login_salt = rand(11111, 99999);
            $save['login_salt'] = $login_salt;
            $save['password'] = md5(md5($this->data['password']).$login_salt);
        }

        //修改手机号
        if ($this->data['phone']) {
            $phone = t($this->data['phone']);
            $userPhone = model('User')->where('`uid`='.$uid)->getField('phone');
            if (!model('Register')->isValidPhone($phone, $userPhone)) {
                return array(
                        'status' => 0,
                        'msg'    => model('Register')->getLastError(),
                );

                return $return;
            }
            $save['phone'] = $phone;
        }

        if (!empty($save)) {
            $res = model('User')->where('`uid`='.$this->data['uid'])->save($save);
            $res !== false && model('User')->cleanCache($uid);
            $user_feeds = model('Feed')->where('uid='.$uid)->field('feed_id')->findAll();
            if ($user_feeds) {
                $feed_ids = getSubByKey($user_feeds, 'feed_id');
                model('Feed')->cleanCache($feed_ids, $uid);
            }
        }

        return array(
                'status' => 1,
                'msg'    => '修改成功',
        );
    }
}
