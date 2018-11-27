<?php
/**
 * 微吧获取图片存在相对地址
 *
 * @param int $attachid 附件ID
 *
 * @return string 附件存储相对地址
 */
function getImageUrlByAttachIdByWeiba($attachid)
{
    if ($attachInfo = model('Attach')->getAttachById($attachid)) {
        return $attachInfo['save_path'].$attachInfo['save_name'];
    } else {
        return false;
    }
}

function updateWeiBaCount($weiba_id)
{
    $map['weiba_id'] = intval($weiba_id);
    $map['is_del'] = 0;

    $save['thread_count'] = M('weiba_post')->where($map)->count();
    $save['post_count'] = M('weiba_reply')->where($map)->count();

    $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $map['post_time'] = array(
            'gt',
            $time,
    );
    $post_count = M('weiba_post')->where($map)->count();
    unset($map['post_time']);

    $map['time'] = array(
            'gt',
            $time,
    );
    $reply_count = M('weiba_reply')->where($map)->count();

    $save['today_count'] = $post_count + $reply_count;

    unset($map['time']);
    $res = D('weiba')->where($map)->save($save);
    // dump($res);
    // dump(D('weiba')->getLastSql());exit;
}

function refreshWeibaCount($weiba_id = 0, $field = array('follow_count', 'thread_count', 'post_count', 'today_count'))
{
    $map['weiba_id'] = $maps['weiba_id'] = $weiba_id;

    // 会员数
    in_array('follow_count', $field) && $data['follow_count'] = M('weiba_follow')->where($map)->count();
    // 主题数
    $map['is_del'] = 0;
    in_array('thread_count', $field) && $data['thread_count'] = M('weiba_post')->where($map)->count();
    // 帖子数
    in_array('post_count', $field) && $data['post_count'] = M('weiba_reply')->where($map)->count();

    if (in_array('today_count', $field)) {
        // 今日帖子数--昨日帖子数
        $time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $ytime = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));

        $map['post_time'] = array(
                'gt',
                $time,
        );
        $data['today_count'] = M('weiba_post')->where($map)->count();

        $map['post_time'] = array(
                'gt',
                $ytime,
        );
        $data['yesterday_count'] = M('weiba_post')->where($map)->count();

        unset($map['post_time']);
        $map['ctime'] = array(
                'gt',
                $time,
        );
        $data['today_count'] = M('weiba_reply')->where($map)->count() + $data['today_count'];

        $map['ctime'] = array(
                'gt',
                $ytime,
        );
        $data['yesterday_count'] = M('weiba_reply')->where($map)->count() + $data['yesterday_count'] - $data['today_count'];

        $data['today_date'] = date('Ymd');
    }
    $res = M('weiba')->where($maps)->save($data);

    return $res;
}

function refreshDayCOunt()
{
    $lock = S('refreshDayCOunt'); // 一天只更新一次历史数据
    $today = date('Ymd');
    if ($lock == $today) {
        return false;
    }

    $yesterday = date('Ymd', strtotime('-1 day'));

    $sql = "UPDATE ts_weiba set yesterday_count=today_count,today_count=0,today_date='$today' where today_date='$yesterday'";
    $res = M()->execute($sql);
// 	dump ( $res );
// 	dump ( $sql );

    $sql = "UPDATE ts_weiba set yesterday_count=0,today_count=0,today_date='$today' where today_date!='$today'";
    $res = M()->execute($sql);
// 	dump ( $res );
// 	dump ( $sql );

    S('refreshDayCOunt', $today);
}

function getDayCount($count, $date)
{
    if ($date == date('Ymd')) {
        return $count;
    } else {
        return 0;
    }
}

