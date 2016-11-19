<?php
/**
 * 分享发布框
 * @example W('Share',array('sid'=>14983,'stable'=>'contact','appname'=>'contact','nums'=>10,'initHTML'=>'这里是默认的话'))
 * @author jason
 * @version TS3.0
 */
class ShareWidget extends Widget
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
        $var['cancomment'] = intval(CheckPermission('core_normal', 'feed_comment'));
        $var['feed_type'] = 'repost';

        is_array($data) && $var = array_merge($var, $data);

        // 获取资源是否被删除
        switch ($data['appname']) {
            case 'weiba':
                $wInfo = D('WeibaPost', 'weiba')->where('post_id='.$var['sid'])->find();
                $sInfo = model('Feed')->getFeedInfo($sInfo['feed_id']);
                break;
            case 'event':
                $eInfo = D('event')->where('id='.$var['sid'])->find();
                $sInfo = model('Feed')->getFeedInfo($eInfo['feed_id']);
                break;
            case 'blog':
                $bInfo = D('blog')->where('id='.$var['sid'])->find();
                $sInfo = model('Feed')->getFeedInfo($bInfo['feed_id']);
                break;
            case 'vote':
                $vInfo = D('vote')->where('id='.$var['sid'])->find();
                $sInfo = model('Feed')->getFeedInfo($vInfo['feed_id']);
                break;
            case 'photo':
                $pInfo = D('photo_album')->where('id='.$var['sid'])->find();
                $sInfo = model('Feed')->getFeedInfo($pInfo['feed_id']);
                break;
            default:
                $sInfo = model('Feed')->getFeedInfo($var['sid']);
        }

        extract($var, EXTR_OVERWRITE);

        if ($nums > 0) {
            $showNums = "&nbsp;({$nums})";
        } else {
            $showNums = '';
        }

        if ($s_is_del == 1) {
            return '<span>'.L('PUBLIC_SHARE_STREAM').$showNums.'</span>';
        } elseif ($var['tpl'] == 'share_repost') {
            return "<a event-node=\"share\" class=\"repost\" href=\"javascript:void(0);\" event-args='sid={$sid}&stable={$stable}&curtable={$current_table}&curid={$current_id}&initHTML={$initHTML}&appname={$appname}&cancomment={$cancomment}&feedtype={$feed_type}&is_repost={$is_repost}'>我的主页</a>";
        } else {
            return '<a event-node="'.($var['enode'] ? $var['enode'] : 'share').'"'.($var['class'] ? ' class="'.$var['class'].'"' : '').($var['title'] ? ' title="'.$var['title'].'"' : '')." href=\"javascript:void(0);\" event-args='sid={$sid}&stable={$stable}&curtable={$current_table}&curid={$current_id}&initHTML={$initHTML}&appname={$appname}&cancomment={$cancomment}&feedtype={$feed_type}&is_repost={$is_repost}'>".($var['text'] ? $var['text'] : L('PUBLIC_SHARE_STREAM').$showNums).'</a>';
        }
    }
}
