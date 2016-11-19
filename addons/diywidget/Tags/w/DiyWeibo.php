<?php
/**
 * 系统分享类
 * @author Stream
 *
 */
class DiyWeibo extends TagsAbstract
{
    /**
     * 是否是封闭的标签
     * @var unknown_type
     */
    public static $TAG_CLOSED = false;

    public $config = array();

    public function __construct()
    {
    }
    public function getTagStatus()
    {
        return self::$TAG_CLOSED;
    }
    /**
     * 返回模板文件
     * @see TagsAbstract::getTemplateFile()
     */
    public function getTemplateFile($tpl = '')
    {
        //返回需要渲染的模板
        $file = $this->attr ['style'];
        if (!empty($tpl)) {
            $file = $tpl;
        }

        return dirname(__FILE__).'/DiyWeibo/'.$file.'.html';
    }

    /**
     * 参数处理
     * @see TagsAbstract::replace()
     */
    public function replace()
    {
        $attr = $this->attr;
        $var['source'] = $this->attr['source'];
        if ($attr['type'] == 'all') {
            $attr['type'] = '';
        }
        if (!empty($attr['type'])) {
            $map['type'] = t($attr['type']);
        }
        $limit = 10;
        if (!empty($attr['limit'])) {
            $limit = $attr['limit'];
        }
        $map['is_del'] = 0;
        if (!empty($attr['order'])) {
            $order = $attr['order'];
        }
        switch ($attr['source']) {
            case 'user'://指定用户分享
                if (!empty($attr['user'])) {
                    $map['uid'] = array('in', explode(',', $attr['user']));
                }
                break;
            case 'topic'://指定话题分享
                if (!empty($attr['topic'])) {
                    $fids = model('FeedTopic')->getFeedIdByTopic($attr['topic']);
                    $map['feed_id'] = array('in', $fids);
                }
                break;
        }
        $list = model('Feed')->getList($map, $limit, $order);
        $attr['data'] = $list['data'];
        $attr['list'] = $list;
        // 获取分享配置
        $weiboSet = model('Xdata')->get('admin_Config:feed');
        $attr = array_merge($attr, $weiboSet);
        $attr['remarkHash'] = model('Follow')->getRemarkHash($GLOBALS['ts']['mid']);

        return $attr;
    }
}
