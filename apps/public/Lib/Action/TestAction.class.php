<?php

class TestAction extends Action
{
    public function _initialize()
    {
        header('Content-Type:text/html; charset=UTF8');
    }

    public function show()
    {
        $demo = model('SensitiveWord')->checkedContent();
        dump($demo);
    }

    public function testGit()
    {
        var_dump('Git Svn!!!2313');
    }

    public function upBug()
    {
        set_time_limit(0);
        // user list
        $sql = 'select a.uid, a.user_group_id from ts_user_group_link as a left join ts_user_group_link as b on a.uid = b.uid and a.user_group_id = b.user_group_id where a.id != b.id order by a.id asc;';
        $list = D()->query($sql);
        $uids = getSubByKey($list, 'uid');
        $uids = array_unique($uids);
        foreach ($uids as $uid) {
            $map['uid'] = $uid;
            $map['user_group_id'] = 5;
            model('UserGroupLink')->where($map)->delete();

            model('UserGroupLink')->add($map);
        }
        dump('OK');
        // $list = model('User')->find
        // $demo = model('User')->getUserInfo($this->mid);
        // dump($demo);
    }

    public function mylove()
    {
        $str = 'alipay_jilu:;atme:;attach:;attach_t:;blog:;blog_category:;channel:;channel_follow:;check_info:;collection:;comment:app_uid;comment:;comment:to_uid;credit_user:;denounce:;denounce:fuid;develop:;diy_page:;diy_widget:;document:deleteUid;document_attach:;document_draft:;document_lock:;event:;event_photo:;event_user:;feed:;feedback:;find_password:;group:;group_atme:;group_attachment:;group_comment:app_uid;group_comment:;group_comment:to_uid;group_feed:;group_invite_verify:;group_log:;group_member:;group_post:;group_topic:;group_user_count:;invite_code:inviter_uid;invite_code:receiver_uid;login:;login_logs:;login_record:;medal_user:;message_content:from_uid;message_list:from_uid;message_member:member_uid;notify_email:;notify_message:;online:;online_logs:;online_logs_bak:;poppk:cUid;poppk_vote:;poster:;sitelist_site:;survey_answer:;task_receive:;task_user:;template_record:;tipoff:;tipoff:bonus_uid;tipoff_log:;tips:;user_app:;user_blacklist:;user_category_link:;user_change_style:;user_count:;user_credit_history:;user_data:;user_department:;user_follow:;user_follow_group:;user_follow_group_link:;user_group_link:;user_official:;user_online:;user_privacy:;user_profile:;user_verified:;vote:;vote_user:;vtask:assigner_uid;vtask:deal_uid;vtask_log:;vtask_process:assigner_uid;vtask_process:deal_uid;weiba:;weiba:admin_uid;weiba_apply:follower_uid;weiba_apply:manager_uid;weiba_favorite:;weiba_favorite:post_uid;weiba_follow:follower_uid;weiba_log:;weiba_post:post_uid;weiba_post:last_reply_uid;weiba_reply:post_uid;weiba_reply:;weiba_reply:to_uid;x_article:;x_logs:';
        $arr = explode(';', $str);
        foreach ($arr as $v) {
            $info = explode(':', $v);
            $table = C('DB_PREFIX').$info[0];
            $field = empty($info[1]) ? 'uid' : $info[1];

            $sql = 'DELETE FROM '.$table.' WHERE '.$field.' NOT IN (SELECT uid FROM ts_user) ';
            M()->execute($sql);
        }

        /* 		$sql = "SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.`COLUMNS` WHERE TABLE_SCHEMA='uat_sociax' AND COLUMN_NAME LIKE '%uid%' AND DATA_TYPE='int'";
                $list = M()->query($sql);
                $str = '';
                foreach ($list as $vo){
                    $str .= str_replace('ts_','', $vo['TABLE_NAME']).':';
                    if($vo['COLUMN_NAME']=='uid'){
                        $str .= ';';
                    }else{
                        $str .= $vo['COLUMN_NAME'].';';
                    }
        
                }
                echo($str); */
    }

