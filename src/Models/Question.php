<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题模型.
 *
 **/
class Question extends Model
{
    protected $table = 'question';

    protected $primaryKey = 'question_id';

    protected $softDelete = false;

    /**
     * 复用的存在用户范围.
     *
     * @param $query
     * @return mixed
     */
    public function scopeExistent($query)
    {
        return $query->where('is_del', '=', 0);
    }

    public function scopeByQuestionId($query, $question_id)
    {
        return $query->where('question_id', '=', $question_id);
    }

    public function scopeByUid($query, $uid)
    {
        return $query->where('uid', '=', $uid);
    }

    public function scopeByTitle($query, $title)
    {
        return $query->where('title', '=', $title);
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
        return stripslashes($content);
    }

    public function tags()
    {
        return $this
            ->hasMany('Ts\\Models\\QuestionTagLink', 'question_id', 'question_id');
    }

    public function answers()
    {
        return $this
            ->hasMany('Ts\\Models\QuestionAnswer', 'question_id', 'question_id');
    }

    public function user()
    {
        return $this
            ->belongsTo('Ts\\Models\User', 'uid', 'uid')
            ->select('uid', 'uname');
    }

} // END class Tag extends Model
