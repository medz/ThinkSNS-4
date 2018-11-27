<?php
/**
 * 后台框架基类.
 *
 *
 * @author jason
 */
class AdministratorAction extends Action
{
    /**
     * 页面字段列表.
     *
     * @var array
     */
    protected $pageKeyList = array();

    /**
     * 针对搜索 或者 页面字段的额外属性.
     *
     * @var array
     */
    protected $opt = array();

    /**
     * 搜索的字段.
     *
     * @var array
     */
    protected $searchKey = array();

    /**
     * 页面字段配置存在system_data表中的页面唯一key值
     *
     * @var string
     */
    protected $pageKey = '';

    /**
     * 页面搜索配置存在system_data表中的页面唯一key值
     *
     * @var string
     */
    protected $searchPageKey = '';

    /**
     * 默认的配置页面保存地址
     *
     * @var string
     */
    protected $savePostUrl = '';

    /**
     * 搜索提交地址
     *
     * @var string
     */
    protected $searchPostUrl = '';

    /**
     * 配置页面的值在system_data表中的对应list值
     *
     * @var string
     */
    protected $systemdata_list = '';

    /**
     * 配置页面的值在system_data表中对应的key值
     *
     * @var string
     */
    protected $systemdata_key = '';

    /**
     * 列表页的TAB切换项
     * 例子 : $this->pageTab[] = array('title'=>'邀请列表','tabHash'=>'list','url'=>U('admin/Home/invatecount'));.
     *
     * @var array
     */
    protected $pageTab = array();

    /**
     * 列表页在分页栏的按钮
     * 例子：$this->pageButton[] = array('title'=>'搜索','onclick'=>"admin.fold('search_form')");.
     *
     * @var array
     */
    protected $pageButton = array();
    /**
     * 列表页是否有全选项.
     *
     * @var bool
     */
    protected $allSelected = true;

    /**
     * 列表中的主键字段.
     *
     * @var unknown_type
     */
    protected $_listpk = 'id';
    /**
     * 页面载入时需要执行的JS列表 （直接函数名）
     * 如：$onload[] = "admin.test()";.
     */
    protected $onload = array();

    /**
     * 提交时候需要进行的验证js函数.
     */
    protected $onsubmit = '';

    /**
     * 不能为空的字段.
     */
    protected $notEmpty = array();

    protected $navList = array();

    protected $submitAlias = '提交';

    public function _initialize()
    {
        if (!model('Passport')->checkAdminLogin()) {
            redirect(U('admin/Public/login'));
        }
        $this->systemdata_list = APP_NAME.'_'.MODULE_NAME;
        $this->systemdata_key = ACTION_NAME;
        $this->pageKey = APP_NAME.'_'.MODULE_NAME.'_'.ACTION_NAME;
        $this->searchPageKey = 'S_'.APP_NAME.'_'.MODULE_NAME.'_'.ACTION_NAME;
        $this->savePostUrl = U('admin/Index/saveConfigData');
        $this->searchPostUrl = U(APP_NAME.'/'.MODULE_NAME.'/'.ACTION_NAME);
        $this->submitAlias = L('PUBLIC_SAVE');
        $this->assign('isAdmin', 1);
        $this->onload[] = 'admin.bindTrOn()';
        $this->getSearchPost(); //默认初始化post查询

        if (!isIpAccess('adminipaccess')) {
            $this->error('你的IP已被管理中心禁止');
        }

        if (!CheckPermission('core_admin', 'admin_login')) {
            $this->assign('jumpUrl', SITE_URL);
            $this->error(L('PUBLIC_NO_FRONTPLATFORM_PERMISSION_ADMIN'));
        }
        $this->navList = model('Xdata')->get('admin_nav:top');
    }

