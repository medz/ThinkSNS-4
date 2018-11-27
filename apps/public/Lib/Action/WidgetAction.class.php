<?php
/**
 * 插件请求控制器.
 *
 * @author zivss guolee226@gmail.com
 *
 * @version TS3.0
 */
class WidgetAction extends Action
{
    public function renderWidget()
    {
        //非登录下widget调用过滤
        if (!$this->mid) {
            $access_widget = array();
            if (!in_array($_REQUEST['name'], $access_widget)) {
                exit;
            }
        }
        $_REQUEST['name'] = t($_REQUEST['name']);
        $_REQUEST['param'] = unserialize(urldecode($_REQUEST['param']));
        send_http_header('utf8');
        echo empty($_REQUEST['name']) ? 'Invalid Param.' : W(t($_REQUEST['name']), t($_REQUEST['param']));
    }

    // 插件的请求转发
    public function addonsRequest()
    {
        Addons::addonsHook(t($_REQUEST['addon']), t($_REQUEST['hook']));
    }

    // 插件的渲染
    public function displayAddons()
    {
        $result = array();
        $param['res'] = &$result;
        $param['type'] = $_REQUEST['type'];
        Addons::addonsHook(t($_GET['addon']), t($_GET['hook']), $param);
        isset($result['url']) && $this->assign('jumpUrl', $result['url']);
        isset($result['title']) && $this->setTitle($result['title']);
        isset($result['jumpUrl']) && $this->assign('jumpUrl', $result['jumpUrl']);
        if (isset($result['status']) && !$result['status']) {
            $this->error($result['info']);
        }
        if (isset($result['status']) && $result['status']) {
            $this->success($result['info']);
        }
    }

    // 发分享
    public function weibo()
    {
        // 解析参数
        $_REQUEST['param'] = unserialize(urldecode($_REQUEST['param']));
        $active_field = $_REQUEST['param']['active_field'] == 'title' ? 'title' : 'body';
        $this->assign('has_status', $_REQUEST['param']['has_status']);
        $this->assign('is_success_status', $_REQUEST['param']['is_success_status']);
        $this->assign('status_title', t($_REQUEST['param']['status_title']));

        // 解析模板(统一使用模板的body字段)
        $_REQUEST['data'] = unserialize(urldecode($_REQUEST['data']));
        $content = model('Template')->parseTemplate(t($_REQUEST['tpl_name']), array($active_field => $_REQUEST['data']));
        // 设置分享发布框的权限
        $type = array('at', 'image', 'video', 'file', 'contribute');
        $actions = array();
        foreach ($type as $value) {
            $actions[$value] = false;
        }
        $this->assign('actions', $actions);
        $this->assign('title', $content['title']);
        $this->assign('initHtml', $content['body']);

        $this->assign('content', h($content[$active_field]));
        $this->assign('source', $_REQUEST['data']['source']);
        $this->assign('sourceUrl', $_REQUEST['data']['url']);
        $this->assign('type', $_REQUEST['data']['type']);
        $this->assign('type_data', $_REQUEST['data']['type_data']);
        $this->assign('button_title', t(urldecode($_REQUEST['button_title'])));
        $this->assign('addon_info', urldecode($_REQUEST['addon_info']));
        $this->display();
    }

    public function share()
    {
        $data['content'] = urldecode($_GET['title']).' '.urldecode($_GET['url']).' ';
        $data['source'] = urldecode($_GET['sourceTitle']);
        $data['sourceUrl'] = urldecode($_GET['sourceUrl']);

        // 获取远程图片 => 生成临时图片
        if ($pic_url = urldecode($_GET['picUrl'])) {
            // http://d.hiphotos.baidu.com/image/w%3D2048/sign=31cded21bb12c8fcb4f3f1cdc83b9345/ac4bd11373f0820219e90e3e49fbfbedab641bb3.jpg
            $imageInfo = getimagesize($pic_url);
            $imageType = strtolower(substr(image_type_to_extension($imageInfo[2]), 1));
            if ('bmp' != $imageType) { // 禁止BMP格式的图片
                $save_path = SITE_PATH.'/data/uploads/temp'; // 临时图片地址
                $filename = md5($pic_url).'.'.$imageType; // 重复刷新时, 生成的文件名应一致
                $img = file_get_contents($pic_url);
                $filepath = $save_path.'/'.$filename;
                $result = file_put_contents($filepath, $img);
                if ($result) {
                    $data['type'] = 1;
                    $data['type_data'] = 'temp/'.$filename;
                }
            }
        }

        // 权限控制
        $type = array('face', 'at', 'image', 'video', 'file', 'topic', 'contribute');
        foreach ($type as $value) {
            $data['actions'][$value] = (in_array($value, array('face', 'image'))) ? true : false;
        }

        $this->assign($data);
        $this->display();
    }

