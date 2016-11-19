<?php
/**
 +------------------------------------------------------------------------------
 * ThinkPHP惯例配置文件
 * 该文件请不要修改，如果要覆盖惯例配置的值，可在项目配置文件中设定和惯例不符的配置项
 * 配置名称大小写任意，系统会统一转换成小写
 * 所有配置参数都可以在生效前动态改变
 +------------------------------------------------------------------------------
 * @category Think
 * @package  Common
 * @author   liu21st <liu21st@gmail.com>
 * @version  $Id$
 +------------------------------------------------------------------------------
 */
if (!defined('THINK_PATH')) {
    exit();
}

return array(

    /* 插件是否开启 */
    'APP_DEBUG' => false,      // 是否开启调试模式
    'GZIP_OPEN' => false,      // 开启Gzip模式
    'DEVELOP_MODE' => true,       // 开发模式，开启后台表单的配置
    'APP_PLUGIN_ON' => true,       // 是否开启插件机制
    'DEFAULT_APPS' => array('public', 'admin', 'channel', 'people', 'square'), //默认核心应用

    /* 项目设定 */
    'SITE_LOGO' => 'image/logo.png', //默认的站点logo
    'DEFAULT_GROUP_ID' => 3, //默认注册之后的用户组ID
    'APP_AUTOLOAD_PATH' => CORE_LIB_PATH.','.CORE_LIB_PATH.'/addons/,'.CORE_LIB_PATH.'/Taglib/,'.ADDON_PATH.'/model/',

    /* Cookie设置 */
    'COOKIE_EXPIRE' => '',        // Coodie有效期
    'COOKIE_DOMAIN' => '',            // Cookie有效域名
    'COOKIE_PATH' => '/',            // Cookie路径
    'COOKIE_PREFIX' => 'TS4_',        // Cookie前缀 避免冲突

    /* Session设置 */
    'SESSION_PREFIX' => 'TS4_',          // Session前缀 避免冲突
    'VAR_SESSION_ID' => '',              // session_id的值
    'SESSION_TYPE' => '',              // Session存储类型
    'SESSION_AUTO_START' => true,            // Session是否自动开启

    /* 默认设定 */
    'DEFAULT_APP' => 'public',       // 默认项目名称，@表示当前项目
    'DEFAULT_MODULE' => 'Index',        // 默认模块名称
    'DEFAULT_ACTION' => 'index',        // 默认操作名称
    'DEFAULT_CHARSET' => 'utf-8',        // 默认输出编码
    'DEFAULT_TIMEZONE' => 'PRC',            // 默认时区
    'DEFAULT_LANG' => 'zh-cn',        // 默认语言
    'DEFAULT_LANG_TYPE' => array('zh-cn', 'zh-tw', 'en'), //默认支持的语言类型

    /* 数据库设置 */
    'DB_TYPE' => 'mysql',     // 数据库类型
    'DB_HOST' => 'localhost', // 服务器地址
    'DB_NAME' => '',          // 数据库名
    'DB_USER' => 'root',      // 用户名
    'DB_PWD' => '',          // 密码
    'DB_PORT' => 3306,        // 端口
    'DB_PREFIX' => 'ts_',    // 数据库表前缀
    'DB_SUFFIX' => '',          // 数据库表后缀
    'DB_FIELDTYPE_CHECK' => false,       // 是否进行字段类型检查
    'DB_FIELDS_CACHE' => true,        // 启用字段缓存
    'DB_CHARSET' => 'utf8',      // 数据库编码默认采用utf8
    'DB_DEPLOY_TYPE' => 0,             // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    'DB_RW_SEPARATE' => false,       // 数据库读写是否分离 主从式有效

    /* 数据缓存设置 */
    'DATA_CACHE_TIME' => null,            // 数据缓存有效期
    'DATA_CACHE_COMPRESS' => true,            // 数据缓存是否压缩缓存
    'DATA_CACHE_CHECK' => true,            // 数据缓存是否校验缓存
    'DATA_CACHE_TYPE' => 'File',            // 数据缓存类型,支持:File|Memcache
    'DATA_CACHE_PATH' => CORE_RUN_PATH.'/datacache',    // 缓存路径设置 (仅对File方式缓存有效)
    'DATA_CACHE_SUBDIR' => true,            // 使用子目录缓存 (自动根据缓存标识的哈希创建子目录)
    'DATA_PATH_LEVEL' => 2,                // 子目录缓存级别
    'DATA_CACHE_PREFIX' => 'TS4_',            //缓存前缀
    'F_CACHE_PATH' => CORE_RUN_PATH.'/filecache',    //F函数缓存的文件目录
    'MEMCACHE_HOST' => '127.0.0.1:11211',    //Memcache 默认的主机，支持简单的多机集群	如:	"127.0.0.1:11211,127.0.0.2:11211"

    /* 错误设置 */
    'ERROR_MESSAGE' => '您浏览的页面暂时发生了错误！请稍后再试～', //错误显示信息,非调试模式有效
    'ERROR_PAGE' => '',    // 错误定向页面

    /* 语言设置 */
    'LANG_SWITCH_ON' => true,   // 默认关闭多语言包功能
    'LANG_AUTO_DETECT' => true,   // 自动侦测语言 开启多语言功能后有效

    /* 知识设置 */
    'LOG_RECORD' => false,   // 默认不记录知识
    'LOG_FILE_SIZE' => 2097152,    // 知识文件大小限制
    'LOG_RECORD_LEVEL' => array('EMERG', 'ALERT', 'CRIT', 'ERR'), // 允许记录的知识级别

    /* SESSION设置 */
    'SESSION_AUTO_START' => true,    // 是否自动开启Session
    'SESSION_NAME' => 'PHPSESSION', // Session名称
    //'SESSION_PATH'        => '',      // Session保存路径
    //'SESSION_CALLBACK'    => '',      // Session 对象反序列化时候的回调函数

    /* 运行时间设置 */
    'SHOW_RUN_TIME' => false,   // 运行时间显示
    'SHOW_ADV_TIME' => false,   // 显示详细的运行时间
    'SHOW_DB_TIMES' => false,   // 显示数据库查询和写入次数
    'SHOW_CACHE_TIMES' => false,   // 显示缓存操作次数
    'SHOW_USE_MEM' => false,   // 显示内存开销
    'SHOW_PAGE_TRACE' => false,   // 显示页面Trace信息 由Trace文件定义和Action操作赋值
    'SHOW_ERROR_MSG' => true,    // 显示错误信息

    /* 模板引擎设置 */
    'TMPL_DENY_FUNC_LIST' => 'echo,exit',    // 模板引擎禁用函数
    'TMPL_PARSE_STRING' => '',          // 模板引擎要自动替换的字符串，必须是数组形式。
    'TMPL_L_DELIM' => '{',            // 模板引擎普通标签开始标记
    'TMPL_R_DELIM' => '}',            // 模板引擎普通标签结束标记
    'TMPL_VAR_IDENTIFY' => 'array',     // 模板变量识别。留空自动判断,参数为'obj'则表示对象
    'TMPL_STRIP_SPACE' => true,       // 是否去除模板文件里面的html空格与换行
    'TMPL_CACHE_ON' => true,        // 是否开启模板编译缓存,设为false则每次都会重新编译
    'TMPL_CACHE_TIME' => -1,         // 模板缓存有效期 -1 为永久，(以数字为值，单位:秒)
    'TMPL_ACTION_ERROR' => 'Public:success', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS' => 'Public:success', // 默认成功跳转对应的模板文件
    'TMPL_TRACE_FILE' => THINK_PATH.'/Tpl/PageTrace.tpl.php',     // 页面Trace的模板文件
    'TMPL_EXCEPTION_FILE' => THINK_PATH.'/Tpl/ThinkException.tpl.php', // 异常页面的模板文件
    'TMPL_FILE_DEPR' => '/', //模板文件MODULE_NAME与ACTION_NAME之间的分割符，只对项目分组部署有效
    'TMPL_CACHE_PATH' => CORE_RUN_PATH.'/tplcache/', //模板文件缓存路径

    /* Think模板引擎标签库相关设定 */
    'TAGLIB_BEGIN' => '<',  // 标签库标签开始标记
    'TAGLIB_END' => '>',  // 标签库标签结束标记
    'TAGLIB_LOAD' => true, // 是否使用内置标签库之外的其它标签库，默认自动检测
    'TAGLIB_BUILD_IN' => 'input,business', // 内置标签库名称(标签使用不必指定标签库名称),以逗号分隔
    'TAGLIB_PRE_LOAD' => 'html',   // 需要额外加载的标签库(须指定标签库名称)，多个以逗号分隔
    'TAG_NESTED_LEVEL' => 3,    // 标签嵌套级别
    'TAG_EXTEND_PARSE' => '',   // 指定对普通标签进行扩展定义和解析的函数名称。

    /* URL设置 */
    'URL_CASE_INSENSITIVE' => true,   // URL地址是否不区分大小写
    'URL_ROUTER_ON' => false,   // 是否开启URL路由
    'URL_DISPATCH_ON' => false,    // 是否启用Dispatcher
    'URL_HTML_SUFFIX' => '',        // URL伪静态后缀设置

    /* 系统变量名称设置 */
    'VAR_APP' => 'app',    // 默认应用获取变量
    'VAR_MODULE' => 'mod',    // 默认模块获取变量
    'VAR_ACTION' => 'act',    // 默认操作获取变量
    'VAR_ROUTER' => 'r',    // 默认路由获取变量
    'VAR_PAGE' => 'p',    // 默认分页跳转变量
    'VAR_TEMPLATE' => 't',    // 默认模板切换变量
    'VAR_LANGUAGE' => 'l',    // 默认语言切换变量
    'VAR_AJAX_SUBMIT' => 'ajax',    // 默认的AJAX提交变量
    'VAR_PATHINFO' => 's',    // PATHINFO 兼容模式获取变量
);
