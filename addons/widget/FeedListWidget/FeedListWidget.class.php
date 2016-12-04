<?php
/**
 * 分享列表
 * @example {:W('FeedList',array('type'=>'space','feed_type'=>$feed_type,'feed_key'=>$feed_key,'loadnew'=>0,'gid'=>$gid))}
 * @author jason
 * @version TS3.0
 */
class FeedListWidget extends Widget
{
    private static $rand = 1;
    private $limitnums = 10;

    /**
     *
     * @param
     *        	string type 获取哪类分享 following:我关注的 space：
     * @param
     *        	string feed_type 分享类型
     * @param
     *        	string feed_key 分享关键字
     * @param
     *        	integer fgid 关注的分组id
     * @param
     *        	integer gid 群组id
     * @param
     *        	integer loadnew 是否加载更多 1:是 0:否
     */
    public function render($data = array())
    {
        /* # 创建默认参数 */
        $var = array(
            'loadmore' => 1,
            'loadnew' => 1,
            'tpl' => 'FeedList.html',
        );

        /* # 合并自定义参数 */
        is_array($data) and $var = array_merge($var, $data);
        unset($data);

        /* # 设置微博设置 */
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $var['initNums'] = $weiboSet['weibo_nums'];
        $var['weibo_type'] = $weiboSet['weibo_type'];
        $var['weibo_premission'] = $weiboSet['weibo_premission'];
        unset($weiboSet);

        /* # 查询是否有话题ID */
        if ($var['topic_id']) {
            $content = $this->getTopicData($var, '_FeedList.html');

        /* # 查询默认数据 */
        } else {
            /* # 是否是频道 */
            if ($var['type'] == 'channel') {
                // $sql = 'SELECT COUNT(`c`.`channel_category_id`) AS `num` FROM `%s` AS `f` LEFT JOIN `%s` AS `c` ON `f`.`channel_category_id` = `c`.`channel_category_id` WHERE `f`.`uid` = %d';
                // $sql = sprintf($sql, D('channel_follow')->getTableName(), D('channel_category')->getTableName(), intval($this->mid));
                // $num = D()->query($sql);
                // $num = array_shift($num);
                // $num = array_shift($num);
                // $var['channel'] = $num;
                // unset($sql, $num);

                $uid = $GLOBALS['ts']['mid'];
                $var['channel'] = \Ts\Models\ChannelCategory::whereHas('follows', function ($q) use ($uid) {
                    $q->where('uid', $uid);
                })->count();
            }

            /* # 取得数据 */
            $content = $this->getData($var, '_FeedList.html');
        }

        /* # 查看是否有更多数据 */
        if (empty($content['html'])) {
            $var['list'] = '';

        /* # 解析数据 */
        } else {
            /* # 是有前置 */
            $content['firstId'] or $var['loadmore'] = 0;

            /* # 赋予数据 */
            $var['list'] = $content['html'];
            $var['lastId'] = $content['lastId'];
            $var['firstId'] = intval($content['firstId']);
            $var['pageHtml'] = $content['pageHtml'];
        }

        /* # 获取HTML内容 */
        $content = $this->renderFile(__DIR__.'/'.$var['tpl'], $var);

        /* # 数量 + 1 */
        self::$rand += 1;

        /* # 注销变量 */
        unset($var);

        /* # 返回数据 */
        return $content;
    }

    /**
     * 显示更多分享
     *
     * @return array 更多分享信息、状态和提示
     */
    public function loadMore()
    {
        // 获取GET与POST数据
        $_REQUEST = $_GET + $_POST;
        // 查询是否有分页
        if (! empty($_REQUEST ['p']) || intval($_REQUEST ['load_count']) == 4) {
            unset($_REQUEST ['loadId']);
            $this->limitnums = 40;
        } else {
            $return = array(
                    'status' => - 1,
                    'msg' => L('PUBLIC_LOADING_ID_ISNULL'),
            );
            $_REQUEST ['loadId'] = intval($_REQUEST ['loadId']);
            $this->limitnums = 10;
        }
        // 查询是否有话题ID
        if ($_REQUEST ['topic_id']) {
            $content = $this->getTopicData($_REQUEST, '_FeedList.html');
        } else {
            $content = $this->getData($_REQUEST, '_FeedList.html');
        }
        // 查看是否有更多数据
        if (empty($content ['html']) || (empty($_REQUEST ['loadId']) && intval($_REQUEST ['load_count']) != 4)) {
            // 没有更多的
            $return = array(
                    'status' => 0,
                    'msg' => L('PUBLIC_WEIBOISNOTNEW'),
            );
        } else {
            $return = array(
                    'status' => 1,
                    'msg' => L('PUBLIC_SUCCESS_LOAD'),
            );
            $return ['html'] = $content ['html'];
            $return ['loadId'] = $content ['lastId'];
            $return ['firstId'] = (empty($_REQUEST ['p']) && empty($_REQUEST ['loadId'])) ? $content ['firstId'] : 0;
            $return ['pageHtml'] = $content ['pageHtml'];
        }
        exit(json_encode($return));
    }

