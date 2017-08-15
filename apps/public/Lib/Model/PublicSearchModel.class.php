<?php
/**
 * 搜索引擎模型 - 核心应用.
 *
 * @author jason <yangjs17@yeah.net>
 *
 * @version TS3.0
 */
class PublicSearchModel extends Model
{
    protected $tableName = 'search';
    protected $fields = array(0    => 'doc_id', 1 => 'app', 2 => 'type', 3 => 'string01', 4 => 'string02',
                                5  => 'string03', 6 => 'string04', 7 => 'string05', 8 => 'int01', 9 => 'int02',
                                10 => 'int03', 11 => 'int04', 12 => 'int05', 13 => 'file_path', 14 => 'content',
                                15 => 'mtime', 16 => 'data', 17 => 'int06', 18 => 'int07', 19 => 'int08', 20 => 'int09', 21 => 'int10', );

    public $appid = 0;                // 应用ID

    /**
     * 搜索引擎接口.
     *
     * @param string $key      查询�
     * �键字
     * @param int    $limit    结果集数目，默认为10
     * @param int    $type     搜索结果类型
     * @param string $tabkey   搜索Tab类型Key值
     * @param string $tabvalue 搜索Tab类型Value值
     *
     * @return array 搜索结果列表数据
     */
    public function search($key, $limit = 10, $type = 1, $tabkey = '', $tabvalue = '')
    {
        if (empty($key)) {
            $this->error = L('PUBLIC_INPUT_KEYWORD');            // 请输入关键字
            return false;
        }

        $initWhere = "string01 like '{$key}' AND int01=0  AND int07= 0 AND int02 = ".intval($type);

        $where = $this->getWhere($key, $type, $tabkey, $tabvalue);

        $query = 'SELECT * FROM '.C('DB_PREFIX').'search WHERE '.$initWhere.$where;

        $list = model('Search')->search($query, $limit);

        return $list;
        exit;
        // 筛选项处理
        $tablist = $this->getTablist($type, $tabkey);
        if (!empty($tablist)) {
            if (empty($tabkey)) {
                $tabkey = 'int04';
            }

            $groupQuery = " SELECT $tabkey,COUNT(*) FROM sociax WHERE ".$initWhere." GROUP BY $tabkey";
            $tabData = model('Search')->query($groupQuery);

            foreach ($tabData as $v) {
                $tablist[$v[$tabkey]]['count'] = $v['@count'];
            }
        }
        $list['tablist'] = $tablist;

        return $this->foramtList($list, $type);
    }

    /**
     * �
     * �站查找接口.
     *
     * @param string $key �
     * �键字
     *
     * @return array 搜索结果列表数据
     */
    public function searchInAll($key)
    {
        if (empty($key)) {
            return false;
        }
        // 搜索有的数据统计数，只支持1个字段，所以使用int02*10000+int01的方式进行排序
        $groupQuery = " SELECT COUNT(*), int01 * 100000 + int02 AS groupint FROM sociax WHERE MATCH('{$key}') AND int07 = 0 GROUP BY groupint";
        $groupData = model('Search')->query($groupQuery);
        if (empty($groupData)) {
            // 没有数据
            return false;
        }
        // 获取所有可查询项目
        $menuList = D('search_select')->field('*, app_id * 100000 + type_id AS groupint')->getHashList('groupint');

        $data = array();
        foreach ($groupData as $v) {
            $appname = $menuList[$v['groupint']]['app_name'];
            $searchModel = ucfirst(strtolower($appname)).'Search';
            $type_id = $menuList[$v['groupint']]['type_id'];
            $data[$appname][$type_id] = D($searchModel, strtolower($appname))->search($key, 5, $type_id);
        }

        $return = array();
        if (isset($data['public'][1])) {
            $return['public'][1] = $data['public'][1];
        }
        if (isset($data['public'][2])) {
            $return['public'][2] = $data['public'][2];
        }
        unset($data['public']);
        $return = is_array($data) ? array_merge($return, $data) : $return;

        return $return;
    }

