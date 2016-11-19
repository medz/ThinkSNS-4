<?php
/**
 * 视频服务模型
 *
 * @package ThinkSNS\Addnons\Model\Video
 * @author Seven Du <lovevipdsw@vip.qq.com>
 **/
class VideoModel extends OldVideoModel
{
} // END class VideoModel extends OldVideoModel

// 原有的文件
class OldVideoModel extends Model
{
    public function upload($from = 0, $timeline = 0)
    {
        // $imageinfo = pathinfo($_FILES['pic']['name']);
        // $image_ext = $imageinfo['extension'];
        $video_config = model('Xdata')->get('admin_Content:video_config');

        $videoinfo = pathinfo($_FILES['video']['name']);
        $video_ext = $videoinfo['extension'];

        $allowExts = $video_config['video_ext'] ? explode(',', $video_config['video_ext']) : array('mp4', 'flv');
        // $uploadCondition = $_FILES['pic'] && $_FILES['video'] && in_array(strtolower($image_ext),$allowExts,true) && in_array(strtolower($video_ext),$allowExts,true);
        $uploadCondition = $_FILES['video'] && in_array(strtolower($video_ext), $allowExts, true);
        // return $_FILES['video'];
        //如果视频上传正确.
        if ($uploadCondition) {
            $savePath = SITE_PATH.$this->_getSavePath();   //网页视频文件夹
            $sourceSavePath = $savePath.'/source';    //源视频文件夹
            $partSavePath = $savePath.'/part';  //视频片段文件夹
            if (!file_exists($sourceSavePath)) {
                mkdir($sourceSavePath, 0777, true);
            }
            if (!file_exists($partSavePath)) {
                mkdir($partSavePath, 0777, true);
            }
            $filename = uniqid();    //文件名称
            $image_name = $filename.'.jpg';
            $video_source_name = $filename.'.'.$video_ext;   //源视频名称
            $video_name = $filename.'.mp4';   //视频名称
            if (@move_uploaded_file($_FILES['video']['tmp_name'], $sourceSavePath.'/'.$video_source_name)) {
                //上传视频到源视频文件夹

                set_time_limit(0);
                if (PATH_SEPARATOR == ':') {  //Linux
                    $ffmpegpath = $video_config['ffmpeg_path'];
                } else {     //Windows
                    $ffmpegpath = SITE_PATH.$video_config['ffmpeg_path'];
                }

                //处理视频格式--转为h264格式
                if ($video_config['video_transfer_async']) {  //后台处理
                    $transfer['sourceSavePath'] = $this->_getSavePath().'/source';
                    $transfer['video_source_name'] = $video_source_name;
                    $transfer['savePath'] = $this->_getSavePath();
                    $transfer['video_name'] = $video_name;
                    $transfer['ctime'] = time();
                    $transfer['status'] = 0;
                    $transfer['uid'] = intval($_SESSION['mid']);
                    $transfer_id = D('video_transfer')->add($transfer);
                } else {
                    $this->mobile_video_codec($ffmpegpath, $sourceSavePath, $video_source_name, $savePath, $video_name);
                }
                // //处理视频格式begin
                // if($from==2){   //安卓向右旋转90度,供网页使用
                //     $command = "$ffmpegpath -y -i ".$sourceSavePath."/".$video_source_name." -vf \"transpose=1\" ".$savePath."/".$video_name;
                //     exec( $command );
                // }
                //处理图片
                if ($_FILES['pic']) {
                    $imageinfo = pathinfo($_FILES['pic']['name']);
                    $image_ext = $imageinfo['extension'];
                    if (in_array(strtolower($image_ext), array('jpg', 'png', 'gif', 'jpeg'), true)) {
                        @move_uploaded_file($_FILES['pic']['tmp_name'], $savePath.'/'.$image_name);
                    }
                }

                //如果图片未上传或上传失败
                if (!file_exists($savePath.'/'.$image_name)) {
                    $this->get_video_image($ffmpegpath, $sourceSavePath.'/'.$video_name, $savePath.'/'.$image_name);
                }
                //截取视频前5秒
                $this->get_video_part($ffmpegpath, $sourceSavePath.'/'.$video_name, $partSavePath.'/'.$video_name);
                // $result['video_path']   = $from==2 ? $this->_getSavePath().'/'.$video_name : $this->_getSavePath().'/source/'.$video_name;

                $result['video_path'] = $this->_getSavePath().'/source/'.$video_name;
                $result['video_mobile_path'] = $this->_getSavePath().'/source/'.$video_name;
                $result['video_part_path'] = $this->_getSavePath().'/part/'.$video_name;
                $result['size'] = intval($_FILES['video']['size']);
                $result['name'] = t($_FILES['video']['name']);
                $result['ctime'] = time();
                $result['uid'] = intval($_SESSION['mid']);
                $result['extension'] = $video_ext;
                $result['image_path'] = $this->_getSavePath().'/'.$image_name;
                $result['transfer_id'] = $transfer_id;

                if ($image_info = getimagesize($savePath.'/'.$image_name)) {
                    $result['image_width'] = $image_info[0];
                    $result['image_height'] = $image_info[1];
                }
                $result['from'] = $from;
                $result['timeline'] = $timeline ? $timeline : $this->get_video_timeline($ffmpegpath, $sourceSavePath.'/'.$video_name);
                $video_id = $this->add($result);
                if ($transfer_id) {
                    D('video_transfer')->where('transfer_id='.$transfer_id)->setField('video_id', $video_id);
                }
                $result['video_id'] = intval($video_id);
                $result['status'] = 1;
            } else {
                $result['status'] = 0;
                $result['msg'] = '上传失败';
            }
        } else {
            $result['status'] = 0;
            $result['msg'] = '上传视频信息错误';
        }

        return $result;
    }

