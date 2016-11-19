<?php

use Ts\Helper\AutoJumper\Jumper;

/**
 * ThinkSNS App基类
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS v4
 */
class App
{
    /**
     * App初始化
     */
    public static function init()
    {
        // 设定错误和异常处理
        set_error_handler(array('App', 'appError'));
        set_exception_handler(array('App', 'appException'));

        // Session初始化
        if (!session_id()) {
            session_start();
        }

        // 加载所有插件
        if (C('APP_PLUGIN_ON')) {
            Addons::loadAllValidAddons();
        }
    }

    /**
     * 运行控制器
     */
    public static function run()
    {
        App::init();

        $GLOBALS['time_run_detail']['init_end'] = microtime(true);

        //检查服务器是否开启了zlib拓展
        if (C('GZIP_OPEN') && extension_loaded('zlib') && function_exists('ob_gzhandler')) {
            ob_end_clean();
            ob_start('ob_gzhandler');
        }

        $GLOBALS['time_run_detail']['obstart'] = microtime(true);

        //API控制器
        if (APP_NAME == 'api') {
            App::execApi();

            $GLOBALS['time_run_detail']['execute_api_end'] = microtime(true);

        //Widget控制器
        } elseif (APP_NAME == 'widget') {
            App::execWidget();

            $GLOBALS['time_run_detail']['execute_widget_end'] = microtime(true);

        //Plugin控制器
        } elseif (APP_NAME == 'plugin') {
            App::execPlugin();

            $GLOBALS['time_run_detail']['execute_plugin_end'] = microtime(true);

        //APP控制器
        } else {
            App::execApp();

            $GLOBALS['time_run_detail']['execute_app_end'] = microtime(true);
        }

        //输出buffer中的内容，即压缩后的css文件
        if (C('GZIP_OPEN') && extension_loaded('zlib') && function_exists('ob_gzhandler')) {
            ob_end_flush();
        }

        $GLOBALS['time_run_detail']['obflush'] = microtime(true);

        if (C('LOG_RECORD')) {
            Log::save();
        }

        $GLOBALS['time_run_detail']['logsave'] = microtime(true);

        return ;
    }

