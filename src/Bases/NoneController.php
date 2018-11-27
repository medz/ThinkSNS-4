<?php

namespace Ts\Bases;

/**
 * 空控制器.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
abstract class NoneController extends Controller
{
    /**
     * 不存在控制器的运行方法.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    abstract protected function run();

    /**
     * 实例化后的执行.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    protected function classConstructAfter()
    {
        $this->run();
    }
} // END abstract class NoneController extends Controller
