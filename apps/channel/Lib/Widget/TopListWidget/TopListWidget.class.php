<?php
/**
 * 用户贡献排行榜Widget.
 *
 * @author zivss <guolee226@gmail.com>
 *
 * @version TS3.0
 */
class TopListWidget extends Widget
{
    /**
     * 模板渲染.
     *
     * @param array $data 相关数据
     *
     * @return string 频道内容渲染入口
     */
    public function render($data)
    {
        // 设置频道模板
        $template = 'top';
        // 配置参数
        $var['cid'] = intval($data['cid']);
        $var['list'] = D('Channel', 'channel')->getTopList($var['cid']);

        $content = $this->renderFile(dirname(__FILE__).'/'.$template.'.html', $var);

        return $content;
    }
}
