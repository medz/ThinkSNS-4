<?php
/**
 * 积分模型 - 数据对象模型
 * @example
 * $credit = model('Credit')->setUserCredit($uid,'weibo_demo');
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class CreditModel extends Model
{
    // 所有设置的值
    public $info;
    public $creditType;
    private $type = 'experience'; // 等级的图标类型

    /**
     * +----------------------------------------------------------
     * 架构函数
     * +----------------------------------------------------------
     *
     * @author melec制作
     */
    public function __construct()
    {
        if (($this->creditType = S('_service_credit_type')) === false) {
            $this->creditType = M('credit_type')->order('id ASC')->findAll();
            S('_service_credit_type', $this->creditType);
        }
    }

    /**
     * 获取积分设置信息
     *
     * @return array 积分设置信息
     */
    public function getSetData()
    {
        if (($data = model('Cache')->get('credit_set')) == false) {
            $data = model('Xdata')->get('admin_Credit:set');
            model('Cache')->set('credit_set', $data);
        }

        return $data;
    }

    /**
     * 获取用户积分
     *
     * 返回积分值的数据结构
     * <code>
     * array(
     * 'score' =>array(
     * 'credit'=>'1',
     * 'alias' =>'积分',
     * ),
     * 'experience'=>array(
     * 'credit'=>'2',
     * 'alias' =>'经验',
     * ),
     * '类型' =>array(
     * 'credit'=>'值',
     * 'alias' =>'名称',
     * ),
     * )
     * </code>
     *
     * @param  int  $uid
     * @return bool array
     */
    public function getUserCredit($uid)
    {
        if (empty($uid)) {
            return false;
        }

        $userCredit = S('getUserCredit_'.$uid);
        if ($userCredit != false) {
            return $userCredit;
        }

        $userCreditInfo = M('credit_user')->where("uid={$uid}")->find(); // 用户积分

        if (!$userCreditInfo) {
            $data['uid'] = $uid;
            M('credit_user')->add($data); // 用户积分
        }

        foreach ($this->creditType as $v) {
            $userCredit ['credit'] [$v ['name']] = array(
                    'value' => intval($userCreditInfo [$v ['name']]),
                    'alias' => $v ['alias'],
            );
        }

        $userCredit ['creditType'] = $this->getTypeList();

        // 获取积分等级规则
        $level = $this->getLevel();
        $data = $userCredit ['credit'] [$this->type] ['value'];

        foreach ($level as $k => $v) {
            if ($data >= $v ['start'] && $data <= $v ['end']) {
                $userCredit ['level'] = $v;
                $userCredit ['level'] ['level_type'] = $this->type;
                $userCredit ['level'] ['nextNeed'] = $v ['end'] - $data;
                $userCredit ['level'] ['nextName'] = $level [$k + 1] ['name'];
                if (is_numeric($userCredit ['level'] ['image'])) {
                    $userCredit ['level'] ['src'] = getImageUrlByAttachId($userCredit['level']['image']);
                } else {
                    $userCredit ['level'] ['src'] = THEME_PUBLIC_URL.'/image/level/'.$userCredit ['level'] ['image'];
                }
                break;
            }
            if ($data > $v ['end'] && ! isset($level [$k + 1])) {
                $userCredit ['level'] = $v;
                $userCredit ['level'] ['nextNeed'] = '';
                $userCredit ['level'] ['nextName'] = '';
                if (is_numeric($userCredit ['level'] ['image'])) {
                    $userCredit ['level'] ['src'] = getImageUrlByAttachId($userCredit['level']['image']);
                } else {
                    $userCredit ['level'] ['src'] = THEME_PUBLIC_URL.'/image/level/'.$userCredit ['level'] ['image'];
                }
                break;
            }
        }
        S('getUserCredit_'.$uid, $userCredit, 604800);  //缓存一周
        return $userCredit;
    }

    /**
     * 获取积分类型列表
     *
     * @param  string $return
     *                        返回类型，默认为has
     * @return [type] [description]
     */
    public function getTypeList()
    {
        $arr = array();
        foreach ($this->creditType as $value) {
            $arr [$value['name']] = $value ['alias'];
        }

        return $arr;
    }

    /**
     * 获取积分等级规则
     *
     * @return array 积分等级规则信息
     */
    public function getLevel()
    {
        $data = model('Xdata')->get('admin_Credit:level');
        if (! $data) {
            $creditlevel = array();
            $creditlevel[] = array('level' => 1, 'name' => 'level1', 'image' => 'level1.png', 'start' => '0', 'end' => '1000');
            $creditlevel[] = array('level' => 2, 'name' => 'level2', 'image' => 'level2.png', 'start' => '1001', 'end' => '2000');
            $creditlevel[] = array('level' => 3, 'name' => 'level3', 'image' => 'level3.png', 'start' => '2001', 'end' => '3000');
            $creditlevel[] = array('level' => 4, 'name' => 'level4', 'image' => 'level4.png', 'start' => '3001', 'end' => '4000');
            $creditlevel[] = array('level' => 5, 'name' => 'level5', 'image' => 'level5.png', 'start' => '4001', 'end' => '5000');
            $creditlevel[] = array('level' => 6, 'name' => 'level6', 'image' => 'level6.png', 'start' => '5001', 'end' => '6000');
            $creditlevel[] = array('level' => 7, 'name' => 'level7', 'image' => 'level7.png', 'start' => '6001', 'end' => '7000');
            $creditlevel[] = array('level' => 8, 'name' => 'level8', 'image' => 'level8.png', 'start' => '7001', 'end' => '8000');
            $creditlevel[] = array('level' => 9, 'name' => 'level9', 'image' => 'level9.png', 'start' => '8001', 'end' => '9000');
            $creditlevel[] = array('level' => 10, 'name' => 'level10', 'image' => 'level10.png', 'start' => '9001', 'end' => '1000000');
            model('Xdata')->put('admin_Credit:level', $creditlevel);
        }

        return $data;
    }

    /**
     * 添加任务积分
     *
     * @param int $exp
     * @param int $score
     * @param int $uid
     */
    public function addTaskCredit($exp, $score, $uid)
    {
        // 加积分
        D('credit_user')->setInc('experience', 'uid='.$uid, $exp);
        D('credit_user')->setInc('score', 'uid='.$uid, $score);

        $this->cleanCache($uid);
    }

    /**
     * TS2兼容方法：获取积分类型列表
     *
     * @return array 积分类型列表
     */
    public function getCreditType()
    {
        return $this->creditType;
    }

    /**
     * TS2兼容方法：设置用户积分
     * 操作用户积分
     *
     * @param int          $uid
     *                             用户ID
     * @param array|string $action
     *                             系统设定的积分规则的名称
     *                             或临时定义的一个积分规则数组，例如array('score'=>-4,'experience'=>3)即socre减4点，experience加三点
     * @param string|int   $type
     *                              reset:按照操作的值直接重设积分值，整型：作为操作的系数，-1可实现增减倒置
     * @return Object
     */
    public function setUserCredit($uid, $action, $type = 1, $des = array())
    {
        if (! $uid) {
            $this->info = false;

            return $this;
        }
        if (is_array($action)) {
            $creditSet = $action;
            if ($action['name']) {
                // 获取配置规则
                $credit_ruls = $this->getCreditRules();
                foreach ($credit_ruls as $v) {
                    if ($v ['name'] == $action['name']) {
                        $creditSet = array_merge($v, $action);
                    }
                }
            }
        } else {
            // 获取配置规则
            $credit_ruls = $this->getCreditRules();
            foreach ($credit_ruls as $v) {
                if ($v ['name'] == $action) {
                    $creditSet = $v;
                }
            }
        }
        if (! $creditSet) {
            $this->info = '积分规则不存在';

            return $this;
        }
        //dump($creditSet);exit;
        //根据 周期范围 和 周期内最多奖励次数 判断是否变更积分
        if ($creditSet['cycle_times']) {
            $now = time();
            switch ($creditSet['cycle']) {
                case 'year':
                    $beginTime = mktime(0, 0, 0, 1, 1, date('Y', $now));
                    break;
                case 'month':
                    $beginTime = mktime(0, 0, 0, date('m', $now), '1', date('Y', $now));
                default:
                    $beginTime = strtotime(date('Y-m-d 00:00:00', $now));
                    break;
            }
            $c['uid'] = $uid;
            $c['ctime'] = array('between', array($beginTime, $now));
            $times = D('credit_record')->where($c)->count();
            if ($times >= $creditSet['cycle_times']) {
                $this->info = '积分变更已达上限';

                return $this;
            }
        }
        $creditUserDao = M('credit_user');
        $creditUser = $creditUserDao->where("uid={$uid}")->find(); // 用户积分
                                                                      // 积分计算
        if ($type == 'reset') {
            foreach ($this->creditType as $v) {
                $creditUser [$v ['name']] = $creditSet [$v ['name']];
            }
        } else {
            $change = array();
            $detail = array();
            $type = intval($type);
            foreach ($this->creditType as $v) {
                $creditUser [$v ['name']] = $creditUser [$v ['name']] + ($type * $creditSet [$v ['name']]);
                //记录
                if ($creditSet[$v['name']] != 0) {
                    if ($creditSet[$v['name']] * $type > 0) {
                        $c = '+'.$creditSet [$v ['name']];
                    } else {
                        $c = $creditSet [$v ['name']];
                    }
                    $change[] = $v['alias'].'<font color="red">'.$c.'</font>';
                    $detail[$v['name']] = "$c";
                }
            }
        }

        $creditUser ['uid'] || $creditUser ['uid'] = $uid;
        $creditUser ['type'] = $creditSet['type'] ? intval($creditSet) : 1;
        if ($creditUserDao->where('uid='.$creditUser['uid'])->count()) {
            $map['id'] = $creditUser['id'];
            $map['uid'] = $creditUser['uid'];
            unset($creditUser['id']);
            unset($creditUser['uid']);
            $res = $creditUserDao->where($map)->save($creditUser);
        } else {
            $res = $creditUserDao->add($creditUser);
        }
        //记录
        $record['cid'] = $creditSet['id'];
        $record['uid'] = $uid;
        $record['action'] = $creditSet['alias'];
        $record['change'] = implode(',', $change);
        $record['ctime'] = time();
        $replace = array_keys($des);
        foreach ($replace as &$v) {
            $v = '{'.$v.'}';
        }
        $record['des'] = str_replace($replace, $des, $creditSet['des']);
        $record['detail'] = $detail ? json_encode($detail) : '{}';
        D('credit_record')->add($record);
        // 用户进行积分操作后，登录用户的缓存将修改
        $this->cleanCache($uid);
        // $userLoginInfo = S('S_userInfo_'.$uid);
        // if(!empty($userLoginInfo)) {
        // $userLoginInfo['credit']['score']['credit'] = $creditUser['score'];
        // $userLoginInfo['credit']['experience']['credit'] = $creditUser['experience'];
        // S('S_userInfo_'.$uid, $userLoginInfo);
        // }
        if ($res) {
            $this->info = $creditSet ['info'];

            return $this;
        } else {
            $this->info = false;

            return $this;
        }
    }

    /**
     * 获取积分操作结果
     *
     * return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * 获取所有系统积分规则
     */
    public function getCreditRules()
    {
        if (($res = S('_service_credit_rules')) === false) {
            $res = M('credit_setting')->order('type ASC')->findAll();
            S('_service_credit_rules', $res);
        }

        return $res;
    }
    /**
     * 获取某个系统积分规则
     */
    public function getCreditRuleByName($name)
    {
        $map['name'] = $name;
        $res = M('credit_setting')->where($map)->find();

        return $res;
    }

    /**
     * 保存积分等级规则
     * @param array $d 修改的积分等级规则
     */
    public function saveCreditLevel($d)
    {
        $data = $this->getLevel();
        $data[$d['level'] - 1]['name'] = $d['name'];
        $data[$d['level'] - 1]['image'] = $d['image'];
        $data[$d['level'] - 1]['start'] = $d['start'];
        $data[$d['level'] - 1]['end'] = $d['end'];
        model('Xdata')->put('admin_Credit:level', $data);

        //清除用户缓存
        $users = model('User')->field('uid')->findAll();
        foreach ($users as $user) {
            $this->cleanCache($user['uid']);
        }
    }

    /**
     * 积分充值成功
     * @param string $serial_number 订单号
     */
    public function charge_success($serial_number)
    {
        $map['serial_number'] = $serial_number;
        if ($GLOBALS['ts']['mid']) {
            $map['uid'] = $GLOBALS['ts']['mid'];
        }
        $detail = D('credit_charge')->where($map)->find();
        if ($detail && $detail['status'] != 1) {
            $res = D('credit_charge')->where($map)->setField('status', 1);
            if ($res !== false) {
                $score = $this->getUserCredit(intval($detail['uid']));
                $score = intval($score['credit']['score']['value']);
                $add['type'] = 2;
                $add['uid'] = intval($detail['uid']);
                $add['action'] = '积分充值';
                $add['des'] = '';
                $add['change'] = intval($detail['charge_sroce']);
                $add['ctime'] = time();
                $add['detail'] = '{"score":"'.$add['change'].'"}';
                M('credit_user')->where("uid={$add['uid']}")->save(array('score' => $score + $add['change']));
                D('credit_record')->add($add);
                $this->cleanCache($add['uid']);

                return true;
            }
        }

        return false;
    }

    /**
     * 积分转账
     * @param  array $data 转账数据
     * @return bool
     */
    public function startTransfer(array $data = array())
    {
        $data = count($data) ? $data : $_POST;
        if (!$data['toUid'] || $data['num'] <= 0 || !$data['fromUid']) {
            return false;
        }
        $score = $this->getUserCredit($data['toUid']);
        $score = intval($score['credit']['score']['value']);
        $score2 = $this->getUserCredit($data['fromUid']);
        $score2 = intval($score2['credit']['score']['value']);
        if ($score2 < intval($data['num'])) {
            return false;
        }
        $add['type'] = 3;
        $add['uid'] = intval($data['toUid']);
        $add['action'] = '积分转入';
        $add['des'] = t($data['desc']);
        $add['change'] = intval($data['num']);
        $add['ctime'] = time();
        $add['detail'] = '{"score":"'.$add['change'].'"}';

        $add2 = $add;
        $add2['uid'] = intval($data['fromUid']);
        $add2['change'] = -1 * intval($data['num']);
        $add2['action'] = '积分转出';
        $add2['detail'] = '{"score":"'.$add2['change'].'"}';
        M('credit_user')->where("uid={$add2['uid']}")->save(array('score' => $score2 - $add['change']));
        M('credit_user')->where("uid={$add['uid']}")->save(array('score' => $score + $add['change']));
        //转账对象积分变动记录
        //当前用户积分变动记录
        D('credit_record')->add($add) && D('credit_record')->add($add2);
        $this->cleanCache($data['toUid']);
        $this->cleanCache($data['fromUid']);

        return true;
    }
    /**
     * 清除用户积分缓存
     */
    public function cleanCache($uid)
    {
        S('S_userInfo_'.$uid, null);
        S('getUserCredit_'.$uid, null);
    }
}
