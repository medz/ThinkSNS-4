<?php

namespace Ts\AutoLoader;

use Ts;

/**
 * Ts核心自动加载.
 *
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class TsAutoLoader
{
    /**
     * 自动加载�
     * �口.
     *
     * @param string $namespace 命名空间
     *
     * @return bool
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public static function entry($namespace)
    {
        $namespace = str_replace('/', Ts::DS, $namespace);
        $namespace = str_replace('\\', Ts::DS, $namespace);
        $namespace = explode(Ts::DS, $namespace);
        switch ($namespace[0]) {
            case 'Ts':
            default:
                $namespace = self::autoLoader($namespace);
                break;
        }

        return call_user_func_array('Ts::import', $namespace);
    }

    /**
     * Ts自身文件加载.
     *
     * @param array $namespace 切割成数组的命名空间
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public static function autoLoader(array $namespace)
    {
        if ($namespace[0] == 'Ts') {
            unset($namespace[0]);
            array_unshift($namespace, Ts::getRootPath());
            array_push($namespace, '.php');

            return $namespace;
        }

        return self::TsOldAutoLoader($namespace);
    }

    /**
     * 原有Ts的命名空间加载.
     *
     * @param array $namespace 切割成数组的命名空间
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public static function TsOldAutoLoader(array $namespace)
    {
        $newArr = array();
        $ext = '.class.php';
        foreach ($namespace as $key => $value) {
            if ($value == 'Apps') {
                $value = 'apps';
            } elseif (in_array($value, array('Controller', 'Model'))) {
                if ($value == 'Controller') {
                    $value = 'Action';
                    $ext = 'Action.class.php';
                } elseif ($value == 'Model') {
                    $value = 'Model';
                    $ext = 'Model.class.php';
                }
                array_push($newArr, 'Lib');
            }
            array_push($newArr, $value);
        }
        $namespace = $newArr;
        unset($newArr);
        array_unshift($namespace, TS_ROOT);
        array_push($namespace, $ext);

        return $namespace;
    }
} // END class TsAutoLoader
