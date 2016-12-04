<?php

class CacheBae extends Cache
{
    public static $_cache;
    private $_handler;

    /**
     +----------------------------------------------------------
     * 架构函数
     +----------------------------------------------------------
     +----------------------------------------------------------
     */
    public function __construct($options = '')
    {
        if (!empty($options)) {
            $this->options = $options;
        }
        $this->options['expire'] = isset($options['expire']) ? $options['expire'] : C('DATA_CACHE_TIME');
        $this->options['length'] = isset($options['length']) ? $options['length'] : 0;
        $this->options['queque'] = 'bae';
        $this->init();
    }

    /**
     +----------------------------------------------------------
     * 初始化检查
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    private function init()
    {
        $this->_handler = new BaeMemcache();
        $this->connected = true;
    }

    /**
     +----------------------------------------------------------
     * 是否连接
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    private function isConnected()
    {
        return $this->connected;
    }
    /**
     +----------------------------------------------------------
     * 读取缓存
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return mixed
     +----------------------------------------------------------
     */
    public function get($name)
    {
        N('cache_read', 1);
        $content = $this->_handler->get($name);
        if (false !== $content) {
            if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                $content = substr($content, 0, -1);  //remvoe \0 in the end
            }
            if (C('DATA_CACHE_CHECK')) {
                //开启数据校验
                $check = substr($content, 0, 32);
                $content = substr($content, 32);
                if ($check != md5($content)) {
                    //校验错误
                    return false;
                }
            }
            if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
                //启用数据压缩
                $content = gzuncompress($content);
            }
            $content = unserialize($content);

            return $content;
        } else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 写入缓存
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $name  缓存变量名
     * @param mixed  $value 存储数据
     * @param int    $expire 有效时间 0为永久
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function set($name, $value, $expire = null)
    {
        N('cache_write', 1);
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }
        $data = serialize($value);
        if (C('DATA_CACHE_COMPRESS') && function_exists('gzcompress')) {
            //数据压缩
        //    $data   =   gzcompress($data,3);
          $data = gzencode($data)."\0";
        }
        if (C('DATA_CACHE_CHECK')) {
            //开启数据校验
            $check = md5($data);
        } else {
            $check = '';
        }
        $data = $check.$data;
        $result = $this->_handler->set($name, $data, 0, intval($expire));
        if ($result) {
            if ($this->options['length'] > 0) {
                // 记录缓存队列
                $this->queue($name);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     +----------------------------------------------------------
     * 删除缓存
     +----------------------------------------------------------
     +----------------------------------------------------------
     * @param string $name 缓存变量名
     +----------------------------------------------------------
     * @return boolen
     +----------------------------------------------------------
     */
    public function rm($name)
    {
        return $this->_handler->delete($name);
    }
    public static function queueSet($name, $value)
    {
        $h = new BaeMemcache();
        if ($h->set($name, $value)) {
            self::$_cache = array($name => $value);
        }
    }
    public static function queueGet($name)
    {
        if (isset(self::$_cache[$name])) {
            return self::$_cache[$name];
        }
        $h = new BaeMemcache();
        $r = $h->get($name);
        if (false === $r) {
            return false;
        }
        self::$_cache[$name] = $r;

        return $r;
    }
}
