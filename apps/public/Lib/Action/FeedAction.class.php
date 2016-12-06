<?php
/**
 * 分享控制器.
 *
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.0
 */
class FeedAction extends Action
{
    /**
     * 获取表情操作.
     *
     * @return json 表情相关的JSON数据
     */
    public function getSmile()
    {
        exit(json_encode(model('Expression')->getAllExpression()));
    }

    /**
     * 返回好友分组列表.
     */
    public function getFriendGroup()
    {
        $usergroupList = model('FollowGroup')->getGroupList($this->mid);
        $grouplist = array();
        foreach ($usergroupList as $g) {
            $group['gid'] = $g['follow_group_id'];
            $group['title'] = $g['title'];
            $grouplist[] = $group;
        }
        // //相互关注
        // $mutualusers = model('Follow')->getFriendsData($this->mid);
        // 未分组
        $nogroupusers = model('FollowGroup')->getDefaultGroupByAll($this->mid);
        // 其他分组
        // $groupusers = array();
        // if( $grouplist ){
        // foreach ( $grouplist as $v ){
        // $groupinfo = model('FollowGroup')->getUsersByGroup( $this->mid , $v['gid'] );
        // $groupusers['group'.$v['gid']] = $groupinfo;
        // }
        // }
        // $groupusers['group-1'] = getSubByKey( $mutualusers , 'fid');
        $groupusers['group-2'] = getSubByKey($nogroupusers, 'fid');
        $groups = array(
                array(
                        'gid'   => -2,
                        'title' => '未分组',
                ),
        );
        // 关注列表
        $grouplist && $groups = array_merge($groups, $grouplist);
        $users = array();
        foreach ($groupusers as &$gu) {
            foreach ($gu as $k => $u) {
                $gu[$k] = model('User')->getUserInfoForSearch($u, 'uid,uname');
            }
        }
        $this->assign('groups', $groups);
        $this->assign('groupusers', $groupusers);
        $this->display();
    }

    public function changGroup()
    {
        $gid = intval($_POST['gid']);
        $groupinfo = model('FollowGroup')->getUsersByGroup($this->mid, $gid);

        $groupuser = array();
        foreach ($groupinfo as $gu) {
            $groupuser[] = model('User')->getUserInfoForSearch($gu, 'uid,uname');
        }
        if (!$groupuser) {
            exit();
        }
        $res = '<ul id="group'.$gid.'">';
        foreach ($groupuser as $u) {
            $res .= '<li onclick=\'core.at.insertUser("'.$u['uname'].'")\'><a href="javascript:void(0);"><img alt="'.$u['uname'].'" src="'.$u['avatar_small'].'">'.$u['uname'].'</a></li>';
        }
        $res .= '</ul>';
        exit($res);
    }

