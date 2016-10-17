<?php
/**
 * 缓存模型 - 业务逻辑模型
 * @example
 * setType($type)                       主动设置缓存类型
 * set($key, $value, $expire = null)    设置缓存key=>value，expire表示有效时间，null表示永久
 * get($key, $mutex = false)            获取缓存数据，支持mutex模式
 * getList($prefix, $key)               批量获取指定前缀下的多个key值的缓存
 * rm($key)                             删除缓存
 * @author jason <yangjs17@yeah.net>
 * @version TS3.0
 */
// 加载缓存操作类
tsload(CORE_LIB_PATH.'/Cache.class.php');
class CacheModel
{
    //public static $_cacheHash = array();  // 缓存的静态变量
    protected $handler;                        // 操作句柄
    protected $type = 'FILE';                // 缓存类型，默认为文件缓存

    /**
     * 初始化缓存模型对象，缓存类型
     */
    public function __construct($type = '')
    {
        if (!C('DATA_CACHE_TYPE_ONLY')) {
            $type = model('Xdata')->get('cacheconfig:cachetype');
            if ($type == 'Memcache') {
                $servers = model('Xdata')->get('cacheconfig:cachesetting');
                C('MEMCACHE_HOST', $servers);
            }
        }
        !$type && $type = $this->type;
        $this->type = strtoupper($type);
        $this->handler = Cache::getInstance($type);
    }

    /**
     * 链式设置缓存类型
     * @param  string $type 缓存类型
     * @return object 缓存模型对象
     */
    public function setType($type)
    {
        $this->type = strtoupper($type);
        $this->handler = Cache::getInstance($type);

        return $this;
    }

    /**
     * 设置缓存
     * @param string $key   缓存Key值
     * @param mix    $value 缓存Value值
     * @param bool 是否设置成功
     */
    public function set($key, $value, $expire = null)
    {
        // 接管过期时间设置，-1表示永远不过期
        $value = array(
                    'CacheData' => $value,
                    'CacheMtime' => time(),
                    'CacheExpire' => is_null($expire) ? '-1' : $expire,
                );
        $key = C('DATA_CACHE_PREFIX').$key;

        return $this->handler->set($key, $value);
    }

    /**
     * 获取缓存操作，支持mutex模式
     * mutex使用注意
     * 1.设置缓存（set）时，需要设置有效时间
     * 2.获取缓存（get）时，需要主动创建缓存
     * @param  string $_key  缓存Key值
     * @param  bool   $mutex 是否启用mutex模式，默认为不启用
     * @return mix    缓存数据
     */
    public function get($_key, $mutex = false)
    {
        $key = C('DATA_CACHE_PREFIX').$_key;
        // 静态缓存
/*      if(isset(self::$_cacheHash[$key])){
            return self::$_cacheHash[$key];
        }*/
        $sc = static_cache('cache_'.$key);
        if (!empty($sc)) {
            return $sc;
        }
        // 获取缓存数据
        $data = $this->handler->get($key);

        // 未设置缓存
        if (!$data) {
            return false;
        }
            // mutex模式未开启
        if (! $mutex) {
            if ($data ['CacheExpire'] < 0 || ($data ['CacheMtime'] + $data ['CacheExpire'] > time())) {
                return $this->_returnData($data ['CacheData'], $key);
            } else {
                // 过期，清理原始缓存
                $this->rm($_key);

                return false;
            }
        }
        // mutex模式开启
        if (($data['CacheMtime'] + $data['CacheExpire']) <= time()) {
            //正常情况，有过期时间设置的mutex模式
            if ($data['CacheExpire'] > 0) {
                $data['CacheMtime'] = time();
                $this->handler->set($key, $data);
                // 返回false，让调用程序去主动更新缓存
                static_cache('cache_'.$key, false);

                return false;
            } else {
                //异常情况，没有设置有效期的时候，永久有效的时候
                if (!$data['CacheData']) {
                    $this->rm($_key);

                    return false;
                }

                return $this->_returnData($data['CacheData'], $key);
            }
        } else {
            return $this->_returnData($data['CacheData'], $key);
        }
    }

    /**
     * 删除缓存
     * @param  string $_key 缓存Key值
     * @return bool   是否删除成功
     */
    public function rm($_key)
    {
        $key = C('DATA_CACHE_PREFIX').$_key;
        static_cache($key, false);

        return $this->handler->rm($key);
    }

    /**
     * 清除缓存
     * @return boolen
     */
    public function clear()
    {
        return $this->handler->clear();
    }

    /**
     * 缓存写入次数
     <<<<<<< HEAD
     * @return 获取缓存写??次数
     =======
     * @return 获取缓存写入次数
     >>>>>>> master
     */
    public function W()
    {
        return $this->handler->W();
    }

    /**
     * 缓存读取次数
     * @return 获取缓存的读取次数
     */
    public function Q()
    {
        return $this->handler->Q();
    }

    /**
     * 根据某个前缀，批量获取多个缓存
     * @param  string $prefix 缓存前缀
     * @param  string $key    缓存Key值
     * @return mix    缓存数据
     */
    public function getList($prefix, $key)
    {
        if ($this->type == 'MEMCACHE') {
            // Memcache有批量获取缓存的接口
            $_data = $this->handler->getMulti($prefix, $key);
            foreach ($_data as $k => $d) {
                $data[$k] = $this->_returnData($d['CacheData'], $key);
            }
        } else {
            foreach ($key as $k) {
                $_k = $prefix.$k;
                $data[$k] = $this->get($_k);
            }
        }

        return $data;
    }

    /**
     * 返回缓存数据操作，方法中，将数据缓存到静态缓存中
     * @param  mix    $data 缓存数据
     * @param  string $key  缓存Key值
     * @return mix    缓存数据
     */
    private function _returnData($data, $key)
    {
        // TODO:可以在此对空值进行处理判断
        static_cache('cache_'.$key, $data);

        return $data;
    }
}
