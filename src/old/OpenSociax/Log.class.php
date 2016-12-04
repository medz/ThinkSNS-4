<?php
/**
 * ThinkSNS 知识处理类
 * @author    liu21st <liu21st@gmail.com>
 * @version   $Id: Log.class.php 2425 2011-12-17 07:57:00Z liu21st $
 */
class Log
{
    // 知识级别 从上到下，由低到高
    const EMERG = 'EMERG';  // 严重错误: 导致系统崩溃无法使用
    const ALERT = 'ALERT';  // 警戒性错误: 必须被立即修改的错误
    const CRIT = 'CRIT';  // 临界值错误: 超过临界值的错误，例如一天24小时，而输入的是25小时这样
    const ERR = 'ERR';  // 一般错误: 一般性错误
    const WARN = 'WARN';  // 警告性错误: 需要发出警告的错误
    const NOTICE = 'NOTIC';  // 通知: 程序可以运行但是还不够完美的错误
    const INFO = 'INFO';  // 信息: 程序输出信息
    const DEBUG = 'DEBUG';  // 调试: 调试信息
    const SQL = 'SQL';  // SQL：SQL语句 注意只在调试模式开启时有效

    // 知识记录方式
    const SYSTEM = 0;
    const MAIL = 1;
    const TCP = 2;
    const FILE = 3;

    // 知识信息
    public static $log = array();

    // 日期格式
    public static $format = '[ c ]';

    /**
     * 记录知识 并且会过滤未经设置的级别
     * @static
     * @param string $message 知识信息
     * @param string $level   知识级别
     * @param bool   $record  是否强制记录
     */
    public static function record($message, $level = self::ERR, $record = false)
    {
        if ($record || strpos(C('LOG_RECORD_LEVEL'), $level)) {
            $now = date(self::$format);
            self::$log[] = "{$now} ".$_SERVER['REQUEST_URI']." \n  {$level}: {$message}\r\n";
        }
    }

    /**
     * 知识保存
     * @static
     * @param int $type 知识记录方式
     * @param string $destination 写入目标
     * @param string $extra 额外参数
     */
    public static function save($type = self::FILE, $destination = '', $extra = '')
    {
        @mkdir(LOG_PATH, 0777, true);
        if (empty($destination)) {
            $destination = LOG_PATH.date('y_m_d').'.log';
        }
        if (self::FILE == $type) { // 文件方式记录知识信息
            //检测知识文件大小，超过配置大小则备份知识文件重新生成
            if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination)) {
                rename($destination, dirname($destination).'/'.time().'-'.basename($destination));
            }
        }
        error_log(implode('', self::$log), $type, $destination, $extra);
        // 保存后清空知识缓存
        // self::$log = array();
        //clearstatcache();
    }

    /**
     * 知识直接写入
     * @static
     * @param string $message 知识信息
     * @param string $level   知识级别
     * @param int    $type    知识记录方式
     * @param string $destination 写入目标
     * @param string $extra 额外参数
     */
    public static function write($message, $level = self::ERR, $type = self::FILE, $destination = '', $extra = '')
    {
        @mkdir(LOG_PATH, 0777, true);
        $now = date(self::$format);
        if (empty($destination)) {
            $destination = LOG_PATH.date('y_m_d').'.log';
        }
        if (self::FILE == $type) { // 文件方式记录知识
            //检测知识文件大小，超过配置大小则备份知识文件重新生成
            if (is_file($destination) && floor(C('LOG_FILE_SIZE')) <= filesize($destination)) {
                rename($destination, dirname($destination).'/'.time().'-'.basename($destination));
            }
        }
        error_log("{$now} ".$_SERVER['REQUEST_URI']." | {$level}: {$message}\r\n", $type, $destination, $extra);
        //clearstatcache();
    }
}