    public function testvideo()
    {

        // $links[] = 'http://v.youku.com/v_show/id_XNTI0OTUwNTE2.html?f=19030254';

        // $links[] = 'http://v.ku6.com/special/show_6596857/btfthNTHWhWZ-On6Z-JghQ...html';

        // $links[] = 'http://www.tudou.com/albumplay/309GI6wJ3dQ/Z023ClGghoU.html';

        // $links[] = 'http://www.tudou.com/programs/view/FAaiuw1_hdo/?fr=rec2';

        // $links[] = 'http://www.tudou.com/listplay/yDXsqh6PHmU.html';

        // $links[] = 'http://douwan.tudou.com/?code=givrrwzNd04';

        // $links[] = 'http://tv.sohu.com/20130306/n367915244.shtml';

        //$links[] = 'http://v.qq.com/cover/4/4m5fr32fb43kdhq.html?vid=d0012l06ejo';

        // $links[] = 'http://www.iqiyi.com/dianshiju/20130302/5dd726a96ce14ee3.html';

        //$links[] = 'http://video.sina.com.cn/p/news/w/v/2013-03-19/075662183493.html';

        $links[] = 'http://www.yinyuetai.com/video/628991';

        foreach ($links as $link) {
            $result = model('Video')->getVideoInfo($link);
            dump($result);
        }
    }

    public function avatar()
    {
        $info = model('Avatar')->init($this->mid)->getUserAvatar();
        dump($info);
    }

    public function refreshWeibaFeed()
    {
        set_time_limit(0);
        $sql = "update ts_feed set app_row_table='weiba_post' where app='weiba' AND type='weiba_post' AND app_row_table='feed'";
        $result = D()->execute($sql);
        if (!$result) {
            dump('update weiba_post : false');
            echo '<hr />';
        }

        $sql = "select * from ts_feed where app='weiba' AND type='repost' AND app_row_table='feed' LIMIT 100";
        $result = D()->query($sql);
        if ($result) {
            dump('update weiba_repost : ');
            dump($result);
            echo '<hr />';
        } else {
            dump('update weiba_repost : OK');
        }
        // $sql = "update ts_feed as a set a.app_row_table='weiba_post',a.app='weiba',a.type='weiba_repost',a.app_row_id=(select app_row_id from ts_feed as b where b.feed_id=a.app_row_id) where app='weiba' AND type='repost';";
        // $result = D()->execute($sql);
        // dump($result);
        // dump(D()->getLastSql());
    }

    public function getTopic()
    {
    }

    public function feed()
    {
        // dump(model('Feed')->getNodeList());
        // echo '<hr />';
        // $feed_template = simplexml_load_file(SITE_PATH.'/apps/public/Conf/post.feed.php');
        // dump($feed_template);
        $actor = '刘晓庆';
        $feedIds = array(37);
        $res = model('Feed')->getFeeds($feedIds);
        dump($res);
    }

    public function cloudimage()
    {
        $this->display();
    }

    //下面是一些测试函数
    public function credit()
    {
        dump(model('Credit')->getUserCredit(14983));
        // model('Credit')->setUserCredit(14983,'weibo_add');
        // model('Credit')->setUserCredit(14983,'weibo_add');
        // model('Credit')->setUserCredit(14983,'weibo_add');
        // dump(model('Credit')->getUserCredit(14983));
    }

    public function hello()
    {
        echo '服务器当前时间:',date('Y-m-d H:i:s');
        dump('hello world');
        echo 11111111;
    }

    public function t()
    {
        dump(model('UserPrivacy')->getPrivacy(10000, 14983));
    }

    public function at($data)
    {
        $html = '@{uid=14983|yangjiasheng}';
        echo parse_html($html);
    }

    public function mail()
    {
        dump(model('Mail')->send_email('yangjs17@yeah.net', 'ttt', 'xxxxxxxx'));
    }

    public function cut()
    {
        getThumbImage('./data/upload/2012/0604/19/4fcc9b2f67d34.jpg', '300', 'auto', false);
        echo '<img src="./data/upload/2012/0604/19/4fcc9b2f67d34_300_auto.jpg">';
        echo 11;
    }

    public function data()
    {
        $nums = 1000; //测试数
        $add = array();
        $add['version'] = 1;
        $add['language'] = 'all';
        $add['source_faq_id'] = 0;
        $add['creator_uid'] = $this->mid;
        $add['create_time'] = time();
        $add['comment_count'] = 0;
        for ($i = 0; $i < $nums; $i++) {
            $add['status'] = rand(0, 2);
            $add['active'] = rand(0, 1);
            $add['category_id'] = rand(1, 4);
            $add['tags'] = '测试tag'.$i;
            $add['question_cn'] = '这里是测试的问题中文'.$i;
            $add['question_en'] = 'here is  test question english'.$i;
            $add['answer_cn'] = '这里是测试的答案'.$i;
            $add['answer_en'] = 'here is test answer english'.$i;
            D('')->table('sociax_support')->add($add);
        }
    }

