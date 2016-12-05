<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 登陆记录.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class Login extends Model
{
    protected $table = 'login';

    protected $primaryKey = 'login_id';

    public function scopeByType($query, $type)
    {
        return $query->where('type', '=', $type);
    }

    public function scopeByVendorId($query, $id)
    {
        return $query->where('type_uid', '=', $id);
    }
} // END class Login extends Model
