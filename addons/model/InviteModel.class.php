<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * 邀请模型 - 数据对象模型
 * @author nonant
 * @version TS3.0
 */
class InviteModel extends Model
{
    protected $tableName = 'invite_code';            // 邀请码数据表
    private $_email_reg = '/[_a-zA-Z\d\-\.]+(@[_a-zA-Z\d\-\.]+\.[_a-zA-Z\d\-]+)+$/i';            // 邮件正则规则

    /**
     * 生成邀请码
     * @param  int         $uid     用户ID
     * @param  string      $type    邀请码类型
     * @param  int         $num     邀请码数量，默认为5
     * @param  bool        $isAdmin 是否为管理员邀请操作，默认为false
     * @return bool|string 成功返回邀请码，失败返回false
     */
    public function createInviteCode($uid, $type, $num = 5, $isAdmin = false)
    {
        $adminVal = $isAdmin ? 1 : 0;
        // 验证数据
        if (empty($uid) || empty($num) || empty($type)) {
            return false;
        }

        // 邀请码数组
        $inviteCodes = array();
        $insertDatas = array();

        for ($i = 1; $i <= $num; $i++) {
            $inviteCode = tsmd5($uid.microtime(true).rand(1111, 9999).$i);
            array_push($inviteCodes, $inviteCode);
            array_push($insertDatas, array(
                'inviter_uid' => $uid,
                'code' => $inviteCode,
                'is_used' => 0,
                'is_admin' => $isAdmin ? 1 : 0,
                'type' => $type,
                'is_received' => 0,
                'receiver_uid' => 0,
                'receiver_email' => null,
                'ctime' => time(),
            ));
        }

        if (count($insertDatas)) {
            Capsule::table('invite_code')->insert($insertDatas);

            return $inviteCodes;
        }

        return false;
    }

    /**
     * 获取指定用户的邀请码列表 - 链接邀请使用
     * @param  int    $uid  用户ID
     * @param  string $type 邀请码类型
     * @return array  指定用户的邀请码列表
     */
    public function getInviteCode($uid, $type)
    {
        $map['inviter_uid'] = $uid;
        $map['type'] = $type;
        $map['is_admin'] = 0;
        $map['is_used'] = 1;
        //已使用过列表
        $_result = $this->where($map)->findAll();
        //未使用的列表
        $map['is_used'] = 0;
        $result = $this->where($map)->findAll();

        //注册配置
        $_register_config = model('Xdata')->get('admin_Config:register');
        $registerType = $_register_config['register_type'];
        // 数据表中没有信息或者为开放注册，将初始化添加邀请码
        if (empty($result) && empty($_result) || (empty($result) && $registerType == 'open')) {
            $conf = model('Xdata')->get('admin_Config:invite');
            $this->createInviteCode($uid, $type, $conf['send_link_num']);
        }

        $list = $this->where($map)->findAll();

        return $list;
    }

    /**
     * 获取后台邀请码列表
     * @param  string $type 邀请码类型
     * @return array  后台邀请码列表
     */
    public function getAdminInviteCode($type)
    {
        $map['type'] = $type;
        $map['is_admin'] = 1;
        $map['is_used'] = 0;
        $list = $this->where($map)->findPage();

        return $list;
    }

    /**
     * 设置指定验证码已被使用
     * @param  string $code         验证码
     * @param  array  $receiverInfo 邀请人用户信息
     * @return bool   设置是否成功
     */
    public function setInviteCodeUsed($code, $receiverInfo)
    {
        $map['code'] = $code;
        $data['is_used'] = 1;
        $data['receiver_uid'] = $receiverInfo['uid'];
        $data['receiver_email'] = $receiverInfo['email'];
        $data['ctime'] = time();
        $result = $this->where($map)->save($data);

        return (boolean) $result;
    }

    /**
     * 获取指定邀请码的相关信息
     * @param string $code 邀请码
     * @return array  指定邀请码的相关信息
     */
    public function getInviteCodeInfo($code)
    {
        $map['code'] = $code;
        $result = $this->where($map)->find();

        return $result;
    }

    /**
     * 获取指定用户可用的邀请码个数
     * @param int $uid 用户ID
     * @param  string $type 邀请码类型，email或者link
     * @return int 指定用户可用的邀请码个数
     */
    public function getAvailableCodeCount($uid, $type)
    {
        $map['inviter_uid'] = $uid;
        $type == 'link' && $map['is_used'] = 0;
        $map['type'] = $type;
        $map['is_admin'] = 0;
        $count = $this->where($map)->count();
        if ($type == 'email') {
            $conf = model('Xdata')->get('admin_Config:invite');
            $count = $conf['send_email_num'] - $count;
            $count < 0 && $count = 0;
        }

        return $count;
    }

