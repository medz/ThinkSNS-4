<?php

namespace Ts\Bases;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * 数据模型基类
 *
 * @package Ts\Base\Model
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
abstract class Model extends Eloquent
{
    public $timestamps = false;
} // END abstract class Model extends Eloquent