    /**
     * 发布分享操作，用于AJAX.
     *
     * @return json 发布分享后的结果信息JSON数据
     */
    public function PostFeed()
    {
        // 返回数据格式
        $return = array(
                'status' => 1,
                'data'   => '',
        );
        // 用户发送内容
        $d['content'] = isset($_POST['content']) ? h($_POST['content']) : '';
        $filterContentStatus = filter_words($d['content']);
        if (!$filterContentStatus['status']) {
            exit(json_encode(array(
                    'status' => 0,
                    'data'   => $filterContentStatus['data'],
            )));
        }
        $d['content'] = $filterContentStatus['data'];

        ///
        if ($_POST['channel_id'] == '' && $_POST['channel'] == 'channel') {
            $return = array(
                    'status' => 0,
                    'data'   => '请选择频道',
            );
            exit(json_encode($return));
        }

        // 原始数据内容
        $filterBodyStatus = filter_words($_POST['body']);
        if (!$filterBodyStatus['status']) {
            $return = array(
                    'status' => 0,
                    'data'   => $filterBodyStatus['data'],
            );
            exit(json_encode($return));
        }
        $d['body'] = $filterBodyStatus['data'];

        // 安全过滤
        foreach ($_POST as $key => $val) {
            $_POST[$key] = t($_POST[$key]);
        }
        $d['source_url'] = urldecode($_POST['source_url']); // 应用分享到分享，原资源链接
                                                                 // 滤掉话题两端的空白
        $d['body'] = preg_replace("/#[\s]*([^#^\s][^#]*[^#^\s])[\s]*#/is", '#'.trim('${1}').'#', $d['body']);
        // $numbers = array(41624,41625,41626,41627,41628,41629);
        // shuffle($numbers);
        // // 附件信息
        // if($_POST ['attach_id']==''&&$_POST ['channel_id']!=''){
        // 	$_POST ['attach_id'] = '|'.$numbers[0].'|'.$numbers[1].'|'.$numbers[2].'|';
        // 	$_POST ['type'] = 'postimage';
        // }

        $d['attach_id'] = trim(t($_POST['attach_id']), '|');
        if (!empty($d['attach_id'])) {
            $d['attach_id'] = explode('|', $d['attach_id']);
            if (count($d['attach_id']) == 1 && $_POST['channel_id'] != '' && false) {
                $d['attach_id'][1] = $d['attach_id'][0];
                $d['attach_id'][0] = $numbers[0];
                $_POST['type'] = 'postimage';
            }
            array_map('intval', $d['attach_id']);
        }
        if ($_POST['video_id']) {
            $d['video_id'] = intval($_POST['video_id']);
        }
        if ($_POST['channel_id'] > 0) {
            $d['is_audit'] = 0;
        }
        // 发送分享的类型
        $type = t($_POST['type']);
        // 附件信息
        // 所属应用名称
        $app = isset($_POST['app_name']) ? t($_POST['app_name']) : APP_NAME; // 当前动态产生所属的应用
        if (!$data = model('Feed')->put($this->uid, $app, $type, $d)) {
            $return = array(
                    'status' => 0,
                    'data'   => model('Feed')->getError(),
            );
            exit(json_encode($return));
        }
        // 发布邮件之后添加积分
        model('Credit')->setUserCredit($this->uid, 'add_weibo');
        // 分享来源设置
        $data['from'] = getFromClient($data['from'], $data['app']);
        $this->assign($data);
        // 分享配置
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $this->assign('weibo_premission', $weiboSet['weibo_premission']);
        $return['data'] = $this->fetch();
        // 分享ID
        $return['feedId'] = $data['feed_id'];
        $return['is_audit'] = $data['is_audit'];
        // 添加话题
        model('FeedTopic')->addTopic(html_entity_decode($d['body'], ENT_QUOTES, 'UTF-8'), $data['feed_id'], $type);
        // 更新用户最后发表的分享
        $last['last_feed_id'] = $data['feed_id'];
        $last['last_post_time'] = $_SERVER['REQUEST_TIME'];
        model('User')->where('uid='.$this->uid)->save($last);

        $isOpenChannel = model('App')->isAppNameOpen('channel');
        if (!$isOpenChannel) {
            exit(json_encode($return));
        }
        // 添加分享到投稿数据中
        $channelId = t($_POST['channel_id']);

        // 绑定用户
        $bindUserChannel = D('Channel', 'channel')->getCategoryByUserBind($this->mid);
        if (!empty($bindUserChannel)) {
            $channelId = array_merge($bindUserChannel, explode(',', $channelId));
            $channelId = array_filter($channelId);
            $channelId = array_unique($channelId);
            $channelId = implode(',', $channelId);
        }
        // 绑定话题
        $content = html_entity_decode($d['body'], ENT_QUOTES, 'UTF-8');
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
            $return['is_audit_channel'] = $channelConf['is_audit'];
            // 添加频道数据
            D('Channel', 'channel')->setChannel($data['feed_id'], $channelId, false);
        }

