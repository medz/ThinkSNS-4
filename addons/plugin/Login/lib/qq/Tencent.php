<?php
/**
 * OAuth授权类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20
 */
class OAuth
{
    public static $client_id = '';
    public static $client_secret = '';

    private static $accessTokenURL = 'https://open.t.qq.com/cgi-bin/oauth2/access_token';
    private static $authorizeURL = 'https://open.t.qq.com/cgi-bin/oauth2/authorize';

    /**
     * 初始化
     * @param $client_id 即 appid
     * @param $client_secret 即 appkey
     * @return
     */
    public static function init($client_id, $client_secret)
    {
        if (!$client_id || !$client_secret) {
            exit('client_id or client_secret is null');
        }
        self::$client_id = $client_id;
        self::$client_secret = $client_secret;
    }

    /**
     * 获取授权URL
     * @param $redirect_uri 授权成功后的回调地址，即第三方应用的url
     * @param $response_type 授权类型，为code
     * @param $wap 用于指定手机授权页的版本，默认PC，值为1时跳到wap1.0的授权页，为2时同理
     * @return string
     */
    public static function getAuthorizeURL($redirect_uri, $response_type = 'code', $wap = false)
    {
        $params = array(
            'client_id' => self::$client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => $response_type,
            'wap' => $type,
        );

        return self::$authorizeURL.'?'.http_build_query($params);
    }

    /**
     * 获取请求token的url
     * @param $code 调用authorize时返回的code
     * @param $redirect_uri 回调地址，必须和请求code时的redirect_uri一致
     * @return string
     */
    public static function getAccessToken($code, $redirect_uri)
    {
        $params = array(
            'client_id' => self::$client_id,
            'client_secret' => self::$client_secret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri,
        );

        return self::$accessTokenURL.'?'.http_build_query($params);
    }

    /**
     * 刷新授权信息
     * 此处以SESSION形式存储做演示，实际使用场景请做相应的修改
     */
    public static function refreshToken()
    {
        $params = array(
            'client_id' => self::$client_id,
            'client_secret' => self::$client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $_SESSION['t_refresh_token'],
        );
        $url = self::$accessTokenURL.'?'.http_build_query($params);
        $r = TencentHttp::request($url);
        parse_str($r, $out);
        if ($out['access_token']) {
            //获取成功
            $_SESSION['t_access_token'] = $out['access_token'];
            $_SESSION['t_refresh_token'] = $out['refresh_token'];
            $_SESSION['t_expire_in'] = $out['expires_in'];

            return $out;
        } else {
            return $r;
        }
    }

    /**
     * 验证授权是否有效
     */
    public static function checkOAuthValid()
    {
        $r = json_decode(Tencent::api('user/info'), true);
        if ($r['data']['name']) {
            return true;
        } else {
            self::clearOAuthInfo();

            return false;
        }
    }

    /**
     * 清除授权
     */
    public static function clearOAuthInfo()
    {
        if (isset($_SESSION['t_access_token'])) {
            unset($_SESSION['t_access_token']);
        }
        if (isset($_SESSION['t_expire_in'])) {
            unset($_SESSION['t_expire_in']);
        }
        if (isset($_SESSION['t_code'])) {
            unset($_SESSION['t_code']);
        }
        if (isset($_SESSION['t_openid'])) {
            unset($_SESSION['t_openid']);
        }
        if (isset($_SESSION['t_openkey'])) {
            unset($_SESSION['t_openkey']);
        }
        if (isset($_SESSION['t_oauth_version'])) {
            unset($_SESSION['t_oauth_version']);
        }
    }
}

/**
 * 腾讯分享API调用类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20
 */
class Tencent
{
    //接口url
    public static $apiUrlHttp = 'http://open.t.qq.com/api/';
    public static $apiUrlHttps = 'https://open.t.qq.com/api/';

    //调试模式
    public static $debug = false;

