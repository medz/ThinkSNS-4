<?php
/**
 * 公告展示 wigdet
 * @example  {:W('Announcement',array('type'=>1, 'limit' =>3))}
 * @version  TS3.0
 */
class AnnouncementWidget extends Widget
{
    /**
     * @param  int type 类型(1:公告,2:页脚配置文章)
     * @param  int limit 显示条数
     */
    public function render($data)
    {
        $var['type'] = 1;
        $var['limit'] = 3;
        $var = array_merge($var, $data);
        $map['type'] = $var['type'];
        $var['announcement'] = model('Xarticle')->where($map)->order('sort desc')->limit($var['limit'])->findAll();
        $content = $this->renderFile(dirname(__FILE__).'/default.html', $var);

        return $content;
    }
}
