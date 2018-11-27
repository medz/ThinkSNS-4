<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 用户关注数据模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class UserFollow extends Model
{
    protected $table = 'user_follow';

    protected $primaryKey = 'follow_id';

    protected $softDelete = false;

    public function byFollower()
    {
        return $this->belongsTo('Ts\\Models\\User', 'fid', 'uid');
    }

    public function byFollowing()
    {
        return $this->belongsTo('Ts\\Models\\User', 'uid', 'uid');
    }
} // END class UserFollow extends Model
