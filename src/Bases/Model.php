<?php

namespace Ts\Bases;

use Illuminate\Database\Eloquent\Model as Eloquent;
use Medz\Component\EmojiFormat;

/**
 * 数据模型基类.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
abstract class Model extends Eloquent
{
    public $timestamps = false;

    protected static function enEmoji($data)
    {
        return EmojiFormat::en($data);
    }

    protected static function deEmoji($data)
    {
        return EmojiFormat::de($data);
    }
} // END abstract class Model extends Eloquent