    public function initdata()
    {
        D('PublicSearch', 'public')->initData();
    }

    public function tsearch()
    {
        D('PublicSearch', 'public')->search();
    }

    public function findLang()
    {

        // - app -
        $filePath[] = SITE_PATH.'/apps/public';
        $filePath[] = SITE_PATH.'/apps/support';
        $filePath[] = SITE_PATH.'/apps/contact';
        $filePath[] = SITE_PATH.'/apps/admin';
        $filePath[] = SITE_PATH.'/apps/task';

        $filelist = array();

        foreach ($filePath as $v) {
            $filelist[$v] = $this->getDir($v);
        }

        $findLang = array();

        foreach ($filelist as $vlist) {
            foreach ($vlist as $v) {
                $ext = substr($v, strrpos($v, '.') + 1, strlen($v));
                $data = file_get_contents($v);

                if ($ext == 'php' || $ext == 'js') {
                    $data = preg_replace("!((/\*)[\s\S]*?(\*/))|(//.*)!", '', $data); //去掉注释里的中文
                }
                preg_match_all('/([\x{4e00}-\x{9fa5}])+/u', $data, $result);
                if (!empty($result[0])) {
                    $findLang[$v] = $result[0];
                }
            }
        }
        dump($findLang);
    }

    public function getDir($dir, $list = array())
    {
        $dirs = new Dir($dir);
        $dirs = $dirs->toArray();
        foreach ($dirs as $v) {
            if ($v['isDir']) {
                $list = $this->getDir($v['pathname'], $list);
            } elseif ($v['isFile'] && in_array($v['ext'], array('php', 'html', 'js'))) {
                $list[] = $v['pathname'];
            } else {
                continue;
            }
        }

        return $list;
    }

    //下面是一些demo
    public function demo()
    {
        $this->display();
    }

    public function getImageInfo()
    {
        $data = getImageInfo('2012/1110/18/509e2b90e7a89.jpeg');
        dump($data);
    }

    public function doupload()
    {
        dump($_POST);
    }

    public function dig()
    {
        $data = model('Tips')->findAll();
        $count0 = 0;
        $count1 = 0;
        foreach ($data as $value) {
            $value['type'] == 1 ? $count1++ : $count0++;
        }

        $this->assign('count0', $count0);
        $this->assign('count1', $count1);
        $this->assign('data', $data);
        $this->display();
    }

    public function tree()
    {
        $category = array(
            array('id' => 1, 'name' => 'A1', 'pid' => 0),
            array('id' => 2, 'name' => 'A2', 'pid' => 1),
            array('id' => 3, 'name' => 'A3', 'pid' => 1),
            array('id' => 4, 'name' => 'A4', 'pid' => 2),
            array('id' => 5, 'name' => 'A5', 'pid' => 4),
            array('id' => 6, 'name' => 'A6', 'pid' => 3),
        );

        print_r($this->_tree($category));
    }

    public function _tree($data)
    {

            //所有节点的子节点
        $child = array();
        //hash缓存数组
        $hash = array();
        foreach ($data as $dv) {
            $hash[$dv['id']] = $dv;
            $tree[$dv['id']] = $dv;
            !isset($child[$dv['id']]) && $child[$dv['id']] = array();
            $tree[$dv['id']]['_child'] = &$child[$dv['id']];
            $child[$dv['pid']][] = &$tree[$dv['id']];
        }

        return $child[0];
    }

    public function plot()
    {
        $this->display();
    }

