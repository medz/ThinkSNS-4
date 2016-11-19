<?php

class LoginHooks extends Hooks
{
    //站点配置
    private static $validLogin = array(
            'sina' => array('sina_wb_akey', 'sina_wb_skey'),
            'qzone' => array('qzone_key', 'qzone_secret'),
            'qq' => array('qq_key', 'qq_secret'),
            'renren' => array('renren_key', 'renren_secret'),
            'baidu' => array('baidu_key', 'baidu_secret'),
            'taobao' => array('taobao_key', 'taobao_secret'),
        );
    //可同步发布动态的站点
    private static $validPublish = array('sina', 'qq', 'qzone'); // 暂时关闭人人网同步-不知道哪里抽风审核不过
    //应用名称
    private static $validAlias = array(
            'sina' => '新浪分享',
            'qzone' => 'QQ互联',
            'qq' => '腾讯分享',
            'renren' => '人人网',
            'baidu' => '百度',
            'taobao' => '淘宝网',
        );
    //应用申请地址
    private static $validApply = array(
            'sina' => 'http://open.weibo.com/',
            'qzone' => 'http://connect.qq.com',
            'qq' => 'http://open.t.qq.com/websites/',
            'renren' => 'http://dev.renren.com',
            'baidu' => 'http://developer.baidu.com',
            'taobao' => 'http://open.taobao.com',
        );

    //异步提交JS
    public function public_head()
    {
        //判断新浪分享绑定是否过期，会拖慢网站运行速度
        return;
        echo '<script>function after_publish_weibo(feed_id){
    	$.post(U("public/Widget/addonsRequest",["addon=Login","hook=ajax_after_publish_weibo"]),{feed_id:feed_id},function(){})
    }</script>';
        //判断新浪分享绑定是否过期,每天一次

