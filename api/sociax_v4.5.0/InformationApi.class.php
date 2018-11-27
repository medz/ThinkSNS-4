<?php

use Apps\Information\Model\Cate;
use Apps\Information\Model\Subject;
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
    public function NewsList()
    {
        $catid = intval($_REQUEST['cid']);
        $newsModel = Subject::getInstance();
        $catid && $map['cid'] = $catid;
        $map['isPre'] = 0;
        $map['isDel'] = 0;
        $this->data['max_id'] && $map['id'] = array('lt', $this->data['max_id']);
        $this->data['limit'] && $limit = $this->data['limit'] ? $this->data['limit'] : 10;
        $newsList = $newsModel->where($map)->field('id,cid,subject,abstract,author,ctime,hits,content,logo')->limit($limit)->order('id desc')->select();
        if (!empty($newsList)) {
            foreach ($newsList as &$subject) {
                // $subject['url'] = 'http://www.baidu.com';
                $subject['url'] = sprintf('%s/index.php?app=Information&mod=Index&act=info&id=%d', SITE_URL, intval($subject['id']));
                if ($subject['logo'] > 0) {
                    $subject['image'] = getImageUrlByAttachId($subject['logo'], '205', '160', true);
                } else {
                    preg_match_all('/\<img(.*?)src\=\"(.*?)\"(.*?)\/?\>/is', $subject['content'], $image);
                    $image = $image[2];
                    if ($image && is_array($image) && count($image) >= 1) {
                        $image = $image[0];
                        if (!preg_match('/https?\:\/\//is', $image)) {
                            $image = parse_url(SITE_URL, PHP_URL_SCHEME) . '://' . parse_url(SITE_URL, PHP_URL_HOST) . '/' . $image;
                        }
                    }
                    // $subject['commentNum'] = $this->_getComentNum($subject['id']);
                    $subject['image'] = $image;
                }

                unset($subject['content']);
            }
            $this->success(array('data' => $newsList));
        } else {
            $this->error('暂时没有资讯');
        }
    }

    /**
     * 咨询分类.
     *
     * @Author Wayne qiaobinloverabbi@gmail.com
     * @DateTime  2016-04-27T09:49:19+0800
     */
    public function NewsCate()
    {
        $cateModel = Cate::getInstance();
        $cates = $cateModel->where(['isDel' => 0])->order('rank asc')->select();
        if (!empty($cates)) {
            array_unshift($cates, array(
                'id' => 0,
                'name' => '全部',
                'isDel' => 0,
                'rank' => 0,
            ));
            $return['msg'] = '获取分类成功';
            $return['status'] = 1;
            $return['data'] = $cates;

            return $return;
        } else {
            $return['msg'] = '没有找到分类';
            $return['status'] = 0;
            $return['data'] = '';

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
     * 评论资讯 --using.
     *
     * @param
     *        	integer id 资讯ID
     * @param
     *        	integer to_comment_id 评论ID
     * @param
     *        	string content 评论内容
     * @param
     *        	integer from 来源(2-android 3-iPhone)
     *
     * @return array 状态+提示
     */
    public function addComment()
    {
        $return ['status'] = 0;
        $return ['msg'] = '评论失败';

        //检测用户是否被禁言
        if ($isDisabled = model('DisableUser')->isDisableUser($this->mid, 'post')) {
            return array(
                'status' => 0,
                'msg' => '您已经被禁言了',
            );
        }
        if (!t($this->data ['content'])) {
            $return ['msg'] = '评论内容不能为空';

            return $return;
        }
        /* 判断是否含有敏感词 */
        $content = sensitiveWord($this->data ['content']);
        if (!sensitiveWord($content)) {
            return array(
                'status' => -3,
                'msg' => '评论内容包含敏感词', // 评论内容包含敏感词
            );
        }
        if (!intval($this->data ['id'])) {
            $return ['msg'] = '请选择你要评论的资讯';

            return $return;
        }

        if (isSubmitLocked()) {
            $return ['msg'] = '发布内容过于频繁，请稍后再试！';

            return $return;
        }
        // 锁定发布
        lockSubmit();
        $id = intval($this->data['id']);
        $information = D('information_list')->where(array('id' => $id, 'isDel' => 0, 'isPre' => 0))->field('id, author')->find();
        if (empty($information)) {
            return array(
                'status' => 0,
                'msg' => '资讯不存在或已被删除',
            );
        }
        $content = t(preg_html($content));
        // 判断是否是回复
        $to_comment_id = intval($this->data['to_comment_id']);
        $to_uid = intval($this->data['to_uid']);
        if ($to_comment_id > 0) {
            $_to_uid = D('Comment')->where(array('comment_id' => $to_comment_id, 'is_del' => 0, 'is_audit' => 1))->field('uid')->find();
            if (!$_to_uid) {
                return array(
                    'status' => 0,
                    'msg' => '回复的评论不存在或已被删除',
                );
            }
            if ($_to_uid['uid'] != $to_uid) {
                return array(
                    'status' => 0,
                    'msg' => '参数错误',
                );
            }
        }

        // 获取接收数据
        $data['row_id'] = $id;
        $data['app'] = 'Information';
        $data['table'] = 'information_list';
        $data['content'] = $content;
        $data['app_uid'] = intval($information['author']);
        $data['to_comment_id'] = $to_comment_id;
        $data['to_uid'] = $to_uid;
        $data['uid'] = $this->mid;
        $data['ctime'] = time();
        $data['is_audit'] = '1';
        $data['client_type'] = getVisitorClient();
        // 解锁
        unlockSubmit();
        if ($comment_id = model('Comment')->addComment($data)) {
            $return ['cid'] = $comment_id;
        }
        $return ['status'] = 1;
        $return ['msg'] = '评论成功';

        return $return;
    }

    /**
     * 返回资讯详情h5
     * @return array|string
     */
    public function readerHtml($id){
        $id = $id ?: intval($_REQUEST['id']);
        $info = Model\InformationList::find($id);
        $info->increment('hits', 1);
        if (!$info) {
            return array(
                'status' => 0,
                'message' => '访问的资讯不存在！',
            );
        }

        /* # 解析表情 */
        $info->content = preg_replace_callback('/\[.+?\]/is', '_parse_expression', $info->content);

        /* # 替换公共变量 */
        $info->content = str_replace('__THEME__', THEME_PUBLIC_URL, $info->content);

        /* 解析emoji */
        $info->content = formatEmoji(false, $info->content);

        // 处理换行，临时解决方案
        $br = array("\r\n", "\n", "\r");
        $replace = '<br/>';
        $info->content = str_replace($br, $replace, $info->content);
        $info->content = str_replace('<img src="/ueditor/php/upload/image', '<img src="'.SITE_URL.'/ueditor/php/upload/image', $info->content);
        return '<style type="text/css">
* {box-sizing: border-box;margin: 0; padding: 0;}
.wrap {width: 100%; height: auto; padding: 20px 15px 15px; background-color: #fff;}
.wrap .title {margin-bottom: 10px; line-height: 1.4; font-weight: 400; font-size: 25px;}
.wrap .date {position: relative; width: 100%; margin-bottom: 18px; line-height: 20px; font-size: 12px; font-style: normal;  color: #999999;}
.wrap .date .right {  position: absolute; right: 0;}
.wrap .abstract { width: 100%; height: auto; margin-bottom: 18px; padding: 10px; background: #edeeef;}
.wrap .content { width: 100%; max-width: 100%; height: auto; overflow-x: hidden; color: #3e3e3e;}
.content img{max-width:100%!important;}
</style>
<div class="wrap"><h2 class="title">' .htmlspecialchars($info->subject, ENT_QUOTES, 'UTF-8').'</h2>
  <div class="date">' .date('Y-m-d', $info->rtime).'<span class="right">浏览：'.intval($info->hits).'</span></div>
  <div class="abstract"><strong>摘要&nbsp;</strong>' .htmlspecialchars($info->abstract, ENT_QUOTES, 'UTF-8').'</div>
  <div class="content">' .$info->content.'</div>
</div>
';//조회: 阅读数// background:url('.$index_ico_url.');
    }

    /**
     * 获取资讯详情.
     *
     * @return array
     */
    public function informationDetail()
    {
        $id = $this->data['id'] ? intval($this->data['id']) : $this->error('查询失败'); // 查询失败
        $count = $this->data['count'] ? intval($this->data['count']) : 10;
        // 判断 max_id 是否大于0，如果大于0 则只返评论信息
        if ($this->max_id < 1) {
            $subject = Subject::getInstance()->setId($id)->getSubject();
            $subject['content'] = (string) $this->readerHtml();
            $subject['url'] = U('Information/Index/info', array('id' => $id));
            $subject['comment_count'] = $this->_getComentNum($id);
            // 获取作者用户信息
            $subject['user_info'] = $this->get_user_info($subject['author']);
        }
        // 获取评论信息
        $subject['comment_info'] = $this->getCommentInfo($id, $count);

        $this->success(array('msg' => '查询成功', 'data' => $subject)); // 查询成功
    }

    /**
     * 获取用户信息 --using.
     *
     * @param
     *        	integer uid 用户UID
     *
     * @return array 用户信息
     */
    private function get_user_info($uid)
    {
        $user_info_whole = api('User')->get_user_info($uid);
        $user_info ['uid'] = $user_info_whole ['uid'];
        $user_info ['uname'] = $user_info_whole ['uname'];
        $user_info ['remark'] = $user_info_whole ['remark'];
        $user_info ['avatar'] ['avatar_middle'] = $user_info_whole ['avatar'] ['avatar_middle'];
        $user_info ['user_group'] = $user_info_whole ['user_group'];
        //个人空间隐私权限
        $privacy = model('UserPrivacy')->getPrivacy($this->mid, $uid);
        $user_info['space_privacy'] = $privacy['space'];
        $user_info['comment_weibo'] = $privacy['comment_weibo'];

        return $user_info;
    }

    /**
     * 获取资讯评论信息(有分页).
     *
     * @param int $id    资讯id
     * @param int $count 查询评论数量
     *
     * @return array
     */
    private function getCommentInfo($id, $count = 10)
    {
        $where = '`is_del` = 0 AND `app` = \'Information\' AND `table` = \'%s\' AND `row_id` = %d';
        $where = sprintf($where, 'information_list', intval($id));
        !empty($this->max_id) && $where .= " AND comment_id < {$this->max_id}";
        $list = model('Comment')->where($where)->limit($count)->field('comment_id, uid, content, to_comment_id, to_uid, ctime, digg_count')->order('comment_id DESC')->findAll();
        foreach ($list as $k => &$v) {
            $v['content'] = parse_remark($v ['content']);
            $v['content'] = formatEmoji(false, $v['content']);
            $v['user_info'] = $this->get_user_info($v ['uid']);
            // 用户隐私设置
            $privacy = model('UserPrivacy')->getPrivacy($this->mid, $v['uid']);
            $v['space_privacy'] = $privacy['space'];
            $v['comment_weibo'] = $privacy['comment_weibo'];
            unset($v['uid'], $v['to_comment_id']);
        }

        return $list ? $list : array();
    }

    /**
     * 删除资讯评论.
     *
     * @return array
     *
     * @author zsy
     */
    public function delComment()
    {
        $comment_id = intval($this->data['comment_id']);

        $res = model('Comment')->deleteComment([$comment_id], $this->mid);
        if (!$res) {
            return array('status' => 0, 'msg' => '删除失败');
        }

        return array('status' => 1, 'msg' => '删除成功');
    }
}
