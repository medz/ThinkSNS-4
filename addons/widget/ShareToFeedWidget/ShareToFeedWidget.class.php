<?php
/**
 * 分享发布框
 * @example W('Share',array('sid'=>14983,'stable'=>'contact','appname'=>'contact','nums'=>10,'initHTML'=>'这里是默认的话'))
 * @author jason
 * @version TS3.0
 */
class ShareToFeedWidget extends Widget
{
    /**
     * @param int sid 资源ID,如分享小名片就是对应用户的用户ID，分享分享就是分享的ID
     * @param string stable 资源所在的表，如小名片就是contact表，分享就是feed表
     * @param string appname 资源所在的应用
     * @param int nums 该资源被分享的次数
     * @param string initHTML 默认的内容
     */
    public function render($data)
    {
        $var = array();
        $var['appname'] = 'public';
        $data['url'] = urlencode($data['url']);
        empty($data['isLoad']) && $var['isLoad'] = 0;
        is_array($data) && $var = array_merge($var, $data);

        //渲染模版
        $content = $this->renderFile(dirname(__FILE__).'/ShareToFeed.html', $var);

        unset($var, $data);
        //输出数据
        return $content;
    }
}
