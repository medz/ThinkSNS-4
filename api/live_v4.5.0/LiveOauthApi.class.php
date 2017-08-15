
<?php

use Ts\Models\CreditUser;

/**
 * 签到API接口.
 *
 * @author
 *
 * @version  TS4.0
 */
class LiveOauthApi extends Api
{
    /**
     * @name 根据ticket获取用户授权
     * @params 依次传�
     * � (string)tickct
     *
     * @return array 结果信息
     */
    protected $api_list = array('ZB_User_Get_AuthByTicket', 'ZB_User_Get_List', 'ZB_User_Follow', 'ZB_Trade_Create', 'ZB_User_Get_Info', 'ZB_User_Get_ticket', 'ZB_Trade_Get_Pretoken', 'ZB_Trade_Get_Status', 'ZB_Trade_Get_list');

    public function __construct()
    {
        parent::__construct();
        $api = t($_REQUEST['api']);
        !$api['api'] && $api = $this->api_list[0];
        if (!in_array($api, $this->api_list)) {
            $return = array(
                        'code'    => '00502',
                        'message' => '非法请求了哦',
                    );
            exit(json_encode($return));
        }
    }

    /**
     * 在第一次登录没有获取到ticket的时候，这里可以重新获取一次，�
     * 用于已登录的用户.
     *
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-15T01:31:52+0800
     */
    public function ZB_User_Get_ticket()
    {
        if (!$this->mid) {
            exit(json_encode(array(
                    'code'    => '00502',
                    'message' => '用户未登录',
                )));
        }
        $map['uid'] = $this->mid;
        $ticket = D('live_user_info')->where($map)
                                     ->getField('ticket');
        if (!$ticket) {
            $live_user_info = file_get_contents(SITE_URL.'/api.php?api_type=live&mod=LiveUser&act=postUser&uid='.$this->mid);
            $live_user_info = json_decode($live_user_info, true);
            $live_user_info['status'] == 1 && $ticket = $live_user_info['data']['ticket'];
        }
        if ($ticket) {
            return array(
                    'code' => '00000',
                    'data' => array(
                                'ticket' => $ticket,
                        ),
                );
        } else {
            return array(
                    'code'    => '00500',
                    'message' => '授权验证失败',
        );
        }
    }

    public function ZB_User_Get_AuthByTicket()
    {
        $api = t($_REQUEST['api']);
        $ticket = t($_REQUEST['ticket']);
        if (!$ticket) {
            $return = array(
                        'code'    => '00502',
                        'message' => '票据丢失了',
                );
            exit(json_encode($return));
        }
        $mod = M('live_user_info');

        $map = array(
                'ticket' => $ticket,
            );

        $uid = $mod->where($map)
                    ->getField('uid');
        if (!$uid) {
            $return['message'] = '用户不存在';
            $return['code'] = '00404';
            exit(json_encode($return));
        }
        $hasUser = M('user')
                    ->where(array('uid' => $uid))
                    ->count();

        if (!$hasUser) {
            $return['message'] = '用户不存在';
            $return['code'] = '00404';
            exit(json_encode($return));
        }
        $oauth_info = M('login')
                        ->where(array('uid' => $uid, 'type' => 'location'))
                        ->field('oauth_token_secret,oauth_token')
                        ->find();
        /*
         * 此处有坑，暂时不能够判断前端用户是根据什么登录方式拿到的ticket
         */
        if (!$oauth_info) {
            $data['oauth_token'] = getOAuthToken($uid);
            $data['oauth_token_secret'] = getOAuthTokenSecret();
            $data['uid'] = $uid;
            $savedata['type'] = 'location';
            $savedata = array_merge($savedata, $data);
            M('login')->add($savedata);
            $oauth_info['oauth_token'] = $data['oauth_token'];
            $oauth_info['oauth_token_secret'] = $data['oauth_token_secret'];
        }
        foreach ($oauth_info as $key => $value) {
            $data[] = array('auth_key' => $key, 'auth_value' => $value);
        }
        $return['data'] = $data;
        $return['code'] = '00000';
        exit(json_encode($return));
        break;
    }

