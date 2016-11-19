<?php
/**
 * 具有相同资料项的人Widget
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class TopicUserWidget extends Widget
{
    /**
     * 渲染话题人物页面
     * @param array $data 配置相关数据
     * @param int topic_id 话题ID
     * @param int type 话题人物类型 1:话题人物推荐   2:参与话题的人
     * @param int limit 人物数量限制
     * @return string 渲染页面的HTML
     */
    public function render($data)
    {
        $limit = isset($data['limit']) ? intval($data['limit']) : 12;
        if ($data['type'] == 1) {
            $topic = model('FeedTopic')->where('topic_id='.$data['topic_id'])->find();
            $var['topic_user'] = array_slice(explode(',', $topic['topic_user']), 0, 12);
        }
        if ($data['type'] == 2) {
            $feedTopicId = getSubByKey(D('feed_topic_link')->where('topic_id='.$data['topic_id'])->order('feed_id desc')->field('feed_id')->findAll(), 'feed_id');
            $map['feed_id'] = array('in', $feedTopicId);
            $topic_user = array_unique(getSubByKey(D('feed')->where($map)->field('uid')->order('feed_id desc')->findAll(), 'uid'));
            $var['topic_user'] = array_slice($topic_user, 0, $limit);
        }
        $var['user'] = model('User')->getUserInfoByUids($var['topic_user']);
        $var['follow_state'] = model('Follow')->getFollowStateByFids($this->mid, $var['topic_user']);
        $var['mid'] = $this->mid;
        $var = array_merge($var, $data);
        $content = $this->renderFile(dirname(__FILE__).'/topicUser.html', $var);

        return $content;
    }
}
