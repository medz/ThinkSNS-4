<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 频道数据模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class Channel extends Model
{
    /**
     * 表名称.
     *
     * @var string
     */
    protected $table = 'channel';

    /**
     * 表主见
     *
     * @var string
     */
    protected $primaryKey = 'feed_channel_link_id';

    /**
     * 表字段.
     *
     * @var array
     */
    protected $fillable = array('feed_channel_link_id', 'feed_id', 'channel_category_id', 'status', 'width', 'height', 'uid');

    /**
     * 设置是否开启软删除.
     *
     * @var bool
     */
    protected $softDelete = false;

    /**
     * 数据分类信息.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-17T14:50:09+0800
     * @homepage http://medz.cn
     */
    public function cate()
    {
        return $this->belongsTo('Ts\\Models\\ChannelFollow', 'channel_category_id');
    }
} // END class Channel extends Model