    public function ZB_User_Get_List()
    {
        $type = $_REQUEST['type'] ? t($_REQUEST['type']) : 'follow';
        model('UserData')->setKeyValue($this->mid, 'new_folower_count', 0);
        if (empty($_REQUEST['usid'])) {
            $uid = $this->mid;
            $udata = model('UserData')->getUserData($this->mid);
            $udata['new_folower_count'] > 0 && model('UserData')->setKeyValue($this->mid, 'new_folower_count', 0);
        } else {
            $uid = D('live_user_info')->where(array(
                        'usid' => t($_REQUEST['usid']),
                ))->getField('uid');
        }

        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        if ($type == 'fans') {
            if (t($this->data['uname'])) {
                $map['f.`uid`'] = $uid;
                !empty($max_id) && $map['follow_id'] = array(
                        'lt',
                        $max_id,
                );
                $map['u.`uname`'] = array(
                        'LIKE',
                        '%'.$this->data['uname'].'%',
                );
                $follower = D()->table('`'.C('DB_PREFIX').'user_follow` AS f LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON f.`uid` = u.`uid`')->field('f.`follow_id` AS `follow_id`,f.`fid` AS `uid`')->where($map)->order('follow_id DESC')->limit($count)->findAll();
            } else {
                $where = 'fid = '.$uid;
                !empty($max_id) && $where .= " AND follow_id < {$max_id}";
                $follower = model('Follow')->where($where)->order('follow_id DESC')->field('follow_id,uid')->limit($count)->findAll();
            }
        }

        if ($type == 'follow') {
            if (t($this->data['uname'])) {
                $map['f.`fid`'] = $uid;
                !empty($max_id) && $map['follow_id'] = array(
                        'lt',
                        $max_id,
                );
                $map['u.`uname`'] = array(
                        'LIKE',
                        '%'.$this->data['uname'].'%',
                );
                $follower = D()->table('`'.C('DB_PREFIX').'user_follow` AS f LEFT JOIN `'.C('DB_PREFIX').'user` AS u ON f.`uid` = u.`uid`')->field('f.`follow_id` AS `follow_id`,f.`uid` AS `uid`')->where($map)->order('follow_id DESC')->limit($count)->findAll();
            } else {
                $where = 'uid = '.$uid;
                !empty($max_id) && $where .= " AND follow_id < {$max_id}";
                $follower = model('Follow')->where($where)->order('follow_id DESC')->field('follow_id,`fid` AS `uid`')->limit($count)->findAll();
            }
        }

        $follow_status = model('Follow')->getFollowStateByFids($this->mid, getSubByKey($follower, 'uid'));
        $follower_arr = array();
        $data = array();
        $live_user_mod = M('live_user_info');
        foreach ($follower as $k => $v) {
            $follower_info = $this->get_user_info($v['uid']);
            $credit = CreditUser::where('uid', $v['uid'])->first();
            $follower_arr[$k]['follow_status'] = $follow_status[$v['uid']];
            $data[$k]['user']['uid'] = (string) $v['uid'];
            $data[$k]['user']['usid'] = ($usid = $live_user_mod->where(array('uid' => $v['uid']))->getField('usid')) ? $usid : '';
            // 没登陆过智播没有usid的 直接生成一个
            if (!$usid = $live_user_mod->where(array('uid' => $v['uid']))->getField('usid')) {
                $live_user_info = file_get_contents(SITE_URL.'/api.php?api_type=live&mod=LiveUser&act=postUser&uid='.$v['uid']);
                $live_user_info = json_decode($live_user_info, true);
                $live_user_info['status'] == 1 && $data[$k]['user']['usid'] = $live_user_info['data']['usid'];
            } else {
                $data[$k]['user']['usid'] = $usid;
            }
            $data[$k]['user']['uname'] = $follower_info['uname'];
            $data[$k]['user']['sex'] = $follower_info['sex'];
            $data[$k]['user']['intro'] = $follower_info['intro'] ? $follower_info['intro'] : '';
            $data[$k]['user']['cover'] = (object) array();
            $data[$k]['user']['location'] = $follower_info['location'] ? $follower_info['location'] : '';
            $data[$k]['user']['avatar'] = (object) array($follower_info['avatar']['avatar_big']);
            $data[$k]['user']['gold'] = $follower_info['user_credit']['credit']['score']['value'];
            $data[$k]['user']['fans_count'] = $follower_info['user_data']['follower_count'];
            $data[$k]['user']['zan_count'] = $credit['zan_remain'];
            $data[$k]['user']['live_time'] = $credit['live_time'];
            $data[$k]['user']['follow_count'] = $follower_info['user_data']['following_count'] ? $follower_info['user_data']['following_count'] : 0;
            $data[$k]['is_follow'] = $follow_status[$v['uid']]['following'];
            $data[$k] = $data[$k];
        }
        if (!empty($data)) {
            return (object) array('code' => '00000', 'data' => $data);
        } else {
            return (object) array('code' => '00000', 'data' => array());
        }
    }

