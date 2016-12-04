<?php
/**
 * ThinkSNS 数据库中间层，只支持MySQL
 * @author  liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS v4
 */

use Illuminate\Database\Capsule\Manager as Capsule;

// 定义MySQL查询输出封装为多结果，必须用循环处理
define('CLIENT_MULTI_RESULTS', 131072);

class Db extends Think
{
    private static $_instance = null;
    // 是否自动释放查询结果
    protected $autoFree = false;
    // 是否显示调试信息 如果启用会在知识文件记录sql语句
    public $debug = false;
    // 是否使用永久连接
    protected $pconnect = false;
    // 当前SQL指令
    protected $queryStr = '';
    // 最后插入ID
    protected $lastInsID = null;
    // 返回或者影响记录数
    protected $numRows = 0;
    // 返回字段数
    protected $numCols = 0;
    // 事务指令数
    protected $transTimes = 0;
    // 错误信息
    protected $error = '';
    // 数据库连接ID 支持多个连接
    protected $linkID = array();
    // 当前连接ID
    protected $_linkID = null;
    // 当前查询ID
    protected $queryID = null;
    // 是否已经连接数据库
    protected $connected = false;
    // 数据库连接参数配置
    protected $config = '';
    // SQL 执行时间记录
    protected $beginTime;
    // 数据库表达式
    protected $comparison = array('eq' => '=', 'neq' => '!=', 'gt' => '>', 'egt' => '>=', 'lt' => '<', 'elt' => '<=', 'notlike' => 'NOT LIKE', 'like' => 'LIKE');
    // 查询表达式
    protected $selectSql = 'SELECT%DISTINCT% %FIELDS% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';
    /**
     * 架构函数
     * @param array $config 数据库配置数组
     */
    public function __construct($config = '')
    {
        // if (!extension_loaded('mysql')) {
        //     throw_exception(L('_NOT_SUPPORT_').':mysql');
        // }
        $this->debug = isset($_GET['debug']) ? true : C('APP_DEBUG');
        $this->config = $this->parseConfig($config);
    }

    /**
     * 获取数据库单例实例
     *
     * @return object self
     * @author Seven Du <lovevipdsw@vip.qq.com>
     **/
    public static function getInstance($config = null)
    {
        if (!self::$_instance instanceof self) {
            self::$_instance = new self($config);
        }

        return self::$_instance;
    }

    /**
     * 分析数据库配置信息，支持数组和DSN
     * @param  mixed  $db_config 数据库配置信息
     * @return string
     */
    private function parseConfig($db_config = '')
    {
        if (!empty($db_config) && is_string($db_config)) {
            // 如果DSN字符串则进行解析
            $db_config = $this->parseDSN($db_config);
        } elseif (empty($db_config)) {
            // 如果配置为空，读取配置文件设置
            $db_config = array(
                'dbms' => C('DB_TYPE'),
                'username' => C('DB_USER'),
                'password' => C('DB_PWD'),
                'hostname' => C('DB_HOST'),
                'hostport' => C('DB_PORT'),
                'database' => C('DB_NAME'),
                'dsn' => C('DB_DSN'),
                'params' => C('DB_PARAMS'),
            );
        }

        return $db_config;
    }

    /**
     +----------------------------------------------------------
     * 增加数据库连接(相同类型的)
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param mixed $config  数据库连接信息
     * @param mixed $linkNum 创建的连接序号
     +----------------------------------------------------------
     +----------------------------------------------------------
     */
    public function addConnect($config, $linkNum = null)
    {
        $db_config = $this->parseConfig($config);
        if (empty($linkNum)) {
            $linkNum = count($this->linkID);
        }
        if (isset($this->linkID[$linkNum])) {
            // 已经存在连接
            return false;
        }
        // 创建新的数据库连接
        return $this->connect($db_config, $linkNum);
    }

