<?php

class DisableUserModel extends Model
{
    protected $tableName = 'user_disable';

    public function setDisableUser($uid, $disableItem, $startTime, $endTime)
    {
        $map['uid'] = $uid;
        $map['type'] = $disableItem;
        $count = $this->where($map)->count();
        $result = false;
        if ($count > 0) {
            $data['start_time'] = $startTime;
            $data['end_time'] = $endTime;
            $data['ctime'] = time();

            $result = $this->where($map)->save($data);
        } else {
            $data['uid'] = $uid;
            $data['type'] = $disableItem;
            $data['start_time'] = $startTime;
            $data['end_time'] = $endTime;
            $data['ctime'] = time();

            $result = $this->add($data);
        }

        S('is_disable_user_'.$disableItem.'_'.$uid, null);

        return (boolean) $result;
    }

    public function getDisableUser($uid)
    {
        $map['uid'] = $uid;
        $list = $this->where($map)->findAll();

        if (empty($list)) {
            return array();
        }

        $tmp = array();
        foreach ($list as $value) {
            $tmp[$value['type']]['start_time'] = $value['start_time'];
            $tmp[$value['type']]['end_time'] = $value['end_time'];
        }

        $data['uid'] = $uid;
        foreach (array('login', 'post') as $value) {
            $data[$value]['start_time'] = isset($tmp[$value]['start_time']) ? $tmp[$value]['start_time'] : '';
            $data[$value]['start_time_format'] = isset($tmp[$value]['start_time']) ? date('Y-m-d H:i:s', $tmp[$value]['start_time']) : '';
            $data[$value]['end_time'] = isset($tmp[$value]['end_time']) ? $tmp[$value]['end_time'] : '';
            $data[$value]['end_time_format'] = isset($tmp[$value]['end_time']) ? date('Y-m-d H:i:s', $tmp[$value]['end_time']) : '';
        }

        return $data;
    }

    public function getDisableUserStatus($uid)
    {
        $data = $this->getDisableUser($uid);

        $result = array();
        $time = time();
        $result['login'] = ($time > $data['login']['start_time'] && $time < $data['login']['end_time']) ? true : false;
        $result['post'] = ($time > $data['post']['start_time'] && $time < $data['post']['end_time']) ? true : false;

        return $result;
    }

    public function getDisableList($type, $limit = 20)
    {
        $map['ud.type'] = $type;
        $map['u.is_del'] = 0;
        $map['ud.end_time'] = array('gt', time());
        $list = D()->table($this->tablePrefix.'user_disable AS ud LEFT JOIN '.$this->tablePrefix.'user AS u ON ud.uid = u.uid')
                   ->field('u.*, ud.user_disable_id, ud.type, ud.start_time, ud.end_time')
                   ->where($map)
                   ->findPage($limit);

        return $list;
    }

    public function setEnableUser($id)
    {
        $map['user_disable_id'] = $id;
        $uid = $this->where($map)->getField('uid');

        $result = $this->where($map)->delete();

        S('is_disable_user_login_'.$uid, null);
        S('is_disable_user_post_'.$uid, null);

        return (boolean) $result;
    }

    public function isDisableUser($uid, $type = 'login')
    {
        if (!in_array($type, array('login', 'post'))) {
            $type = 'login';
        }
        if (empty($uid)) {
            return false;
        }
        $key = 'is_disable_user_'.$type.'_'.$uid;
        $result = S($key);
        if ($result == false) {
            $map['uid'] = $uid;
            $map['type'] = $type;
            $time = time();
            $map['start_time'] = array('lt', $time);
            $map['end_time'] = array('gt', $time);
            $data = $this->where($map)->find();

            if (empty($data)) {
                $result['status'] = false;
            } else {
                $result['status'] = true;
                $result['time'] = $data['end_time'];
            }
            S($key, $result);
        }

        if ($result['status'] && $result['time'] < time()) {
            S($key, null);

            return false;
        }

        return $result['status'];
    }
}
