<?php

//+----------------------------------------------------------------------
// | Sociax [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2012 http://www.thinksns.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: jason <yangjs17@yeah.net>
// +----------------------------------------------------------------------
//

/**
 * 内容管理.
 +------------------------------------------------------------------------------
 *
 * @author    jason <yangjs17@yeah.net>
 *
 * @version   1.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');

class ContentAction extends AdministratorAction
{
    public $pageTitle = array();
    //TODO  要移位置
    public $from = array(0 => '网站', 1 => '手机网页版', 2 => 'android', 3 => 'iphone');

    public function feed($isRec = 0, $is_audit = 1)
    {
        //搜索区别
        $_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;
        if (!$isRec) {
            $_POST['is_audit'] = $isRec = isset($_REQUEST['is_audit']) ? t($_REQUEST['is_audit']) : $isRec;
        }

        $this->pageKeyList = array('feed_id', 'uid', 'uname', 'data', 'publish_time', 'type', 'from', 'DOACTION');
        $this->searchKey = array('feed_id', 'uid', 'type', 'rec');
        $this->opt['type'] = array('0' => L('PUBLIC_ALL_STREAM'), 'post' => L('PUBLIC_ORDINARY_WEIBO'), 'repost' => L('PUBLIC_SHARE_WEIBO'), 'postimage' => L('PUBLIC_PICTURE_WEIBO'), 'postfile' => L('PUBLIC_ATTACHMENT_WEIBO'));    //TODO 临时写死

        $this->pageTab[] = array('title' => L('PUBLIC_DYNAMIC_MANAGEMENT'), 'tabHash' => 'list', 'url' => U('admin/Content/feed'));
        $this->pageTab[] = array('title' => '待审列表', 'tabHash' => 'unAudit', 'url' => U('admin/Content/feedUnAudit'));
        $this->pageTab[] = array('title' => L('PUBLIC_RECYCLE_BIN'), 'tabHash' => 'rec', 'url' => U('admin/Content/feedRec'));

        $this->pageButton[] = array('title' => L('PUBLIC_DYNAMIC_SEARCH'), 'onclick' => "admin.fold('search_form')");
        if ($isRec == 0 && $is_audit == 1) {
            $this->pageButton[] = array('title' => L('PUBLIC_DYNAMIC_DELETE'), 'onclick' => "admin.ContentEdit('','delFeed','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_DYNAMIC')."')");
        } elseif ($is_Rec == 0 && $is_audit == 0) {
            $this->pageButton[] = array('title' => '通过', 'onclick' => "admin.ContentEdit('','auditFeed','".'通过'."','".L('PUBLIC_DYNAMIC')."')");
            $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.ContentEdit('','delFeed','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_DYNAMIC')."')");
        } else {
            $this->pageButton[] = array('title' => L('PUBLIC_REMOVE_COMPLETELY'), 'onclick' => "admin.ContentEdit('','deleteFeed','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_DYNAMIC')."')");
        }

        $isRec == 1 && $_REQUEST['tabHash'] = 'rec';
        $is_audit == 0 && $_REQUEST['tabHash'] = 'unAudit';
        $this->assign('pageTitle', $isRec ? L('PUBLIC_RECYCLE_BIN') : L('PUBLIC_DYNAMIC_MANAGEMENT'));
        $map['is_del'] = $isRec == 1 ? 1 : 0;
        if (!$isRec) {
            $map['is_audit'] = $is_audit == 1 ? 1 : 0;
        }
        !empty($_POST['feed_id']) && $map['feed_id'] = array('in', explode(',', $_POST['feed_id']));
        !empty($_POST['uid']) && $map['uid'] = array('in', explode(',', $_POST['uid']));
        !empty($_POST['type']) && $map['type'] = t($_POST['type']);

        $listData = model('Feed')->getList($map, 20);
        foreach ($listData['data'] as &$v) {
            $v['uname'] = $v['user_info']['space_link'];
            $v['type'] = $this->opt['type'][$v['type']];
            $v['from'] = $this->from[$v['from']];
            $v['data'] = '<div style="width:500px;line-height:22px" model-node="feed_list" class="feed_list">'.$v['body'].'  <a target="_blank" href="'.U('public/Profile/feed', array('feed_id' => $v['feed_id'], 'uid' => $v['uid'])).'">'.L('PUBLIC_VIEW_DETAIL').'&raquo;</a></div>';
            $v['publish_time'] = date('Y-m-d H:i:s', $v['publish_time']);
            //$v['DOACTION'] = $isRec==0 ? "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"delFeed\",\"".L('PUBLIC_STREAM_DELETE')."\",\"".L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_STREAM_DELETE')."</a>"
            //							:"<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"feedRecover\",\"".L('PUBLIC_RECOVER')."\",\"".L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_RECOVER')."</a>";
            if ($isRec == 0 && $is_audit == 1) {
                $v['DOACTION'] = "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"delFeed\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_STREAM_DELETE').'</a>';
            } elseif ($isRec == 0 && $is_audit == 0) {
                $v['DOACTION'] = "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"auditFeed\",\"".'通过'.'","'.L('PUBLIC_DYNAMIC')."\")'>".'通过'.'</a>&nbsp;|&nbsp;'."<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"delFeed\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_STREAM_DELETE').'</a>';
            } else {
                $v['DOACTION'] = "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['feed_id']},\"feedRecover\",\"".L('PUBLIC_RECOVER').'","'.L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_RECOVER').'</a>';
            }
        }
        $this->_listpk = 'feed_id';
        $this->displayList($listData);
    }

    //待审列表
    public function feedUnAudit()
    {
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_feed';
        $this->searchPageKey = 'S_'.$this->pageKey;
        $this->feed(0, 0);
    }

    //回收站
    public function feedRec()
    {
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_feed';
        $this->searchPageKey = 'S_'.$this->pageKey;
        $this->feed(1);
    }

    //恢复
    public function feedRecover()
    {
        $return = model('Feed')->doEditFeed($_POST['id'], 'feedRecover', L('PUBLIC_RECOVER'));
        if ($return['status'] == 0) {
            $return['data'] = L('PUBLIC_RECOVERY_FAILED');
        } else {
            $return['data'] = L('PUBLIC_RECOVERY_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    //分享通过审核
    public function auditFeed()
    {
        $return = model('Feed')->doAuditFeed($_POST['id']);
        if ($return['status'] == 0) {
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    //假删除
    public function delFeed()
    {
        $return = model('Feed')->doEditFeed($_POST['id'], 'delFeed', L('PUBLIC_STREAM_DELETE'));
        if ($return['status'] == 0) {
            $return['data'] = L('PUBLIC_DELETE_FAIL');
        } else {
            $return['data'] = L('PUBLIC_DELETE_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    //真删除
    public function deleteFeed()
    {
        $return = model('Feed')->doEditFeed($_POST['id'], 'deleteFeed', L('PUBLIC_REMOVE_COMPLETELY'));
        if ($return['status'] == 0) {
            $return['data'] = L('PUBLIC_REMOVE_COMPLETELY_FAIL');
        } else {
            $return['data'] = L('PUBLIC_REMOVE_COMPLETELY_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 评论管理.
     *
     * @param bool $isRec 是否是回收站列表
     *
     * @return array 相关数据
     */
    public function comment($isRec = false, $is_audit = 1)
    {
        // 搜索区别
        $_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;

        $this->pageKeyList = array('comment_id', 'uid', 'app_uid', 'source_type', 'content', 'ctime', 'client_type', 'DOACTION');
        $this->searchKey = array('comment_id', 'uid', 'app_uid');

        $this->pageTab[] = array('title' => '评论管理', 'tabHash' => 'list', 'url' => U('admin/Content/comment'));
        $this->pageTab[] = array('title' => '待审评论列表', 'tabHash' => 'unAudit', 'url' => U('admin/Content/commentUnAudit'));
        $this->pageTab[] = array('title' => L('PUBLIC_RECYCLE_BIN'), 'tabHash' => 'rec', 'url' => U('admin/Content/commentRec'));

        $this->pageButton[] = array('title' => L('PUBLIC_SEARCH_COMMENT'), 'onclick' => "admin.fold('search_form')");
        if ($isRec == 0 && $is_audit == 1) {
            $this->pageButton[] = array('title' => L('PUBLIC_DELETE_COMMENT'), 'onclick' => "admin.ContentEdit('','delComment','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_STREAM_COMMENT')."')");
        } elseif ($is_Rec == 0 && $is_audit == 0) {
            $this->pageButton[] = array('title' => '通过', 'onclick' => "admin.ContentEdit('','auditComment','".'通过'."','".L('PUBLIC_DYNAMIC')."')");
            $this->pageButton[] = array('title' => '删除', 'onclick' => "admin.ContentEdit('','delComment','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_DYNAMIC')."')");
        } else {
            $this->pageButton[] = array('title' => L('PUBLIC_REMOVE_COMPLETELY'), 'onclick' => "admin.ContentEdit('','deleteComment','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_STREAM_COMMENT')."')");
        }

        $isRec == 1 && $_REQUEST['tabHash'] = 'rec';
        $is_audit == 0 && $_REQUEST['tabHash'] = 'unAudit';
        $this->assign('pageTitle', $isRec ? L('PUBLIC_RECYCLE_BIN') : '评论管理');
        $map['is_del'] = $isRec == 1 ? 1 : 0;
        if (!$isRec) {
            $map['is_audit'] = $is_audit == 1 ? 1 : 0;
        }
        !empty($_POST['comment_id']) && $map['comment_id'] = array('in', explode(',', $_POST['comment_id']));
        !empty($_POST['uid']) && $map['uid'] = array('in', explode(',', $_POST['uid']));
        !empty($_POST['app_uid']) && $map['app_uid'] = array('in', explode(',', $_POST['app_uid']));
        $listData = model('Comment')->getCommentList($map, 'comment_id desc', 20);

        foreach ($listData['data'] as &$v) {
            $v['uid'] = $v['user_info']['space_link'];
            $v['app_uid'] = $v['sourceInfo']['source_user_info']['space_link'];
            $v['source_type'] = "<a href='{$v['sourceInfo']['source_url']}' target='_blank'>".$v['sourceInfo']['source_type'].'</a>';
            $v['content'] = '<div style="width:400px">'.$v['content'].'</div>';
            $v['client_type'] = $this->from[$v['client_type']];
            $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $v['DOACTION'] = $isRec == 0 ? "<a href='".$v['sourceInfo']['source_url']."' target='_blank'>".L('PUBLIC_VIEW')."</a> <a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"delComment\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_STREAM_COMMENT')."\")'>".L('PUBLIC_STREAM_DELETE').'</a>'
                                        : "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"CommentRecover\",\"".L('PUBLIC_RECOVER').'","'.L('PUBLIC_STREAM_COMMENT')."\")'>".L('PUBLIC_RECOVER').'</a>';
            if ($isRec == 0 && $is_audit == 1) {
                $v['DOACTION'] = "<a href='".$v['sourceInfo']['source_url']."' target='_blank'>".L('PUBLIC_VIEW')."</a> <a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"delComment\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_STREAM_COMMENT')."\")'>".L('PUBLIC_STREAM_DELETE').'</a>';
            } elseif ($isRec == 0 && $is_audit == 0) {
                $v['DOACTION'] = "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"auditComment\",\"".'通过'.'","'.L('PUBLIC_STREAM_COMMENT')."\")'>".'通过'.'</a>&nbsp;|&nbsp;'."<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"delComment\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_DYNAMIC')."\")'>".L('PUBLIC_STREAM_DELETE').'</a>';
            } else {
                $v['DOACTION'] = "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['comment_id']},\"CommentRecover\",\"".L('PUBLIC_RECOVER').'","'.L('PUBLIC_STREAM_COMMENT')."\")'>".L('PUBLIC_RECOVER').'</a>';
            }
        }
        $this->_listpk = 'comment_id';
        $this->displayList($listData);
    }

    //待审列表
    public function commentUnAudit()
    {
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_comment';
        $this->searchPageKey = 'S_'.$this->pageKey;
        $this->comment(0, 0);
    }

    //回收站
    public function commentRec()
    {
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_comment';
        $this->searchPageKey = 'S_'.$this->pageKey;
        $this->comment(1);
    }

    //恢复
    public function commentRecover()
    {
        echo json_encode(model('Comment')->doEditComment($_POST['id'], 'commentRecover', '恢复成功'));
    }

    //评论通过审核
    public function auditComment()
    {
        $return = model('Comment')->doAuditComment($_POST['id']);
        if ($return['status'] == 0) {
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    //假删除
    public function delComment()
    {
        echo json_encode(model('Comment')->doEditComment($_POST['id'], 'delComment', '删除成功'));
    }

    //真删除
    public function deleteComment()
    {
        echo json_encode(model('Comment')->doEditComment($_POST['id'], 'deleteComment', '评论彻底删除成功'));
    }

    /**
     * 私信管理列表.
     *
     * @param int $isRec [description]
     */
    public function message($isRec = 0)
    {
        // 搜索区别
        $_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;
        // 列表字段配置
        $this->pageKeyList = array('message_id', 'fuid', 'from_uid', 'mix_man', 'content', 'mtime', 'DOACTION');
        // 搜索字段配置
        $this->searchKey = array('from_uid', 'mix_man', 'content');
        // Tab标签配置
        $this->pageTab[] = array('title' => L('PUBLIC_PRIVATE_MESSAGE_MANAGEMENT'), 'tabHash' => 'list', 'url' => U('admin/Content/message'));
        $this->pageTab[] = array('title' => L('PUBLIC_RECYCLE_BIN'), 'tabHash' => 'rec', 'url' => U('admin/Content/messageRec'));
        // 批量操作按钮配置
        $this->pageButton[] = array('title' => L('PUBLIC_MASSAGE_SEARCH'), 'onclick' => "admin.fold('search_form')");
        if ($isRec == 0) {
            $this->pageButton[] = array('title' => L('PUBLIC_MASSAGE_DEL'), 'onclick' => "admin.ContentEdit('','delMessage','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_PRIVATE_MESSAGE')."');");
        } else {
            $this->pageButton[] = array('title' => L('PUBLIC_REMOVE_COMPLETELY'), 'onclick' => "admin.ContentEdit('','deleteMessage','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_PRIVATE_MESSAGE')."')");
        }
        $isRec == 1 && $_REQUEST['tabHash'] = 'rec';
        $this->assign('pageTitle', $isRec ? L('PUBLIC_RECYCLE_BIN') : L('PUBLIC_PRIVATE_MESSAGE_MANAGEMENT'));
        // 未删除的
        $map['a.is_del'] = ($isRec == 1) ? 1 : 0;
        !empty($_POST['from_uid']) && $map['a.from_uid'] = intval($_POST['from_uid']);
        !empty($_POST['mix_man']) && $map['c.member_uid'] = intval($_POST['mix_man']);
        !empty($_POST['content']) && $map['a.content'] = array('like', '%'.t($_POST['content']).'%');
        $map['b.type'] = array('neq', 3);
        // 获取列表信息
        $listData = model('Message')->getDetailList($map);
        // 整理列表数据
        foreach ($listData['data'] as &$v) {
            $uids = explode('_', $v['min_max']);
            $map = array();
            $map['uid'] = array('in', $uids);
            $uname = model('User')->where($map)->getHashList('uid', 'uname');

            $v['mix_man'] = implode(',', $uname);

            if ($v['fuid'] == '1') {
                $v['fuid'] = L('PUBLIC_SYSTEM');
            } else {
                $v['fuid'] = $uname[$v['fuid']];
            }

            if ($v['from_uid'] == '1') {
                $v['from_uid'] = L('PUBLIC_SYSTEM');
            } else {
                $v['from_uid'] = $uname[$v['from_uid']];
            }

            $v['content'] = '<div style="width:500px">'.getShort($v['content'], 120, '...').'</div>'; // 截取120字
            $v['mtime'] = date('Y-m-d H:i:s', $v['mtime']);
            $v['DOACTION'] = $isRec == 0 ? "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['message_id']},\"delMessage\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_PRIVATE_MESSAGE')."\");'>".L('PUBLIC_STREAM_DELETE').'</a>'
                                        : "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['message_id']},\"MessageRecover\",\"".L('PUBLIC_RECOVER').'","'.L('PUBLIC_PRIVATE_MESSAGE')."\")'>".L('PUBLIC_RECOVER').'</a>';
        }
        // 设置操作主键
        $this->_listpk = 'message_id';
        $this->displayList($listData);
    }

    //回收站
    public function messageRec()
    {
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_message';
        $this->searchPageKey = 'S_'.$this->pageKey;
        $this->message(1);
    }

    //恢复
    public function messageRecover()
    {
        echo json_encode(model('Message')->doEditMessage($_POST['id'], 'messageRecover', L('PUBLIC_RECOVER')));
    }

    //假删除
    public function delMessage()
    {
        echo json_encode(model('Message')->doEditMessage($_POST['id'], 'delMessage', L('PUBLIC_STREAM_DELETE')));
    }

    //真删除
    public function deleteMessage()
    {
        echo json_encode(model('Message')->doEditMessage($_POST['id'], 'deleteMessage', L('PUBLIC_REMOVE_COMPLETELY')));
    }

    public function attach($isRec = 0)
    {
        $this->_listpk = 'attach_id';
        //搜索区别
        $_POST['rec'] = $isRec = isset($_REQUEST['rec']) ? t($_REQUEST['rec']) : $isRec;

        $this->pageKeyList = array('attach_id', 'name', 'size', 'uid', 'ctime', 'from', 'DOACTION');
        $this->searchKey = array('attach_id', 'name', 'from');

        $this->opt['from'] = array_merge(array('-1' => L('PUBLIC_ALL_STREAM')), $this->from);
        $this->pageTab[] = array('title' => L('PUBLIC_FILE_MANAGEMENT'), 'tabHash' => 'list', 'url' => U('admin/Content/attach'));
        $this->pageTab[] = array('title' => L('PUBLIC_RECYCLE_BIN'), 'tabHash' => 'rec', 'url' => U('admin/Content/attachRec'));

        $this->pageButton[] = array('title' => L('PUBLIC_FILE_STREAM_SEARCH'), 'onclick' => "admin.fold('search_form')");
        if ($isRec == 0) {
            $this->pageButton[] = array('title' => L('PUBLIC_FILE_STREAM_DEL'), 'onclick' => "admin.ContentEdit('','delAttach','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_FILE_STREAM')."');");
        } else {
            $this->pageButton[] = array('title' => L('PUBLIC_REMOVE_COMPLETELY'), 'onclick' => "admin.ContentEdit('','deleteAttach','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_FILE_STREAM')."')");
        }

        $isRec == 1 && $_REQUEST['tabHash'] = 'rec';
        $this->assign('pageTitle', $isRec ? L('PUBLIC_RECYCLE_BIN') : L('PUBLIC_FILE_MANAGEMENT'));
        $map['is_del'] = $isRec == 1 ? 1 : 0;    //未删除的
        !empty($_POST['attach_id']) && $map['attach_id'] = array('in', explode(',', $_POST['attach_id']));
        $_POST['from'] > 0 && $map['from'] = intval($_POST['from'] - 1);
        !empty($_POST['name']) && $map['name'] = array('like', '%'.t($_POST['name']).'%');

        $listData = model('Attach')->getAttachList($map, '*', 'attach_id desc', 10);

        //$listData = model('Comment')->getCommentList($map,'comment_id desc',20);
        $image = array('png', 'jpg', 'gif', 'jpeg', 'bmp');

        foreach ($listData['data'] as &$v) {
            $user = model('User')->getUserInfo($v['uid']);
            $v['uid'] = $user['space_link'];
            $v['name'] = in_array($v['extension'], $image)
                            ? '<a href="'.U('widget/Upload/down', array('attach_id' => $v['attach_id'])).'">'.
                                "<img src='".getImageUrl($v['save_path'].$v['save_name'], 225)."' width='100'><br/>{$v['name']}</a>"
                            : '<a href="'.U('widget/Upload/down', array('attach_id' => $v['attach_id'])).'">'.$v['name'].'</a>';
            $v['size'] = byte_format($v['size']);
            $v['from'] = $this->from[$v['from']];
            $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $v['DOACTION'] = $isRec == 0 ? "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['attach_id']},\"delAttach\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_FILE_STREAM')."\");'>".L('PUBLIC_STREAM_DELETE').'</a>'
                                        : "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['attach_id']},\"AttachRecover\",\"".L('PUBLIC_RECOVER').'","'.L('PUBLIC_FILE_STREAM')."\")'>".L('PUBLIC_RECOVER').'</a>';
        }
        $this->displayList($listData);
    }

    //回收站
    public function attachRec()
    {
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_attach';
        $this->searchPageKey = 'S_'.$this->pageKey;
        $this->attach(1);
    }

    //恢复
    public function attachRecover()
    {
        echo json_encode(model('Attach')->doEditAttach($_POST['id'], 'attachRecover', L('PUBLIC_RECOVER')));
    }

    //假删除
    public function delAttach()
    {
        echo json_encode(model('Attach')->doEditAttach($_POST['id'], 'delAttach', L('PUBLIC_STREAM_DELETE')));
    }

    //真删除
    public function deleteAttach()
    {
        echo json_encode(model('Attach')->doEditAttach($_POST['id'], 'deleteAttach', L('PUBLIC_REMOVE_COMPLETELY')));
    }

    //TODO 临时放着 后面要移动到messagemodel中

    /**
     * 视频管理.
     */
    public function video($is_del = 0)
    {
        $this->_listpk = 'video_id';
        //搜索区别
        $_POST['is_del'] = $isRec = isset($_REQUEST['is_del']) ? t($_REQUEST['is_del']) : $isRec;

        $this->pageKeyList = array('video_id', 'name', 'size', 'uid', 'ctime', 'from', 'DOACTION');
        $this->searchKey = array('video_id', 'name', 'from');
        $this->opt['from'] = array_merge(array('-1' => L('PUBLIC_ALL_STREAM')), $this->from);
        $this->pageTab[] = array('title' => '视频列表', 'tabHash' => 'list', 'url' => U('admin/Content/video'));
        $this->pageTab[] = array('title' => L('PUBLIC_RECYCLE_BIN'), 'tabHash' => 'rec', 'url' => U('admin/Content/videoRec'));
        $this->pageTab[] = array('title' => '视频配置', 'tabHash' => 'video_config', 'url' => U('admin/Content/video_config'));

        $this->pageButton[] = array('title' => L('PUBLIC_FILE_STREAM_SEARCH'), 'onclick' => "admin.fold('search_form')");
        if ($is_del == 0) {
            $this->pageButton[] = array('title' => L('PUBLIC_FILE_STREAM_DEL'), 'onclick' => "admin.ContentEdit('','delVideo','".L('PUBLIC_STREAM_DELETE')."','".L('PUBLIC_FILE_STREAM')."');");
        } else {
            $this->pageButton[] = array('title' => L('PUBLIC_REMOVE_COMPLETELY'), 'onclick' => "admin.ContentEdit('','deleteVideo','".L('PUBLIC_REMOVE_COMPLETELY')."','".L('PUBLIC_FILE_STREAM')."')");
        }

        $is_del == 1 && $_REQUEST['tabHash'] = 'rec';
        $this->assign('pageTitle', $is_del ? L('PUBLIC_RECYCLE_BIN') : L('视频管理'));
        $map['is_del'] = $is_del == 1 ? 1 : 0;    //未删除的
        !empty($_POST['video_id']) && $map['video_id'] = array('in', explode(',', $_POST['video_id']));
        $_POST['from'] > 0 && $map['from'] = intval($_POST['from'] - 1);
        !empty($_POST['name']) && $map['name'] = array('like', '%'.t($_POST['name']).'%');
        // $listData = model('Attach')->getAttachList($map,'*','attach_id desc',10);
        $listData = D('video')->where($map)->findPage(20);

        foreach ($listData['data'] as &$v) {
            $user = model('User')->getUserInfo($v['uid']);
            $v['uid'] = $user['space_link'];
            $v['name'] = $v['image_path'] ? '<a target="_blank" href="'.SITE_URL.$v['video_path'].'">'.
                                "<img src='".SITE_URL.$v['image_path']."' width='100'><br/>{$v['name']}</a>"
                            : '<a target="_blank" href="'.SITE_URL.$v['video_path'].'">'.$v['name'].'</a>';
            $v['size'] = byte_format($v['size']);
            $v['from'] = $this->from[$v['from']];
            $v['ctime'] = date('Y-m-d H:i:s', $v['ctime']);
            $v['DOACTION'] = $is_del == 0 ? "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['video_id']},\"delVideo\",\"".L('PUBLIC_STREAM_DELETE').'","'.L('PUBLIC_FILE_STREAM')."\");'>".L('PUBLIC_STREAM_DELETE').'</a>'
                                        : "<a href='javascript:void(0)' onclick='admin.ContentEdit({$v['video_id']},\"VideoRecover\",\"".L('PUBLIC_RECOVER').'","'.L('PUBLIC_FILE_STREAM')."\")'>".L('PUBLIC_RECOVER').'</a>';
        }
        $this->displayList($listData);
    }

    //回收站
    public function videoRec()
    {
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_video';
        $this->searchPageKey = 'S_'.$this->pageKey;
        $this->video(1);
    }

    //恢复
    public function videoRecover()
    {
        echo json_encode(model('Video')->doEditVideo($_POST['id'], 'videoRecover', L('PUBLIC_RECOVER')));
    }

    //假删除
    public function delvideo()
    {
        echo json_encode(model('Video')->doEditVideo($_POST['id'], 'delVideo', L('PUBLIC_STREAM_DELETE')));
    }

    //真删除
    public function deletevideo()
    {
        echo json_encode(model('Video')->doEditVideo($_POST['id'], 'deleteVideo', L('PUBLIC_REMOVE_COMPLETELY')));
    }

    //视频配置
    public function video_config()
    {
        $this->assign('pageTitle', L('视频配置'));
        $this->pageTab[] = array('title' => '视频列表', 'tabHash' => 'list', 'url' => U('admin/Content/video'));
        $this->pageTab[] = array('title' => L('PUBLIC_RECYCLE_BIN'), 'tabHash' => 'rec', 'url' => U('admin/Content/videoRec'));
        $this->pageTab[] = array('title' => '视频配置', 'tabHash' => 'video_config', 'url' => U('admin/Content/video_config'));
        $data = model('Xdata')->get('admin_Content:video_config');
        // dump($data);exit;
        $this->pageKeyList = array('ffmpeg_path', 'video_server', 'video_ext', 'video_size', 'video_transfer_async');
        $this->opt['video_transfer_async'] = array(0 => '否', 1 => '是');
        $this->savePostUrl = U('admin/Content/do_video_config');
        $this->displayConfig($data);
    }

    //下载用户
    public function download_user()
    {
        $this->assign('pageTitle', L('下载用户'));
        $data = M('check_download')->findPage(20);

        foreach ($data['data'] as &$value) {
            $value['ctime'] = date('Y-m-d H:i', $value['ctime']);
        }
        $this->pageKeyList = array('phone', 'ctime');
        $this->displayList($data);
    }

    public function do_video_config()
    {
        $list = $_POST['systemdata_list'];
        $key = $_POST['systemdata_key'];
        $key = $list.':'.$key;
        $value['ffmpeg_path'] = $_POST['ffmpeg_path'];
        $value['video_server'] = $_POST['video_server'];
        $value['video_ext'] = $_POST['video_ext'];
        $value['video_size'] = $_POST['video_size'];
        $value['video_transfer_async'] = $_POST['video_transfer_async'];
        $res = model('Xdata')->put($key, $value);
        if ($res) {
            $this->success(L('PUBLIC_ADMIN_OPRETING_SUCCESS'));
        } else {
            $this->error(L('PUBLIC_ADMIN_OPRETING_ERROR'));
        }
    }

    /**
     * 举报管理.
     */
    public function denounce($map)
    {
        $_GET['id'] && $map['id'] = array('in', explode(',', t($_GET['id'])));
        $_GET['uid'] && $map['uid'] = array('in', explode(',', t($_GET['uid'])));
        $_GET['fuid'] && $map['fuid'] = array('in', explode(',', t($_GET['fuid'])));
        $_GET['from'] && $map['from'] = t($_GET['from']);
        $map['state'] = $_GET['state'] ? $_GET['state'] : '0';
        $data = model('Denounce')->getFromList($map);
        $data['state'] = $map['state'];
        $this->assign($data);
        if (is_array($map) && count($map) == '1') {
            unset($map);
        }
        $this->assign($_GET);
        $this->assign('id', t($_GET['id']));
        $this->assign('uid', t($_GET['uid']));
        $this->assign('fuid', t($_GET['fuid']));
        $this->assign('from', t($_GET['from']));
        $this->assign('isSearch', empty($map) ? '0' : '1');
        $this->display('denounce');
    }

    /**
     * 删除举报回收站内容.
     *
     * @return int 是否删除成功
     */
    public function doDeleteDenounce()
    {
        // 判断参数
        if (empty($_POST['ids'])) {
            echo 0;
            exit;
        }

        $data[] = L('PUBLIC_CONTENT_REPORT_DELETE');
        $map['id'] = array('in', t($_POST['ids']));
        $data[] = model('Denounce')->where($map)->findAll();
        // todo 记录知识
        echo model('Denounce')->deleteDenounce(t($_POST['ids']), intval($_POST['state'])) ? '1' : '0';
    }

    /**
     * 撤销举报内容.
     *
     * @return int 是否撤销成功
     */
    public function doReviewDenounce()
    {
        // 判断参数
        if (empty($_POST['ids'])) {
            echo 0;
            exit;
        }

        $data[] = L('PUBLIC_CONTENT_REPORT_REVOKE');
        $map['id'] = array('in', t($_POST['ids']));
        $data[] = model('Denounce')->where($map)->findall();
        //todo 记录知识
        echo model('Denounce')->reviewDenounce(t($_POST['ids'])) ? '1' : '0';
    }

    /**
     * 话题管理.
     */
    public function topic()
    {
        $this->assign('pageTitle', '话题管理');
        // 设置列表主键
        $this->_listpk = 'topic_id';
        $this->pageTab[] = array('title' => '话题管理', 'tabHash' => 'list', 'url' => U('admin/Content/topic'));
        $this->pageTab[] = array('title' => '推荐话题', 'tabHash' => 'recommendTopic', 'url' => U('admin/Content/topic', array('recommend' => 1)));
        $this->pageTab[] = array('title' => '添加话题', 'tabHash' => 'addTopic', 'url' => U('admin/Content/addTopic'));
        $this->pageButton[] = array('title' => '搜索话题', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '批量屏蔽', 'onclick' => "admin.setTopic(3,'',0)");
        $this->searchKey = array('topic_id', 'topic_name', 'lock');
        $this->searchPostUrl = U('admin/Content/topic', array('tabHash' => $_REQUEST['tabHash'], 'recommend' => $_REQUEST['recommend']));
        $this->opt['recommend'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => '是', '2' => '否');
        $this->opt['essence'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => '是', '2' => '否');
        $this->opt['lock'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => '是', '2' => '否');
        $this->pageKeyList = array('topic_id', 'topic_name', 'note', 'domain', 'des', 'pic', 'topic_user', 'outlink', 'DOACTION');
        //dump($_POST);exit;
        $listData = model('FeedTopicAdmin')->getTopic('', $_REQUEST['recommend']);
        foreach ($listData['data'] as $k => &$v) {
            $v['note'] = "<div style='width:400px; border:0; margin:0; padding:0;'>".$v['note'].'</div>';
        }
        //dump($listData);exit;
        $this->displayList($listData);
    }

    /**
     * 添加话题.
     */
    public function addTopic()
    {
        $this->assign('pageTitle', '添加话题');
        $this->pageTab[] = array('title' => '话题管理', 'tabHash' => 'list', 'url' => U('admin/Content/topic'));
        $this->pageTab[] = array('title' => '推荐话题', 'tabHash' => 'recommendTopic', 'url' => U('admin/Content/topic', array('recommend' => 1)));
        $this->pageTab[] = array('title' => '添加话题', 'tabHash' => 'addTopic', 'url' => U('admin/Content/addTopic'));
        $this->pageKeyList = array('topic_name', 'note', 'domain', 'des', 'pic', 'topic_user', 'outlink', 'recommend');
        $topic['domain'] = SITE_URL.'/topics/'.'<input type="text" value="" name="domain" id="form_domain">';
        $this->opt['recommend'] = array('1' => '是', '0' => '否');
        //$this->opt['essence'] = array('1'=>'是','0'=>'否');
        $this->notEmpty = array('topic_name', 'note');
        // 表单URL设置
        $this->savePostUrl = U('admin/Content/doAddTopic');
        $this->onsubmit = 'admin.topicCheck(this)';
        $this->onload[] = "$('#search_uids').val('');";
        $this->displayConfig($topic);
    }

    /**
     * 执行添加话题.
     */
    public function doAddTopic()
    {
        t($_POST['topic_name']) == '' && $this->error('话题名称不能为空');
        t($_POST['note']) == '' && $this->error('话题注释不能为空');
        $map['topic_name'] = t($_POST['topic_name']);
        if (model('FeedTopic')->where($map)->find()) {
            $this->error('此话题已存在');
        }
        if ($_POST['domain'] != '') {
            $map1['domain'] = t($_POST['domain']);
            if (model('FeedTopic')->where($map1)->find()) {
                $this->error('此话题域名已存在');
            }
        }
        if (h(t($_POST['outlink'])) != '') {
            $res = preg_match('/^(?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)/', h($_POST['outlink']));
            if (!$res) {
                $this->error('外链格式错误');
            }
        }
        $res = model('FeedTopicAdmin')->addTopic($_POST);
        if ($res) {
            $this->assign('jumpUrl', U('admin/Content/topic'));
            $this->success(L('PUBLIC_ADD_SUCCESS'));
        } else {
            $this->error(model('FeedTopicAdmin')->getError());
        }
    }

    /**
     * 设置话题为推荐、精华或屏蔽.
     *
     * @return array 操作成功状态和提示信息
     */
    public function setTopic()
    {
        if (empty($_POST['topic_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        switch (intval($_POST['type'])) {
            case '1':
                $field = 'recommend';
                break;
            case '2':
                $field = 'essence';
                break;
            case '3':
                $field = 'lock';
                break;
        }
        if (intval($_POST['value']) == 1) {
            $value = 0;
        } else {
            $value = 1;
        }
        !is_array($_POST['topic_id']) && $_POST['topic_id'] = array($_POST['topic_id']);
        $map['topic_id'] = array('in', $_POST['topic_id']);
        $result = model('FeedTopic')->where($map)->setField($field, $value);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
            model('Cache')->set('feed_topic_recommend', null);
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 编辑话题.
     */
    public function editTopic()
    {
        $this->assign('pageTitle', '编辑话题');
        $this->pageTab[] = array('title' => '话题管理', 'tabHash' => 'list', 'url' => U('admin/Content/topic'));
        $this->pageTab[] = array('title' => '推荐话题', 'tabHash' => 'recommendTopic', 'url' => U('admin/Content/topic', array('recommend' => 1)));
        $this->pageTab[] = array('title' => '添加话题', 'tabHash' => 'addTopic', 'url' => U('admin/Content/addTopic'));
        $this->pageTab[] = array('title' => '编辑话题', 'tabHash' => 'editTopic', 'url' => U('admin/Content/editTopic', array('topic_id' => intval($_GET['topic_id']), 'tabHash' => 'editTopic')));
        $this->pageKeyList = array('topic_id', 'topic_name', 'note', 'domain', 'des', 'pic', 'topic_user', 'outlink', 'recommend');
        $this->opt['recommend'] = array('1' => '是', '0' => '否');
        //$this->opt['essence'] = array('1'=>'是','0'=>'否');
        $topic = model('FeedTopic')->where('topic_id='.intval($_GET['topic_id']))->find();
        if ($topic['pic']) {
            $pic = D('attach')->where('attach_id='.$topic['pic'])->find();
            $pic_url = $pic['save_path'].$pic['save_name'];
            $topic['pic_url'] = getImageUrl($pic_url);
        }
        $topic['domain'] = SITE_URL.'/topics/'.'<input type="text" value="'.$topic['domain'].'" name="domain" id="form_domain">';
        $this->notEmpty = array('note');
        $this->savePostUrl = U('admin/Content/doEditTopic');
        $this->onsubmit = 'admin.topicCheck(this)';
        $this->displayConfig($topic);
    }

    /**
     * 执行编辑话题.
     */
    public function doEditTopic()
    {
        //$_POST['name']=="" && $this->error('话题名称不能为空');
        $_POST['note'] == '' && $this->error('话题注释不能为空');
        //$map['topic_id'] = array('neq', $_POST['topic_id']);
        //$map['name'] = t($_POST['name']);
        //if(model('FeedTopic')->where($map)->find()) $this->error('此话题已存在');
        if ($_POST['domain'] != '') {
            $map1['topic_id'] = array('neq', $_POST['topic_id']);
            $map1['domain'] = t($_POST['domain']);
            if (model('FeedTopic')->where($map1)->find()) {
                $this->error('此话题域名已存在');
            }
        }
        if (h(t($_POST['outlink'])) != '') {
            $res = preg_match('/^(?:https?|ftp):\/\/(?:www\.)?(?:[a-zA-Z0-9][a-zA-Z0-9\-]*)/', h($_POST['outlink']));
            if (!$res) {
                $this->error('外链格式错误');
            }
        }
        //$data['name'] = t($_POST['name']);
        $data['note'] = t($_POST['note']);
        $data['domain'] = t($_POST['domain']);
        $data['des'] = h($_POST['des']);
        $data['pic'] = t($_POST['pic']);
        $data['topic_user'] = t($_POST['topic_user']);
        $data['outlink'] = t($_POST['outlink']);
        $data['recommend'] = intval($_POST['recommend']);
        if ($data['recommend'] == 1) {
            if (!D('feed_topic')->where('topic_id='.intval($_POST['topic_id']))->getField('recommend_time')) {
                $data['recommend_time'] = time();
            }
        } else {
            if (D('feed_topic')->where('topic_id='.intval($_POST['topic_id']))->getField('recommend_time')) {
                $data['recommend_time'] = 0;
            }
        }
        $data['essence'] = intval($_POST['essence']);
        $res = D('feed_topic')->where('topic_id='.intval($_POST['topic_id']))->save($data);
        if ($res !== false) {
            $this->assign('jumpUrl', U('admin/Content/topic'));
            $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
        } else {
            $this->error(D('feed_topic')->getError());
        }
    }

    /**
     * 模板管理页面.
     */
    public function template()
    {
        $this->assign('pageTitle', '模板管理');

        $this->pageTab[] = array('title' => '模板管理', 'tabHash' => 'template', 'url' => U('admin/Content/template'));
        $this->pageTab[] = array('title' => '添加模板', 'tabHash' => 'upTemplate', 'url' => U('admin/Content/upTemplate'));

        $this->pageButton[] = array('title' => '添加模板', 'onclick' => "location.href='".U('admin/Content/upTemplate')."'");
        $this->pageButton[] = array('title' => '删除模板', 'onclick' => 'admin.delTemplate()');

        $this->pageKeyList = array('tpl_id', 'name', 'alias', 'title', 'body', 'lang', 'type', 'type2', 'is_cache', 'ctime', 'DOACTION');
        // 获取模板数据
        $listData = model('Template')->getTemplate();
        foreach ($listData['data'] as &$value) {
            $value['is_cache'] = ($value['is_cache'] == 1) ? '是' : '否';
            $value['ctime'] = date('Y-m-d H:i:s', $value['ctime']);
            $value['DOACTION'] = '<a href="'.U('admin/Content/upTemplate', array('tpl_id' => $value['tpl_id'])).'">编辑</a>&nbsp;-&nbsp;<a href="javascript:;" onclick="admin.delTemplate('.$value['tpl_id'].')">删除</a>';
        }

        $this->displayList($listData);
    }

    /**
     * 添加/编辑模板页面.
     */
    public function upTemplate()
    {
        $_REQUEST['tabHash'] = 'upTemplate';
        $this->pageTab[] = array('title' => '模板管理', 'tabHash' => 'template', 'url' => U('admin/Content/template'));
        if (isset($_GET['tpl_id'])) {
            $this->assign('pageTitle', '编辑模板');
            $this->pageTab[] = array('title' => '编辑模板', 'tabHash' => 'upTemplate', 'url' => U('admin/Content/upTemplate', array('tpl_id' => intval($_GET['tpl_id']))));
        } else {
            $this->assign('pageTitle', '添加模板');
            $this->pageTab[] = array('title' => '添加模板', 'tabHash' => 'upTemplate', 'url' => U('admin/Content/upTemplate'));
        }

        $this->pageKeyList = array('tpl_id', 'name', 'alias', 'title', 'body', 'lang', 'type', 'type2', 'is_cache');
        $this->opt['is_cache'] = array('否', '是');

        $this->notEmpty = array('name', 'lang');
        $this->onsubmit = 'admin.checkTemplate(this)';

        // 获取信息
        $detail = array();
        if (isset($_GET['tpl_id'])) {
            $tplId = intval($_GET['tpl_id']);
            $detail = model('Template')->getTemplateById($tplId);
        }

        $this->savePostUrl = !empty($detail) ? U('admin/Content/doSaveTemplate') : U('admin/Content/doAddTemplate');

        $this->displayConfig($detail);
    }

    public function doAddTemplate()
    {
        $data['name'] = t($_POST['name']);
        $data['alias'] = t($_POST['alias']);
        $data['title'] = t($_POST['title']);
        $data['body'] = t($_POST['body']);
        $data['lang'] = t($_POST['lang']);
        $data['type'] = t($_POST['type']);
        $data['type2'] = t($_POST['type2']);
        $data['is_cache'] = intval($_POST['is_cache']);
        $result = model('Template')->addTemplate($data);
        if ($result) {
            $this->assign('jumpUrl', U('admin/Content/template'));
            $this->success('添加成功');
        } else {
            $this->error('添加失败');
        }
    }

    public function doSaveTemplate()
    {
        $tplId = intval($_POST['tpl_id']);
        $data['name'] = t($_POST['name']);
        $data['alias'] = t($_POST['alias']);
        $data['title'] = t($_POST['title']);
        $data['body'] = t($_POST['body']);
        $data['lang'] = t($_POST['lang']);
        $data['type'] = t($_POST['type']);
        $data['type2'] = t($_POST['type2']);
        $data['is_cache'] = intval($_POST['is_cache']);
        $result = model('Template')->upTemplate($tplId, $data);
        if ($result) {
            $this->assign('jumpUrl', U('admin/Content/template'));
            $this->success('编辑成功');
        } else {
            $this->error('编辑失败');
        }
    }

    public function doDelTemplate()
    {
        $tplId = intval($_POST['id']);
        $result = array();
        if (empty($tplId)) {
            $result['status'] = 0;
            $result['data'] = '删除失败';
            exit(json_encode($result));
        }
        // 删除指定模板
        $res = model('Template')->delTemplate($tplId);
        if ($res) {
            $result['status'] = 1;
            $result['data'] = '删除成功';
        } else {
            $result['status'] = 0;
            $result['data'] = '删除失败';
        }
        exit(json_encode($result));
    }
}
