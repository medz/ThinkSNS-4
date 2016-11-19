<?php

class qq
{
    public $loginUrl;

    private function getCallback($type = '', $do = 'login')
    {
        $callbackurl = Addons::createAddonShow('Login', 'no_register_display', array('type' => $type, 'do' => $do));

        return urlencode($callbackurl);
    }

    //获取授权URL
    public function getUrl($url)
    {
        if ($url) {
            $this->loginUrl = $url;

            return $this->loginUrl;
        }
        OAuth::init(QQ_KEY, QQ_SECRET);
        $url = $this->getCallback('qq');
        $this->loginUrl = OAuth::getAuthorizeURL($url);

        return $this->loginUrl;
    }

    //用户资料
    public function userInfo()
    {
        OAuth::init(QQ_KEY, QQ_SECRET);
        $r = Tencent::api('user/info');
        $me = json_decode($r, true);
        $user['id'] = $me['data']['name'];
        $user['uname'] = $me['data']['nick'];
        $user['province'] = $me['data']['province_code'];
        $user['city'] = $me['data']['city_code'];
        $user['location'] = $me['data']['location'];
        $user['userface'] = $me['data']['head'].'/120';
        $user['sex'] = ($me['data']['sex'] == '1') ? 1 : 0;

        return $user;
    }

    //验证用户
    public function checkUser($do)
    {

        // dump($_REQUEST);
        // dump($do);
        // exit;

        OAuth::init(QQ_KEY, QQ_SECRET);
        $callback = $this->getCallback('qq', $do);

        if ($_REQUEST['code']) {
            $code = $_REQUEST['code'];
            $openid = $_REQUEST['openid'];
            $openkey = $_REQUEST['openkey'];
            //获取授权token
            $url = OAuth::getAccessToken($code, $callback);
            $r = Http::request($url);
            parse_str($r, $out);
            //存储授权数据
            if ($out['access_token']) {
                $_SESSION['t_access_token'] = $out['access_token'];
                $_SESSION['t_refresh_token'] = $out['refresh_token'];
                $_SESSION['t_expire_in'] = $out['expires_in'];
                $_SESSION['t_code'] = $code;
                $_SESSION['t_openid'] = $openid;
                $_SESSION['t_openkey'] = $openkey;
                $_SESSION['qq']['access_token'] = $out['access_token'];
                $_SESSION['qq']['refresh_token'] = $out['refresh_token'];
                $_SESSION['open_platform_type'] = 'qq';

                //验证授权
                $r = OAuth::checkOAuthValid();
                if ($r) {
                    // header('Location: ' . $callback);//刷新页面
                    return true;
                } else {
                    // exit('<h3>授权失败,请重试</h3>');
                    return false;
                }
            } else {
                exit($r);
            }
        } else {
            //获取授权code
            if ($_GET['openid'] && $_GET['openkey']) {
                //应用频道
                $_SESSION['t_openid'] = $_GET['openid'];
                $_SESSION['t_openkey'] = $_GET['openkey'];
                //验证授权
                $r = OAuth::checkOAuthValid();
                if ($r) {
                    // header('Location: ' . $callback);//刷新页面
                    return true;
                } else {
                    // exit('<h3>授权失败,请重试</h3>');
                    return false;
                }
            } else {
                $url = OAuth::getAuthorizeURL($callback);
                header('Location: '.$url);
            }
        }
    }

    //发布一条分享
    public function update($text, $opt)
    {
        $params = array(
            'content' => $text,
        );

        return Tencent::api('t/add_pic_url', $params, 'POST');
    }

    //上传一个照片，并发布一条分享
    public function upload($text, $opt, $pic)
    {
        if (file_exists($pic)) {
            $params = array(
                'content' => $text,
            );
            $multi = array('pic' => $pic);

            return Tencent::api('t/add_pic', $params, 'POST', $multi);
        } else {
            return $this->update($text, $opt);
        }
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
