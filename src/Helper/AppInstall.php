<?php

namespace Ts\Helper;

use Exception;
use Medz\Component\Filesystem\Filesystem;
use Ts;

/**
 * 应用安�
 * 器.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class AppInstall
{
    /**
     * 需要安�
     * 的应用名称.
     *
     * @var string
     **/
    protected static $appName;

    /**
     * 储存当前实例化对象
     *
     * @var self
     **/
    protected static $instances = array();

    /**
     * 应用目录.
     *
     * @var string
     **/
    protected static $applicationDir;

    /**
     * 应用详�
     * .
     *
     * @var array
     **/
    protected static $appInfo;

    /**
     * 获取应用实例化的单例.
     *
     * @return self
     *
     * @author Seven Du <lovevipdswoutlook.com>
     **/
    public static function getInstance($appName)
    {
        self::$appName = strtolower($appName);
        if (
            !isset(self::$instances[self::$appName]) ||
            !(self::$instances[self::$appName] instanceof self)
        ) {
            self::$instances[self::$appName] = new self();
        }

        return self::$instances[self::$appName];
    }

    /**
     * 构造方法.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    protected function __construct()
    {
        if (!self::$appName) {
            throw new Exception('没有传递需要初始化的应用！', 1);
        }
        $handle = opendir(TS_APPLICATION);
        while (($file = readdir($handle)) !== false) {
            if (strtolower($file) == self::$appName) {
                self::$applicationDir = TS_APPLICATION.Ts::DS.$file;
                break;
            }
        }
        closedir($handle);
        $manageFile = self::$applicationDir.Ts::DS.'manage.json';
        if (!self::$applicationDir) {
            throw new Exception('应用：“'.self::$appName.'”不存在！', 1);
        } elseif (!Filesystem::exists($manageFile)) {
            throw new Exception(sprintf('不存在应用配置文件：“%s”', $manageFile));
        }
        self::$appInfo = file_get_contents($manageFile);
        self::$appInfo = json_decode(self::$appInfo, true);
        if (!isset(self::$appInfo['resource'])) {
            self::$appInfo['resource'] = '_static';
        }
    }

    /**
     * 复制应用的�
     * �开静态资源.
     *
     * @return self
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function moveResources()
    {
        Filesystem::mirror(
            sprintf('%s%s%s', self::$applicationDir, Ts::DS, self::$appInfo['resource']), // 原始目录
            sprintf('%s%s/app/%s', TS_ROOT, TS_STORAGE, self::$appName)                   // 目标目录
        );

        return $this;
    }

    /**
     * 移动Ts中应用中所有的静态资源.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public static function moveAllApplicationResources()
    {
        $handle = opendir(TS_APPLICATION);
        while (($file = readdir($handle)) !== false) {
            if (in_array($file, array('.', '..'))) {
                continue;
            }
            $manageFile = sprintf('%s/%s/manage.json', TS_APPLICATION, $file);
            if (
                !file_exists($manageFile) ||
                !($manageInfo = json_decode(file_get_contents($manageFile), true))
            ) {
                continue;
            } elseif (!isset($manageInfo['resource'])) {
                $manageInfo['resource'] = '_static';
            }
            Filesystem::mirror(
                sprintf('%s/%s/%s', TS_APPLICATION, $file, $manageInfo['resource']),
                sprintf('%s/%s/app/%s', TS_ROOT, TS_STORAGE, strtolower($file))
            );
        }
        closedir($handle);
    }
} // END class AppInstall
