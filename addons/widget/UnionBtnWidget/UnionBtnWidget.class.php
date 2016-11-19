<?php
/**
 * 联盟用户按钮Widget
 * @example W('UnionBtn', array('fid'=>10000, 'uname'=>'uname', 'union_state'=>$fallowState))
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class UnionBtnWidget extends Widget
{
    /**
     * 渲染联盟按钮模板
     * @example
     * $data['fid'] integer 目标用户的ID
     * $data['uname'] string 目标用户的昵称
     * $data['union_state'] array 当前用户与目标用户的联盟状态，array('unioning'=>1,'unioner'=>0)
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
