<?php
/**
 * 微吧管理知识模型 - 数据对象模型.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class LogModel extends Model
{
    protected $tableName = 'weiba_log';
    protected $error = '';
    protected $fields = array(
                            0 => 'id', 1 => 'weiba_id', 2 => 'uid', 3 => 'type', 4 => 'content', 5 => 'ctime', 6 => 'square', '_autoinc' => true, '_pk' => 'weiba_id',
                        );

    /**
     * 记录知识.
     *
     * @param [type] $gid     [description]
     * @param [type] $uid     [description]
     * @param [type] $content [description]
     * @param string $type    [description]
     *
     * @return [type] [description]
     */
    public function writeLog($weiba_id, $uid, $content, $type = 'topic')
    {
        $map['weiba_id'] = $weiba_id;
        $map['uid'] = $uid;
        $map['type'] = $type;
        $user_info = model('User')->getUserInfoByUids(array($uid));
        $map['content'] = $user_info[$uid]['space_link'].' '.$content;
        //dump($user_info);exit;
        $map['ctime'] = time();
        $this->add($map);
    }
}
