<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2012 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

defined('THINK_PATH') or exit();
/**
 * Memcache缓存驱动
 * @category   Extend
 * @package  Extend
 * @subpackage  Driver.Cache
 * @author    liu21st <liu21st@gmail.com>
 */
class CacheMemcache extends Cache
{
    /**
     * 架构函数
     * @param array $options 缓存参数
     */
    public function __construct($options = array())
    {
        if (!extension_loaded('memcache')) {
            throw_exception(L('_NOT_SUPPORT_').':memcache');
        }

        $hosts = array(); //服务器列表
        $servers = explode(',', C('MEMCACHE_HOST')); //支持多服务器配置
        foreach ($servers as $k => $host) {
            list($host, $port) = explode(':', $host, 2);
            $hosts[] = array(
                'host' => $host ? $host : '127.0.0.1',
                'port' => empty($port) ? 11211 : $port,
            );
        }
        if (empty($options)) {
            $options = array(
                'host' => $hosts[0]['host'],
                'port' => $hosts[0]['port'],
                'timeout' => C('DATA_CACHE_TIMEOUT') ? C('DATA_CACHE_TIMEOUT') : false,
                'persistent' => false,
                'servers' => $hosts,
            );
        }
        $this->options = $options;
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['prefix'] = isset($options['prefix']) ? $options['prefix'] : C('DATA_CACHE_PREFIX');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $func = $options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new Memcache;
        //Memcache集群支持
        if (isset($hosts[1])) {
            foreach ($hosts as $host) {
                $this->handler->addServer($host['host'], $host['port'], $options['persistent']);
            }
        } else {
            $host = $hosts[0];
            $options['timeout'] === false ?
                $this->handler->$func($host['host'], $host['port']) :
                $this->handler->$func($host['host'], $host['port'], $options['timeout']);
        }
        //设置大数据压缩
        $this->handler->setCompressThreshold(8 * 1024);
    }

    /**
     * 读取缓存
     * @param  string $name 缓存变量名
     * @return mixed
     */
    public function get($name)
    {
        N('cache_read', 1);

        return $this->handler->get($this->options['prefix'].$name);
    }

    /**
     * 批量读取缓存
     * @param  string $prefix 缓存前缀
     * @return mixed
     */
    public function getMulti($prefix, $key)
    {
        N('cache_read', 1);
        foreach ($key as $k => $v) {
            $namelist[] = $this->options['prefix'].$prefix.$v;
        }

        $result = $this->handler->get($namelist);

        foreach ($result as $k => $v) {
            $k = str_replace($this->options['prefix'].$prefix, '', $k);
            $data[ $k ] = $v;
        }
        unset($result);

        return $data;
    }

    /**
     * 写入缓存
     * @param  string $name   缓存变量名
     * @param  mixed  $value  存储数据
     * @param  int    $expire 有效时间（秒）
     * @return boolen
     */
    public function set($name, $value, $expire = null)
    {
        N('cache_write', 1);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $name = $this->options['prefix'].$name;
        if ($this->handler->set($name, $value, 0, $expire)) {
            if ($this->options['length'] > 0) {
                // 记录缓存队列
                $this->queue($name);
            }

            return true;
        }

        return false;
    }

    /**
     * 删除缓存
     * @param  string $name 缓存变量名
     * @return boolen
     */
    public function rm($name, $ttl = false)
    {
        $name = $this->options['prefix'].$name;

        return $ttl === false ?
            $this->handler->delete($name) :
            $this->handler->delete($name, $ttl);
    }

    /**
     * 清除缓存
     * @return boolen
     */
    public function clear()
    {
        return $this->handler->flush();
    }
}
