<?php

use Composer\Autoload\ClassLoader;
use Ts\AutoLoader\TsAutoLoader;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

/**
 * 新入口核心
 *
 * @package Ts;
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
final class Ts
{
    /**
     * 系统文件目录分隔符
     *
     * @var string
     **/
    const DS = DIRECTORY_SEPARATOR;

    /**
     * 储存数据库管理链接
     *
     * @var Illuminate\Database\Capsule\Manager
     **/
    protected static $capsule;

    /**
     * 文件列表
     *
     * @var array
     **/
    protected static $_files = array();

    /**
     * 框架根
     *
     * @var string
     **/
    protected static $_root;

    /**
     * 储存Composer自动加载类的对象
     *
     * @var new \Composer\Autoload\ClassLoader();
     **/
    protected static $_classLoader;

    /**
     * 入口文件
     *
     * @param \Composer\Autoload\ClassLoader $classLoader
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public static function run(ClassLoader $classLoader)
    {
        self::$_classLoader = $classLoader;
        self::init();
        /* 新的自动加载类 */
        spl_autoload_register(function ($namespace) {
            TsAutoLoader::entry($namespace);
        });

        // var_dump(Capsule::table('user')->where('uid', 1)->select('uname')->get());exit;
    }

    /**
     * 获取框架根目录
     *
     * @return string
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public static function getRootPath()
    {
        return self::$_root;
    }

    /**
     * 初始化
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    protected static function init()
    {
        self::$_root = dirname(__FILE__);
        /* # 设置时区 */
        if (function_exists('date_default_timezone_set')) {
            date_default_timezone_set('Asia/Shanghai');
        }

        /* 初始化数据库 */
        self::$capsule = new Capsule;
        self::$capsule->addConnection((array) include TS_CONFIGURE.'/database.php');
        self::$capsule->setEventDispatcher(new Dispatcher(new Container));
        // Make this Capsule instance available globally via static methods... (optional)
        self::$capsule->setAsGlobal();
        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        self::$capsule->bootEloquent();
        // 关闭日志功能
        self::$capsule->connection()->disableQueryLog();
    }

    /**
     * 文件加载类
     *
     * @param string $name 文件名
     * @param string $ext  文件拓展名
     * @param param [param ...] 按照完整路径的层级，最后一个默认为拓展名
     * @return bool
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public static function import($name, $ext = '.php')
    {
        $name = func_get_args();
        $ext = array_pop($name);
        $name = implode(self::DS, $name);
        $name .= $ext;
        unset($ext);

        if (isset(self::$_files[$name])) {
            return self::$_files[$name];
        } elseif (file_exists($name) && is_file($name)) {
            self::$_files[$name] = true;
            include_once $name;
        } else {
            self::$_files[$name] = false;
        }

        return self::$_files[$name];
    }

    /**
     * 取得Composer的ClassLoader对象
     *
     * @return new \Composer\Autoload\ClassLoader();
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public static function classLoader()
    {
        return self::$_classLoader;
    }

    /**
     * 取得 Illuminate\Database\Capsule\Manager
     *
     * @return Illuminate\Database\Capsule\Manager
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public static function getCapsule()
    {
        return self::$capsule;
    }
} // END final class Ts
