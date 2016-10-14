<?php

//相册模块
class PhotoApi extends Api
{
    public function showAll()
    {
        return D('Album', 'photo')->findall();
    }
    public function showPhotos()
    {
        $map['albumId'] = intval($this->data['albumId']);
        if (!$map['albumId']) {
            return 0;
        }

        return D('Photo', 'photo')->where($map)->findall();
    }
    //创建相册
    public function createPhoto()
    {
        $data['userId'] = $this->mid;
        $data['name'] = t($this->data['name']);
        $data['privacy'] = intval($this->data['privacy']);
        $data['privacy_data'] = t($this->data['privacy_data']);
        if (!$data['userId'] || empty($data['name']) || !$data['privacy'] || ($data['privacy'] == 4 && empty($data['privacy_data']))) {
            return 0;
        }
        $albumId = M('PhotoAlbum')->add($data);
        if ($albumId > 0) {
            return $albumId;
        } else {
            return -1;
        }
    }

    //删除相册
    public function delAlbum()
    {
        $res = D('Album', 'photo')->deleteAlbum($this->data['id'], $this->mid);
        if ($res == false) {
            return 0;
        } else {
            return 1;
        }
    }

    //修改相册
    public function editAlbum()
    {
        //相册信息
        $map['id'] = intval($this->data['albumId']);
        $data['name'] = t($this->data['name']);
        $data['privacy'] = intval($this->data['privacy']);
        $data['privacy_data'] = t($this->data['privacy_data']);
        if (empty($this->mid) || empty($map['id']) || empty($data['name']) || empty($data['privacy'])) {
            return 0;
        } else {
            $res = M('PhotoAlbum')->where($map)->save($data);

            return $res;
        }
    }

    //查看相册属性
    public function getAlbumInfo()
    {
        $map['id'] = $this->data['id'];
        $res = D('Album', 'photo')->where($map)->find();
        if ($res == false) {
            return 0;
        } else {
            return $res;
        }
    }

    //上传照片
    public function uploadPhoto()
    {
        $albumId = intval($this->data['albumId']);
        $albumDao = D('Album', 'photo');
        $albumInfo = $albumDao->field('id')->find($albumId);
        $options['save_photo']['albumId'] = $albumId;
        $info = X('Xattach')->upload('photo', $options);
        $info['info'] = $this->save_photo($albumId, $info['info']);

        return $info;
    }

    //删除照片
    public function delPhoto()
    {
        $res = D('Album', 'photo')->deletePhoto(intval($this->data['id']), $this->mid);
        if ($res == false) {
            return 0;
        } else {
            return 1;
        }
    }

    //修改照片属性
    public function editPhoto()
    {
        $id = intval($this->data['id']);
        $map['albumId'] = intval($this->data['albumId']);

        if (!$id || !$map['albumId']) {
            return 0;
        }
        if (!empty($this->data['name'])) {
            $map['name'] = t($this->data['name']);
        }

        $photoDao = D('Photo', 'photo');
        $albumDao = D('Album', 'photo');
        //图片原信息
        $oldInfo = $photoDao->where("id={$id} AND userId={$this->mid}")->field('albumId')->find();
        //更新信息
        $result = $photoDao->where("id={$id} AND userId={$this->mid}")->save($map);
        //移动图片则重置相册图片数
        if ($map['albumId'] != $oldInfo['albumId']) {
            $albumDao->updateAlbumPhotoCount($map['albumId']);
            $albumDao->updateAlbumPhotoCount($oldInfo['albumId']);
        }

        return $result;
    }

    //获取照片属性
    public function getPhotoInfo()
    {
        if (empty($this->data['id'])) {
            return 0;
        } else {
            $map['id'] = $this->data['id'];
            $result = D('Photo')->where($map)->find();

            return $result;
        }
    }

    //发布（回复）评论
    public function addComment()
    {
        $this->data['with_new_weibo'] = intval($this->data['with_new_weibo']);
        $this->data['type'] = 'photo';
        $this->data['appid'] = intval($this->data['appid']);
        $this->data['comment'] = $this->data['comment'];
        $this->data['to_id'] = intval($this->data['to_id']);
        $this->data['author_uid'] = intval($this->data['author_uid']);
        $this->data['title'] = t(html_entity_decode($this->data['title'], ENT_QUOTES));
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
        $res = M('comment')->add($map);

        return $res;
    }

