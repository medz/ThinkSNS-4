<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题模型.
 *
 **/
class QuestionAnswer extends Model
{
    protected $table = 'question_answer';

    protected $primaryKey = 'answer_id';

    protected $softDelete = false;

    public function scopeByAnswerId($query, $answer_id)
    {
        return $query->where('answer_id', '=', $answer_id);
    }

    public function scopeByQuestionId($query, $question_id)
    {
        return $query->where('question_id', '=', $question_id);
    }

    public function scopeByUid($query, $uid)
    {
        return $query->where('uid', '=', $uid);
    }

    public function scopeByIsAdopt($query, $is_adopt)
    {
        return $query->where('is_adopt', '=', $is_adopt);
    }

    public function scopeByIsDel($query, $is_del = 0)
    {
        return $query->where('is_del', '=', $is_del);
    }

    public function setContentAttribute($content)
    {
        $this->attributes['content'] = addslashes(h($content));
    }

    public function getContentAttribute($content)
    {
        return htmlspecialchars_decode(stripslashes($content), ENT_QUOTES);
    }

    public function question()
    {
        return $this
            ->belongsTo('Ts\\Models\\Question', 'question_id', 'question_id');
    }

    public function user()
    {
        return $this
            ->belongsTo('Ts\\Models\\User', 'uid', 'uid')
            ->select('uid', 'uname')
            ->with('tags');
    }

    public function replyCount()
    {
        return $this
            ->hasMany('Ts\\Models\Comment', 'row_id', 'answer_id')
            ->byApp('wenda')
            ->byTable('question_answer')
            ->byIsAudit('1')
            ->byIsDel('0')
            ->select('row_id');
    }

    public function collect()
    {
        return $this->hasOne('Ts\\Models\QuestionCollect', 'row_id', 'answer_id');
    }

    public function collectStatus($uid)
    {
        return (bool) $this->collect()
            ->ByUid($uid)
            ->ByType(2)
            ->count(array('id'));
    }

} // END class Tag extends Model
