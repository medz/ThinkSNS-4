<?php
/**
 * 添加标签 Widget
 * @author jason
 * @version TS3.0
 */
class StringTextWidget extends Widget
{
    /**
     * @param string inputname 文本框名称
     * @param string value 默认值
     */
    public function render($data)
    {
        $var = array();

        $var['tpl'] = 'default';

        is_array($data) && $var = array_merge($var, $data);
        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/'.$var['tpl'].'.html', $var);

        unset($var, $data);

        //输出数据
        return $content;
    }
}