    public function ZB_User_Follow()
    {
        $action = (int) $_REQUEST['action'];
        $usid = t($_REQUEST['usid']);
        !$action && $action = 3;
        if (!$usid) {
            exit(json_encode(array('code' => '00502', 'message' => '用户id缺失')));
        }

        $uid = M('live_user_info')->where(array('usid' => $usid))->getField('uid');
        if (!$uid) {
            exit(json_encode(array('code' => '00000', 'data' => array())));
        }
        $_follow_model = model('Follow');
        switch ($action) {
                // 关注操作
              case 1:
                  $res = $_follow_model->doFollow($this->mid, intval($uid));
                  if (!$res) {
                      $error = $_follow_model->getError();
                      exit(json_encode(array('code' => '00506', 'message' => $error))); //由于关注相关判断返回的关注失败信息 bs
                  } else {
                      exit(json_encode(array('code' => '00000', 'data' => array('is_follow' => (int) $res['following']))));
                  }
                  break;

                  //取关操作
              case 2:
                    $res = $_follow_model->unFollow($this->mid, intval($uid));
                    exit(json_encode(array('code' => '00000', 'data' => array('is_follow' => (int) $res['following']))));
                break;

                //查询操作
                case 3:
                    $res = $_follow_model->getFollowStateByFids($this->mid, intval($uid));
                    exit(json_encode(array('code' => '00000', 'data' => array('is_follow' => (int) $res[$uid]['following']))));
                break;
              default:
                  $res = $_follow_model->getFollowStateByFids($this->mid, intval($uid));
                    exit(json_encode(array('code' => '00000', 'data' => array('is_follow' => (int) $res[$uid]['following']))));
                  break;
          }
    }

