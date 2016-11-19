<?php
/**
 * 关注用户按钮Widget
 * @example W('FollowBtn', array('fid'=>10000, 'uname'=>'uname', 'follow_state'=>$followState))
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class FollowBtnWidget extends Widget
{
    /**
     * 渲染关注按钮模板
     * @example
     * $data['fid'] integer 目标用户的ID
     * $data['uname'] string 目标用户的昵称
     * $data['follow_state'] array 当前用户与目标用户的关注状态，array('following'=>1,'follower'=>0)
     * $data['isrefresh'] integer 操作成功后是否刷新页面
     * @param  array  $data 渲染的相关配置参数
     * @return string 渲染后的模板数据
     */
    public function render($data)
    {
        $var = array();
        $var['type'] = isset($data['type']) ? $data['type'] : 'normal';
        is_array($data) && $var = array_merge($var, $data);
        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__)."/{$var['type']}.html", $var);
        unset($var, $data);
        // 输出数据
        return $content;
    }
}