    //删除评论
    public function delComment()
    {
        $this->data['id'] = explode(',', t($this->data['id']));
        if (empty($this->data['id'])) {
            return ;
        }
        if (model('GlobalComment')->deleteComment($this->data['id'])) {
            //积分处理
            X('Credit')->setUserCredit($this->mid, 'delete_comment');
            echo 1;
            exit();
        } else {
            echo 0;
            exit();
        }
    }

    //查看评论
    public function scanComment()
    {
        $map['appid'] = intval($this->data['appid']);
        $map['type'] = 'photo';
        $list = model('GlobalComment')->where($map)->findall();

        return $list;
    }

    //是否同步到分享
    public function sendToWeibo()
    {
        $_REQUEST['with_new_weibo'] = intval($_REQUEST['with_new_weibo']);
        $_REQUEST['type'] = t($_REQUEST['type']);
        $_REQUEST['appid'] = intval($_REQUEST['appid']);
        $_REQUEST['comment'] = $_REQUEST['comment'];
        $_REQUEST['to_id'] = intval($_REQUEST['to_id']);
        $_REQUEST['author_uid'] = intval($_REQUEST['author_uid']);
        $_REQUEST['title'] = t(html_entity_decode($_REQUEST['title'], ENT_QUOTES));
        $_REQUEST['url'] = urldecode($_REQUEST['url']);
        $_REQUEST['table'] = t($_REQUEST['table']);
        $_REQUEST['id_field'] = t($_REQUEST['id_field']);
        $_REQUEST['comment_count_field'] = t($_REQUEST['comment_count_field']);
        $app_alias = getAppAlias($_REQUEST['type']);
            // 被回复内容
            $former_comment = array();
        if ($_REQUEST['to_id'] > 0) {
            $former_comment = M('comment')->where("`id`='{$_REQUEST['to_id']}'")->find();
        }
            // 插入新数据
            $map['type'] = $_REQUEST['type']; // 应用名
            $map['appid'] = $_REQUEST['appid'];
        $map['appuid'] = $_REQUEST['author_uid'];
        $map['uid'] = $this->mid;
        $map['comment'] = t(getShort($_REQUEST['comment'], $GLOBALS['ts']['site']['length']));
        $map['cTime'] = time();
        $map['toId'] = $_REQUEST['to_id'];
        $map['status'] = 0; // 0: 未读 1:已读
            $map['quietly'] = 0;
        $map['to_uid'] = $former_comment['uid'] ? $former_comment['uid'] : $_REQUEST['author_uid'];
        $map['data'] = serialize(array(
                                            'title' => keyWordFilter($_REQUEST['title']),
                                            'url' => $_REQUEST['url'],
                                            'table' => $_REQUEST['table'],
                                            'id_field' => $_REQUEST['id_field'],
                                            'comment_count_field' => $_REQUEST['comment_count_field'],
                                        ));
        $res = M('comment')->add($map);
            // 避免命名冲突
            unset($map['data']);
        if ($res) {
            // 发表分享
                if ($_REQUEST['with_new_weibo']) {
                    $from_data = array('app_type' => 'local_app', 'app_name' => $_REQUEST['type'], 'title' => $_REQUEST['title'], 'url' => $_REQUEST['url']);
                    $from_data = serialize($from_data);
                    D('Weibo', 'weibo')->publish($this->mid,
                                                array(
                                                    'content' => html_entity_decode(
                                                                     $_REQUEST['comment'].($_REQUEST['to_id'] > 0 ? (' //@'.getUserName($former_comment['uid']).' :'.$former_comment['comment']) : ''),
                                                                     ENT_QUOTES
                                                                 ),
                                                ), 0, 0, '', '', $from_data);
                }

                // 组装结果集
                $result = $map;
            $result['data']['uavatar'] = getUserSpace($this->mid, 'null', '_blank', '{uavatar}');
            $result['data']['uspace'] = getUserSpace($this->mid, 'null', '_blank', '{uname}');
                //$result['data']['comment']        = $_REQUEST['comment'];
                $result['data']['ctime'] = L('just_now');
            $result['data']['uname'] = getUserName($this->mid);
            $result['data']['comment'] = formatComment(t($_REQUEST['comment']));
            $result['data']['id'] = $res;
            $result['data']['userGroupIcon'] = getUserGroupIcon($this->mid);
            $result['data']['del_state'] = 1;

            return json_encode($result);
        } else {
            echo -1;
        }
    }
}
