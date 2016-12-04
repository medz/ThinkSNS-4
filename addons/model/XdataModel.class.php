<?php
/**
 * Key-Value存储引擎模型 - 数据对象模型
 * Key-value存储引擎，用MySQL模拟memcache等key-value数据库写法
 * 以后可以切换到其它成熟数据库或amazon云计算平台
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
class XdataModel extends Model
{
    protected $tableName = 'system_data';
    protected $fields = array(0 => 'id', 1 => 'uid', 2 => 'list', 3 => 'key', 4 => 'value', 5 => 'mtime', '_autoinc' => true, '_pk' => 'id');

    protected $list_name = 'global';            // 默认列表名

    //键值白名单，主要用于获取和设置配置文件某个
    protected $whiteList = array('site' => '');

    /**
     * 写入参数列表
     * @param string $listName 参数列表list
     * @param  array  $listData 存入的数据，形式为key=>value
     * @return bool   是否写入成功
     */
    public function lput($listName = '', $listData = array())
    {
        // 初始化list_name
        $listName = $this->_strip_key($listName);
        $result = false;
        // 格式化数据
        if (is_array($listData)) {
            $insert_sql .= 'REPLACE INTO __TABLE__ (`list`,`key`,`value`,`mtime`) VALUES ';
            foreach ($listData as $key => $data) {
                $insert_sql .= " ('$listName','$key','".addslashes(serialize($data))."','".date('Y-m-d H:i:s')."') ,";
            }
            $insert_sql = rtrim($insert_sql, ',');
            // 插入数据列表
            $result = $this->execute($insert_sql);
        }

        $cache_id = '_xdata_lget_'.$listName;

        F($cache_id, null);    //删除缓存，不要使用主动创建发方式、因为listData不一定是listName的全部值

        return $result;
    }

    /**
     * 读取参数列表
     * @param  string $list_name 参数列表list
     * @param  bool   $nostatic  是否不使用静态缓存，默认为false
     * @return array  参数列表
     */
    public function lget($list_name = '', $nostatic = false)
    {
        $list_name = $this->_strip_key($list_name);

        static $_res = array();
        if (isset($_res[$list_name]) && !$nostatic) {
            return $_res[$list_name];
        }

        $cache_id = '_xdata_lget_'.$list_name;

        if (($data = F($cache_id)) === false || $data == '') {
            $data = array();
            $map['`list`'] = $list_name;
            $result = $this->order('id ASC')->where($map)->findAll();

            if ($result) {
                foreach ($result as $v) {
                    $data[$v['key']] = unserialize($v['value']);
                }
            }
            F($cache_id, $data);
        }

        $_res[$list_name] = $data;

        return $_res[$list_name];
    }

    /**
     * 写入单个数据
     * @param string $key   要存储的参数list:key
     * @param string $value 要存储的参数的值
     * @param  bool   $replace false为插入新参数，ture为更新已有参数，默认为true
     * @return bool   是否写入成功
     */
    public function put($key, $value = '', $replace = true)
    {
        $key = $this->_strip_key($key);
        $keys = explode(':', $key);
        $data = addslashes(serialize($value));

        if ($replace) {
            $insert_sql = 'REPLACE INTO __TABLE__ ';
        } else {
            $insert_sql = 'INSERT INTO __TABLE__ ';
        }

        $insert_sql .= "(`list`,`key`,`value`,`mtime`) VALUES ('$keys[0]','$keys[1]','$data','".date('Y-m-d H:i:s')."')";
        $result = $this->execute($insert_sql);

        $cache_id = '_xdata_lget_'.$keys[0];

        F($cache_id, null);

        return $result;
    }

    /**
     * 读取数据list:key
     * @param  string $key 要获取的某个参数list:key；如果没有:则认为，只有list没有key
     * @return string 相应的list中的key值数据
     */
    public function get($key)
    {
        $key = $this->_strip_key($key);
        $keys = explode(':', $key);
        static $_res = array();
        if (isset($_res[$key])) {
            return $_res[$key];
        }

        $list = $this->lget($keys[0]);

        return $list ? $list[$keys[1]] : '';
    }

    /**
     * 传入的key参数为直接要获取的的key值
     * @param  string $key        在配置中的key值 [必须]
     * @param  string $systemKey  在system_data表中的key值 [必须]
     * @param  string $systemList 在system_data表中的list值 默认为Config
     * @return string 获取key对应的值
     */
    public function getConfig($key, $systemKey, $systemList = 'admin_Config')
    {
        if (empty($systemKey)) {
            return false;
        }
        $_key = $systemList.':'.$systemKey;
        $data = $this->get($_key);

        return $data[$key];
    }

    /**
     * 存储单个数据，将原来的save修改为saveKey
     * @param  string $key   要存储的参数list:key
     * @param  string $value 要存储的参数的值
     * @return bool   是否存储成功
     */
    public function saveKey($key, $value = '')
    {
        $result = $this->put($key, $value, true);

        return $result;
    }

    /**
     * 批量读取数据，非必要
     * @param  string       $listName 参数列表list
     * @param  array|object $keys     参数键key
     * @return array        通过list与key批量获取的数据
     */
    public function getAll($listName, $keys)
    {
        // 用于获取list下所有数据
        if ($key) {
            $keysArray = $this->_parse_keys($keys);
            $map['`key`'] = array('IN', $keysArray);
        }

        $map['`list`'] = $listName;
        $result = $this->where($map)->findAll();

        if (!$result) {
            return false;
        } else {
            foreach ($result as $v) {
                $datas[$v['list']][$v['key']] = unserialize($v['value']);
            }
        }

        return $datas;
    }

    /**
     * 解析过滤输入
     * @param  string|object|array $input 输入的数据
     * @return array               解析过滤后的输入数据
     */
    protected function _parse_keys($input = '')
    {
        $output = '';
        if (is_array($input) || is_object($input)) {
            foreach ($input as $v) {
                $output[] = $this->_strip_key($v);
            }
        } elseif (is_string($input)) {
            $output[] = $this->_strip_key($input);
        } else {
            // 异常处理
        }

        return $output;
    }

    /**
     * 过滤key值
     * @param  string $key 只允许格式，数字字母下划线，list:key不允许出现html代码和这些符号 ' " & * % ^ $ ? ->
     * @return string 过滤后的key值
     */
    protected function _strip_key($key = '')
    {
        if ($key == '') {
            return $this->list_name;
        } else {
            return preg_replace('/([^0-9a-zA-Z\_\:\/\*])/', '', $key);
        }
    }
}
