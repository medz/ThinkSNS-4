<?php
/**
 * ThinkPHP系统基类
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id$
 */
class Think
{
    private static $_instance = array();

    /**
     * 自动变量设置
     * @param $name 属性名称
     * @param $value  属性值
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    /**
     * 自动变量获取
     * @param $name 属性名称
     * @return mixed
     */
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    /**
     * 系统自动加载ThinkPHP类库
     * 并且支持配置自动加载路径
     * @param string $classname 对象类名
     */
    public static function autoload($classname)
    {
        // 检查是否存在别名定义
        if (tsload($classname)) {
            return ;
        }
        // 自动加载当前项目的Actioon类和Model类
        if (substr($classname, -5) == 'Model') {
            tsload(APP_MODEL_PATH.'/'.$classname.'.class.php');
        } elseif (substr($classname, -6) == 'Action') {
            tsload(APP_ACTION_PATH.'/'.$classname.'.class.php');
        } else {
            // 根据自动加载路径设置进行尝试搜索
            if (tsconfig('APP_AUTOLOAD_PATH')) {
                $paths = explode(',', tsconfig('APP_AUTOLOAD_PATH'));
                foreach ($paths as $path) {
                    if (tsload($path.'/'.$classname.'.class.php')) {
                        // 如果加载类成功则返回
                        return ;
                    }
                }
            }
        }

        return ;
    }

    /**
     * 取得对象实例 支持调用类的静态方法
     * @param  string $class  对象类名
     * @param  string $method 类的静态方法名
     * @return object
     */
    public static function instance($class, $method = '')
    {
        $identify = $class.$method;
        if (!isset(self::$_instance[$identify])) {
            if (class_exists($class)) {
                $o = new $class();
                if (!empty($method) && method_exists($o, $method)) {
                    self::$_instance[$identify] = call_user_func_array(array(&$o, $method));
                } else {
                    self::$_instance[$identify] = $o;
                }
            } else {
                halt(L('_CLASS_NOT_EXIST_').' = '.$class.' = '.$method);
            }
        }

        return self::$_instance[$identify];
    }
}//类定义结束
