<?php
/**
 * 微吧模型 - 数据对象模型.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class WeibaModel extends Model
{
    protected $tableName = 'weiba';
    protected $error = '';
    protected $fields = array(
            0          => 'weiba_id',
            1          => 'weiba_name',
            2          => 'uid',
            3          => 'ctime',
            4          => 'logo',
            5          => 'intro',
            6          => 'who_can_post',
            7          => 'who_can_reply',
            8          => 'follower_count',
            9          => 'thread_count',
            10         => 'admin_uid',
            11         => 'recommend',
            12         => 'status',
            13         => 'api_key',
            14         => 'domain',
            15         => 'province',
            16         => 'city',
            17         => 'area',
            18         => 'reg_ip',
            19         => 'is_del',
            20         => 'notify',
            21         => 'cid',
            22         => 'avatar_big',
            23         => 'avatar_middle',
            24         => 'new_count',
            25         => 'new_day',
            26         => 'info',
            27         => 'input_city',
            '_autoinc' => true,
            '_pk'      => 'weiba_id',
    );

    // 个人感兴趣的群组
    public function interestingWeiba($uid, $pagesize = 4)
    {
        // 个人兴趣
        $user_tag = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($uid);
        $i = 0;
        foreach ((array) $user_tag as $k => $v) {
            if ($i == 0) {
                $_tag_in_name .= " AND ( g.weiba_name LIKE '%{$v}%' ";
                $_tag_in_intro .= " OR g.intro LIKE '%{$v}%' ";
            } else {
                $_tag_in_name .= " OR g.weiba_name LIKE '%{$v}%' ";
                $_tag_in_intro .= " OR g.intro LIKE '%{$v}%' ";
            }
            $i++;
        }
        // 管理和已经加入的群组
        $my_weiba = M('weiba_follow')->field('weiba_id')->where('level >= 1 AND follower_uid='.$uid)->findAll();
        foreach ((array) $my_weiba as $v) {
            $_my_weiba_id[] = $v['weiba_id'];
        }

        $map = 'g.status=1 AND g.is_del=0 AND g.avatar_middle!=""';
        empty($_tag_in_name) || $map .= $_tag_in_name;
        empty($_tag_in_intro) || $map .= $_tag_in_intro;
        if ($_tag_in_name || $_tag_in_intro) {
            $_my_weiba_id && $map .= ') AND g.weiba_id NOT IN ('.implode(',', $_my_weiba_id).')';
            !$_my_weiba_id && $map .= ')';
        } else {
            $_my_weiba_id && $map .= ' AND g.weiba_id NOT IN ('.implode(',', $_my_weiba_id).')';
            !$_my_weiba_id && $map .= ')';
        }
        $weiba_count = $this->field('count(DISTINCT(g.weiba_id)) AS count')->table("{$this->tablePrefix}weiba AS g")->where($map)->find();
        $weiba_list = $this->field('DISTINCT(g.weiba_id),g.weiba_name,g.avatar_middle,g.follower_count,g.ctime,g.intro')->table("{$this->tablePrefix}weiba AS g")->order(' rand() ')->where($map)->findPage($pagesize, $weiba_count['count']);
        //dump(M()->getLastSql());exit;
        // 标签相关的群组不够四个
        if ($weiba_list['count'] < $pagesize) {
            if ($weiba_list['data']) {
                $not_in_gids = array_merge($_my_weiba_id, getSubByKey($weiba_list['data'], 'weiba_id'));
            }
            if ($not_in_gids) {
                $gid_map = ' AND weiba_id NOT IN ('.implode(',', $not_in_gids).') ';
            }
            $_count = $this->where('status=1 AND is_del=0 AND avatar_middle !="" '.$gid_map)->count();
            $rand_list = $this->field('weiba_id,weiba_name,avatar_middle,follower_count,ctime,intro')->where('status=1 AND is_del=0 AND avatar_middle !="" '.$gid_map)->order(' rand() ')->limit((rand(0, $_count - ($pagesize - $weiba_count['count']))).','.($pagesize - $weiba_count['count']))->findAll();
            if (!is_array($weiba_list['data'])) {
                $weiba_list['data'] = array();
            }
            foreach ($rand_list as $v) {
                $v['reason'] = '热门微吧';
                $weiba_list['data'][] = $v;
            }
            //dump($weiba_list); 			dump($this->getLastSql());exit;
        }

        return $weiba_list;
    }

    /**
     * 获取微吧列表，后台可以根据条件查询.
     *
     * @param int   $limit
     *                     结果集数目，默认为20
     * @param array $map
     *                     查询条件
     *
     * @return array 微吧列表信息
     */
    public function getWeibaList($limit = 20, $map = array())
    {
        if (isset($_POST)) {
            // 搜索时用到
            $_POST['weiba_id'] && $map['weiba_id'] = intval($_POST['weiba_id']);
            $_POST['weiba_name'] && $map['weiba_name'] = array(
                    'like',
                    '%'.$_POST['weiba_name'].'%',
            );
            $_POST['uid'] && $map['uid'] = intval($_POST['uid']);
            $_POST['admin_uid'] && $map['admin_uid'] = intval($_POST['admin_uid']);
            $_POST['recommend'] && $map['recommend'] = $_POST['recommend'] == 1 ? 1 : 0;
            $_POST['weiba_cate'] && $map['cid'] = intval($_POST['weiba_cate']);
        }
        $map['is_del'] = 0;
        // 查询数据
        $list = $this->where($map)->order('follower_count desc,thread_count desc')->findPage($limit);

        $weibacate = D('weiba_category')->findAll();
        $cids = array();
        foreach ($weibacate as $c) {
            $cids[$c['id']] = $c['name'];
        }
        // 数据组装
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['weiba_name'] = '<a target="_blank" href="'.U('weiba/Index/detail', array(
                    'weiba_id' => $v['weiba_id'],
            )).'">'.$v['weiba_name'].'</a>';
            $list['data'][$k]['logo'] && $list['data'][$k]['logo'] = '<img src="'.getImageUrlByAttachId($v['logo']).'" width="50" height="50">';
            $create_uid = model('User')->getUserInfoByUids($v['uid']);
            $list['data'][$k]['uid'] = $create_uid[$v['uid']]['space_link'];
            $list['data'][$k]['ctime'] = friendlyDate($v['ctime']);
            $admin_uid = model('User')->getUserInfoByUids($v['admin_uid']);
            $list['data'][$k]['admin_uid'] = $admin_uid[$v['admin_uid']]['space_link'];
            $list['data'][$k]['follower_count/thread_count'] = $v['follower_count'].'/'.$v['thread_count'];
            $isrecommend = $v['recommend'] ? '取消推荐' : '首页热帖推荐';
            $list['data'][$k]['weiba_cate'] = $cids[$v['cid']];
            $list['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.recommend('.$v['weiba_id'].','.$v['recommend'].');">'.$isrecommend.'</a>&nbsp;-&nbsp;<a href="'.U('weiba/Admin/editWeiba', array(
                    'weiba_id' => $v['weiba_id'],
                    'tabHash'  => 'editWeiba',
            )).'">编辑</a>&nbsp;-&nbsp;<a onclick="admin.delWeiba('.$v['weiba_id'].');" href="javascript:void(0)">解散</a>';
        }

        return $list;
    }

    /**
     * 获取微吧的Hash数组.
     *
     * @param string $k
     *                  Hash数组的Key值字段
     * @param string $v
     *                  Hash数组的Value值字段
     *
     * @return array 用户组的Hash数组
     */
    public function getHashWeiba($k = 'weiba_id', $v = 'weiba_name')
    {
        $list = $this->findAll();
        $r = array();
        foreach ($list as $lv) {
            $r[$lv['weiba_id']] = $lv[$v];
        }

        return $r;
    }

    /**
     * 获取帖子列表，后台可以根据条件查询.
     *
     * @param int   $limit
     *                     结果集数目，默认为20
     * @param array $map
     *                     查询条件
     *
     * @return array 微吧列表信息
     */
    public function getPostList($limit = 20, $map = array())
    {
        if (isset($_POST)) {
            // 搜索时用到
            $_POST['post_id'] && $map['post_id'] = intval($_POST['post_id']);
            $_POST['title'] && $map['title'] = array(
                    'like',
                    '%'.$_POST['title'].'%',
            );
            $_POST['post_uid'] && $map['post_uid'] = intval($_POST['post_uid']);
            $_POST['recommend'] && $map['recommend'] = $_POST['recommend'] == 1 ? 1 : 0;
            $_POST['digest'] && $map['digest'] = $_POST['digest'] == 1 ? 1 : 0;
            $_POST['top'] && $map['top'] = intval($_POST['top']);
            $_POST['weiba_id'] && $map['weiba_id'] = intval($_POST['weiba_id']);
        }
        // 查询数据
        if (!$map['weiba_id']) {
            $map['weiba_id'] = array(
                    'in',
                    getSubByKey(D('weiba')->where('is_del=0')->findAll(), 'weiba_id'),
            );
        }
        $list = D('weiba_post')->where($map)->order('last_reply_time desc,post_time desc')->findPage($limit);

        // 数据组装
        foreach ($list['data'] as $k => $v) {
            $list['data'][$k]['title'] = '<a target="_blank" href="'.U('weiba/Index/postDetail', array(
                    'post_id' => $v['post_id'],
            )).'">'.$v['title'].'</a>';
            $author = model('User')->getUserInfoByUids($v['post_uid']);
            $list['data'][$k]['post_uid'] = $author[$v['post_uid']]['space_link'];
            $list['data'][$k]['post_time'] = friendlyDate($v['post_time']);
            $list['data'][$k]['last_reply_time'] = friendlyDate($v['last_reply_time']);
            $list['data'][$k]['read_count/reply_count'] = $v['read_count'].'/'.$v['reply_count'];
            $list['data'][$k]['weiba_id'] = $this->where('weiba_id='.$v['weiba_id'])->getField('weiba_name');
            if ($v['is_del'] == 0) {
                $isRecommend = $v['recommend'] ? '取消推荐' : '推荐到首页';
                $isDigest = $v['digest'] ? '取消精华' : '设为精华';
                $isGlobalTop = $v['top'] == 2 ? '取消全局置顶' : '设为全局置顶';
                $isLocalTop = $v['top'] == 1 ? '取消吧内置顶' : '设为吧内置顶';
                // $list['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',1,'.$v['recommend'].');">'.$isRecommend.'</a>|<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',2,'.$v['digest'].')">'.$isDigest.'</a>|<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',3,'.$v['top'].',2)">'.$isGlobalTop.'</a>|<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',3,'.$v['top'].',1)">'.$isLocalTop.'</a>|<a href="'.U('weiba/Admin/editPost',array('post_id'=>$v['post_id'],'tabHash'=>'editPost')).'">编辑</a>|<a href="javascript:void(0)" onclick="admin.doStorey('.$v['post_id'].')">调整回复楼层</a>|<a href="javascript:void(0)" onclick="admin.delPost('.$v['post_id'].')">删除</a>';
                $list['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',1,'.$v['recommend'].');">'.$isRecommend.'</a>&nbsp;-&nbsp;<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',2,'.$v['digest'].')">'.$isDigest.'</a>&nbsp;-&nbsp;<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',3,'.$v['top'].',2)">'.$isGlobalTop.'</a>&nbsp;-&nbsp;<a href="javascript:void(0)" onclick="admin.setPost('.$v['post_id'].',3,'.$v['top'].',1)">'.$isLocalTop.'</a>&nbsp;-&nbsp;<a href="'.U('weiba/Admin/editPost', array(
                        'post_id' => $v['post_id'],
                        'tabHash' => 'editPost',
                )).'">编辑</a>&nbsp;-&nbsp;<a href="javascript:void(0)" onclick="admin.delPost('.$v['post_id'].')">删除</a>';
            } else {
                $list['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.recoverPost('.$v['post_id'].')">还原</a>&nbsp;-&nbsp;<a href="javascript:void(0)" onclick="admin.deletePost('.$v['post_id'].')">彻底删除</a>';
            }
        }

        return $list;
    }

    /**
     * 根据微吧ID获取微吧信息.
     *
     * @param int $weiba_id
     *                      微吧ID
     *
     * @return array 微吧信息
     */
    public function getWeibaById($weiba_id)
    {
        $weiba = $this->where('weiba_id='.$weiba_id)->find();
        if ($weiba['logo']) {
            $weiba['pic_url'] = getImageUrlByAttachId($weiba['logo']);
        }

        return $weiba;
    }

    /**
     * �
     * �注微吧.
     *
     * @param
     *        	integer uid 用户UID
     * @param
     *        	integer weiba_id 微吧ID
     *
     * @return int 新添加的数据ID
     */
    public function doFollowWeiba($uid, $weiba_id)
    {
        $data['weiba_id'] = $weiba_id;
        $data['follower_uid'] = $uid;
        if (D('weiba_follow')->where($data)->find()) {
            $this->error = '您已关注该微吧';

            return false;
        } else {
            $res = D('weiba_follow')->add($data);
            if ($res) {
                D('weiba')->where('weiba_id='.$weiba_id)->setInc('follower_count');

                // 添加积分
                model('Credit')->setUserCredit($uid, 'follow_weiba');

                return true;
            } else {
                $this->error = '关注失败';

                return false;
            }
        }
    }

    /**
     * 取消�
     * �注微吧.
     *
     * @param
     *        	integer uid 用户UID
     * @param
     *        	integer weiba_id 微吧ID
     *
     * @return int 新添加的数据ID
     */
    public function unFollowWeiba($uid, $weiba_id)
    {
        $data['weiba_id'] = $weiba_id;
        $data['follower_uid'] = $uid;
        if (D('weiba_follow')->where($data)->find()) {
            $res = D('weiba_follow')->where($data)->delete();
            if ($res) {
                D('weiba')->where('weiba_id='.$weiba_id)->setDec('follower_count');
                D('weiba_apply')->where($data)->delete();

                // 添加积分
                model('Credit')->setUserCredit($uid, 'unfollow_weiba');

                return true;
            } else {
                $this->error = '关注失败';

                return false;
            }
        } else {
            $this->error = '您尚未关注该微吧';

            return false;
        }
    }

    /**
     * 判断是否�
     * �注某个微吧.
     *
     * @param
     *        	integer uid 用户UID
     * @param
     *        	integer weiba_id 微吧ID
     *
     * @return bool 是否已�
     * �注
     */
    public function getFollowStateByWeibaid($uid, $weiba_id)
    {
        if (empty($weiba_id)) {
            return 0;
        }
        $follow_data = D('weiba_follow')->where(" ( follower_uid = '{$uid}' AND weiba_id = '{$weiba_id}' ) ")->find();
        if ($follow_data) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 批量获取微吧�
     * �注状态
     *
     * @param
     *        	integer uid 用户UID
     * @param
     *        	array weiba_ids 微吧ID
     *
     * @return [type] [description]
     */
    public function getFollowStateByWeibaids($uid, $weiba_ids)
    {
        $_weibaids = is_array($weiba_ids) ? implode(',', $weiba_ids) : $weiba_ids;
        if (empty($_weibaids)) {
            return array();
        }
        $follow_data = D('weiba_follow')->where(" ( follower_uid = '{$uid}' AND weiba_id IN({$_weibaids}) ) ")->findAll();

        $follow_states = $this->_formatFollowState($uid, $weiba_ids, $follow_data);

        return $follow_states[$uid];
    }

    /**
     * 格式化，用户的�
     * �注数据.
     *
     * @param int   $uid
     *                           用户ID
     * @param array $fids
     *                           用户ID数组
     * @param array $follow_data
     *                           �
     * �注状态数据
     *
     * @return array 格式化后的用户�
     * �注状态数据
     */
    private function _formatFollowState($uid, $weiba_ids, $follow_data)
    {
        !is_array($weiba_ids) && $fids = explode(',', $weiba_ids);
        foreach ($weiba_ids as $weiba_ids) {
            $follow_states[$uid][$weiba_ids] = array(
                    'following' => 0,
            );
        }
        foreach ($follow_data as $r_v) {
            if ($r_v['follower_uid'] == $uid) {
                $follow_states[$r_v['follower_uid']][$r_v['weiba_id']]['following'] = 1;
            }
        }

        return $follow_states;
    }

    /**
     * 获取微吧列表.
     *
     * @param
     *        	integer limit 每页显示条数
     * @param
     *        	integer page 第几页
     *
     * @return array 微吧列表
     */
    public function get_weibas_forapi($since_id, $max_id, $limit, $page, $uid)
    {
        $limit = intval($limit);
        $page = intval($page);
        $where = 'is_del=0';
        if (!empty($since_id) || !empty($max_id)) {
            !empty($since_id) && $where .= " AND weiba_id > {$since_id}";
            !empty($max_id) && $where .= " AND weiba_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $weibaList = $this->where($where)->limit("{$start},{$end}")->order('weiba_id asc')->findAll();
        foreach ($weibaList as $k => $v) {
            if ($v['logo']) {
                $weibaList[$k]['logo_url'] = getImageUrlByAttachId($v['logo']);
            }
            if (D('weiba_follow')->where('follower_uid='.$uid.' AND weiba_id='.$v['weiba_id'])->find()) {
                $weibaList[$k]['followstate'] = 1;
            } else {
                $weibaList[$k]['followstate'] = 0;
            }
            $postStatus = array(
                    'status' => 0,
                    'msg'    => '没有贴吧发帖权限',
            );
            // 添加微吧权限
            if ($GLOBALS['ts']['mid'] && CheckPermission('weiba_normal', 'weiba_post')) {
                $whoCanPost = $v['who_can_post'];
                CheckPermission('core_admin', 'admin_login') && $whoCanPost = 0;
                switch ($whoCanPost) {
                    case 0:
                        $postStatus['status'] = 1;
                        $postStatus['msg'] = '具有此贴吧的发帖权限';
                        break;
                    case 1:
                        if ($v['followstate'] == 1) {
                            $postStatus['status'] = 1;
                            $postStatus['msg'] = '具有此贴吧的发帖权限';
                        } else {
                            $postStatus['status'] = 0;
                            $postStatus['msg'] = '该贴吧关注后才能发帖';
                        }
                        break;
                    case 2:
                        $map['weiba_id'] = $weiba_id;
                        $map['level'] = array(
                                'in',
                                '2,3',
                        );
                        $weiba_admin_uids = D('weiba_follow')->where($map)->order('level DESC')->getAsFieldArray('follower_uid');
                        if (in_array($this->mid, $weiba_admin_uids)) {
                            $postStatus['status'] = 1;
                            $postStatus['msg'] = '具有此贴吧的发帖权限';
                        } else {
                            $postStatus['status'] = 0;
                            $postStatus['msg'] = '该贴吧只有圈主能发帖';
                        }
                        break;
                    case 3:
                        $weiba_super_admin = D('weiba_follow')->where('level=3 and weiba_id='.$v['weiba_id'])->getField('follower_uid');
                        if ($this->mid == $weiba_super_admin) {
                            $postStatus['status'] = 1;
                            $postStatus['msg'] = '具有此贴吧的发帖权限';
                        } else {
                            $postStatus['status'] = 0;
                            $postStatus['msg'] = '该贴吧只有贴吧管理员能发帖';
                        }
                        break;
                }
            }
            $weibaList[$k]['post_status'] = $postStatus;
        }

        return $weibaList;
    }

    /**
     * 获取帖子列表.
     *
     * @param
     *        	integer limit 每页显示条数
     * @param
     *        	integer page 第几页
     * @param
     *        	integer weiba_id 所属微吧ID(可选)
     *
     * @return array 帖子列表
     */
    public function get_posts_forapi($limit = 20, $page = 1, $weiba_id = null)
    {
        $limit = intval($limit);
        $page = intval($page);
        $start = ($page - 1) * $limit;
        $end = $limit;
        if ($weiba_id) {
            $map['weiba_id'] = $weiba_id;
        }
        $map['is_del'] = 0;
        $postList = D('weiba_post')->where($map)->limit("{$start},{$end}")->order('top desc,last_reply_time desc')->findAll();
        foreach ($postList as $k => $v) {
            $postList[$k]['author_info'] = model('User')->getUserInfo($v['post_uid']);
            if (D('weiba_favorite')->where('post_id='.$v['post_id'].' AND uid='.$GLOBALS['ts']['mid'])->find()) {
                $postList[$k]['favorite'] = 1;
            } else {
                $postList[$k]['favorite'] = 0;
            }
        }

        return $postList;
    }

    /**
     * 获取我的帖子.
     *
     * @param
     *        	integer limit 每页显示条数
     * @param
     *        	integer page 第几页
     * @param
     *        	uid 用户UID
     * @param
     *        	varchar type 类型
     *
     * @return array 帖子列表
     */
    public function myWeibaForApi($limit, $page, $uid, $type)
    {
        $map['is_del'] = 0;
        $limit = intval($limit);
        $page = intval($page);
        $start = ($page - 1) * $limit;
        $end = $limit;
        switch ($type) {
            case 'myPost':
                $map['post_uid'] = $uid;
                $postList = D('weiba_post')->where($map)->limit("{$start},{$end}")->order('post_time desc')->findAll();
                break;
            case 'myReply':
                $myreply = D('weiba_reply')->where('uid='.$uid)->order('ctime desc')->field('post_id')->findAll();
                $map['post_id'] = array(
                        'in',
                        array_unique(getSubByKey($myreply, 'post_id')),
                );
                $postList = D('weiba_post')->where($map)->limit("{$start},{$end}")->order('last_reply_time desc')->findAll();
                break;
            case 'myFollow':
                $myFollow = D('weiba_follow')->where('follower_uid='.$uid)->findAll();
                $map['weiba_id'] = array(
                        'in',
                        getSubByKey($myFollow, 'weiba_id'),
                );
                $postList = D('weiba_post')->where($map)->limit("{$start},{$end}")->order('top desc,post_time desc')->findAll();
                break;
            case 'myFavorite':
                $myFavorite = D('weiba_favorite')->where('uid='.$uid)->order('id desc')->findAll();
                $map['post_id'] = array(
                        'in',
                        getSubByKey($myFavorite, 'post_id'),
                );
                $postList = D('weiba_post')->where($map)->limit("{$start},{$end}")->findAll();
        }
        foreach ($postList as $k => $v) {
            $postList[$k]['author_info'] = model('User')->getUserInfo($v['post_uid']);
            if (D('weiba_favorite')->where('post_id='.$v['post_id'].' AND uid='.$uid)->find()) {
                $postList[$k]['favorite'] = 1;
            } else {
                $postList[$k]['favorite'] = 0;
            }
        }

        return $postList;
    }

    /**
     * 搜索微吧.
     *
     * @param
     *        	varchar keyword 搜索�
     * �键字
     * @param
     *        	integer limit 每页显示条数
     * @param
     *        	integer page 第几页
     * @param
     *        	integer uid 用户UID
     *
     * @return array 微吧列表
     */
    public function searchWeibaForApi($keyword, $limit, $page, $uid)
    {
        $limit = intval($limit);
        $page = intval($page);
        $start = ($page - 1) * $limit;
        $end = $limit;
        $map['is_del'] = 0;
        $where['weiba_name'] = array(
                'like',
                '%'.$keyword.'%',
        );
        $where['intro'] = array(
                'like',
                '%'.$keyword.'%',
        );
        $where['_logic'] = 'or';
        $map['_complex'] = $where;
        $weibaList = D('weiba')->where($map)->limit("{$start},{$end}")->order('follower_count desc,thread_count desc')->findAll();
        if ($weibaList) {
            foreach ($weibaList as $k => $v) {
                if ($v['logo']) {
                    $weibaList[$k]['logo_url'] = getImageUrlByAttachId($v['logo']);
                }
                if (D('weiba_follow')->where('follower_uid='.$uid.' AND weiba_id='.$v['weiba_id'])->find()) {
                    $weibaList[$k]['followstate'] = 1;
                } else {
                    $weibaList[$k]['followstate'] = 0;
                }
            }

            return $weibaList;
        } else {
            return array();
        }
    }

    /**
     * 搜索帖子.
     *
     * @param
     *        	varchar keyword 搜索�
     * �键字
     * @param
     *        	integer limit 每页显示条数
     * @param
     *        	integer page 第几页
     *
     * @return array 帖子列表
     */
    public function searchPostForApi($keyword, $limit, $page)
    {
        $limit = intval($limit);
        $page = intval($page);
        $start = ($page - 1) * $limit;
        $end = $limit;
        $map['is_del'] = 0;
        $where['title'] = array(
                'like',
                '%'.$keyword.'%',
        );
        $where['content'] = array(
                'like',
                '%'.$keyword.'%',
        );
        $where['_logic'] = 'or';
        $map['_complex'] = $where;
        $postList = D('weiba_post')->where($map)->limit("{$start},{$end}")->order('post_time desc')->findAll();
        if ($postList) {
            foreach ($postList as $k => $v) {
                $postList[$k]['weiba'] = D('weiba')->where('weiba_id='.$v['weiba_id'])->getField('weiba_name');
                foreach ($postList as $k => $v) {
                    $postList[$k]['author_info'] = model('User')->getUserInfo($v['post_uid']);
                }
            }

            return $postList;
        } else {
            return array();
        }
    }

    public function setNewcount($weiba_id, $num = 1)
    {
        $map['weiba_id'] = $weiba_id;
        $time = time();
        $weiba = D('weiba')->where($map)->find();
        if ($weiba['new_day'] != date('Y-m-d', $time)) {
            D('weiba')->where($map)->setField('new_day', date('Y-m-d', $time));
            D('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num == 0) {
            D('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num > 0) {
            D('weiba')->where($map)->setField('new_count', (int) $num + (int) $weiba['new_count']);
        }

        return true;
    }

    public function getLastError()
    {
        return $this->error;
    }

    /*
     * 获取微吧名称
     */
    public function getWeibaName($weiba_ids)
    {
        $weiba_ids = array_unique($weiba_ids);
        if (empty($weiba_ids)) {
            return false;
        }
        $map['weiba_id'] = array(
                'in',
                $weiba_ids,
        );
        $names = D('weiba')->where($map)->field('weiba_id,weiba_name')->findAll();
        foreach ($names as $n) {
            $nameArr[$n['weiba_id']] = $n['weiba_name'];
        }

        return $nameArr;
    }
}
