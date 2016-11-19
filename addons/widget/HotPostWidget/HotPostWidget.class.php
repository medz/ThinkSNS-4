<?php
/**
 * 不同类型的话题列表
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class HotPostWidget extends Widget
{
    /**
     * 渲染话题列表页面
     * @param array $data 配置相关数据
     * @param int type 话题类型 1:推荐话题   2:精华话题
     * @param int limit 列表条数
     * @return string 渲染页面的HTML
     */
    public function render($data)
    {
        if ($data['type'] == 1) {
            $map['recommend'] = 1;
            $map['lock'] = 0;
            $list = model('Cache')->get('feed_topic_recommend');
            if (!$list) {
                $list = model('FeedTopic')->where($map)->order('count desc')->limit($data['limit'])->findAll();
                !$list && $list = 1;
                model('Cache')->set('feed_topic_recommend', $list, 86400);
            }
            $var['topic_list'] = $list;
            $var['title'] = '热门帖子';
        }
        // if($data['type']==2){
        //     $var['topic_list'] = model('FeedTopic')->where('essence=1')->limit($data['limit'])->findAll();
        //     $var['title'] = "精华话题";
        // }
        $var = array_merge($var, $data);
        $content = $this->renderFile(dirname(__FILE__).'/HotPost.html', $var);

        return $content;
    }
}