    public function weiboShow()
    {
        $width = intval($_GET['width']);
        $height = intval($_GET['height']);
        $skin = t($_GET['skin']);
        // 分享秀样式
        $data['style']['width'] = $width < 190 ? 190 : ($width > 1024 ? 1024 : $width);
        $data['style']['height'] = $height < 75 ? 75 : ($height > 800 ? 800 : $height);
        $data['style']['skin'] = $skin;

        $data['uid'] = $this->uid;
        // 用户基本信息
        $data['user'] = model('User')->getUserInfo($this->uid);
        // 分享列表
        $user_data['user_id'] = $this->uid;
        // $data['weibolist'] = api('Statuses')->data($user_data)->user_timeline();
        $map['uid'] = $this->uid;
        $map['is_del'] = 0;
        $weibolist = model('Feed')->getList($map, 20);
        $data['weibolist'] = $weibolist['data'];
        // 粉丝列表
        $data['follower'] = model('Follow')->getFollowerList($this->uid);

        $data['fid'] = $this->uid;
        $data['uname'] = $data['user']['uname'];
        $data['follow_state'] = model('Follow')->getFollowState($this->mid, $this->uid);
        $data['union_state'] = model('Union')->getUnionState($this->mid, $uid);

        $this->assign($data);
        $this->display();
    }

    // 评论箱
    public function webpageComment()
    {
        $url = t(urldecode($_GET['url']));
        if (!$url) {
            exit('URL参数不可为空');
        }
        // 获取已生成的包含该地址的分享ID
        $hash = md5($url);
        $webpage_model = M('webpage');
        $webpage_info = $webpage_model->where("`hash`='{$hash}'")->find();
        // 若不存在对应的分享，则创建之
        if (!$webpage_info) {
            $content = file_get_contents($url);
            // 抓取内容失败，则退出
            if (!$content) {
                exit('网页不存在');
            }
            // 网页标题
            preg_match("/<title>\s*(.+)\s*<\/title>/i", $content, $title);
            $title = $title[1];
            // 拼装分享内容
            $weibo_content = array(
                'content' => $title.' '.$url,
            );
            // // 评论箱UID
            // $uid = 10315;
            // // 生成分享
            // $weibo_id = D('Weibo', 'weibo')->publish(10315, $weibo_content);
            // if (false == $weibo_id) {
            // 	exit('添加分享失败');
            // }
            // 保存信息
            $webpage_info = array(
                'url'   => $url,
                'hash'  => $hash,
                'title' => t($title),
            );
            $webpage_id = $webpage_model->add($webpage_info);
            if (false === $webpage_id) {
                exit('添加网页失败');
            }
            $webpage_info['webpage_id'] = $webpage_id;
        }

        // 分享秀样式
        $data['style']['width'] = $_GET['width'] < 190 ? 190 : ($_GET['width'] > 1024 ? 1024 : intval($_GET['width']));
        $data['style']['skin'] = t($_GET['skin']);

        $this->assign('webpage_info', $webpage_info);
        $this->assign($data);

        $this->assign('appid', $webpage_info['webpage_id']);

        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $this->assign('weibo_nums', $weiboSet['weibo_nums']);

        $commentList = $this->getComment($webpage_info['webpage_id']);
        $this->assign('commentList', $commentList);

        $this->display();
    }