    /**
     * 初始化查询时post值
     */
    public function getSearchPost()
    {
        $init = empty($_POST) ? true : false;

        if (!empty($_POST)) {
            $_SESSION['admin_init_post'][$this->searchPageKey] = $_POST;
        } else {
            $_POST = $_SESSION['admin_init_post'][$this->searchPageKey];
        }

        //去除其他页面的session数据
        foreach ($_SESSION['admin_init_post'] as $k => $v) {
            if ($k != $this->searchPageKey) {
                unset($_SESSION['admin_init_post'][$k]);
            } else {
                if ($init && intval($_REQUEST['p']) == 0) {
                    unset($_POST);
                    unset($_SESSION['admin_init_post'][$k]);
                }
            }
        }

        return $_POST;
    }

    public function setSearchPost($data)
    {
        $_SESSION['admin_init_post'][$this->searchPageKey] = $data;
    }

    /**
     * 显示配置详细页面.
     */
    public function displayConfig($detailData = false)
    {

        //页面Key配置保存的值
        $this->_assignPageKeyData($detailData);

        $this->display(THEME_PATH.'/admin_config.html');
    }

    /**
     * 显示列表页面.
     */
    public function displayList($listData = array())
    {
        //搜索部分设置
        if (!empty($this->searchKey)) {
            $searchKeyData = model('Xconfig')->pagekey_get('searchPageKey:'.$this->searchPageKey);
            $this->assign('searchKeyData', $searchKeyData);
            $this->assign('searchKeyList', $this->searchKey);
        }
        $this->assign('searchPageKey', $this->searchPageKey);

        $this->assign('searchPostUrl', $this->searchPostUrl);
        $this->assign('searchData', $this->getSearchPost());
        //页面key配置保存的数据
        $this->_assignPageKeyData();

        //页面数据
        $this->assign('listData', $listData);
        $this->assign('pageButton', $this->pageButton);
        $this->assign('_listpk', $this->_listpk);
        $this->assign('allSelected', $this->allSelected);
        $this->display(THEME_PATH.'/admin_list.html');
    }

    /**
     *  显示分类页面.
     */
    public function displayCateTree($tree = array())
    {

        //数据保存动作提交的地址
        $this->onload[] = 'admin.bindCatetree()';
        //页面Key配置保存的值
        $pageKeyData = model('Xconfig')->pagekey_get('pageKey:'.$this->pageKey);

        $this->assign('pageKeyData', $pageKeyData);

        $this->assign('tree', $tree['_child']);

        $this->display(THEME_PATH.'/admin_catetree.html');
    }

    /**
     * 现实分类页面.
     *
     * @param array  $tree     树形结构数据
     * @param string $stable   资源表明
     * @param int    $level    子分类添加层级数目，默认为0（无限极）
     * @param array  $delParam 删除关联数据模型参数，app、module、method
     * @param array  $extra    附加配置信息字段，字段间使用|分割，字段的属性用-分割。例：attach|type-是-否|is_audit
     *
     * @return string HTML页面数据
     */
    public function displayTree($tree = array(), $stable = null, $level = 0, $delParam = null, $extra = '', $limit = 0)
    {
        $this->assign('stable', $stable);
        if (!isset($delParam['module']) || !isset($delParam['method'])) {
            $delParam = null;
        }
        $this->assign('delParam', $delParam);
        $this->assign('tree', $tree);
        $this->assign('level', $level);
        $this->assign('extra', $extra);
        $this->assign('limit', $limit);
        $this->display(THEME_PATH.'/admin_tree.html');
    }

    private function _assignPageKeyData($detailData = false)
    {
        $pageKeyData = model('Xconfig')->pagekey_get('pageKey:'.$this->pageKey);

        $this->assign('pageKeyData', $pageKeyData);

        if ($detailData === false) {
            $detailData = model('Xdata')->get($this->systemdata_list.':'.$this->systemdata_key);
        }

        $this->assign('detailData', $detailData);
    }