    /**
     +----------------------------------------------------------
     * 切换数据库连接
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param int $linkNum 创建的连接序号
     +----------------------------------------------------------
     +----------------------------------------------------------
     */
    public function switchConnect($linkNum)
    {
        if (isset($this->linkID[$linkNum])) {
            // 存在指定的数据库连接序号
            $this->_linkID = $this->linkID[$linkNum];

            return true;
        } else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 初始化数据库连接
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param bool $master 主服务器
     +----------------------------------------------------------
     +----------------------------------------------------------
     */
    protected function initConnect($master = true)
    {
        if (1 == C('DB_DEPLOY_TYPE')) {
            // 采用分布式数据库
            $this->_linkID = $this->multiConnect($master);
        } else {
            // 默认单数据库
            if (!$this->connected) {
                $this->_linkID = $this->connect();
            }
        }
    }

    /**
     +----------------------------------------------------------
     * 连接分布式服务器
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param bool $master 主服务器
     +----------------------------------------------------------
     +----------------------------------------------------------
     */
    protected function multiConnect($master = false)
    {
        static $_config = array();
        if (empty($_config)) {
            // 缓存分布式数据库配置解析
            foreach ($this->config as $key => $val) {
                $_config[$key] = explode(',', $val);
            }
        }
        // 数据库读写是否分离
        if (C('DB_RW_SEPARATE')) {
            // 主从式采用读写分离
            if ($master) {
                // 默认主服务器是连接第一个数据库配置
                $r = 0;
            } else {
                // 读操作连接从服务器
                $r = floor(mt_rand(1, count($_config['hostname']) - 1));
            }   // 每次随机连接的数据库
        } else {
            // 读写操作不区分服务器
            $r = floor(mt_rand(0, count($_config['hostname']) - 1));   // 每次随机连接的数据库
        }
        $db_config = array(
            'username' => isset($_config['username'][$r]) ? $_config['username'][$r] : $_config['username'][0],
            'password' => isset($_config['password'][$r]) ? $_config['password'][$r] : $_config['password'][0],
            'hostname' => isset($_config['hostname'][$r]) ? $_config['hostname'][$r] : $_config['hostname'][0],
            'hostport' => isset($_config['hostport'][$r]) ? $_config['hostport'][$r] : $_config['hostport'][0],
            'database' => isset($_config['database'][$r]) ? $_config['database'][$r] : $_config['database'][0],
            'dsn' => isset($_config['dsn'][$r]) ? $_config['dsn'][$r] : $_config['dsn'][0],
            'params' => isset($_config['params'][$r]) ? $_config['params'][$r] : $_config['params'][0],
        );

        return $this->connect($db_config, $r);
    }

    /**
     +----------------------------------------------------------
     * DSN解析
     * 格式： mysql://username:passwd@localhost:3306/DbName
     * @static
     * @param  string $dsnStr
     * @return array
     */
    public function parseDSN($dsnStr)
    {
        if (empty($dsnStr)) {
            return false;
        }
        $info = parse_url($dsnStr);
        if ($info['scheme']) {
            $dsn = array(
            'dbms' => $info['scheme'],
            'username' => isset($info['user']) ? $info['user'] : '',
            'password' => isset($info['pass']) ? $info['pass'] : '',
            'hostname' => isset($info['host']) ? $info['host'] : '',
            'hostport' => isset($info['port']) ? $info['port'] : '',
            'database' => isset($info['path']) ? substr($info['path'], 1) : '',
            );
        } else {
            preg_match('/^(.*?)\:\/\/(.*?)\:(.*?)\@(.*?)\:([0-9]{1, 6})\/(.*?)$/', trim($dsnStr), $matches);
            $dsn = array(
            'dbms' => $matches[1],
            'username' => $matches[2],
            'password' => $matches[3],
            'hostname' => $matches[4],
            'hostport' => $matches[5],
            'database' => $matches[6],
            );
        }

        return $dsn;
    }

    /**
     * 数据库调试 记录当前SQL
     */
    protected function debug()
    {
        // 记录操作结束时间
        if ($this->debug) {
            $runtime = number_format(microtime(true) - $this->beginTime, 6);
            Log::record(' RunTime:'.$runtime.'s SQL = '.$this->queryStr, Log::SQL, true); //强行记录SQL知识
        }
    }

    /**
     * 设置锁机制
     * @return string
     */
    protected function parseLock($lock = false)
    {
        if (!$lock) {
            return '';
        }

        return ' FOR UPDATE ';
    }

    /**
     * set分析
     * @param  array  $data
     * @return string
     */
    protected function parseSet($data)
    {
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            if (is_scalar($value)) { // 过滤非标量数据
                $set[] = $this->addSpecialChar($key).'='.$value;
            }
        }

        return ' SET '.implode(',', $set);
    }

