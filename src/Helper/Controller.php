<?php

namespace Ts\Helper;

use Exception;

/**
 * 控制器帮助类.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class Controller
{
    /**
     * 定义控制器中动作的后缀
     *
     * @var string
     **/
    const ACTION_SUFFIX = 'Action';

    /**
     * 运行的应用名称.
     *
     * @var string
     **/
    protected $appName;

    /**
     * 运行的控制器名称.
     *
     * @var string
     **/
    protected $controllerName;

    /**
     * 运行的控制器动作名称.
     *
     * @var string
     **/
    protected $appAction;

    /**
     * 运行是缓存的参数.
     *
     * @var string
     **/
    protected $appParams = array();

    /**
     * 控制器缓存.
     *
     * @var array
     **/
    protected static $controllers = array();

    /**
     * 需要兼容的控制器命名空间.
     *
     * @var array
     **/
    protected static $controllerClass = array(
        'Ts-2016' => 'App\\%s\\Controller\\%s',
        'Ts-2015' => 'Apps\\%s\\Controller\\%s',
        'Ts-old'  => '%sAction',
    );

    /**
     * 构建需要的对象
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    protected function build($oldControllerName = false)
    {
        $className = null;
        foreach (self::$controllerClass as $key => $value) {
            if (
                $key == 'Ts-2016' &&
                class_exists($className = sprintf($value, ucfirst($this->appName), ucfirst($this->controllerName)))
            ) {
                $this->setAction(sprintf('%s%s', $this->appAction, self::ACTION_SUFFIX));
                break;
            } elseif (
                $key == 'Ts-2015' &&
                class_exists($className = sprintf($value, $this->appName, ucfirst($this->controllerName)))
            ) {
                break;
            } elseif (
                $key == 'Ts-old' &&
                class_exists($className = sprintf($value, ucfirst($this->controllerName)))
            ) {
                break;
            }
        }

        /* 缓存中存在直接返回 */
        if (
            isset(static::$controllers[$className]) &&
            static::$controllers[$className] instanceof $className
        ) {
            return sttaic::$controllers[$className];

        /* 如果不存在，就判断 None类 */
        } elseif (
            class_exists($className) === false &&
            $oldControllerName === false
        ) {
            $className = $this->controllerName;
            $this->setController('None');

            return $this->build($className);

        /* 兼容旧系统的emptyAction */
        } elseif (
            class_exists($className) === false &&
            $oldControllerName !== false &&
            $this->controllerName != 'empty'
        ) {
            $this->setController('empty');

            return $this->build($oldControllerName);

        /* 抛出异常 */
        } elseif (
            class_exists($className) === false
        ) {
            throw new Exception(sprintf('%s:“%s”', L('_MODULE_NOT_EXIST_'), $oldControllerName), 1);
        }

        return self::$controllers[$className] = new $className();
    }

    /**
     * 设置运行的应用.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function setApp($appName)
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * 设置控制器名称.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function setController($controllerName)
    {
        $this->controllerName = $controllerName;

        return $this;
    }

    /**
     * 设置运行的控制器执行的动作.
     *
     * @return self
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function setAction($actionName)
    {
        $this->appAction = $actionName;

        return $this;
    }

    /**
     * 设置应用运行注入的参数.
     *
     * @return self
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function setParams($params)
    {
        $this->appParams = (array) $params;

        return $this;
    }

    /**
     * 运行.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function run()
    {
        $GLOBALS['time_run_detail']['action_instance'] = microtime(true); // 旧系统的时间记录

        return call_user_func_array(
            array(
                $this->build(),
                $this->appAction,
            ),
            $this->appParams
        );
    }
} // END class Controller
