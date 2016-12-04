<?php
/**
 * 评论模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class CommentModel extends Model
{
    protected $tableName = 'comment';
    protected $fields = array('type', 'comment_id', 'app', 'table', 'row_id', 'app_uid', 'uid', 'content', 'to_comment_id', 'to_uid', 'data', 'ctime', 'is_del', 'client_type', 'is_audit', 'storey', 'app_detail_url', 'app_detail_summary', 'client_ip', 'client_port', 'digg_count');

    private $_app = null;                                                   // 所属应用
    private $_app_table = null;                                             // 所属资源表
    private $_app_pk_field = null;                                          // 应用主键字段

    // private static $infoList = array();

    /**
     * 设置所属应用
     * @param  string $app 应用名称
     * @return object 评论对象
     */
    public function setAppName($app)
    {
        $this->_app = $app;

        return $this;
    }

    /**
     * 设置相关内容所存储的资源表
     * @param  string $app_table 数据表名
     * @return object 评论对象
     */
    public function setAppTable($app_table)
    {
        $this->_app_table = $app_table;

        return $this;
    }
    /**
     * 设置所需的参数
     * @param  string $app       应用名称
     * @param  string $app_table 数据表名
     * @return object 评论对象
     */
    public function init($app, $app_table)
    {
        $this->_app = $app;
        $this->_app_table = $app_table;

        return $this;
    }
    /**
     * 获取评论的种类，用于评论的Tab
     * @param array $map 查询条件
     * @return array 评论种类与其资源数目
     */
    public function getTab($map)
    {
        return $this->field('COUNT(1) AS `nums`, `table`')
                    ->where($map)
                    ->group('`table`')
                    ->getHashList('table', 'nums');
        // return $list;
    }

    public function getTabForApp($map)
    {
        $list = $this->field('COUNT(1) AS `nums`, `app`')->where($map)->group('`app`')->getHashList('app', 'nums');
        foreach ($list as $key => $value) {
            if ($key == 'public') {
                $list['feed'] = $value;
                unset($list['public']);
                break;
            }
        }

        return $list;
    }

    /**
     * 获取评论列表，已在后台被使用
     * @param  array  $map     查询条件
     * @param  string $order   排序条件，默认为comment_id ASC
     * @param  int    $limit   结果集数目，默认为10
     * @param  bool   $isReply 是否显示回复信息
     * @return array  评论列表信息
     */
    public function getCommentList($map = null, $order = 'comment_id ASC', $limit = 10, $isReply = false)
    {
        !$map['app'] && $this->_app && ($map['app'] = $this->_app);
        !$map['table'] && $this->_app_table && ($map['table'] = $this->_app_table);
        !isset($map['is_del']) && ($map['is_del'] = 0);
        $data = $this->where($map)->order($order)->findPage($limit);
       // dump($data);exit;
        foreach ($data['data'] as $k => &$v) {
            if (!empty($v['to_comment_id']) && $isReply) {
                $replyInfo = $this->getCommentInfo($v['to_comment_id'], false);
                $v['replyInfo'] = '//@{uid='.$replyInfo['user_info']['uid'].'|'.$replyInfo['user_info']['uname'].'}：'.$replyInfo['content'];
            }
            $v['user_info'] = model('User')->getUserInfo($v['uid']);
            $groupData = static_cache('groupdata'.$v['uid']);
            if (!$groupData) {
                $groupData = model('UserGroupLink')->getUserGroupData($v['uid']);
                if (!$groupData) {
                    $groupData = 1;
                }
                static_cache('groupdata'.$v['uid'], $groupData);
            }
            $v['user_info']['groupData'] = $groupData;   //获取用户组信息
            $v['content'] = parse_html($v['content'].$v['replyInfo']);
            $v['content'] = formatEmoji(false, $v['content']); // 解析emoji
            $v['sourceInfo'] = model('Source')->getCommentSource($v);
            //$v['data'] = unserialize($v['data']);
            $order = strtolower($order);
            if (strpos($order, 'desc')) {
                $v['storey'] = $data['count'] - $k - ($data['nowPage'] - 1) * $limit;
            } else {
                $v['storey'] = $k + 1 + ($data['nowPage'] - 1) * $limit;
            }
            $v['client_type'] = getFromClient($v['client_type'], $v['app']);
        }

        return $data;
    }

    /**
     * 获取评论信息
     * @param  int   $id     评论ID
     * @param  bool  $source 是否显示资源信息，默认为true
     * @return array 获取评论信息
     */
    public function getCommentInfo($id, $source = true)
    {
        $id = intval($id);
        if (empty($id)) {
            $this->error = L('PUBLIC_WRONG_DATA');        // 错误的参数
            return false;
        }
        if ($info = static_cache('comment_info_'.$id)) {
            return $info;
        }
        $map['comment_id'] = $id;
        $info = $this->where($map)->find();
        $info['user_info'] = model('User')->getUserInfo($info['uid']);
        $info['content'] = $info['content'];
        /* 解析出emoji */
        $info['content'] = formatEmoji(false, $info['content']);
        $source && $info['sourceInfo'] = model('Source')->getCommentSource($info);

        static_cache('comment_info_'.$id, $info);

        return $info;
    }

    /**
     * 添加评论操作
     * @param  array $data     评论数据
     * @param  bool  $forApi   是否用于API，默认为false
     * @param  bool  $notCount 是否统计到未读评论
     * @param  array $lessUids 除去@用户ID
     * @return bool  是否添加评论成功
     */
    public function addComment($data, $forApi = false, $notCount = false, $lessUids = null)
    {

        // 判断用户是否登录
        if (!$GLOBALS['ts']['mid']) {
            $this->error = L('PUBLIC_REGISTER_REQUIRED');         // 请先登录
            return false;
        }
        if (isSubmitLocked()) {
            $this->error = '发布内容过于频繁，请稍后再试！';

            return false;
        }

        /* # 将Emoji编码 */
        $data['content'] = formatEmoji(true, $data['content']);
        //检测评论来源
        // $comment_from = $data['from'];

        // 检测数据安全性
        // unset($data['from']);
        $add = $this->_escapeData($data);
        if ($add['content'] === '') {
            $this->error = L('PUBLIC_COMMENT_CONTENT_REQUIRED');        // 评论内容不可为空
            return false;
        }
        $add['is_del'] = 0;
        //判断是否先审后发
        $filterStatus = filter_words($add['content']);
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $weibo_premission = $weiboSet['weibo_premission'];
        if (in_array('audit', $weibo_premission) || CheckPermission('core_normal', 'feed_audit') || $filterStatus['type'] == 2) {
            $add['is_audit'] = 0;
        } else {
            $add['is_audit'] = 1;
        }
        $add['client_ip'] = get_client_ip();
        $add['client_port'] = get_client_port();
        if ($res = $this->add($add)) {
            //锁定发布
            lockSubmit();

            //添加楼层信息 弃用 20130607
/*             $storeyCount = $this->where("table='".$add['table']."' and row_id=".$data['row_id'].' and comment_id<'.$res)->count();
            $this->where('comment_id='.$res)->setField('storey',$storeyCount+1); */
            if (!$add['is_audit']) {
                $touid = D('user_group_link')->where('user_group_id=1')->field('uid')->findAll();
                $touidArr = getSubByKey($touid, 'uid');
                model('Notify')->sendNotify($touidArr, 'comment_audit');
            }
            // 获取排除@用户ID
            $lessUids[] = intval($data['app_uid']);
            !empty($data['to_uid']) && $lessUids[] = intval($data['to_uid']);
            // 获取用户发送的内容，仅仅以//进行分割
            $scream = explode('//', $data['content']);
            model('Atme')->setAppName('Public')->setAppTable('comment')->addAtme(trim($scream[0]), $res, null, $lessUids);
            // 被评论内容的“评论统计数”加1，同时可检测出app，table，row_id的有效性
            $pk = D($add['table'])->getPk();
            $where = "`{$pk}`={$add['row_id']}";
            D($add['table'])->setInc('comment_count', $where);

            D($add['app'])->setInc('commentCount', $where);
            D($add['app'])->setInc('comment_all_count', $where);
            //评论时间
            M($add['app'])->where('feed_id='.$add['row_id'])->setField('rTime', time());
            // 给应用UID添加一个未读的评论数 原作者
            if ($GLOBALS['ts']['mid'] != $add['app_uid'] && $add['app_uid'] != '' && $add['app_uid'] != $add['to_uid']) {
                // !$notCount && model('UserData')->updateKey('unread_comment', 1, true, $add['app_uid']);
                /* 如果是微吧 */
                if (!$notCount and $add['app'] == 'weiba') {
                    //model('UserData')->updateKey('unread_comment_weiba', 1, true, $add['app_uid']);
                    model('UserData')->updateKey('unread_comment', 1, true, $add['app_uid']);
                } elseif (!$notCount) {
                    model('UserData')->updateKey('unread_comment', 1, true, $add['app_uid']);
                }
            }
            // 回复发送提示信息
            if (!empty($add['to_uid']) && $add['to_uid'] != $GLOBALS['ts']['mid']) {
                // !$notCount && model('UserData')->updateKey('unread_comment', 1, true, $add['to_uid']);
                /* 如果是微吧 */
                if (!$notCount and $add['app'] == 'weiba') {
                    //model('UserData')->updateKey('unread_comment_weiba', 1, true, $add['to_uid']);
                    model('UserData')->updateKey('unread_comment', 1, true, $add['to_uid']);
                } elseif (!$notCount) {
                    model('UserData')->updateKey('unread_comment', 1, true, $add['to_uid']);
                }
            }
            // 加积分操作
            if ($add['table'] == 'feed') {
                model('Credit')->setUserCredit($GLOBALS['ts']['mid'], 'comment_weibo');
                model('Credit')->setUserCredit($data['app_uid'], 'commented_weibo');
                model('Feed')->cleanCache($add['row_id']);
            }
            // 发邮件
             if ($add['to_uid'] != $GLOBALS['ts']['mid'] || $add['app_uid'] != $GLOBALS['ts']['mid'] && $add['app_uid'] != '') {
                 $author = model('User')->getUserInfo($GLOBALS['ts']['mid']);
                 $config['name'] = $author['uname'];
                 $config['space_url'] = $author['space_url'];
                 $config['face'] = $author['avatar_small'];
                 $sourceInfo = model('Source')->getCommentSource($add, $forApi);
                 $config['content'] = parse_html($add['content']);
                 $config['ctime'] = date('Y-m-d H:i:s', time());
                 $config['sourceurl'] = $sourceInfo['source_url'];
                 $config['source_content'] = parse_html($sourceInfo['source_content']);
                 $config['source_ctime'] = isset($sourceInfo['ctime']) ? date('Y-m-d H:i:s', $sourceInfo['ctime']) : date('Y-m-d H:i:s');
                 if (!empty($add['to_uid'])) {
                     // 回复
                    $config['comment_type'] = '回复 我 的评论:';
                     model('Notify')->sendNotify($add['to_uid'], 'comment', $config);
                 } else {
                     // 评论
                    $config['comment_type'] = '评论 我 的分享:';
                     if (!empty($add['app_uid'])) {
                         model('Notify')->sendNotify($add['app_uid'], 'comment', $config);
                     }
                 }
             }
        }

        $this->error = $res ? L('PUBLIC_CONCENT_IS_OK') : L('PUBLIC_CONCENT_IS_ERROR');         // 评论成功，评论失败

        return $res;
    }

    /**
     * 将指定用户的评论，全部设置为已读
     * @param int $uid 用户UID
     */
    public function setUnreadCountToZero($uid)
    {
        // TODO:更新全局统计表
    }

    /**
     * 获取指定用户的评论，未读评论数
     * @param int $uid 用户UID
     */
    public function getUnreadCount($uid)
    {
        // TODO:查询全局统计表
    }

    /**
     * 删除评论
     * @param  array $app_name 评论所属应用   积分加减时用到
     * @param  array $ids      评论ID数组
     * @param  int   $uid      用户UID
     * @return bool  是否删除评论成功
     */
    public function deleteComment($ids, $uid = null, $app_name = 'public')
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        $map = array();
        $map['comment_id'] = array('IN', $ids);
        $comments = $this->field('comment_id, app,`table`, row_id, app_uid, uid')->where($map)->findAll();
        if (empty($comments)) {
            return false;
        }
        // 删除@信息
        foreach ($comments as $value) {
            model('Atme')->setAppName('Public')->setAppTable('comment')->deleteAtme(null, $value['comment_id'], null);
        }

           // 应用回调，减少应用的评论计数
        // 已优化: 先统计出哪篇应用需要减几, 然后再减. 这样可以有效减少数据库操作次数
           $_comments = array();
           // 统计各table、row_id对应的评论
           foreach ($comments as $c_k => $c_v) {
               // 如果此条评论不属于指定用户[发布者或被评论的内容的作者]，则不可操作  《=有管理权限的也可以做
/*       	    if (isset($uid) && !in_array($uid, array($c_v['app_uid'], $c_v['uid']))) {
                unset($comments[$c_k]);
                       continue;
            }*/
            if ($c_v['app'] == 'public') {
                $_comments[$c_v['table']][$c_v['row_id']][] = $c_v['comment_id'];
            } else {
                $tmp = model('Feed')->getFeedInfo($c_v['row_id']);
                $_comments[$c_v['app']][$tmp['app_row_id']] = $c_v['comment_id'];
            }

            //同步删除微吧评论
            if ($c_v['app'] == 'weiba') {
                $post_id = D('weiba_reply')->where('comment_id='.$c_v['comment_id'])->getField('post_id');
                D('weiba_post')->where('post_id='.$post_id)->setDec('reply_count');
                D('weiba_reply')->where('comment_id='.$c_v['comment_id'])->delete();
            }
           }
        // 删除评论：先删除评论，在处理统计
        $map = array();
        $map['comment_id'] = array('IN', getSubByKey($comments, 'comment_id'));
        $data = array('is_del' => 1);
        $res = $this->where($map)->save($data);

        if ($res) {
            // 更新统计数目
               foreach ($_comments as $_c_k => $_c_v) {
                   foreach ($_c_v as $_c_v_k => $_c_v_v) {
                       // 应用表格“评论统计”统一使用comment_count字段名
                    $field = D($_c_k)->getPK();
                       if (empty($field)) {
                           $field = $_c_k.'_id';
                       }
                       D($_c_k)->setDec('comment_count', "`{$field}`={$_c_v_k}", count($_c_v_v));
                    //兼容旧app评论
                    D($_c_k)->setDec('commentCount', "`{$field}`={$_c_v_k}", count($_c_v_v));
                       D($_c_k)->setDec('comment_all_count', "`{$field}`={$_c_v_k}", count($_c_v_v));
                    //dump(D($_c_k)->getLastSql());
                    if ($app_name == 'feed' || $app_name == 'public') {
                        model($_c_k)->cleanCache($_c_v_k);
                    }
                   }
               }

            //删除积分
            if ($app_name == 'weiba') {
                model('Credit')->setUserCredit($uid, 'delete_topic_comment');
            }
            if ($app_name == 'public') {
                model('Credit')->setUserCredit($uid, 'delete_weibo_comment');
            }
        }

        $this->error = $res != false ? L('PUBLIC_ADMIN_OPRETING_SUCCESS') : L('PUBLIC_ADMIN_OPRETING_ERROR');       // 操作成功，操作失败

           return $res;
    }

    /**
     * 评论处理方法，包含彻底删除、假删除与恢复功能
     * @param int    $id   评论ID
     * @param string $type 操作类型，delComment假删除、deleteComment彻底删除、commentRecover恢复
     * @param  string $title 提示语言所附加的内容
     * @return array 评论处理后，返回的数组操作信息
     */
    public function doEditComment($id, $type, $title)
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));           // 操作成功
        if (empty($id)) {
            $return['data'] = L('PUBLIC_WRONG_DATA');            // 错误的参数
        } else {
            $map['comment_id'] = is_array($id) ? array('IN', $id) : intval($id);
            $save['is_del'] = $type == 'delComment' ? 1 : 0;
            if ($type == 'deleteComment') {
                $res = $this->where($map)->delete();
            } else {
                if ($type == 'commentRecover') {
                    $res = $this->commentRecover($id);
                } else {
                    $res = $this->deleteComment($id);
                }
            }
            if ($res != false) {
                empty($title) && $title = L('PUBLIC_CONCENT_IS_OK');
                $return = array('status' => 1, 'data' => $title);          // 评论成功
            }
        }

        return $return;
    }

    /**
     * 评论恢复操作
     * @param  int  $id 评论ID
     * @return bool 评论是否恢复成功
     */
    public function commentRecover($id)
    {
        if (empty($id)) {
            return false;
        }
        $map['comment_id'] = $id;
        $comment = $this->field('comment_id, app,`table`, row_id, app_uid, uid')->where($map)->find();
        $save['is_del'] = 0;
        if ($this->where($map)->save($save)) {
            D($comment['table'])->setInc('comment_count', '`'.$comment['table'].'_id`='.$comment['row_id']);
            // 删除分享缓存
            switch ($comment['table']) {
                case 'feed':
                    $feedIds = $this->where($map)->getAsFieldArray('row_id');
                    model('Feed')->cleanCache($feedIds);
                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * 审核通过评论
     * @param  int   $comment_id 评论ID
     * @return array 评论操作后的结果信息数组
     */
    public function doAuditComment($comment_id)
    {
        $return = array('status' => '0');
        if (empty($comment_id)) {
            $return['data'] = '请选择评论！';
        } else {
            $map['comment_id'] = is_array($comment_id) ? array('IN', $comment_id) : intval($comment_id);
            $save['is_audit'] = 1;
            $res = $this->where($map)->save($save);
            if ($res) {
                $return = array('status' => 1);
            }
        }

        return $return;
    }

    /**
     * 检测数据安全性
     * @param  array $data 待检测的数据
     * @return array 验证后的数据
     */
    private function _escapeData($data)
    {
        $add['type'] = !$data['type'] ? 1 : $data['type'];
        $add['app'] = !$data['app'] ? $this->_app : $data['app'];
        $add['table'] = !$data['table'] ? $this->_app_table : $data['table'];
        $add['row_id'] = intval($data['row_id']);
        $add['app_uid'] = intval($data['app_uid']);
        $add['uid'] = $GLOBALS['ts']['mid'];
        $add['content'] = preg_html($data['content']);
        $add['to_comment_id'] = intval($data['to_comment_id']);
        $add['to_uid'] = intval($data['to_uid']);
        $add['data'] = serialize($data['data']);
        $add['ctime'] = $_SERVER['REQUEST_TIME'];
        $add['client_type'] = isset($data['client_type']) ? intval($data['client_type']) : getVisitorClient();
        $add['app_detail_summary'] = t($data['app_detail_summary']);
        $add['app_detail_url'] = $data['app_detail_url'];

        return $add;
    }

    /*** API使用 ***/
    /**
     * 获取评论列表，API使用
     * @param  string $where    查询条件
     * @param  int    $since_id 主键起始ID，默认为0
     * @param  int    $max_id   主键最大ID，默认为0
     * @param  int    $limit    每页结果集数目，默认为20
     * @param  int    $page     页数，默认为1
     * @param  bool   $source   是否获取资源信息，默认为false
     * @return array  评论列表数据
     */
    public function getCommentListForApi($where = '', $since_id = 0, $max_id = 0, $limit = 20, $page = 1, $source = false)
    {
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = empty($where) ? ' is_del = 0 ' : $where.' AND is_del=0';
        if (!empty($since_id) || !empty($max_id)) {
            !empty($since_id) && $where .= " AND comment_id > {$since_id}";
            !empty($max_id) && $where .= " AND comment_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $data = $this->where($where)->order('comment_id DESC')->limit("$start, $end")->findAll();
        foreach ($data as &$v) {
            $v['user_info'] = model('User')->getUserInfo($v['uid']);
            $v['content'] = parseForApi($v['content']);
            $v['ctime'] = date('Y-m-d H:i', $v['ctime']);
            $source && $v['sourceInfo'] = model('Source')->getCommentSource($v, true);
            /* 解析出emoji */
            $v['content'] = formatEmoji(false, $v['content']);
        }

        is_null($data) && $data = array();

        return $data;
    }

    /**
     * 设置资源评论的绝对楼层
     * @param  int    $rowId 资源ID
     * @param  string $app   应用名称
     * @param  string $table 资源表名称
     * @param  bool   $inc   是否自增，默认为true
     * @return int    楼层ID
     */
    public function getStorey($rowId, $app, $table, $inc = true)
    {
        $map[$table.'_id'] = $rowId;
        $data = model(ucfirst($table))->where($map)->getField('comment_all_count');
        $inc && $data++;

        return $data;
    }
}
