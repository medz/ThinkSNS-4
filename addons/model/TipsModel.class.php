<?php
/**
 * 支持、反对模型 - 数据对象模型
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class TipsModel extends Model
{
    protected $tableName = 'tips';
    protected $fields = array('id', 'source_id', 'source_table', 'uid', 'type', 'ctime', 'ip');

    private $ip;        // 存储客户端IP

    /**
     * 初始化数据，获取客户端用户的IP地址
     */
    protected function _initialize()
    {
        $this->ip = get_client_ip();
    }

    /**
     * 增加对资源的操作信息数据
     * @param  int    $sid    资源ID
     * @param  string $stable 资源表
     * @param  int    $uid    操作人UID，默认为登录用户
     * @param  int    $type   类型：0（支持）、1（反对）
     * @return int    返回操作状态，0（添加失败）、1（添加成功）、2（已经添加）
     */
    public function doSourceExec($sid, $stable, $uid, $type)
    {
        $isExist = $this->whetherExec($sid, $stable, $uid, $type);
        if ($isExist) {
            $data['source_id'] = $sid;
            $data['source_table'] = $stable;
            $data['uid'] = $uid;
            $data['type'] = $type;
            $data['ctime'] = time();
            $data['ip'] = $this->ip;

            $res = $this->data($data)->add();
            $res = ($res === false) ? 0 : 1;

            return $res;
        } else {
            return 2;
        }
    }

    /**
     * 删除指定的资源信息数据
     * @param  int    $sid    资源ID
     * @param  string $stable 资源表
     * @return bool   是否删除成功
     */
    public function delSourceExec($sid, $stable)
    {
        $map['source_id'] = $sid;
        $map['source_table'] = $stable;
        $res = $this->where($map)->delete();
        $res = ($res === false) ? false : true;

        return $res;
    }

    /**
     * 获取指定资源的信息数据
     * @param  int    $sid    资源ID
     * @param  string $stable 资源表
     * @param  int    $type   类型
     * @return int    返回相应的资源统计数目
     */
    public function getSourceExec($sid, $stable, $type)
    {
        $map['source_id'] = $sid;
        $map['source_table'] = $stable;
        $map['type'] = $type;
        $count = $this->where($map)->count();

        return $count;
    }

    /**
     * 判断是否能进行操作
     * 每个用户对每条资源只能进行一次支持或者反对操作。
     * 如果uid=0或者uid<1(游客)，则每个IP只能对每条资源进行一次支持或者反对操作
     * @param  int    $sid    资源ID
     * @param  string $stable 资源表
     * @param  int    $uid    操作用户UID
     * @param  int    $type   类型
     * @return bool   判断该用户是否操作过
     */
    public function whetherExec($sid, $stable, $uid, $type)
    {
        $map['source_id'] = $sid;
        $map['source_table'] = $stable;
        $map['type'] = $type;

        if ($uid < 1) {
            $map['ip'] = $this->ip;
        } else {
            $map['uid'] = $uid;
        }

        $count = $this->where($map)->count();
        $res = ($count > 0) ? false : true;

        return $res;
    }
}