function parseflash($w, $h, $url)
{
    $w = !$w ? 550 : $w;
    $h = !$h ? 400 : $h;
    preg_match("/((https?){1}:\/\/|www\.)[^\[\"'\?]+(\.swf|\.flv)(\?.+)?/i", $url, $matches);
    $url = $matches[0];
    $randomid = 'swf_'.random(3);
    if (fileext($url) != 'flv') {
        return '<span id="'.$randomid.'"></span><script type="text/javascript" reload="1">$(\''.$randomid.'\').innerHTML=AC_FL_RunContent(\'width\', \''.$w.'\', \'height\', \''.$h.'\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', encodeURI(\''.$url.'\'), \'quality\', \'high\', \'bgcolor\', \'#ffffff\', \'wmode\', \'transparent\', \'allowfullscreen\', \'true\');</script>';
    } else {
        return '<span id="'.$randomid.'"></span><script type="text/javascript" reload="1">$(\''.$randomid.'\').innerHTML=AC_FL_RunContent(\'width\', \''.$w.'\', \'height\', \''.$h.'\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', \''.STATICURL.'image/common/flvplayer.swf\', \'flashvars\', \'file='.rawurlencode($url).'\', \'quality\', \'high\', \'wmode\', \'transparent\', \'allowfullscreen\', \'true\');</script>';
    }
}

function parseemail($email, $text)
{
    $text = str_replace('\"', '"', $text);
    if (!$email && preg_match("/\s*([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+)\s*/i", $text, $matches)) {
        $email = trim($matches[0]);

        return '<a href="mailto:'.$email.'">'.$email.'</a>';
    } else {
        return '<a href="mailto:'.substr($email, 1).'">'.$text.'</a>';
    }
}

function parsetable($width, $bgcolor, $message)
{
    if (strpos($message, '[/tr]') === false && strpos($message, '[/td]') === false) {
        $rows = explode("\n", $message);
        $s = !defined('IN_MOBILE') ? '<table cellspacing="0" class="t_table" '.
            ($width == '' ? null : 'style="width:'.$width.'"').
            ($bgcolor ? ' bgcolor="'.$bgcolor.'">' : '>') : '<table>';
        foreach ($rows as $row) {
            $s .= '<tr><td>'.str_replace(array('\|', '|', '\n'), array('&#124;', '</td><td>', "\n"), $row).'</td></tr>';
        }
        $s .= '</table>';

        return $s;
    } else {
        if (!preg_match("/^\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td([=\d,%]+)?\]/", $message) && !preg_match("/^<tr[^>]*?>\s*<td[^>]*?>/", $message)) {
            return str_replace('\\"', '"', preg_replace("/\[tr(?:=([\(\)\s%,#\w]+))?\]|\[td([=\d,%]+)?\]|\[\/td\]|\[\/tr\]/", '', $message));
        }
        if (substr($width, -1) == '%') {
            $width = substr($width, 0, -1) <= 98 ? intval($width).'%' : '98%';
        } else {
            $width = intval($width);
            $width = $width ? ($width <= 560 ? $width.'px' : '98%') : '';
        }

        return (!defined('IN_MOBILE') ? '<table cellspacing="0" class="t_table" '.
            ($width == '' ? null : 'style="width:'.$width.'"').
            ($bgcolor ? ' bgcolor="'.$bgcolor.'">' : '>') : '<table>').
            str_replace('\\"', '"', preg_replace(array(
                    "/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td(?:=(\d{1,4}%?))?\]/ie",
                    "/\[\/td\]\s*\[td(?:=(\d{1,4}%?))?\]/ie",
                    "/\[tr(?:=([\(\)\s%,#\w]+))?\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/ie",
                    "/\[\/td\]\s*\[td(?:=(\d{1,2}),(\d{1,2})(?:,(\d{1,4}%?))?)?\]/ie",
                    "/\[\/td\]\s*\[\/tr\]\s*/i",
                ), array(
                    "parsetrtd('\\1', '0', '0', '\\2')",
                    "parsetrtd('td', '0', '0', '\\1')",
                    "parsetrtd('\\1', '\\2', '\\3', '\\4')",
                    "parsetrtd('td', '\\1', '\\2', '\\3')",
                    '</td></tr>',
                ), $message)
            ).'</table>';
    }
}
function parseaudio($url, $width = 400)
{
    $url = addslashes($url);
    if (!in_array(strtolower(substr($url, 0, 6)), array('http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://')) && !preg_match('/^static\//', $url) && !preg_match('/^data\//', $url)) {
        return dhtmlspecialchars($url);
    }
    $ext = fileext($url);
    switch ($ext) {
        case 'mp3':
            $randomid = 'mp3_'.random(3);

            return '<span id="'.$randomid.'"></span><script type="text/javascript" reload="1">$(\''.$randomid.'\').innerHTML=AC_FL_RunContent(\'FlashVars\', \'soundFile='.urlencode($url).'\', \'width\', \'290\', \'height\', \'24\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', \''.STATICURL.'image/common/player.swf\', \'quality\', \'high\', \'bgcolor\', \'#FFFFFF\', \'menu\', \'false\', \'wmode\', \'transparent\', \'allowNetworking\', \'internal\');</script>';
        case 'wma':
        case 'mid':
        case 'wav':
            return '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="'.$width.'" height="64"><param name="invokeURLs" value="0"><param name="autostart" value="0" /><param name="url" value="'.$url.'" /><embed src="'.$url.'" autostart="0" type="application/x-mplayer2" width="'.$width.'" height="64"></embed></object>';
        case 'ra':
        case 'rm':
        case 'ram':
            $mediaid = 'media_'.random(3);

            return '<object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="'.$width.'" height="32"><param name="autostart" value="0" /><param name="src" value="'.$url.'" /><param name="controls" value="controlpanel" /><param name="console" value="'.$mediaid.'_" /><embed src="'.$url.'" autostart="0" type="audio/x-pn-realaudio-plugin" controls="ControlPanel" console="'.$mediaid.'_" width="'.$width.'" height="32"></embed></object>';
    }
}

function parsemedia($params, $url)
{
    $params = explode(',', $params);
    $width = intval($params[1]) > 800 ? 800 : intval($params[1]);
    $height = intval($params[2]) > 600 ? 600 : intval($params[2]);

    $url = addslashes($url);
    if (!in_array(strtolower(substr($url, 0, 6)), array('http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://')) && !preg_match('/^static\//', $url) && !preg_match('/^data\//', $url)) {
        return dhtmlspecialchars($url);
    }

    if ($flv = parseflv($url, $width, $height)) {
        return $flv;
    }
    if (in_array(count($params), array(3, 4))) {
        $type = $params[0];
        $url = htmlspecialchars(str_replace(array('<', '>'), '', str_replace('\\"', '\"', $url)));
        switch ($type) {
            case 'mp3':
            case 'wma':
            case 'ra':
            case 'ram':
            case 'wav':
            case 'mid':
                return parseaudio($url, $width);
            case 'rm':
            case 'rmvb':
            case 'rtsp':
                $mediaid = 'media_'.random(3);

                return '<object classid="clsid:CFCDAA03-8BE4-11cf-B84B-0020AFBBCCFA" width="'.$width.'" height="'.$height.'"><param name="autostart" value="0" /><param name="src" value="'.$url.'" /><param name="controls" value="imagewindow" /><param name="console" value="'.$mediaid.'_" /><embed src="'.$url.'" autostart="0" type="audio/x-pn-realaudio-plugin" controls="imagewindow" console="'.$mediaid.'_" width="'.$width.'" height="'.$height.'"></embed></object><br /><object classid="clsid:CFCDAA03-8BE4-11CF-B84B-0020AFBBCCFA" width="'.$width.'" height="32"><param name="src" value="'.$url.'" /><param name="controls" value="controlpanel" /><param name="console" value="'.$mediaid.'_" /><embed src="'.$url.'" autostart="0" type="audio/x-pn-realaudio-plugin" controls="controlpanel" console="'.$mediaid.'_" width="'.$width.'" height="32"></embed></object>';
            case 'flv':
                $randomid = 'flv_'.random(3);

                return '<span id="'.$randomid.'"></span><script type="text/javascript" reload="1">$(\''.$randomid.'\').innerHTML=AC_FL_RunContent(\'width\', \''.$width.'\', \'height\', \''.$height.'\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', \''.STATICURL.'image/common/flvplayer.swf\', \'flashvars\', \'file='.rawurlencode($url).'\', \'quality\', \'high\', \'wmode\', \'transparent\', \'allowfullscreen\', \'true\');</script>';
            case 'swf':
                $randomid = 'swf_'.random(3);

                return '<span id="'.$randomid.'"></span><script type="text/javascript" reload="1">$(\''.$randomid.'\').innerHTML=AC_FL_RunContent(\'width\', \''.$width.'\', \'height\', \''.$height.'\', \'allowNetworking\', \'internal\', \'allowScriptAccess\', \'never\', \'src\', encodeURI(\''.$url.'\'), \'quality\', \'high\', \'bgcolor\', \'#ffffff\', \'wmode\', \'transparent\', \'allowfullscreen\', \'true\');</script>';
            case 'asf':
            case 'asx':
            case 'wmv':
            case 'mms':
            case 'avi':
            case 'mpg':
            case 'mpeg':
                return '<object classid="clsid:6BF52A52-394A-11d3-B153-00C04F79FAA6" width="'.$width.'" height="'.$height.'"><param name="invokeURLs" value="0"><param name="autostart" value="0" /><param name="url" value="'.$url.'" /><embed src="'.$url.'" autostart="0" type="application/x-mplayer2" width="'.$width.'" height="'.$height.'"></embed></object>';
            case 'mov':
                return '<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" width="'.$width.'" height="'.$height.'"><param name="autostart" value="false" /><param name="src" value="'.$url.'" /><embed src="'.$url.'" autostart="false" type="video/quicktime" controller="true" width="'.$width.'" height="'.$height.'"></embed></object>';
            default:
                return '<a href="'.$url.'" target="_blank">'.$url.'</a>';
        }
    }
}

function bbcodeurl($url, $tags)
{
    if (!preg_match('/<.+?>/s', $url)) {
        if (!in_array(strtolower(substr($url, 0, 6)), array('http:/', 'https:', 'ftp://', 'rtsp:/', 'mms://')) && !preg_match('/^static\//', $url) && !preg_match('/^data\//', $url)) {
            $url = 'http://'.$url;
        }

        return str_replace(array('submit', 'member.php?mod=logging'), array('', ''), str_replace('{url}', addslashes($url), $tags));
    } else {
        return '&nbsp;'.$url;
    }
}

function parseurl($url, $text, $scheme)
{
    global $_G;
    if (!$url && preg_match("/((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.)[^\[\"']+/i", trim($text), $matches)) {
        $url = $matches[0];
        $length = 65;
        if (strlen($url) > $length) {
            $text = substr($url, 0, intval($length * 0.5)).' ... '.substr($url, -intval($length * 0.3));
        }

        return '<a href="'.(substr(strtolower($url), 0, 4) == 'www.' ? 'http://'.$url : $url).'" target="_blank">'.$text.'</a>';
    } else {
        $url = substr($url, 1);
        if (substr(strtolower($url), 0, 4) == 'www.') {
            $url = 'http://'.$url;
        }
        $url = !$scheme ? $_G['siteurl'].$url : $url;

        return '<a href="'.$url.'" target="_blank">'.$text.'</a>';
    }
}

function parsetrtd($bgcolor, $colspan, $rowspan, $width)
{
    return ($bgcolor == 'td' ? '</td>' : '<tr'.($bgcolor && !defined('IN_MOBILE') ? ' style="background-color:'.$bgcolor.'"' : '').'>').'<td'.($colspan > 1 ? ' colspan="'.$colspan.'"' : '').($rowspan > 1 ? ' rowspan="'.$rowspan.'"' : '').($width && !defined('IN_MOBILE') ? ' width="'.$width.'"' : '').'>';
}

//解析UBB标签
function bbcode($message)
{
    $msglower = strtolower($message);

    if (strpos($msglower, '[/url]') !== false) {
        $message = preg_replace("/\[url(=((https?|ftp|gopher|news|telnet|rtsp|mms|callto|bctp|thunder|qqdl|synacast){1}:\/\/|www\.|mailto:)?([^\r\n\[\"']+?))?\](.+?)\[\/url\]/ies", "parseurl('\\1', '\\5', '\\2')", $message);
    }

    if (strpos($msglower, '[/email]') !== false) {
        $message = preg_replace("/\[email(=([a-z0-9\-_.+]+)@([a-z0-9\-_]+[.][a-z0-9\-_.]+))?\](.+?)\[\/email\]/ies", "parseemail('\\1', '\\4')", $message);
    }

    $nest = 0;
    while (strpos($msglower, '[table') !== false && strpos($msglower, '[/table]') !== false) {
        $message = preg_replace("/\[table(?:=(\d{1,4}%?)(?:,([\(\)%,#\w ]+))?)?\]\s*(.+?)\s*\[\/table\]/ies", "parsetable('\\1', '\\2', '\\3')", $message);
        if (++$nest > 4) {
            break;
        }
    }

    $message = str_replace(array(
            '[/color]', '[/backcolor]', '[/size]', '[/font]', '[/align]', '[b]', '[/b]', '[s]', '[/s]', '[hr]', '[/p]',
            '[i=s]', '[i]', '[/i]', '[u]', '[/u]', '[list]', '[list=1]', '[list=a]',
            '[list=A]', "\r\n[*]", '[*]', '[/list]', '[indent]', '[/indent]', '[/float]',
    ), array(
            '</font>', '</font>', '</font>', '</font>', '</div>', '<strong>', '</strong>', '<strike>', '</strike>', '<hr class="l" />', '</p>', '<i class="pstatus">', '<i>',
            '</i>', '<u>', '</u>', '<ul>', '<ul type="1" class="litype_1">', '<ul type="a" class="litype_2">',
            '<ul type="A" class="litype_3">', '<li>', '<li>', '</ul>', '<blockquote>', '</blockquote>', '</span>',
    ), preg_replace(array(
            "/\[color=([#\w]+?)\]/i",
            "/\[color=((rgb|rgba)\([\d\s,]+?\))\]/i",
            "/\[backcolor=([#\w]+?)\]/i",
            "/\[backcolor=((rgb|rgba)\([\d\s,]+?\))\]/i",
            "/\[size=(\d{1,2}?)\]/i",
            "/\[size=(\d{1,2}(\.\d{1,2}+)?(px|pt)+?)\]/i",
            "/\[font=([^\[\<]+?)\]/i",
            "/\[align=(left|center|right)\]/i",
            "/\[p=(\d{1,2}|null), (\d{1,2}|null), (left|center|right)\]/i",
            "/\[float=left\]/i",
            "/\[float=right\]/i",

    ), array(
            '<font color="\\1">',
            '<font style="color:\\1">',
            '<font style="background-color:\\1">',
            '<font style="background-color:\\1">',
            '<font size="\\1">',
            '<font style="font-size:\\1">',
            '\\2', //"<font face=\"\\1\">",
            '<div align="\\1">',
            '<p style="line-height:\\1px;text-indent:\\2em;text-align:\\3">',
            '<span style="float:left;margin-right:5px">',
            '<span style="float:right;margin-left:5px">',
    ), $message));

    $message = preg_replace("/\s?\[postbg\]\s*([^\[\<\r\n;'\"\?\(\)]+?)\s*\[\/postbg\]\s?/is", '', $message);

    if (strpos($msglower, '[/quote]') !== false) {
        $message = preg_replace("/\s?\[quote\][\n\r]*(.+?)[\n\r]*\[\/quote\]\s?/is", '<div class="quote"><blockquote>\\1</blockquote></div>', $message);
    }

    if (strpos($msglower, '[/free]') !== false) {
        $message = preg_replace("/\s*\[free\][\n\r]*(.+?)[\n\r]*\[\/free\]\s*/is", '\\1', $message);
    }

    // if(!defined('IN_MOBILE')) {
    if (strpos($msglower, '[/media]') !== false) {
        $message = preg_replace("/\[media=([\w,]+)\]\s*([^\[\<\r\n]+?)\s*\[\/media\]/ies", $allowmediacode ? "parsemedia('\\1', '\\2')" : "bbcodeurl('\\2', '<a href=\"{url}\" target=\"_blank\">{url}</a>')", $message);
    }
    if (strpos($msglower, '[/audio]') !== false) {
        $message = preg_replace("/\[audio(=1)*\]\s*([^\[\<\r\n]+?)\s*\[\/audio\]/ies", $allowmediacode ? "parseaudio('\\2', 400)" : "bbcodeurl('\\2', '<a href=\"{url}\" target=\"_blank\">{url}</a>')", $message);
    }
    if (strpos($msglower, '[/flash]') !== false) {
        $message = preg_replace("/\[flash(=(\d+),(\d+))?\]\s*([^\[\<\r\n]+?)\s*\[\/flash\]/ies", $allowmediacode ? "parseflash('\\2', '\\3', '\\4');" : "bbcodeurl('\\4', '<a href=\"{url}\" target=\"_blank\">{url}</a>')", $message);
    }

    $message = preg_replace("/\[attach\](.+?)\[\/attach\]/is", '<img src="attach.php?aid=\\1" />', $message);

    $message = str_replace('[img]static/image/common/back.gif[/img]', '', $message);

    $message = str_replace('[img=700,28]static/image/hrline/4.gif[/img]', '', $message);

    $neter_mo = array(
      "/\[img=([0-9]+),([0-9]+)\](.+?)\[\/img\]/is",
      "/\[img\](.+?)\[\/img\]/is",
      "/\[hide\](.+?)\[\/hide\]/is",
    );

    $neter_str = array(
      '<img width="\\1" height="\\2" src="\\3" />',
      '<img src="\\1" />',
      '<div class="hide">\\1</div>',
    );

    $message = preg_replace($neter_mo, $neter_str, $message);

    // $message = nl2br($message);

    $message = h($message);

    return $message;
}
