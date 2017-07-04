<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 问题标签关联.
 *
 **/
class QuestionTagLink extends Model
{
    protected $table = 'question_tag_link';

    protected $primaryKey = 'id';

    protected $softDelete = false;

    protected $fillable = array('question_id', 'tag_id');

    protected $appends = array('tag');

    public function getTagAttribute()
    {
        return $this->belongsTo('Ts\\Models\\QuestionTag', 'tag_id', 'tag_id')
            ->select('tag_id', 'tag_name', 'type');
    }

    public function info()
    {
        return $this->belongsTo('Ts\\Models\\QuestionTag', 'tag_id', 'tag_id')
            ->select('tag_id', 'tag_name', 'type');
    }
} // END class AppTag extends Model
