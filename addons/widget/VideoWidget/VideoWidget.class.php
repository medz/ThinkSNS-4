<?php
/**
 * 视频模块
 * @author Stream
 *
 */
class VideoWidget extends Widget
{
    public function render($data)
    {
    }

    /**
     *
     * 上传视频接受处理
     *
     */
    public function paramUrl()
    {
        $link = t($_POST['url']);
        if (preg_match('/(youku.com|youtube.com|qq.com|ku6.com|sohu.com|sina.com.cn|tudou.com|yinyuetai.com)/i', $link, $hosts)) {
            $return['boolen'] = 1;
            $return['data'] = $link;
        } else {
            $return['boolen'] = 0;
            $return['message'] = '仅支持优酷网、土豆网、音悦台视频发布';
        }

        $flashinfo = model('Video')->_video_getflashinfo($link, strtolower($hosts[1]));

        $return['title'] = 1;
        if (!$flashinfo['title'] || json_encode($flashinfo['title']) == 'null') {
            $return['title'] = 0;
            $return['message'] = '仅支持优酷网、土豆网、音悦台视频发布';
        }
        $return['data'] = $flashinfo['title'].' '.$return['data'];
        exit(json_encode($return));
    }
}
