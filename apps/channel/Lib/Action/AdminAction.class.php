<?php
/**
 * 频道后台配置
 * 1.频道分类管理 - 目前支持1级分类.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
tsload(APPS_PATH.'/admin/Lib/Action/AdministratorAction.class.php');
class AdminAction extends AdministratorAction
{
    private $_model_category;

    /**
     * 初始化，配置内容标题.
     */
    public function _initialize()
    {
        // 管理标题项目
        $this->pageTitle['index'] = '频道基本配置';
        $this->pageTitle['channelCategory'] = '频道分类配置';
        $this->pageTitle['auditList'] = '已审核列表';
        $this->pageTitle['unauditList'] = '未审核列表';
        // 管理分页项目
        $this->pageTab[] = array('title' => $this->pageTitle['index'], 'tabHash' => 'index', 'url' => U('channel/Admin/index'));
        $this->pageTab[] = array('title' => $this->pageTitle['channelCategory'], 'tabHash' => 'channelCategory', 'url' => U('channel/Admin/channelCategory'));
        $this->pageTab[] = array('title' => $this->pageTitle['auditList'], 'tabHash' => 'auditList', 'url' => U('channel/Admin/auditList'));
        $this->pageTab[] = array('title' => $this->pageTitle['unauditList'], 'tabHash' => 'unauditList', 'url' => U('channel/Admin/unauditList'));

        $this->_model_category = model('CategoryTree')->setTable('channel_category');

        parent::_initialize();
    }

    /**
     * 频道基本配置页面.
     */
    public function index()
    {
        // 列表key值 DOACTION表示操作
        $this->pageKeyList = array('is_audit', 'default_category', 'show_type');
        $this->opt['is_audit'] = array('是', '否');
        $this->opt['default_category'] = $this->_model_category->getCategoryHash();
        $this->opt['show_type'] = array('瀑布流', '列表');

        $this->displayConfig();
    }

    /**
     * 频道分类配置页面.
     */
    public function channelCategory()
    {
        $_GET['pid'] = intval($_GET['pid']);
        $treeData = $this->_model_category->getNetworkList();
        $extra = array('attach', 'desc', 'show_type' => array('瀑布流', '列表'), 'user_bind', 'topic_bind');
        $channelConf = model('Xdata')->get('channel_Admin:index');
        $defaultExtra = array('show_type' => $channelConf['show_type']);
        $extra = encodeCategoryExtra($extra, $defaultExtra);
        // 配置删除关联信息
        $delParam['app'] = 'channel';
        $delParam['module'] = 'Channel';
        $delParam['method'] = 'deleteAssociatedData';
        $this->displayTree($treeData, 'channel_category', 1, $delParam, $extra);
    }

    /**
     * 已审核管理页面.
     */
    public function auditList()
    {
        // 批量操作按钮
        $this->pageButton[] = array('title' => '取消推荐', 'onclick' => 'admin.cancelRecommended()');
        // 获取列表数据
        $map['status'] = 1;
        $listData = $this->_getData($map, 'audit');

        $this->displayList($listData);
    }

    /**
     * 未审核管理页面.
     */
    public function unauditList()
    {
        // 批量操作按钮
        $this->pageButton[] = array('title' => '通过审核', 'onclick' => 'admin.auditChannelList()');
        $this->pageButton[] = array('title' => '驳回', 'onclick' => 'admin.rejectChannel()');
        // 获取列表数据
        $map['status'] = 0;
        $listData = $this->_getData($map, 'unaudit');

        $this->displayList($listData);
    }

    /**
     * 取消推荐操作.
     *
     * @return josn 相关操作信息数据
     */
    public function cancelRecommended()
    {
        $post = t($_POST['rowId']);
        $rowIds = explode(',', $post);
        $res = D('Channel', 'channel')->cancelRecommended($rowIds);
        $result = array();
        if ($res) {
            $result['status'] = 1;
            $result['data'] = '取消推荐成功';
        } else {
            $result['status'] = 0;
            $result['data'] = '取消推荐失败';
        }

        exit(json_encode($result));
    }

    /**
     * 审核操作.
     *
     * @return josn 相关操作信息数据
     */
    public function auditChannelList()
    {
        $post = t($_POST['rowId']);
        $rowIds = explode(',', $post);
        $res = D('Channel', 'channel')->auditChannelList($rowIds);
        $result = array();
        if ($res) {
            foreach ($rowIds as $v) {
                $config['feed_content'] = getShort(D('feed_data')->where('feed_id='.$v)->getField('feed_content'), 10);
                $channel_category = D('channel')->where('feed_id='.$v)->findAll();
                $map['channel_category_id'] = array('in', getSubByKey($channel_category, 'channel_category_id'));
                $config['channel_name'] = implode(',', getSubByKey(D('channel_category')->where($map)->field('title')->findAll(), 'title'));
                $config['feed_url'] = '<a target="_blank" href="'.U('public/Profile/feed', array('feed_id' => $v, 'uid' => $channel_category[0][uid])).'">'.$config['feed_content'].'</a>';
                model('Notify')->sendNotify($uid, 'channel_add_feed', $config);
            }
            $result['status'] = 1;
            $result['data'] = '审核成功';
        } else {
            $result['status'] = 0;
            $result['data'] = '审核失败';
        }

        exit(json_encode($result));
    }

    /**
     * 频道管理弹窗.
     */
    public function editAdminBox()
    {
        // 获取分享ID
        $data['feedId'] = intval($_REQUEST['feed_id']);
        // 频道分类ID
        $data['channelId'] = empty($_REQUEST['channel_id']) ? 0 : intval($_REQUEST['channel_id']);
        // 获取全部频道列表
        $data['categoryList'] = $this->_model_category->getCategoryList();
        // 获取该分享已经选中的频道
        $data['selectedChannels'] = D('Channel', 'channel')->getSelectedChannels($data['feedId']);

        $this->assign($data);
        $this->display();
    }

    /**
     * 获取内容信息.
     *
     * @param array  $map  查询条件
     * @param string $type 类型
     *
     * @return array 获取相应的列表信息
     */
    private function _getData($map, $type)
    {
        // 键值对
        $this->pageKeyList = array('id', 'cid', 'uname', 'content', 'status', 'category', 'DOACTION');
        $data = D('Channel', 'channel')->getChannelList($map);
        // 组装数据
        foreach ($data['data'] as &$value) {
            $value['id'] = $value['feed_id'];
            $value['cid'] = $value['feed_channel_link_id'];
            $value['content'] = '<div style="width:500px;line-height:22px" model-node="feed_list">'.$value['content'].'  <a target="_blank" href="'.U('public/Profile/feed', array('feed_id' => $value['feed_id'], 'uid' => $value['uid'])).'">'.L('PUBLIC_VIEW_DETAIL').'&raquo;</a></div>';
            $value['status'] = ($value['status'] == 1) ? '<span style="color:green;cursor:auto;">已审核</span>' : '<span style="color:red;cursor:auto;">未审核</span>';
            $value['category'] = implode('<br />', getSubByKey($value['categoryInfo'], 'title'));
            switch ($type) {
                case 'audit':
                    $value['DOACTION'] = '<a href="javascript:;" onclick="admin.cancelRecommended('.$value['feed_id'].')">取消推荐</a>';
                    break;
                case 'unaudit':
                    $channelId = implode(',', getSubByKey($value['categoryInfo'], 'channel_category_id'));
                    $value['DOACTION'] = '<a href="javascript:;" onclick="admin.auditChannelList('.$value['feed_id'].', \''.$channelId.'\')">通过审核</a>&nbsp;-&nbsp;<a href="javascript:;" onclick="admin.rejectChannel('.$value['feed_id'].')">驳回</a>';
                    break;
            }
        }

        return $data;
    }
}
