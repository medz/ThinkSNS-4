<?php
/**
 * ThinkSNS API接口抽象类
 * @author liuxiaoqing@zhishisoft.com
 * @version TS4.0
 */
class Api
{
    public $mid; //当前登陆的用户ID
    public $since_id;
    public $max_id;
    public $page;
    public $count;
    public $user_id;
    public $user_name;
    public $id;
    public $data;
    public $error;

    /**
     * 架构函数
     * @param bool $location 是否本机调用，本机调用不需要认证
     */
    public function __construct($location = false)
    {
        //$this->mid = $_SESSION['mid'];
        //外部接口调用
        if ($location == false && !defined('DEBUG')) {
            $this->verifyUser();
        //本机调用
        } else {
            $this->mid = @intval($_SESSION['mid']);
        }

        $GLOBALS['ts']['mid'] = $this->mid;

        //默认参数处理
        $this->since_id = isset($_REQUEST['since_id']) ? intval($_REQUEST['since_id']) : '';
        $this->max_id = isset($_REQUEST['max_id']) ? intval($_REQUEST['max_id']) : '';
        $this->page = isset($_REQUEST['page']) ? intval($_REQUEST['page']) : 1;
        $this->count = isset($_REQUEST['count']) ? intval($_REQUEST['count']) : 20;
        $this->user_id = isset($_REQUEST['user_id']) ? intval($_REQUEST['user_id']) : 0;
        $this->user_name = isset($_REQUEST['user_name']) ? h($_REQUEST['user_name']) : '';
        $this->uid = isset($_REQUEST['uid']) ? intval($_REQUEST['uid']) : 0;
        $this->uname = isset($_REQUEST['uname']) ? h($_REQUEST['uname']) : '';
        $this->id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $this->data = $_REQUEST;

        // findPage
        $_REQUEST[C('VAR_PAGE')] = $this->page;

        //接口初始化钩子
        Addons::hook('core_filter_init_api');

        //控制器初始化
        if (method_exists($this, '_initialize')) {
            $this->_initialize();
        }
    }

    /**
     * 用户身份认证
     */
    private function verifyUser()
    {
        $canaccess = false;

        //ACL访问控制
        if (file_exists(SITE_PATH.'/config/api.inc.php')) {
            $acl = include SITE_PATH.'/config/api.inc.php';
        }

        if (!isset($acl['access'])) {
            $acl['access'] = array('Oauth/*' => true);
        }

        if (isset($acl['access'][MODULE_NAME.'/'.ACTION_NAME])) {
            $canaccess = (boolean) $acl['access'][MODULE_NAME.'/'.ACTION_NAME];
        } elseif (isset($acl['access'][MODULE_NAME.'/*'])) {
            $canaccess = (boolean) $acl['access'][MODULE_NAME.'/*'];
        } else {
            $canaccess = false;
        }

        //白名单无需认证
        if ($canaccess) {
            return;
        }

        //签名验证方法
        if (isset($_REQUEST['app_signature']) && isset($_REQUEST['app_id'])) {
            $signature = t($_REQUEST['app_signature']);
            $app_uid = (int) $_REQUEST['app_uid'];
            $app_time = (int) $_REQUEST['app_time'];
            $app_id = t($_REQUEST['app_id']);
            $app_secret = C('APP_SECRET');

            //过期时间判断 - 默认10分钟
            if ((time() - $app_time) > 600) {
                $message['msg'] = '接口认证失败';
                $message['status'] = 403;
                //兼容
                $message['message'] = '接口认证失败';
                $message['code'] = '00001';

                return $this->error($message);
            }

            //签名判断
            $tmpArr = array($app_time, $app_uid, $app_token, $app_secret);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr, '&');
            $tmpStr = md5($tmpStr);
            if ($tmpStr == $signature) {
                $_SESSION['mid'] = $app_uid;

                return true;
            } else {
                $message['msg'] = '签名认证失败';
                $message['status'] = 403;
                //兼容
                $message['message'] = '签名认证失败';
                $message['code'] = '00001';

                return $this->error($message);
            }
        }

        //OAUTH_TOKEN认证
        $token_key = $_REQUEST['oauth_token'].$_REQUEST['oauth_token_secret'];
        $login = S($token_key);
        if (!$login) {
            if (isset($_REQUEST['oauth_token'])) {
                $verifycode['oauth_token'] = h($_REQUEST['oauth_token']);
                $verifycode['oauth_token_secret'] = h($_REQUEST['oauth_token_secret']);
                $verifycode['type'] = 'location';
                $login = D('Login')->where($verifycode)->getField('uid');
                if (isset($login) && $login > 0) {
                    $this->mid = (int) $login;
                    $_SESSION['mid'] = $this->mid;
                    $canaccess = true;
                    S($token_key, $login, 84600);
                } else {
                    $canaccess = false;
                }
            }
        } else {
            $this->mid = (int) $login;
            $_SESSION['mid'] = $this->mid;
            $canaccess = true;
            $canaccess = true;
        }

