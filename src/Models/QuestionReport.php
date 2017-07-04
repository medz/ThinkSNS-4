<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题模型.
 **/
class QuestionReport extends Model
{
    protected $table = 'question_report';

    protected $primaryKey = 'id';

    protected $softDelete = false;

    public function scopeById($query, $id)
    {
        return $query->where('id', '=', $id);
    }

    public function scopeByUid($query, $uid)
    {
        return $query->where('uid', '=', $uid);
    }

    public function scopeByRowId($query, $row_id)
    {
        return $query->where('row_id', '=', $row_id);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', '=', $type);
    }

    public function scopeByIsDel($query, $is_del = 0)
    {
        return $query->where('is_del', '=', $is_del);
    }

    public function setReasonAttribute($reason)
    {
        $this->attributes['reason'] = addslashes(h($reason));
    }

    public function getReasonAttribute($reason)
    {
        return stripslashes($reason);
    }
} // END class Tag extends Model