    /**
     * 根据类型获取提供筛选的Tab数组.
     *
     * @param int    $type   数据类型
     * @param string $tabkey 选定的Tab的Key值
     *
     * @return array 提供筛选的Tab数组
     */
    public function getTablist($type, $tabkey = '')
    {
        $tablist = array();
        if ($type == 1) { //用户
        } else {
            $tablist[1] = array('tabkey' => 'int04', 'tabvalue' => '1', 'tabtitle' => L('PUBLIC_ORIGINAL_STREAM'), 'count' => 0);
            $tablist[2] = array('tabkey' => 'int04', 'tabvalue' => '2', 'tabtitle' => L('PUBLIC_SHARE_STREAM'), 'count' => 0);
            $tablist[3] = array('tabkey' => 'int04', 'tabvalue' => '3', 'tabtitle' => L('PUBLIC_IMAGE_STREAM'), 'count' => 0);
            $tablist[4] = array('tabkey' => 'int04', 'tabvalue' => '4', 'tabtitle' => L('PUBLIC_FILE_STREAM'), 'count' => 0);
            $tablist[0] = array('tabkey' => 'int04', 'tabvalue' => '0', 'tabtitle' => L('PUBLIC_STREAM_LIKE'), 'count' => 0);
        }

        return $tablist;
    }

    /**
     * 初始化数据，用户数据与分享数据.
     */
    public function initData()
    {
        // 初始化用户
        $this->initUser();
        // 初始化feed
        $this->initFeed();
    }

    /**
     * 格式化搜索结果的数据.
     *
     * @param array $list 搜索的结果数据
     * @param int   $type 类型值
     *
     * @return array 格式化后的搜索结果数据
     */
    private function foramtList($list, $type)
    {
        $pkIds = array();
        $dataHash = array();
        // 获取主键
        foreach ($list['data'] as $v) {
            $pkIds[] = $v['int03'];
            $dataHash[$v['int03']] = $v;
        }
        if (empty($pkIds)) {
            $list['data'] = array();

            return $list;
        }

        if ($type == 1) {
            // 用户数据
            $data = model('User')->getUserInfoByUids($pkIds);
            // 关注关系判断
            if ($GLOBALS['ts']['mid'] > 0) {
                $followStates = model('Follow')->getFollowStateByFids($GLOBALS['ts']['mid'], $pkIds);
            }
            // 批量获取用户的字段配置信息
            $profileInfo = model('UserProfile')->getUserProfileByUids($pkIds);
            $list['profileSetting'] = model('UserProfile')->getUserProfileSetting(array('type' => 2));
            // 批量获取用户标签
            $list['user_tag'] = model('Tag')->setAppName('User')->setAppTable('user')->getAppTags($pkIds);
            foreach ($data as &$v) {
                $v['followState'] = @$followStates[$v['uid']];
                $v['search_info'] = $dataHash[$v['uid']];
                $v['search_info']['type'] = $type;
                $v['user_data'] = model('UserData')->getUserData($v['uid']);
                $v['profile'] = $profileInfo[$v['uid']];
                /*foreach($profileSetting as $ps){
                    if(!$v['profile'][$ps['field_id']]){
                        $v['profile'][$ps['field_id']] = array('field_data'=>'');
                    }
                    $v['profile'][$ps['field_id']]['field_key']  = $ps['field_key'];
                    $v['profile'][$ps['field_id']]['field_name'] = $ps['field_name'];
                }*/
            }
            $list['data'] = $data;
        } else {
            // 分享数据
            $data = model('Feed')->getFeeds($pkIds);
            foreach ($data as &$v) {
                $v['search_info'] = $dataHash[$v['feed_id']];
                $v['search_info']['type'] = $type;
            }
            $list['data'] = $data;
        }

        return $list;
    }

    /**
     * 获取查询的Query的条件语句.
     *
     * @param string $key      查询�
     * �键字
     * @param int    $type     搜索结果类型
     * @param string $tabkey   搜索Tab类型Key值
     * @param string $tabvalue 搜索Tab类型Value值
     *
     * @return string 查询的Query的条件语句
     */
    private function getWhere($key, $type, $tabkey, $tabvalue)
    {
        if ($type == 1) {
            if (!empty($tabkey)) {
            }
        }
        if ($type == 2) {
            if (!empty($tabkey)) {
                $where .= ' AND '.t($tabkey).' = '.intval($tabvalue);
            }
        }

        return $where;
    }

