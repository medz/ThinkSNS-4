<?php

class TopicAction extends Action
{
    // 专题页
    public function index()
    {
        //echo $_GET['domain'];exit;
        if ($_GET['domain']) {
            $map['domain'] = t($_GET['domain']);
            //echo $domain;exit;
            $data['search_key'] = model('FeedTopic')->where($map)->getField('topic_name');
        } else {
            $data['search_key'] = $this->__getSearchKey();
        }
        // 专题信息
        if (false == $data['topics'] = model('FeedTopic')->getTopic($data['search_key'], false)) {
            if (!$data['topics']) {
                $this->error('此话题不存在');
            }
            $data['topics']['name'] = t($data['search_key']);
        }
        if ($data['topics']['lock'] == 1) {
            $this->error('该话题已被屏蔽');
        }
        if ($data['topics']['pic']) {
            $pic = D('attach')->where('attach_id='.$data['topics']['pic'])->find();
            //$data['topics']['pic'] = UPLOAD_URL.'/'.$pic['save_path'].$pic['save_name'];
            $pic_url = $pic['save_path'].$pic['save_name'];
            $data['topics']['pic'] = getImageUrl($pic_url);
        }
        $data['topic'] = $data['search_key'] ? $data['search_key'] : html_entity_decode($data['topics']['name'], ENT_QUOTES, 'UTF-8');
        $data['topic_id'] = $data['topics']['topic_id'] ? $data['topics']['topic_id'] : model('FeedTopic')->getTopicId($data['search_key']);
        $initHtml = '#'.$data['search_key'].'#';
        $this->assign('initHtml', $initHtml);
        $this->assign($data);
        //seo
        $seo = model('Xdata')->get('admin_Config:seo_feed_topic');
        $replace['topicName'] = $data['topic'];
        $replace['topicNote'] = $data['topics']['note'];
        $replace['topicDes'] = $data['topics']['des'];
        if ($lastTopic = D('feed_data')->where('feed_id='.D('feed_topic_link')->where('topic_id='.$data['topic_id'])->order('feed_topic_id desc')->limit(1)->getField('feed_id'))->getField('feed_content')) {
            $replace['lastTopic'] = $lastTopic;
        }
        $replaces = array_keys($replace);
        foreach ($replaces as &$v) {
            $v = '{'.$v.'}';
        }
        $seo['title'] = str_replace($replaces, $replace, $seo['title']);
        $seo['keywords'] = str_replace($replaces, $replace, $seo['keywords']);
        $seo['des'] = str_replace($replaces, $replace, $seo['des']);
        !empty($seo['title']) && $this->setTitle($seo['title']);
        !empty($seo['keywords']) && $this->setKeywords($seo['keywords']);
        !empty($seo['des']) && $this->setDescription($seo['des']);
        $this->display();
    }

    private function __getSearchKey()
    {
        $key = '';
        // 为使搜索条件在分页时也有效，将搜索条件记录到SESSION中
        if (isset($_REQUEST['k']) && !empty($_REQUEST['k'])) {
            if (t($_GET['k'])) {
                $key = urldecode($_GET['k']);
            } elseif (t($_POST['k'])) {
                $key = $_POST['k'];
            }
            //$key = t($key);
            // 关键字不能超过200个字符
            if (mb_strlen($key, 'UTF8') > 200) {
                $key = mb_substr($key, 0, 200, 'UTF8');
            }
            $_SESSION['home_user_search_key'] = serialize($key);
        } elseif (is_numeric($_GET[C('VAR_PAGE')])) {
            $key = unserialize($_SESSION['home_user_search_key']);
        } else {
            //unset($_SESSION['home_user_search_key']);
        }
        $key = str_replace(array('%', '"', '<', '>'), '', $key);

        return trim($key);
    }

    //话题列表页
    public function topic_list()
    {
        //热门话题
        $re_map['recommend'] = 1;
        $re_map['lock'] = 0;
        $re_map['status'] = 0;
        $recommend_list = D('FeedTopic')->where($re_map)->limit(10)->findAll();

        foreach ($recommend_list as $key => &$value) {
            $feedTopicId = getSubByKey(D('feed_topic_link')->where('topic_id='.$value['topic_id'])->order('feed_id desc')->field('feed_id')->findAll(), 'feed_id');
            $map['feed_id'] = array('in', $feedTopicId);
            $value['user_count'] = count(array_unique(getSubByKey(D('feed')->where($map)->field('uid')->order('feed_id desc')->findAll(), 'uid')));
        }

        $this->assign('recommend_list', $recommend_list);
        //正在发生
        $l_map['lock'] = 0;
        $l_map['status'] = 0;
        $latest_list = D('FeedTopic')->where($l_map)->order('ctime desc')->limit(20)->findAll();

        foreach ($latest_list as $key => &$value) {
            $feedTopicId = getSubByKey(D('feed_topic_link')->where('topic_id='.$value['topic_id'])->order('feed_id desc')->field('feed_id')->findAll(), 'feed_id');
            $map['feed_id'] = array('in', $feedTopicId);
            $value['user_count'] = count(array_unique(getSubByKey(D('feed')->where($map)->field('uid')->order('feed_id desc')->findAll(), 'uid')));
        }

        $this->assign('latest_list', $latest_list);

        $this->display();
    }
}
