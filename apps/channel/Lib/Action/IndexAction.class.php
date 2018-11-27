<?php
/**
 * 频道首页控制器.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class IndexAction extends Action
{
    /**
     * 频道首页页面.
     */
    public function index()
    {
        // 添加样式
        $this->appCssList[] = 'channel.css';
        // 获取频道分类列表
        $channelCategory = model('CategoryTree')->setTable('channel_category')->getCategoryList();
        $this->assign('channelCategory', $channelCategory);
        // 频道分类选中
        $cid = intval($_GET['cid']);
        $categoryIds = getSubByKey($channelCategory, 'channel_category_id');
        if (!in_array($cid, $categoryIds) && !empty($cid)) {
            $this->error('您请求的频道分类不存在');

            return false;
        }
        $channelConf = model('Xdata')->get('channel_Admin:index');
        if (empty($cid)) {
            $cid = $channelConf['default_category'];
            if (empty($cid)) {
                $cid = array_shift($categoryIds);
            }
        }
        $this->assign('cid', $cid);
        // 获取用户与频道分类的关注状态
        $followStatus = model('ChannelFollow')->getFollowStatus($this->mid, $cid);
        $this->assign('followStatus', $followStatus);

        // 获取模板样式
        $templete = t($_GET['tpl']);
        if (empty($templete) || !in_array($templete, array('load', 'list'))) {
            $categoryConf = model('CategoryTree')->setTable('channel_category')->getCatgoryConf($cid);
            $templete = empty($categoryConf) ? (($channelConf['show_type'] == 1) ? 'list' : 'load') : (($categoryConf['show_type'] == 1) ? 'list' : 'load');
        }
        $templete = 'load';
        $this->assign('tpl', $templete);
        //获取频道信息
        //广播数
        $channel_count = model('Channel')->where('channel_category_id='.$cid.' AND status=1')->count();
        $this->assign('channel_count', $channel_count);
        //收听人数
        $channel_follower_count = model('ChannelFollow')->where('channel_category_id='.$cid)->count();
        $this->assign('channel_follower_count', $channel_follower_count);
        //banner,desc
        $channel_category = D('channelCategory')->where('channel_category_id='.$cid)->getField('ext');
        $channel_category = unserialize($channel_category);
        $channel_banner = getImageUrlByAttachId($channel_category['attach'], 1000);
        $this->assign('channel_banner', $channel_banner);
        $this->assign('channel_desc', $channel_category['desc']);

        //排序
        $order = $_GET['order'] == null ? 0 : $_GET['order'];
        $this->assign('order', intval($order));

        // 设置页面信息
        $titleHash = model('CategoryTree')->setTable('channel_category')->getCategoryHash();
        $title = empty($cid) ? '频道首页' : $titleHash[$cid];
        $this->setTitle($title);
        $this->setKeywords($title);
        $this->setDescription(implode(',', getSubByKey($channelCategory, 'title')));
        $this->display();
    }

    /**
     * 获取分类数据列表.
     */
    public function getCategoryData()
    {
        $data = model('CategoryTree')->setTable('channel_category')->getCategoryList();
        $result = array();
        if (empty($data)) {
            $result['status'] = 0;
            $result['data'] = '获取数据失败';
        } else {
            $result['status'] = 1;
            $result['data'] = $data;
        }

        exit(json_encode($result));
    }

    /**
     * 投稿发布框.
     */
    public function contributeBox()
    {
        $cid = intval($_GET['cid']);
        $this->assign('cid', $cid);
        // 获取投稿分类信息
        $info = model('CategoryTree')->setTable('channel_category')->getCategoryInfo($cid);
        $title = '投稿到：['.$info['title'].']';
        $this->assign('title', $title);
        // 发布框类型
        $type = array('at', 'topic', 'contribute');
        $actions = array();
        foreach ($type as $value) {
            $actions[$value] = false;
        }
        $this->assign('actions', $actions);
        // 获取后台配置数据 -- 提示语
        $channelConf = model('Xdata')->get('channel_Admin:index');
        $prompt = '';
        if ($channelConf['is_audit'] == 1) {
            $prompt = '投稿成功';
        } else {
            $prompt = '投稿正在审核中';
        }
        $this->assign('prompt', $prompt);

        $this->display();
    }
}
