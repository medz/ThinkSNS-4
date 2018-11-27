<?php

class WeibaReplyDiggModel extends Model
{
    protected $tableName = 'weiba_reply_digg';

    public function addDigg($row_id, $mid)
    {
        $data['row_id'] = $row_id;
        $data['uid'] = $mid;
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

        $data['cTime'] = time();
        $res = $this->add($data);
        if ($res) {
            D('weiba_reply')->where('reply_id='.$row_id)->setInc('digg_count');

            $this->setDiggCache($mid, $row_id, 'add');
        }

        return $res;
    }

    public function delDigg($row_id, $mid)
    {
        $data['row_id'] = $row_id;
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
            D('weiba_reply')->where('reply_id='.$row_id)->setDec('digg_count');
            $reply = D('weiba_reply')->where('reply_id='.$row_id)->find();
            if ($reply) {
                model('UserData')->updateKey('unread_digg_weibareply', 1, true, $reply['post_uid']);
            }

            $this->setDiggCache($mid, $row_id, 'del');
        }

        return $res;
    }

    /**
     * 返回指定用户是否赞了指定的分享.
     *
     * @var 指定的分享数组
     * @var $uid                  指定的用户
     *
     * @return array
     */
    public function checkIsDigg($row_ids, $uid)
    {
        $res = array();

        !is_array($row_ids) && $row_ids = array($row_ids);
        $row_ids = array_filter($row_ids);

        $digg = S('weiba_user_digg_'.$uid);

        if ($digg === false) {
            $map['row_id'] = array('IN', $row_ids);
            $map['uid'] = $uid;
            $list = $this->where($map)->field('row_id')->findAll();
            foreach ($list as $v) {
                $res[$v['row_id']] = 1;
            }
            $this->setDiggCache($uid);
        } else {
            foreach ($row_ids as $v) {
                in_array($v, $digg) && $res[$v] = 1;
            }
        }

        return $res;
    }

    public function getLastError()
    {
        return $this->error;
    }

    private function setDiggCache($uid, $feedId = 0, $type = 'add')
    {
        $key = 'weiba_reply_digg_'.$uid;
        $data = S($key);
        if (!$data) {
            $map['uid'] = $uid;
            $data = $this->where($map)->getAsFieldArray('row_id');
        }
        if ($type === 'add') {
            array_push($data, $feedId);
        } elseif ($type === 'del') {
            $s_key = array_search($feedId, $data);
            if ($s_key !== false) {
                unset($data[$s_key]);
            }
        }
        S($key, array_unique($data));
    }
}
