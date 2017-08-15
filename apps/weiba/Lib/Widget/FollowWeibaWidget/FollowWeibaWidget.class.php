<?php
/**
 * �
 * �注微吧按钮Widget.
 *
 * @example W('FollowWeiba', array('weiba_id'=>10000, 'weiba_name'=>'weiba_name', 'follow_state'=>$followState))
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class FollowWeibaWidget extends Widget
{
    /**
     * 渲染�
     * �注按钮模板
     *
     * @example
     * $data['weiba_id'] integer 目标微吧的ID
     * $data['weiba_name'] string 目标微吧的名称
     * $data['follow_state'] array 当前用户与目标微吧的�
     * �注状态，array('following'=>1)
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
        $var['type'] = 'normal';
        $var['isrefresh'] = 0;
        is_array($data) && $var = array_merge($var, $data);
        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__)."/{$var['type']}.html", $var);
        unset($var, $data);
        // 输出数据
        return $content;
    }
}
