<?php

class UMWidget extends Widget
{
    public function render($data)
    {
        $var['type'] = isset($data['type']) ? $data['type'] : 'editor';
        $var['contentName'] = isset($data['contentName']) ? $data['contentName'] : 'content';
        $var['content'] = isset($data['content']) ? $data['content'] : '';
        $var['width'] = isset($data['width']) ? $data['width'] : 626;
        isset($data['tpl']) ? $tpl = $data['tpl'].'.html' : $tpl = 'um.html';
        $content = $this->renderFile(dirname(__FILE__).'/'.$tpl, $var);

        return $content;
    }
}
