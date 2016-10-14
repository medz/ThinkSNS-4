<?php

use Apps\Information\Model\Cate;
use Apps\Information\Model\Subject;
// use Ts\Models\InformationList;
use Ts\Models as Model;

/**
 * 资讯接口api类
 * Wayne qiaobinloverabbi@gmail.com.
 */
class InformationApi extends Api
{
    /**
     * 阅读资讯详情.
     *
     * @author Seven Du <lovevipdsw@outlook.com>
     * @datetime 2016-05-08T11:37:33+0800
     * @homepage http://medz.cn
     */
    public function reader()
    {
        $id = intval($_REQUEST['id']);
        $info = Model\InformationList::find($id);
        $info->increment('hits', 1);
        if (!$info) {
            return array(
                'status' => 0,
                'message' => '访问的资讯不存在！',
            );
        }
        echo '<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, height=device-height, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no, minimal-ui">
  <title>'.htmlspecialchars($info->subject, ENT_QUOTES, 'UTF-8').'</title>
  <style type="text/css">
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    html, body {
      font-family: -apple-system-font,"Helvetica Neue","PingFang SC","Hiragino Sans GB","Microsoft YaHei",sans-serif;
    }
    .wrap {
      width: 100%;
      height: auto;
      padding: 20px 15px 15px;
      background-color: #fff;
    }

    .wrap .title {
      margin-bottom: 10px;
      line-height: 1.4;
      font-weight: 400;
      font-size: 25px;
    }

    .wrap .date {
      position: relative;
      width: 100%;
      margin-bottom: 18px;
      line-height: 20px;
      font-size: 17px;
      font-style: normal;
      color: #8c8c8c;
    }

    .wrap .date .right {
      position: absolute;
      right: 0;
    }

    .wrap .abstract {
      width: 100%;
      height: auto;
      margin-bottom: 18px;
      padding: 10px;
      background: #edeeef;
    }

    .wrap .content {
      width: 100%;
      max-width: 100%;
      height: auto;
      overflow-x: hidden;
      color: #3e3e3e;
    }
    .content img{max-width:100%!important;}
  </style>
</head>
<body>
<div class="wrap">
  <h2 class="title">'.htmlspecialchars($info->subject, ENT_QUOTES, 'UTF-8').'</h2>
  <div class="date">
    '.date('Y-m-d', $info->rtime).'
    <span class="right">浏览：'.intval($info->hits).'</span>
  </div>
  <div class="abstract"><strong>[摘要]&nbsp;</strong>'.htmlspecialchars($info->abstract, ENT_QUOTES, 'UTF-8').'</div>
  <div class="content">
    '.$info->content.'
  </div>
</div>
</body>
</html>
';
        exit;
    }