    /**
     * 初始化用户数据
     * 搜索引擎参数说明
     * string01:用户名
     * string02:email
     * int01: 0 表示应用为核心
     * int02: 1 表示用户数据
     * int03: uid
     * int04: ctime
     * int05:  is_active 是否激活
     * int06: is_audit 是否审核
     * int07: is_del 是否删除
     * int08: is_init  是否初始化
     * content :用户�
     * �置数据组合进来int02: 1 表示用户数据.
     *
     * @return array 初始化用户数据
     */
    private function initUser()
    {
        //更新删除的内容
        $sql = 'UPDATE `'.$this->tablePrefix.'search` AS a, `'.$this->tablePrefix.'user` AS b '.
               'SET a.int07= 1 '.
               ' WHERE a.int01 = 0 AND a.int02 = 1 AND a.int03 = b.uid AND b.is_del = 1';
        $this->query($sql);

        $sql = 'UPDATE `'.$this->tablePrefix.'search` AS a, `'.$this->tablePrefix.'user` AS b '.
               'SET a.int07= 0 '.
               ' WHERE a.int01 = 0 AND a.int02= 1 AND a.int03 = b.uid AND b.is_del = 0';
        $this->query($sql);

        $map['int01'] = 0;
        $map['int02'] = 1;
        $maxId = $this->where($map)->field('MAX(int03) AS maxId')->find();
        $maxId = intval($maxId['maxId']);

        $sql = 'INSERT INTO '.$this->tablePrefix."search (app,type,string01,string02,int01,int02,int03,int04,int05,int06,int07,int08,content)
				SELECT 'public','user',a.uname, a.email,0,1,a.uid,a.ctime, a.is_active, a.is_audit, a.is_del, a.is_init, b.`profile`
				FROM (
					SELECT uid, GROUP_CONCAT( field_data ) AS `profile`
					FROM ".$this->tablePrefix."user_profile
					where uid > {$maxId}
					GROUP BY uid
				) b
				LEFT JOIN ".$this->tablePrefix."user a ON b.uid = a.uid  where a.uid > {$maxId}";

        return $this->query($sql);
    }

    /**
     * 初始化分享数据
     * 搜索引擎参数说明
     * string01:动态title
     * int01: 0 表示应用为核心
     * int02: 2 表示feed数据
     * int03: feed_id
     * int04: type
     * int05: uid
     * int06: publish_time
     * int07: is_del 是否删除
     * int08: from 数据来源（客户端还是网站）
     * content :用户�
     * �置数据组合进来.
     *
     * @return array 初始化分享数据
     */
    private function initFeed()
    {
        // 更新删除的内容
        $sql = 'UPDATE `'.$this->tablePrefix.'search` a, `'.$this->tablePrefix.'feed` b '.
               'SET a.int07= 1 '.
               ' WHERE a.int01 = 0 AND a.int02= 2 AND a.int03 = b.feed_id AND  b.is_del = 1';
        $this->query($sql);

        $sql = 'UPDATE  `'.$this->tablePrefix.'search` a, `'.$this->tablePrefix.'feed` b '.
               'SET a.int07= 0 '.
               ' WHERE a.int01 = 0 AND a.int02= 2 AND a.int03 = b.feed_id AND  b.is_del = 0';
        $this->query($sql);

        $map['int01'] = 0;
        $map['int02'] = 2;
        $maxId = $this->where($map)->field('MAX(int03) AS maxId')->find();
        $maxId = intval($maxId['maxId']);

        $fmap['feed_id'] = array('gt', $maxId);
        $feedIds = model('Feed')->where($fmap)->getAsFieldArray('feed_id');
        if (empty($feedIds)) {
            return false;
        }
        $feedInfos = model('Feed')->getFeeds($feedIds);
        $add['app'] = 'public';
        $add['type'] = 'feed';
        $add['int01'] = 0;
        $add['int02'] = 2;
        $feedType = array('post' => 1, 'repost' => 2, 'postimage' => 3, 'postfile' => 4);
        foreach ($feedInfos as $v) {
            $add['string01'] = t($v['title']);
            $add['int03'] = $v['feed_id'];
            $add['int04'] = isset($feedType[$v['type']]) ? $feedType[$v['type']] : 0;
            $add['int05'] = $v['uid'];
            $add['int06'] = $v['publish_time'];
            $add['int07'] = $v['is_del'];
            $add['int08'] = $v['from'];
            $add['content'] = t($v['body']);
            $this->add($add);
        }
    }
}
