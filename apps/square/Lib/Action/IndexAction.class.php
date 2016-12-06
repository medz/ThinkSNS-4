<?php
/**
 * IndexAction
 * blog的Action.接收和过滤网页传参
 *
 * @uses Action
 *
 * @version $id$
 *
 * @copyright 2009-2011 SamPeng
 * @author SamPeng <sampeng87@gmail.com>
 * @license PHP Version 5.2 {@link www.sampeng.cn}
 */
class IndexAction extends Action
{
    /**
     * __initialize
     * 初始化.
     */
    public function _initialize()
    {
    }

    /**
     * index
     * 好友的广场.
     */
    public function index()
    {
        $setting = model('Xdata')->lget('square');
        $map = array();
        if ($setting['channel'] == '1') {
            //频道
            $setting['channelid'] = explode(',', $setting['channelid']);
            $map['status'] = 1;
            if (!$setting['channelid']) {
                $list = D('Channel', 'channel')->where($map)->order('rand()')->limit(8)->findAll();
            } else {
                $map['feed_channel_link_id'] = array('in', $setting['channelid']);
                $list = D('Channel', 'channel')->where($map)->order('rand()')->limit(8)->findAll();
            }

            $feedIds = getSubByKey($list, 'feed_id');
            // 获取分享信息
            $feedInfo = model('Feed')->getFeeds($feedIds);
            $feedInfos = array();
            foreach ($feedInfo as $val) {
                $feedInfos[$val['feed_id']] = $val;
            }
            $cmap['c.feed_id'] = array('IN', $feedIds);
            $categoryInfo = D()->table('`'.C('DB_PREFIX').'channel` AS c LEFT JOIN `'.C('DB_PREFIX').'channel_category` AS cc ON cc.channel_category_id = c.channel_category_id')
            ->field('c.`feed_id`,c.`feed_channel_link_id`, c.`status`, cc.channel_category_id, cc.`title`')
            ->where($cmap)
            ->findAll();
            $categoryInfos = array();
            foreach ($categoryInfo as $val) {
                $categoryInfos[$val['feed_id']][] = $val;
            }
            // 组装信息
            foreach ($list as &$value) {
                $info = @unserialize($feedInfos[$value['feed_id']]['feed_data']);
                $value['uid'] = $feedInfos[$value['feed_id']]['user_info']['uid'];
                $value['uname'] = $feedInfos[$value['feed_id']]['user_info']['uname'];
                $image = matchImages($feedInfos[$value['feed_id']]['body']);
                $value['img'] = '';
                if ($feedInfos[$value['feed_id']]['type'] == 'postimage') {
                    if ($info['attach_id']) {
                        $value['img'] = getImageUrlByAttachId($info['attach_id'][0], 236, 177, true);
                    }
                }
                $value['content'] = $info['body'] ? t($info['body']) : t($info['content']);
                $value['comment_count'] = $feedInfos[$value['feed_id']]['comment_count'];
                $value['digg_count'] = $feedInfos[$value['feed_id']]['digg_count'];
                $value['categoryInfo'] = $categoryInfos[$value['feed_id']];
            }
            $this->assign('channel', $list);
        }
        $map = array();
        if ($setting['weiba'] == '1') {
            $setting['weibaid'] = str_replace('，', ',', $setting['weibaid']);
            $setting['weibaid'] = explode(',', $setting['weibaid']);
            $weibaId = array();
            foreach ($setting['weibaid'] as $k => $vo) {
                $vo = intval(trim($vo));
                if ($vo > 0 && !in_array($vo, $weibaId)) {
                    $weibaId[] = $vo;
                }
            }

            $weiba = M('weiba');

            $map['is_del'] = 0;
            $map['status'] = 1;
            if ($weibaId) {
                $map['weiba_id'] = array('in', $weibaId);
                $order = 'FIND_IN_SET(weiba_id,\''.implode(',', $weibaId).'\')';
                $weiba_recommend = $weiba->where($map)->order($order)->limit(6)->select();
            }
            if (!$weibaId || !$weiba_recommend) {
                $weiba_recommend = array();
            }
            //当推荐微吧不足2 或 4 时自动补齐
            $count = count($weiba_recommend);
            // if ($count < 2 || ($count > 2 && $count < 4)) {
                if ($count < 4) {
                    if ($count != 0) {
                        $map['weiba_id'] = array('not in', $weibaId);
                    } elseif (isset($map['weiba_id'])) {
                        unset($map['weiba_id']);
                    }
                    $order = 'recommend DESC,follower_count DESC';
                    $weiba->where($map)->order($order);
                    $limit = 4 - $count;
                    $array = $weiba->limit($limit)->select();
                    $weiba_recommend = array_merge($weiba_recommend, $array);
                }

            foreach ($weiba_recommend as $k => $v) {
                $weiba_recommend[$k]['logo'] = getImageUrlByAttachId($v['logo']);
                //帖子推荐
                $sql = 'SELECT post_id,title FROM `'.C('DB_PREFIX').'weiba_post` WHERE weiba_id='.$v['weiba_id'].' AND ( `is_del` = 0 ) ORDER BY recommend desc,recommend_time desc,post_time desc LIMIT 3';
                $weiba_post = M('weiba_post')->query($sql);
                if ($weiba_post) {
                    foreach ($weiba_post as $kk => $vv) {
                        $weiba_post[$kk]['title'] = t($vv['title']);
                    }
                    $weiba_recommend[$k]['post'] = $weiba_post;
                }
            }
            $this->assign('weiba_recommend', $weiba_recommend);

            //精彩帖子
            $maps['is_del'] = 0;
            //剔除不符合微吧ID
            $fwid = D('weiba')->where('is_del=1 OR status=0')->order($order)->select();
            $fids = getSubByKey($fwid, 'weiba_id');
            if ($fids) {
                $maps['weiba_id'] = array(
                        'not in',
                        $fids,
                );
            }
            //首页推荐帖子
            $order = 'is_index_time desc';
            $maps['is_index'] = 1;
            $list = D('weiba_post')->field('weiba_id,post_id,title,content,index_img')->where($maps)->order($order)->select();
            //如果首页推荐帖子不够6个，获取全局置顶，吧内置顶，最新回复帖子
            if (count($list) < 6) {
                $limit = 6 - count($list);
                $post_ids = getSubByKey($list, 'post_id');
                $maps['post_id'] = array(
                        'not in',
                        $post_ids,
                );
                $maps['is_index'] = 0;
                $order = 'top desc,last_reply_time desc';
                $_list = D('weiba_post')->field('post_id,title,content')->where($maps)->order($order)->limit($limit)->select();
            }
            $weiba_hot = array_merge($list, $_list);
            if ($weiba_hot[0]['index_img'] != null) {
                //首页帖子图片换成缩略图
                $index_img = model('Attach')->getAttachById($weiba_hot[0]['index_img']);
                $weiba_hot[0]['index_img'] = getImageUrl($index_img['save_path'].$index_img['save_name'], '290', '100', true, false);
            }
            foreach ($weiba_hot as $key => &$value) {
                $value['content'] = t($value['content']);
            }
            $this->assign('weiba_hot', $weiba_hot);
        } else {
            $this->assign('weiba_recommend', '');
        }

        if ($setting['relateduser'] == '1') {
            //最新认证用户
            $user_recommend = model('RelatedUser')->getRelatedUserSquare(6);
            foreach ($user_recommend as $k => $vo) {
                // 用户兴趣
                $tags = model('Tag')->setAppName('public')->setAppTable('user')->getAppTags($vo['userInfo']['uid']);
                //$user_recommend[$k]['userInfo']['tags'] =  implode (',',$tags);
                $user_recommend[$k]['userInfo']['tags'] = $tags;
            }
            $this->assign('user_recommend', $user_recommend);
            //后台推荐认证用户
            $setting = model('Xdata')->lget('square');
            $_user_recommend_verified = D('user_verified')->where('verified=1 and uid='.$setting['user_recommend_uid'])->find();

            $setting = model('Xdata')->lget('square');
            //用户数据
            $_user_recommend = model('User')->getUserInfo($_user_recommend_verified['uid']);
            $_user_recommend['verified_info'] = $_user_recommend_verified;
            //用户组图标
            $icon = getSubByKey($_user_recommend['user_group'], 'user_group_icon',
                    array('user_group_id', $_user_recommend['verified_info']['usergroup_id']));
            $icon = array_pop($icon);
            $_user_recommend['verified_info']['icon'] = basename(substr($icon, 0, strpos($icon, '.')));
            $this->assign('_user_recommend', $_user_recommend);
            //用户分享配图
            $_user_recommend_feedimages = $this->getUserAttachData($_user_recommend_verified['uid']);
            foreach ($_user_recommend_feedimages as $key => &$value) {
                $value['src'] = getImageUrl($value['savepath'], '103', '100', true, false);
                $value['uid'] = $_user_recommend_verified['uid'];
            }
            $this->assign('_user_recommend_feedimages', $_user_recommend_feedimages);
            //用户最新帖子
            $pmap['post_uid'] = $_user_recommend_verified['uid'];
            $pmap['is_del'] = 0;
            $_user_recommend_posts = D('weiba_post')->field('post_id,title')->where($pmap)->limit(5)->order('post_time desc')->select();
            $this->assign('_user_recommend_posts', $_user_recommend_posts);
        }

        $this->display();
    }

    private function getUserAttachData($uid, $limit = 4, $page = 1)
    {
        $map['a.uid'] = $uid;
        $map['a.type'] = 'postimage';
        $map['is_del'] = 0;
        $limit_start = ($page - 1) * $limit;
        $list = D()->table('`'.C('DB_PREFIX').'feed` AS a LEFT JOIN `'.C('DB_PREFIX').'feed_data` AS b ON a.`feed_id` = b.`feed_id`')
                   ->field('a.`feed_id`, a.`publish_time`, b.`feed_data`')
                   ->where($map)
                   ->order('feed_id DESC')
                   ->limit($limit_start.','.$limit)
                   ->findAll();

        // 获取附件信息
        foreach ($list as &$value) {
            $tmp = unserialize($value['feed_data']);
            $attachId = is_array($tmp['attach_id']) ? intval($tmp['attach_id'][0]) : intval($tmp['attach_id']);
            $attachInfo = model('Attach')->getAttachById($attachId);
            $value['savepath'] = $attachInfo['save_path'].$attachInfo['save_name'];
            $value['name'] = $attachInfo['name'];
            $value['body'] = parseForApi($tmp['body']);
        }

        return $list;
    }
}
