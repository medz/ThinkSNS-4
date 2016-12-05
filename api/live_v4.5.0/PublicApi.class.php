<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * 公开api接口.
 *
 * @author Medz Seven <lovevipdsw@vip.qq.com>
 **/
class PublicApi extends Api
{
    public function getAreaAll()
    {
        return Capsule::table('area')->get();
    }

    /**
     * 按照层级获取地区列表.
     *
     * @request int     $pid     地区ID
     *
     * @param bool $notsort 是否不排序，默认排序
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getArea()
    {
        $pid = intval($this->data['pid']);
        $pid or
        $pid = 0;

        isset($this->data['notsort']) or
        $notsort = false;
        $notsort = (bool) $this->data['notsort'];

        $list = model('Area')->getAreaList($pid);

        if ($notsort) {
            return $list;
        }

        $areas = array();
        foreach ($list as $area) {
            $pre = getShortPinyin($area['title'], 'utf-8', '#');

            /* 多音字处理 */
            if ($area['title'] == '重庆') {
                $pre = 'C';
            }

            if (!isset($areas[$pre]) or !is_array($areas[$pre])) {
                $areas[$pre] = array();
            }
            array_push($areas[$pre], $area);
        }
        ksort($areas);

        return $areas;
    }

    /**
     * 获取application幻灯数据.
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getSlideShow()
    {
        $list = D('application_slide')->field('`title`, `image`, `type`, `data`')->select();

        foreach ($list as $key => $value) {
            $value['image'] = getImageUrlByAttachId($value['image']);
            $list[$key] = $value;
        }

        return $list;
    }

    /**
     * 获取关于我们HTML信息.
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function showAbout()
    {
        ob_end_clean();
        ob_start();
        header('Content-Type:text/html;charset=utf-8');
        echo '<!DOCTYPE html>',
             '<html lang="zh">',
                '<head><title>关于我们</title></head>',
                '<body>',
                json_decode(json_encode(model('Xdata')->get('admin_Application:about')), false)->about,
                '</body>',
             '</html>';
        ob_end_flush();
        exit;
    }

    /**
     * 发现.
     *
     * @return array
     *
     * @author hhh <missu082500@163.com>
     **/
    public function discover()
    {
        $open_arr = !empty($this->data['needs']) ? explode(',', t($this->data['needs'])) : array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10');
        $type = !empty($this->data['type']) ? t($this->data['type']) : 'system';
        $list = S('api_discover_'.$type);

        if (!$list) {
            $list = array();

            // 轮播图
            if (in_array('1', $open_arr)) {
                $banners = $this->getSlideShow();
                $list['banner'] = $banners ? $banners : array();
            }

            // 微吧
            if (in_array('2', $open_arr)) {
                $wmap['recommend'] = 1;
                $wmap['status'] = 1;
                $wmap['is_del'] = 0;
                $weiba_recommend = D('Weiba')->where($wmap)->limit(8)->findAll();

                $weiba_id = getSubByKey($weiba_recommend, 'weiba_id');
                $followStatus = api('Weiba')->getFollowStateByWeibaids($this->mid, $weiba_id);
                foreach ($weiba_recommend as $k => $v) {
                    $weiba_recommend[$k]['logo'] = getImageUrlByAttachId($v['logo'], 200, 200);
                    $weiba_recommend[$k]['following'] = $followStatus[$v['weiba_id']]['following'];
                    if ($v['new_day'] != date('Y-m-d', time())) {
                        $weiba_recommend[$k]['new_count'] = 0;
                        $this->setNewcount($v['weiba_id'], 0);
                    }
                    $weiba_recommend[$k]['title'] = formatEmoji(false, $weiba_recommend[$k]['title']);
                    $weiba_recommend[$k]['content'] = formatEmoji(false, $weiba_recommend[$k]['content']);
                }
                $list['weibas'] = $weiba_recommend ? $weiba_recommend : array();
            }

            // 话题
            if (in_array('3', $open_arr)) {
                $tmap['recommend'] = 1;
                $tmap['lock'] = 0;
                $topic_recommend = D('FeedTopic')->where($tmap)->order('count desc')->limit(8)->field('topic_id,topic_name,pic')->findAll();

                foreach ($topic_recommend as $key => $value) {
                    if ($value['pic'] != null) {
                        $topic_recommend[$key]['pic'] = getImageUrlByAttachId($value['pic'], 100, 100);
                    } else {
                        $topic_recommend[$key]['pic'] = '';
                    }
                }
                $list['topics'] = $topic_recommend ? $topic_recommend : array();
            }

            //频道
            if (in_array('4', $open_arr)) {
                $cmap['pid'] = 0;
                $channel_recommend = D('ChannelCategory')->where($cmap)->order('sort asc')->limit(8)->field('channel_category_id,title,ext')->findAll();

                foreach ($channel_recommend as $key => $value) {
                    $serialize = unserialize($value['ext']);
                    if ($serialize['attach'] != '') {
                        $channel_recommend[$key]['pic'] = getImageUrlByAttachId($serialize['attach'], 100, 100);
                    } else {
                        $channel_recommend[$key]['pic'] = '';
                    }
                    unset($channel_recommend[$key]['ext']);
                }
                $list['channels'] = $channel_recommend ? $channel_recommend : array();
            }

            //资讯
            if (in_array('5', $open_arr)) {
                $tconf = model('Xdata')->get('Information_Admin:config');
                $hotTime = intval($tconf['hotTime']);
                if ($hotTime > 0) {
                    $hotTime = 60 * 60 * 24 * $hotTime;
                    $hotTime = time() - $hotTime;
                    $imap['ctime'] = array('gt', intval($hotTime));
                }

                $imap['isPre'] = 0;
                $imap['isDel'] = 0;
                $information_recommend = D('InformationList')->where($imap)->order('hits desc')->limit(8)->field('id,subject,content')->findAll();

                foreach ($information_recommend as $key => $value) {
                    preg_match_all('/\<img(.*?)src\=\"(.*?)\"(.*?)\/?\>/is', $value['content'], $image);
                    $image = $image[2];
                    if ($image && is_array($image) && count($image) >= 1) {
                        $image = $image[array_rand($image)];
                        if (!preg_match('/https?\:\/\//is', $image)) {
                            $image = parse_url(SITE_URL, PHP_URL_SCHEME).'://'.parse_url(SITE_URL, PHP_URL_HOST).'/'.$image;
                        }
                    }
                    $information_recommend[$key]['pic'] = !empty($image) ? $image : '';
                    $information_recommend[$key]['url'] = sprintf('%s/api.php?mod=Information&act=reader&id=%d', SITE_URL, intval($value['id']));
                    unset($information_recommend[$key]['content']);
                }
                $list['information'] = $information_recommend ? $information_recommend : array();
            }

            //找人
            if (in_array('6', $open_arr)) {
                $user = model('RelatedUser')->getRelatedUser(8);
                $user_list = array();

                foreach ($user as $k => $v) {
                    $user_list[$k]['uid'] = $v['userInfo']['uid'];
                    $user_list[$k]['uname'] = $v['userInfo']['uname'];
                    $user_list[$k]['remark'] = $v['userInfo']['remark'];
                    $user_list[$k]['avatar'] = $v['userInfo']['avatar_big'];
                    $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['userInfo']['uid']);
                    $user_list[$k]['space_privacy'] = $privacy['space'];
                }
                $list['users'] = $user_list;
            }

            //附近的人
            if (in_array('7', $open_arr)) {
                $users = api('FindPeople')->around();

                foreach ($users['data'] as $key => $value) {
                    $findp['uid'] = $value['uid'];
                    $findp['uname'] = $value['username'];
                    $findp['remark'] = $value['remark'];
                    $findp['avatar'] = $value['avatar'];
                    $privacy = model('UserPrivacy')->getPrivacy($this->mid, $value['uid']);
                    $findp['space_privacy'] = $privacy['space'];
                    $fpeople[] = $findp;
                }
                $list['near_users'] = $fpeople ? $fpeople : array();
            }

            //积分商城
            if (in_array('8', $open_arr)) {
                $giftlogs = D('GiftLog')->field('`gid`')->group('`gid`')->order('COUNT(`gid`) DESC')->limit(8)->findAll();

                foreach ($giftlogs as $key => $value) {
                    $gift = D('Gift')->where(array('id' => $value['gid']))->field('id,name,image')->find();
                    $gift['image'] = getImageUrlByAttachId($gift['image']);
                    $gifts[] = $gift;
                }
                $list['gifts'] = $gifts ? $gifts : array();
            }

            S('api_discover_'.$type, $list, 3600);
        }

        //直播
        if (in_array('9', $open_arr)) {
            $lives_url = 'http://zbtest.zhibocloud.cn/stream/getList';
            $lives_rs = file_get_contents($lives_url);
            $lives_rs = json_decode($lives_rs, true);

            if ($lives_rs['data']) {
                foreach ($lives_rs['data'] as $key => $value) {
                    if ($key > 8) {
                        break;
                    }

                    //用户信息
                    $uid = D('live_user_info')->where(array('usid' => $value['user']['usid']))->getField('uid');
                    $userInfo = api('User')->get_user_info($uid);
                    $user_info['uid'] = (string) $userInfo['uid'];
                    $user_info['uname'] = $userInfo['uname'];
                    $user_info['sex'] = $userInfo['sex'];
                    $user_info['intro'] = $userInfo['intro'] ? formatEmoji(false, $userInfo['intro']) : '';
                    $user_info['location'] = $userInfo['location'] ? $userInfo['location'] : '';
                    $user_info['avatar'] = (object) array($userInfo['avatar']['avatar_big']);
                    $user_info['gold'] = intval($userInfo['user_credit']['credit']['score']['value']);
                    $user_info['fans_count'] = intval($userInfo['user_data']['follower_count']);
                    $user_info['is_verified'] = 0;
                    $user_info['usid'] = $value['user']['usid'];
                    $credit_mod = M('credit_user');
                    $credit = $credit_mod->where(array('uid' => $uid))->find();
                    $user_info['zan_count'] = $credit['zan_remain'];
                    $user_info['live_time'] = $credit['live_time'];
                    $res = model('Follow')->getFollowStateByFids($this->mid, intval($uid));
                    $user_info['is_follow'] = $res[$uid]['following'];
                    /* # 获取用户封面 */
                    $cover = D('user_data')->where('`key` LIKE "application_user_cover" AND `uid` = '.$v)->field('value')->getField('value');
                    $user_info['cover'] = $cover ? (object) array($cover) : (object) array();
                    $value['user'] = $user_info;

                    $icon = $value['stream']['icon'];
                    $value['stream']['icon'] = $icon ? (object) $icon : (object) array();
                    $lives[] = $value;
                }
                $list['lives'] = $lives;
            } else {
                $list['lives'] = array();
            }
        }

        //极铺商品
        if (in_array('10', $open_arr)) {
            $jipu_url = 'http://www.jipushop.com/Api/tsGoods';
            $goods_rs = file_get_contents($jipu_url);
            $goods_rs = json_decode($goods_rs, true);

            $list['jipu_goods'] = $goods_rs;
        }

        return $list;
    }
} // END class PublicApi extends Api
