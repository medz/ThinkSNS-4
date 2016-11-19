<?php
/**
 * 用户备注模型
 *
 * @author Foreach <missu082500@163.com>
 **/
class UserRemarkModel extends Model
{
    /**
     * 设置用户备注名
     *
     * @param  int    $uid    被设置用户id
     * @param  string $remark 备注名
     * @return bool
     * @author Foreach <missu082500@163.com>
     **/
    public function setRemark($uid, $remark)
    {
        $data['uid'] = $uid;
        $data['mid'] = $GLOBALS['ts']['mid'];
        if ($data['mid'] == 0) {
            return false;
        }

        if ($this->where($data)->find()) {
            $rs = $this->where($data)->save(array('remark' => $remark));
        } else {
            $data['remark'] = $remark;
            $rs = $this->add($data);
        }
        //清理缓存
        D('User')->cleanCache($uid);

        return $rs;
    }

    /**
     * 获取用户备注名
     *
     * @param  int    $uid 被设置用户id
     * @return string
     * @author Foreach <missu082500@163.com>
     **/
    public function getRemark($mid, $uid)
    {
        if ($mid == $uid) {
            return '';
        }
        $map['mid'] = $mid;
        $map['uid'] = $uid;

        $remark = $this->where($map)->getField('remark');
        $rs = $remark !== null ? $remark : '';

        return $rs;
    }

    /**
     * 通过备注名搜索
     *
     * @param  int    $mid    用户id
     * @param  string $remark 备注名
     * @return array
     * @author Foreach <missu082500@163.com>
     **/
    public function searchRemark($mid, $remark)
    {
        $rmap['mid'] = $mid;
        $rmap['remark'] = array('LIKE', '%'.$remark.'%');
        $ruid_arr = getSubByKey($this->where($rmap)->field('uid')->findAll(), 'uid');

        return $ruid_arr;
    }
}
