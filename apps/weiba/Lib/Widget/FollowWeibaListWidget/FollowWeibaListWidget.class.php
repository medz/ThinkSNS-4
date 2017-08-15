<?php
/**
 * 某人�
 * �注的微吧Widget.
 *
 * @example W('FollowWeibaList', array('follower_uid'=>10000,'right'=>0))
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class FollowWeibaListWidget extends Widget
{
    /**
     * 渲染�
     * �注按钮模板
     *
     * @example
     * $data['follower_uid'] integer 用户ID
     *
     * @param array $data 渲染的相�
     * ��
     * �置参数
     *
     * @return string 渲染后的模板数据
     */
    public function render($data)
    {
        $var = array();
        $var['type'] = 'FollowWeibaList';

        $follow = D('weiba_follow')->where('follower_uid='.$data['follower_uid'])->findAll();

        $map['weiba_id'] = array('in', getSubByKey($follow, 'weiba_id'));
        $map['is_del'] = 0;
        $var['weibaList'] = D('weiba')->where($map)->findAll();
        if ($data['right'] == '1') {
            $var['nweibaList'] = array_chunk($var['weibaList'], 4);
        }

        $var['weibaListCount'] = D('weiba')->where($map)->count();
        foreach ($var['weibaList'] as $k => $v) {
            $var['weibaList'][$k]['logo'] = getImageUrlByAttachId($v['logo'], 50, 50);
        }
        is_array($data) && $var = array_merge($var, $data);
        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/followWeibaList.html', $var);
        unset($var, $data);
        // 输出数据
        return $content;
    }
}
