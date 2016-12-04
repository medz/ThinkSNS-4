<?php
/**
 * 黑名单模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class UserBlacklistModel extends Model
{
    protected $tableName = 'user_blacklist';
    protected $fields = array(0 => 'uid', 1 => 'fid', 2 => 'ctime');
    public static $blackHash = array();

    /**
     * 获取指定用户的黑名单列表
     * @param  int   $uid 用户UID
     * @return array 指定用户的黑名单列表
     */
    public function getUserBlackList($uid)
    {
        $uid = intval($uid);
        if (empty($uid)) {
            $this->error = '用户ID不能为空';
        }
        if (isset(self::$blackHash[$uid])) {
            return self::$blackHash[$uid];
        }
        if (($list = model('Cache')->get('u_blacklist_'.$uid)) == false) {
            $map['uid'] = $uid;
            $list = $this->where($map)->getHashList('fid');
            model('Cache')->set('u_blacklist_'.$uid, $list);
        }
        self::$blackHash[$uid] = $list;

        return $list;
    }

    /**
     * 指定用户添加黑名单
     * @param  int  $uid 指定用户UID
     * @param  int  $fid 黑名单用户UID
     * @return bool 是否添加成功
     */
    public function addUser($uid, $fid)
    {
        $uid = intval($uid);
        $fid = intval($fid);
        if (empty($uid) || empty($fid)) {
            $this->error = '用户ID不能为空';

            return false;
        }
        $blackList = $this->getUserBlackList($uid);
        if (isset($blackList[$fid])) {
            $this->error = '用户已经在黑名单中了';

            return false;
        }
        $blackList[$fid] = array('uid' => $uid, 'fid' => $fid, 'ctime' => time());
        if ($this->add($blackList[$fid])) {
            model('Follow')->unFollow($uid, $fid);
            model('Follow')->unFollow($fid, $uid);
            model('Cache')->set('u_blacklist_'.$uid, $blackList);

            return true;
        }

        return false;
    }

    /**
     * 指定用户取消黑名单
     * @param  int  $uid 指定用户UID
     * @param  int  $fid 黑名单用户UID
     * @return bool 是否移除成功
     */
    public function removeUser($uid, $fid)
    {
        $uid = intval($uid);
        $fid = intval($fid);
        if (empty($uid) || empty($fid)) {
            $this->error = '用户ID不能为空';

            return false;
        }
        $blackList = $this->getUserBlackList($uid);
        if (!isset($blackList[$fid])) {
            $this->error = '用户不在黑名单中了';

            return false;
        }
        unset($blackList[$fid]);
        $map['uid'] = $uid;
        $map['fid'] = $fid;
        if ($this->where($map)->limit(1)->delete()) {
            model('Cache')->set('u_blacklist_'.$uid, $blackList);

            return true;
        }

        return false;
    }

    /**
     * 清除用户的黑名单缓存信息
     * @param array $uids 用户UID数组
     * @return bool  缓存是否清除成功
     */
    public function cleanCache($uids)
    {
        $uids = is_array($uids) ? $uids : explode(',', $uids);
        $cache = model('Cache');
        foreach ($uids as $uid) {
            $cache->rm('u_blacklist_'.$uid);
        }

        return true;
    }
}