    public function addcomment()
    {
        $data['app'] = 'public';
        $data['table'] = 'webpage';
        $data['row_id'] = intval($_POST['row_id']);
        $data['app_uid'] = 0;
        $data['uid'] = $this->mid;
        $data['content'] = t($_POST['content']);
        $data['to_comment_id'] = intval($_POST['to_comment_id']);
        $data['to_uid'] = intval($_POST['to_uid']);
        // $data['data'] = '';
        $data['ctime'] = time();
        $data['is_del'] = 0;
        $data['client_type'] = 0;
        $data['is_audit'] = 1;
        $data['storey'] = 0;
        $data['app_detail_url'] = '';
        $data['app_detail_summary'] = '';
        $data['client_ip'] = get_client_ip();
        $data['client_port'] = get_client_port();
        $result = model('Comment')->add($data);

        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['data'] = '评论成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '评论失败';
        }

        $with_new_weibo = intval($_POST['with_new_weibo']);
        if ($with_new_weibo == 1) {
            // 用户发送内容
            $d['content'] = '';
            $d['body'] = $data['content'];
            $d['source_url'] = '';
            // 滤掉话题两端的空白
            $d['body'] = preg_replace("/#[\s]*([^#^\s][^#]*[^#^\s])[\s]*#/is", '#'.trim('${1}').'#', $d['body']);
            // 发送分享的类型
            $type = 'post';
            // 所属应用名称
            $app = 'public';
            if ($data = model('Feed')->put($this->mid, $app, $type, $d)) {
                model('Credit')->setUserCredit($this->mid, 'add_weibo');
                // 添加话题
                model('FeedTopic')->addTopic(html_entity_decode($d['body'], ENT_QUOTES, 'UTF-8'), $data['feed_id'], $type);
                // 更新用户最后发表的分享
                $last['last_feed_id'] = $data['feed_id'];
                $last['last_post_time'] = $_SERVER['REQUEST_TIME'];
                model('User')->where('uid='.$this->mid)->save($last);
            }
        }

        exit(json_encode($res));
    }

    public function getComment($row_id, $limit = 10)
    {
        $map['row_id'] = $row_id;
        $map['app'] = 'public';
        $map['table'] = 'webpage';
        $map['is_del'] = 0;
        $list = model('Comment')->where($map)->order('comment_id DESC')->findPage($limit);

        return $list;
    }

    public function delComment()
    {
        $id = intval($_POST['id']);
        $map['comment_id'] = $id;
        $result = model('Comment')->where($map)->setField('is_del', 1);
        $res = array();
        if ($result) {
            $res['status'] = 1;
            $res['data'] = '删除成功';
        } else {
            $res['status'] = 0;
            $res['data'] = '删除成功';
        }

        exit(json_encode($res));
    }

    // 批量关注挂件
    public function bulkFollow()
    {
        $uids = t($_GET['uids']);
        $uids = $uids ? explode(',', $uids) : array();
        $uids = array_unique(array_filter(array_map('intval', $uids)));

        $user_list = model('User')->getUserInfoByUids($uids);

        // 按照$_GET['uids'] 的顺序排序
        $_user_list = array_combine($uids, $uids);
        foreach ($user_list as $user) {
            $_user_list[$user['uid']] = $user;
        }
        // 过滤不存在的uid
        $user_list = array_diff($_user_list, $uids);

        // 分享秀样式
        $data['style']['width'] = $_GET['width'] < 190 ? 190 : ($_GET['width'] > 1024 ? 1024 : intval($_GET['width']));
        $data['style']['skin'] = t($_GET['skin']);

        $this->assign('user_list', $user_list);
        $this->assign($data);
        $this->display();
    }

    public function doBulkFollow()
    {
        $fids = t($_POST['fids']);
        $fids = explode(',', $fids);
        $fids = array_diff($fids, array($this->mid));
        $fids = implode(',', $fids);
        $res = model('Follow')->bulkDoFollow($this->mid, $fids);
        $this->ajaxReturn($res, model('Follow')->getError(), false !== $res);
    }
}
