<?php

class FeedTopHomeAddons extends NormalAddons
{
    protected $version = '1.0';
    protected $author = '智士软件';
    protected $site = 'http://www.thinksns.com';
    protected $info = '空间分享置顶';
    protected $pluginName = '空间分享置顶';
    protected $sqlfile = '暂无';
    protected $tsVersion = '3.0';

    public function getHooksInfo()
    {
        $hooks['list'] = array('FeedTopHomeHooks');

        return $hooks;
    }

    public function adminMenu()
    {
    }

    public function start()
    {
    }

    public function install()
    {
        $db_prefix = C('DB_PREFIX');
        $sql = "CREATE TABLE `{$db_prefix}feed_top_home` (
				`feed_top_home_id` int(11) NOT NULL AUTO_INCREMENT,
				`uid` int(11) NOT NULL,
				`feed_id` int(11) NOT NULL,
				`ctime` int(11) NOT NULL,
				PRIMARY KEY (`feed_top_home_id`),
				UNIQUE KEY `feed_id_UNIQUE` (`feed_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        D()->execute($sql);

        return true;
    }

    public function uninstall()
    {
        $db_prefix = C('DB_PREFIX');
        $sql = "DROP TABLE `{$db_prefix}feed_top_home`;";
        D()->execute($sql);

        return true;
    }
}
