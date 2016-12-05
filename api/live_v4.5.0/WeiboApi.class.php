<?php

// 微博Api接口V2
class WeiboApi extends Api
{
    /**
     * ******** 微博首页列表API *********.
     */

    /**
     * 获取全站最新发布微博 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条微博ID
     * @param
     *        	integer count 微博条数
     * @param
     *        	varchar type 微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 微博列表
     */
    public function public_timeline()
    {
        // return $this->mid;
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $where = 'is_del=0 and is_audit=1';
        // 动态类型
        $type = $this->data['type'];
        if (in_array($type, array(
                'postimage',
                // 'postfile',
                'postvideo',
        ))) {
            $where .= " AND type='{$type}' ";
        } elseif ($type == 'post') {
            $where .= ' AND is_repost=0';
        } elseif ($type == 'repost') {
            $where .= ' AND is_repost=1';
        }
        !empty($max_id) && $where .= " AND feed_id < {$max_id}";
        // $where .= " AND (app='public')";
        $where .= " AND (app='public' OR app='weiba')";
        $where .= " AND type != 'postfile'";
        $feed_ids = model('Feed')->where($where)->field('feed_id')->limit($count)->order('feed_id DESC')->getAsFieldArray('feed_id');

        return $this->format_feed($feed_ids);
    }

    /**
     * 获取当前用户所关注的用户发布的微博 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条微博ID
     * @param
     *        	integer count 微博条数
     * @param
     *        	varchar type 微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 微博列表
     */
    public function friends_timeline()
    {
        $tablePrefix = C('DB_PREFIX');
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $where = 'a.is_del=0 and a.is_audit=1';
        // 动态类型
        $type = $this->data['type'];
        if (in_array($type, array(
                'postimage',
                // 'postfile',
                'postvideo',
        ))) {
            $where .= " AND a.type='{$type}' ";
        } elseif ($type == 'post') {
            $where .= ' AND a.is_repost=0';
        } elseif ($type == 'repost') {
            $where .= ' AND a.is_repost=1';
        }
        $where .= " AND a.type != 'postfile'";
        $max_id && $where .= " AND a.feed_id < {$max_id}";
        $where .= " AND (a.app='public')";
        $table = "{$tablePrefix}feed AS a LEFT JOIN {$tablePrefix}user_follow AS b ON a.uid=b.fid AND b.uid = {$GLOBALS['ts']['mid']}";
        $where = "(a.uid = '{$GLOBALS['ts']['mid']}' OR b.uid = '{$GLOBALS['ts']['mid']}') AND ($where)"; // 加上自己的信息，若不需要此数据，请屏蔽下面语句
        $feed_ids = model('Feed')->where($where)->table($table)->field('a.feed_id')->limit($count)->order('a.feed_id DESC')->getAsFieldArray('feed_id');

        return $this->format_feed($feed_ids);
    }

    /**
     * 获取当前用户所关注频道分类下的微博 --using.
     *
     * @param
     *        	integer cid 频道ID(可选,0或null为全部)
     * @param
     *        	integer max_id 上次返回的最后一条微博ID
     * @param
     *        	integer count 微博条数
     * @param
     *        	varchar type 微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 指定频道分类下的微博列表
     */
    public function channels_timeline()
    {
        // 我关注的频道
        $list = D('ChannelFollow', 'channel')->getFollowList($GLOBALS['ts']['mid']);
        if (!$list) {
            return array();
        }
        $cids = getSubByKey($list, 'channel_category_id');

        $tablePrefix = C('DB_PREFIX');
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $cid = intval($this->data['cid']);
        $where = 'c.status = 1';
        if ($cid && in_array($cid, $cids)) {
            $where .= ' AND c.channel_category_id = '.intval($cid);
        } else {
            $where .= ' AND c.channel_category_id in ('.implode(',', $cids).')';
        }
        !empty($max_id) && $where .= " AND c.feed_id < {$max_id}";
        $type = $this->data['type'];
        if (in_array($type, array(
                'postimage',
                // 'postfile',
                'postvideo',
        ))) {
            $where .= " AND f.type='{$type}' ";
        } elseif ($type == 'post') {
            $where .= ' AND f.is_repost=0';
        } elseif ($type == 'repost') {
            $where .= ' AND f.is_repost=1';
        }
        $where .= " AND (f.app='public')";
        $where .= " AND f.type != 'postfile'";
        $order = 'c.feed_id DESC';
        $sql = 'SELECT distinct c.feed_id FROM `'.$tablePrefix.'channel` c LEFT JOIN `'.$tablePrefix.'feed` f ON c.feed_id = f.feed_id WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$count.'';
        $feed_ids = getSubByKey(D()->query($sql), 'feed_id');

        return $this->format_feed($feed_ids);
    }

    /**
     * 获取某个话题下的微博 --using.
     *
     * @param
     *        	varchar topic_name 话题名称
     * @param
     *        	integer max_id 上次返回的最后一条微博ID
     * @param
     *        	integer count 微博条数
     * @param
     *        	integer type 微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 话题详情
     */
    public function topic_timeline()
    {
        $topic_name = t($this->data['topic_name']);
        if (!$topic_name) {
            return array(
                    'status' => 0,
                    'msg'    => '话题名称不能为空',
            );
        }
        $weibo_list = array();
        $topic_detail = D('feed_topic')->where(array(
                'topic_name' => formatEmoji(true, $topic_name),
        ))->find();
        if (!$topic_detail) {
            return array(
                    'status' => 1,
                    'msg'    => '列表为空',
                    'data'   => $weibo_list,
            );
        }
        if ($topic_detail['lock'] == 1) {
            return array(
                    'status' => 0,
                    'msg'    => '该话题已屏蔽',
            );
        }

        $tablePrefix = C('DB_PREFIX');
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        $where = 'f.is_del = 0';
        if (!empty($topic_detail['top_feed'])) {
            $fids = array_filter(explode(',', $topic_detail['top_feed']));
            $map_test['feed_id'] = array(
                    'in',
                    $fids,
            );
            $test = M('feed')->where($map_test)->field('feed_id')->findAll();
            $fids = array();
            if (!empty($test)) {
                $fids = getSubByKey($test, 'feed_id');
            }
            empty($fids) || $where = 'f.feed_id not in ('.implode(',', $fids).') ';
        }

        $where .= ' AND t.topic_id = '.intval($topic_detail['topic_id']);

        !empty($max_id) && $where .= " AND t.feed_id < {$max_id}";
        $type = $this->data['type'];
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
        $where .= " AND (f.app='public')";
        $where .= " AND f.type != 'postfile'";
        $order = 't.feed_id DESC';
        $sql = 'SELECT t.feed_id FROM `'.$tablePrefix.'feed_topic_link` t LEFT JOIN `'.$tablePrefix.'feed` f ON t.feed_id = f.feed_id WHERE '.$where.' ORDER BY '.$order.' LIMIT '.$count.'';
        $feed_ids = getSubByKey(D()->query($sql), 'feed_id');
        if ($max_id == 0 && !empty($fids)) {
            $feed_ids = array_merge($fids, $feed_ids);
        }
        $feeds = $this->format_feed($feed_ids);
        foreach ($feeds as &$v) {
            if (in_array($v['feed_id'], $fids)) {
                $v['is_top'] = 1;
            } else {
                $v['is_top'] = 0;
            }
        }
        if ($max_id) {
            return array(
                    'status' => 1,
                    'msg'    => '列表',
                    'data'   => $feeds,
            );
        } else {
            $detail['topic_name'] = '#'.$topic_detail['topic_name'].'#';
            $detail['des'] = $topic_detail['des'] ? t($topic_detail['des']) : '';
            $detail['count'] = intval($topic_detail['count']);
            if ($topic_detail['pic']) {
                $attach = model('Attach')->getAttachById($topic_detail['pic']);
                $detail['pic'] = getImageUrl($attach['save_path'].$attach['save_name']);
            } else {
                $detail['pic'] = '';
            }
            // $detail['feeds'] = $feeds;
            return array(
                    'status' => 1,
                    'msg'    => '列表',
                    'detail' => $detail,
                    'data'   => $feeds,
            );
        }
    }

    /**
     * 获取推荐最新发布微博 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条微博ID
     * @param
     *        	integer count 微博条数
     *
     * @return array 微博列表
     */
    public function recommend_timeline()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        $where = 'is_del=0 and is_audit=1 and is_recommend=1';
        !empty($max_id) && $where .= " AND feed_id < {$max_id}";
        $where .= " AND type != 'postfile'";

        $lists = model('Feed')->getList($where, $count, 'recommend_time desc, feed_id desc');
        //$lists = D ( 'ChannelFollow', 'channel' )->getFollowingFeed ( $where, $count );

        $feed_ids = getSubByKey($lists['data'], 'feed_id');