        if (!$canaccess) {
            $message['msg'] = '接口认证失败';
            $message['status'] = 403;
            //兼容
            $message['message'] = '接口认证失败';
            $message['code'] = '00001';

            return $this->error($message);
        }
    }

    /**
     * 输出API认证失败信息
     * @return object|json
     */
    protected function verifyError()
    {
        $message['msg'] = '接口认证失败';
        $message['status'] = 403;
        $message['message'] = '接口认证失败';
        $message['code'] = '00001';

        return $this->error($message);
    }

    /**
     * 通过api方法调用API时的赋值
     * api('WeiboStatuses')->data($data)->public_timeline();
     * @param array $data 方法调用时的参数
     */
    public function data($data)
    {
        if (is_object($data)) {
            $data = get_object_vars($data);
        }
        $this->since_id = $data['since_id'] ? intval($data['since_id']) : '';
        $this->max_id = $data['max_id'] ? intval($data['max_id']) : '';
        $this->page = $data['page'] ? intval($data['page']) : 1;
        $this->count = $data['count'] ? intval($data['count']) : 20;
        $this->user_id = $data['user_id'] ? intval($data['user_id']) : $this->mid;
        $this->user_name = $data['user_name'] ? h($data['user_name']) : '';
        $this->uid = $_REQUEST['uid'] ? intval($_REQUEST['uid']) : 0;
        $this->uname = $_REQUEST['uname'] ? h($_REQUEST['uname']) : '';
        $this->id = $data['id'] ? intval($data['id']) : 0;
        $this->data = $data;

        return $this;
    }

    //返回错误信息
    public static function error($msg = '')
    {
        $message['msg'] = '操作失败';
        $message['status'] = 0;
        if (is_array($msg)) {
            $message = array_merge($message, $msg);
        } elseif ($msg != '') {
            $message['msg'] = t($msg);
        }

        //格式化输出
        if (isset($_REQUEST['format']) && $_REQUEST['format'] == 'test') {
            //测试输出
            dump($message);
            exit;
        } else {
            exit(json_encode($message));
        }
    }

    //返回成功信息
    public static function success($msg = '')
    {
        $message['msg'] = '操作成功';
        $message['status'] = 1;
        if (is_array($msg)) {
            $message = array_merge($message, $msg);
        } elseif ($msg != '') {
            $message['msg'] = t($msg);
        }

        //格式化输出
        if (isset($_REQUEST['format']) && $_REQUEST['format'] == 'test') {
            //测试输出
            dump($message);
            exit;
        } else {
            exit(json_encode($message));
        }
    }

    //返回错误信息
    public static function getError()
    {
        return $this->error;
    }

    /**
     * 运行控制器
     */
    public static function run()
    {

        // 设定错误和异常处理
        set_error_handler(array('App', 'appError'));
        set_exception_handler(array('App', 'appException'));

        // Session初始化
        if (!session_id()) {
            session_start();
        }

        // 模版检查
        $GLOBALS['time_run_detail']['init_end'] = microtime(true);

        //检查服务器是否开启了zlib拓展
        if (C('GZIP_OPEN') && extension_loaded('zlib') && function_exists('ob_gzhandler')) {
            ob_end_clean();
            ob_start('ob_gzhandler');
        }

        $GLOBALS['time_run_detail']['obstart'] = microtime(true);

        $pharApiFile = sprintf('%s/api/ts-api.phar', TS_ROOT);
        if (
            constant('API_VERSION') &&
            API_VERSION == 'sociax' &&
            !\Medz\Component\Filesystem\Filesystem::exists(sprintf('%s/api/sociax', TS_ROOT))
        ) {
            $class_file = sprintf('phar://%s/%sApi.class.php', $pharApiFile, MODULE_NAME);
        } elseif (constant('API_VERSION')) {
            $class_file = SITE_PATH.'/api/'.API_VERSION.'/'.MODULE_NAME.'Api.class.php';
        } else {
            $class_file = SITE_PATH.'/api/thinksns/'.MODULE_NAME.'Api.class.php';
        }

        if (!file_exists($class_file)) {
            $message['msg'] = '接口不存在';
            $message['status'] = 404;
            API::error($message);
        }

        //执行当前操作
        include $class_file;
        $className = MODULE_NAME.'Api';
        $module = new $className();
        $action = ACTION_NAME;
        $data = call_user_func(array(&$module, $action));

        //格式化输出
        if ($_REQUEST['format'] == 'php') {
            //输出php格式
            echo var_export($data);
        } elseif ($_REQUEST['format'] == 'test') {
            //测试输出
            dump($data);
        } else {
            header('Content-Type:application/json');
            echo json_encode($data);
        }

        //输出buffer中的内容，即压缩后的css文件
        if (C('GZIP_OPEN') && extension_loaded('zlib') && function_exists('ob_gzhandler')) {
            ob_end_flush();
        }

        $GLOBALS['time_run_detail']['obflush'] = microtime(true);

        if (C('LOG_RECORD')) {
            Log::save();
        }

        return ;
    }

    /**
     * app异常处理
     */
    public static function appException($e)
    {
        die('system_error:'.$e->__toString());
    }

    /**
     * 自定义错误处理
     * @param int    $errno   错误类型
     * @param string $errstr  错误信息
     * @param string $errfile 错误文件
     * @param int    $errline 错误行数
     */
    public static function appError($errno, $errstr, $errfile, $errline)
    {
        switch ($errno) {
          case E_ERROR:
          case E_USER_ERROR:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            //if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
            echo $errorStr;
            break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            //Log::record($errorStr,Log::NOTICE);
            break;
      }
    }
}
