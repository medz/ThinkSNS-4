<?php
/**
 * 微吧模型 - 数据对象模型.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class WeibaReplyModel extends Model
{
    protected $tableName = 'weiba_reply';
    protected $error = '';
    protected $fields = array(
                            0 => 'reply_id', 1 => 'weiba_id', 2 => 'post_id', 3 => 'post_uid', 4 => 'uid', 5 => 'ctime',
                            6 => 'content', 7 => 'is_del', 8 => 'comment_id', 9 => 'storey', 10 => 'attach_id', '_autoinc' => true, '_pk' => 'post_id',
                        );

    /**
     * 获取回复列表.
     *
     * @param array  $map   查询条件
     * @param string $order 排序条件，默认为comment_id ASC
     * @param int    $limit 结果集数目，默认为10
     *
     * @return array 评论列表信息
     */
    public function getReplyList($map = null, $order = 'reply_id desc', $limit = 10)
    {
        !isset($map['is_del']) && ($map['is_del'] = 0);
        $data = $this->where($map)->order($order)->findPage($limit);
        // // TODO:后续优化
        foreach ($data['data'] as &$v) {
            $v['user_info'] = model('User')->getUserInfo($v['uid']);
            $v['user_info']['groupData'] = model('UserGroupLink')->getUserGroupData($v['uid']);   //获取用户组信息
            $v['content'] = parse_html(h(htmlspecialchars($v['content'])));
            //$v['sourceInfo'] = model('Source')->getSourceInfo($v['table'], $v['row_id'], false, $v['app']);
            $v['attach_info'] = model('Attach')->getAttachById($v['attach_id']);
            if ($v['attach_info']['attach_type'] == 'weiba_comment_image' || $v['attach_info']['attach_type'] == 'feed_image') {
                $v['attach_info']['attach_url'] = getImageUrl($v['attach_info']['save_path'].$v['attach_info']['save_name'], 590);
            }
        }

        return $data;
    }

    /**
     * 获取回复列表forapi.
     *
     * @param array  $map   查询条件
     * @param string $order 排序条件，默认为comment_id ASC
     * @param int    $limit 结果集数目，默认为10
     *
     * @return array 评论列表信息
     */
    public function getReplyListForApi($map = null, $order = 'reply_id desc', $limit = 20, $page = 1)
    {
        !isset($map['is_del']) && ($map['is_del'] = 0);
        $limit = intval($limit);
        $page = intval($page);
        $start = ($page - 1) * $limit;
        $end = $limit;
        $data = $this->where($map)->limit("{$start},{$end}")->order($order)->findAll();
        // TODO:后续优化
        foreach ($data as $k => $v) {
            $data[$k]['author_info'] = model('User')->getUserInfo($v['uid']);
            $data[$k]['storey'] = $start + $k + 1;
        }

        return $data;
    }

    /**
     * 添加帖子评论forApi.
     *
     * @param int post_id 帖子ID
     * @param int content 帖子内容
     * @param int uid 评论者UID
     *
     * @return bool 是否评论成功
     */
    public function addReplyForApi($post_id, $content, $uid)
    {
        $post_detail = D('weiba_post')->where('post_id='.$post_id)->find();
        $data['weiba_id'] = intval($post_detail['weiba_id']);
        $data['post_id'] = $post_id;
        $data['post_uid'] = intval($post_detail['post_uid']);
        $data['uid'] = $uid;
        $data['ctime'] = time();
        $data['content'] = preg_html(h($content));
        if ($data['reply_id'] = D('weiba_reply')->add($data)) {
            $map['last_reply_uid'] = $data['uid'];
            $map['last_reply_time'] = $data['ctime'];
            D('weiba_post')->where('post_id='.$data['post_id'])->save($map);
            D('weiba_post')->where('post_id='.$data['post_id'])->setInc('reply_count'); //回复统计数加1
            //同步到分享评论
            //$feed_id = intval($_POST['feed_id']);
            $datas['app'] = 'weiba';
            $datas['table'] = 'feed';
            $datas['row_id'] = intval($post_detail['feed_id']);
            $datas['app_uid'] = intval($post_detail['post_uid']);
            //$datas['to_comment_id'] = $data['to_reply_id']?D('weiba_reply')->where('reply_id='.$data['to_reply_id'])->getField('comment_id'):0;
            //$datas['to_uid'] = intval($_POST['to_uid']);
            $datas['uid'] = $data['uid'];
            $datas['content'] = preg_html($data['content']);
            $datas['ctime'] = $data['ctime'];
            $datas['client_type'] = getVisitorClient();
            // 设置评论绝对楼层
            // $data['data']['storey'] = model('Comment')->getStorey($datas['row_id'], $datas['app'], $datas['table']);
            // $datas['data'] = serialize($data['data']);
            if ($comment_id = model('Comment')->addComment($datas)) {
                // $data1['comment_id'] = $comment_id;
                // $data1['storey'] = model('Comment')->where('comment_id='.$comment_id)->getField('storey');
                // D('weiba_reply')->where('reply_id='.$data['reply_id'])->save($data1);
                // 被评论内容的“评论统计数”加1，同时可检测出app，table，row_id的有效性
                // D('feed')->where('feed_id='.$datas['row_id'])->setInc('comment_count');
                // 给应用UID添加一个未读的评论数
                if ($GLOBALS['ts']['mid'] != $datas['app_uid'] && $datas['app_uid'] != '') {
                    !$notCount && model('UserData')->updateKey('unread_comment', 1, true, $datas['app_uid']);
                }
                model('Feed')->cleanCache($datas['row_id']);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加评论回复forApi.
     *
     * @param int reply_id 评论ID
     * @param int content 回复内容
     * @param int uid 回复者UID
     *
     * @return bool 是否回复成功
     */
    public function addReplyToCommentForApi($reply_id, $content, $uid)
    {
        $reply_detail = $this->where('reply_id='.$reply_id)->find();
        $data['weiba_id'] = intval($reply_detail['weiba_id']);
        $data['post_id'] = intval($reply_detail['post_id']);
        $data['post_uid'] = intval($reply_detail['post_uid']);
        $data['to_reply_id'] = $reply_id;
        $data['to_uid'] = intval($reply_detail['uid']);
        $data['uid'] = $uid;
        $data['ctime'] = time();
        $data['content'] = preg_html(h($content));
        if ($data['reply_id'] = D('weiba_reply')->add($data)) {
            $map['last_reply_uid'] = $data['uid'];
            $map['last_reply_time'] = $data['ctime'];
            D('weiba_post')->where('post_id='.$data['post_id'])->save($map);
            D('weiba_post')->where('post_id='.$data['post_id'])->setInc('reply_count'); //回复统计数加1
            //同步到分享评论
            //$feed_id = intval($_POST['feed_id']);
            $datas['app'] = 'weiba';
            $datas['table'] = 'feed';
            $datas['row_id'] = D('weiba_post')->where('post_id='.$data['post_id'])->getField('feed_id');
            $datas['app_uid'] = intval($data['post_uid']);
            $datas['to_comment_id'] = intval($reply_detail['comment_id']);
            $datas['to_uid'] = $data['to_uid'];
            $datas['uid'] = $data['uid'];
            $datas['content'] = preg_html($data['content']);
            $datas['ctime'] = $data['ctime'];
            $datas['client_type'] = getVisitorClient();
            if ($comment_id = D('comment')->add($datas)) {
                D('weiba_reply')->where('reply_id='.$data['reply_id'])->setField('comment_id', $comment_id);
                // 被评论内容的“评论统计数”加1，同时可检测出app，table，row_id的有效性
                D('feed')->where('feed_id='.$datas['row_id'])->setInc('comment_count');
                // 给应用UID添加一个未读的评论数
                if ($GLOBALS['ts']['mid'] != $datas['app_uid'] && $datas['app_uid'] != '') {
                    !$notCount && model('UserData')->updateKey('unread_comment', 1, true, $datas['app_uid']);
                }
                model('Feed')->cleanCache($datas['row_id']);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除评论forapi.
     *
     * @param reply_id 评论ID
     *
     * @return bool 是否回复成功
     */
    public function delReplyForApi($reply_id)
    {
        $comment_id = $this->where('reply_id='.$reply_id)->getField('comment_id');
        //echo $comment_id;exit;
        D('comment')->where('comment_id='.$comment_id)->delete();

        return $this->where('reply_id='.$reply_id)->delete();
    }
}
