<?php

//投票接口
include_once SITE_PATH.'/apps/vote/Lib/Model/BaseModel.class.php';
class VoteApi extends Api
{
    //查看所有投票id
    public function showAll()
    {
        return M('Vote')->Field('id,title')->findAll();
    }
    //查看所有投票id
    public function voteIds()
    {
        return M('Vote')->getField('id,title');
    }
    //查看某投票
    public function viewVote()
    {
        $id = intval($this->data['id']);
        $data['vote'] = M('Vote')->where(array('id' => $id))->find();
        $data['vote']['name'] = getUserName($data['vote']['uid']);
        $data['vote_opts'] = D('VoteOpt')->where("vote_id = $id")->order('id asc')->findAll();
        $data['vote_users'] = D('VoteUser')->where("vote_id = $id AND opts<>'' ")->findAll();

        return $data;
    }
    //评论某投票
    public function commentVote()
    {
        $this->data['with_new_weibo'] = intval($this->data['with_new_weibo']);
        //$this->data['type']					= t($this->data['type']);
        $this->data['type'] = 'vote';
        $this->data['appid'] = intval($this->data['appid']);
        $this->data['comment'] = $this->data['comment'];
        $this->data['to_id'] = intval($this->data['to_id']);
        $this->data['author_uid'] = intval($this->data['author_uid']);
        $this->data['title'] = t(html_entity_decode($this->data['title'], ENT_QUOTES, 'UTF-8'));
        $this->data['url'] = urldecode($this->data['url']);
        $this->data['table'] = t($this->data['table']);
        $this->data['id_field'] = t($this->data['id_field']);
        $this->data['comment_count_field'] = t($this->data['comment_count_field']);
        $app_alias = getAppAlias($this->data['type']);
        // 被回复内容
        $former_comment = array();
        if ($this->data['to_id'] > 0) {
            $former_comment = M('comment')->where("`id`='{$this->data['to_id']}'")->find();
        }
        // 插入新数据
        $map['type'] = $this->data['type']; // 应用名
        $map['appid'] = $this->data['appid'];
        $map['appuid'] = $this->data['author_uid'];
        $map['uid'] = $this->mid;
        $map['comment'] = t(getShort($this->data['comment'], $GLOBALS['ts']['site']['length']));
        $map['cTime'] = time();
        $map['toId'] = $this->data['to_id'];
        $map['status'] = 0; // 0: 未读 1:已读
        $map['quietly'] = 0;
        $map['to_uid'] = $former_comment['uid'] ? $former_comment['uid'] : $this->data['author_uid'];
        $map['data'] = serialize(array(
                                        'title' => $this->data['title'],
                                        'url' => $this->data['url'],
                                        'table' => $this->data['table'],
                                        'id_field' => $this->data['id_field'],
                                        'comment_count_field' => $this->data['comment_count_field'],
                                    ));
        if (empty($map['type']) && empty($map['appid']) && empty($map['appuid']) && empty($map['comment'])) {
            return 0;
        }
        $res = M('comment')->add($map);

        return $res;
    }
    //发起投票
    public function createVote()
    {
        $data['title'] = t($this->data['title']);
        if ($this->data['date'] == 'custom') {
            $data['deadline'] = mktime($this->data['deadline']['hour'], 0, 0, $this->data['deadline']['month'], $this->data['deadline']['day'], $this->data['deadline']['year']);
        } else {
            $data['deadline'] = time() + $this->data['date'] * 86400;
        }
        $data['uid'] = $this->mid;
        $data['explain'] = h($this->data['explain']);
        $data['type'] = intval($this->data['type']);
        $data['onlyfriend'] = intval($this->data['onlyfriend']);
        $data['cTime'] = time();
        $opt = $this->data['opt'];
        $opt = explode(',', $this->data['opt']);
        $_POST['opt'] = $opt;
        if ($opt !== null && $data['title'] !== null && $opt !== null) {
            return $result = D('Vote', 'vote')->addVote($data, $opt);
        } else {
            return 0;
        }
    }
    //删除投票
    public function destroyVote()
    {
        $res = D('Vote', 'vote')->doDeleteVote($this->data['id']);
        if ($res == false) {
            return 0;
        } else {
            return 1;
        }
    }
    //参与投票
    public function joinVote()
    {
        //用户投票信息
        $voteUserDao = D('VoteUser');
        $vote_id = intval($this->data['vote_id']);
        //检查ID是否合法
        if (empty($vote_id) || 0 == $vote_id) {
            return 0;
            exit;
        }
        $this->data['opts_ids'] = rtrim(t($this->data['opts_ids']), ',');
        if (!$this->data['opts_ids']) {
            return 0;
            exit;
        }
        //先看看投票期限过期与否
        $voteDao = D('Vote');
        $the_vote = $voteDao->where("id=$vote_id")->find();
        $vote_user_id = $the_vote['uid'];
        $deadline = $the_vote['deadline'];
        if ($deadline <= time()) {
            return -3;
            exit;
        }
        //再看看投过没
        $count = $voteUserDao->where("vote_id=$vote_id AND uid=$this->mid AND opts <>''")->count();
        if ($count > 0) {
            return -1;
            exit;
        }
        //读取选项
        $vote_opts = D('VoteOpt')->where('id IN ('.$this->data['opts_ids'].')')->findAll();
        $vote_opts = implode(',', getSubByKey($vote_opts, 'name'));
        //如果没投过，就添加
        $data['vote_id'] = $vote_id;
        $data['uid'] = $this->mid;
        $data['opts'] = $vote_opts;
        $data['cTime'] = time();
        if (empty($data['opts'])) {
            return 0;
            exit;
        }
        $addid = $voteUserDao->add($data);
        //投票选项信息的num+1
        $dao = D('VoteOpt');
        $opts_ids = rtrim(t($this->data['opts_ids']), ',');
        $opts_ids = explode(',', $opts_ids);
        foreach ($opts_ids as $v) {
            $v = intval($v);
            $dao->setInc('num', "id=$v");
        }
        //投票信息的vote_num+1
        D('Vote')->setInc('vote_num', "id=$vote_id");
        if ($the_vote['uid'] != $this->mid) {
            X('Credit')->setUserCredit($the_vote['uid'], 'joined_vote')
                       ->setUserCredit($this->mid, 'join_vote');
        }
        echo 1;
        exit;
    }
}
