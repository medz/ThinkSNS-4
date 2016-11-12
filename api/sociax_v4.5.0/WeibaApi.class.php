<?php
/**
 *
 * @author jason
 *
 */
class WeibaApi extends Api
{
    /**
     * 删除微吧帖子
     * 直接把PC删除的Action代码copy
     *
     * @request int post_id 帖子ID
     * @return bool
     * @author Seven Du <lovevipdsw@outlook.com>
     **/
    public function deletePost()
    {
        //$weiba = D('weiba_post')->where('post_id='.intval($_POST['post_id']))->field('weiba_id,post_uid')->find();
        $post_id = intval($this->data['post_id']);
        $info = D('weiba_post')->where(array(
            'post_id' => array(
                'eq', $post_id, ), ))->find();

        if (!$info || !isset($info['weiba_id'])) {
            return array(
                'status' => 0,
                'message' => '帖子不存在或者已经被删除！', );
        }
        $post_uid = $info['post_uid'];
        $weiba_id = $info['weiba_id'];

        if (CheckPermission('weiba_normal', 'weiba_del') ||
            $post_uid == $this->mid ||
            CheckWeibaPermission('', $weiba_id)) {
            if ($post_uid != $this->mid && CheckWeibaPermission('', $weiba_id)) {
                return array(
                    'status' => 0,
                    'message' => '你没有权限操作！', );
            }
        } else {
            return array(
                'status' => 0,
                'message' => '你没有权限操作！', );
        }

        if (!CheckWeibaPermission('', $weiba['weiba_id'])) {
            if (!CheckPermission('weiba_normal', 'weiba_del') || $post_uid != $this->mid) {
                return array(
                    'status' => 0,
                    'message' => '你没有权限操作！', );
            }
        }

        // D('weiba_post')->where('post_id='.$post_id)->setField('is_del',1)
        $status = D('weiba_post')->where(array(
            'post_id' => array(
                'eq', $post_id, ), ))->setField('is_del', 1);

        if ($status) {
            D('log')->writeLog($info['weiba_id'], $this->mid, '删除了帖子“'.$info['title'].'”', 'posts');

            // D('weiba')->where('weiba_id='.intval($_POST['weiba_id']))->setDec('thread_count');
            D('weiba')->where(array(
                'weiba_id' => array(
                    'eq', $weiba_id, ), ))->setDec('thread_count');

            //添加积分
            model('Credit')->setUserCredit($this->mid, 'delete_topic');

            // 删除相应的分享信息
            model('Feed')->doEditFeed($info['feed_id'], 'delFeed', '', $this->mid);

            /* 删除收藏 */
            D('WeibaPost')->where(array('post_id' => $post_id))->delete();

            return array(
                'status' => 1,
                'message' => '删除成功！', );
        }

        return array(
            'status' => 0,
            'message' => '删除失败！', );
    }

    /**
     * 获取帖子评论提醒
     *
     * @return int
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getRemind2PostReply()
    {
        $num = M('UserData')->getUserData($this->mid);
        $num = $num['unread_comment_weiba'];

        return intval($num);
    }

    /**
     * 获取当前用户未读的点赞列表
     *
     * @return int
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getUnreadDiggNum()
    {
        $num = M('UserData')->getUserData($this->mid);
        $num = $num['unread_digg_weibapost'];

        return intval($num);
    }

    /**
     * 获取当前用户帖子点赞消息列表
     *
     * @return array
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getDiggList()
    {
        /*
            SELECT b.* FROM `ts_weiba_post` as a right join ts_weiba_post_digg as b on b.post_id = a.post_id where a.post_uid = 1 order by cTime desc

            SELECT count(1) as count FROM `ts_weiba_post_digg` left join ts_weiba_post on ts_weiba_post.post_id = ts_weiba_post_digg.post_id where ts_weiba_post.post_uid = 1
         */
        $tn1 = D('weiba_post')->getTableName();
        $tn2 = D('weiba_post_digg')->getTableName();
        $list = D('weiba_post_digg')->where(sprintf('%s.post_uid = %d', $tn1, $this->mid))
                                    ->join(sprintf('left join %s on %s.post_id = %s.post_id', $tn1, $tn1, $tn2))
                                    ->field(sprintf('%s.*', $tn2))
                                    ->findPage(20);

        foreach ($list['data'] as $key => $value) {
            $value['user'] = model('User')->getUserInfo($value['uid']);
            $list['data'][$key] = $value;
        }
        model('UserData')->setKeyValue($this->mid, 'unread_digg_weibapost', 0);

