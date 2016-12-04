<?php
/**
 * ThinkSNS插件调度类，由该对象调度插件的运行逻辑
 * @author SamPeng <penglingjun@zhishisoft.com>
 * @version TS v4
 */
class Addons
{
    private static $validHooks = array();       // 有效的插件数组
    private static $addonsObj = array();        // 插件对象
    private static $hooksObj = array();         // 钩子对象

    /**
     * 获取有效的插件列表
     * @return array 有效的插件列表
     */
    public static function getValidHooks()
    {
        return self::$validHooks;
    }

    /**
     * 调用插件Hook
     * @param string $name 钩子名称
     * @param array  $param 相关参数
     */
    public static function hook($name, $param = array())
    {
        // 验证钩子是否能够请求
        $hasValid = self::requireHooks($name);
        if (!$hasValid) {
            return false;
        }
        // 获取指定钩子下的插件列表
        $list = self::$validHooks[$name];
        // 插件路径
        $dirName = ADDON_PATH.'/plugin';
        // 插件URL
        $urlDir = SITE_URL.'/addons/plugin';
        // 调用插件中的钩子
        foreach ($list as $key => $value) {
            // 获取插件对象
            if (isset(self::$addonsObj[$key])) {
                $obj = self::$addonsObj[$key];
            } else {
                $addonPath = $dirName.'/'.$key;
                $addonUrl = $urlDir.'/'.$key;
                $filename = $addonPath.'/'.$key.'Addons.class.php';
                tsload($filename);
                $className = $key.'Addons';
                $obj = new $className();
                $obj->setPath($addonPath);
                $obj->setUrl($addonUrl);
                self::$addonsObj[$key] = $obj;
            }
            // 判断是否是简单插件
            $simple = $obj instanceof SimpleAddons;
            // 执行插件的钩子
            foreach ($value as $hook) {
                if ($simple) {
                    $obj->$hook($param);
                } else {
                    if (isset(self::$hooksObj[$hook])) {
                        self::$hooksObj[$hook]->$name($param);
                    } else {
                        $filename = $dirName.'/'.$key.'/hooks/'.$hook.'.class.php';
                        tsload($filename);
                        $tempObj = new $hook();
                        self::$hooksObj[$hook] = $tempObj;
                        $tempObj->setPath($obj->getPath());
                        $tempObj->setPath($obj->getUrl(), true);
                        $tempObj->$name($param);
                    }
                }
            }   // foreach
        }   // foreach
    }

    /**
     * 单个调用钩子
     * @param string $addonsName 插件名称
     * @param string $name       钩子名称
     * @param array  $param      相关参数
     * @param bool $admin 是否是管理员
     */
    public static function addonsHook($addonsName, $name, $param = array(), $admin = false)
    {
        if (!$addonsName) {
            return;
        }
        $addonsName = basename($addonsName);
        $dirName = ADDON_PATH.'/plugin';
        $urlDir = SITE_URL.'/addons/plugin';
        $path = $dirName.'/'.$addonsName;
        $addonUrl = $urlDir.'/'.$addonsName;

        $adminHooks = array();
        if (isset(self::$addonsObj[$addonsName])) {
            $obj = self::$addonsObj[$addonsName];
        } else {
            $filename = $path.'/'.$addonsName.'Addons.class.php';
            tsload($filename);
            $className = $addonsName.'Addons';
            if (!class_exists($className)) {
                die('不存在该类');
            }
            $obj = new $className();
            $obj->setPath($path);
            $obj->setUrl($addonUrl);
            self::$addonsObj[$addonsName] = $addonsName;
        }
        $simple = $obj instanceof SimpleAddons;

        $adminHooks = $obj->adminMenu();
        if (!$admin && isset($adminHooks[$name])) {
            throw new ThinkException('非法操作，该操作只允许管理员操作');
        }

        if ($simple) {
            $obj->$name($param);
        } else {
            $list = self::$validHooks[$name];
            foreach ($list[$addonsName] as $hooks) {
                if (isset(self::$hooksObj[$hooks])) {
                    self::$hooksObj[$hooks]->$name($param);
                } else {
                    $filename = $dirName.'/'.$addonsName.'/hooks/'.$hooks.'.class.php';
                    tsload($filename);
                    $tempObj = new $hooks();
                    self::$hooksObj[$hooks] = $tempObj;
                    $tempObj->setPath($path);
                    $tempObj->setPath($obj->getUrl(), true);
                    $tempObj->$name($param);
                }
            }   // foreach
        }
    }

    /**
     * 加载所有有效的插件
     */
    public static function loadAllValidAddons()
    {
        // self::$validHooks = S('system_addons_list');
        if (empty(self::$validHooks)) {
            self::$validHooks = model('Addon')->resetAddonCache(true);
            //dump(self::$validHooks);
        }
    }

    /**
     * 是否能请求钩子操作
     * @param  string $hookname 钩子名称
     * @param  object $addon    钩子对象
     * @return bool   是否能请求钩子操作
     */
    public static function requireHooks($hookname, $addon = null)
    {
        if (empty($addon)) {
            return isset(self::$validHooks[$hookname]);
        }
    }

    /**
     * 用于生成插件后台管理页面的URL
     * @param string $page 管理页面或操作
     * @param  array  $param 相关参数
     * @return string 插件后台管理页面的URL
     */
    public static function adminPage($page, $param = null)
    {
        return U('admin/Addons/admin', array('pluginid' => intval($_GET['pluginid']), 'page' => $page) + (array) $param);
    }

    /**
     * [adminUrl description]
     * @param  string $page
     * @param  array  $param
     * @return [type] [description]
     */
    public static function adminUrl($page, $param = null)
    {
        return U('admin/Addons/doAdmin', array('pluginid' => intval($_GET['pluginid']), 'page' => $page) + (array) $param);
    }

    /**
     * [createAddonUrl description]
     * @param  [type] $name  [description]
     * @param  [type] $hooks [description]
     * @param  [type] $param [description]
     * @return [type] [description]
     */
    public static function createAddonUrl($name, $hooks, $param = null)
    {
        $param['addon'] = $name;
        $param['hook'] = $hooks;

        return U('public/Widget/addonsRequest', $param);
    }

    /**
     * createAddonShow
     * 为插件的展示页快速创建一个链接
     * @param mixed $name
     * @param mixed $hooks
     * @param mixed $param
     * @static
     */
    public static function createAddonShow($name, $hooks, $param = null)
    {
        $param['addon'] = $name;
        $param['hook'] = $hooks;

        return U('public/Widget/displayAddons', $param);
    }
}
