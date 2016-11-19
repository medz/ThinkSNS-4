<?php
/**
 * 频道应用API接口
 * @author zivss guolee226@gmail.com
 * @version  TS3.0
 */
class ChannelApi extends Api
{
    /**
     * 获取所有频道分类 --using
     *
     * @param
     *        	integer max_id 上次返回的最后一条sort_id
     * @param
     *        	integer count 频道条数
     * @return json 所有频道分类
     */
    public function get_all_channel()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        ! empty($max_id) && $where = " sort > {$max_id}";
        $channels = D('channel_category')->where($where)->limit($count)->field('channel_category_id,title,sort')->order('sort ASC')->findAll();
        $channel_ids = getSubByKey($channels, 'channel_category_id');
        $map2 ['channel_category_id'] = array(
                'in',
                $channel_ids,
        );

        $map2 ['status'] = 1;
        $count_list = M('channel')->where($map2)->field('channel_category_id, count(feed_id) as num')->group('channel_category_id')->findAll();
        foreach ($count_list as $c) {
            $countArr [$c ['channel_category_id']] = intval($c ['num']);
        }

        unset($map2 ['status']);
        $channels = D('channel_category')->where($map2)->field('channel_category_id,title')->order('sort ASC')->findAll();
        if (! $channels) {
            return array();
        }

        //用户关注频道
        $map ['uid'] = empty($this->uid) ? $this->mid : $this->uid;
        $follow = M('channel_follow')->where($map)->findAll();
        $_channel_ids = getSubByKey($follow, 'channel_category_id');

        foreach ($channels as $k => $v) {
            if (in_array($v['channel_category_id'], $_channel_ids)) {
                $channels[$k]['is_follow'] = 1;
            } else {
                $channels[$k]['is_follow'] = 0;
            }
            $big_image = D('channel')->where('status=1 and channel_category_id='.$v ['channel_category_id'].' and width>20 and height>20')->max('feed_id');
            if ($big_image) {
                $feed_data = unserialize(D('feed_data')->where('feed_id='.$big_image)->getField('feed_data'));
                $big_image_info = model('Attach')->getAttachById($feed_data ['attach_id'] [0]);
                $channels [$k] ['image'] = getImageUrl($big_image_info ['save_path'].$big_image_info ['save_name'], 590, 245, true);
            } else {
                $channels [$k] ['image'] = SITE_URL.'/apps/channel/_static/image/api_small_1.png';
            }
            $channels [$k] ['count'] = $countArr [$v ['channel_category_id']];
        }

        return $channels;

