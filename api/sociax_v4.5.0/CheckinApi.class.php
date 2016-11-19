
<?php
/**
 * 签到API接口
 * @author
 * @version  TS4.0
 */
class CheckinApi extends Api
{
    /**
     * 获取签到情况 --using
     *
     * @return array 签到信息
     */
    public function get_check_info()
    {
        $uid = $this->mid;
        $data = model('Cache')->get('check_info_'.$uid.'_'.date('Ymd'));
        if (! $data) {
            $map ['uid'] = $uid;
            $map ['ctime'] = array(
                    'gt',
                    strtotime(date('Ymd')),
            );
            $res = D('check_info')->where($map)->find();
            // 是否签到
            $data ['ischeck'] = $res ? true : false;
            $checkinfo = D('check_info')->where('uid='.$uid)->order('ctime desc')->limit(1)->find();
            if ($checkinfo) {
                if ($checkinfo ['ctime'] > (strtotime(date('Ymd')) - 86400)) {
                    $data ['con_num'] = $checkinfo ['con_num'];
                } else {
                    $data ['con_num'] = 0;
                }
                $data ['total_num'] = $checkinfo ['total_num'];
            } else {
                $data ['con_num'] = 0;
                $data ['total_num'] = 0;
            }
            $data ['day'] = date('m.d');
            model('Cache')->set('check_info_'.$uid.'_'.date('Ymd'), $data);
        }

        return $data;
    }
    // 排行榜
    public function rank()
    {
        $list = D('check_info')->where('ctime>'.strtotime(date('Ymd')))->order('ctime asc')->limit(5)->findAll();
        foreach ($list as &$v) {
            $avatar = model('Avatar')->init($v ['uid'])->getUserAvatar();
            $v ['avatar'] = $avatar ['avatar_big'];
            $v ['uname'] = getUserName($v ['uid']);
            $v ['remark'] = D('UserRemark')->getRemark($this->mid, $v['uid']);
        }

        return $list;
    }

    /**
     * 获取指定分类下的微博 --using
     *
     * @return array 签到情况
     */
    public function checkin()
    {
        $uid = $this->mid;

        $map ['ctime'] = array(
                'gt',
                strtotime(date('Ymd')),
        );

        $map ['uid'] = $uid;

        $ischeck = D('check_info')->where($map)->find();
        // 未签到
        if (! $ischeck) {
            // 清理缓存
            model('Cache')->set('check_info_'.$uid.'_'.date('Ymd'), null);

            $map ['ctime'] = array(
                    'lt',
                    strtotime(date('Ymd')),
            );
            $last = D('check_info')->where($map)->order('ctime desc')->find();
            $data ['uid'] = $uid;
            $data ['ctime'] = $_SERVER ['REQUEST_TIME'];
            // 是否有签到记录
            if ($last) {
                // 是否是连续签到
                if ($last ['ctime'] > (strtotime(date('Ymd')) - 86400)) {
                    $data ['con_num'] = $last ['con_num'] + 1;
                } else {
                    $data ['con_num'] = 1;
                }
                $data ['total_num'] = $last ['total_num'] + 1;
            } else {
                $data ['con_num'] = 1;
                $data ['total_num'] = 1;
            }

            if (D('check_info')->add($data)) {
                model('Credit')->setUserCredit($uid, 'check_in', 1, array(
                    'user' => $GLOBALS['ts']['user']['uname'],
                    'content' => '签到',
                ));
                // 更新连续签到和累计签到的数据
                $connum = D('user_data')->where('uid='.$uid." and `key`='check_connum'")->find();
                if ($connum) {
                    $connum = D('check_info')->where('uid='.$uid)->getField('max(con_num)');
                    D('user_data')->setField('value', $connum, "`key`='check_connum' and uid=".$uid);
                    D('user_data')->setField('value', $data ['total_num'], "`key`='check_totalnum' and uid=".$uid);
                } else {
                    $connumdata ['uid'] = $uid;
                    $connumdata ['value'] = $data ['con_num'];
                    $connumdata ['key'] = 'check_connum';
                    D('user_data')->add($connumdata);

                    $totalnumdata ['uid'] = $uid;
                    $totalnumdata ['value'] = $data ['total_num'];
                    $totalnumdata ['key'] = 'check_totalnum';
                    D('user_data')->add($totalnumdata);
                }
            }
        }

        return $this->get_check_info();
    }
    // 记录用户的最后活动位置
    public function checkinlocation()
    {
        $latitude = floatval($this->data ['latitude']);
        $longitude = floatval($this->data ['longitude']);
        // 记录用户的UID、经度、纬度、checkin_time、checkin_count
        // 如果没有记录则写入，如果有记录则更新传过来的字段包括：sex\nickname\infomation（用于对周边人进行搜索）
        $checkin_count = D('mobile_user')->where('uid='.$this->mid)->getField('checkin_count');
        $data ['last_latitude'] = $latitude;
        $data ['last_longitude'] = $longitude;
        $data ['last_checkin'] = time();
        // dump(444);
        if ($checkin_count) {
            $data ['checkin_count'] = $checkin_count + 1;
            $res = D('mobile_user')->where('uid='.$this->mid)->save($data);
        } else {
            $user = model('User')->where('uid='.$this->mid)->field('uname,intro,sex')->find();
            $data ['nickname'] = $user ['uname'];
            $data ['infomation'] = $user ['intro'];
            $data ['sex'] = $user ['sex'];

            $data ['checkin_count'] = 1;
            $data ['uid'] = $this->mid;
            $res = D('mobile_user')->add($data);

            // dump($data);
            // dump(D('mobile_user')->getLastSql());
            // dump($res);
        }

        return array(
                'status' => intval($res),
        );
    }
}
