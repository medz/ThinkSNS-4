<?php
/**
 * ProtocolModel
 * 提供给Ts核心调用的协议类.
 */
class WeibaProtocolModel extends Model
{
    // 假删除用户数据
    public function deleteUserAppData($uidArr)
    {
        $this->_deal($uidArr, 'deleteUserAppData');
    }

    // 恢复假删除的用户数据
    public function rebackUserAppData($uidArr)
    {
        $this->_deal($uidArr, 'rebackUserAppData');
    }

    // 彻底删除用户数据
    public function trueDeleteUserAppData($uidArr)
    {
        if (empty($uidArr)) {
            return false;
        }
        $uidStr = implode(',', $uidArr);
        M('weiba')->where("uid in ($uidStr) or admin_uid in ($uidStr)")->delete();
        M('weiba_post')->where("post_uid in ($uidStr) or last_reply_uid in ($uidStr)")->delete();
        M('weiba_reply')->where("uid in ($uidStr) or post_uid in ($uidStr)")->delete();
        M('weiba_follow')->where("follower_uid in ($uidStr)")->delete();
    }

    // 共同处理方法
    public function _deal($uidArr, $type)
    {
        if (empty($uidArr)) {
            return false;
        }
        $uidStr = implode(',', $uidArr);
        $value = 0;
        if ($type == 'deleteUserAppData') {
            $value = 1;
        }
        M('weiba')->where("uid in ($uidStr) or admin_uid in ($uidStr)")->setField('is_del', $value);
        M('weiba_post')->where("post_uid in ($uidStr) or last_reply_uid in ($uidStr)")->setField('is_del', $value);
        M('weiba_reply')->where("uid in ($uidStr) or post_uid in ($uidStr)")->setField('is_del', $value);
        M('weiba_follow')->where("follower_uid in ($uidStr)")->setField('is_del', $value);
    }

