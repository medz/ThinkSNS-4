<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 频道粉丝模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class ChannelFollow extends Model
{
    /**
     * 表名称.
     *
     * @var string
     */
    protected $table = 'channel_follow';

    /**
     * 表主键.
     *
     * @var string
     */
    protected $primaryKey = 'channel_category_id';

    /**
     * 表字段.
     *
     * @var array
     */
    protected $fillable = array('channel_follow_id', 'uid', 'channel_category_id');

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
        return $this->belongsTo('Ts\\Models\\ChannelCategory', 'channel_category_id');
    }
} // END class ChannelFollow extends Model