        $uid = $_SESSION['mid'];
        if (!$login = S('user_login_'.$mid)) {
            $login = M('login')->where("uid='{$uid}' AND type='sina'")->find();
            S('user_login_'.$mid, $login);
        }
        if ($login) {
            $this->_loadTypeLogin('sina');
            $sina = new sina();
            $return = $sina->getTokenInfo($login['oauth_token']);
            if (isset($return['create_at']) && isset($return['expire_in']) && ($return['create_at'] + $return['expire_in']) < time()) {
                $url = $sina->getUrl();
                $text = '<dl class="pop_sync"><dt></dt>您绑定的'.$type.'帐号已过期，请<dd><a class="btn-att-green" href="'.$url.'">重新绑定</a></dd></dl>';
                echo "<script>ui.box.show('{$text}', '绑定帐号')</script>";
            }
        }
    }

    //添加个人设置菜单
    public function home_account_tab($param)
    {
        $param['tab_list_security'][] = array('field_key' => 'bind', 'field_name' => '帐号绑定');
    }

    //添加个人设置页面
    public function home_account_page($param)
    {
        if ($param['extend'] == 'bind') {
            $bindData = array();
            Addons::hook('account_bind_after', array('bindInfo' => &$bindData));
            $this->assign('bind', $bindData);
            $param['data'] = $this->fetch('account_bind');
        }
    }
    public function login_input_footer_title()
    {
        $platform_options = model('AddonData')->lget('login');
        if ($platform_options['open']) {
            echo '<fieldset class="f12"><legend>其它帐号登录：</legend></fieldset>';
        }
    }
    public function login_input_footer_title_q()
    {
        $platform_options = model('AddonData')->lget('login');
        if ($platform_options['open']) {
            echo '<span>其它帐号登录：</span>';
        }
    }
    //登录页 第三方账号同步登录插件位
    public function login_input_footer($param)
    {
        //注册配置
        $regInfo = model('Xdata')->get('admin_Config:register');
        $platform_options = model('AddonData')->lget('login');
        $data = self::$validLogin;
        $platform = array();
        foreach ($data as $plateformName => $value) {
            $check = array();
            foreach ($value as $v) {
                $check [] = ! empty($platform_options [$v]);
            }
            if (count(array_filter($check)) == count($value) && in_array($plateformName, $platform_options['open'])) {
                $platform [$plateformName] = Addons::createAddonShow('Login', 'login_sync_other', array('type' => $plateformName));
            }
        }
        // if ($regInfo ['register_type'] != 'admin' && ! empty ( $platform )) {
        if (! empty($platform)) {
            $html = '<div class="third-party">';
            $html .= '<dl>';
            foreach ($platform as $key => $value) {
                $html .= sprintf('<dd><a href="%s" class="ico-%s"></a></dd>', $value, $key);
            }
            $html .= '</dl>';
            $html .= '</div>';
            echo $html;
        }
    }

    //添加验证代码meta标签
    public function public_meta($param)
    {
        $platform_options = model('AddonData')->lget('login');
        echo $platform_options['platformMeta'];
    }

    //执行绑定操作
    public function sync_bind($param)
    {
        // session_start();
        $type = $param['type'];
        $result = &$param['res'];
        $config = model('AddonData')->lget('login');
        $email = t($_POST['email']);
        $uname = t($_POST['uname']);
        if (!in_array($type, $config['open'])) {
            $result ['status'] = 0;
            $result ['info'] = '当前站点不允许此帐号同步登录';
            // session_write_close();
            return;
        }

        $regInfo = model('Xdata')->get('admin_Config:register');
        if ($regInfo ['register_type'] == 'admin') {
            $result ['status'] = 0;
            $result ['info'] = '当前站点不允许第三方账号登录';
            // session_write_close();
            return;
        }

        //尝试使用输入的邮箱地址进行获取用户信息。
        $passport = model('Passport');
        $passwd = $_POST['passwd'] ? $_POST['passwd'] : true;
        $user = $passport->getLocalUser($email, $passwd);

        //如果获取到信息，则是对已有帐号进行绑定
        if ($user) {
            //对昵称进行覆盖绑定操作
            $user['uname'] = $uname;
            $res = $this->_bindaccunt($type, $result, $user);
        } else {
            //反之，则是创建新的帐号,或者帐号密码错误
            $res = model('User')->getUserInfoByEmail($email);
            if (!$res) {
                $res = $this->_register($type, $result);
            } else {
                $result ['status'] = 0;
                $result ['info'] = '登录邮箱密码错误，请检查邮箱密码';
                // session_write_close();
                return;
            }
        }

       // Session::pause();
    }

    public function no_register_do($param)
    {
        // Session::start();
        $type = $param['type'];
        $result = &$param['res'];
        $config = model('AddonData')->lget('login');
        if (!in_array($type, $config['open'])) {
            $result ['status'] = 0;
            $result ['info'] = '该同步操作管理员已关闭';
            //Session::pause();
            return;
        }
        switch ($_REQUEST ['connectMod']) {
        case 'bind':
            $this->_bindaccunt($type, $result);
            break;
        case 'createNew':
            $this->_register($type, $result);
            break;
        default:
            $result ['status'] = 0;
            $result ['info'] = '非法参数';
        }
        // Session::pause();
    }

    public function account_bind_after($param)
    {
        // Session::start();
        $bindInfo = &$param['bindInfo'];
        //可同步平台
        $validPublish = self::$validPublish;
        //可绑定平台
        $validAlias = self::$validAlias;
        //用户已绑定数据
        $bind = M('login')->where('uid='.$this->mid)->findAll();
        //检查可同步的平台的key值是否可用
        $config = model('AddonData')->lget('login');
        //dump($config);
        //dump($validPublish);
        foreach ($validAlias as $k => $v) {
            //检查是否在后台config设置好
            if (!in_array($k, $config['open'])) {
                continue;
            }
            if (in_array($k, $validPublish)) {
                $can_sync = true;
            } else {
                $can_sync = false;
            }
            //$ico = $this->htmlPath.'/html/image/ico_'.$k.'.gif';
            $is_bind = false;
            $is_sync = false;
            foreach ($bind as $value) {
                if ($value['type'] == $k) {
                    $is_bind = true;
                }
                if ($value['type'] == $k && $value['is_sync']) {
                    $is_sync = true;
                }
                if ($value['type'] == $k && $value['bind_time']) {
                    $bind_time = $value['bind_time'];
                }
                if ($value['type'] == $k && $value['bind_user']) {
                    $bind_user = $value['bind_user'];
                }
            }
            $bindInfo[] = array('type' => $k,
                'name' => self::$validAlias[$k],
                'isBind' => $is_bind,
                'isSync' => $is_sync,
                'canSync' => $can_sync,
                'bind_time' => $bind_time,
                'bind_user' => $bind_user,
                'addon' => 'Login',
                'bind_hook' => 'login_bind_publish_weibo',
                'unbind_hook' => 'unbind',
                //'ico'=>$ico
            );
        }
        // Session::pause();
    }

    public function login_bind_publish_weibo($param)
    {
        // Session::start();
        $type = $param['type'];
        if ($_REQUEST['do'] == 'ajax_bind') {
            $_SESSION ['weibo_bind_target_url'] = U('public/Index/index');
        } else {
            $_SESSION ['weibo_bind_target_url'] = U('public/Account/bind');
        }
        $this->_loadTypeLogin($type);
        $platform = new $type();
        $call_back_url = Addons::createAddonShow('Login', 'no_register_display', array('type' => $type, 'do' => 'bind'));
        redirect($platform->getUrl($call_back_url));
        // Session::pause();
    }

    public function login_ajax_bind_publish_weibo($param)
    {
        if (isset($_POST['type'])) {
            $param['type'] = $_POST['type'];
        }
        //         $config = model('AddonData')->lget('login');
        //         if(!in_array($param['type'],$config['open'])){
        //             $this->error("该同步操作管理员已关闭");
        //         }
        $type = strtolower($param['type']);

        if (!in_array($type, array_keys(self::$validLogin))) {
            echo '<dl class="pop_sync"><dt></dt>请求的第三方账户类型不存在！</dl>';
            exit;
        }

        // 展示"开始绑定"按钮
        $map ['uid'] = $this->mid;
        $map ['type'] = $type;
        if (M('login')->where("uid={$this->mid} AND type='{$type}' AND oauth_token<>''")->count()) {
            M('login')->setField('is_sync', 1, $map);
            S('user_login_'.$this->mid, null);
            echo '1';
            exit();
        } else {
            // Session::start();
            $_SESSION ['weibo_bind_target_url'] = U('public/Index/index');
            $this->_loadTypeLogin($type);
            $platform = new $type ();
            $call_back_url = Addons::createAddonShow('Login', 'no_register_display', array('type' => $type, 'do' => 'bind'));
            $url = $platform->getUrl($call_back_url);
            // Session::pause();
            echo '<dl class="pop_sync"><dt></dt>您还未绑定'.$type.'帐号, 请点这里<dd><a class="btn-att-green" href="'.$url.'">开始绑定</a></dd></dl>';
            exit();
        }
    }

    //发布框解除同步绑定
    public function login_unbind_publish_weibo($param)
    {
        if (isset($_POST['type'])) {
            $param['type'] = $_POST['type'];
        }
        $type = strtolower($param['type']);
        echo M('login')->setField('is_sync', 0, "uid={$this->mid} AND type='{$type}'");
        S('user_login_'.$this->mid, null);
    }

    //资料页删除绑定
    public function unbind()
    {
        if ($this->mid > 0) {
            $type = h($_POST['type']);
            echo M('login')->where("uid={$this->mid} AND type='{$type}'")->delete();
            S('user_login_'.$this->mid, null);
        } else {
            echo 0;
        }
    }

    //主页发布框下的位置，增加同步绑定选项
    public function home_index_middle_publish()
    {
        $sync = self::$validPublish;
        //TODO:增加缓存
        $bind = unserialize((S('user_login_'.$this->mid)));
        if (false === $bind) {
            $bind = M('login')->where('uid='.$this->mid)->findAll();
            S('user_login_'.$this->mid, serialize($bind));
        }

        foreach ($bind as $v) {
            $login_bind [$v ['type']] = $v ['is_sync'];
        }
        //检查可同步的平台的key值是否可用
        $config = model('AddonData')->lget('login');
        $validSync = array();
        foreach ($sync as $value) {
            if (!in_array($value, $config['publish']) || empty($config[self::$validLogin[$value][0]]) || empty($config[self::$validLogin[$value][1]])) {
                continue;
            }
            $validSync[] = $value;
        }

        $this->assign('htmlPath', $this->htmlPath);
        $this->assign('login_bind', $login_bind);
        $this->assign('sync', $validSync);
        $this->assign('alias', self::$validAlias);
        if (!empty($validSync)) {
            $this->display('sync');
        }
    }

    //发布分享后的操作 - 同步发布信息到社交网站
    public function ajax_after_publish_weibo()
    {
        set_time_limit(0);
        ignore_user_abort(1);
        $feedid = intval($_POST['feed_id']);
        if (!$feedid) {
            return;
        }
        $feeddata = D('FeedData')->where('feed_id='.$feedid)->getField('feed_data');
        $data = unserialize($feeddata);
        $id = $feedid;
        //如果传递了一次性同步参数?以参数为主:否则以帐号绑定同步设置为主
        $sync = $_POST['sync'];
        $content = $data['content'];
        if ($data['type'] == 'postimage') {
            $attach_id = intval($data['attach_id'][0]);
            $attach = model('Attach')->where('attach_id='.$attach_id)->find();
            $cloud = model('CloudImage');
            if ($cloud->isOpen()) {
                $is_cloud = ture;
                $pic = $cloud->getImageUrl($attach['save_path'].$attach['save_name']);
                $pic_url = $pic;
                if (!file_exists($pic)) {
                    $tmp_path = UPLOAD_PATH.'/weibo_tmp/';
                    @mkdir($tmp_path, 0777, true);
                    $tmp_file = uniqid().'.jpg';
                    @file_put_contents($tmp_path.$tmp_file, file_get_contents($pic));
                    $pic = $tmp_path.$tmp_file;
                }
            } else {
                $pic = UPLOAD_PATH.'/'.$attach['save_path'].$attach['save_name'];
                $pic_url = UPLOAD_URL.'/'.$attach['save_path'].$attach['save_name'];
            }
        }
        $feed_url = U('public/Profile/feed', array('feed_id' => $id, 'uid' => $data['uid']));

        //if(!empty($sync)){

            $result = array();
            // foreach($sync as $key=>$v){
            //     $sync[$key] = "'{$v}'";
            // }
            //
            $opt = M('login')->where('uid='.intval($data['uid']).' and is_sync=1')->findAll();

            // Url格式化问题
            $formatContent = model('Feed')->formatFeedContent($content, 128);
        $content = $formatContent['body'];
            //content需要截取成128字
            $content = getShort($content, 128, '..');
        $content = str_ireplace('[SITE_URL]', SITE_URL, $content);
        foreach ($opt as $v) {
            if ($v['type'] == 'location') {
                continue;
            }
            $this->_loadTypeLogin($v['type']);
            $v['pic'] = $pic;
            $v['pic_url'] = $pic_url;
            $v['feed_url'] = $feed_url;
            $v['feed_content'] = $content;
            $platform = new $v['type']();
            switch ($data['type']) {
                case 'post':
                    $syncData = $platform->update($content.' '.$feed_url, $v);
                    break;
                case 'postimage':
                    $syncData = $platform->upload($content.' '.$feed_url, $v, $pic);
                    //if($is_cloud)
                    //    @unlink($tmp_path.$tmp_file);
                    break;
                default:
                    $syncData = $platform->update($content.' '.$feed_url, $v);

                    return;
                }
                //记录发的新分享到数据库
                if (empty($result)) {
                    $result = $platform->saveData($syncData);
                } else {
                    $result = array_merge($result, $platform->saveData($syncData));
                }
        }
            // if(!empty($result)){
            //     $dao = M('login_weibo');
            //     $result['weiboId'] = $id;
            //     $dao->add($result);
            // }
        //}
    }

    //转发分享后的操作 - 同步发布信息到社交网站
    public function weibo_transpond_after($param)
    {
        // Session::start();
        $id = $param['weibo_id'];
        $post = $param['post'];
        $data = $param['data'];
        $result = array();

        $opt = M('login')->where('uid='.intval($data['uid']).' and is_sync=1')->findAll();

        //检查该分享数据
        $map['weiboId'] = $post['transpond_id'];

        $data = M('login_weibo')->field('qqId,sinaId')->where($map)->find();

        foreach ($opt as $value) {
            $type = $value['type'];
            if (!in_array($type, self::$validPublish)) {
                continue;
            }
            $this->_loadTypeLogin($type);
            $platform = new $type();
            if ($data) {
                if (!empty($data[$type.'Id'])) {
                    $syncData = $platform->transpond($data[$type.'Id'], 0, $post['content'], $value);
                }
            } else {
                $post['content'] = $post['content'].' '.U('home/space/detail', array('id' => $post['transpond_id'])).' ';
                $syncData = $platform->update($post['content'], $value);
            }
            //记录发的新分享到数据库
            if (empty($result)) {
                $result = $platform->saveData($syncData);
            } else {
                $result = array_merge($result, $platform->saveData($syncData));
            }
        }

        if (!empty($result)) {
            $dao = M('login_weibo');
            if ($data) {
                $result['weiboId'] = $id;
                $dao->add($result);
            }
        }
        // Session::pause();
    }

    //取得第三方账号授权token后，第一步先跳转到这里
    public function no_register_display($param)
    {
        // Session::start();
        S('user_login_'.$this->mid, null);
        $type = strtolower($param['type']);
        $result = &$param['res'];
        //dump($_GET);exit;
        //当前操作如果是绑定
        if ($_GET ['do'] == 'bind') {
            $this->_bindPublish($type, $param['res']);

        //当前操作如果是登录
        } else {
            $type = t($_GET ['type']);
            $this->_loadTypeLogin($type);
            $platform = new $type ();
            $platform->checkUser('login');
            $userinfo = $platform->userInfo();
            // var_dump($userinfo);exit();
            // 检查是否成功获取用户信息
            if (empty($userinfo ['id']) || empty($userinfo ['uname'])) {
                $result ['status'] = 0;
                $result ['url'] = SITE_URL;
                $result ['info'] = '获取用户信息失败';

                return;
            }

            //检查是否存在这个用户的登录信息
            if ($info = D('Login')->where("`type_uid`='".$userinfo ['id']."' AND type='{$type}'")->find()) {
                //获取用户信息
                $user = D('User')->where('uid='.$info ['uid'])->find();
                // 未在本站找到用户信息, 删除用户站外信息,让用户重新登录
                if (empty($user)) {
                    D('Login')->where('type_uid='.$userinfo ['id']." AND type='{$type}'")->delete();
                //已经绑定过，执行登录操作，设置token
                } else {
                    if ($info ['oauth_token'] == '') {
                        $syncdata ['login_id'] = $info ['login_id'];
                        $syncdata ['oauth_token'] = $_SESSION [$type] ['access_token'] ['oauth_token'];
                        $syncdata ['oauth_token_secret'] = $_SESSION [$type] ['access_token'] ['oauth_token_secret'];
                        D('Login')->save($syncdata);
                    }
                    model('Passport')->loginLocalWithoutPassword($user['uname']);
                    $result ['status'] = 1;
                    $result ['url'] = $GLOBALS['ts']['site']['home_url'];
                    $result ['info'] = '同步登录成功';

                    return;
                }
            }
            $regInfo = model('Xdata')->get('admin_Config:register');
            $regInfo['bindemail'] = model('AddonData')->get('login:bindemail');
            $this->assign('config', $regInfo);
            //没绑定过，去注册页面
            $this->assign('user', $userinfo);
            $this->assign('type', $type);
            $this->assign('typeName', self::$validAlias[$type]);
            // 设置token的相关值
            $oauth_token = isset($_GET['oauth_token']) ? t($_GET['oauth_token']) : $_SESSION[$type]['access_token']['oauth_token'];
            $this->assign('oauth_token', $oauth_token);
            $oauth_token_secret = isset($_GET['oauth_token_secret']) ? t($_GET['oauth_token_secret']) : $_SESSION[$type]['access_token']['oauth_token_secret'];
            $this->assign('oauth_token_secret', $oauth_token_secret);
            if (isMobile()) {
                $this->display('wap_login');
            } else {
                $this->display('login');
            }
        }
    }

    public function login_sync_other($param)
    {
        // Session::start();
        $regInfo = $param['regInfo'];
        $platform_options = model('AddonData')->lget('login');
        $data = self::$validLogin;
        $type = strtolower($param['type']);

        $platform = array();
        $check = array();
        foreach ($data[$type] as $v) {
            $check [] = ! empty($platform_options [$v]);
        }
        if (count(array_filter($check)) == count($data[$type]) && in_array($type, $platform_options['open'])) {
            $this->_loadTypeLogin($type);
            $object = new $type ();
            $url = Addons::createAddonShow('Login', 'no_register_display', array('type' => $type));
            $url = $object->getUrl($url);
           // if(!$url){
                //dump($type.'-login-error:'.$object->getError());
            //}
            redirect($url);
        }

        // Session::pause();
    }

    private function _bindPublish($type, &$result)
    {
        // Session::start();
        $this->_loadTypeLogin($type);
        $obj = new $type ();
        $obj->checkUser('bind');
        if (! isset(self::$validPublish [$_SESSION ['open_platform_type']])) {
            $result ['status'] = 0;
            $result ['url'] = U('public/Widget/displayAddons', array('class' => __CLASS__, 'type' => "{$type}"));
            $result ['info'] = '授权失败';
        }

        // 检查是否成功获取用户信息
        $userinfo = $obj->userInfo();
        if (!isset($userinfo ['id']) || empty($userinfo ['uname'])) {
            $result ['status'] = 0;
            $result ['url'] = U('public/Widget/displayAddons', array('class' => __CLASS__, 'type' => "{$type}"));
            $result ['info'] = '获取用户信息失败';

            return;
        }

        $syncdata ['uid'] = $this->mid;
        $syncdata ['type_uid'] = $userinfo ['id'];
        $syncdata ['type'] = $type;
        $syncdata ['oauth_token'] = $_SESSION [$type] ['access_token'] ['oauth_token'];
        $syncdata ['oauth_token_secret'] = $_SESSION [$type] ['access_token'] ['oauth_token_secret'];
        $syncdata ['is_sync'] = ($_SESSION [$type] ['isSync']) ? $_SESSION [$type] ['isSync'] : '1';
        S('user_login_'.$this->mid, null);

        if ($info = M('login')->where("type_uid={$userinfo['id']} AND type='".$type."'")->find()) {
            // 该新浪用户已在本站存在, 将其与当前用户关联(即原用户ID失效)
            M('login')->where("`login_id`={$info['login_id']}")->save($syncdata);
        } else {
            // 添加同步信息
            M('login')->add($syncdata);
        }

        if (isset($_SESSION ['weibo_bind_target_url'])) {
            $result ['url'] = $_SESSION ['weibo_bind_target_url'];
            unset($_SESSION ['weibo_bind_target_url']);
        } else {
            $result ['url'] = U('square/Index/index');
        }

        $result ['status'] = 1;
        $result ['info'] = '绑定成功';
        // Session::pause();
    }

    private function _bindaccunt($type, &$result, $user)
    {
        $_POST['type'] = t($_POST['type']);
        if (! isset(self::$validLogin [$_POST ['type']])) {
            $result ['status'] = 0;
            $result ['info'] = '参数错误';

            return;
        }

        $type = $_POST ['type'];
        $this->_loadTypeLogin($type);
        $platform = new $type ();
        $userinfo = $platform->userInfo();

        // 检查是否成功获取用户信息
        if (empty($userinfo ['id']) || empty($userinfo ['uname'])) {
            $result ['status'] = 0;
            $result ['jumpUrl'] = SITE_URL;
            $result ['info'] = '获取用户信息失败';

            return;
        }
        //如果该类型的绑定已经进行过，则是系统异常。正确流程并不会进行两次绑定
        $sync['uid'] = $user['uid'];
        $sync['type'] = $type;
        //dump($sync);
        if (D('login')->where($sync)->count()) {
            $result ['status'] = 0;
            $result ['jumpUrl'] = SITE_URL;
            $result ['info'] = '该帐号已经绑定了其他新浪分享帐号';

            return;
        }

        // 更新该用户的昵称数据
        $save['uname'] = $user['uname'];
        $map['uid'] = $user['uid'];
        $res = model('User')->where($map)->save($save);

        $syncdata['oauth_token'] = $_SESSION [$type] ['access_token'] ['oauth_token'];
        $syncdata['oauth_token_secret'] = $_SESSION [$type] ['access_token'] ['oauth_token_secret'];
        $syncdata['uid'] = $user ['uid'];
        $syncdata['type_uid'] = $userinfo ['id'];
        $syncdata['type'] = $type;
        $syncdata['is_sync'] = 0;

        S('user_login_'.$user['uid'], null);

        if (D('login')->add($syncdata)) {
            $res = model('Passport')->loginLocalWithoutPassword($user['uname'], true);
            if ($res) {
                $result ['status'] = 1;
                $result ['jumpUrl'] = U('square/Index/index');
                $result ['info'] = '绑定成功';

                return true;
            } else {
                $result ['status'] = 0;
                $result ['jumpUrl'] = SITE_URL;
                $result ['info'] = '绑定失败';

                return false;
            }
        } else {
            $result ['status'] = 0;
            $result ['jumpUrl'] = SITE_URL;
            $result ['info'] = '绑定失败';

            return false;
        }
    }

    private function _register($type, &$result)
    {
        if (! isset(self::$validLogin [$type])) {
            $result ['status'] = 0;
            $result ['info'] = '参数错误';

            return;
        }

        if (!model('Register')->isValidEmail($email)) {
            $result ['status'] = 0;
            $result ['info'] = model('Register')->getLastError();

            return;
        }

        if (!model('Register')->isValidPassword($password, $repassword)) {
            $result ['status'] = 0;
            $result ['info'] = model('Register')->getLastError();

            return;
        }

        // if (! isLegalUsername ( t ( $_POST ['uname'] ) )) {
        //     $result ['status'] = 0;
        //     $result ['info'] = "昵称格式不正确";
        //     return;
        // }

        // $haveName = D ( 'User' )->where ( "`uname`='" . t ( $_POST ['uname'] ) . "'" )->find ();
        // if (is_array ( $haveName ) && sizeof ( $haveName ) > 0) {
        //     $result ['status'] = 0;
        //     $result ['info'] = "昵称已被使用";
        //     return;
        // }

        $type = $_POST ['type'];
        $this->_loadTypeLogin($type);
        $platform = new $type ();
        $userinfo = $platform->userInfo();

        // 检查是否成功获取用户信息
        if (empty($userinfo ['id']) || empty($userinfo ['uname'])) {
            $result ['status'] = 0;
            $result ['jumpUrl'] = SITE_URL;
            $result ['info'] = '获取用户信息失败';

            return;
        }

        // 初使化用户信息, 激活帐号
        $data ['uname'] = t($_POST ['uname']) ? t($_POST ['uname']) : $userinfo ['uname'];
        $data ['email'] = t($_POST['email']);
        $data ['login'] = t($_POST['email']);
        $data ['sex'] = intval($userinfo ['sex']);
        $data ['reg_ip'] = get_client_ip();
        $data ['ctime'] = time();
        $data ['login_salt'] = rand(11111, 99999);
        $data ['password'] = md5(md5($_POST['passwd']).$data['login_salt']);
        $data ['location'] = t($_POST['city_names']);
        $cityIds = t($_POST['city_ids']);
        $cityIds = explode(',', $cityIds);
        isset($cityIds[0]) && $data['province'] = intval($cityIds[0]);
        isset($cityIds[1]) && $data['city'] = intval($cityIds[1]);
        isset($cityIds[2]) && $data['area'] = intval($cityIds[2]);
        // 审核状态： 0-需要审核；1-通过审核
        $regInfo = model('Xdata')->get('admin_Config:register');
        $data['is_audit'] = $regInfo['register_audit'] ? 0 : 1;
        $data['first_letter'] = getFirstLetter($data['uname']);
        //如果包含中文将中文翻译成拼音
        if (preg_match('/[\x7f-\xff]+/', $data['uname'])) {
            //昵称和呢称拼音保存到搜索字段
            $data['search_key'] = $data['uname'].' '.model('PinYin')->Pinyin($data['uname']);
        } else {
            $data['search_key'] = $data['uname'];
        }

        if ($id = D('user')->add($data)) {
            // 记录至同步登录表
            $syncdata ['uid'] = $id;
            $syncdata ['type_uid'] = $userinfo ['id'];
            $syncdata ['type'] = $type;
            $syncdata ['oauth_token'] = $_SESSION [$type] ['access_token'] ['oauth_token'];
            $syncdata ['oauth_token_secret'] = $_SESSION [$type] ['access_token'] ['oauth_token_secret'];
            D('login')->add($syncdata);

            // 转换头像
            //if ($_POST ['type'] != 'qq' || $_POST['type'] !='qzone') { // 暂且不转换QQ头像: QQ头像的转换很慢, 且会拖慢apache
            //  D ( 'Avatar' )->saveAvatar ( $id, $userinfo ['userface'] );
            //}
            $res = model('Passport')->loginLocalWithoutPassword($data['uname'], true);
            $this->registerRelation($id);
        } else {
            $result ['status'] = 0;
            $result ['info'] = '同步帐号发生错误';

            return false;
        }
    }

    // 注册的关联操作
    private function registerRelation($uid, $invite_info = null)
    {
        // 如果是邀请注册，则邀请码失效
        if ($invite) {
            $receiverInfo = model('User')->getUserInfo($uid);
            // 验证码使用
            model('Invite')->setInviteCodeUsed($inviteCode, $receiverInfo);
            // 添加用户邀请码字段
            model('User')->where('uid='.$uid)->setField('invite_code', $inviteCode);
        }
        // 添加至默认的用户组
        $userGroup = model('Xdata')->get('config:register');
        $userGroup = empty($userGroup['default_user_group']) ? C('DEFAULT_GROUP_ID') : $userGroup['default_user_group'];
        model('UserGroupLink')->domoveUsergroup($uid, $userGroup);
        $regInfo = model('Xdata')->get('admin_Config:register');
        if ($regInfo['register_audit']) {
            $this->redirect('public/Register/waitForAudit', array('uid' => $uid));
        } else {
            model('Register')->sendActivationEmail($uid);
            $this->redirect('public/Register/waitForActivation', array('uid' => $uid));
        }
    }

    /* 移动客户端外部帐号登录 */
    public function login_on_client()
    {
        $type = $_GET['type'];

        $this->_loadTypeLogin($type);
        $platform = new $type();

        $call_back_url = Addons::createAddonUrl('Login', 'login_callback_on_client', array('type' => $type));
        redirect($platform->getUrl($call_back_url));
    }

    public function login_callback_on_client()
    {
        $type = $_GET['type'];
        switch ($type) {
            case 'sina':
                $this->_loadTypeLogin($type);
                $sina = new sina();
                $sina->checkUser();
                redirect(Addons::createAddonUrl('Login', 'login_display_on_client', array('type' => $type)));
                break;
            default:
                ;
        }
    }

    // 外站帐号登录
    public function login_display_on_client()
    {
        if (!in_array($_SESSION['open_platform_type'], array('sina', 'qzone', 'qq', 'renren', 'taobao', 'baidu'))) {
            $this->error('授权失败');
        }

        $type = $_SESSION['open_platform_type'];
        $this->_loadTypeLogin($type);
        $platform = new $type();
        $userinfo = $platform->userInfo();
        // 检查是否成功获取用户信息
        if (empty($userinfo['id']) || empty($userinfo['uname'])) {
            $this->_loginFailureOnClient('获取用户信息失败');
        }
        if ($info = M('login')->where("`type_uid`='".$userinfo['id']."' AND type='{$type}'")->find()) {
            $user = M('user')->where('uid='.$info['uid'])->find();
            if (empty($user)) {
                // 未在本站找到用户信息, 删除用户站外信息,让用户重新登录
                M('login')->where('type_uid='.$userinfo['id']." AND type='{$type}'")->delete();
            } else {
                if ($info['oauth_token'] == '') {
                    $syncdata['login_id'] = $info['login_id'];
                    $syncdata['oauth_token'] = $_SESSION[$type]['access_token']['oauth_token'];
                    $syncdata['oauth_token_secret'] = $_SESSION[$type]['access_token']['oauth_token_secret'];
                    M('login')->save($syncdata);
                }

                service('Passport')->registerLogin($user);

                $this->_loginSuccessOnClient($user['uid'], $type);
            }
        }
        $this->assign('user', $userinfo);
        $this->assign('type', $type);
        $this->display('wap_login');
    }

    // 注册新本地帐号
    public function login_register_on_client()
    {
        if (! in_array($_POST['type'], array('douban', 'sina', 'qq'))) {
            $this->_loginFailureOnClient('参数错误');
        }

        if (!isLegalUsername(t($_POST['uname']))) {
            $this->_loginFailureOnClient('昵称格式不正确');
        }

        $haveName = M('User')->where("`uname`='".t($_POST['uname'])."'")->find();
        if (is_array($haveName) && count($haveName) > 0) {
            $this->_loginFailureOnClient('昵称已被使用');
        }

        $type = $_POST['type'];
        $this->_loadTypeLogin($type);
        $platform = new $type();
        $userinfo = $platform->userInfo();

        // 检查是否成功获取用户信息
        if (empty($userinfo['id']) || empty($userinfo['uname'])) {
            $this->_loginFailureOnClient('获取用户信息失败');
        }

        // 检查是否已加入本站
        $map['type_uid'] = $userinfo['id'];
        $map['type'] = $type;
        if (($local_uid = M('login')->where($map)->getField('uid')) && (M('user')->where('uid='.$local_uid)->find())) {
            $this->_loginSuccessOnClient($local_uid, $type);
        }
        // 初使化用户信息, 激活帐号
        $data['uname'] = t($_POST['uname']) ? t($_POST['uname']) : $userinfo['uname'];
        $data['province'] = intval($userinfo['province']);
        $data['city'] = intval($userinfo['city']);
        $data['location'] = $userinfo['location'];
        $data['sex'] = intval($userinfo['sex']);
        $data['is_active'] = 1;
        $data['is_init'] = 1;
        $data['ctime'] = time();
        $data['is_synchronizing'] = ($type == 'sina') ? '1' : '0'; // 是否同步新浪分享. 目前仅能同步新浪分享

        if ($id = M('user')->add($data)) {
            // 记录至同步登录表
            $syncdata['uid'] = $id;
            $syncdata['type_uid'] = $userinfo['id'];
            $syncdata['type'] = $type;
            $syncdata['oauth_token'] = $_SESSION[$type]['access_token']['oauth_token'];
            $syncdata['oauth_token_secret'] = $_SESSION[$type]['access_token']['oauth_token_secret'];
            M('login')->add($syncdata);

            // 转换头像
            if ($_POST['type'] != 'qq') { // 暂且不转换QQ头像: QQ头像的转换很慢, 且会拖慢apache
                D('Avatar')->saveAvatar($id, $userinfo['userface']);
            }

            // 将用户添加到myop_userlog，以使漫游应用能获取到用户信息
            $userlog = array(
                'uid' => $id,
                'action' => 'add',
                'type' => '0',
                'dateline' => time(),
            );
            M('myop_userlog')->add($userlog);

            service('Passport')->loginLocal($id);

            $this->registerRelation($id);

            $this->_loginSuccessOnClient($id, $type);
        } else {
            $this->_loginFailureOnClient('同步帐号发生错误');
        }
    }

    // 绑定已有帐号
    public function login_bind_on_client()
    {
        if (! in_array($_POST['type'], array('douban', 'sina', 'qq'))) {
            $this->_loginFailureOnClient('参数错误');
        }

        $psd = ($_POST['passwd']) ? $_POST['passwd'] : true;
        $type = $_POST['type'];

        if ($user = service('Passport')->getLocalUser($_POST['email'], $psd)) {
            $this->_loadTypeLogin($type);
            $platform = new $type();
            $userinfo = $platform->userInfo();

            // 检查是否成功获取用户信息
            if (empty($userinfo['id']) || empty($userinfo['uname'])) {
                $this->_loginFailureOnClient('获取用户信息失败');
            }

            // 检查是否已加入本站
            $map['type_uid'] = $userinfo['id'];
            $map['type'] = $type;
            if (($local_uid = M('login')->where($map)->getField('uid')) && (M('user')->where('uid='.$local_uid)->find())) {
                $this->_loginSuccessOnClient($local_uid, $type);
            }

            $syncdata['uid'] = $user['uid'];
            $syncdata['type_uid'] = $userinfo['id'];
            $syncdata['type'] = $type;
            if (M('login')->add($syncdata)) {
                service('Passport')->registerLogin($user);

                $this->_loginSuccessOnClient($user['uid'], $type);
            } else {
                $this->_loginFailureOnClient('绑定失败');
            }
        } else {
            $this->_loginFailureOnClient('帐号输入有误');
        }
    }

    private function _loginSuccessOnClient($local_uid, $type)
    {
        if ($login = M('login')->where('uid='.$local_uid." AND type='location'")->find()) {
            $data['oauth_token'] = $login['oauth_token'];
            $data['oauth_token_secret'] = $login['oauth_token_secret'];
            $data['uid'] = $local_uid;
            $data['type'] = 'location';
        } else {
            $data['oauth_token'] = getOAuthToken($local_uid);
            $data['oauth_token_secret'] = getOAuthTokenSecret();
            $data['uid'] = $local_uid;
            $data['type'] = 'location';
            M('login')->add($data);
        }
        redirect(Addons::createAddonUrl('Login', 'login_success_on_client', $data));
    }

    private function _loginFailureOnClient($text = '登录失败')
    {
        header('Content-type:text/html;charset=utf-8');
        echo $text;
        exit;
    }

    public function login_success_on_client()
    {
        header('Content-type:text/html;charset=utf-8');
        echo '登录成功，点击进入'.'<a href="'.U('wap').'">我的主页</a>';
        exit;
    }

    // 注册的关联操作
    private function _registerRelation($uid, $invite_info = null)
    {
        if (($uid = intval($uid)) <= 0) {
            return;
        }

        $dao = D('Follow', 'weibo');

        // 使用邀请码时, 建立与邀请人的关系
        if ($invite_info['uid']) {
            // 互相关注
            D('Follow', 'weibo')->dofollow($uid, $invite_info['uid']);
            D('Follow', 'weibo')->dofollow($invite_info['uid'], $uid);

            // 添加邀请记录
            model('InviteRecord')->addRecord($invite_info['uid'], $uid);

            //邀请人积分操作
            model('Credit')->setUserCredit($invite_info['uid'], 'invite_friend');
        }

        // 默认关注的好友
        $auto_freind = model('Xdata')->lget('register');
        $auto_freind['register_auto_friend'] = explode(',', $auto_freind['register_auto_friend']);
        foreach ($auto_freind['register_auto_friend'] as $v) {
            if (($v = intval($v)) <= 0) {
                continue ;
            }
            $dao->dofollow($v, $uid);
            $dao->dofollow($uid, $v);
        }

        // 开通个人空间
        $data['uid'] = $uid;
        model('Space')->add($data);

        //注册成功 初始积分
        model('Credit')->setUserCredit($uid, 'init_default');
    }

    public function login_plugin_login()
    {
        $config = model('AddonData')->lget('login');
        $this->assign('config', $config);
        $this->assign('data', self::$validLogin);
        $this->assign('alias', self::$validAlias);
        $this->assign('applyUrl', self::$validApply);
        $this->display('sync_admin');
    }

    public function saveAdminConfig()
    {
        $data = array();
        foreach ($_POST as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $value[$k] = h($v);
                }
                $data[$key] = $value;
            } else {
                $data[$key] = h($value);
            }
        }
        if (!$_POST['open']) {
            $data['open'] = array();
        }
        if ($_POST['bindemail'] == 1) {
            $data['bindemail'] = 1;
        } else {
            $data['bindemail'] = 0;
        }
        $_POST && $res = model('AddonData')->lput('login', $data);
        if ($res) {
            $this->assign('jumpUrl', Addons::adminPage('login_plugin_login'));
        } else {
            $this->error();
        }
    }

    private function _loadTypeLogin($type, $config = array())
    {
        $config = empty($config) ? model('AddonData')->lget('login') : $config;
        if (isset(self::$validLogin[$type])) {
            foreach (self::$validLogin[$type] as $value) {
                if (empty($config[$value])) {
                    $this->error(self::$validAlias[$type].'没有设置Key,请勿异常操作');
                }
                !defined(strtoupper($value)) && define(strtoupper($value), $config[$value]);
            }
        }
    }
}
