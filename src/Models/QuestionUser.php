<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题模型.
 *
 **/
class QuestionUser extends Model
{
    protected $table = 'question_user';

    protected $softDelete = false;

    public function scopeByUid($query, $uid)
    {
        return $query->where('uid', '=', $uid);
    }

    public function user()
    {
        return $this->belongsTo('Ts\\Models\User', 'uid', 'uid')
            ->select('uid', 'uname');
    }

} // END class Tag extends Model
