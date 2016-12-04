<?php
/**
 * 举报模型 - 数据对象模型
 * @author JunStar <wangjuncheng@zhishisoft.com>
 * @version TS3.0
 */
class DenounceModel extends Model
{
    protected $tableName = 'denounce';
    protected $fields = array(0 => 'id', 1 => 'from', 2 => 'aid', 3 => 'state', 4 => 'uid', 5 => 'fuid', 6 => 'reason', 7 => 'content', 8 => 'ctime', 9 => 'source_url');

    /**
     * 获取相应类型的举报列表
     * @param  array $map 查询条件
     * @return array 相应类型的举报列表
     */
    public function getFromList($map)
    {
        $list = $this->where($map)->order('id DESC')->findPage();
        $arr = array(
                'feed' => '网站-分享',
                'Android-weiba' => '安卓手机-帖子',
                'iPhone-weiba' => '苹果手机-帖子',
                'weiba_post' => '网站-帖子',
                'mobile-weiba' => '3G手机-帖子',
                'Android' => '安卓手机-分享',
                'iPhone' => '苹果手机-分享',
                'mobile' => '3G手机-分享',
        );
        foreach ($list['data'] as &$v) {
            $v['source_url'] = str_replace('[SITE_URL]', SITE_URL, $v['source_url']);
            $v['from'] = $arr[$v['from']];
        }

        return $list;
    }
    /**
     * 彻底删除举报信息
     * @param  array $ids 被举报的资源ID
     * @return mix   删除失败返回false，成功返回删除的资源ID
     */
    public function deleteDenounce($ids, $state)
    {
        $weiboIds = $this->_getWeiboIdsByDenounce($ids);
        $weibo_map['feed_id'] = array('IN', $weiboIds);
        $weibo_set = model('Feed')->where($weibo_map)->save(array('is_del' => 1));
        if ($state == 0) {
            $result = $this->where($this->_paramMaps($ids))->save(array('state' => '1'));
        } elseif ($state == 1) {
            $result = $this->where($this->_paramMaps($ids))->delete();
        }

        return $result;
    }

    /**
     * 举报内容，审核通过
     * @param  array $ids 被举报的资源ID
     * @return mix   审核失败返回false，成功返回审核的资源ID
     */
    public function reviewDenounce($ids)
    {
        $weiboIds = $this->_getWeiboIdsByDenounce($ids);
        $weibo_map['feed_id'] = array('IN', $weiboIds);
        $weibo_set = model('Feed')->where($weibo_map)->save(array('is_del' => 0));
        // 删除举报信息
        $result = $this->where($this->_paramMaps($ids))->delete();

        return $result;
    }

    /**
     * 添加举报信息
     * @param $id 举报的资源ID
     * @param int $uid 举报用户ID
     * @param  string $content 举报附加内容
     * @param  string $type 举报资源类型
     * @return mix    添加失败返回false，成功返回新添加的举报ID
     */
    public function autoDenounce($id, $uid, $content, $type = 'feed')
    {
        $map['from'] = 'weibo';
        $map['aid'] = $id;
        $map['uid'] = '0';
        $map['fuid'] = $uid;
        $map['content'] = $content;
        $map['reason'] = '';
        $map['ctime'] = time();
        $map['state'] = '1';
        $weibo_map['feed_id'] = $id;
        model('Feed')->where($weibo_map)->save(array('is_del' => 1));

        return $this->add($map);
    }

    /**
     * 获取指定资源已经被举报且进入回收站的资源ID
     * @param  string       $from 资源类型
     * @param  string       $type 是输出数组还是字符串，默认为字符串
     * @return array|string 回收站中的举报资源ID
     */
    public function getIdsDenounce($from, $type = '')
    {
        $map['from'] = $from;
        $map['state'] = '1';
        $ids = getSubByKey($this->where($map)->field('aid')->findAll(), 'aid');
        empty($type) && $ids = implode(',', $ids);

        return $ids;
    }

    /**
     * 获取被举报的分享ID
     * @param  array $ids 举报ID数组
     * @return array 被举报的分享ID
     */
    private function _getWeiboIdsByDenounce($ids)
    {
        $data = $this->where($this->_paramMaps($ids))->field('aid')->findAll();
        $weibo_id = getSubByKey($data, 'aid');

        return $weibo_id;
    }

    /**
     * 格式化，资源ID数据
     * @param  string|array $ids 资源ID数据
     * @return array        格式化后的资源ID数据
     */
    private function _paramMaps($ids)
    {
        $ids = is_array($ids) ? $ids : explode(',', $ids);
        if (empty($ids)) {
            return false;
        }
        $map['id'] = array('IN', $ids);

        return $map;
    }
}
