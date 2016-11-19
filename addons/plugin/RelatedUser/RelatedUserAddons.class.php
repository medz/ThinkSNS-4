<?php

class RelatedUserAddons extends NormalAddons
{
    protected $version = '3.0';
    protected $author = '智士软件';
    protected $site = 'http://www.thinksns.com';
    protected $info = '根据当前用户推荐可能感兴趣的人';
    protected $pluginName = '可能感兴趣的人';
    protected $sqlfile = '暂无';
    protected $tsVersion = '3.0';

    public function getHooksInfo()
    {
        $hooks['list'] = array('RelatedUserHooks');

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