        return $this->format_feed($feed_ids);
    }

    /**
     * 某条微博详细内容 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     *
     * @return array 微博详细信息
     */
    public function weibo_detail()
    {
        $feed_id = intval($this->data['feed_id']);
        $feed_info = model('Cache')->get('feed_info_api_'.$feed_id);
        if (!$feed_info) {
            $feed_info = $this->get_feed_info($feed_id);
            if ($feed_info['is_repost'] == 1) {
                $feed_info['source_info'] = $this->get_source_info($feed_info['app_name'], $feed_info['stable'], $feed_info['sid']);
            } else {
                $feed_info['source_info'] = array();
            }
            model('Cache')->set('feed_info_api_'.$feed_id, $feed_info);
        }
        // 用户信息
        $feed_info['user_info'] = $this->get_user_info($feed_info['uid']);
        // 赞、收藏
        $diggarr = model('FeedDigg')->checkIsDigg($feed_id, $this->mid);
        $feed_info['is_digg'] = $diggarr[$feed_id] ? 1 : 0;
        $feed_info['is_favorite'] = model('Collection')->where('uid='.$GLOBALS['ts']['mid'].' and source_id='.$feed_id)->count();
        if ($this->mid != $feed_info['uid']) {
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $feed_info['uid']);
            if ($privacy['comment_weibo'] == 1) {
                $feed_info['can_comment'] = 0;
            } else {
                $feed_info['can_comment'] = 1;
            }
        } else {
            $feed_info['can_comment'] = 1;
        }
        $feed_info['comment_info'] = $this->weibo_comments($feed_id, 10);
        $feed_info['digg_info'] = $this->weibo_diggs($feed_id);

        return $feed_info;
    }

    /**
     * 获取指定微博的评论列表 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     * @param
     *        	integer max_id 上次返回的最后一条评论ID
     * @param
     *        	integer count 评论条数
     *
     * @return array 评论列表
     */
    public function weibo_comments($feed_id, $count)
    {
        if (!$feed_id) {
            $feed_id = $this->data['feed_id'];
        }
        $comment_list = array();
        $where = 'is_del=0 and row_id='.$feed_id;
        if (!$count) {
            $count = $this->count;
            !empty($this->max_id) && $where .= " AND comment_id < {$this->max_id}";
        }
        $comments = model('Comment')->where($where)->order('comment_id DESC')->limit($count)->findAll();
        foreach ($comments as $v) {
            switch ($v['type']) {
                case '2':
                    $type = '转发了此微博';
                    break;
                case '3':
                    $type = '分享了此微博';
                    break;
                case '4':
                    $type = '赞了此微博';
                    break;
                default:
                    $type = '评论了此微博';
                    break;
            }
            $comment_info['type'] = $type;
            $comment_info['user_info'] = $this->get_user_info($v['uid']);
            $comment_info['comment_id'] = $v['comment_id'];
            $comment_info['content'] = parse_remark($v['content']);
            $comment_info['ctime'] = $v['ctime'];
            $comment_info['digg_count'] = $v['digg_count'];
            $diggarr = model('CommentDigg')->checkIsDigg($v['comment_id'], $GLOBALS['ts']['mid']);
            $comment_info['is_digg'] = t($diggarr[$v['comment_id']] ? 1 : 0);

            /* # 将评论里面的emoji解析 */
            $comment_info['content'] = formatEmoji(false, $comment_info['content']);

            $comment_list[] = $comment_info;
        }

        return $comment_list;
    }

    /**
     * 获取指定微博的赞过的人的列表 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     * @param
     *        	integer max_id 上次返回的最后一条赞的ID
     * @param
     *        	integer count 数量
     *
     * @return array 点赞的用户列表
     */
    public function weibo_diggs($feed_id, $count = 10)
    {
        if (!$feed_id) {
            $feed_id = $this->data['feed_id'];
        }
        $where = 'feed_id='.$feed_id;
        !empty($this->max_id) && $where .= " AND id < {$this->max_id}";
        $digg_list = model('FeedDigg')->where($where)->order('cTime DESC')->limit($count)->findAll();
        if (!$digg_list) {
            return array();
        }
        $follow_status = model('Follow')->getFollowStateByFids($this->mid, getSubByKey($digg_list, 'uid'));
        foreach ($digg_list as $k => $v) {
            $user_info = api('User')->get_user_info($v['uid']);
            $digg_list[$k]['remark'] = $user_info['remark'];
            $digg_list[$k]['uname'] = $user_info['uname'];
            $digg_list[$k]['intro'] = $user_info['intro'];
            $digg_list[$k]['avatar'] = $user_info['avatar']['avatar_big'];
            $digg_list[$k]['follow_status'] = $follow_status[$v['uid']];
            unset($digg_list[$k]['feed_id']);
        }

        return $digg_list;
    }

    /**
     * ******** 微博的操作API *********.
     */

    /**
     * 发布一条微博 --using.
     *
     * @param
     *        	string content 微博内容
     * @param float  $latitude
     *                          纬度
     * @param float  $longitude
     *                          经度
     * @param string $address
     *                          具体地址
     * @param
     *        	integer from 来源(2-android 3-iphone)
     * @param
     *        	string channel_category_id 频道ID(多个频道ID之间用逗号隔开)
     *
     * @return array 状态+提示/数据
     */
    public function post_weibo($datas)
    {
        if (!$datas) {
            if (!CheckPermission('core_normal', 'feed_post')) {
                return array(
                        'status' => 0,
                        'msg'    => '您没有权限',
                );
            }
        } else {
            $this->data['type'] = $datas['type'];
        }

        //检测用户是否被禁言
        if ($isDisabled = model('DisableUser')->isDisableUser($this->mid, 'post')) {
            return array(
                'status' => 0,
                'msg'    => '您已经被禁言了',
            );
        }

        $data['uid'] = $this->mid;
        $data['body'] = $this->data['content'];

        /* 格式化emoji */
        $data['body'] = formatEmoji(true, $data['body']);

        if (trim($data['body']) == '') {
            return array(
                    'status' => 0,
                    'msg'    => '内容不能为空',
            );
        }
        $data['type'] = isset($this->data['type']) ? $this->data['type'] : 'post';
        $data['app'] = 'public';
        $data['app_row_id'] = '0';
        $data['from'] = $this->data['from'] ? intval($this->data['from']) : '0';
        $data['publish_time'] = time();
        // $data ['latitude'] = floatval ( $this->data ['latitude'] );
        // $data ['longitude'] = floatval ( $this->data ['longitude'] );
        $data['address'] = t($this->data['address']);

        /* 经纬度 */
        $data['latitude'] = t($this->data['latitude']);
        $data['longitude'] = t($this->data['longitude']);

        $feed_id = model('Feed')->data($data)->add();

        // 附件处理
        if (isset($datas['attach_id'])) { // 图片类型
            $attach_id = $datas['attach_id'];
            array_map('intval', $attach_id);
            $data['attach_id'] = $attach_id;
        }
        if (isset($datas['video_id'])) { // 视频类型
            D('video')->where('video_id='.$datas['video_id'])->setField('feed_id', $feed_id);
            // 如果需要转码
            if (D('video_transfer')->where('video_id='.$datas['video_id'])->count()) {
                D('video_transfer')->where('video_id='.$datas['video_id'])->setField('feed_id', $feed_id);
            }
            $data = array_merge($data, $datas);
        }

        $feed_data = D('FeedData')->data(array(
                'feed_id'      => $feed_id,
                'feed_data'    => serialize($data),
                'client_ip'    => get_client_ip(),
                'feed_content' => $data['body'],
        ))->add();

        if ($feed_id && $feed_data) {
            /* 更新图片信息 */
            if (isset($datas['attach_id'])) {
                model('Attach')->where(array('attach_id' => array('in', $datas['attach_id'])))->save(array(
                    'app_name' => 'public',
                    'table'    => 'feed',
                    'row_id'   => $feed_id,
                ));
            }

            // 更新最近@的人
            model('Atme')->updateRecentAtForApi($data['body'], $feed_id);
            // 加积分
            model('Credit')->setUserCredit($this->mid, 'add_weibo');
            // Feed数
            model('UserData')->setUid($this->mid)->updateKey('feed_count', 1);
            model('UserData')->setUid($this->mid)->updateKey('weibo_count', 1);
            // 添加到话题
            model('FeedTopic')->addTopic(html_entity_decode($data['body'], ENT_QUOTES, 'UTF-8'), $feed_id, $data['type']);
            // 添加到频道
            $isOpenChannel = model('App')->isAppNameOpen('channel');
            if (!$isOpenChannel) {
                return array(
                        'status'  => 1,
                        'msg'     => '发布成功',
                        'feed_id' => $feed_id,
                );
            }
            // 添加微博到频道中
            $channelId = t($this->data['channel_category_id']);
            // 判断是否有频道绑定该用户
            $bindUserChannel = D('Channel', 'channel')->getCategoryByUserBind($this->mid);
            if (!empty($bindUserChannel)) {
                $channelId = array_merge($bindUserChannel, explode(',', $channelId));
                $channelId = array_filter($channelId);
                $channelId = array_unique($channelId);
                $channelId = implode(',', $channelId);
            }
            // 判断是否有频道绑定该话题
            $content = html_entity_decode($this->data['content'], ENT_QUOTES, 'UTF-8');
            $content = str_replace('＃', '#', $content);
            preg_match_all("/#([^#]*[^#^\s][^#]*)#/is", $content, $topics);
            $topics = array_unique($topics[1]);
            foreach ($topics as &$topic) {
                $topic = trim(preg_replace('/#/', '', t($topic)));
            }
            $bindTopicChannel = D('Channel', 'channel')->getCategoryByTopicBind($topics);
            if (!empty($bindTopicChannel)) {
                $channelId = array_merge($bindTopicChannel, explode(',', $channelId));
                $channelId = array_filter($channelId);
                $channelId = array_unique($channelId);
                $channelId = implode(',', $channelId);
            }
            if (!empty($channelId)) {
                // 获取后台配置数据
                $channelConf = model('Xdata')->get('channel_Admin:index');
                // 添加频道数据
                D('Channel', 'channel')->setChannel($feed_id, $channelId, false);
            }

            return array(
                    'status'           => 1,
                    'msg'              => '发布成功',
                    'feed_id'          => $feed_id,
                    'is_audit_channel' => intval($channelConf['is_audit']),
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '发布失败',
            );
        }
    }

    /**
     * 发布图片微博 --using.
     *
     * @param file $_FILE
     *                    图片
     * @param
     *        	string content 微博内容
     * @param float  $latitude
     *                          纬度
     * @param float  $longitude
     *                          经度
     * @param string $address
     *                          具体地址
     * @param
     *        	integer from 来源(2-android 3-iphone)
     * @param
     *        	string channel_id 频道ID(多个频道ID之间用逗号隔开)
     *
     * @return array 状态+提示/数据
     */
    public function upload_photo()
    {
        $d['attach_type'] = 'feed_image';
        $d['upload_type'] = 'image';
        $GLOBALS['fromMobile'] = true;
        $info = model('Attach')->upload($d, $d);
        $data = $this->data;
        if ($info['status']) {
            $data['type'] = 'postimage';
            $data['attach_id'] = getSubByKey($info['info'], 'attach_id');

            return $this->post_weibo($data);
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '发布失败',
            );
        }
    }

    /**
     * 发布视频微博 --using.
     *
     * @param file $_FILE
     *                    视频
     * @param
     *        	string content 微博内容
     * @param float  $latitude
     *                          纬度
     * @param float  $longitude
     *                          经度
     * @param string $address
     *                          具体地址
     * @param
     *        	integer from 来源(2-android 3-iphone)
     * @param
     *        	string channel_id 频道ID(多个频道ID之间用逗号隔开)
     *
     * @return array 状态+提示/数据
     */
    public function upload_video()
    {
        // return $_FILES;

        // dump($_REQUEST);exit;
        $info = model('Video')->upload($this->data['from'], $this->data['timeline']);
        if ($info['status']) {
            $data['type'] = 'postvideo';
            $data['video_id'] = intval($info['video_id']);
            $data['video_path'] = t($info['video_path']);
            $data['video_mobile_path'] = t($info['video_mobile_path']);
            $data['video_part_path'] = t($info['video_part_path']);
            $data['image_path'] = t($info['image_path']);
            $data['image_width'] = intval($info['image_width']);
            $data['image_height'] = intval($info['image_height']);
            $data['video_id'] = intval($info['video_id']);
            $data['from'] = intval($this->data['from']);

            return $this->post_weibo($data);
        } else {
            return $info;
        }
    }

    /**
     * 删除一条微博 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     *
     * @return array 状态+提示
     */
    public function del_weibo()
    {
        $feed_id = intval($this->data['feed_id']);
        $feed_mod = model('Feed');
        $feed_info = $feed_mod->get($feed_id);
        $return = model('Feed')->doEditFeed($feed_id, 'delFeed', '', $this->mid);
        // 删除话题相关信息
        $return['status'] == 1 && model('FeedTopic')->deleteWeiboJoinTopic($feed_id);
        // 删除频道关联信息
        D('Channel', 'channel')->deleteChannelLink($feed_id);
        // 删除@信息
        model('Atme')->setAppName('Public')->setAppTable('feed')->deleteAtme(null, $feed_id, null);
        // 删除收藏信息
        model('Collection')->delCollection($feed_id, 'feed');
        if ($feed_info['type'] == 'weiba_post' && $feed_info['app_row_id']) {
            $map['post_id'] = $feed_info['app_row_id'];
            $data['is_del'] = 1;
            M('weiba_post')->where($map)->data($data)->save();
            M('weiba_reply')->where($map)->data($data)->save();
            model('Comment')->where(array('row_id' => $feed_id))->data($data)->save();
        }
        if ($return['status'] == 1) {
            return array(
                    'status' => 1,
                    'msg'    => '删除成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '删除失败',
            );
        }
    }

    /**
     * 转发一条微博 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     * @param
     *        	string content 转发内容
     * @param float  $latitude
     *                          纬度
     * @param float  $longitude
     *                          经度
     * @param string $address
     *                          具体地址
     * @param
     *        	integer from 来源(2-android 3-iPhone)
     *
     * @return array 状态+提示
     */
    public function repost_weibo()
    {
        if (!CheckPermission('core_normal', 'feed_post')) {
            return array(
                    'status' => 0,
                    'msg'    => '您没有权限',
            );
        }
        if (!t($this->data['content'])) {
            return array(
                    'status' => 0,
                    'msg'    => '转发内容不能为空',
            );
        }
        $feed_detail = model('Feed')->where('feed_id='.intval($this->data['feed_id']))->field('app,app_row_table,app_row_id')->find();
        $p['app_name'] = isset($feed_detail['app']) ? $feed_detail['app'] : 'public';
        $p['type'] = isset($feed_detail['app_row_table']) ? $feed_detail['app_row_table'] : 'feed';
        $p['sid'] = $feed_detail['app_row_id'] ? intval($feed_detail['app_row_id']) : intval($this->data['feed_id']);
        $p['curid'] = intval($this->data['feed_id']);
        $p['body'] = $this->data['content'];
        $p['from'] = $this->data['from'] ? intval($this->data['from']) : '0';
        $p['curtable'] = 'feed';
        $p['forApi'] = true;
        $p['content'] = '';
        $p['latitude'] = floatval($this->data['latitude']);
        $p['longitude'] = floatval($this->data['longitude']);
        $p['address'] = t($this->data['address']);

        /* # 将emoji编码 */
        $p['body'] = formatEmoji(true, $p['body']);

        $return = model('Share')->shareFeed($p, 'share');
        if ($return['status'] == 1) {
            // 添加积分
            model('Credit')->setUserCredit($this->mid, 'forward_weibo');

            return array(
                    'status'  => 1,
                    'msg'     => '转发成功',
                    'feed_id' => $return['data']['feed_id'],
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '转发失败',
            );
        }
    }

    /**
     * 评论一条微博 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     * @param
     *        	integer to_comment_id 评论ID
     * @param
     *        	string content 评论内容
     * @param
     *        	integer from 来源(2-android 3-iPhone)
     *
     * @return array 状态+提示
     */
    public function comment_weibo()
    {
        if (!CheckPermission('core_normal', 'feed_comment')) {
            return array(
                    'status' => 0,
                    'msg'    => '您没有权限',
            );
        }
        if (!t($this->data['content'])) {
            return array(
                    'status' => 0,
                    'msg'    => '评论内容不能为空',
            );
        }
        //检测用户是否被禁言
        if ($isDisabled = model('DisableUser')->isDisableUser($this->mid, 'post')) {
            return array(
                'status' => 0,
                'msg'    => '您已经被禁言了',
            );
        }
        $feed_detail = model('Feed')->where('feed_id='.intval($this->data['feed_id']))->find();
        $data['type'] = 1;
        $data['app'] = $feed_detail['app'];
        $data['table'] = 'feed';
        $data['row_id'] = intval($this->data['feed_id']);
        $data['app_uid'] = $feed_detail['uid'];
        $data['content'] = $this->data['content'];
        // $data ['from'] = 'feed';
        /* # 将emoji编码 */
        $data['content'] = formatEmoji(true, $data['content']);
        if ($this->data['to_comment_id']) {
            $data['to_comment_id'] = intval($this->data['to_comment_id']);
            $data['to_uid'] = model('Comment')->where('comment_id='.intval($this->data['to_comment_id']))->getField('uid');
        }
        if (($data['comment_id'] = model('Comment')->addComment($data, true))) {
            //如果回复的源为微吧，同步评论到相应的帖子
            if ($data['app'] == 'weiba') {
                $weiba_post_detail = M('weiba_post')->where(array('post_id' => $feed_detail['app_row_id']))->find();

                $wr_data['weiba_id'] = intval($weiba_post_detail['weiba_id']);
                $wr_data['post_id'] = intval($weiba_post_detail['post_id']);
                $wr_data['post_uid'] = intval($weiba_post_detail['post_uid']);

                if (!empty($this->data['to_comment_id'])) {
                    $wr_data['to_reply_id'] = intval($this->data['to_comment_id']);
                    $wr_data['to_uid'] = model('Comment')->where('comment_id='.intval($this->data['to_comment_id']))->getField('uid');
                }

                $wr_data['uid'] = $this->mid;
                $wr_data['ctime'] = time();
                $wr_data['content'] = $data['content'];

                $filterContentStatus = filter_words($wr_data['content']);
                if (!$filterContentStatus['status']) {
                    return array(
                            'status' => 0,
                            'msg'    => $filterContentStatus['data'],
                    );
                }
                $wr_data['content'] = $filterContentStatus['data'];
                $wr_data['reply_id'] = $data['comment_id'];

                D('weiba_reply')->add($wr_data);

                $wp_up['last_reply_uid'] = $this->mid;
                $wp_up['last_reply_time'] = $wr_data['ctime'];
                $wp_up['reply_count'] = array(
                        'exp',
                        'reply_count+1',
                );
                $wp_up['reply_all_count'] = array(
                        'exp',
                        'reply_all_count+1',
                );
                D('weiba_post', 'weiba')->where('post_id = '.$feed_detail['app_row_id'])->save($wp_up);
            }

            return array(
                    'status' => 1,
                    'msg'    => '评论成功',
                    'cid'    => $data['comment_id'],
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '评论失败',
            );
        }
    }

    /**
     * 删除微博评论.
     *
     * @return array
     *
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function delComment()
    {
        $cid = intval($this->data['commentid']);

        /*
         * 验证是否传入了参数是否合法
         */
        if (!$cid or !$this->mid) {
            return array(
                'status'  => 0,
                'message' => '传入的参数不合法',
            );

        /*
         * 判断是否删除成功
         */
        } elseif (model('Comment')->deleteComment(array($cid), $this->mid)) {
            return array(
                'status'  => 1,
                'message' => '删除成功',
            );
        }

        return array(
            'status'  => -1,
            'message' => '删除失败',
        );
    }

    /**
     * 赞某条微博 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     *
     * @return array 状态+提示
     */
    public function digg_weibo()
    {
        $feed_id = intval($this->data['feed_id']);
        $res = model('FeedDigg')->addDigg($feed_id, $this->mid);
        if ($res) {
            return array(
                    'status' => 1,
                    'msg'    => '操作成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '操作失败',
            );
        }
    }

    /**
     * 取消赞某条微博 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     *
     * @return array 状态+提示
     */
    public function undigg_weibo()
    {
        $feed_id = intval($this->data['feed_id']);
        $res = model('FeedDigg')->delDigg($feed_id, $this->mid);
        if ($res) {
            return array(
                    'status' => 1,
                    'msg'    => '操作成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '操作失败',
            );
        }
    }

    /**
     * 赞某条评论 --using.
     *
     * @param
     *        	integer comment_id 评论ID
     *
     * @return array 状态+提示
     */
    public function digg_comment()
    {
        $comment_id = intval($this->data['comment_id']);
        $res = model('CommentDigg')->addDigg($comment_id, $this->mid);
        if ($res) {
            return array(
                    'status' => 1,
                    'msg'    => '操作成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '操作失败',
            );
        }
    }

    /**
     * 取消赞某条评论 --using.
     *
     * @param
     *        	integer comment_id 评论ID
     *
     * @return array 状态+提示
     */
    public function undigg_comment()
    {
        $comment_id = intval($this->data['comment_id']);
        $res = model('CommentDigg')->delDigg($comment_id, $this->mid);
        if ($res) {
            return array(
                    'status' => 1,
                    'msg'    => '操作成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '操作失败',
            );
        }
    }

    /**
     * 收藏一条资源 --using.
     *
     * @param
     *        	integer feed_id 资源ID
     *
     * @return array 状态+提示
     */
    public function favorite_weibo()
    {
        $data['source_table_name'] = 'feed'; // feed
        $data['source_id'] = $this->data['feed_id']; // 140
        $data['source_app'] = 'public'; // public

        if (model('Collection')->addCollection($data)) {
            return array(
                    'status' => 1,
                    'msg'    => '收藏成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '收藏失败',
            );
        }
    }

    /**
     * 取消收藏 --using.
     *
     * @param
     *        	integer feed_id 资源ID
     *
     * @return array 状态+提示
     */
    public function unfavorite_weibo()
    {
        if (model('Collection')->delCollection($this->data['feed_id'], 'feed')) {
            return array(
                    'status' => 1,
                    'msg'    => '取消收藏成功',
            );
        } else {
            return array(
                    'status' => 0,
                    'msg'    => '取消收藏失败',
            );
        }
    }

    /**
     * 举报一条微博 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     * @param
     *        	varchar reason 举报原因
     * @param
     *        	integer from 来源(2-android 3-iphone)
     *
     * @return array 状态+提示
     */
    public function denounce_weibo()
    {
        $feed_id = intval($this->data['feed_id']);
        $feed_uid = model('Feed')->where('is_del=0 and feed_id='.$feed_id)->getField('uid');
        if (!$feed_uid) {
            return array(
                    'status' => 0,
                    'msg'    => '内容已被删除，举报失败',
            );
        }

        if ($this->data['from'] == 2) {
            $data['from'] = 'Android';
        } elseif ($this->data['from'] == 3) {
            $data['from'] = 'iPhone';
        } else {
            $data['from'] = 'mobile';
        }
        $data['aid'] = $feed_id;
        $data['uid'] = $this->mid;
        $data['fuid'] = $feed_uid;
        if ($isDenounce = model('Denounce')->where($data)->count()) {
            return array(
                    'status' => 0,
                    'msg'    => L('PUBLIC_REPORTING_INFO'),
            );
        } else {
            $data['content'] = D('feed_data')->where('feed_id='.$feed_id)->getField('feed_content');
            $data['reason'] = t($this->data['reason']);
            $data['source_url'] = '[SITE_URL]/index.php?app=public&mod=Profile&act=feed&feed_id='.$feed_id;
            $data['ctime'] = time();
            if ($id = model('Denounce')->add($data)) {
                // 添加积分
                // model('Credit')->setUserCredit($this->mid, 'report_weibo');
                // model('Credit')->setUserCredit($feed_uid, 'reported_weibo');

                $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
                foreach ($touid as $k => $v) {
                    model('Notify')->sendNotify($v['uid'], 'denouce_audit');
                }

                return array(
                        'status' => 1,
                        'msg'    => '举报成功',
                );
            } else {
                return array(
                        'status' => 0,
                        'msg'    => L('PUBLIC_REPORT_ERROR'),
                );
            }
        }
    }

    /**
     * ******** 用户相关微博信息列表API *********.
     */

    /**
     * 用户发的微博 --using.
     *
     * @param int     $user_id
     *                         用户UID
     * @param varchar $uname
     *                         用户名
     * @param int     $max_id
     *                         上次返回的最后一条微博ID
     * @param int     $count
     *                         微博条数
     * @param int     $type
     *                         微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 微博列表
     */
    public function user_timeline()
    {
        if (empty($this->user_id) && empty($this->data['uname'])) {
            $uid = $this->mid;
        } else {
            if ($this->user_id) {
                $uid = intval($this->user_id);
            } else {
                $uid = model('User')->where(array(
                        'uname' => $this->data['uname'],
                ))->getField('uid');
            }
        }
        // echo $uid ;exit();
        $max_id = intval($this->max_id);
        $count = intval($this->count);
        $type = $this->data['type'];

        $where = "uid = '{$uid}' AND is_del = 0 AND app='public' ";
        if (in_array($type, array(
                'postimage',
                'postfile',
                'postvideo',
        ))) {
            $where .= " AND type='{$type}' ";
        } elseif ($type == 'post') {
            $where .= ' AND is_repost=0';
        } elseif ($type == 'repost') {
            $where .= ' AND is_repost=1';
        }
        !empty($max_id) && $where .= " AND feed_id < {$max_id}";
        $feed_ids = model('Feed')->where($where)->field('feed_id')->limit($count)->order('feed_id DESC')->getAsFieldArray('feed_id');

        return $this->format_feed($feed_ids);
    }

    /**
     * 用户收藏的微博 --using.
     *
     * @param
     *        	integer user_id 用户UID
     * @param
     *        	integer max_id 上次返回的最后一条收藏ID
     * @param
     *        	integer count 微博条数
     * @param
     *        	integer type 微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 微博列表
     */
    public function user_collections()
    {
        $user_id = $this->user_id ? intval($this->user_id) : $this->mid;
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $type = t($this->data['type']);

        $map['c.uid'] = $user_id;
        // $map ['f.app'] = 'public';
        $map['f.app'] = array('in', array("'public'", "'weiba'"));
        if (in_array($type, array(
                'postimage',
                'postfile',
                'postvideo',
        ))) {
            $map['f.type'] = $type;
        } elseif ($type == 'post') {
            $map['f.is_repost'] = 0;
        } elseif ($type == 'repost') {
            $map['f.is_repost'] = 1;
        }
        !empty($max_id) && $map['c.collection_id'] = array(
                'lt',
                $max_id,
        );
        $list = D()->table('`'.C('DB_PREFIX').'feed` AS f LEFT JOIN `'.C('DB_PREFIX').'collection` AS c ON f.`feed_id` = c.`source_id`')->field('c.`source_id`,c.collection_id')->where($map)->order('c.collection_id DESC')->limit($count)->findAll();
        $collection_list = array();
        foreach ($list as $k => $v) {
            // 微博信息
            $feed_info = model('Cache')->get('feed_info_api_'.$v['source_id']);
            if ($feed_info) {
                $r[$k] = $feed_info;
            } else {
                $r[$k] = $this->get_feed_info($v['source_id']);
                if ($r[$k]['is_repost'] == 1) {
                    $r[$k]['source_info'] = $this->get_source_info($r[$k]['app_name'], $r[$k]['stable'], $r[$k]['sid']);
                } else {
                    $r[$k]['source_info'] = array();
                }
                model('Cache')->set('feed_info_api_'.$v['source_id'], $r[$k]);
            }
            // 赞、评论
            $diggarr = model('FeedDigg')->checkIsDigg($v['source_id'], $GLOBALS['ts']['mid']);
            $r[$k]['is_digg'] = t($diggarr[$v['source_id']] ? 1 : 0);
            $r[$k]['is_favorite'] = model('Collection')->where('uid='.$GLOBALS['ts']['mid'].' and source_id='.$v['source_id'])->count();
            if ($this->mid != $feed_info['uid']) {
                $privacy = model('UserPrivacy')->getPrivacy($this->mid, $feed_info['uid']);
                if ($privacy['comment_weibo'] == 1) {
                    $r[$k]['can_comment'] = 0;
                } else {
                    $r[$k]['can_comment'] = 1;
                }
            } else {
                $r[$k]['can_comment'] = 1;
            }
            // 用户信息
            $r[$k]['user_info'] = $this->get_user_info($r[$k]['uid']);
            // 评论
            $r[$k]['comment_info'] = $this->weibo_comments($v['source_id'], 4);
            // 收藏ID
            $r[$k]['collection_id'] = $v['collection_id'];
            $collection_list[] = $r[$k];
        }

        return $collection_list;
    }

    /**
     * ******** 搜索相关的接口API *********.
     */

    /**
     * 按关键字搜索微博 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条收藏ID
     * @param
     *        	integer count 微博条数
     * @param
     *        	varchar key 关键字
     * @param
     *        	integer type 微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 微博列表
     */
    public function weibo_search_weibo()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $key = $this->data['key'];
        $type = t($this->data['type']);

        $key = t(trim($key));
        $key = str_ireplace(array(
                '%',
                "'",
                '"',
        ), '', $key);
        if (empty($key)) {
            return array();
        }
        $map['a.is_del'] = 0;
        $map['a.is_audit'] = 1;
        !empty($max_id) && $map['a.feed_id'] = array(
                'lt',
                $max_id,
        );
        $map['b.feed_content'] = array(
                'LIKE',
                '%'.$key.'%',
        );
        if (in_array($type, array(
                'postimage',
                'postfile',
                'postvideo',
        ))) {
            $map['a.type'] = $type;
        } elseif ($type == 'post') {
            $map['a.is_repost'] = 0;
        } elseif ($type == 'repost') {
            $map['a.is_repost'] = 1;
        }
        $feed_ids = D()->table('`'.C('DB_PREFIX').'feed` AS a LEFT JOIN `'.C('DB_PREFIX').'feed_data` AS b ON a.`feed_id` = b.`feed_id`')->field('a.`feed_id`')->where($map)->order('a.`feed_id` DESC')->limit($count)->getAsFieldArray('feed_id');

        return $this->format_feed($feed_ids);
    }

    /**
     * 按话题搜索微博 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条收藏ID
     * @param
     *        	integer count 微博条数
     * @param
     *        	varchar key 关键字
     * @param
     *        	integer type 微博类型 'post','repost','postimage','postfile','postvideo'
     *
     * @return array 微博列表
     */
    public function weibo_search_topic()
    {
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $key = $this->data['key'];
        $type = t($this->data['type']);

        $key = t(trim($key));
        $key = trim($key, '#');
        $key = str_ireplace(array(
                '%',
                "'",
                '"',
        ), '', $key);
        if (empty($key)) {
            return array();
        }
        $map['a.is_del'] = 0;
        $map['a.is_audit'] = 1;
        !empty($max_id) && $map['a.feed_id'] = array(
                'lt',
                $max_id,
        );
        $map['b.feed_content'] = array(
                'LIKE',
                '%#'.$key.'#%',
        );
        if (in_array($type, array(
                'postimage',
                'postfile',
                'postvideo',
        ))) {
            $map['a.type'] = $type;
        } elseif ($type == 'post') {
            $map['a.is_repost'] = 0;
        } elseif ($type == 'repost') {
            $map['a.is_repost'] = 1;
        }
        $feed_ids = D()->table('`'.C('DB_PREFIX').'feed` AS a LEFT JOIN `'.C('DB_PREFIX').'feed_data` AS b ON a.`feed_id` = b.`feed_id`')->field('a.`feed_id`')->where($map)->order('a.`feed_id` DESC')->limit($count)->getAsFieldArray('feed_id');

        return $this->format_feed($feed_ids);
    }

    /**
     * 搜索@最近联系人 --using.
     *
     * @param
     *        	varchar key 关键字
     * @param
     *        	integer max_id 上次返回的最后一条用户UID
     * @param
     *        	integer count 用户条数
     *
     * @return array 用户列表
     */
    public function search_at()
    {
        $key = trim(t($this->data['key']));
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        $at_list = array();
        if (!$key) {
            if (!$max_id) {
                $map['uid'] = $this->mid;
                $map['key'] = 'user_recentat';
                $users = model('UserData')->where($map)->getField('value');
                $user_list = unserialize($users);
                if ($user_list) {
                    foreach ($user_list as $k => $v) {
                        $at_list[$k] = $v;
                        $intro = model('User')->where('uid='.$v['uid'])->getField('intro');
                        $at_list[$k]['intro'] = $intro ? formatEmoji(false, $intro) : '';
                        $at_list[$k]['avatar'] = $v['avatar_small'];
                    }
                }
            }
        } else {
            $uid_arr = model('User')->where(array(
                    'uname' => $key,
            ))->field('uid,uname,intro')->findAll(); // 先搜索和key一致的，优先显示
            if ($uid_arr) {
                $map['uid'] = array(
                        'neq',
                        $uid_arr[0]['uid'],
                );
                !empty($key) && $map['search_key'] = array(
                        'like',
                        '%'.$key.'%',
                );
                if (!$max_id) {
                    $user_list = model('User')->where($map)->field('uid,uname,intro')->order('uid desc')->limit($count - 1)->findAll();
                    $user_list = array_merge($uid_arr, $user_list);
                } else {
                    $map['uid'] = array(
                            array(
                                    'lt',
                                    $max_id,
                            ),
                            array(
                                    'neq',
                                    $uid_arr[0]['uid'],
                            ),
                            'AND',
                    );
                    $user_list = model('User')->where($map)->field('uid,uname,intro')->order('uid desc')->limit($count)->findAll();
                }
            } else {
                !empty($max_id) && $map['uid'] = array(
                        'lt',
                        $max_id,
                );
                !empty($key) && $map['search_key'] = array(
                        'like',
                        '%'.$key.'%',
                );
                $user_list = model('User')->where($map)->field('uid,uname,intro')->order('uid desc')->limit($count)->findAll();
            }
            if ($user_list) {
                foreach ($user_list as $k => $v) {
                    $at_list[$k]['uid'] = $v['uid'];
                    $at_list[$k]['uname'] = $v['uname'];
                    $at_list[$k]['remark'] = D('UserRemark')->getRemark($this->mid, $v['uid']);
                    $at_list[$k]['intro'] = $v['intro'] ? formatEmoji(false, $v['intro']) : '';
                    $avatar = model('Avatar')->init($v['uid'])->getUserAvatar();
                    $at_list[$k]['avatar'] = $avatar['avatar_small'];
                }
            }
        }

        return $at_list;
    }

    /**
     * 搜索话题 --using.
     *
     * @param
     *        	varchar key 关键字
     * @param
     *        	integer max_id 上次返回的最后一条话题ID
     * @param
     *        	integer count 话题条数
     *
     * @return array 话题列表
     */
    public function search_topic()
    {
        $key = formatEmoji(true, trim(t($this->data['key'])));
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;

        !empty($max_id) && $map['topic_id'] = array(
                'lt',
                $max_id,
        );
        !empty($key) && $map['topic_name'] = array(
                'like',
                '%'.$key.'%',
        );
        $map['lock'] = 0;
        $data = model('FeedTopic')->where($map)->field('topic_id,topic_name')->limit($count)->order('topic_id desc')->findAll();
        if ($data) {
            foreach ($data as &$v) {
                $v['topic_name'] = parseForApi($v['topic_name']);
            }

            return $data;
        } else {
            return array();
        }
    }

    /**
     * ******** 用户的相关微博--将合并 @我的、评论我的等等微博列表 *********.
     */

    /**
     * 提到用户的微博 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条atme_id
     * @param
     *        	integer count @条数
     *
     * @return array 提到我的列表
     */
    public function user_mentions()
    {
        model('UserData')->setKeyValue($this->mid, 'unread_atme', 0);
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $where = "uid = '{$this->mid}'";
        !empty($max_id) && $where .= " AND atme_id < {$max_id}";

        $list = D('atme')->where($where)->limit($count)->order('atme_id DESC')->findAll();
        $atme_arr = array();
        foreach ($list as $k => $v) {
            $atme['atme_id'] = $v['atme_id'];
            if ($v['table'] == 'comment') {
                $atme['atme_type'] = 'comment';
                $comment = D('comment')->where('comment_id='.$v['row_id'])->field('row_id,uid,content,ctime')->find();
                $atme['feed_id'] = $comment['row_id'];
                $atme['type'] = 'post';
                $atme['content'] = $comment['content'];
                $atme['ctime'] = $comment['ctime'];
                $atme['from'] = '来自网站';
                $atme['user_info'] = $this->get_user_info($comment['uid']);
                $atme['attach_info'] = array();
                $feed_info = $this->format_feed(array(
                        $comment['row_id'],
                ), 0);
                if (!$feed_info[0] || $feed_info[0]['is_del'] == 1) {
                    unset($atme);
                    continue;
                }
                $atme['feed_info'] = $feed_info[0];
            } else { // 微博
                $atme['atme_type'] = 'feed';
                $feed_info = $this->format_feed(array(
                        $v['row_id'],
                ), 0);
                if (!$feed_info[0] || $feed_info[0]['is_del'] == 1) {
                    unset($atme);
                    continue;
                }
                $atme['feed_id'] = $feed_info[0]['feed_id'];
                $atme['type'] = $feed_info[0]['type'];
                $atme['content'] = $feed_info[0]['content'];
                $atme['ctime'] = $feed_info[0]['publish_time'];
                $atme['from'] = $feed_info[0]['from'];
                $atme['user_info'] = $feed_info[0]['user_info'];
                $atme['attach_info'] = $feed_info[0]['attach_info'];
                $atme['feed_info'] = $feed_info[0]['source_info'];
            }
            $atme_arr[] = $atme;
            unset($atme);
        }

        return $atme_arr;
    }

    /**
     * 与我相关.
     *
     * @param
     *        	integer max_id 上次返回的最后一条atme_id
     * @param`
     *        	integer count @条数
     *
     * @return array 与我相关列表
     */
    public function user_related()
    {
        model('UserData')->setKeyValue($this->mid, 'unread_atme', 0);
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $where = "uid = '{$this->mid}'";
        !empty($max_id) && $where .= " AND row_id < {$max_id}";
        $list = D('atme')->where($where)->limit($count)->order('atme_id DESC')->findAll();

        foreach ($list as $k => $v) {
            if ($v['table'] == 'comment') {
                $comment = D('comment')->where('comment_id='.$v['row_id'])->field('row_id,uid,content,ctime')->find();
                $row_ids[] = $comment['row_id'];
            } else { // 微博
                $row_ids[] = $v['row_id'];
            }
        }
        $feed_info = $this->format_feed($row_ids);

        //剔除已删除数据
        foreach ($feed_info as $k => $v) {
            if (!$v['is_del']) {
                $_feed_info[] = $v;
            }
        }
        if (count($_feed_info) > 0) {
            return $_feed_info;
        } else {
            return array();
        }
    }

    /**
     * 获取当前用户收到的评论 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条comment_id
     * @param
     *        	integer count 评论条数
     *
     * @return array 评论列表
     */
    public function user_comments_to_me()
    {
        $where = " ( (app_uid = '{$this->mid}' or to_uid = '{$this->mid}') and uid != '{$this->mid}' )";
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $where .= ' AND is_del=0';
        if ($this->data['type'] == 'weiba_post') {
            $where .= ' AND app="weiba"';
            model('UserData')->setKeyValue($this->mid, 'unread_comment_weiba', 0);
        } else {
            $where .= ' AND app!="weiba"';
            model('UserData')->setKeyValue($this->mid, 'unread_comment', 0);
        }
        !empty($max_id) && $where .= " AND comment_id < {$max_id}";

        $list = model('Comment')->where($where)->order('comment_id DESC')->limit($count)->findAll();
        $comment_arr = array();
        foreach ($list as $k => $v) {
            $feed_info = $this->format_feed(array(
                    $v['row_id'],
            ), 0);
            if (!$feed_info[0] || $feed_info[0]['is_del'] == 1) {
                unset($comment);
                continue;
            }
            $comment['comment_id'] = $v['comment_id'];
            $comment['feed_id'] = $v['row_id'];
            $comment['type'] = 'post';
            $comment['content'] = formatEmoji(false, $v['content']);
            $comment['ctime'] = $v['ctime'];
            $comment['from'] = '来自网站';
            $comment['user_info'] = $this->get_user_info($v['uid']);
            $comment['attach_info'] = array();
            $comment['feed_info'] = $feed_info[0];

            $comment_arr[] = $comment;
            unset($comment);
        }

        return $comment_arr;
    }

    /**
     * 获取当前用户发出的评论 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条comment_id
     * @param
     *        	integer count 评论条数
     *
     * @return array 评论列表
     */
    public function user_comments_by_me()
    {
        $where = " uid = '{$this->mid}' ";
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $where .= ' AND is_del=0';
        !empty($max_id) && $where .= " AND comment_id < {$max_id}";

        $list = model('Comment')->where($where)->order('comment_id DESC')->limit($count)->findAll();
        $comment_arr = array();
        foreach ($list as $k => $v) {
            $feed_info = $this->format_feed(array(
                    $v['row_id'],
            ), 0);
            if (!$feed_info[0] || $feed_info[0]['is_del'] == 1) {
                unset($comment);
                continue;
            }
            $comment['comment_id'] = $v['comment_id'];
            $comment['feed_id'] = $v['row_id'];
            $comment['type'] = 'post';
            $comment['content'] = $v['content'];
            $comment['ctime'] = $v['ctime'];
            $comment['from'] = '来自网站';
            $comment['user_info'] = $this->get_user_info($v['uid']);
            $comment['attach_info'] = array();
            $comment['feed_info'] = $feed_info[0];

            $comment_arr[] = $comment;
            unset($comment);
        }

        return $comment_arr;
    }

    /**
     * 获取当前用户的收到的赞 --using.
     *
     * @param
     *        	integer max_id 上次返回的最后一条digg_id
     * @param
     *        	integer count 赞条数
     *
     * @return array 赞列表
     */
    public function user_diggs_to_me()
    {
        model('UserData')->setKeyValue($this->mid, 'unread_digg', 0);
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        $map['f.uid'] = $this->mid;
        $map['f.is_del'] = 0;
        !empty($max_id) && $map['d.id'] = array(
                'lt',
                $max_id,
        );
        $tablePrefix = C('DB_PREFIX');
        $list = D()->table("{$tablePrefix}feed AS f RIGHT JOIN {$tablePrefix}feed_digg AS d ON f.feed_id = d.feed_id ")->where($map)->order('d.id desc')->field('d.id as id,d.uid as uid,d.feed_id as feed_id,d.cTime as ctime')->limit($count)->findAll();
        $digg_arr = array();
        foreach ($list as $k => $v) {
            $digg['digg_id'] = $v['id'];
            $feed_info = $this->format_feed(array(
                    $v['feed_id'],
            ), 0);
            if (!$feed_info[0] || $feed_info[0]['is_del'] == 1) {
                unset($digg);
                continue;
            }
            $digg['feed_id'] = $v['feed_id'];
            $digg['type'] = 'post';
            $digg['content'] = '赞了这条微博';
            $digg['ctime'] = $v['ctime'];
            $digg['from'] = '来自网站';
            $digg['user_info'] = $this->get_user_info($v['uid']);
            $digg['attach_info'] = array();
            $digg['feed_info'] = $feed_info[0];

            $digg_arr[] = $digg;
            unset($digg);
        }

        return $digg_arr;
    }

    /**
     * ******** 其他公用操作API *********.
     */

    /**
     * 格式化手机端微博 --using.
     *
     * @param
     *        	array feed_ids 微博ID
     *
     * @return array 微博详细信息
     */
    public function format_feed($feed_ids, $show_comment = 1)
    {
        if (count($feed_ids) > 0) {
            $r = array();
            foreach ($feed_ids as $k => $v) {
                // 微博信息
                $feed_info = model('Cache')->get('feed_info_api_'.$v);
                if ($feed_info) {
                    //每个用户的备注信息不同
                    foreach ($feed_info['digg_users'] as $key => &$value) {
                        unset($value['remark']);
                        $value['remark'] = D('UserRemark')->getRemark($this->mid, $value['uid']);
                    }
                    $r[$k] = $feed_info;
                } else {
                    $r[$k] = $this->get_feed_info($v);
                    if (empty($r[$k])) {
                        unset($r[$k]);
                        continue;
                    } else {
                        if ($r[$k]['is_repost'] == 1) {
                            $r[$k]['source_info'] = $this->get_source_info($r[$k]['app_name'], $r[$k]['stable'], $r[$k]['sid']);
                            //转发内容为文件时，不显示
                            if ($r[$k]['source_info']['type'] == 'postfile') {
                                unset($r[$k]);
                                continue;
                            }
                        } else {
                            $r[$k]['source_info'] = array();
                        }
                        model('Cache')->set('feed_info_api_'.$v, $r[$k]);
                    }
                }
                // 用户信息
                $r[$k]['user_info'] = $this->get_user_info($r[$k]['uid']);
                // 赞、收藏
                $diggarr = model('FeedDigg')->checkIsDigg($v, $GLOBALS['ts']['mid']);
                $r[$k]['is_digg'] = $diggarr[$v] ? 1 : 0;
                $r[$k]['is_favorite'] = model('Collection')->where('uid='.$GLOBALS['ts']['mid'].' and source_id='.$v)->count();
                if ($this->mid != $feed_info['uid']) {
                    $privacy = model('UserPrivacy')->getPrivacy($this->mid, $feed_info['uid']);
                    if ($privacy['comment_weibo'] == 1) {
                        $r[$k]['can_comment'] = 0;
                    } else {
                        $r[$k]['can_comment'] = 1;
                    }
                } else {
                    $r[$k]['can_comment'] = 1;
                }
                // 评论
                if ($show_comment == 1) {
                    $r[$k]['comment_info'] = $this->weibo_comments($v, 4);
                }

                /* # 地址信息 */
                // $feed = model('Feed')->where('`feed_id` = ' . $v)->field('`latitude`, `longitude`, `address`')->find();
                // $feed['address'] or $feed['address'] = null;
                // $r[$k] = array_merge($r[$k], $feed);
                unset($feed);
            }

            return array_values($r);
        } else {
            return array();
        }
    }

    /**
     * 获取微博详情 --using.
     *
     * @param
     *        	integer feed_id 微博ID
     * @param
     *        	integer is_source 是否为原微博
     *
     * @return array 微博详细信息
     */
    public function get_feed_info($feed_id)
    {
        $tablePrefix = C('DB_PREFIX');
        // $map['a.is_del'] = 0;
        $map['a.feed_id'] = $feed_id;

        //20150704 手机端不显示文件
        $map['a.type'] = array('neq', 'postfile');

        $feed_info = array();
        $data = model('Feed')->where($map)->table("{$tablePrefix}feed AS a LEFT JOIN {$tablePrefix}feed_data AS b ON a.feed_id = b.feed_id ")->find();
        if (!$data) {
            return array();
        }
        if ($data['is_del'] == 0) {
            $feed_info['status'] = 'no';
            $feed_data = unserialize($data['feed_data']);
            // 微博信息
            $feed_info['feed_id'] = $data['feed_id'];
            $feed_info['uid'] = $data['uid'];
            $feed_info['type'] = $data['type'];
            $feed_info['app_name'] = $data['app'];
            $feed_info['stable'] = $data['app_row_table'];
            $feed_info['sid'] = $data['app_row_id'] ? $data['app_row_id'] : $data['feed_id'];
            $feed_info['is_repost'] = $data['is_repost'];
            $feed_info['publish_time'] = $data['publish_time'];

            /* # 地址信息 */
            $feed_info['latitude'] = $data['latitude'];
            $feed_info['longitude'] = $data['longitude'];
            $feed_info['address'] = $data['address'];
            $feed_info['address'] or $feed_info['address'] = null;

            if ($channel_category_id = D('channel')->where('feed_id='.$data['feed_id'])->getField('channel_category_id')) {
                $feed_info['channel_category_id'] = $channel_category_id;
                $channel_category_name = D('channel_category')->where('channel_category_id='.$channel_category_id)->getField('title');
                $feed_info['channel_category_name'] = $channel_category_name;
                $from = '来自'.$channel_category_name;
            } else {
                switch ($data['from']) {
                    case 1:
                        $from = '来自手机';
                        break;
                    case 2:
                        $from = '来自Android';
                        break;
                    case 3:
                        $from = '来自iPhone';
                        break;
                    case 4:
                        $from = '来自iPad';
                        break;
                    case 5:
                        $from = '来自Windows';
                        break;
                    case 6:
                        $from = '来自H5客户端';
                        break;
                    case 0:
                    default:
                        $from = '来自网站';
                        break;
                }
            }
            $feed_info['from'] = $from;
            if (in_array($data['type'], array(
                    'post',
                    'postimage',
                    'postfile',
                    'postvideo',
            )) || stristr($data['type'], 'repost')) {
                $feed_info['content'] = parseForApi($feed_data['body']);
                // $feed_info ['content'] = $feed_data ['body'];
                // $feed_info['content'] = $feed_info['feed_content']; // 调试性代码，因为mysql储存的字节有限，存了不完整的序列化字符串
            } else { // 内容为空，提取应用里的信息
                $source_info = $this->get_source_info($data['app'], $data['app_row_table'], $data['app_row_id']);
                $feed_info['title'] = $source_info['title'];
                $feed_info['content'] = $source_info['content'];
                $feed_info['source_name'] = $source_info['source_name'];
                $feed_info['source_url'] = $source_info['source_url'];
            }
            // 其它信息
            $feed_info['repost_count'] = $data['repost_count'];
            $feed_info['comment_count'] = $data['comment_count'];
            $feed_info['digg_count'] = $data['digg_count'];
            /* # 点赞人数列表 */
            $feed_info['digg_users'] = $this->weibo_diggs($data['feed_id'], 5);
            // 附件处理
            if (!empty($feed_data['attach_id'])) {
                $attach = model('Attach')->getAttachByIds($feed_data['attach_id']);
                foreach ($attach as $ak => $av) {
                    $_attach = array(
                            'attach_id'        => $av['attach_id'],
                            'attach_name'      => $av['name'],
                            'attach_extension' => $av['extension'],
                    );
                    if ($data['type'] == 'postimage') {
                        $_attach['attach_origin'] = getImageUrl($av['save_path'].$av['save_name']);
                        $_attach['attach_origin_width'] = $av['width'];
                        $_attach['attach_origin_height'] = $av['height'];
                        if ($av['width'] > 384 && $av['height'] > 384) {
                            $_attach['attach_middle'] = getImageUrl($av['save_path'].$av['save_name'], 384, 384, true);
                        } else {
                            $_attach['attach_middle'] = $_attach['attach_origin'];
                        }
                        if ($av['width'] > 220 && $av['height'] > 220) {
                            $_attach['attach_small'] = getImageUrl($av['save_path'].$av['save_name'], 220, 220, true);
                        } else {
                            $_attach['attach_small'] = $_attach['attach_origin'];
                        }
                    }
                    $feed_info['attach_info'][] = $_attach;
                }
            } else {
                $feed_info['attach_info'] = array(
                    'attach_id'        => '',
                    'attach_name'      => '',
                    'attach_extension' => '',
                    'attach_origin'    => '',
                    'attach_middle'    => '',
                    'attach_small'     => '',
                );
            }
            if ($data['type'] == 'postvideo') {
                if ($feed_data['video_id']) {
                    $video_info['host'] = '1';
                    $video_config = model('Xdata')->get('admin_Content:video_config');
                    $video_server = $video_config['video_server'] ? $video_config['video_server'] : SITE_URL;
                    $video_info['video_id'] = $feed_data['video_id'];
                    $video_info['flashimg'] = $video_server.$feed_data['image_path'];
                    $video_info['flash_width'] = $feed_data['image_width'];
                    $video_info['flash_height'] = $feed_data['image_height'];
                    if ($feed_data['transfer_id'] && !D('video_transfer')->where('transfer_id='.$feed_data['transfer_id'])->getField('status')) {
                        $video_info['transfering'] = 1;
                    } else {
                        $video_info['flashvar'] = $feed_data['video_mobile_path'] ? $video_server.$feed_data['video_mobile_path'] : $video_server.$feed_data['video_path'];
                        $video_info['flashvar_part'] = $video_server.$feed_data['video_part_path'];
                    }
                } else {
                    $video_info['host'] = $feed_data['host'];
                    $video_info['flashvar'] = $feed_data['flashvar'];
                    $video_info['source'] = $feed_data['source'];
                    $video_info['flashimg'] = UPLOAD_URL.'/'.$feed_data['flashimg'];
                    $video_info['title'] = $feed_data['title'];
                }
                $feed_info['attach_info'] = $video_info;
            }
        } else {
            $feed_info['is_del'] = 1;
            $feed_info['feed_id'] = $data['feed_id'];
            $feed_info['user_info'] = $this->get_user_info($data['uid']);
            $feed_info['publish_time'] = $data['publish_time'];
        }

        /* # 将emoji代码格式化为emoji */
        $feed_info['content'] = formatEmoji(false, $feed_info['content']);

        return $feed_info;
    }

    /**
     * 获取资源信息 --using.
     *
     * @param
     *        	varchar app 应用名称
     * @param
     *        	integer app_row_table 资源所在表
     * @param
     *        	integer app_row_id 资源ID
     *
     * @return array 资源信息
     */
    private function get_source_info($app, $app_row_table, $app_row_id)
    {
        switch ($app) {
            case 'weiba':
                $weiba_post = D('weiba_post')->where('post_id='.$app_row_id.' AND is_del = 0')->field('weiba_id,post_uid,title,content')->find();
                if ($weiba_post) {
                    $source_info['user_info'] = $this->get_user_info($weiba_post['post_uid']);
                    $source_info['title'] = $weiba_post['title'];
                    $source_info['content'] = real_strip_tags($weiba_post['content']);
                    $source_info['url'] = 'mod=Weibo&act=weibo_detail&id='.$app_row_id;
                    $source_info['source_name'] = D('weiba')->where('weiba_id='.$weiba_post['weiba_id'])->getField('weiba_name');
                    $source_info['source_url'] = 'api.php?mod=Weiba&act=post_detail&id='.$app_row_id;
                    /* emoji解析 */
                    $source_info['title'] = formatEmoji(false, $source_info['title']);
                    $source_info['content'] = formatEmoji(false, $source_info['content']);
                } else {
                    $source_info['is_del'] = 1;
                }
                break;
            default:
                $tablePrefix = C('DB_PREFIX');
                $map['a.feed_id'] = $app_row_id;
                $map['a.is_del'] = 0;
                $data = model('Feed')->where($map)->table("{$tablePrefix}feed AS a LEFT JOIN {$tablePrefix}feed_data AS b ON a.feed_id = b.feed_id ")->find();
                if ($data['feed_id']) {
                    $source_info['publish_time'] = $data['publish_time'];
                    $source_info['feed_id'] = $app_row_id;
                    $source_info['user_info'] = $this->get_user_info($data['uid']);
                    $source_info['type'] = real_strip_tags($data['type']);
                    $source_info['content'] = real_strip_tags($data['feed_content']);
                    $source_info['content'] = parseForApi($source_info['content']);
                    $source_info['url'] = 'mod=Weibo&act=weibo_detail&id='.$app_row_id;
                    // 附件处理
                    $feed_data = unserialize($data['feed_data']);
                    if (!empty($feed_data['attach_id'])) {
                        $attach = model('Attach')->getAttachByIds($feed_data['attach_id']);
                        foreach ($attach as $ak => $av) {
                            $_attach = array(
                                    'attach_id'   => $av['attach_id'],
                                    'attach_name' => $av['name'],
                            );
                            if ($data['type'] == 'postimage') {
                                $_attach['attach_origin'] = getImageUrl($av['save_path'].$av['save_name']);
                                $_attach['attach_origin_width'] = $av['width'];
                                $_attach['attach_origin_height'] = $av['height'];
                                if ($av['width'] > 550 && $av['height'] > 550) {
                                    $_attach['attach_small'] = getImageUrl($av['save_path'].$av['save_name'], 550, 550, true);
                                } else {
                                    $_attach['attach_small'] = $_attach['attach_origin'];
                                }
                            }
                            $source_info['attach_info'][] = $_attach;
                        }
                    } else {
                        $source_info['attach_info'] = array();
                    }
                    if ($data['type'] == 'postvideo') {
                        if ($feed_data['video_id']) {
                            $video_config = model('Xdata')->get('admin_Content:video_config');
                            $video_server = $video_config['video_server'] ? $video_config['video_server'] : SITE_URL;
                            $video_info['video_id'] = $feed_data['video_id'];
                            $video_info['flashimg'] = $video_server.$feed_data['image_path'];
                            $video_info['flash_width'] = $feed_data['image_width'];
                            $video_info['flash_height'] = $feed_data['image_height'];
                            if ($feed_data['transfer_id'] && !D('video_transfer')->where('transfer_id='.$feed_data['transfer_id'])->getField('status')) {
                                $video_info['transfering'] = 1;
                            } else {
                                $video_info['flashvar'] = $feed_data['video_mobile_path'] ? $video_server.$feed_data['video_mobile_path'] : $video_server.$feed_data['video_path'];
                                $video_info['flashvar_part'] = $video_server.$feed_data['video_part_path'];
                            }
                        } else {
                            $video_info['host'] = $feed_data['host'];
                            $video_info['flashvar'] = $feed_data['source'];
                            $video_info['source'] = $feed_data['source'];
                            $video_info['flashimg'] = UPLOAD_URL.$feed_data['flashimg'];
                            $video_info['title'] = $feed_data['title'];
                        }
                        $source_info['attach_info'][] = $video_info;
                    }
                } else {
                    $source_info['is_del'] = 1;
                }
                break;
        }

        return $source_info;
    }

    /**
     * 获取用户信息 --using.
     *
     * @param
     *        	integer uid 用户UID
     *
     * @return array 用户信息
     */
    private function get_user_info($uid)
    {
        $user_info_whole = api('User')->get_user_info($uid);
        $user_info['uid'] = $user_info_whole['uid'];
        $user_info['uname'] = $user_info_whole['uname'];
        $user_info['remark'] = $user_info_whole['remark'];
        $user_info['avatar']['avatar_middle'] = $user_info_whole['avatar']['avatar_big'];
        $user_info['user_group'] = $user_info_whole['user_group'];

        /* 关注状态 */
        $user_info['follow_state'] = model('Follow')->getFollowState($this->mid, $uid);

        // 用户隐私设置
        $privacy = model('UserPrivacy')->getPrivacy($this->mid, $uid);
        $user_info['space_privacy'] = $privacy['space'];

        return $user_info;
    }

    /**
     * 获取热门话题.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getHotTopic()
    {
        return model('FeedTopic')->where(array(
            'recommend' => 1,
            'lock'      => 0,
        ))->order('`recommend_time` DESC')
          ->limit(5)
          ->select();
    }

    /**
     * 获取正在进行的话题.
     *
     * @return array
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getNewTopic()
    {
        $max_id = intval($this->data['max_id']);
        $limit = intval($this->data['limit']);
        $where = array(
            'lock' => 0,
        );
        $max_id && $where['topic_id'] = array('lt', $max_id);

        return model('FeedTopic')->where($where)
                                 ->order('`topic_id` DESC')
                                 ->limit($limit)
                                 ->select();
    }

    public function all_topic()
    {
        $max_id = intval($this->data['max_id']);
        $limit = intval($this->data['limit']);

        if (empty($max_id)) {
            $map2['recommend'] = 1;
            $map2['lock'] = 0;
            $res['commends'] = (array) M('feed_topic')->where($map2)->order('recommend_time desc')->limit(5)->findAll();
            empty($res['commends']) || $map['topic_id'] = array(
                    'not in',
                    getSubByKey($res['commends'], 'topic_id'),
            );
        } else {
            $map['topic_id'] = array(
                    'lt',
                    $max_id,
            );
        }
        $map['lock'] = 0;
        $res['lists'] = (array) M('feed_topic')->where($map)->order('topic_id desc')->limit($limit)->findAll();
        foreach ($res['lists'] as &$v) {
            $v['topic_name'] = parseForApi($v['topic_name']);
        }

        return $res;
    }

    /**
     * 获取微博限制字数.
     *
     * @return int
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getWeiboStrMaxLength()
    {
        return array(
            'num' => json_decode(json_encode(model('Xdata')->get('admin_Config:feed')), false)->weibo_nums,
        );
    }
}