    /**
     * 检验验证码是否可用
     * @param string $code 验证码
     * @param string $type 注册类型
     * @return int    邀请码使用情况，0：邀请码不存在，1：邀请码可用，2：邀请码已被使用
     */
    public function checkInviteCode($code, $type)
    {
        $map['code'] = $code;
        $type == 'admin' && $map['is_admin'] = 1;
        $isUsed = $this->where($map)->getField('is_used');
        $result = 0;
        if (!is_null($isUsed)) {
            $result = ($isUsed === 0) ? 1 : 2;
        }

        return $result;
    }

    /**
     * 通过邀请码获取邀请人相关信息
     * @param string $code 邀请码
     * @return array  获取邀请人相关信息
     */
    public function getInviterInfoByCode($code)
    {
        if (empty($code)) {
            return array();
        }
        $inviterUid = $this->where("code='{$code}'")->getField('inviter_uid');
        $inviterInfo = model('User')->getUserInfo($inviterUid);

        return $inviterInfo;
    }

    /**
     * 获取指定用户所邀请的用户列表
     * @param  int   $uid     用户ID
     * @param  array $type    邀请类型
     * @param  bool  $isAdmin 是否为管理员操作，默认为false
     * @return array 指定用户所邀请的用户列表
     */
    public function getInviteUserList($uid, $type, $isAdmin = false)
    {
        $map['c.inviter_uid'] = $uid;
        $map['c.is_used'] = 1;
        !empty($type) && $map['c.type'] = $type;
        $map['c.is_admin'] = $isAdmin ? 1 : 0;
        $map['u.is_init'] = 1;
        $list = D()->table('`'.$this->tablePrefix.'invite_code` AS c LEFT JOIN `'.$this->tablePrefix.'user` AS u ON c.receiver_uid = u.uid')
                   ->field('c.*')
                   ->where($map)
                   ->order('invite_code_id DESC')
                   ->findPage(20);
        $uids = getSubByKey($list['data'], 'receiver_uid');
        $userInfos = model('User')->getUserInfoByUids($uids);
        foreach ($list['data'] as &$value) {
            $value = array_merge($value, $userInfos[$value['receiver_uid']]);
        }

        return $list;
    }

    /**
     * 获取指定用户所邀请的用户列表
     * @param  array $type    邀请类型
     * @param  bool  $isAdmin 是否为管理员操作，默认为false
     * @return array 指定用户所邀请的用户列表
     */
    public function getInviteAdminUserList($type)
    {
        $map['c.is_used'] = 1;
        !empty($type) && $map['c.type'] = $type;
        $map['c.is_admin'] = 1;
        $map['u.is_init'] = 1;
        $list = D()->table('`'.C('DB_PREFIX').'invite_code` AS c LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON c.receiver_uid = u.uid')
                   ->field('c.*')
                   ->where($map)
                   ->order('invite_code_id DESC')
                   ->findPage(20);
        $uids = getSubByKey($list['data'], 'receiver_uid');
        $userInfos = model('User')->getUserInfoByUids($uids);
        foreach ($list['data'] as &$value) {
            $value = array_merge($value, $userInfos[$value['receiver_uid']]);
        }

        return $list;
    }

