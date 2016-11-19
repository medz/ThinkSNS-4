<?php
/**
 * 分享赞模型
 * @version TS3.0
 */
class CommentDiggModel extends Model
{
    public $tableName = 'comment_digg';
    protected $fields = array(
            0 => 'id',
            1 => 'uid',
            2 => 'comment_id',
            3 => 'fid',
            4 => 'cTime',
            '_pk' => 'id',
    );

    public function addDigg($comment_id, $mid)
    {
        $data ['comment_id'] = $comment_id;
        $data ['uid'] = $mid;
        $data['uid'] = !$data['uid'] ? $GLOBALS['ts']['mid'] : $data['uid'];
        if (!$data['uid']) {
            $this->error = '未登录不能赞';

            return false;
        }
        $isExit = $this->where($data)->getField('id');
        if ($isExit) {
            $this->error = '你已经赞过';

            return false;
        }
        $data ['cTime'] = time();
        $res = $this->add($data);

        if ($res) {
            model('Comment')->where('comment_id='.$comment_id)->setInc('digg_count');
// 			model('Feed')->cleanCache($feed_id);
// 			model('UserData')->updateKey('unread_digg', 1, true, $feed['uid']);
// 				// dump($result);dump(model('Feed')->getLastSql());

// 			// 增加通知::  {user} 赞了你的分享：{content}。<a href="{sourceurl}" target='_blank'>去看看>></a>
// 			$author = model ( 'User' )->getUserInfo ( $mid );
// 			$config['user'] = '<a href="'.$author ['space_url'].'" >'.$author ['uname'].'</a>';

// //			$config ['content'] = t($feed ['source_content']);
// //			$config ['content'] = str_replace('◆','',$config ['content']);
// //			$config ['content'] = mStr($config ['content'], 34);
// 			$config ['sourceurl'] = $feed ['source_url'];
// 			model ( 'Notify' )->sendNotify ( $feed['uid'], 'digg', $config );

            //增加积分
            // model('Credit')->setUserCredit($mid, 'digg_weibo');
            // model('Credit')->setUserCredit($feed['uid'], 'digged_weibo');

            $this->setDiggCache($mid, $comment_id, 'add');
        }

        return $res;
    }

    public function delDigg($comment_id, $mid)
    {
        $data['comment_id'] = $comment_id;
        $data['uid'] = $mid;
        $data['uid'] = !$data['uid'] ? $GLOBALS['ts']['mid'] : $data['uid'];
        if (!$data['uid']) {
            $this->error = '未登录不能取消赞';

            return false;
        }
        $isExit = $this->where($data)->getField('id');
        if (!$isExit) {
            $this->error = '取消赞失败，您可以已取消过赞信息';

            return false;
        }

        $res = $this->where($data)->delete();

        if ($res) {
            // $feed = model('Source')->getSourceInfo('feed', $feed_id);
            $result = model('Comment')->where('comment_id='.$comment_id)->setDec('digg_count');
            // model('Feed')->cleanCache($feed_id);

            $this->setDiggCache($mid, $comment_id, 'del');
        }

        return $res;
    }
    /**
     * 返回赞列表
     * @param  unknown_type $map
     * @param  unknown_type $page  -- 是否分页
     * @param  unknown_type $limit --分页代表每页条数 不分页表示查询条数
     * @return unknown
     */
    public function getDiggList($map, $page = true, $limit = 20)
    {
        if ($page) {
            $list = $this->where($map)->order('id desc')->findPage($limit);
            foreach ($list['data'] as &$d) {
                $d['user'] = model('User')->getUserInfo($d['uid']);
                $d['feed'] = model('Feed')->getFeedInfo($d['feed_id']);
                switch ($d['feed']['app']) {
                    case 'weiba':
                        $d['feed']['from'] = getFromClient(0, $d['feed']['app'], '微吧');
                        break;
                    default:
                        $d['feed']['from'] = getFromClient($d['feed']['from'], $d['feed']['app']);
                        break;
                }
                unset($d['feed']['diggs'], $d['feed']['api_source'], $d['feed']['source_body'], $d['feed']['transpond_data']['diggs'], $d['user']['group_icon'], $d['user']['api_user_group']);
            }
        } else {
            $list = $this->where($map)->limit($limit)->order('id desc')->findAll();
            foreach ($list as &$d) {
                $d['user'] = model('User')->getUserInfo($d['uid']);
                $d['feed'] = model('Feed')->getFeedInfo($d['feed_id']);
                switch ($d['feed']['app']) {
                    case 'weiba':
                        $d['feed']['from'] = getFromClient(0, $d['feed']['app'], '微吧');
                        break;
                    default:
                        $d['feed']['from'] = getFromClient($d['feed']['from'], $d['feed']['app']);
                        break;
                }
            }
        }

        return $list;
    }
    public function getDiggListPage($map, $limit = 20)
    {
        $list = $this->where($map)->order('id desc')->findPage($limit);
        foreach ($list['data'] as &$d) {
            $d['user'] = model('User')->getUserInfo($d['uid']);
            $d['feed'] = model('Feed')->getFeedInfo($d['feed_id']);
            $d['feed']['content'] = parse_html($d['feed']['content']);
            $d['feed']['feed_content'] = parse_html($d['feed']['feed_content']);
            switch ($d['feed']['app']) {
                case 'weiba':
                    $d['feed']['from'] = getFromClient(0, $d['feed']['app'], '微吧');
                    break;
                default:
                    $d['feed']['from'] = getFromClient($d['feed']['from'], $d['feed']['app']);
                    break;
            }
        }

        return $list;
    }

    /**
     * 返回赞过的用户列表
     */
    public function getDiggUser($map, $page = true, $limit = 20)
    {
        if ($page) {
            $list = $this->where($map)->order('id desc')->findPage($limit);
        } else {
            $list['data'] = $this->where($map)->order('id desc')->limit($limit)->findAll();
        }
        foreach ($list['data'] as &$v) {
            $v['user'] = model('User')->getUserInfo($v['fid']);
        }

        return $list;
    }

    /**
     * 返回指定用户是否赞了指定的分享
     * @var    $comment_ids 指定的分享数组
     * @var    $uid         指定的用户
     * @return array
     */
    public function checkIsDigg($comment_ids, $uid)
    {
        if (! is_array($comment_ids)) {
            $comment_ids = array(
                    $comment_ids,
            );
        }

        $comment_ids = array_filter($comment_ids);

        $digg = S('user_comment_digg_'.$uid);

        if ($digg === false) {
            $map['comment_id'] = array('IN', $comment_ids);
            $map['uid'] = $uid;
            $list = $this->where($map)->field('comment_id')->findAll();
            foreach ($list as $v) {
                $res [$v ['comment_id']] = 1;
            }
            $this->setDiggCache($uid);
        } else {
            foreach ($comment_ids as $v) {
                in_array($v, $digg) && $res[$v] = 1;
            }
        }

        return $res;
    }

    public function getLastError()
    {
        return $this->error;
    }

    private function setDiggCache($uid, $comment_id, $type = 'add')
    {
        $key = 'user_comment_digg_'.$uid;
        $data = S($key);
        if (!$data) {
            $map['uid'] = $uid;
            $data = $this->where($map)->getAsFieldArray('comment_id');
        }
        if ($type === 'add') {
            array_push($data, $comment_id);
        } elseif ($type === 'del') {
            $s_key = array_search($comment_id, $data);
            if ($s_key !== false) {
                unset($data[$s_key]);
            }
        }
        S($key, array_unique($data));
    }
}
