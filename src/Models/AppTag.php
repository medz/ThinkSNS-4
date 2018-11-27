<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 应用标签关联.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class AppTag extends Model
{
    protected $table = 'app_tag';

    protected $softDelete = false;

    public function scopeByApp($query, $appName)
    {
        return $query->where('app', '=', $appName);
    }

    public function scopeByTable($query, $tableName)
    {
        return $query->where('table', '=', $tableName);
    }

    public function scopeByRowId($query, $rowId)
    {
        return $query->where('row_id', '=', $rowId);
    }

    public function scopeByTagId($query, $tagId)
    {
        return $query->where('tag_id', '=', $tagId);
    }

    public function tag()
    {
        return $this->hasOne('Ts\\Models\\Tag', 'tag_id', 'tag_id');
    }
} // END class AppTag extends Model
