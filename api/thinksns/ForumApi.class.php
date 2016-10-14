<?php

// 论坛API接口
class ForumApi extends Api
{
    //获取版块列表
    public function board()
    {
        return D('Core', 'forum')->getCategory();
    }

    //获取版块分类列表
    public function category()
    {
        $fid = intval($this->data['fid']);
        if (!$fid) {
            return 0;
        }

        return D('ForumApi', 'forum')->getCategoryInBoard($fid);
    }

    //显示所有帖子
    public function showAll()
    {
        $fid = empty($this->data['fid']) ? 0 : intval($this->data['fid']);
        $row = empty($this->data['row']) ? 0 : intval($this->data['row']);
        $data = D('ForumApi', 'forum')->getAllTopics($fid, $row);

        return $data;
    }

    // 发帖
    // @param string $title 帖子标题
    // @param string $content 帖子内容
    // @param int $class 版块ID
    // @param int $category 版块分类ID
    public function postTopic()
    {
        $title = $this->data['title'];
        $content = $this->data['content'];
        if (!empty($this->data['tboard'])) {
            $class = intval($this->data['tboard']);
        } else {
            $class = intval($this->data['fid']);
        }
        $category = empty($this->data['category']) ? 0 : intval($this->data['category']);
        $uid = $this->mid;
        $result = D('ForumApi', 'forum')->postTopic($title, $content, $class, $category, $uid);

        return $result;
    }

    // 删帖 - OK
    // @param int $tid 论坛ID
    public function delTopic()
    {
        $tid = intval($this->data['tid']);
        $result = D('ForumApi', 'forum')->delTopic($tid);

        return $result;
    }

    //删除帖子回复
    public function delComment()
    {
        $pid = intval($this->data['pid']);
        $uid = $this->mid;
        $result = D('ForumApi', 'forum')->delComment($pid, $uid);

        return $result;
    }
    // 回复某个帖子 - OK
    // @param int tid 论坛ID
    // @param string content 内容
    public function replyTopic()
    {
        $tid = intval($this->data['tid']);
        $uid = $this->mid;
        $title = $this->data['title'];
        $content = $this->data['reply_content'];
        $quote = $this->data['quote'];
        $result = D('ForumApi', 'forum')->replyTopic($tid, $uid, $title, $content, $quote);

        return $result;
    }
    //编辑回复的帖子
    public function replyEdit()
    {
        $pid = intval($this->data['pid']);
        $content = $this->data['reply_content'];
        $title = $this->data['title'];
        $tid = $this->data['tid'];
        $result = D('ForumApi', 'forum')->replyEdit($tid, $pid, $content, $title);

        return $result;
    }
    // 对自己的帖子进行封贴 - OK
    // @param int $tid 论坛ID
    public function notAllowReply()
    {
        $tid = intval($this->data['tid']);
        $uid = $this->mid;
        $result = D('ForumApi', 'forum')->notAllowReply($uid, $tid);

        return $result;
    }

    // 查看自己所有的帖子 - OK
    public function getAllMyTopics($uid)
    {
        $uid = $uid;
        $data = D('ForumApi', 'forum')->getAllMyTopics($uid);

        return $data;
    }

    // 查看自己所评论的所有帖子 - OK
    public function getAllMyCommentTopics()
    {
        $uid = $this->mid;
        $row = intval($this->data['row']);
        $data = D('ForumApi', 'forum')->getAllMyCommentTopics($uid, $row);

        return $data;
    }

    // 查看帖子详细资料（浏览数、回复数、发布信息等） - OK
    // @param int $tid 帖子ID
    // @return array $data 帖子信息
    public function getThreadDetail()
    {
        $tid = intval($this->data['tid']);
        $data = D('ForumApi', 'forum')->getForumDetail($tid);

        return $data;
    }

    //查看特定帖子的回复
    public function getComments()
    {
        $tid = intval($this->data['tid']);
        $data = D('ForumApi', 'forum')->getComments($tid);

        return $data;
    }

    //编辑帖子
    public function editTopic()
    {
        $title = $this->data['title'];
        $content = $this->data['content'];
        $tid = $tid->data['tid'];
        if (!empty($this->data['tboard'])) {
            $class = intval($this->data['tboard']);
        } else {
            $class = intval($this->data['fid']);
        }
        $category = empty($this->data['category']) ? 0 : intval($this->data['category']);
        $uid = $this->mid;
        $result = D('ForumApi', 'forum')->editTopic($this->data['tid'], $title, $content, $class, $category, $uid);

        return $result;
    }
    // 编辑帖子回复
    public function editComment()
    {
        $pid = intval($this->data['pid']);
        $data = D('ForumApi', 'forum')->editComment($pid);

        return $data;
    }

    //置顶 精华
    public function topicSet()
    {
        $tid = $this->data['tid'];
        $type = $this->data['type'];
        $res = D('ForumApi', 'forum')->topicSet($tid, $type);

        return $res;
    }
}
