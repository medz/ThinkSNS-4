<?php
/**
 * Key-Value存储引擎模型 - 数据对象模型
 * Key-value存储引擎，用MySQL模拟memcache等key-value数据库写法
 * 以后可以切换到其它成熟数据库或amazon云计算平台
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
class XconfigModel extends Model
{
    protected $tableName = 'system_config';
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
    public function pageKey_lput($listName = '', $listData = array())
    {
        // 初始化list_name
        $listName = $this->_strip_key($listName);
        $result = false;
        // 格式化数据
        if (is_array($listData)) {
            $insert_sql .= 'REPLACE INTO __TABLE__ (`list`,`key`,`value`,`mtime`) VALUES ';
            foreach ($listData as $key => $data) {
                $insert_sql .= " ('$listName','$key','".serialize($data)."','".date('Y-m-d H:i:s')."') ,";
            }
            $insert_sql = rtrim($insert_sql, ',');
            // 插入数据列表
            $result = $this->execute($insert_sql);
        }

        $cache_id = '_system_config_lget_'.$listName;

        F($cache_id, null);    //删除缓存，不要使用主动创建发方式、因为listData不一定是listName的全部值

        return $result;
    }

    /**
     * 读取数据list:key
     * @param  string $key 要获取的某个参数list:key；如果没有:则认为，只有list没有key
     * @return string 相应的list中的key值数据
     */
    public function pagekey_get($key)
    {
        $key = $this->_strip_key($key);

        $keys = explode(':', $key);
        static $_res = array();
        if (isset($_res[$key])) {
            return $_res[$key];
        }
        $list = $this->pagekey_lget($keys[0]);

        return $list ? $list[$keys[1]] : '';
    }

    /**
     * 读取参数列表
     * @param  string $list_name 参数列表list
     * @param  bool   $nostatic  是否不使用静态缓存，默认为false
     * @return array  参数列表
     */
    public function pagekey_lget($list_name = '', $nostatic = false)
    {
        $list_name = $this->_strip_key($list_name);

        static $_res = array();
        if (isset($_res[$list_name]) && !$nostatic) {
            return $_res[$list_name];
        }

        $cache_id = '_system_config_lget_'.$list_name;

        if (($data = F($cache_id)) === false || $data == '' || empty($data)) {
            $data = array();
            $map['`list`'] = $list_name;

            $result = D('system_config')->order('id ASC')->where($map)->findAll();
            if ($result) {
                foreach ($result as $v) {
                    $data[$v['key']] = unserialize($v['value']);
                }
            }
            F($cache_id, $data);
        }
        //dump($data);exit;
        $_res[$list_name] = $data;

        return $_res[$list_name];
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
            return preg_replace('/([^0-9a-zA-Z\_\:])/', '', $key);
        }
    }
}