    public function upload_by_web($from = 0)
    {
        // $imageinfo = pathinfo($_FILES['pic']['name']);
        // $image_ext = $imageinfo['extension'];
        $video_config = model('Xdata')->get('admin_Content:video_config');
        $videoinfo = pathinfo($_FILES['Filedata']['name']);
        $video_ext = $videoinfo['extension'];
        $allowExts = $video_config['video_ext'] ? explode(',', $video_config['video_ext']) : array('mp4');
        $uploadCondition = $_FILES['Filedata'] && in_array(strtolower($video_ext), $allowExts, true);
        //如果视频上传正确.
        if ($uploadCondition) {
            $savePath = SITE_PATH.$this->_getSavePath(); //网页视频文件夹
            $sourceSavePath = $savePath.'/source';  //源文件文件夹
            $partSavePath = $savePath.'/part';  //视频片段文件夹
            if (!file_exists($sourceSavePath)) {
                mkdir($sourceSavePath, 0777, true);
            }
            if (!file_exists($partSavePath)) {
                mkdir($partSavePath, 0777, true);
            }
            $filename = uniqid();   //文件名称
            $image_name = $filename.'.jpg';
            $video_source_name = $filename.'.'.$video_ext;  //源视频名称
            $video_name = $filename.'.mp4';  //视频名称
            if (@move_uploaded_file($_FILES['Filedata']['tmp_name'], $sourceSavePath.'/'.$video_source_name)) {
                //上传到source文件夹

                set_time_limit(0);
                if (PATH_SEPARATOR == ':') {  //Linux
                    $ffmpegpath = $video_config['ffmpeg_path'];
                } else {     //Windows
                    $ffmpegpath = SITE_PATH.$video_config['ffmpeg_path'];
                }
                //处理视频格式--转为h264格式
                if ($video_config['video_transfer_async']) {  //后台处理
                    $transfer['sourceSavePath'] = $this->_getSavePath().'/source';
                    $transfer['video_source_name'] = $video_source_name;
                    $transfer['savePath'] = $this->_getSavePath();
                    $transfer['video_name'] = $video_name;
                    $transfer['ctime'] = time();
                    $transfer['status'] = 0;
                    $transfer['uid'] = intval($_SESSION['mid']);
                    $transfer_id = D('video_transfer')->add($transfer);
                } else {
                    $command = $ffmpegpath.' -y -i '.$sourceSavePath.'/'.$video_source_name.' -vcodec libx264 '.$savePath.'/'.$video_name;
                    exec($command);
                }

                //获取时长
                $result['timeline'] = $this->get_video_timeline($ffmpegpath, $sourceSavePath.'/'.$video_source_name);
                //截图
                $this->get_video_image($ffmpegpath, $sourceSavePath.'/'.$video_source_name, $savePath.'/'.$image_name);
                //截取视频前5秒
                $this->get_video_part($ffmpegpath, $sourceSavePath.'/'.$video_name, $partSavePath.'/'.$video_name);

                // # 临时解决方案 #medz
                $result['video_path'] = $this->_getSavePath().'/source/'.$video_name;
                // $result['video_path']   = $this->_getSavePath().'/'.$video_name;
                $result['video_mobile_path'] = $this->_getSavePath().'/'.$video_name;
                $result['video_part_path'] = $this->_getSavePath().'/part/'.$video_name;
                $result['size'] = intval($_FILES['Filedata']['size']);
                $result['name'] = t($_FILES['Filedata']['name']);
                $result['ctime'] = time();
                $result['uid'] = intval($_SESSION['mid']);
                $result['extension'] = $video_ext;
                $result['image_path'] = $this->_getSavePath().'/'.$image_name;
                $result['transfer_id'] = $transfer_id;

                if ($image_info = getimagesize($savePath.'/'.$image_name)) {
                    $result['image_width'] = $image_info[0];
                    $result['image_height'] = $image_info[1];
                }
                $result['from'] = $from;
                $video_id = $this->add($result);
                if ($transfer_id) {
                    D('video_transfer')->where('transfer_id='.$transfer_id)->setField('video_id', $video_id);
                }
                $result['image_path'] = SITE_URL.$this->_getSavePath().'/'.$image_name;
                $result['video_id'] = intval($video_id);
                $result['status'] = 1;
            } else {
                $result['status'] = 0;
                $result['message'] = '上传失败';
            }
        } else {
            $result['status'] = 0;
            $result['message'] = '上传失败';
        }

        return $result;
    }

