<?php

class OauthApi extends Api
{
    /**
     * 新 注册接口.
     *
     * @request int    $phone     用户注册手机号码
     * @request int    $code      用户注册手机验证码
     * @request string $username  用户名
     * @request string $password  用户密码
     * @request string $intro     User intro.
     * @request int    $sex       用户性别，1：男，2：女，default:1
     * @request string $location  格式化的地区地址，format：“省 市 区/县”
     * @request int    $province  地区-县/直辖市 areaId
     * @request int    $city      地区-市/直辖市区县 areaID
     * @request int    $area      地区-区/县/直辖市村
     * @request string $avatarUrl 用户头像URL
     * @request int    $avatarW   用户头像宽度
     * @request int    $avatarH   用户头像宽度
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function signIn()
    {
        $phone = floatval($this->data['phone']);    // 手机号码
        $code = intval($this->data['code']);     // 验证码
        $username = t($this->data['username']);      // 用户名
        $password = $this->data['password'];         // 密码
        $intro = $this->data['intro'] ? formatEmoji(true, t($this->data['intro'])) : '';         // 用户简介

        $sex = intval($this->data['sex']);
        in_array($sex, array(1, 2)) or
        $sex = 1;                               // 默认 男 1.男，2女

        $location = t($this->data['location']);      // 地区文字
        $province = intval($this->data['province']); // 地区 - 省
        $city = intval($this->data['city']);     // 地区 - 市
        $area = intval($this->data['area']);     // 地区 - 区/县

        $avatarUrl = t($this->data['avatarUrl']);    // 用户头像URL
        $avatarW = intval($this->data['avatarW']); // 用户头像宽度
        $avatarH = intval($this->data['avatarH']); // 用户头像高度

        $register = model('Register');
        $config = model('Xdata')->get('admin_Config:register'); // 配置

        /* 判断用户手机号码可用性 */
        if (!$register->isValidPhone($phone)) {
            return array(
                'status'  => 0,
                'message' => $register->getLastError(),
            );

            /* 判断用户名是否可用 */
        } elseif (!$register->isValidName($username)) {
            return array(
                'status'  => 0,
                'message' => $register->getLastError(),
            );

            /* 判断验证码是否正确 */
        } elseif (!$register->isValidRegCode($code, $phone)) {
            return array(
                'status'  => 0,
                'message' => $register->getLastError(),
            );

            /* 判断头像传递信息是否完整 */
        } elseif (!$avatarUrl or !$avatarW or !$avatarH) {
            return array(
                'status'  => 0,
                'message' => '用户头像上传不完整',
            );

            /* 密码判断 */
        } elseif (!$register->isValidPasswordNoRepeat($password)) {
            return array(
                'status'  => 0,
                'message' => $register->getLastError(),
            );

            /* 格式化地区地址判断 */
        } elseif (!$location) {
            return array(
                'status'  => 0,
                'message' => '格式化地区地址不能为空',
            );

            /* 地区判断 */
        } elseif (!$province or !$city) {
            return array(
                'status'  => 0,
                'message' => '请完整的选择地区',
            );
        }

        $userData = array(
            'login_salt' => rand(10000, 99999),     // 用户登录加密盐值
        );                                         // 用户基本资料数组
        $userData['password'] = model('User')->encryptPassword($password, $userData['login_salt']); // 用户密码
        $userData['uname'] = $username;         // 用户名
        $userData['phone'] = $phone;            // 用户手机号码
        $userData['sex'] = $sex;              // 用户性别
        $userData['location'] = $location;         // 格式化地址
        $userData['province'] = $province;         // 省
        $userData['city'] = $city;
        $userData['area'] = $area;             // 地区
        $userData['intro'] = $intro;            // 用户简介
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
                'status'  => 0,
                'message' => '注册失败',
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

        /* 保存用户头像 */
        $avatarData = array(
            'picurl'   => $avatarUrl, // 用户头像地址
            'picwidth' => $avatarW,    // 用户头像宽度
        );
        $scaling = 5;              // 未知参数
        $avatarData['w'] = $avatarW * $scaling;
        $avatarData['h'] = $avatarH * $scaling;
        $avatarData['x1'] = 0;
        $avatarData['y1'] = 0;
        $avatarData['x2'] = $avatarData['w'];
        $avatarData['y2'] = $avatarData['h'];
        model('Avatar')->init($uid)->dosave($avatarData, true);

        if ($userData['is_audit'] == 1) {
            $_POST['login'] = $phone;
            $_POST['password'] = $password;

            return $this->authorize();
        }

