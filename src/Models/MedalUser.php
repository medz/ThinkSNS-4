<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 用户勋章�
 * �联模型.
 */
class MedalUser extends Model
{
    protected $table = 'medal_user';

    protected $primaryKey = 'id';

    protected $softDelete = false;

    public function medal()
    {
        return $this->hasOne('Ts\\Models\\Medal', 'id', 'medal_id');
    }
}
