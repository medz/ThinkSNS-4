<?php

class SelectFriendsAction extends Action
{
    public function getOne()
    {
        $name = t(urldecode($_GET['name']));
        $db_prefix = C('DB_PREFIX');
        $Model = M('');
        if ($name) {
            //从我关注的人中找
            $followings = $Model->field('follow.fid AS fuid,user.uname AS funame')
                                ->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.fid=user.uid")
                                ->where("follow.uid={$this->mid} AND user.uname LIKE '%{$name}%'")
                                ->order('follow.follow_id DESC')
                                ->limit(10)
                                ->findAll();
            //从我的粉丝中找
            $followers = $Model->field('follow.uid AS fuid,user.uname AS funame')
                               ->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.uid=user.uid")
                               ->where("follow.fid={$this->mid} AND user.uname LIKE '%{$name}%'")
                               ->order('follow.follow_id DESC')
                               ->limit(10)
                               ->findAll();
        }
        //合并，并过滤重复
        is_array($followings) || $followings = array(); //查询返回空时，不为数组，则需转为空数组
        is_array($followers) || $followers = array();
        $follow = $this->unique_arr(array_merge($followings, $followers));
        foreach ($follow as $k => $v) {
            $out[$k]['fUid'] = $v['0'];
            $out[$k]['friendUserName'] = $v['1'];
            $out[$k]['friendHeadPic'] = getUserFace($v['0']);
        }

        exit(json_encode($out));
    }

    public function getAll()
    {
        $typeId = intval($_GET['typeId']);
        empty($typeId) && $typeId = 2;
        $db_prefix = C('DB_PREFIX');
        if ($typeId == 2) {
            $follow = M('')->field('follow.fid AS fuid,user.uname AS funame')
                           ->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.fid=user.uid")
                           ->where("follow.uid={$this->mid}")
                           ->order('follow.follow_id DESC')
                           ->findPage(15);
        } elseif ($typeId == 3) {
            $follow = M('')->field('follow.uid AS fuid,user.uname AS funame')
                           ->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.uid=user.uid")
                           ->where("follow.fid={$this->mid}")
                           ->order('follow.follow_id DESC')
                           ->findPage(15);
        } else {
            //目前没有好的方案高效的显示互粉的列表人.
            //默认显示互粉
            // $follow = M('')->field('follow.fid AS fuid,user.uname AS funame')
                // 			   ->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.fid=user.uid")
                // 			   ->where("follow.uid={$this->mid} AND follow.fid IN (SELECT uid FROM {$db_prefix}user_follow WHERE fid={$this->mid})")
                // 			   ->order('follow.follow_id DESC')
                // 			   ->findPage(15);

                //$follow = M('User')->field('uid AS fuid,uname AS funame')->where("uid IN (SELECT uid FROM {$db_prefix}user_follow WHERE fid={$this->mid}) AND uid IN (SELECT fid FROM {$db_prefix}user_follow WHERE uid={$this->mid})")->findPage(15);

                //$follow = M('')->query("SELECT follow.fid AS fuid,user.uname AS funame FROM {$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.fid=user.uid WHERE follow.uid={$this->mid} AND follow.type={$typeId}");
        }

        foreach ($follow['data'] as $k => $v) {
            $out[$k]['fUid'] = $v['fuid'];
            $out[$k]['friendUserName'] = getShort($v['funame'], '3', '…');
            $out[$k]['friendHeadPic'] = getUserFace($v['fuid']);
        }

        exit(json_encode($out));
    }

    public function getType()
    {
        //$map = "uid = 0 or uid = ".$this->mid;
            //$friendType = D("FriendGroup")->where($map)->field("id,name")->findAll();
            $typeId = array(
                //互粉的人性能有问题，不显示这个列表的用户了
                            //array('id'=>1,'name'=>L('follow_each_other')),
                            array('id' => 2, 'name' => '我关注的'),
                            array('id' => 3, 'name' => '我的粉丝'),
                          );
        echo json_encode($typeId);
    }

    public function getCount()
    {
        //$gid = $_GET["typeId"]?intval($_GET["typeId"]):false;
            //echo $this->api->friend_getFriNum($this->mid,$gid);
            $typeId = intval($_GET['typeId']);
        empty($typeId) && $typeId = 2;
        $db_prefix = C('DB_PREFIX');
        if ($typeId == 2) {
            $followNum = M('')->field('follow.fid AS fuid,user.uname AS funame')->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.fid=user.uid")->where("follow.uid={$this->mid}")->order('follow.follow_id DESC')->count();
        } elseif ($typeId == 3) {
            $followNum = M('')->field('follow.uid AS fuid,user.uname AS funame')->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.uid=user.uid")->where("follow.fid={$this->mid}")->order('follow.follow_id DESC')->count();
        } else {
            //默认显示互粉
                // $followNum = M('')->field('follow.fid AS fuid,user.uname AS funame')->table("{$db_prefix}user_follow AS follow LEFT JOIN {$db_prefix}user AS user ON follow.fid=user.uid")->where("follow.uid={$this->mid} AND follow.fid IN (SELECT uid FROM {$db_prefix}user_follow WHERE fid={$this->mid})")->order('follow.follow_id DESC')->count();
        }
        echo $followNum;
    }

    // 二维数组去重复
    public function unique_arr($array2D)
    {
        foreach ($array2D as &$v) {
            $v = implode(',', $v);  //降维,也可以用implode,将一维数组转换为用逗号连接的字
        }
        $array2D = array_unique($array2D);    //去掉重复的字符串,也就是重复的一维数组
        foreach ($array2D as &$v) {
            $v = explode(',', $v);   //再将拆开的数组重新组装
        }

        return $array2D;
    }
}
