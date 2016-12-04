<?php
/**
 * 微吧活跃成员组建
 *
 * @package ThinkSNS\Widget\WeibaDaren
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class WeibaDarenWidget extends Widget
{
    /**
     * 渲染执行方法
     * 输出默认html结构
     *
     * @param  array  $var 固定结构
     * @return string 执行后的HTML字符串
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function render($data)
    {
        /* # 合并覆盖初始数据 */
        $data = array_merge(array(
            'max' => 5,
            'weibaid' => null,
            'title' => '用户推荐',
            'uid' => null,
        ), $data);

        return $this->renderFile(dirname(__FILE__).'/template.html', $data);
    }

    /**
     * 重新加载推荐数据
     *
     * @return string 用户列表数据
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public function reload()
    {
        /* 用户ID */
        $uid = intval($_POST['uid']);
        /* 微吧ID */
        $wid = intval($_POST['wid']);
        /* 数量 */
        $limit = intval($_POST['limit']);
        $limit || $limit = 3;

        $where = '`is_del` != 1';
        $uid && $where .= ' AND `post_uid` != '.$uid;
        $wid && $where .= ' AND `weiba_id` = '.$wid;
        $uids = D('WeibaPost', 'weiba')->where($where)->field('`post_uid`, count(`post_id`) as `num`')->order('`num` DESC')->group('`post_uid`')->limit(50)->select();
        $limit > count($uids) && $limit = count($uids);
        $ids = array();
        foreach (array_rand($uids, $limit) as $key) {
            array_push($ids, $uids[$key]['post_uid']);
        }
        unset($uids);
        $ids = model('User')->getUserInfoByUids($ids);
        echo $this->renderFile(dirname(__FILE__).'/_userList.html', array('userList' => $ids));
        exit;
    }
} // END class WeibaDarenWidget extends Widget

/**
 * 可能感兴趣的人Widget
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class OldWeibaDarenWidget extends Widget
{
    /**
     * 渲染可能感兴趣的人页面
     *
     * @param array $data
     *                      配置相关数据
     * @return string 渲染页面的HTML
     */
    public function render($data)
    {
        //$var = $this->_getRelatedDaren($data);
        $var = $data;
        // 用户ID
        $var ['uid'] = isset($data ['uid']) ? intval($data ['uid']) : $GLOBALS ['ts'] ['mid'];
        // 显示相关人数
        if (isset($data ['max']) && ! isset($data ['limit'])) {
            $data ['limit'] = $data ['max'];
        }
        $var ['limit'] = isset($data ['limit']) ? intval($data ['limit']) : 8;
        $var ['weibaid'] = isset($data ['weibaid']) ? intval($data ['weibaid']) : 0;
        // 标题信息
        $var ['title'] = isset($data ['title']) ? t($data ['title']) : '推荐关注';
        $content = $this->renderFile(dirname(__FILE__).'/weibaDaren.html', $var);

        return $content;
    }

    /**
     * 换一换数据处理
     *
     * @return json 渲染页面所需的JSON数据
     */
    public function changeRelate()
    {
        $data ['uid'] = intval($_POST ['uid']);
        $data ['limit'] = intval($_POST ['limit']);
        $data ['weibaid'] = intval($_POST ['weibaid']);
        $var = $this->_getRelatedDaren($data);
        $content = $this->renderFile(dirname(__FILE__).'/_weibaDaren.html', $var);
        exit(json_encode($content));
    }

    /**
     * 获取用户的相关数据
     *
     * @param array $data
     *                     配置相关数据
     * @return array 显示所需数据
     */
    private function _getRelatedDaren($data)
    {
        // 用户ID
        $var ['uid'] = isset($data ['uid']) ? intval($data ['uid']) : $GLOBALS ['ts'] ['mid'];
        // 显示相关人数
        $var ['limit'] = isset($data ['limit']) ? intval($data ['limit']) : 4;
        if (!empty($data ['weibaid'])) {
            $weibaid = intval($data ['weibaid']);
        }
        $var ['weibaid'] = isset($data ['weibaid']) ? intval($data ['weibaid']) : 0;
        // 收藏达人的信息
        $key = '_getWeibaDaren'.$var ['uid'].'_'.$var ['limit'].'_'.date('Ymd').'_'.$weibaid;
        $var ['user'] = S($key);
        if ($var ['user'] === false || intval($_REQUEST ['rel']) == 1) {
            $uidlist = M('user_group_link')->where('user_group_id=7')->limit(1000)->select();
            $map['follower_uid'] = array('in', getSubByKey($uidlist, 'uid'));
            if ($data ['weibaid']) {
                $map['weiba_id'] = $weibaid;
            }
            $list = M('weiba_follow')->where($map)->group('follower_uid')->limit($var ['limit'])->select();
            $uids = getSubByKey($list, 'follower_uid');
            $userInfos = model('User')->getUserInfoByUids($uids);
            $userStates = model('Follow')->getFollowStateByFids($GLOBALS ['ts'] ['mid'], $uids);
            foreach ($list as $v) {
                $key = $v ['follower_uid'];
                $arr [$key] ['userInfo'] = $userInfos [$key];
                $arr [$key] ['followState'] = $userStates [$key];
                $arr [$key] ['info'] ['msg'] = '掌柜';
                $arr [$key] ['info'] ['extendMsg'] = '';
            }
            $var ['user'] = $arr;

            S($key, $var ['user'], 86400);
        }

        return $var;
    }
}
