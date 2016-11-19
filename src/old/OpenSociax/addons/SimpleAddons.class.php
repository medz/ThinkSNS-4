<?php
/**
 * 标准插件抽象。该插件具备插件的标准行为。
 * 获取信息，以及该插件拥有的管理操作
 * @author sampeng
 *
 */
tsload(CORE_LIB_PATH.'/addons/AbstractAddons.class.php');
abstract class SimpleAddons extends AbstractAddons
{
    private $name;
    private $hooklist = array();
    /**
     * getHooksList
     * 获取该插件的所有钩子列表
     */
    public function getHooksList($name)
    {
        $this->name = $name;
        $this->getHooksInfo();

        return $this->hooklist;
    }

    //管理面板
    public function adminMenu()
    {
        return array();
    }

    //注册hook位该执行的方法
    public function apply($hook, $method)
    {
        $this->hooklist[$hook][$this->name][] = $method;
    }

    public function start()
    {
        return true;
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }
}
