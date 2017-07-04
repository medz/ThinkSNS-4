<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题标签模型.
 *
 **/
class QuestionTag extends Model
{
    protected $table = 'question_tag';

    protected $primaryKey = 'tag_id';

    protected $softDelete = false;

    public function scopeByIsDel($query, $is_del = '0')
    {
        return $query->where('is_del', '=', $is_del);
    }
} // END class Tag extends Model
