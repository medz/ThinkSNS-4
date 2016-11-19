<?php
/**
 * TS插件 - 天气预报插件
 * @author 程序_小时代
 * @version TS3.0
 */
class InviteTestAddons extends NormalAddons
{
    protected $version = '1.0';
    protected $author = 'while';
    protected $site = 'master@xiew.net';
    protected $info = '邀请内测插件（官方版） - 以发放邀请码的方式邀请用户测试';
    protected $pluginName = '邀请内测 - 官方版';
    protected $tsVersion = '4.0';

    /**
     * 获的改插件使用了那些钩子聚合类
     */
    public function getHooksInfo()
    {
        $hooks['list'] = array('InviteTestHooks');

        return $hooks;
    }

    /**
     * 后台管理入口
     * @return array 管理相关数据
     */
    public function adminMenu()
    {
        $menu = array(
            'invite' => '邀请码管理',
            'addinvite' => '添加邀请码',
            'config' => '邀请配置',
        );

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
        $sqls[] = "DROP TABLE IF EXISTS `{$dbPrefix}invite_test`;";
        $sqls[] = "CREATE TABLE `{$dbPrefix}invite_test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NULL DEFAULT '0' COMMENT '绑定用户',
  `hash` varchar(32) NULL DEFAULT '' COMMENT '请求hash',
  `code` varchar(32) NULL DEFAULT '' COMMENT '邀请码',
  `utime` int(10) unsigned NULL DEFAULT '0' COMMENT '最新使用时间',
  `is_disable` tinyint(3) unsigned NULL DEFAULT '0' COMMENT '是否禁用',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='邀请测试表';";
        foreach ($sqls as $sql) {
            D()->execute($sql);
        }

        return true;
    }

    /**
     * 插件卸载
     * @return bool
     */
    public function uninstall()
    {
        $dbPrefix = C('DB_PREFIX');
        echo "DROP TABLE IF EXISTS `{$dbPrefix}invite_test`;";
        D()->execute("DROP TABLE IF EXISTS `{$dbPrefix}invite_test`;");

        return true;
    }
}
