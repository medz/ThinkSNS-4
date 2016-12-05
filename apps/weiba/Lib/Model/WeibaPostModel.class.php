<?php
/**
 * 微吧模型 - 数据对象模型.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class WeibaPostModel extends Model
{
    protected $tableName = 'weiba_post';
    protected $error = '';
    protected $fields = array(
                            0  => 'post_id', 1 => 'weiba_id', 2 => 'post_uid', 3 => 'title', 4 => 'content', 5 => 'post_time',
                            6  => 'reply_count', 7 => 'read_count', 8 => 'last_reply_uid', 9 => 'last_reply_time', 10 => 'digest', 11 => 'top', 12 => 'lock',
                            13 => 'api_key', 14 => 'domain', 15 => 'is_index', 16 => 'index_img', 17 => 'reg_ip',
                            18 => 'is_del', 19 => 'feed_id', 20 => 'reply_all_count', 21 => 'attach', 22 => 'form', 23 => 'top_time', 24 => 'is_index_time', '_autoinc' => true, '_pk' => 'post_id',
                        );

    /**
     * 发帖同步到分享.
     *
     * @param int post_id 帖子ID
     * @param string title 帖子标题
     * @param string content 帖子内容
     * @param int uid 发布者uid
     *
     * @return int feed_id 分享ID
     */
    public function syncToFeed($post_id, $title, $content, $uid)
    {
        $d['content'] = '';
        $d['body'] = '帖子&nbsp;|&nbsp;'.$title.'-'.getShort($content, 100).'&nbsp;';
        $feed = model('Feed')->put($uid, 'weiba', 'weiba_post', $d, $post_id, 'weiba_post');

        return $feed['feed_id'];
    }

    /**
     * 发表帖子forapi.
     *
     * @param int weiba_id 微吧ID
     * @param varchar title 帖子标题
     * @param varchar content 帖子内容
     * @param int user_id 帖子作者
     */
    public function createPostForApi($weiba_id, $title, $content, $uid)
    {
        $data['weiba_id'] = intval($weiba_id);
        $data['title'] = t($title);
        $data['content'] = h($content);
        $data['post_uid'] = intval($uid);
        $data['post_time'] = time();
        $data['last_reply_time'] = $data['post_time'];
        $res = D('weiba_post')->add($data);
        if ($res) {
            D('weiba')->where('weiba_id='.$data['weiba_id'])->setInc('thread_count');
            //同步到分享
            $feed_id = $this->syncToFeed($res, $data['title'], $data['content'], $data['post_uid']);
            D('weiba_post')->where('post_id='.$res)->setField('feed_id', $feed_id);

            return true;
        } else {
            return false;
        }
    }

    /**
     * 收藏帖子.
     *
     * @param int post_id 帖子ID
     */
    public function favoriteForApi($post_id)
    {
        $postDetail = D('weiba_post')->where('post_id='.intval($post_id))->find();
        $data['post_id'] = intval($post_id);
        $data['weiba_id'] = $postDetail['weiba_id'];
        $data['post_uid'] = $postDetail['post_uid'];
        $data['uid'] = $GLOBALS['ts']['mid'];
        $data['favorite_time'] = time();
        if (D('weiba_favorite')->add($data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取消收藏帖子.
     *
     * @param int post_id 帖子ID
     */
    public function unfavoriteForApi($post_id)
    {
        $map['post_id'] = intval($post_id);
        $map['uid'] = $GLOBALS['ts']['mid'];
        if (D('weiba_favorite')->where($map)->delete()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 为feed提供应用数据来源信息 - 与模板weiba_post.feed.php配合使用.
     *
     * @param int row_id 帖子ID
     * @param bool _forApi 提供给API的数据
     */
    public function getSourceInfo($row_id, $_forApi = false)
    {
        $info = $this->find($row_id);
        if (!$info) {
            return false;
        }
        $info['source_user_info'] = model('User')->getUserInfo($info['post_uid']);
        $info['source_user'] = $info['post_uid'] == $GLOBALS['ts']['mid'] ? L('PUBLIC_ME') : $info['source_user_info']['space_link'];            // 我
        $info['source_type'] = L('PUBLIC_WEIBA');
        $info['source_title'] = $forApi ? parseForApi($info['source_user_info']['space_link']) : $info['source_user_info']['space_link'];    //分享title暂时为空
        $info['source_url'] = U('weiba/Index/postDetail', array('post_id' => $row_id));
        $info['ctime'] = $info['post_time'];
        $feed = D('feed_data')->field('feed_id,feed_content')->find($info['feed_id']);
        $info['source_content'] = $feed['feed_content'];
        $info['app_row_table'] = 'weiba_post';
        $info['app_row_id'] = $info['post_id'];

        return $info;
    }
}