    /**
     * value分析
     * @param  mixed  $value
     * @return string
     */
    protected function parseValue(&$value)
    {
        if (is_string($value)) {
            $value = '\''.$this->escape_string($value).'\'';
        } elseif (isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp') {
            $value = $this->escape_string($value[1]);
        } elseif (is_null($value)) {
            $value = 'null';
        }

        return $value;
    }

    /**
     * field分析
     * @param  mixed  $fields
     * @return string
     */
    protected function parseField($fields)
    {
        if (is_array($fields)) {
            // 完善数组方式传字段名的支持
            // 支持 'field1'=>'field2' 这样的字段别名定义
            $array = array();
            foreach ($fields as $key => $field) {
                if (!is_numeric($key)) {
                    $array[] = $this->addSpecialChar($key).' AS '.$this->addSpecialChar($field);
                } else {
                    $array[] = $this->addSpecialChar($field);
                }
            }
            $fieldsStr = implode(',', $array);
        } elseif (is_string($fields) && !empty($fields)) {
            $fieldsStr = $this->addSpecialChar($fields);
        } else {
            $fieldsStr = '*';
        }

        return $fieldsStr;
    }

    /**
     * table分析
     * @param  mixed  $table
     * @return string
     */
    protected function parseTable($tables)
    {
        if (is_string($tables)) {
            $tables = explode(',', $tables);
        }
        array_walk($tables, array(&$this, 'addSpecialChar'));

        return implode(',', $tables);
    }

    /**
     * where分析
     * @param  mixed  $where
     * @return string
     */
    protected function parseWhere($where)
    {
        $whereStr = '';
        if (is_string($where)) {
            // 直接使用字符串条件
            $whereStr = $where;
        } else { // 使用数组条件表达式
            if (array_key_exists('_logic', $where)) {
                // 定义逻辑运算规则 例如 OR XOR AND NOT
                $operate = ' '.strtoupper($where['_logic']).' ';
                unset($where['_logic']);
            } else {
                // 默认进行 AND 运算
                $operate = ' AND ';
            }
            foreach ($where as $key => $val) {
                $whereStr .= '( ';
                if (0 === strpos($key, '_')) {
                    // 解析特殊条件表达式
                    $whereStr .= $this->parseThinkWhere($key, $val);
                } else {
                    $key = $this->addSpecialChar($key);
                    if (is_array($val)) {
                        if (is_string($val[0])) {
                            if (preg_match('/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)$/i', $val[0])) { // 比较运算
                                $whereStr .= $key.' '.$this->comparison[strtolower($val[0])].' '.$this->parseValue($val[1]);
                            } elseif ('exp' == strtolower($val[0])) { // 使用表达式
                                $whereStr .= ' ('.$key.' '.$val[1].') ';
                            } elseif (preg_match('/IN/i', $val[0])) { // IN 运算
                                $zone = is_array($val[1]) ? implode(',', $this->parseValue($val[1])) : $val[1];
                                $whereStr .= $key.' '.strtoupper($val[0]).' ('.$zone.')';
                            } elseif (preg_match('/BETWEEN/i', $val[0])) { // BETWEEN运算
                                $data = is_string($val[1]) ? explode(',', $val[1]) : $val[1];
                                $whereStr .= ' ('.$key.' BETWEEN '.$data[0].' AND '.$data[1].' )';
                            } else {
                                throw_exception(L('_EXPRESS_ERROR_').':'.$val[0]);
                            }
                        } else {
                            $count = count($val);
                            if (in_array(strtoupper(trim($val[$count - 1])), array('AND', 'OR', 'XOR'))) {
                                $rule = strtoupper(trim($val[$count - 1]));
                                $count = $count - 1;
                            } else {
                                $rule = 'AND';
                            }
                            for ($i = 0; $i < $count; $i++) {
                                $data = is_array($val[$i]) ? $val[$i][1] : $val[$i];
                                if ('exp' == strtolower($val[$i][0])) {
                                    $whereStr .= '('.$key.' '.$data.') '.$rule.' ';
                                } else {
                                    $op = is_array($val[$i]) ? $this->comparison[strtolower($val[$i][0])] : '=';
                                    $whereStr .= '('.$key.' '.$op.' '.$this->parseValue($data).') '.$rule.' ';
                                }
                            }
                            $whereStr = substr($whereStr, 0, -4);
                        }
                    } else {
                        //对字符串类型字段采用模糊匹配
                        if (C('DB_LIKE_FIELDS') && preg_match('/('.C('DB_LIKE_FIELDS').')/i', $key)) {
                            $val = '%'.$val.'%';
                            $whereStr .= $key.' LIKE '.$this->parseValue($val);
                        } else {
                            $whereStr .= $key.' = '.$this->parseValue($val);
                        }
                    }
                }
                $whereStr .= ' )'.$operate;
            }
            $whereStr = substr($whereStr, 0, -strlen($operate));
        }

        return empty($whereStr) ? '' : ' WHERE '.$whereStr;
    }

