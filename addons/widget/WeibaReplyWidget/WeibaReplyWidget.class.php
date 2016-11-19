<?php
/**
  * 评论发布/显示框
  * @example W('Comment',array('tpl'=>'detail','row_id'=>72,'order'=>'DESC','app_uid'=>'14983','cancomment'=>1,'cancomment_old'=>0,'showlist'=>1,'canrepost'=>1))
  * @author jason <yangjs17@yeah.net>
  * @version TS3.0
  */
class WeibaReplyWidget extends Widget
{
    private static $rand = 1;

    /**
     * @param string tpl 显示模版 默认为comment，一般使用detail表示详细资源页面的评论
     * @param int weiba_id 微吧ID
     * @param int post_id 帖子ID
     * @param int post_uid 帖子发布者
     * @param int feed_id 对应的分享ID
     * @param int limit 每页显示条数
     * @param string order 回复排列顺序，默认ASC
     * @param bool addtoend 新回复是否添加到尾部 0：否，1：是
     */
    public function render($data)
    {
        $var = array();
        //默认配置数据
         $var['cancomment'] = 1;  //是否可以评论
        // $var['canrepost']   = 1;  //是否允许转发
        // $var['cancomment_old'] = 1; //是否可以评论给原作者
        $var['showlist'] = 1;         // 默认显示原评论列表
        $var['tpl'] = 'detail'; // 显示模板
        $var['app_name'] = 'weiba';
        $var['table'] = 'weiba_post';
        $var['limit'] = 10;
        $var['order'] = 'ASC';
        $var['initNums'] = model('Xdata')->getConfig('weibo_nums', 'feed');
        $map['weiba_id'] = $data['weiba_id'];
        $map['level'] = array('gt', 1);
        $var['weiba_admin'] = getSubByKey(D('weiba_follow', 'weiba')->where($map)->findAll(), 'follower_uid');

        $_REQUEST['p'] = $_GET['p'] ? $_GET['p'] : $_POST['p'];
        empty($data) && $data = $_POST;
        is_array($data) && $var = array_merge($var, $data);

        !isset($var['type']) && $var['type'] = 'time';
        if ($var['type'] == 'digg') {
            $var['order_field'] = 'digg_count';
            $var['order'] = 'DESC';
        } else {
            $var['order_field'] = 'ctime';
            $var['order'] = 'ASC';
        }
        if ($var['showlist'] == 1) { //默认只取出前10条
            $map = array();
            $map['post_id'] = intval($var['post_id']);   //必须存在
            if (!empty($map['post_id'])) {
                //分页形式数据
                $var['list'] = D('WeibaReply', 'weiba')->getReplyList($map, $var['order_field'].' '.$var['order'], $var['limit']);
            }

            $row_ids = getSubByKey($var['list']['data'], 'reply_id');
            $var['diggArr'] = D('WeibaReplyDigg', 'weiba')->checkIsDigg($row_ids, $GLOBALS['ts']['mid']);
        }//渲染模版x
        $content = $this->renderFile(dirname(__FILE__).'/'.$var['tpl'].'.html', $var);
        self::$rand ++;
        $ajax = $var['isAjax'];
        unset($var, $data);
        //输出数据
        $return = array('status' => 1, 'data' => $content);

        return $ajax == 1 ? json_encode($return) : $return['data'];
    }

