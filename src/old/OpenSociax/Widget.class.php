<?php
/**
 * ThinkSNS Widget类 抽象类
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>,liu21st <liu21st@gmail.com>
 * @version TS v4 only
 */
abstract class Widget
{
    // 使用的模板引擎 每个Widget可以单独配置不受系统影响
    protected $template = '';
    protected $attr = array();
    protected $cacheChecked = false;
    protected $mid;
    protected $uid;
    protected $user;
    protected $site;

    /**
     * 渲染输出 render方法是Widget唯一的接口
     * 使用字符串返回 不能有任何输出
     * @param  mixed  $data 要渲染的数据
     * @return string
     */
    abstract public function render($data);

    /**
     * 架构函数,处理核心变量
     * 使用字符串返回 不能有任何输出
     */
    public function __construct()
    {

        //当前登录者uid
        $GLOBALS['ts']['mid'] = $this->mid = intval($_SESSION['mid']);

        //当前访问对象的uid
        $GLOBALS['ts']['uid'] = $this->uid = intval($_REQUEST['uid'] == 0 ? $this->mid : $_REQUEST['uid']);

        // 赋值当前访问者用户
        $GLOBALS['ts']['user'] = $this->user = model('User')->getUserInfo($this->mid);
        if ($this->mid != $this->uid) {
            $GLOBALS['ts']['_user'] = model('User')->getUserInfo($this->uid);
        } else {
            $GLOBALS['ts']['_user'] = $GLOBALS['ts']['user'];
        }

        //当前用户的所有已添加的应用
        $GLOBALS['ts']['_userApp'] = $userApp = model('UserApp')->getUserApp($this->uid);
        //当前用户的统计数据
        $GLOBALS['ts']['_userData'] = $userData = model('UserData')->getUserData($this->uid);

        $this->site = D('Xdata')->get('admin_Config:site');
        $this->site['logo'] = getSiteLogo($this->site['site_logo']);
        $GLOBALS['ts']['site'] = $this->site;

        //语言包判断
        if (TRUE_APPNAME != 'public' && APP_NAME != TRUE_APPNAME) {
            addLang(TRUE_APPNAME);
        }
        Addons::hook('core_filter_init_widget');
    }

    /**
     * 渲染模板输出 供render方法内部调用
     * @param  string $templateFile 模板文件
     * @param  mixed  $var          模板变量
     * @param  string $charset      模板编码
     * @return string
     */
    protected function renderFile($templateFile = '', $var = '', $charset = 'utf-8')
    {
        $var['ts'] = $GLOBALS['ts'];
        if (! file_exists_case($templateFile)) {
            // 自动定位模板文件
            // $name = substr ( get_class ( $this ), 0, - 6 );
            // $filename = empty ( $templateFile ) ? $name : $templateFile;
            // $templateFile =   'widget/' . $name . '/' . $filename . C ( 'TMPL_TEMPLATE_SUFFIX' );
            // if (! file_exists_case ( $templateFile ))
            throw_exception(L('_WIDGET_TEMPLATE_NOT_EXIST_').'['.$templateFile.']');
        }

        $template = $this->template ? $this->template : strtolower(C('TMPL_ENGINE_TYPE') ? C('TMPL_ENGINE_TYPE') : 'php');

        $content = fetch($templateFile, $var, $charset);

        return $content;
    }
}