    /**
     * 特殊条件分析
     * @param  string $key
     * @param  mixed  $val
     * @return string
     */
    protected function parseThinkWhere($key, $val)
    {
        $whereStr = '';
        switch ($key) {
            case '_string':
                // 字符串模式查询条件
                $whereStr = $val;
                break;
            case '_complex':
                // 复合查询条件
                $whereStr = substr($this->parseWhere($val), 6);
                break;
            case '_query':
                // 字符串模式查询条件
                parse_str($val, $where);
                if (array_key_exists('_logic', $where)) {
                    $op = ' '.strtoupper($where['_logic']).' ';
                    unset($where['_logic']);
                } else {
                    $op = ' AND ';
                }
                $array = array();
                foreach ($where as $field => $data) {
                    $array[] = $this->addSpecialChar($field).' = '.$this->parseValue($data);
                }
                $whereStr = implode($op, $array);
                break;
        }

        return $whereStr;
    }

    /**
     * limit分析
     * @param  mixed  $lmit
     * @return string
     */
    protected function parseLimit($limit)
    {
        return !empty($limit) ? ' LIMIT '.$limit.' ' : '';
    }

    /**
     * join分析
     * @param  mixed  $join
     * @return string
     */
    protected function parseJoin($join)
    {
        $joinStr = '';
        if (!empty($join)) {
            if (is_array($join)) {
                foreach ($join as $key => $_join) {
                    if (false !== stripos($_join, 'JOIN')) {
                        $joinStr .= ' '.$_join;
                    } else {
                        $joinStr .= ' LEFT JOIN '.$_join;
                    }
                }
            } else {
                $joinStr .= ' LEFT JOIN '.$join;
            }
        }

        return $joinStr;
    }

    /**
     * order分析
     * @param  mixed  $order
     * @return string
     */
    protected function parseOrder($order)
    {
        if (is_array($order)) {
            $array = array();
            foreach ($order as $key => $val) {
                if (is_numeric($key)) {
                    $array[] = $this->addSpecialChar($val);
                } else {
                    $array[] = $this->addSpecialChar($key).' '.$val;
                }
            }
            $order = implode(',', $array);
        }

        return !empty($order) ? ' ORDER BY '.$order : '';
    }

    /**
     * group分析
     * @param  mixed  $group
     * @return string
     */
    protected function parseGroup($group)
    {
        return !empty($group) ? ' GROUP BY '.$group : '';
    }

    /**
     * having分析
     * @param  string $having
     * @return string
     */
    protected function parseHaving($having)
    {
        return  !empty($having) ? ' HAVING '.$having : '';
    }

    /**
     * distinct分析
     * @param  mixed  $distinct
     * @return string
     */
    protected function parseDistinct($distinct)
    {
        return !empty($distinct) ? ' DISTINCT ' : '';
    }

