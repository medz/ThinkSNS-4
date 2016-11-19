<?php
/**
 * 广告位插件
 * @author zivss <guolee226@gmail.com>
 * @version TS3.0
 */
class AdSpaceAddons extends NormalAddons
{
    protected $version = '1.0';
    protected $author = '智士软件';
    protected $site = 'http://www.thinksns.com';
    protected $info = '广告位官方版';
    protected $pluginName = '广告位 - 官方版';
    protected $tsVersion = '3.0';

    /**
     * 获取该插件使用钩子
     * @return array 钩子信息数组
     */
    public function getHooksInfo()
    {
        $hooks['list'] = array('AdSpaceHooks');

        return $hooks;
    }

    /**
     * 插件后台管理入口
     * @return array 管理相关数据
     */
    public function adminMenu()
    {
        $menu = array();
        $menu['config'] = '广告位管理';
        $menu['addAdSpace'] = '添加广告位';
        $page = isset($_GET['page']) ? t($_GET['page']) : 'addAdSpace';
        if ($page === 'editAdSpace') {
            unset($menu['addAdSpace']);
            $menu['editAdSpace'] = array('content' => '编辑广告位', 'param' => array('id' => intval($_GET['id'])));
        }

        return $menu;
    }

    public function start()
    {
    }

    /**
     * 插件安装入口
     * @return bool 是否安装成功
     */
    public function install()
    {
        // 插入数据表
        $dbPrefix = C('DB_PREFIX');
        $sql = "CREATE TABLE `{$dbPrefix}ad` (
				  `ad_id` int(11) NOT NULL AUTO_INCREMENT COMMENT '广告ID，主键',
				  `title` varchar(255) DEFAULT NULL COMMENT '广告标题',
				  `place` tinyint(1) NOT NULL DEFAULT '0' COMMENT '广告位置：0-中部；1-头部；2-左下；3-右下；4-底部；5-右上；',
				  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '是否有效；0-无效；1-有效；',
				  `is_closable` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否关闭，目前没有使用。',
				  `ctime` int(11) DEFAULT NULL COMMENT '创建时间',
				  `mtime` int(11) DEFAULT NULL COMMENT '更新时间',
				  `display_order` smallint(2) NOT NULL DEFAULT '0' COMMENT '排序值',
				  `display_type` tinyint(1) unsigned DEFAULT '1' COMMENT '广告类型：1 - HTML；2 - 代码；3 - 轮播',
				  `content` text COMMENT '广告位内容',
				  PRIMARY KEY (`ad_id`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='广告位表';";
        D()->execute($sql);

        return true;
    }

    /**
     * 插件卸载入口
     * @return bool 是否卸载成功
     */
    public function uninstall()
    {
        // 卸载数据表
        $dbPrefix = C('DB_PREFIX');
        $sql = "DROP TABLE IF EXISTS `{$dbPrefix}ad`;";
        D()->execute($sql);

        return true;
    }
}