    public function timeline_format($num)
    {
        $hour = floor($num / 3600);
        $minute = floor(($num - 3600 * $hour) / 60);
        $second = floor((($num - 3600 * $hour) - 60 * $minute) % 60);

        return $hour.'时'.$minute.'分'.$second.'秒';
    }

    public function mobile_video_codec($ffmpegpath, $sourceSavePath, $video_source_name, $savePath, $video_name)
    {
        $command = $ffmpegpath.' -y -i '.$sourceSavePath.'/'.$video_source_name.' -vcodec libx264 -vf transpose=1 -metadata:s:v:0 rotate=0 '.$savePath.'/'.$video_name;
        exec($command);
    }

    public function get_video_timeline($ffmpegpath, $input)
    {
        if (!file_exists($input)) {
            return false;
        }
        if (!$ffmpegpath) {
            $data = model('Xdata')->get('admin_Content:video_config');
            if (PATH_SEPARATOR == ':') {  //Linux
                $ffmpegpath = $data['ffmpeg_path'];
            } else {     //Windows
                $ffmpegpath = SITE_PATH.$data['ffmpeg_path'];
            }
        }
        $command = "$ffmpegpath -i ".$input." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
        $timeline = exec($command);
        $time_arr = explode(':', $timeline);
        $timeline = $time_arr[0] * 3600 + $time_arr[1] * 60 + intval($time_arr[2]);

        return $timeline;
    }

    public function get_video_image($ffmpegpath, $input, $output, $fromdurasec = '01')
    {
        if (!file_exists($input)) {
            return false;
        }
        if (!$ffmpegpath) {
            $data = model('Xdata')->get('admin_Content:video_config');
            if (PATH_SEPARATOR == ':') {  //Linux
                $ffmpegpath = $data['ffmpeg_path'];
            } else {     //Windows
                $ffmpegpath = SITE_PATH.$data['ffmpeg_path'];
            }
        }

        $command = "$ffmpegpath -i $input -an -ss 00:00:$fromdurasec -r 1 -vframes 1 -f mjpeg -y $output";
        exec($command);
    }

    public function get_video_part($ffmpegpath, $input, $output, $begin_second = '01', $end_second = '05')
    {
        if (!file_exists($input)) {
            return false;
        }
        if (!$ffmpegpath) {
            $data = model('Xdata')->get('admin_Content:video_config');
            if (PATH_SEPARATOR == ':') {  //Linux
                $ffmpegpath = $data['ffmpeg_path'];
            } else {     //Windows
                $ffmpegpath = SITE_PATH.$data['ffmpeg_path'];
            }
        }
        $command = "$ffmpegpath -ss 00:00:$begin_second -i $input -t 00:00:$end_second $output";
        exec($command);
    }

