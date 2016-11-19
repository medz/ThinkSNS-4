<?php

class renren
{
    private function getCallback($site = '', $type = 'bind', $callbackurl = '')
    {
        if (!$callbackurl) {
            if ($type == 'bind') {
                $callbackurl = Addons::createAddonShow('Login', 'no_register_display', array('type' => $site, 'do' => 'bind'));
            } else {
                $callbackurl = Addons::createAddonShow('Login', 'no_register_display', array('type' => $site));
            }
        }

        return urlencode($callbackurl);
    }
    public function getUrl($callbackurl)
    {
        $_SESSION['state'] = md5(uniqid(rand(), true));
        $loginUrl = 'https://graph.renren.com/oauth/authorize?response_type=code'
                    .'&client_id='.RENREN_KEY
                    .'&redirect_uri='.$this->getCallback('renren', 'bind', $callbackurl)
                    .'&state='.$_SESSION['state']
                    .'&scope=publish_feed';

        return $loginUrl;
    }
    //用户资料
    public function userInfo()
    {
        if ($_SESSION['renren']['uid']) {
            $user['id'] = $_SESSION['renren']['uid'];
            $user['uname'] = $_SESSION['renren']['uname'];
            $user['province'] = 0;
            $user['city'] = 0;
            $user['location'] = '';
            $user['userface'] = $_SESSION['renren']['userface'];
            $user['sex'] = ($_SESSION['renren']['sex'] == '1') ? 1 : 0;

            return $user;
        } else {
            //用接口获取数据
            return false;
        }
    }
    //验证用户
    public function checkUser($type = 'bind')
    {
        if ($_REQUEST['code'] && $_REQUEST['state'] == $_SESSION['state']) {
            $token_url = 'https://graph.renren.com/oauth/token?grant_type=authorization_code'
                .'&client_id='.RENREN_KEY
                .'&code='.$_REQUEST['code']
                .'&client_secret='.RENREN_SECRET
                .'&redirect_uri='.$this->getCallback('renren', $type);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $token_url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $res = json_decode($result, true);
            if ($res['user']) {
                $_SESSION['renren']['access_token']['oauth_token'] = $res['access_token'];
                $_SESSION['renren']['access_token']['oauth_token_secret'] = $res['refresh_token'];
                $_SESSION['renren']['isSync'] = 1;
                $_SESSION['renren']['uid'] = $res['user']['id'];
                $_SESSION['renren']['uname'] = $res['user']['name'];
                $_SESSION['renren']['userface'] = $res['user']['avatar'][2]['url'] ? $res['user']['avatar'][2]['url'] : '';
                $_SESSION['open_platform_type'] = 'renren';

                return $res;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    //发布一条分享
    public function update($text, $opt)
    {
        $refresh_uri = 'https://graph.renren.com/oauth/token?grant_type=refresh_token&refresh_token='.$opt['oauth_token_secret'].'&client_id='.RENREN_KEY.'&client_secret='.RENREN_SECRET;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $refresh_uri);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $res = json_decode($result);
        $access_token = $res->access_token;
        $refresh_token = $res->refresh_token;
        $config = new stdClass;
        $config->APIURL = 'http://api.renren.com/restserver.do';
        $config->APIKey = RENREN_KEY;
        $config->SecretKey = RENREN_SECRET;
        $config->APIVersion = '1.0';
        $config->decodeFormat = 'json';
        $GLOBALS['config'] = & $config;
        $rrObj = new RenrenRestApiService;

        $params = array('name' => getShort($opt['feed_content'], 30),
                        'description' => $opt['feed_content'],
                        'url' => $opt['feed_url'],
                        'image' => $opt['pic_url'],
                        'action_name' => $GLOBALS['ts']['site']['site_name'],
                        'action_link' => $opt['feed_url'],
                        'message' => '分享',
                        'access_token' => $access_token, );
        $res = $rrObj->rr_post_curl('feed.publishFeed', $params);

        return true;
    }
    //上传一个照片，并发布一条分享
    public function upload($text, $opt, $pic)
    {
        $this->update($text, $opt);

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