    /*
     * *
     * 保存页面配置信息
     *
     */
    public function savePageConfig()
    {

        //TODO 保存权限判断
        $key = t($_POST['pageKey']);
        $title = t($_POST['pageTitle']);
        unset($_POST['pageKey'], $_POST['pageTitle']);
        if (!isset($_POST['key'])) {
            $this->error();
            exit();
        }
        // 保存成KEY=>VALUE形式
        $keyArr = $_POST['key'];
        foreach ($_POST as &$v) {
            $v = $this->setKVArr($v, $keyArr);
        }
        $data[$key] = $_POST;

        if (model('Xconfig')->pageKey_lput('pageKey', $data)) {
            LogRecord('admin_config', 'editPagekey', array('name' => $title, 'k1' => L('PUBLIC_ADMIN_EDIT_PEIZHI')), true);
            $this->success();
        } else {
            $this->error();
        }
    }

    /**
     * 修正数据格式 -- 仅开发阶段使用
     * Enter description here ...
     */
    public function createData()
    {
        $sql = 'select * from '.C('DB_PREFIX')."system_data where list = 'pageKey' or list = 'searchPageKey'";
        $list = D('')->query($sql);
        foreach ($list as $v) {
            $v['value'] = unserialize($v['value']);
            $keyArr = $v['value']['key'];
            foreach ($v['value'] as &$vv) {
                $vv = $this->setKVArr($vv, $keyArr);
            }
            $map = array();
            $map['id'] = $v['id'];
            unset($v['id']);
            $v['value'] = serialize($v['value']);
            $save = $v;
            D('system_data')->where($map)->save($v);
            echo $v['list'],':',$v['key'],' is OK!<br/>';
        }
    }

    //设置数组key=》value形式
    private function setKVArr($arr, $keyList)
    {
        $r = array();
        foreach ($arr as $k => $v) {
            $key = is_array($keyList[$k]) ? $keyList[$k][0] : $keyList[$k];
            $r[$key] = $v;
        }

        return $r;
    }

    public function saveSearchConfig()
    {
        $key = $_POST['searchPageKey'];
        $title = $_POST['pageTitle'];
        unset($_POST['searchPageKey'], $_POST['pageTitle']);
       // 保存成KEY=>VALUE形式
        $keyArr = $_POST['key'];
        foreach ($_POST as &$v) {
            $v = $this->setKVArr($v, $keyArr);
        }
        $data[$key] = $_POST;

        if (model('Xconfig')->pageKey_lput('searchPageKey', $data)) {
            LogRecord('admin_config', 'editSearchPagekey', array('name' => $title, 'k1' => L('PUBLIC_ADMIN_EDIT_PEIZHI')), true);
            $this->success();
        } else {
            $this->error();
        }
    }

    /**
     * 保存配置页面详细数据.
     */
    public function saveConfigData()
    {
        if (empty($_POST['systemdata_list']) || empty($_POST['systemdata_key'])) {
            $this->error(L('PUBLIC_SAVE_FAIL'));            // 保存失败
        }
        $key = t($_POST['systemdata_list']).':'.t($_POST['systemdata_key']);
        $title = t($_POST['pageTitle']);
        unset($_POST['systemdata_list'], $_POST['systemdata_key'], $_POST['pageTitle']);
        //rewrite验证.
        if (isset($_POST['site_rewrite_on']) && $_POST['site_rewrite_on'] == 1) {
            $rewrite_test_content = file_get_contents(SITE_URL.'/rewrite');
            if ($rewrite_test_content != 'thinksns') {
                $this->error('服务器设置不支持Rewrite，请检查配置');
            }
        }
        if (isset($_POST['site_analytics_code'])) {
            $_POST['site_analytics_code'] = base64_encode($_POST['site_analytics_code']);
        }
        if (isset($_POST['site_theme_name']) && $_POST['site_theme_name'] != C('THEME_NAME')) {
            $res = $this->_switchTheme(t($_POST['site_theme_name']));
        }
        if ($key == 'admin_Config:attach') {
            $exts = explode(',', $_POST['attach_allow_extension']);
            $objext = array('gif', 'png', 'jpeg', 'zip', 'rar', 'doc', 'xls', 'ppt', 'docx', 'xlsx', 'pptx', 'pdf', 'jpg', 'mp3');
            $_POST['attach_allow_extension'] = implode(',', array_intersect($exts, $objext));
        }
        $result = model('Xdata')->put($key, $_POST);
        LogRecord('admin_config', 'editDetail', array('name' => $title, 'k1' => L('PUBLIC_ADMIN_EDIT_EDTAIL_PEIZHI')), true);       // 保存修改编辑详细数据

        if ($res === false) {
            $this->error(L('PUBLIC_SWITCH_THEME_FAIL'));            // 写config.inc.php文件失败
        } elseif ($result) {
            $this->success();
        } else {
            $this->error(L('PUBLIC_SAVE_FAIL'));            // 保存失败
        }
    }

