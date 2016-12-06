<?php

namespace Ts\Helper\AutoJumper;

use ArrayIterator;
use Closure;

/**
 * ThinkSNS v4 Auto URL jump Conponent.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class Jumper
{
    protected static $iterator;

    /**
     * Run jump.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public static function start()
    {
        static::registerArrayIterator();
        require __DIR__.'/rule.php';

        $url = false;

        while (static::$iterator->valid()) {
            $url = call_user_func_array(static::$iterator->current(), array(APP_NAME, MODULE_NAME, ACTION_NAME, (array) $_REQUEST));

            if ($url === true) {
                return null;
            } elseif ($url != false) {
                break;
            }

            static::$iterator->next();
        }

        if ($url != false) {
            redirect($url, 0, '系统即将跳转到h5页面');
        }
    }

    /**
     * add state machine.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public static function add(Closure $matchHandle)
    {
        static::registerArrayIterator();
        static::$iterator->append($matchHandle);
    }

    protected static function registerArrayIterator()
    {
        if (!static::$iterator instanceof ArrayIterator) {
            static::$iterator = new ArrayIterator();
        }
    }
} // END class Jumper
