<?php
/**
 * 注册模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class RegisterModel extends Model
{
    private $_config;                                                                    // 注册配置字段
    private $_user_model;                                                                // 用户模型对象字段
    private $_error;                                                                    // 错误信息字段
    private $_email_reg = '/[_a-zA-Z\d\-\.]+(@[_a-zA-Z\d\-\.]+\.[_a-zA-Z\d\-]+)+$/i';        // 邮箱正则规则
    private $_mobile_reg = '/^1[34578][0-9]{1}[0-9]{8}$/';                        //手机正在规则
    private $_name_reg = "/^[\x{4e00}-\x{9fa5}A-Za-z0-9_\.]+$/u";                            // 昵称正则规则
    private $_phone_reg = '/^1[34578][0-9]{1}[0-9]{8}$/';

    /**
     * 初始化操作，获取注册配置信息；实例化用户模型对象
     */
    public function __construct()
    {
        parent::__construct();
        $this->_config = model('Xdata')->get('admin_Config:register');
        $this->_user_model = model('User');
    }

    /**
     * 验证邀请邮件内容的正确性
     * @param  string $email     邀请邮箱的信息
     * @param  string $old_email 原始邮箱的信息
     * @return bool   是否验证成功
     */
    public function isValidEmail_invite($email, $old_email = null)
    {
        $res = preg_match($this->_email_reg, $email, $matches) !== 0;
        if (!$res) {
            $this->_error = L('PUBLIC_EMAIL_TIPS');            // 无效的Email地址
        } elseif (!empty($this->_config['email_suffix'])) {
            $res = in_array($matches['1'], explode(',', $this->_config['email_suffix']));
            // !$res && $this->_error =L('PUBLIC_EMAIL_SUFFIX_FORBIDDEN');			// 邮箱后缀不允许注册
            !$res && $this->_error = '该邮箱后缀不允许注册';            // 邮箱后缀不允许注册
        }
        if ($res && ($email != $old_email) && $this->_user_model->where('`email`="'.mysql_escape_string($email).'"')->find()) {
            $this->_error = L('PUBLIC_ACCOUNT_REGISTERED');            // 该用户已注册
            $res = false;
        }

        return (boolean) $res;
    }

    /**
     * 验证邮箱正确性
     *
     * @param  string $email    邮箱地址
     * @param  string $oldEmail 旧邮箱地址
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function isValidEmail($email, $oldEmail = null)
    {
        // # 判断邮箱格式正确性
        if (!preg_match($this->_email_reg, $email, $matches)) {
            $this->_error = '无效的Email地址';

            return false;

        // # 判断是否是规定的后缀
        } elseif (!empty($this->_config['email_suffix']) and !in_array($matches['1'], explode(',', $this->_config['email_suffix']))) {
            $this->_error = '该邮箱后缀不允许注册';

            return false;

        // # 判断是否被注册
        } elseif (!$this->_user_model->isChangeEmail($email, $this->_user_model->where('`email` LIKE "'.$oldEmail.'"')->field('uid')->getField('uid'))) {
            $this->_error = '该Email已被注册';

            return false;
        }

        return true;
    }

    /**
     * 验证邮箱内容的正确性
     *
     * @param string $email
     *                           输入邮箱的信息
     * @param  string $old_email
     *                           原始邮箱的信息
     * @return bool   是否验证成功
     */
    // public function isValidEmail($email, $old_email = null) {
    // 	$res = preg_match ( $this->_email_reg, $email, $matches ) !== 0;
    // 	$mobile_res = preg_match ( $this->_mobile_reg, $email, $matches ) !== 0;
    // 	if (! $res && ! $mobile_res) {
    // 		$this->_error = '无效的账号格式';
    // 	} else if ($res && ! $mobile_res) {
    // 		if (! empty ( $this->_config ['email_suffix'] )) {
    // 			$res = in_array ( $matches ['1'], explode ( ',', $this->_config ['email_suffix'] ) );
    // 			// !$res && $this->_error = $matches['1'].L('PUBLIC_EMAIL_SUFFIX_FORBIDDEN'); // 邮箱后缀不允许注册
    // 			! $res && $this->_error = '该邮箱后缀不允许注册'; // 邮箱后缀不允许注册
    // 		}
    // 		if ($res && ($email != $old_email) && $this->_user_model->where ( '`email`="' . mysql_escape_string ( $email ) . '"' )->find ()) {
    // 			$this->_error = L ( 'PUBLIC_EMAIL_REGISTER' ); // 该Email已被注册
    // 			$res = false;
    // 		}
    // 	} else if ($mobile_res && ! $res) {
    // 		if ($mobile_res && ($email != $old_email) && $this->_user_model->where ( '`email`="' . $email . '"' )->find ()) {
    // 			$this->_error = '该Email已被注册'; // 该Email已被注册
    // 			$res = false;
    // 		} else {
    // 			$res = true;
    // 		}
    // 	}
    // 	return ( boolean ) $res;
    // }

    public function isValidPhone($phone, $old_phone = null)
    {
        $res = preg_match($this->_phone_reg, $phone, $matches) !== 0;
        if (!$res) {
            $this->_error = '无效的手机号';
        }
        if ($res && $this->_user_model->where('`phone`="'.mysql_escape_string($phone).'"')->find() and $phone != $old_phone) {
            $this->_error = '该手机号已被注册';
            $res = false;
        }

        return (boolean) $res;
    }

    /**
     * 验证注册验证码是否正确
     *
     * @param  int   $code  验证码
     * @param  float $phone 手机号码
     * @return bool
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function isValidRegCode($code, $phone)
    {
        /* # 安全过滤 */
        $code = intval($code);
        $phone = floatval($phone);

        /* # 验证手机号码是否为空 */
        if (!$phone) {
            $this->_error = '手机号不能为空';

            return false;

        /* # 验证验证码是否为空 */
        } elseif (!$code) {
            $this->_error = '验证码不能为空';

            return false;

        /* # 验证验证码是否错误 */
        } elseif (($sms = model('Sms')) and !$sms->CheckCaptcha($phone, $code)) {
            $this->_error = $sms->getMessage();
            unset($sms);

            return false;
        }

        return true;
    }

    /*public function isValidRegCode($regCode, $phone) {
        if (empty($phone)) {
            $this->_error = '手机号不能为空';
            return false;
        }
        if (empty($regCode)) {
            $this->_error = '验证码不能为空';
            return false;
        }
        $res = model('Captcha')->checkRegisterCode($phone , $regCode);
        if (!$res) {
            $this->_error = '验证码错误';
        }

        return (boolean)$res;
    }*/

    /**
     * 验证昵称内容的正确性
     * @param  string $name     输入昵称的信息
     * @param  string $old_name 原始昵称的信息
     * @return bool   是否验证成功
     */
    public function isValidName($name, $old_name = null)
    {
        // 默认不准使用的昵称
        $protected_name = array('name', 'uname', 'admin', 'profile', 'space');
        $site_config = model('Xdata')->get('admin_Config:site');
        !empty($site_config['sys_nickname']) && $protected_name = array_merge($protected_name, explode(',', $site_config['sys_nickname']));
        if (in_array($name, $protected_name)) {
            $this->_error = L('PUBLIC_NICKNAME_RESERVED');                // 抱歉，该昵称不允许被使用
            return false;
        }
        //不能为手机号格式
        $phone_reg = preg_match($this->_phone_reg, $name) !== 0;
        if ($phone_reg) {
            $this->_error = '用户名不能为手机号格式';

            return false;
        }
        //其他格式
        $res = preg_match($this->_name_reg, $name) !== 0;
        if ($res) {
            $length = get_str_length($name);
            $res = ($length >= 2 && $length <= 10);
            if (!$res) {
                $this->_error = L('PUBLIC_NICKNAME_LIMIT', array('nums' => '2-10'));            // 昵称长度必须在2-10个汉字之间
                return false;
            }
        } else {
            $this->_error = '昵称仅支持中英文，数字，下划线';

            return false;
        }

        $user = \Ts\Models\User::existent()->byUserName($name)->first();

        if (!$old_name && $user) {
            $this->_error = '该用户名已经存在';

            return false;
        } elseif ($old_name) {
            $old_user = \Ts\Models\User::existent()->byUserName($old_name)->first();
            if ($name != $old_name && $old_user && $user && $old_user->uid != $user->uid) {
                $this->_error = '该用户名已经存在';

                return false;
            }
        }

        //敏感词
        if (filter_keyword($name) !== $name) {
            $this->_error = '抱歉，该昵称包含敏感词不允许被使用';

            return false;
        }

        return true;
    }

    /**
     * 验证密码内容的正确性
     * @param  string $pwd   密码信息
     * @param  string $repwd 确认密码信息
     * @return bool   是否验证成功
     */
    public function isValidPassword($pwd, $repwd)
    {
        $res = true;
        $length = strlen($pwd);
        if ($length < 6) {
            $this->_error = L('PUBLIC_PASSWORD_TIPS');            // 密码太短了，最少6位
            $res = false;
        } elseif ($length > 15) {
            $this->_error = '密码太长了，最多15位';
            $res = false;
        } elseif ($pwd !== $repwd) {
            $this->_error = L('PUBLIC_PASSWORD_UNSIMILAR');        // 新密码与确认密码不一致
            $res = false;
        }

        return $res;
    }

    public function isValidPasswordNoRepeat($pwd)
    {
        $res = true;
        if (!preg_match('/^[a-zA-Z0-9]+$/', $pwd)) {
            $this->_error = L('密码只能包含字母和数字');
            $res = false;

            return $res;
        }
        $length = strlen($pwd);
        if ($length < 6) {
            $this->_error = L('PUBLIC_PASSWORD_TIPS');            // 密码太短了，最少6位
            $res = false;
        } elseif ($length > 15) {
            $this->_error = '密码太长了，最多15位';
            $res = false;
        }

        return $res;
    }

    /**
     * 审核用户
     * @param  array $uids 用户UID数组
     * @param  int   $type 类型，0表示取消审核，1表示通过审核
     * @return bool  是否审核成功
     */
    public function audit($uids, $type = 1)
    {
        // 处理数据
        !is_array($uids) && $uids = explode(',', $uids);
        $uids = array_unique(array_filter(array_map('intval', $uids)));
        // 审核指定用户
        $map['uid'] = array('IN', $uids);
        $result = $this->_user_model->where($map)->setField('is_audit', $type);
        model('User')->cleanCache($uids);
        if (!$result) {
            $this->_error = L('PUBLIC_REVIEW_FAIL');        // 审核失败
            return false;
        } else {
            if ($type == 0) {
                $this->_error = L('PUBLIC_CANCEL_REVIEW_SUCCESS');        // 取消审核成功
                // 发送取消审核邮件
                foreach ($uids as $touid) {
                    model('Notify')->sendNotify($touid, 'audit_error');
                }

                return true;
            }

            // 发送通过审核邮件
            foreach ($uids as $uid) {
                $this->sendActivationEmail($uid, 'audit_ok');
            }
            $this->_error = L('PUBLIC_REVIEW_SUCCESS');        // 审核成功
            return true;
        }
    }

    /**
     * 给指定用户发送激活账户邮件
     * @param  int    $uid  用户UID
     * @param  string $node 邮件模板类型
     * @return bool   是否发送成功
     */
    public function sendActivationEmail($uid, $node = 'register_active')
    {
        $map['uid'] = $uid;
        $user_info = $this->_user_model->where($map)->find();

        if (!$user_info) {
            $this->_error = L('PUBLI_USER_NOTEXSIT');            // 用户不存在
            return false;
        } elseif ($user_info['is_audit']) {
            if ($user_info['is_active'] == 1) {
                $config['activeurl'] = $GLOBALS['ts']['site']['home_url'];
            } else {
                $code = $this->getActivationCode($user_info);
                $config['activeurl'] = U('public/Register/activate', array('uid' => $uid, 'code' => $code));
            }
            $config['name'] = $user_info['uname'];
            model('Notify')->sendNotify($uid, $node, $config);
            $this->_error = '发送成功';        // 系统已将一封激活邮件发送至您的邮箱，请立即查收邮件激活帐号
            return true;
        } else {
            $this->_error = !$user_info['is_audit'] ? L('PUBLIC_ACCOUNT_REVIEW_FAIL') : L('PUBLIC_ACCOUNT_ACTIVATED_SUCCESSFULLY');        // 您的帐号未通过审核，恭喜，帐号已成功激活
            return false;
        }
    }

    /**
     * 激活指定用户
     * @param  int    $uid  用户UID
     * @param  string $code 激活码
     * @return bool   是否激活成功
     */
    public function activate($uid, $code)
    {
        $map['uid'] = $uid;
        $user_info = $this->_user_model->where($map)->find();

        $res = ($code == $this->getActivationCode($user_info));
        if ($res && !$user_info['is_active']) {
            $res = $this->_user_model->where($map)->save(array('is_active' => 1));
            $this->_user_model->cleanCache($uid);
        }

        if ($res) {
            $this->_error = L('PUBLIC_ACCOUNT_ACTIVATED_SUCCESSFULLY');        // 恭喜，帐号已成功激活
            return true;
        } else {
            $this->_error = L('PUBLIC_ACTIVATE_USER_FAIL');            // 激活用户失败
            return false;
        }
    }

    /**
     * 获取激活码
     * @param  array  $user_info 用户的相关信息
     * @return string 激活码
     */
    public function getActivationCode($user_info)
    {
        return md5($user_info['login'].$user_info['password'].$user_info['login_salt']);
    }

    /**
     * 初始化用户账号
     * @param  int  $uid 用户UID
     * @return bool 是否成功初始化用户账号
     */
    public function initUser($uid)
    {
        $map['uid'] = $uid;
        $user_info = $this->_user_model->where($map)->find();
        $user_info['is_active'] && $res = $this->_user_model->where($map)->save(array('is_init' => 1));
        // 清除用户缓存
        $this->_user_model->cleanCache($uid);

        if ($res) {
            $this->_error = L('PUBLIC_ACCOUNT_INITIALIZE_SUCCESS');            // 帐号初始化成功
            return true;
        } else {
            $this->_error = L('PUBLIC_ACCOUNT_INITIALIZE_FAIL');            // 帐号初始化失败
            return false;
        }
    }

    /**
     * 获取最后的错误信息
     * @return string 最后的错误信息
     */
    public function getLastError()
    {
        return $this->_error;
    }

    /**
     * 修改指定用户的注册邮箱
     * @param  int    $uid   用户ID
     * @param  string $email 邮箱地址
     * @return bool   是否更改邮箱成功
     */
    public function changeRegisterEmail($uid, $email)
    {
        $map['uid'] = $uid;
        $map['is_active'] = 0;
        $data['login'] = $email;
        $data['email'] = $email;
        $res = $this->_user_model->where($map)->save($data);
        $res = (boolean) $res;
        if ($res) {
            $this->_error = '更换邮箱成功';
            $this->_user_model->cleanCache($uid);
        } else {
            $this->_error = '更换邮箱失败';
        }

        return $res;
    }

    /**
     * 指定用户初始化完成
     * @param  int  $uid 用户ID
     * @return bool 是否初始化成功
     */
    public function overUserInit($uid)
    {
        $map['uid'] = $uid;
        $data['is_init'] = 1;
        $res = $this->_user_model->where($map)->save($data);
        $res = (boolean) $res;
        // if($res) {
        // 	// 获取用户信息
        // 	$receiverInfo = model('User')->getUserInfo($uid);
        // 	// 获取发起邀请用户ID
        // 	$inviteUid = model('Invite')->where("code='{$receiverInfo['invite_code']}'")->getField('inviter_uid');
        // 	// 相互关注操作
        // 	model('Follow')->doFollow($uid, intval($inviteUid));
        // 	model('Follow')->doFollow(intval($inviteUid), $uid);
        //     // 清除用户缓存
        //     $this->_user_model->cleanCache($uid);
        // 	// 发送通知
        // 	$config['name'] = $receiverInfo['uname'];
        // 	$config['space_url'] = $receiverInfo['space_url'];
        // 	model('Notify')->sendNotify($inviteUid, 'register_invate_ok', $config);
        // 	$registerConfig = model('Xdata')->get('admin_Config:register');
        // 	if($registerConfig['welcome_email']){
        // 		model('Notify')->sendNotify($uid, 'register_welcome', $config);
        // 	}
        // }
        // 清除用户缓存
        $this->_user_model->cleanCache($uid);

        return $res;
    }
}
