<?php

//知识接口
class BlogApi extends Api
{
    //查看知识分类
    public function category()
    {
        if (empty($this->data['uid']) && empty($this->mid)) {
            return 0;
        } else {
            $uid = isset($this->data['uid']) ? intval($this->data['uid']) : intval($this->mid);
            $result = M('BlogCategory')->where("`uid`=$uid OR uid=0")->field('id,name')->findAll();

            return $result;
        }
    }
    //查看所有知识
    public function showAll()
    {
        return M('Blog')->findall();
    }

    //发表知识
    public function addBlog()
    {
        $data = $this->__getPost();
        if (empty($data['content']) || empty($data['title']) || empty($this->mid)) {
            return 0;
        }
        $add = D('Blog', 'blog')->doAddBlog($data, true);

        return $add;
    }

    //删除知识
    public function delBlog()
    {
        if (empty($this->data['id']) || empty($this->mid)) {
            return 0;
        } else {
            $map['id'] = $this->data['id'];
            $res = D('Blog', 'blog')->doDeleteblog($map, $this->mid);

            return $res;
        }
    }

    //编辑知识
    public function editBlog()
    {
        $map['id'] = $this->data['id'];
        $data['title'] = $this->data['title'];
        $data['content'] = $this->data['content'];
        if ($map['id'] && $data['title'] && $data['content']) {
            D('Blog', 'blog')->where($map)->save($data);
        } else {
            return false;
        }
    }
    //查看知识
    public function viewBlog()
    {
        if (empty($this->data['id'])) {
            return 0;
        } else {
            $res = M('Blog')->where(array('id' => $this->data['id'], 'uid' => $this->data['uid']))->find();

            return $res;
        }
    }
    //评论知识
    public function commentBlog()
    {
        $map['type'] = $this->data['type']; // 应用名
        $map['type'] = 'blog';
        $map['appid'] = $this->data['appid']; //知识id
        $map['appuid'] = $this->data['author_uid']; //作者uid
        $map['uid'] = $this->mid;
        $map['comment'] = t(getShort($this->data['comment'], $GLOBALS['ts']['site']['length']));
        $map['cTime'] = time();
        //$map['toId']	= $this->data['to_id'];//废弃
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
        if (empty($map['type']) || empty($map['comment']) || empty($map['appid']) || empty($map['appuid'])) {
            return 0;
        }
        $res = M('comment')->add($map);

        return $res;
    }
    private function __getPost()
    {
        //得到发知识人的名字
        $userName = M('user')->where('uid='.$this->mid)->getField('uname');
        $data['name'] = $userName;
        $data['content'] = safe($this->data['content']);
        $data['uid'] = $this->mid;
        $data['category'] = intval($this->data['category']);
        $data['password'] = text($this->data['password']);
        $data['mention'] = $this->data['fri_ids'];
        $data['title'] = !empty($this->data['title']) ? text($this->data['title']) : '无标题';
        $data['private'] = intval($this->data['private']);
        $data['canableComment'] = intval(t($this->data['cc']));
        //处理attach数据
        $data['attach'] = serialize($this->__wipeVerticalArray($this->data['attach']));
        if (empty($this->data['attach']) || !isset($this->data['attach'])) {
            $data['attach'] = null;
        }

        return $data;
    }
    private function __wipeVerticalArray($array)
    {
        $result = array();
        foreach ($array as $key => $value) {
            $temp = explode('|', $value);
            $result[$key]['id'] = $temp[0];
            $result[$key]['name'] = $temp[1];
        }

        return $result;
    }
}
