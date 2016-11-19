<?php
/**
  * 评论发布/显示框
  * @example W('Comment',array('tpl'=>'detail','row_id'=>72,'order'=>'DESC','app_uid'=>'14983','cancomment'=>1,'cancomment_old'=>0,'showlist'=>1,'canrepost'=>1))
  * @author jason <yangjs17@yeah.net>
  * @version TS3.0
  */
class CommentWidget extends Widget
{
    private static $rand = 1;

    /**
     *
     * @param
     *        	string tpl 显示模版 默认为comment，一般使用detail表示详细资源页面的评论
     * @param
     *        	integer row_id 评论对象所在的表的ID
     * @param
     *        	string order 评论的排序，默认为ASC 表示从早到晚,应用中一般是DESC
     * @param
     *        	integer app_uid 评论的对象的作者ID
     * @param
     *        	integer cancomment 是否可以评论 默认为1,由应用中判断好权限之后传入给wigdet
     * @param
     *        	integer cancomment_old 是否可以评论给原作者 默认为1,应用开发时统一使用0
     * @param
     *        	integer showlist 是否显示评论列表 默认为1
     * @param
     *        	integer canrepost 是否允许转发 默认为1,应用开发的时候根据应用需求设置1、0
     */
    public function render($data)
    {
        $var = array();
        // 默认配置数据
        $var ['cancomment'] = 1; // 是否可以评论
        $var ['canrepost'] = 1; // 是否允许转发
        $var ['cancomment_old'] = 1; // 是否可以评论给原作者
        $var ['showlist'] = 1; // 默认显示原评论列表
        $var ['tpl'] = 'Comment'; // 显示模板
        $var ['app_name'] = 'public';
        $var ['table'] = 'feed';
        $var ['limit'] = 10;
        $var ['order'] = 'DESC';
        $var ['initNums'] = model('Xdata')->getConfig('weibo_nums', 'feed');
        $_REQUEST ['p'] = intval($_GET ['p']) ? intval($_GET ['p']) : intval($_POST ['p']);
        if (empty($data)) {
            $data['app_uid'] = intval($_POST['app_uid']);
            $data['row_id'] = intval($_POST['row_id']);
            $data['app_row_id'] = intval($_POST['app_row_id']);
            $data['app_row_table'] = t($_POST['app_row_table']);
            $data['isAjax'] = intval($_POST['isAjax']);
            $data['showlist'] = intval($_POST['showlist']);
            $data['cancomment'] = intval($_POST['cancomment']);
            $data['cancomment_old'] = intval($_POST['cancomment_old']);
            $data['app_name'] = t($_POST['app_name']);
            $data['table'] = t($_POST['table']);
            $data['canrepost'] = intval($_POST['canrepost']);
        }
        // empty ( $data ) && $data = $_POST;
        is_array($data) && $var = array_merge($var, $data);
        $var['app_uid'] = intval($var['app_uid']);
        $var['row_id'] = intval($var['row_id']);
                // 获取资源类型
                $sourceInfo = model('Feed')->get($var ['row_id']);
        $var ['feedtype'] = $sourceInfo ['type'];
                //判断是否有权限评论当前用户
                if ($this->mid != $sourceInfo ['uid']) {
                    $userPrivacy = model('UserPrivacy')->getPrivacy($this->mid, $sourceInfo ['uid']);
                    if ($userPrivacy ['comment_weibo'] == 1) {
                        $return = array(
                                            'status' => 0,
                                            'data' => L('PUBLIC_CONCENT_TIPES'),
                            );

                        return $var ['isAjax'] == 1 ? json_encode($return) : $return ['data'];
                    }
                }
        $var['comment_origin'] = 0;
        if ($var ['table'] == 'feed' && $this->mid != $var ['app_uid']) {
            // 获取源资源作者用户信息
            $appRowData = model('Feed')->get(intval($sourceInfo['app_row_id']));
            $var['user_info'] = $appRowData['user_info'];

                        //评论原分享权限
                        $userPrivacy = model('UserPrivacy')->getPrivacy($this->mid, $var['user_info']['uid']);
            if ($userPrivacy ['comment_weibo'] === 0) {
                $var['comment_origin'] = 1;
            }
        }

        if ($this->mid == $var ['app_uid']) {
            $var['comment_origin'] = 1;
        }
        $return['data'] = $var['comment_origin'];
        if ($var ['showlist'] == 1) { // 默认只取出前10条
            $map = array();
            $map ['app'] = t($var ['app_name']);
            $map ['table'] = t($var ['table']);
            $map ['row_id'] = intval($var ['row_id']); // 必须存在
            if (! empty($map ['row_id'])) {
                // 分页形式数据
                $var ['list'] = model('Comment')->getCommentList($map, 'comment_id '.t($var ['order']), intval($var ['limit']));
            }
        } // 渲染模版

        // 转发权限判断
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        if (! CheckPermission('core_normal', 'feed_share') || ! in_array('repost', $weiboSet ['weibo_premission'])) {
            $var ['canrepost'] = 0;
        }
        $content = $this->renderFile(dirname(__FILE__).'/'.$var ['tpl'].'.html', $var);
        self::$rand ++;
        $ajax = $var ['isAjax'];
        unset($var, $data);
        // 输出数据
        $return = array(
                'status' => 1,
                'data' => $content,
        );
        // var_dump($return);exit;

        return $ajax == 1 ? json_encode($return) : $return ['data'];
    }

