<?php

namespace Ts\Models;

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
} // END class User extends Model
