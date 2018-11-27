<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 敏感词
 *
 * Created by PhpStorm.
 * Author: Zuo
 * Since: 2017/8/25 11:21
 */
class SensitiveWord extends Model
{
    protected $table = 'sensitive_word';

    protected $primaryKey = 'sensitive_word_id';
} // END class SensitiveWord extends Model