    /**
     * 获取评论列表
     *
     * @return array
     */
    public function getCommentList()
    {
        $map = array();
        $map ['app'] = t($_POST ['app_name']);
        $map ['table'] = t($_POST ['table']);
        $map ['row_id'] = intval($_POST ['row_id']); // 必须存在
        if (! empty($map ['row_id'])) {
            // 分页形式数据
            $var ['limit'] = 10;
            $var ['order'] = 'DESC';
            $var ['cancomment'] = $_POST ['cancomment'];
            $var ['showlist'] = $_POST ['showlist'];
            $var ['app_name'] = t($_POST ['app_name']);
            $var ['table'] = t($_POST ['table']);
            $var ['row_id'] = intval($_POST ['row_id']);
            $var ['list'] = model('Comment')->getCommentList($map, 'comment_id '.$var ['order'], $var ['limit']);
        }
        $content = $this->renderFile(dirname(__FILE__).'/commentList.html', $var);
        exit($content);
    }

    /**
     * 添加评论的操作
     *
     * @return array 评论添加状态和提示信息
     */
    public function addcomment()
    {
        // 返回结果集默认值
        $return = array(
                'status' => 0,
                'data' => L('PUBLIC_CONCENT_IS_ERROR'),
        );

        //检测用户是否被禁言
        if ($isDisabled = model('DisableUser')->isDisableUser($this->mid, 'post')) {
            return json_encode(array(
                'status' => 0,
                'data' => '您已经被禁言了',
            ));
        }
        // 获取接收数据
        $data ['app'] = t($_POST ['app_name']);
        $data ['table'] = t($_POST ['table_name']);
        //model ( 'Comment' )->addComment已针对$data['content']转义，请勿重复操作
        //$data ['content'] = h ( $_POST ['content'] );
        $data ['content'] = $_POST ['content'];
        $data ['app_uid'] = intval($_POST['app_uid']);
        $data ['app_row_id'] = intval($_POST['app_row_id']);
        $data ['app_row_table'] = t($_POST['app_row_table']);
        $data ['row_id'] = intval($_POST['row_id']);
        $data ['to_comment_id'] = intval($_POST['to_comment_id']);
        $data ['to_uid'] = intval($_POST['to_uid']);
        $data ['ifShareFeed'] = intval($_POST['ifShareFeed']);
        $data ['comment_old'] = intval($_POST['comment_old']);
        $data ['app_detail_summary'] = t($_POST['app_detail_summary']);

        $source = model('Source')->getSourceInfo($data ['table'], $data ['row_id'], false, $data ['app']);
        $uid = $source['uid'];
        if ($this->mid != $uid) {
            $userPrivacy = model('UserPrivacy')->getPrivacy($this->mid, $uid);
            if ($userPrivacy['comment_weibo'] == 1) {
                exit(json_encode(array('status' => 0, 'data' => L('PUBLIC_CONCENT_TIPES'))));
            }
        }
        $filterContentStatus = filter_words($data['content']);
        if (!$filterContentStatus['status']) {
            exit(json_encode(array('status' => 0, 'data' => $filterContentStatus['data'])));
        }
        $data['content'] = $filterContentStatus['data'];

        // 判断资源是否被删除
        $dao = M($data ['table']);
        $idField = $dao->getPk();
        $map [$idField] = intval($data ['row_id']);
        $sourceInfo = $dao->where($map)->find();

        if (! $sourceInfo) {
            $return ['status'] = 0;
            $return ['data'] = '内容已被删除，评论失败';
            exit(json_encode($return));
        }

        //兼容旧方法
        if (empty($data['app_detail_summary'])) {
            $source = model('Source')->getSourceInfo($data ['table'], $data ['row_id'], false, $data ['app']);
            $data['app_detail_summary'] = $source['source_body'];
            $data['app_detail_url'] = $source['source_url'];
            $data['app_uid'] = $source['source_user_info']['uid'];
        } else {
            $data['app_detail_summary'] = $data ['app_detail_summary'].'<a class="ico-details" href="'.$data['app_detail_url'].'"></a>';
        }
        // $data['from'] = 'feed';
        // 添加评论操作
        $data ['comment_id'] = model('Comment')->addComment($data);
        $return['sql'] = D()->getLastSql();
        if ($data ['comment_id']) {
            $talkbox = intval($_POST['talkbox']);
            $return ['status'] = 1;
            $return ['data'] = $this->parseComment($data, $talkbox);

            // 同步到微吧
            if ($data ['app'] == 'weiba') {
                $this->_upateToweiba($data);
            }

            // 去掉回复用户@
            $lessUids = array();
            if (! empty($data ['to_uid'])) {
                $lessUids [] = $data ['to_uid'];
            }

            if ($_POST ['ifShareFeed'] == 1) {  // 转发到我的分享
                //解锁内容发布
                unlockSubmit();
                $this->_updateToweibo($data, $sourceInfo, $lessUids);
            } elseif ($data ['comment_old'] != 0) {  // 是否评论给原来作者
                unlockSubmit();
                $this->_updateToComment($data, $sourceInfo, $lessUids);
            }
        }
        !$data['comment_id'] && $return['data'] = model('Comment')->getError();
        exit(json_encode($return));
    }

