<?php

namespace Ts\Models;

use Medz\Component\EmojiFormat;
use Ts\Bases\Model;

/**
 * 分享数据模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class FeedData extends Model
{
    protected $table = 'feed_data';

    protected $primaryKey = 'feed_id';

    public function setFeedDataAttribute($value)
    {
        if (!is_string($value)) {
            $value = serialize($value);
        }
        $value = EmojiFormat::en($value);
        $this->attributes['feed_data'] = $value;
    }

    public function getFeedDataAttribute($value)
    {
        return EmojiFormat::de($value);
    }

    public function setFeedContentAttribute($value)
    {
        $this->attributes['feed_content'] = EmojiFormat::en($value);
    }

    public function getFeedContentAttribute($value)
    {
        return EmojiFormat::de($value);
    }

    public function getFeedDataObjectAttribute()
    {
        return (object) unserialize(EmojiFormat::en($this->feed_data));
    }
} // END class FeedData extends Model