    /**
     * 添加帖子回复的操作
     * @return array 评论添加状态和提示信息
     */
    public function addReply()
    {
        //检测用户是否被禁言
        if ($isDisabled = model('DisableUser')->isDisableUser($this->mid, 'post')) {
            exit(json_encode(array(
                'status' => 0,
                'data' => '您已经被禁言了',
            )));
        }
        //   echo $_POST['post_id'];exit;
         if (!$this->mid || !CheckPermission('weiba_normal', 'weiba_reply')) {
             return;
         }
        $is_lock = M('weiba_blacklist')->where('weiba_id='.intval($_POST['weiba_id']).' and uid='.intval($_POST['post_uid']))->find();
        if ($is_lock) {
            $return['status'] = 0;
            $return['data'] = '您是黑名单用户没有发帖权限！';
            exit(json_encode($return));
        }
        $return = array('status' => 0, 'data' => L('PUBLIC_CONCENT_IS_ERROR'));
        $data['weiba_id'] = intval($_POST['weiba_id']);
        $data['post_id'] = intval($_POST['post_id']);
        $data['post_uid'] = intval($_POST['post_uid']);
        $data['to_reply_id'] = intval($_POST['to_reply_id']);
        $data['to_uid'] = intval($_POST['to_uid']);
        $data['uid'] = $this->mid;
        $data['ctime'] = time();
        $data['content'] = preg_html(h($_POST['content']));
        $data['attach_id'] = intval($_POST['attach_id']);

        $filterContentStatus = filter_words($data['content']);
        if (!$filterContentStatus['status']) {
            exit(json_encode(array('status' => 0, 'data' => $filterContentStatus['data'])));
        }
        $data['content'] = $filterContentStatus['data'];

        if (isSubmitLocked()) {
            $return['status'] = 0;
            $return['data'] = '发布内容过于频繁，请稍后再试！';
            exit(json_encode($return));
        }

        // 字段补充
        $data['comment_id'] = 0;

        if ($data['reply_id'] = D('weiba_reply')->add($data)) {

            // 锁定发布
            lockSubmit();

            // 更新微吧今日新帖
            D('Weiba')->setNewcount($data['weiba_id']);

            //添加积分
            model('Credit')->setUserCredit(intval($_POST['post_uid']), 'comment_topic');
            model('Credit')->setUserCredit($data['to_uid'], 'commented_topic');

            $map['last_reply_uid'] = $this->mid;
            $map['last_reply_time'] = $data['ctime'];
            $map ['reply_count'] = array(
                    'exp',
                    'reply_count+1',
            );
            $map ['reply_all_count'] = array(
                    'exp',
                    'reply_all_count+1',
            );
            D('weiba_post', 'weiba')->where('post_id='.$data['post_id'])->save($map);
            //同步到分享评论
            //$feed_id = intval($_POST['feed_id']);
            $datas['app'] = 'weiba';
            $datas['table'] = 'feed';
            $datas['content'] = preg_html($data['content']);
            $datas['app_uid'] = intval($_POST['post_uid']);
            $datas['row_id'] = intval($_POST['feed_id']);
            $datas['to_comment_id'] = $data['to_reply_id'] ? D('weiba_reply', 'weiba')->where('reply_id='.$data['to_reply_id'])->getField('comment_id') : 0;
            $datas['to_uid'] = intval($_POST['to_uid']);
            $datas['uid'] = $this->mid;
            $datas['ctime'] = time();
            $datas['client_type'] = getVisitorClient();
            // $datas['from'] = 'weiba';
            $data['cancomment'] = 1;
            $data['list_count'] = intval($_POST['list_count']);
            // 解锁
            unlockSubmit();
            if ($comment_id = model('Comment')->addComment($datas)) {
                $data1['comment_id'] = $comment_id;
                // $data1['storey'] = model('Comment')->where('comment_id='.$comment_id)->getField('storey');
                D('weiba_reply', 'weiba')->where('reply_id='.$data['reply_id'])->save($data1);
                // 给应用UID添加一个未读的评论数
                if ($GLOBALS['ts']['mid'] != $datas['app_uid'] && $datas['app_uid'] != '') {
                    //!$notCount && model('UserData')->updateKey('unread_comment_weiba', 1, true, $datas['app_uid']);
                    !$notCount && model('UserData')->updateKey('unread_comment', 1, true, $datas['app_uid']);
                }
                model('Feed')->cleanCache($datas['row_id']);
            }
            //转发到我的分享
            if ($_POST['ifShareFeed'] == 1) {
                $commentInfo = model('Source')->getSourceInfo($datas['table'], $datas['row_id'], false, $datas['app']);
                $oldInfo = isset($commentInfo['sourceInfo']) ? $commentInfo['sourceInfo'] : $commentInfo;
                // 根据评论的对象获取原来的内容
                $s['sid'] = $data['post_id'];
                $s['app_name'] = 'weiba';
                if (!empty($data ['to_comment_id'])) {
                    $replyInfo = model('Comment')->init($data ['app'], $data ['table'])->getCommentInfo($data ['to_comment_id'], false);
                    $data ['content'] .= $replyInfo ['content'];
                }
                $s ['body'] = $data ['content'];
                $s['type'] = 'weiba_post';
                $s['comment'] = $data['comment_old'];
                // 去掉回复用户@
                $lessUids = array();
                if (!empty($data['to_uid'])) {
                    $lessUids[] = $data['to_uid'];
                }
                // 如果为原创分享，不给原创用户发送@信息
                if ($oldInfo['feedtype'] == 'post' && empty($data['to_uid'])) {
                    $lessUids[] = $oldInfo['uid'];
                }
                unlockSubmit();
                model('Share')->shareFeed($s, 'comment', $lessUids);
            }
            $data['feed_id'] = $datas['row_id'];
            $data['comment_id'] = $comment_id;
            $data['storey'] = $data1['storey'];

            $data['attach_info'] = model('Attach')->getAttachById($data['attach_id']);
            if ($data['attach_info']['attach_type'] == 'weiba_comment_image' || $data['attach_info']['attach_type'] == 'feed_image') {
                $data['attach_info']['attach_url'] = getImageUrl($data['attach_info']['save_path'].$data['attach_info']['save_name'], 590);
            }
            $return['status'] = 1 ;
            $return['data'] = $this->parseReply($data);
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 删除回复(在分享评论删除中同步删除微吧回复)
     * @return bool true or false
     */
    public function delReply()
    {
        if (!CheckPermission('core_admin', 'comment_del')) {
            $map['reply_id'] = intval($_POST['reply_id']);
            $map['uid'] = $this->mid;
            $map['is_del'] = 0;
            $count = D('weiba_reply', 'weiba')->where($map)->count();
            if (!CheckPermission('weiba_normal', 'weiba_del_reply') || $count <= 0) {
                return false;
            }
        }

        $reply_id = intval($_POST['reply_id']);
        $app_name = t($_POST['widget_appname']);
        if (!empty($reply_id)) {
            $comment_id = D('weiba_reply', 'weiba')->where('reply_id='.$reply_id)->getField('comment_id');
            model('Comment')->deleteComment($comment_id, '', $app_name);
            model('Credit')->setUserCredit($this->mid, 'delete_topic_comment');

            return 1;
        }

        return false;
    }

    /**
     * 渲染评论页面 在addcomment方法中调用
     */
    public function parseReply($data)
    {
        $data['userInfo'] = model('User')->getUserInfo($GLOBALS['ts']['uid']);
        $data['userInfo']['groupData'] = model('UserGroupLink')->getUserGroupData($GLOBALS['ts']['uid']);   //获取用户组信息
        $data['content'] = preg_html($data['content']);
        $data['content'] = parse_html($data['content']);

        return $this->renderFile(dirname(__FILE__).'/_parseComment.html', $data);
    }

    /**
     * 评论帖子回复
     */
    public function reply_reply()
    {
        if (!CheckPermission('weiba_normal', 'weiba_reply')) {
            return false;
        }
        $var = $_GET;

        $var['initNums'] = model('Xdata')->getConfig('weibo_nums', 'feed');
        $var['commentInfo'] = model('Comment')->getCommentInfo($var['comment_id'], false);
        $var['canrepost'] = $var['commentInfo']['table'] == 'feed' ? 1 : 0;
        $var['cancomment'] = 1;

      // 获取原作者信息
      $rowData = model('Feed')->get(intval($var['commentInfo']['row_id']));
        $appRowData = model('Feed')->get($rowData['app_row_id']);
        $var['user_info'] = $appRowData['user_info'];
      // 分享类型
      $var['feedtype'] = $rowData['type'];
      // $var['cancomment_old'] = ($var['commentInfo']['uid'] != $var['commentInfo']['app_uid'] && $var['commentInfo']['app_uid'] != $this->uid) ? 1 : 0;
      $var['initHtml'] = L('PUBLIC_STREAM_REPLY').'@'.$var['commentInfo']['user_info']['uname'].' ：';   // 回复
      //dump($var);exit;
      return $this->renderFile(dirname(__FILE__).'/reply_reply.html', $var);
    }
}