    //上传临时文件
    private function _getSavePath()
    {
        $savePath = '/data/video/'.date('Y/md');
        $fullPath = SITE_PATH.$savePath;
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0777, true);
        }

        return $savePath;
    }

    //转码
    public function video_transfer()
    {
        $video_list = D('video_transfer')->where('status=0')->order('transfer_id Asc')->limit(3)->findAll();
        // dump($video_list);exit;
        if ($video_list) {
            set_time_limit(0);
            $video_config = model('Xdata')->get('admin_Content:video_config');
            if (PATH_SEPARATOR == ':') {  //Linux
                $ffmpegpath = $video_config['ffmpeg_path'];
            } else {     //Windows
                $ffmpegpath = SITE_PATH.$video_config['ffmpeg_path'];
            }
            foreach ($video_list as $k => $v) {
                if (file_exists(SITE_PATH.$v['sourceSavePath'].'/'.$v['video_source_name'])) {
                    $sourceSavePath = SITE_PATH.$v['sourceSavePath'];
                    $savePath = SITE_PATH.$v['savePath'];
                    $command = $ffmpegpath.' -y -i '.$sourceSavePath.'/'.$v['video_source_name'].' -vcodec libx264 '.$savePath.'/'.$v['video_name'];
                    exec($command);
                    D('video_transfer')->where('transfer_id='.$v['transfer_id'])->setField('status', 1);
                    $feed_id = D('video_transfer')->where('transfer_id='.$v['transfer_id'])->getField('feed_id');
                    model('Feed')->cleanCache(array($feed_id));
                } else {
                    continue;
                }
            }
        }
    }

    public function getVideoInfo($link)
    {
        $link = t($link);
        $parseLink = parse_url($link);
        if (preg_match('/(youku.com|youtube.com|qq.com|ku6.com|sohu.com|sina.com.cn|tudou.com|yinyuetai.com)$/i', $parseLink['host'], $hosts)) {
            $flashinfo = $this->_video_getflashinfo($link, strtolower($hosts[1]));
        }
        if ($flashinfo['flash_url']) {
            //$flashinfo['host']      = $hosts[1];
            $flashinfo['video_url'] = $link;

            return $flashinfo;
        } else {
            return false;
        }
    }

    public function _weiboTypePublish($type_data)
    {
        $link = $type_data;
        $parseLink = parse_url($link);
        if (preg_match('/(youku.com|youtube.com|qq.com|ku6.com|sohu.com|sina.com.cn|tudou.com|yinyuetai.com)$/i', $parseLink['host'], $hosts)) {
            $flashinfo = $this->_video_getflashinfo($link, $hosts[1]);
        }
        if ($flashinfo['flash_url']) {
            $typedata['flashvar'] = $flashinfo['flash_url'];
            $typedata['flashimg'] = $flashinfo['image_url'];
            $typedata['host'] = $hosts[1];
            $typedata['source'] = $type_data;
            $typedata['title'] = $flashinfo['title'];
        }

        return $typedata;
    }

    public function getQqOutsideVideoInfo($link)
    {
        if (extension_loaded('zlib')) {
            $content = file_get_contents('compress.zlib://'.$link); //获取
        }

        if (!$content) {
            $content = file_get_contents($link);
        }
        preg_match('/vid:\s*"(.+?)",/i', $content, $flashvar);
        preg_match('/itemprop=\"image\" content=\"(.+?)\"/i', $content, $img);
        preg_match("/<title>(.*?)<\/title>/", $content, $title);
        $flash_url = 'http://static.video.qq.com/TPout.swf?vid='.$flashvar[1].'&auto=1';
        $return = array();
        $return['title'] = $title[1];
        $return['image_url'] = $img[1];
        $return['flash_url'] = $flash_url;

        return $return;
    }

    public function getLeOutsideVideoInfo($link)
    {
        $contents = file_get_contents($link);
        if (extension_loaded('zlib')) {
            $content = file_get_contents('compress.zlib://'.$link); //获取
        }

        if (!$content) {
            $content = file_get_contents($link);
        }
        preg_match_all('/(vid:(\d+)\)/is', $content, $vid);
        // preg_match_all('/<[\s]*meta[\s]*property="?og\:([^>"]*)"?[\s]*content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $contents, $match);
        preg_match_all('/(title:(.*)\)/is', $conetnt, $title);
        preg_match_all('/(videoPic:(.*)\)/is', $content, $images);
        $baseurl = 'http://img1.c0.letv.com/ptv/player/swfPlayer.swf?autoPlay=1&isPlayerAd=0&id=';
        $return = array();
        $return['title'] = $title;
        $return['image_url'] = $image;
        $return['flash_url'] = $baseurl.$vid[0];

        return $return;
    }

    /**
     * 更具优酷播放页面地址，获取优酷单条视频信息地址
     *
     * @return return array
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getYoukuOutsideVideoInfo($link)
    {
        $showBasicUrl = 'https://openapi.youku.com/v2/videos/show_basic.json';
        $config = model('Xdata')->get('outside:video');

        /* # link */
        /* http://player.youku.com/player.php/sid/XMTMzMDA2NTExMg==/v.swf */
        $sid = preg_replace('/https?\:\/\/player\.youku\.com\/player\.php\/sid\/(.*?)\/v.swf(.*)?/is', '\\1', $link);

        /* # link */
        /* http://v.youku.com/v_show/id_XMTM1NzI5NjM1Ng==.html?firsttime=270&from=y1.9-4 */
        $sid = preg_replace('/https?\:\/\/v\.youku\.com\/v\_show\/id\_(.*?)\.html\??(.*)?/is', '\\1', $sid);

        $conf = array();
        $conf['video_url'] = urlencode($link);
        $conf['client_id'] = $config['youku_client_id'];
        /* # 兼容player地址 */
        $conf['video_id'] = urlencode($sid);

        $_temp = array();
        foreach ($conf as $key => $value) {
            array_push($_temp, $key.'='.$value);
        }

        $url = $showBasicUrl.'?'.implode('&', $_temp);
        $data = file_get_contents($url);
        $data = json_decode($data, true);

        $return['title'] = $data['title'];
        $return['image_url'] = $data['thumbnail'];
        $return['flash_url'] = $data['player'];

        return $return;
    }

    /**
     * 根据土豆页面地址获取单条视频信息
     *
     * @return array
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getTudouOutsideVideoInfo($link)
    {
        $url = 'http://api.tudou.com/v6/tool/repaste';
        $config = model('Xdata')->get('outside:video');

        $conf = array();
        $conf['app_key'] = $config['tudou_app_key'];
        $conf['url'] = urlencode($link);
        $conf['format'] = 'json';

        $config = array();
        foreach ($conf as $key => $value) {
            array_push($config, $key.'='.$value);
        }

        $url .= '?'.implode('&', $config);
        $data = file_get_contents($url);
        $data = json_decode($data, true);

        $return['title'] = $data['itemInfo']['title'];
        $return['image_url'] = $data['itemInfo']['bigPicUrl'];
        $return['flash_url'] = $data['itemInfo']['outerPlayerUrl'];

        return $return;
    }

    /**
     * 根据音悦台页面地址获取mv信息
     *
     * @return array
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getYinyuetaiOutsideVideoInfo($link)
    {
        $contents = file_get_contents($link);
        preg_match_all('/<[\s]*meta[\s]*property="?og\:([^>"]*)"?[\s]*content="?([^>"]*)"?[\s]*[\/]?[\s]*>/si', $contents, $match);

        $data = array();
        foreach ($match['0'] as $key => $value) {
            $data[$match['1'][$key]] = $match['2'][$key];
        }

        $return = array();
        $return['title'] = $data['title'];
        $return['image_url'] = $data['image'];
        $return['flash_url'] = $data['videosrc'];

        return $return;
    }

    /**
     * 获取外部视频信息
     *
     * @return array
     * @author Medz Seven <lovevipdsw@vip.qq.com>
     **/
    public function getOutsideVideoInfo($link, $host)
    {
        $return = array();
        switch (strtolower($host)) {
            // # 优酷
            case 'youku.com':
                $return = $this->getYoukuOutsideVideoInfo($link);
                break;

            // # 土豆
            case 'tudou.com':
                $return = $this->getTudouOutsideVideoInfo($link);
                break;

            // # 音悦台
            case 'yinyuetai.com':
                $return = $this->getYinyuetaiOutsideVideoInfo($link);
                break;
            case 'le.com':
                $return = $this->getLeOutsideVideoInfo($link);
                break;
            case 'qq.com':
                $return = $this->getQqOutsideVideoInfo($link);
                break;
            default:
                $return = null;
                break;
        }

        isset($return['image_url']) and $return['image_url'] = saveImageToLocal($return['image_url']); // # 图片本地化！

        return $return;
    }

    //此代码需要持续更新.视频网站有变动.就得修改代码.
    public function _video_getflashinfo($link, $host)
    {
        return $this->getOutsideVideoInfo($link, $host);

        $return = array();
        if ($host == 'youku.com') {
            preg_match('/id_([\w\-=]+)/i', $link, $matchs);
            if (!empty($matchs[1])) {
                $videoId = $matchs[1];
                $jsonData = file_get_contents("http://v.youku.com/player/getPlayList/VideoIDS/{$videoId}/timezone/+08/version/5/source/out?password=&ran=2513&n=3");
                $data = @json_decode($jsonData, true);
                if (!empty($data['data'][0])) {
                    $img = isset($data['data'][0]['logo']) ? $data['data'][0]['logo'] : null;
                    $title = isset($data['data'][0]['title']) ? $data['data'][0]['title'] : null;
                    $videoId = isset($data['data'][0]['vidEncoded']) ? $data['data'][0]['vidEncoded'] : null;
                    if ($videoId) {
                        $flash_url = "http://player.youku.com/player.php/sid/{$videoId}/v.swf";
                    }
                }
            }
        } else {
            if (extension_loaded('zlib')) {
                $content = file_get_contents('compress.zlib://'.$link); //获取
            }

            if (!$content) {
                $content = file_get_contents($link);
            }//有些站点无法获取
        }
        if ('ku6.com' == $host) {
            // 2012/3/7 修复ku6链接和图片抓去
            preg_match("/\/([\w\-\.]+)\.html/", $link, $flashvar);
            //preg_match("/<span class=\"s_pic\">(.*?)<\/span>/i",$content,$img);
            preg_match('/cover: "(.+?)"/i', $content, $img);
            preg_match("/<title>(.*?)<\/title>/i", $content, $title);
            $title[1] = iconv('GBK', 'UTF-8', $title[1]);
            $flash_url = 'http://player.ku6.com/refer/'.$flashvar[1].'/v.swf';
        } elseif ('tudou.com' == $host && strpos($link, 'www.tudou.com/albumplay') !== false) {
            preg_match("/albumplay\/([\w\-\.]+)\//", $link, $flashvar);
            preg_match("/<title>(.*?)<\/title>/i", $content, $title);
            preg_match('/pic: "(.+?)"/i', $content, $img);
            $title[1] = iconv('GBK', 'UTF-8', $title[1]);
            $flash_url = 'http://www.tudou.com/a/'.$flashvar[1].'/&autoPlay=true/v.swf';
        } elseif ('tudou.com' == $host && strpos($link, 'www.tudou.com/programs') !== false) {
            //dump(auto_charset($content,'GBK','UTF8'));
            preg_match("/programs\/view\/([\w\-\.]+)\//", $link, $flashvar);
            preg_match("/<title>(.*?)<\/title>/i", $content, $title);
            preg_match("/pic: \'(.+?)\'/i", $content, $img);
            $title[1] = iconv('GBK', 'UTF-8', $title[1]);
            $flash_url = 'http://www.tudou.com/v/'.$flashvar[1].'/&autoPlay=true/v.swf';
        } elseif ('tudou.com' == $host && strpos($link, 'www.tudou.com/listplay') !== false) {
            //dump(auto_charset($content,'GBK','UTF8'));
            preg_match("/listplay\/([\w\-\.]+)\//", $link, $flashvar);
            preg_match("/<title>(.*?)<\/title>/i", $content, $title);
            preg_match('/pic:"(.+?)"/i', $content, $img);
            $title[1] = iconv('GBK', 'UTF-8', $title[1]);
            $flash_url = 'http://www.tudou.com/l/'.$flashvar[1].'/&autoPlay=true/v.swf';
        } elseif ('tudou.com' == $host && strpos($link, 'douwan.tudou.com') !== false) {
            //dump(auto_charset($content,'GBK','UTF8'));
            preg_match("/code=([\w\-\.]+)$/", $link, $flashvar);
            preg_match('/title":"(.+?)"/i', $content, $title);
            preg_match('/itempic":"(.+?)"/i', $content, $img);
            $title[1] = iconv('GBK', 'UTF-8', $title[1]);
            $flash_url = 'http://www.tudou.com/v/'.$flashvar[1].'/&autoPlay=true/v.swf';
        } elseif ('youtube.com' == $host) {
            preg_match('/http:\/\/www.youtube.com\/watch\?v=([^\/&]+)&?/i', $link, $flashvar);
            preg_match('/<link itemprop="thumbnailUrl" href="(.+?)">/i', $content, $img);
            preg_match("/<title>(.*?)<\/title>/", $content, $title);
            $flash_url = 'http://www.youtube.com/embed/'.$FLASHVAR[1];
        } elseif ('sohu.com' == $host) {
            preg_match('/og:videosrc" content="(.+?)"/i', $content, $flashvar);
            preg_match('/og:title" content="(.+?)"/i', $content, $title);
            preg_match('/og:image" content="(.+?)"/i', $content, $img);
            $title[1] = iconv('GBK', 'UTF-8', $title[1]);
            $flash_url = $flashvar[1];
        } elseif ('qq.com' == $host) {
            preg_match('/vid:"(.+?)",/i', $content, $flashvar);
            preg_match('/itemprop=\"image\" content=\"(.+?)\"/i', $content, $img);
            preg_match("/<title>(.*?)<\/title>/", $content, $title);
            $flash_url = 'http://static.video.qq.com/TPout.swf?vid='.$flashvar[1].'&auto=1';
        } elseif ('sina.com.cn' == $host) {
            preg_match("/swfOutsideUrl:\'(.+?)\'/i", $content, $flashvar);
            preg_match("/pic\:[ ]*\'(.*?)\'/i", $content, $img);
            preg_match("/<title>(.*?)<\/title>/i", $content, $title);
            $flash_url = $flashvar[1];
        } elseif ('yinyuetai.com' == $host) {
            preg_match("/video\/([\w\-]+)$/", $link, $flashvar);
            preg_match("/<meta property=\"og:image\" content=\"(.*)\"\/>/i", $content, $img);
            preg_match("/<meta property=\"og:title\" content=\"(.*)\"\/>/i", $content, $title);
            $flash_url = 'http://player.yinyuetai.com/video/player/'.$flashvar[1].'/v_0.swf';
            $base = base64_encode(file_get_contents($img[1]));
            $img[1] = 'data:image/jpeg;base64,'.$base;
        } elseif ('le.com' == $host) {
            echo $content;
            exit();
        }
        $return['title'] = is_array($title) ? t($title[1]) : $title;
        $return['flash_url'] = t($flash_url);
        $return['image_url'] = is_array($img) ? t($img[1]) : $img;

        return $return;
    }

    /**
     * 删除视频信息，提供假删除功能
     * @param  int    $id    视频ID
     * @param  string $type  操作类型，若为delVideo则进行假删除操作，deleteVideo则进行彻底删除操作
     * @param  string $title ???
     * @return array  返回操作结果信息
     */
    public function doEditVideo($id, $type, $title)
    {
        $return = array('status' => '0', 'data' => L('PUBLIC_ADMIN_OPRETING_ERROR'));        // 操作失败
        if (empty($id)) {
            $return['data'] = L('视频ID不能为空');            // 附件ID不能为空
        } else {
            $map['video_id'] = is_array($id) ? array('IN', $id) : intval($id);
            $save['is_del'] = ($type == 'delVideo') ? 1 : 0;       //TODO:1 为用户uid 临时为1
            if ($type == 'deleteVideo') {
                // 彻底删除操作
                $res = D('Video')->where($map)->delete();
                // TODO:删除附件文件
            } else {
                // 假删除或者恢复操作
                $res = D('Video')->where($map)->save($save);
            }
            if ($res) {
                //TODO:是否记录知识，以及后期缓存处理
                $return = array('status' => 1, 'data' => L('PUBLIC_ADMIN_OPRETING_SUCCESS'));        // 操作成功
            }
        }

        return $return;
    }

    public function update_viewrecord($feed_id, $uid)
    {
        //增加浏览量
            $map['feed_id'] = $data['feed_id'] = $feed_id;
        $map['uid'] = $data['uid'] = $uid;
        $data['last_view_time'] = time();
        if (D('feed_video_viewrecord')->where($map)->find()) {
            D('feed_video_viewrecord')->where($map)->setField('last_view_time', $data['last_view_time']);
            D('feed_video_viewrecord')->where($map)->setInc('times');
        } else {
            $map['times'] = 1;
            D('feed_video_viewrecord')->add($data);
        }
    }
}
