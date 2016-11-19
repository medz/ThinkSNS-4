<?php
/**
 * 投稿插件
 * @author Stream
 *
 */
class ContributeAddons extends NormalAddons
{
    protected $version = '3.0';
    protected $author = 'thinksns';
    protected $site = 'http://www.thinksns.com';
    protected $info = '向频道管理员投稿';
    protected $pluginName = '分享投稿';
    protected $sqlfile = '暂无';
    protected $tsVersion = '3.0';
    public function getHooksInfo()
    {
        $hooks['list'] = array('ContributeHooks');

        return $hooks;
    }

    public function start()
    {
    }

    public function install()
    {
        return true;
    }

    public function uninstall()
    {
        return true;
    }

    public function adminMenu()
    {
    }
}