        return $list;
    }

    /**
     * 举报一条微博 --using
     *
     * @param
     *          integer feed_id 微博ID
     * @param
     *          varchar reason 举报原因
     * @param
     *          integer from 来源(2-android 3-iphone)
     * @return array 状态+提示
     */
    public function denounce_weiba()
    {
        $post_id = intval($this->data ['post_id']);
        $post_uid = M('weiba_post')->where('is_del = 0 and post_id='.$post_id)->getField('post_uid');
        if (! $post_uid) {
            return array(
                    'status' => 0,
                    'msg' => '内容已被删除，举报失败',
            );
        }

        $data ['from'] = 'weiba_post';

        $data ['aid'] = $post_id;
        $data ['uid'] = $this->mid;
        $data ['fuid'] = $post_uid;
        if ($isDenounce = model('Denounce')->where($data)->count()) {
            return array(
                    'status' => 0,
                    'msg' => L('PUBLIC_REPORTING_INFO'),
            );
        } else {
            $data ['content'] = D('weiba_post')->where('post_id = '.$post_id)->getField('title');
            $data ['reason'] = t($this->data ['reason']);
            $data ['source_url'] = '[SITE_URL]/index.php?app=weiba&mod=Index&act=postDetail&post_id='.$post_id;
            $data ['ctime'] = time();
            if ($id = model('Denounce')->add($data)) {
                // 添加积分
                // model('Credit')->setUserCredit($this->mid, 'report_weiba_post');
                // model('Credit')->setUserCredit($post_uid, 'reported_weiba_post');

                $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
                foreach ($touid as $k => $v) {
                    model('Notify')->sendNotify($v ['uid'], 'denouce_audit');
                }

                return array(
                        'status' => 1,
                        'msg' => '举报成功',
                );
            } else {
                return array(
                        'status' => 0,
                        'msg' => L('PUBLIC_REPORT_ERROR'),
                );
            }
        }
    }

    /**
     * 帖子详情 --using
     *
     * @param
     *        	integer id 帖子ID
     * @return array 帖子信息
     */
    public function post_detail()
    {
        $this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
        $data = D('weiba_post')->where('post_id='.$this->id)->find();

        /* # 解析表情 */
        $data['content'] = preg_replace_callback('/\[.+?\]/is', '_parse_expression', $data['content']);

        /* # 替换公共变量 */
        $data['content'] = str_replace('__THEME__', THEME_PUBLIC_URL, $data['content']);

        /* 解析emoji */
        $data['content'] = formatEmoji(false, $data['content']);
        $data['title'] = formatEmoji(false, $data['title']);

        // 处理换行，临时解决方案
        $br = array("\r\n", "\n", "\r");
        $replace = '<br/>';
        $data['content'] = str_replace($br, $replace, $data['content']);

        $weiba_detail = D('weiba')->where('weiba_id='.$data ['weiba_id'])->find();
        $weiba_detail ['logo'] = getImageUrlByAttachId($weiba_detail ['logo'], 200, 200);

        $follow = M('weiba_follow')->where('follower_uid='.$this->user_id.' and weiba_id='.$data ['weiba_id'])->find();
        if ($follow) {
            $weiba_detail ['follow'] = 1;
        } else {
            $weiba_detail ['follow'] = 0;
        }

        $data ['weiba'] = $weiba_detail;
        $data ['user_info'] = $this->get_user_info($data ['post_uid']);
        if (empty($data ['from'])) {
            $data ['from'] = '来自网站';
        }
        if ($data ['from'] == 1) {
            $data ['from'] = '来自手机网页版';
        }
        if ($data ['from'] == 2) {
            $data ['from'] = '来自android';
        }
        if ($data ['from'] == 3) {
            $data ['from'] = '来自iphone';
        }
        if (D('weiba_favorite')->where('post_id='.$this->id.' AND uid='.$this->user_id)->find()) {
            $data ['is_favorite'] = 1;
        } else {
            $data ['is_favorite'] = 0;
        }
        $data ['digg_count'] = intval(M('weiba_post_digg')->where('post_id='.$data ['post_id'])->count());
        if ($data ['digg_count'] > 0) {
            $is_digg = M('weiba_post_digg')->where('post_id='.$data ['post_id'].' and uid='.$this->mid)->find();
            $data ['is_digg'] = $is_digg ? '1' : '0';

            $data ['digg_info'] = $this->weiba_post_digg($data ['post_id']);
        } else {
            $data ['is_digg'] = 0;
            $data ['digg_info'] = array();
        }
        // $data ['comment_info'] = $this->weiba_comments($data ['feed_id'], 10);
        $data ['comment_info'] = $this->weiba_comments($this->id, 10);
        /* 增加帖子阅读数 */
        D('weiba_post')->where('`post_id` = '.intval($this->id))->setInc('read_count');

        return $data;
    }
    public function digg_lists()
    {
        return $this->weiba_post_digg($this->data ['post_id'], 20);
    }

    /*
     * 微吧详情3G版链接地址
     */
    public function get_weiba_url()
    {
        $weiba_id = intval($this->id);
        $data ['url'] = U('w3g/Weiba/postDetail', array(
                'post_id' => $weiba_id,
        ));

        return $data;
    }

    /**
     * 圈子 --using
     *
     * @param
     *        	integer id 圈子ID
     * @return array 圈子信息
     */
    public function weiba_detail()
    {
        $this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
        $sfollow = D('weiba_follow')->where('follower_uid='.$this->user_id)->findAll();
        $sfollow = getSubByKey($sfollow, 'weiba_id');
        $sfollow = implode(',', $sfollow);
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        // $count = $this->count ? intval ( $this->count ) : 20;
        // !empty($max_id) && $map['weiba_id'] = array('lt', $max_id);
        if ($verify_arr [1]) {
            $map ['user_verified_category_id'] = intval($verify_arr [1]);
        }
        $map ['is_del'] = 0;
        $map ['status'] = 1;
        if (! empty($max_id)) {
            $map ['weiba_id'] = array(
                    array(
                            'exp',
                            '< '.$max_id,
                    ),
                    array(
                            'exp',
                            'in('.$sfollow.')',
                    ),
                    'and',
            );
        } else {
            $map ['weiba_id'] = array(
                    'exp',
                    'in('.$sfollow.')',
            );
        }
        $var = M('weiba')->where($map)->order('weiba_id desc')->findAll();

        // dump(M()->getLastSql());
        if ($var) {
            foreach ($var as $k => $v) {
                $var [$k] ['logo'] = getImageUrlByAttachId($v ['logo'], 200, 200);
                if ($v ['new_day'] != date('Y-m-d', time())) {
                    $var [$k] ['new_count'] = 0;
                    $this->setNewcount($v ['weiba_id'], 0);
                }
            }
        }
        $weiba_recommend = $this->_weiba_recommend(4, 200, 200);
        $res ['my'] = (array) $var;
        $res ['recommend'] = (array) $weiba_recommend;

        return $res;
    }

    public function _post_list($list)
    {
        foreach ($list as $k => $v) {
            if (empty($v ['from'])) {
                $list [$k] ['from'] = '来自网站';
            }
            if ($v ['from'] == 1) {
                $list [$k] ['from'] = '来自手机网页版';
            }
            if ($v ['from'] == 2) {
                $list [$k] ['from'] = '来自android';
            }
            if ($v ['from'] == 3) {
                $list [$k] ['from'] = '来自iphone';
            }
            $list [$k] ['user_info'] = $this->get_user_info($v ['post_uid']);
            // 匹配图片的src
            preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v ['content'], $match);
            preg_match_all('#<img.*?_src="([^"]*)"[^>]*>#i', $v ['content'], $match_src);
            if (count($match [1]) > 0) {
                foreach ($match [1] as $key => $imgurl) {
                    $imgurl = $imgurl;
                    if (! empty($imgurl)) {
                        $list [$k] ['img'] [$key]['small'] = $imgurl;
                        $list [$k] ['img'] [$key]['big'] = $match_src[1][$key] ? $match_src[1][$key] : null;
                    }
                }
            } else {
                $list [$k] ['img'] = array();
            }
            $is_digg = M('weiba_post_digg')->where('post_id='.$v ['post_id'].' and uid='.$this->mid)->find();
            $list [$k] ['digg'] = $is_digg ? 'digg' : 'undigg';
            $list [$k] ['content'] = t($list [$k] ['content']);
            /* # 解析emoji */
            $list[$k]['content'] = formatEmoji(false, $list[$k]['content']);
            $list[$k]['title'] = formatEmoji(false, $list[$k]['title']);
        }

        return $list;
    }
    /**
     * 圈子 --using
     *
     * @param
     *        	integer id 圈子ID
     * @return array 圈子信息
     */
    public function detail()
    {
        $this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
        $weiba_id = $_REQUEST ['weiba_id'];
        $type = $_REQUEST ['type'];
        $weiba_detail = D('weiba')->where('is_del=0 and status=1 and weiba_id='.$weiba_id)->find();
        $weiba_detail ['logo'] = getImageUrlByAttachId($weiba_detail ['logo'], 200, 200);
        if (! $weiba_detail) {
            $data ['error'] = '该圈子还未被审核或已被解散';
        } else {
            $follow = M('weiba_follow')->where('follower_uid='.$this->user_id.' and weiba_id='.$weiba_id)->find();
            $maps ['is_del'] = 0;
            $maps ['weiba_id'] = $weiba_id;

            if ($follow) {
                $order = 'post_id desc';
                $max_id = $this->max_id ? intval($this->max_id) : 0;
                $count = $this->count ? intval($this->count) : 20;
                ! empty($max_id) && $maps ['post_id'] = array(
                        'lt',
                        $max_id,
                );

                $data ['follow'] = 1;
            } else {
                $order = 'reply_count desc';
                $count = 5;

                $data ['follow'] = 0;
            }
            $list = D('weiba_post')->where($maps)->order($order)->limit($count)->findAll();
            // dump(M()->getLastSql());
            // dump($list);

            $mapc ['top'] = array(
                    'in',
                    array(
                            1,
                            2,
                    ),
            );
            $mapc ['is_del'] = 0;
            $mapc ['weiba_id'] = $weiba_id;
            $top = D('weiba_post')->where($mapc)->order('post_time desc')->limit(2)->findAll();
            // dump(D ( 'weiba_post' )->getLastSql());
            if (! $top) {
                $top = array();
            }
            $mapz ['digest'] = 1;
            $mapz ['is_del'] = 0;
            $mapz ['weiba_id'] = $weiba_id;
            $digest = D('weiba_post')->where($mapz)->count();
            if ($list) {
                $list = $this->_post_list($list);
            } else {
                $list = array();
            }
            // dump(M()->getLastSql());
            $data ['weiba_info'] = $weiba_detail;
            $data ['weiba_post'] = $list;
            $data ['weiba_digest'] = array(
                    $digest,
            );
            $data ['weiba_top'] = $top;
        }

        return $data;
    }
    public function detail_digest()
    {
        $this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
        $weiba_id = $_REQUEST ['weiba_id'];
        $type = $_REQUEST ['type'];
        $weiba_detail = D('weiba')->where('is_del=0 and status=1 and weiba_id='.$weiba_id)->find();

        if (! $weiba_detail) {
            $data ['error'] = '该圈子还未被审核或已被解散';
        } else {
            // 精华帖
            $maps ['digest'] = 1;
            $maps ['is_del'] = 0;
            $maps ['weiba_id'] = $weiba_id;
            $order = 'post_id desc';
            $max_id = $this->max_id ? intval($this->max_id) : 0;
            $count = $this->count ? intval($this->count) : 20;
            ! empty($max_id) && $maps ['post_id'] = array(
                    'lt',
                    $max_id,
            );
            // dump($maps);
            $list = D('weiba_post')->where($maps)->order($order)->limit($count)->findAll();
            // dump(M()->getLastSql());
            if ($list) {
                $list = $this->_post_list($list);
            } else {
                $list = array();
            }
        }

        return $list;
    }
    public function setNewcount($weiba_id, $num = 1)
    {
        $map ['weiba_id'] = $weiba_id;
        $time = time();
        $weiba = D('weiba')->where($map)->find();
        if ($weiba ['new_day'] != date('Y-m-d', $time)) {
            M('weiba')->where($map)->setField('new_day', date('Y-m-d', $time));
            M('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num == 0) {
            M('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num > 0) {
            M('weiba')->where($map)->setField('new_count', (int) $num + (int) $weiba ['new_count']);
        }

        return true;
    }
    public function findWeiba()
    {
        $limit = intval($_REQUEST ['limit']) ? intval($_REQUEST ['limit']) : 4;
        $weiba = $this->_weiba_recommend($limit, 200, 200);
        $map ['is_del'] = 0;
        $map ['status'] = 1;
        if ($_REQUEST ['key'] != '') {
            $map ['weiba_name'] = array(
                    'like',
                    '%'.$_REQUEST ['key'].'%',
            );
        }
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        if (! empty($max_id)) {
            $map ['weiba_id'] = array(
                    'exp',
                    '< '.$max_id,
            );
        }
        $var = M('weiba')->where($map)->order('weiba_id desc')->limit($count)->findAll();
        if ($var) {
            foreach ($var as $k => $v) {
                $var [$k] ['logo'] = getImageUrlByAttachId($v ['logo'], 200, 200);
                if ($v ['new_day'] != date('Y-m-d', time())) {
                    $var [$k] ['new_count'] = 0;
                    $this->setNewcount($v ['weiba_id'], 0);
                }
                /* 解析emoji */
                $var[$k]['title'] = formatEmoji(false, $var[$k]['title']);
                $var[$k]['content'] = formatEmoji(false, $var[$k]['content']);
            }
        } else {
            $var = array();
        }

        return array(
                $weiba,
                $var,
        );
    }

    /**
     * 我创建的圈子
     */
    public function weiba_creat_my()
    {
        $map ['is_del'] = 0;
        $map ['status'] = 1;
        $map ['uid'] = empty($this->user_id) ? $this->mid : $this->user_id;
        $limit = intval($_REQUEST ['limit']) ? intval($_REQUEST ['limit']) : 4;
        $var = M('weiba')->where($map)->order('weiba_id desc')->limit($limit)->findAll();
        if ($var) {
            foreach ($var as $k => $v) {
                $var [$k] ['logo'] = getImageUrlByAttachId($v ['logo'], 200, 200);
                if ($v ['new_day'] != date('Y-m-d', time())) {
                    $var [$k] ['new_count'] = 0;
                    $this->setNewcount($v ['weiba_id'], 0);
                }
                /* 解析emoji */
                $var[$k]['title'] = formatEmoji(false, $var[$k]['title']);
                $var[$k]['content'] = formatEmoji(false, $var[$k]['content']);
            }
        } else {
            $var = array();
        }

        return $var;
    }

    /**
     * 我加入的圈子
     */
    public function weiba_join_my()
    {
        $uid = empty($this->user_id) ? $this->mid : $this->user_id;
        $sfollow = D('weiba_follow')->where('follower_uid='.$uid)->findAll();
        if ($sfollow) {
            $sfollow = getSubByKey($sfollow, 'weiba_id');
            $sfollow = implode(',', $sfollow);
        }
        $map ['is_del'] = 0;
        $map ['status'] = 1;
        $map ['uid'] = array(
                'neq',
                $uid,
        );
        $max_id = $this->max_id ? intval($this->max_id) : 0;
        $count = $this->count ? intval($this->count) : 20;
        if (! empty($max_id)) {
            $map ['weiba_id'] = array(
                    array(
                            'exp',
                            '< '.$max_id,
                    ),
                    array(
                            'exp',
                            'in('.$sfollow.')',
                    ),
                    'and',
            );
        } else {
            $map ['weiba_id'] = array(
                    'exp',
                    'in('.$sfollow.')',
            );
        }
        $var = M('weiba')->where($map)->order('weiba_id desc')->limit($count)->findAll();
        if ($var) {
            foreach ($var as $k => $v) {
                $var [$k] ['logo'] = getImageUrlByAttachId($v ['logo'], 200, 200);
                if ($v ['new_day'] != date('Y-m-d', time())) {
                    $var [$k] ['new_count'] = 0;
                    $this->setNewcount($v ['weiba_id'], 0);
                }
                /* 解析emoji */
                $var[$k]['title'] = formatEmoji(false, $var[$k]['title']);
                $var[$k]['content'] = formatEmoji(false, $var[$k]['content']);
            }
        } else {
            $var = array();
        }

        return $var;
    }

    /**
     * 推荐圈子 推荐人员
     */
    public function recommends($limit = 8, $width = 200, $height = 200)
    {
        $man = model('RelatedUser')->getRelatedUser(8);
        $weiba = $this->_weiba_recommend(8, 200, 200);

        return array(
                $weiba,
                $man,
        );
    }

    /**
     * 关注圈子
     *
     * @param
     *        	integer uid 用户UID
     * @param
     *        	integer weiba_id 圈子ID
     * @return int 新添加的数据ID
     */
    public function doFollowWeiba()
    {
        $data ['weiba_id'] = intval($_REQUEST ['weiba_id']);
        $data ['follower_uid'] = empty($this->user_id) ? $this->mid : $this->user_id;
        if (M('weiba_follow')->where($data)->find()) {
            $nres ['status'] = 0;
            $nres ['msg'] = '您已关注该圈子';

            return $nres;
        } else {
            $res = M('weiba_follow')->add($data);
            if ($res) {
                M('weiba')->where('weiba_id='.$data['weiba_id'])->setInc('follower_count');

                // 添加积分
                model('Credit')->setUserCredit($data['follower_uid'], 'follow_weiba');
                $nres ['status'] = 1;
                $nres ['msg'] = '关注成功';

                return $nres;
            } else {
                $nres ['status'] = 0;
                $nres ['msg'] = '关注失败';

                return $nres;
            }
        }
    }

    /**
     * 取消关注圈子
     *
     * @param
     *        	integer uid 用户UID
     * @param
     *        	integer weiba_id 圈子ID
     * @return int 新添加的数据ID
     */
    public function unFollowWeiba()
    {
        $data ['weiba_id'] = intval($_REQUEST ['weiba_id']);
        $data ['follower_uid'] = empty($this->user_id) ? $this->mid : $this->user_id;
        if (M('weiba_follow')->where($data)->find()) {
            $res = D('weiba_follow')->where($data)->delete();
            if ($res) {
                M('weiba')->where('weiba_id='.$weiba_id)->setDec('follower_count');
                M('weiba_apply')->where($data)->delete();

                // 添加积分
                model('Credit')->setUserCredit($uid, 'unfollow_weiba');
                $nres ['status'] = 1;
                $nres ['msg'] = '取消关注成功';

                return $nres;
            } else {
                $nres ['status'] = 0;
                $nres ['msg'] = '取消关注失败';

                return $nres;
            }
        } else {
            $nres ['status'] = 0;
            $nres ['msg'] = '您尚未关注该圈子';

            return $nres;
        }
    }

    // 赞帖子
    public function addPostDigg()
    {
        $maps ['post_id'] = $map ['post_id'] = intval($_REQUEST ['row_id']);
        $map ['uid'] = empty($this->user_id) ? $this->mid : $this->user_id;
        $hasdigg = M('weiba_post_digg')->where($map)->find();
        $map ['cTime'] = time();
        if (! $hasdigg) {
            $result = M('weiba_post_digg')->add($map);
            if ($result) {
                $post = M('weiba_post')->where($maps)->find();
                M('weiba_post')->where($maps)->setField('praise', $post ['praise'] + 1);
                $res ['status'] = 1;
                $res ['info'] = '赞成功';
            } else {
                $res ['status'] = 0;
                $res ['info'] = '赞失败';
            }
        } else {
            $res ['status'] = 0;
            $res ['info'] = '您以赞过';
        }

        return $res;
    }
    // 取消赞帖子
    public function delPostDigg()
    {
        $maps ['post_id'] = $map ['post_id'] = intval($_REQUEST ['row_id']);
        $map ['uid'] = empty($this->user_id) ? $this->mid : $this->user_id;
        $hasdigg = M('weiba_post_digg')->where($map)->find();
        if ($hasdigg) {
            if (M('weiba_post_digg')->where($map)->delete()) {
                $post = M('weiba_post')->where($maps)->find();
                M('weiba_post')->where($maps)->setField('praise', $post ['praise'] - 1);
                $res ['status'] = 1;
                $res ['info'] = '取消赞成功';
            } else {
                $res ['status'] = 0;
                $res ['info'] = '取消赞失败';
            }
        } else {
            $res ['status'] = 0;
            $res ['info'] = '您还没赞过';
        }

        return $res;
    }

    /**
     * 收藏帖子
     *
     */
    public function favorite()
    {
        $data ['post_id'] = intval($_REQUEST ['post_id']);
        $data ['weiba_id'] = intval($_REQUEST ['weiba_id']);
        $data ['post_uid'] = intval($_REQUEST ['post_uid']);
        $data ['uid'] = empty($this->user_id) ? $this->mid : $this->user_id;
        $resault = M('weiba_favorite')->where($data)->find();
        $data ['favorite_time'] = time();
        if (! $resault) {
            if (M('weiba_favorite')->add($data)) {
                D('UserData')->updateKey('collect_topic_count', 1);
                D('UserData')->updateKey('collect_total_count', 1);

                // 添加积分
                model('Credit')->setUserCredit($data ['uid'], 'collect_topic');
                model('Credit')->setUserCredit($data ['post_uid'], 'collected_topic');

                $res ['status'] = 1;
                $res ['msg'] = '收藏成功';
            } else {
                $res ['status'] = 0;
                $res ['msg'] = '收藏失败';
            }
        } else {
            $res ['status'] = 0;
            $res ['msg'] = '您已经收藏过';
        }

        return $res;
    }

    /**
     * 取消收藏帖子
     *
     */
    public function unfavorite()
    {
        $map ['post_id'] = intval($_REQUEST ['post_id']);
        $map ['uid'] = empty($this->user_id) ? $this->mid : $this->user_id;
        $resault = M('weiba_favorite')->where($map)->find();
        if ($resault) {
            if (M('weiba_favorite')->where($map)->delete()) {
                D('UserData')->updateKey('collect_topic_count', - 1);
                D('UserData')->updateKey('collect_total_count', - 1);
                $res ['status'] = 1;
                $res ['msg'] = '取消收藏成功';
            } else {
                $res ['status'] = 0;
                $res ['msg'] = '取消收藏失败';
            }
        } else {
            $res ['status'] = 0;
            $res ['msg'] = '你还没有收藏';
        }

        return $res;
    }

    /**
     * 获取用户收藏的帖子列表
     *
     * @request int $max_id 上次返回的最大值 默认值是0
     * @request int $count 每次获取的条数，默认值是20
     * @request int $uid 需要获取的用户id，默认为当前登录用户
     * @return array
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function getUserFavorite()
    {
        /* # 上次返回的条数id */
        $this->max_id or
        $this->max_id = 0;

        /* # 处理每次获取的条数 */
        $this->count or
        $this->count = 20;

        /* # 用户uid */
        $this->uid or
        $this->uid = $this->mid;

        /* 封装条件 */
        $where = array('uid' => array('eq', $this->uid));
        $this->max_id > 0 and
        $where['id'] = array('lt', $this->max_id);

        /* 获取资源id */
        $ids = D('weiba_favorite')->where($where)->order('`id` DESC')->limit($this->count)->field('`id`,`post_id`')->select();

        /* # 获取下次访问的起始ID */
        $this->max_id = array_pop($ids);
        array_push($ids, $this->max_id);
        $this->max_id = $this->max_id['id'];

        /* # 过滤ID */
        $ids = getSubByKey($ids, 'post_id');

        /* # 获取所有资源 */
        $ids = D('weiba_post')->where(array('post_id' => array('IN', $ids)))->order('find_in_set(post_id,\''.implode(',', $ids).'\')')->select();

        return array(
            'max_id' => $this->max_id,
            'data' => $this->_post_list($ids),
        );
    }

    /**
     * 圈子推荐
     *
     * @param
     *        	integer limit 获取圈子条数
     */
    public function _weiba_recommend($limit = 8, $width = 200, $height = 200)
    {
        $this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
        $map ['recommend'] = 1;
        $map ['status'] = 1;
        $map ['is_del'] = 0;
        $follows = M('weiba_follow')->where('follower_uid ='.$this->user_id)->findAll();
        if ($follows) {
            $weiba_ids = getSubByKey($follows, 'weiba_id');
            $map ['weiba_id'] = array(
                    'not in',
                    $weiba_ids,
            );
        }
        $weiba_recommend = D('weiba')->where($map)->limit($limit)->order('rand()')->select();
        $weiba_id = getSubByKey($weiba_recommend, 'weiba_id');
        $followStatus = $this->getFollowStateByWeibaids($this->user_id, $weiba_id);
        foreach ($weiba_recommend as $k => $v) {
            $weiba_recommend [$k] ['logo'] = getImageUrlByAttachId($v ['logo'], $width, $height);
            $weiba_recommend [$k] ['following'] = $followStatus [$v ['weiba_id']] ['following'];
            if ($v ['new_day'] != date('Y-m-d', time())) {
                $weiba_recommend [$k] ['new_count'] = 0;
                $this->setNewcount($v ['weiba_id'], 0);
            }
            /* 解析emoji */
            $weiba_recommend[$k]['title'] = formatEmoji(false, $weiba_recommend[$k]['title']);
            $weiba_recommend[$k]['content'] = formatEmoji(false, $weiba_recommend[$k]['content']);
        }

        return $weiba_recommend;
    }

    /**
     * 批量获取圈子关注状态
     *
     * @param
     *        	integer uid 用户UID
     * @param
     *        	array weiba_ids 圈子ID
     * @return [type] [description]
     */
    public function getFollowStateByWeibaids($uid, $weiba_ids)
    {
        $_weibaids = is_array($weiba_ids) ? implode(',', $weiba_ids) : $weiba_ids;
        if (empty($_weibaids)) {
            return array();
        }
        $follow_data = M('weiba_follow')->where(" ( follower_uid = '{$uid}' AND weiba_id IN({$_weibaids}) ) ")->findAll();
        $follow_states = $this->_formatFollowState($uid, $weiba_ids, $follow_data);

        return $follow_states [$uid];
    }

    /**
     * 格式化，用户的关注数据
     *
     * @param  int   $uid
     *                            用户ID
     * @param  array $fids
     *                            用户ID数组
     * @param  array $follow_data
     *                            关注状态数据
     * @return array 格式化后的用户关注状态数据
     */
    public function _formatFollowState($uid, $weiba_ids, $follow_data)
    {
        ! is_array($weiba_ids) && $weiba_ids = explode(',', $weiba_ids);
        foreach ($weiba_ids as $weiba_id) {
            $follow_states [$uid] [$weiba_id] = array(
                    'following' => 0,
            );
        }
        foreach ($follow_data as $r_v) {
            if ($r_v ['follower_uid'] == $uid) {
                $follow_states [$r_v ['follower_uid']] [$r_v ['weiba_id']] ['following'] = 1;
            }
        }

        return $follow_states;
    }
    /**
     * 获取指定分享的评论列表 --using
     *
     * @param
     *        	integer feed_id 分享ID // 修改为 帖子post_id
     * @param
     *        	integer max_id 上次返回的最后一条评论ID
     * @param
     *        	integer count 评论条数
     * @return array 评论列表
     */
    public function weiba_comments($feed_id, $count)
    {
        if (! $feed_id) {
            // $feed_id = $this->data ['feed_id'];
            $feed_id = M('weiba_post')->where(array('feed_id' => $this->data['feed_id']))->getField('post_id');
        }
        $comment_list = array();
        // $where = 'is_del=0 and row_id=' . $feed_id;
        $where = 'is_del=0 and post_id='.$feed_id;
        if (! $count) {
            $count = $this->count;
            // ! empty($this->max_id) && $where .= " AND comment_id < {$this->max_id}";
            ! empty($this->max_id) && $where .= " AND reply_id < {$this->max_id}";
        }
        $floor = '';
        if ($this->max_id) {
            // $floor = M('comment')->where('is_del=0 and row_id=' . $feed_id . ' and comment_id <' . $this->max_id)->count();
            $floor = M('weiba_reply')->where(array('is_del' => 0, 'post_id' => $feed_id, 'reply_id' => array('gt', $this->max_id)))->count();
        } else {
            // $floor = M('comment')->where('is_del=0 and row_id=' . $feed_id)->count();
            $floor = M('weiba_reply')->where(array('is_del' => 0, 'post_id' => $feed_id))->count();
        }
        // $comments = model('Comment')->where($where)->order('comment_id DESC')->limit($count)->findAll();
        $comments = M('weiba_reply')->where($where)->order('reply_id DESC')->limit($count)->findAll();
        foreach ($comments as $v) {
            // switch ($v ['type']) {
            //     case '2' :
            //         $type = '转发了此贴';
            //         break;
            //     case '3' :
            //         $type = '分享了此贴';
            //         break;
            //     case '4' :
            //         $type = '赞了此贴';
            //         break;
            //     default :
            //         $type = '评论了此贴';
            //         break;
            // }
            // $comment_info ['type'] = $type;
            $comment_info ['type'] = '评论了此贴';
            $comment_info ['floor'] = $floor;
            $floor --;
            $comment_info ['user_info'] = $this->get_user_info($v ['uid']);
            // $comment_info ['comment_id'] = $v ['comment_id'];
            $comment_info ['comment_id'] = $v['reply_id'];
            $comment_info ['content'] = $v ['content'];
            /* # 解析出emoji' */
            $comment_info['content'] = formatEmoji(false, $comment_info['content']);
            $comment_info ['ctime'] = $v ['ctime'];
            $comment_info ['digg_count'] = $v ['digg_count'];
            // $diggarr = model('CommentDigg')->checkIsDigg($v ['comment_id'], $GLOBALS ['ts'] ['mid']);
            $diggarr = D('WeibaReplyDigg', 'weiba')->checkIsDigg($v ['reply_id'], $GLOBALS ['ts'] ['mid']);
            $comment_info ['is_digg'] = t($diggarr [$v ['reply_id']] ? 1 : 0);
            $comment_list [] = $comment_info;
        }

        return $comment_list;
    }
    /**
     * 获取指定帖子的赞过的人的列表 --using
     *
     * @param
     *        	integer feed_id 分享ID
     * @param
     *        	integer max_id 上次返回的最后一条赞的ID
     * @param
     *        	integer count 数量
     * @return array 点赞的用户列表
     */
    public function weiba_post_digg($feed_id, $count = 10)
    {
        if (! $feed_id) {
            $feed_id = $this->data ['feed_id'];
        }
        $this->user_id = empty($this->user_id) ? $this->mid : $this->user_id;
        $where = 'post_id='.$feed_id;
        ! empty($this->max_id) && $where .= " AND id < {$this->max_id}";
        $digg_list = M('weiba_post_digg')->where($where)->order('cTime DESC')->limit($count)->findAll();
        if (! $digg_list) {
            return array();
        }
        $follow_status = model('Follow')->getFollowStateByFids($this->user_id, getSubByKey($digg_list, 'uid'));
        foreach ($digg_list as $k => $v) {
            $user_info = api('User')->get_user_info($v ['uid']);
            $digg_list [$k] ['uname'] = $user_info ['uname'];
            $digg_list [$k] ['remark'] = $user_info ['remark'];
            $digg_list [$k] ['intro'] = $user_info ['intro'] ;
            $digg_list [$k] ['avatar'] = $user_info ['avatar'] ['avatar_middle'];
            $digg_list [$k] ['follow_status'] = $follow_status [$v ['uid']];
            unset($digg_list [$k] ['post_id']);
        }

        return $digg_list;
    }
    /**
     * 获取用户信息 --using
     *
     * @param
     *        	integer uid 用户UID
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

        return $user_info;
    }
    public function recommend_topic()
    {
        // 推荐帖子
        $map ['recommend'] = 1;
        $map ['is_del'] = 0;
        $list = M('weiba_post')->where($map)->order('recommend_time desc')->limit(2)->findAll();
        $list = $this->_post_list($list);
        $res ['commend'] = (array) $list;
        unset($list);

        // 关注的帖子
        $wmap ['follower_uid'] = $this->mid;
        $weibas = M('weiba_follow')->where($wmap)->field('weiba_id')->findAll();
        if (! empty($weibas)) {
            $pmap ['weiba_id'] = array(
                    'in',
                    getSubByKey($weibas, 'weiba_id'),
            );
            empty($this->max_id) || $pmap ['post_id'] = array(
                    'lt',
                    $this->max_id,
            );
            $pmap ['is_del'] = 0;
            $list = M('weiba_post')->where($pmap)->order('post_time desc')->limit(20)->findAll();
            $list = $this->_post_list($list);
        }
        $res ['my'] = (array) $list;

        return (array) $res;
    }
    public function search_topic()
    {
        $map ['is_del'] = 0;
        $map ['title'] = array(
                'like',
                '%'.t($this->data ['key']).'%',
        );
        if (! empty($this->data ['weiba_id'])) {
            $map ['weiba_id'] = intval($this->data ['weiba_id']);
        }
        empty($this->max_id) || $map ['post_id'] = array(
                'lt',
                $this->max_id,
        );

        $list = M('weiba_post')->where($map)->order('post_time desc')->limit(20)->findAll();

        return (array) $this->_post_list($list);
    }
    public function recommend_all()
    {
        // 推荐帖子
        $map ['recommend'] = 1;
        $map ['is_del'] = 0;
        empty($this->max_id) || $map ['post_id'] = array(
                'lt',
                $this->max_id,
        );
        empty($this->data ['weiba_id']) || $map ['weiba_id'] = $this->data ['weiba_id'];
        $list = M('weiba_post')->where($map)->order('post_time desc')->limit(20)->findAll();
        $list = $this->_post_list($list);

        return (array) $list;
    }
    public function post_all()
    {
        // 推荐帖子
        $map ['is_del'] = 0;
        empty($this->max_id) || $map ['post_id'] = array(
                'lt',
                $this->max_id,
        );
        empty($this->data ['weiba_id']) || $map ['weiba_id'] = $this->data ['weiba_id'];
        $list = M('weiba_post')->where($map)->order('post_time desc')->limit(20)->findAll();
        $list = $this->_post_list($list);

        return (array) $list;
    }
    public function post_one()
    {
        // 推荐帖子
        $map ['is_del'] = 0;
        $map ['post_id'] = intval($this->data ['post_id']);
        $list = M('weiba_post')->where($map)->findAll();
        $list = $this->_post_list($list);

        return (array) $list;
    }
    public function digest_all()
    {
        // 推荐帖子
        $map ['digest'] = 1;
        $map ['is_del'] = 0;
        empty($this->max_id) || $map ['post_id'] = array(
                'lt',
                $this->max_id,
        );
        empty($this->data ['weiba_id']) || $map ['weiba_id'] = $this->data ['weiba_id'];
        $list = M('weiba_post')->where($map)->order('post_time desc')->limit(20)->findAll();
        $list = $this->_post_list($list);

        return (array) $list;
    }
    public function all_wieba()
    {
        empty($this->max_id) || $map ['weiba_id'] = array(
                'lt',
                $this->max_id,
        );
        $map['is_del'] = 0;
        $map['status'] = 1;
        $list = M('weiba')->where($map)->order('weiba_id desc')->limit(20)->findAll();

        $weiba_id = getSubByKey($list, 'weiba_id');
        $uid = $this->user_id ? $this->user_id : $this->uid ? $this->uid : $this->mid;
        $followStatus = $this->getFollowStateByWeibaids($uid, $weiba_id);
        foreach ($list as $k => $v) {
            $list [$k] ['logo'] = getImageUrlByAttachId($v ['logo'], 200, 200);
            $list [$k] ['following'] = $followStatus [$v ['weiba_id']] ['following'];
            if ($v ['new_day'] != date('Y-m-d', time())) {
                $list [$k] ['new_count'] = 0;
                $this->setNewcount($v ['weiba_id'], 0);
            }
            $list[$k]['title'] = formatEmoji(false, $list[$k]['title']);
            $list[$k]['content'] = formatEmoji(false, $list[$k]['content']);
        }
        // dump(M ( 'weiba' )->getLastSql());
        return (array) $list;
    }
    /**
     * 评论帖子 --using
     *
     * @param
     *        	integer post_id 帖子ID
     * @param
     *        	integer to_comment_id 评论ID
     * @param
     *        	string content 评论内容
     * @param
     *        	integer from 来源(2-android 3-iPhone)
     * @return array 状态+提示
     */
    public function comment_post()
    {
        $return ['status'] = 0;
        $return ['msg'] = '发布失败';

        //检测用户是否被禁言
        if ($isDisabled = model('DisableUser')->isDisableUser($this->mid, 'post')) {
            return array(
                'status' => 0,
                'msg' => '您已经被禁言了',
            );
        }
        if (! t($this->data ['content'])) {
            $return ['msg'] = '评论内容不能为空';

            return $return;
        }
        if (! intval($this->data ['post_id'])) {
            $return ['msg'] = '参数非法';

            return $return;
        }
        if (! $this->mid || ! CheckPermission('weiba_normal', 'weiba_reply')) {
            $return ['msg'] = '你无权发布';

            return $return;
        }

        $feed_detail = M('weiba_post')->where('post_id='.intval($this->data ['post_id']))->find();

        $data ['weiba_id'] = intval($feed_detail ['weiba_id']);
        $data ['post_id'] = intval($this->data ['post_id']);
        $data ['post_uid'] = intval($feed_detail ['post_uid']);
        if (! empty($this->data ['to_comment_id'])) {
            $data ['to_reply_id'] = intval($this->data ['to_comment_id']);
            $data ['to_uid'] = model('Comment')->where('comment_id='.intval($this->data ['to_comment_id']))->getField('uid');
        }
        $data ['uid'] = $this->mid;
        $data ['ctime'] = time();
        $data ['content'] = preg_html(h($this->data ['content']));
        /* # 格式化emoji */
        $data['content'] = formatEmoji(true, $data['content']);
        $data ['attach_id'] = intval($this->data ['attach_id']);

        $filterContentStatus = filter_words($data ['content']);
        if (! $filterContentStatus ['status']) {
            return array(
                    'status' => 0,
                    'msg' => $filterContentStatus ['data'],
            );
        }
        $data ['content'] = $filterContentStatus ['data'];

        if (isSubmitLocked()) {
            $return ['msg'] = '发布内容过于频繁，请稍后再试！';

            return $return;
        }

        if ($data ['reply_id'] = D('weiba_reply')->add($data)) {
            // 锁定发布
            lockSubmit();

            // 添加积分
            model('Credit')->setUserCredit(intval($data ['post_uid']), 'comment_topic');
            model('Credit')->setUserCredit($data ['to_uid'], 'commented_topic');

            $map ['last_reply_uid'] = $this->mid;
            $map ['last_reply_time'] = $data ['ctime'];
            $map ['reply_count'] = array(
                    'exp',
                    'reply_count+1',
            );
            $map ['reply_all_count'] = array(
                    'exp',
                    'reply_all_count+1',
            );
            D('weiba_post')->where('post_id='.$data ['post_id'])->save($map);
            // 同步到微博评论
            $datas ['app'] = 'weiba';
            $datas ['table'] = 'feed';
            $datas ['content'] = preg_html($data ['content']);
            $datas ['app_uid'] = intval($feed_detail ['post_uid']);
            $datas ['row_id'] = intval($feed_detail ['feed_id']);
            $datas ['to_comment_id'] = $data ['to_reply_id'] ? D('weiba_reply')->where('reply_id='.$data ['to_reply_id'])->getField('comment_id') : 0;
            $datas ['to_uid'] = intval($data ['to_uid']);
            $datas ['uid'] = $this->mid;
            $datas ['ctime'] = time();
            $datas ['client_type'] = getVisitorClient();
            // $datas ['from'] = 'weiba';
            $data ['cancomment'] = 1;
            // 解锁
            unlockSubmit();
            if ($comment_id = model('Comment')->addComment($datas)) {
                $data1 ['comment_id'] = $comment_id;
                // $data1['storey'] = model('Comment')->where('comment_id='.$comment_id)->getField('storey');
                D('weiba_reply')->where('reply_id='.$data ['reply_id'])->save($data1);
                // 给应用UID添加一个未读的评论数
                // if ($GLOBALS ['ts'] ['mid'] != $datas ['app_uid'] && $datas ['app_uid'] != '') {
                //     ! $notCount && model('UserData')->updateKey('unread_comment_weiba', 1, true, $datas ['app_uid']);
                // }
                model('Feed')->cleanCache($datas ['row_id']);
            }
            // 转发到我的微博
            if ($this->data ['ifShareFeed'] == 1) {
                $commentInfo = model('Source')->getSourceInfo($datas ['table'], $datas ['row_id'], false, $datas ['app']);
                $oldInfo = isset($commentInfo ['sourceInfo']) ? $commentInfo ['sourceInfo'] : $commentInfo;
                // 根据评论的对象获取原来的内容
                $s ['sid'] = $data ['post_id'];
                $s ['app_name'] = 'weiba';
                if (! empty($data ['to_comment_id'])) {
                    $replyInfo = model('Comment')->init($data ['app'], $data ['table'])->getCommentInfo($data ['to_comment_id'], false);
                    $data ['content'] .= $replyInfo ['content'];
                }
                $s ['body'] = $data ['content'];
                $s ['type'] = 'weiba_post';
                $s ['comment'] = $data ['comment_old'];
                // 去掉回复用户@
                $lessUids = array();
                if (! empty($data ['to_uid'])) {
                    $lessUids [] = $data ['to_uid'];
                }
                // 如果为原创微博，不给原创用户发送@信息
                if ($oldInfo ['feedtype'] == 'post' && empty($data ['to_uid'])) {
                    $lessUids [] = $oldInfo ['uid'];
                }
                unlockSubmit();
                model('Share')->shareFeed($s, 'comment', $lessUids);
            }
            $data ['feed_id'] = $datas ['row_id'];
            $data ['comment_id'] = $comment_id;
            $data ['storey'] = $data1 ['storey'];

            $data ['attach_info'] = model('Attach')->getAttachById($data ['attach_id']);
            if ($data ['attach_info'] ['attach_type'] == 'weiba_comment_image' || $data ['attach_info'] ['attach_type'] == 'feed_image') {
                $data ['attach_info'] ['attach_url'] = getImageUrl($data ['attach_info'] ['save_path'].$data ['attach_info'] ['save_name'], 200, 200);
            }

            $return ['status'] = 1;
            $return ['msg'] = '发布成功';
        }

        return $return;
    }
    public function add_post_digg()
    {
        $maps ['post_id'] = $map ['post_id'] = intval($this->data ['post_id']);
        $map ['uid'] = $this->mid;
        $hasdigg = M('weiba_post_digg')->where($map)->find();
        if ($hasdigg) {
            $result ['status'] = 0;
            $result ['msg'] = '你已经赞过';

            return $result;
        }
        $map ['cTime'] = time();
        $res = M('weiba_post_digg')->add($map);
        if ($res) {
            $post = M('weiba_post')->where($maps)->find();
            M('weiba_post')->where($maps)->setField('praise', $post ['praise'] + 1);

            $result ['status'] = 1;
            $result ['msg'] = '操作成功';

            return $result;
        } else {
            $result ['status'] = 0;
            $result ['msg'] = '操作失败';

            return $result;
        }
    }

    /**
     * 删除帖子
     * @AuthorHTL
     * @DateTime  2016-04-26T15:46:24+0800
     * @return json 删除消息
     */
    public function del_post()
    {
        $return['status'] = 0;
        $return['msg'] = '操作失败';
        if (!$this->data['post_id']) {
            $return['msg'] = '请选择帖子';

            return $return;
        }
        $weiba_post_mod = M('weiba_post');
        $map['post_id'] = intval($this->data['post_id']);
        $data['is_del'] = 1;

        //帖子假删除
        $weiba_post_mod->where($map)->data($data)->save();

        //帖子评论假删除
        M('weiba_reply')->where($map)->data($data)->save();

        $feed_map['feed_id'] = $comment_map['row_id'] = $weiba_post_mod->where($map)->getField('feed_id');

        //同步生成的微博的评论假删除

        $return = model('Feed')->doEditFeed($feed_map['feed_id'], 'delFeed', '', $this->mid);

        $return ['status'] == 1 && model('FeedTopic')->deleteWeiboJoinTopic($feed_map['feed_id']);

        model('Comment')->where($comment_map)->data($data)->save();

        // 删除@信息
        model('Atme')->setAppName('Public')->setAppTable('feed')->deleteAtme(null, $feed_map['feed_id'], null);
        // 删除收藏信息
        model('Collection')->delCollection($feed_map['feed_id'], 'feed');
        $return['status'] = '1';
        $return['msg'] = '删除成功';

        return $return;
    }

    //取消赞
    public function del_post_digg()
    {
        $maps ['post_id'] = $map ['post_id'] = intval($this->data ['post_id']);
        $map ['uid'] = $this->mid;
        $res = M('weiba_post_digg')->where($map)->delete();
        if ($res) {
            $post = M('weiba_post')->where($maps)->find();
            M('weiba_post')->where($maps)->setField('praise', $post ['praise'] - 1);
            $result ['status'] = 1;
            $result ['msg'] = '操作成功';

            return $result;
        } else {
            $result ['status'] = 0;
            $result ['msg'] = '操作失败';

            return $result;
        }
    }
    public function upload_photo()
    {
        $d ['attach_type'] = 'weiba_post';
        $d ['upload_type'] = 'image';
        $GLOBALS ['fromMobile'] = true;
        $info = model('Attach')->upload($d, $d);

        return  $this->add_post($info ['info']);
    }
    public function add_post($imgs)
    {
        if (! CheckPermission('weiba_normal', 'weiba_post')) {
            $this->error('对不起，您没有权限进行该操作！');
        }
        $weibaid = intval($this->data ['weiba_id']);
        if (! $weibaid) {
            $this->error('请选择微吧！');
        }
        $weiba = D('weiba')->where('weiba_id='.$weibaid)->find();
        if (! CheckPermission('core_admin', 'admin_login')) {
            switch ($weiba ['who_can_post']) {
                case 1:
                    $map ['weiba_id'] = $weibaid;
                    $map ['follower_uid'] = $this->mid;
                    $res = D('weiba_follow')->where($map)->find();
                    if (! $res && ! CheckPermission('core_admin', 'admin_login')) {
                        $this->error('对不起，您没有发帖权限，请关注该微吧！');
                    }
                    break;
                case 2:
                    $map ['weiba_id'] = $weibaid;
                    $map ['level'] = array(
                            'in',
                            '2,3',
                    );
                    $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid')->findAll();
                    if (! in_array($this->mid, getSubByKey($weiba_admin, 'follower_uid')) && ! CheckPermission('core_admin', 'admin_login')) {
                        $this->error('对不起，您没有发帖权限，仅限管理员发帖！');
                    }
                    break;
                case 3:
                    $map ['weiba_id'] = $weibaid;
                    $map ['level'] = 3;
                    $weiba_admin = D('weiba_follow')->where($map)->order('level desc')->field('follower_uid')->find();
                    if ($this->mid != $weiba_admin ['follower_uid'] && ! CheckPermission('core_admin', 'admin_login')) {
                        $this->error('对不起，您没有发帖权限，仅限吧主发帖！');
                    }
                    break;
            }
        }

        if (! empty($imgs)) {
            foreach ($imgs as $v) {
                $src = getImageUrlByAttachId($v ['attach_id'], 320, 1000);
                $src && $img_arr [] = '<img src="'.$src.'" class="mobile_upload" _src="'.getImageUrlByAttachId($v ['attach_id']).'" />';
            }

            $this->data ['content'] = implode(' ', $img_arr).$this->data ['content'];
        }

        $checkContent = str_replace('&nbsp;', '', $this->data ['content']);
        $checkContent = str_replace('<br />', '', $checkContent);
        $checkContent = str_replace('<p>', '', $checkContent);
        $checkContent = str_replace('</p>', '', $checkContent);
        $checkContents = preg_replace('/<img(.*?)src=/i', 'img', $checkContent);
        $checkContents = preg_replace('/<embed(.*?)src=/i', 'img', $checkContents);
        if (strlen(t($this->data ['title'])) == 0) {
            $this->error('帖子标题不能为空');
        }
        if (strlen(t($checkContents)) == 0) {
            $this->error('帖子内容不能为空');
        }
        preg_match_all('/./us', t($this->data ['title']), $match);
        if (count($match [0]) > 20) { // 汉字和字母都为一个字
            $this->error('帖子标题不能超过20个字');
        }
        if ($this->data ['attach_ids']) {
            $attach = explode('|', $this->data ['attach_ids']);
            foreach ($attach as $k => $a) {
                if (! $a) {
                    unset($attach [$k]);
                }
            }
            $attach = array_map('intval', $attach);
            $data ['attach'] = serialize($attach);
        }
        $data ['weiba_id'] = $weibaid;
        $data ['title'] = t($this->data ['title']);
        $data ['content'] = h($this->data ['content']);

        // 格式化emoji
        $data['title'] = formatEmoji(true, $data['title']);
        $data['content'] = formatEmoji(true, $data['content']);

        // 处理换行，临时解决方案
        $br = array("\r\n", "\n", "\r");
        $replace = '<br/>';
        $data['content'] = str_replace($br, $replace, $data['content']);


        $data ['post_uid'] = $this->mid;
        $data ['post_time'] = time();
        $data ['last_reply_uid'] = $this->mid;
        $data ['last_reply_time'] = $data ['post_time'];

        $filterTitleStatus = filter_words($data ['title']);
        if (! $filterTitleStatus ['status']) {
            $this->error($filterTitleStatus ['data'], true);
        }
        $data ['title'] = $filterTitleStatus ['data'];

        $filterContentStatus = filter_words($data ['content']);
        if (! $filterContentStatus ['status']) {
            $this->error($filterContentStatus ['data'], true);
        }
        $data ['content'] = $filterContentStatus ['data'];

        $res = D('weiba_post')->add($data);
        if ($res) {
            D('weiba')->where('weiba_id='.$data ['weiba_id'])->setInc('thread_count');
            // 同步到微博
            // $feed_id = D('weibaPost')->syncToFeed($res,$data['title'],t($checkContent),$this->mid);
            $feed_id = model('Feed')->syncToFeed('weiba', $this->mid, $res);
            D('weiba_post')->where('post_id='.$res)->setField('feed_id', $feed_id);
            // $this->assign('jumpUrl', U('weiba/Index/postDetail',array('post_id'=>$res)));
            // $this->success('发布成功');

            $result ['id'] = $res;
            $result ['feed_id'] = $feed_id;
            // 添加积分
            model('Credit')->setUserCredit($this->mid, 'publish_topic');

            return array(
                    'status' => 1,
                    'post_id' => $res,
                    'msg' => '发布成功',
            );
        } else {
            $this->error('发布失败');
        }
    }
}
