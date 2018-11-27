<?php
/**
 * MentionAction 提到我的.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class MentionAction extends Action
{
    /**
     * 提到我的分享页面.
     */
    public function index()
    {
        // 获取未读@Me的条数
        $this->assign('unread_atme_count', model('UserData')->where('uid='.$this->mid." and `key`='unread_atme'")->getField('value'));
        // 拼装查询条件
        $map['uid'] = $this->mid;

        $d['tab'] = model('Atme')->getTab(null);
        foreach ($d['tab'] as $key => $vo) {
            if ($key == 'feed') {
                $d['tabHash']['feed'] = L('PUBLIC_WEIBO');
            } elseif ($key == 'comment') {
                $d['tabHash']['comment'] = L('PUBLIC_STREAM_COMMENT');
            } else {
                $langKey = 'PUBLIC_APPNAME_'.strtoupper($key);
                $lang = L($langKey);
                if ($lang == $langKey) {
                    $d['tabHash'][$key] = ucfirst($key);
                } else {
                    $d['tabHash'][$key] = $lang;
                }
            }
        }
        $this->assign($d);

        !empty($_GET['t']) && $map['table'] = t($_GET['t']);

        // 设置应用名称与表名称
        $app_name = isset($_GET['app_name']) ? t($_GET['app_name']) : 'public';
        // $app_table = isset($_GET['app_table']) ? t($_GET['app_table']) : '';
        // 获取@Me分享列表
        $at_list = model('Atme')->setAppName($app_name)->setAppTable($app_table)->getAtmeList($map);

        // 赞功能
        $feed_ids = getSubByKey($at_list['data'], 'feed_id');
        $diggArr = model('FeedDigg')->checkIsDigg($feed_ids, $GLOBALS['ts']['mid']);
        $this->assign('diggArr', $diggArr);

        // dump($at_list);exit;
        // 添加Widget参数数据
        foreach ($at_list['data'] as &$val) {
            if ($val['source_table'] == 'comment') {
                $val['widget_sid'] = $val['sourceInfo']['source_id'];
                $val['widget_style'] = $val['sourceInfo']['source_table'];
                $val['widget_sapp'] = $val['sourceInfo']['app'];
                $val['widget_suid'] = $val['sourceInfo']['uid'];
                $val['widget_share_sid'] = $val['sourceInfo']['source_id'];
            } elseif ($val['is_repost'] == 1) {
                $val['widget_sid'] = $val['source_id'];
                $val['widget_stype'] = $val['source_table'];
                $val['widget_sapp'] = $val['app'];
                $val['widget_suid'] = $val['uid'];
                $val['widget_share_sid'] = $val['app_row_id'];
                $val['widget_curid'] = $val['source_id'];
                $val['widget_curtable'] = $val['source_table'];
            } else {
                $val['widget_sid'] = $val['source_id'];
                $val['widget_stype'] = $val['source_table'];
                $val['widget_sapp'] = $val['app'];
                $val['widget_suid'] = $val['uid'];
                $val['widget_share_sid'] = $val['source_id'];
            }
            // 获取转发与评论数目
            if ($val['source_table'] != 'comment') {
                $feedInfo = model('Feed')->get($val['widget_sid']);
                $val['repost_count'] = $feedInfo['repost_count'];
                $val['comment_count'] = $feedInfo['comment_count'];
            }
            // 解析数据成网页端显示格式(@xxx 加链接)
            $val['source_content'] = parse_html($val['source_content']);
            $val['from'] = getFromClient($val['from'], $val['app']);
        }
        // 获取分享设置
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $this->assign($weiboSet);
        // 用户@Me未读数目重置
        // model('UserCount')->resetUserCount($this->mid, 'unread_atme', 0);
        $this->setTitle(L('PUBLIC_MENTION_INDEX'));
        $userInfo = model('User')->getUserInfo($this->mid);
        $this->setKeywords('@提到'.$userInfo['uname'].'的消息');
        $this->assign($at_list);
        $this->display();
    }

    /**
     * @某个人的弹窗
     */
    public function at()
    {
        $uid = t($_GET['touid']);
        if (!empty($uid)) {
            $userInfo = model('User')->getUserInfo($uid);
            if (!empty($userInfo)) {
                $d['initHtml'] = '@'.$userInfo['uname'].' ';
            }
        }
        $this->assign($d);
        // 权限控制
        $actions['contribute'] = false;
        $this->assign('actions', $actions);

        $this->display();
    }
}
