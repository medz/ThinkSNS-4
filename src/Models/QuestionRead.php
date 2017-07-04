<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题模型.
 **/
class QuestionRead extends Model
{
    protected $table = 'question_read';

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
} // END class Tag extends Model