        // if (! $channels)
        // 	return array ();
        // foreach ( $channels as $k => $v ) {
        // 	$big_image = D ( 'channel' )->where ( 'status=1 and channel_category_id=' . $v ['channel_category_id'] . ' and width>=590 and height>=245' )->max ( 'feed_id' );
        // 	if ($big_image) {
        // 		$feed_data = unserialize ( D ( 'feed_data' )->where ( 'feed_id=' . $big_image )->getField ( 'feed_data' ) );
        // 		$big_image_info = model ( 'Attach' )->getAttachById ( $feed_data ['attach_id'] [0] );
        // 		$channels [$k] ['image'] [0] = getImageUrl ( $big_image_info ['save_path'] . $big_image_info ['save_name'], 590, 245, true );
        // 		;
        // 	} else {
        // 		$channels [$k] ['image'] [0] = SITE_URL . '/apps/channel/_static/image/api_big.png';
        // 	}
        // 	$small_image = D ( 'channel' )->where ( 'status=1 and channel_category_id=' . $v ['channel_category_id'] . ' and width>=196 and width<590 and height>=156 and height<245' )->order ( 'feed_id desc' )->limit ( 3 )->findAll ();
        // 	if ($small_image [0]) {
        // 		$feed_data = unserialize ( D ( 'feed_data' )->where ( 'feed_id=' . $small_image [0] ['feed_id'] )->getField ( 'feed_data' ) );
        // 		$small_image_info_1 = model ( 'Attach' )->getAttachById ( $feed_data ['attach_id'] [0] );
        // 		$channels [$k] ['image'] [1] = getImageUrl ( $small_image_info_1 ['save_path'] . $small_image_info_1 ['save_name'], 196, 156, true );
        // 	} else {
        // 		$channels [$k] ['image'] [1] = SITE_URL . '/apps/channel/_static/image/api_small_1.png';
        // 	}
        // 	if ($small_image [1]) {
        // 		$feed_data = unserialize ( D ( 'feed_data' )->where ( 'feed_id=' . $small_image [1] ['feed_id'] )->getField ( 'feed_data' ) );
        // 		$small_image_info_2 = model ( 'Attach' )->getAttachById ( $feed_data ['attach_id'] [0] );
        // 		$channels [$k] ['image'] [2] = getImageUrl ( $small_image_info_2 ['save_path'] . $small_image_info_2 ['save_name'], 196, 156, true );
        // 	} else {
        // 		$channels [$k] ['image'] [2] = SITE_URL . '/apps/channel/_static/image/api_small_2.png';
        // 	}
        // 	if ($small_image [2]) {
        // 		$feed_data = unserialize ( D ( 'feed_data' )->where ( 'feed_id=' . $small_image [2] ['feed_id'] )->getField ( 'feed_data' ) );
        // 		$small_image_info_3 = model ( 'Attach' )->getAttachById ( $feed_data ['attach_id'] [0] );
        // 		$channels [$k] ['image'] [3] = getImageUrl ( $small_image_info_3 ['save_path'] . $small_image_info_3 ['save_name'], 196, 156, true );
        // 	} else {
        // 		$channels [$k] ['image'] [3] = SITE_URL . '/apps/channel/_static/image/api_small_3.png';
        // 	}
        // 	$channels [$k] ['is_follow'] = intval ( D ( 'ChannelFollow', 'channel' )->getFollowStatus ( $this->mid, $v ['channel_category_id'] ) );
        // }
        // // 关注的放后面
        // foreach ( $channels as $v ) {
        // 	$arr [$v ['is_follow']] [] = $v;
        // }
        // $channels = array_merge ( ( array ) $arr [0], ( array ) $arr [1] );
        // return $channels;
    }
    public function get_user_channel()
    {
        $map ['uid'] = empty($this->uid) ? $this->mid : $this->uid;
        $follow = M('channel_follow')->where($map)->findAll();
        if (empty($follow)) {
            return array();
        }

        $channel_ids = getSubByKey($follow, 'channel_category_id');
        $map2 ['channel_category_id'] = array(
                'in',
                $channel_ids,
        );
        $map2 ['status'] = 1;
        $count_list = M('channel')->where($map2)->field('channel_category_id, count(feed_id) as num')->group('channel_category_id')->findAll();
        foreach ($count_list as $c) {
            $countArr [$c ['channel_category_id']] = intval($c ['num']);
        }

        unset($map2 ['status']);
        $channels = D('channel_category')->where($map2)->field('channel_category_id,title')->order('sort ASC')->findAll();
        if (! $channels) {
            return array();
        }
        foreach ($channels as $k => $v) {
            $big_image = D('channel')->where('status=1 and channel_category_id='.$v ['channel_category_id'].' and width>20 and height>20')->max('feed_id');
            if ($big_image) {
                $feed_data = unserialize(D('feed_data')->where('feed_id='.$big_image)->getField('feed_data'));
                $big_image_info = model('Attach')->getAttachById($feed_data ['attach_id'] [0]);
                $channels [$k] ['image'] = getImageUrl($big_image_info ['save_path'].$big_image_info ['save_name'], 590, 245, true);
            } else {
                $channels [$k] ['image'] = SITE_URL.'/apps/channel/_static/image/api_small_1.png';
            }
            $channels [$k] ['count'] = $countArr [$v ['channel_category_id']];
        }

        return $channels;
    }

    /**
     * 获取频道分类下的微博 --using
     *
     * @param
     *        	integer channel_category_id 频道分类ID
     * @param
     *        	integer max_id 上次返回的最后一条feed_id
     * @param
     *        	integer count 微博条数
     * @param
     *        	integer type 微博类型 0-全部 1-原创 2-转发 3-图片 4-附件 5-视频
     * @return json 指定分类下的微博
     */
    public function channel_detail()
    {
        $cid = intval($this->data ['channel_category_id']);
        if (! $cid) {
            return array(
                    'status' => 0,
                    'msg' => '请选择频道',
            );
        }
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        //频道信息
        // if(!empty($max_id)){
        // $channel_detail = D('channel_category')->where('channel_category_id='.$cid)->field('channel_category_id,title')->find();
        // }else{
        // $channel_detail = array();
        // }

        /* 频道信息 */
        $channel_detail = S('api_channel_info_'.$cid);
        if (!$channel_detail) {
            $channel_detail = D('channel_category')->where(array('channel_category_id' => array('eq', $cid)))
              ->find();
            $channel_detail = array_merge($channel_detail, unserialize($channel_detail['ext']));
            unset($channel_detail['ext']);
            $channel_detail['banner'] = '';
            if ($channel_detail['attach']) {
                $channel_detail['banner'] = getImageUrlByAttachId($channel_detail['attach']);
            }
            S('api_channel_info_'.$cid, $channel_detail);
        }

        // 频道下的微博
        $where = 'c.status = 1';
        $where .= ' AND c.channel_category_id ='.$cid;
        ! empty($max_id) && $where .= " AND c.feed_id < {$max_id}";
        $type = $this->data ['type'];
        if (in_array($type, array(
                'postimage',
                'postfile',
                'postvideo',
        ))) {
            $where .= " AND f.type='{$type}' ";
        } elseif ($type == 'post') {
            $where .= ' AND f.is_repost=0';
        } elseif ($type == 'repost') {
            $where .= ' AND f.is_repost=1';
        }
        $order = 'c.feed_id DESC';
        $sql = 'SELECT distinct c.feed_id FROM `'.C('DB_PREFIX').'channel` c LEFT JOIN `'.C('DB_PREFIX').'feed` f ON c.feed_id = f.feed_id WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$count.'';
        $feed_ids = getSubByKey(D()->query($sql), 'feed_id');
        $channel_detail ['feed_list'] = api('Weibo')->format_feed($feed_ids);

        return $channel_detail;
    }

    /**
     * 频道关注或取消关注 --using
     *
     * @param  int           $channel_category_id
     *                                            频道分类ID
     * @param  int           $type
     *                                            1-关注 0-取消关注
     * @return 状态+提示
     */
    public function channel_follow()
    {
        $cids = t($this->data ['channel_category_id']);
        $cids = explode(',', $cids);

        $type = intval($this->data ['type']);
        if ($type == 1) { // 加关注
            $action = 'add';
            $info = '关注';
        } else {
            $action = 'del';
            $info = '取消关注';
        }
        foreach ($cids as $cid) {
            $res = D('ChannelFollow', 'channel')->upFollow($this->mid, $cid, $action);
        }
        if ($res) {
            $data ['status'] = 1;
            $data ['msg'] = $info.'成功';
        } else {
            $data ['status'] = 0;
            $data ['msg'] = $info.'失败';
        }

        return $data;
    }
}
