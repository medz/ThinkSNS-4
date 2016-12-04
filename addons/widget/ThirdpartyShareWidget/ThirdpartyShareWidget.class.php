<?php
/**
 * 可能感兴趣的人Widget
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class ThirdpartyShareWidget extends Widget
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
        $content = $this->renderFile(dirname(__FILE__).'/ThirdpartyShare.html', $var);

        return $content;
    }
}
