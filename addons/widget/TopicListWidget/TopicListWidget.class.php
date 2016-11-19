<?php
/**
 * 不同类型的话题列表
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class TopicListWidget extends Widget
{
    /**
     * 渲染话题列表页面
     *
     * @param array $data
     *                    配置相关数据
     * @param
     *        	integer type 话题类型 1:推荐话题 2:精华话题
     * @param
     *        	integer limit 列表条数
     * @return string 渲染页面的HTML
     */
    public function render($data)
    {
        if ($data ['type'] == 1) {
            $map ['recommend'] = 1;
            $map ['lock'] = 0;
            $list = model('Cache')->get('feed_topic_recommend');
            if (! $list) {
                $list = model('FeedTopic')->where($map)->order('count desc')->limit($data ['limit'])->findAll();
                ! $list && $list = 1;
                model('Cache')->set('feed_topic_recommend', $list, 86400);
            }
            $var ['topic_list'] = $list;
            $var ['title'] = '推荐话题';
        }
        // if($data['type']==2){
        // $var['topic_list'] = model('FeedTopic')->where('essence=1')->limit($data['limit'])->findAll();
        // $var['title'] = "精华话题";
        // }
        $var = array_merge($var, $data);
        $content = $this->renderFile(dirname(__FILE__).'/topicList.html', $var);

        return $content;
    }
    /**
     * 搜索话题 用于发布分享发表框
     */
    public function searchTopic()
    {
        $key = trim(t($_REQUEST ['key']));
        $feedtopicDao = model('FeedTopic');
        // if ( $key ){
        $data = $feedtopicDao->where("topic_name like '%".$key."%' and recommend=1")->field('topic_id,topic_name')->limit(10)->findAll();
        // } else {
        // $data = $feedtopicDao->where('recommend=1')->field('topic_id,topic_name')->order('count desc')->limit(10)->findAll();
        // }
        exit(json_encode($data));
    }
    public function refresh()
    {
        $map ['lock'] = 0;
        $list = model('FeedTopic')->where($map)->order('RAND()')->limit(10)->findAll();
        foreach ($list as $vo) {
            $html .= '<li><p><a href="{:U(\'public/Topic/index\',array(\'k\'=>'.urlencode($vo ['topic_name']).'))}">'.$vo ['topic_name'].'</a>（'.$vo ['count'].'）</p></li>';
        }
        echo $html;
    }
}