    /**
     * 发起一个腾讯API请求
     * @param $command 接口名称 如：t/add
     * @param $params 接口参数  array('content'=>'test');
     * @param $method 请求方式 POST|GET
     * @param $multi 图片信息
     * @return string
     */
    public static function api($command, $params = array(), $method = 'GET', $multi = false)
    {
        if (isset($_SESSION['t_access_token'])) {
            //OAuth 2.0 方式
            //鉴权参数
            $params['access_token'] = $_SESSION['t_access_token'];
            $params['oauth_consumer_key'] = OAuth::$client_id;
            $params['openid'] = $_SESSION['t_openid'];
            $params['oauth_version'] = '2.a';
            $params['clientip'] = Common::getClientIp();
            $params['scope'] = 'all';
            $params['appfrom'] = 'php-sdk2.0beta';
            $params['seqid'] = time();
            $params['serverip'] = $_SERVER['SERVER_ADDR'];

            $url = self::$apiUrlHttps.trim($command, '/');
        } elseif (isset($_SESSION['t_openid']) && isset($_SESSION['t_openkey'])) {
            //openid & openkey方式
            $params['appid'] = OAuth::$client_id;
            $params['openid'] = $_SESSION['t_openid'];
            $params['openkey'] = $_SESSION['t_openkey'];
            $params['clientip'] = Common::getClientIp();
            $params['reqtime'] = time();
            $params['wbversion'] = '1';
            $params['pf'] = 'php-sdk2.0beta';

            $url = self::$apiUrlHttp.trim($command, '/');
            //生成签名
            $urls = @parse_url($url);
            $sig = SnsSign::makeSig($method, $urls['path'], $params, OAuth::$client_secret.'&');
            $params['sig'] = $sig;
        }

        //请求接口
        $r = TencentHttp::request($url, $params, $method, $multi);
        $r = preg_replace('/[^\x20-\xff]*/', '', $r); //清除不可见字符
        $r = iconv('utf-8', 'utf-8//ignore', $r); //UTF-8转码
        //调试信息
        if (self::$debug) {
            echo '<pre>';
            echo '接口：'.$url;
            echo '<br>请求参数：<br>';
            print_r($params);
            echo '返回结果：'.$r;
            echo '</pre>';
        }

        return $r;
    }
}

/**
 * HTTP请求类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20
 */
class TencentHttp
{
    /**
     * 发起一个HTTP/HTTPS的请求
     * @param $url 接口的URL
     * @param $params 接口参数   array('content'=>'test', 'format'=>'json');
     * @param $method 请求类型    GET|POST
     * @param $multi 图片信息
     * @param $extheaders 扩展的包头信息
     * @return string
     */
    public static function request($url, $params = array(), $method = 'GET', $multi = false, $extheaders = array())
    {
        if (!function_exists('curl_init')) {
            exit('Need to open the curl extension');
        }
        $method = strtoupper($method);
        $ci = curl_init();
        curl_setopt($ci, CURLOPT_USERAGENT, 'PHP-SDK OAuth2.0');
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 3);
        $timeout = $multi ? 30 : 3;
        curl_setopt($ci, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);
        $headers = (array) $extheaders;
        switch ($method) {
            case 'POST':
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($params)) {
                    if ($multi) {
                        foreach ($multi as $key => $file) {
                            $params[$key] = '@'.$file;
                        }
                        curl_setopt($ci, CURLOPT_POSTFIELDS, $params);
                        $headers[] = 'Expect: ';
                    } else {
                        curl_setopt($ci, CURLOPT_POSTFIELDS, http_build_query($params));
                    }
                }
                break;
            case 'DELETE':
            case 'GET':
                $method == 'DELETE' && curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (!empty($params)) {
                    $url = $url.(strpos($url, '?') ? '&' : '?')
                        .(is_array($params) ? http_build_query($params) : $params);
                }
                break;
        }
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        curl_setopt($ci, CURLOPT_URL, $url);
        if ($headers) {
            curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ci);
        curl_close($ci);

        return $response;
    }
}

/**
 * 公共函数类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20 *
 */
class Common
{
    //获取客户端IP
    public static function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER ['REMOTE_ADDR']) && $_SERVER ['REMOTE_ADDR'] && strcasecmp($_SERVER ['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER ['REMOTE_ADDR'];
        } else {
            $ip = 'unknown';
        }

        return $ip;
    }
}

/**
 * Openid & Openkey签名类
 * @author xiaopengzhu <xp_zhu@qq.com>
 * @version 2.0 2012-04-20
 */
class SnsSign
{
    /**
     * 生成签名
     * @param string $method   请求方法 "get" or "post"
     * @param string $url_path
     * @param array  $params   表单参数
     * @param string $secret   密钥
     */
    public static function makeSig($method, $url_path, $params, $secret)
    {
        $mk = self::makeSource($method, $url_path, $params);
        $my_sign = hash_hmac('sha1', $mk, strtr($secret, '-_', '+/'), true);
        $my_sign = base64_encode($my_sign);

        return $my_sign;
    }

    private static function makeSource($method, $url_path, $params)
    {
        ksort($params);
        $strs = strtoupper($method).'&'.rawurlencode($url_path).'&';
        $str = '';
        foreach ($params as $key => $val) {
            $str .= "$key=$val&";
        }
        $strc = substr($str, 0, strlen($str) - 1);

        return $strs.rawurlencode($strc);
    }
}
