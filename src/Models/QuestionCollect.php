<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题模型.
 *
 **/
class QuestionCollect extends Model
{
    protected $table = 'question_collect';

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

    public function question()
    {
        return $this->belongsTo('Ts\\Models\Question', 'row_id', 'question_id')
            ->select('question_id', 'title');
    }

    public function answer()
    {
        return $this->belongsTo('Ts\\Models\QuestionAnswer', 'row_id', 'answer_id')
            ->select('answer_id', 'question_id', 'content', 'is_adopt', 'rtime', 'uid', 'reply_count')
            ->with('question')
            ->with('user');
    }

    public function user()
    {
        return $this
            ->belongsTo('Ts\\Models\User', 'uid', 'uid')
            ->select('uid', 'uname');
    }
} // END class Tag extends Model
