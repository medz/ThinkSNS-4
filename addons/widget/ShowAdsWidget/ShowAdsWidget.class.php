<?php
/**
 * 可能感兴趣的人Widget
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class ShowAdsWidget extends Widget
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
        $var = $this->_getRelatedDaren($data);
        //dump($var);exit;
        $content = $this->renderFile(dirname(__FILE__).'/ShowAds.html', $var);

        return $content;
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
        $var ['limit'] = isset($data ['limit']) ? intval($data ['limit']) : 2;
        // 收藏达人的信息

        $key = '_getRelatedDaren'.$var ['uid'].'_'.$var ['limit'].'_'.date('Ymd');
        $var ['user'] = S($key);
        if ($var ['user'] === false || intval($_REQUEST ['rel']) == 1) {
            $map['d.isHot'] = 1;
            $tablePrefix = C('DB_PREFIX');
            $list = D()->table("{$tablePrefix}event f RIGHT JOIN {$tablePrefix}event_opts AS d ON f.id = d.id ")
            ->where($map)
            ->order('id desc')
            ->field('f.*')
            ->limit($var ['limit'])
            ->findAll();

            //$list = M ()->query ( $sql );
            ///dump(M()->getlastSQL());
            ///dump($list);
            foreach ($list as $v) {
                $key = $v['id'];
                $arr [$key] ['title'] = $v['title'];
                $arr [$key] ['uid'] = $v['uid'];
                $arr [$key] ['coverId'] = $v['coverId'];
                $arr [$key] ['id'] = $v['id'];
            }
            $var ['user'] = $arr;

            S($key, $var ['user'], 86400);
        }

        return $var;
    }
}
