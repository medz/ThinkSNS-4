<?php

/**
 * 搜索用户数据
 * @example W('SearchUser', array('name' => 'selectUser', 'uids' => array(10000,14983),'follow'=>0,'max'=>10,'editable'=>0))
 * @author jason
 * @version TS3.0
 */
class SearchUserWidget extends Widget
{
    private static $rand = 1;

    /**
     * @param string name 存储uid的input名称
     * @param mixed uids 已选择的用户id集合
     * @param int follow 是否只能选择已关注的人（0表示可以选择全部用户，1表示只能选择已关注的人）
     * @param int max 最多可选择的用户个数
     * @param int editable 是否可修改选择的结果，如果为0则不能选择用户，不能删除已经选择的用户
     */
    public function render($data)
    {
        $var = array();
        $var['follow'] = 0;
        $var['max'] = 0; //最多可以选择用户个数 为0表示不限制
        $var['editable'] = 1;
        $var['noself'] = 1; //默认不包括自己
        is_array($data) && $var = array_merge($var, $data);
        $var['rand'] = self::$rand;
        //默认参数
        if (isset($var['uids'])) {
            $var['uids'] = $uids = is_array($var['uids']) ? $var['uids'] : explode(',', $var['uids']);
            foreach ($uids as $v) {
                !empty($v) && $var['userList'][] = model('User')->getUserInfo($v);
            }
        }
        $tpl = 'SearchUser.html';
        $var['tpl'] && $tpl = $var['tpl'];
        //dump($tpl);exit;
        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$tpl, $var);

        self::$rand ++;

        unset($var, $data);
        //输出数据
        return $content;
    }

    /**
     * 搜索用户
     * @return array 搜索状态及用户列表数据
     */
    public function search()
    {
        $key = t($_REQUEST['key']);
        $follow = intval($_REQUEST['follow']);
        $noself = intval($_REQUEST['noself']);
        // @Me添加
        $type = t($_REQUEST['type']);
        if ($type == 'at') {
            $feedConf = model('Xdata')->get('admin_Config:feed');
            $follow = $feedConf['weibo_at_me'];
            $noself = 1;
        }
        $list = model('User')->searchUser($key, $follow, 10, '', '', $noself, '', $type);
        foreach ($list['data'] as $k => &$v) {
            $user = $v;
            $v = array();
            $v['uid'] = $user['uid'];
            $v['uname'] = $user['uname'];
            $v['avatar_small'] = $user['avatar_small'];
            $v['search_key'] = $user['search_key'];
        }
        $data = $list['data'];
        $msg = array('status' => 1, 'data' => $data);
        exit(json_encode($msg));
    }

    /**
     * 搜索最近@的人
     * @return array 搜索状态及用户列表数据
     */
    public function searchAt()
    {
        $users = model('UserData')->where("`key`='user_recentat' and uid=".$GLOBALS['ts']['mid'])->getField('at_value');
        $data = unserialize($users);
        $msg = array('data' => $data);
        exit(json_encode($msg));
    }
}