    /**
     * 插入记录
     * @param  mixed $data    数据
     * @param  array $options 参数表达式
     * @return false | integer
     */
    public function insert($data, $options = array())
    {
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            if (is_scalar($value)) { // 过滤非标量数据
                $values[] = $value;
                $fields[] = $this->addSpecialChar($key);
            }
        }
        $sql = 'INSERT INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);

        return $this->execute($sql);
    }

    /**
     * 通过Select方式插入记录
     * @param  string $fields 要插入的数据表字段名
     * @param  string $table  要插入的数据表名
     * @param  array $option 查询数据参数
     * @return false | integer
     */
    public function selectInsert($fields, $table, $options = array())
    {
        if (is_string($fields)) {
            $fields = explode(',', $fields);
        }
        array_walk($fields, array($this, 'addSpecialChar'));
        $sql = 'INSERT INTO '.$this->parseTable($table).' ('.implode(',', $fields).') ';
        $sql .= str_replace(
            array('%TABLE%', '%DISTINCT%', '%FIELDS%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                $this->parseField(isset($options['field']) ? $options['field'] : '*'),
                $this->parseJoin(isset($options['join']) ? $options['join'] : ''),
                $this->parseWhere(isset($options['where']) ? $options['where'] : ''),
                $this->parseGroup(isset($options['group']) ? $options['group'] : ''),
                $this->parseHaving(isset($options['having']) ? $options['having'] : ''),
                $this->parseOrder(isset($options['order']) ? $options['order'] : ''),
                $this->parseLimit(isset($options['limit']) ? $options['limit'] : ''),
            ), $this->selectSql);
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);

        return $this->execute($sql);
    }

    /**
     * 更新记录
     * @param  mixed $data    数据
     * @param  array $options 表达式
     * @return false | integer
     */
    public function update($data, $options)
    {
        $sql = 'UPDATE '
            .$this->parseTable($options['table'])
            .$this->parseSet($data)
            .$this->parseWhere(isset($options['where']) ? $options['where'] : '')
            .$this->parseOrder(isset($options['order']) ? $options['order'] : '')
            .$this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
            .$this->parseLock(isset($options['lock']) ? $options['lock'] : false);

        return $this->execute($sql);
    }

    /**
     * 删除记录
     * @param  array $options 表达式
     * @return false | integer
     */
    public function delete($options = array())
    {
        $sql = 'DELETE FROM '
            .$this->parseTable($options['table'])
            .$this->parseWhere(isset($options['where']) ? $options['where'] : '')
            .$this->parseOrder(isset($options['order']) ? $options['order'] : '')
            .$this->parseLimit(isset($options['limit']) ? $options['limit'] : '')
            .$this->parseLock(isset($options['lock']) ? $options['lock'] : false);

        return $this->execute($sql);
    }

    /**
     * 查找记录
     * @param  array $options 表达式
     * @return array
     */
    public function select($options = array())
    {
        if (isset($options['page'])) {
            // 根据页数计算limit
            list($page, $listRows) = explode(',', $options['page']);
            $listRows = $listRows ? $listRows : ((isset($options['limit']) && is_numeric($options['limit'])) ? $options['limit'] : 20);
            $offset = $listRows * ((int) $page - 1);
            $options['limit'] = $offset.','.$listRows;
        }
        $sql = str_replace(
            array('%TABLE%', '%DISTINCT%', '%FIELDS%', '%JOIN%', '%WHERE%', '%GROUP%', '%HAVING%', '%ORDER%', '%LIMIT%'),
            array(
                $this->parseTable($options['table']),
                $this->parseDistinct(isset($options['distinct']) ? $options['distinct'] : false),
                $this->parseField(isset($options['field']) ? $options['field'] : '*'),
                $this->parseJoin(isset($options['join']) ? $options['join'] : ''),
                $this->parseWhere(isset($options['where']) ? $options['where'] : ''),
                $this->parseGroup(isset($options['group']) ? $options['group'] : ''),
                $this->parseHaving(isset($options['having']) ? $options['having'] : ''),
                $this->parseOrder(isset($options['order']) ? $options['order'] : ''),
                $this->parseLimit(isset($options['limit']) ? $options['limit'] : ''),
            ), $this->selectSql);
        $sql .= $this->parseLock(isset($options['lock']) ? $options['lock'] : false);

        return $this->query($sql);
    }

    /**
     * 字段和表名添加`
     * 保证指令中使用关键字不出错 针对mysql
     * @param  mixed $value
     * @return mixed
     */
    protected function addSpecialChar(&$value)
    {
        $value = trim($value);
        if (false !== strpos($value, ' ') || false !== strpos($value, ',') || false !== strpos($value, '*') || false !== strpos($value, '(') || false !== strpos($value, '.') || false !== strpos($value, '`')) {
            //如果包含* 或者 使用了sql方法 则不作处理
        } else {
            $value = '`'.$value.'`';
        }

        return $value;
    }

    /**
     * 查询次数更新或者查询
     * @param mixed $times
     */
    public function Q($times = '')
    {
        static $_times = 0;
        if (empty($times)) {
            return $_times;
        } else {
            $_times++;
            // 记录开始执行时间
            $this->beginTime = microtime(true);
        }
    }

    /**
     * 写入次数更新或者查询
     * @param mixed $times
     */
    public function W($times = '')
    {
        static $_times = 0;
        if (empty($times)) {
            return $_times;
        } else {
            $_times++;
            // 记录开始执行时间
            $this->beginTime = microtime(true);
        }
    }

    /**
     * 获取最近一次查询的sql语句
     * @return string
     */
    public function getLastSql()
    {
        return $this->queryStr;
    }

    /**
     * 连接数据库方法
     * @throws ThinkExecption
     */
    public function connect($config = '', $linkNum = 0)
    {
        if (!isset($this->linkID[$linkNum])) {
            if (empty($config)) {
                $config = $this->config;
            }
            // 处理不带端口号的socket连接情况
            $host = $config['hostname'].($config['hostport'] ? ":{$config['hostport']}" : '');
            if ($this->pconnect) {
                $this->linkID[$linkNum] = mysql_pconnect($host, $config['username'], $config['password'], CLIENT_MULTI_RESULTS);
            } else {
                $this->linkID[$linkNum] = mysql_connect($host, $config['username'], $config['password'], true, CLIENT_MULTI_RESULTS);
            }
            if (!$this->linkID[$linkNum] || (!empty($config['database']) && !mysql_select_db($config['database'], $this->linkID[$linkNum]))) {
                throw_exception(mysql_error());
            }
            $dbVersion = mysql_get_server_info($this->linkID[$linkNum]);
            if ($dbVersion >= '4.1') {
                //使用UTF8存取数据库 需要mysql 4.1.0以上支持
                mysql_query("SET NAMES '".C('DB_CHARSET')."'", $this->linkID[$linkNum]);
            }
            //设置 sql_model
            if ($dbVersion > '5.0.1') {
                mysql_query("SET sql_mode=''", $this->linkID[$linkNum]);
            }
            // 标记连接成功
            $this->connected = true;
            // 注销数据库连接配置信息
            if (1 != C('DB_DEPLOY_TYPE')) {
                unset($this->config);
            }
        }

        return $this->linkID[$linkNum];
    }

    /**
     * 释放查询结果
     */
    public function free()
    {
        // @mysql_free_result($this->queryID);
        $this->queryID = 0;
    }

    /**
     * 执行查询 主要针对 SELECT, SHOW 等指令
     * 返回数据集
     * @param  string         $str sql指令
     * @return mixed
     * @throws ThinkExecption
     */
    public function query($str)
    {
        try {
            // $sth = Capsule::getReadPdo()->prepare($str);
            // var_dump($sth);exit;
            $this->queryStr = $str;
            $pdos = Capsule::getReadPdo()->query($str);
            $this->debug();
            $this->numRows = $pdos->rowCount();

            return $pdos->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }

        return false;

        // // var_dump(Capsule::getReadPdo()->query('show tablesa'));exit;
        // return Capsule::select($str);
        // $this->initConnect(false);
        // if (!$this->_linkID) {
        //     return false;
        // }
        // $this->queryStr = $str;
        // //释放前次的查询结果
        // if ($this->queryID) {
        //     $this->free();
        // }
        // $this->Q(1);
        // // var_dump($str);exit;
        // // var_dump(Capsule::select($str));
        // $this->queryID = mysql_query($str, $this->_linkID);
        // $this->debug();
        // if (false === $this->queryID) {
        //     $this->error();
        //     return false;
        // } else {
        //     $this->numRows = mysql_num_rows($this->queryID);
        //     // var_dump($this->numRows);exit;
        //     return $this->getAll();
        // }
    }

    /**
     * 执行语句 针对 INSERT, UPDATE 以及DELETE
     * @param  string         $str sql指令
     * @return int
     * @throws ThinkExecption
     */
    public function execute($str)
    {
        try {
            $this->queryStr = $str;
            $this->numRows = Capsule::getReadPdo()->exec($str);
            $this->lastInsID = Capsule::getReadPdo()->lastInsertId();
            $this->debug();

            return $this->numRows;
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
        }

        return false;

        // // return Capsule::select($str);
        // $this->initConnect(true);
        // if (!$this->_linkID) {
        //     return false;
        // }
        // $this->queryStr = $str;
        // //释放前次的查询结果
        // if ($this->queryID) {
        //     $this->free();
        // }
        // $this->W(1);
        // // var_dump($str);exit;
        // $result =   mysql_query($str, $this->_linkID) ;
        // $this->debug();
        // if (false === $result) {
        //     $this->error();
        //     return false;
        // } else {
        //     $this->numRows = mysql_affected_rows($this->_linkID);
        //     $this->lastInsID = mysql_insert_id($this->_linkID);
        //     return $this->numRows;
        // }
    }

    /**
     * 启动事务
     * @throws ThinkExecption
     */
    public function startTrans()
    {
        /*$this->initConnect(true);
        if (!$this->_linkID) {
            return false;
        }*/
        //数据rollback 支持
        if ($this->transTimes == 0) {
            Capsule::getReadPdo()->beginTransaction();
            // mysql_query('START TRANSACTION', $this->_linkID);
        }
        $this->transTimes++;

        return ;
    }

    /**
     * 用于非自动提交状态下面的查询提交
     * @return boolen
     * @throws ThinkExecption
     */
    public function commit()
    {
        if ($this->transTimes > 0) {
            // Capsule::getReadPdo()->commit();
            // $result = mysql_query('COMMIT', $this->_linkID);
            if (!Capsule::getReadPdo()->commit()) {
                throw_exception($this->error());
            }
            $this->transTimes = 0;
        }

        return true;
    }

    /**
     * 事务回滚
     * @return boolen
     * @throws ThinkExecption
     */
    public function rollback()
    {
        if ($this->transTimes > 0) {
            // $result = mysql_query('ROLLBACK', $this->_linkID);
            $this->transTimes = 0;
            if (!Capsule::getReadPdo()->rollBack()) {
                throw_exception($this->error());
            }
        }

        return true;
    }

    /**
     * 获得所有的查询数据
     * @return array
     * @throws ThinkExecption
     */
    private function getAll()
    {
        //返回数据集
        $result = array();
        if ($this->numRows > 0) {
            while ($row = mysql_fetch_assoc($this->queryID)) {
                $result[] = $row;
            }
            mysql_data_seek($this->queryID, 0);
        }
        // var_dump($result);
        return $result;
    }

    /**
     * 获取以传入的参数的数据结果集合
     * @return array
     * @throws ThinkExecption
     */
    public function getAsFieldArray($field = '*', $nouse = '')
    {
        if (!$this->queryID) {
            throw_exception($this->error());

            return false;
        }
        //返回数据集
        $result = array();
        if ($this->numRows > 0) {
            while ($row = mysql_fetch_assoc($this->queryID)) {
                $result[] = $field == '*' ? $row : @$row[$field];
            }
            mysql_data_seek($this->queryID, 0);
        }

        return $result;
    }

    /**
     * 获取以hashkey作为键值的hash数组
     * @return array
     * @throws ThinkExecption
     */
    public function getHashList($hashKey = '', $hashValue = '*')
    {
        if (!$this->queryID) {
            throw_exception($this->error());

            return false;
        }
        //返回数据集
        $result = array();
        if ($this->numRows > 0) {
            while ($row = mysql_fetch_assoc($this->queryID)) {
                if (empty($hashKey)) {
                    $reuslt[] = $hashValue == '*' ? $row : @$row[$hashValue];
                } else {
                    $result[$row[$hashKey]] = $hashValue == '*' ? $row : @$row[$hashValue];
                }
            }
            mysql_data_seek($this->queryID, 0);
        }

        return $result;
    }

    /**
     * 取得数据表的字段信息
     */
    public function getFields($tableName)
    {
        $result = $this->query('SHOW COLUMNS FROM '.$tableName);
        $info = array();
        if ($result) {
            foreach ($result as $key => $val) {
                $info[$val['Field']] = array(
                    'name' => $val['Field'],
                    'type' => $val['Type'],
                    'notnull' => (bool) ($val['Null'] === ''), // not null is empty, null is yes
                    'default' => $val['Default'],
                    'primary' => (strtolower($val['Key']) == 'pri'),
                    'autoinc' => (strtolower($val['Extra']) == 'auto_increment'),
                );
            }
        }

        return $info;
    }

    /**
     * 取得数据库的表信息
     */
    public function getTables($dbName = '')
    {
        if (!empty($dbName)) {
            $sql = 'SHOW TABLES FROM '.$dbName;
        } else {
            $sql = 'SHOW TABLES ';
        }
        $result = $this->query($sql);
        $info = array();
        foreach ($result as $key => $val) {
            $info[$key] = current($val);
        }

        return $info;
    }

    /**
     * 替换记录
     * @param  mixed $data    数据
     * @param  array $options 参数表达式
     * @return false | integer
     */
    public function replace($data, $options = array())
    {
        foreach ($data as $key => $val) {
            $value = $this->parseValue($val);
            if (is_scalar($value)) { // 过滤非标量数据
                $values[] = $value;
                $fields[] = $this->addSpecialChar($key);
            }
        }
        $sql = 'REPLACE INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')';

        return $this->execute($sql);
    }

    /**
     +----------------------------------------------------------
     * 插入记录
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param mixed $datas   数据
     * @param array $options 参数表达式
     +----------------------------------------------------------
     * @return false | integer
     +----------------------------------------------------------
     */
    public function insertAll($datas, $options = array())
    {
        if (!is_array($datas[0])) {
            return false;
        }
        $fields = array_keys($datas[0]);
        array_walk($fields, array($this, 'addSpecialChar'));
        $values = array();
        foreach ($datas as $data) {
            $value = array();
            foreach ($data as $key => $val) {
                $val = $this->parseValue($val);
                if (is_scalar($val)) { // 过滤非标量数据
                    $value[] = $val;
                }
            }
            $values[] = '('.implode(',', $value).')';
        }
        $sql = 'INSERT INTO '.$this->parseTable($options['table']).' ('.implode(',', $fields).') VALUES '.implode(',', $values);

        return $this->execute($sql);
    }

    /**
     * 关闭数据库
     * @throws ThinkExecption
     */
    public function close()
    {
        // if (!empty($this->queryID)) {
        //     mysql_free_result($this->queryID);
        // }
        // if ($this->_linkID && !mysql_close($this->_linkID)) {
        //     throw_exception($this->error());
        // }
        $this->_linkID = 0;
    }

    /**
     * 数据库错误信息
     * 并显示当前的SQL语句
     * @return string
     */
    public function error()
    {
        // $this->error = mysql_error($this->_linkID);
        if ($this->debug && '' != $this->queryStr) {
            $this->error .= "\n [ SQL语句 ] : ".$this->queryStr;
        }

        return $this->error;
    }

    /**
     * SQL指令安全过滤
     * @param  string $str SQL字符串
     * @return string
     */
    public function escape_string($str)
    {
        // $res = @mysql_escape_string($str);
        // $res === false && $res = $str;
        // return $res;
        return $str;
    }

    /**
     * 析构方法
     */
    public function __destruct()
    {
        // 关闭连接
        $this->close();
    }
}//类定义结束
