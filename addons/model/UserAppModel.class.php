<?php
/**
 * 用户应用关联模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class UserAppModel extends Model
{
    protected $tableName = 'user_app';
    protected $fields = array(0 => 'user_app_id', 1 => 'app_id', 2 => 'uid', 3 => 'display_order', 4 => 'ctime', 5 => 'type', 6 => 'oauth_token', 7 => 'oauth_token_secret', 8 => 'inweb');

    /**
     * 获取用户可用的应用列表
     * @param  int   $uid   用户UID
     * @param  int   $inweb 是否是Web端，默认为1
     * @return array 用户可用的应用列表数据
     */
    public function getUserApp($uid, $inweb = 1)
    {
        // 默认应用
        if ($appList = static_cache('userApp_uapp_'.$uid.'_'.$inweb)) {
            return $appList;
        }

        if (($appList = model('Cache')->get('userApp_uapp_'.$uid.'_'.$inweb)) === false) {
            $appList = array();
            //$return = model('App')->getDefaultApp();
            $imap['a.uid'] = $uid;
            $imap['a.inweb'] = intval($inweb);
            $imap['b.status'] = 1;
            $table = $this->tablePrefix.'user_app AS a LEFT JOIN '.$this->tablePrefix.'app AS b ON a.app_id = b.app_id';
            if ($list = $this->table($table)->where($imap)->field('a.app_id')->order('a.display_order ASC')->getAsFieldArray('app_id')) {
                foreach ($list as $v) {
                    $appList[] = model('App')->getAppById($v);
                }
            }
/*			if(!empty($return)){
                $appList = empty($appList) ? $return :array_merge($return,$appList);
            }*/
            model('Cache')->set('userApp_uapp_'.$uid.'_'.$inweb, $appList, 120);
        }

        static_cache('userApp_uapp_'.$uid.'_'.$inweb, $appList);

        return $appList;
    }

    /**
     * 获取指定用户所安装的应用ID数组
     * @param int $uid   用户UID
     * @param int $inweb 是否是Web端，默认为1
     * @return array 指定用户安装的应用ID数组
     */
    public function getUserAppIds($uid, $inweb = 1)
    {
        if (empty($uid)) {
            $this->error = L('PUBLIC_USER_EMPTY');            // 用户名不能为空
            return false;
        }
        $list = $this->getUserApp($uid, $inweb);
        $r = array();
        foreach ($list as $v) {
            $r[] = $v['app_id'];
        }

        return $r;
    }

    /**
     * 获取一个指定应用的使用情况
     * @param int $appId 应用ID
     * @return array 指定应用的使用情况
     */
    public function getUsed($appId)
    {
        if (($used = model('Cache')->get('AppUsed_'.$appId)) === false) {
            $map['app_id'] = $appId;
            $used = $this->where($map)->field('COUNT(DISTINCT uid) AS `count`')->find();
            $used = intval($used['count']);
            model('Cache')->set('AppUsed_'.$appId, $used);
        }

        return $used;
    }

    /**
     * 清除指定应用使用情况的缓存
     * @param int $appId 应用ID
     */
    public function cleanUsed($appId)
    {
        model('Cache')->rm('AppUsed_'.$appId);
    }

    /**
     * 指定用户卸载指定应用
     * @param  int  $uid   用户UID
     * @param  int  $appId 应用ID
     * @param  int  $inweb 是否是Web端，默认为1
     * @return bool 是否卸载成功
     */
    public function uninstall($uid, $appId, $inweb = 1)
    {
        if (empty($uid) || empty($appId)) {
            $this->error = L('PUBLIC_WRONG_DATA');            // 错误的参数
            return false;
        }
        // 验证用户是否已经安装了该应用
        $inweb = intval($inweb);
        $uid = intval($uid);
        $appId = intval($appId);
        $ids = $this->getUserAppIds($uid, $inweb);
        if (!in_array($appId, $ids)) {
            $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR');        // 操作失败
            return false;
        }
        $map['uid'] = $uid;
        $map['app_id'] = $appId;
        $map['inweb'] = $inweb;
        $this->updateUserApp($uid, $appId, false);
        if ($this->where($map)->limit(1)->delete()) {
            return true;
        } else {
            $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR');        // 操作失败
            return false;
        }
    }

    /**
     * 指定用户安装指定应用
     * @param int $uid   用户UID
     * @param int $appId 应用ID
     * @param int $inweb 是否是Web端，默认为1
     * @return bool 是否安装成功
     */
    public function install($uid, $appId, $inweb = 1)
    {
        if (empty($uid) || empty($appId)) {
            $this->error = L('PUBLIC_WRONG_DATA');            // 错误的参数
            return false;
        }
        // 验证用户是否已经安装了该应用
        $inweb = intval($inweb);
        $uid = intval($uid);
        $appId = intval($appId);
        $ids = $this->getUserAppIds($uid, $inweb);
        if (in_array($appId, $ids)) {
            $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR');        // 操作失败
            return false;
        }
        $map['uid'] = $uid;
        $map['app_id'] = $appId;
        $map['ctime'] = time();
        $map['inweb'] = $inweb;
        $map['display_order'] = 255;
        if ($this->add($map)) {
            $this->updateUserApp($uid, $appId);

            return true;
        } else {
            $this->error = L('PUBLIC_ADMIN_OPRETING_ERROR');        // 操作失败
            return false;
        }
    }

    /**
     * 更新用户安装/卸载应用的缓存信息
     * @param int $uid   用户UID
     * @param int $appId 应用ID
     * @param  bool $install 是否是安装信息，默认为true
     * @return bool 是否更新成功
     */
    public function updateUserApp($uid, $appId, $install = true)
    {
        if (empty($appId) || empty($uid)) {
            $this->error = L('PUBLIC_WRONG_DATA');            // 错误的参数
            return false;
        }
        $this->cleanUsed($appId);
        $this->cleanCache($uid);

        return true;
    }

    /**
     * 清除指定用户的应用信息缓存
     * @param int $uids 用户UID
     * @return bool 是否清除成功
     */
    public function cleanCache($uids)
    {
        !is_array($uids) && $uids = explode(',', $uids);
        foreach ($uids as $uid) {
            model('Cache')->rm('userApp_uapp_'.$uid.'_0');
            model('Cache')->rm('userApp_uapp_'.$uid.'_1');
        }

        return true;
    }
}
