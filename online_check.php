<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/* # 设置时区 */
if (!ini_get('date.timezone') and function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Shanghai');
}

// ini_set('display_errors', true);
error_reporting(0);
// set_time_limit(0);

// 新的系统核心接入
require dirname(__FILE__).'/src/bootstrap.php';

//session 设置
ini_set('session.cookie_httponly', 1);
//设置session路径到本地
if (strtolower(ini_get('session.save_handler')) == 'files') {
    $session_dir = dirname(__FILE__).'/data/session';
    if (!is_dir($session_dir)) {
        mkdir($session_dir, 0777, true);
    }
    session_save_path($session_dir);
}
session_start();
//$encrypt	=	1;
//exit;

/* ===================================== 公共部分 ========================================== */
// 获取客户端IP地址
if (!function_exists('getClientIp')) {
    function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'unknown';
        }

        return addslashes($ip);
    }
}

// 过滤非法html标签
if (!function_exists('t')) {
    function t($text)
    {
        //过滤标签
        $text = nl2br($text);
        $text = real_strip_tags($text);
        $text = addslashes($text);
        $text = trim($text);

        return addslashes($text);
    }
}

if (!function_exists('real_strip_tags')) {
    function real_strip_tags($str, $allowable_tags = '')
    {
        $str = stripslashes(htmlspecialchars_decode($str));

        return strip_tags($str, $allowable_tags);
    }
}

// 获取用户浏览器型号。新加浏览器，修改代码，增加特征字符串.把IE加到12.0 可以使用5-10年了.
if (!function_exists('getBrower')) {
    function getBrower()
    {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'Maxthon')) {
            $browser = 'Maxthon';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 12.0')) {
            $browser = 'IE12.0';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 11.0')) {
            $browser = 'IE11.0';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 10.0')) {
            $browser = 'IE10.0';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9.0')) {
            $browser = 'IE9.0';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 8.0')) {
            $browser = 'IE8.0';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 7.0')) {
            $browser = 'IE7.0';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.0')) {
            $browser = 'IE6.0';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'NetCaptor')) {
            $browser = 'NetCaptor';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Netscape')) {
            $browser = 'Netscape';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Lynx')) {
            $browser = 'Lynx';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Opera')) {
            $browser = 'Opera';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome')) {
            $browser = 'Google';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox')) {
            $browser = 'Firefox';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Safari')) {
            $browser = 'Safari';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'iphone') || strpos($_SERVER['HTTP_USER_AGENT'], 'ipod')) {
            $browser = 'iphone';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'ipad')) {
            $browser = 'iphone';
        } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'android')) {
            $browser = 'android';
        } else {
            $browser = 'other';
        }

        return addslashes($browser);
    }
}

// 浏览器友好的变量输出
if (!function_exists('dump')) {
    function dump($var)
    {
        ob_start();
        var_dump($var);
        $output = ob_get_clean();
        if (!extension_loaded('xdebug')) {
            $output = preg_replace("/\]\=\>\n(\s+)/m", '] => ', $output);
            $output = '<pre style="text-align:left">'.$label.htmlspecialchars($output, ENT_QUOTES).'</pre>';
        }
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        echo $output;
    }
}

