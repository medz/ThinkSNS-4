<?php

class FaceWidget extends Widget
{
    /**
     * 模板渲染.
     *
     * @param array $data 相关数据
     *
     * @return string 用户身份选择模板
     */
    public function render($data)
    {
        // 设置模板
        $template = empty($data['tpl']) ? 'face' : strtolower(t($data['type']));
        // 获取相关数据
        $var['uids'] = explode(',', $data['uids']);
        $var['type'] = t($data['type']);
        $var['faceList'] = D('People', 'people')->getTopUserInfos($var['uids'], $var['type']);
        // 渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$template.'.html', $var);
        // 输出数据
        return $content;
    }
}
