<?php

class FeedTopHomeModel extends Model
{
    protected $tableName = 'feed_top_home';

    public function setFeedTopHome($uid, $feedId)
    {
        $checked = $this->canSetFeedTop($uid, $feedId);
        if (!$checked) {
            return false;
        }
        $data['uid'] = $uid;
        $data['feed_id'] = $feedId;
        $data['ctime'] = time();
        $result = $this->add($data);

        return (boolean) $result;
    }

    public function delFeedTopHome($uid, $feedId)
    {
        $checked = $this->checkedFeedTop($uid, $feedId);
        if (!$checked) {
            return false;
        }
        $map['uid'] = $uid;
        $map['feed_id'] = $feedId;
        $result = $this->where($map)->delete();

        return (boolean) $result;
    }

    public function getFeedTopHome($uid)
    {
        if (empty($uid)) {
            return array();
        }
        $map['a.uid'] = $uid;
        $map['b.is_del'] = 0;
        $list = $this->table('`'.C('DB_PREFIX').'feed_top_home` AS a LEFT JOIN `'.C('DB_PREFIX').'feed` AS b ON a.feed_id = b.feed_id')
                     ->field('a.*')
                     ->where($map)
                     ->findAll();

        return $list;
    }

    public function checkedFeedTop($uid, $feedId)
    {
        if (empty($uid) || empty($feedId)) {
            return false;
        }

        $map['uid'] = $uid;
        $map['feed_id'] = $feedId;
        $count = $this->where($map)->count();

        if ($count > 0) {
            return ture;
        } else {
            return false;
        }
    }

    public function canSetFeedTop($uid, $feedId)
    {
        if (empty($uid) || empty($feedId)) {
            return false;
        }

        $map['uid'] = $uid;
        $map['feed_id'] = $feedId;
        $count = $this->where($map)->count();
        $allCount = $this->where("uid='{$uid}'")->count();

        if ($count == 0) {
            return true;
        } else {
            return false;
        }
    }
}
