<?php

//使用V2版本的客户端,支持Oauth2.0
class baidu
{
    public $loginUrl;
    public $baidu;
    public function getUrl($redirectUri)
    {
        $baidu = new BaiduAPI(BAIDU_KEY, BAIDU_SECRET, $redirectUri, new BaiduCookieStore(BAIDU_KEY));
        $loginUrl = $baidu->getLoginUrl();

        return $loginUrl;
    }
    //用户资料
    public function userInfo()
    {
        $baidu = new BaiduAPI(BAIDU_KEY, BAIDU_SECRET);
        $user = $baidu->getLoggedInUser();
        if ($user) {
            $apiClient = $baidu->getBaiduApiClientService();
            $profile = $apiClient->api('/rest/2.0/passport/users/getInfo');
        }
        $user['id'] = $user['uid'];
        $user['uname'] = $user['uname'];
        $user['province'] = 0;
        $user['city'] = 0;
        $user['location'] = '';
        $user['userface'] = $profile['portrait'];
        $user['sex'] = $profile['sex'];

        return $user;
    }
    //验证用户
    public function checkUser()
    {
        $baidu = new BaiduAPI(BAIDU_KEY, BAIDU_SECRET);
        $access_token = $baidu->getAccessToken();
        $refresh_token = $baidu->getRefreshToken();
        $user = $baidu->getLoggedInUser();
        if ($user) {
            $_SESSION['baidu']['access_token']['oauth_token'] = $access_token;
            $_SESSION['baidu']['access_token']['oauth_token_secret'] = $refresh_token;
            $_SESSION['baidu']['isSync'] = 0;
            $_SESSION['baidu']['uid'] = $user['uid'];
            $_SESSION['open_platform_type'] = 'baidu';

            return $user;
        } else {
            return false;
        }
    }
    //发布一条分享
    public function update($text, $opt)
    {
        return true;
    }
    //上传一个照片，并发布一条分享
    public function upload($text, $opt, $pic)
    {
        return true;
    }
    //转发一条分享
    public function transpond($transpondId, $reId, $content = '', $opt = null)
    {
        return true;
    }
    //保存数据
    public function saveData($data)
    {
        return true;
    }
}
