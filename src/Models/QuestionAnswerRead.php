<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题模型.
 **/
class QuestionAnswerRead extends Model
{
    protected $table = 'question_answer_read';

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

    public function scopeByAnswerId($query, $answer_id)
    {
        return $query->where('answer_id', '=', $answer_id);
    }

    public function scopeByIsRead($query, $is_read = 0)
    {
        return $query->where('is_read', '=', $is_read);
    }

    public function answer()
    {
        return $this
            ->belongsTo('Ts\\Models\QuestionAnswer', 'answer_id', 'answer_id')
            ->select('answer_id', 'question_id', 'content', 'is_adopt', 'rtime', 'uid', 'reply_count')
            ->with('question')
            ->with('user');
    }
} // END class Tag extends Model