    public function ZB_User_Get_Info()
    {
        $usid = t($_REQUEST['usid']);
        $field = t($_REQUEST['field']);
        if (!$usid) {
            exit(json_encode(array('code' => '00502', 'message' => '用户id缺失')));
        }
        $usid = explode(',', $usid);
        $live_user_info_mod = D('live_user_info');

        $uids = array();
        foreach ($usid as $v) {
            if ($v) {
                $uids[] = $live_user_info_mod->where(array('usid' => $v))->getField('uid');
            }
        }
        foreach ($usid as $key => &$value) {
            $value = '"'.$value.'"';
        }
        $amap['usid'] = array(
                            'IN',
                            $usid,
        );
        $uid_usid = $live_user_info_mod->where($amap)->getField('uid,usid');
        if (!$uids) {
            exit(json_encode(array('code' => '00000', 'data' => array())));
        }
        // $num = $_REQUEST['num'];
        // $num = intval($num);
        // $num or $num = 10;

        if (empty($uids) && empty($this->data['uname'])) {
            $uids = array($this->mid);
        } else {
            if (!$uids) {
                (array) $uids = model('User')->where(array(
                        'uname' => $this->data['uname'],
                ))->getField('uid');
            }
        }
        //if (!in_array($this->mid,$uid)) {
        //	foreach($uid as &key => $v){
        //		$privacy = model('UserPrivacy')->getPrivacy($this->mid, $v);
        //    		if ($privacy ['space'] == 1) {
        //       		 return array(
        //               		'code' => '00502',
        //                	'message' => '您没有权限进入TA的个人主页',
        //	        );
        //            }

        //	}
        //}
        foreach ($uids as $key => $v) {
            $userInfo = $this->get_user_info($v);
            if (!$userInfo['uname']) {
                return array(
                                'code'    => '00404',
                                'message' => '该用户不存在或已被删除',
                        );
            }
            $user_info[$key]['uid'] = (string) $userInfo['uid'];
            $user_info[$key]['uname'] = $userInfo['uname'];
            $user_info[$key]['sex'] = $userInfo['sex'];
            $user_info[$key]['intro'] = $userInfo['intro'] ? formatEmoji(false, $userInfo['intro']) : '';
            $user_info[$key]['location'] = $userInfo['location'] ? $userInfo['location'] : '';
            $user_info[$key]['avatar'] = (object) array($userInfo['avatar']['avatar_big']);
            $user_info[$key]['gold'] = intval($userInfo['user_credit']['credit']['score']['value']);
            $user_info[$key]['fans_count'] = intval($userInfo['user_data']['follower_count']);
            $user_info[$key]['is_verified'] = 0;
            $user_info[$key]['usid'] = $uid_usid[$v] ? $uid_usid[$v] : '';
            $credit_mod = M('credit_user');
            $credit = $credit_mod->where(array('uid' => $v))->find();
            $user_info[$key]['zan_count'] = $credit['zan_remain'];
            $user_info[$key]['live_time'] = $credit['live_time'];
            $res = model('Follow')->getFollowStateByFids($this->mid, intval($v));
            $user_info[$key]['is_follow'] = $res[$v]['following'];

            /* # 获取用户封面 */
            $user_info[$key]['cover'] = D('user_data')->where('`key` LIKE "application_user_cover" AND `uid` = '.$v)->field('value')->getField('value');
            // $user_info['cover'] = (object)getImageUrlByAttachId($user_info['cover']);
            $user_info[$key]['cover'] = (object) array();

            if ($field != '') { //返回指定字段
                $_user_info = $user_info[$key];
                unset($user_info[$key]);
                $field_arr = explode(',', $field);
                foreach ($field_arr as $fk => $fv) {
                    $user_info[$key][$fv] = $_user_info[$fv];
                }
            }

            $user_info[$key] = (object) $user_info[$key];
        }

        return array('code' => '00000', 'data' => $user_info);
    }

    /**
     * 查询账单接口.
     *
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-27T14:58:32+0800
     */
    public function ZB_Trade_Get_Status()
    {
        $trade_order = $this->jiemi(t($_POST['trade_order']));
        $trade_order = t($_POST['trade_order']);

        if (!$trade_order) {
            return array('code' => '00404', 'message' => '订单号丢失了');
        }

        $map['trade_order'] = $trade_order;
        $order = M('order_logs')->where($map)->getField('save_status');
        $user_data = model('UserData')->getUserData($this->mid);
        $user_gold = CreditUser::where('uid', $this->mid)->select('zan_remain as zan_count', 'score as gold')->first();
        $data['zan_count'] = $user_gold['zan_count'];
        $data['gold'] = $user_gold['gold'];
        // if($order != 1)
        // {
        //     return $order == 0 ? array('code' => '00505', 'message' =>'交易处理中') : array('code'=>'00506', 'message' => '交易被驳回');
        // }
        return array('code' => '00000', 'data' => array_merge($data, array('follow_count' => $user_data['following_count'] ? $user_data['following_count'] : 0, 'fans_count' => $user_data['follower_count'] ? $user_data['follower_count'] : 0)));
    }

    /**
     * �
     * �换记录.
     *
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-27T15:38:52+0800
     */
    public function ZB_Trade_Get_list()
    {
        $map['uid'] = $this->mid;
        $map['type'] = t($_POST['type']) == 'zan' ? 5 : 4;
        $order = ' ctime DESC ';
        $limit = $_POST['limit'] ? intval($_POST['limit']) : 20;
        $result = M('credit_record')->where($map)->order($order)->findPage(10);
        unset($map);
        $return = array('code' => '00000', 'data' => array());
        if ($result['data']) {
            $order_logs = M('order_logs');
            foreach ($result['data'] as &$value) {
                $detail = json_decode($value['detail'], true);
                $map['trade_order'] = $detail['order'];
                $field = 'type, save_status, uid, to_uid';
                $order = $order_logs->where($map)->field($field)->find();
                $uname = $to_uname = model('User')->where(array('uid' => $order['uid']))->getField('uname');
                if ($order['uid'] != $order['to_uid']) {
                    $to_uname = model('User')->where(array('uid' => $order['to_uid']))->getField('uname');
                }
                $data['desction'] = $value['des'];
                $data['ctime'] = (string) $value['ctime'];
                $data['num'] = $value['change'];
                $data['trade_order'] = $detail['order'];
                $data['order_type'] = (string) $order['type'];
                $data['save_status'] = $order['save_status'];
                $data['uname'] = $uname;
                $data['to_uname'] = $to_uname;
                $return['data'][] = $data;
                unset($detail, $map, $field, $order, $data);
            }
        }

        return $return;
    }

