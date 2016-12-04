<?php
/**
 * 钩子抽象类
 * @author SamPeng <penglingjun@zhishisoft.com>
 * @version TS v4
 */
abstract class Hooks
{
    protected $mid;                // 登录用户ID
    protected $model;              // 插件数据模型对象
    protected $tVar;               // 模板变量
    protected $path;               // 插件路径
    protected $htmlPath;           // 插件HTML路径

    /**
     * 初始化相关信息
     */
    public function __construct()
    {
        $this->mid = $_SESSION['mid'];
        $this->model = model('AddonData');
        $this->tVar = array();
    }

    /**
     * 设置该插件的路径，不能进行重写
     * @param string $path 路径地址
     * @param bool   $html 是否为HTML路径，默认为false
     */
    final public function setPath($path, $html = false)
    {
        if ($html) {
            $this->htmlPath = $path;
        } else {
            $this->path = $path;
        }
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
     * 渲染HTML页面
     * @param string $templateFile 模板文件路径
     * @param string $charset      字符集，默认为UTF8
     * @param  string $contentType  内容类型，默认为text/html
     * @return string HTML页面数据
     */
    public function fetch($templateFile = '', $charset = 'utf-8', $contentType = 'text/html')
    {
        if (!is_file($templateFile)) {
            $templateFile = realpath($this->path.DIRECTORY_SEPARATOR.'html'.DIRECTORY_SEPARATOR.$templateFile.'.html');
        }

        // 获取当前Js语言包
        $this->langJsList = setLangJavsScript();
        $this->assign('langJsList', $this->langJsList);

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
            //if(!$this->get('jumpUrl'))
                $this->assign('jumpUrl', $_SERVER['HTTP_REFERER']);

            echo $this->fetch(THEME_PATH.'/success.html');
        } else {
            //发生错误时候默认停留3秒
            $this->assign('waitSecond', '5');
            // 默认发生错误的话自动返回上页
            //if(!$this->get('jumpUrl'))
                $this->assign('jumpUrl', 'javascript:history.back(-1);');

            echo $this->fetch(THEME_PATH.'/success.html');
        }
        if (C('LOG_RECORD')) {
            Log::save();
        }
        // 中止执行  避免出错后继续执行
        exit ;
    }

    /**
     * 获取插件目录下的Model模型文件
     * @param  string $name  Model名称
     * @param  string $class 类名后缀，默认为Model
     * @return object 返回一个模型对象
     */
    protected function model($name, $class = 'Model')
    {
        $className = ucfirst($name).$class;
        tsload($this->path.DIRECTORY_SEPARATOR.$className.'.class.php');

        return new $className();
    }
}