    /**
     * 执行App控制器
     */
    public static function execApp()
    {
        //防止CSRF
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && stripos($_SERVER['HTTP_REFERER'], SITE_URL) !== 0 && $_SERVER['HTTP_USER_AGENT'] !== 'Shockwave Flash' && (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'adobe flash player') === false) && MODULE_NAME != 'Weixin') {
            exit('illegal request.');
        }

        // 微信自动登陆和绑定
        $openid = session('wx_open_id');
        $mid = $_SESSION['mid'] ?: $_SESSION['uid'];
        // var_dump($openid);exit;
        if ($openid) {
            $login = Ts\Models\Login::byType('weixin')
                ->byVendorId($openid)
                ->orderBy('login_id', 'desc')
                ->first()
            ;

            if (!$login && $mid) {
                $login = new Ts\Model\Login;
                $login->uid = $mid;
                $login->type_uid = $openid;
                $login->type = 'weixin';
                $login->is_sync = 0;
                $login->save();
                // var_dump(123);
            }

            // var_dump($openid, $login, $_SESSION);exit;

            if (!$mid && $login) {
                $_SESSION['mid'] = $login->uid;
            }
        }

        //  微信
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false && isset($_REQUEST['w_sign']) && !$openid) {
            U('h5/sign/weixin', '', true);

        /* # 跳转移动版首页，以后有时间，可以做兼容跳转 */
        } elseif (
            /* # 是否不是wap，强制访问pc SESSION记录 */
            $_SESSION['wap_to_normal'] != 1 and
            /* # 是否不是wap，强制访问pc SCOOKIE记录 */
            cookie('wap_to_normal') != 1 and
            /* # 是否请求参数带强制访问 */
            $_REQUEST['wap_to_normal'] != 1 and
            /* # 默认iPad不跳转，目前iPad显示PC页面不宽 */
            !isiPad() and
            /* # 是否开启了移动端开关 */
            json_decode(json_encode(model('Xdata')->get('admin_Mobile:setting')), false)->switch and
            in_array(APP_NAME, array('public', 'channel', 'weiba', 'square', 'people')) and
            MODULE_NAME != 'Widget' and
            !in_array(strtolower(MODULE_NAME), array('message', 'register', 'feed')) and
            strtolower(ACTION_NAME) != 'message' and
            isMobile()
        ) {
            if (\Medz\Component\Filesystem\Filesystem::exists(TS_APPLICATION.'/h5') === true) {
                Jumper::start();
            }
        }

        $GLOBALS['time_run_detail']['addons_end'] = microtime(true);

        //创建Action控制器实例
        $className = MODULE_NAME.'Action';
        // tsload(APP_ACTION_PATH.'/'.$className.'.class.php');

        $action = ACTION_NAME; // action名称

        $appTimer = sprintf('%s/%s/app/%s/timer', TS_ROOT, TS_STORAGE, strtolower(APP_NAME));
        if (
            !file_exists($appTimer) || // 不存在
            (time() - file_get_contents($appTimer)) > 604800 || // 七天为一个更新周期
            (defined('TS_APP_DEV') && TS_APP_DEV == true)
        ) {
            \Ts\Helper\AppInstall::getInstance(APP_NAME)->moveResources();
            \Medz\Component\Filesystem\Filesystem::mkdir(dirname($appTimer), 0777);
            file_put_contents($appTimer, time());
        }

        $app = new \Ts\Helper\Controller();
        $app
            ->setApp(APP_NAME)
            ->setController(MODULE_NAME)
            ->setAction(ACTION_NAME)
            ->run()
        ;

        //执行计划任务
        model('Schedule')->run();

        $GLOBALS['time_run_detail']['action_run'] = microtime(true);

        return ;
    }

    /**
     * 执行Api控制器
     */
    public static function execApi()
    {
        include_once SITE_PATH.'/api/'.API_VERSION.'/'.MODULE_NAME.'Api.class.php';
        $className = MODULE_NAME.'Api';
        $module = new $className();
        $action = ACTION_NAME;
        //执行当前操作
        $data = call_user_func(array(&$module, $action));
        $format = (in_array($_REQUEST['format'], array('json', 'php', 'test'))) ? $_REQUEST['format'] : 'json';
        $format = strtolower($format);
        /* json */
        if ($format == 'json') {
            ob_end_clean();
            ob_start(function ($buffer, $mode) {
                if (extension_loaded('zlib') and function_exists('ob_gzhandler')) {
                    return ob_gzhandler($buffer, $mode);
                }

                return $buffer;
            });
            header('Content-type:application/json;charset=utf-8');
            echo json_encode($data);
            ob_end_flush();
            exit;

        /* php */
        } elseif ($format == 'php') {
            var_export($data);
            exit;

        /* test */
        } elseif ($format == 'test') {
            dump($data);
            exit;
        }

        return ;
    }

    /**
     * 执行Widget控制器
     */
    public static function execWidget()
    {

        //防止CSRF
        if (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST' && stripos($_SERVER['HTTP_REFERER'], SITE_URL) !== 0 && $_SERVER['HTTP_USER_AGENT'] !== 'Shockwave Flash' && (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'adobe flash player') === false) && MODULE_NAME != 'Weixin') {
            die('illegal request.');
        }

        $className = MODULE_NAME.'Widget';
        if (!class_exists($className)) {
            if (file_exists(APP_PATH.'/Lib/Widget/'.MODULE_NAME.'Widget/'.MODULE_NAME.'Widget.class.php')) {
                tsload(APP_PATH.'/Lib/Widget/'.MODULE_NAME.'Widget/'.MODULE_NAME.'Widget.class.php');
            } else {
                tsload(APPS_PATH.'/'.$_GET['app_widget'].'/Lib/Widget/'.MODULE_NAME.'Widget/'.MODULE_NAME.'Widget.class.php');
            }
        }

        $module = new $className();

        //异常处理
        if (!$module) {
            // 模块不存在 抛出异常
            throw_exception(L('_MODULE_NOT_EXIST_').MODULE_NAME);
        }

        //获取当前操作名
        $action = ACTION_NAME;

        //执行当前操作
        if ($rs = call_user_func(array(&$module, $action))) {
            echo $rs;
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
} //类定义结束
