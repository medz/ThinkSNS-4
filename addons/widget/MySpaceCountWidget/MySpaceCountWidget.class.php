<?php
/**
 * 个人空间数据统计
 * @example {:W('MySpaceCount')}
 * @version TS3.0
 */
class MySpaceCountWidget extends Widget
{
    /**
     * 渲染空间数据统计模板
     */
    public function render($data)
    {
        $content = $this->renderFile(dirname(__FILE__).'/content.html', $var);

        unset($var, $data);

        return $content;
    }
}