        exit(json_encode($return));
    }

    /**
     * 分享/转发分享操作，需要传入POST的值
     *
     * @return json 分享/转发分享后的结果信息JSON数据
     */
    public function shareFeed()
    {
        // 获取传入的值
        $post = $_POST;
        // 安全过滤
        foreach ($post as $key => $val) {
            $post[$key] = t($post[$key]);
        }
        // 过滤内容值
        // $post['body'] = filter_keyword($post['body']);
        $filterBodyStatus = filter_words($post['body']);
        if (!$filterBodyStatus['status']) {
            $return = array(
                    'status' => 0,
                    'data'   => $filterBodyStatus['data'],
            );
            exit(json_encode($return));
        }
        $post['body'] = $filterBodyStatus['data'];

        // 判断资源是否删除
        if (empty($post['curid'])) {
            $map['feed_id'] = intval($post['sid']);
        } else {
            $map['feed_id'] = intval($post['curid']);
        }
        $map['is_del'] = 0;
        $isExist = model('Feed')->where($map)->count();
        if ($isExist == 0) {
            $return['status'] = 0;
            $return['data'] = '内容已被删除，转发失败';
            exit(json_encode($return));
        }

        // 进行分享操作
        $return = model('Share')->shareFeed($post, 'share');
        if ($return['status'] == 1) {
            $app_name = $post['app_name'];

            // 添加积分
            if ($app_name == 'public') {
                model('Credit')->setUserCredit($this->uid, 'forward_weibo');
                // 分享被转发
                $suid = model('Feed')->where($map)->getField('uid');
                model('Credit')->setUserCredit($suid, 'forwarded_weibo');
            }
            if ($app_name == 'weiba') {
                model('Credit')->setUserCredit($this->uid, 'forward_topic');
                // 分享被转发
                $suid = D('Feed')->where('feed_id='.$map['feed_id'])->getField('uid');
                model('Credit')->setUserCredit($suid, 'forwarded_topic');
            }

            $this->assign($return['data']);
            // 分享配置
            $weiboSet = model('Xdata')->get('admin_Config:feed');
            $this->assign('weibo_premission', $weiboSet['weibo_premission']);
            $return['feed_id'] = $return['data']['feed_id'];
            $return['data'] = $this->fetch('PostFeed');
        }

        if ($post['comment'] == '1') {
        }
        exit(json_encode($return));
    }

    /**
     * 删除分享操作，用于AJAX.
     *
     * @return json 删除分享后的结果信息JSON数据
     */
    public function removeFeed()
    {
        $return = array(
                'status' => 0,
                'data'   => L('PUBLIC_DELETE_FAIL'),
                'msg'    => '',
        ); // 删除失败
        $feed_id = intval($_POST['feed_id']);
        $feed = model('Feed')->getFeedInfo($feed_id);
        // 不存在时
        if (!$feed) {
            $return['msg'] = '不存在';
            exit(json_encode($return));
        }
        // 非作者时
        if ($feed['uid'] != $this->mid) {
            // 没有管理权限不可以删除
            if (!CheckPermission('core_admin', 'feed_del')) {
                $return['msg'] = '没有权限';
                exit(json_encode($return));
            }
            // 是作者时
        } else {
            // 没有前台权限不可以删除
            if (!CheckPermission('core_normal', 'feed_del')) {
                $return['msg'] = '没有前台权限';
                exit(json_encode($return));
            }
        }
        // 执行删除操作
        $return = model('Feed')->doEditFeed($feed_id, 'delFeed', '', $this->mid);
        // 执行应用信息相关删除
        switch ($feed['type']) {
            case 'photo_post':
                $photoList = D('photo')->where('feed_id='.$feed_id)->findAll();
                foreach ($photoList as $photoInfo) {
                    $photoId = $photoInfo['id'];
                    if (D('Album', 'photo')->deletePhoto($photoId, $photoInfo['userId'])) {
                        model('Credit')->setUserCredit($photoInfo['userId'], 'delete_photo');
                    }
                }
                break;
            case 'vote_post':
                $voteInfo = D('vote')->where('feed_id='.$feed_id)->find();
                $voteId = $voteInfo['id'];
                if (D('Vote', 'vote')->doDeleteVote($voteId)) {
                    model('Credit')->setUserCredit($voteInfo['uid'], 'delete_vote');
                }
                break;
            case 'event_post':
                $eventInfo = D('event')->where('feed_id='.$feed_id)->find();
                $eventId = $eventInfo['id'];
                D('Event', 'event')->doDeleteEvent($eventId);
                break;
            case 'blog_post':
                $blogInfo = D('blog')->where('feed_id='.$feed_id)->find();
                $blogId = $blogInfo['id'];
                $bmap['id'] = $blogId;
                if (D('Blog', 'blog')->doDeleteblog($bmap, $blogInfo['uid'])) {
                    model('Credit')->setUserCredit($blogInfo['uid'], 'delete_blog');
                }
                break;
            case 'weiba_post':
                $postInfo = D('weiba_post')->where('feed_id='.$feed_id)->find();
                $postId = $postInfo['post_id'];
                $weibaId = $postInfo['weiba_id'];
                if (D('weiba_post')->where('post_id='.$postId)->setField('is_del', 1)) {
                    $postDetail = D('weiba_post')->where('post_id='.$postId)->find();
                    D('Log', 'weiba')->writeLog($postDetail['weiba_id'], $this->mid, '删除了帖子“'.$postDetail['title'].'”', 'posts');
                    D('weiba')->where('weiba_id='.$weibaId)->setDec('thread_count');
                    model('Credit')->setUserCredit($postInfo['post_uid'], 'delete_topic');
                }
                break;
        }
        // 删除失败或删除成功的消息
        $return['data'] = ($return['status'] == 0) ? L('PUBLIC_DELETE_FAIL') : L('PUBLIC_DELETE_SUCCESS');
        // 批注：下面的代码应该挪到FeedModel中
        // 删除话题相关信息
        $return['status'] == 1 && model('FeedTopic')->deleteWeiboJoinTopic($feed_id);
        // 删除频道关联信息
        D('Channel', 'channel')->deleteChannelLink($feed_id);
        // 删除@信息
        model('Atme')->setAppName('Public')->setAppTable('feed')->deleteAtme(null, $feed_id, null);
        // 删除话题信息
        $topics = D('feed_topic_link')->where('feed_id='.$feed_id)->field('topic_id')->findAll();
        D('feed_topic_link')->where('feed_id='.$feed)->delete();
        $tpmap['topic_id'] = array(
                'in',
                getSubByKey($topics, 'topic_id'),
        );
        model('FeedTopic')->where($tpmap)->setDec('count');
        exit(json_encode($return));
    }

    /**
     * 显示大展示图界面.
     */
    public function showBigImage()
    {
        // 获取分享ID
        $feedId = intval($_POST['feedId']);
        if (empty($feedId)) {
            $feedId = intval($_GET['p']);
        }
        $var['feedId'] = $feedId;
        // 获取索引ID
        $i = intval($_POST['i']);
        $var['i'] = empty($i) ? 1 : $i;
        // 获取分享信息
        $var['feedInfo'] = model('Feed')->getFeedInfo($feedId);
        // 图片信息
        $var['images'] = json_encode($var['feedInfo']['attach']);
        // 分享配置信息
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $var['initNums'] = $weiboSet['weibo_nums'];

        $data['status'] = 1;
        $data['html'] = fetch('bigImageBox', $var);
        exit(json_encode($data));
        // echo fetch('bigImageBox', $var);
    }

    /**
     * 获取Ajax列表数据.
     *
     * @return JSON数据
     */
    public function ajaxList()
    {
        $type = t($_GET['type']);
        $feedId = intval($_GET['feedId']);
        if (empty($type) || empty($feedId)) {
            return array();
        }
        $data = array();
        // 获取分享信息
        $sourceInfo = model('Feed')->getFeedInfo($feedId);
        if ($type === 'report') {
            $var = array();
            $var['app_name'] = 'public';
            $var['table'] = 'feed';
            $var['limit'] = 5;
            $var['order'] = 'a.publish_time DESC';
            $map = array();
            $map['a.app_row_id'] = $feedId;
            $map['a.app'] = $var['app_name'];
            $map['a.app_row_table'] = $var['table'];
            $var['list'] = D()->table('`'.C('DB_PREFIX').'feed` AS a LEFT JOIN `'.C('DB_PREFIX').'feed_data` AS b ON a.`feed_id` = b.`feed_id`')->field('a.`uid`, b.`feed_content`, a.`publish_time`, a.`feed_id` AS `curid`, a.`app_row_id` AS `sid`, a.`is_repost`')->where($map)->order($var['order'])->findPage($var['limit']);
            foreach ($var['list']['data'] as &$value) {
                $value['user_info'] = model('User')->getUserInfo($value['uid']);
            }
            $html = fetch('ajaxListReport', $var);
            $over = ($var['list']['totalPages'] == $var['list']['nowPage'] || empty($var['list']['data'])) ? 1 : 0;
            $data = array(
                    'status' => 1,
                    'data'   => $html,
                    'over'   => $over,
            );
        } elseif ($type === 'comment') {
            $weiboSet = model('Xdata')->get('admin_Config:feed');
            $var = array();
            // 默认配置数据
            $var['cancomment'] = 1; // 是否可以评论
            $var['canrepost'] = 1; // 是否允许转发
            $var['cancomment_old'] = 1; // 是否可以评论给原作者
            $var['app_name'] = 'public';
            $var['table'] = 'feed';
            $var['limit'] = 5;
            $var['order'] = 'DESC';
            $var['app_uid'] = $sourceInfo['uid'];
            $var['feedtype'] = $sourceInfo['type'];
            $var['user_info'] = model('User')->getUserInfo($var['app_uid']);
            // 获取分享评论信息
            if ($var['table'] == 'feed' && $this->mid != $var['app_uid']) {
                // 判断隐私设置
                if ($this->mid != $var['app_uid']) {
                    $userPrivacy = model('UserPrivacy')->getPrivacy($this->mid, $var['app_uid']);
                    if ($userPrivacy['comment_weibo'] == 1) {
                        $data = array(
                                'status' => 0,
                                'data'   => L('PUBLIC_CONCENT_TIPES'),
                        );
                        exit(json_encode($data));
                    }
                }
            }
            // 获取数据
            $map = array();
            $map['app'] = $var['app_name'];
            $map['table'] = $var['table'];
            $map['row_id'] = $feedId;
            $var['list'] = model('Comment')->getCommentList($map, 'comment_id '.$var['order'], $var['limit']);
            // 转发权限判断
            if (!CheckPermission('core_normal', 'feed_share') || !in_array('repost', $weiboSet['weibo_premission'])) {
                $var['canrepost'] = 0;
            }
            // 组装数据
            $html = fetch('ajaxListComment', $var);
            $over = ($var['list']['totalPages'] == $var['list']['nowPage'] || empty($var['list']['data'])) ? 1 : 0;
            $data = array(
                    'status' => 1,
                    'data'   => $html,
                    'over'   => $over,
            );
        }

        exit(json_encode($data));
    }

    public function addComment()
    {
        // 返回结果集默认值
        $return = array(
                'status' => 0,
                'data'   => L('PUBLIC_CONCENT_IS_ERROR'),
        );
        // 获取接收数据
        $data = $_POST;
        // 安全过滤
        foreach ($data as $key => $val) {
            $data[$key] = t($data[$key]);
        }
        // 评论所属与评论内容
        $data['app'] = $data['app_name'];
        $data['table'] = $data['table_name'];
        $data['content'] = h($data['content']);
        // 判断资源是否被删除
        $dao = M($data['table']);
        $idField = $dao->getPk();
        $map[$idField] = $data['row_id'];
        $sourceInfo = $dao->where($map)->find();

        if (!$sourceInfo) {
            $return['status'] = 0;
            $return['data'] = '内容已被删除，评论失败';
            exit(json_encode($return));
        }
        // 兼容旧方法
        if (empty($data['app_detail_summary'])) {
            $source = model('Source')->getSourceInfo($data['table'], $data['row_id'], false, $data['app']);
            $data['app_detail_summary'] = $source['source_body'];
            $data['app_detail_url'] = $source['source_url'];
            $data['app_uid'] = $source['source_user_info']['uid'];
        } else {
            $data['app_detail_summary'] = $data['app_detail_summary'].'<a class="ico-details" href="'.$data['app_detail_url'].'"></a>';
        }
        // 添加评论操作
        $data['comment_id'] = model('Comment')->addComment($data);
        if ($data['comment_id']) {
            $return['status'] = 1;
            $commentInfo = model('Comment')->getCommentInfo($data['comment_id']);
            $html = '<dl class="comment_list" id="comment_list" id="comment_list_'.$commentInfo['comment_id'].'">
				<dt><a href="'.$commentInfo['user_info']['space_url'].'"><img src="'.$commentInfo['user_info']['avatar_tiny'].'" width="30" height="30"/></a></dt>
				<dd>
				<p class="cont">'.$commentInfo['user_info']['space_link'].'：<em>'.str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($commentInfo['content'])).'<span class="time">'.friendlyDate($commentInfo['ctime']).'</span><span class="handle">&nbsp;<a href="javascript:;" onclick="deleteComment('.$commentInfo['comment_id'].');">删除</a>
				<a href="javascript:;" onclick="replyComment(\''.$commentInfo['user_info']['uname'].'\', '.$commentInfo['user_info']['uid'].', '.$commentInfo['comment_id'].');">回复</a></span></em></p>
				</dd>
				</dl>';
            $return['data'] = $html;

            // 去掉回复用户@
            $lessUids = array();
            if (!empty($data['to_uid'])) {
                $lessUids[] = $data['to_uid'];
            }

            if ($_POST['ifShareFeed'] == 1) { // 转发到我的分享
                unlockSubmit();
                $this->_updateToweibo($data, $sourceInfo, $lessUids);
            } elseif ($data['comment_old'] != 0) { // 是否评论给原来作者
                unlockSubmit();
                $this->_updateToComment($data, $sourceInfo, $lessUids);
            }
        } else {
            $return['data'] = model('Comment')->getError();
        }

        exit(json_encode($return));
    }

    // 转发到我的分享
    private function _updateToweibo($data, $sourceInfo, $lessUids)
    {
        $commentInfo = model('Source')->getSourceInfo($data['table'], $data['row_id'], false, $data['app']);
        $oldInfo = isset($commentInfo['sourceInfo']) ? $commentInfo['sourceInfo'] : $commentInfo;

        // 根据评论的对象获取原来的内容
        $arr = array(
                'post',
                'postimage',
                'postfile',
                'weiba_post',
                'postvideo',
        );
        $scream = '';
        if (!in_array($sourceInfo['type'], $arr)) {
            $scream = '//@'.$commentInfo['source_user_info']['uname'].'：'.$commentInfo['source_content'];
        }
        if (!empty($data['to_comment_id'])) {
            $replyInfo = model('Comment')->init($data['app'], $data['table'])->getCommentInfo($data['to_comment_id'], false);
            $replyScream = '//@'.$replyInfo['user_info']['uname'].' ：';
            $data['content'] .= $replyScream.$replyInfo['content'];
        }
        $s['body'] = $data['content'].$scream;

        $s['sid'] = $oldInfo['source_id'];
        $s['app_name'] = $oldInfo['app'];
        $s['type'] = $oldInfo['source_table'];
        $s['comment'] = $data['comment_old'];
        $s['comment_touid'] = $data['app_uid'];

        // 如果为原创分享，不给原创用户发送@信息
        if ($sourceInfo['type'] == 'post' && empty($data['to_uid'])) {
            $lessUids[] = $this->mid;
        }
        model('Share')->shareFeed($s, 'comment', $lessUids);
        model('Credit')->setUserCredit($this->mid, 'forwarded_weibo');
    }

    // 评论给原来作者
    private function _updateToComment($data, $sourceInfo, $lessUids)
    {
        $commentInfo = model('Source')->getSourceInfo($data['app_row_table'], $data['app_row_id'], false, $data['app']);
        $oldInfo = isset($commentInfo['sourceInfo']) ? $commentInfo['sourceInfo'] : $commentInfo;
        // 发表评论
        $c['app'] = $data['app'];
        $c['table'] = 'feed'; // 2013/3/27
        $c['app_uid'] = !empty($oldInfo['source_user_info']['uid']) ? $oldInfo['source_user_info']['uid'] : $oldInfo['uid'];
        $c['content'] = $data['content'];
        $c['row_id'] = !empty($oldInfo['sourceInfo']) ? $oldInfo['sourceInfo']['source_id'] : $oldInfo['source_id'];
        if ($data['app']) {
            $c['row_id'] = $oldInfo['feed_id'];
        }
        $c['client_type'] = getVisitorClient();

        model('Comment')->addComment($c, false, false, $lessUids);
    }

    public function addReport()
    {
        // 获取传入的值
        $post = $_POST;
        // 安全过滤
        foreach ($post as $key => $val) {
            $post[$key] = t($post[$key]);
        }
        // 过滤内容值
        $post['body'] = filter_keyword($post['body']);

        // 判断资源是否删除
        if (empty($post['curid'])) {
            $map['feed_id'] = $post['sid'];
        } else {
            $map['feed_id'] = $post['curid'];
        }
        $map['is_del'] = 0;
        $isExist = model('Feed')->where($map)->count();
        if ($isExist == 0) {
            $return['status'] = 0;
            $return['data'] = '内容已被删除，转发失败';
            exit(json_encode($return));
        }

        // 进行分享操作
        $return = model('Share')->shareFeed($post, 'share');
        if ($return['status'] == 1) {
            $app_name = $post['app_name'];

            // 添加积分
            if ($app_name == 'public') {
                model('Credit')->setUserCredit($this->uid, 'forward_weibo');
                // 分享被转发
                $suid = model('Feed')->where($map)->getField('uid');
                model('Credit')->setUserCredit($suid, 'forwarded_weibo');
            }
            if ($app_name == 'weiba') {
                model('Credit')->setUserCredit($this->uid, 'forward_topic');
                // 分享被转发
                $suid = D('Feed')->where('feed_id='.$map['feed_id'])->getField('uid');
                model('Credit')->setUserCredit($suid, 'forwarded_topic');
            }

            $this->assign($return['data']);
            // 分享配置
            $weiboSet = model('Xdata')->get('admin_Config:feed');
            $this->assign('weibo_premission', $weiboSet['weibo_premission']);
            $html = '<dl class="comment_list" id="comment_list">
					<dt><a href="'.$return['data']['user_info']['space_url'].'"><img src="'.$return['data']['user_info']['avatar_tiny'].'" width="30" height="30"/></a></dt>
					<dd>
					<p class="cont">'.$return['data']['user_info']['space_link'].'：<em>'.str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($return['data']['content'])).'<span class="time">('.friendlyDate($return['data']['publish_time']).')</span></em></p>
					<p class="right mt5"><span><a href="javascript:;" onclick="shareFeed('.$return['data']['feed_id'].', '.$return['data']['curid'].');">转发</a></span></p>
					</dd>
					</dl>';
            $return['data'] = $html;
        }
        exit(json_encode($return));
    }

    /**
     * 异步获取指定分享内容.
     *
     * @return json 指定分享的内容
     */
    public function ajaxWeiboInfo()
    {
        $feedId = intval($_POST['feedId']);
        // 获取信息失败
        if (empty($feedId)) {
            $data['status'] = 0;
            $data['data'] = '获取分享信息失败';
            exit(json_encode($data));
        }
        $var['feedId'] = $feedId;
        // 获取分享信息
        $var['feedInfo'] = model('Feed')->getFeedInfo($feedId);
        // 分享配置信息
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $var['initNums'] = $weiboSet['weibo_nums'];
        // 赞功能
        $var['diggArr'] = model('FeedDigg')->checkIsDigg($feedId, $GLOBALS['ts']['mid']);
        // 输出信息
        $data['status'] = 1;
        $data['data'] = fetch('ajaxWeiboInfo', $var);
        exit(json_encode($data));
    }

    /**
     * 异步获取指定图片内容.
     *
     * @return json 指定分享图片信息
     */
    public function ajaxImageInfo()
    {
        $feedId = intval($_POST['feedId']);
        $index = intval($_POST['i']);
        // 获取信息失败
        if (empty($feedId) || empty($index)) {
            $data['status'] = 0;
            $data['data'] = '获取图片信息失败';
            exit(json_encode($data));
        }
        // 获取索引ID
        $var['iShow'] = $index;
        // 获取分享信息
        $feedInfo = model('Feed')->getFeedInfo($feedId);
        $var['feedInfo'] = $feedInfo; //print_r($feedInfo);
        // 获取图片尺寸
        $var['attach'] = array();
        foreach ($feedInfo['attach'] as $value) {
            $attach = model('Attach')->getAttachById($value['attach_id']);
            $width = $attach['width'];
            $height = $attach['height'];
            $var['attach'][] = array_merge($value, array(
                    'width'  => $width,
                    'height' => $height,
            ));
        }
        // 图片信息
        $var['images'] = json_encode($var['attach']);
        $data['status'] = 1;
        // dump($var);
        $data['data'] = fetch('ajaxImageInfo', $var);
        exit(json_encode($data));
    }

    /**
     * 获取多图上传弹窗结构.
     */
    public function multimageBox()
    {
        // 返回的JSON值
        $data['unid'] = substr(strtoupper(md5(uniqid(mt_rand(), true))), 0, 8);
        $data['status'] = 1;
        $data['total'] = 9;
        // 设置渲染变量
        $var['unid'] = $data['unid'];
        $attachConf = model('Xdata')->get('admin_Config:attachimage');
        //var_dump($attachConf);
        // 发布版本后，在进行修改
        $defaultExt = array(
                'jpg',
                'gif',
                'jpeg',
                'png',
        );
        $ext = array_intersect($defaultExt, explode(',', $attachConf['attach_allow_extension']));
        foreach ($ext as $value) {
            $var['fileTypeExts'] .= '*.'.strtolower($value).'; ';
        }
        //var_dump($var ['fileTypeExts']);exit;

        $var['fileSizeLimit'] = floor($attachConf['attach_max_size'] * 1024).'KB';
        $var['total'] = $data['total'];
        $data['html'] = fetch('multimageBox', $var);
        exit(json_encode($data));
    }

    /**
     * 获取视频上传弹窗结构.
     */
    public function videoBox()
    {
        $weibo_config = model('Xdata')->get('admin_Config:feed');
        if ($weibo_config['weibo_uploadvideo_open']) {
            $data['weibo_uploadvideo_open'] = 1;
            // 返回的JSON值
            $data['unid'] = substr(strtoupper(md5(uniqid(mt_rand(), true))), 0, 8);
            // 设置渲染变量
            $var['unid'] = $data['unid'];
            $video_config = model('Xdata')->get('admin_Content:video_config');
            $defaultExt = array(
                    'mp4',
            );
            $defaultVideoSize = 50;
            $ext = $video_config['video_ext'] ? explode(',', $video_config['video_ext']) : $defaultExt;
            foreach ($ext as $value) {
                $var['fileTypeExts'] .= '*.'.strtolower($value).'; ';
            }
            $video_size = $video_config['video_size'] ? intval($video_config['video_size']) : $defaultVideoSize;
            $var['fileSizeLimit'] = $video_size.'MB';
            $var['total'] = 1;
            $data['html'] = fetch('videoBox', $var);
            $data['video_ext'] = implode(',', $ext);
            $data['video_size'] = $video_size;
        } else {
            $data['weibo_uploadvideo_open'] = 0;
        }
        exit(json_encode($data));
    }

    public function video_exist()
    {
        $flashvar = $_POST['flashvar'];
        $flashvar = str_replace(SITE_URL, SITE_PATH, $flashvar);
        $host = t($_POST['host']);
        if (file_exists($flashvar) || $host) {
            // 更新浏览记录
            model('Video')->update_viewrecord(intval($_POST['id']), $this->mid);
            $data['status'] = 1;
        } else {
            $data['status'] = 0;
            $data['msg'] = '该视频正在转码中';
        }
        exit(json_encode($data));
    }

    public function feed_recommend()
    {
        $map['feed_id'] = intval($_POST['feed_id']);

        $dao = model('Feed');
        $data['is_recommend'] = intval($_POST['val']);
        $data['recommend_time'] = time();

        $dao->where($map)->save($data);
        $dao->cleanCache($map);
    }
}