    /**
     * 新闻列表接口.
     *
     * @ wayne qiaobinloverabbi@gmail.com
     * @DateTime  2016-04-27T09:26:55+0800
     */
    public function newsList()
    {
        !$_REQUEST['cid'] && $this->error('资讯分类不能为空');
        $catid = intval($_REQUEST['cid']);
        $newsModel = Subject::getInstance();
        $map['cid'] = $catid;
        $map['isPre'] = 0;
        $map['isDel'] = 0;
        //根据标题进行搜索
        if (isset($this->data['subject'])) {
            $map['subject'] = array('like', '%'.t($this->data['subject']).'%');
        }
        $this->data['max_id'] && $map['id'] = array('lt', $this->data['max_id']);
        $this->data['limit'] && $limit = $this->data['limit'] ? $this->data['limit'] : 10;
        $newsList = $newsModel->where($map)->field('id,cid,subject,abstract,author,ctime,hits,content')->limit($limit)->order('id desc')->select();
        if (!empty($newsList)) {
            foreach ($newsList as &$subject) {
                $subject['url'] = sprintf('%s/api.php?mod=Information&act=readInformation&id=%d', SITE_URL, intval($subject['id']));
                preg_match_all('/\<img(.*?)src\=\"(.*?)\"(.*?)\/?\>/is', $subject['content'], $image);
                $image = $image[2];
                if ($image && is_array($image) && count($image) >= 1) {
                    $image = $image[0];
                    if (!preg_match('/https?\:\/\//is', $image)) {
                        $image = parse_url(SITE_URL, PHP_URL_SCHEME).'://'.parse_url(SITE_URL, PHP_URL_HOST).'/'.$image;
                    }
                }
                $subject['image'] = $image ? $image : '';
                $subject['authorName'] = getUserName($subject['author']);
                $subject['ctimes'] = friendlyDate($subject['ctime'], 'mohu');
                $subject['commentNum'] = $this->_getComentNum($subject['id']);
                unset($subject['content']);
            }
            $this->success(array('data' => $newsList));
        } else {
            $this->error('暂时没有资讯');
        }
    }
    //文章搜索
    public function search_infos()
    {
        if (!$this->data['subject']) {
            $this->error('搜索内容不能为空！');
        }
        $newsModel = Subject::getInstance();
        $map['isPre'] = 0;
        $map['isDel'] = 0;
        //根据标题进行搜索
        $map['subject'] = array('like', '%'.t($this->data['subject']).'%');
        $this->data['max_id'] && $map['id'] = array('lt', $this->data['max_id']);
        $this->data['limit'] && $limit = $this->data['limit'] ? $this->data['limit'] : 10;
        $newsList = $newsModel->where($map)->field('id,cid,subject,abstract,author,ctime,hits,content')->limit($limit)->order('id desc')->select();
        if (!empty($newsList)) {
            foreach ($newsList as &$subject) {
                $subject['url'] = sprintf('%s/api.php?mod=Information&act=readInformation&id=%d', SITE_URL, intval($subject['id']));
                preg_match_all('/\<img(.*?)src\=\"(.*?)\"(.*?)\/?\>/is', $subject['content'], $image);
                $image = $image[2];
                if ($image && is_array($image) && count($image) >= 1) {
                    $image = $image[0];
                    if (!preg_match('/https?\:\/\//is', $image)) {
                        $image = parse_url(SITE_URL, PHP_URL_SCHEME).'://'.parse_url(SITE_URL, PHP_URL_HOST).'/'.$image;
                    }
                }
                $subject['image'] = $image ? $image : '';
                $subject['authorName'] = getUserName($subject['author']);
                $subject['ctimes'] = friendlyDate($subject['ctime'], 'mohu');
                $subject['commentNum'] = $this->_getComentNum($subject['id']);
                unset($subject['content']);
            }
            $this->success(array('data' => $newsList));
        } else {
            $this->error('没有搜索到相关的内容');
        }
    }
    /**
     * 删除文章.
     *
     * @author zhangwei
     * @date   2016-09-26
     *
     * @return json
     */
    public function delPost()
    {
        !$this->data['post_id'] && $this->error('参数错误');
        $map['id'] = intval($this->data['post_id']);
        $list = D('information_list')->where($map)->find();
        if (!count($list)) {
            $this->error('文章不存在');
        }
        if ($list['author'] != $this->mid) {
            $this->error('没有此权限');
        }
        $map['isDel'] = 1;
        if (D('information_list')->save($map)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 咨询分类.
     *
     * @Author Wayne qiaobinloverabbi@gmail.com
     * @DateTime  2016-04-27T09:49:19+0800
     */
    public function newsCate()
    {
        $cateModel = Cate::getInstance();
        $cates = $cateModel->where(['isDel' => 0])->order('rank asc')->select();
        if (!empty($cates)) {
            $return ['msg'] = '获取分类成功';
            $return ['status'] = 1;
            $return ['data'] = $cates;

            return $return;
        } else {
            $return ['msg'] = '没有找到分类';
            $return ['status'] = 0;
            $return ['data'] = '';

            return $return;
        }
    }

    /**
     * 获取评论数.
     *
     * @param int $sid 主题ID
     *
     * @return int 评论数
     *
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    private function _getComentNum($sid)
    {
        $where = '`is_del` = 0 AND `app` = \'Information\' AND `table` = \'%s\' AND `row_id` = %d';
        $where = sprintf($where, 'information_list', intval($sid));

        return model('Comment')->where($where)->field('comment_id')->count();
    }

    /**
     * 收藏文章.
     *
     * @author zhangwei
     * @date   2016-09-26
     *
     * @return json
     */
    public function favorite()
    {
        !$this->data['post_id'] && $this->error('参数错误');
        $data['post_id'] = intval($this->data['post_id']);
        $data['post_uid'] = intval($this->data['post_uid']);
        $data['uid'] = $this->mid;
        if (D('information_favorite')->where($data)->count()) {
            $this->error('已收藏');
        }
        $data['favorite_time'] = time();
        if (D('information_favorite')->add($data)) {
            $this->success('收藏成功');
        } else {
            $this->error('收藏失败');
        }
    }

    /**
     * 取消收藏文章.
     *
     * @author zhangwei
     * @date   2016-09-26
     *
     * @return json
     */
    public function unFavorite()
    {
        !$this->data['post_id'] && $this->error('参数错误');
        $map['post_id'] = intval($this->data['post_id']);
        $map['uid'] = $this->mid;
        if (D('information_favorite')->where($map)->delete()) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 文章点赞.
     *
     * @author zhangwei
     * @date   2016-09-26
     *
     * @return json
     */
    public function addPostDigg()
    {
        !$this->data['post_id'] && $this->error('参数错误');
        $data['post_id'] = intval($this->data['post_id']);
        $data['uid'] = $this->mid;
        if (D('information_digg')->where($data)->count()) {
            $this->error('已赞');
        }
        $data['ctime'] = time();
        if (D('information_digg')->add($data)) {
            $pubId = D('information_list')->where(array('id' => $data['post_id']))->find();
            //添加文章点赞信息
            model('UserData')->setUid($pubId['author'])->updateKey('information_digg', 1);
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 取消文章点赞数.
     *
     * @author zhangwei
     * @date   2016-09-26
     *
     * @return json
     */
    public function delPostDigg()
    {
        !$this->data['post_id'] && $this->error('参数错误');
        $map['post_id'] = intval($this->data['post_id']);
        $map['uid'] = $this->mid;
        if (D('information_digg')->where($map)->delete()) {
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 判断资源是否被举报.
     *
     * @author zhangwei
     * @date   2016-09-26
     *
     * @return json
     *              type = post aid = 文章id uid = 举报人
     */
    public function isDenounce()
    {
        $map['from'] = t($_REQUEST['type']);
        $map['aid'] = t($_REQUEST['aid']);
        $map['uid'] = $GLOBALS['ts']['mid'];
        $count = model('Denounce')->where($map)->count();
        $res = array();
        if ($count) {
            $this->success('该信息已被举报！');
        } else {
            $this->error('举报失败,请稍候再试');
        }
    }

    /**
     * 提交文章举报信息.
     *
     * @author zhangwei
     * @date   2016-09-26
     *
     * @return json
     */
    public function denounce()
    {
        $map['from'] = trim($this->data['from']);
        $map['aid'] = intval($this->data['aid']);
        $map['uid'] = $this->mid;
        $map['fuid'] = intval($this->data['fuid']);
        // 判断资源是否删除
        $fmap['isDel'] = 0;
        if ($this->data['from'] == 'information') {
            $fmap['id'] = intval($this->data['aid']);
            $isExist = D('information_list')->where($fmap)->count();
        }
        if ($isExist == 0) {
            $this->error('内容已被删除，举报失败');
        }
        $return = array();
        if ($isDenounce = model('Denounce')->where($map)->count()) {
            $return = array('status' => 0, 'msg' => L('PUBLIC_REPORTING_INFO'));
        } else {
            $map['content'] = h($this->data['content']);
            $map['reason'] = t($this->data['reason']);
            $map['source_url'] = str_replace(SITE_URL, '[SITE_URL]', t($this->data['source_url']));
            $map['ctime'] = time();
            if ($id = model('Denounce')->add($map)) {
                $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
                foreach ($touid as $k => $v) {
                    model('Notify')->sendNotify($v['uid'], 'denouce_audit');
                }
                $return = array('status' => 1, 'msg' => '您已经成功举报此信息');
            } else {
                $return = array('status' => 0, 'msg' => L('PUBLIC_REPORT_ERROR'));
            }
        }

        return $return;
    }

    //信息详情
    public function readInformation()
    {
        $id = intval($this->data['id']);
        $type = t($this->data['type']);
        if ($id) {
            $information = D('information_list')->where(array('id' => $id))->find();
            if ($information) {
                $information['authorname'] = getUserName($information['author']);
                $information['headimg'] = getUserFace($information['author'], 'm');
                $information['rtime'] = friendlyDate($information['rtime'], 'mohu');
                //获取点赞人数
                $information['likeCount'] = D('information_digg')->where(array('post_id' => $id))->count();
                //获取点赞人信息
                $information['likeInfo'] = $this->diggInfomation($id, '7', $type);
                //获取评论
                //$max_id = isset($this->data['max_id']) ? intval($this->data['max_id']) : 10;
                //$information['comment'] = $this->getCommentList($id, $max_id);
                $information['comment'] = $this->getCommentList($id);

                $information['commentNum'] = $this->_getComentNum($information['id']);
                //判断是否已经点赞
                $digg = D('information_digg')->where(array('uid' => $this->mid, 'post_id' => $id))->find();
                if ($digg) {
                    $information['isDigg'] = 1;
                } else {
                    $information['isDigg'] = 0;
                }
                //判断是否已经收藏
                $favorite = D('information_favorite')->where(array('uid' => $this->mid, 'post_id' => $id))->find();
                //收藏数量
                $information['favoriteNum'] = D('information_favorite')->where(array('post_id' => $id))->count();
                if ($favorite) {
                    $information['isFavorite'] = 1;
                } else {
                    $information['isFavorite'] = 0;
                }
                $informationData['data'] = $information;
                $informationData['status'] = 1;

                return $informationData;
            }

            return array('data' => '', 'status' => 0, 'msg' => '文章不存在');
        }

        return array('data' => '', 'status' => 0, 'msg' => '文章不存在');
    }

    /**
     * 点赞列表.
     *
     * @param string $type (文章点赞，贴子点赞)
     *
     * @return int 列表数据
     **/
    public function diggList()
    {
        $id = intval($this->data['id']);
        $type = t($this->data['type']);
        if ($id) {
            $max_id = $this->data['max_id'] ? $this->data['max_id'] : 0;
            $data = $this->diggInfomation($id, $max_id, $type);
            if ($data) {
                return array('data' => $data, 'status' => 1, 'msg' => '获取成功');
            } else {
                return array('data' => '', 'status' => 0, 'msg' => '信息不存在');
            }
        } else {
            return array('data' => '', 'status' => 0, 'msg' => '信息不存在');
        }
    }

    //评论列表
    public function getComment()
    {
        $id = $this->data['id'];
        if ($id) {
            $max_id = $this->data['max_id'];
            $data = $this->getCommentList($id, $max_id);

            return array('data' => $data, 'status' => 1, 'msg' => '获取成功');
        } else {
            return array('data' => '', 'status' => 0, 'msg' => '信息不存在');
        }
    }

    //获取评论列表
    protected function getCommentList($rowid)
    {
        $map['table'] = 'information_list';
        $map['is_del'] = 0;
        $map['row_id'] = $rowid;
        //$map['comment_id'] = array('lt',$max_id);
        $order = 'ctime desc';
        $isReply = false;
        $data = model('Comment')->where($map)->order($order)->limit(10)->select();
        if (!empty($data)) {
            $comment_list = array();
            foreach ($data as $k => $v) {
                if (!empty($v['to_comment_id']) && $isReply) {
                    $replyInfo = model('Comment')->getCommentInfo($v['to_comment_id'], false);
                    $comment['replyInfo'] = '//@{uid='.$replyInfo['user_info']['uid'].'|'.$replyInfo['user_info']['uname'].'}：'.$replyInfo['content'];
                }
                $userinfo = model('User')->getUserInfo($v['uid']);
                if ($userinfo) {
                    $comment['comment_id'] = $v['comment_id'];
                    $comment['type'] = $v['type'];
                    $comment['app'] = $v['app'];
                    $comment['table'] = $v['table'];
                    $comment['row_id'] = $v['row_id'];
                    $comment['app_uid'] = $v['app_uid'];
                    $comment['uid'] = $v['uid'];
                    $comment['to_comment_id'] = $v['to_comment_id'];
                    $comment['to_uid'] = $v['to_uid'];
                    $comment['data'] = $v['data'];
                    $comment['is_del'] = $v['is_del'];
                    $comment['ctime'] = friendlyDate($v['ctime'], 'mohu');
                    $comment['content'] = parse_html($v['content'].$v['replyInfo']);
                    $comment['content'] = formatEmoji(false, $v['content']); // 解析emoji
                    $comment['client_type'] = getFromClient($v['client_type'], $v['app']);
                    $comment['user_info']['uname'] = $userinfo['uname'];
                    $comment['user_info']['avatar'] = $userinfo['avatar_middle'];
                    $comment['user_info']['avatar_url'] = $userinfo['avatar_small'];
                    $comment['user_info']['space_url'] = $userinfo['space_url'];
                    $comment['user_info']['space_link'] = $userinfo['space_link'];
                    $comment['sourceInfo'] = model('Source')->getCommentSource($v);

                    array_push($comment_list, $comment);
                }
            }
            //更新文章评论信息
            $pubId = D('information_list')->where(array('id' => $rowid))->find();
            model('UserData')->setUid($pubId['author'])->updateKey('information_comment', 0);

            return $comment_list;
        } else {
            return array();
        }
    }
    //获取点赞人信息
    protected function diggInfomation($post_id, $max_id, $type)
    {
        if (in_array($type, array('information', 'weiba'))) {
            //更新点赞人信息
            $pubId = D('information_list')->where(array('id' => $post_id))->find();
            model('UserData')->setUid($pubId['author'])->updateKey('information_digg', 0);
            $likeInfo = array();
            if ($type == 'information') {
                $diggData = D('information_digg')->where(array('post_id' => $post_id))->limit(0, $max_id)->order('ctime desc')->select();
            } elseif ($type == 'weiba') {
                $diggData = D('weiba_post_digg')->where(array('post_id' => $post_id))->limit(0, $max_id)->order('ctime desc')->select();
            }
            if (!empty($diggData)) {
                $likeInfos = array();
                foreach ($diggData as $key => $val) {
                    $userInfo = model('User')->getUserInfo($val['uid']);
                    if ($userInfo) {
                        $likeInfo['userInfo']['uid'] = $userInfo['uid'];
                        $likeInfo['userInfo']['uname'] = $userInfo['uname'];
                        $likeInfo['userInfo']['avatar'] = $userInfo['avatar_middle'];
                        $likeInfo['userInfo']['avatar_url'] = $userInfo['avatar_small'];
                        $likeInfo['userInfo']['space_url'] = $userInfo['space_url'];
                        $likeInfo['userInfo']['space_link'] = $userInfo['space_link'];
                        $likeInfo['userInfo']['ctime'] = friendlyDate($val['ctime'], 'mohu');
                        array_push($likeInfos, $likeInfo);
                    }
                }

                return $likeInfos;
            } else {
                return array();
            }
        } else {
            return array();
        }
    }
    //我发布的文章
    public function myPublish()
    {
        $where = array();
        if (!$uid = $this->data['uid']) {
            $uid = $this->mid;
        }
        $where['author'] = $uid;
        $where['isDel'] = 0;
        $where['isPre'] = 0;
        if (isset($this->data['subject'])) {
            $where['subject'] = array('like', '%'.t($this->data['subject']).'%');
        }
        if ($this->data['max_id']) {
            $max_id = $this->data['max_id'];
            $where['id'] = array('lt', $max_id);
        }
        $newsModel = Subject::getInstance();
        $newsList = $newsModel->where($where)->field('id,cid,subject,abstract,author,ctime,hits,content')->order('id desc')->select();
        if (!empty($newsList)) {
            foreach ($newsList as &$subject) {
                $subject['url'] = sprintf('%s/api.php?mod=Information&act=readInformation&id=%d', SITE_URL, intval($subject['id']));
                preg_match_all('/\<img(.*?)src\=\"(.*?)\"(.*?)\/?\>/is', $subject['content'], $image);
                $image = $image[2];
                if ($image && is_array($image) && count($image) >= 1) {
                    $image = $image[0];
                    if (!preg_match('/https?\:\/\//is', $image)) {
                        $image = parse_url(SITE_URL, PHP_URL_SCHEME).'://'.parse_url(SITE_URL, PHP_URL_HOST).'/'.$image;
                    }
                }
                $subject['authorName'] = getUserName($subject['author']);
                $subject['ctimes'] = friendlyDate($subject['ctime'], 'mohu');
                $subject['commentNum'] = $this->_getComentNum($subject['id']);
                $subject['image'] = $image;
                $subject['author'] = getUserName($subject['author']);
                $subject['ctime'] = friendlyDate($subject['ctime'], 'mohu');
                $subject['commentNum'] = $this->_getComentNum($subject['id']);
                unset($subject['content']);
                unset($subject['author']);
                unset($subject['ctime']);
            }

            return array('status' => 1, 'msg' => '获取成功', 'data' => $newsList);
        } else {
            return array('status' => 0, 'msg' => '暂时没有资讯', 'data' => array());
        }
    }

    //我收藏的文章
    public function myFavorite()
    {
        //获取用户收藏的文章ID
        $uid = $this->mid;
        $map['uid'] = $uid;
        $max_id = $this->data['max_id'] ? intval($this->data['max_id']) : 0;
        $max_id && $map['id'] = array('lt', $max_id);
        $favorite = D('information_favorite')->where($map)->field('post_id')->order('id desc')->select();
        if ($favorite) {
            //根据post_id 查找文章
            $ids = getSubByKey($favorite, 'post_id');
            $where['id'] = array('in', $ids);
            if (isset($this->data['subject'])) {
                $where['subject'] = array('like', '%'.t($this->data['subject']).'%');
            }
            $news = D('information_list')->where($where)->select();
            if (!empty($news)) {
                foreach ($news as &$subject) {
                    $subject['url'] = sprintf('%s/api.php?mod=Information&act=readInformation&id=%d', SITE_URL, intval($subject['id']));
                    preg_match_all('/\<img(.*?)src\=\"(.*?)\"(.*?)\/?\>/is', $subject['content'], $image);
                    $image = $image[2];
                    if ($image && is_array($image) && count($image) >= 1) {
                        $image = $image[0];
                        if (!preg_match('/https?\:\/\//is', $image)) {
                            $image = parse_url(SITE_URL, PHP_URL_SCHEME).'://'.parse_url(SITE_URL, PHP_URL_HOST).'/'.$image;
                        }
                    }
                    $subject['authorName'] = getUserName($subject['author']);
                    $subject['ctimes'] = friendlyDate($subject['ctime'], 'mohu');
                    $subject['commentNum'] = $this->_getComentNum($subject['id']);
                    $subject['image'] = $image;
                    $subject['author'] = getUserName($subject['author']);
                    $subject['ctime'] = friendlyDate($subject['ctime'], 'mohu');
                    $subject['commentNum'] = $this->_getComentNum($subject['id']);
                    unset($subject['ctime']);
                    unset($subject['author']);
                    unset($subject['content']);
                }
                $favorite_count = D('information_favorite')->where(array('uid' => $uid))->count();
                $favorite_data = array('favorite_count' => $favorite_count, 'list' => $news);

                return array('status' => 1, 'msg' => '获取成功', 'favorite_count' => $favorite_count, 'data' => $favorite_data);
            } else {
                return array('status' => 0, 'msg' => '暂时没有资讯', 'data' => array());
            }
        } else {
            return array('status' => 0, 'msg' => '还没有文章收藏信息', 'data' => array());
        }
    }
}
