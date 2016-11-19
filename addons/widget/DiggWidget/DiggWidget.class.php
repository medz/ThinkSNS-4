<?php
/**
 * 赞Widget
 * @version TS3.0
 */
class DiggWidget extends Widget
{
    /**
     * 渲染赞页面
     * @return string 赞HTML相关信息
     */
    public function render($data)
    {
        $var['tpl'] = 'digg';
        $var['feed_id'] = intval($data['feed_id']);
        $var['digg_count'] = intval($data['digg_count']);
        $var['diggArr'] = (array) $data['diggArr'];
        $var['diggId'] = empty($data['diggId']) ? 'digg' : t($data['diggId']);
        // 判断是否可以自己赞自己
        // $var['self_feed'] = ($GLOBALS['ts']['mid'] == intval($data['feed_uid'])) ? true : false;

        // 直接输出，减少模版解析，提升效率
        if ($var['tpl'] == 'digg') {
            return $this->renderData($var);
        } else {
            return $this->renderFile(dirname(__FILE__).'/'.t($var['tpl']).'.html', $var);
        }
    }

    public function addDigg()
    {
        $feed_id = intval($_POST['feed_id']);
        $result = model('FeedDigg')->addDigg($feed_id, $this->mid);
        if ($result) {
            $res['status'] = 1;
            $res['info'] = model('FeedDigg')->getLastError();
        } else {
            $res['status'] = 0;
            $res['info'] = model('FeedDigg')->getLastError();
        }
        exit(json_encode($res));
    }

    public function delDigg()
    {
        $feed_id = intval($_POST['feed_id']);
        $result = model('FeedDigg')->delDigg($feed_id, $this->mid);
        if ($result) {
            $res['status'] = 1;
            $res['info'] = model('FeedDigg')->getLastError();
        } else {
            $res['status'] = 0;
            $res['info'] = model('FeedDigg')->getLastError();
        }
        exit(json_encode($res));
    }

    private function renderData($var)
    {
        extract($var, EXTR_OVERWRITE);
        $html = '<span id="'.$diggId.$feed_id.'" rel="'.$digg_count.'">';
        if (!isset($diggArr[$feed_id])) {
            if (!empty($digg_count)) {
                $html .= '<a href="javascript:;" onclick="core.digg.addDigg('.$feed_id.')" title="赞"><i class="digg-like"></i>('.$digg_count.')</a>';
            } else {
                $html .= '<a href="javascript:;" onclick="core.digg.addDigg('.$feed_id.')" title="赞"><i class="digg-like"></i></a>';
            }
        } else {
            if (!empty($digg_count)) {
                $html .= '<a href="javascript:;" class="like-h digg-like-yes" title="取消赞" onclick="core.digg.delDigg('.$feed_id.')"><i class="digg-like"></i>('.$digg_count.')</a>';
            }
        }
        $html .= '</span>';

        return $html;
    }
}