    public function autotag()
    {
        //需要提取的文本
        $text = ' 这里asxasx C++ ,test我也有很多T恤衣服！！！';
        //获取model
        $tagX = model('Tag');
        //设置text
        $tagX->setText($text);
        //获取前10个标签
        $result = $tagX->getTop(10);

        echo '<pre>';
        echo 'text:',$text;
        echo '<br/>结果:',$result;
        echo '
		
		---使用举例---
		
		核心php实现 
		( ajax 请求地址： public/Index/getTags  参数：text(内容)，limit:提取的标签个数 ）

		//需要提取的文本
		$text = "这里asxasx C++ ,test我也有很多T恤衣服！！！";
		//获取model
		$tagX = model("Tag");
		//设置text
		$tagX->setText($text);
		//获取前10个标签
		$result = $tagX->getTop(10);';
        echo '</pre>';
    }

    /**
     * 生成语言文件.
     */
    public function createLangPhpFile()
    {
        set_time_limit(0);
        // 判断文件夹路径是否存在
        if (!file_exists(LANG_PATH)) {
            mkdir(LANG_PATH, 0777);
        }
        $data = model('Lang')->where($map)->order('lang_id asc')->findAll();
        $fileName = LANG_PATH.'/langForLoadUpadte.php';
        // 权限处理
        $fp = fopen($fileName, 'w+');
        $fileData = "<?php\n";
        $fileData .= "return array(\n";
        foreach ($data as $val) {
            $val['zh-cn'] = htmlspecialchars($val['zh-cn'], ENT_QUOTES);
            $val['en'] = htmlspecialchars($val['en'], ENT_QUOTES);
            $val['zh-tw'] = htmlspecialchars($val['zh-tw'], ENT_QUOTES);
            $content[] = "'{$val['key']}-{$val['appname']}-{$val['filetype']}'=>array(0=>'{$val['zh-cn']}',1=>'{$val['en']}',2=>'{$val['zh-tw']}',)";
        }
        $fileData .= implode(",\n", $content);
        $fileData .= "\n);";
        fwrite($fp, $fileData);
        fclose($fp);
        unset($fileData);
        unset($content);
        @chmod($fileName, 0775);
    }

    /**
     * 获取生成的语言文件�
     * 容.
     */
    public function getzLang()
    {
        $a = include LANG_PATH.'/langForLoadUpadte.php';
        dump($a);
    }

    public function initLang()
    {
        model('Lang')->createCacheFile('public', 0);
        model('Lang')->createCacheFile('public', 1);
        model('Lang')->createCacheFile('channel', 0);
        model('Lang')->createCacheFile('channel', 1);
    }

    public function initLangPHP()
    {
        $lang = include CONF_PATH.'/lang/ask_zh-cn2.php';
        $sql = 'insert into sociax_lang (`key`,`appname`,`filetype`,`zh-cn`) VALUES ';
        foreach ($lang as $k => $v) {
            $k = trim($k);
            $v = trim($v);
            $sqlArr[] = " ('{$k}','ASK','0','{$v}') ";
        }
        $sql = $sql.implode(',', $sqlArr).';';
        D('')->query($sql);
        echo  model()->getError();
        echo '<br/>',$sql,'<br/>';
    }

    // public function initLangJS(){
    // 	$lang = include(CONF_PATH.'/lang/public_zh-cn.js');
    // 	$sql = "insert into sociax_lang (`key`,`appname`,`filetype`,`zh-cn`) VALUES ";
    // 	foreach($lang as $k=>$v){
    // 		$sqlArr[] =" ('{$k}','PUBLIC','1','{$v}') ";
    // 	}
    // 	$sql = $sql.implode(',', $sqlArr).';';
    // 	D('')->query($sql);
    // }

    public function editLang()
    {
        // $lang = include(CONF_PATH.'/lang/public_en.js-');
        // foreach($lang as $k=>$v){
        // 	$map = $save = array();
        // 	$map['filetype'] = 1;
        // 	$map['key'] = $k;
        // 	$save['en'] = $v;
        // 	D('')->table('sociax_lang')->where($map)->save($save);
        // }

        // $lang = include(CONF_PATH.'/lang/public_zh-cn.js-');
        // foreach($lang as $k=>$v){
        // 	$map = $save = array();
        // 	$map['filetype'] = 1;
        // 	$map['key'] = $k;
        // 	$save['zh-cn'] = $v;
        // 	D('')->table('sociax_lang')->where($map)->save($save);
        // }

        // $lang = include(CONF_PATH.'/lang/public_zh-tw.js');
        // foreach($lang as $k=>$v){
        // 	$map = $save = array();
        // 	$map['filetype'] = 1;
        // 	$map['key'] = $k;
        // 	$save['zh-tw'] = $v;
        // 	D('')->table('sociax_lang')->where($map)->save($save);
        // }

        $lang = include CONF_PATH.'/lang/ask_en.php';
        foreach ($lang as $k => $v) {
            $map = $save = array();
            $map['filetype'] = 0;
            $map['key'] = $k;
            $save['en'] = $v;
            D('')->table('sociax_lang')->where($map)->save($save);
        }

        $lang = include CONF_PATH.'/lang/ask_zh-tw.php';
        foreach ($lang as $k => $v) {
            $map = $save = array();
            $map['filetype'] = 0;
            $map['key'] = $k;
            $save['zh-tw'] = $v;
            D('')->table('sociax_lang')->where($map)->save($save);
        }
    }

    public function langEdit()
    {
        $data = include CONF_PATH.'/lang/tt.php';
        foreach ($data as $k => $v) {
            $key = trim($k);
            $value = trim($v);
            $map['key'] = $key;
            $save['en'] = $value;
            model('Lang')->where($map)->save($save);
        }
    }

    public function updateCategorySort()
    {
        $stable = t($_GET['t']);
        !empty($stable) && model('CategoryTree')->setTable($stable)->updateSort();
    }

    public function tt()
    {
        // $file = fopen(CONF_PATH.'/lang/t.php','r');
        // while(!feof($file)){
        // 	$d = fgets($file,'4096');
        // 	$data[] = trim($d)."',";
        // }
        // fclose($file);
        // //生成文件
        // $fileData = implode("\n",$data);
        // $fp = fopen(CONF_PATH.'/lang/tt.php','w+');
        // fwrite($fp, $fileData);
        // fclose($fp);
    }

    public function followTest()
    {
        model('Friend')->_getRelatedUserFromFriend(11860);
        model('Friend')->_getNewRelatedUser();
    }

    public function testSVn()
    {
        dump('CESHI');
    }

    public function testUserData()
    {
        model('UserData')->updateUserData();
    }

    public function addChannelData()
    {
        set_time_limit(0);
        $body = array(
                    '秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，秋天去哪里看落叶，',
                    '随手拍花朵，夏天森林公园行',
                    '这个焦点怎么没有乱。。。',
                    '春季一定要吃的家常菜 - 知识 - 这个菜炒出来卖相不好看，所以怎么拍的好看是个难题，点缀了红辣椒、葱丝和香菜叶之后总算能见人了。至于香椿和鸡蛋的比例，随个人喜欢，可以香椿多，也可以鸡蛋多，这个看个人啦',
                    '部发表于18世纪的法国传奇小说，种下了魔力的种子，从文字到影像穿越时空开花结果。从法国宫廷、美国校园到韩国贵族，再到中国老上海，历经数个电影版本 的演绎，萦绕在《危险关系》戏里戏外的故事，自然生长，如镜子般折射时代、国别、名利场，一次次赋予古老的原著新鲜的生命力。本周四，章子怡、',
                    '分享图片',
                );
        $attach = array(2352, 2351, 2350, 2349);
        $data['content'] = '';
        $type = 'postimage';
        $app = 'public';
        for ($i = 0; $i < 150; $i++) {
            $data['body'] = $body[rand(0, 5)];
            $data['attach_id'] = $attach[rand(0, 4)];
            $result = model('Feed')->put($this->uid, $app, $type, $data);
            $add['channel_category_id'] = 13;
            $add['feed_id'] = $result['feed_id'];
            D('channel')->add($add);
        }
    }

    // 刷新图片数据
    public function updateImgData()
    {
        set_time_limit(0);
        $data = D('channel')->findAll();
        foreach ($data as $value) {
            $feedInfo = model('Feed')->get($value['feed_id']);
            if ($feedInfo['type'] == 'postimage') {
                $feedData = unserialize($feedInfo['feed_data']);
                $imgAttachId = is_array($feedData['attach_id']) ? $feedData['attach_id'][0] : $feedData['attach_id'];
                $attach = model('Attach')->getAttachById($imgAttachId);
                $path = UPLOAD_PATH.'/'.$attach['save_path'].$attach['save_name'];
                $imageInfo = getimagesize($path);
                $up['height'] = intval(ceil(195 * $imageInfo[1] / $imageInfo[0]));
                $up['width'] = 195;
                D('channel')->where('feed_id='.$value['feed_id'])->save($up);
            }
        }
    }

    /**
     * 插�
     * �Ts2.8用户信息.
     */
    public function insertTsUser()
    {
        set_time_limit(0);
        // 获取插入用户数据
        $data = D('old_user')->field('email, password, sex, uname')->findAll();

        foreach ($data as $value) {
            $user['uname'] = $value['uname'];
            $salt = rand(11111, 99999);
            $user['login_salt'] = $salt;
            $user['login'] = $value['email'];
            $user['email'] = $value['email'];
            $user['password'] = md5($value['password'].$salt);
            $user['ctime'] = time();
            $user['first_letter'] = getFirstLetter($value['uname']);
            $user['sex'] = ($value['sex'] == 0) ? 1 : 2;
            $user['is_audit'] = 1;
            $user['is_active'] = 1;
            // 添加用户
            $result = model('User')->add($user);
            // 添加用户组
            model('UserGroupLink')->domoveUsergroup($result, 3);
        }
    }

    public function testpiny()
    {
        $unames = model('User')->field('uid,uname')->findAll();
        foreach ($unames as $u) {
            if (preg_match('/[\x7f-\xff]+/', $u['uname'])) {
                model('User')->setField('search_key', $u['uname'].' '.model('PinYin')->Pinyin($u['uname']), 'uid='.$u['uid']);
            } else {
                model('User')->setField('search_key', $u['uname'], 'uid='.$u['uid']);
            }
        }
    }

    public function testSort()
    {
        dump(111);
        model('CategoryTree')->setTable('area')->updateSort();
        dump(222);
    }

    public function testrm()
    {
        dump(111);
        model('CategoryTree')->setTable('area')->rmTreeCategory();
        dump(222);
    }

    public function testfeed()
    {
        $feedInfo = model('Feed')->get(5210);
        dump($feedInfo);
    }

    public function updateChannelUid()
    {
        set_time_limit(0);
        $channels = D('Channel', 'channel')->findAll();
        foreach ($channels as $value) {
            $feedInfo = model('Feed')->get($value['feed_id']);
            $data['uid'] = $feedInfo['uid'];
            D('channel')->where('feed_channel_link_id='.$value['feed_channel_link_id'])->save($data);
        }
    }

    public function testChannel()
    {
        $channels = D('ChannelApi', 'channel')->getAllChannel();
        dump($channels);
    }

    public function testnav()
    {
        $topNav = model('Navi')->getTopNav();
        dump($topNav);
        dump($GLOBALS['ts']['site_top_nav']);
        $bottomNav = model('Navi')->getBottomNav();
        $this->assign('site_top_nav', $topNav);
        $this->assign('site_bottom_nav', $bottomNav);
    }

    public function testTime()
    {
        dump(time());
        dump(friendlyDate(time()));
    }

    public function updataStorey()
    {
        $map['data'] = array('neq', 'N;');
        $commentlist = D('comment')->where($map)->findAll();
        foreach ($commentlist as $v) {
            $data = unserialize($v['data']);
            if ($data['storey']) {
                D('comment')->where('comment_id='.$v['comment_id'])->setField('storey', $data['storey']);
            }
        }
    }

    /**
     * Google翻译API.
     *
     * @return [type] [description]
     */
    private function translatorGoogleAPI($text, $tl = 'zh-CN', $sl = 'auto', $ie = 'UTF-8')
    {
        $ch = curl_init('http://translate.google.cn/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "&hl=zh-CN&sl={$sl}&ie={$ie}&tl={$tl}&text=".urlencode($text));
        $html = curl_exec($ch);
        preg_match('#<span id=result_box class="short_text">(.*?)</span></div>#', $html, $doc);

        return strip_tags($doc['1'], '<br>');
    }

    public function translateLang()
    {
        set_time_limit(0);
        $sql = 'SELECT `lang_id` ,`zh-cn` FROM `'.C('DB_PREFIX')."lang` WHERE  appname = 'PUBLIC' AND `en` = '==**==' AND `zh-tw` LIKE '%ts3/apps/%' ORDER BY lang_id ASC;";
        $data = D()->query($sql);
        foreach ($data as $value) {
            $en = $this->translatorGoogleAPI($value['zh-cn'], 'en');
            $tw = $this->translatorGoogleAPI($value['zh-cn'], 'zh-TW');
            $insert_sql = 'UPDATE `'.C('DB_PREFIX')."lang` SET `en` = '".$en."', `zh-tw` = '".$tw."' WHERE `lang_id` = '".$value['lang_id']."' LIMIT 1;";
            // dump($insert_sql);
            D()->execute($insert_sql);
            // dump($en);
            // dump($tw);
        }
    }

    public function upUserData()
    {
        set_time_limit(0);
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $count = 10;
        $limit = ($p - 1) * $count.', '.$count;
        $list = model('User')->limit($limit)->getAsFieldArray('uid');

        if (empty($list)) {
            dump('OK');
            exit;
        } else {
            foreach ($list as $uid) {
                if (empty($uid)) {
                    continue;
                }

                // 总分享数目和未假删除的分享数目
                $sql = 'SELECT count(feed_id) as total, SUM(is_del) as delSum FROM '.C('DB_PREFIX').'feed where uid ='.$uid;
                $vo = M()->query($sql);
                $res['feed_count'] = intval($vo[0]['total']);
                $res['weibo_count'] = $res['feed_count'] - intval($vo[0]['delSum']);
                // 收藏数目
                $sql = 'SELECT count(collection_id) as total FROM '.C('DB_PREFIX').'collection where uid ='.$uid;
                $vo = M()->query($sql);
                $res['favorite_count'] = intval($vo[0]['total']);

                // 关注数目
                $sql = 'SELECT count(follow_id) as total FROM '.C('DB_PREFIX').'user_follow where uid ='.$uid;
                $vo = M()->query($sql);
                $res['following_count'] = intval($vo[0]['total']);

                // 粉丝数目
                $sql = 'SELECT count(follow_id) as total FROM '.C('DB_PREFIX').'user_follow where fid ='.$uid;
                $vo = M()->query($sql);
                $res['follower_count'] = intval($vo[0]['total']);

                $map['uid'] = $uid;
                $map['key'] = array('in', array('feed_count', 'weibo_count', 'favorite_count', 'following_count', 'follower_count'));
                M('UserData')->where($map)->delete();

                $sql = 'INSERT INTO '.C('DB_PREFIX').'user_data (`uid`,`key`,`value`) values';
                $data['uid'] = $uid;
                $k = 0;
                foreach ($res as $key => $val) {
                    if ($k == 0) {
                        $sql .= " ($uid,'$key','$val')";
                    } else {
                        $sql .= " , ($uid,'$key','$val')";
                    }
                    $k++;
                }
                $rr = M()->execute($sql);
                dump($sql);
                $sql = '';
                // 清掉该用户的缓存
                model('Cache')->rm('UserData_'.$uid);

                if ($rr) {
                    echo $uid.' -- done -- <br />';
                } else {
                    echo  $uid.' -- error -- <br />';
                }
            }

            $p += 1;
            echo '<script>window.location.href="'.U('public/Test/upUserData', array('p' => $p)).'";</script>';
        }
    }

    /**
     * 转移2.8头像为3.0头像地址
     */
    public function upFacePath()
    {
        set_time_limit(0);
        $p = isset($_GET['p']) ? intval($_GET['p']) : 1;
        $count = 1000;
        $limit = ($p - 1) * $count.', '.$count;
        $list = model('User')->limit($limit)->getAsFieldArray('uid');

        if (empty($list)) {
            dump('OK');
            exit;
        } else {
            foreach ($list as $uid) {
                if (empty($uid)) {
                    continue;
                }

                $oldPath = UPLOAD_PATH.'/avatar/'.$uid.'/big.jpg';
                if (!file_exists($oldPath)) {
                    continue;
                }

                // $uid = 10045;
                $path = UPLOAD_PATH.'/avatar'.model('Avatar')->convertUidToPath($uid);
                $this->_createFolder($path);

                $res = copy($oldPath, $path.'/original.jpg');
                echo $oldPath.'  =>  '.$path.'/original.jpg'.'  '.$res.'<br/>';
            }

            $p += 1;
            echo '<script>window.location.href="'.U('public/Test/upFacePath', array('p' => $p)).'";</script>';
        }
    }

    /**
     * 创建多级文件目录.
     *
     * @param string $path 路径名称
     */
    private function _createFolder($path)
    {
        if (!is_dir($path)) {
            $this->_createFolder(dirname($path));
            mkdir($path, 0777, true);
        }
    }

    /**
     * 生成后台菜单�
     * �置文件.
     *
     * @return [type] void
     */
    public function createSystemConfigPhpFile()
    {
        set_time_limit(0);
        // 判断文件夹路径是否存在
        if (!file_exists(LANG_PATH)) {
            mkdir(LANG_PATH, 0777);
        }
        $data = D('system_config')->findAll();
        // foreach($data as &$val) {
        // 	$val['value'] = unserialize($val['value']);
        // 	$content[] = "'{$val['key']}'=>{$val['value']}";
        // }
        // dump($data);exit;
        $fileName = LANG_PATH.'/system_config.php';
        // 权限处理
        $fp = fopen($fileName, 'w+');
        $fileData = "<?php\n";
        $fileData .= "return array(\n";
        foreach ($data as $val) {
            $val['value'] = unserialize($val['value']);
            $arr = 'array(';
            if ($val['value']['key']) {
                $arr .= '\'key\'=>array(';
                foreach ($val['value']['key'] as $k0 => $v0) {
                    $arr .= '\''.$k0.'\'=>\''.htmlspecialchars($v0, ENT_QUOTES).'\',';
                }
                $arr .= '),';
            }
            if ($val['value']['key_name']) {
                $arr .= '\'key_name\'=>array(';
                foreach ($val['value']['key_name'] as $k1 => $v1) {
                    $arr .= '\''.$k1.'\'=>\''.htmlspecialchars($v1, ENT_QUOTES).'\',';
                }
                $arr .= '),';
            }
            if ($val['value']['key_hidden']) {
                $arr .= '\'key_hidden\'=>array(';
                foreach ($val['value']['key_hidden'] as $k2 => $v2) {
                    $arr .= '\''.$k2.'\'=>\''.htmlspecialchars($v2, ENT_QUOTES).'\',';
                }
                $arr .= '),';
            }
            if ($val['value']['key_type']) {
                $arr .= '\'key_type\'=>array(';
                foreach ($val['value']['key_type'] as $k3 => $v3) {
                    $arr .= '\''.$k3.'\'=>\''.htmlspecialchars($v3, ENT_QUOTES).'\',';
                }
                $arr .= '),';
            }
            if ($val['value']['key_default']) {
                $arr .= '\'key_default\'=>array(';
                foreach ($val['value']['key_default'] as $k4 => $v4) {
                    $arr .= '\''.$k4.'\'=>\''.htmlspecialchars($v4, ENT_QUOTES).'\',';
                }
                $arr .= '),';
            }
            if ($val['value']['key_tishi']) {
                $arr .= '\'key_tishi\'=>array(';
                foreach ($val['value']['key_tishi'] as $k5 => $v5) {
                    $arr .= '\''.$k5.'\'=>\''.htmlspecialchars($v5, ENT_QUOTES).'\',';
                }
                $arr .= '),';
            }
            if ($val['value']['key_javascript']) {
                $arr .= '\'key_javascript\'=>array(';
                foreach ($val['value']['key_javascript'] as $k6 => $v6) {
                    $arr .= '\''.$k6.'\'=>\''.htmlspecialchars($v6, ENT_QUOTES).'\',';
                }
                $arr .= ')';
            }
            $arr .= ')';
            $content[] = "'{$val['key']}-{$val['list']}'=>".$arr;
        }
        $fileData .= implode(",\n", $content);
        $fileData .= "\n);";
        fwrite($fp, $fileData);
        fclose($fp);
        unset($fileData);
        unset($content);
        @chmod($fileName, 0775);
    }

    /**
     * 获得后台菜单�
     * �置文件.
     *
     * @return [type] void
     */
    public function getzLang1()
    {
        $a = include LANG_PATH.'/langForLoadUpadte.php';
        dump($a);
    }

    public function test_ffmpeg()
    {
        $ffmpegpath = '/usr/local/bin/ffmpeg/ffmpeg';
        $input = SITE_PATH.'/data/video/2014/0526/5382f24121cb6.mp4';
        // // $output = SITE_PATH.'/data/video/2014/0422/535662ba3394a0.mp4';
        // dump($input);
        // dump($output);
        // $ffmpegpath = SITE_PATH.'/ffmpeg.exe';
        set_time_limit(0);
        $command = "$ffmpegpath -i ".$input." 2>&1 | grep 'Duration' | cut -d ' ' -f 4 | sed s/,//";
        // $command = 'E:\xampp\htdocs\t4\ffmpeg.exe -i E:\xampp\htdocs\t4\data\video\2014\0526\source\5382eba8561cb.mp4';
        // $command = "/usr/local/bin/ffmpeg/ffmpeg -y -i /home/wwwroot/dev.thinksns.com/t4/data/video/2014/0523/source/537ec589ce479.mp4 -vcodec libx264 /home/wwwroot/dev.thinksns.com/t4/data/video/2014/0523/537ec589ce479.mp4";
        dump($command);
        dump(exec($command));
    }

    public function credit_rules()
    {
        dump(model('Credit')->getCreditRules());
    }

    public function info()
    {
        $c = model('Credit')->setUserCredit($this->mid, 'user_login', '1', array('user' => '<a href="http://www.baidu.com">呵呵</a>', 'content' => '<a href="http://www.google.com">hh</a>'));
    }
}
