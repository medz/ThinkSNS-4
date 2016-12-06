<?php
/**
 * WebMessageAction Web消息模块.
 *
 * @version TS4.0
 */
class WebMessageAction extends Action
{
    public function tz()
    {
        $map['uid'] = $this->mid;
        $list = D('notify_message')->where($map)->order('ctime desc')->findpage(20);
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['body'] = parse_html($v['body']);
            if ($v['appname'] != 'public') {
                $list['data'][$k]['app'] = model('App')->getAppByName($v['appname']);
            }
        }
        model('Notify')->setRead($this->mid);
        $this->assign('list', $list);
        $this->display();
    }

    public function pl()
    {
        // 安全过滤
        $type = t($_GET['type']);
        if (empty($_GET['type'])) {
            $type = $_GET['type'] = 'receive';
        }

        if ($type == 'send') {
            $keyword = '发出';
            $map['uid'] = $this->mid;
        } else {
            // 分享配置
            $weiboSet = model('Xdata')->get('admin_Config:feed');
            $this->assign('weibo_premission', $weiboSet['weibo_premission']);
            $keyword = '收到';
            //获取未读评论的条数
            $this->assign('unread_comment_count', model('UserData')->where('uid='.$this->mid." and `key`='unread_comment'")->getField('value'));
            // 收到的
            $map['_string'] = " (to_uid = '{$this->uid}' OR app_uid = '{$this->mid}') AND uid !=".$this->mid;
        }

//		$d['tab'] = model('Comment')->getTab($map);
        $d['tab'] = model('Comment')->getTabForApp($map);
        foreach ($d['tab'] as $key => $vo) {
            if ($key == 'feed') {
                $d['tabHash']['feed'] = L('PUBLIC_WEIBO');
            } elseif ($key == 'webpage') {
                $d['tabHash']['webpage'] = '评论箱';
            } else {
                // 微吧
                strtolower($key) === 'weiba_post' && $key = 'weiba';

                $langKey = 'PUBLIC_APPNAME_'.strtoupper($key);
                $lang = L($langKey);
                if ($lang == $langKey) {
                    $d['tabHash'][$key] = ucfirst($key);
                } else {
                    $d['tabHash'][$key] = $lang;
                }
            }
        }
        ksort($d['tab']);
        $this->assign($d);

        // 安全过滤
        $t = t($_GET['t']);
//		!empty($t) && $map['table'] = $t;
        !empty($t) && $map['app'] = $t;
        if ($t == 'feed') {
            $map['app'] = 'public';
        }
        $list = model('Comment')->setAppName(t($_GET['app_name']))->getCommentList($map, 'comment_id DESC', null, true);
        foreach ($list['data'] as $k => $v) {
            if ($v['sourceInfo']['app'] == 'weiba') {
                $list['data'][$k]['sourceInfo']['source_body'] = str_replace($v['sourceInfo']['row_id'], $v['comment_id'], $v['sourceInfo']['source_body']);
            }
            if ($v['table'] === 'webpage') {
                $list['data'][$k]['hasComment'] = false;
            } else {
                $list['data'][$k]['hasComment'] = true;
            }
            $sourceInfo = $v['sourceInfo'];

            $sourceContent = null;
            $sourceImage = null;
            $sourceUrl = $sourceInfo['source_url'];
            if ($sourceInfo && $sourceInfo['is_del'] == 0) {
                if ($sourceInfo['app'] == 'weiba' && $sourceInfo['is_repost'] == 0) {
                    if (!empty($sourceInfo['api_source'])) {
                        if (empty($sourceInfo['api_source']['title']) && empty($sourceInfo['api_source']['content'])) {
                            $sourceContent = '原帖子内容已被删除~';
                            $sourceUrl = 'javascript:;';
                        } else {
                            $sourceContent = '帖子&nbsp;|&nbsp;'.getShort(t($sourceInfo['api_source']['title'].' - '.$sourceInfo['api_source']['content']), 100);
                            $sourceImage = $sourceInfo['api_source']['pic_url_small'];
                            $sourceUrl = $sourceInfo['api_source']['source_url'];
                        }
                    }
                } elseif ($sourceInfo['type'] == 'postvideo') {
                    if (strpos($sourceInfo['flashimg'], '://')) {
                        $sourceImage = $sourceInfo['flashimg'];
                    } else {
                        $sourceImage = getImageUrl($sourceInfo['flashimg'], 120, 120, true);
                    }
                } elseif ($sourceInfo['type'] == 'postimage') {
                    $sourceImage = $sourceInfo['attach'][0]['attach_small'];
                }
                if (empty($sourceContent)) {
                    if (!empty($sourceInfo['feed_content'])) {
                        $sourceContent = t($sourceInfo['feed_content']);
                    } elseif (!empty($sourceInfo['source_content'])) {
                        $sourceContent = t($sourceInfo['source_content']);
                    } elseif (!empty($sourceInfo['source_body'])) {
                        $sourceContent = t($sourceInfo['source_body']);
                    }
                }
                if (empty($sourceContent)) {
                    $sourceContent = '原分享内容已被删除~';
                    $sourceUrl = 'javascript:;';
                } else {
                    $sourceContent = preg_replace('/\s+/u', ' ', $sourceContent);
                }
            } else {
                $sourceContent = '原分享内容已被删除~';
                $sourceUrl = 'javascript:;';
            }

            $list['data'][$k]['sourceContent'] = $sourceContent;
            $list['data'][$k]['sourceIsVideo'] = $sourceInfo['type'] == 'postvideo';
            $list['data'][$k]['sourceImage'] = $sourceImage;
            $list['data'][$k]['sourceUrl'] = $sourceUrl;
        }
        model('UserCount')->resetUserCount($this->mid, 'unread_comment', 0);
        $this->assign('list', $list);
        $this->setTitle($keyword.'的评论');                    // 我的评论
        $userInfo = model('User')->getUserInfo($this->mid);
        $this->setKeywords($userInfo['uname'].$keyword.'的评论');
        $this->assign('type', $type);
        $this->assign('t', $t);
        $this->display();
    }

    public function zan()
    {
        // 关联未读数量
        $keys = array(
            'feed'        => 'unread_digg',
            'weiba_post'  => 'unread_digg_weibapost',
            'weiba_reply' => 'unread_digg_weibareply',
        );
        $unreadCount = array();
        $userData = M('UserData')->getUserData($this->mid);
        //存在APP
        if (D('App')->isAppNameExist('weiba')) {
            $types = array(
                'feed'        => '分享',
                'weiba_post'  => '微吧帖子',
                'weiba_reply' => '微吧回复',
            );
        } else { //不存在
            $types = array(
                'feed' => '分享',
            );
        }
        foreach ($types as $key => $val) {
            $count = (int) $userData[$keys[$key]];
            if ($key == 'feed' || $type == $key) {
                $unreadCount[$key] = $count;
                continue;
            }
            $method = $key.'Zan';
            if ($this->$method($ismy, true) <= 0) {
                unset($types[$key]);
            } else {
                $unreadCount[$key] = $count;
            }
        }
        if (!isset($types[$_GET['type']])) {
            $type = 'feed';
            // 自动跳到新消息页
            if (empty($_GET['type'])) {
                foreach ($unreadCount as $key => $val) {
                    if ($val > 0) {
                        $type = $key;
                        break;
                    }
                }
            }
        } else {
            $type = $_GET['type'];
        }

        model('UserCount')->resetUserCount($this->mid, $keys[$type], 0);

        $ismy = !empty($_GET['ismy']);
        $method = $type.'Zan';
        $result = $this->$method($ismy);

        $this->assign('list', $result);
        $this->assign('types', $types);
        $this->assign('type', $type);
        $this->assign('ismy', $ismy);
        $this->assign('unreadCount', $unreadCount);
        $this->assign('surplusCount', array_sum($unreadCount) - $unreadCount[$type]);
        $this->display();
    }

    public function lxr()
    {
        $this->friends();
        //$this->roomList();
    }

    /**
     * At me消息.
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function at()
    {
        $this->assign('list', model('Atme')->getAtmeList(array('uid' => $this->mid)));
        $this->display('at');
    }

    public function at2()
    {
        echo '<pre>';
        print_r(model('Atme')->getAtmeList(array('uid' => $this->mid)));
        exit;
        $this->display('at');
    }

    public function roomList()
    {
        $list = model('WebMessage')->getRoomList();
        $this->assign('list', $list);
        $this->assign('currentUserId', $this->mid);
        $this->display('roomList');
    }

    public function latelyRoomList()
    {
        $limit = floor($_GET['limit']);
        $limit = $limit <= 0 ? 10 : $limit;
        $list = model('WebMessage')->getRoomList($limit, false);
        $data = array();
        foreach ($list as $key => $rs) {
            $data[$key]['room_id'] = $rs['list_id'];
            $data[$key]['msg_new'] = $rs['msg_new'];
            if ($rs['type'] == 2) {
                $data[$key]['title'] = '来自群消息';
                if ($rs['title']) {
                    $data[$key]['title'] = '群:'.$rs['title'];
                }
                if ($rs['logo'] > 0) {
                    $data[$key]['src'] = getImageUrlByAttachId($rs['logo'], 50, 50);
                }
                if (!isset($data[$key]['src']) or !$data[$key]['src']) {
                    $data[$key]['src'] = THEME_PUBLIC_URL.'/image/message/group.png';
                }
            } else {
                $_uid = intval(trim(str_replace('_'.$this->mid.'_', '_', '_'.$rs['min_max'].'_'), '_'));
                $_user = getUserInfo($_uid);
                $data[$key]['title'] = '联系人:'.$_user['uname'];
                $data[$key]['src'] = $_user['avatar_small'];
                $data[$key]['uid'] = $_uid;
                $data[$key]['min_max'] = $rs['min_max'];
            }
        }

        /* # 评论， 赞， 通知，AT */
        $info = array();

        /* # 评论 */
        $info['comment'] = model('UserData')->where('`uid`='.$this->mid." AND `key`='unread_comment'")->getField('value');

        /* # 赞 */
        $info['digg'] = model('UserData')->where('`uid`='.$this->mid." AND `key`='unread_digg'")->getField('value');

        /* # 通知 */
        $info['notice'] = D('notify_message')->where('`uid` = '.$this->mid.' AND `is_read` != 1')->field('`id`')->count();

        /* # At me */
        $info['at'] = model('UserData')->where('`uid`='.$this->mid." AND `key`='unread_atme'")->getField('value');

        $this->ajaxReturn($data, $info);
    }

    public function friends()
    {
        $mid = $this->mid;
        $data = model('Follow')->getFriendsForApi($mid, $mid, 0, 0, 9999);
        $exclude = array();
        if (!empty($_GET['roomid'])) {
            $members = model('WebMessage')->getRoomMember($_GET['roomid']);
            if ($members) {
                $exclude = array_column($members, 'member_uid');
            }
            $this->assign('addGroupMember', true);
            $this->assign('roomId', (int) $_GET['roomid']);
            $this->assign('memberCount', $members ? count($members) : 0);
        }
        $array = array();
        foreach ($data as $key => $val) {
            if (in_array($val['uid'], $exclude)) {
                continue;
            }
            $first_letter = strtoupper($val['first_letter']);
            if (isset($array[$first_letter])) {
                $array[$first_letter][] = $val;
            } else {
                $array[$first_letter] = array($val);
            }
        }
        ksort($array);
        $this->assign('count', $data ? count($data) : 0);
        $this->assign('data', $array);
        $this->display('friends');
    }

    public function room()
    {
        $webMessage = model('WebMessage');
        if (!empty($_GET['uid'])) {
            $room = $webMessage->getMessageRoom((int) $_GET['uid']);
            $roomId = $room['list_id'];
        } else {
            $roomId = (int) $_GET['roomid'];
        }

        $list = $webMessage->getMessageList($roomId, null, 'lt', 6);
        $isMore = count($list) == 6;
        if ($isMor) {
            array_shift($list);
        }
        $this->assign('isMore', $isMore);
        $webMessage->clearMessage($roomId, 'unread');
        $data = $this->buildMsgList($list, $webMessage->getUserId(), true);
        if ($list) {
            $last = end($list);
            $lastMessageId = (int) $last['message_id'];
        } else {
            $lastMessageId = 0;
        }
        $room = $webMessage->room()->find($roomId);
        $members = $webMessage->getRoomMember($roomId);
        $this->assign('room', $room);
        $this->assign('members', $members);
        $this->assign('title', $this->getRoomTitle($room, $members));
        $this->assign('list', $data);
        $this->assign('lastMessageId', $lastMessageId);
        $this->assign('curentUid', $webMessage->getUserId());
        $this->assign('roomId', $roomId);
        $this->display();
    }

    public function getMsgList()
    {
        $webMessage = model('WebMessage');
        $list = $webMessage->getMessageList((int) $_GET['roomid'], (int) $_GET['msgid'], 'lt', 20);
        $this->assign('isGetMessageList', true);
        $data = $this->buildMsgList($list, $webMessage->getUserId());
        $this->ajaxReturn($data, '', 1);
    }

    public function pullMessage($roomId = null, $msgId = null)
    {
        if (null === $roomId && null === $msgId) {
            $roomId = (int) $_GET['roomid'];
            $msgId = (int) $_GET['msgid'];
        }
        $webMessage = model('WebMessage');
        $list = $webMessage->getMessageList($roomId, $msgId, 'gt');
        $webMessage->clearMessage($roomId, 'unread');
        $data = $this->buildMsgList($list, $webMessage->getUserId());
        if ($list) {
            $last = end($list);
            $lastMessageId = (int) $last['message_id'];
        } else {
            $lastMessageId = '';
        }
        $this->ajaxReturn($data, $lastMessageId, 1);
    }

    public function sendText()
    {
        ignore_user_abort(true);
        $webMessage = model('WebMessage');
        $result = $webMessage->sendMessage(array(
            'room_id'      => $_POST['room_id'],
            'content'      => $_POST['content'],
            'message_type' => 'text',
        ));

        if ($result) {
            if (isset($_POST['msgid'])) {
                $this->pullMessage($_POST['room_id'], $_POST['msgid']);
            } else {
                $html = $this->buildMsgList(array($result));
                $this->ajaxReturn($html, (int) $result['message_id'], 1);
            }
        } else {
            $this->ajaxReturn('', '发送失败', 0);
        }
    }

    public function sendImage()
    {
        $attachs = $this->uploadFile('image', 'message_image', 'gif,jpg,png,jpeg,bmp');
        if (isset($attachs[0])) {
            $webMessage = model('WebMessage');
            $result = $webMessage->sendMessage(array(
                'room_id'      => $_GET['room_id'],
                'attach_id'    => $attachs[0],
                'message_type' => 'image',
            ));
            if ($result) {
                $html = $this->buildMsgList(array($result));
                $data = array('status' => 1, 'info' => (int) $result['message_id'], 'id' => t($_GET['id']), 'data' => $html);
            }
        }
        if (!isset($data)) {
            $data = array('status' => 0, 'id' => t($_GET['id']), 'info' => '图片发送失败');
        }
        echo '<script> window.parent.sendImageCallback('.json_encode($data).'); </script>';
        exit;
    }

    public function createGroupRoom()
    {
        $webMessage = model('WebMessage');
        if (is_numeric($_POST['uids'])) {
            $room = $webMessage->getMessageRoom((int) $_POST['uids']);
        } else {
            $room = $webMessage->createGroupRoom($_POST['uids'], (string) $_POST['title']);
        }

        if ($room) {
            $this->ajaxReturn($room['list_id'], '', 1);
        } else {
            $msg = is_numeric($_POST['uids']) ? '发起聊天失败' : '创建群聊失败';
            $this->ajaxReturn('', $msg, 0);
        }
    }

    public function addGroupMember()
    {
        $webMessage = model('WebMessage');
        $result = $webMessage->addGroupMember((int) $_REQUEST['roomid'], $_POST['uids']);
        if ($result) {
            $this->ajaxReturn((int) $_REQUEST['roomid'], '', 1);
        } else {
            $this->ajaxReturn('', '添加群成员失败', 0);
        }
    }

    public function removeGroupMember()
    {
        $webMessage = model('WebMessage');
        $result = $webMessage->removeGroupMember((int) $_REQUEST['roomid'], $_POST['uids']);
        if ($result) {
            $this->ajaxReturn((int) $_REQUEST['roomid'], '', 1);
        } else {
            $this->ajaxReturn('', '移除群成员失败', 0);
        }
    }

    public function quitGroupRoom()
    {
        $webMessage = model('WebMessage');
        $result = $webMessage->quitGroupRoom((int) $_REQUEST['roomid']);
        if ($result) {
            $this->ajaxReturn((int) $_REQUEST['roomid'], '', 1);
        } else {
            $this->ajaxReturn('', '退出群房间失败', 0);
        }
    }

    public function groupMember()
    {
        $roomId = (int) $_GET['roomid'];
        $webMessage = model('WebMessage');
        if ($webMessage->roomHasUser($roomId, $this->mid)) {
            $members = $webMessage->getRoomMember($roomId);
            $this->assign('members', $members);
            $this->assign('roomId', $roomId);
            $this->assign('currentUserId', $this->mid);
            $this->assign('room', $webMessage->room()->find($roomId));
            $this->display();
        } else {
            exit('你无权查看此房间成员');
        }
    }

    public function clearMessage()
    {
        $roomId = (string) $_REQUEST['roomid'];
        $webMessage = model('WebMessage');
        if ($webMessage->clearMessage($roomId, 'all')) {
            $this->ajaxReturn($roomId, '', 1);
        } else {
            $this->ajaxReturn($roomId, '', 0);
        }
    }

    protected function buildMsgList($list, $userId = null, $isInit = false)
    {
        if (!$list) {
            return '';
        }
        if (null === $userId) {
            $userId = $this->mid;
        }
        // dump($list);exit;
        $this->assign('prevTime', intval($_REQUEST['msgPrevTime']));
        $this->assign('list', $list);
        $this->assign('isInit', $isInit);
        $this->assign('currentUserId', $userId);

        return $this->fetch('msg_list');
    }

    protected function uploadFile($uploadType, $attachType, $allowTypes)
    {
        $option = array(
            'attach_type' => $attachType,
        );
        if (is_array($allowTypes)) {
            $option['allow_exts'] = implode(',', $ext);
        } else {
            $option['allow_exts'] = $allowTypes;
        }

        $file = model('Attach')->upload(array(
            'upload_type' => $uploadType,
        ), $option);

        // 判断是否有上传
        if (count($file['info']) <= 0 || !$file['status']) {
            return false;
        }

        $data = array();
        foreach ($file['info'] as $value) {
            $data[] = $value['attach_id'];
        }

        return $data;
    }

    protected function weiba_postZan($ismy, $count = false)
    {
        if ($ismy) {
            $where = array('uid' => $this->mid);
        } else {
            $dbprefix = C('DB_PREFIX');
            $sql = "SELECT post_id FROM {$dbprefix}weiba_post WHERE post_uid={$this->mid}";
            $where = array('post_id' => array('in', $sql));
        }
        if ($count) {
            return M('WeibaPostDigg')->where($where)->count();
        }
        $result = D('WeibaPostDigg')->where($where)->order('cTime DESC')->findPage();
        $weibaPostModel = D('WeibaPost');
        foreach ($result['data'] as &$rs) {
            $user = getUserInfo($rs['uid']);
            $rs['face'] = $user['avatar_small'];
            $rs['uname'] = $user['uname'];
            $rs['space'] = $user['space_url'];
            $rs['ctime'] = $rs['cTime'];
            $rs['data_id'] = $rs['post_id'];
            $rs['data_type'] = 'weiba_post';
            $post = $weibaPostModel->field('title,content,is_del')->find($rs['post_id']);
            if ($post && $post['is_del'] == 0) {
                $rs['source_url'] = U('weiba/Index/postDetail', array('post_id' => $rs['post_id']));
                $rs['source_content'] = '帖子|'.$post['title'].'-'.getShort(t($post['content']), 15);
                $image = getEditorImages($post['content']);
                if ($image) {
                    $rs['source_image'] = $image;
                    $imageLocal = str_replace(UPLOAD_URL, '', $image);
                    if ($image != $imageLocal) {
                        $imageLocal = getImageUrl($imageLocal, 120, 120, true);
                        if ($imageLocal) {
                            $rs['source_image'] = $imageLocal;
                        }
                    }
                }
            } else {
                $rs['source_content'] = '原帖子已被删除了~';
            }
        }

        return $result;
    }

    protected function weiba_replyZan($ismy, $count = false)
    {
        if ($ismy) {
            $where = array('uid' => $this->mid);
        } else {
            $dbprefix = C('DB_PREFIX');
            $sql = "SELECT reply_id FROM {$dbprefix}weiba_reply WHERE uid={$this->mid}";
            $where = array('row_id' => array('in', $sql));
        }
        if ($count) {
            return M('WeibaReplyDigg')->where($where)->count();
        }
        $result = D('WeibaReplyDigg')->where($where)->order('cTime DESC')->findPage();
        $weibaPostModel = D('WeibaPost');
        $weibaReplyModel = D('WeibaReply');
        foreach ($result['data'] as &$rs) {
            $user = getUserInfo($rs['uid']);
            $rs['face'] = $user['avatar_small'];
            $rs['uname'] = $user['uname'];
            $rs['space'] = $user['space_url'];
            $rs['ctime'] = $rs['cTime'];
            $rs['data_id'] = $rs['row_id'];
            $rs['data_type'] = 'weiba_reply';
            $reply = $weibaReplyModel->find($rs['row_id']);
            if ($reply && $reply['is_del'] == 0) {
                $post = $weibaPostModel->field('title,is_del')->find($rs['post_id']);
                if ($post && $post['is_del'] == 0) {
                    $rs['source_url'] = U('weiba/Index/postDetail', array('post_id' => $rs['row_id'])).'#reply_'.$rs['id'];
                    $rs['source_content'] = $reply['content'].' //帖子|'.$post['title'];
                } else {
                    $rs['source_content'] = getShort($reply['content'], 7, '...').' //帖子已删除';
                }
            } else {
                $rs['source_content'] = '原回复已被删除了~';
            }
        }

        return $result;
    }

    protected function feedZan($ismy)
    {
        if ($ismy) {
            $where = array('uid' => $this->mid);
        } else {
            $dbprefix = C('DB_PREFIX');
            $feedId = "SELECT feed_id FROM {$dbprefix}feed WHERE uid={$this->mid}";
            $where = array('feed_id' => array('in', $feedId));
        }
        $result = D('FeedDigg')->where($where)->order('cTime DESC')->findPage();
        $feedModel = D('Feed');
        foreach ($result['data'] as &$rs) {
            $user = getUserInfo($rs['uid']);
            $feed = $feedModel->getFeedInfo($rs['feed_id']);
            $rs['face'] = $user['avatar_small'];
            $rs['uname'] = $user['uname'];
            $rs['space'] = $user['space_url'];
            $rs['ctime'] = $rs['cTime'];
            $rs['data_id'] = $rs['feed_id'];
            $rs['data_type'] = 'feed';
            $rs['source_url'] = U('public/Profile/feed', array('feed_id' => $feed['feed_id']));
            if ($feed['app'] == 'weiba' && $feed['is_repost'] == 0) {
                if (!empty($feed['api_source'])) {
                    if (empty($feed['api_source']['title']) && empty($feed['api_source']['content'])) {
                        $rs['source_content'] = '原分享内容已被删除~';
                    } else {
                        $rs['source_content'] = '帖子&nbsp;|&nbsp;'.getShort(t($feed['api_source']['title'].' - '.$feed['api_source']['content']), 100);
                        $rs['source_image'] = $feed['api_source']['pic_url_small'];
                    }
                }
            }
            if (empty($rs['source_content'])) {
                //print_r($feed);
                if (!empty($feed['content'])) {
                    $rs['source_content'] = t($feed['content']);
                } elseif (!empty($feed['feed_content'])) {
                    $rs['source_content'] = t($feed['feed_content']);
                } else {
                    $rs['source_content'] = t($feed['source_body']);
                }
                if (empty($rs['source_content']) || $feed['is_del']) {
                    $rs['source_content'] = '原分享内容已被删除~';
                    $rs['source_url'] = 'javascript:;';
                } else {
                    $rs['source_content'] = preg_replace('/\s+/u', ' ', $rs['source_content']);
                }
                if ($feed['type'] == 'postimage' && $feed['attach'][0]['attach_small']) {
                    $rs['source_image'] = $feed['attach'][0]['attach_small'];
                } elseif ($feed['type'] == 'postvideo' && $feed['flashimg']) {
                    if (strpos($feed['flashimg'], '://')) {
                        $rs['source_image'] = $feed['flashimg'];
                    } else {
                        $rs['source_image'] = getImageUrl($feed['flashimg'], 120, 120, true);
                    }
                    $rs['source_isvideo'] = true;
                }
            }
        }

        return $result;
    }

    protected function getRoomTitle($room, $members = null)
    {
        if (is_numeric($room)) {
            $room = model('WebMessage')->room()->find($room);
        }
        if ($room['title']) {
            return $room['title'];
        } elseif ($room['type'] == 1) {
            $uid = intval(trim(str_replace('_'.$this->mid.'_', '_', '_'.$room['min_max'].'_'), '_'));

            return getUserName($uid);
        } else {
            if (null === $members) {
                $members = model('WebMessage')->getRoomMember($room['list_id']);
            }
            $title = '';
            foreach ($members as $i => $member) {
                $title .= getShort(getUserName($member['member_uid']), 5, '...').'/';
                if ($i >= 2) {
                    break;
                }
            }
            if (count($members) > 3) {
                $title .= '...';
            }

            return str_replace('/', '、', trim($title, '/'));
        }
    }
}