        return array(
            'status'  => 2,
            'message' => '注册成功，请等待审核',
        );
    }

    /********** 登录注销 **********/

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

                //直播用户信息
                $query['uid'] = $user['uid'];
                if ($live_user_info = D('live_user_info')->where(array('uid' => $user['uid']))->find()) {
                    $query['ticket'] = $live_user_info['ticket'];
                }
                $params = http_build_query($query);
                $live_user_info = file_get_contents(SITE_URL.'/api.php?api_type=live&mod=LiveUser&act=postUser&'.$params);
                $live_user_info = json_decode($live_user_info, true);
                $live_user_info['status'] == 1 && $ticket = $live_user_info['data']['ticket'];

                //记录token
                $data['oauth_token'] = getOAuthToken($user['uid']);
                $data['oauth_token_secret'] = getOAuthTokenSecret();
                $data['uid'] = $user['uid'];
                $login = D('')->table(C('DB_PREFIX').'login')->where('uid='.$user['uid']." AND type='location'")->find();
                if (!$login) {
                    $savedata['type'] = 'location';
                    $savedata = array_merge($savedata, $data);
                    D('')->table(C('DB_PREFIX').'login')->add($savedata);
                } else {
                    //清除缓存
                    model('Cache')->rm($login['oauth_token'].$login['oauth_token_secret']);
                    D('')->table(C('DB_PREFIX').'login')->where('uid='.$user['uid']." AND type='location'")->save($data);
                }

                $data['ticket'] = $ticket;
                $data['status'] = 1;

                return $data;
            } else {
                return array('status' => 0, 'msg' => '用户名或密码错误');
            }
        } else {
            return array('status' => 0, 'msg' => '用户名或密码不能为空');
        }
    }

    /**
     * 注销帐号，刷新token --using.
     *
     * @param varchar login 手机号或用户名
     *
     * @return array 状态+提示
     */
    public function logout()
    {
        $login = $this->data['login'];
        $login = addslashes($login);

        $where = '`is_del` = 0 AND (`uid` = \'__LOGIN__\' OR `phone` = \'__LOGIN__\' OR `email` = \'__LOGIN__\' OR `uname` = \'__LOGIN__\')';
        $where = str_replace('__LOGIN__', $login, $where);

        //判断密码是否正确
        $user = model('User')->where($where)->field('uid')->find();
        if ($user) {
            $data['oauth_token'] = getOAuthToken($user['uid']);
            $data['oauth_token_secret'] = getOAuthTokenSecret();
            $data['uid'] = $user['uid'];
            D('')->table(C('DB_PREFIX').'login')->where('uid='.$user['uid']." AND type='location'")->save($data);

            return array('status' => 1, 'msg' => '退出成功');
        } else {
            return array('status' => 0, 'msg' => '退出失败');
        }
    }

    /********找回密码*********/

    /**
     * 发送短信验证码
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function sendCodeByPhone()
    {
        $login = t($this->data['login']);

        $where = '`is_del` = 0 AND (`uid` = \'__LOGIN__\' OR `phone` = \'__LOGIN__\' OR `email` = \'__LOGIN__\' OR `uname` = \'__LOGIN__\')';
        $where = str_replace('__LOGIN__', $login, $where);

        $phone = model('User')->where($where)->field('`phone`')->getField('phone');

        if (!$phone) {
            return array(
                'status'  => 0,
                'message' => '该用户没有绑定手机号码，或者用户不存在！',
            );
        } elseif (!model('Sms')->sendCaptcha($phone, false)) {
            return array(
                'status'  => -1,
                'message' => model('Sms')->getMessage(),
            );
        }

        return array(
            'status'  => 1,
            'message' => '发送成功！',
        );
    }

    /**
     * 判断手机验证码是否正确.
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function checkCodeByPhone()
    {
        $login = t($this->data['login']);
        $code = intval($this->data['code']);

        $where = '`is_del` = 0 AND (`uid` = \'__LOGIN__\' OR `phone` = \'__LOGIN__\' OR `email` = \'__LOGIN__\' OR `uname` = \'__LOGIN__\')';
        $where = str_replace('__LOGIN__', $login, $where);

        $phone = model('User')->where($where)->field('`phone`')->getField('phone');

        if (!$phone) {
            return array(
                'status'  => 0,
                'message' => '用户不存在或者没有绑定手机号码',
            );
        } elseif (!$code) {
            return array(
                'status'  => -1,
                'message' => '验证码不能为空',
            );
        } elseif (!model('Sms')->CheckCaptcha($phone, $code)) {
            return array(
                'status'  => -2,
                'message' => model('Sms')->getMessage(),
            );
        }

        return array(
            'status'  => 1,
            'message' => '验证码正确',
        );
    }

    /**
     * 保存用户密码
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function saveUserPasswordByPhone()
    {
        $login = t($this->data['login']);
        $password = t($this->data['password']);
        $code = intval($this->data['code']);

        $where = '`is_del` = 0 AND (`uid` = \'__LOGIN__\' OR `phone` = \'__LOGIN__\' OR `email` = \'__LOGIN__\' OR `uname` = \'__LOGIN__\')';
        $where = str_replace('__LOGIN__', $login, $where);

        $phone = model('User')->where($where)->field('`phone`')->getField('phone');

        if (!$phone) {
            return array(
                'status'  => 0,
                'message' => '用户不存在或者没有绑定手机号码',
            );
        } elseif (!$code) {
            return array(
                'status'  => -1,
                'message' => '验证码不能为空',
            );
        } elseif (!model('Register')->isValidPasswordNoRepeat($password)) {
            return array(
                'status'  => -2,
                'message' => model('Register')->getLastError(),
            );
        } elseif (!model('Sms')->CheckCaptcha($phone, $code)) {
            return array(
                'status'  => -3,
                'message' => model('Sms')->getMessage(),
            );
        }

        $data = array();
        $data['login_salt'] = rand(10000, 99999);
        $data['password'] = model('User')->encryptPassword($password, $data['login_salt']);

        if (model('User')->where('`phone` = '.$phone)->save($data)) {
            return array(
                'status'  => 1,
                'message' => '修改成功',
            );
        }

        return array(
            'status'  => -4,
            'message' => '修改失败',
        );
    }

    /********** 注册 **********/

    /**
     * 发送注册验证码 --using.
     *
     * @param varchar phone 手机号
     *
     * @return array 状态值+提示信息
     */
    // public function send_register_code(){
    // 	$phone = t( $_POST['phone'] );
    // 	if(!$phone) return array('status'=>0,'msg'=>'请输入手机号');
    // 	$from = 'mobile';

    // 	$regmodel = model('Register');
    // 	if($phone && !$regmodel->isValidPhone($phone)) {
    // 		$msg = $regmodel->getLastError();
    // 		$return = array('status'=>0, 'msg'=>$msg);
    // 		return $return;
    // 	}
    // 	$smsModel = model( 'Sms' );
    // 	$res = $smsModel->sendRegisterCode( $phone , $from );
    // 	if ( $res ){
    // 		$data['status'] = 1;
    // 		$data['msg'] = '发送成功！';
    // 	} else {
    // 		$data['status'] = 0;
    // 		$data['msg'] = $smsModel->getError();
    // 	}
    // 	return $data;
    // }

    /**
     * 发送注册验证码
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function send_register_code()
    {
        $phone = floatval($_REQUEST['phone']);

        /* # 检查是否可以已经被注册 */
        if (!model('User')->isChangePhone($phone)) {
            $this->error(array(
                'status' => 0,
                'msg'    => '该手机已经存在，无法再次注册',
            ));

            /* # 检查是否发送失败 */
        } elseif (($sms = model('Sms')) and !$sms->sendCaptcha($phone, true)) {
            $this->error(array(
                'status' => 0,
                'msg'    => $sms->getMessage(),
            ));
        }

        return array(
            'status' => 1,
            'msg'    => '发送成功！',
        );
    }

    // /**
    //  * 判断手机注册验证码是否正确 --using
    //  * @param varchar phone 手机号
    //  * @param varchar regCode 验证码
    //  * @return array 状态值+提示信息
    //  */
    // public function check_register_code(){
    // 	$phone = t($this->data['phone']);
    // 	$regCode = intval($this->data['regCode']);

    // 	if ( !model('Sms')->checkRegisterCode( $phone , $regCode ) ){
    // 		$return = array('status'=>0, 'msg'=>'验证码错误');
    // 	}else{
    // 		$return = array('status'=>1, 'msg'=>'验证通过');
    // 	}
    // 	return $return;
    // }

    /**
     * 判断手机注册验证码是否正确.
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function check_register_code()
    {
        $phone = floatval($_REQUEST['phone']);
        $code = intval($_REQUEST['regCode']);
        $sms = model('Sms');

        /* # 判断验证码是否正确 */
        if ($sms->CheckCaptcha($phone, $code)) {
            return array(
                'status' => 1,
                'msg'    => '验证通过',
            );
        }

        return array(
            'status' => 0,
            'msg'    => $sms->getMessage(),
        );
    }

    /**
     * 注册上传头像 --using.
     *
     * @return array 状态值+提示信息
     */
    public function register_upload_avatar()
    {
        $dAvatar = model('Avatar');
        $res = $dAvatar->upload(true);

        return $res;
    }

    /**
     * 注册帐号 --using.
     *
     * @param varchar phone 手机号
     * @param varchar regCode 验证码
     * @param varchar uname 用户名
     * @param varchar password 密码
     * @param int sex 性别 1-男 2-女
     * @param varchar avatar_url 头像地址
     * @param int avatar_width 头像宽度
     * @param int avatar_height 头像高度
     *
     * @return array 状态值+提示信息
     */
    public function register()
    {
        $regmodel = model('Register');
        $registerConfig = model('Xdata')->get('admin_Config:register');

        $phone = t($_POST['phone']);
        $regCode = t($_POST['regCode']);
        $uname = t($_POST['uname']);
        $sex = intval($_POST['sex']);
        $password = t($_POST['password']);
        //return array('status'=>0, 'msg'=>'注册失败，必须设置头像');
        if (in_array('face', $registerConfig['personal_required']) && $_POST['avatar_url'] == '') {
            return array('status' => 0, 'msg' => '注册失败，请上传头像');
        }
        $avatar['picurl'] = $_POST['avatar_url'];
        $avatar['picwidth'] = intval($_POST['avatar_width']);
        $avatar['picheight'] = intval($_POST['avatar_height']);

        // //手机号验证
        // if ( !model('Sms')->checkRegisterCode( $phone , $regCode ) ){
        // 	$return = array('status'=>0, 'msg'=>'验证码错误');
        // }

        /* # 验证手机号码 */
        if (($sms = model('Sms')) and !$sms->CheckCaptcha($phone, $regCode)) {
            return array(
                'status' => 0,
                'msg'    => $sms->getMessage(),
            );
        }
        unset($sms);

        if (!$regmodel->isValidPhone($phone)) {
            $msg = $regmodel->getLastError();
            $return = array('status' => 0, 'msg' => $msg);

            return $return;
        }
        /*
        //头像验证
        if($avatar['picurl'] && $avatar['picwidth'] && $avatar['picheight']){
            //code
        }else{
            $required = $this->registerConfig['personal_required'];
            if(in_array('face', $required)) return array('status'=>0, 'msg'=>'请上传头像');
        }*/
        //用户名验证
        if (!$regmodel->isValidName($uname)) {
            $msg = $regmodel->getLastError();
            $return = array('status' => 0, 'msg' => $msg);

            return $return;
        }
        //密码验证
        if (!$regmodel->isValidPasswordNoRepeat($password)) {
            $msg = $regmodel->getLastError();
            $return = array('status' => 0, 'msg' => $msg);

            return $return;
        }
        //开始注册
        $login_salt = rand(11111, 99999);
        $map['uname'] = $uname;
        $map['sex'] = $sex;
        $map['login_salt'] = $login_salt;
        $map['password'] = md5(md5($password).$login_salt);
        $map['phone'] = $_POST['login'] = $phone;
        $map['ctime'] = time();
        $map['is_audit'] = $registerConfig['register_audit'] ? 0 : 1;
        // $map['is_audit'] = 1;
        $map['is_active'] = 1; //手机端不需要激活
        $map['is_init'] = 1; //手机端不需要初始化步骤
        $map['first_letter'] = getFirstLetter($uname);
        $map['intro'] = $_POST['intro'] ? formatEmoji(true, $_POST['intro']) : '';
        if (preg_match('/[\x7f-\xff]+/', $map['uname'])) {    //如果包含中文将中文翻译成拼音
            $map['search_key'] = $map['uname'].' '.model('PinYin')->Pinyin($map['uname']);
        } else {
            $map['search_key'] = $map['uname'];
        }
        $uid = model('User')->add($map);
        if ($uid) {
            //第三方登录数据写入
            if (isset($this->data['type'])) {
                $other['oauth_token'] = addslashes($this->data['access_token']);
                $other['oauth_token_secret'] = addslashes($this->data['refresh_token']);
                $other['type'] = addslashes($this->data['type']);
                $other['type_uid'] = addslashes($this->data['type_uid']);
                $other['uid'] = $uid;
                M('login')->add($other);
            }
            // 添加至默认的用户组
            $userGroup = empty($registerConfig['default_user_group']) ? C('DEFAULT_GROUP_ID') : $registerConfig['default_user_group'];
            model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));
            // 添加双向关注用户
            $eachFollow = $registerConfig['each_follow'];
            if (!empty($eachFollow)) {
                model('Follow')->eachDoFollow($uid, $eachFollow);
            }
            // 添加默认关注用户
            $defaultFollow = $registerConfig['default_follow'];
            $defaultFollow = array_diff(explode(',', $defaultFollow), explode(',', $eachFollow));
            if (!empty($defaultFollow)) {
                model('Follow')->bulkDoFollow($uid, $defaultFollow);
            }

            //保存头像
            if ($avatar['picurl'] && $avatar['picwidth'] && $avatar['picheight']) {
                $dAvatar = model('Avatar');
                $dAvatar->init($uid);
                $data['picurl'] = $avatar['picurl'];
                $data['picwidth'] = $avatar['picwidth'];
                $scaling = 5;
                $data['w'] = $avatar['picwidth'] * $scaling;
                $data['h'] = $avatar['picheight'] * $scaling;
                $data['x1'] = $data['y1'] = 0;
                $data['x2'] = $data['w'];
                $data['y2'] = $data['h'];
                $dAvatar->dosave($data, true);
            }

            if ($map['is_audit'] == 1) {
                return $this->authorize();
                // 				$return = array('status'=>1, 'msg'=>'注册成功', 'need_audit'=>0);
            } else {
                $return = array('status' => 1, 'msg' => '注册成功，请等待审核', 'need_audit' => 1);
            }

            return $return;
        } else {
            $return = array('status' => 0, 'msg' => '注册失败');

            return $return;
        }
    }

    // function test123(){
    // 	return  M('sms')->order('ID desc')->find();
    // }

    /**
     * 记录或获取第三方登录接口获取到的信息 --using.
     *
     * @param varchar type 帐号类型
     * @param varchar type_uid 第三方用户标识
     * @param varchar access_token 第三方access token
     * @param varchar refresh_token 第三方refresh token（选填，根据第三方返回值）
     * @param varchar expire_in 过期时间（选填，根据第三方返回值）
     *
     * @return array 状态+提示信息/数据
     */
    public function get_other_login_info()
    {
        $type = addslashes($this->data['type']);
        $type_uid = addslashes($this->data['type_uid']);
        $access_token = addslashes($this->data['access_token']);
        $refresh_token = addslashes($this->data['refresh_token']);
        $openid = addslashes($this->data['openid']);
        $expire = intval($this->data['expire_in']);
        if (!empty($type) && !empty($type_uid)) {
            $user = M('login')->where("type_uid='{$type_uid}' AND type='{$type}'")->find();

            //目前微信登录根据unionid判断  老用户通过openid登录时 判断并生成一条unionid绑定的登录信息
            if (!$user && !empty($openid)) {
                $user = M('login')->where("type_uid='{$openid}' AND type='{$type}'")->find();
                if (!empty($user)) {
                    $unionid = $this->getUnionId($access_token, $openid);
                    if ($unionid == $type_uid) {
                        $newdata['uid'] = $user['uid'];
                        $newdata['type_uid'] = $type_uid; //存入新的unionid
                        $newdata['type'] = $user['type'];
                        $newdata['oauth_token'] = $user['oauth_token'];
                        $newdata['oauth_token_secret'] = $user['oauth_token_secret'];
                        $newdata['is_sync'] = $user['is_sync'];

                        M('login')->add($newdata);
                    }
                }
            }

            if ($user && $user['uid'] > 0) {
                if (model('DisableUser')->isDisableUser($user['uid'])) {
                    return array('status' => -1, 'msg' => '您的帐号被已管理员禁用');
                }
                $data['oauth_token'] = getOAuthToken($user['uid']);
                $data['oauth_token_secret'] = getOAuthTokenSecret();
                $data['uid'] = $user['uid'];
                $login = D('')->table(C('DB_PREFIX').'login')->where('uid='.$user['uid']." AND type='location'")->find();
                if (!$login) {
                    $savedata['type'] = 'location';
                    $savedata = array_merge($savedata, $data);
                    $result = D('')->table(C('DB_PREFIX').'login')->add($savedata);
                } else {
                    //清除缓存
                    model('Cache')->rm($login['oauth_token'].$login['oauth_token_secret']);
                    $result = D('')->table(C('DB_PREFIX').'login')->where('uid='.$user['uid']." AND type='location'")->save($data);
                }
                if (!$result) {
                    return array('status' => 0, 'msg' => '获取失败');
                }
                // 获取用户信息
                $arr_un_in = M('user')->where(array('uid' => $user['uid']))->field('uname,intro')->find();
                $data['uname'] = $arr_un_in['uname'];
                $data['intro'] = $arr_un_in['intro'] ? formatEmoji(true, $arr_un_in['intro']) : '';
                $data['avatar'] = getUserFace($user['uid'], 'm');

                //生成ticket
                if ($live_user_info = D('live_user_info')->where(array('uid' => $user['uid']))->find()) {
                    $data['ticket'] = $live_user_info['ticket'];
                } else {
                    $live_user_info = file_get_contents(SITE_URL.'/api.php?api_type=live&mod=LiveUser&act=postUser&uid='.$user['uid']);
                    $live_user_info = json_decode($live_user_info, true);
                    $live_user_info['status'] == 1 && $data['ticket'] = $live_user_info['data']['ticket'];
                }
                $data['ticket'] = $live_user_info['ticket'];

                return $data;
            } else {
                return array('status' => 1, 'msg' => '帐号尚未绑定');
            }
        } else {
            return array('status' => 0, 'msg' => '参数错误');
        }
    }

    /**
     * 绑定第三方帐号，生成新账号 --using.
     *
     * @param varchar uname 用户名
     * @param varchar password 密码
     * @param varchar type 帐号类型
     * @param varchar type_uid 第三方用户标识
     * @param varchar access_token 第三方access token
     * @param varchar refresh_token 第三方refresh token（选填，根据第三方返回值）
     * @param varchar expire_in 过期时间（选填，根据第三方返回值）
     */
    public function bind_new_user()
    {
        $uname = t($this->data['uname']);
        $password = t($this->data['password']);
        //用户名验证
        if (!model('Register')->isValidName($uname)) {
            $msg = model('Register')->getLastError();
            $return = array('status' => 0, 'msg' => $msg);

            return $return;
        }
        //密码验证
        if (!model('Register')->isValidPasswordNoRepeat($password)) {
            $msg = model('Register')->getLastError();
            $return = array('status' => 0, 'msg' => $msg);

            return $return;
        }
        $login_salt = rand(11111, 99999);
        $map['uname'] = $uname;
        $map['login_salt'] = $login_salt;
        $map['password'] = md5(md5($password).$login_salt);
        // $map['login'] = $uname; // # 该字段为手机号，有用户名方式和email登陆！
        $map['ctime'] = time();
        $registerConfig = model('Xdata')->get('admin_Config:register');
        $map['is_audit'] = $registerConfig['register_audit'] ? 0 : 1;
        $map['is_active'] = 1; //手机端不需要激活
        $map['is_init'] = 1; //手机端不需要初始化步骤
        $map['first_letter'] = getFirstLetter($uname);
        $map['sex'] = $_REQUEST['other_sex'] == '男' ? 1 : 2;
        if (preg_match('/[\x7f-\xff]+/', $map['uname'])) {    //如果包含中文将中文翻译成拼音
            $map['search_key'] = $map['uname'].' '.model('PinYin')->Pinyin($map['uname']);
        } else {
            $map['search_key'] = $map['uname'];
        }
        $uid = model('User')->add($map);
        if ($uid) {
            //第三方登录数据写入
            $other['oauth_token'] = addslashes($this->data['access_token']);
            $other['oauth_token_secret'] = addslashes($this->data['refresh_token']);
            $other['type'] = addslashes($this->data['type']);
            $other['type_uid'] = addslashes($this->data['type_uid']);
            $other['uid'] = $uid;
            M('login')->add($other);

            $data['oauth_token'] = getOAuthToken($uid);
            $data['oauth_token_secret'] = getOAuthTokenSecret();
            $data['uid'] = $uid;
            $savedata['type'] = 'location';
            $savedata = array_merge($savedata, $data);
            $result = M('login')->add($savedata);

            //直播用户信息
            if ($live_user_info = D('live_user_info')->where(array('uid' => $uid))->find()) {
                $data['ticket'] = $live_user_info['ticket'];
            } else {
                $live_user_info = file_get_contents(SITE_URL.'/api.php?api_type=live&mod=LiveUser&act=postUser&uid='.$uid);
                //$live_user_info && $data['ticket'] = $live_user_info['ticket'];
                $live_user_info = json_decode($live_user_info, true);
                $live_user_info['status'] == 1 && $data['ticket'] = $live_user_info['data']['ticket'];
            }

            //保存头像
            if ($_REQUEST['other_avatar']) {
                model('Avatar')->saveRemoteAvatar(urldecode($_REQUEST['other_avatar']), $uid);
            }
            // 添加至默认的用户组
            $userGroup = empty($registerConfig['default_user_group']) ? C('DEFAULT_GROUP_ID') : $registerConfig['default_user_group'];
            model('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));
            // 添加双向关注用户
            $eachFollow = $registerConfig['each_follow'];
            if (!empty($eachFollow)) {
                model('Follow')->eachDoFollow($uid, $eachFollow);
            }
            // 添加默认关注用户
            $defaultFollow = $registerConfig['default_follow'];
            $defaultFollow = array_diff(explode(',', $defaultFollow), explode(',', $eachFollow));
            if (!empty($defaultFollow)) {
                model('Follow')->bulkDoFollow($uid, $defaultFollow);
            }
            if ($map['is_audit'] == 1) {
                return $data;
            } else {
                $return = array('status' => 1, 'msg' => '注册成功，请等待审核', 'need_audit' => 1);
            }
        } else {
            return array('status' => 0, 'msg' => '注册失败');
        }
    }

    /********** �    �他�    �用操作API **********/

    /**
     * 验证是否是合法的email.
     *
     * @param string $string �
     * 验证的字串
     *
     * @return bool 如果是email则返回true，否则返回false
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     *
     * @link http://medz.cn
     */
    public function isEmail($string)
    {
        return 0 < preg_match("/^\w+(?:[-+.']\w+)*@\w+(?:[-.]\w+)*\.\w+(?:[-.]\w+)*$/", $string);
    }

    /**
     * 验证字符串是否是手机号 --using.
     *
     * @param varchar phone 手机号
     *
     * @return bool
     */
    public function isValidPhone($phone)
    {
        return preg_match("/^[1][3578]\d{9}$/", $phone) !== 0;
    }

    /*===============E-Mail API satrt==================*/

    /**
     * 获取邮箱验证码
     *
     * @request string email 邮箱地址
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     *
     * @link http://medz.cn
     **/
    public function getEmailCode()
    {
        /*
         * 邮箱地址
         */
        $email = $this->data['email'];

        /*
         * 验证是否是正确的邮箱地址
         */
        if (!$this->isEmail($email)) {
            return array(
                'status'  => 0,
                'message' => '不是合法的E-Mail地址',
            );

            /*
             * 验证用户是否存在
             */
        } elseif (model('User')->hasUser($email) and !$this->data['notreg']) {
            return array(
                'status'  => -1,
                'message' => '该邮箱用户已经存在，无法使用',
            );

            /*
             * 发送验证码，并检查是否发送失败,并加入时间锁
             */
        } elseif (($sms = model('Sms')) and !$sms->sendEmaillCaptcha($email, true)) {
            return array(
                'status'  => -2,
                'message' => $sms->getMessage(),
            );
        }
        unset($sms);

        return array(
            'status'  => 1,
            'message' => '发送成功，请注意查收',
        );
    }

    /**
     * 验证邮箱验证码
     *
     * @reuqest string email 邮箱
     * @request string code 验证码
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function hasCodeByEmail()
    {
        /*
         * 邮箱地址
         */
        $email = $this->data['email'];
        $email = addslashes($email);

        /*
         * 验证码
         */
        $code = $this->data['code'];
        $code = intval($code);

        /*
         * 验证邮箱是否是不合法邮箱地址
         */
        if (!$this->isEmail($email)) {
            return array(
                'status'  => 0,
                'message' => '不合法的E-mail地址',
            );

            /*
             * 验证验证码是否为空
             */
        } elseif (!$code) {
            return array(
                'status'  => -1,
                'message' => '验证码不能为空',
            );

            /*
             * 验证验证码是否正确
             */
        } elseif (($sms = model('Sms')) and !$sms->checkEmailCaptcha($email, $code)) {
            return array(
                'status'  => -3,
                'message' => $sms->getMessage(),
            );
        }
        unset($sms);

        return array(
            'status'  => 1,
            'message' => '正确，可以注册',
        );
    }

    /**
     * 以邮箱方式注册.
     *
     * @request string email 邮箱地址
     * @request strin username 用户名
     * @request string password 用户密码
     * @request int code 验证码
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     *
     * @link http://medz.cn
     **/
    public function signUp2Email()
    {
        /*
         * 邮箱地址
         */
        $email = $this->data['email'];
        $email = addslashes($email);

        /*
         * 验证码
         */
        $code = $this->data['code'];
        $code = intval($code);

        /*
         * 用户名
         */
        $username = $this->data['username'];
        $username = addslashes($username);

        /*
         * 用户密码
         */
        $password = $this->data['password'];
        $password = addslashes($password);

        /* # 用户头像信息 */
        $avatar = array(
            'picurl'    => $this->data['picurl'],
            'picwidth'  => $this->data['picwidth'],
            'picheight' => $this->data['picheight'],
        );

        /* # 性别 */
        $sex = intval($this->data['sex']);

        /*
         * 验证邮箱是否是不合法邮箱地址
         */
        if (!$this->isEmail($email)) {
            return array(
                'status'  => 0,
                'message' => '不合法的E-mail地址',
            );

            /*
             * 验证验证码是否为空
             */
        } elseif (!$code) {
            return array(
                'status'  => -1,
                'message' => '验证码不能为空',
            );

            /* # 判断性别是否不符合 */
        } elseif (!in_array($sex, array(0, 1, 2))) {
            return array(
                'status'  => 0,
                'message' => '性别参数错误',
            );

            /*
             * 验证邮箱是否已经注册过了
             */
        } elseif (model('User')->hasUser($email)) {
            return array(
                'status'  => -2,
                'message' => '该邮箱用户已经存在，无法注册',
            );

            /*
             * 验证username是否已经被注册了
             */
        } elseif (model('User')->hasUser($username)) {
            return array(
                'status'  => -3,
                'message' => '该用户名已经被注册',
            );

            /*
             * 验证密码格式是否非法
             */
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            return array(
                'status'  => -4,
                'message' => '密码非法，只能是大小写英文和数字组成',
            );

            /*
             * 验证密码是否过短
             */
        } elseif (($plen = strlen($password)) and $plen < 6) {
            return array(
                'status'  => -5,
                'message' => '密码太短，最少需要6位',
            );

            /*
             * 验证密码是否太长
             */
        } elseif ($plen > 15) {
            return array(
                'status'  => -6,
                'message' => '密码太长，最多15位',
            );

            /* # 判断是否没有上传头像 */
        } elseif (!$avatar['picurl']) {
            return array(
                'status'  => 0,
                'message' => '请上传头像',
            );

            /*
             * 验证验证码是否正确
             */
        } elseif (($sms = model('Sms')) and !$sms->checkEmailCaptcha($email, $code)) {
            return array(
                'status'  => -7,
                'message' => $sms->getMessage(),
            );
        }
        unset($sms);

        /*
         * 用户数据
         * @var array
         */
        $userData = array();

        /*
         * 用户邮箱地址
         * @var string
         */
        $userData['email'] = $email;

        /*
         * 用户名
         * @var string
         */
        $userData['uname'] = $username;

        /*
         * 用户盐值
         * @var int
         */
        $userData['login_salt'] = rand(10000, 99999);

        /*
         * 用户密码
         * @var string
         */
        $userData['password'] = model('User')->encryptPassword($password, $userData['login_salt']);

        /*
         * 用户注册时间
         * @var int
         */
        $userData['ctime'] = time();

        /*
         * 是否通过审核
         * @var int
         */
        $userData['is_audit'] = 1;

        /*
         * 是否激活
         * @var int
         */
        $userData['is_active'] = 1;

        /*
         * 是否初始化
         * @var int
         */
        $userData['is_init'] = 1;

        /*
         * 注册IP
         * @var string
         */
        $userData['reg_ip'] = get_client_ip();

        /*
         * 用户名首字母
         * @var string
         */
        $userData['first_letter'] = getFirstLetter($username);

        /*
         * 用户搜索字段
         * @var sring
         */
        $userData['search_key'] = $username;
        preg_match('/[\x7f-\xff]+/', $username) and $userData['search_key'] .= model('PinYin')->Pinyin($username);

        /*
         * 用户性别
         * @var int
         */
        $userData['sex'] = $sex;

        /*
         * 添加用户到数据库
         */
        if (($uid = model('User')->add($userData))) {
            unset($userData);
            /*
             * 注册配置信息
             * @var array
             */
            $registerConfig = model('Xdata')->get('admin_Config:register');

            /*
             * 默认用户组
             * @var int|array
             */
            $defaultUserGroup = empty($registerConfig['default_user_group']) ? C('DEFAULT_GROUP_ID') : $registerConfig['default_user_group'];
            $defaultUserGroup = is_array($defaultUserGroup) ? implode(',', $defaultUserGroup) : $defaultUserGroup;

            /*
             * 将用户移动到用户组
             */
            model('UserGroupLink')->domoveUsergroup($uid, $defaultUserGroup);
            unset($defaultUserGroup);

            /*
             * 添加双向关注用户
             */
            empty($registerConfig['each_follow']) or model('Follow')->eachDoFollow($uid, $registerConfig['each_follow']);

            /*
             * 添加默认关注用户
             */
            $defaultFollow = $registerConfig['default_follow'];
            /* # 去重 */
            $defaultFollow = array_diff(explode(',', $defaultFollow), explode(',', $registerConfig['each_follow']));
            /* # 执行关注 */
            empty($defaultFollow) or model('Follow')->bulkDoFollow($uid, $defaultFollow);
            unset($defaultFollow);

            /* # 保存用户头像 */
            if ($avatar['picurl'] && $avatar['picwidth'] && $avatar['picheight']) {
                $dAvatar = model('Avatar');
                $dAvatar->init($uid);
                $data['picurl'] = $avatar['picurl'];
                $data['picwidth'] = $avatar['picwidth'];
                $scaling = 5;
                $data['w'] = $avatar['picwidth'] * $scaling;
                $data['h'] = $avatar['picheight'] * $scaling;
                $data['x1'] = $data['y1'] = 0;
                $data['x2'] = $data['w'];
                $data['y2'] = $data['h'];
                $dAvatar->dosave($data, true);
                unset($dAvatar, $data);
            }

            /*
             * 添加邮箱到login参数，保证登陆成功
             */
            $_POST['login'] = $email;

            /*
             * 执行登陆流程
             */
            return $this->authorize();
        }
        unset($userData);

        return array(
            'status'  => -8,
            'message' => '注册失败',
        );
    }

    /**
     * 用邮箱找回密码
     *
     * @request string email 邮箱地址
     * @request int    code  验证码
     * @request string password 密码
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function findPassword2Email()
    {
        /*
         * 邮箱地址
         * @var string
         */
        $email = $this->data['email'];
        $email = addslashes($email);

        /*
         * 密码
         * @var string
         */
        $password = $this->data['password'];
        $password = addslashes($password);

        /*
         * 验证码
         * @var int
         */
        $code = $this->data['code'];
        $code = intval($code);

        /*
         * 验证邮箱格式是否正确
         */
        if (!$this->isEmail($email)) {
            return array(
                'status'  => 0,
                'message' => '不是合法的E-Mail地址',
            );

            /*
             * 验证验证码是否不存在
             */
        } elseif (!$code) {
            return array(
                'status'  => -1,
                'message' => '验证码不能为空',
            );

            /*
             * 验证邮箱用户是否不存在
             */
        } elseif (!($uid = model('User')->where('`email` = \''.t($email).'\'')->field('`uid`')->getField('uid'))) {
            return array(
                'status'  => -2,
                'message' => '用户不存在',
            );

            /*
             * 验证密码格式是否非法
             */
        } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            return array(
                'status'  => -3,
                'message' => '密码非法，只能是大小写英文和数字组成',
            );

            /*
             * 验证密码是否过短
             */
        } elseif (($plen = strlen($password)) and $plen < 6) {
            return array(
                'status'  => -4,
                'message' => '密码太短，最少需要6位',
            );

            /*
             * 验证密码是否太长
             */
        } elseif ($plen > 15) {
            return array(
                'status'  => -5,
                'message' => '密码太长，最多15位',
            );

            /*
             * 验证验证码是否不正确
             */
        } elseif (($sms = model('Sms')) and !$sms->checkEmailCaptcha($email, $code)) {
            return array(
                'status'  => -6,
                'message' => $sms->getMessage(),
            );
        }
        unset($sms, $plen);

        /*
         * 用户数据
         * @var array
         */
        $userData = array();

        /*
         * 用户盐值
         * @var int
         */
        $userData['login_salt'] = rand(10000, 99999);

        /*
         * 用户密码
         * @var string
         */
        $userData['password'] = model('User')->encryptPassword($password, $userData['login_salt']);

        /*
         * 修改用户密码
         */
        if (model('User')->where('`uid` = '.$uid)->save($userData)) {
            /*
             * 清理用户缓存
             */
            model('User')->cleanCache(array($uid));

            /*
             * 返回修改成功信息
             */
            return array(
                'status'  => 1,
                'message' => '密码找回并修改成功',
            );
        }

        return array(
            'status'  => -7,
            'message' => '密码找回失败',
        );
    }

    /**
     * 获取�
     * �许的邮箱后缀
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getEmailSuffix()
    {
        $emailSuffix = model('Xdata')->get('admin_Config:register');
        $emailSuffix = $emailSuffix['email_suffix'];

        if (!$emailSuffix) {
            return array(
                'status'  => 2,
                'message' => '无邮箱后缀限制',
            );
        }

        return array(
            'status'  => 1,
            'message' => '成功',
            'data'    => explode(',', $emailSuffix),
        );
    }

    /*===============E-Mail API end  ==================*/
}