    /********************************
     *                              *
     *          权限设置            *
     *                              *
     ********************************/

    public function permissionset()
    {
        if ((empty($_GET['appname']) || empty($_GET['appgroup'])) && (empty($_GET['gid']))) {
            $this->error(L('PUBLIC_SYSTEM_USERGROUP_NOEXIST'));
        }
        $ruleList = model('Permission')->getRuleList(intval($_GET['gid']), t($_GET['appname']), t($_GET['appgroup']));
        $this->assign('moduleHash', array('normal' => L('PUBLIC_SYSTEM_NORMAL_USER'), 'admin' => L('PUBLIC_SYSTEM_ADMIN_USER')));
        $this->assign($ruleList);
        $this->display('admin_permissionset');
    }

    public function permissionsave()
    {
        $data = array();
        if (isset($_POST['per'])) {
            $data = $_POST['per'];
            if (is_array($data)) {
                foreach ($data as &$val1) {
                    if (is_array($val1)) {
                        foreach ($val1 as &$val2) {
                            if (is_array($val2)) {
                                foreach ($val2 as &$val3) {
                                    $val3 = t($val3);
                                }
                            }
                        }
                    }
                }
            }
        }
        // $data = isset($_POST['per']) ? t($_POST['per']) : array();
        model('Permission')->setGroupPermission(intval($_POST['user_group_id']), $data);
        $this->success(L('PUBLIC_SYSTEM_MODIFY_SUCCESS'));
    }

    public function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $this->assign('systemdata_list', $this->systemdata_list);
        $this->assign('systemdata_key', $this->systemdata_key);
        $this->assign('opt', $this->opt);    //分类列表选项
        $this->assign('onsubmit', $this->onsubmit);
        $this->assign('onload', $this->onload);
        //数据保存动作提交的地址
        $this->assign('savePostUrl', $this->savePostUrl);
        $this->assign('pageKeyList', $this->pageKeyList);
        $this->assign('pageKey', $this->pageKey);
        $this->assign('notEmpty', $this->notEmpty);
        // 页面标题
        $this->pageTitle[ACTION_NAME] && $this->assign('pageTitle', $this->pageTitle[ACTION_NAME]);
        // 页面标题
        $this->assign('pageTab', $this->pageTab);
        $this->assign('submitAlias', $this->submitAlias);
        parent::display($templateFile, $charset, $contentType);
    }

    private function _switchTheme($themeName = '')
    {
        if (empty($themeName)) {
            $themeName = THEME_NAME;
        }
        $file = SITE_PATH.'/config/config.inc.php';
        if (!is_writable($file)) {
            return false;
        }
        $content = file_get_contents($file);
        $pos = strpos($content, 'THEME_NAME');
        if ($pos === false) {
            $content = str_replace('return array(', 'return array(
	\'THEME_NAME\' => \''.$themeName.'\', ', $content);
        } else {
            $content = preg_replace('/\'THEME_NAME\'\s*=>\s*\'([0-9a-zA-Z_]+)\'/', '\'THEME_NAME\' => \''.$themeName.'\'', $content);
        }

        return file_put_contents($file, $content);
    }
}
