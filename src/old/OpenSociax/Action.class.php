<?php

use Medz\Component\EmojiFormat;

/**
 * ThinkSNS Action控制器基类
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS v4
 */
abstract class Action
{
    //类定义开始

    // 当前Action名称
    private $name = '';
    protected $tVar = array();
    protected $trace = array();
    protected $templateFile = '';

    protected $appCssList = array();
    protected $appJsList = array();
    protected $langJsList = array();

    protected $site = array();
    protected $user = array();
    protected $app = array();

    protected $mid = 0;
    protected $uid = 0;

    protected $initUserData = false;

    /**
     * 架构函数 取得模板对
     */
    public function __construct()
    {
        $GLOBALS['time_run_detail']['action_init_start'] = microtime(true);

        $this->initSite();

        $this->initUser();

        $this->initApp();
        Addons::hook('core_filter_init_action');
        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }

        $GLOBALS['time_run_detail']['action_init_end'] = microtime(true);
    }

    /**
     * 站点信息初始化
     */
    private function initSite()
    {

        //初始化语言包
        $cacheFile = DATA_PATH.'/lang/_initSiteLang.lock';
        if (!file_exists($cacheFile)) {
            model('Lang')->initSiteLang();
        }

        $GLOBALS['time_run_detail']['action_init_site_start'] = microtime(true);

        //载入网站全局配置
        $this->site = F('global_site_config');

        if ($this->site === false) {
            //载入站点配置全局变量
            $this->site = model('Xdata')->get('admin_Config:site');
            $GLOBALS['time_run_detail']['action_init_site_siteconfig'] = microtime(true);

            $GLOBALS['time_run_detail']['action_init_site_language'] = microtime(true);

            //LOGO处理
            $this->site['logo'] = getSiteLogo($this->site['site_logo']);
            $GLOBALS['time_run_detail']['action_init_site_logo'] = microtime(true);

            //默认登录后首页
            if (intval($this->site['home_page'])) {
                $appInfo = model('App')->where('app_id='.intval($this->site['home_page']))->find();
                $this->site['home_url'] = U($appInfo['app_name'].'/'.$appInfo['app_entry']);
            } else {
                $this->site['home_url'] = U('public/Index/index');
            }

            $GLOBALS['time_run_detail']['action_init_site_homepage'] = microtime(true);

            //网站导航
            $this->site['site_top_nav'] = model('Navi')->getTopNav();
            $this->site['site_bottom_nav'] = model('Navi')->getBottomNav();
            $this->site['site_bottom_child_nav'] = model('Navi')->getBottomChildNav($GLOBALS['ts']['site_bottom_nav']);
            if (!$this->mid) {
                //游客导航
                $this->site['site_guest_nav'] = model('Navi')->getGuestNav();
            }
            $GLOBALS['time_run_detail']['action_init_site_language'] = microtime(true);

            //获取可搜索的内容列表
            $this->site['search_menu_list'] = D('SearchSelect')->findAll();
            $GLOBALS['time_run_detail']['action_init_site_search'] = microtime(true);

            //网站所有的应用
            $this->site['site_nav_apps'] = model('App')->getAppList(array('status' => 1, 'add_front_top' => 1), 9);
            $GLOBALS['time_run_detail']['action_init_site_applist'] = microtime(true);

            //获取当前Js语言包
            $this->site['langJsList'] = setLangJavsScript();

            //分享字数
            $this->site['initNums'] = model('Xdata')->getConfig('weibo_nums', 'feed');

            //赋值给全局变量
            F('global_site_config', $this->site);
        }

        //检查站点是否关闭
        if ($this->site['site_closed'] == 0 && APP_NAME != 'admin') {
            $this->page404($this->site['site_closed_reason']);
            exit();
        }
        // 检查网页端是否关闭
        if ($this->site['web_closed'] == 0 && APP_NAME != 'admin' && APP_NAME != 'w3g' && IS_GET) {
            $this->page404('网页版已经关闭');
            exit();
        }
        $GLOBALS['time_run_detail']['action_init_site_closed'] = microtime(true);

        //检查是否启用IP控制
        // if (!isIpAccess('ipaccess') && APP_NAME !='admin') {
        //     $this->site['site_closed'] = 0;
        //     $this->page404('你的IP地址已被禁止'); exit();
        // }
        // $GLOBALS['time_run_detail']['action_init_site_ipaccess'] = microtime(true);

        //检查是否启用rewrite
        if (isset($this->site['site_rewrite_on'])) {
            C('URL_ROUTER_ON', ($this->site['site_rewrite_on'] == 1));
        }
        $GLOBALS['time_run_detail']['action_init_site_rewrite'] = microtime(true);

        $this->langJsList = $this->site['langJsList'];

        $this->site['sys_version'] = C('VERSION');

        $GLOBALS['ts']['site'] = $this->site;

        //网站全局变量过滤插件
        Addons::hook('core_filter_init_site');

        $this->assign('site', $this->site);
        $this->assign('site_top_nav', $this->site['site_top_nav']);
        $this->assign('site_bottom_nav', $this->site['site_bottom_nav']);
        $this->assign('site_bottom_child_nav', $this->site['site_bottom_child_nav']);
        $this->assign('site_guest_nav', $this->site['site_guest_nav']);
        $this->assign('site_nav_apps', $this->site['site_nav_apps']);
        $this->assign('menuList', $this->site['search_menu_list']);
        $this->assign('initNums', $this->site['initNums']);

        $GLOBALS['time_run_detail']['action_init_site_end'] = microtime(true);

        return true;
    }

    /**
     * 应用信息初始化
     *
     */
    private function initApp()
    {
        $GLOBALS['time_run_detail']['action_init_app_start'] = microtime(true);

        //是否为核心的应用
        if (in_array(APP_NAME, C('DEFAULT_APPS'))) {
            return true;
        }

        //加载后台已安装应用列表
        $GLOBALS['ts']['app'] = $this->app = model('App')->getAppByName(APP_NAME);

        if (empty($this->app) || !$this->app) {
            $this->error('此应用不存在');

            return false;
        }

        if (!empty($this->app) && $this->app['status'] == 0) {
            $this->error('此应用已经关闭');

            return false;
        }

        Addons::hook('core_filter_init_app');

        $GLOBALS['time_run_detail']['action_init_app_end'] = microtime(true);

        return true;
    }

    /**
     * 用户信息初始化
     */
    private function initUser()
    {
        $GLOBALS['time_run_detail']['action_init_user_start'] = microtime(true);

        // 邀请跳转
        if (isset($_GET['invite']) && APP_NAME.'/'.MODULE_NAME != 'public/Register') {
            redirect(U('public/Register/index', array('invite' => t($_GET['invite']))));
            exit();
        }

        // 验证登陆
        if (intval($_SESSION['mid']) == 0 && model('Passport')->needLogin()) {
            if (defined('LOGIN_URL')) {
                redirect(LOGIN_URL);
            } else {
                if (APP_NAME == 'admin') {
                    if (MODULE_NAME != 'Public' && !model('Passport')->checkAdminLogin()) {
                        redirect(U('admin/Public/login'));
                        exit();
                    }
                } else {
                    redirect(U('public/Passport/login'));
                    exit();
                }
            }
        }

        $GLOBALS['time_run_detail']['action_init_user_login'] = microtime(true);

        //判断登录有效期

        $activeTime = cookie('TSV4_ACTIVE_TIME');
        if (C('COOKIE_EXPIRE') && $activeTime < time() && APP_NAME != 'admin' && ACTION_NAME != 'login') {
            unset($_SESSION['mid']);
            cookie('TSV4_LOGGED_USER', null);
            $this->assign('jumpUrl', U('public/Passport/login'));
            $this->error(L('PUBLIC_TIME_OUT'));
            exit();
        } else {
            cookie('TSV4_ACTIVE_TIME', time() + C('COOKIE_EXPIRE'));
        }

        //当前登录者uid
        $GLOBALS['ts']['mid'] = $this->mid = intval($_SESSION['mid']);

        //当前访问对象的uid
        $GLOBALS['ts']['uid'] = $this->uid = intval($_REQUEST['uid'] == 0 ? $this->mid : $_REQUEST['uid']);

        // 验证站点访问权限
        // 验证应用访问权限

        // 获取用户基本资料
        if ($this->mid > 0 || $this->uid > 0) {
            $GLOBALS['ts']['user'] = !empty($this->mid) ? $this->user = model('User')->getUserInfo($this->mid) : array();

            if ($this->mid != $this->uid) {
                $GLOBALS['ts']['_user'] = !empty($this->uid) ? model('User')->getUserInfo($this->uid) : array();
            } else {
                $GLOBALS['ts']['_user'] = $GLOBALS['ts']['user'];
            }

            $GLOBALS['time_run_detail']['action_init_user_info'] = microtime(true);

            // 未初始化
            $module_arr = array('Register' => 1, 'Passport' => 1, 'Account' => 1);
            if (0 < $this->mid && 0 == $this->user ['is_init'] && APP_NAME != 'admin' && ! isset($module_arr [MODULE_NAME])) {
                // 注册完成后就开启此功能
                if ($this->user ['is_active'] == '0') {
                    U('public/Register/waitForActivation', 'uid='.$this->mid, true);
                } else {
                    $init_config = model('Xdata')->get('admin_Config:register');
                    $user_tags = D('app_tag')->where('row_id='.$this->mid)->findAll();

                    //若开启资料完善
                    if ($init_config['personal_open']) {
                        if (in_array('face', $init_config['personal_required']) && !model('Avatar')->hasAvatar()) {
                            U('public/Register/step2', '', true);
                        } elseif (in_array('location', $init_config['personal_required']) && $GLOBALS['ts']['_user']['location'] == '') {
                            U('public/Register/step3', '', true);
                        } elseif (in_array('intro', $init_config['personal_required']) && $GLOBALS['ts']['_user']['intro'] == '') {
                            U('public/Register/step3', '', true);
                        } elseif (in_array('tag', $init_config['personal_required']) && !$user_tags) {
                            U('public/Register/step3', '', true);
                        }
                    }
                    /*
                    if ($init_config ['photo_open']) {
                        U ( 'public/Register/step2', '', true );
                    }
                    if ($init_config ['tag_open']) {
                        U ( 'public/Register/step3', '', true );
                    }*/

                    // 添加双向关注用户
                    $registerConfig = model('Xdata')->get('admin_Config:register');
                    $eachFollow = $registerConfig['each_follow'];
                    if (!empty($eachFollow)) {
                        model('Follow')->eachDoFollow($this->mid, $eachFollow);
                    }
                    // 添加默认关注用户
                    $defaultFollow = $registerConfig['default_follow'];
                    $defaultFollow = array_diff(explode(',', $defaultFollow), explode(',', $eachFollow));
                    if (!empty($defaultFollow)) {
                        model('Follow')->bulkDoFollow($this->mid, $defaultFollow);
                    }

                    model('Register')->overUserInit($GLOBALS ['ts'] ['mid']);
                    U('square/Index/index', '', true);
                }
            }

            $GLOBALS['time_run_detail']['action_init_user_inition'] = microtime(true);

            if ($this->uid > 0 && $this->initUserData) {
                //当前用户的所有已添加的应用
                $GLOBALS['ts']['_userApp'] = $userApp = model('UserApp')->getUserApp($this->uid);
                $GLOBALS['time_run_detail']['action_init_user_data_app'] = microtime(true);
                //当前用户的统计数据
                $GLOBALS['ts']['_userData'] = $userData = model('UserData')->getUserData($this->uid);
                $GLOBALS['time_run_detail']['action_init_user_data_data'] = microtime(true);
                $userCredit = model('Credit')->getUserCredit($this->uid);
                $GLOBALS['time_run_detail']['action_init_user_data_credit'] = microtime(true);
                $this->assign('userCredit', $userCredit);
                $this->assign('_userData', $userData);
                $this->assign('_userApp', $userApp);
            }

            $GLOBALS['time_run_detail']['action_init_user_data'] = microtime(true);

            $GLOBALS['time_run_detail']['action_init_user_disable'] = microtime(true);

            //oauth_token
            $login = D('login')->where('uid='.$this->mid." AND type='location'")->find();
            if (! $login) {
                $data['uid'] = $this->mid;
                $data['oauth_token'] = getOAuthToken($this->mid);
                $data['oauth_token_secret'] = getOAuthTokenSecret();
                $savedata['type'] = 'location';
                $savedata = array_merge($savedata, $data);
                D('')->table(C('DB_PREFIX').'login')->add($savedata);
            } else {
                $data['oauth_token'] = $login['oauth_token'];
                $data['oauth_token_secret'] = $login['oauth_token_secret'];
            }
            $login = $data;
        }

        $this->user = EmojiFormat::de($this->user);

        $this->assign('mid', $this->mid);   //登录者
        $this->assign('uid', $this->uid);   //访问对象
        $this->assign('user', $this->user); //当前登陆的人
        $this->assign('login_token', $login);

        Addons::hook('core_filter_init_user');

        $GLOBALS['time_run_detail']['action_init_user_end'] = microtime(true);

        return true;
    }

    /**
     * 重设访问对象的用户信息 主要用于重写等地方
     */
    public function reinitUser($uid = '')
    {
        if (empty($uid) || $this->mid == $uid) {
            return true;
        }

        $GLOBALS['ts']['uid'] = $_REQUEST['uid'] = $this->uid = $uid;
        $GLOBALS['ts']['_user'] = model('User')->getUserInfo($this->uid);
        //当前用户的所有已添加的应用
        $GLOBALS['ts']['_userApp'] = $userApp = model('UserApp')->getUserApp($this->uid);
        //当前用户的统计数据
        $GLOBALS['ts']['_userData'] = $userData = model('UserData')->getUserData($this->uid);
        $userCredit = model('Credit')->getUserCredit($this->uid);

        $this->assign('uid', $this->uid);   //访问对象
        $this->assign('_userData', $userData);
        $this->assign('_userApp', $userApp);
        $this->assign('userCredit', $userCredit);
    }

    /**
     * 魔术方法 有不存在的操作的时候
     * @param  string $method 方法名
     * @param  array  $parms
     * @return mix
     */
    public function __call($method, $parms)
    {
        if (0 === strcasecmp($method, ACTION_NAME)) {
            // 检查扩展操作方法
            $_action = C('_actions_');
            if ($_action) {
                // 'module:action'=>'callback'
                if (isset($_action[MODULE_NAME.':'.ACTION_NAME])) {
                    $action = $_action[MODULE_NAME.':'.ACTION_NAME];
                } elseif (isset($_action[ACTION_NAME])) {
                    // 'action'=>'callback'
                    $action = $_action[ACTION_NAME];
                }
                if (!empty($action)) {
                    call_user_func($action);

                    return ;
                }
            }
            // 如果定义了_empty操作 则调用
            if (method_exists($this, '_empty')) {
                $this->_empty($method, $parms);
            } else {
                // 检查是否存在默认模版 如果有直接输出模版
                    $this->display();
            }
        } elseif (in_array(strtolower($method), array('ispost', 'isget', 'ishead', 'isdelete', 'isput'))) {
            return strtolower($_SERVER['REQUEST_METHOD']) == strtolower(substr($method, 2));
        } else {
            throw_exception(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));
        }
    }

    /**
     * 模板Title
     * @param mixed $input 要
     * @return
     */
    public function setTitle($title = '')
    {
        Addons::hook('core_filter_set_title', $title);
        $this->assign('_title', $title);
    }

    /**
     * 模板keywords
     * @param mixed $input 要
     * @return
     */
    public function setKeywords($keywords = '')
    {
        $this->assign('_keywords', $keywords);
    }

    /**
     * 模板description
     * @param mixed $input 要
     * @return
     */
    public function setDescription($description = '')
    {
        $this->assign('_description', $description);
    }

    /**
     * 模板变量赋
     * @param mixed $name  要显示的模板变量
     * @param mixed $value 变量的
     */
    public function assign($name, $value = '')
    {
        if (is_array($name)) {
            $this->tVar = array_merge($this->tVar, $name);
        } elseif (is_object($name)) {
            foreach ($name as $key => $val) {
                $this->tVar[$key] = $val;
            }
        } else {
            $this->tVar[$name] = $value;
        }
    }

    /**
     * 魔术方法：注册模版变量
     * @param  string $name  模版变量
     * @param  mix    $value 变量值
     * @return mixed
     */
    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }

    /**
     * 取得模板显示变量的值
     * @param  string $name 模板显示变量
     * @return mixed
     */
    protected function get($name)
    {
        if (isset($this->tVar[$name])) {
            return $this->tVar[$name];
        } else {
            return false;
        }
    }

    /**
     * Trace变量赋值
     * @param mixed $name  要显示的模板变量
     * @param mixed $value 变量的值
     */
    protected function trace($name, $value = '')
    {
        if (is_array($name)) {
            $this->trace = array_merge($this->trace, $name);
        } else {
            $this->trace[$name] = $value;
        }
    }

    /**
     * 模板显示
     * 调用内置的模板引擎显示方法
     * @param  string $templateFile 指定要调用的模板文件
     *                              默认为空 由系统自动定位模板文件
     * @param  string $charset      输出编码
     * @param  string $contentType  输出类
     * @return voi
     */
    protected function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        echo $this->fetch($templateFile, $charset, $contentType);
    }

    /**
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法
     * @param  string $templateFile 指定要调用的模板文件
     *                              默认为空 由系统自动定位模板文件
     * @param  string $charset      输出编码
     * @param  string $contentType  输出类
     * @return strin
     */
    protected function fetch($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $GLOBALS['time_run_detail']['action_display_before_fetch'] = microtime(true);
        $this->assign('appCssList', $this->appCssList);
        $this->assign('appJsList', $this->appJsList);
        $this->assign('langJsList', $this->langJsList);
        Addons::hook('core_display_tpl', array('tpl' => $templateFile, 'vars' => $this->tVar, 'charset' => $charset, 'contentType' => $contentType, 'display' => $display));
        $content = fetch($templateFile, $this->tVar, $charset, $contentType);
        $this->buildHtml($content);

        return $content;
    }

    /**
     *  输出静态化内容
     * @param  string $content 模板内容
     * @return boolen
     */
    protected function buildHtml($content)
    {
        //规则验证
        $html = C('html');
        if (isset($html[ACTION_CODE]) && $html[ACTION_CODE] == true) {
            $htmlpath = CORE_RUN_PATH.'/htmlcache/';
            $htmlfile = str_replace('/', '_', ACTION_CODE).'.html';
            // 如果静态目录不存在 则创建
            if (!is_dir($htmlpath)) {
                mkdir($htmlpath, 0777, true);
            }

            return file_put_contents($htmlpath.'/'.$htmlfile, $content);
        }
    }

    /**
     * 操作错误跳转的快捷方
     * @param  string  $message 错误信息
     * @param  Boolean $ajax    是否为Ajax方
     * @return voi
     */
    protected function error($message, $ajax = false)
    {
        Addons::hook('core_filter_error_message', $message);
        $this->_dispatch_jump($message, 0, $ajax);
    }

    protected function page404($message)
    {
        $this->assign('site_closed', $this->site['site_closed']);
        $this->assign('message', $message);
        $this->display(THEME_PATH.'/page404.html');
    }
    /**
     * 操作成功跳转的快捷方
     * @param  string  $message 提示信息
     * @param  Boolean $ajax    是否为Ajax方
     * @return voi
     */
    protected function success($message = '', $ajax = false)
    {
        Addons::hook('core_filter_success_message', $message);
        $this->_dispatch_jump($message, 1, $ajax);
    }

    /**
     * Ajax方式返回数据到客户端
     * @param mixed  $data   要返回的数据
     * @param String $info   提示信息
     * @param bool   $status 返回状态
     * @param String $status ajax返回类型 JSON XML
     */
    protected function ajaxReturn($data, $info = '', $status = 1, $type = 'JSON')
    {
        // 保证AJAX返回后也能保存知识
        if (C('LOG_RECORD')) {
            Log::save();
        }
        $result = array();
        $result['status'] = $status;
        $result['info'] = $info;
        $result['data'] = $data;
        if (empty($type)) {
            $type = C('DEFAULT_AJAX_RETURN');
        }
        if (strtoupper($type) == 'JSON') {
            // 返回JSON数据格式到客户端 包含状态信息
            header('Content-Type:application/json; charset=utf-8');
            exit(json_encode($result));
        } elseif (strtoupper($type) == 'XML') {
            // 返回xml格式数据
            header('Content-Type:text/xml; charset=utf-8');
            exit(xml_encode($result));
        } elseif (strtoupper($type) == 'EVAL') {
            // 返回可执行的js脚本
            header('Content-Type:text/html; charset=utf-8');
            exit($data);
        } else {
            // TODO 增加其它格式
        }
    }

    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     * @param string $url 跳转的URL表达式
     * @param array  $params 其它URL参数
     * @param int    $delay 延时跳转的时间 单位为秒
     * @param string $msg   跳转提示信息
     */
    protected function redirect($url, $params = array(), $delay = 0, $msg = '')
    {
        if (C('LOG_RECORD')) {
            Log::save();
        }
        $url = U($url, $params);
        redirect($url, $delay, $msg);
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     * @param string  $message 提示信息
     * @param Boolean $status  状态
     * @param Boolean $ajax    是否为Ajax方式
     */
    private function _dispatch_jump($message, $status = 1, $ajax = false)
    {
        // 判断是否为AJAX返回
        if ($ajax || $this->isAjax()) {
            $data['jumpUrl'] = false;
            if ($this->get('jumpUrl')) {
                $data['jumpUrl'] = $this->get('jumpUrl');
            }
            $this->ajaxReturn($data, $message, $status);
        }
        // 提示标题
        $this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if ($this->get('closeWin')) {
            $this->assign('jumpUrl', 'javascript:window.close();');
        }
        $this->assign('status', $status);   // 状态
        empty($message) && ($message = $status == 1 ? '操作成功' : '操作失败');
        $this->assign('message', $message); // 提示信息
        $tpl = 'success.html';
        isMobile() && $tpl = 'wap_success.html';
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON', false);
        if ($status) { //发送成功信息
            // 成功操作后默认停留1秒
            if (!$this->get('waitSecond')) {
                $this->assign('waitSecond', '2');
            }
            // 默认操作成功自动返回操作前页面
            if (!$this->get('jumpUrl')) {
                $this->assign('jumpUrl', $_SERVER['HTTP_REFERER']);
            }
            //sociax:2010-1-21
            //$this->display(C('TMPL_ACTION_SUCCESS'));
            $this->display(THEME_PATH.'/'.$tpl);
        } else {
            //发生错误时候默认停留3秒
            if (!$this->get('waitSecond')) {
                $this->assign('waitSecond', '5');
            }
            // 默认发生错误的话自动返回上页
            if (!$this->get('jumpUrl')) {
                $this->assign('jumpUrl', 'javascript:history.back(-1);');
            }
            //sociax:2010-1-21
            //$this->display(C('TMPL_ACTION_ERROR'));

            $this->display(THEME_PATH.'/'.$tpl);
        }
        if (C('LOG_RECORD')) {
            Log::save();
        }
        // 中止执行  避免出错后继续执行
        exit ;
    }

    /**
     * 是否AJAX请求
     * @return bool
     */
    protected function isAjax()
    {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            if ('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                return true;
            }
        }
        if (!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) {
            // 判断Ajax方式提交
            return true;
        }

        return false;
    }
} //类定义结束