    /**
     * 删除评论
     *
     * @return bool true or false
     */
    public function delcomment()
    {
        // if( !CheckPermission('core_normal','comment_del') && !CheckPermission('core_admin','comment_del')){
        // return false;
        // }
        $comment_id = intval($_POST ['comment_id']);
        $comment = model('Comment')->getCommentInfo($comment_id);
        // 不存在时
        if (! $comment) {
            return false;
        }
        // 非作者时
        if ($comment ['uid'] != $this->mid) {
            // 没有管理权限不可以删除
            if (! CheckPermission('core_admin', 'comment_del')) {
                return false;
            }
            // 是作者时
        } else {
            // 没有前台权限不可以删除
            if (! CheckPermission('core_normal', 'comment_del')) {
                return false;
            }
        }

        if (! empty($comment_id)) {
            return model('Comment')->deleteComment($comment_id, $this->mid);
        }

        return false;
    }

    /**
     * 渲染评论页面 在addcomment方法中调用
     */
    public function parseComment($data, $talkbox)
    {
        $data ['userInfo'] = model('User')->getUserInfo($GLOBALS ['ts'] ['uid']);
        // 获取用户组信息
        $data ['userInfo'] ['groupData'] = model('UserGroupLink')->getUserGroupData($GLOBALS ['ts'] ['uid']);
        $data ['content'] = preg_html($data ['content']);
        $data ['content'] = parse_html($data ['content']);
        $data['content'] = str_replace('__THEME__', THEME_PUBLIC_URL, parse_html($data['content']));
        $data ['iscommentdel'] = CheckPermission('core_normal', 'comment_del');
        if ($talkbox) {
            $html = '<dl model-node="comment_list" class="msg-dialog">';
            $html .= '<dt class="right">';
            $html .= '<a href="'.U('public/Profile/index', array('uid' => $data['userInfo']['uid'])).'"><img src="'.$data['userInfo']['avatar_tiny'].'"></a>';
            $html .= '</dt>';
            $html .= '<dd class="dialog-r">';
            $html .= '<i class="arrow-mes-r"></i>';
            $html .= '<p class="info">'.$data['userInfo']['space_link'].':&nbsp;'.$data['content'].'</p>';
            $html .= '<p class="date"><span class="right">';
            $html .= '<a href="javascript:;" event-node="comment_del" event-args="comment_id='.$data['comment_id'].'">删除</a>'."\n";
            $html .= '<i class="vline">|</i>'."\n";
            $html .= '<a href="javascript:;" event-args="row_id='.$data['row_id'].'&app_uid='.$data['app_uid'].'&to_comment_id='.$data['to_comment_id'].'&to_uid='.$data['to_uid'].'&to_comment_uname='.$data['userInfo']['uname'].'&app_name='.$data['app'].'&table='.$data['table'].'" event-node="reply_comment" >回复</a>';
            $html .= '</span>刚刚</p>';
            $html .= '</dd>';
            $html .= '</dl>';

            return $html;
        } else {
            return $this->renderFile(dirname(__FILE__).'/_parseComment.html', $data);
        }
    }

