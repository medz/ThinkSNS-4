<?php

class FeedManageWidget extends Widget
{
    public function render($data)
    {
        if (empty($data ['feed_id']) || empty($data ['feed_uid'])) {
            return '';
        }
        // 获取分享ID
        $feedId = intval($data ['feed_id']);
        // 获取分享发布者UID
        $feedUid = intval($data ['feed_uid']);
        // 管理员删除分享权限
        $adminfeeddel = CheckPermission('core_admin', 'feed_del');
        // 推荐到频道权限
        $feed = model('Feed')->get($data ['feed_id']);
        if ($feed ['is_repost'] == 1) {
            $adminchannelrecom = false;
        } else {
            $adminchannelrecom = CheckPermission('channel_admin', 'channel_recommend');
        }
        // 推荐到事务权限
        $admintaskrecom = CheckPermission('vtask_admin', 'vtask_recommend');
        // 插件显示 - 个人空间分享置顶权限
        Addons::hook('check_feed_manage', array(
                'feed_id' => $data ['feed_id'],
                'uid' => $data ['feed_uid'],
                'plugin_list' => &$pluginList,
        ));
        $checkShowBtn = array();
        $checkShowBtn [] = $adminfeeddel;
        $checkShowBtn [] = $adminchannelrecom;
        $checkShowBtn [] = $admintaskrecom;
        ! empty($pluginList) && $checkShowBtn = array_merge($checkShowBtn, getSubByKey($pluginList, 'status'));

        // 判断是否显示管理权限
        $showBtn = true;
        if (! in_array(true, $checkShowBtn)) {
            $showBtn = false;
        }

        if (! $showBtn) {
            return '';
        }

        // 管理参数组合
        $args = array();
        $args ['feed_id'] = $feedId;
        $args ['uid'] = $feedUid;
        $args ['is_recommend'] = $data['is_recommend'];
        $args ['feed_del'] = $adminfeeddel;
        $args ['feed_recommend'] = CheckPermission('core_admin', 'feed_recommend');
        $args ['channel_recommend'] = $adminchannelrecom;
        $args ['vtask_recommend'] = $admintaskrecom;
        foreach ($pluginList as $value) {
            $args [$value ['plugin']] = $value ['status'];
        }
        isset($data ['channel_id']) && $args ['channel_id'] = intval($data ['channel_id']);
        isset($data ['clear']) && $args ['clear'] = intval($data ['clear']);
        isset($data ['isrefresh']) && $args ['isrefresh'] = intval($data ['isrefresh']);

        $var ['feed_id'] = $feedId;
        // 获取列表页面HTML
        $var ['listHtml'] = $this->renderFile(dirname(__FILE__).'/list.html', $args);

        $tmp = array();
        foreach ($args as $key => $val) {
            $tmp [] = $key.'='.intval($val);
        }
        $eventArgs = implode('&', $tmp);
        $var ['eventArgs'] = $eventArgs;

        $manageClass = isset($data ['manage_class']) ? $data ['manage_class'] : 'right hover dp-cs';
        $var ['manageClass'] = $manageClass;

        $content = $this->renderFile(dirname(__FILE__).'/manage.html', $var);

        return $content;
    }
}
