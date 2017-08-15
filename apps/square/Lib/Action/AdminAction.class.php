 <?php
/**
 * 后台，用户管理控制器.
 *
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 *
 * @version TS3.0
 */
// 加载后台控制器
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminAction extends AdministratorAction
{
    public $pageTitle = array();

    /**
     * 初始化，初始化页面表头信息，用于双语.
     */
    public function _initialize()
    {
        $this->pageTitle['index'] = '微吧列表';
        //$this->pageTitle['addWeiba'] = '添加微吧';
        $this->pageTitle['weibaCate'] = '微吧分类';
        $this->pageTitle['addWeibaCate'] = '添加分类';
        $this->pageTitle['editWeibaCate'] = '编辑分类';
        $this->pageTitle['postList'] = '帖子列表';
        $this->pageTitle['postRecycle'] = '帖子回收站';
        $this->pageTitle['weibaAdminAuditConfig'] = '申请圈主配置';
        $this->pageTitle['weibaAdminAudit'] = '圈主审核';
        $this->pageTitle['weibaAuditConfig'] = '申请微吧配置';
        $this->pageTitle['weibaAudit'] = '微吧审核';
        parent::_initialize();
    }

    /**
     * 微吧列表.
     */
    public function index()
    {
        if (isset($_POST['editSubmit']) == '1') {
            array_map('h', $_POST);
            $res = model('Xdata')->lput('square', $_POST);
            if ($res) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }

        //model('Xdata')->lput('square', $this->GroupSetting->getGroupSetting());
        $setting = model('Xdata')->lget('square');

        $this->assign('setting', $setting);
        $this->display();
    }

    /**
     * 添加微吧.
     */
    public function addWeiba()
    {
        // 初始化微吧列表管理菜单
        $this->_initWeibaListAdminMenu();
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('weiba_name', 'cid', 'logo', 'intro', 'who_can_post', 'admin_uid', 'recommend');
        $this->opt['who_can_post'] = array('0' => '所有人', '1' => '吧内成员', 2 => '微吧管理员', 3 => '微吧圈主');
        $this->opt['recommend'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $list = D('WeibaCategory')->getAllWeibaCate();
        $this->opt['cid'] = $list;
        // 表单URL设置
        $this->savePostUrl = U('weiba/Admin/doAddWeiba');
        $this->notEmpty = array('weiba_name', 'cid', 'logo', 'intro');
        $this->onsubmit = 'admin.checkAddWeiba(this)';
        $this->displayConfig();
    }

    /**
     * 执行添加微吧.
     */
    public function doAddWeiba()
    {
        //dump($_POST);exit;
        $data['weiba_name'] = t($_POST['weiba_name']);
        $data['is_del'] = 0;
        if (D('weiba')->where($data)->find()) {
            $this->error('此微吧已存在');
        }
        $data['cid'] = intval($_POST['cid']);
        if (empty($data['cid'])) {
            $this->error('微吧分类不能为空');
        }
        $data['uid'] = $this->mid;
        $data['ctime'] = time();
        $data['logo'] = t($_POST['logo']);
        $data['intro'] = $_POST['intro'];
        $data['who_can_post'] = intval($_POST['who_can_post']);
        if ($_POST['admin_uid']) {
            $data['admin_uid'] = t($_POST['admin_uid']);
            $data['follower_count'] = 1;
        }
        $data['recommend'] = intval($_POST['recommend']);
        $data['status'] = 1;
        $res = D('Weiba', 'weiba')->add($data);
        if ($res) {
            if ($_POST['admin_uid']) {      //超级圈主加入微吧
                $follow['follower_uid'] = $data['admin_uid'];
                $follow['weiba_id'] = $res;
                $follow['level'] = 3;
                D('weiba_follow')->add($follow);
            }
            if ($data['admin_uid'] != $this->mid) {    //创建者加入微吧
                $follows['follower_uid'] = $this->mid;
                $follows['weiba_id'] = $res;
                $follows['level'] = 1;
                D('weiba_follow')->add($follows);
                D('weiba')->where('weiba_id='.$res)->setInc('follower_count');
            }
            $this->assign('jumpUrl', U('weiba/Admin/index'));
            $this->success(L('PUBLIC_ADD_SUCCESS'));
        } else {
            $this->error(D('Weiba', 'weiba')->getLastError());
        }
    }

    /**
     * 编辑微吧.
     */
    public function editWeiba()
    {
        $this->assign('pageTitle', '编辑微吧');
        // 初始化微吧列表管理菜单
        // 		$this->_initWeibaListAdminMenu();
        $this->pageTab[] = array('title' => '微吧列表', 'tabHash' => 'index', 'url' => U('weiba/Admin/index'));
        //$this->pageTab[] = array('title'=>'添加微吧','tabHash'=>'addWeiba','url'=>U('weiba/Admin/addWeiba'));
        $this->pageTab[] = array('title' => '微吧分类', 'tabHash' => 'weibaCate', 'url' => U('weiba/Admin/weibaCate'));
        $this->pageTab[] = array('title' => '编辑微吧', 'tabHash' => 'editWeiba', 'url' => U('weiba/Admin/editWeiba', array('weiba_id' => $_GET['weiba_id'])));
        $this->pageTab[] = array('title' => '帖子列表', 'tabHash' => 'postList', 'url' => U('weiba/Admin/postList'));
        $this->pageTab[] = array('title' => '帖子回收站', 'tabHash' => 'postRecycle', 'url' => U('weiba/Admin/postRecycle'));
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('weiba_id', 'weiba_name', 'cid', 'logo', 'intro', 'notify', 'who_can_post', 'admin_uid', 'recommend');
        $list = D('WeibaCategory')->getAllWeibaCate();
        $this->opt['cid'] = $list;
        $this->opt['who_can_post'] = array('0' => '所有人', '1' => '吧内成员', 2 => '微吧管理员', 3 => '微吧圈主');
        $this->opt['recommend'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $weiba_id = intval($_GET['weiba_id']);
        $data = D('weiba', 'weiba')->getWeibaById($weiba_id);
        if (!$data['admin_uid']) {
            $data['admin_uid'] = '';
        }
        // 表单URL设置
        $this->savePostUrl = U('weiba/Admin/doEditWeiba');
        $this->notEmpty = array('weiba_name', 'cid', 'logo', 'intro');
        $this->onsubmit = 'admin.checkAddWeiba(this)';

        $this->displayConfig($data);
    }

    /**
     * 执行编辑微吧.
     */
    public function doEditWeiba()
    {
        $weiba_id = intval($_POST['weiba_id']);
        $data['weiba_name'] = t($_POST['weiba_name']);
        $map['weiba_id'] = array('neq', $weiba_id);
        $map['weiba_name'] = $data['weiba_name'];
        $map['is_del'] = 0;
        if (D('weiba')->where($map)->find()) {
            $this->error('此微吧已存在');
        }
        //$data['uid'] = $this->mid;
        $data['cid'] = intval($_POST['cid']);
        if (empty($data['cid'])) {
            $this->error('微吧分类不能为空');
        }
        $data['logo'] = t($_POST['logo']);
        $data['intro'] = $_POST['intro'];
        $data['notify'] = t($_POST['notify']);
        $data['who_can_post'] = t($_POST['who_can_post']);
        $data['admin_uid'] = t($_POST['admin_uid']);
        $data['recommend'] = intval($_POST['recommend']);
        $res = D('weiba')->where('weiba_id='.$weiba_id)->save($data);
        if ($res !== false) {
            //现有超级圈主
            $follow['level'] = 3;
            $follow['weiba_id'] = $weiba_id;
            $admin_uid = D('weiba_follow')->where($follow)->getField('follower_uid');
            if ($admin_uid && $admin_uid != $data['admin_uid']) {  //如果存在圈主并且设置了新圈主，则原圈主降为普通成员
                $a['follower_uid'] = $admin_uid;
                $a['weiba_id'] = $weiba_id;
                D('weiba_follow')->where($a)->setField('level', 1);
            }
            if ($data['admin_uid']) {
                $follows['follower_uid'] = $data['admin_uid'];
                $follows['weiba_id'] = $weiba_id;
                if (D('weiba_follow')->where($follows)->find()) {  //该圈主已经为成员
                    D('weiba_follow')->where($follows)->where($follows)->setField('level', 3);
                } else {
                    $follows['level'] = 3;
                    D('weiba_follow')->add($follows);
                }
            }
            $this->assign('jumpUrl', U('weiba/Admin/index'));
            $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
        } else {
            $this->error(D('weiba')->getLastError());
        }
    }

    /**
     * 微吧分类列表.
     */
    public function weibaCate()
    {
        // 初始化微吧列表管理菜单
        $this->_initWeibaListAdminMenu();
        $this->pageKeyList = array('id', 'name', 'DOACTION');
        $this->pageButton[] = array('title' => '添加分类', 'onclick' => "javascript:location.href='".U('weiba/Admin/addWeibaCate', array('tabHash' => 'weibaCate'))."';");
        $this->pageButton[] = array('title' => '删除分类', 'onclick' => 'admin.delWeibaCate()');
        $list = D('weiba_category')->findPage();
        foreach ($list['data'] as &$v) {
            $v['DOACTION'] = "<a href='".U('weiba/Admin/editWeibaCate', array('id' => $v['id'], 'tabHash' => 'weibaCate'))."'>编辑</a> <a href='javascript:;' onclick='admin.delWeibaCate(".$v['id'].")'>删除</a>";
        }
        $this->displayList($list);
    }

    /**
     * 添加微吧分类页面.
     */
    public function addWeibaCate()
    {
        // 初始化微吧列表管理菜单
        $this->_initWeibaListAdminMenu();
        $this->pageKeyList = array('name');
        $this->savePostUrl = U('weiba/Admin/doAddWeibaCate');
        $this->displayConfig();
    }

    /**
     * 添加微吧分类数据.
     */
    public function doAddWeibaCate()
    {
        $name = t($_POST['name']);
        if ($name) {
            $data['name'] = $name;
            $exist = D('WeibaCategory')->where($data)->find();
            if ($exist) {
                $this->error('已存在相同分类！');
            }
            $res = D('WeibaCategory')->add($data);
            if ($res) {
                $this->assign('jumpUrl', U('weiba/Admin/weibaCate', array('tabHash' => 'weibaCate')));
                $this->success('添加成功');
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->error('分类不能为空');
        }
    }

    /**
     * 编辑微吧分类页面.
     */
    public function editWeibaCate()
    {
        // 初始化微吧列表管理菜单
        $this->_initWeibaListAdminMenu();
        $id = intval($_GET['id']);
        $data = D('WeibaCategory')->where('id='.$id)->find();
        $this->pageKeyList = array('id', 'name');
        $this->savePostUrl = U('weiba/Admin/doEditWeibaCate');
        $this->displayConfig($data);
    }

    /**
     * 编辑微吧分类数据.
     */
    public function doEditWeibaCate()
    {
        $name = t($_POST['name']);
        $id = intval($_POST['id']);
        if ($name) {
            $data['name'] = $name;
            $map['name'] = $name;
            $map['id'] = array('neq', $id);
            $exist = D('WeibaCategory')->where($map)->find();
            if ($exist) {
                $this->error('已存在相同分类！');
            }
            $res = D('WeibaCategory')->where('id='.$id)->save($data);
            if ($res) {
                $this->assign('jumpUrl', U('weiba/Admin/weibaCate', array('tabHash' => 'weibaCate')));
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        } else {
            $this->error('分类不能为空');
        }
    }

    /**
     * 删除微吧分类.
     */
    public function delWeibaCate()
    {
        $ids = $_POST['cate_id'];
        if ($ids) {
            !is_array($ids) && array($ids);
            $map['id'] = array('in', $ids);
            $wmap['cid'] = array('in', $ids);
            $exist = D('Weiba')->where($wmap)->find();
            if ($exist) {
                $return['status'] = 0;
                $return['data'] = '删除失败，该分类下还有微吧！';
                exit(json_encode($return));
            }
            $res = D('WeibaCategory')->where($map)->delete();
            if ($res) {
                $return['status'] = 1;
                $return['data'] = '删除成功';
            } else {
                $return['status'] = 0;
                $return['data'] = '删除失败';
            }
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 设置微吧推荐状态
     *
     * @return array 操作成功状态和提示信息
     */
    public function setRecommend()
    {
        if (empty($_POST['weiba_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        if (intval($_POST['type']) == 1) {
            $value = 0;
        } else {
            $value = 1;
        }
        $weiba_id = intval($_POST['weiba_id']);
        $result = D('weiba')->where('weiba_id='.$weiba_id)->setField('recommend', $value);
        $uid = D('weiba')->where('weiba_id='.$weiba_id)->getField('uid');
        //添加积分
        if ($value == 1) {
            model('Credit')->setUserCredit($uid, 'recommended_weiba');
        }

        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 解散微吧.
     *
     * @return array 操作成功状态和提示信息
     */
    public function delWeiba()
    {
        if (empty($_POST['weiba_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        !is_array($_POST['weiba_id']) && $_POST['weiba_id'] = array($_POST['weiba_id']);
        $data['weiba_id'] = array('in', $_POST['weiba_id']);
        $result = D('weiba')->where($data)->setField('is_del', 1);
        if ($result) {
            // D('weiba_post')->where('weiba_id='.$weiba_id)->delete();
            // D('weiba_reply')->where('weiba_id='.$weiba_id)->delete();
            // D('weiba_follow')->where('weiba_id='.$weiba_id)->delete();
            // D('weiba_log')->where('weiba_id='.$weiba_id)->delete();
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 后台帖子列表.
     */
    public function postList()
    {
        $_REQUEST['tabHash'] = 'postList';
        $this->_initWeibaListAdminMenu();
        // 设置列表主键
        $this->_listpk = 'post_id';
        $this->pageButton[] = array('title' => '搜索帖子', 'onclick' => "admin.fold('search_form')");
        // $this->pageButton[] = array('title'=>'调整回复楼层','onclick'=>"admin.doStorey()");
        $this->pageButton[] = array('title' => '删除帖子', 'onclick' => 'admin.delPost()');
        $this->searchKey = array('post_id', 'title', 'post_uid', 'recommend', 'digest', 'top', 'weiba_id');
        $this->opt['recommend'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => '是', '2' => '否');
        $this->opt['digest'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => '是', '2' => '否');
        $this->opt['top'] = array('0' => L('PUBLIC_SYSTEMD_NOACCEPT'), '1' => '吧内置顶', '2' => '全局置顶');
        $weibaList = D('weiba')->getHashList($k = 'weiba_id', $v = 'weiba_name');
        $weibaList[0] = L('PUBLIC_SYSTEMD_NOACCEPT');
        $this->opt['weiba_id'] = $weibaList;
        $this->pageKeyList = array('post_id', 'title', 'post_uid', 'post_time', 'last_reply_time', 'read_count/reply_count', 'weiba_id', 'DOACTION');
        // 数据的格式化与listKey保持一致
        $listData = D('Weiba', 'weiba')->getPostList(20, array('is_del' => 0));
        $this->displayList($listData);
    }

    /**
     * 帖子回收站.
     */
    public function postRecycle()
    {
        $_REQUEST['tabHash'] = 'postRecycle';
        $this->_initWeibaListAdminMenu();
        // 设置列表主键
        $this->_listpk = 'post_id';
        $this->pageButton[] = array('title' => '搜索帖子', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '还原', 'onclick' => 'admin.recoverPost()');
        $this->pageButton[] = array('title' => '彻底删除', 'onclick' => 'admin.deletePost()');
        $this->searchKey = array('post_id', 'title', 'post_uid', 'weiba_id');
        $weibaList = D('weiba')->getHashList($k = 'weiba_id', $v = 'weiba_name');
        $weibaList[0] = L('PUBLIC_SYSTEMD_NOACCEPT');
        $this->opt['weiba_id'] = $weibaList;
        $this->pageKeyList = array('post_id', 'title', 'post_uid', 'post_time', 'last_reply_time', 'read_count/reply_count', 'weiba_id', 'DOACTION');
        // 数据的格式化与listKey保持一致
        $listData = D('Weiba', 'weiba')->getPostList(20, array('is_del' => 1));
        $this->displayList($listData);
    }

    /**
     * 设置帖子状态
     *
     * @return array 操作成功状态和提示信息
     */
    public function setPost()
    {
        if (empty($_POST['post_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        $post_detail = D('weiba_post')->where('post_id='.$post_id)->find();
        switch ($_POST['type']) {
            case '1':         //推荐
                $field = 'recommend';
                if (intval($_POST['curValue']) == 1) {
                    $value = 0;
                } else {
                    $value = 1;
                }
                break;
            case '2':         //精华
                $field = 'digest';
                if (intval($_POST['curValue']) == 1) {
                    $value = 0;
                } else {
                    $value = 1;
                }
                break;
            case '3':         //置顶
                $field = 'top';
                if (intval($_POST['curValue']) == intval($_POST['topValue'])) {
                    $value = 0;
                } else {
                    $value = intval($_POST['topValue']);
                }
                break;
        }
        $post_id = intval($_POST['post_id']);
        $result = D('weiba_post')->where('post_id='.$post_id)->setField($field, $value);
        if (!$result) {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        } else {
            $post_detail = D('weiba_post')->where('post_id='.$post_id)->find();
            $config['post_name'] = $post_detail['title'];
            $config['post_url'] = '<a href="'.U('weiba/Index/postDetail', array('post_id' => $post_id)).'" target="_blank">'.$post_detail['title'].'</a>';
            switch ($_POST['type']) {
                case '1':         //推荐
                    //添加积分
                    if ($value == 1) {
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'recommend_topic');
                    }
                    break;
                case '2':         //精华
                    if ($value == 1) {
                        $config['typename'] = '精华';
                        model('Notify')->sendNotify($post_detail['post_uid'], 'weiba_post_set', $config);
                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'dist_topic');
                    }

                    break;
                case '3':         //置顶
                    if ($value == 1) {
                        $config['typename'] = '吧内置顶';
                        model('Notify')->sendNotify($post_detail['post_uid'], 'weiba_post_set', $config);
                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'top_topic_weiba');
                    } elseif ($value == 2) {
                        $config['typename'] = '全局置顶';
                        model('Notify')->sendNotify($post_detail['post_uid'], 'weiba_post_set', $config);
                        //添加积分
                        model('Credit')->setUserCredit($post_detail['post_uid'], 'top_topic_all');
                    }
                    break;
            }
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 后台编辑帖子.
     */
    public function editPost()
    {
        $this->assign('pageTitle', '编辑帖子');
        // 初始化微吧列表管理菜单
        $this->pageTab[] = array('title' => '微吧列表', 'tabHash' => 'index', 'url' => U('weiba/Admin/index'));
        //$this->pageTab[] = array('title'=>'添加微吧','tabHash'=>'addWeiba','url'=>U('weiba/Admin/addWeiba'));
        $this->pageTab[] = array('title' => '微吧分类', 'tabHash' => 'weibaCate', 'url' => U('weiba/Admin/weibaCate'));
        $this->pageTab[] = array('title' => '帖子列表', 'tabHash' => 'postList', 'url' => U('weiba/Admin/postList'));
        $this->pageTab[] = array('title' => '编辑帖子', 'tabHash' => 'editPost', 'url' => U('weiba/Admin/editPost', array('post_id' => $_GET['post_id'])));
        $this->pageTab[] = array('title' => '帖子回收站', 'tabHash' => 'postRecycle', 'url' => U('weiba/Admin/postRecycle'));
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('post_id', 'title', 'content', 'recommend', 'digest', 'top');
        $this->opt['recommend'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['digest'] = array('1' => L('PUBLIC_SYSTEMD_TRUE'), '0' => L('PUBLIC_SYSTEMD_FALSE'));
        $this->opt['top'] = array('0' => L('PUBLIC_SYSTEMD_FALSE'), '1' => '吧内置顶', '2' => '全局置顶');
        $post_id = intval($_GET['post_id']);
        $data = D('weiba_post')->where('post_id='.$post_id)->find();
        // 表单URL设置
        $this->savePostUrl = U('weiba/Admin/doEditPost');
        $this->notEmpty = array('title', 'content');
        $this->onsubmit = 'admin.checkEditPost(this)';
        $this->displayConfig($data);
    }

    /**
     * 执行编辑帖子.
     */
    public function doEditPost()
    {
        $checkContent = str_replace('&nbsp;', '', $_POST['content']);
        $checkContent = str_replace('<br />', '', $checkContent);
        $checkContent = str_replace('<p>', '', $checkContent);
        $checkContent = str_replace('</p>', '', $checkContent);
        $checkContents = preg_replace('/<img(.*?)src=/i', 'img', $checkContent);
        if (strlen(t($_POST['title'])) == 0) {
            $this->error('帖子标题不能为空');
        }
        if (strlen(t($checkContents)) == 0) {
            $this->error('帖子内容不能为空');
        }
        $post_id = intval($_POST['post_id']);
        $data['title'] = t($_POST['title']);
        $data['content'] = h($_POST['content']);
        $data['recommend'] = intval($_POST['recommend']);
        $data['digest'] = intval($_POST['digest']);
        $data['top'] = intval($_POST['top']);
        $res = D('weiba_post')->where('post_id='.$post_id)->save($data);
        if ($res !== false) {
            //同步到分享
            $feed_id = D('weiba_post')->where('post_id='.$post_id)->getField('feed_id');
            $feedInfo = D('feed_data')->where('feed_id='.$feed_id)->find();
            $datas = unserialize($feedInfo['feed_data']);
            $datas['content'] = '【'.$data['title'].'】'.getShort(t($checkContent), 100).'&nbsp;';
            $datas['body'] = $datas['content'];
            $data1['feed_data'] = serialize($datas);
            $data1['feed_content'] = $datas['content'];
            D('feed_data')->where('feed_id='.$feed_id)->save($data1);
            model('Cache')->rm('fd_'.$feed_id);
            $this->assign('jumpUrl', U('weiba/Admin/postList', array('tabHash' => 'postList')));
            $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
        } else {
            $this->error(D('weiba_post')->getLastError());
        }
    }

    /**
     * 后台删除帖子至回收站.
     */
    public function delPost()
    {
        if (empty($_POST['post_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        !is_array($_POST['post_id']) && $_POST['post_id'] = array($_POST['post_id']);
        $data['post_id'] = array('in', $_POST['post_id']);
        $res = D('weiba_post')->where($data)->setField('is_del', 1);
        if ($res) {
            $postList = D('weiba_post')->where($data)->findAll();
            foreach ($postList as $v) {
                D('weiba')->where('weiba_id='.$v['weiba_id'])->setDec('thread_count');
            }
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 调整评论楼层
     */
    public function doStorey()
    {
        if (empty($_POST['post_id'])) {
            echo 0;
            exit();
        }
        //echo 1;exit;
        !is_array($_POST['post_id']) && $_POST['post_id'] = array($_POST['post_id']);
        $data['post_id'] = array('in', $_POST['post_id']);
        $postList = D('weiba_post')->where($data)->findAll();
        foreach ($postList as $v) {
            $replyList = D('weiba_reply')->where('post_id='.$v['post_id'].' AND is_del=0')->order('reply_id ASC')->findAll();
            foreach ($replyList as $key => $val) {
                D('weiba_reply')->where('reply_id='.$val['reply_id'])->setField('storey', $key + 1);
            }
            D('weiba_post')->where('post_id='.$v['post_id'])->setField('reply_all_count', count($replyList)); //总回复统计数加1
        }
        echo 1;
        exit;
    }

    /**
     * 后台还原帖子.
     */
    public function recoverPost()
    {
        if (empty($_POST['post_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        !is_array($_POST['post_id']) && $_POST['post_id'] = array($_POST['post_id']);
        $data['post_id'] = array('in', $_POST['post_id']);
        $res = D('weiba_post')->where($data)->setField('is_del', 0);
        if ($res) {
            $postList = D('weiba_post')->where($data)->findAll();
            foreach ($postList as $v) {
                D('weiba')->where('weiba_id='.$v['weiba_id'])->setInc('thread_count');
            }
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 后台删除帖子至回收站.
     */
    public function deletePost()
    {
        if (empty($_POST['post_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        !is_array($_POST['post_id']) && $_POST['post_id'] = array($_POST['post_id']);
        $data['post_id'] = array('in', $_POST['post_id']);
        $res = D('weiba_post')->where($data)->delete();
        if ($res) {
            D('weiba_reply')->where($data)->delete();
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 圈主审核�
     * �置.
     */
    public function weibaAdminAuditConfig()
    {
        $this->_initWeibaListAdminMenu();
        $this->pageKeyList = array('follower_open', 'follower', 'level_open', 'level', 'weiba_post_open', 'weiba_post');
        $this->savePostUrl = U('weiba/Admin/doWeibaAdminAuditConfig');
        $this->opt['follower_open'] = array('0' => '否', '1' => '是');
        $this->opt['level_open'] = array('0' => '否', '1' => '是');
        $this->opt['weiba_post_open'] = array('0' => '否', '1' => '是');
        $weibaAdminAuditConfig = model('Xdata')->get('weiba_Admin:weibaAdminAuditConfig');
        // dump($weibaAdminAuditConfig);exit;
        $this->displayConfig($weibaAdminAuditConfig);
    }

    public function doWeibaAdminAuditConfig()
    {
        $list = $_POST['systemdata_list'];
        $key = $_POST['systemdata_key'];
        $key = $list.':'.$key;
        $value['follower_open'] = intval($_POST['follower_open']);
        $value['follower'] = intval($_POST['follower']);
        $value['level_open'] = intval($_POST['level_open']);
        $value['level'] = intval($_POST['level']);
        $value['weiba_post_open'] = intval($_POST['weiba_post_open']);
        $value['weiba_post'] = intval($_POST['weiba_post']);
        $res = model('Xdata')->put($key, $value);
        if ($res) {
            $this->assign('jumpUrl', U('weiba/Admin/weibaAdminAuditConfig', array('tabHash' => 'weibaAdminAuditConfig')));
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 圈主审核.
     */
    public function weibaAdminAudit()
    {
        $_REQUEST['tabHash'] = 'weibaAdminAudit';
        $this->_initWeibaListAdminMenu();
        // 设置列表主键
        $this->_listpk = 'id';
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        // $this->pageButton[] = array('title'=>'通过','onclick'=>"admin.doAudit('', 1)");
        // $this->pageButton[] = array('title'=>'驳回','onclick'=>"admin.doAudit('', -1)");
        $this->searchKey = array('follower_uid', 'weiba_name');
        $this->pageKeyList = array('id', 'follower_uid', 'follower_uname', 'weiba_name', 'type', 'reason', 'DOACTION');
        !empty($_POST['follower_uid']) && $map['follower_uid'] = intval($_POST['follower_uid']);
        if (!empty($_POST['weiba_name'])) {
            $maps['weiba_name'] = array('like', '%'.t($_POST['weiba_name']).'%');
            $map['weiba_id'] = array('in', getSubByKey(D('weiba')->where($maps)->field('weiba_id')->findAll(), 'weiba_id'));
        }
        $map['status'] = 0;
        // 数据的格式化与listKey保持一致
        $listData = D('weiba_apply')->where($map)->findPage(20);
        foreach ($listData['data'] as $k => $v) {
            $userInfo = model('User')->getUserInfo($v['follower_uid']);
            $listData['data'][$k]['follower_uname'] = $userInfo['uname'];
            $listData['data'][$k]['weiba_name'] = D('weiba')->where('weiba_id='.$v['weiba_id'])->getField('weiba_name');
            switch ($v['type']) {
                case '2':
                    $listData['data'][$k]['type'] = '小主';
                    break;
                case '3':
                    $listData['data'][$k]['type'] = '圈主';
                    break;
            }
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.doAudit('.$v['weiba_id'].','.$v['follower_uid'].','.$v['type'].');">通过</a>&nbsp;|&nbsp;<a href="javascript:void(0)" onclick="admin.doAudit('.$v['weiba_id'].','.$v['follower_uid'].',-1);">驳回</a>';
        }
        $this->allSelected = false;
        $this->displayList($listData);
    }

    /**
     * 圈主审核通过/驳回.
     */
    public function doAudit()
    {
        if (empty($_POST['id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        !is_array($_POST['id']) && $_POST['id'] = array($_POST['id']);
        $map['id'] = array('in', $_POST['id']);
        $data['status'] = intval($_POST['val']);
        $data['manager_uid'] = $this->mid;
        $res = D('weiba_apply')->where($map)->save($data);
        if ($res) {
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 微吧审核�
     * �置.
     */
    public function weibaAuditConfig()
    {
        $this->_initWeibaListAdminMenu();
        $this->pageKeyList = array('apply_weiba_open', 'follower_open', 'follower', 'level_open', 'level', 'weiba_post_open', 'weiba_post', 'manager_open');
        $this->savePostUrl = U('weiba/Admin/doWeibaAuditConfig');
        $this->opt['apply_weiba_open'] = array('0' => '否', '1' => '是');
        $this->opt['follower_open'] = array('0' => '否', '1' => '是');
        $this->opt['level_open'] = array('0' => '否', '1' => '是');
        $this->opt['weiba_post_open'] = array('0' => '否', '1' => '是');
        $this->opt['manager_open'] = array('0' => '否', '1' => '是');
        $weibaAuditConfig = model('Xdata')->get('weiba_Admin:weibaAuditConfig');
        // dump($weibaAuditConfig);exit;
        $this->displayConfig($weibaAuditConfig);
    }

    public function doWeibaAuditConfig()
    {
        $list = $_POST['systemdata_list'];
        $key = $_POST['systemdata_key'];
        $key = $list.':'.$key;
        $value['apply_weiba_open'] = intval($_POST['apply_weiba_open']);
        $value['follower_open'] = intval($_POST['follower_open']);
        $value['follower'] = intval($_POST['follower']);
        $value['level_open'] = intval($_POST['level_open']);
        $value['level'] = intval($_POST['level']);
        $value['weiba_post_open'] = intval($_POST['weiba_post_open']);
        $value['weiba_post'] = intval($_POST['weiba_post']);
        $value['manager_open'] = intval($_POST['manager_open']);
        $res = model('Xdata')->put($key, $value);
        if ($res) {
            $this->assign('jumpUrl', U('weiba/Admin/weibaAuditConfig', array('tabHash' => 'weibaAuditConfig')));
            $this->success('保存成功');
        } else {
            $this->error('保存失败');
        }
    }

    /**
     * 微吧审核.
     */
    public function weibaAudit()
    {
        $_REQUEST['tabHash'] = 'weibaAudit';
        $this->_initWeibaListAdminMenu();
        // 设置列表主键
        $this->_listpk = 'weiba_id';
        $this->pageButton[] = array('title' => '搜索', 'onclick' => "admin.fold('search_form')");
        $this->pageButton[] = array('title' => '通过', 'onclick' => "admin.doWeibaAudit('', 1)");
        $this->pageButton[] = array('title' => '驳回', 'onclick' => "admin.doWeibaAudit('', -1)");
        $this->searchKey = array('weiba_name');
        $this->pageKeyList = array('weiba_id', 'weiba_name', 'weiba_cate', 'info', 'uid', 'uinfo', 'live', 'ctime', 'DOACTION');
        if (!empty($_POST['weiba_name'])) {
            $maps['weiba_name'] = array('like', '%'.t($_POST['weiba_name']).'%');
            $map['weiba_id'] = array('in', getSubByKey(D('weiba')->where($maps)->field('weiba_id')->findAll(), 'weiba_id'));
        }
        $map['status'] = 0;
        // 数据的格式化与listKey保持一致
        $listData = D('weiba')->where($map)->order('ctime desc')->findPage(20);

        $weibacate = D('weiba_category')->findAll();
        $cids = array();
        foreach ($weibacate as $c) {
            $cids[$c['id']] = $c['name'];
        }
        foreach ($listData['data'] as $k => $v) {
            $listData['data'][$k]['weiba_name'] = '<a target="_blank" href="'.U('weiba/Index/detail', array('weiba_id' => $v['weiba_id'])).'">'.$v['weiba_name'].'</a>';
            $listData['data'][$k]['logo'] && $listData['data'][$k]['logo'] = '<img src="'.getImageUrlByAttachId($v['logo']).'" width="50" height="50">';
            $create_uid = model('User')->getUserInfoByUids($v['uid']);
            $listData['data'][$k]['uid'] = $create_uid[$v['uid']]['space_link'];
            $listData['data'][$k]['uinfo'] = $create_uid[$v['uid']]['group_icon'];
            $listData['data'][$k]['live'] = $create_uid[$v['uid']]['credit_info']['level']['name'];
            $listData['data'][$k]['ctime'] = friendlyDate($v['ctime']);
            $listData['data'][$k]['weiba_cate'] = $cids[$v['cid']];
            $listData['data'][$k]['DOACTION'] = '<a href="javascript:void(0)" onclick="admin.doWeibaAudit('.$v['weiba_id'].',1);">通过</a>&nbsp;|&nbsp;<a href="javascript:void(0)" onclick="admin.doWeibaAudit('.$v['weiba_id'].',-1);">驳回</a>';
        }
        $this->displayList($listData);
    }

    public function doWeibaAudit()
    {
        if (empty($_POST['weiba_id'])) {
            $return['status'] = 0;
            $return['data'] = '';
            echo json_encode($return);
            exit();
        }
        !is_array($_POST['weiba_id']) && $_POST['weiba_id'] = array($_POST['weiba_id']);
        $map['weiba_id'] = array('in', $_POST['weiba_id']);
        $data['status'] = intval($_POST['value']);
        $res = D('weiba')->where($map)->save($data);
        if ($res) {
            $return['status'] = 1;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_SUCCESS');
        } else {
            $return['status'] = 0;
            $return['data'] = L('PUBLIC_ADMIN_OPRETING_ERROR');
        }
        echo json_encode($return);
        exit();
    }

    /**
     * 微吧后台管理菜单.
     */
    private function _initWeibaListAdminMenu()
    {
        $this->pageTab[] = array('title' => '微吧列表', 'tabHash' => 'index', 'url' => U('weiba/Admin/index'));
        //$this->pageTab[] = array('title'=>'添加微吧','tabHash'=>'addWeiba','url'=>U('weiba/Admin/addWeiba'));
        $this->pageTab[] = array('title' => '微吧分类', 'tabHash' => 'weibaCate', 'url' => U('weiba/Admin/weibaCate'));
        $this->pageTab[] = array('title' => '帖子列表', 'tabHash' => 'postList', 'url' => U('weiba/Admin/postList'));
        $this->pageTab[] = array('title' => '帖子回收站', 'tabHash' => 'postRecycle', 'url' => U('weiba/Admin/postRecycle'));
        $this->pageTab[] = array('title' => '申请圈主配置', 'tabHash' => 'weibaAdminAuditConfig', 'url' => U('weiba/Admin/weibaAdminAuditConfig'));
        $this->pageTab[] = array('title' => '圈主审核', 'tabHash' => 'weibaAdminAudit', 'url' => U('weiba/Admin/weibaAdminAudit'));
        $this->pageTab[] = array('title' => '申请微吧配置', 'tabHash' => 'weibaAuditConfig', 'url' => U('weiba/Admin/weibaAuditConfig'));
        $this->pageTab[] = array('title' => '微吧审核', 'tabHash' => 'weibaAudit', 'url' => U('weiba/Admin/weibaAudit'));
    }
}
