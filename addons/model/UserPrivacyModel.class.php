<?php
/**
 * 用户隐私模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class UserPrivacyModel extends Model
{
    protected $tableName = 'user_privacy';
    protected $fields = array(0 => 'uid', 1 => 'key', 2 => 'value');

    /**
     * 获取指定用户的隐私设置
     * @param  int   $uid 用户UID
     * @return array 指定用户的隐私设置信息
     */
    public function getUserSet($uid)
    {
        $set = $this->_defaultSet();

        $uid = intval($uid);

        $userPrivacy = $this->where('uid='.$uid)->field('`key`,`value`')->findAll();
        if ($userPrivacy) {
            foreach ($userPrivacy as $k => $v) {
                $set[$v['key']] = $v['value'];
            }
        }

        return $set;
    }

    /**
     * 保存指定用户的隐私配置
     * @param int $uid 用户UID
     * @param  array $data 隐私配置相关数据
     * @return bool 是否保存成功
     */
    public function dosave($uid, $data)
    {
        // 验证数据
        if (empty($uid)) {
            return false;
        }
        $map = array();
        $map['uid'] = $uid;
        $this->where($map)->delete();
        foreach ($data as $key => $value) {
            $key = t($key);
            $value = intval($value);
            $sql[] = "($uid,'{$key}',{$value})";
        }
        $sql = "INSERT INTO {$this->tablePrefix}user_privacy (uid,`key`,`value`) VALUES ".implode(',', $sql);
        $res = $this->query($sql);

        $this->error = L('PUBLIC_SAVE_SUCCESS');            // 保存成功

        return true;
    }

    /**
     * 获取A用户针对B用户的隐私设置情况
     * @param  int $mid B用户UID
     * @param  int $uid A用户UID
     * @return int 隐私状态，0表示不限制；1表示限制，不可以发送
     */
    public function getPrivacy($mid, $uid)
    {
        $data = $this->getUserSet($uid);
        // $mid为0表示系统
        if ($mid != $uid && $mid != 0) {
            if ($this->isInBlackList($mid, $uid)) {
                $data['comment_weibo'] = 1;
                $data['message'] = 1;
                $data['space'] = 1;
            } else {
                $followState = model('Follow')->getFollowState($uid, $mid);
                if ($data['comment_weibo'] != 0 && $followState['following'] == 1) {
                    $data['comment_weibo'] = 0;
                }
                if ($data['message'] != 0 && $followState['following'] == 1) {
                    $data['message'] = 0;
                }
                if ($data['space'] != 0 && $followState['following'] == 1) {
                    $data['space'] = 0;
                }
            }
        }

        return $data;
    }

    /**
     * 系统的默认用户隐私设置配置
     * @return array 默认隐私配置数组
     */
    private function _defaultSet()
    {
        return array(
            'comment_weibo' => 0,        // 所有人
            'message' => 0,                // 所有人
            'space' => 0,                // 所有人
            //'email' => 0,				// 接收系统邮件
            'atme_email' => 0,                // 接收系统邮件
            'comment_email' => 0,                // 接收系统邮件
            'message_email' => 0,                // 接收系统邮件

        );
    }

    /**
     * 判断用户是否是黑名单关系
     * @param  int   $mid B用户UID
     * @param  int   $uid A用户UID
     * @return array
     */
    public function isInBlackList($mid, $uid)
    {
        $uid = intval($uid);
        $mid = intval($mid);
        $result = D('user_blacklist')->where("uid=$uid AND fid=$mid")->find();

        return    $result;
    }
}
