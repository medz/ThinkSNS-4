<?php
/**
 * 插件机制接口
 * @author SamPeng <penglingjun@zhishisoft.com>
 * @version TS v4
 */
interface AddonsInterface
{
    /**
     * 告之系统，该插件使用了哪些hooks以及排序等信息
     * @return array()
     */
    public function getHooksInfo();
    /**
     * 插件初始化时需要的数据信息。所以就不需要写类的构造函数
     * Enter description here ...
     */
    public function start();
    /**
     * 该插件的基本信息
     * 这个方法不需要用户实现，将在下一层抽象中实现。
     * 用户需要填写几个基本信息作为该插件的属性即可
     */
    public function getAddonInfo();

    /**
     * setUp
     * 启动插件时的接口
     */
    public function install();

    /**
     * setDown
     * 卸载插件时的接口;
     */
    public function uninstall();

    /**
     * 显示不同的管理面板或表单等操作的处理受理接口。默认$page为false.也就是只显示第一个管理面板页面
     */
    public function adminMenu();
}

/**
 * 插件机制抽象类
 * @author SamPeng <penglingjun@zhishisoft.com>
 * @version TS v4
 */
abstract class AbstractAddons implements AddonsInterface
{
    protected $version;         // 插件版本号
    protected $author;          // 作者
    protected $site;            // 网站
    protected $info;            // 插件描述信息
    protected $pluginName;      // 插件名字
    protected $path;            // 插件路径
    protected $url;             // 插件URL
    protected $tVar;            // 模板变量
    protected $mid;             // 登录用户ID
    protected $model;           // 插件数据模型对象

    /**
     * 初始化相关信息
     */
    public function __construct()
    {
        $this->mid = @intval($_SESSION['mid']);
        $this->model = model('AddonData');
        $this->tVar = array();
        $this->start();
    }

    /**
     * 获取URL地址
     * @return string URL地址
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * 设置URL地址
     * @param string $url URL地址
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * 获取路径地址
     * @return string 路径地址
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param 设置路径地址
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    abstract public function getHooksList($name);

    /**
     * 获取插件信息
     * @return array 插件信息
     */
    public function getAddonInfo()
    {
        $data['version'] = $this->version;
        $data['author'] = $this->author;
        $data['site'] = $this->site;
        $data['info'] = $this->info;
        $data['pluginName'] = $this->pluginName;
        $data['tsVersion'] = $this->tsVersion;
        $data['is_weixin'] = intval($this->is_weixin);

        return $data;
    }

    /**
     * 将数据渲染到HTML页面，设置模板变量的值
     * @param string $name  Key值
     * @param string $value Value值
     */
    protected function assign($name, $value = '')
    {
        $this->tVar[$name] = $value;
    }

    /**
     * 获取指定模板变量的值
     * @param  string $name Key值
     * @return mixed  指定模板变量的值
     */
    protected function get($name)
    {
        $data = isset($this->tVar[$name]) ? $this->tVar[$name] : false;

        return $data;
    }

    /**
     * 渲染HTML页面
     * @param string $templateFile 模板文件路径
     * @param string $charset      字符集，默认为UTF8
     * @param  string $contentType  内容类型，默认为text/html
     * @return string HTML页面数据
     */
    public function fetch($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        $templateFile = realpath($this->path.DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR.$templateFile.'.html');

        return fetch($templateFile, $this->tVar, $charset, $contentType, false);
    }

    /**
     * 显示指定HTML页面
     * @param string $templateFile 模板文件路径
     * @param string $charset      字符集，默认为UTF8
     * @param  string $contentType  内容类型，默认为text/html
     * @return string HTML页面数据
     */
    public function display($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        echo $this->fetch($templateFile, $charset, $contentType);
    }

    /**
     * 错误提示方法
     * @param string $message 提示信息
     */
    protected function error($message)
    {
        $message = $message ? $message : '操作失败';
        $this->_dispatch_jump($message, 0);
    }

    /**
     * 成功提示方法
     * @param string $message 提示信息
     */
    protected function success($message)
    {
        $message = $message ? $message : '操作成功';
        $this->_dispatch_jump($message, 1);
    }

    /**
     * 跳转操作
     * @param string $message 提示信息
     * @param int    $status  状态。1表示成功，0表示失败
     */
    private function _dispatch_jump($message, $status = 1)
    {
        // 跳转时不展示广告
        unset($GLOBALS['ts']['ad']);

        // 提示标题
        $this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        $this->assign('status', $status);   // 状态
        $this->assign('message', $message); // 提示信息
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON', false);
        if ($status) { //发送成功信息
            // 成功操作后默认停留1秒
            $this->assign('waitSecond', '1');
            // 默认操作成功自动返回操作前页面
            if (!$this->get('jumpUrl')) {
                $this->assign('jumpUrl', $_SERVER['HTTP_REFERER']);
            }

            echo $this->fetch(THEME_PATH.'/success.html');
        } else {
            //发生错误时候默认停留3秒
            $this->assign('waitSecond', '5');
            // 默认发生错误的话自动返回上页
            if (!$this->get('jumpUrl')) {
                $this->assign('jumpUrl', 'javascript:history.back(-1);');
            }

            echo $this->fetch(THEME_PATH.'/success.html');
        }
        if (C('LOG_RECORD')) {
            Log::save();
        }
        // 中止执行  避免出错后继续执行
        exit ;
    }
}
