<?php
/**
 * 勋章数据模型类
 * @author Stream
 *
 */
class MedalModel extends Model
{
    public function getList($map, $limit = 20)
    {
        $list = $this->where($map)->order('id desc')->findPage($limit);
        if (is_array($list['data'])) {
            foreach ($list['data'] as &$v) {
                $src = explode('|', $v['src']);
                $v['src'] = getImageUrl($src[1]);
                $smallsrc = explode('|', $v['small_src']);
                //$v['small_src'] = getImageUrl( $smallsrc[1] );
                $v['small_src'] = $smallsrc[1];
            }
        }

        return $list;
    }

    public function getAllMedal()
    {
        $list = $this->findAll();
        $keyidlist = array();
        if (is_array($list)) {
            foreach ($list as $v) {
                $keyidlist[$v['id']] = $v['name'];
            }
        }

        return $keyidlist;
    }

    /**
     * 返回用户勋章
     * @param unknown_type $uid
     */
    public function getMedalByUid($uid)
    {
        $list = D()->query('select b.* from '.C('DB_PREFIX').'medal_user a inner join '.C('DB_PREFIX').'medal b on a.medal_id=b.id where a.uid='.$uid.' order by a.ctime desc');
        if (is_array($list)) {
            foreach ($list as &$v) {
                $src = explode('|', $v['src']);
                $v['src'] = getImageUrl($src[1]);

                $smallsrc = explode('|', $v['small_src']);
                $v['small_src'] = $smallsrc[1];
                //$v['small_src'] = getImageUrl( $smallsrc[1] );
            }
        }

        return $list;
    }

    /**
     * 返回用户勋章列表
     * @param  unknown_type $map
     * @param  unknown_type $limit
     * @return unknown
     */
    public function getUserMedalList($map, $limit = 20)
    {
        $list = D('medal_user')->where($map)->findPage();
        if (!$list) {
            return false;
        }
        $uids = getSubByKey($list['data'], 'uid');
        $mids = getSubByKey($list['data'], 'medal_id');

        $users = model('User')->getUserInfoByUids($uids);
        $unames = array();
        foreach ($users as $n) {
            $unames[$n['uid']] = $n['uname'];
        }

        $gmap['id'] = array('in', $mids);
        $medals = $this->where($gmap)->findAll();
        $medalnames = array();
        foreach ($medals as $m) {
            $src = explode('|', $m['src']);
            $medalnames[$m['id']]['src'] = $src[1];
            $medalnames[$m['id']]['name'] = $m['name'];
        }

        foreach ($list['data'] as &$v) {
            $v['uname'] = $unames[$v['uid']];
            $v['medalsrc'] = $medalnames[$v['medal_id']]['src'];
            $v['medalname'] = $medalnames[$v['medal_id']]['name'];
        }

        return $list;
    }

    /**
     * 为用户添加勋章
     * @param unknown_type $users
     * @param unknown_type $medalid
     */
    public function addUserMedal($users, $medalid, $desc)
    {
        $medalDao = D('medal_user');
        $uname = array();
        if (!is_array($users)) {
            return false;
        }
        foreach ($users as $u) {
            $data['medal_id'] = $medalid;
            $data['uid'] = $u;
            $exist = $medalDao->where($data)->find();
            if ($exist) {
                $userinfo = model('User')->getUserInfo($u);
                $uname[] = $userinfo['uname'];
                $this->error = '给'.implode(',', $uname).'颁发了重复勋章已经被忽略，其余成功';
                continue;
            }
            $data['desc'] = $desc;
            $data['ctime'] = $_SERVER['REQUEST_TIME'];
            $res = $medalDao->add($data);
            if ($res) {
                $config['medal_name'] = D('medal')->where('id='.$medalid)->getField('name');
                $config['desc'] = $data['desc'];
                $config['medal_url'] = U('public/Medal/index');
                model('Notify')->sendNotify($data['uid'], 'admin_add_user_medal', $config);
            }
        }

        return $res;
    }

    public function getLastError()
    {
        return $this->error;
    }
}
