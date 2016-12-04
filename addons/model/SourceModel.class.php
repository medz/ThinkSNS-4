<?php
/**
 * 资源模型 - 业务逻辑模型
 * @example
 * 根据表名及资源ID，获取对应的资源信息
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class SourceModel
{
    /**
     * 获取指定资源，并格式化输出
     *
     * @param  string $table
     *                         资源表名
     * @param  int    $row_id
     *                         资源ID
     * @param  bool   $_forApi
     *                         是否提供API，默认为false
     * @param  string $appname
     *                         自定应用名称，默认为public
     * @return [type] [description]
     */
    public function getSourceInfo($table, $row_id, $_forApi = false, $appname = 'public')
    {
        static $forApi = '0';
        $forApi == '0' && $forApi = intval($_forApi);

        $key = $forApi ? $table.$row_id.'_api' : $table.$row_id;
        if ($info = static_cache('source_info_'.$key)) {
            return $info;
        }
        switch ($table) {
            case 'feed':
                $info = $this->getInfoFromFeed($table, $row_id, $_forApi);
                break;
            case 'comment':
                $info = $this->getInfoFromComment($table, $row_id, $_forApi);
                break;
            case 'poster':
                $poster = D('poster')->where('id='.$row_id)->field('title,uid,pid')->find();
                $info['title'] = $poster['title'];
                $info ['source_user_info'] = model('User')->getUserInfo($poster ['uid']);
                $info ['source_url'] = U('poster/Index/posterDetail', array(
                        'id' => $row_id,
                ));
                $info ['source_body'] = $poster ['title'].'<a class="ico-details" href="'.U('poster/Index/posterDetail', array(
                        'id' => $row_id,
                )).'"></a>';
                $info['category_id'] = $poster['pid'];
                $info['category_name'] = D('poster_type')->where('id='.$poster['pid'])->getField('name');
                break;
            case 'event':
                $event = D('event')->where('id='.$row_id)->field('title,uid,sTime,eTime,address,joinCount,attentionCount,coverId,feed_id')->find();
                $info['source_user_info'] = model('User')->getUserInfo($event['uid']);
                $info['source_url'] = U('event/Index/eventDetail', array('id' => $row_id, 'uid' => $event['uid']));
                $info['source_content'] = ($info['source_user_info'] !== false) ? '发表了一个活动' : '内容已被删除';
                $info['source_body'] = $event['title'].'<a class="ico-details" href="'.U('event/Index/eventDetail', array('id' => $row_id, 'uid' => $event['uid'])).'"></a>';
                $info['title'] = $event['title'];
                $info['feed_id'] = $event['feed_id'];
                $info['start_time'] = $event['sTime'];
                $info['end_time'] = $event['eTime'];
                $info['address'] = $event['address'];
                $info['join_count'] = $event['joinCount'];
                $info['attention_count'] = $event['attentionCount'];
                if (empty($event['coverId'])) {
                    $info['pic_url_small'] = THEME_PUBLIC_URL.'/image/event.png';
                    $info['pic_url'] = THEME_PUBLIC_URL.'/image/event.png';
                } else {
                    $attach = model('Attach')->getAttachById($event['coverId']);
                    $info['pic_url_small'] = getImageUrl($attach['save_path'].$attach['save_name'], 100, 100, true);
                    $info['pic_url'] = getImageUrl($attach['save_path'].$attach['save_name'], 200, 200, true);
                }
                break;
            case 'blog':
                $blog = D('blog')->where('id='.$row_id.' AND `status` = 1')->field('title,category,uid,content,feed_id')->find();
                $info['source_user_info'] = model('User')->getUserInfo($blog ['uid']);
                $info['source_url'] = U('blog/Index/show', array('id' => $row_id, 'mid' => $blog['uid']));
                $info['source_content'] = ($info['source_user_info'] !== false) ? '发表了一篇知识' : '内容已被删除';
                $info['source_body'] = $blog['title'].'<a class="ico-details" href="'.U('blog/Index/show', array('id' => $row_id, 'mid' => $blog['uid'])).'"></a>';
                $info['title'] = $blog['title'];
                $info['content'] = strip_tags($blog['content']);
                $info['feed_id'] = $blog['feed_id'];
                $info['category_id'] = $blog['category'];
                $info['category_name'] = D('blog_category')->where('id='.$blog['category'])->getField('name');
                // 获取编辑器中的图片内容
                $editorImage = $this->getEditorImages($blog['content']);
                $info['pic_url_small'] = $editorImage['pic_url_small'];
                $info['pic_url'] = $editorImage['pic_url'];
                break;
            case 'photo':
                $photo = D('photo')->where('id='.$row_id)->field('name, albumId, userId, savepath, feed_id')->find();
                $info['source_user_info'] = model('User')->getUserInfo($photo['userId']);
                $info['source_url'] = U('photo/Index/photo', array('id' => $row_id, 'aid' => $photo['albumId'], 'uid' => $photo['userId']));
                $uploadCount = D('photo')->where('feed_id='.$photo['feed_id'])->count();
                $info['photo_upload_count'] = $uploadCount;
                $info['source_content'] = ($info['source_user_info'] !== false) ? '上传了'.$uploadCount.'张照片' : '内容已被删除';
                $info['source_body'] = $photo['name'].'<a class="ico-details" href="'.$info['source_url'].'"></a>';
                $album = D('photo_album')->where('id='.$photo['albumId'])->find();
                $info['title'] = $album['name'];
                $info['feed_id'] = $photo['feed_id'];
                $info['photo_count'] = $album['photoCount'];
                $info['cover_image_path_small'] = getImageUrl($photo['savepath'], 100, 100, true);
                $info['cover_image_path'] = getImageUrl($photo['savepath'], 200, 200, true);
                $info['album_url'] = U('photo/Index/album', array('uid' => $photo['userId'], 'id' => $photo['albumId']));
                break;
            case 'vote':
                $vote = D('vote')->where('id='.$row_id)->field('title, uid, vote_num, cTime,feed_id')->find();
                $info['source_user_info'] = model('User')->getUserInfo($vote['uid']);
                $info['source_url'] = U('vote/Index/pollDetail', array('id' => $row_id));
                $info['source_content'] = ($info['source_user_info'] !== false) ? '发表了一个投票' : '内容已被删除';
                $info['source_body'] = $vote ['title'].'<a class="ico-details" href="'.U('vote/Index/pollDetail', array('id' => $row_id)).'"></a>';
                $info['feed_id'] = $vote['feed_id'];
                $voteOpts = D('VoteOpt')->where('vote_id='.$row_id)->order('id ASC')->findAll();
                $info['vote_opts'] = $voteOpts;
                $info['title'] = $vote['title'];
                $info['ctime'] = $vote['cTime'];
                $info['vote_num'] = $vote['vote_num'];
                break;
            case 'develop':
                $develop = D('develop')->where('develop_id='.$row_id)->field('title, uid')->find();
                $info['source_user_info'] = model('User')->getUserInfo($develop['uid']);
                $info['source_url'] = U('develop/Index/detail', array('id' => $row_id));
                $info['source_body'] = $develop['title'].'<a class="ico-details" href="'.U('develop/Index/detail', array('id' => $row_id)).'"></a>';
                break;
            case 'weiba_post':
                $weiba = D('weiba_post')->where('post_id='.$row_id.' AND is_del = 0')->field('weiba_id, post_uid, title, content,feed_id,post_time')->find();
                $info['publish_time'] = $weiba['post_time'];
                $info['source_user_info'] = model('User')->getUserInfo($weiba['post_uid']);
                $info['source_url'] = U('weiba/Index/postDetail', array('post_id' => $row_id));
                $info['source_content'] = ($info['source_user_info'] !== false) ? '发表了一个帖子' : '内容已被删除';
                $info['source_body'] = $weiba['title'].'<a class="ico-details" href="'.U('weiba/Index/postDetail', array('post_id' => $row_id)).'"></a>';
                $info['title'] = $weiba['title'];
                $info['content'] = trim(strip_tags($weiba['content']));
                $info['feed_id'] = $weiba['feed_id'];
                $info['weiba_name'] = D('weiba')->where('weiba_id='.$weiba['weiba_id'])->getField('weiba_name');
                $info['weiba_url'] = U('weiba/Index/detail', array('weiba_id' => $weiba['weiba_id']));
                // 获取编辑器中的图片内容
                $editorImage = $this->getEditorImages($weiba['content']);
                $info['pic_url_small'] = $editorImage['pic_url_small'];
                $info['pic_url_medium'] = $editorImage['pic_url_medium'];
                $info['pic_url'] = $editorImage['pic_url'];
                break;
            default:
                // 单独的内容，通过此路径获取资源信息
                // 通过应用下的{$appname}ProtocolModel.Class.php模型里的getSourceInfo方法，来写各应用的来源数据获取方法
                $appname = strtolower($appname);
                $name = ucfirst($appname);
                $dao = D($name.'Protocol', $appname, false);
                if (method_exists($dao, 'getSourceInfo')) {
                    $info = $dao->getSourceInfo($row_id, $_forApi);
                }
                unset($dao);

                // 兼容旧方案
                if (!$info) {
                    $modelArr = explode('_', $table);
                    $model = '';
                    foreach ($modelArr as $v) {
                        $model .= ucfirst($v);
                    }
                    $dao = D($model, $appname);
                    if (method_exists($dao, 'getSourceInfo')) {
                        $info = $dao->getSourceInfo($row_id, $_forApi);
                    }
                }
                break;
        }
        $info ['source_table'] = $table;
        $info ['source_id'] = $row_id;
        static_cache('source_info_'.$key, $info);

        return $info;
    }

    /**
     * 从Feed中提取资源数据
     *
     * @param  string $table
     *                        资源表名
     * @param  int    $row_id
     *                        资源ID
     * @param  bool   $forApi
     *                        是否提供API，默认为false
     * @return array  格式化后的资源数据
     */
    private function getInfoFromFeed($table, $row_id, $forApi)
    {
        $info = model('Feed')->getFeedInfo($row_id, $forApi);
        $info ['source_user_info'] = model('User')->getUserInfo($info ['uid']);
        $info ['source_user'] = $info ['uid'] == $GLOBALS ['ts'] ['mid'] ? L('PUBLIC_ME') : $info ['source_user_info'] ['space_link']; // 我
        $info ['source_type'] = L('PUBLIC_WEIBO');
        $info ['source_title'] = $forApi ? parseForApi($_info ['user_info'] ['space_link']) : $_info ['user_info'] ['space_link']; // 分享title暂时为空
        $info ['source_url'] = U('public/Profile/feed', array(
                'feed_id' => $row_id,
                'uid' => $info ['uid'],
        ));
        $info ['source_content'] = $info ['content'];
        $info ['ctime'] = $info ['publish_time'];
        $info ['is_del'] = $info ['is_del'];
        unset($info ['content']);

        return $info;
    }

    /**
     * 从评论中提取资源数据
     *
     * @param  string $table
     *                        资源表名
     * @param  int    $row_id
     *                        资源ID
     * @param  bool   $forApi
     *                        是否提供API，默认为false
     * @return array  格式化后的资源数据
     */
    private function getInfoFromComment($table, $row_id, $forApi)
    {
        $_info = model('Comment')->getCommentInfo($row_id, true);
        $info ['uid'] = $_info ['app_uid'];
        $info ['row_id'] = $_info ['row_id'];
        $info ['is_audit'] = $_info ['is_audit'];
        $info ['source_user'] = $info ['uid'] == $GLOBALS ['ts'] ['mid'] ? L('PUBLIC_ME') : $_info ['user_info'] ['space_link']; // 我
        $info ['comment_user_info'] = model('User')->getUserInfo($_info ['user_info'] ['uid']);
        $forApi && $info ['source_user'] = parseForApi($info ['source_user']);
        $info ['source_user_info'] = model('User')->getUserInfo($info ['uid']);
        $info ['source_type'] = L('PUBLIC_STREAM_COMMENT'); // 评论
        $info ['source_content'] = $forApi ? parseForApi($_info ['content']) : $_info ['content'];
        $info ['source_url'] = $_info ['sourceInfo'] ['source_url'];
        $info ['ctime'] = $_info ['ctime'];
        $info ['app'] = $_info ['app'];
        $info ['sourceInfo'] = $_info ['sourceInfo'];
        // 分享title暂时为空
        $info ['source_title'] = $forApi ? parseForApi($_info ['user_info'] ['space_link']) : $_info ['user_info'] ['space_link'];

        return $info;
    }

    public function getCommentSource($data, $forApi = false)
    {
        if ($data['table'] == 'feed' || $data['table'] == 'comment' || empty($data['app_detail_summary']) || $forApi) {
            return $this->getSourceInfo($data['table'], $data['row_id'], $forApi, $data['app']);
        }
        //新的应用评论机制 20130607
        $info['source_user_info'] = model('User')->getUserInfo($data['app_uid']);
        $info['source_url'] = $data['app_detail_url'];
        $info['source_body'] = t($data['app_detail_summary']);
        //dump($info);
        return $info;
    }

    /**
     * 获取编辑器内容中的第一个图片（非表情图片）
     * @param  string $content 编辑器内容
     * @return array 图片的地址数组
     */
    private function getEditorImages($content)
    {
        preg_match_all('/<img.*src=\s*[\'"](.*)[\s>\'"]/isU', $content, $matchs);
        $info['pic_url'] = $info['pic_url_small'] = '';
        foreach ($matchs[1] as $match) {
            if (strpos($match, '/emotion/') === false) {
                $file = null;
                $path = substr(UPLOAD_PATH, strlen(SITE_PATH));
                if (strpos($match, $path)) {
                    list($unkn, $file) = explode($path, $match, 2);
                }
                if ($file && is_file(UPLOAD_PATH.$file)) {
                    $info['pic_url_small'] = getImageUrl($file, 120, 120, true);
                    $info['pic_url_medium'] = getImageUrl($file, 240);
                    $info['pic_url'] = getImageUrl($file);
                } else {
                    $info['pic_url_small'] = $match;
                    $info['pic_url_medium'] = $match;
                    $info['pic_url'] = $match;
                }
                break;
            }
        }

        return $info;
    }
}