    // 在个人空间里查看该应用的内容列表
    public function profileContent($uid)
    {
        if ($_GET['feed_type'] == 'weiba') {
            $sfollow = D('weiba_follow')->where('follower_uid='.$uid)->findAll();
            $map['weiba_id'] = array('in', getSubByKey($sfollow, 'weiba_id'));
            $map['is_del'] = 0;
            $map['status'] = 1;
            $map['uid'] = array('neq', $uid);
            $post_list = D('weiba')->where($map)->findPage(20);
            //帖子推荐
            $sql = 'SELECT a.* FROM `ts_weiba_post` a, `ts_weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`recommend` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.recommend_time desc LIMIT 10';
            $slist = D('weiba_post')->query($sql);
            $weiba_ids = getSubByKey($post_list['data'], 'weiba_id');
            $nameArr = $this->_getWeibaName($weiba_ids);
            foreach ($post_list['data'] as $k => $v) {
                $post_list['data'][$k]['weiba'] = $nameArr[$v['weiba_id']];
                $post_list['data'][$k]['user'] = model('User')->getUserInfo($v['post_uid']);
                $post_list['data'][$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
                // $images = matchImages($v['content']);
                // $images[0] && $list['data'][$k]['image'] = array_slice( $images , 0 , 5 );
                $image = getEditorImages($v['content']);
                !empty($image) && $post_list['data'][$k]['image'] = array($image);
                //匹配图片的src
                preg_match_all('#<img.*?src="([^"]*)"[^>]*>#i', $v['content'], $match);
                foreach ($match[1] as $imgurl) {
                    $imgurl = $imgurl;
                    if (!empty($imgurl)) {
                        $post_list['data'][$k]['img'][] = $imgurl;
                    }
                }
                $is_digg = M('weiba_post_digg')->where('post_id='.$v['post_id'].' and uid='.$this->mid)->find();
                $post_list['data'][$k]['digg'] = $is_digg ? 'digg' : 'undigg';
                $post_list['data'][$k]['content'] = t($post_list['data'][$k]['content']);
                if ($v['new_day'] != date('Y-m-d', time())) {
                    $post_list['data'][$k]['new_count'] = 0;
                    $this->setNewcount($v['weiba_id'], 0);
                }
            }
            foreach ($slist as $i => $s) {
                $user = model('User')->getUserInfo($s['post_uid']);
                $slist[$i]['weiba'] = $user['uname'];
            }
            //dump($post_list);exit;
            //dump(D ()->getLastSql());
            //dump($slist);
            $post_list['post_recommend_list'] = $slist;
            $tpl = APPS_PATH.'/weiba/Tpl/default/Index/profileWeiba.html';
        } elseif ($_GET['feed_type'] == 'cweiba') {
            $map['uid'] = $uid;
            $map['is_del'] = 0;
            if ($uid != $_SESSION['mid']) {
                $map['status'] = 1;
            }
            $map['status'] = array('in', array('0', '1'));
            $post_list = D('weiba')->where($map)->findPage(20);
            //帖子推荐
            $sql = 'SELECT a.* FROM `ts_weiba_post` a, `ts_weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`recommend` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.recommend_time desc LIMIT 10';
            $slist = D('weiba_post')->query($sql);
            foreach ($post_list['data'] as $k => $v) {
                if ($v['new_day'] != date('Y-m-d', time())) {
                    $post_list['data'][$k]['new_count'] = 0;
                    $this->setNewcount($v['weiba_id'], 0);
                }
            }
            foreach ($slist as $i => $s) {
                $user = model('User')->getUserInfo($s['post_uid']);
                $slist[$i]['weiba'] = $user['uname'];
            }
            //dump(D ()->getLastSql());
            //dump($slist);
            $post_list['post_recommend_list'] = $slist;
            $post_list['mid'] = $_SESSION['mid'];
            $tpl = APPS_PATH.'/weiba/Tpl/default/Index/profileCweiba.html';
        } else {
            $map['post_uid'] = $uid;
            $map['is_del'] = 0;
            $post_list = D('weiba_post')->where($map)->order('post_time DESC')->findPage(20);
            $weiba_ids = getSubByKey($post_list['data'], 'weiba_id');
            $nameArr = $this->_getWeibaName($weiba_ids);
            foreach ($post_list['data'] as $k => $v) {
                $post_list['data'][$k]['weiba'] = $nameArr[$v['weiba_id']];
                $post_list['data'][$k]['user'] = model('User')->getUserInfo($v['post_uid']);
                $post_list['data'][$k]['replyuser'] = model('User')->getUserInfo($v['last_reply_uid']);
                $image = getEditorImages($v['content']);
                !empty($image) && $post_list['data'][$k]['image'] = array($image);
            }
            //帖子推荐
            $sql = 'SELECT a.* FROM `ts_weiba_post` a, `ts_weiba` b WHERE a.weiba_id=b.weiba_id AND ( b.`is_del` = 0 ) AND ( b.`status` = 1 )  AND ( a.`recommend` = 1 ) AND ( a.`is_del` = 0 ) ORDER BY a.recommend_time desc LIMIT 10';
            $slist = D('weiba_post')->query($sql);
            //dump(D ()->getLastSql());
            foreach ($slist as $i => $s) {
                $user = model('User')->getUserInfo($s['post_uid']);
                $slist[$i]['weiba'] = $user['uname'];
            }
            //dump($slist);exit;
            $post_list['post_recommend_list'] = $slist;
            $tpl = APPS_PATH.'/weiba/Tpl/default/Index/profileContent.html';
        }
        $post_list['uid'] = $uid;

        return fetch($tpl, $post_list);
    }

    public function setNewcount($weiba_id, $num = 1)
    {
        $map['weiba_id'] = $weiba_id;
        $time = time();
        $weiba = D('weiba')->where($map)->find();
        if ($weiba['new_day'] != date('Y-m-d', $time)) {
            M('weiba')->where($map)->setField('new_day', date('Y-m-d', $time));
            M('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num == 0) {
            M('weiba')->where($map)->setField('new_count', 0);
        }
        if ($num > 0) {
            M('weiba')->where($map)->setField('new_count', (int) $num + (int) $weiba['new_count']);
        }

        return true;
    }

    public function getFollowStateByWeibaids($uid, $weiba_ids)
    {
        $_weibaids = is_array($weiba_ids) ? implode(',', $weiba_ids) : $weiba_ids;
        if (empty($_weibaids)) {
            return array();
        }
        $follow_data = M('weiba_follow')->where(" ( follower_uid = '{$uid}' AND weiba_id IN({$_weibaids}) ) ")->findAll();

        $follow_states = $this->_formatFollowState($uid, $weiba_ids, $follow_data);

        return $follow_states[$uid];
    }

    private function _formatFollowState($uid, $weiba_ids, $follow_data)
    {
        !is_array($weiba_ids) && $fids = explode(',', $weiba_ids);
        foreach ($weiba_ids as $weiba_ids) {
            $follow_states[$uid][$weiba_ids] = array(
                    'following' => 0,
            );
        }
        foreach ($follow_data as $r_v) {
            if ($r_v['follower_uid'] == $uid) {
                $follow_states[$r_v['follower_uid']][$r_v['weiba_id']]['following'] = 1;
            }
        }

        return $follow_states;
    }

    private function _getWeibaName($weiba_ids)
    {
        $weiba_ids = array_unique($weiba_ids);
        if (empty($weiba_ids)) {
            return false;
        }
        $map['weiba_id'] = array('in', $weiba_ids);
        $names = D('weiba')->where($map)->field('weiba_id,weiba_name')->findAll();
        foreach ($names as $n) {
            $nameArr[$n['weiba_id']] = $n['weiba_name'];
        }

        return $nameArr;
    }
}
