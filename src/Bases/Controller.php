<?php

namespace Ts\Bases;

use Action as BaseController;

/**
 * undocumented class.
 *
 * @author
 **/
abstract class Controller extends BaseController
{
    /**
     * 构造方法.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    final public function __construct()
    {
        $notInitOld = null;

        /* class 构造之前 */
        method_exists($this, 'classConstructBefore') &&
        $notInitOld = $this->classConstructBefore();

        if (
            $notInitOld === null ||
            $notInitOld === false
        ) {
            parent::__construct();
        }

        /* calss 构造之后 */
        method_exists($this, 'classConstructAfter') &&
        $this->classConstructAfter();
    }

    /**
     * 析构方法.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    final public function __destruct()
    {
        /* 执行析构方法 */
        method_exists($this, 'classDestruct') &&
        $this->classDestruct();
    }

    /**
     * 重载方法.
     *
     * @param string $method 方法名称
     * @param array  $params 方法参数
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    final public function __call($method, array $params = array())
    {
        /* class 重载之前 */
        $notCallOld = null;
        method_exists($this, 'classCallBefore') &&
        $notCallOld = $this->classCallBefore($method, $params);

        if (
            $notCallOld === null ||
            $notCallOld === false
        ) {
            parent::__call($method, $params);
        }

        /* class 重载之后 */
        method_exists($this, 'classCallAfter') &&
        $this->classCallAfter($method, $params);
    }
} // END abstract class Controller extends Controller
