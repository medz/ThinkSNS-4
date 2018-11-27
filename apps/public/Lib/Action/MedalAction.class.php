<?php
/**
 * 勋章馆.
 *
 * @author Stream
 */
class MedalAction extends Action
{
    public function __construct()
    {
        parent::__construct();
        if (!CheckTaskSwitch()) {
            $this->error('该页面不存在！');
        }
    }

    public function index()
    {
        $type = $_GET['type'] ? intval($_GET['type']) : 1;
        $uid = $_GET['uid'] ? intval($_GET['uid']) : $GLOBALS['ts']['mid'];

        if ($type == 1) {
            $user = model('User')->getUserInfo($uid);
            $medals = $user['medals'];
            if ($medals) {
                $map['id'] = array('in', getSubByKey($medals, 'id'));
                $list = model('Medal')->getList($map, 12);
            } else {
                $list['count'] = 0;
            }
            $list['user_count'] = $list['count'];
            /*勋章总数*/
            $list['all_count'] = model('Medal')->count();
            $this->assign('face', $user['avatar_middle']);
            $this->assign('spaceurl', $user['space_url']);
            $this->assign('uname', $user['uname']);
            $this->assign('uid', $user['uid']);
        } else {
            $list = model('Medal')->getList('', 12);
            $list['all_count'] = $list['count'];
            /*用户的勋章数量*/
            $user = model('User')->getUserInfo($uid);
            $medals = $user['medals'];
            if ($medals) {
                $map['id'] = array('in', getSubByKey($medals, 'id'));
                $list['user_count'] = model('Medal')->where($map)->count();
            } else {
                $list['user_count'] = 0;
            }
        }
        $isme = $uid == $this->mid ? true : false;
        $this->assign('isme', $isme);

        $lastpage = $list['nowPage'] - 1;
        $nextpage = $list['nowPage'] + 1;

        $showlast = true;
        if ($lastpage <= 0) {
            $showlast = false;
        }
        $shownext = true;
        if ($nextpage > $list['totalPages']) {
            $shownext = false;
        }
        $this->assign('lpage', $lastpage);
        $this->assign('npage', $nextpage);
        $this->assign('showlast', $showlast);
        $this->assign('shownext', $shownext);
        $this->assign('type', $type);
        $this->assign($list);
        $this->display();
    }

    /**
     * 勋章详细.
     */
    public function showdetail()
    {
        $id = intval($_GET['id']);
        $type = intval($_GET['type']);
        $medal['id'] = $id;
        if ($id) {
            if ($type == 1) {
                $umedal = D('medal_user')->where('uid='.$GLOBALS['ts']['mid'].' and medal_id='.$id)->field('`desc`,ctime')->find();
                $desc = $umedal['desc'];
                $ctime = $umedal['ctime'];
            }
            $medal = model('Medal')->where('id='.$id)->find();
            if ($medal) {
                $src = explode('|', $medal['src']);
                $medal['src'] = getImageUrl($src[1]);
                $desc && $medal['desc'] = $desc;
                $ctime && $medal['ctime'] = date('Y-m-d H:i:s', $ctime);

                //炫耀卡片
                $share_card_src = explode('|', $medal['share_card']);
                $medal['share_card'] = getImageUrl($share_card_src[1]);

                $this->assign('medal', $medal);
                $this->display();
            }
        }
    }

    /**
     * 炫耀勋章.
     */
    public function flaunt($id)
    {
        $id = intval($_POST['id']);
        if ($id) {
            $map['id'] = $id;
            $medal = model('Medal')->where($map)->find();

            if ($medal) {
                $str .= '我获得了‘'.$medal['name'].'’勋章！也是个有身份有地位的人了。快来一起做任务吧。';
                $str .= U('public/Task/index');
            }

            $feedtype = 'post';
            //炫耀卡片
            $map['name'] = $medal['name'];
            $share_card = model('Medal')->where($map)->getField('share_card');

            if ($share_card != null) {
                $share_card = explode('|', $share_card);
                $data['attach_id'] = $share_card[0];
                $feedtype = 'postimage';
            }
            $data['body'] = $str;
            $result = model('Feed')->put($this->mid, 'public', $feedtype, $data);
            echo json_encode($result);
        }
    }
}