    /**
     * 获取预操作口令.
     *
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-22T10:46:19+0800
     */
    public function ZB_Trade_Get_Pretoken()
    {
        //此方法只能用post请求
        $return = array(
                    'code'    => '40007',
                    'message' => '',
            );
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            exit(json_encode(array('code' => '00502', 'message' => '非法请求')));
        }
        $type = intval($_POST['type']);
        $token = t($_POST['token']);
        $hextime = t($_POST['hextime']);
        $uid = intval($_POST['user_id']);
        // $_POST['to_user_id'] && $to_user_id = (int)t($_POST['to_user_id']);
        // echo $type.'/'.$token.'/'.$hextime.'/'.$uid;die;
        //仅用于转账
        if ((!$type || $type != 1) || !$token || !$hextime || !$uid) {
            $return['message'] = '参数错误';
            exit(json_encode($return));
        }
        if ($uid != $this->mid) {
            $return['message'] = '您没有权限执行此操作';
            exit(json_encode($return));
        }
        //口令时间检测
        $ctime = hexdec($hextime);
        if ($ctime + 120 < NOW_TIME) {
            //过期的口令
            $return['message'] = '交易超时';
            exit(json_encode($return));
        }
        $m_token = md5($ctime.$type.$uid);
        if (strtolower($m_token) != strtolower($token)) {
            //口令验证失败
            $return['message'] = '口令验证失败';
            $return['code'] = '50000';
            exit(json_encode($return));
        }
        //条件
        $data = array(
            'uid'    => $uid,
            'to_uid' => $this->mid,
            'type'   => $type,
        );
        //尝试获取预交易口令
        $token = $this->getPreToken($data);
        if ($token) {
            return array('code' => '00000', 'data' => array('pre_token' => $this->jiami($token)));
        }

        $return['code'] = '70500';
        $return['message'] = '交易失败';

