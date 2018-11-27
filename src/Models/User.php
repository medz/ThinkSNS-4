<?php

namespace Ts\Models;

use AvatarModel as Avatar;
use CreditModel as OldTsCreditModel;
use Ts\Bases\Model;

/**
 * 用户数据模型.
 *
 * @author Seven Du <lovevipdsw@outlook.com>
 **/
class User extends Model
{
    protected $table = 'user';

    protected $primaryKey = 'uid';

    protected $softDelete = false;

    protected $hidden = array('password', 'login_salt');

    protected $appends = array('face', 'credit');

    protected static $instances = array();

    /**
     * 复用的存在用户范围.
     *
     * @param Illuminate\Database\Eloquent\Builder $query 查询器
     *
     * @return Illuminate\Database\Eloquent\Builder 查询器
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-04-15T23:31:40+0800
     * @homepage http://medz.cn
     */
    public function scopeExistent($query)
    {
        return $query->where('is_del', '=', 0);
    }

    /**
     * 复用的以审核通过的用户范围.
     *
     * @param Illuminate\Database\Eloquent\Builder $query 查询器
     *
     * @return Illuminate\Database\Eloquent\Builder 查询器
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-04-15T23:33:11+0800
     * @homepage http://medz.cn
     */
    public function scopeAudit($query)
    {
        return $query->where('is_audit', '=', 1);
    }

    public function scopeByPhone($query, $phone)
    {
        return $query->where('phone', '=', $phone);
    }

    public function scopeByUserName($query, $username)
    {
        $username = self::enEmoji($username);

        return $query->where('uname', '=', $username);
    }

    public function scopeByUid($query, $uid)
    {
        return $query->where('uid', '=', intval($uid));
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', '=', $email);
    }

    public function setUnameAttribute($username)
    {
        $this->attributes['uname'] = self::enEmoji($username);
    }

    public function getUnameAttribute($username)
    {
        return self::deEmoji($username);
    }

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = md5(md5($password).$this->login_salt);
    }

    public function setFirstLetterAttribute($firstLetter)
    {
        $firstLetter = strtoupper(mb_substr($firstLetter, 0, 1));

        if (!preg_match('/^[a-zA-Z0-9](.*)/', $firstLetter)) {
            $firstLetter = '#';
        }

        $this->attributes['first_letter'] = $firstLetter;
    }

    public function setSearchKeyAttribute($key)
    {
        $this->attributes['search_key'] = self::enEmoji($key);
    }

    public function getSearchKeyAttribute($key)
    {
        return self::deEmoji($key);
    }

    public function setIntroAttribute($intro)
    {
        $this->attributes['intro'] = self::enEmoji($intro);
    }

    public function getIntroAttribute($intro)
    {
        return self::deEmoji($intro);
    }

    public function checkPassword($password)
    {
        return $this->password == md5(md5($password).$this->login_salt);
    }

    /**
     * 获取当前查询用户的头像.
     *
     * @return object 用户头像数据
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-21T13:22:39+0800
     * @homepage http://medz.cn
     */
    public function getFaceAttribute()
    {
        $classNamme = 'Avatar';
        if (!isset(static::$instances[$classNamme]) || !static::$instances[$classNamme] instanceof Avatar) {
            static::$instances[$classNamme] = new Avatar();
        } elseif (!$this->uid) {
            return $this;
        }

        return (object) static::$instances[$classNamme]
            ->init($this->uid)
            ->getUserAvatar();
    }

    public function getCreditAttribute()
    {
        if (!isset(static::$instances['OldTsCreditModel']) || !static::$instances['OldTsCreditModel'] instanceof OldTsCreditModel) {
            static::$instances['OldTsCreditModel'] = new OldTsCreditModel();
        }

        return static::$instances['OldTsCreditModel']->getUserCredit($this->uid);
    }

    public function getLevelImgAttribute()
    {
        return $this->credit['level']['src'];
    }

    /**
     * 用户用户组关系字段.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-22T11:15:40+0800
     * @homepage http://medz.cn
     */
    public function group()
    {
        return $this->hasMany('Ts\\Models\\UserGroupLink', 'uid', 'uid');
    }

    public function userData()
    {
        return $this->hasMany('Ts\\Models\\UserData', 'uid', 'uid');
    }

    /* 部门 */
    public function department()
    {
        return $this->hasMany('Ts\\Models\\UserDepartment', 'uid', 'uid');
    }

    /* 勋章 */
    public function medal()
    {
        return $this->hasMany('Ts\\Models\\MedalUser', 'uid', 'uid');
    }

    /* 粉丝 */
    public function followers()
    {
        return $this->hasMany('Ts\\Models\\UserFollow', 'uid', 'uid');
    }

    /* 关注的用户 */
    public function followings()
    {
        return $this->hasMany('Ts\\Models\\UserFollow', 'fid', 'uid');
    }

    public function tags()
    {
        return $this
            ->hasMany('Ts\\Models\\AppTag', 'row_id', 'uid')
            ->byApp('public')
            ->byTable('user');
    }

    /**
     * 检查用户（$uid）是否否关注了内容用户.
     *
     * @param int $uid 需要检查的用户
     *
     * @return bool
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-22T13:02:20+0800
     * @homepage http://medz.cn
     */
    public function followStatus($uid)
    {
        return $this->uid == $uid
            ?: (bool) $this->followers()
                ->where('fid', '=', $uid)
                ->count(array('follow_id'));
    }

    /**
     * 检查内容用户是否否关注了用户（$uid）.
     *
     * @param int $uid 需要检查的用户
     *
     * @return bool
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-03-22T13:02:20+0800
     * @homepage http://medz.cn
     */
    public function followIngStatus($uid)
    {
        return $this->uid == $uid
            ?: (bool) $this->followings()
                ->where('uid', '=', $uid)
                ->count(array('follow_id'));
    }

    /* 备注 */
    public function remark($uid)
    {
        return $this->hasMany('Ts\\Models\\UserRemark', 'uid', 'uid')
            ->where('mid', '=', $uid)
            ->select('remark')
            ->first()
            ->remark;
    }
} // END class User extends Model