// 设置cookie
if (!function_exists('cookie')) {
    function cookie($name, $value = '', $option = null)
    {
        // 默认设置
        $config = array(
            'prefix' => isset($GLOBALS['config']['COOKIE_PREFIX']) ?: '', // cookie 名称前缀
            'expire' => isset($GLOBALS['config']['COOKIE_EXPIRE']) ?: 3600, // cookie 保存时间
            'path'   => '/',   // cookie 保存路径
            'domain' => '', // cookie 有效域名
        );

        // 参数设置(会覆盖黙认设置)
        if (!empty($option)) {
            if (is_numeric($option)) {
                $option = array('expire' => $option);
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
            $config = array_merge($config, array_change_key_case($option));
        }

        // 清除指定前缀的所有cookie
        if (is_null($name)) {
            if (empty($_COOKIE)) {
                return;
            }
           // 要删除的cookie前缀，不指定则删除config设置的指定前缀
           $prefix = empty($value) ? $config['prefix'] : $value;
            if (!empty($prefix)) {
                // 如果前缀为空字符串将不作处理直接返回

               foreach ($_COOKIE as $key => $val) {
                   if (0 === stripos($key, $prefix)) {
                       setcookie($_COOKIE[$key], '', time() - 3600, $config['path'], $config['domain']);
                       unset($_COOKIE[$key]);
                   }
               }
            }

            return;
        }
        $name = $config['prefix'].$name;

        if ('' === $value) {
            //return isset($_COOKIE[$name]) ? unserialize($_COOKIE[$name]) : null;// 获取指定Cookie
            return isset($_COOKIE[$name]) ? ($_COOKIE[$name]) : null; // 获取指定Cookie
        } else {
            if (is_null($value)) {
                setcookie($name, '', time() - 3600, $config['path'], $config['domain']);
                unset($_COOKIE[$name]); // 删除指定cookie
            } else {
                // 设置cookie
                $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
                //setcookie($name,serialize($value),$expire,$config['path'],$config['domain']);

                setcookie($name, ($value), $expire, $config['path'], $config['domain'], false, true);

                //$_COOKIE[$name] = ($value);
            }
        }
    }
}

/* ===================================== 配置部分 ========================================== */

$check_time = 300;    //10分钟检查一次
$online_time = 1800;    //统计30分钟的在线用户

$app = t($_GET['app']) ? t($_GET['app']) : 'square';
$mod = t($_GET['mod']) ? t($_GET['mod']) : 'Index';
$act = t($_GET['act']) ? t($_GET['act']) : 'index';
$action = $app.'/'.$mod.'/'.$act;
$uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$uname = t($_GET['uname']) ? t($_GET['uname']) : 'guest';
$agent = getBrower();
$ip = getClientIp();

$refer = '站内';
isset($_SERVER['HTTP_REFERER']) &&
$refer = addslashes($_SERVER['HTTP_REFERER']);

$isGuest = ($uid == -1 || $uid == 0) ? 1 : 0;
$isIntranet = (substr($ip, 0, 2) == '10.') ? 1 : 0;
$cTime = time();
$ext = '';

//记录在线统计.
if ($_GET['action'] == 'trace') {

    /* ===================================== step 1 record track ========================================== */
    $result = Capsule::table('online_logs')
        ->insert(
            array(
                // 'day' => 'CURRENT_DATE',
                'day'        => date('Y-m-d'),
                'uid'        => $uid,
                'uname'      => $uname,
                'action'     => $action,
                'refer'      => $refer,
                'isGuest'    => $isGuest,
                'isIntranet' => $isIntranet,
                'ip'         => $ip,
                'agent'      => $agent,
                'ext'        => $ext,
            )
        );

    /* ===================================== step 2 update hits ========================================== */

    //memcached更新.写入全局点击量.每个应用的点击量.每个版块的点击量.

    /* ===================================== step 3 update heartbeat ========================================== */

    if ((cookie('online_update') + $check_time) < $cTime) {

        // 刷新用户在线时间
        // 设置10分钟过期
        cookie('online_update', $cTime, 7200);

        $online = Capsule::table('online');

        //判断是否存在记录.
        if ($uid > 0) {
            $online->where('uid', '=', $uid);
        } else {
            $online
                ->where('uid', '=', 0, 'and')
                ->where('ip', '=', $ip);
        }
        $result = $online->select('uid')->get();

        //如果没有记录.添加记录.
        if ($result) {
            $result = $online->update(array(
                'activeTime' => $cTime,
                'ip'         => $ip,
            ));
        } else {
            $result = $online->insert(array(
                'uid'        => $uid,
                'uname'      => $uname,
                'app'        => $app,
                'ip'         => $ip,
                'agent'      => $agent,
                'activeTime' => $cTime,
            ));
        }
    }
    if ($result) {
        echo 'var onlineclick = "ok";';
    }
}
