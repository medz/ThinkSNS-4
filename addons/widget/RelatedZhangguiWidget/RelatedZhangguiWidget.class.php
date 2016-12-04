<?php
/**
 * 可能感兴趣的人Widget
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class RelatedZhangguiWidget extends Widget
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
        // $var = $this->_getRelatedZhanggui($data);
        $var = $data;
        // 用户ID
        $var ['uid'] = isset($data ['uid']) ? intval($data ['uid']) : $GLOBALS ['ts'] ['mid'];
        // 显示相关人数
        if (isset($data ['max']) && ! isset($data ['limit'])) {
            $data ['limit'] = $data ['max'];
        }
        $var ['limit'] = isset($data ['limit']) ? intval($data ['limit']) : 8;
        // 标题信息
        $var ['title'] = isset($data ['title']) ? t($data ['title']) : '推荐关注';
        $content = $this->renderFile(dirname(__FILE__).'/relatedZhanggui.html', $var);

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
        $var = $this->_getRelatedZhanggui($data);
        $content = $this->renderFile(dirname(__FILE__).'/_relatedZhanggui.html', $var);
        exit(json_encode($content));
    }

    /**
     * 获取用户的相关数据
     *
     * @param array $data
     *                     配置相关数据
     * @return array 显示所需数据
     */
    private function _getRelatedZhanggui($data)
    {
        // 用户ID
        $var ['uid'] = isset($data ['uid']) ? intval($data ['uid']) : $GLOBALS ['ts'] ['mid'];
        // 显示相关人数
        $var ['limit'] = isset($data ['limit']) ? intval($data ['limit']) : 4;

        // 掌柜信息

        $key = '_getRelatedZhanggui'.$var ['uid'].'_'.$var ['limit'].'_'.date('Ymd');

        $var ['user'] = S($key);
        if ($var ['user'] === false || intval($_REQUEST ['rel']) == 1) {
            $sql = 'SELECT uid FROM `ts_user_verified` WHERE usergroup_id=5 AND verified=1 order by rand() limit '.$var ['limit'];
            $list = M()->query($sql);
// 			dump($list);
            $uids = getSubByKey($list, 'uid');
            $userInfos = model('User')->getUserInfoByUids($uids);
            $userStates = model('Follow')->getFollowStateByFids($GLOBALS ['mid'], $uids);
            foreach ($list as $v) {
                $key = $v ['uid'];
                $arr [$key] ['userInfo'] = $userInfos [$key];
                $arr [$key] ['followState'] = $userStates [$key];
                $arr [$key] ['info'] ['msg'] = '掌柜';
                $arr [$key] ['info'] ['extendMsg'] = '';
            }
            $var ['user'] = $arr;

            S($key, $var ['user'], 86400);
// 			S ( 'now_'.$key, $var ['user'], 86400 );
        }
// 		dump($var);
        return $var;
    }
}
