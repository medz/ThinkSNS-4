<?php
/**
 * 收藏控制器.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class CollectionAction extends Action
{
    /**
     * 我的收藏页面.
     */
    public function index()
    {
        $map['uid'] = $GLOBALS['ts']['uid'];
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $this->assign($weiboSet);
        // TODO:后续可能由表中获取语言KEY
        $d['tabHash'] = array(
                            'feed' => L('PUBLIC_WEIBO'),                // 分享
                        );

        $d['tab'] = model('Collection')->getCollTab($map);
        $this->assign($d);

        // 安全过滤
        $t = t($_GET['t']);
        !empty($t) && $map['source_table_name'] = $t;
        $key = t($_POST['collection_key']);
        if ($key === '') {
            $list = model('Collection')->getCollectionList($map, 20);
        } else {
            $list = model('Collection')->searchCollections($key, 20);
            $this->assign('collection_key', $key);
            $this->assign('jsonKey', json_encode($key));
        }
        foreach ($list['data'] as &$value) {
            if ($value['app_row_id'] == 0) {
                $value['app_row_id'] = $value['feed_id'];
            }
        }
        $this->assign($list);
        $this->setTitle(L('PUBLIC_COLLECTION_INDEX'));                    // 我的收藏
        // 是否有返回按钮
        $this->assign('isReturn', 1);
        // 获取用户统计信息
        $userData = model('UserData')->getUserData($GLOBALS['ts']['mid']);
        $this->assign('favoriteCount', $userData['favorite_count']);

        $userInfo = model('User')->getUserInfo($this->mid);
        $this->setTitle('我的收藏');
        $this->setKeywords($userInfo['uname'].'的收藏');
        $this->display();
    }
}
