<?php
/**
 * 某人关注的微吧Widget.
 *
 * @example W('ManageWeibaList', array('manage_uid'=>10000))
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class ManageWeibaListWidget extends Widget
{
    /**
     * 渲染关注按钮模板
     *
     * @example
     * $data['manage_uid'] integer 用户ID
     *
     * @param array $data 渲染的相关配置参数
     *
     * @return string 渲染后的模板数据
     */
    public function render($data)
    {
        $var = array();

        $manage = D('weiba_follow')->where(array('follower_uid' => $data['manage_uid'], 'level' => array('in', array(2, 3))))->findAll();
        $map['weiba_id'] = array('in', getSubByKey($manage, 'weiba_id'));
        $map['is_del'] = 0;
        $var['manageWeibaList'] = D('weiba')->where($map)->findAll();
        $var['manageWeibaListCount'] = D('weiba')->where($map)->count();
        foreach ($var['manageWeibaList'] as $k => $v) {
            $var['manageWeibaList'][$k]['logo'] = getImageUrlByAttachId($v['logo'], 50, 50);
        }
        is_array($data) && $var = array_merge($var, $data);
        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/manageWeibaList.html', $var);
        unset($var, $data);
        // 输出数据
        return $content;
    }
}
