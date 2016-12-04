<?php
/**
 * 收藏模型 - 数据对象模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class CollectionModel extends Model
{
    protected $tableName = 'collection';
    protected $fields = array('collection_id', 'uid', 'source_id', 'source_table_name', 'source_app', 'ctime');

    private $collectionTables = array('feed', 'weiba_post');

    /**
     * 添加收藏记录
     * @param  array $data 收藏相关数据
     * @return bool 是否收藏成功
     */
    public function addCollection(array $data)
    {
        $data['uid'] or $data['uid'] = $GLOBALS['ts']['mid'];

        // # 检车必要数据是否为空
        if (empty($data['source_id']) or empty($data['source_table_name']) or empty($data['source_app'])) {
            $this->error = L('PUBLIC_RESOURCE_ERROR');

            return false;

        // # 判断是否收藏过
        } elseif ($this->getCollection($data['source_id'], $data['source_table_name'])) {
            $this->error = L('PUBLIC_RESOURCE_ERROR');

            return false;

        // # 判断是否登陆了！
        } elseif (!$data['uid']) {
            $this->error = '没有登陆';

            return false;
        }
        // // 验证数据
        // if(empty($data['source_id']) || empty($data['source_table_name']) || empty($data['source_app'])) {
        // 	$this->error = L('PUBLIC_RESOURCE_ERROR');			// 资源ID,资源所在表名,资源所在应用不能为空
        // 	return false;
        // }
        // // 判断是否已收藏
        // $isExist = $this->getCollection($data['source_id'], $data['source_table_name']);
        // if(!empty($isExist)) {
        // 	$this->error = L('PUBLIC_FAVORITE_ALREADY');		// 您已经收藏过了
        // 	return false;
        // }
        // $data['uid'] = !$data['uid'] ? $GLOBALS['ts']['mid'] : $data['uid'];
        // if ( !$data['uid'] ){
        // 	$this->error = '未登录';		// 收藏失败
        // 	return false;
        // }
        $data['source_id'] = intval($data['source_id']);
        $data['source_table_name'] = t($data['source_table_name']);
        $data['source_app'] = t($data['source_app']);
        $data['ctime'] = time();
        if ($data['collection_id'] = $this->add($data)) {
            // 生成缓存
            model('Cache')->set('collect_'.$data['uid'].'_'.$data['source_table_name'].'_'.$data['source_id'], $data);
            model('Cache')->rm('coll_count_'.$data['source_table_name'].'_'.$data['source_id']);
            $data['source_table_name'] === 'feed' && model('Cache')->rm('feed_info_'.$data['source_id']);
            //添加积分
            model('Credit')->setUserCredit($data['uid'], 'collect_weibo');
            $uid = model('Feed')->where('feed_id='.$data['source_id'])->getField('uid');
            model('Credit')->setUserCredit($uid, 'collected_weibo');

            // 收藏数加1
            model('UserData')->updateKey('favorite_count', 1);

            return true;
        } else {
            $this->error = L('PUBLIC_FAVORITE_FAIL');        // 收藏失败,您可能已经收藏此资源
            return false;
        }
    }

    /**
     * 返回指定资源的收藏数目
     * @param  int    $sid    资源ID
     * @param  string $stable 资源表名
     * @return int    指定资源的收藏数目
     */
    public function getCollectionCount($sid, $stable)
    {
        if (($count = model('Cache')->get('coll_count_'.$stable.'_'.$sid)) === false) {
            $map['source_id'] = $sid;
            $map['source_table_name'] = $stable;
            $count = $this->where($map)->count();
            model('Cache')->set('coll_count_'.$stable.'_'.$sid, $count);
        }

        return $count;
    }

    /**
     * 获取收藏列表
     * @param  array  $map   查询条件
     * @param  int    $limit 结果集显示数目，默认为20
     * @param  string $order 排序条件，默认为ctime DESC
     * @return array  收藏列表数据
     */
    public function getCollectionList($map, $limit = 20, $order = 'ctime DESC')
    {
        $list = $this->where($map)->order($order)->findPage($limit);
        foreach ($list['data'] as &$v) {
            $sourceInfo = model('Source')->getSourceInfo($v['source_table_name'], $v['source_id'], false, $v['source_app']);
            $publish_time = array('publish_time' => $sourceInfo['ctime']);
            switch ($v['source_table_name']) {
                case 'feed':
                    $data = model('Feed')->get($v['source_id']);
                    $sourceData = array('source_data' => $data);
                    break;
                default:
                    $sourceData = array('source_data' => null);
                    break;
            }
            $v = array_merge($sourceInfo, $v, $publish_time, $sourceData);
        }

        return $list;
    }

    /**
     * 获取收藏的种类，用于收藏的Tab
     * @param array $map 查询条件
     * @return array 收藏种类与其资源数目
     */
    public function getCollTab($map)
    {
        return $this->field('COUNT(1) AS `nums`, `source_table_name`')
                    ->where($map)
                    ->group('source_table_name')
                    ->getHashList('source_table_name', 'nums');
        // $list = $this->field('COUNT(1) AS `nums`, `source_table_name`')->where($map)->group('source_table_name')->getHashList('source_table_name', 'nums');
        // return $list;
    }

    /**
     * 获取指定收藏的信息
     * @param  int    $sid    资源ID
     * @param  string $stable 资源表名称
     * @param  int    $uid    用户UID
     * @return array  指定收藏的信息
     */
    public function getCollection($sid, $stable, $uid = '')
    {
        // 验证数据
        if (empty($sid) || empty($stable)) {
            $this->error = L('PUBLIC_WRONG_DATA');        // 错误的参数
            return false;
        }

        empty($uid) && $uid = $GLOBALS['ts']['mid'];
        // 获取收藏信息
        if (($cache = model('Cache')->get('collect_'.$uid.'_'.$stable.'_'.$sid)) === false) {
            $map['source_table_name'] = $stable;
            $map['source_id'] = $sid;
            $map['uid'] = $uid;
            $cache = $this->where($map)->find();
            model('Cache')->set('collect_'.$uid.'_'.$stable.'_'.$sid, $cache);
        }

        return $cache;
    }

    /**
     * 取消收藏
     * @param  int    $sid    资源ID
     * @param  string $stable 资源表名称
     * @param  int    $uid    用户UID
     * @return bool   是否取消收藏成功
     */
    public function delCollection($sid, $stable, $uid = '')
    {
        // 验证数据
        if (empty($sid) || empty($stable)) {
            $this->error = L('PUBLIC_WRONG_DATA');        // 错误的参数
            return false;
        }
        if (!in_array($stable, $this->collectionTables)) {
            $stable = 'feed';
        }
        $uid = empty($uid) ? $GLOBALS['ts']['mid'] : $uid;
        $map['uid'] = $uid;
        $map['source_id'] = $sid;
        $map['source_table_name'] = $stable;
        // 取消收藏操作
        // dump($map);
        if ($this->where($map)->delete()) {
            // 设置缓存
            model('Cache')->set('collect_'.$uid.'_'.$stable.'_'.$sid, '');
            model('Cache')->rm('coll_count_'.$stable.'_'.$sid);
            $stable === 'feed' && model('Cache')->rm('feed_info_'.$sid);
            // 收藏数减1
            model('UserData')->updateKey('favorite_count', -1);

            return true;
        } else {
            $this->error = L('PUBLIC_CANCEL_FAVORITE_FAIL');        // 取消失败,您可能已经取消了该信息的收藏
            return false;
        }
    }

    public function delByFeed($sid, $stable)
    {
        if (!$sid) {
            $this->error = '资源ID不能为空';

            return false;
        } elseif (!$stable) {
            $this->error = '资源表不能为空';

            return false;
        }
        $where = array(
            'source_id' => array('IN', $sid),
            'source_table_name' => t($stable),
        );
        $uids = $this->where($where)->field('uid')->getAsFieldArray('uid');
        if ($this->where($where)->delete()) {
            foreach ($uids as $uid) {
                model('Cache')->set('collect_'.$uid.'_'.$stable.'_'.$sid, '');
                model('Cache')->rm('coll_count_'.$stable.'_'.$sid);
                $stable === 'feed' && model('Cache')->rm('feed_info_'.$sid);
                // 收藏数减1
                model('UserData')->updateKey('favorite_count', -1, true, $uid);
            }

            return true;
        }
        $this->error = '删除错误';

        return false;
    }

    /*** API使用 ***/
    /**
     * 获取收藏列表，API使用
     * @param  int   $uid      用户UID
     * @param  int   $since_id 主键起始ID，默认为0
     * @param  int   $max_id   主键最大ID，默认为0
     * @param  int   $limit    每页结果集数目，默认为20
     * @param  int   $page     页数，默认为1
     * @return array 收藏列表数据
     */
    public function getCollectionForApi($uid, $since_id = 0, $max_id = 0, $limit = 20, $page = 1)
    {
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = " uid = {$uid} ";
        if (!empty($since_id) || !empty($max_id)) {
            !empty($since_id) && $where .= " AND collection_id > {$since_id}";
            !empty($max_id) && $where .= " AND collection_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $list = $this->where($where)->limit("$start, $end")->order('collection_id DESC')->findAll();
        foreach ($list as &$v) {
            $sourceInfo = model('Source')->getSourceInfo($v['source_table_name'], $v['source_id'], true, $v['source_app']);
            $v = array_merge($sourceInfo, $v);
        }

        return $list;
    }

    /**
     * 获取动态（分享）收藏列表，API使用
     * @param  int   $uid      用户UID
     * @param  int   $since_id 主键起始ID，默认为0
     * @param  int   $max_id   主键最大ID，默认为0
     * @param  int   $limit    每页结果集数目，默认为20
     * @param  int   $page     页数，默认为1
     * @return array 收藏列表数据
     */
    public function getCollectionFeedForApi($uid, $since_id = 0, $max_id = 0, $limit = 20, $page = 1)
    {
        $since_id = intval($since_id);
        $max_id = intval($max_id);
        $limit = intval($limit);
        $page = intval($page);
        $where = " uid = {$uid} AND source_table_name ='feed' ";
        if (!empty($since_id) || !empty($max_id)) {
            !empty($since_id) && $where .= " AND source_id > {$since_id}";
            !empty($max_id) && $where .= " AND source_id < {$max_id}";
        }
        $start = ($page - 1) * $limit;
        $end = $limit;
        $feed_ids = $this->where($where)->limit("$start, $end")->order('collection_id DESC')->field('source_id')->getAsFieldArray('source_id');
        $list = model('Feed')->formatFeed($feed_ids, true);

        return $list;
    }

    /**
     * 数据库搜索收藏分享
     * @param  string $key   关键字
     * @param  int   $limit 结果集数目，默认20
     * @return array 搜索的结果数据
     */
    public function searchCollections($key, $limit = 20)
    {
        if ($key === '') {
            return array();
        }
        $map['a.`uid`'] = $GLOBALS['ts']['mid'];
        $map['b.`feed_content`'] = array('LIKE', '%'.$key.'%');
        $list = D()->table('`'.C('DB_PREFIX').'collection` AS a LEFT JOIN `'.C('DB_PREFIX').'feed_data` AS b ON a.`source_id` = b.`feed_id`')
                   ->field('a.*')
                   ->where($map)
                   ->findPage($limit);
        foreach ($list['data'] as &$v) {
            $sourceInfo = model('Source')->getSourceInfo($v['source_table_name'], $v['source_id'], false, $v['source_app']);
            $publish_time = array('publish_time' => $sourceInfo['ctime']);
            switch ($v['source_table_name']) {
                case 'feed':
                    $data = model('Feed')->get($v['source_id']);
                    $sourceData = array('source_data' => $data);
                    break;
                default:
                    $sourceData = array('source_data' => null);
                    break;
            }
            $v = array_merge($sourceInfo, $v, $publish_time, $sourceData);
        }

        return $list;
    }
}
