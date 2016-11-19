<?php

class qzone
{
    private function getCallback($type = '', $callbackurl = '')
    {
        if (!$callbackurl) {
            $callbackurl = Addons::createAddonShow('Login', 'no_register_display', array('type' => $type));
        }

        return urlencode($callbackurl);
    }
    public function getUrl($callbackurl)
    {
        $_SESSION['state'] = md5(uniqid(rand(), true));

        $loginUrl = 'https://graph.qq.com/oauth2.0/authorize?response_type=code'
                    .'&client_id='.QZONE_KEY
                    .'&redirect_uri='.$this->getCallback('qzone', $callbackurl)
                    .'&state='.$_SESSION['state']
                    .'&scope=get_user_info,add_share';

        return $loginUrl;
    }
    //用户资料
    public function userInfo()
    {
        // print_r($_SESSION['qzone']);exit();
        $get_user_info = 'https://graph.qq.com/user/get_user_info?'
                .'access_token='.$_SESSION['qzone']['access_token']['oauth_token']
                .'&oauth_consumer_key='.QZONE_KEY
                .'&openid='.$_SESSION['qzone']['openid']
                .'&format=json';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $get_user_info);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $info = curl_exec($ch);
        $me = json_decode($info);
        // return $me;
        $user['id'] = $_SESSION['qzone']['openid'];
        $user['uname'] = $me->nickname;
        $user['province'] = 0;
        $user['city'] = 0;
        $user['location'] = '';
        $user['userface'] = $me->figureurl_2;
        $user['sex'] = 0;
        //print_r($user);
        return $user;
    }
    //验证用户
    public function checkUser()
    {
        if ($_REQUEST['code'] && $_REQUEST['state'] == $_SESSION['state']) {
            $token_url = 'https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id='.QZONE_KEY.'&code='.$_REQUEST['code'].'&client_secret='.QZONE_SECRET.'&redirect_uri='.$this->getCallback('qzone', $callbackurl);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $token_url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            if (strpos($response, 'callback') !== false) {
                $lpos = strpos($response, '(');
                $rpos = strrpos($response, ')');
                $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
                $msg = json_decode($response);
                if (isset($msg->error)) {
                    return false;
                    //echo "<h3>error:</h3>" . $msg->error;
                    //echo "<h3>msg  :</h3>" . $msg->error_description;
                    //exit;
                }
            }
            $params = array();
            parse_str($response, $params);

            $access_token = $params['access_token'];

            $graph_url = 'https://graph.qq.com/oauth2.0/me?access_token='.$access_token;
            curl_setopt($ch, CURLOPT_URL, $graph_url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            if (strpos($response, 'callback') !== false) {
                $lpos = strpos($response, '(');
                $rpos = strrpos($response, ')');
                $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
            }
            $user = json_decode($response);
            if (isset($user->error)) {
                echo '<h3>error:</h3>'.$user->error;
                echo '<h3>msg  :</h3>'.$user->error_description;
                exit;
            } else {
                $_SESSION['qzone']['access_token']['oauth_token'] = $access_token;
                $_SESSION['qzone']['access_token']['oauth_token_secret'] = $user->openid;
                $_SESSION['qzone']['isSync'] = 1;
                $_SESSION['qzone']['openid'] = $user->openid;
                //$_SESSION['qzone']['uid'] = $user->openid;
                //$_SESSION['qzone']['uname'] = $res['user']['name'];
                $_SESSION['open_platform_type'] = 'qzone';
            }
        } else {
            return false;
        }

        return true;
    }
    //发布一条分享
    public function update($text, $opt)
    {
        $share_url = 'https://graph.qq.com/share/add_share?'
            .'access_token='.$opt['oauth_token']
            .'&oauth_consumer_key='.QZONE_KEY
            .'&openid='.$opt['oauth_token_secret']
            .'&format=json'
            .'&title='.urlencode('心情分享')
            .'&url='.urlencode($opt['feed_url'])
            .'&comment='.urlencode($opt['feed_content'])
            .'&summary='.urlencode()
            .'&images='.urlencode($opt['pic_url']);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $share_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

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