        return $return;
    }

    /**
     * 发起创建订单.
     *
     * @Author   Wayne[qiaobin@zhiyicx.com]
     * @DateTime 2016-10-26T17:36:37+0800
     */
    public function ZB_Trade_Create()
    {
        $pre_token = t($_POST['pre_token']);
        if (!$pre_token) { //口令丢失
            return array('code' => '70402', 'message' => '操作失败');
        }
        $pre_token = $this->jiemi($pre_token);
        $count = intval($_POST['count']);
        if (!$count || $count < 0) { //兑换值错误
            return array('code' => '70401', 'message' => '操作失败');
        }
        $params = stripslashes($_POST['params']);
        $params && $params = json_decode($params, true);
        $data['pre_token'] = $pre_token;
        $data['count'] = $count;
        $data['uid'] = $data['to_uid'] = $this->mid;
        $data = $params ? array_merge($data, $params) : $data;
        $result = $this->createOrder($data);
        if ($result['code'] != '00000') {
            return $result;
            die;
        } else {
            $result['trade_order'] = $this->jiami($result['trade_order']);
            unset($result['code']);

            return array('code' => '00000', 'data' => $result);
            die;
        }
        $error['code'] = '70401';
        $error['message'] = '操作失败';

        return $error;
    }

    /**
     * @name 生成预交易口令
     */
    public function getPreToken($data = array())
    {
        if (empty($data) || !$data['uid'] || !$data['to_uid']) {
            return '';
        }
        $table = D('preorder_token');
        //禁用状态
        $data['disable'] = 0;
        //是否存在未使用的口令
        $hasOne = $table->where($data)->find();
        if ($hasOne) {
            //存在
            $save['token'] = $this->jiami(date('mdHs', time() - 60).mt_rand(10000, 99999).$data['uid']);
            $save['create_time'] = NOW_TIME;
            $table->where('token_id='.$hasOne['token_id'])->save($save);
            $token = $save['token'];
        } else {
            //不存在
            $data['token'] = $this->jiami(date('mdHs', time() - 60).mt_rand(10000, 99999).$data['uid']);
            $data['create_time'] = NOW_TIME;
            if ($table->add($data)) {
                $token = $data['token'];
            } else {
                $token = '';
            }
        }

        return $token;
    }

    /**
     * 获取用户信息 --using.
     *
     * @param int $uid
     *                 用户UID
     *
     * @return array 用户信息
     */
    protected function get_user_info($uid)
    {
        $user_info = model('Cache')->get('user_info_api_'.$uid);
        if (!$user_info) {
            $user_info = model('User')->where('uid='.$uid)->field('uid,uname,sex,location,province,city,area,intro')->find();
            // 头像
            $avatar = model('Avatar')->init($uid)->getUserAvatar();
            // $user_info ['avatar'] ['avatar_middle'] = $avatar ["avatar_big"];
            // $user_info ['avatar'] ['avatar_big'] = $avatar ["avatar_big"];
            $user_info['avatar'] = $avatar;
            // 用户组
            $user_group = model('UserGroupLink')->where('uid='.$uid)->field('user_group_id')->findAll();
            foreach ($user_group as $v) {
                $user_group_icon = D('user_group')->where('user_group_id='.$v['user_group_id'])->getField('user_group_icon');
                if ($user_group_icon != -1) {
                    $user_info['user_group'][] = THEME_PUBLIC_URL.'/image/usergroup/'.$user_group_icon;
                }
            }
            model('Cache')->set('user_info_api_'.$uid, $user_info);
        }
        // 积分、经验
        $user_info['user_credit'] = model('Credit')->getUserCredit($uid);
        $user_info['intro'] && $user_info['intro'] = formatEmoji(false, $user_info['intro']);
        // 用户统计
        $user_info['user_data'] = model('UserData')->getUserData($uid);

        return $user_info;
    }

    /**
     * @name 生成订单
     */
    protected function createOrder($data = array())
    {
        //默认的错误提示信息
        $this->error = '支付异常,请重新尝试';
        if (!$data['pre_token']) {
            // return getApiErrorCode('70403');
            return array('code' => '70403', 'message' => '预操作口令不能为空');
        }
        // if (!model('Valid')->isString($data['pre_token'])) {
        //     return getApiErrorCode('70403');
        // }

        //查询口令的信息
        $table = M('preorder_token');
        $token = $table->where(['token' => $data['pre_token']])->find();
        if ($token['uid'] != $data['uid']) {
            return array('code' => '70403', 'message' => '口令错误');
        }
        //口令有效性检测
        if (!$token || $token['disable'] == 1 || (int) $token['create_time'] + 1800 < NOW_TIME) {
            return array('code' => '70403', 'message' => '口令失效');
        }

        //有效参数验证
        if ($token['type'] != 1 /*&& !$data['gift_code']*/) {
            // $this->error = '不明确的礼物类型';
            return array('code' => '70403', 'message' => '交易类型错误');
        }
        // print_r($token);die;
        //口令有效,生成订单
        if ($token['trade_order'] = self::_create_order($token)) {
            $return = self::do_trade_order(array_merge($token, $data));

            return $return;
        }

        return false;
    }

    /**
     *@name 处理�
     * �换
     */
    protected function do_trade_order($order = array())
    {
        $count = (int) $order['count'];
        //兑换
        // \think\Config::load(join(DIRECTORY_SEPARATOR, array(APP_PATH, 'user', 'config.php')));
        $config_list = C('exchange_type');
        $gold_num = intval($count / $config_list);
        if (!$gold_num) {
            //不支持的对话金额
            return array('code' => '70302', 'message' => '数量不能为0');
        }
        $credit_mod = M('credit_user');
        // $credit_mod = new CreditUser(); //laravel4.1的事务使用匿名函数提交..

        // M('credit_user')->startTrans();
        $credit_mod->startTrans();
        if ($credit_mod->setInc('score', 'uid='.$order['to_uid'], $gold_num)) {
            // if($credit_mod->increment('score', $gold_num, array('uid'=>$order['to_uid']))){
            //扣除支出方金币成功--添加收款方金币
            $dec['zan_remain'] = 'zan_remain - '.$count;
            $where['uid'] = $order['uid'];
            if ($credit_mod->setDec('zan_remain', 'uid='.$order['uid'], $count)) {
                $credit_mod->commit();
                //收款方接收成功,生成日志(支出方+接收方)
                //收款记录
                $credit_record = M('credit_record');
                $log = array(
                    'change' => $gold_num,
                    'uid'    => $order['uid'], //支出方
                    'type'   => 4, //ts中记录赞兑换积分为4
                    'des'    => '赞兑换积分',
                    'cid'    => 0,
                    'action' => '直播赞兑换',
                    'ctime'  => NOW_TIME,
                    'detail' => json_encode(array('score' => $gold_num, 'order' => $order['trade_order'])),
                );
                $credit_record->add($log);
                //支付记录
                //添加赞消费记录
                $log = array(
                    'change' => -$count,
                    'uid'    => $order['uid'],
                    'type'   => 5, //ts中赞消耗的类型为5
                    'des'    => '赞兑换积分',
                    'cid'    => 0,
                    'action' => '赞消费',
                    'ctime'  => NOW_TIME,
                    'detail' => json_encode(array('zan_remain' => -$count, 'order' => $order['trade_order'])),
                );
                $credit_record->add($log);
                model('User')->cleanCache($order['uid']);
                //给直播服务器发送数据同步通知
                // $zhibocloud = new \app\common\model\ZhiboCloud;
                // $zhibocloud->sendSyncNotify($order['uid']);
                // self::setOrder($order['trade_order'], ['save_status' => 1, 'save_time' => NOW_TIME]);
                M('order_logs')->where(array('trade_order' => $order['trade_order']))->setField(array('save_status', 'save_time'), array(1, NOW_TIME));
            } else {
                //收款方接收失败,回滚记录
                //兑换失败
                M('credit_user')->rollback();
                M('order_logs')->where(array('trade_order' => $order['trade_order']))->setField(array('save_status', 'save_time'), array(0, NOW_TIME));

                return array('code' => '70403', 'message' => '兑换失败');
            }
        } else {
            return array('code' => '700401', 'message' => '兑换失败');
        }

        return array('code' => '00000', 'trade_order' => $order['trade_order']);
    }

    /**
     * @name 生成预处理订单
     *
     * @param array $token 创建订单的相�
     * �信息
     *
     * @return string 订单号
     */
    public function _create_order($token = [])
    {
        if (!is_array($token)) {
            return 0;
        }
        $trade_order = date('YmdHis', time()).mt_rand(1000, 9999);
        $data = array(
            'trade_order' => $trade_order,
            'uid'         => $token['uid'],
            'to_uid'      => $token['to_uid'],
            'create_time' => NOW_TIME,
            'type'        => $token['type'],
        );
        $Order = D('OrderLogs');
        if ($Order->data($data)->add()) {
            return $trade_order;
        }

        return 0;
    }

    //加密函数
    private function jiami($txt, $key = null)
    {
        if (empty($key)) {
            $key = C('SECURE_CODE');
        }
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_';
        $nh = rand(0, 64);
        $ch = $chars[$nh];
        $mdKey = md5($key.$ch);
        $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
        $txt = base64_encode($txt);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = ($nh + strpos($chars, $txt[$i]) + ord($mdKey[$k++])) % 64;
            $tmp .= $chars[$j];
        }

        return $ch.$tmp;
    }

    //解密函数
    private function jiemi($txt, $key = null)
    {
        if (empty($key)) {
            $key = C('SECURE_CODE');
        }
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_';
        $ch = $txt[0];
        $nh = strpos($chars, $ch);
        $mdKey = md5($key.$ch);
        $mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
        $txt = substr($txt, 1);
        $tmp = '';
        $i = 0;
        $j = 0;
        $k = 0;
        for ($i = 0; $i < strlen($txt); $i++) {
            $k = $k == strlen($mdKey) ? 0 : $k;
            $j = strpos($chars, $txt[$i]) - $nh - ord($mdKey[$k++]);
            while ($j < 0) {
                $j += 64;
            }
            $tmp .= $chars[$j];
        }

        return base64_decode($tmp);
    }
}
