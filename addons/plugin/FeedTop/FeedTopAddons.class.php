<?php
/**
 * TS插件 - 分享置顶
 * @author 云脉网
 * @version TS3.0
 */
class FeedTopAddons extends NormalAddons
{
    protected $version = '1.3';
    protected $author = '云脉网';
    protected $site = '';
    protected $info = '分享置顶';
    protected $pluginName = '分享置顶';
    protected $sqlfile = '暂无';
    protected $tsVersion = '3.0';

    /**
     * 获的改插件使用了那些钩子聚合类，那些钩子是需要进行排序的
     */
    public function getHooksInfo()
    {
        $hooks['list'] = array('FeedTopHooks');

        return $hooks;
    }
    /**
     * 后台管理入口
     * @return array 管理相关数据
     */
    public function adminMenu()
    {
        $menu = array();
        $menu['config'] = '分享置顶管理';

        return $menu;
    }
    public function start()
    {
    }
    public function install()
    {
        // 插入数据表
        $db_prefix = C('DB_PREFIX');
        $sql = "CREATE TABLE `{$db_prefix}feed_top` (
			  `id` int(11) NOT NULL auto_increment,
			  `feed_id` int(11) NOT NULL,
			  `title` varchar(255) default NULL,
			  `status` tinyint(1) NOT NULL default '0',
			  `ctime` int(11) default NULL,
			  PRIMARY KEY  (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";
        D()->execute($sql);

        return true;
    }
    public function uninstall()
    {
        // 卸载数据表
        $db_prefix = C('DB_PREFIX');
        $sql = "DROP TABLE `{$db_prefix}feed_top`;";
        D()->execute($sql);

        return true;
    }
}
