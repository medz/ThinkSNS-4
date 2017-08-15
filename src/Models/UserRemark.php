<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 用户�
 * �注数据模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class UserRemark extends Model
{
    protected $table = 'user_remark';

    protected $primaryKey = 'remark_id';

    protected $softDelete = false;
} // END class UserFollow extends Model