    /**
     * 显示最新分享
     *
     * @return array 最新分享信息、状态和提示
     */
    public function loadNew()
    {
        $return = array(
                'status' => - 1,
                'msg' => '',
        );
        $_REQUEST ['maxId'] = intval($_REQUEST ['maxId']);
        if (empty($_REQUEST ['maxId'])) {
            echo json_encode($return);
            exit();
        }
        $content = $this->getData($_REQUEST, '_FeedList.html');
        if (empty($content ['html'])) { // 没有最新的
            $return = array(
                    'status' => 0,
                    'msg' => L('PUBLIC_WEIBOISNOTNEW'),
            );
        } else {
            $return = array(
                    'status' => 1,
                    'msg' => L('PUBLIC_SUCCESS_LOAD'),
            );
            $return ['html'] = $content ['html'];
            $return ['maxId'] = intval($content ['firstId']);
            $return ['count'] = intval($content ['count']);
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 获取分享数据，渲染分享显示页面
     *
     * @param array $var
     *                     分享数据相关参数
     * @param string $tpl
     *                    渲染的模板
     * @return array  获取分享相关模板数据
     */
    private function getData(array $var, $tpl = 'FeedList.html')
    {
        // # 安全处理
        $var['feed_key'] = t($var['feed_key']);

        // # 不存在默认赋值1
        isset($var['cancomment']) or $var['cancomment'] = 1;

        // # 模式
        $var['cancomment_old_type'] = array('post', 'repost', 'postimage', 'postfile', 'weiba_post', 'weiba_repost', 'blog_post', 'blog_repost', 'event_post', 'event_repost', 'vote_post', 'vote_repost', 'photo_post', 'photo_repost');

        // # 合并配置
        $var = array_merge($var, model('Xdata')->get('admin_Config:feed'));

        $var['remarkHash'] = model('Follow')->getRemarkHash($this->mid);

        $map = $list = array();
        $type = $var ['new'] ? 'new'.$var ['type'] : $var ['type']; // 最新的分享与默认分享类型一一对应

        switch ($type) {
            case 'following': // 我关注的
                if (! empty($var ['feed_key'])) {
                    // 关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
                    $list = model('Feed')->searchFeed($var ['feed_key'], 'following', $var ['loadId'], $this->limitnums);
                } else {
                    $where = '( a.is_audit=1 OR ( a.is_audit=0 AND a.uid='.$GLOBALS ['ts'] ['mid'].') ) AND a.is_del = 0 ';
                    if ($var ['loadId'] > 0) { // 非第一次
                        $where .= " AND a.feed_id < '".intval($var ['loadId'])."'";
                    }
                    if (! empty($var ['feed_type'])) {
                        if ($var ['feed_type'] == 'post') {
                            $where .= ' AND a.is_repost = 0';
                        } elseif ($var ['feed_type'] == 'repost') {
                            $where .= " AND a.type LIKE '%repost'";
                        } else {
                            $where .= " AND a.type = '".t($var ['feed_type'])."'";
                        }
                    }
                    // 设定可查看的关注分享总数，可以提高大数据量下的查询效率
                    $max = null; //1000;
                    $list = model('Feed')->getFollowingFeed($where, $this->limitnums, '', $var ['fgid'], $max);
                }
                break;
            case 'union': // 我的人脉
                if (! empty($var ['feed_key'])) {
                    // 关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
                    $list = model('Feed')->searchFeed($var ['feed_key'], 'union', $var ['loadId'], $this->limitnums);
                } else {
                    $where = ' a.is_audit=1 AND a.is_del = 0 ';
                    if ($var ['loadId'] > 0) { // 非第一次
                        $where .= " AND a.feed_id < '".intval($var ['loadId'])."'";
                    }
                    if (! empty($var ['feed_type'])) {
                        if ($var ['feed_type'] == 'post') {
                            $where .= ' AND a.is_repost = 0';
                        } elseif ($var ['feed_type'] == 'repost') {
                            $where .= " AND a.type LIKE '%repost'";
                        } else {
                            $where .= " AND a.type = '".t($var ['feed_type'])."'";
                        }
                    }
                    // 设定可查看的关注分享总数，可以提高大数据量下的查询效率
                    $max = null; //1000;
                    $list = model('Feed')->getUnionFeed($where, $this->limitnums, '', $var ['fgid'], $max);
                }
                break;
            case 'all': // 所有的 --正在发生的
                if (! empty($var ['feed_key'])) {
                    // 关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
                    $list = model('Feed')->searchFeed($var ['feed_key'], 'all', $var ['loadId'], $this->limitnums);
                } else {
                    $where = ' (is_audit=1 OR is_audit=0 AND uid='.$GLOBALS ['ts'] ['mid'].') AND is_del = 0 ';
                    if ($var ['loadId'] > 0) { // 非第一次
                        $where .= " AND feed_id < '".intval($var ['loadId'])."'";
                    }
                    if (! empty($var ['feed_type'])) {
                        if ($var ['feed_type'] == 'post') {
                            $where .= ' AND is_repost = 0';
                        } elseif ($var ['feed_type'] == 'repost') {
                            $where .= " AND type LIKE '%repost'";
                        } else {
                            $where .= " AND type = '".t($var ['feed_type'])."'";
                        }
                    }

                    // 设定可查看的全站分享总数，可以提高大数据量下的查询效率
                    $max = null; //10000;
                    $list = model('Feed')->getList($where, $this->limitnums, '', $max);
                }
                break;
            case 'newfollowing': // 关注的人的最新分享
                $where = '( a.is_audit=1 OR ( a.is_audit=0 AND a.uid='.$GLOBALS ['ts'] ['mid'].') ) AND a.is_del = 0 ';
                if ($var ['maxId'] > 0) {
                    $where .= " AND a.feed_id > '".intval($var ['maxId'])."'";
                    $list = model('Feed')->getFollowingFeed($where);
                    $content ['count'] = $list ['count'];
                }
                break;
            case 'newall': // 所有人最新分享 -- 正在发生的
                if ($var ['maxId'] > 0) {
                    $map ['feed_id'] = array(
                            'gt',
                            intval($var ['maxId']),
                    );
                }
                $map ['is_del'] = 0;
                $map ['is_audit'] = 1;
                $map ['uid'] = array(
                        'neq',
                        $GLOBALS ['ts'] ['uid'],
                );
                $list = model('Feed')->getList($map);
                $content ['count'] = $list ['count'];

                break;
            case 'space': // 用户个人空间
                if ($var ['feed_key'] !== '') {
                    // 关键字匹配 采用搜索引擎兼容函数搜索 后期可能会扩展为搜索引擎
                    $list = model('Feed')->searchFeed($var ['feed_key'], 'space', $var ['loadId'], $this->limitnums, '', $var ['feed_type']);
                } else {
                    if ($var ['loadId'] > 0) {
                        $map ['feed_id'] = array(
                                'lt',
                                intval($var ['loadId']),
                        );
                    }
                    $map ['is_del'] = 0;
                    if ($GLOBALS ['ts'] ['mid'] != $GLOBALS ['ts'] ['uid']) {
                        $map ['is_audit'] = 1;
                    }
                    $list = model('Feed')->getUserList($map, $GLOBALS ['ts'] ['uid'], $var ['feedApp'], $var ['feed_type'], $this->limitnums);
                }
                break;
            case 'channel':
                $where = ' (c.is_audit=1 OR c.is_audit=0) AND c.is_del = 0 ';
                if ($var ['loadId'] > 0) { // 非第一次
                    $where .= " AND c.feed_id < '".intval($var ['loadId'])."'";
                }
                if (! empty($var ['feed_type'])) {
                    if ($var ['feed_type'] == 'repost') {
                        $where .= " AND c.type LIKE '%repost'";
                    } else {
                        $where .= " AND c.type = '".t($var ['feed_type'])."'";
                    }
                }

                $list = D('ChannelFollow', 'channel')->getFollowingFeed($where, $this->limitnums, '', $var ['fgid']);
                $content ['count'] = $list ['count'];
                break;
            case 'one':
                $where = ' (is_audit=1 OR is_audit=0 AND uid='.$GLOBALS ['ts'] ['mid'].') AND is_del = 0 AND feed_id = '.$var ['feed_id'];
                // 设定可查看的全站分享总数，可以提高大数据量下的查询效率
                $max = null; //10000;
                $list = model('Feed')->getList($where, $this->limitnums, '', $max);
                break;
            case 'love':
                $ids = M('Collection')->where('uid='.$GLOBALS ['ts'] ['mid'].' and source_table_name="feed"')->findAll();
                $map ['feed_id'] = array(
                        'in',
                        getSubByKey($ids, 'source_id'),
                );
                $map ['is_del'] = 0;
                if ($GLOBALS ['ts'] ['mid'] != $GLOBALS ['ts'] ['uid']) {
                    $map ['is_audit'] = 1;
                }
                $list = model('Feed')->getList($map, $this->limitnums, '', $max);
                // $list = model ( 'Feed' )->getUserList ( $map, $GLOBALS ['ts'] ['uid'], $var ['feedApp'], $var ['feed_type'], $this->limitnums );
                break;
            case 'recommend': // 推荐
// 				if ($var ['maxId'] > 0) {
// 					$map ['feed_id'] = array (
// 							'gt',
// 							intval ( $var ['maxId'] )
// 					);
// 				}
                if ($var ['loadId'] > 0) { // 非第一次
                    // 						$where .= " AND feed_id < '" . intval ( $var ['loadId'] ) . "'";
                    $map ['feed_id'] = array(
                            'lt',
                            intval($var ['loadId']),
                    );
                }
                $map ['is_del'] = 0;
                $map ['is_audit'] = 1;
                $map ['is_recommend'] = 1;
                // $map ['uid'] = array (
                // 'neq',
                // $GLOBALS ['ts'] ['uid']
                // );

                $list = model('Feed')->getList($map, 10, 'feed_id desc,recommend_time desc');
                $content ['count'] = $list ['count'];
                break;
            case 'weiba': // 推荐
// 					if ($var ['maxId'] > 0) {
// 						$map ['feed_id'] = array (
// 								'gt',
// 								intval ( $var ['maxId'] )
// 						);
// 					}
                    if ($var ['loadId'] > 0) { // 非第一次
// 						$where .= " AND feed_id < '" . intval ( $var ['loadId'] ) . "'";
                        $map ['feed_id'] = array(
                                'lt',
                                intval($var ['loadId']),
                        );
                    }
                    $map ['is_del'] = 0;
                    $map ['is_audit'] = 1;
                    // $map ['uid'] = array (
                    // 'neq',
                    // $GLOBALS ['ts'] ['uid']
                    // );
                    $map['type'] = 'weiba_post';
                    $list = model('Feed')->getList($map, 10, 'recommend_time desc, feed_id desc');
                    $content ['count'] = $list ['count'];
                    break;
        }
        // 分页的设置
        isset($list ['html']) && $var ['html'] = $list ['html'];
        if (! empty($list ['data'])) {
            $content ['firstId'] = $var ['firstId'] = $list ['data'] [0] ['feed_id'];
            $content ['lastId'] = $var ['lastId'] = $list ['data'] [(count($list ['data']) - 1)] ['feed_id'];
            $var ['data'] = $list ['data'];

            // 赞功能
            $feed_ids = getSubByKey($var ['data'], 'feed_id');
            $var ['diggArr'] = model('FeedDigg')->checkIsDigg($feed_ids, $GLOBALS ['ts'] ['mid']);

            $uids = array();
            foreach ($var ['data'] as &$v) {
                switch ($v ['app']) {
                    case 'weiba':
                        $v ['from'] = getFromClient(0, $v ['app'], '微吧');
                        break;
                    case 'tipoff':
                        $v ['from'] = getFromClient(0, $v ['app'], '爆料');
                        break;
                    case 'w3g':
                        $v ['from'] = getFromClient(6, $v ['app'], '3G版');
                        break;
                    default:
                        $v ['from'] = getFromClient($v ['from'], $v ['app']);
                        break;
                }
                ! isset($uids [$v ['uid']]) && $v ['uid'] != $GLOBALS ['ts'] ['mid'] && $uids [] = $v ['uid'];
            }
            if (! empty($uids)) {
                $map = array();
                $map ['uid'] = $GLOBALS ['ts'] ['mid'];
                $map ['fid'] = array(
                        'in',
                        $uids,
                );
                $var ['followUids'] = model('Follow')->where($map)->getAsFieldArray('fid');
            } else {
                $var ['followUids'] = array();
            }
        }
        $content ['pageHtml'] = $list['html'];
        // 渲染模版
        $content ['html'] = $this->renderFile(dirname(__FILE__).'/'.$tpl, $var);

        return $content;
    }

    /**
     * 获取话题分享数据，渲染分享显示页面
     *
     * @param array $var
     *                     分享数据相关参数
     * @param string $tpl
     *                    渲染的模板
     * @return array  获取分享相关模板数据
     */
    private function getTopicData(array $var, $tpl = 'FeedList.html')
    {
        /* # 判断 */
        $var['cancomment'] or $var['cancomment'] = 1;

        /* # Old type */
        $var['cancomment_old_type'] = array(
            'post',         /* 分享     */
            'repost',       /* 回复分享 */
            'postimage',    /* 分享图片 */
            'postfile',     /* 分享文件 */
            'weiba_post',   /* 微吧发表 */
            'weiba_repost',  /* 微吧回复 */
        );

        /* # 合并后台分享设置 */
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $var = array_merge($var, $weiboSet);
        unset($weiboSet);

        /* # 获取指定用户的备注列表 */
        $var['remarkHash'] = model('Follow')->getRemarkHash($this->mid);

        /* # 初始where条件 */
        $where = '`topic_id` = '.intval($var['topic_id']);

        /* # load id */
        ($var['loadId'] > 0) and $where .= ' AND `feed_id` < '.intval($var['loadId']);

        /* # 分享条件 */
        $map = array(
            'feed_id' => array(
                'IN',
                getSubByKey(D('feed_topic_link')->where($where)->field('`feed_id`')->select(), 'feed_id'),
            ),
        );
        unset($where);

        /* # 分享类型 */
        empty($var['feed_type']) or $map['feed_type'] = t($var['feed_type']);

        /* # string where */
        $map['_string'] = ' (`is_audit` = 1 OR (is_audit = 0 AND `uid` = '.$this->mid.')) AND `is_del` = 0';
        /*$map ['_string'] = ' (is_audit=1 OR is_audit=0 AND uid=' . $GLOBALS ['ts'] ['mid'] . ') AND is_del = 0 ';*/

        /* # 获取分享列表 */
        $list = model('Feed')->getList($map, $this->limitnums);
        unset($map);

        /* # 分页设置 */
        isset($list['html']) and $var['html'] = $list['html'];

        /* # 如果有数据 */
        if (!empty($list['data'])) {
            /* # 第一条ID */
            $first = array_shift($list['data']);
            $content['firstId'] = $var['firstId'] = $first['feed_id'];

            /* # 将第一条数据还原 */
            array_unshift($list['data'], $first);
            unset($first);

            /* # 最后一条 */
            $last = array_pop($list['data']);
            $content['lastId'] = $var['lastId'] = $last['feed_id'];

            /* # 将最后一条数据还原 */
            array_push($list['data'], $last);
            unset($last);

            /* # 数据复制 */
            $var['data'] = $list['data'];

            /* # 赞功能，获取所有分享ID */
            $feed_ids = getSubByKey($var['data'], 'feed_id');

            /* # 指定用户是否赞了指定的分享 */
            $var['diggArr'] = model('FeedDigg')->checkIsDigg($feed_ids, $this->mid);
            unset($feed_ids);

            /* # 定义储存用户uid的数组 */
            $uids = array();

            /* # 遍历数据 */
            foreach ($var['data'] as $key => $value) {
                /* # 判断来自客户端类型 */
                switch ($value['app']) {
                    /* # 判断是否来自微吧 */
                    case 'weiba':
                        $var['data'][$key]['from'] = getFromClient(0, $value['app'], '微吧');
                        break;

                    /* # 其他 */
                    default:
                        $var['data'][$key]['from'] = getFromClient($value['from'], $value['app']);
                        break;
                }

                /* # 加入UID */
                if (!in_array($value['uid'], $uids) and $value['uid'] != $this->mid) {
                    array_push($uids, $value['uid']);
                }

                /* # 变量注销 */
                unset($key, $value);
            }

            /* # 默认数据 */
            $var['followUids'] = array();

            /* # 如果存在uids */
            if (!empty($uids)) {
                /* # 创建where条件 */
                $map = array(
                    'uid' => $this->mid,
                    'fid' => array('IN', $uids),
                );

                /* # 取得数据 */
                $var['followUids'] = model('Follow')->where($map)->getAsFieldArray('fid');
                unset($map);
            }

            /* # 注销变量 */
            unset($uids);
        }

        /* # 取得分页数据 */
        $content['pageHtml'] = $list['html'];

        /* # 渲染的模板数据 */
        $content['html'] = $this->renderFile(__DIR__.'/'.$tpl, $var);

        /* # 注销变量 */
        unset($var, $list);

        /* # 返回数据 */
        return $content;
    }

    /**
     * 获取微吧帖子数据
     *
     * @param
     *        	[varname] [description]
     */
    public function getPostDetail()
    {
        $post_id = intval($_POST ['post_id']);
        $post_detail = D('weiba_post')->where('is_del=0 and post_id='.$post_id)->find();
        if ($post_detail && D('weiba')->where('is_del=0 and weiba_id='.$post_detail ['weiba_id'])->find()) {
            $post_detail ['post_url'] = U('weiba/Index/postDetail', array(
                    'post_id' => $post_id,
            ));
            $author = model('User')->getUserInfo($post_detail ['post_uid']);
            $post_detail ['author'] = $author ['space_link'];
            $post_detail ['post_time'] = friendlyDate($post_detail ['post_time']);
            $post_detail ['from_weiba'] = D('weiba')->where('weiba_id='.$post_detail ['weiba_id'])->getField('weiba_name');
            $post_detail ['weiba_url'] = U('weiba/Index/detail', array(
                    'weiba_id' => $post_detail ['weiba_id'],
            ));

            return json_encode($post_detail);
        } else {
            echo 0;
        }
    }
    public function getTipoffDetail()
    {
        $tipoff_id = intval($_POST ['tipoff_id']);
        $tipoff_detail = D('tipoff')->where('deleted=0 and archived=0 and tipoff_id='.$tipoff_id)->find();
        if ($tipoff_detail) {
            $tipoff_detail ['tipoff_url'] = U('tipoff/Index/detail', array(
                    'id' => $tipoff_id,
            ));
            $author = model('User')->getUserInfo($tipoff_detail ['uid']);
            $tipoff_detail ['author'] = $author ['space_link'];
            $tipoff_detail ['publish_time'] = friendlyDate($tipoff_detail ['publish_time']);
            $tipoff_detail ['from_category'] = D('tipoff_category')->where('tipoff_category_id='.$tipoff_detail ['category_id'])->getField('title');
            $tipoff_detail ['category_url'] = U('tipoff/Index/index', array(
                    'cid' => $tipoff_detail ['category_id'],
            ));

            return json_encode($tipoff_detail);
        } else {
            echo 0;
        }
    }
    public function getListItemByFeedId()
    {
        $feedId = intval($_REQUEST ['feed_id']);
        $var ['type'] = 'one';
        $var ['feed_id'] = $feedId;
        $content = $this->getData($var, '_FeedList.html');

        $result = array();
        if (empty($content ['html'])) {
            $result ['status'] = 0;
            $result ['html'] = '';
        } else {
            $result ['status'] = 1;
            $result ['html'] = $content ['html'];
        }

        exit(json_encode($result));
    }
}
