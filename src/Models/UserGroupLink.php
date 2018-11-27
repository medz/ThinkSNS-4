<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 用户用户组关系模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class UserGroupLink extends Model
{
    protected $table = 'user_group_link';

    protected $primaryKey = 'id';

    protected $softDelete = false;

    protected $fillable = array('uid', 'user_group_id');

    /**
     * 用户组关系字段.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-22T11:12:55+0800
     * @homepage http://medz.cn
     */
    public function info()
    {
        return $this->belongsTo('Ts\\Models\\UserGroup', 'user_group_id', 'user_group_id');
    }
} // END class UserGroupLink extends Model
