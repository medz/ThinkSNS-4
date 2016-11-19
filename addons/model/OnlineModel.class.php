
<?php
/**
 * 在线统计模型 - 业务逻辑模型
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
class OnlineModel
{
    private $today = 0;                        // 今日日期字符串
    private $todayTimestamp = 0;            // 今日0点的时间戳
    private $check_point = 0;                // 查询在线起始时间点的时间戳
    private $check_step = 1200;                // 检查在线用户步长，10分钟检查一次
    private $stats_step = 1800;                // 统计在线用户步长，30分钟
    /**
     * 初始化方法，数据库配置、连接初始化
     */
    public function __construct()
    {
        $dbconfig = array();
        $_config = C('ONLINE_DB');
        $dbconfig['DB_TYPE'] = isset($_config['DB_TYPE']) ? $_config['DB_TYPE'] : C('DB_TYPE');
        $dbconfig['DB_HOST'] = isset($_config['DB_HOST']) ? $_config['DB_HOST'] : C('DB_HOST');
        $dbconfig['DB_NAME'] = isset($_config['DB_NAME']) ? $_config['DB_NAME'] : C('DB_NAME');
        $dbconfig['DB_USER'] = isset($_config['DB_USER']) ? $_config['DB_USER'] : C('DB_USER');
        $dbconfig['DB_PWD'] = isset($_config['DB_PWD']) ? $_config['DB_PWD'] : C('DB_PWD');
        $dbconfig['DB_PORT'] = isset($_config['DB_PORT']) ? $_config['DB_PORT'] : C('DB_PORT');
        $dbconfig['DB_PREFIX'] = isset($_config['DB_PREFIX']) ? $_config['DB_PREFIX'] : C('DB_PREFIX');
        $dbconfig['DB_CHARSET'] = isset($_config['DB_CHARSET']) ? $_config['DB_CHARSET'] : C('DB_CHARSET');

        $db_pwd = $dbconfig['DB_PWD'];

        if ($dbconfig['DB_ENCRYPT'] == 1) {
            if ($db_pwd != '') {
                require_once SITE_PATH.'/addons/library/CryptDES.php';
                $crypt = new CryptDES;
                $db_pwd = (string) $crypt->decrypt($db_pwd);
            }
        }
        // 重设Service的数据连接信息
        $connection = array(
                            'dbms' => $dbconfig['DB_TYPE'],
                            'hostname' => $dbconfig['DB_HOST'],
                            'hostport' => $dbconfig['DB_PORT'],
                            'database' => $dbconfig['DB_NAME'],
                            'username' => $dbconfig['DB_USER'],
                            'password' => $db_pwd,
                        );
        // 实例化Online数据库连接
        $this->odb = new Db($connection);
        $this->today = date('Y-m-d');
        $this->todayTimestamp = strtotime(date('Y-m-d'));
    }

    /**
     * 获取统计列表
     * @param  string $where 查询条件
     * @param  int    $limit 结果集数目，默认为30
     * @return array  统计列表数据
     */
    public function getStatsList($where = '1', $limit = 30)
    {
        $p = $_REQUEST['p'] ? $_REQUEST['p'] : 1;
        $start = ($p - 1) * $limit;
        $sqlCount = 'SELECT COUNT(1) as count FROM '.C('DB_PREFIX')."online_stats WHERE {$where} ";
        if ($count = $this->odb->query($sqlCount)) {
            $count = $count[0]['count'];
        } else {
            $count = 0;
        }

        $sql = 'SELECT * FROM '.C('DB_PREFIX')."online_stats WHERE {$where} LIMIT $start,$limit";

        $data = $this->odb->query($sql);

        $p = new Page($count, $limit);
        $output['count'] = $count;
        $output['totalPages'] = $p->totalPages;
        $output['totalRows'] = $p->totalRows;
        $output['nowPage'] = $p->nowPage;
        $output['html'] = $p->show();
        $output['data'] = $data;

        return $output;
    }

    /**
     * 执行统计
     */
    public function dostatus()
    {
        // 获取最大ID
        $sql = 'SELECT MAX(id) AS max_id FROM '.C('DB_PREFIX').'online_logs WHERE statsed = 0';
        $max_id = $this->odb->query($sql);
        $max_id = @$max_id['0']['max_id'];
        if (empty($max_id)) {
            return false;
        } else {
            $sql = 'UPDATE '.C('DB_PREFIX')."online_logs SET statsed = 1 WHERE id < {$max_id}";
            $this->odb->execute($sql);
        }
        // 开始统计
        // TODO:需要计划任务支持移动上一日数据到备份表，现在在每次统计之后备份今天之前的数据到备份表
        // 从logs累计总的用户数，总的游客数到stats表
        $userDataSql = 'SELECT COUNT(1) AS pv, COUNT(DISTINCT uid) AS pu, COUNT(ip) AS guestpu, `day`, isGuest 
						FROM `'.C('DB_PREFIX')."online_logs`
						WHERE id <= {$max_id}
						GROUP BY day, isGuest";

        $userData = $this->odb->query($userDataSql);

        if (!empty($userData)) {
            $upData = array();
            foreach ($userData as $v) {
                if ($v['isGuest'] == 0) {
                    // 注册用户
                    $upData[$v['day']]['total_users'] = $v['pu'];
                    $upData[$v['day']]['total_pageviews'] += $v['pv'];
                } else {
                    // 游客
                    $upData[$v['day']]['total_guests'] = $v['guestpu'];
                    $upData[$v['day']]['total_pageviews'] += $v['pv'];
                }
            }
            foreach ($upData as $k => $v) {
                $sql = 'SELECT id FROM '.C('DB_PREFIX')."online_stats WHERE day = '{$k}'";
                $issetRow = $this->odb->query($sql);
                if (empty($issetRow)) {
                    $sql = 'INSERT INTO '.C('DB_PREFIX')."online_stats (`day`,`total_users`,`total_guests`,`total_pageviews`) 
							 VALUES ('{$k}','{$v['total_users']}','{$v['total_guests']}','{$v['total_pageviews']}')";
                } else {
                    $sql = ' UPDATE '.C('DB_PREFIX')."online_stats 
							 SET total_users = '{$v['total_users']}',
							 total_guests = '{$v['total_guests']}',
							 total_pageviews = {$v['total_pageviews']}
							 WHERE day = '{$k}'";
                }
                $this->odb->execute($sql);
            }
        }

        // 从online表统计在线用户到most_onine_user表
        $this->checkOnline();
        // 将logs表中今天之前的的数据移动到bak表
        $sql = 'INSERT INTO '.C('DB_PREFIX').'online_logs_bak SELECT * FROM `'.C('DB_PREFIX')."online_logs` WHERE day <='".date('Y-m-d', strtotime('-1 day'))."'";
        $this->odb->execute($sql);
        // 删除logs表中今天之前的数据删除
        $sql = ' DELETE FROM `'.C('DB_PREFIX')."online_logs` WHERE day <='".date('Y-m-d', strtotime('-1 day'))."'";
        // 统计结束
        $this->odb->execute($sql);
    }

    /**
     * 在线用户检查及入库
     */
    public function checkOnline()
    {
        $startTime = time() - $this->stats_step;
        $day = date('Y-m-d');
        // 今日统计数据
        $sql = 'SELECT * FROM '.C('DB_PREFIX')."online_stats WHERE day ='{$day}'";
        $dayData = $this->odb->query($sql);

        if (!empty($dayData)) {
            // 在线注册用户
            $sql = 'SELECT COUNT(1) AS pu FROM '.C('DB_PREFIX')."online WHERE uid !=0 AND activeTime  >= {$startTime}";
            $onlineData = $this->odb->query($sql);

            $set = array();
            if ($onlineData && $onlineData[0]['pu'] > 0 && $onlineData[0]['pu'] > $dayData[0]['most_online_users']) {
                $set[] = 'most_online_users = '.$onlineData[0]['pu'];
            }
            // 在线游客
            $sql = 'SELECT COUNT(ip) AS pu FROM '.C('DB_PREFIX')."online WHERE uid = 0 AND activeTime >= {$startTime}";
            $onlineGuestData = $this->odb->query($sql);
            if ($onlineGuestData && $onlineGuestData[0]['pu'] > 0 && $onlineGuestData[0]['pu'] > $dayData[0]['most_online_guests']) {
                $set[] = ' most_online_guests = '.$onlineGuestData[0]['pu'];
            }
            $mostUser = intval($onlineData[0]['pu']) + intval($onlineGuestData[0]['pu']);
            if (empty($mostUser)) {
                $mostUser = 1;
            }
            if ($mostUser > $dayData[0]['most_online']) {
                $set[] = ' most_online = '.$mostUser;
                $set[] = ' most_online_time  ='.time();
            }

            if (!empty($set)) {
                $sql = ' UPDATE '.C('DB_PREFIX').'online_stats SET '.implode(',', $set)." WHERE day = '{$day}'";
                $this->odb->execute($sql);
            }
        }
    }

    /**
     * 获取指定用户最后操作的IP地址信息
     * @param  array $uids 指定用户ID数组
     * @return array 指定用户最后操作的IP地址信息
     */
    public function getLastOnlineInfo($uids)
    {
        $map['uid'] = array('IN', $uids);
        $data = D()->table(C('DB_PREFIX').'online')->where($map)->getHashList('uid', 'ip');

        return $data;
    }

    /**
     * 获取指定用户的操作知识 - 分页型
     * @param  int    $uid   用户ID
     * @param  array  $map   查询条件
     * @param  int    $count 结果集数目，默认为20
     * @param  string $order 排序条件，默认为day DESC
     * @return array  指定用户的操作知识 - 分页型
     */
    public function getUserOperatingList($uid, $map, $count = 20, $order = 'id DESC')
    {
        $map['uid'] = $uid;
        $data = D()->table(C('DB_PREFIX').'online_logs_bak')->where($map)->order($order)->findPage($count);

        return $data;
    }
}
