<?php
/**
 * ShareAction 分享控制器.
 *
 * @author    jason <yangjs17@yeah.net>
 *
 * @version   TS3.0
 */
class ShareAction extends Action
{
    /**
     * _initialize 模块初始化.
     */
    protected function _initialize()
    {
    }

    /**
     * 分享控制.
     */
    public function index()
    {
        $shareInfo['sid'] = intval($_GET['sid']);
        $shareInfo['stable'] = t($_GET['stable']);
        $shareInfo['initHTML'] = h($_GET['initHTML']);
        $shareInfo['curid'] = t($_GET['curid']);
        $shareInfo['curtable'] = t($_GET['curtable']);
        $shareInfo['appname'] = t($_GET['appname']);
        $shareInfo['cancomment'] = intval($_GET['cancomment']);
        $shareInfo['is_repost'] = intval($_GET['is_repost']);
        if (empty($shareInfo['stable']) || empty($shareInfo['sid'])) {
            echo L('PUBLIC_TYPE_NOEMPTY');
            exit();
        }
        if (!$oldInfo = model('Source')->getSourceInfo($shareInfo['stable'], $shareInfo['sid'], false, $shareInfo['appname'])) {
            echo L('PUBLIC_INFO_SHARE_FORBIDDEN');
            exit();
        }
        empty($shareInfo['appname']) && $shareInfo['appname'] = $oldInfo['app'];
        if ($shareInfo['appname'] != '' && $shareInfo['appname'] != 'public') {
            addLang($shareInfo['appname']);
        }
        if (empty($shareInfo['initHTML']) && !empty($shareInfo['curid'])) {
            //判断是否为转发的分享
            if ($shareInfo['curid'] != $shareInfo['sid'] && $shareInfo['is_repost'] == 1) {
                $app = $curtable == $shareInfo['stable'] ? $shareInfo['appname'] : 'public';
                $curInfo = model('Source')->getSourceInfo($shareInfo['curtable'], $shareInfo['curid'], false, $app);
                $userInfo = $curInfo['source_user_info'];
                // if($userInfo['uid'] != $this->mid){	//分享其他人的分享，非自己的
                    $shareInfo['initHTML'] = ' //@'.$userInfo['uname'].'：'.$curInfo['source_content'];
                // }
                $shareInfo['initHTML'] = str_replace(array("\n", "\r"), array('', ''), $shareInfo['initHTML']);
            }
        }
        if (!CheckPermission('core_normal', 'feed_comment')) {
            $shareInfo['cancomment'] = 0;
        }
        if ($shareInfo['sid'] != $shareInfo['curid']) {
            //获取被评论的分享信息
                $source = model('Feed')->get($shareInfo['sid']);
                //判断是否有权限评论当前用户
                if ($this->mid != $source['uid']) {
                    $userPrivacy = model('UserPrivacy')->getPrivacy($this->mid, $source['uid']);
                    if ($userPrivacy['comment_weibo'] == 1) {
                        $shareInfo['cancomment'] = 0;
                    } else {
                        $shareInfo['cancomment'] = 1;
                    }
                }
        }
        $shareInfo['shareHtml'] = !empty($oldInfo['shareHtml']) ? $oldInfo['shareHtml'] : '';
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $canShareFeed = in_array('repost', $weiboSet['weibo_premission']) ? 1 : '0';
        $this->assign('canShareFeed', $canShareFeed);
        $this->assign('initNums', $weiboSet['weibo_nums']);
        $this->assign('shareInfo', $shareInfo);
        $this->assign('oldInfo', $oldInfo);
        $this->display();
    }

    /**
     * 分享信息.
     *
     * @return mix 分享状态和提示
     */
    public function shareMessage()
    {
        $post = $_POST;
        // 安全过滤
        foreach ($post as $key => $val) {
            $post[$key] = t($post[$key]);
        }
        // 判断资源是否存在
        // 判断资源是否删除
        if (empty($post['curid'])) {
            $map['feed_id'] = $post['sid'];
        } else {
            $map['feed_id'] = $post['curid'];
        }
        $map['is_del'] = 0;
        $isExist = model('Feed')->where($map)->count();
        if ($isExist == 0) {
            $return['status'] = 0;
            $return['data'] = '内容已被删除，分享失败';
            exit(json_encode($return));
        }
        // 过滤数据，安全性
        foreach ($post as $key => $val) {
            $post[$key] = t($post[$key]);
        }

        exit(json_encode(model('Share')->shareMessage($post)));
    }

    /**
     * 分享到分享.
     */
    public function shareToFeed()
    {
        $var = array();
        $var['mid'] = $GLOBALS['ts']['mid'];
        $var['initHTML'] = $_GET['initHTML'];
        $var['source_url'] = urlencode($_GET['source_url']);
        $var['post_event'] = 'post_feed_box';
        $var['cancomment'] = 0;
        !$var['send_type'] && $var['send_type'] = 'send_weibo';
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $var['weibo_type'] = $weiboSet['weibo_type'];
        $var['weibo_premission'] = $weiboSet['weibo_premission'];
        $var['attachId'] = intval($_GET['attachId']);
        //取附件
        if ($var['attachId']) {
            $attachIds = is_array($var['attachId']) ? $var['attachId'] : explode(',', $var['attachId']);
            $attach = model('Attach')->getAttachByIds($attachIds);
            foreach ($attach as $ak => $av) {
                $_attach = array(
                    'attach_id'   => $av['attach_id'],
                    'attach_name' => $av['name'],
                    'attach_url'  => getImageUrl($av['save_path'].$av['save_name']),
                    'extension'   => $av['extension'],
                    'size'        => $av['size'],
                );
                if (in_array($av['extension'], array('jpg', 'png', 'gif', 'bmp'))) {
                    $_attach['attach_small'] = getImageUrl($av['save_path'].$av['save_name'], 100, 100, true);
                    $_attach['attach_middle'] = getImageUrl($av['save_path'].$av['save_name'], 740);
                }
                $var['attachInfo'][] = $_attach;
            }
            $var['attach_ids'] = '|'.implode('|', $attachIds).'|';
        }

        !$var['type'] && $var['type'] = 'post';
        !$var['app_name'] && $var['app_name'] = 'public';
        !$var['prompt'] && $var['prompt'] = '分享成功';
        $var['time'] = $_SERVER['REQUEST_TIME'];

        // 权限控制
        $type = array('face', 'at', 'image', 'video', 'file', 'topic', 'contribute');
        foreach ($type as $value) {
            !isset($var['actions'][$value]) && $var['actions'][$value] = true;
        }
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $var['initNums'] = $weiboSet['weibo_nums'];
        $var['weibo_type'] = $weiboSet['weibo_type'];
        $this->assign($var);
        $this->display();
    }
}