    /**
     * 邮件邀请注册
     * @param array $email 被邀请人邮箱数组
     * @param  string $detail  邀请相关信息
     * @param  int  $uid     邀请人ID
     * @param  bool $isAdmin 是否为管理员邀请操作，默认为false
     * @return bool 是否发送邀请成功
     */
    public function doInvite($email, $detail, $uid, $isAdmin = false)
    {
        $_register_config = model('Xdata')->get('admin_Config:register');
        $registerType = $_register_config['register_type'];
        // 判断是否能进行邀请
        if (!$isAdmin && $registerType == 'invite') {
            $count = $this->getAvailableCodeCount($uid, 'email');

            // 扣除积分
            if ($count == 0) {
                $stauts = $this->applyInviteCode($uid, 'email');
                if (!$stauts) {
                    $this->error = '积分值不足够，不能进行邀请';

                    return false;
                }
            }
        }
        // 格式化数据
        $email = is_array($email) ? $email : explode(',', $email);
        // 获取邀请人相关资料
        $userInfo = model('User')->getUserInfo($uid);
        // 设置配置信息
        $config['name'] = $userInfo['uname'];
        $config['space_url'] = $userInfo['space_url'];
        $config['face'] = $userInfo['avatar_small'];
        $config['content'] = $detail;
        // 历史邮箱数据
        $oldEmail = null;
        // 是否有可用邮箱
        $isEmail = false;
        // 邮箱内容验证
        foreach ($email as $k => $v) {
            // 若邮箱为空
            if (empty($v)) {
                unset($email[$k]);
                continue;
            }
            // 邮箱验证
            $res = preg_match($this->_email_reg, $v, $matches) !== 0;
            if (!$res) {
                $this->error = L('PUBLIC_EMAIL_TIPS');                // 无效的Email地址
                return false;
            } else {
                $registerConf = model('Xdata')->get('admin_Config:register');
                if (!empty($registerConf['email_suffix'])) {
                    $res = in_array($matches[1], explode(',', $registerConf['email_suffix']));
                    if (!$res) {
                        $this->error = $matches['1'].L('PUBLIC_EMAIL_SUFFIX_FORBIDDEN');            // 邮箱后缀不允许注册
                        return false;
                    }
                }
            }
            if ($res && ($v != $oldEmail) && model('User')->where('`email`="'.mysql_escape_string($v).'"')->find()) {
                $this->error = L('PUBLIC_ACCOUNT_REGISTERED');            // 该用户已注册
                return false;
            }
            $isEmail = true;
        }

        if (!$isEmail) {
            $this->error = L('PUBLIC_INVITE_EMAIL_NOEMPTY');            // 邀请Email不能为空
            return false;
        }

        if (!$res) {
            $this->error = '';

            return false;
        }
        // 发送邀请邮件
        foreach ($email as $k => $v) {
            $codes = $this->createInviteCode($uid, 'email', 1, $isAdmin);
            $key = $codes[0];
            $config['invateurl'] = SITE_URL.'/index.php?invite='.$key;
            $data = model('Notify')->getDataByNode('register_invate', $config);
            $notify['uid'] = 0;
            $notify['node'] = 'register_invate';
            $notify['email'] = $v;
            $notify['title'] = $data['title'];
            $notify['body'] = $data['body'];
            $notify['appname'] = 'public';
            model('Notify')->sendEmail($notify);
        }

        $this->error = L('PUBLIC_SEND_INVITE_SUCCESS');                // 发送邀请成功
        return true;
    }

    /**
     * 普通用户获取邀请码操作
     * @param  int    $uid  用户ID
     * @param  string $type 邀请码类型
     * @return bool   是否获取邀请码成功
     */
    public function applyInviteCode($uid, $type)
    {
        // 获取后台积分配置
        $creditRule = model('Credit')->getCreditRuleByName('core_code');
        $applyCredit = abs($creditRule['score']);
        // 更新积分
        $userCredit = model('Credit')->getUserCredit($uid);
        $score = $userCredit['credit']['score']['value'];
        if ($score < $applyCredit) {
            return false;
        } else {
            // 添加邀请码操作
            $type == 'link' && $result = $this->createInviteCode($uid, $type, 1);
            // 扣除积分操作
            if ($result || $type == 'email') {
                model('Credit')->setUserCredit($uid, 'core_code');

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 获取邀请结果列表，用于后台 - 分页型
     * @param  array $map      查询条件
     * @param  int   $pageNums 结果集数目，默认为10
     * @return array 邀请结果列表
     */
    public function getPage($map = array(), $pageNums = 10)
    {
        $map['is_used'] = 1;
        $list = $this->where($map)->order('ctime DESC')->findPage($pageNums);

        return $list;
    }

    /**
     * 获取邀请排行信息
     * @param  string $where    查询条件
     * @param  int    $pageNums 结果集数目，默认为20
     * @return array  邀请排行信息
     */
    public function getTopPage($where = '', $pageNums = '20')
    {
        if (empty($where)) {
            $where = ' WHERE is_used = 1 ';
        } else {
            $where = ' WHERE is_used = 1 AND '.$where;
        }
        $sql = 'SELECT inviter_uid, COUNT(receiver_uid) AS nums FROM '.$this->tablePrefix.$this->tableName." {$where} GROUP BY inviter_uid ";
        $count = $this->query("SELECT COUNT(1) AS nums FROM ({$sql}) a ");
        $count = $count[0]['nums'];
        $sql .= ' ORDER BY COUNT(inviter_uid) DESC ';
        $list = $this->findPageBySql($sql, $count, $pageNums);

        return $list;
    }
}