    // 同步到微吧
    public function _upateToweiba($data)
    {
        $postDetail = D('weiba_post')->where('feed_id='.$data ['row_id'])->find();
        if (! $postDetail) {
            return false;
        }

        $datas ['weiba_id'] = $postDetail ['weiba_id'];
        $datas ['post_id'] = $postDetail ['post_id'];
        $datas ['post_uid'] = $postDetail ['post_uid'];
        $datas ['to_reply_id'] = $data ['to_comment_id'] ? D('weiba_reply')->where('comment_id='.$data ['to_comment_id'])->getField('reply_id') : 0;
        $datas ['to_uid'] = $data ['to_uid'];
        $datas ['uid'] = $this->mid;
        $datas ['ctime'] = time();
        $datas ['content'] = $data ['content'];
        $datas ['comment_id'] = $data ['comment_id'];
        $datas ['storey'] = model('comment')->where('comment_id='.$data ['comment_id'])->getField('storey');
        if (D('weiba_reply')->add($datas)) {
            $map ['last_reply_uid'] = $this->mid;
            $map ['last_reply_time'] = $datas ['ctime'];
            $map ['reply_count'] = array(
                    'exp',
                    'reply_count+1',
            );
            D('weiba_post')->where('post_id='.$datas ['post_id'])->save($map);
        }
    }

    // 转发到我的分享
    public function _updateToweibo($data, $sourceInfo, $lessUids)
    {
        $commentInfo = model('Source')->getSourceInfo($data ['table'], $data ['row_id'], false, $data ['app']);
        $oldInfo = isset($commentInfo ['sourceInfo']) ? $commentInfo ['sourceInfo'] : $commentInfo;

        // 根据评论的对象获取原来的内容
        $arr = array(
                'post',
                'postimage',
                'postfile',
                'weiba_post',
                'postvideo',
                'blog_post',
                'weiba_post',
                'event_post',
                'vote_post',
                'photo_post',
        );
        $scream = '';
        if (! in_array($sourceInfo ['type'], $arr)) {
            $scream = '//@'.$commentInfo ['source_user_info'] ['uname'].'：'.$commentInfo ['source_content'];
        }
        if (! empty($data ['to_comment_id'])) {
            $replyInfo = model('Comment')->init($data ['app'], $data ['table'])->getCommentInfo($data ['to_comment_id'], false);
            $replyScream = '//@'.$replyInfo ['user_info'] ['uname'].' ：';
            $data ['content'] .= $replyScream.$replyInfo ['content'];
        }
        $s ['body'] = $data ['content'].$scream;

        $s ['sid'] = $oldInfo ['source_id'];
        $s ['app_name'] = $oldInfo ['app'];
        $s ['type'] = $oldInfo ['source_table'];
        $s ['comment'] = $data ['comment_old'];
        $s ['comment_touid'] = $data ['app_uid'];

        // 如果为原创分享，不给原创用户发送@信息
        if ($sourceInfo ['type'] == 'post' && empty($data ['to_uid'])) {
            $lessUids [] = $this->mid;
        }
        model('Share')->shareFeed($s, 'comment', $lessUids);
        model('Credit')->setUserCredit($this->mid, 'forwarded_weibo');
    }

    // 评论给原来作者
    public function _updateToComment($data, $sourceInfo, $lessUids)
    {
        $commentInfo = model('Source')->getSourceInfo($data ['app_row_table'], $data ['app_row_id'], false, $data ['app']);
        $oldInfo = isset($commentInfo ['sourceInfo']) ? $commentInfo ['sourceInfo'] : $commentInfo;
        // 发表评论
        $c ['app'] = $data ['app'];
        $c ['table'] = 'feed'; // 2013/3/27
        $c ['app_uid'] = ! empty($oldInfo ['source_user_info'] ['uid']) ? $oldInfo ['source_user_info'] ['uid'] : $oldInfo ['uid'];
        $c ['content'] = $data ['content'];
        $c ['row_id'] = ! empty($oldInfo ['sourceInfo']) ? $oldInfo ['sourceInfo'] ['source_id'] : $oldInfo ['source_id'];
        if ($data ['app']) {
            $c ['row_id'] = $oldInfo ['feed_id'];
        }
        $c ['client_type'] = getVisitorClient();

        model('Comment')->addComment($c, false, false, $lessUids);
    }
}
