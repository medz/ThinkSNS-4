<?php

namespace Ts\Models;

use Ts\Bases\Model;

/**
 * 频道分类数据模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class ChannelCategory extends Model
{
    /**
     * 表名称.
     *
     * @var string
     */
    protected $table = 'channel_category';

    /**
     * 表主见
     *
     * @var string
     */
    protected $primaryKey = 'channel_category_id';

    /**
     * 表字段.
     *
     * @var array
     */
    protected $fillable = array('channel_category_id', 'title', 'pid', 'sort', 'ext');

    /**
     * 设置是否开启软删除.
     *
     * @var bool
     */
    protected $softDelete = false;

    /**
     * 频道下的数据.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-17T14:56:57+0800
     * @homepage http://medz.cn
     */
    public function channels()
    {
        return $this->hasMany('Ts\\Models\\Channel', 'channel_category_id');
    }

    /**
     * 频道粉丝数据.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-17T16:00:35+0800
     * @homepage http://medz.cn
     */
    public function follows()
    {
        return $this->hasMany('Ts\\Models\\ChannelFollow', 'channel_category_id');
    }
} // END class ChannelCategory extends Model
