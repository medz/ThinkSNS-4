<?php

class InviteTestModel extends Model
{
    protected static $config = null;

    public function check($code, $uid, $uniqid)
    {
        $map['code'] = $code;
        $data = $this->where($map)->find();
        if (!$data) {
            $this->error = '没有这个邀请码';

            return false;
        }
        //禁用邀请码
        if ($data['is_disable']) {
            $this->error = '此邀请码已被禁用';
        }
        //检查是否重复IP使用
        $hash = md5(C('SECURE_CODE').get_client_ip().$uniqid);
        if ($data['hash'] != $hash && $data['utime'] > time() - 900) {
            $this->error = '此邀请码其他用户正在使用';

            return false;
        } else {
            $data['hash'] = $hash;
        }
        if ($data['uid']) { // 已经绑定了用户ID
            if ($uid && $data['uid'] != $uid) {
                $this->error = '邀请码已绑定其他帐号';

                return false;
            } else {
                //用户使用了已绑定的用户ID的邀请码
                //但是当前用户还没登录，无法识别身份
            }
        } else { // 还未绑定用户ID
            if ($uid) { //当前已登录
                //检查用户是否已经绑定了其他邀请码
                //if($this->where(array('uid'=>$uid))->count()>0){
                //	$this->error = '你已绑定了其他邀请码';
                //	return false;
                //}
                //自动绑定到此用户账户上
                $data['uid'] = $uid;
            } else { //当前未登录
                //继续使用
            }
        }
        $data['utime'] = time();
        $map = array('id' => $data['id']);
        unset($data['id']);

        return false !== $this->where($map)->save($data);
    }

    public function create($num)
    {
        $str = '0123456789abcdefghijklmnopqrstuvwxyz';
        $add = 0;
        for ($i = 0; $i < $num; $i++) {
            $rand = substr(str_shuffle($str), 0, 6);
            if ($this->add(array('code' => $rand))) {
                $add++;
            }
        }

        return $add;
    }

    public function saveConfig(array $config)
    {
        $save['bgimg'] = intval($config['bgimg']);
        $save['rule'] = $config['rule'];

        return !!model('AddonData')->lput('InviteTest', $save);
    }

    public function getConfig()
    {
        if (self::$config === null) {
            self::$config = model('AddonData')->lget('InviteTest');
        }

        return self::$config;
    }
}
